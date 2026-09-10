<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Domain Staff Attendance Entity for Geofenced Clock-In & Time Tracking (SRS §22, §23, §24)
 */
final class StaffAttendance
{
    public const STATUS_PRESENT = 'present';
    public const STATUS_LATE = 'late';
    public const STATUS_HALF_DAY = 'half_day';
    public const STATUS_ABSENT = 'absent';
    public const STATUS_EXCUSED = 'excused';

    public function __construct(
        public readonly int $id,
        public readonly int $userId,
        public readonly ?int $sessionId,
        public readonly ?int $termId,
        public readonly string $attendanceDate,
        public readonly string $clockInAt,
        public readonly ?string $clockOutAt,
        public readonly float $clockInLatitude,
        public readonly float $clockInLongitude,
        public readonly int $clockInDistanceMeters,
        public readonly ?float $clockOutLatitude = null,
        public readonly ?float $clockOutLongitude = null,
        public readonly ?int $clockOutDistanceMeters = null,
        public readonly string $status = self::STATUS_PRESENT,
        public readonly bool $isLate = false,
        public readonly bool $isEarlyDeparture = false,
        public readonly ?int $workDurationMinutes = null,
        public readonly ?string $deviceFingerprint = null,
        public readonly ?string $ipAddress = null,
        public readonly ?string $userAgent = null,
        public readonly ?string $notes = null,
        public readonly bool $isOfflineSync = false,
        public readonly ?string $syncedAt = null,
        public readonly string $createdAt = '',
        public readonly ?string $updatedAt = null,
        // Hydrated relations
        public readonly ?string $staffName = null,
        public readonly ?string $staffEmail = null,
        public readonly ?string $staffRole = null
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            id: (int)$data['id'],
            userId: (int)$data['user_id'],
            sessionId: !empty($data['session_id']) ? (int)$data['session_id'] : null,
            termId: !empty($data['term_id']) ? (int)$data['term_id'] : null,
            attendanceDate: (string)$data['attendance_date'],
            clockInAt: (string)$data['clock_in_at'],
            clockOutAt: !empty($data['clock_out_at']) ? (string)$data['clock_out_at'] : null,
            clockInLatitude: (float)$data['clock_in_latitude'],
            clockInLongitude: (float)$data['clock_in_longitude'],
            clockInDistanceMeters: (int)$data['clock_in_distance_meters'],
            clockOutLatitude: isset($data['clock_out_latitude']) && $data['clock_out_latitude'] !== null ? (float)$data['clock_out_latitude'] : null,
            clockOutLongitude: isset($data['clock_out_longitude']) && $data['clock_out_longitude'] !== null ? (float)$data['clock_out_longitude'] : null,
            clockOutDistanceMeters: isset($data['clock_out_distance_meters']) && $data['clock_out_distance_meters'] !== null ? (int)$data['clock_out_distance_meters'] : null,
            status: (string)($data['status'] ?? self::STATUS_PRESENT),
            isLate: (bool)($data['is_late'] ?? false),
            isEarlyDeparture: (bool)($data['is_early_departure'] ?? false),
            workDurationMinutes: isset($data['work_duration_minutes']) && $data['work_duration_minutes'] !== null ? (int)$data['work_duration_minutes'] : null,
            deviceFingerprint: $data['device_fingerprint'] ?? null,
            ipAddress: $data['ip_address'] ?? null,
            userAgent: $data['user_agent'] ?? null,
            notes: $data['notes'] ?? null,
            isOfflineSync: (bool)($data['is_offline_sync'] ?? false),
            syncedAt: $data['synced_at'] ?? null,
            createdAt: (string)($data['created_at'] ?? ''),
            updatedAt: $data['updated_at'] ?? null,
            staffName: $data['staff_name'] ?? $data['name'] ?? null,
            staffEmail: $data['staff_email'] ?? $data['email'] ?? null,
            staffRole: $data['staff_role'] ?? $data['role'] ?? null
        );
    }

    public function isClockedOut(): bool
    {
        return !empty($this->clockOutAt);
    }

    public function getFormattedClockInTime(): string
    {
        $ts = strtotime($this->clockInAt);
        return $ts ? date('h:i A', $ts) : $this->clockInAt;
    }

    public function getFormattedClockOutTime(): ?string
    {
        if (!$this->clockOutAt) {
            return null;
        }
        $ts = strtotime($this->clockOutAt);
        return $ts ? date('h:i A', $ts) : $this->clockOutAt;
    }

    public function getFormattedWorkDuration(): string
    {
        if ($this->workDurationMinutes === null) {
            return 'In Progress';
        }
        $hours = intdiv($this->workDurationMinutes, 60);
        $minutes = $this->workDurationMinutes % 60;
        if ($hours > 0) {
            return "{$hours}h {$minutes}m";
        }
        return "{$minutes}m";
    }

    public function getStatusBadge(): array
    {
        return match ($this->status) {
            self::STATUS_PRESENT => [
                'label' => $this->isLate ? 'Late Arrival' : 'Present (On Time)',
                'class' => $this->isLate ? 'bg-amber-50 text-amber-800 border-amber-200' : 'bg-emerald-50 text-emerald-800 border-emerald-200',
                'dot' => $this->isLate ? 'bg-amber-500' : 'bg-emerald-500',
            ],
            self::STATUS_LATE => [
                'label' => 'Late Arrival',
                'class' => 'bg-amber-50 text-amber-800 border-amber-200',
                'dot' => 'bg-amber-500',
            ],
            self::STATUS_HALF_DAY => [
                'label' => 'Half Day',
                'class' => 'bg-sky-50 text-sky-800 border-sky-200',
                'dot' => 'bg-sky-500',
            ],
            self::STATUS_EXCUSED => [
                'label' => 'Excused / On Leave',
                'class' => 'bg-blue-50 text-blue-800 border-blue-200',
                'dot' => 'bg-blue-500',
            ],
            default => [
                'label' => 'Absent',
                'class' => 'bg-rose-50 text-rose-800 border-rose-200',
                'dot' => 'bg-rose-500',
            ],
        };
    }
}
