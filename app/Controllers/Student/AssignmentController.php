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
use App\Repositories\AssignmentRepository;
use App\Repositories\EnrollmentRepository;
use App\Repositories\StudentRepository;
use App\Services\AssignmentService;

/**
 * Controller for Student Coursework Discovery & Assignment Overview
 */
class AssignmentController extends Controller
{
    private AssignmentService $assignmentService;
    private AssignmentRepository $assignmentRepository;
    private AcademicRepository $academicRepository;
    private StudentRepository $studentRepository;
    private EnrollmentRepository $enrollmentRepository;

    public function __construct(
        ?AuthenticatorInterface $authenticator = null,
        ?AssignmentService $assignmentService = null,
        ?AssignmentRepository $assignmentRepository = null,
        ?AcademicRepository $academicRepository = null,
        ?StudentRepository $studentRepository = null,
        ?EnrollmentRepository $enrollmentRepository = null
    ) {
        parent::__construct($authenticator);
        $this->assignmentService = $assignmentService ?? new AssignmentService();
        $this->assignmentRepository = $assignmentRepository ?? new AssignmentRepository();
        $this->academicRepository = $academicRepository ?? new AcademicRepository();
        $this->studentRepository = $studentRepository ?? new StudentRepository();
        $this->enrollmentRepository = $enrollmentRepository ?? new EnrollmentRepository();
    }

    /**
     * List assignments for enrolled student.
     * Route: GET /student/assignments
     */
    public function index(Request $request): Response
    {
        $userContext = $this->authenticator->getUserContext();
        $student = $this->studentRepository->findByUserId($userContext->id);

        if (!$student) {
            return $this->view('errors/403', ['message' => 'Student profile not found.'], 403);
        }

        $activeSession = $this->academicRepository->getActiveSession();
        $termId = $request->query('term_id') !== null ? (int)$request->query('term_id') : null;

        $data = $this->assignmentService->getStudentAssignments($userContext, $termId);

        return $this->view('student/assignments/index', [
            'activeAssignments' => $data['active'] ?? [],
            'pastDueAssignments' => $data['past_due'] ?? [],
            'submissions' => $data['submissions'] ?? [],
            'activeSession' => $activeSession,
            'student' => $student,
            'user' => $userContext,
        ]);
    }

    /**
     * Show assignment details and student's submission status.
     * Route: GET /student/assignments/{id}
     */
    public function show(Request $request, array $params): Response
    {
        $userContext = $this->authenticator->getUserContext();
        $id = (int)($params['id'] ?? 0);

        $assignment = $this->assignmentRepository->findById($id);
        if (!$assignment) {
            return $this->view('errors/404', ['message' => 'Assignment not found.'], 404);
        }

        if (!AssignmentPolicy::canViewAssignment(
            $userContext,
            $assignment,
            $this->academicRepository,
            null,
            $this->studentRepository,
            $this->enrollmentRepository
        )) {
            return $this->view('errors/403', ['message' => 'You are not authorized to view this assignment.'], 403);
        }

        $student = $this->studentRepository->findByUserId($userContext->id);
        $submission = $student ? $this->assignmentRepository->findSubmissionByAssignmentAndStudent($id, $student->id) : null;

        return $this->view('student/assignments/show', [
            'assignment' => $assignment,
            'submission' => $submission,
            'student' => $student,
            'user' => $userContext,
        ]);
    }
}
