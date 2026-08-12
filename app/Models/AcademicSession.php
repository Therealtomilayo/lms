<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Domain Academic Session Entity
 */
final class AcademicSession
{
    public const STATUS_PLANNING = 'planning';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_ARCHIVED = 'archived';

    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $startDate,
        public readonly string $endDate,
        public readonly string $status = self::STATUS_PLANNING,
        public readonly ?string $createdAt = null,
        public readonly ?string $updatedAt = null
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            id: (int)$data['id'],
            name: (string)$data['name'],
            startDate: (string)($data['start_date'] ?? ''),
            endDate: (string)($data['end_date'] ?? ''),
            status: (string)($data['status'] ?? self::STATUS_PLANNING),
            createdAt: isset($data['created_at']) ? (string)$data['created_at'] : null,
            updatedAt: isset($data['updated_at']) ? (string)$data['updated_at'] : null
        );
    }

    public function isPlanning(): bool
    {
        return $this->status === self::STATUS_PLANNING;
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isArchived(): bool
    {
        return $this->status === self::STATUS_ARCHIVED;
    }

    public function canTransitionTo(string $targetStatus): bool
    {
        if ($this->status === $targetStatus) {
            return false;
        }

        return match ($this->status) {
            self::STATUS_PLANNING => in_array($targetStatus, [self::STATUS_ACTIVE, self::STATUS_ARCHIVED], true),
            self::STATUS_ACTIVE => $targetStatus === self::STATUS_ARCHIVED,
            self::STATUS_ARCHIVED => false, // Immutable terminal state
            default => false,
        };
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'start_date' => $this->startDate,
            'end_date' => $this->endDate,
            'status' => $this->status,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
