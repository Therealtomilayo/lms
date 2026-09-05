<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

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

/**
 * Controller for Administrative Batch Remarks & Behavioral Evaluation Workspace (ADMIN-32)
 */
class BatchRemarkController extends Controller
{
    private GradebookRepository $gradebookRepo;
    private SkillRepository $skillRepo;
    private AcademicRepository $academicRepo;
    private StudentRepository $studentRepo;
    private EnrollmentRepository $enrollmentRepo;

    public function __construct(
        ?AuthenticatorInterface $authenticator = null,
        ?GradebookRepository $gradebookRepo = null,
        ?SkillRepository $skillRepo = null,
        ?AcademicRepository $academicRepo = null,
        ?StudentRepository $studentRepo = null,
        ?EnrollmentRepository $enrollmentRepo = null
    ) {
        parent::__construct($authenticator);
        $this->gradebookRepo = $gradebookRepo ?? new GradebookRepository();
        $this->skillRepo = $skillRepo ?? new SkillRepository();
        $this->academicRepo = $academicRepo ?? new AcademicRepository();
        $this->studentRepo = $studentRepo ?? new StudentRepository();
        $this->enrollmentRepo = $enrollmentRepo ?? new EnrollmentRepository();
    }

    /**
     * Batch Comments & Behavioral Ratings Workspace
     * Route: GET /admin/results/comments
     */
    public function index(Request $request): Response
    {
        $userContext = $this->requireAuthContext($request);
        if (!$userContext->isAdmin()) {
            throw new AuthorizationException('Administrator access required.');
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

        $allClasses = $this->academicRepo->getAllClasses();
        $selectedClassId = (int)($request->get('class_id') ?: ($allClasses[0]->id ?? 0));
        $selectedClass = $selectedClassId > 0 ? $this->academicRepo->findClassById($selectedClassId) : null;

        // Fetch students enrolled in this class
        $students = [];
        if ($selectedClassId > 0) {
            $students = $this->studentRepo->getAll(limit: 500, classId: $selectedClassId);
            if (empty($students) && $selectedSessionId > 0) {
                $roster = $this->enrollmentRepo->getClassRoster($selectedClassId, $selectedSessionId);
                $students = array_filter(array_map(fn($ce) => $ce->student, $roster));
            }
        }

        // Fetch existing term summaries (contains teacher and principal remarks)
        $summaries = ($selectedClassId > 0 && $selectedTermId > 0)
            ? $this->gradebookRepo->getSummariesByClassAndTerm($selectedClassId, $selectedTermId)
            : [];
        $summariesByStudentId = [];
        foreach ($summaries as $s) {
            $summariesByStudentId[$s->studentId] = $s;
        }

        // Fetch skills & ratings matrix
        $skills = $this->skillRepo->getAllSkills(null, 'active');
        $ratingsMatrix = ($selectedClassId > 0 && $selectedTermId > 0)
            ? $this->skillRepo->getClassRatingsMatrix($selectedClassId, $selectedTermId)
            : [];

        // Fetch configurable remark presets for quick suggestion insertion
        $teacherPresets = $this->skillRepo->getRemarkPresets('teacher', true);
        $principalPresets = $this->skillRepo->getRemarkPresets('principal', true);

        return Response::html($this->render('admin/results/comments', [
            'user' => $userContext,
            'sessions' => $allSessions,
            'selectedSessionId' => $selectedSessionId,
            'terms' => $terms,
            'selectedTermId' => $selectedTermId,
            'classes' => $allClasses,
            'selectedClassId' => $selectedClassId,
            'selectedClass' => $selectedClass,
            'students' => $students,
            'summariesByStudentId' => $summariesByStudentId,
            'skills' => $skills,
            'ratingsMatrix' => $ratingsMatrix,
            'teacherPresets' => $teacherPresets,
            'principalPresets' => $principalPresets,
            'flashSuccess' => \App\Core\Session::getFlash('success'),
            'flashError' => \App\Core\Session::getFlash('error'),
        ]));
    }

    /**
     * Batch Save Comments & Behavioral Ratings
     * Route: POST /admin/results/comments
     */
    public function save(Request $request): Response
    {
        $userContext = $this->requireAuthContext($request);
        if (!$userContext->isAdmin()) {
            throw new AuthorizationException('Administrator access required.');
        }

        $sessionId = (int)$request->post('session_id', 0);
        $termId = (int)$request->post('term_id', 0);
        $classId = (int)$request->post('class_id', 0);

        if ($termId <= 0 || $classId <= 0) {
            return $this->redirectWithError('/admin/results/comments', 'Valid class arm and academic term are required.');
        }

        $comments = (array)$request->post('comments', []);
        $ratings = (array)$request->post('ratings', []);

        // Process comments
        $remarksPayload = [];
        foreach ($comments as $studentId => $row) {
            if (!is_array($row)) {
                continue;
            }
            $remarksPayload[(int)$studentId] = [
                'teacher_remark' => isset($row['teacher_remark']) ? (string)$row['teacher_remark'] : null,
                'principal_remark' => isset($row['principal_remark']) ? (string)$row['principal_remark'] : null,
            ];
        }

        if (!empty($remarksPayload)) {
            $this->gradebookRepo->batchUpdateRemarks($termId, $classId, $remarksPayload);
        }

        // Process ratings
        if (!empty($ratings)) {
            $this->skillRepo->batchSaveRatings($termId, $ratings, $userContext->id);
        }

        $redirectUrl = "/admin/results/comments?session_id={$sessionId}&term_id={$termId}&class_id={$classId}";
        return $this->redirectWithSuccess($redirectUrl, 'Class remarks and behavioral ratings saved successfully.');
    }
}
