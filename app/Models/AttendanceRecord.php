<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Domain Attendance Record Entity
 */
final class AttendanceRecord
{
    public function __construct(
        public readonly int $id,
        public readonly int $sessionId,
        public readonly int $termId,
        public readonly int $classId,
        public readonly ?int $classSubjectId,
        public readonly int $studentId,
        public readonly string $date,
        public readonly ?int $periodNumber,
        public readonly string $status, // 'present', 'absent', 'late', 'excused'
        public readonly int $markedBy,
        public readonly ?int $updatedBy = null,
        public readonly ?string $correctionReason = null,
        public readonly ?string $createdAt = null,
        public readonly ?string $updatedAt = null,
        // Optional hydrated fields
        public readonly ?string $studentName = null,
        public readonly ?string $admissionNumber = null,
        public readonly ?string $markerName = null,
        public readonly ?string $updaterName = null
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            id: (int)($data['id'] ?? 0),
            sessionId: (int)($data['session_id'] ?? 0),
            termId: (int)($data['term_id'] ?? 0),
            classId: (int)($data['class_id'] ?? 0),
            classSubjectId: isset($data['class_subject_id']) && $data['class_subject_id'] !== null && $data['class_subject_id'] !== '' ? (int)$data['class_subject_id'] : null,
            studentId: (int)($data['student_id'] ?? 0),
            date: (string)($data['date'] ?? ''),
            periodNumber: isset($data['period_number']) && $data['period_number'] !== null && $data['period_number'] !== '' ? (int)$data['period_number'] : null,
            status: (string)($data['status'] ?? 'present'),
            markedBy: (int)($data['marked_by'] ?? 0),
            updatedBy: isset($data['updated_by']) && $data['updated_by'] !== null && $data['updated_by'] !== '' ? (int)$data['updated_by'] : null,
            correctionReason: isset($data['correction_reason']) && $data['correction_reason'] !== '' ? (string)$data['correction_reason'] : null,
            createdAt: isset($data['created_at']) ? (string)$data['created_at'] : null,
            updatedAt: isset($data['updated_at']) ? (string)$data['updated_at'] : null,
            studentName: isset($data['student_name']) ? (string)$data['student_name'] : null,
            admissionNumber: isset($data['admission_number']) ? (string)$data['admission_number'] : null,
            markerName: isset($data['marker_name']) ? (string)$data['marker_name'] : null,
            updaterName: isset($data['updater_name']) ? (string)$data['updater_name'] : null
        );
    }

    public function isPresent(): bool
    {
        return $this->status === 'present';
    }

    public function isAbsent(): bool
    {
        return $this->status === 'absent';
    }

    public function isLate(): bool
    {
        return $this->status === 'late';
    }

    public function isExcused(): bool
    {
        return $this->status === 'excused';
    }

    public function isDaily(): bool
    {
        return $this->classSubjectId === null && $this->periodNumber === null;
    }

    public function isPeriod(): bool
    {
        return $this->classSubjectId !== null || $this->periodNumber !== null;
    }

    public function wasCorrected(): bool
    {
        return $this->updatedBy !== null || $this->correctionReason !== null;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'session_id' => $this->sessionId,
            'term_id' => $this->termId,
            'class_id' => $this->classId,
            'class_subject_id' => $this->classSubjectId,
            'student_id' => $this->studentId,
            'date' => $this->date,
            'period_number' => $this->periodNumber,
            'status' => $this->status,
            'marked_by' => $this->markedBy,
            'updated_by' => $this->updatedBy,
            'correction_reason' => $this->correctionReason,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
            'student_name' => $this->studentName,
            'admission_number' => $this->admissionNumber,
            'marker_name' => $this->markerName,
            'updater_name' => $this->updaterName,
        ];
    }
}
