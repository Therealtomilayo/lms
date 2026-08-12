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
        $userContext = $this->authenticator->getUserContext();
        $teacher = $this->teacherRepository->findTeacherByUserId($userContext->id);

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

        return $this->view('teacher/assignments/index', [
            'assignments' => $assignments,
            'submissionCounts' => $submissionCounts,
            'gradedCounts' => $gradedCounts,
            'activeSession' => $activeSession,
            'user' => $userContext,
        ]);
    }

    /**
     * Show form to create an assignment.
     * Route: GET /teacher/assignments/create
     */
    public function create(Request $request): Response
    {
        $userContext = $this->authenticator->getUserContext();
        $teacher = $this->teacherRepository->findTeacherByUserId($userContext->id);
        $activeSession = $this->academicRepository->getActiveSession();

        $teacherId = $teacher ? $teacher->id : 0;
        $classSubjects = $teacher
            ? $this->academicRepository->getClassSubjectsByTeacher($teacherId, $activeSession?->id)
            : $this->academicRepository->getAllClassSubjects($activeSession?->id);

        $terms = $activeSession ? $this->academicRepository->getTermsBySession($activeSession->id) : [];

        $presetClassSubjectId = (int)$request->query('class_subject_id', 0);

        return $this->view('teacher/assignments/create', [
            'classSubjects' => $classSubjects,
            'terms' => $terms,
            'presetClassSubjectId' => $presetClassSubjectId,
            'activeSession' => $activeSession,
            'user' => $userContext,
        ]);
    }

    /**
     * Store a new assignment.
     * Route: POST /teacher/assignments/create
     */
    public function store(Request $request): Response
    {
        $userContext = $this->authenticator->getUserContext();
        $postData = $request->post();
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
    public function edit(Request $request, array $params): Response
    {
        $userContext = $this->authenticator->getUserContext();
        $id = (int)($params['id'] ?? 0);

        $assignment = $this->assignmentRepository->findById($id);
        if (!$assignment) {
            return $this->view('errors/404', ['message' => 'Assignment not found.'], 404);
        }

        $activeSession = $this->academicRepository->getActiveSession();
        $terms = $activeSession ? $this->academicRepository->getTermsBySession($activeSession->id) : [];

        return $this->view('teacher/assignments/edit', [
            'assignment' => $assignment,
            'terms' => $terms,
            'user' => $userContext,
        ]);
    }

    /**
     * Update an existing assignment.
     * Route: POST /teacher/assignments/{id}/edit
     */
    public function update(Request $request, array $params): Response
    {
        $userContext = $this->authenticator->getUserContext();
        $id = (int)($params['id'] ?? 0);
        $postData = $request->post();
        $files = $request->files();
        $uploadedFile = $files['attachment'] ?? null;

        try {
            $this->assignmentService->updateAssignment($id, $postData, $uploadedFile, $userContext);

            return $this->redirectWithSuccess(
                '/teacher/assignments',
                'Assignment updated successfully.'
            );
        } catch (ValidationException $e) {
            return $this->redirectWithErrors("/teacher/assignments/{$id}/edit", $e->getErrors(), $postData);
        } catch (ResourceNotFoundException $e) {
            return $this->view('errors/404', ['message' => $e->getMessage()], 404);
        } catch (AuthorizationException $e) {
            return $this->view('errors/403', ['message' => $e->getMessage()], 403);
        } catch (\Throwable $e) {
            return $this->redirectWithError("/teacher/assignments/{$id}/edit", $e->getMessage());
        }
    }

    /**
     * Delete or archive an assignment.
     * Route: POST /teacher/assignments/{id}/delete
     */
    public function delete(Request $request, array $params): Response
    {
        $userContext = $this->authenticator->getUserContext();
        $id = (int)($params['id'] ?? 0);

        try {
            $this->assignmentService->deleteAssignment($id, $userContext);

            return $this->redirectWithSuccess(
                '/teacher/assignments',
                'Assignment deleted or archived successfully.'
            );
        } catch (\Throwable $e) {
            return $this->redirectWithError('/teacher/assignments', $e->getMessage());
        }
    }
}
