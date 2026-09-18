<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Domain Entity for Gamified Learning Badge (SRS §33, §57 Phase 3)
 */
#[\AllowDynamicProperties]
final class Badge
{
    public const CATEGORY_ACADEMIC = 'academic';
    public const CATEGORY_ATTENDANCE = 'attendance';
    public const CATEGORY_PROGRESSION = 'progression';
    public const CATEGORY_CITIZENSHIP = 'citizenship';

    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $slug,
        public readonly string $description,
        public readonly string $category = self::CATEGORY_ACADEMIC,
        public readonly string $iconName = 'award',
        public readonly string $colorScheme = 'brand',
        public readonly bool $isSystem = true,
        public readonly ?string $createdAt = null
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            id: (int)($data['id'] ?? 0),
            name: (string)($data['name'] ?? ''),
            slug: (string)($data['slug'] ?? ''),
            description: (string)($data['description'] ?? ''),
            category: (string)($data['category'] ?? self::CATEGORY_ACADEMIC),
            iconName: (string)($data['icon_name'] ?? 'award'),
            colorScheme: (string)($data['color_scheme'] ?? 'brand'),
            isSystem: (bool)($data['is_system'] ?? true),
            createdAt: isset($data['created_at']) ? (string)$data['created_at'] : null
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'category' => $this->category,
            'icon_name' => $this->iconName,
            'color_scheme' => $this->colorScheme,
            'is_system' => $this->isSystem,
            'created_at' => $this->createdAt,
        ];
    }
}
