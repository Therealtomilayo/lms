<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Domain Parent Profile Entity
 * Represents either an active portal user parent (user_id != null)
 * or a school guardian contact record (user_id = null).
 */
final class ParentProfile
{
    /**
     * @param Student[] $students
     */
    public function __construct(
        public readonly int $id,
        public readonly ?int $userId = null,
        public readonly ?string $rawName = null,
        public readonly ?string $rawPhone = null,
        public readonly ?string $rawEmail = null,
        public readonly ?string $createdAt = null,
        public readonly ?string $updatedAt = null,
        public readonly ?User $user = null,
        public readonly array $students = []
    ) {
    }

    public static function fromArray(array $data, ?User $user = null, array $students = []): self
    {
        $userId = isset($data['user_id']) && $data['user_id'] !== '' && $data['user_id'] !== null ? (int)$data['user_id'] : null;
        $rawName = !empty($data['name']) ? (string)$data['name'] : (!empty($data['user_name']) ? (string)$data['user_name'] : null);
        $rawPhone = !empty($data['phone']) ? (string)$data['phone'] : (!empty($data['user_phone']) ? (string)$data['user_phone'] : null);
        $rawEmail = !empty($data['email']) ? (string)$data['email'] : (!empty($data['user_email']) ? (string)$data['user_email'] : null);

        $builtUser = $user;
        if ($builtUser === null && $userId !== null && (!empty($data['user_email']) || !empty($data['email']))) {
            $builtUser = User::fromArray([
                'id' => $userId,
                'uuid' => $data['uuid'] ?? '',
                'name' => $data['user_name'] ?? $rawName ?? '',
                'email' => $data['user_email'] ?? $rawEmail ?? '',
                'phone' => $data['user_phone'] ?? $rawPhone ?? null,
                'password_hash' => $data['password_hash'] ?? '',
                'status' => $data['user_status'] ?? $data['status'] ?? 'active',
                'must_change_password' => $data['must_change_password'] ?? 0,
                'created_at' => $data['user_created_at'] ?? $data['created_at'] ?? null,
                'updated_at' => $data['user_updated_at'] ?? $data['updated_at'] ?? null,
                'avatar_url' => $data['user_avatar'] ?? $data['avatar_url'] ?? null,
            ]);
        }

        return new self(
            id: (int)$data['id'],
            userId: $userId,
            rawName: $rawName,
            rawPhone: $rawPhone,
            rawEmail: $rawEmail,
            createdAt: isset($data['created_at']) ? (string)$data['created_at'] : null,
            updatedAt: isset($data['updated_at']) ? (string)$data['updated_at'] : null,
            user: $builtUser,
            students: $students
        );
    }

    public function isPortalActivated(): bool
    {
        return $this->userId !== null && $this->userId > 0;
    }

    public function getAvatarUrl(): ?string
    {
        return $this->user?->avatarUrl;
    }

    public function getName(): string
    {
        if ($this->user !== null && trim($this->user->name) !== '') {
            return $this->user->name;
        }
        return $this->rawName ?? '';
    }

    public function getPhone(): ?string
    {
        if ($this->user !== null && !empty($this->user->phone) && trim($this->user->phone) !== '') {
            return $this->user->phone;
        }
        return $this->rawPhone;
    }

    public function getEmail(): ?string
    {
        if ($this->user !== null && !empty($this->user->email) && trim($this->user->email) !== '') {
            return $this->user->email;
        }
        return $this->rawEmail;
    }

    public function __get(string $name): mixed
    {
        return match ($name) {
            'name', 'displayName', 'display_name', 'user_name', 'userName' => $this->getName(),
            'email', 'user_email', 'userEmail' => $this->getEmail(),
            'phone', 'user_phone', 'userPhone' => $this->getPhone(),
            default => null,
        };
    }

    public function __isset(string $name): bool
    {
        return in_array($name, ['name', 'displayName', 'display_name', 'user_name', 'userName', 'email', 'user_email', 'userEmail', 'phone', 'user_phone', 'userPhone'], true);
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->userId,
            'is_activated' => $this->isPortalActivated(),
            'name' => $this->getName(),
            'user_name' => $this->getName(),
            'phone' => $this->getPhone(),
            'user_phone' => $this->getPhone(),
            'email' => $this->getEmail(),
            'user_email' => $this->getEmail(),
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
