<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Core\Exceptions\DomainRuleException;
use App\Core\Exceptions\ResourceNotFoundException;
use App\Core\Exceptions\ValidationException;
use App\Core\Request;
use App\Core\Response;
use App\Core\UserContext;
use App\Policies\AcademicPolicy;
use App\Repositories\AcademicRepository;
use App\Repositories\EnrollmentRepository;
use App\Repositories\StudentRepository;
use App\Services\EnrollmentService;

/**
 * Controller for Student Class & Subject Enrollments Administration
 */
class EnrollmentController extends Controller
{
    private EnrollmentService $enrollmentService;
    private AcademicRepository $academicRepository;
    private EnrollmentRepository $enrollmentRepository;
    private StudentRepository $studentRepository;

    public function __construct(
        ?EnrollmentService $enrollmentService = null,
        ?AcademicRepository $academicRepository = null,
        ?EnrollmentRepository $enrollmentRepository = null,
        ?StudentRepository $studentRepository = null
    ) {
        $this->enrollmentService = $enrollmentService ?? new EnrollmentService();
        $this->academicRepository = $academicRepository ?? new AcademicRepository();
        $this->enrollmentRepository = $enrollmentRepository ?? new EnrollmentRepository();
        $this->studentRepository = $studentRepository ?? new StudentRepository();
    }

    public function index(Request $request): Response
    {
        $userContext = $request->getAttribute('user_context');
        if (!$userContext instanceof UserContext || !AcademicPolicy::canViewEnrollments($userContext)) {
            return $this->forbidden('Forbidden');
        }

        $sessions = $this->academicRepository->getAllSessions();
        $activeSession = $this->academicRepository->findActiveSession();
        $selectedSessionId = (int)$request->query('session_id', $activeSession ? $activeSession->id : ($sessions[0]->id ?? 0));

        $classes = $this->academicRepository->getAllClasses();
        $selectedClassId = (int)$request->query('class_id', $classes[0]->id ?? 0);

        $status = $request->query('status');

        $roster = [];
        if ($selectedSessionId > 0 && $selectedClassId > 0) {
            $roster = $this->enrollmentRepository->getClassRoster($selectedClassId, $selectedSessionId, $status ?: null);
        }

        $allStudents = $this->studentRepository->getAll(200);

        return $this->view('admin/enrollments/index', [
            'title' => 'Class Enrollments — Claret LMS',
            'headerTitle' => 'Student Class & Subject Enrollments',
            'sessions' => $sessions,
            'classes' => $classes,
            'selectedSessionId' => $selectedSessionId,
            'selectedClassId' => $selectedClassId,
            'selectedStatus' => $status,
            'roster' => $roster,
            'allStudents' => $allStudents,
            'canManage' => AcademicPolicy::canManageEnrollments($userContext),
        ]);
    }

    public function store(Request $request): Response
    {
        $userContext = $request->getAttribute('user_context');
        if (!$userContext instanceof UserContext || !AcademicPolicy::canManageEnrollments($userContext)) {
            return $this->forbidden('Forbidden');
        }

        $sessionId = (int)$request->post('session_id', 0);
        $classId = (int)$request->post('class_id', 0);
        $studentId = (int)$request->post('student_id', 0);
        $status = (string)$request->post('status', 'active');

        $redirectUrl = "/admin/enrollments?session_id={$sessionId}&class_id={$classId}";

        try {
            $this->enrollmentService->enrollStudentInClass($studentId, $classId, $sessionId, $status);
            return $this->redirectWithSuccess($redirectUrl, 'Student enrolled successfully and core subjects allocated.');
        } catch (ValidationException|DomainRuleException $e) {
            return $this->redirectWithError($redirectUrl, $e->getMessage());
        } catch (ResourceNotFoundException $e) {
            return Response::html($e->getMessage(), 404);
        }
    }

    public function bulk(Request $request): Response
    {
        $userContext = $request->getAttribute('user_context');
        if (!$userContext instanceof UserContext || !AcademicPolicy::canManageEnrollments($userContext)) {
            return $this->forbidden('Forbidden');
        }

        $sessionId = (int)$request->post('session_id', 0);
        $classId = (int)$request->post('class_id', 0);
        $studentIds = (array)($request->post('student_ids', []));

        $redirectUrl = "/admin/enrollments?session_id={$sessionId}&class_id={$classId}";

        try {
            $res = $this->enrollmentService->bulkEnrollClass($studentIds, $classId, $sessionId);
            $data = $res->getData();
            return $this->redirectWithSuccess(
                $redirectUrl,
                "Enrolled {$data['enrolled_count']} student(s) successfully into class."
            );
        } catch (ValidationException|DomainRuleException $e) {
            return $this->redirectWithError($redirectUrl, $e->getMessage());
        }
    }

    public function status(Request $request, string|int $id = 0): Response
    {
        $userContext = $request->getAttribute('user_context');
        if (!$userContext instanceof UserContext || !AcademicPolicy::canManageEnrollments($userContext)) {
            return $this->forbidden('Forbidden');
        }

        $id = (int)($id ?: $request->post('id', 0));
        $status = (string)$request->post('status', 'active');
        $sessionId = (int)$request->post('session_id', 0);
        $classId = (int)$request->post('class_id', 0);
        $redirectUrl = "/admin/enrollments?session_id={$sessionId}&class_id={$classId}";

        try {
            $this->enrollmentService->updateEnrollmentStatus($id, $status);
            return $this->redirectWithSuccess($redirectUrl, 'Enrollment status updated.');
        } catch (DomainRuleException|ValidationException $e) {
            return $this->redirectWithError($redirectUrl, $e->getMessage());
        } catch (ResourceNotFoundException $e) {
            return Response::html($e->getMessage(), 404);
        }
    }
}
