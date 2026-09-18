<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Domain Entity for Activity Prerequisite Relationships
 */
final class ActivityPrerequisite
{
    public const REQUIREMENT_COMPLETION = 'completion';

    public function __construct(
        public readonly int $id,
        public readonly string $activityType,
        public readonly int $activityId,
        public readonly string $prerequisiteActivityType,
        public readonly int $prerequisiteActivityId,
        public readonly string $requirementType = self::REQUIREMENT_COMPLETION,
        public readonly ?string $createdAt = null,
        public readonly ?string $updatedAt = null
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            id: (int)($data['id'] ?? 0),
            activityType: (string)($data['activity_type'] ?? ''),
            activityId: (int)($data['activity_id'] ?? 0),
            prerequisiteActivityType: (string)($data['prerequisite_activity_type'] ?? ''),
            prerequisiteActivityId: (int)($data['prerequisite_activity_id'] ?? 0),
            requirementType: (string)($data['requirement_type'] ?? self::REQUIREMENT_COMPLETION),
            createdAt: $data['created_at'] ?? null,
            updatedAt: $data['updated_at'] ?? null
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'activity_type' => $this->activityType,
            'activity_id' => $this->activityId,
            'prerequisite_activity_type' => $this->prerequisiteActivityType,
            'prerequisite_activity_id' => $this->prerequisiteActivityId,
            'requirement_type' => $this->requirementType,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
