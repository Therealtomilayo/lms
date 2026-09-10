<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\StaffAttendance;
use App\Models\StaffAttendanceBreach;
use PDO;

class StaffAttendanceRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /**
     * Get attendance record for a specific staff user on a given date.
     */
    public function getTodayAttendanceForUser(int $userId, string $date): ?StaffAttendance
    {
        $sql = "SELECT sa.*, u.name as staff_name, u.email as staff_email 
                FROM `staff_attendance` sa
                JOIN `users` u ON u.id = sa.user_id
                WHERE sa.user_id = :user_id AND sa.attendance_date = :att_date
                LIMIT 1";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':user_id' => $userId,
            ':att_date' => $date,
        ]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? StaffAttendance::fromArray($row) : null;
    }

    /**
     * Check if an attendance record exists for user on a given date.
     */
    public function existsForUserAndDate(int $userId, string $date): bool
    {
        $stmt = $this->pdo->prepare("SELECT 1 FROM `staff_attendance` WHERE `user_id` = :user_id AND `attendance_date` = :att_date LIMIT 1");
        $stmt->execute([':user_id' => $userId, ':att_date' => $date]);
        return (bool)$stmt->fetchColumn();
    }

    /**
     * Record new Clock-In session.
     */
    public function recordClockIn(array $data): StaffAttendance
    {
        $sql = "INSERT INTO `staff_attendance` (
                    `user_id`, `session_id`, `term_id`, `attendance_date`,
                    `clock_in_at`, `clock_in_latitude`, `clock_in_longitude`,
                    `clock_in_distance_meters`, `status`, `is_late`,
                    `device_fingerprint`, `ip_address`, `user_agent`,
                    `notes`, `is_offline_sync`, `synced_at`
                ) VALUES (
                    :user_id, :session_id, :term_id, :attendance_date,
                    :clock_in_at, :clock_in_latitude, :clock_in_longitude,
                    :clock_in_distance_meters, :status, :is_late,
                    :device_fingerprint, :ip_address, :user_agent,
                    :notes, :is_offline_sync, :synced_at
                )";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':user_id' => $data['user_id'],
            ':session_id' => $data['session_id'] ?? null,
            ':term_id' => $data['term_id'] ?? null,
            ':attendance_date' => $data['attendance_date'],
            ':clock_in_at' => $data['clock_in_at'],
            ':clock_in_latitude' => $data['clock_in_latitude'],
            ':clock_in_longitude' => $data['clock_in_longitude'],
            ':clock_in_distance_meters' => $data['clock_in_distance_meters'],
            ':status' => $data['status'] ?? StaffAttendance::STATUS_PRESENT,
            ':is_late' => !empty($data['is_late']) ? 1 : 0,
            ':device_fingerprint' => $data['device_fingerprint'] ?? null,
            ':ip_address' => $data['ip_address'] ?? null,
            ':user_agent' => $data['user_agent'] ?? null,
            ':notes' => $data['notes'] ?? null,
            ':is_offline_sync' => !empty($data['is_offline_sync']) ? 1 : 0,
            ':synced_at' => $data['synced_at'] ?? null,
        ]);

        $id = (int)$this->pdo->lastInsertId();
        return $this->findById($id);
    }

    /**
     * Record Clock-Out for existing session.
     */
    public function recordClockOut(int $attendanceId, array $data): StaffAttendance
    {
        $existing = $this->findById($attendanceId);
        $notes = $data['notes'] ?? null;
        $updatedNotes = $existing?->notes 
            ? ($notes ? $existing->notes . ' | Out: ' . $notes : $existing->notes)
            : $notes;

        $sql = "UPDATE `staff_attendance` SET
                    `clock_out_at` = :clock_out_at,
                    `clock_out_latitude` = :clock_out_latitude,
                    `clock_out_longitude` = :clock_out_longitude,
                    `clock_out_distance_meters` = :clock_out_distance_meters,
                    `is_early_departure` = :is_early_departure,
                    `work_duration_minutes` = :work_duration_minutes,
                    `notes` = :notes,
                    `updated_at` = CURRENT_TIMESTAMP
                WHERE `id` = :id";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':id' => $attendanceId,
            ':clock_out_at' => $data['clock_out_at'],
            ':clock_out_latitude' => $data['clock_out_latitude'],
            ':clock_out_longitude' => $data['clock_out_longitude'],
            ':clock_out_distance_meters' => $data['clock_out_distance_meters'],
            ':is_early_departure' => !empty($data['is_early_departure']) ? 1 : 0,
            ':work_duration_minutes' => $data['work_duration_minutes'] ?? null,
            ':notes' => $updatedNotes,
        ]);

        return $this->findById($attendanceId);
    }

    /**
     * Find attendance by ID.
     */
    public function findById(int $id): ?StaffAttendance
    {
        $sql = "SELECT sa.*, u.name as staff_name, u.email as staff_email 
                FROM `staff_attendance` sa
                JOIN `users` u ON u.id = sa.user_id
                WHERE sa.id = :id LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? StaffAttendance::fromArray($row) : null;
    }

    /**
     * Log geofence perimeter rejection.
     */
    public function logGeofenceBreach(array $data): void
    {
        $sql = "INSERT INTO `staff_attendance_breaches` (
                    `user_id`, `attempt_type`, `latitude`, `longitude`,
                    `distance_meters`, `allowed_radius_meters`, `ip_address`,
                    `device_fingerprint`, `user_agent`, `failure_reason`
                ) VALUES (
                    :user_id, :attempt_type, :latitude, :longitude,
                    :distance_meters, :allowed_radius_meters, :ip_address,
                    :device_fingerprint, :user_agent, :failure_reason
                )";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':user_id' => $data['user_id'],
            ':attempt_type' => $data['attempt_type'] ?? 'clock_in',
            ':latitude' => $data['latitude'],
            ':longitude' => $data['longitude'],
            ':distance_meters' => $data['distance_meters'],
            ':allowed_radius_meters' => $data['allowed_radius_meters'],
            ':ip_address' => $data['ip_address'] ?? null,
            ':device_fingerprint' => $data['device_fingerprint'] ?? null,
            ':user_agent' => $data['user_agent'] ?? null,
            ':failure_reason' => $data['failure_reason'],
        ]);
    }

    /**
     * Get personal attendance log for staff member.
     *
     * @return StaffAttendance[]
     */
    public function getAttendanceHistoryForUser(int $userId, int $limit = 30): array
    {
        $sql = "SELECT sa.*, u.name as staff_name, u.email as staff_email 
                FROM `staff_attendance` sa
                JOIN `users` u ON u.id = sa.user_id
                WHERE sa.user_id = :user_id 
                ORDER BY sa.attendance_date DESC 
                LIMIT :limit";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return array_map(fn(array $r) => StaffAttendance::fromArray($r), $rows);
    }

    /**
     * Get institutional daily attendance register with search and status filters.
     *
     * @return StaffAttendance[]
     */
    public function getDailyRegister(string $date, ?string $status = null, ?string $search = null): array
    {
        $where = ['sa.attendance_date = :att_date'];
        $params = [':att_date' => $date];

        if (!empty($status)) {
            $where[] = 'sa.status = :status';
            $params[':status'] = $status;
        }

        if (!empty($search)) {
            $where[] = '(u.name LIKE :search_name OR u.email LIKE :search_email)';
            $searchVal = '%' . trim($search) . '%';
            $params[':search_name'] = $searchVal;
            $params[':search_email'] = $searchVal;
        }

        $whereSql = implode(' AND ', $where);
        $sql = "SELECT sa.*, u.name as staff_name, u.email as staff_email 
                FROM `staff_attendance` sa
                JOIN `users` u ON u.id = sa.user_id
                WHERE {$whereSql}
                ORDER BY sa.clock_in_at ASC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return array_map(fn(array $r) => StaffAttendance::fromArray($r), $rows);
    }

    /**
     * Get summary KPI statistics for a specific date.
     */
    public function getDailySummary(string $date): array
    {
        // 1. Total active staff (teachers and admins)
        $staffCountStmt = $this->pdo->query("
            SELECT COUNT(DISTINCT u.id) 
            FROM `users` u
            JOIN `user_roles` ur ON ur.user_id = u.id
            WHERE ur.role IN ('teacher', 'admin') AND u.status = 'active'
        ");
        $totalStaff = (int)$staffCountStmt->fetchColumn();

        // 2. Attendance counts for the day
        $attStmt = $this->pdo->prepare("
            SELECT 
                COUNT(*) as clocked_in_count,
                SUM(CASE WHEN is_late = 1 THEN 1 ELSE 0 END) as late_count,
                SUM(CASE WHEN clock_out_at IS NOT NULL THEN 1 ELSE 0 END) as clocked_out_count,
                SUM(CASE WHEN is_early_departure = 1 THEN 1 ELSE 0 END) as early_departure_count
            FROM `staff_attendance`
            WHERE `attendance_date` = :att_date
        ");
        $attStmt->execute([':att_date' => $date]);
        $counts = $attStmt->fetch(PDO::FETCH_ASSOC) ?: [];

        // 3. Geofence breach count for the day
        $breachStmt = $this->pdo->prepare("
            SELECT COUNT(*) 
            FROM `staff_attendance_breaches`
            WHERE DATE(attempted_at) = :att_date
        ");
        $breachStmt->execute([':att_date' => $date]);
        $breachesCount = (int)$breachStmt->fetchColumn();

        $clockedIn = (int)($counts['clocked_in_count'] ?? 0);
        $late = (int)($counts['late_count'] ?? 0);
        $clockedOut = (int)($counts['clocked_out_count'] ?? 0);
        $early = (int)($counts['early_departure_count'] ?? 0);
        $absent = max(0, $totalStaff - $clockedIn);

        return [
            'total_staff' => $totalStaff,
            'clocked_in' => $clockedIn,
            'punctual' => max(0, $clockedIn - $late),
            'late' => $late,
            'clocked_out' => $clockedOut,
            'early_departure' => $early,
            'absent' => $absent,
            'breaches' => $breachesCount,
            'rate_percent' => $totalStaff > 0 ? round(($clockedIn / $totalStaff) * 100, 1) : 0.0,
        ];
    }

    /**
     * Retrieve logged out-of-perimeter breaches.
     *
     * @return StaffAttendanceBreach[]
     */
    public function getBreaches(?string $date = null, int $limit = 50): array
    {
        $where = [];
        $params = [];

        if (!empty($date)) {
            $where[] = 'DATE(b.attempted_at) = :att_date';
            $params[':att_date'] = $date;
        }

        $whereSql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        $sql = "SELECT b.*, u.name as staff_name, u.email as staff_email 
                FROM `staff_attendance_breaches` b
                JOIN `users` u ON u.id = b.user_id
                {$whereSql}
                ORDER BY b.attempted_at DESC 
                LIMIT :limit";

        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return array_map(fn(array $r) => StaffAttendanceBreach::fromArray($r), $rows);
    }

    /**
     * Manual administrative correction/recording.
     */
    public function manualCorrection(int $userId, string $date, array $data): StaffAttendance
    {
        $existing = $this->getTodayAttendanceForUser($userId, $date);

        if ($existing) {
            $sql = "UPDATE `staff_attendance` SET
                        `status` = :status,
                        `is_late` = :is_late,
                        `notes` = :notes,
                        `updated_at` = CURRENT_TIMESTAMP
                    WHERE `id` = :id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':id' => $existing->id,
                ':status' => $data['status'],
                ':is_late' => !empty($data['is_late']) ? 1 : 0,
                ':notes' => $data['notes'] ?? $existing->notes,
            ]);
            return $this->findById($existing->id);
        }

        // Insert new corrected entry
        $data['user_id'] = $userId;
        $data['attendance_date'] = $date;
        $data['clock_in_at'] = $data['clock_in_at'] ?? "{$date} 08:00:00";
        $data['clock_in_latitude'] = $data['clock_in_latitude'] ?? 9.0882;
        $data['clock_in_longitude'] = $data['clock_in_longitude'] ?? 7.4641;
        $data['clock_in_distance_meters'] = $data['clock_in_distance_meters'] ?? 0;
        return $this->recordClockIn($data);
    }
}
