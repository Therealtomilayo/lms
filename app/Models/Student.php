<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Domain Student Entity
 */
final class Student
{
    public function __construct(
        public readonly int $id,
        public readonly int $userId,
        public readonly string $admissionNumber,
        public readonly ?string $dateOfBirth = null,
        public readonly ?string $gender = null,
        public readonly ?int $currentClassId = null,
        public readonly ?string $createdAt = null,
        public readonly ?string $updatedAt = null,
        public readonly ?User $user = null,
        public readonly ?SchoolClass $currentClass = null
    ) {
    }

    public static function fromArray(array $data, ?User $user = null, ?SchoolClass $currentClass = null): self
    {
        return new self(
            id: (int)$data['id'],
            userId: (int)($data['user_id'] ?? 0),
            admissionNumber: (string)($data['admission_number'] ?? ''),
            dateOfBirth: $data['date_of_birth'] ?? null,
            gender: $data['gender'] ?? null,
            currentClassId: isset($data['current_class_id']) && $data['current_class_id'] !== null ? (int)$data['current_class_id'] : null,
            createdAt: isset($data['created_at']) ? (string)$data['created_at'] : null,
            updatedAt: isset($data['updated_at']) ? (string)$data['updated_at'] : null,
            user: $user ?? (!empty($data['user_name']) || !empty($data['name']) || !empty($data['user_email']) || !empty($data['email']) ? User::fromArray([
                'id' => $data['user_id'] ?? $data['id'],
                'uuid' => $data['uuid'] ?? '',
                'name' => $data['user_name'] ?? $data['name'] ?? '',
                'email' => $data['user_email'] ?? $data['email'] ?? '',
                'phone' => $data['phone'] ?? null,
                'password_hash' => $data['password_hash'] ?? '',
                'status' => $data['user_status'] ?? $data['status'] ?? 'active',
                'must_change_password' => $data['must_change_password'] ?? 0,
                'created_at' => $data['user_created_at'] ?? $data['created_at'] ?? null,
                'updated_at' => $data['user_updated_at'] ?? $data['updated_at'] ?? null,
            ]) : null),
            currentClass: $currentClass ?? (!empty($data['class_name']) ? SchoolClass::fromArray([
                'id' => $data['current_class_id'] ?? $data['class_id'],
                'academic_level_id' => $data['academic_level_id'] ?? 0,
                'name' => $data['class_name'],
                'section_arm' => $data['section_arm'] ?? null,
                'status' => $data['class_status'] ?? 'active',
            ]) : null)
        );
    }

    public function __get(string $name): mixed
    {
        return match ($name) {
            'name', 'user_name', 'userName' => $this->user?->name ?? '',
            'email', 'user_email', 'userEmail' => $this->user?->email ?? '',
            'phone', 'user_phone', 'userPhone' => $this->user?->phone,
            'class_name', 'className' => $this->currentClass?->name ?? '',
            default => null,
        };
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->userId,
            'admission_number' => $this->admissionNumber,
            'date_of_birth' => $this->dateOfBirth,
            'gender' => $this->gender,
            'current_class_id' => $this->currentClassId,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
            'user_name' => $this->user?->name,
            'user_email' => $this->user?->email,
        ];
    }
}
