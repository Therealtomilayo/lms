<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Domain Term Entity
 */
final class Term
{
    public const STATUS_PLANNING = 'planning';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_GRADING_OPEN = 'grading_open';
    public const STATUS_GRADING_LOCKED = 'grading_locked';
    public const STATUS_ARCHIVED = 'archived';

    public function __construct(
        public readonly int $id,
        public readonly int $sessionId,
        public readonly string $name,
        public readonly string $startDate,
        public readonly string $endDate,
        public readonly ?string $gradingStartsAt = null,
        public readonly ?string $gradingEndsAt = null,
        public readonly string $status = self::STATUS_PLANNING,
        public readonly ?string $createdAt = null,
        public readonly ?string $updatedAt = null
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            id: (int)$data['id'],
            sessionId: (int)($data['session_id'] ?? 0),
            name: (string)($data['name'] ?? ''),
            startDate: (string)($data['start_date'] ?? ''),
            endDate: (string)($data['end_date'] ?? ''),
            gradingStartsAt: isset($data['grading_starts_at']) && $data['grading_starts_at'] !== '' ? (string)$data['grading_starts_at'] : null,
            gradingEndsAt: isset($data['grading_ends_at']) && $data['grading_ends_at'] !== '' ? (string)$data['grading_ends_at'] : null,
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

    public function isGradingOpen(): bool
    {
        return $this->status === self::STATUS_GRADING_OPEN;
    }

    public function isGradingLocked(): bool
    {
        return $this->status === self::STATUS_GRADING_LOCKED;
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
            self::STATUS_ACTIVE => in_array($targetStatus, [self::STATUS_GRADING_OPEN, self::STATUS_ARCHIVED], true),
            self::STATUS_GRADING_OPEN => in_array($targetStatus, [self::STATUS_GRADING_LOCKED, self::STATUS_ACTIVE], true),
            self::STATUS_GRADING_LOCKED => in_array($targetStatus, [self::STATUS_GRADING_OPEN, self::STATUS_ARCHIVED], true),
            self::STATUS_ARCHIVED => false, // Immutable terminal state
            default => false,
        };
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'session_id' => $this->sessionId,
            'name' => $this->name,
            'start_date' => $this->startDate,
            'end_date' => $this->endDate,
            'grading_starts_at' => $this->gradingStartsAt,
            'grading_ends_at' => $this->gradingEndsAt,
            'status' => $this->status,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }

    public function __get(string $name): mixed
    {
        if ($name === 'isCurrent') {
            return $this->isActive();
        }
        return null;
    }

    public function __isset(string $name): bool
    {
        return $name === 'isCurrent';
    }
}
