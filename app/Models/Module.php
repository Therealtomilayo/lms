<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Domain Entity for Course Learning Module
 * Organizes learning activities into structured sequential instructional units.
 */
#[\AllowDynamicProperties]
final class Module
{
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_DRAFT = 'draft';

    /**
     * @param ModuleItem[] $items
     */
    public function __construct(
        public readonly int $id,
        public readonly int $classSubjectId,
        public readonly string $title,
        public readonly ?string $description = null,
        public readonly int $sequenceOrder = 1,
        public readonly string $status = self::STATUS_PUBLISHED,
        public readonly ?string $createdAt = null,
        public readonly ?string $updatedAt = null,
        public array $items = []
    ) {
    }

    public ?float $progressPercent = null;
    public ?bool $isCompleted = null;
    public int $completedItemsCount = 0;
    public int $totalItemsCount = 0;
    public bool $isUnlocked = true;
    public string $computedStatus = 'Not Started'; // 'Completed', 'In Progress', 'Not Started', 'Locked'

    public static function fromArray(array $data, array $items = []): self
    {
        return new self(
            id: (int)($data['id'] ?? 0),
            classSubjectId: (int)($data['class_subject_id'] ?? 0),
            title: trim((string)($data['title'] ?? '')),
            description: isset($data['description']) && $data['description'] !== null ? (string)$data['description'] : null,
            sequenceOrder: (int)($data['sequence_order'] ?? 1),
            status: (string)($data['status'] ?? self::STATUS_PUBLISHED),
            createdAt: isset($data['created_at']) ? (string)$data['created_at'] : null,
            updatedAt: isset($data['updated_at']) ? (string)$data['updated_at'] : null,
            items: $items
        );
    }

    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'class_subject_id' => $this->classSubjectId,
            'title' => $this->title,
            'description' => $this->description,
            'sequence_order' => $this->sequenceOrder,
            'status' => $this->status,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
            'items' => array_map(fn(ModuleItem $item) => $item->toArray(), $this->items),
            'progress_percent' => $this->progressPercent,
            'is_completed' => $this->isCompleted,
            'completed_items_count' => $this->completedItemsCount,
            'total_items_count' => $this->totalItemsCount,
            'is_unlocked' => $this->isUnlocked,
            'computed_status' => $this->computedStatus,
        ];
    }
}
