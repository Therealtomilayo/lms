<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Domain StudentAssessmentScore Entity
 */
final class StudentAssessmentScore
{
    public function __construct(
        public readonly int $id,
        public readonly int $assessmentCategoryId,
        public readonly int $studentId,
        public readonly int $classSubjectId,
        public readonly float $rawScore,
        public readonly int $recordedBy,
        public readonly ?string $recordedAt = null,
        public readonly ?string $createdAt = null,
        public readonly ?string $updatedAt = null
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            id: (int)($data['id'] ?? 0),
            assessmentCategoryId: (int)($data['assessment_category_id'] ?? 0),
            studentId: (int)($data['student_id'] ?? 0),
            classSubjectId: (int)($data['class_subject_id'] ?? 0),
            rawScore: (float)($data['raw_score'] ?? 0.0),
            recordedBy: (int)($data['recorded_by'] ?? 0),
            recordedAt: isset($data['recorded_at']) ? (string)$data['recorded_at'] : null,
            createdAt: isset($data['created_at']) ? (string)$data['created_at'] : null,
            updatedAt: isset($data['updated_at']) ? (string)$data['updated_at'] : null
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'assessment_category_id' => $this->assessmentCategoryId,
            'student_id' => $this->studentId,
            'class_subject_id' => $this->classSubjectId,
            'raw_score' => $this->rawScore,
            'recorded_by' => $this->recordedBy,
            'recorded_at' => $this->recordedAt,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
