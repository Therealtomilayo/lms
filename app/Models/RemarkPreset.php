<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Domain Remark Preset Entity for Configurable Teacher/Principal Suggestions
 */
final class RemarkPreset
{
    public function __construct(
        public readonly int $id,
        public readonly string $type, // 'teacher', 'principal', 'general'
        public readonly string $category, // 'excellent', 'good', 'average', 'improvement', 'conduct'
        public readonly string $text,
        public readonly bool $isActive = true,
        public readonly ?string $createdAt = null,
        public readonly ?string $updatedAt = null
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            id: (int)($data['id'] ?? 0),
            type: (string)($data['type'] ?? 'teacher'),
            category: (string)($data['category'] ?? 'general'),
            text: (string)($data['text'] ?? ''),
            isActive: !empty($data['is_active']),
            createdAt: isset($data['created_at']) ? (string)$data['created_at'] : null,
            updatedAt: isset($data['updated_at']) ? (string)$data['updated_at'] : null
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'category' => $this->category,
            'text' => $this->text,
            'is_active' => $this->isActive ? 1 : 0,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
