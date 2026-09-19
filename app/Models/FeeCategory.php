<?php

declare(strict_types=1);

namespace App\Models;

final class FeeCategory
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly ?string $description = null,
        public readonly bool $isActive = true,
        public readonly ?string $createdAt = null,
        public readonly ?string $updatedAt = null
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            id: (int)$data['id'],
            name: (string)$data['name'],
            description: $data['description'] ?? null,
            isActive: (bool)($data['is_active'] ?? true),
            createdAt: $data['created_at'] ?? null,
            updatedAt: $data['updated_at'] ?? null
        );
    }
}
