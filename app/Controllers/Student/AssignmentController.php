<?php

declare(strict_types=1);

namespace App\Controllers\Student;

use App\Controllers\Controller;
use App\Core\AuthenticatorInterface;
use App\Core\Exceptions\AuthorizationException;
use App\Core\Exceptions\ResourceNotFoundException;
use App\Core\Request;
use App\Core\Response;
use App\Policies\AssignmentPolicy;
use App\Repositories\AcademicRepository;
use App\Models\ActivityProgress;
use App\Repositories\AssignmentRepository;
use App\Repositories\EnrollmentRepository;
use App\Repositories\StudentRepository;
use App\Services\AssignmentService;
use App\Services\PrerequisiteService;

/**
 * Controller for Student Coursework Discovery & Assignment Overview
 */
class AssignmentController extends Controller
{
    private AssignmentService $assignmentService;
    private AssignmentRepository $assignmentRepo;
    private AcademicRepository $academicRepo;
    private StudentRepository $studentRepo;
    private EnrollmentRepository $enrollmentRepo;
    private PrerequisiteService $prerequisiteService;

    public function __construct(
        ?AuthenticatorInterface $authenticator = null,
        ?AssignmentService $assignmentService = null,
        ?AssignmentRepository $assignmentRepo = null,
        ?AcademicRepository $academicRepo = null,
        ?StudentRepository $studentRepo = null,
        ?EnrollmentRepository $enrollmentRepo = null,
        ?PrerequisiteService $prerequisiteService = null
    ) {
        parent::__construct($authenticator);
        $this->assignmentService = $assignmentService ?? new AssignmentService();
        $this->assignmentRepo = $assignmentRepo ?? new AssignmentRepository();
        $this->academicRepo = $academicRepo ?? new AcademicRepository();
        $this->studentRepo = $studentRepo ?? new StudentRepository();
        $this->enrollmentRepo = $enrollmentRepo ?? new EnrollmentRepository();
        $this->prerequisiteService = $prerequisiteService ?? new PrerequisiteService();
    }

    /**
     * List assignments for enrolled student.
     * Route: GET /student/assignments
     */
    public function index(Request $request): Response
    {
        $userContext = $this->requireAuthContext($request);
        $student = $this->studentRepo->findByUserId($userContext->id);

        if (!$student && !$userContext->isAdmin()) {
            return Response::forbidden('Student profile not found.');
        }

        $activeSession = $this->academicRepo->findCurrentSession();
        $activeTerm = $this->academicRepo->findCurrentTerm();
        $termId = $request->get('term_id') !== null ? (int)$request->get('term_id') : ($activeTerm?->id ?? null);

        $data = $this->assignmentService->getStudentAssignments($userContext, $termId);

        return Response::html($this->render('student/assignments/index', [
            'title' => 'Coursework & Assignments — Student Portal',
            'headerTitle' => 'Coursework & Assignments Tracker',
            'user' => $userContext,
            'student' => $student,
            'activeSession' => $activeSession,
            'activeTerm' => $activeTerm,
            'activeAssignments' => $data['active'] ?? [],
            'pastDueAssignments' => $data['past_due'] ?? [],
            'submissions' => $data['submissions'] ?? [],
        ], 'layouts/student'));
    }

    /**
     * Show assignment details and student's submission status.
     * Route: GET /student/assignments/{id}
     */
    public function show(Request $request, array|string|int $id): Response
    {
        $userContext = $this->requireAuthContext($request);
        $assignmentId = is_array($id) ? (int)($id['id'] ?? 0) : (int)$id;

        $assignment = $this->assignmentRepo->findById($assignmentId);
        if (!$assignment) {
            return Response::notFound('Assignment coursework not found.');
        }

        if (!AssignmentPolicy::canViewAssignment(
            $userContext,
            $assignment,
            $this->academicRepo,
            null,
            $this->studentRepo,
            $this->enrollmentRepo
        )) {
            return Response::forbidden('You are not authorized to view this assignment.');
        }

        $student = $this->studentRepo->findByUserId($userContext->id);
        $submission = $student ? $this->assignmentRepo->findSubmissionByAssignmentAndStudent($assignmentId, $student->id) : null;

        // Prerequisite evaluation
        $prerequisiteStatus = null;
        $isUnlocked = true;
        if ($student) {
            $prerequisiteStatus = $this->prerequisiteService->getPrerequisiteStatus(
                studentId: $student->id,
                activityType: ActivityProgress::TYPE_ASSIGNMENT,
                activityId: $assignmentId
            );
            $isUnlocked = $prerequisiteStatus['is_unlocked'];
        }

        return Response::html($this->render('student/assignments/show', [
            'title' => "{$assignment->title} — Coursework Task",
            'headerTitle' => 'Coursework Task',
            'user' => $userContext,
            'student' => $student,
            'assignment' => $assignment,
            'submission' => $submission,
            'isUnlocked' => $isUnlocked,
            'prerequisiteStatus' => $prerequisiteStatus,
        ], 'layouts/student'));
    }
}
