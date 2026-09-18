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
use App\Models\ActivityProgress;
use App\Repositories\AcademicRepository;
use App\Repositories\TeacherRepository;
use App\Services\ContentService;
use App\Services\PrerequisiteService;

/**
 * Controller for Teacher Coursework Content & Lesson Delivery Management
 */
class ContentController extends Controller
{
    private ContentService $contentService;
    private AcademicRepository $academicRepository;
    private TeacherRepository $teacherRepository;
    private PrerequisiteService $prerequisiteService;

    public function __construct(
        ?AuthenticatorInterface $authenticator = null,
        ?ContentService $contentService = null,
        ?AcademicRepository $academicRepository = null,
        ?TeacherRepository $teacherRepository = null,
        ?PrerequisiteService $prerequisiteService = null
    ) {
        parent::__construct($authenticator);
        $this->contentService = $contentService ?? new ContentService();
        $this->academicRepository = $academicRepository ?? new AcademicRepository();
        $this->teacherRepository = $teacherRepository ?? new TeacherRepository();
        $this->prerequisiteService = $prerequisiteService ?? new PrerequisiteService();
    }

    /**
     * List content items for teacher's assigned class-subjects.
     * Route: GET /teacher/content
     */
    public function index(Request $request): Response
    {
        $userContext = $this->authenticator->getUserContext();
        $userId = $userContext->id ?? ($userContext->userId ?? 0);
        $teacher = $this->teacherRepository->findTeacherByUserId((int)$userId);

        if (!$teacher && !$userContext->hasAnyRole(['super_admin', 'admin'])) {
            return $this->view('errors/403', ['message' => 'Teacher profile not found.'], 403);
        }

        $activeSession = $this->academicRepository->getActiveSession();
        $teacherId = $teacher ? $teacher->id : 0;
        $classSubjects = $teacher
            ? $this->academicRepository->getClassSubjectsByTeacher($teacherId, $activeSession?->id)
            : $this->academicRepository->getAllClassSubjects($activeSession?->id);

        $selectedClassSubjectId = (int)($request->query('class_subject_id', 0) ?: $request->get('class_subject_id', 0));
        if ($selectedClassSubjectId <= 0 && !empty($classSubjects)) {
            $selectedClassSubjectId = $classSubjects[0]->id;
        }

        $items = [];
        $topics = [];
        $selectedClassSubject = null;

        if ($selectedClassSubjectId > 0) {
            try {
                $result = $this->contentService->getContentForTeacher($selectedClassSubjectId, $userContext);
                $items = $result->data['items'] ?? [];
                $topics = $result->data['topics'] ?? [];
                $selectedClassSubject = $result->data['class_subject'] ?? null;
            } catch (\Throwable $e) {
                // If unauthorized or not found, keep empty
            }
        }

        return Response::html($this->render('teacher/content/index', [
            'title' => 'Learning Materials — Claret Faculty Portal',
            'headerTitle' => 'Course Content & Study Materials',
            'classSubjects' => $classSubjects,
            'selectedClassSubjectId' => $selectedClassSubjectId,
            'selectedClassSubject' => $selectedClassSubject,
            'items' => $items,
            'topics' => $topics,
            'activeSession' => $activeSession,
            'user' => $userContext,
        ], 'layouts/teacher'));
    }

    /**
     * Show form to create a new content item.
     * Route: GET /teacher/content/create
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

        $presetClassSubjectId = (int)($request->query('class_subject_id', 0) ?: $request->get('class_subject_id', 0));

        return Response::html($this->render('teacher/content/create', [
            'title' => 'Upload Learning Material — Claret Faculty Portal',
            'headerTitle' => 'Create Learning Material',
            'classSubjects' => $classSubjects,
            'presetClassSubjectId' => $presetClassSubjectId,
            'activeSession' => $activeSession,
            'user' => $userContext,
        ], 'layouts/teacher'));
    }

    /**
     * Store a new content item.
     * Route: POST /teacher/content/create
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
            $result = $this->contentService->createContent($postData, $uploadedFile, $userContext);
            $classSubjectId = (int)($postData['class_subject_id'] ?? 0);

            return $this->redirectWithSuccess(
                '/teacher/content?class_subject_id=' . $classSubjectId,
                'Lesson material created successfully.'
            );
        } catch (ValidationException $e) {
            return $this->redirectWithErrors('/teacher/content/create', $e->getErrors(), $postData);
        } catch (AuthorizationException $e) {
            return $this->view('errors/403', ['message' => $e->getMessage()], 403);
        } catch (\Throwable $e) {
            return $this->redirectWithError('/teacher/content/create', $e->getMessage());
        }
    }

    /**
     * Show form to edit an existing content item.
     * Route: GET /teacher/content/{id}/edit
     */
    public function edit(Request $request, array|string|int $id): Response
    {
        $userContext = $this->getUserContext($request);
        if (!$userContext) {
            return Response::redirect('/login');
        }

        $contentId = is_array($id) ? (int)($id['id'] ?? 0) : (int)$id;

        try {
            $result = $this->contentService->getContentItem($contentId, $userContext);
            $item = $result->data['content_item'];

            $activeSession = $this->academicRepository->getActiveSession();
            $userId = $userContext->id ?? ($userContext->userId ?? 0);
            $teacher = $this->teacherRepository->findTeacherByUserId((int)$userId);
            $classSubjects = $teacher
                ? $this->academicRepository->getClassSubjectsByTeacher($teacher->id, $activeSession?->id)
                : $this->academicRepository->getAllClassSubjects($activeSession?->id);

            $sections = $this->contentService->getSectionsForContent($contentId);

            // Attach configured prerequisites to sections and document
            $docPrerequisites = $this->prerequisiteService->getConfiguredPrerequisites(
                ActivityProgress::TYPE_DOCUMENT,
                $contentId
            );

            foreach ($sections as $sec) {
                $sec->configuredPrerequisites = $this->prerequisiteService->getConfiguredPrerequisites(
                    ActivityProgress::TYPE_DOCUMENT_SECTION,
                    $sec->id
                );
            }

            return Response::html($this->render('teacher/content/edit', [
                'title' => 'Edit Learning Material — Claret Faculty Portal',
                'headerTitle' => 'Edit Learning Material',
                'item' => $item,
                'classSubjects' => $classSubjects,
                'sections' => $sections,
                'docPrerequisites' => $docPrerequisites,
                'user' => $userContext,
            ], 'layouts/teacher'));
        } catch (ResourceNotFoundException $e) {
            return $this->view('errors/404', ['message' => $e->getMessage()], 404);
        } catch (AuthorizationException $e) {
            return $this->view('errors/403', ['message' => $e->getMessage()], 403);
        }
    }

    /**
     * Update an existing content item.
     * Route: POST /teacher/content/{id}/edit
     */
    public function update(Request $request, array|string|int $id): Response
    {
        $userContext = $this->getUserContext($request);
        if (!$userContext) {
            return Response::redirect('/login');
        }

        $contentId = is_array($id) ? (int)($id['id'] ?? 0) : (int)$id;
        $postData = $request->all();
        $files = $request->files();
        $uploadedFile = $files['attachment'] ?? null;

        try {
            $result = $this->contentService->updateContent($contentId, $postData, $uploadedFile, $userContext);
            $item = $result->data['content_item'];

            return $this->redirectWithSuccess(
                '/teacher/content?class_subject_id=' . $item->classSubjectId,
                'Lesson material updated successfully.'
            );
        } catch (ValidationException $e) {
            return $this->redirectWithErrors("/teacher/content/{$contentId}/edit", $e->getErrors(), $postData);
        } catch (ResourceNotFoundException $e) {
            return $this->view('errors/404', ['message' => $e->getMessage()], 404);
        } catch (AuthorizationException $e) {
            return $this->view('errors/403', ['message' => $e->getMessage()], 403);
        } catch (\Throwable $e) {
            return $this->redirectWithError("/teacher/content/{$contentId}/edit", $e->getMessage());
        }
    }

    /**
     * Publish or unpublish a content item.
     * Route: POST /teacher/content/{id}/publish
     */
    public function togglePublish(Request $request, array|string|int $id): Response
    {
        $userContext = $this->getUserContext($request);
        if (!$userContext) {
            return Response::redirect('/login');
        }

        $contentId = is_array($id) ? (int)($id['id'] ?? 0) : (int)$id;
        $action = $request->post('action', 'publish');

        try {
            if ($action === 'unpublish') {
                $result = $this->contentService->unpublishContent($contentId, $userContext);
                $message = 'Learning material unpublished (saved as draft).';
            } else {
                $result = $this->contentService->publishContent($contentId, $userContext);
                $message = 'Learning material published successfully to enrolled students.';
            }

            $item = $result->data['content_item'] ?? null;
            $classSubjectId = $item ? $item->classSubjectId : 0;

            return $this->redirectWithSuccess(
                '/teacher/content' . ($classSubjectId > 0 ? '?class_subject_id=' . $classSubjectId : ''),
                $message
            );
        } catch (\Throwable $e) {
            return $this->redirectWithError('/teacher/content', $e->getMessage());
        }
    }

    /**
     * Delete a content item.
     * Route: POST /teacher/content/{id}/delete
     */
    public function delete(Request $request, array|string|int $id): Response
    {
        $userContext = $this->getUserContext($request);
        if (!$userContext) {
            return Response::redirect('/login');
        }

        $contentId = is_array($id) ? (int)($id['id'] ?? 0) : (int)$id;
        $classSubjectId = (int)$request->post('class_subject_id', 0);

        try {
            $this->contentService->deleteContent($contentId, $userContext);

            return $this->redirectWithSuccess(
                '/teacher/content' . ($classSubjectId > 0 ? '?class_subject_id=' . $classSubjectId : ''),
                'Content item deleted successfully.'
            );
        } catch (\Throwable $e) {
            return $this->redirectWithError('/teacher/content', $e->getMessage());
        }
    }

    /**
     * Create or update a logical document section.
     * Route: POST /teacher/content/{id}/sections
     */
    public function storeSection(Request $request, array|string|int $id): Response
    {
        $userContext = $this->getUserContext($request);
        if (!$userContext) {
            return Response::redirect('/login');
        }

        $contentId = is_array($id) ? (int)($id['id'] ?? 0) : (int)$id;
        $postData = $request->all();
        $sectionId = (int)($postData['section_id'] ?? 0);
        $wantsJson = $request->header('Accept') === 'application/json' || $request->header('X-Requested-With') === 'XMLHttpRequest';

        try {
            if ($sectionId > 0) {
                $result = $this->contentService->updateSection($sectionId, $postData, $userContext);
            } else {
                $result = $this->contentService->createSection($contentId, $postData, $userContext);
            }

            if ($wantsJson) {
                return Response::json([
                    'success' => true,
                    'message' => $result->getMessage(),
                    'section' => $result->getData()['section']?->toArray(),
                ]);
            }

            return $this->redirectWithSuccess(
                "/teacher/content/{$contentId}/edit",
                $result->getMessage() ?? 'Document section saved successfully.'
            );
        } catch (ValidationException $e) {
            if ($wantsJson) {
                return Response::json(['success' => false, 'errors' => $e->getErrors()], 422);
            }
            return $this->redirectWithErrors("/teacher/content/{$contentId}/edit", $e->getErrors(), $postData);
        } catch (ResourceNotFoundException $e) {
            if ($wantsJson) {
                return Response::json(['success' => false, 'error' => $e->getMessage()], 404);
            }
            return $this->view('errors/404', ['message' => $e->getMessage()], 404);
        } catch (AuthorizationException $e) {
            if ($wantsJson) {
                return Response::json(['success' => false, 'error' => $e->getMessage()], 403);
            }
            return $this->view('errors/403', ['message' => $e->getMessage()], 403);
        } catch (DomainRuleException $e) {
            if ($wantsJson) {
                return Response::json(['success' => false, 'error' => $e->getMessage()], 422);
            }
            return $this->redirectWithError("/teacher/content/{$contentId}/edit", $e->getMessage());
        } catch (\Throwable $e) {
            if ($wantsJson) {
                return Response::json(['success' => false, 'error' => $e->getMessage()], 500);
            }
            return $this->redirectWithError("/teacher/content/{$contentId}/edit", $e->getMessage());
        }
    }

    /**
     * Delete a logical document section.
     * Route: POST /teacher/content/{id}/sections/{sectionId}/delete
     * Route: POST /teacher/content/{id}/sections/delete
     */
    public function deleteSection(Request $request, array|string|int $id, array|string|int $sectionId = 0): Response
    {
        $userContext = $this->getUserContext($request);
        if (!$userContext) {
            return Response::redirect('/login');
        }

        $contentId = is_array($id) ? (int)($id['id'] ?? 0) : (int)$id;
        $secId = is_array($sectionId) ? (int)($sectionId['sectionId'] ?? 0) : (int)$sectionId;
        if ($secId <= 0) {
            $secId = (int)$request->post('section_id', 0);
        }

        $wantsJson = $request->header('Accept') === 'application/json' || $request->header('X-Requested-With') === 'XMLHttpRequest';

        try {
            $result = $this->contentService->deleteSection($secId, $userContext);

            if ($wantsJson) {
                return Response::json(['success' => true, 'message' => $result->getMessage()]);
            }

            return $this->redirectWithSuccess(
                "/teacher/content/{$contentId}/edit",
                $result->getMessage() ?? 'Document section deleted successfully.'
            );
        } catch (ResourceNotFoundException $e) {
            if ($wantsJson) {
                return Response::json(['success' => false, 'error' => $e->getMessage()], 404);
            }
            return $this->view('errors/404', ['message' => $e->getMessage()], 404);
        } catch (AuthorizationException $e) {
            if ($wantsJson) {
                return Response::json(['success' => false, 'error' => $e->getMessage()], 403);
            }
            return $this->view('errors/403', ['message' => $e->getMessage()], 403);
        } catch (\Throwable $e) {
            if ($wantsJson) {
                return Response::json(['success' => false, 'error' => $e->getMessage()], 500);
            }
            return $this->redirectWithError("/teacher/content/{$contentId}/edit", $e->getMessage());
        }
    }

    /**
     * Add a prerequisite relationship for an activity (e.g. section or document).
     * Route: POST /teacher/content/{id}/prerequisites
     */
    public function addPrerequisite(Request $request, array|string|int $id): Response
    {
        $userContext = $this->getUserContext($request);
        if (!$userContext) {
            return Response::redirect('/login');
        }

        $contentId = is_array($id) ? (int)($id['id'] ?? 0) : (int)$id;

        try {
            // Authorize teacher access to target content
            $result = $this->contentService->getContentItem($contentId, $userContext);
            $item = $result->data['content_item'];

            $targetType = trim((string)$request->post('target_type', ''));
            $targetId = (int)$request->post('target_id', 0);
            $prereqType = trim((string)$request->post('prerequisite_type', ''));
            $prereqId = (int)$request->post('prerequisite_id', 0);

            $this->prerequisiteService->createPrerequisite(
                targetType: $targetType,
                targetId: $targetId,
                prereqType: $prereqType,
                prereqId: $prereqId
            );

            return $this->redirectWithSuccess(
                "/teacher/content/{$contentId}/edit",
                'Prerequisite relationship created successfully.'
            );
        } catch (ResourceNotFoundException $e) {
            return $this->view('errors/404', ['message' => $e->getMessage()], 404);
        } catch (AuthorizationException $e) {
            return $this->view('errors/403', ['message' => $e->getMessage()], 403);
        } catch (ValidationException|DomainRuleException $e) {
            return $this->redirectWithError(
                "/teacher/content/{$contentId}/edit",
                $e instanceof ValidationException ? implode(' ', $e->getErrors()) : $e->getMessage()
            );
        } catch (\Throwable $e) {
            return $this->redirectWithError("/teacher/content/{$contentId}/edit", $e->getMessage());
        }
    }

    /**
     * Delete a prerequisite relationship.
     * Route: POST /teacher/content/{id}/prerequisites/{prerequisiteId}/delete
     */
    public function deletePrerequisite(Request $request, array|string|int $id, array|string|int $prerequisiteId = 0): Response
    {
        $userContext = $this->getUserContext($request);
        if (!$userContext) {
            return Response::redirect('/login');
        }

        $contentId = is_array($id) ? (int)($id['id'] ?? 0) : (int)$id;
        $prereqRelId = is_array($prerequisiteId) ? (int)($prerequisiteId['prerequisiteId'] ?? 0) : (int)$prerequisiteId;
        if ($prereqRelId <= 0) {
            $prereqRelId = (int)$request->post('prerequisite_id', 0);
        }

        try {
            $this->contentService->getContentItem($contentId, $userContext);
            $this->prerequisiteService->deletePrerequisite($prereqRelId);

            return $this->redirectWithSuccess(
                "/teacher/content/{$contentId}/edit",
                'Prerequisite removed successfully.'
            );
        } catch (ResourceNotFoundException $e) {
            return $this->view('errors/404', ['message' => $e->getMessage()], 404);
        } catch (AuthorizationException $e) {
            return $this->view('errors/403', ['message' => $e->getMessage()], 403);
        } catch (\Throwable $e) {
            return $this->redirectWithError("/teacher/content/{$contentId}/edit", $e->getMessage());
        }
    }
}
