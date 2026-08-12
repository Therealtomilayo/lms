<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Domain Academic Level Entity
 */
final class AcademicLevel
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $stage,
        public readonly int $rankOrder = 0,
        public readonly ?int $gradingScaleId = null,
        public readonly ?string $createdAt = null,
        public readonly ?string $updatedAt = null
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            id: (int)$data['id'],
            name: (string)$data['name'],
            stage: (string)$data['stage'],
            rankOrder: (int)($data['rank_order'] ?? 0),
            gradingScaleId: isset($data['grading_scale_id']) && $data['grading_scale_id'] !== '' ? (int)$data['grading_scale_id'] : null,
            createdAt: isset($data['created_at']) ? (string)$data['created_at'] : null,
            updatedAt: isset($data['updated_at']) ? (string)$data['updated_at'] : null
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'stage' => $this->stage,
            'rank_order' => $this->rankOrder,
            'grading_scale_id' => $this->gradingScaleId,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
