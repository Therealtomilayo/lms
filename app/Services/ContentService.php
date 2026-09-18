<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\Exceptions\AuthorizationException;
use App\Core\Exceptions\DomainRuleException;
use App\Core\Exceptions\ResourceNotFoundException;
use App\Core\Exceptions\ValidationException;
use App\Core\UserContext;
use App\DTO\ServiceResult;
use App\Models\ActivityProgress;
use App\Models\ContentItem;
use App\Policies\ContentPolicy;
use App\Repositories\AcademicRepository;
use App\Repositories\ActivityProgressRepository;
use App\Repositories\ContentRepository;
use App\Repositories\EnrollmentRepository;
use App\Repositories\FileRepository;
use App\Repositories\ParentRepository;
use App\Repositories\StudentRepository;
use App\Repositories\TeacherRepository;
use App\Models\DocumentSection;
use App\Repositories\DocumentSectionRepository;
use PDO;

/**
 * Application Service for Class-Subject Content Delivery & Learning Materials
 */
class ContentService
{
    private ContentRepository $contentRepository;
    private FileRepository $fileRepository;
    private FileStorageService $fileStorageService;
    private AcademicRepository $academicRepository;
    private TeacherRepository $teacherRepository;
    private StudentRepository $studentRepository;
    private EnrollmentRepository $enrollmentRepository;
    private ParentRepository $parentRepository;
    private ActivityProgressRepository $activityProgressRepository;
    private DocumentSectionRepository $documentSectionRepository;
    private \App\Repositories\ActivityPrerequisiteRepository $prerequisiteRepository;
    private \App\Repositories\ModuleRepository $moduleRepository;
    private PDO $pdo;

    public function __construct(
        ?ContentRepository $contentRepository = null,
        ?FileRepository $fileRepository = null,
        ?FileStorageService $fileStorageService = null,
        ?AcademicRepository $academicRepository = null,
        ?TeacherRepository $teacherRepository = null,
        ?StudentRepository $studentRepository = null,
        ?EnrollmentRepository $enrollmentRepository = null,
        ?ParentRepository $parentRepository = null,
        ?PDO $pdo = null,
        ?ActivityProgressRepository $activityProgressRepository = null,
        ?DocumentSectionRepository $documentSectionRepository = null,
        ?\App\Repositories\ActivityPrerequisiteRepository $prerequisiteRepository = null,
        ?\App\Repositories\ModuleRepository $moduleRepository = null
    ) {
        $this->contentRepository = $contentRepository ?? new ContentRepository();
        $this->fileRepository = $fileRepository ?? new FileRepository();
        $this->fileStorageService = $fileStorageService ?? new FileStorageService($this->fileRepository, $this->contentRepository);
        $this->academicRepository = $academicRepository ?? new AcademicRepository();
        $this->teacherRepository = $teacherRepository ?? new TeacherRepository();
        $this->studentRepository = $studentRepository ?? new StudentRepository();
        $this->enrollmentRepository = $enrollmentRepository ?? new EnrollmentRepository();
        $this->parentRepository = $parentRepository ?? new ParentRepository();
        $this->pdo = $pdo ?? Database::getInstance();
        $this->activityProgressRepository = $activityProgressRepository ?? new ActivityProgressRepository($this->pdo);
        $this->documentSectionRepository = $documentSectionRepository ?? new DocumentSectionRepository($this->pdo);
        $this->prerequisiteRepository = $prerequisiteRepository ?? new \App\Repositories\ActivityPrerequisiteRepository($this->pdo);
        $this->moduleRepository = $moduleRepository ?? new \App\Repositories\ModuleRepository($this->pdo);
    }

    /**
     * Create a new content item (note, video, link, document) scoped to a class-subject.
     */
    public function createContent(array $data, ?array $uploadedFile, UserContext $actor): ServiceResult
    {
        $errors = [];

        $classSubjectId = (int)($data['class_subject_id'] ?? 0);
        if ($classSubjectId <= 0) {
            $errors['class_subject_id'][] = 'Class subject is required.';
        }

        $title = trim((string)($data['title'] ?? ''));
        if ($title === '') {
            $errors['title'][] = 'Title is required.';
        } elseif (mb_strlen($title) > 200) {
            $errors['title'][] = 'Title cannot exceed 200 characters.';
        }

        $type = (string)($data['type'] ?? 'note');
        if (!in_array($type, ContentItem::VALID_TYPES, true)) {
            $errors['type'][] = 'Invalid content type.';
        }

        $topic = isset($data['topic']) && trim((string)$data['topic']) !== '' ? trim((string)$data['topic']) : null;
        if ($topic !== null && mb_strlen($topic) > 100) {
            $errors['topic'][] = 'Topic cannot exceed 100 characters.';
        }

        $description = isset($data['description']) ? (string)$data['description'] : null;
        $externalUrl = isset($data['external_url']) && trim((string)$data['external_url']) !== '' ? trim((string)$data['external_url']) : null;

        if ($type === ContentItem::TYPE_LINK && empty($externalUrl)) {
            $errors['external_url'][] = 'External URL is required for link type content.';
        }

        if ($externalUrl !== null && !filter_var($externalUrl, FILTER_VALIDATE_URL)) {
            $errors['external_url'][] = 'Invalid URL format.';
        }

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }

        // Validate teacher / admin authorization
        if (!ContentPolicy::canCreateContent($actor, $classSubjectId, null, $this->academicRepository, $this->teacherRepository)) {
            throw new AuthorizationException('You are not authorized to publish content for this class subject.');
        }

        $classSubject = $this->academicRepository->findClassSubjectById($classSubjectId);
        if (!$classSubject) {
            throw new ResourceNotFoundException('Class subject not found.');
        }

        // Determine teacher ID
        $teacherId = $classSubject->teacherId;
        if ($actor->hasRole('teacher')) {
            $teacher = $this->teacherRepository->findTeacherByUserId($actor->userId);
            if ($teacher) {
                $teacherId = $teacher->id;
            }
        }

        $publishNow = !empty($data['publish_now']) || !empty($data['is_published']);
        $publishedAt = $publishNow ? date('Y-m-d H:i:s') : null;

        $this->pdo->beginTransaction();

        try {
            // First create content item record with temporary null file_id
            $item = $this->contentRepository->create(
                classSubjectId: $classSubjectId,
                teacherId: $teacherId,
                topic: $topic,
                title: $title,
                description: $description,
                type: $type,
                fileId: null,
                externalUrl: $externalUrl,
                publishedAt: $publishedAt
            );

            // If a file was uploaded, store it and attach to content item
            if ($uploadedFile !== null && isset($uploadedFile['error']) && $uploadedFile['error'] === UPLOAD_ERR_OK) {
                $fileRecord = $this->fileStorageService->storeUploadedFile(
                    file: $uploadedFile,
                    uploadedBy: $actor->userId,
                    ownerType: 'content_item',
                    ownerId: $item->id
                );

                $this->contentRepository->update($item->id, ['file_id' => $fileRecord->id]);
                $item = $this->contentRepository->findById($item->id);
            }

            $this->pdo->commit();

            return ServiceResult::success(['content_item' => $item], 'Content item created successfully.');
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            if ($e instanceof ValidationException || $e instanceof AuthorizationException || $e instanceof ResourceNotFoundException) {
                throw $e;
            }
            throw new DomainRuleException('Failed to create content item: ' . $e->getMessage());
        }
    }

    /**
     * Update an existing content item.
     */
    public function updateContent(int $id, array $data, ?array $uploadedFile, UserContext $actor): ServiceResult
    {
        $item = $this->contentRepository->findById($id);
        if (!$item) {
            throw new ResourceNotFoundException("Content item #{$id} not found.");
        }

        if (!ContentPolicy::canEditContent($actor, $item, null, $this->academicRepository, $this->teacherRepository)) {
            throw new AuthorizationException('You are not authorized to update this content item.');
        }

        $errors = [];

        $title = isset($data['title']) ? trim((string)$data['title']) : $item->title;
        if ($title === '') {
            $errors['title'][] = 'Title is required.';
        } elseif (mb_strlen($title) > 200) {
            $errors['title'][] = 'Title cannot exceed 200 characters.';
        }

        $type = isset($data['type']) ? (string)$data['type'] : $item->type;
        if (!in_array($type, ContentItem::VALID_TYPES, true)) {
            $errors['type'][] = 'Invalid content type.';
        }

        $topic = array_key_exists('topic', $data) ? (trim((string)$data['topic']) !== '' ? trim((string)$data['topic']) : null) : $item->topic;
        if ($topic !== null && mb_strlen($topic) > 100) {
            $errors['topic'][] = 'Topic cannot exceed 100 characters.';
        }

        $description = array_key_exists('description', $data) ? (string)$data['description'] : $item->description;
        $externalUrl = array_key_exists('external_url', $data) ? (trim((string)$data['external_url']) !== '' ? trim((string)$data['external_url']) : null) : $item->externalUrl;

        if ($type === ContentItem::TYPE_LINK && empty($externalUrl)) {
            $errors['external_url'][] = 'External URL is required for link type content.';
        }

        if ($externalUrl !== null && !filter_var($externalUrl, FILTER_VALIDATE_URL)) {
            $errors['external_url'][] = 'Invalid URL format.';
        }

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }

        $updatePayload = [
            'title' => $title,
            'type' => $type,
            'topic' => $topic,
            'description' => $description,
            'external_url' => $externalUrl,
        ];

        if (array_key_exists('is_published', $data)) {
            $updatePayload['published_at'] = !empty($data['is_published']) ? ($item->publishedAt ?? date('Y-m-d H:i:s')) : null;
        }

        $this->pdo->beginTransaction();

        try {
            // Handle file replacement if a new file is uploaded
            if ($uploadedFile !== null && isset($uploadedFile['error']) && $uploadedFile['error'] === UPLOAD_ERR_OK) {
                // If old file exists, soft-delete it
                if ($item->fileId) {
                    $this->fileRepository->softDelete($item->fileId);
                }

                $fileRecord = $this->fileStorageService->storeUploadedFile(
                    file: $uploadedFile,
                    uploadedBy: $actor->userId,
                    ownerType: 'content_item',
                    ownerId: $item->id
                );

                $updatePayload['file_id'] = $fileRecord->id;
            }

            $this->contentRepository->update($id, $updatePayload);
            $this->pdo->commit();

            $updatedItem = $this->contentRepository->findById($id);

            return ServiceResult::success(['content_item' => $updatedItem], 'Content item updated successfully.');
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            if ($e instanceof ValidationException || $e instanceof AuthorizationException || $e instanceof ResourceNotFoundException) {
                throw $e;
            }
            throw new DomainRuleException('Failed to update content item: ' . $e->getMessage());
        }
    }

    public function publishContent(int $id, UserContext $actor): ServiceResult
    {
        $item = $this->contentRepository->findById($id);
        if (!$item) {
            throw new ResourceNotFoundException("Content item #{$id} not found.");
        }

        if (!ContentPolicy::canEditContent($actor, $item, null, $this->academicRepository, $this->teacherRepository)) {
            throw new AuthorizationException('You are not authorized to publish this content item.');
        }

        $this->contentRepository->publish($id);
        $updated = $this->contentRepository->findById($id);

        return ServiceResult::success(['content_item' => $updated], 'Content published successfully.');
    }

    public function unpublishContent(int $id, UserContext $actor): ServiceResult
    {
        $item = $this->contentRepository->findById($id);
        if (!$item) {
            throw new ResourceNotFoundException("Content item #{$id} not found.");
        }

        if (!ContentPolicy::canEditContent($actor, $item, null, $this->academicRepository, $this->teacherRepository)) {
            throw new AuthorizationException('You are not authorized to unpublish this content item.');
        }

        $this->contentRepository->unpublish($id);
        $updated = $this->contentRepository->findById($id);

        return ServiceResult::success(['content_item' => $updated], 'Content unpublished (moved to draft).');
    }

    public function deleteContent(int $id, UserContext $actor): ServiceResult
    {
        $item = $this->contentRepository->findById($id);
        if (!$item) {
            throw new ResourceNotFoundException("Content item #{$id} not found.");
        }

        if (!ContentPolicy::canDeleteContent($actor, $item, null, $this->academicRepository, $this->teacherRepository)) {
            throw new AuthorizationException('You are not authorized to delete this content item.');
        }

        $this->pdo->beginTransaction();

        try {
            if ($item->fileId) {
                $this->fileRepository->softDelete($item->fileId);
            }

            // 1. Clean up any sections of this document
            $sections = $this->documentSectionRepository->getByContentItemId($id);
            foreach ($sections as $sec) {
                // Prerequisite rules referencing section
                $this->prerequisiteRepository->deleteForActivity(ActivityProgress::TYPE_DOCUMENT_SECTION, $sec->id);
                // Module items referencing section
                $this->moduleRepository->deleteItemsByActivity(ActivityProgress::TYPE_DOCUMENT_SECTION, $sec->id);
                // Section progress
                $this->activityProgressRepository->deleteProgressForActivity(ActivityProgress::TYPE_DOCUMENT_SECTION, $sec->id);
                // Section record
                $this->documentSectionRepository->delete($sec->id);
            }

            // 2. Clean up polymorphic references for the document itself
            // Prerequisite rules referencing document (target or prerequisite)
            $this->prerequisiteRepository->deleteForActivity(ActivityProgress::TYPE_DOCUMENT, $id);
            // Module items referencing document
            $this->moduleRepository->deleteItemsByActivity(ActivityProgress::TYPE_DOCUMENT, $id);
            // Learning activity progress for document
            $this->activityProgressRepository->deleteProgressForActivity(ActivityProgress::TYPE_DOCUMENT, $id);

            // 3. Delete content item
            $this->contentRepository->delete($id);
            $this->pdo->commit();

            return ServiceResult::success([], 'Content item deleted successfully.');
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw new DomainRuleException('Failed to delete content item: ' . $e->getMessage());
        }
    }

    /**
     * Retrieve content items for teacher management (including drafts).
     */
    public function getContentForTeacher(int $classSubjectId, UserContext $actor): ServiceResult
    {
        if (!ContentPolicy::canCreateContent($actor, $classSubjectId, null, $this->academicRepository, $this->teacherRepository)) {
            throw new AuthorizationException('You are not authorized to manage content for this class subject.');
        }

        $items = $this->contentRepository->getByClassSubject($classSubjectId);
        $topics = $this->contentRepository->getTopicsByClassSubject($classSubjectId);
        $classSubject = $this->academicRepository->findClassSubjectById($classSubjectId);

        return ServiceResult::success([
            'class_subject' => $classSubject,
            'items' => $items,
            'topics' => $topics,
        ]);
    }

    /**
     * Retrieve published content items for an enrolled student or linked parent.
     */
    public function getContentForStudent(int $classSubjectId, UserContext $actor): ServiceResult
    {
        $classSubject = $this->academicRepository->findClassSubjectById($classSubjectId);
        if (!$classSubject) {
            throw new ResourceNotFoundException('Class subject not found.');
        }

        $sessionId = $classSubject->sessionId;

        // Verify Student enrollment or Parent link
        if ($actor->hasRole('student')) {
            $student = $this->studentRepository->findByUserId($actor->userId);
            if (!$student || !$this->enrollmentRepository->isStudentEnrolledInSubject($student->id, $classSubjectId, $sessionId)) {
                throw new AuthorizationException('You are not enrolled in this subject.');
            }
        } elseif ($actor->hasRole('parent')) {
            $parent = $this->parentRepository->findByUserId($actor->userId);
            $isLinkedToEnrolledChild = false;
            if ($parent) {
                $linked = $this->parentRepository->getLinkedStudents($parent->id);
                foreach ($linked as $child) {
                    if ($this->enrollmentRepository->isStudentEnrolledInSubject($child->id, $classSubjectId, $sessionId)) {
                        $isLinkedToEnrolledChild = true;
                        break;
                    }
                }
            }
            if (!$isLinkedToEnrolledChild && !$actor->hasAnyRole(['super_admin', 'admin'])) {
                throw new AuthorizationException('You do not have a linked child enrolled in this subject.');
            }
        } elseif (!$actor->hasAnyRole(['super_admin', 'admin', 'teacher'])) {
            throw new AuthorizationException('Access denied.');
        }

        $items = $this->contentRepository->getPublishedByClassSubject($classSubjectId);
        $topics = $this->contentRepository->getTopicsByClassSubject($classSubjectId);

        return ServiceResult::success([
            'class_subject' => $classSubject,
            'items' => $items,
            'topics' => $topics,
        ]);
    }

    /**
     * Retrieve a single content item with authorization check.
     */
    public function getContentItem(int $id, UserContext $actor): ServiceResult
    {
        $item = $this->contentRepository->findById($id);
        if (!$item) {
            throw new ResourceNotFoundException("Content item #{$id} not found.");
        }

        if (!ContentPolicy::canViewContent(
            $actor,
            $item,
            null,
            $this->academicRepository,
            $this->teacherRepository,
            $this->studentRepository,
            $this->enrollmentRepository,
            $this->parentRepository
        )) {
            // Masked denial per 06-rbac-permissions.md
            throw new ResourceNotFoundException("Content item not found.");
        }

        return ServiceResult::success(['content_item' => $item]);
    }

    /**
     * Record document reading progress with unique page coverage for a student.
     * Enforces student identity, enrollment, and PDF document type.
     */
    public function recordDocumentReadingProgress(
        int $contentItemId,
        int $lastPage,
        int $totalPages,
        array $newPages,
        UserContext $actor
    ): ServiceResult {
        $student = $this->studentRepository->findByUserId($actor->id);
        if (!$student && !$actor->hasRole('student')) {
            throw new AuthorizationException('Student profile required to record reading progress.');
        }

        if (!$student) {
            throw new ResourceNotFoundException('Student profile not found.');
        }

        $item = $this->contentRepository->findById($contentItemId);
        if (!$item) {
            throw new ResourceNotFoundException("Content item #{$contentItemId} not found.");
        }

        if (!ContentPolicy::canViewContent(
            $actor,
            $item,
            null,
            $this->academicRepository,
            $this->teacherRepository,
            $this->studentRepository,
            $this->enrollmentRepository,
            $this->parentRepository
        )) {
            // Masked denial per 06-rbac-permissions.md
            throw new ResourceNotFoundException('Content item not found.');
        }

        $isPdf = $item->type === ContentItem::TYPE_DOCUMENT
            && $item->file
            && ($item->file->mimeType === 'application/pdf' || str_ends_with(strtolower($item->file->originalName), '.pdf'));

        $isDocx = $item->type === ContentItem::TYPE_DOCUMENT
            && $item->file
            && ($item->file->mimeType === 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
                || $item->file->mimeType === 'application/zip'
                || $item->file->mimeType === 'application/msword'
                || str_ends_with(strtolower($item->file->originalName), '.docx'));

        if (!$isPdf && !$isDocx) {
            throw new DomainRuleException('Reading progress tracking is only supported for online-readable documents (PDF and DOCX).');
        }

        $progress = $this->activityProgressRepository->recordDocumentReadingProgress(
            studentId: $student->id,
            contentItemId: $contentItemId,
            lastPage: $lastPage,
            totalPages: $totalPages,
            newPagesNewlyViewed: $newPages
        );

        // Synchronize section progress for all logical sections belonging to this PDF document
        $sectionSummaries = [];
        if ($isPdf) {
            $sections = $this->documentSectionRepository->getByContentItemId($contentItemId);
            foreach ($sections as $sec) {
                $secProgress = $this->activityProgressRepository->syncSectionProgress(
                    studentId: $student->id,
                    section: $sec,
                    documentPagesRead: $progress->pagesRead
                );
                $sectionSummaries[] = [
                    'id' => $sec->id,
                    'title' => $sec->title,
                    'start_page' => $sec->startPage,
                    'end_page' => $sec->endPage,
                    'sequence_order' => $sec->sequenceOrder,
                    'progress_percent' => $secProgress->progressPercent,
                    'is_completed' => $secProgress->isCompleted(),
                    'completed_at' => $secProgress->completedAt,
                ];
            }
        }

        return ServiceResult::success([
            'progress' => $progress,
            'last_page' => $progress->lastPage,
            'total_pages' => $progress->totalPages,
            'pages_read' => $progress->pagesRead,
            'unique_pages_count' => $progress->getUniquePagesCount(),
            'progress_percent' => $progress->progressPercent,
            'is_completed' => $progress->isCompleted(),
            'completed_at' => $progress->completedAt,
            'sections' => $sectionSummaries,
        ], 'Reading progress updated successfully.');
    }

    /**
     * Retrieve document reading progress for a student.
     */
    public function getDocumentReadingProgress(int $contentItemId, UserContext $actor): ?ActivityProgress
    {
        $student = $this->studentRepository->findByUserId($actor->id);
        if (!$student) {
            return null;
        }

        $item = $this->contentRepository->findById($contentItemId);
        if (!$item) {
            return null;
        }

        if (!ContentPolicy::canViewContent(
            $actor,
            $item,
            null,
            $this->academicRepository,
            $this->teacherRepository,
            $this->studentRepository,
            $this->enrollmentRepository,
            $this->parentRepository
        )) {
            return null;
        }

        return $this->activityProgressRepository->findDocumentProgress($student->id, $contentItemId);
    }

    /**
     * Bulk fetch reading progress map for a student.
     *
     * @param int $studentId
     * @param int[] $contentItemIds
     * @return array<int, ActivityProgress>
     */
    public function getDocumentProgressMapForStudent(int $studentId, array $contentItemIds): array
    {
        return $this->activityProgressRepository->getDocumentProgressMap($studentId, $contentItemIds);
    }

    /**
     * Retrieve all logical sections for a content item.
     *
     * @return \App\Models\DocumentSection[]
     */
    public function getSectionsForContent(int $contentItemId): array
    {
        return $this->documentSectionRepository->getByContentItemId($contentItemId);
    }

    /**
     * Retrieve all logical sections for a content item along with a student's progress on each section.
     *
     * @return \App\Models\DocumentSection[]
     */
    public function getDocumentSectionsWithProgress(int $contentItemId, int $studentId): array
    {
        $sections = $this->documentSectionRepository->getByContentItemId($contentItemId);
        if (empty($sections)) {
            return [];
        }

        $sectionIds = array_map(fn($s) => $s->id, $sections);
        $progressMap = $this->activityProgressRepository->getSectionProgressMap($studentId, $sectionIds);

        $result = [];
        foreach ($sections as $sec) {
            $prog = $progressMap[$sec->id] ?? null;
            $secCopy = clone $sec;
            $secCopy->progressPercent = $prog ? (float)$prog->progressPercent : 0.0;
            $secCopy->isCompleted = $prog ? $prog->isCompleted() : false;
            $secCopy->completedAt = $prog?->completedAt;
            $result[] = $secCopy;
        }

        return $result;
    }

    /**
     * Create a logical document section for a PDF content item.
     */
    public function createSection(int $contentItemId, array $data, UserContext $actor): ServiceResult
    {
        $item = $this->contentRepository->findById($contentItemId);
        if (!$item) {
            throw new ResourceNotFoundException("Content item #{$contentItemId} not found.");
        }

        $isPdf = $item->type === ContentItem::TYPE_DOCUMENT
            && $item->file
            && ($item->file->mimeType === 'application/pdf' || str_ends_with(strtolower($item->file->originalName), '.pdf'));

        if (!$isPdf) {
            throw new DomainRuleException('Logical sections can only be created for PDF documents.');
        }

        if (!ContentPolicy::canEditContent(
            $actor,
            $item,
            null,
            $this->academicRepository,
            $this->teacherRepository
        )) {
            throw new AuthorizationException('You do not have permission to manage sections for this material.');
        }

        $title = trim((string)($data['title'] ?? ''));
        $startPage = (int)($data['start_page'] ?? 0);
        $endPage = (int)($data['end_page'] ?? 0);
        $sequenceOrder = isset($data['sequence_order']) ? (int)$data['sequence_order'] : null;

        $errors = [];
        if ($title === '') {
            $errors['title'][] = 'Section title is required.';
        } elseif (mb_strlen($title) > 200) {
            $errors['title'][] = 'Section title cannot exceed 200 characters.';
        }

        if ($startPage <= 0) {
            $errors['start_page'][] = 'Start page must be a positive integer.';
        }
        if ($endPage <= 0) {
            $errors['end_page'][] = 'End page must be a positive integer.';
        }
        if ($startPage > 0 && $endPage > 0 && $startPage > $endPage) {
            $errors['start_page'][] = 'Start page cannot be greater than end page.';
        }

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }

        // Overlap validation
        $overlap = $this->documentSectionRepository->findOverlappingSection($contentItemId, $startPage, $endPage);
        if ($overlap) {
            throw new ValidationException([
                'start_page' => "Pages {$startPage}–{$endPage} overlap with existing section \"{$overlap->title}\" (Pages {$overlap->startPage}–{$overlap->endPage}).",
            ]);
        }

        $createData = [
            'content_item_id' => $contentItemId,
            'title' => $title,
            'start_page' => $startPage,
            'end_page' => $endPage,
        ];
        if ($sequenceOrder !== null) {
            $createData['sequence_order'] = $sequenceOrder;
        }

        $section = $this->documentSectionRepository->create($createData);

        return ServiceResult::success(['section' => $section], 'Document section created successfully.');
    }

    /**
     * Update an existing logical document section.
     */
    public function updateSection(int $sectionId, array $data, UserContext $actor): ServiceResult
    {
        $section = $this->documentSectionRepository->findById($sectionId);
        if (!$section) {
            throw new ResourceNotFoundException("Document section #{$sectionId} not found.");
        }

        $item = $this->contentRepository->findById($section->contentItemId);
        if (!$item) {
            throw new ResourceNotFoundException("Content item #{$section->contentItemId} not found.");
        }

        if (!ContentPolicy::canEditContent(
            $actor,
            $item,
            null,
            $this->academicRepository,
            $this->teacherRepository
        )) {
            throw new AuthorizationException('You do not have permission to manage sections for this material.');
        }

        $title = trim((string)($data['title'] ?? $section->title));
        $startPage = isset($data['start_page']) ? (int)$data['start_page'] : $section->startPage;
        $endPage = isset($data['end_page']) ? (int)$data['end_page'] : $section->endPage;
        $sequenceOrder = isset($data['sequence_order']) ? (int)$data['sequence_order'] : $section->sequenceOrder;

        $errors = [];
        if ($title === '') {
            $errors['title'][] = 'Section title is required.';
        } elseif (mb_strlen($title) > 200) {
            $errors['title'][] = 'Section title cannot exceed 200 characters.';
        }

        if ($startPage <= 0) {
            $errors['start_page'][] = 'Start page must be a positive integer.';
        }
        if ($endPage <= 0) {
            $errors['end_page'][] = 'End page must be a positive integer.';
        }
        if ($startPage > 0 && $endPage > 0 && $startPage > $endPage) {
            $errors['start_page'][] = 'Start page cannot be greater than end page.';
        }

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }

        // Overlap validation excluding current section ID
        $overlap = $this->documentSectionRepository->findOverlappingSection($section->contentItemId, $startPage, $endPage, $sectionId);
        if ($overlap) {
            throw new ValidationException([
                'start_page' => "Pages {$startPage}–{$endPage} overlap with existing section \"{$overlap->title}\" (Pages {$overlap->startPage}–{$overlap->endPage}).",
            ]);
        }

        $updatedSection = $this->documentSectionRepository->update($sectionId, [
            'title' => $title,
            'start_page' => $startPage,
            'end_page' => $endPage,
            'sequence_order' => $sequenceOrder,
        ]);

        // Recalculate section progress for any student who has progress on this document
        $stmt = $this->pdo->prepare("
            SELECT `student_id`, `pages_read_json` FROM `learning_activity_progress`
            WHERE `activity_type` = 'document' AND `activity_id` = :content_id
        ");
        $stmt->execute([':content_id' => $section->contentItemId]);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $pages = json_decode((string)$row['pages_read_json'], true) ?: [];
            $this->activityProgressRepository->syncSectionProgress((int)$row['student_id'], $updatedSection, $pages);
        }

        return ServiceResult::success(['section' => $updatedSection], 'Document section updated successfully.');
    }

    /**
     * Delete a logical document section.
     */
    public function deleteSection(int $sectionId, UserContext $actor): ServiceResult
    {
        $section = $this->documentSectionRepository->findById($sectionId);
        if (!$section) {
            throw new ResourceNotFoundException("Document section #{$sectionId} not found.");
        }

        $item = $this->contentRepository->findById($section->contentItemId);
        if (!$item) {
            throw new ResourceNotFoundException("Content item #{$section->contentItemId} not found.");
        }

        if (!ContentPolicy::canEditContent(
            $actor,
            $item,
            null,
            $this->academicRepository,
            $this->teacherRepository
        )) {
            throw new AuthorizationException('You do not have permission to manage sections for this material.');
        }

        $this->pdo->beginTransaction();

        try {
            // Cleanup derived section progress
            $this->activityProgressRepository->deleteProgressForActivity(ActivityProgress::TYPE_DOCUMENT_SECTION, $sectionId);

            // Cleanup any module item links referencing this section
            $this->moduleRepository->deleteItemsByActivity(ActivityProgress::TYPE_DOCUMENT_SECTION, $sectionId);

            // Cleanup any prerequisite relationships where this section is target or prerequisite
            $this->prerequisiteRepository->deleteForActivity(ActivityProgress::TYPE_DOCUMENT_SECTION, $sectionId);

            // Delete section
            $this->documentSectionRepository->delete($sectionId);

            $this->pdo->commit();

            return ServiceResult::success([], 'Document section deleted successfully.');
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw new DomainRuleException('Failed to delete document section: ' . $e->getMessage());
        }
    }
}
