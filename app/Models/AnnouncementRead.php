<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Domain Announcement Read Entity
 */
final class AnnouncementRead
{
    public function __construct(
        public readonly int $id,
        public readonly int $announcementId,
        public readonly int $userId,
        public readonly string $readAt,
        public readonly ?string $createdAt = null
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            id: (int)($data['id'] ?? 0),
            announcementId: (int)($data['announcement_id'] ?? 0),
            userId: (int)($data['user_id'] ?? 0),
            readAt: (string)($data['read_at'] ?? ''),
            createdAt: isset($data['created_at']) ? (string)$data['created_at'] : null
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'announcement_id' => $this->announcementId,
            'user_id' => $this->userId,
            'read_at' => $this->readAt,
            'created_at' => $this->createdAt,
        ];
    }
}
