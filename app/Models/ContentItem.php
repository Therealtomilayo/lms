<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Domain Content Item Entity
 * Represents lesson notes, documents, video lessons, and external links scoped to a ClassSubject.
 */
final class ContentItem
{
    public const TYPE_NOTE = 'note';
    public const TYPE_VIDEO = 'video';
    public const TYPE_LINK = 'link';
    public const TYPE_DOCUMENT = 'document';

    public const VALID_TYPES = [
        self::TYPE_NOTE,
        self::TYPE_VIDEO,
        self::TYPE_LINK,
        self::TYPE_DOCUMENT,
    ];

    public function __construct(
        public readonly int $id,
        public readonly int $classSubjectId,
        public readonly int $teacherId,
        public readonly ?string $topic,
        public readonly string $title,
        public readonly ?string $description,
        public readonly string $type,
        public readonly ?int $fileId = null,
        public readonly ?string $externalUrl = null,
        public readonly ?string $publishedAt = null,
        public readonly ?string $createdAt = null,
        public readonly ?string $updatedAt = null,
        public readonly ?ClassSubject $classSubject = null,
        public readonly ?Teacher $teacher = null,
        public readonly ?FileRecord $file = null
    ) {
    }

    public static function fromArray(
        array $data,
        ?ClassSubject $classSubject = null,
        ?Teacher $teacher = null,
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
            'owner_type' => $data['file_owner_type'] ?? 'content_item',
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

        return new self(
            id: (int)$data['id'],
            classSubjectId: (int)$data['class_subject_id'],
            teacherId: (int)$data['teacher_id'],
            topic: isset($data['topic']) && trim((string)$data['topic']) !== '' ? trim((string)$data['topic']) : null,
            title: (string)$data['title'],
            description: isset($data['description']) ? (string)$data['description'] : null,
            type: (string)$data['type'],
            fileId: isset($data['file_id']) && $data['file_id'] !== '' && $data['file_id'] !== null ? (int)$data['file_id'] : null,
            externalUrl: isset($data['external_url']) && trim((string)$data['external_url']) !== '' ? trim((string)$data['external_url']) : null,
            publishedAt: isset($data['published_at']) ? (string)$data['published_at'] : null,
            createdAt: isset($data['created_at']) ? (string)$data['created_at'] : null,
            updatedAt: isset($data['updated_at']) ? (string)$data['updated_at'] : null,
            classSubject: $classSubject,
            teacher: $teacher,
            file: $file
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'class_subject_id' => $this->classSubjectId,
            'teacher_id' => $this->teacherId,
            'topic' => $this->topic,
            'title' => $this->title,
            'description' => $this->description,
            'type' => $this->type,
            'file_id' => $this->fileId,
            'external_url' => $this->externalUrl,
            'published_at' => $this->publishedAt,
            'is_published' => $this->isPublished(),
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
            'file' => $this->file?->toArray(),
        ];
    }

    public function isPublished(): bool
    {
        return $this->publishedAt !== null && $this->publishedAt !== '';
    }

    public function isDraft(): bool
    {
        return !$this->isPublished();
    }
}
