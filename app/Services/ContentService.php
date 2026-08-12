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
use App\Models\ContentItem;
use App\Policies\ContentPolicy;
use App\Repositories\AcademicRepository;
use App\Repositories\ContentRepository;
use App\Repositories\EnrollmentRepository;
use App\Repositories\FileRepository;
use App\Repositories\ParentRepository;
use App\Repositories\StudentRepository;
use App\Repositories\TeacherRepository;
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
        ?PDO $pdo = null
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
}
