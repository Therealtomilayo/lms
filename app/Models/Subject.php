<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Domain Subject Entity
 */
final class Subject
{
    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';

    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $code,
        public readonly string $status = self::STATUS_ACTIVE,
        public readonly ?string $createdAt = null,
        public readonly ?string $updatedAt = null
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            id: (int)$data['id'],
            name: (string)$data['name'],
            code: strtoupper(trim((string)$data['code'])),
            status: (string)($data['status'] ?? self::STATUS_ACTIVE),
            createdAt: isset($data['created_at']) ? (string)$data['created_at'] : null,
            updatedAt: isset($data['updated_at']) ? (string)$data['updated_at'] : null
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
            'name' => $this->name,
            'code' => $this->code,
            'status' => $this->status,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
