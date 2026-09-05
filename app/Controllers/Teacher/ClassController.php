<?php

declare(strict_types=1);

namespace App\Controllers\Teacher;

use App\Controllers\Controller;
use App\Core\AuthenticatorInterface;
use App\Core\Exceptions\AuthorizationException;
use App\Core\Request;
use App\Core\Response;
use App\Policies\GradebookPolicy;
use App\Repositories\AcademicRepository;
use App\Repositories\EnrollmentRepository;
use App\Repositories\ParentRepository;
use App\Repositories\TeacherRepository;

/**
 * Controller for Faculty Class Workspace & Student Rosters (TEACHER-25)
 */
class ClassController extends Controller
{
    private TeacherRepository $teacherRepo;
    private AcademicRepository $academicRepo;
    private EnrollmentRepository $enrollmentRepo;
    private ParentRepository $parentRepo;

    public function __construct(
        ?AuthenticatorInterface $authenticator = null,
        ?TeacherRepository $teacherRepo = null,
        ?AcademicRepository $academicRepo = null,
        ?EnrollmentRepository $enrollmentRepo = null,
        ?ParentRepository $parentRepo = null
    ) {
        parent::__construct($authenticator);
        $this->teacherRepo = $teacherRepo ?? new TeacherRepository();
        $this->academicRepo = $academicRepo ?? new AcademicRepository();
        $this->enrollmentRepo = $enrollmentRepo ?? new EnrollmentRepository();
        $this->parentRepo = $parentRepo ?? new ParentRepository();
    }

    /**
     * List all assigned teaching cohorts and student classes
     * Route: GET /teacher/classes
     */
    public function index(Request $request): Response
    {
        $userContext = $this->requireAuthContext($request);
        $teacher = $this->teacherRepo->findTeacherByUserId($userContext->id);
        $teacherId = $teacher ? $teacher->id : null;

        if (!$teacherId && !$userContext->isAdmin()) {
            throw new AuthorizationException('Teacher profile required.');
        }

        $activeSession = $this->academicRepo->findCurrentSession();
        $activeTerm = $this->academicRepo->findCurrentTerm();
        $sessionId = $activeSession?->id ?? 0;

        $classSubjects = $teacherId !== null
            ? $this->academicRepo->findClassSubjectsByTeacherId($teacherId)
            : $this->academicRepo->findAllClassSubjects();

        // Calculate cohort roster sizes & distinct student count
        $cohortData = [];
        $uniqueStudentIds = [];
        $distinctClassIds = [];

        foreach ($classSubjects as $cs) {
            $students = $this->enrollmentRepo->getStudentsBySubjectAndSession(
                $cs->classId,
                $cs->subjectId,
                $sessionId
            );
            $enrolledCount = count($students);

            foreach ($students as $st) {
                $uniqueStudentIds[$st->id] = true;
            }
            $distinctClassIds[$cs->classId] = true;

            $cohortData[] = [
                'classSubject' => $cs,
                'enrolledCount' => $enrolledCount,
                'class' => $cs->schoolClass,
                'subject' => $cs->subject,
            ];
        }

        return Response::html($this->render('teacher/classes/index', [
            'title' => 'My Classes & Student Rosters — Claret Faculty',
            'headerTitle' => 'My Classes & Rosters',
            'user' => $userContext,
            'teacher' => $teacher,
            'activeSession' => $activeSession,
            'activeTerm' => $activeTerm,
            'cohortData' => $cohortData,
            'totalCohorts' => count($cohortData),
            'totalStudents' => count($uniqueStudentIds),
            'totalClasses' => count($distinctClassIds),
        ], 'layouts/teacher'));
    }

    /**
     * Show detailed candidate student roster for an assigned class-subject cohort
     * Route: GET /teacher/classes/{classSubjectId}
     */
    public function show(Request $request, array|string|int $classSubjectId): Response
    {
        $userContext = $this->requireAuthContext($request);
        $teacher = $this->teacherRepo->findTeacherByUserId($userContext->id);
        $teacherId = $teacher ? $teacher->id : null;

        if (!$teacherId && !$userContext->isAdmin()) {
            throw new AuthorizationException('Teacher profile required.');
        }

        $csId = is_array($classSubjectId) 
            ? (int)($classSubjectId['classSubjectId'] ?? $classSubjectId['id'] ?? 0) 
            : (int)$classSubjectId;

        $classSubject = $this->academicRepo->findClassSubjectById($csId);
        if (!$classSubject) {
            return $this->notFound('Class subject cohort not found.');
        }

        if (!GradebookPolicy::canView($userContext, $classSubject, $this->teacherRepo)) {
            return $this->forbidden('You are not authorized to access this class roster.');
        }

        $activeSession = $this->academicRepo->findCurrentSession();
        $activeTerm = $this->academicRepo->findCurrentTerm();
        $sessionId = $activeSession?->id ?? $classSubject->sessionId;

        $students = $this->enrollmentRepo->getStudentsBySubjectAndSession(
            $classSubject->classId,
            $classSubject->subjectId,
            $sessionId
        );

        // Fetch guardian info for each candidate
        $roster = [];
        $maleCount = 0;
        $femaleCount = 0;
        $guardiansLinkedCount = 0;

        foreach ($students as $student) {
            $guardians = $this->parentRepo->getGuardiansForStudent($student->id);
            if (!empty($guardians)) {
                $guardiansLinkedCount++;
            }

            $gender = strtolower((string)$student->gender);
            if ($gender === 'male' || $gender === 'm') {
                $maleCount++;
            } elseif ($gender === 'female' || $gender === 'f') {
                $femaleCount++;
            }

            $roster[] = [
                'student' => $student,
                'guardians' => $guardians,
            ];
        }

        $className = $classSubject->schoolClass?->name ?? 'Class';
        $subjectName = $classSubject->subject?->name ?? 'Subject';

        return Response::html($this->render('teacher/classes/show', [
            'title' => "{$className} — {$subjectName} Roster — Claret Faculty",
            'headerTitle' => "{$className} Roster",
            'user' => $userContext,
            'teacher' => $teacher,
            'classSubject' => $classSubject,
            'activeSession' => $activeSession,
            'activeTerm' => $activeTerm,
            'roster' => $roster,
            'totalStudents' => count($roster),
            'maleCount' => $maleCount,
            'femaleCount' => $femaleCount,
            'guardiansLinkedCount' => $guardiansLinkedCount,
        ], 'layouts/teacher'));
    }
}
