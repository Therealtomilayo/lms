<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Domain User Entity
 */
final class User
{
    /**
     * @param string[] $roles
     */
    public function __construct(
        public readonly int $id,
        public readonly string $uuid,
        public readonly string $name,
        public readonly string $email,
        public readonly ?string $phone = null,
        public readonly string $passwordHash = '',
        public readonly string $status = 'active',
        public readonly bool $mustChangePassword = false,
        public readonly ?string $createdAt = null,
        public readonly ?string $updatedAt = null,
        public readonly array $roles = []
    ) {
    }

    public static function fromArray(array $data, array $roles = []): self
    {
        $rolesList = !empty($roles) ? $roles : ($data['roles'] ?? []);

        return new self(
            id: (int)($data['id'] ?? 0),
            uuid: (string)($data['uuid'] ?? ''),
            name: (string)($data['name'] ?? ''),
            email: (string)($data['email'] ?? ''),
            phone: isset($data['phone']) && $data['phone'] !== '' ? (string)$data['phone'] : null,
            passwordHash: (string)($data['password_hash'] ?? ''),
            status: (string)($data['status'] ?? 'active'),
            mustChangePassword: (bool)($data['must_change_password'] ?? false),
            createdAt: isset($data['created_at']) ? (string)$data['created_at'] : null,
            updatedAt: isset($data['updated_at']) ? (string)$data['updated_at'] : null,
            roles: $rolesList
        );
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isSuspended(): bool
    {
        return $this->status === 'suspended';
    }

    public function isInactive(): bool
    {
        return $this->status === 'inactive';
    }

    public function hasRole(string $role): bool
    {
        return in_array($role, $this->roles, true);
    }

    public function hasAnyRole(array $roles): bool
    {
        foreach ($roles as $role) {
            if ($this->hasRole($role)) {
                return true;
            }
        }

        return false;
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole('super_admin');
    }

    public function isAdmin(): bool
    {
        return $this->hasRole('admin') || $this->hasRole('super_admin');
    }

    public function isTeacher(): bool
    {
        return $this->hasRole('teacher');
    }

    public function isStudent(): bool
    {
        return $this->hasRole('student');
    }

    public function isParent(): bool
    {
        return $this->hasRole('parent');
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'status' => $this->status,
            'must_change_password' => $this->mustChangePassword,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
            'roles' => $this->roles,
        ];
    }
}
