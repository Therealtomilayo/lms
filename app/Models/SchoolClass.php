<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Domain School Class Entity
 */
final class SchoolClass
{
    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';

    public function __construct(
        public readonly int $id,
        public readonly int $academicLevelId,
        public readonly string $name,
        public readonly ?string $sectionArm = null,
        public readonly string $status = self::STATUS_ACTIVE,
        public readonly ?string $createdAt = null,
        public readonly ?string $updatedAt = null,
        public readonly ?AcademicLevel $academicLevel = null
    ) {
    }

    public static function fromArray(array $data, ?AcademicLevel $academicLevel = null): self
    {
        return new self(
            id: (int)$data['id'],
            academicLevelId: (int)$data['academic_level_id'],
            name: (string)$data['name'],
            sectionArm: isset($data['section_arm']) && $data['section_arm'] !== '' ? (string)$data['section_arm'] : null,
            status: (string)($data['status'] ?? self::STATUS_ACTIVE),
            createdAt: isset($data['created_at']) ? (string)$data['created_at'] : null,
            updatedAt: isset($data['updated_at']) ? (string)$data['updated_at'] : null,
            academicLevel: $academicLevel
        );
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'academic_level_id' => $this->academicLevelId,
            'name' => $this->name,
            'section_arm' => $this->sectionArm,
            'status' => $this->status,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
