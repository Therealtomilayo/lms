<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Domain Entity for Class Subject Discussion Reply (SRS §47, §57 Phase 3)
 */
#[\AllowDynamicProperties]
final class ClassDiscussionReply
{
    public function __construct(
        public readonly int $id,
        public readonly int $discussionId,
        public readonly int $userId,
        public readonly string $content,
        public readonly ?string $createdAt = null,
        public readonly ?string $updatedAt = null,
        public ?User $author = null
    ) {
    }

    public static function fromArray(array $data, ?User $author = null): self
    {
        return new self(
            id: (int)($data['id'] ?? 0),
            discussionId: (int)($data['discussion_id'] ?? 0),
            userId: (int)($data['user_id'] ?? 0),
            content: (string)($data['content'] ?? ''),
            createdAt: isset($data['created_at']) ? (string)$data['created_at'] : null,
            updatedAt: isset($data['updated_at']) ? (string)$data['updated_at'] : null,
            author: $author
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'discussion_id' => $this->discussionId,
            'user_id' => $this->userId,
            'content' => $this->content,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
