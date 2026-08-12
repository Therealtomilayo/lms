<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Domain Class Subject / Teaching Assignment Entity
 * Represents the session-scoped delivery of a subject to a class by an assigned teacher.
 */
final class ClassSubject
{
    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';

    public function __construct(
        public readonly int $id,
        public readonly int $sessionId,
        public readonly int $classId,
        public readonly int $subjectId,
        public readonly int $teacherId,
        public readonly string $status = self::STATUS_ACTIVE,
        public readonly ?string $createdAt = null,
        public readonly ?string $updatedAt = null,
        public readonly ?AcademicSession $academicSession = null,
        public readonly ?SchoolClass $schoolClass = null,
        public readonly ?Subject $subject = null,
        public readonly ?Teacher $teacher = null
    ) {
    }

    public static function fromArray(
        array $data,
        ?AcademicSession $academicSession = null,
        ?SchoolClass $schoolClass = null,
        ?Subject $subject = null,
        ?Teacher $teacher = null
    ): self {
        $subject = $subject ?? (!empty($data['subject_name']) ? Subject::fromArray([
            'id' => $data['subject_id'],
            'code' => $data['subject_code'] ?? '',
            'name' => $data['subject_name'],
            'category' => $data['subject_category'] ?? null,
            'status' => $data['subject_status'] ?? 'active',
        ]) : null);

        $schoolClass = $schoolClass ?? (!empty($data['class_name']) ? SchoolClass::fromArray([
            'id' => $data['class_id'],
            'academic_level_id' => $data['academic_level_id'] ?? 0,
            'name' => $data['class_name'],
            'section_arm' => $data['section_arm'] ?? null,
            'status' => $data['class_status'] ?? 'active',
        ]) : null);

        $teacher = $teacher ?? (!empty($data['teacher_name']) ? Teacher::fromArray([
            'id' => $data['teacher_id'],
            'user_id' => $data['teacher_user_id'] ?? 0,
            'staff_id' => $data['teacher_staff_id'] ?? '',
            'user_name' => $data['teacher_name'],
        ]) : null);

        return new self(
            id: (int)$data['id'],
            sessionId: (int)($data['session_id'] ?? 0),
            classId: (int)($data['class_id'] ?? 0),
            subjectId: (int)($data['subject_id'] ?? 0),
            teacherId: (int)($data['teacher_id'] ?? 0),
            status: (string)($data['status'] ?? self::STATUS_ACTIVE),
            createdAt: isset($data['created_at']) ? (string)$data['created_at'] : null,
            updatedAt: isset($data['updated_at']) ? (string)$data['updated_at'] : null,
            academicSession: $academicSession,
            schoolClass: $schoolClass,
            subject: $subject,
            teacher: $teacher
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
            'session_id' => $this->sessionId,
            'class_id' => $this->classId,
            'subject_id' => $this->subjectId,
            'teacher_id' => $this->teacherId,
            'status' => $this->status,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
