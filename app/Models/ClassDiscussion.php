<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Domain Entity for Class Subject Discussion Board Topic (SRS §47, §57 Phase 3)
 */
#[\AllowDynamicProperties]
final class ClassDiscussion
{
    /**
     * @param ClassDiscussionReply[] $replies
     */
    public function __construct(
        public readonly int $id,
        public readonly int $classSubjectId,
        public readonly int $userId,
        public readonly string $title,
        public readonly string $content,
        public readonly bool $isPinned = false,
        public readonly bool $isLocked = false,
        public readonly ?string $createdAt = null,
        public readonly ?string $updatedAt = null,
        public ?User $author = null,
        public array $replies = [],
        public int $replyCount = 0
    ) {
    }

    public static function fromArray(array $data, ?User $author = null, array $replies = []): self
    {
        return new self(
            id: (int)($data['id'] ?? 0),
            classSubjectId: (int)($data['class_subject_id'] ?? 0),
            userId: (int)($data['user_id'] ?? 0),
            title: (string)($data['title'] ?? ''),
            content: (string)($data['content'] ?? ''),
            isPinned: (bool)($data['is_pinned'] ?? false),
            isLocked: (bool)($data['is_locked'] ?? false),
            createdAt: isset($data['created_at']) ? (string)$data['created_at'] : null,
            updatedAt: isset($data['updated_at']) ? (string)$data['updated_at'] : null,
            author: $author,
            replies: $replies,
            replyCount: (int)($data['reply_count'] ?? count($replies))
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'class_subject_id' => $this->classSubjectId,
            'user_id' => $this->userId,
            'title' => $this->title,
            'content' => $this->content,
            'is_pinned' => $this->isPinned,
            'is_locked' => $this->isLocked,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
            'reply_count' => $this->replyCount,
        ];
    }
}
