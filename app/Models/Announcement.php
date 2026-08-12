<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Domain Announcement Entity
 */
final class Announcement
{
    public function __construct(
        public readonly int $id,
        public readonly int $authorId,
        public readonly string $scope, // 'school', 'class', 'class_subject'
        public readonly ?int $scopeId = null,
        public readonly string $title = '',
        public readonly string $body = '',
        public readonly ?string $publishedAt = null,
        public readonly ?string $expiresAt = null,
        public readonly ?string $createdAt = null,
        public readonly ?string $updatedAt = null,
        // Optional hydrated fields
        public readonly ?string $authorName = null,
        public readonly ?string $targetName = null,
        public readonly bool $isRead = false,
        public readonly ?string $readAt = null
    ) {
    }

    public static function fromArray(array $data): self
    {
        $scope = (string)($data['scope'] ?? 'school');
        $targetName = isset($data['target_name']) && $data['target_name'] !== '' ? (string)$data['target_name'] : null;
        if ($targetName === null) {
            if ($scope === 'school') {
                $targetName = 'School-wide';
            } elseif ($scope === 'class' && !empty($data['class_name'])) {
                $targetName = (string)$data['class_name'];
            } elseif ($scope === 'class_subject' && (!empty($data['cs_class_name']) || !empty($data['cs_subject_name']))) {
                $targetName = trim(($data['cs_class_name'] ?? '') . ' - ' . ($data['cs_subject_name'] ?? ''));
            }
        }

        return new self(
            id: (int)($data['id'] ?? 0),
            authorId: (int)($data['author_id'] ?? 0),
            scope: $scope,
            scopeId: isset($data['scope_id']) && $data['scope_id'] !== null && $data['scope_id'] !== '' ? (int)$data['scope_id'] : null,
            title: (string)($data['title'] ?? ''),
            body: (string)($data['body'] ?? ''),
            publishedAt: isset($data['published_at']) ? (string)$data['published_at'] : null,
            expiresAt: isset($data['expires_at']) ? (string)$data['expires_at'] : null,
            createdAt: isset($data['created_at']) ? (string)$data['created_at'] : null,
            updatedAt: isset($data['updated_at']) ? (string)$data['updated_at'] : null,
            authorName: isset($data['author_name']) ? (string)$data['author_name'] : null,
            targetName: $targetName,
            isRead: (bool)($data['is_read'] ?? false),
            readAt: isset($data['read_at']) ? (string)$data['read_at'] : null
        );
    }

    public function isSchoolWide(): bool
    {
        return $this->scope === 'school';
    }

    public function isClassScoped(): bool
    {
        return $this->scope === 'class';
    }

    public function isSubjectScoped(): bool
    {
        return $this->scope === 'class_subject';
    }

    public function isPublished(): bool
    {
        if ($this->publishedAt === null) {
            return false;
        }

        return strtotime($this->publishedAt) <= time();
    }

    public function isExpired(): bool
    {
        if ($this->expiresAt === null) {
            return false;
        }

        return strtotime($this->expiresAt) < time();
    }

    public function isActive(): bool
    {
        return $this->isPublished() && !$this->isExpired();
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'author_id' => $this->authorId,
            'scope' => $this->scope,
            'scope_id' => $this->scopeId,
            'title' => $this->title,
            'body' => $this->body,
            'published_at' => $this->publishedAt,
            'expires_at' => $this->expiresAt,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
            'author_name' => $this->authorName,
            'target_name' => $this->targetName,
            'is_read' => $this->isRead,
            'read_at' => $this->readAt,
        ];
    }
}
