<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Domain entity for recording geofence out-of-perimeter clock-in violations (SRS §23)
 */
final class StaffAttendanceBreach
{
    public function __construct(
        public readonly int $id,
        public readonly int $userId,
        public readonly string $attemptType,
        public readonly string $attemptedAt,
        public readonly float $latitude,
        public readonly float $longitude,
        public readonly int $distanceMeters,
        public readonly int $allowedRadiusMeters,
        public readonly string $failureReason,
        public readonly ?string $ipAddress = null,
        public readonly ?string $deviceFingerprint = null,
        public readonly ?string $userAgent = null,
        public readonly ?string $staffName = null,
        public readonly ?string $staffEmail = null
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            id: (int)$data['id'],
            userId: (int)$data['user_id'],
            attemptType: (string)($data['attempt_type'] ?? 'clock_in'),
            attemptedAt: (string)$data['attempted_at'],
            latitude: (float)$data['latitude'],
            longitude: (float)$data['longitude'],
            distanceMeters: (int)$data['distance_meters'],
            allowedRadiusMeters: (int)$data['allowed_radius_meters'],
            failureReason: (string)$data['failure_reason'],
            ipAddress: $data['ip_address'] ?? null,
            deviceFingerprint: $data['device_fingerprint'] ?? null,
            userAgent: $data['user_agent'] ?? null,
            staffName: $data['staff_name'] ?? $data['name'] ?? null,
            staffEmail: $data['staff_email'] ?? $data['email'] ?? null
        );
    }
}
