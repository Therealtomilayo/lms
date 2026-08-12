<?php

declare(strict_types=1);

namespace App\Models;

use DateTimeImmutable;
use DateTimeInterface;

/**
 * Domain Assignment Entity
 * Represents coursework created within a ClassSubject and Term.
 */
final class Assignment
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_ARCHIVED = 'archived';

    public function __construct(
        public readonly int $id,
        public readonly int $classSubjectId,
        public readonly int $termId,
        public readonly ?int $assessmentCategoryId,
        public readonly int $teacherId,
        public readonly ?string $topic,
        public readonly string $title,
        public readonly string $instructions,
        public readonly string $dueAt,
        public readonly float $maxScore,
        public readonly ?int $fileId = null,
        public readonly string $status = self::STATUS_PUBLISHED,
        public readonly ?string $createdAt = null,
        public readonly ?string $updatedAt = null,
        public readonly ?ClassSubject $classSubject = null,
        public readonly ?Teacher $teacher = null,
        public readonly ?Term $term = null,
        public readonly ?FileRecord $file = null
    ) {
    }

    public static function fromArray(
        array $data,
        ?ClassSubject $classSubject = null,
        ?Teacher $teacher = null,
        ?Term $term = null,
        ?FileRecord $file = null
    ): self {
        $file = $file ?? (!empty($data['file_uuid']) || !empty($data['file_storage_key']) || !empty($data['file_original_name']) ? FileRecord::fromArray([
            'id' => $data['file_id'],
            'uuid' => $data['file_uuid'] ?? '',
            'storage_key' => $data['file_storage_key'] ?? '',
            'original_name' => $data['file_original_name'] ?? 'attachment',
            'mime_type' => $data['file_mime_type'] ?? 'application/octet-stream',
            'size_bytes' => $data['file_size_bytes'] ?? 0,
            'sha256' => $data['file_sha256'] ?? '',
            'uploaded_by' => $data['file_uploaded_by'] ?? 0,
            'owner_type' => $data['file_owner_type'] ?? 'assignment',
            'owner_id' => $data['file_owner_id'] ?? (int)$data['id'],
            'created_at' => $data['file_created_at'] ?? null,
            'deleted_at' => $data['file_deleted_at'] ?? null,
        ]) : null);

        $teacher = $teacher ?? (!empty($data['teacher_name']) ? Teacher::fromArray([
            'id' => (int)$data['teacher_id'],
            'user_id' => (int)($data['teacher_user_id'] ?? 0),
            'staff_id' => (string)($data['teacher_staff_id'] ?? ''),
            'user_name' => (string)$data['teacher_name'],
            'user_email' => (string)($data['teacher_email'] ?? ''),
        ]) : null);

        $classSubject = $classSubject ?? (!empty($data['subject_name']) || !empty($data['class_name']) ? ClassSubject::fromArray([
            'id' => (int)$data['class_subject_id'],
            'session_id' => (int)($data['session_id'] ?? 0),
            'class_id' => (int)($data['class_id'] ?? 0),
            'subject_id' => (int)($data['subject_id'] ?? 0),
            'teacher_id' => (int)$data['teacher_id'],
            'subject_name' => $data['subject_name'] ?? '',
            'subject_code' => $data['subject_code'] ?? '',
            'class_name' => $data['class_name'] ?? '',
            'section_arm' => $data['section_arm'] ?? null,
        ]) : null);

        $term = $term ?? (!empty($data['term_name']) ? Term::fromArray([
            'id' => (int)$data['term_id'],
            'session_id' => (int)($data['term_session_id'] ?? ($data['session_id'] ?? 0)),
            'name' => (string)$data['term_name'],
            'start_date' => (string)($data['term_start_date'] ?? ''),
            'end_date' => (string)($data['term_end_date'] ?? ''),
            'status' => (string)($data['term_status'] ?? 'active'),
            'is_current' => (bool)($data['term_is_current'] ?? 0),
        ]) : null);

        return new self(
            id: (int)$data['id'],
            classSubjectId: (int)$data['class_subject_id'],
            termId: (int)$data['term_id'],
            assessmentCategoryId: isset($data['assessment_category_id']) && $data['assessment_category_id'] !== '' && $data['assessment_category_id'] !== null ? (int)$data['assessment_category_id'] : null,
            teacherId: (int)$data['teacher_id'],
            topic: isset($data['topic']) && trim((string)$data['topic']) !== '' ? trim((string)$data['topic']) : null,
            title: (string)$data['title'],
            instructions: (string)($data['instructions'] ?? ($data['description'] ?? '')),
            dueAt: (string)$data['due_at'],
            maxScore: (float)($data['max_score'] ?? 100.00),
            fileId: isset($data['file_id']) && $data['file_id'] !== '' && $data['file_id'] !== null ? (int)$data['file_id'] : null,
            status: (string)($data['status'] ?? self::STATUS_PUBLISHED),
            createdAt: isset($data['created_at']) ? (string)$data['created_at'] : null,
            updatedAt: isset($data['updated_at']) ? (string)$data['updated_at'] : null,
            classSubject: $classSubject,
            teacher: $teacher,
            term: $term,
            file: $file
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'class_subject_id' => $this->classSubjectId,
            'term_id' => $this->termId,
            'assessment_category_id' => $this->assessmentCategoryId,
            'teacher_id' => $this->teacherId,
            'topic' => $this->topic,
            'title' => $this->title,
            'instructions' => $this->instructions,
            'due_at' => $this->dueAt,
            'max_score' => $this->maxScore,
            'file_id' => $this->fileId,
            'status' => $this->status,
            'is_published' => $this->isPublished(),
            'is_past_due' => $this->isPastDue(),
            'has_file' => $this->hasFile(),
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
            'file' => $this->file?->toArray(),
        ];
    }

    public function isPastDue(?DateTimeInterface $now = null): bool
    {
        $currentTime = $now ? $now->getTimestamp() : (new DateTimeImmutable())->getTimestamp();
        $dueTime = strtotime($this->dueAt);

        return $dueTime !== false && $currentTime > $dueTime;
    }

    public function hasFile(): bool
    {
        return $this->fileId !== null && $this->fileId > 0;
    }

    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED;
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isArchived(): bool
    {
        return $this->status === self::STATUS_ARCHIVED;
    }
}
