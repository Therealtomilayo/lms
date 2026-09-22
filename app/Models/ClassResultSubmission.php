<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Domain entity for Class Result Submission by Form Teacher
 */
final class ClassResultSubmission
{
    public function __construct(
        public readonly int $id,
        public readonly int $classId,
        public readonly int $termId,
        public readonly int $submittedBy,
        public readonly string $submittedAt,
        public readonly string $status = 'submitted',
        public readonly ?string $notes = null,
        public readonly ?int $reviewedBy = null,
        public readonly ?string $reviewedAt = null,
        public readonly ?string $teacherName = null,
        public readonly ?string $className = null,
        public readonly ?string $sectionArm = null,
        public readonly ?string $termName = null,
        public readonly ?string $createdAt = null,
        public readonly ?string $updatedAt = null
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            id: (int)$data['id'],
            classId: (int)$data['class_id'],
            termId: (int)$data['term_id'],
            submittedBy: (int)$data['submitted_by'],
            submittedAt: (string)($data['submitted_at'] ?? date('Y-m-d H:i:s')),
            status: (string)($data['status'] ?? 'submitted'),
            notes: isset($data['notes']) ? (string)$data['notes'] : null,
            reviewedBy: isset($data['reviewed_by']) && $data['reviewed_by'] !== '' ? (int)$data['reviewed_by'] : null,
            reviewedAt: isset($data['reviewed_at']) ? (string)$data['reviewed_at'] : null,
            teacherName: isset($data['teacher_name']) ? (string)$data['teacher_name'] : null,
            className: isset($data['class_name']) ? (string)$data['class_name'] : null,
            sectionArm: isset($data['section_arm']) ? (string)$data['section_arm'] : null,
            termName: isset($data['term_name']) ? (string)$data['term_name'] : null,
            createdAt: isset($data['created_at']) ? (string)$data['created_at'] : null,
            updatedAt: isset($data['updated_at']) ? (string)$data['updated_at'] : null
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'class_id' => $this->classId,
            'term_id' => $this->termId,
            'submitted_by' => $this->submittedBy,
            'submitted_at' => $this->submittedAt,
            'status' => $this->status,
            'notes' => $this->notes,
            'reviewed_by' => $this->reviewedBy,
            'reviewed_at' => $this->reviewedAt,
            'teacher_name' => $this->teacherName,
            'class_name' => $this->className,
            'section_arm' => $this->sectionArm,
            'term_name' => $this->termName,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }

    public function isSubmitted(): bool
    {
        return $this->status === 'submitted';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }
}
