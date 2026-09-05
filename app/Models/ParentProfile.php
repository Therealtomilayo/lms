<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Domain Parent Profile Entity
 */
final class ParentProfile
{
    /**
     * @param Student[] $students
     */
    public function __construct(
        public readonly int $id,
        public readonly int $userId,
        public readonly ?string $createdAt = null,
        public readonly ?string $updatedAt = null,
        public readonly ?User $user = null,
        public readonly array $students = []
    ) {
    }

    public static function fromArray(array $data, ?User $user = null, array $students = []): self
    {
        return new self(
            id: (int)$data['id'],
            userId: (int)$data['user_id'],
            createdAt: isset($data['created_at']) ? (string)$data['created_at'] : null,
            updatedAt: isset($data['updated_at']) ? (string)$data['updated_at'] : null,
            user: $user ?? (!empty($data['user_name']) || !empty($data['name']) || !empty($data['user_email']) || !empty($data['email']) ? User::fromArray([
                'id' => $data['user_id'] ?? $data['id'],
                'uuid' => $data['uuid'] ?? '',
                'name' => $data['user_name'] ?? $data['name'] ?? '',
                'email' => $data['user_email'] ?? $data['email'] ?? '',
                'phone' => $data['user_phone'] ?? $data['phone'] ?? null,
                'password_hash' => $data['password_hash'] ?? '',
                'status' => $data['user_status'] ?? $data['status'] ?? 'active',
                'must_change_password' => $data['must_change_password'] ?? 0,
                'created_at' => $data['user_created_at'] ?? $data['created_at'] ?? null,
                'updated_at' => $data['user_updated_at'] ?? $data['updated_at'] ?? null,
            ]) : null),
            students: $students
        );
    }

    public function __get(string $name): mixed
    {
        return match ($name) {
            'name', 'user_name', 'userName' => $this->user?->name ?? '',
            'email', 'user_email', 'userEmail' => $this->user?->email ?? '',
            'phone', 'user_phone', 'userPhone' => $this->user?->phone,
            default => null,
        };
    }

    public function __isset(string $name): bool
    {
        return in_array($name, ['name', 'user_name', 'userName', 'email', 'user_email', 'userEmail', 'phone', 'user_phone', 'userPhone'], true);
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->userId,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
            'user_name' => $this->user?->name,
            'user_email' => $this->user?->email,
        ];
    }
}
