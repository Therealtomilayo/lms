<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Domain Teacher Entity
 */
final class Teacher
{
    public function __construct(
        public readonly int $id,
        public readonly int $userId,
        public readonly string $staffId,
        public readonly ?string $createdAt = null,
        public readonly ?string $updatedAt = null,
        public readonly ?User $user = null
    ) {
    }

    public static function fromArray(array $data, ?User $user = null): self
    {
        return new self(
            id: (int)$data['id'],
            userId: (int)$data['user_id'],
            staffId: (string)$data['staff_id'],
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
            ]) : null)
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->userId,
            'staff_id' => $this->staffId,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
