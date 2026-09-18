<?php

declare(strict_types=1);

namespace App\Controllers\Student;

use App\Controllers\Controller;
use App\Core\AuthenticatorInterface;
use App\Core\Exceptions\AuthorizationException;
use App\Core\Exceptions\DomainRuleException;
use App\Core\Exceptions\ResourceNotFoundException;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\AcademicRepository;
use App\Repositories\EnrollmentRepository;
use App\Repositories\StudentRepository;
use App\Services\ContentService;
use App\Models\ActivityProgress;
use App\Services\PrerequisiteService;

/**
 * Controller for Student Learning Materials & Course Study Content
 */
class ContentController extends Controller
{
    private ContentService $contentService;
    private AcademicRepository $academicRepo;
    private StudentRepository $studentRepo;
    private EnrollmentRepository $enrollmentRepo;
    private PrerequisiteService $prerequisiteService;

    public function __construct(
        ?AuthenticatorInterface $authenticator = null,
        ?ContentService $contentService = null,
        ?AcademicRepository $academicRepo = null,
        ?StudentRepository $studentRepo = null,
        ?EnrollmentRepository $enrollmentRepo = null,
        ?PrerequisiteService $prerequisiteService = null
    ) {
        parent::__construct($authenticator);
        $this->contentService = $contentService ?? new ContentService();
        $this->academicRepo = $academicRepo ?? new AcademicRepository();
        $this->studentRepo = $studentRepo ?? new StudentRepository();
        $this->enrollmentRepo = $enrollmentRepo ?? new EnrollmentRepository();
        $this->prerequisiteService = $prerequisiteService ?? new PrerequisiteService();
    }

    /**
     * List study materials for student's enrolled subjects.
     * Route: GET /student/content
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
        $sessionId = $activeSession ? $activeSession->id : 0;
        
        $subjectEnrollments = ($student && $sessionId > 0)
            ? $this->enrollmentRepo->getStudentSubjectEnrollments($student->id, $sessionId)
            : [];

        $selectedClassSubjectId = (int)$request->get('class_subject_id', 0);
        $items = [];
        $topics = [];
        $selectedClassSubject = null;

        if ($selectedClassSubjectId > 0) {
            try {
                $result = $this->contentService->getContentForStudent($selectedClassSubjectId, $userContext);
                $items = $result->data['items'] ?? [];
                $topics = $result->data['topics'] ?? [];
                $selectedClassSubject = $result->data['class_subject'] ?? null;
            } catch (\Throwable $e) {
                // Ignore error if student isn't enrolled
            }
        } elseif (!empty($subjectEnrollments)) {
            $firstSubject = (int)$subjectEnrollments[0]->classSubjectId;
            $selectedClassSubjectId = $firstSubject;
            try {
                $result = $this->contentService->getContentForStudent($firstSubject, $userContext);
                $items = $result->data['items'] ?? [];
                $topics = $result->data['topics'] ?? [];
                $selectedClassSubject = $result->data['class_subject'] ?? null;
            } catch (\Throwable $e) {
                // Fallback
            }
        }

        $itemIds = array_map(fn($item) => (int)$item->id, $items);
        $progressMap = ($student && !empty($itemIds))
            ? $this->contentService->getDocumentProgressMapForStudent($student->id, $itemIds)
            : [];

        return Response::html($this->render('student/content/index', [
            'title' => 'Learning Materials & Notes — Student Portal',
            'headerTitle' => 'Course Materials & Notes',
            'user' => $userContext,
            'student' => $student,
            'activeSession' => $activeSession,
            'activeTerm' => $activeTerm,
            'subjectEnrollments' => $subjectEnrollments,
            'selectedClassSubjectId' => $selectedClassSubjectId,
            'selectedClassSubject' => $selectedClassSubject,
            'items' => $items,
            'topics' => $topics,
            'progressMap' => $progressMap,
        ], 'layouts/student'));
    }

    /**
     * View a single lesson/content item.
     * Route: GET /student/content/{id}
     */
    public function show(Request $request, array|string|int $id): Response
    {
        $userContext = $this->requireAuthContext($request);
        $cId = is_array($id) ? (int)($id['id'] ?? 0) : (int)$id;

        try {
            $result = $this->contentService->getContentItem($cId, $userContext);
            $item = $result->data['content_item'];
            $student = $this->studentRepo->findByUserId($userContext->id);

            // Check document-level prerequisite unlocking
            $docPrereqStatus = null;
            $isDocUnlocked = true;
            if ($student) {
                $docPrereqStatus = $this->prerequisiteService->getPrerequisiteStatus(
                    studentId: $student->id,
                    activityType: ActivityProgress::TYPE_DOCUMENT,
                    activityId: $cId
                );
                $isDocUnlocked = $docPrereqStatus['is_unlocked'];
            }

            $progress = $this->contentService->getDocumentReadingProgress($cId, $userContext);
            $sections = ($student && $item->type === 'document')
                ? $this->contentService->getDocumentSectionsWithProgress($cId, $student->id)
                : [];

            // Attach section-level prerequisite status
            if ($student && !empty($sections)) {
                foreach ($sections as &$sec) {
                    $secStatus = $this->prerequisiteService->getPrerequisiteStatus(
                        studentId: $student->id,
                        activityType: ActivityProgress::TYPE_DOCUMENT_SECTION,
                        activityId: $sec->id
                    );
                    $sec->isUnlocked = $secStatus['is_unlocked'];
                    $sec->prerequisiteStatus = $secStatus;
                }
                unset($sec);
            }

            return Response::html($this->render('student/content/show', [
                'title' => "{$item->title} — Study Material",
                'headerTitle' => 'Lesson Material',
                'user' => $userContext,
                'item' => $item,
                'progress' => $progress,
                'sections' => $sections,
                'isDocUnlocked' => $isDocUnlocked,
                'docPrereqStatus' => $docPrereqStatus,
            ], 'layouts/student'));
        } catch (ResourceNotFoundException | AuthorizationException $e) {
            return $this->notFound('Lesson material not found or access restricted.');
        }
    }

    /**
     * Dedicated Online Reader for PDF learning materials.
     * Route: GET /student/content/{id}/read
     */
    public function read(Request $request, array|string|int $id): Response
    {
        $userContext = $this->requireAuthContext($request);
        $cId = is_array($id) ? (int)($id['id'] ?? 0) : (int)$id;

        try {
            $result = $this->contentService->getContentItem($cId, $userContext);
            $item = $result->data['content_item'];

            if (!$item->file) {
                return $this->notFound('This study material has no attached file.');
            }

            $student = $this->studentRepo->findByUserId($userContext->id);

            // Server-authoritative check: If document itself has unmet prerequisites, block reader
            if ($student) {
                $isDocUnlocked = $this->prerequisiteService->isActivityUnlocked(
                    studentId: $student->id,
                    activityType: ActivityProgress::TYPE_DOCUMENT,
                    activityId: $cId
                );

                if (!$isDocUnlocked) {
                    return $this->redirectWithError(
                        "/student/content/{$cId}",
                        'Access denied: You must complete all required prerequisite activities before reading this document.'
                    );
                }
            }

            $isPdf = $item->file->mimeType === 'application/pdf'
                || str_ends_with(strtolower($item->file->originalName), '.pdf');

            $isDocx = $item->file->mimeType === 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
                || $item->file->mimeType === 'application/zip'
                || $item->file->mimeType === 'application/msword'
                || str_ends_with(strtolower($item->file->originalName), '.docx');

            if (!$isPdf && !$isDocx) {
                // Non-readable documents fallback to show page
                return $this->redirect("/student/content/{$cId}");
            }

            $progress = $this->contentService->getDocumentReadingProgress($cId, $userContext);

            if ($isDocx) {
                return Response::html($this->render('student/content/docx_reader', [
                    'title' => "Read: {$item->title} — Student Portal",
                    'headerTitle' => 'Online Document Reader',
                    'user' => $userContext,
                    'item' => $item,
                    'file' => $item->file,
                    'progress' => $progress,
                    'initialLastPage' => $progress?->lastPage ?? 1,
                    'streamUrl' => "/files/{$item->file->id}/stream",
                    'downloadUrl' => "/files/{$item->file->id}/download",
                ], 'layouts/student'));
            }

            $sections = $student
                ? $this->contentService->getDocumentSectionsWithProgress($cId, $student->id)
                : [];

            // Attach section-level prerequisite status
            if ($student && !empty($sections)) {
                foreach ($sections as &$sec) {
                    $secStatus = $this->prerequisiteService->getPrerequisiteStatus(
                        studentId: $student->id,
                        activityType: ActivityProgress::TYPE_DOCUMENT_SECTION,
                        activityId: $sec->id
                    );
                    $sec->isUnlocked = $secStatus['is_unlocked'];
                    $sec->prerequisiteStatus = $secStatus;
                }
                unset($sec);
            }

            $requestedPage = (int)$request->get('page', 0);

            // If a specific page was requested corresponding to a locked section, verify server-side
            if ($student && $requestedPage > 0 && !empty($sections)) {
                foreach ($sections as $sec) {
                    if ($requestedPage >= $sec->startPage && $requestedPage <= $sec->endPage) {
                        if (!($sec->isUnlocked ?? true)) {
                            return $this->redirectWithError(
                                "/student/content/{$cId}",
                                "Section '{$sec->title}' is currently locked because its prerequisites have not been completed."
                            );
                        }
                    }
                }
            }

            $initialLastPage = ($requestedPage > 0)
                ? $requestedPage
                : ($progress?->lastPage ?? 1);

            return Response::html($this->render('student/content/reader', [
                'title' => "Read: {$item->title} — Student Portal",
                'headerTitle' => 'Online Document Reader',
                'user' => $userContext,
                'item' => $item,
                'file' => $item->file,
                'progress' => $progress,
                'sections' => $sections,
                'initialLastPage' => $initialLastPage,
                'streamUrl' => "/files/{$item->file->id}/stream",
                'downloadUrl' => "/files/{$item->file->id}/download",
            ], 'layouts/student'));
        } catch (ResourceNotFoundException | AuthorizationException $e) {
            return $this->notFound('Lesson material not found or access restricted.');
        }
    }

    /**
     * AJAX endpoint to save student reading progress.
     * Route: POST /student/content/{id}/progress
     */
    public function saveProgress(Request $request, array|string|int $id): Response
    {
        try {
            $userContext = $this->requireAuthContext($request);
            $cId = is_array($id) ? (int)($id['id'] ?? 0) : (int)$id;
            $data = $request->json() ?? $request->all();

            $totalPages = (int)($data['total_pages'] ?? 0);
            if ($totalPages <= 0) {
                return Response::json(['success' => false, 'error' => 'Total pages must be a positive integer.'], 422);
            }

            $lastPage = (int)($data['last_page'] ?? $data['current_page'] ?? 1);
            $viewedPages = is_array($data['viewed_pages'] ?? null)
                ? $data['viewed_pages']
                : [$lastPage];

            $result = $this->contentService->recordDocumentReadingProgress(
                contentItemId: $cId,
                lastPage: $lastPage,
                totalPages: $totalPages,
                newPages: $viewedPages,
                actor: $userContext
            );

            return Response::json([
                'success' => true,
                'data' => [
                    'last_page' => $result->data['last_page'],
                    'total_pages' => $result->data['total_pages'],
                    'pages_read' => $result->data['pages_read'],
                    'unique_pages_count' => $result->data['unique_pages_count'],
                    'progress_percent' => $result->data['progress_percent'],
                    'is_completed' => $result->data['is_completed'],
                    'completed_at' => $result->data['completed_at'],
                    'sections' => $result->data['sections'] ?? [],
                ],
            ]);
        } catch (ResourceNotFoundException $e) {
            return Response::json(['success' => false, 'error' => 'Study material not found or access denied.'], 404);
        } catch (AuthorizationException $e) {
            return Response::json(['success' => false, 'error' => $e->getMessage()], 403);
        } catch (DomainRuleException $e) {
            return Response::json(['success' => false, 'error' => $e->getMessage()], 400);
        } catch (\Throwable $e) {
            return Response::json(['success' => false, 'error' => 'An error occurred while saving reading progress.'], 500);
        }
    }

    /**
     * AJAX endpoint to retrieve current student reading progress.
     * Route: GET /student/content/{id}/progress
     */
    public function getProgress(Request $request, array|string|int $id): Response
    {
        try {
            $userContext = $this->requireAuthContext($request);
            $cId = is_array($id) ? (int)($id['id'] ?? 0) : (int)$id;

            $student = $this->studentRepo->findByUserId($userContext->id);
            $progress = $this->contentService->getDocumentReadingProgress($cId, $userContext);
            $sections = ($student)
                ? $this->contentService->getDocumentSectionsWithProgress($cId, $student->id)
                : [];
            $sectionsData = array_map(function($s) {
                return [
                    'id' => $s->id,
                    'title' => $s->title,
                    'start_page' => $s->startPage,
                    'end_page' => $s->endPage,
                    'sequence_order' => $s->sequenceOrder,
                    'total_pages' => $s->getTotalPages(),
                    'progress_percent' => $s->progressPercent ?? 0.0,
                    'is_completed' => $s->isCompleted ?? false,
                    'completed_at' => $s->completedAt ?? null,
                ];
            }, $sections);

            if (!$progress) {
                return Response::json([
                    'success' => true,
                    'data' => null,
                    'last_page' => 1,
                    'total_pages' => null,
                    'unique_pages_count' => 0,
                    'progress_percent' => 0.0,
                    'is_completed' => false,
                    'completed_at' => null,
                    'sections' => $sectionsData,
                ]);
            }

            return Response::json([
                'success' => true,
                'data' => $progress->toArray(),
                'last_page' => $progress->lastPage,
                'total_pages' => $progress->totalPages,
                'unique_pages_count' => $progress->getUniquePagesCount(),
                'progress_percent' => $progress->progressPercent,
                'is_completed' => $progress->isCompleted(),
                'completed_at' => $progress->completedAt,
                'sections' => $sectionsData,
            ]);
        } catch (ResourceNotFoundException $e) {
            return Response::json(['success' => false, 'error' => 'Study material not found or access denied.'], 404);
        } catch (AuthorizationException $e) {
            return Response::json(['success' => false, 'error' => $e->getMessage()], 403);
        } catch (\Throwable $e) {
            return Response::json(['success' => false, 'error' => 'An error occurred while retrieving reading progress.'], 500);
        }
    }
}
