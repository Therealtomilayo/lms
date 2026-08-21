<?php

declare(strict_types=1);

namespace App\Controllers\Teacher;

use App\Controllers\Controller;
use App\Core\AuthenticatorInterface;
use App\Core\Exceptions\AuthorizationException;
use App\Core\Exceptions\DomainRuleException;
use App\Core\Exceptions\ResourceNotFoundException;
use App\Core\Exceptions\ValidationException;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\AcademicRepository;
use App\Repositories\AssignmentRepository;
use App\Repositories\TeacherRepository;
use App\Services\AssignmentService;

/**
 * Controller for Teacher Coursework Assignment Creation & Management
 */
class AssignmentController extends Controller
{
    private AssignmentService $assignmentService;
    private AssignmentRepository $assignmentRepository;
    private AcademicRepository $academicRepository;
    private TeacherRepository $teacherRepository;

    public function __construct(
        ?AuthenticatorInterface $authenticator = null,
        ?AssignmentService $assignmentService = null,
        ?AssignmentRepository $assignmentRepository = null,
        ?AcademicRepository $academicRepository = null,
        ?TeacherRepository $teacherRepository = null
    ) {
        parent::__construct($authenticator);
        $this->assignmentService = $assignmentService ?? new AssignmentService();
        $this->assignmentRepository = $assignmentRepository ?? new AssignmentRepository();
        $this->academicRepository = $academicRepository ?? new AcademicRepository();
        $this->teacherRepository = $teacherRepository ?? new TeacherRepository();
    }

    /**
     * List assignments for teacher.
     * Route: GET /teacher/assignments
     */
    public function index(Request $request): Response
    {
        $userContext = $this->getUserContext($request);
        if (!$userContext) {
            return Response::redirect('/login');
        }

        $userId = $userContext->id ?? ($userContext->userId ?? 0);
        $teacher = $this->teacherRepository->findTeacherByUserId((int)$userId);

        if (!$teacher && !$userContext->hasAnyRole(['super_admin', 'admin'])) {
            return $this->view('errors/403', ['message' => 'Teacher profile not found.'], 403);
        }

        $activeSession = $this->academicRepository->getActiveSession();
        $assignments = $this->assignmentService->getTeacherAssignments($userContext, $activeSession?->id);

        $submissionCounts = [];
        $gradedCounts = [];
        foreach ($assignments as $assignment) {
            $submissionCounts[$assignment->id] = $this->assignmentRepository->countSubmissions($assignment->id);
            $gradedCounts[$assignment->id] = $this->assignmentRepository->countGradedSubmissions($assignment->id);
        }

        return Response::html($this->render('teacher/assignments/index', [
            'title' => 'Coursework Assignments — Claret Faculty Portal',
            'headerTitle' => 'Coursework Assignments & Grading',
            'assignments' => $assignments,
            'submissionCounts' => $submissionCounts,
            'gradedCounts' => $gradedCounts,
            'activeSession' => $activeSession,
            'user' => $userContext,
        ], 'layouts/teacher'));
    }

    /**
     * Show form to create an assignment.
     * Route: GET /teacher/assignments/create
     */
    public function create(Request $request): Response
    {
        $userContext = $this->getUserContext($request);
        if (!$userContext) {
            return Response::redirect('/login');
        }

        $userId = $userContext->id ?? ($userContext->userId ?? 0);
        $teacher = $this->teacherRepository->findTeacherByUserId((int)$userId);
        $activeSession = $this->academicRepository->getActiveSession();

        $teacherId = $teacher ? $teacher->id : 0;
        $classSubjects = $teacher
            ? $this->academicRepository->getClassSubjectsByTeacher($teacherId, $activeSession?->id)
            : $this->academicRepository->getAllClassSubjects($activeSession?->id);

        $terms = $activeSession ? $this->academicRepository->getTermsBySession($activeSession->id) : [];

        $presetClassSubjectId = (int)($request->query('class_subject_id', 0) ?: $request->get('class_subject_id', 0));

        return Response::html($this->render('teacher/assignments/create', [
            'title' => 'Create Assignment — Claret Faculty Portal',
            'headerTitle' => 'Create Assignment',
            'classSubjects' => $classSubjects,
            'terms' => $terms,
            'presetClassSubjectId' => $presetClassSubjectId,
            'activeSession' => $activeSession,
            'user' => $userContext,
        ], 'layouts/teacher'));
    }

    /**
     * Store a new assignment.
     * Route: POST /teacher/assignments/create
     */
    public function store(Request $request): Response
    {
        $userContext = $this->getUserContext($request);
        if (!$userContext) {
            return Response::redirect('/login');
        }

        $postData = $request->all();
        $files = $request->files();
        $uploadedFile = $files['attachment'] ?? null;

        try {
            $this->assignmentService->createAssignment($postData, $uploadedFile, $userContext);

            return $this->redirectWithSuccess(
                '/teacher/assignments',
                'Assignment published successfully.'
            );
        } catch (ValidationException $e) {
            return $this->redirectWithErrors('/teacher/assignments/create', $e->getErrors(), $postData);
        } catch (DomainRuleException $e) {
            return $this->redirectWithError('/teacher/assignments/create', $e->getMessage());
        } catch (AuthorizationException $e) {
            return $this->view('errors/403', ['message' => $e->getMessage()], 403);
        } catch (\Throwable $e) {
            return $this->redirectWithError('/teacher/assignments/create', $e->getMessage());
        }
    }

    /**
     * Show form to edit an assignment.
     * Route: GET /teacher/assignments/{id}/edit
     */
    public function edit(Request $request, array|string|int $id): Response
    {
        $userContext = $this->getUserContext($request);
        if (!$userContext) {
            return Response::redirect('/login');
        }

        $assignmentId = is_array($id) ? (int)($id['id'] ?? 0) : (int)$id;

        $assignment = $this->assignmentRepository->findById($assignmentId);
        if (!$assignment) {
            return $this->view('errors/404', ['message' => 'Assignment not found.'], 404);
        }

        $activeSession = $this->academicRepository->getActiveSession();
        $terms = $activeSession ? $this->academicRepository->getTermsBySession($activeSession->id) : [];

        return Response::html($this->render('teacher/assignments/edit', [
            'title' => 'Edit Assignment — Claret Faculty Portal',
            'headerTitle' => 'Edit Assignment',
            'assignment' => $assignment,
            'terms' => $terms,
            'user' => $userContext,
        ], 'layouts/teacher'));
    }

    /**
     * Update an existing assignment.
     * Route: POST /teacher/assignments/{id}/edit
     */
    public function update(Request $request, array|string|int $id): Response
    {
        $userContext = $this->getUserContext($request);
        $assignmentId = is_array($id) ? (int)($id['id'] ?? 0) : (int)$id;
        $postData = $request->all();
        $files = $request->files();
        $uploadedFile = $files['attachment'] ?? null;

        try {
            $this->assignmentService->updateAssignment($assignmentId, $postData, $uploadedFile, $userContext);

            return $this->redirectWithSuccess(
                '/teacher/assignments',
                'Assignment updated successfully.'
            );
        } catch (ValidationException $e) {
            return $this->redirectWithErrors("/teacher/assignments/{$assignmentId}/edit", $e->getErrors(), $postData);
        } catch (ResourceNotFoundException $e) {
            return $this->view('errors/404', ['message' => $e->getMessage()], 404);
        } catch (AuthorizationException $e) {
            return $this->view('errors/403', ['message' => $e->getMessage()], 403);
        } catch (\Throwable $e) {
            return $this->redirectWithError("/teacher/assignments/{$assignmentId}/edit", $e->getMessage());
        }
    }

    /**
     * Delete or archive an assignment.
     * Route: POST /teacher/assignments/{id}/delete
     */
    public function delete(Request $request, array|string|int $id): Response
    {
        $userContext = $this->getUserContext($request);
        $assignmentId = is_array($id) ? (int)($id['id'] ?? 0) : (int)$id;

        try {
            $this->assignmentService->deleteAssignment($assignmentId, $userContext);

            return $this->redirectWithSuccess(
                '/teacher/assignments',
                'Assignment deleted or archived successfully.'
            );
        } catch (\Throwable $e) {
            return $this->redirectWithError('/teacher/assignments', $e->getMessage());
        }
    }
}
