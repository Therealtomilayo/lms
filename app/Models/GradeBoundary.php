<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Domain GradeBoundary Entity
 */
final class GradeBoundary
{
    public function __construct(
        public readonly int $id,
        public readonly int $gradingScaleId,
        public readonly string $letter,
        public readonly float $minScore,
        public readonly float $maxScore,
        public readonly ?float $gradePoint = null,
        public readonly ?string $remark = null,
        public readonly ?string $createdAt = null,
        public readonly ?string $updatedAt = null
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            id: (int)($data['id'] ?? 0),
            gradingScaleId: (int)($data['grading_scale_id'] ?? 0),
            letter: (string)($data['letter'] ?? ''),
            minScore: (float)($data['min_score'] ?? 0.0),
            maxScore: (float)($data['max_score'] ?? 0.0),
            gradePoint: isset($data['grade_point']) && $data['grade_point'] !== '' && $data['grade_point'] !== null
                ? (float)$data['grade_point']
                : null,
            remark: isset($data['remark']) && $data['remark'] !== '' ? (string)$data['remark'] : null,
            createdAt: isset($data['created_at']) ? (string)$data['created_at'] : null,
            updatedAt: isset($data['updated_at']) ? (string)$data['updated_at'] : null
        );
    }

    public function matches(float $score): bool
    {
        return $score >= $this->minScore && $score <= $this->maxScore;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'grading_scale_id' => $this->gradingScaleId,
            'letter' => $this->letter,
            'min_score' => $this->minScore,
            'max_score' => $this->maxScore,
            'grade_point' => $this->gradePoint,
            'remark' => $this->remark,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
