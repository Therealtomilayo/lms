<?php

declare(strict_types=1);

namespace App\Services;

use App\DTO\ServiceResult;
use App\Models\StaffAttendance;
use App\Repositories\AcademicRepository;
use App\Repositories\StaffAttendanceRepository;
use PDO;

class StaffAttendanceService
{
    private const EARTH_RADIUS_METERS = 6371000;

    public function __construct(
        private readonly StaffAttendanceRepository $attendanceRepository,
        private readonly AcademicRepository $academicRepository,
        private readonly PDO $pdo
    ) {
    }

    /**
     * Compute Great-Circle distance using Haversine formula in meters.
     */
    public function calculateDistanceMeters(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $lat1Rad = deg2rad($lat1);
        $lng1Rad = deg2rad($lng1);
        $lat2Rad = deg2rad($lat2);
        $lng2Rad = deg2rad($lng2);

        $deltaLat = $lat2Rad - $lat1Rad;
        $deltaLng = $lng2Rad - $lng1Rad;

        $a = sin($deltaLat / 2) ** 2 +
             cos($lat1Rad) * cos($lat2Rad) * (sin($deltaLng / 2) ** 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return round(self::EARTH_RADIUS_METERS * $c, 2);
    }

    /**
     * Retrieve current Geofence & Workday Settings.
     */
    public function getGeofenceConfig(): array
    {
        $stmt = $this->pdo->query("SELECT setting_key, setting_value FROM `system_settings` WHERE setting_key LIKE 'staff_%'");
        $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR) ?: [];

        return [
            'latitude' => isset($settings['staff_geofence_latitude']) ? (float)$settings['staff_geofence_latitude'] : 9.08820000,
            'longitude' => isset($settings['staff_geofence_longitude']) ? (float)$settings['staff_geofence_longitude'] : 7.46410000,
            'radius_meters' => isset($settings['staff_geofence_radius_meters']) ? (int)$settings['staff_geofence_radius_meters'] : 250,
            'workday_start_time' => $settings['staff_workday_start_time'] ?? '08:00',
            'workday_late_threshold' => $settings['staff_workday_late_threshold'] ?? '08:15',
            'workday_end_time' => $settings['staff_workday_end_time'] ?? '15:30',
            'enforced' => !isset($settings['staff_geofence_enforced']) || (bool)$settings['staff_geofence_enforced'],
        ];
    }

    /**
     * Update geofence perimeter and workday hours configuration.
     */
    public function updateGeofenceConfig(array $config, int $adminUserId): void
    {
        $keys = [
            'staff_geofence_latitude' => (string)($config['latitude'] ?? 9.0882),
            'staff_geofence_longitude' => (string)($config['longitude'] ?? 7.4641),
            'staff_geofence_radius_meters' => (string)($config['radius_meters'] ?? 250),
            'staff_workday_start_time' => (string)($config['workday_start_time'] ?? '08:00'),
            'staff_workday_late_threshold' => (string)($config['workday_late_threshold'] ?? '08:15'),
            'staff_workday_end_time' => (string)($config['workday_end_time'] ?? '15:30'),
            'staff_geofence_enforced' => !empty($config['enforced']) ? '1' : '0',
        ];

        $checkStmt = $this->pdo->prepare("SELECT 1 FROM `system_settings` WHERE `setting_key` = :key LIMIT 1");
        $updateStmt = $this->pdo->prepare("UPDATE `system_settings` SET `setting_value` = :val, `updated_by` = :admin_id, `updated_at` = CURRENT_TIMESTAMP WHERE `setting_key` = :key");
        $insertStmt = $this->pdo->prepare("INSERT INTO `system_settings` (`setting_key`, `setting_value`, `updated_by`, `updated_at`) VALUES (:key, :val, :admin_id, CURRENT_TIMESTAMP)");

        foreach ($keys as $k => $v) {
            $checkStmt->execute([':key' => $k]);
            if ($checkStmt->fetchColumn()) {
                $updateStmt->execute([':key' => $k, ':val' => $v, ':admin_id' => $adminUserId]);
            } else {
                $insertStmt->execute([':key' => $k, ':val' => $v, ':admin_id' => $adminUserId]);
            }
        }
    }

    /**
     * Process staff Clock-In with Geofencing verification.
     */
    public function clockIn(
        int $userId,
        float $latitude,
        float $longitude,
        ?string $fingerprint = null,
        ?string $ip = null,
        ?string $userAgent = null,
        ?string $notes = null,
        bool $isOffline = false,
        ?string $offlineTimestamp = null
    ): ServiceResult {
        $config = $this->getGeofenceConfig();
        $distance = (int)$this->calculateDistanceMeters(
            $latitude,
            $longitude,
            $config['latitude'],
            $config['longitude']
        );

        $now = $isOffline && !empty($offlineTimestamp) ? $offlineTimestamp : date('Y-m-d H:i:s');
        $date = date('Y-m-d', strtotime($now));

        // 1. Geofence Perimeter Validation
        if ($config['enforced'] && $distance > $config['radius_meters']) {
            $this->attendanceRepository->logGeofenceBreach([
                'user_id' => $userId,
                'attempt_type' => 'clock_in',
                'latitude' => $latitude,
                'longitude' => $longitude,
                'distance_meters' => $distance,
                'allowed_radius_meters' => $config['radius_meters'],
                'ip_address' => $ip,
                'device_fingerprint' => $fingerprint,
                'user_agent' => $userAgent,
                'failure_reason' => "Outside radius: {$distance}m from center (max allowed {$config['radius_meters']}m)",
            ]);

            return ServiceResult::failure(
                "Clock-in rejected: You are physically located {$distance} meters away from the campus perimeter. Attendance requires you to be within the approved {$config['radius_meters']}m radius.",
                'OUT_OF_BOUNDS'
            );
        }

        // 2. Duplicate Check
        if ($this->attendanceRepository->existsForUserAndDate($userId, $date)) {
            return ServiceResult::failure("Attendance record already exists for today ({$date}).", 'DUPLICATE_ENTRY');
        }

        // 3. Punctuality Calculation
        $timeString = date('H:i:s', strtotime($now));
        $lateThreshold = $config['workday_late_threshold'] . ':00';
        $isLate = ($timeString > $lateThreshold);
        $status = $isLate ? StaffAttendance::STATUS_LATE : StaffAttendance::STATUS_PRESENT;

        // 4. Resolve Active Session & Term
        $activeSession = $this->academicRepository->getCurrentSession();
        $activeTerm = $this->academicRepository->getCurrentTerm();

        // 5. Insert Record
        $record = $this->attendanceRepository->recordClockIn([
            'user_id' => $userId,
            'session_id' => $activeSession?->id,
            'term_id' => $activeTerm?->id,
            'attendance_date' => $date,
            'clock_in_at' => $now,
            'clock_in_latitude' => $latitude,
            'clock_in_longitude' => $longitude,
            'clock_in_distance_meters' => $distance,
            'status' => $status,
            'is_late' => $isLate,
            'device_fingerprint' => $fingerprint,
            'ip_address' => $ip,
            'user_agent' => $userAgent,
            'notes' => $notes,
            'is_offline_sync' => $isOffline,
            'synced_at' => $isOffline ? date('Y-m-d H:i:s') : null,
        ]);

        $statusLabel = $isLate ? 'Late Arrival' : 'Present (On Time)';
        return ServiceResult::success($record, "Clock-in successful ({$statusLabel})! Distance: {$distance}m.");
    }

    /**
     * Process staff Clock-Out.
     */
    public function clockOut(
        int $userId,
        float $latitude,
        float $longitude,
        ?string $fingerprint = null,
        ?string $ip = null,
        ?string $userAgent = null,
        ?string $notes = null,
        bool $isOffline = false,
        ?string $offlineTimestamp = null
    ): ServiceResult {
        $targetDate = $isOffline && !empty($offlineTimestamp) ? date('Y-m-d', strtotime($offlineTimestamp)) : date('Y-m-d');
        $existing = $this->attendanceRepository->getTodayAttendanceForUser($userId, $targetDate);

        if (!$existing) {
            return ServiceResult::failure("No active clock-in session found for {$targetDate}. You must clock in first.", 'NOT_FOUND');
        }

        if ($existing->isClockedOut()) {
            return ServiceResult::failure("You have already clocked out for {$targetDate} at {$existing->getFormattedClockOutTime()}.", 'ALREADY_CLOCKED_OUT');
        }

        $config = $this->getGeofenceConfig();
        $distance = (int)$this->calculateDistanceMeters(
            $latitude,
            $longitude,
            $config['latitude'],
            $config['longitude']
        );

        $now = $isOffline && !empty($offlineTimestamp) ? $offlineTimestamp : date('Y-m-d H:i:s');
        $clockInTs = strtotime($existing->clockInAt);
        $clockOutTs = strtotime($now);
        $durationMinutes = max(0, (int)round(($clockOutTs - $clockInTs) / 60));

        // Early departure check
        $nowTime = date('H:i:s', strtotime($now));
        $workdayEnd = $config['workday_end_time'] . ':00';
        $isEarlyDeparture = ($nowTime < $workdayEnd);

        $updated = $this->attendanceRepository->recordClockOut($existing->id, [
            'clock_out_at' => $now,
            'clock_out_latitude' => $latitude,
            'clock_out_longitude' => $longitude,
            'clock_out_distance_meters' => $distance,
            'is_early_departure' => $isEarlyDeparture,
            'work_duration_minutes' => $durationMinutes,
            'notes' => $notes,
        ]);

        $durText = $updated->getFormattedWorkDuration();
        $earlyMsg = $isEarlyDeparture ? ' (Early Departure noted)' : '';
        return ServiceResult::success($updated, "Clock-out successful! Total time on duty: {$durText}{$earlyMsg}.");
    }

    /**
     * Synchronize offline recorded attendance queue.
     */
    public function syncOfflineRecords(int $userId, array $records, ?string $ip = null, ?string $userAgent = null): array
    {
        $synced = [];
        $failed = [];

        foreach ($records as $item) {
            $lat = (float)($item['latitude'] ?? 0.0);
            $lng = (float)($item['longitude'] ?? 0.0);
            $fingerprint = $item['fingerprint'] ?? null;
            $notes = $item['notes'] ?? 'Offline synchronized';
            $timestamp = $item['timestamp'] ?? date('Y-m-d H:i:s');
            $action = $item['action'] ?? 'clock_in';

            if ($action === 'clock_in') {
                $result = $this->clockIn(
                    $userId,
                    $lat,
                    $lng,
                    $fingerprint,
                    $ip,
                    $userAgent,
                    $notes,
                    true,
                    $timestamp
                );

                if ($result->isSuccess()) {
                    $synced[] = ['timestamp' => $timestamp, 'message' => $result->getMessage()];
                } else {
                    $failed[] = ['timestamp' => $timestamp, 'error' => $result->getMessage()];
                }
            } elseif ($action === 'clock_out') {
                $result = $this->clockOut(
                    $userId,
                    $lat,
                    $lng,
                    $fingerprint,
                    $ip,
                    $userAgent,
                    $notes,
                    true,
                    $timestamp
                );
                if ($result->isSuccess()) {
                    $synced[] = ['timestamp' => $timestamp, 'message' => $result->getMessage()];
                } else {
                    $failed[] = ['timestamp' => $timestamp, 'error' => $result->getMessage()];
                }
            }
        }

        return ['synced' => $synced, 'failed' => $failed];
    }

    /**
     * Get live status bundle for teacher/staff view.
     */
    public function getTodayStatus(int $userId): array
    {
        $today = date('Y-m-d');
        $attendance = $this->attendanceRepository->getTodayAttendanceForUser($userId, $today);
        $config = $this->getGeofenceConfig();

        return [
            'today' => $today,
            'current_time' => date('h:i:s A'),
            'attendance' => $attendance,
            'is_clocked_in' => $attendance !== null,
            'is_clocked_out' => $attendance?->isClockedOut() ?? false,
            'geofence' => [
                'latitude' => $config['latitude'],
                'longitude' => $config['longitude'],
                'radius_meters' => $config['radius_meters'],
                'enforced' => $config['enforced'],
            ],
            'workday' => [
                'start' => $config['workday_start_time'],
                'late_threshold' => $config['workday_late_threshold'],
                'end' => $config['workday_end_time'],
            ],
        ];
    }
}
