<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Domain LiveClassAttendee Entity (SRS §31)
 * Represents an attendance event for a student joining an online synchronous class.
 */
final class LiveClassAttendee
{
    public function __construct(
        public readonly int $id,
        public readonly int $liveClassId,
        public readonly int $studentId,
        public readonly string $joinedAt,
        public readonly ?string $lastSeenAt = null,
        public readonly ?string $ipAddress = null,
        public readonly ?string $userAgent = null,
        public readonly ?string $studentName = null,
        public readonly ?string $admissionNumber = null,
        public readonly ?string $className = null
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            id: (int)($data['id'] ?? 0),
            liveClassId: (int)($data['live_class_id'] ?? 0),
            studentId: (int)($data['student_id'] ?? 0),
            joinedAt: (string)($data['joined_at'] ?? date('Y-m-d H:i:s')),
            lastSeenAt: isset($data['last_seen_at']) ? (string)$data['last_seen_at'] : null,
            ipAddress: isset($data['ip_address']) ? (string)$data['ip_address'] : null,
            userAgent: isset($data['user_agent']) ? (string)$data['user_agent'] : null,
            studentName: isset($data['student_name']) ? (string)$data['student_name'] : null,
            admissionNumber: isset($data['admission_number']) ? (string)$data['admission_number'] : null,
            className: isset($data['class_name']) ? (string)$data['class_name'] : null
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'live_class_id' => $this->liveClassId,
            'student_id' => $this->studentId,
            'joined_at' => $this->joinedAt,
            'last_seen_at' => $this->lastSeenAt,
            'ip_address' => $this->ipAddress,
            'user_agent' => $this->userAgent,
            'student_name' => $this->studentName,
            'admission_number' => $this->admissionNumber,
            'class_name' => $this->className,
        ];
    }
}
