<?php

declare(strict_types=1);

namespace App\Models;

final class FeeStructure
{
    /**
     * @param FeeStructureItem[] $items
     */
    public function __construct(
        public readonly int $id,
        public readonly int $sessionId,
        public readonly int $termId,
        public readonly ?int $academicLevelId = null,
        public readonly ?int $classId = null,
        public readonly string $title = '',
        public readonly string $currency = 'NGN',
        public readonly ?string $dueDate = null,
        public readonly bool $isActive = true,
        public readonly int $createdBy = 0,
        public readonly ?string $createdAt = null,
        public readonly ?string $updatedAt = null,
        public readonly ?string $sessionName = null,
        public readonly ?string $termName = null,
        public readonly ?string $levelName = null,
        public readonly ?string $className = null,
        public readonly array $items = []
    ) {
    }

    public static function fromArray(array $data, array $items = []): self
    {
        return new self(
            id: (int)$data['id'],
            sessionId: (int)$data['session_id'],
            termId: (int)$data['term_id'],
            academicLevelId: isset($data['academic_level_id']) && $data['academic_level_id'] !== null && $data['academic_level_id'] !== '' ? (int)$data['academic_level_id'] : null,
            classId: isset($data['class_id']) && $data['class_id'] !== null && $data['class_id'] !== '' ? (int)$data['class_id'] : null,
            title: (string)($data['title'] ?? ''),
            currency: (string)($data['currency'] ?? 'NGN'),
            dueDate: $data['due_date'] ?? null,
            isActive: (bool)($data['is_active'] ?? true),
            createdBy: (int)($data['created_by'] ?? 0),
            createdAt: $data['created_at'] ?? null,
            updatedAt: $data['updated_at'] ?? null,
            sessionName: $data['session_name'] ?? null,
            termName: $data['term_name'] ?? null,
            levelName: $data['level_name'] ?? null,
            className: $data['class_name'] ?? null,
            items: $items
        );
    }

    public function getTotalAmount(): float
    {
        $sum = 0.0;
        foreach ($this->items as $item) {
            $sum += $item->amount;
        }
        return $sum;
    }
}
