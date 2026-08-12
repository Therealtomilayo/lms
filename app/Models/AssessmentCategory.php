<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Domain AssessmentCategory Entity
 */
final class AssessmentCategory
{
    public function __construct(
        public readonly int $id,
        public readonly int $sessionId,
        public readonly int $termId,
        public readonly ?int $classSubjectId = null,
        public readonly string $name = '',
        public readonly float $weightPercentage = 0.0,
        public readonly float $maxPoints = 100.0,
        public readonly ?string $createdAt = null,
        public readonly ?string $updatedAt = null
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            id: (int)($data['id'] ?? 0),
            sessionId: (int)($data['session_id'] ?? 0),
            termId: (int)($data['term_id'] ?? 0),
            classSubjectId: isset($data['class_subject_id']) && $data['class_subject_id'] !== '' && $data['class_subject_id'] !== null
                ? (int)$data['class_subject_id']
                : null,
            name: (string)($data['name'] ?? ''),
            weightPercentage: (float)($data['weight_percentage'] ?? 0.0),
            maxPoints: (float)($data['max_points'] ?? 100.0),
            createdAt: isset($data['created_at']) ? (string)$data['created_at'] : null,
            updatedAt: isset($data['updated_at']) ? (string)$data['updated_at'] : null
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'session_id' => $this->sessionId,
            'term_id' => $this->termId,
            'class_subject_id' => $this->classSubjectId,
            'name' => $this->name,
            'weight_percentage' => $this->weightPercentage,
            'max_points' => $this->maxPoints,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
