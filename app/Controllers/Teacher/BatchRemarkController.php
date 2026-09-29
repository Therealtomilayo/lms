<?php

declare(strict_types=1);

namespace App\Controllers\Teacher;

use App\Controllers\Controller;
use App\Core\AuthenticatorInterface;
use App\Core\Exceptions\AuthorizationException;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\AcademicRepository;
use App\Repositories\EnrollmentRepository;
use App\Repositories\GradebookRepository;
use App\Repositories\SkillRepository;
use App\Repositories\StudentRepository;
use App\Repositories\TeacherRepository;

/**
 * Controller for Faculty Batch Student Remarks & Behavioral Ratings Workspace
 */
class BatchRemarkController extends Controller
{
    private TeacherRepository $teacherRepo;
    private GradebookRepository $gradebookRepo;
    private SkillRepository $skillRepo;
    private AcademicRepository $academicRepo;
    private StudentRepository $studentRepo;
    private EnrollmentRepository $enrollmentRepo;

    public function __construct(
        ?AuthenticatorInterface $authenticator = null,
        ?TeacherRepository $teacherRepo = null,
        ?GradebookRepository $gradebookRepo = null,
        ?SkillRepository $skillRepo = null,
        ?AcademicRepository $academicRepo = null,
        ?StudentRepository $studentRepo = null,
        ?EnrollmentRepository $enrollmentRepo = null
    ) {
        parent::__construct($authenticator);
        $this->teacherRepo = $teacherRepo ?? new TeacherRepository();
        $this->gradebookRepo = $gradebookRepo ?? new GradebookRepository();
        $this->skillRepo = $skillRepo ?? new SkillRepository();
        $this->academicRepo = $academicRepo ?? new AcademicRepository();
        $this->studentRepo = $studentRepo ?? new StudentRepository();
        $this->enrollmentRepo = $enrollmentRepo ?? new EnrollmentRepository();
    }

    /**
     * Teacher Class Comments & Ratings Interface
     * Route: GET /teacher/results/comments
     */
    public function index(Request $request): Response
    {
        $userContext = $this->requireAuthContext($request);
        $teacher = $this->teacherRepo->findTeacherByUserId($userContext->id);
        $teacherId = $teacher ? $teacher->id : null;

        if (!$teacherId && !$userContext->isAdmin()) {
            throw new AuthorizationException('Teacher profile required.');
        }

        $allSessions = $this->academicRepo->getAllSessions();
        $activeSession = $this->academicRepo->findCurrentSession() ?? $this->academicRepo->findActiveSession();
        $selectedSessionId = (int)($request->get('session_id') ?: ($activeSession?->id ?? ($allSessions[0]->id ?? 0)));

        $terms = $selectedSessionId > 0 ? $this->academicRepo->getTermsBySession($selectedSessionId) : [];
        $activeTerm = null;
        foreach ($terms as $t) {
            if ($t->status === 'active' || $t->status === 'open') {
                $activeTerm = $t;
                break;
            }
        }
        $selectedTermId = (int)($request->get('term_id') ?: ($activeTerm?->id ?? ($terms[0]->id ?? 0)));

        // Only assigned Class Teachers (Form Teachers) or Admins can access Batch Remarks
        if ($userContext->isAdmin()) {
            $classes = $this->academicRepo->getAllClasses();
        } else {
            $classes = $teacherId ? $this->academicRepo->getClassesByFormTeacherId($teacherId) : [];
        }

        $classIds = array_map(fn($c) => (int)$c->id, $classes);
        $requestedClassId = (int)$request->get('class_id', 0);
        $selectedClassId = ($requestedClassId > 0 && in_array($requestedClassId, $classIds, true))
            ? $requestedClassId
            : ($classes[0]->id ?? 0);
        $selectedClass = $selectedClassId > 0 ? $this->academicRepo->findClassById($selectedClassId) : null;

        // Students enrolled
        $students = [];
        if ($selectedClassId > 0) {
            $students = $this->studentRepo->getAll(limit: 500, classId: $selectedClassId);
            if (empty($students) && $selectedSessionId > 0) {
                $roster = $this->enrollmentRepo->getClassRoster($selectedClassId, $selectedSessionId);
                $students = array_filter(array_map(fn($ce) => $ce->student, $roster));
            }
        }

        // Summaries
        $summaries = ($selectedClassId > 0 && $selectedTermId > 0)
            ? $this->gradebookRepo->getSummariesByClassAndTerm($selectedClassId, $selectedTermId)
            : [];
        $summariesByStudentId = [];
        foreach ($summaries as $s) {
            $summariesByStudentId[$s->studentId] = $s;
        }

        // Skills & Ratings
        $skills = $this->skillRepo->getAllSkills(null, 'active');
        $ratingsMatrix = ($selectedClassId > 0 && $selectedTermId > 0)
            ? $this->skillRepo->getClassRatingsMatrix($selectedClassId, $selectedTermId)
            : [];

        // Teacher Remark Presets
        $teacherPresets = $this->skillRepo->getRemarkPresets('teacher', true);

        return Response::html($this->render('teacher/results/comments', [
            'title' => 'Batch Remarks & Behavioral Ratings — Claret Faculty Portal',
            'headerTitle' => 'Batch Remarks & Behavioral Ratings',
            'headerSubtitle' => 'Author termly teacher remarks with quick-suggestion templates and evaluate students on psychomotor & affective traits.',
            'user' => $userContext,
            'sessions' => $allSessions,
            'selectedSessionId' => $selectedSessionId,
            'terms' => $terms,
            'selectedTermId' => $selectedTermId,
            'classes' => $classes,
            'selectedClassId' => $selectedClassId,
            'selectedClass' => $selectedClass,
            'students' => $students,
            'summariesByStudentId' => $summariesByStudentId,
            'skills' => $skills,
            'ratingsMatrix' => $ratingsMatrix,
            'teacherPresets' => $teacherPresets,
            'flashSuccess' => \App\Core\Session::getFlash('success'),
            'flashError' => \App\Core\Session::getFlash('error'),
        ], 'layouts/teacher'));
    }

    /**
     * Batch Save Teacher Comments & Behavioral Ratings
     * Route: POST /teacher/results/comments
     */
    public function save(Request $request): Response
    {
        $userContext = $this->requireAuthContext($request);
        $teacher = $this->teacherRepo->findTeacherByUserId($userContext->id);
        if (!$teacher && !$userContext->isAdmin()) {
            throw new AuthorizationException('Teacher profile required.');
        }

        $sessionId = (int)$request->post('session_id', 0);
        $termId = (int)$request->post('term_id', 0);
        $classId = (int)$request->post('class_id', 0);

        if ($termId <= 0 || $classId <= 0) {
            return $this->redirectWithError('/teacher/results/comments', 'Valid class arm and academic term are required.');
        }

        $class = $this->academicRepo->findClassById($classId);
        if (!$class) {
            return $this->notFound('Class not found.');
        }

        // Authorization check: Only assigned Class Teacher or Admin can save batch remarks
        if (!$userContext->isAdmin() && (int)$class->formTeacherId !== (int)$teacher->id) {
            return $this->forbidden('Only the assigned Class Teacher can record remarks for this class.');
        }

        $comments = (array)$request->post('comments', []);
        $ratings = (array)$request->post('ratings', []);

        // Process comments (only updating teacher_remark)
        $remarksPayload = [];
        foreach ($comments as $studentId => $row) {
            if (!is_array($row) || !isset($row['teacher_remark'])) {
                continue;
            }
            $remarksPayload[(int)$studentId] = [
                'teacher_remark' => (string)$row['teacher_remark'],
            ];
        }

        if (!empty($remarksPayload)) {
            $this->gradebookRepo->batchUpdateRemarks($termId, $classId, $remarksPayload);
        }

        // Process ratings
        if (!empty($ratings)) {
            $this->skillRepo->batchSaveRatings($termId, $ratings, $userContext->id);
        }

        $redirectUrl = "/teacher/results/comments?session_id={$sessionId}&term_id={$termId}&class_id={$classId}";
        return $this->redirectWithSuccess($redirectUrl, 'Class teacher remarks and behavioral ratings saved successfully.');
    }

    /**
     * Dedicated Homeroom Affective & Psychomotor Evaluation Matrix
     * Route: GET /teacher/results/skills
     */
    public function skillsMatrix(Request $request): Response
    {
        $userContext = $this->requireAuthContext($request);
        $teacher = $this->teacherRepo->findTeacherByUserId($userContext->id);
        $teacherId = $teacher ? $teacher->id : null;

        if (!$teacherId && !$userContext->isAdmin()) {
            throw new AuthorizationException('Teacher profile required.');
        }

        $allSessions = $this->academicRepo->getAllSessions();
        $activeSession = $this->academicRepo->findCurrentSession() ?? $this->academicRepo->findActiveSession();
        $selectedSessionId = (int)($request->get('session_id') ?: ($activeSession?->id ?? ($allSessions[0]->id ?? 0)));

        $terms = $selectedSessionId > 0 ? $this->academicRepo->getTermsBySession($selectedSessionId) : [];
        $activeTerm = null;
        foreach ($terms as $t) {
            if ($t->status === 'active' || $t->status === 'open') {
                $activeTerm = $t;
                break;
            }
        }
        $selectedTermId = (int)($request->get('term_id') ?: ($activeTerm?->id ?? ($terms[0]->id ?? 0)));

        // Only assigned Class Teachers (Form Teachers) or Admins can access
        if ($userContext->isAdmin()) {
            $classes = $this->academicRepo->getAllClasses();
        } else {
            $classes = $teacherId ? $this->academicRepo->getClassesByFormTeacherId($teacherId) : [];
        }

        $classIds = array_map(fn($c) => (int)$c->id, $classes);
        $requestedClassId = (int)$request->get('class_id', 0);
        $selectedClassId = ($requestedClassId > 0 && in_array($requestedClassId, $classIds, true))
            ? $requestedClassId
            : ($classes[0]->id ?? 0);
        $selectedClass = $selectedClassId > 0 ? $this->academicRepo->findClassById($selectedClassId) : null;

        // Students enrolled
        $students = [];
        if ($selectedClassId > 0) {
            $students = $this->studentRepo->getAll(limit: 500, classId: $selectedClassId);
            if (empty($students) && $selectedSessionId > 0) {
                $roster = $this->enrollmentRepo->getClassRoster($selectedClassId, $selectedSessionId);
                $students = array_filter(array_map(fn($ce) => $ce->student, $roster));
            }
        }

        // Skills & Ratings
        $skills = $this->skillRepo->getAllSkills(null, 'active');
        $ratingsMatrix = ($selectedClassId > 0 && $selectedTermId > 0)
            ? $this->skillRepo->getClassRatingsMatrix($selectedClassId, $selectedTermId)
            : [];

        return Response::html($this->render('teacher/results/skills', [
            'title' => 'Affective & Psychomotor Evaluation Matrix — Claret Faculty Portal',
            'headerTitle' => 'Affective & Psychomotor Evaluation Matrix',
            'headerSubtitle' => 'Streamlined rating grid for homeroom teachers to assess students on affective and psychomotor domain traits.',
            'user' => $userContext,
            'sessions' => $allSessions,
            'selectedSessionId' => $selectedSessionId,
            'terms' => $terms,
            'selectedTermId' => $selectedTermId,
            'classes' => $classes,
            'selectedClassId' => $selectedClassId,
            'selectedClass' => $selectedClass,
            'students' => $students,
            'skills' => $skills,
            'ratingsMatrix' => $ratingsMatrix,
            'flashSuccess' => \App\Core\Session::getFlash('success'),
            'flashError' => \App\Core\Session::getFlash('error'),
        ], 'layouts/teacher'));
    }

    /**
     * Batch Save Homeroom Affective & Psychomotor Ratings
     * Route: POST /teacher/results/skills
     */
    public function saveSkillsMatrix(Request $request): Response
    {
        $userContext = $this->requireAuthContext($request);
        $teacher = $this->teacherRepo->findTeacherByUserId($userContext->id);
        if (!$teacher && !$userContext->isAdmin()) {
            throw new AuthorizationException('Teacher profile required.');
        }

        $sessionId = (int)$request->post('session_id', 0);
        $termId = (int)$request->post('term_id', 0);
        $classId = (int)$request->post('class_id', 0);

        if ($termId <= 0 || $classId <= 0) {
            return $this->redirectWithError('/teacher/results/skills', 'Valid class arm and academic term are required.');
        }

        $class = $this->academicRepo->findClassById($classId);
        if (!$class) {
            return $this->notFound('Class not found.');
        }

        // Authorization check: Only assigned Class Teacher or Admin can save
        if (!$userContext->isAdmin() && (int)$class->formTeacherId !== (int)$teacher->id) {
            return $this->forbidden('Only the assigned Class Teacher can record behavioral traits for this class.');
        }

        $ratings = (array)$request->post('ratings', []);
        if (!empty($ratings)) {
            $this->skillRepo->batchSaveRatings($termId, $ratings, $userContext->id);
        }

        $redirectUrl = "/teacher/results/skills?session_id={$sessionId}&term_id={$termId}&class_id={$classId}";
        return $this->redirectWithSuccess($redirectUrl, 'Affective and psychomotor domain ratings saved successfully.');
    }
}
