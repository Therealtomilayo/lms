<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Domain TermResult Entity (Subject-level result for a student in a term)
 */
final class TermResult
{
    /**
     * @param array<string, mixed>|string $breakdownJson
     */
    public function __construct(
        public readonly int $id,
        public readonly int $studentId,
        public readonly int $classSubjectId,
        public readonly int $termId,
        public readonly float $computedScore,
        public readonly string $gradeLetter,
        public readonly ?float $gradePoint = null,
        public readonly ?string $remark = null,
        public readonly array $breakdown = [],
        public readonly bool $isLocked = false,
        public readonly ?string $lockedAt = null,
        public readonly ?int $lockedBy = null,
        public readonly ?string $createdAt = null,
        public readonly ?string $updatedAt = null,
        public readonly ?Student $student = null,
        public readonly ?ClassSubject $classSubject = null
    ) {
    }

    public static function fromArray(
        array $data,
        ?Student $student = null,
        ?ClassSubject $classSubject = null
    ): self {
        $breakdown = [];
        if (isset($data['breakdown_json'])) {
            if (is_array($data['breakdown_json'])) {
                $breakdown = $data['breakdown_json'];
            } elseif (is_string($data['breakdown_json']) && $data['breakdown_json'] !== '') {
                $decoded = json_decode($data['breakdown_json'], true);
                if (is_array($decoded)) {
                    $breakdown = $decoded;
                }
            }
        }

        return new self(
            id: (int)($data['id'] ?? 0),
            studentId: (int)($data['student_id'] ?? 0),
            classSubjectId: (int)($data['class_subject_id'] ?? 0),
            termId: (int)($data['term_id'] ?? 0),
            computedScore: (float)($data['computed_score'] ?? 0.0),
            gradeLetter: (string)($data['grade_letter'] ?? ''),
            gradePoint: isset($data['grade_point']) && $data['grade_point'] !== '' && $data['grade_point'] !== null
                ? (float)$data['grade_point']
                : null,
            remark: isset($data['remark']) && $data['remark'] !== '' ? (string)$data['remark'] : null,
            breakdown: $breakdown,
            isLocked: (bool)($data['is_locked'] ?? false),
            lockedAt: isset($data['locked_at']) ? (string)$data['locked_at'] : null,
            lockedBy: isset($data['locked_by']) && $data['locked_by'] !== '' && $data['locked_by'] !== null
                ? (int)$data['locked_by']
                : null,
            createdAt: isset($data['created_at']) ? (string)$data['created_at'] : null,
            updatedAt: isset($data['updated_at']) ? (string)$data['updated_at'] : null,
            student: $student,
            classSubject: $classSubject
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'student_id' => $this->studentId,
            'class_subject_id' => $this->classSubjectId,
            'term_id' => $this->termId,
            'computed_score' => $this->computedScore,
            'grade_letter' => $this->gradeLetter,
            'grade_point' => $this->gradePoint,
            'remark' => $this->remark,
            'breakdown_json' => json_encode($this->breakdown),
            'is_locked' => $this->isLocked,
            'locked_at' => $this->lockedAt,
            'locked_by' => $this->lockedBy,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
