<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Domain Student Subject Enrollment Entity
 */
final class StudentSubjectEnrollment
{
    public const STATUS_ACTIVE = 'active';
    public const STATUS_DROPPED = 'dropped';

    public function __construct(
        public readonly int $id,
        public readonly int $studentId,
        public readonly int $classSubjectId,
        public readonly int $sessionId,
        public readonly bool $isElective = false,
        public readonly string $status = self::STATUS_ACTIVE,
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
        return new self(
            id: (int)$data['id'],
            studentId: (int)$data['student_id'],
            classSubjectId: (int)$data['class_subject_id'],
            sessionId: (int)$data['session_id'],
            isElective: (bool)($data['is_elective'] ?? false),
            status: (string)($data['status'] ?? self::STATUS_ACTIVE),
            createdAt: isset($data['created_at']) ? (string)$data['created_at'] : null,
            updatedAt: isset($data['updated_at']) ? (string)$data['updated_at'] : null,
            student: $student,
            classSubject: $classSubject
        );
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'student_id' => $this->studentId,
            'class_subject_id' => $this->classSubjectId,
            'session_id' => $this->sessionId,
            'is_elective' => $this->isElective ? 1 : 0,
            'status' => $this->status,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
