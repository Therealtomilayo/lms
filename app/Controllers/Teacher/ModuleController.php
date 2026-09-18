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
use App\Repositories\TeacherRepository;
use App\Services\ModuleService;

/**
 * Controller for Teacher Course Modules & Learning Progression Management
 */
class ModuleController extends Controller
{
    private ModuleService $moduleService;
    private AcademicRepository $academicRepo;
    private TeacherRepository $teacherRepo;

    public function __construct(
        ?AuthenticatorInterface $authenticator = null,
        ?ModuleService $moduleService = null,
        ?AcademicRepository $academicRepo = null,
        ?TeacherRepository $teacherRepo = null
    ) {
        parent::__construct($authenticator);
        $this->moduleService = $moduleService ?? new ModuleService();
        $this->academicRepo = $academicRepo ?? new AcademicRepository();
        $this->teacherRepo = $teacherRepo ?? new TeacherRepository();
    }

    /**
     * Display course modules organizer for a teacher's assigned subject.
     * Route: GET /teacher/modules
     */
    public function index(Request $request): Response
    {
        $userContext = $this->getUserContext($request);
        if (!$userContext) {
            return Response::redirect('/login');
        }

        $userId = $userContext->id ?? ($userContext->userId ?? 0);
        $teacher = $this->teacherRepo->findTeacherByUserId((int)$userId);

        if (!$teacher && !$userContext->hasAnyRole(['super_admin', 'admin'])) {
            return $this->view('errors/403', ['message' => 'Teacher profile not found.'], 403);
        }

        $activeSession = $this->academicRepo->getActiveSession();
        $teacherId = $teacher ? $teacher->id : 0;
        $classSubjects = $teacher
            ? $this->academicRepo->getClassSubjectsByTeacher($teacherId, $activeSession?->id)
            : $this->academicRepo->getAllClassSubjects($activeSession?->id);

        $selectedClassSubjectId = (int)($request->query('class_subject_id', 0) ?: $request->get('class_subject_id', 0));
        if ($selectedClassSubjectId <= 0 && !empty($classSubjects)) {
            $selectedClassSubjectId = $classSubjects[0]->id;
        }

        if ($request->query('tab') === 'progress' && $selectedClassSubjectId > 0) {
            return Response::redirect("/teacher/subjects/{$selectedClassSubjectId}/progress");
        }

        $modules = [];
        $availableActivities = ['documents' => [], 'quizzes' => [], 'assignments' => []];
        $selectedClassSubject = null;

        if ($selectedClassSubjectId > 0) {
            try {
                $selectedClassSubject = $this->academicRepo->findClassSubjectById($selectedClassSubjectId);
                $modules = $this->moduleService->getModulesForTeacher($selectedClassSubjectId, $userContext);
                $availableActivities = $this->moduleService->getAvailableActivitiesForSubject($selectedClassSubjectId, $userContext);
            } catch (AuthorizationException $e) {
                return $this->view('errors/403', ['message' => $e->getMessage()], 403);
            } catch (\Throwable $e) {
                // Keep empty on error
            }
        }

        return Response::html($this->render('teacher/modules/index', [
            'title' => 'Course Modules & Learning Progression — Claret Faculty Portal',
            'headerTitle' => 'Course Modules & Progression',
            'classSubjects' => $classSubjects,
            'selectedClassSubjectId' => $selectedClassSubjectId,
            'selectedClassSubject' => $selectedClassSubject,
            'modules' => $modules,
            'availableActivities' => $availableActivities,
            'activeSession' => $activeSession,
            'user' => $userContext,
        ], 'layouts/teacher'));
    }

    /**
     * Create a new module in a subject.
     * Route: POST /teacher/modules
     */
    public function store(Request $request): Response
    {
        $userContext = $this->getUserContext($request);
        if (!$userContext) {
            return Response::redirect('/login');
        }

        $data = $request->all();
        $classSubjectId = (int)($data['class_subject_id'] ?? 0);

        try {
            $this->moduleService->createModule($classSubjectId, $data, $userContext);
            return $this->redirectWithSuccess(
                "/teacher/modules?class_subject_id={$classSubjectId}",
                'Learning module created successfully.'
            );
        } catch (ValidationException $e) {
            return $this->redirectWithErrors("/teacher/modules?class_subject_id={$classSubjectId}", $e->getErrors(), $data);
        } catch (AuthorizationException $e) {
            return $this->view('errors/403', ['message' => $e->getMessage()], 403);
        } catch (\Throwable $e) {
            return $this->redirectWithError("/teacher/modules?class_subject_id={$classSubjectId}", $e->getMessage());
        }
    }

    /**
     * Update an existing module.
     * Route: POST /teacher/modules/{id}/edit
     */
    public function update(Request $request, array|string|int $id): Response
    {
        $userContext = $this->getUserContext($request);
        if (!$userContext) {
            return Response::redirect('/login');
        }

        $moduleId = is_array($id) ? (int)($id['id'] ?? 0) : (int)$id;
        $data = $request->all();
        $classSubjectId = (int)($data['class_subject_id'] ?? 0);

        try {
            $updated = $this->moduleService->updateModule($moduleId, $data, $userContext);
            $targetCsId = $classSubjectId > 0 ? $classSubjectId : $updated->classSubjectId;
            return $this->redirectWithSuccess(
                "/teacher/modules?class_subject_id={$targetCsId}",
                'Module updated successfully.'
            );
        } catch (ValidationException $e) {
            return $this->redirectWithErrors("/teacher/modules?class_subject_id={$classSubjectId}", $e->getErrors(), $data);
        } catch (AuthorizationException $e) {
            return $this->view('errors/403', ['message' => $e->getMessage()], 403);
        } catch (\Throwable $e) {
            return $this->redirectWithError("/teacher/modules?class_subject_id={$classSubjectId}", $e->getMessage());
        }
    }

    /**
     * Safely delete a module.
     * Route: POST /teacher/modules/{id}/delete
     */
    public function delete(Request $request, array|string|int $id): Response
    {
        $userContext = $this->getUserContext($request);
        if (!$userContext) {
            return Response::redirect('/login');
        }

        $moduleId = is_array($id) ? (int)($id['id'] ?? 0) : (int)$id;
        $classSubjectId = (int)($request->get('class_subject_id', 0) ?: $request->post('class_subject_id', 0));

        try {
            $this->moduleService->deleteModule($moduleId, $userContext, false);
            return $this->redirectWithSuccess(
                "/teacher/modules?class_subject_id={$classSubjectId}",
                'Module deleted successfully.'
            );
        } catch (DomainRuleException $e) {
            return $this->redirectWithError("/teacher/modules?class_subject_id={$classSubjectId}", $e->getMessage());
        } catch (AuthorizationException $e) {
            return $this->view('errors/403', ['message' => $e->getMessage()], 403);
        } catch (\Throwable $e) {
            return $this->redirectWithError("/teacher/modules?class_subject_id={$classSubjectId}", $e->getMessage());
        }
    }

    /**
     * Reorder modules within a class subject.
     * Route: POST /teacher/modules/reorder
     */
    public function reorder(Request $request): Response
    {
        $userContext = $this->getUserContext($request);
        if (!$userContext) {
            return $this->json(['success' => false, 'message' => 'Unauthenticated'], 401);
        }

        $classSubjectId = (int)$request->get('class_subject_id', 0);
        $moduleIds = $request->get('module_ids', []);
        if (is_string($moduleIds)) {
            $decoded = json_decode($moduleIds, true);
            $moduleIds = is_array($decoded) ? $decoded : explode(',', $moduleIds);
        }

        try {
            $this->moduleService->reorderModules($classSubjectId, (array)$moduleIds, $userContext);
            if ($request->isAjax() || $request->wantsJson()) {
                return $this->json(['success' => true, 'message' => 'Modules reordered successfully.']);
            }
            return $this->redirectWithSuccess("/teacher/modules?class_subject_id={$classSubjectId}", 'Modules reordered successfully.');
        } catch (\Throwable $e) {
            if ($request->isAjax() || $request->wantsJson()) {
                return $this->json(['success' => false, 'message' => $e->getMessage()], 400);
            }
            return $this->redirectWithError("/teacher/modules?class_subject_id={$classSubjectId}", $e->getMessage());
        }
    }

    /**
     * Add an activity to a module.
     * Route: POST /teacher/modules/{id}/items
     */
    public function addItem(Request $request, array|string|int $id): Response
    {
        $userContext = $this->getUserContext($request);
        if (!$userContext) {
            return Response::redirect('/login');
        }

        $moduleId = is_array($id) ? (int)($id['id'] ?? 0) : (int)$id;
        $classSubjectId = (int)$request->get('class_subject_id', 0);
        $activityType = (string)$request->get('activity_type', '');
        $activityId = (int)$request->get('activity_id', 0);
        $sequenceOrder = $request->get('sequence_order') ? (int)$request->get('sequence_order') : null;
        $isRequired = (bool)($request->get('is_required', 1));

        try {
            $this->moduleService->addActivityToModule(
                moduleId: $moduleId,
                activityType: $activityType,
                activityId: $activityId,
                sequenceOrder: $sequenceOrder,
                isRequired: $isRequired,
                actor: $userContext
            );

            return $this->redirectWithSuccess(
                "/teacher/modules?class_subject_id={$classSubjectId}",
                'Activity successfully attached to module.'
            );
        } catch (DomainRuleException | ValidationException $e) {
            return $this->redirectWithError("/teacher/modules?class_subject_id={$classSubjectId}", $e->getMessage());
        } catch (AuthorizationException $e) {
            return $this->view('errors/403', ['message' => $e->getMessage()], 403);
        } catch (\Throwable $e) {
            return $this->redirectWithError("/teacher/modules?class_subject_id={$classSubjectId}", $e->getMessage());
        }
    }

    /**
     * Remove an activity item from a module.
     * Route: POST /teacher/modules/items/{itemId}/delete
     */
    public function removeItem(Request $request, array|string|int $itemId): Response
    {
        $userContext = $this->getUserContext($request);
        if (!$userContext) {
            return Response::redirect('/login');
        }

        $itemIdInt = is_array($itemId) ? (int)($itemId['itemId'] ?? 0) : (int)$itemId;
        $classSubjectId = (int)$request->get('class_subject_id', 0);

        try {
            $this->moduleService->removeActivityFromModule($itemIdInt, $userContext);
            return $this->redirectWithSuccess(
                "/teacher/modules?class_subject_id={$classSubjectId}",
                'Activity removed from module successfully.'
            );
        } catch (AuthorizationException $e) {
            return $this->view('errors/403', ['message' => $e->getMessage()], 403);
        } catch (\Throwable $e) {
            return $this->redirectWithError("/teacher/modules?class_subject_id={$classSubjectId}", $e->getMessage());
        }
    }

    /**
     * Reorder activity items within a module.
     * Route: POST /teacher/modules/{id}/items/reorder
     */
    public function reorderItems(Request $request, array|string|int $id): Response
    {
        $userContext = $this->getUserContext($request);
        if (!$userContext) {
            return $this->json(['success' => false, 'message' => 'Unauthenticated'], 401);
        }

        $moduleId = is_array($id) ? (int)($id['id'] ?? 0) : (int)$id;
        $classSubjectId = (int)$request->get('class_subject_id', 0);
        $itemIds = $request->get('item_ids', []);
        if (is_string($itemIds)) {
            $decoded = json_decode($itemIds, true);
            $itemIds = is_array($decoded) ? $decoded : explode(',', $itemIds);
        }

        try {
            $this->moduleService->reorderActivities($moduleId, (array)$itemIds, $userContext);
            if ($request->isAjax() || $request->wantsJson()) {
                return $this->json(['success' => true, 'message' => 'Activities reordered successfully.']);
            }
            return $this->redirectWithSuccess("/teacher/modules?class_subject_id={$classSubjectId}", 'Activities reordered successfully.');
        } catch (\Throwable $e) {
            if ($request->isAjax() || $request->wantsJson()) {
                return $this->json(['success' => false, 'message' => $e->getMessage()], 400);
            }
            return $this->redirectWithError("/teacher/modules?class_subject_id={$classSubjectId}", $e->getMessage());
        }
    }

    /**
     * Display cohort learning progress report for an authorized teacher and class subject.
     * Route: GET /teacher/subjects/{classSubjectId}/progress
     */
    public function progress(Request $request, array|string|int $classSubjectId): Response
    {
        $userContext = $this->getUserContext($request);
        if (!$userContext) {
            return Response::redirect('/login');
        }

        $csId = is_array($classSubjectId)
            ? (int)($classSubjectId['classSubjectId'] ?? $classSubjectId['id'] ?? 0)
            : (int)$classSubjectId;

        if ($csId <= 0) {
            return $this->view('errors/404', ['message' => 'Invalid class subject ID provided.'], 404);
        }

        try {
            $report = $this->moduleService->getCohortProgressionReport($csId, $userContext);

            // Fetch teacher's assigned subjects for quick switcher dropdown
            $teacher = $this->teacherRepo->findTeacherByUserId((int)($userContext->id ?? $userContext->userId ?? 0));
            $activeSession = $this->academicRepo->getActiveSession();
            $teacherId = $teacher ? $teacher->id : 0;
            $classSubjects = $teacher
                ? $this->academicRepo->getClassSubjectsByTeacher($teacherId, $activeSession?->id)
                : $this->academicRepo->getAllClassSubjects($activeSession?->id);

            return Response::html($this->render('teacher/reports/progress', [
                'title' => 'Learning Progression Report — Claret Faculty Portal',
                'headerTitle' => 'Learning Progression Report',
                'report' => $report,
                'classSubject' => $report['class_subject'],
                'classSubjects' => $classSubjects,
                'selectedClassSubjectId' => $csId,
                'activeSession' => $activeSession,
                'user' => $userContext,
            ], 'layouts/teacher'));
        } catch (AuthorizationException $e) {
            return $this->view('errors/403', ['message' => $e->getMessage()], 403);
        } catch (ResourceNotFoundException $e) {
            return $this->view('errors/404', ['message' => $e->getMessage()], 404);
        } catch (\Throwable $e) {
            error_log("Cohort progression report error: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());
            return $this->view('errors/500', ['message' => 'An error occurred generating the learning progress report.'], 500);
        }
    }

    /**
     * Display detailed individual student learning progress report.
     * Route: GET /teacher/subjects/{classSubjectId}/students/{studentId}/progress
     */
    public function studentProgress(Request $request, array|string|int $classSubjectId, array|string|int $studentId = 0): Response
    {
        $userContext = $this->getUserContext($request);
        if (!$userContext) {
            return Response::redirect('/login');
        }

        if (is_array($classSubjectId)) {
            $csId = (int)($classSubjectId['classSubjectId'] ?? 0);
            $sId = (int)($classSubjectId['studentId'] ?? 0);
        } else {
            $csId = (int)$classSubjectId;
            $sId = is_array($studentId) ? (int)($studentId['studentId'] ?? 0) : (int)$studentId;
        }

        if ($csId <= 0 || $sId <= 0) {
            return $this->view('errors/404', ['message' => 'Invalid subject or student ID provided.'], 404);
        }

        try {
            $detail = $this->moduleService->getStudentProgressionDetail($csId, $sId, $userContext);
            $activeSession = $this->academicRepo->getActiveSession();

            return Response::html($this->render('teacher/reports/student_detail', [
                'title' => 'Student Learning Progress Detail — Claret Faculty Portal',
                'headerTitle' => 'Student Progress Detail',
                'detail' => $detail,
                'student' => $detail['student'],
                'classSubject' => $detail['class_subject'],
                'learningPath' => $detail['learning_path'],
                'resumeTarget' => $detail['resume_target'],
                'activeSession' => $activeSession,
                'user' => $userContext,
            ], 'layouts/teacher'));
        } catch (AuthorizationException $e) {
            return $this->view('errors/403', ['message' => $e->getMessage()], 403);
        } catch (ResourceNotFoundException $e) {
            return $this->view('errors/404', ['message' => $e->getMessage()], 404);
        } catch (\Throwable $e) {
            error_log("Student progression detail error: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());
            return $this->view('errors/500', ['message' => 'An error occurred loading the student progress detail.'], 500);
        }
    }
}
