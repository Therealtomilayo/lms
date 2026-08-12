<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Domain ResultPublication Entity
 */
final class ResultPublication
{
    public function __construct(
        public readonly int $id,
        public readonly int $termId,
        public readonly ?int $classId = null,
        public readonly int $publishedBy = 0,
        public readonly string $publishedAt = '',
        public readonly ?string $unpublishedAt = null,
        public readonly string $status = 'published',
        public readonly ?string $reason = null,
        public readonly ?string $createdAt = null,
        public readonly ?string $updatedAt = null
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            id: (int)($data['id'] ?? 0),
            termId: (int)($data['term_id'] ?? 0),
            classId: isset($data['class_id']) && $data['class_id'] !== '' && $data['class_id'] !== null
                ? (int)$data['class_id']
                : null,
            publishedBy: (int)($data['published_by'] ?? 0),
            publishedAt: (string)($data['published_at'] ?? ''),
            unpublishedAt: isset($data['unpublished_at']) && $data['unpublished_at'] !== ''
                ? (string)$data['unpublished_at']
                : null,
            status: (string)($data['status'] ?? 'published'),
            reason: isset($data['reason']) && $data['reason'] !== '' ? (string)$data['reason'] : null,
            createdAt: isset($data['created_at']) ? (string)$data['created_at'] : null,
            updatedAt: isset($data['updated_at']) ? (string)$data['updated_at'] : null
        );
    }

    public function isPublished(): bool
    {
        return $this->status === 'published';
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'term_id' => $this->termId,
            'class_id' => $this->classId,
            'published_by' => $this->publishedBy,
            'published_at' => $this->publishedAt,
            'unpublished_at' => $this->unpublishedAt,
            'status' => $this->status,
            'reason' => $this->reason,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
