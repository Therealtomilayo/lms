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

        // 1. Classes where teacher is the designated Class Teacher (Form Teacher)
        $formClasses = $teacherId !== null
            ? $this->academicRepo->getClassesByFormTeacherId($teacherId)
            : [];
        if ($userContext->isAdmin() && empty($formClasses)) {
            $formClasses = $this->academicRepo->getAllClasses();
        }

        $formClassIds = array_map(fn($c) => (int)$c->id, $formClasses);
        $assignedClassesData = [];
        $uniqueStudentIds = [];
        $distinctClassIds = [];

        foreach ($formClasses as $fc) {
            $students = $this->enrollmentRepo->getStudentsByClassAndSession($fc->id, $sessionId);
            $enrolledCount = count($students);

            foreach ($students as $st) {
                $uniqueStudentIds[$st->id] = true;
            }
            $distinctClassIds[$fc->id] = true;

            $assignedClassesData[] = [
                'class' => $fc,
                'enrolledCount' => $enrolledCount,
            ];
        }

        // 2. Subject Allocations where teacher is the Subject Teacher
        $classSubjects = $teacherId !== null
            ? $this->academicRepo->findClassSubjectsByTeacherId($teacherId)
            : $this->academicRepo->findAllClassSubjects();

        $subjectAllocationsData = [];
        $cohortData = [];

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

            $isClassTeacherForThisClass = in_array((int)$cs->classId, $formClassIds, true) || $userContext->isAdmin();

            $row = [
                'classSubject' => $cs,
                'enrolledCount' => $enrolledCount,
                'class' => $cs->schoolClass,
                'subject' => $cs->subject,
                'isClassTeacher' => $isClassTeacherForThisClass,
            ];
            $subjectAllocationsData[] = $row;
            $cohortData[] = $row;
        }

        return Response::html($this->render('teacher/classes/index', [
            'title' => 'My Classes & Student Rosters — Claret Faculty',
            'headerTitle' => 'My Classes & Rosters',
            'user' => $userContext,
            'teacher' => $teacher,
            'activeSession' => $activeSession,
            'activeTerm' => $activeTerm,
            'assignedClassesData' => $assignedClassesData,
            'subjectAllocationsData' => $subjectAllocationsData,
            'cohortData' => $cohortData,
            'totalAssignedClasses' => count($assignedClassesData),
            'totalSubjectAllocations' => count($subjectAllocationsData),
            'totalCohorts' => count($cohortData),
            'totalStudents' => count($uniqueStudentIds),
            'totalClasses' => count($distinctClassIds),
        ], 'layouts/teacher'));
    }

    /**
     * Show detailed candidate student roster for an assigned class-subject allocation
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
            return $this->notFound('Class subject allocation not found.');
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

        $schoolClass = $this->academicRepo->findClassById($classSubject->classId) ?? $classSubject->schoolClass;
        $isClassTeacher = $userContext->isAdmin() || (
            $teacherId !== null &&
            $schoolClass !== null &&
            (int)$schoolClass->formTeacherId === (int)$teacherId
        );

        $roster = [];
        $maleCount = 0;
        $femaleCount = 0;
        $guardiansLinkedCount = 0;

        foreach ($students as $student) {
            // Privacy rule: Only assigned Class Teacher or Admin can view student parent contacts
            $guardians = $isClassTeacher ? $this->parentRepo->getGuardiansForStudent($student->id) : [];
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

        $className = method_exists($schoolClass, 'getFullName') ? $schoolClass->getFullName() : ($schoolClass?->name ?? 'Class');
        $subjectName = $classSubject->subject?->name ?? 'Subject';

        return Response::html($this->render('teacher/classes/show', [
            'title' => "{$className} — {$subjectName} Roster — Claret Faculty",
            'headerTitle' => "{$className} Roster",
            'user' => $userContext,
            'teacher' => $teacher,
            'classSubject' => $classSubject,
            'class' => $schoolClass,
            'isClassTeacher' => $isClassTeacher,
            'canViewParentContacts' => $isClassTeacher,
            'activeSession' => $activeSession,
            'activeTerm' => $activeTerm,
            'roster' => $roster,
            'totalStudents' => count($roster),
            'maleCount' => $maleCount,
            'femaleCount' => $femaleCount,
            'guardiansLinkedCount' => $guardiansLinkedCount,
        ], 'layouts/teacher'));
    }

    /**
     * Show full class roster for assigned Class Teacher
     * Route: GET /teacher/classes/class/{classId}
     */
    public function showClass(Request $request, array|string|int $classId): Response
    {
        $userContext = $this->requireAuthContext($request);
        $teacher = $this->teacherRepo->findTeacherByUserId($userContext->id);
        $teacherId = $teacher ? $teacher->id : null;

        if (!$teacherId && !$userContext->isAdmin()) {
            throw new AuthorizationException('Teacher profile required.');
        }

        $cId = is_array($classId) 
            ? (int)($classId['classId'] ?? $classId['id'] ?? 0) 
            : (int)$classId;

        $class = $this->academicRepo->findClassById($cId);
        if (!$class) {
            return $this->notFound('Class not found.');
        }

        $isClassTeacher = $userContext->isAdmin() || (
            $teacherId !== null && (int)$class->formTeacherId === (int)$teacherId
        );

        if (!$isClassTeacher) {
            return $this->forbidden('Only the assigned Class Teacher can access the full class roster.');
        }

        $activeSession = $this->academicRepo->findCurrentSession();
        $activeTerm = $this->academicRepo->findCurrentTerm();
        $sessionId = $activeSession?->id ?? 0;

        $students = $this->enrollmentRepo->getStudentsByClassAndSession($cId, $sessionId);

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

        $className = method_exists($class, 'getFullName') ? $class->getFullName() : $class->name;

        return Response::html($this->render('teacher/classes/show', [
            'title' => "{$className} — Class Roster (Class Teacher) — Claret Faculty",
            'headerTitle' => "{$className} Class Roster",
            'user' => $userContext,
            'teacher' => $teacher,
            'class' => $class,
            'classSubject' => null,
            'isClassTeacher' => true,
            'canViewParentContacts' => true,
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
