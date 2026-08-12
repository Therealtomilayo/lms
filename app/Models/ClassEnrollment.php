<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Domain Class Enrollment Entity
 */
final class ClassEnrollment
{
    public const STATUS_ACTIVE = 'active';
    public const STATUS_PROMOTED = 'promoted';
    public const STATUS_REPEATING = 'repeating';
    public const STATUS_TRANSFERRED = 'transferred';
    public const STATUS_WITHDRAWN = 'withdrawn';

    public function __construct(
        public readonly int $id,
        public readonly int $studentId,
        public readonly int $classId,
        public readonly int $sessionId,
        public readonly string $status = self::STATUS_ACTIVE,
        public readonly string $enrolledAt = '',
        public readonly ?string $createdAt = null,
        public readonly ?string $updatedAt = null,
        public readonly ?Student $student = null,
        public readonly ?SchoolClass $class = null,
        public readonly ?AcademicSession $session = null
    ) {
    }

    public static function fromArray(
        array $data,
        ?Student $student = null,
        ?SchoolClass $class = null,
        ?AcademicSession $session = null
    ): self {
        return new self(
            id: (int)$data['id'],
            studentId: (int)$data['student_id'],
            classId: (int)$data['class_id'],
            sessionId: (int)$data['session_id'],
            status: (string)($data['status'] ?? self::STATUS_ACTIVE),
            enrolledAt: (string)($data['enrolled_at'] ?? ''),
            createdAt: isset($data['created_at']) ? (string)$data['created_at'] : null,
            updatedAt: isset($data['updated_at']) ? (string)$data['updated_at'] : null,
            student: $student,
            class: $class,
            session: $session
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
            'class_id' => $this->classId,
            'session_id' => $this->sessionId,
            'status' => $this->status,
            'enrolled_at' => $this->enrolledAt,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
