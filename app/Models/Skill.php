<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Domain Skill Entity for Affective & Psychomotor Domains
 */
final class Skill
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $category, // 'psychomotor' or 'affective'
        public readonly int $displayOrder = 1,
        public readonly string $status = 'active',
        public readonly ?string $createdAt = null,
        public readonly ?string $updatedAt = null
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            id: (int)($data['id'] ?? 0),
            name: (string)($data['name'] ?? ''),
            category: (string)($data['category'] ?? 'psychomotor'),
            displayOrder: (int)($data['display_order'] ?? 1),
            status: (string)($data['status'] ?? 'active'),
            createdAt: isset($data['created_at']) ? (string)$data['created_at'] : null,
            updatedAt: isset($data['updated_at']) ? (string)$data['updated_at'] : null
        );
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isPsychomotor(): bool
    {
        return strtolower($this->category) === 'psychomotor';
    }

    public function isAffective(): bool
    {
        return strtolower($this->category) === 'affective';
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'category' => $this->category,
            'display_order' => $this->displayOrder,
            'status' => $this->status,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
