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
        public readonly ?AcademicLevel $academicLevel = null,
        public readonly ?int $formTeacherId = null,
        public readonly ?string $formTeacherName = null,
        public readonly ?string $formTeacherStaffId = null
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
            academicLevel: $academicLevel,
            formTeacherId: isset($data['form_teacher_id']) && $data['form_teacher_id'] !== '' && $data['form_teacher_id'] !== null ? (int)$data['form_teacher_id'] : null,
            formTeacherName: !empty($data['form_teacher_name']) ? (string)$data['form_teacher_name'] : null,
            formTeacherStaffId: !empty($data['form_teacher_staff_id']) ? (string)$data['form_teacher_staff_id'] : null
        );
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function getFullName(): string
    {
        if (!empty($this->sectionArm)) {
            $arm = trim($this->sectionArm);
            if (str_ends_with(strtoupper($this->name), strtoupper($arm))) {
                return $this->name;
            }
            return $this->name . ' (' . $arm . ')';
        }
        return $this->name;
    }

    public function getDisplayName(): string
    {
        return $this->getFullName();
    }

    public function __get(string $name): mixed
    {
        if ($name === 'fullName' || $name === 'full_name' || $name === 'displayName') {
            return $this->getFullName();
        }
        return null;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'academic_level_id' => $this->academicLevelId,
            'name' => $this->name,
            'section_arm' => $this->sectionArm,
            'full_name' => $this->getFullName(),
            'status' => $this->status,
            'form_teacher_id' => $this->formTeacherId,
            'form_teacher_name' => $this->formTeacherName,
            'form_teacher_staff_id' => $this->formTeacherStaffId,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}

