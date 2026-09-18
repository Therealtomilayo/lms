<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Models\AttendanceRecord;
use PDO;

/**
 * Attendance Repository
 */
class AttendanceRepository
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::getConnection();
    }

    /**
     * Get enrolled student roster for a class with their attendance status on a specific date.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getRosterForDate(
        int $classId,
        string $date,
        ?int $classSubjectId = null,
        ?int $periodNumber = null
    ): array {
        $sql = "
            SELECT 
                s.id AS student_id,
                s.admission_number,
                u.name AS student_name,
                u.email AS student_email,
                ar.id AS attendance_id,
                ar.session_id,
                ar.term_id,
                ar.status,
                ar.marked_by,
                ar.updated_by,
                ar.correction_reason,
                ar.created_at,
                ar.updated_at
            FROM class_enrollments ce
            JOIN students s ON s.id = ce.student_id
            JOIN users u ON u.id = s.user_id
            LEFT JOIN attendance_records ar ON ar.student_id = s.id 
                AND ar.class_id = ce.class_id 
                AND ar.date = :date
                AND ((:class_subject_id IS NULL AND ar.class_subject_id IS NULL) OR ar.class_subject_id = :class_subject_id_match)
                AND ((:period_number IS NULL AND ar.period_number IS NULL) OR ar.period_number = :period_number_match)
            WHERE ce.class_id = :class_id
              AND ce.status = 'active'
              AND u.status = 'active'
            ORDER BY u.name ASC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':date', $date);
        $stmt->bindValue(':class_subject_id', $classSubjectId, $classSubjectId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindValue(':class_subject_id_match', $classSubjectId, $classSubjectId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindValue(':period_number', $periodNumber, $periodNumber === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindValue(':period_number_match', $periodNumber, $periodNumber === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindValue(':class_id', $classId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Save a roster of attendance records in batch.
     *
     * @param array<int, array{student_id: int, status: string}> $records
     */
    public function saveRosterBatch(
        int $sessionId,
        int $termId,
        int $classId,
        ?int $classSubjectId,
        string $date,
        ?int $periodNumber,
        int $markedBy,
        array $records,
        ?string $correctionReason = null
    ): bool {
        $driver = $this->db->getAttribute(PDO::ATTR_DRIVER_NAME);

        foreach ($records as $rec) {
            $studentId = (int)$rec['student_id'];
            $status = (string)$rec['status'];

            // Find existing record
            $existing = $this->findExistingRecord($studentId, $classId, $date, $classSubjectId, $periodNumber);

            if ($existing) {
                // If status changed or updating
                $updateSql = "
                    UPDATE attendance_records
                    SET status = :status,
                        updated_by = :updated_by,
                        correction_reason = COALESCE(:correction_reason, correction_reason),
                        updated_at = CURRENT_TIMESTAMP
                    WHERE id = :id
                ";
                $stmt = $this->db->prepare($updateSql);
                $stmt->execute([
                    ':status' => $status,
                    ':updated_by' => $markedBy,
                    ':correction_reason' => $correctionReason,
                    ':id' => $existing->id,
                ]);
            } else {
                $insertSql = "
                    INSERT INTO attendance_records (
                        session_id, term_id, class_id, class_subject_id, student_id,
                        date, period_number, status, marked_by, correction_reason, created_at, updated_at
                    ) VALUES (
                        :session_id, :term_id, :class_id, :class_subject_id, :student_id,
                        :date, :period_number, :status, :marked_by, :correction_reason, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
                    )
                ";
                $stmt = $this->db->prepare($insertSql);
                $stmt->bindValue(':session_id', $sessionId, PDO::PARAM_INT);
                $stmt->bindValue(':term_id', $termId, PDO::PARAM_INT);
                $stmt->bindValue(':class_id', $classId, PDO::PARAM_INT);
                $stmt->bindValue(':class_subject_id', $classSubjectId, $classSubjectId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
                $stmt->bindValue(':student_id', $studentId, PDO::PARAM_INT);
                $stmt->bindValue(':date', $date);
                $stmt->bindValue(':period_number', $periodNumber, $periodNumber === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
                $stmt->bindValue(':status', $status);
                $stmt->bindValue(':marked_by', $markedBy, PDO::PARAM_INT);
                $stmt->bindValue(':correction_reason', $correctionReason, $correctionReason === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
                $stmt->execute();
            }
        }

        return true;
    }

    public function findExistingRecord(
        int $studentId,
        int $classId,
        string $date,
        ?int $classSubjectId = null,
        ?int $periodNumber = null
    ): ?AttendanceRecord {
        $sql = "
            SELECT * FROM attendance_records
            WHERE student_id = :student_id
              AND class_id = :class_id
              AND date = :date
              AND ((:class_subject_id IS NULL AND class_subject_id IS NULL) OR class_subject_id = :class_subject_id_match)
              AND ((:period_number IS NULL AND period_number IS NULL) OR period_number = :period_number_match)
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':student_id', $studentId, PDO::PARAM_INT);
        $stmt->bindValue(':class_id', $classId, PDO::PARAM_INT);
        $stmt->bindValue(':date', $date);
        $stmt->bindValue(':class_subject_id', $classSubjectId, $classSubjectId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindValue(':class_subject_id_match', $classSubjectId, $classSubjectId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindValue(':period_number', $periodNumber, $periodNumber === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindValue(':period_number_match', $periodNumber, $periodNumber === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? AttendanceRecord::fromArray($row) : null;
    }

    public function findRecordById(int $id): ?AttendanceRecord
    {
        $sql = "
            SELECT ar.*, u.name AS student_name, s.admission_number, m.name AS marker_name, up.name AS updater_name
            FROM attendance_records ar
            JOIN students s ON s.id = ar.student_id
            JOIN users u ON u.id = s.user_id
            JOIN users m ON m.id = ar.marked_by
            LEFT JOIN users up ON up.id = ar.updated_by
            WHERE ar.id = :id
            LIMIT 1
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? AttendanceRecord::fromArray($row) : null;
    }

    public function updateRecord(
        int $id,
        string $status,
        int $updatedBy,
        string $correctionReason
    ): bool {
        $sql = "
            UPDATE attendance_records
            SET status = :status,
                updated_by = :updated_by,
                correction_reason = :correction_reason,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = :id
        ";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':status' => $status,
            ':updated_by' => $updatedBy,
            ':correction_reason' => $correctionReason,
            ':id' => $id,
        ]);
    }

    /**
     * Get configured late attendance weight from system_settings (default: 0.60 = 60%).
     */
    public function getLateWeight(): float
    {
        try {
            $stmt = $this->db->prepare("SELECT setting_value FROM system_settings WHERE setting_key = 'attendance_late_weight' LIMIT 1");
            $stmt->execute();
            $val = $stmt->fetchColumn();
            if ($val !== false && is_numeric($val)) {
                $floatVal = (float)$val;
                // If stored as percentage (e.g. 60 or 50), normalize to decimal
                if ($floatVal > 1.0) {
                    $floatVal = $floatVal / 100.0;
                }
                return max(0.0, min(1.0, round($floatVal, 4)));
            }
        } catch (\Throwable) {
            // Fallback to default
        }
        return 0.60;
    }

    /**
     * Update attendance late weight in system_settings.
     */
    public function updateAttendancePolicy(float $lateWeight, ?int $adminId = null): void
    {
        $normalized = max(0.0, min(1.0, $lateWeight));
        $valStr = (string)$normalized;

        $checkStmt = $this->db->prepare("SELECT 1 FROM system_settings WHERE setting_key = 'attendance_late_weight' LIMIT 1");
        $checkStmt->execute();
        if ($checkStmt->fetchColumn()) {
            $stmt = $this->db->prepare("
                UPDATE system_settings 
                SET setting_value = :val, updated_by = :admin_id, updated_at = CURRENT_TIMESTAMP 
                WHERE setting_key = 'attendance_late_weight'
            ");
        } else {
            $stmt = $this->db->prepare("
                INSERT INTO system_settings (setting_key, setting_value, updated_by, updated_at) 
                VALUES ('attendance_late_weight', :val, :admin_id, CURRENT_TIMESTAMP)
            ");
        }
        $stmt->execute([
            ':val' => $valStr,
            ':admin_id' => $adminId,
        ]);
    }

    /**
     * Calculate student attendance metrics.
     *
     * @return array{total_days: int, present_days: int, absent_days: int, late_days: int, excused_days: int, attendance_rate: float, weighted_rate: float, unweighted_rate: float, late_weight: float}
     */
    public function getStudentAttendanceSummary(int $studentId, int $termId, ?int $classSubjectId = null): array
    {
        $sql = "
            SELECT 
                COUNT(*) AS total_records,
                SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) AS present_count,
                SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) AS absent_count,
                SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) AS late_count,
                SUM(CASE WHEN status = 'excused' THEN 1 ELSE 0 END) AS excused_count
            FROM attendance_records
            WHERE student_id = :student_id
              AND term_id = :term_id
              AND ((:class_subject_id IS NULL AND class_subject_id IS NULL) OR class_subject_id = :class_subject_id_match)
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':student_id', $studentId, PDO::PARAM_INT);
        $stmt->bindValue(':term_id', $termId, PDO::PARAM_INT);
        $stmt->bindValue(':class_subject_id', $classSubjectId, $classSubjectId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindValue(':class_subject_id_match', $classSubjectId, $classSubjectId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        $total = (int)($row['total_records'] ?? 0);
        $present = (int)($row['present_count'] ?? 0);
        $absent = (int)($row['absent_count'] ?? 0);
        $late = (int)($row['late_count'] ?? 0);
        $excused = (int)($row['excused_count'] ?? 0);

        // Effective attended = present + late
        $attended = $present + $late;
        $rate = $total > 0 ? round(($attended / $total) * 100, 2) : 100.00;

        // SRS §26: Configurable Weighted Late Attendance Calculation Formula:
        // Attendance Rate = (Present + (Late * LateWeight)) / Total * 100
        $lateWeight = $this->getLateWeight();
        $effectiveAttended = (float)$present + ((float)$late * $lateWeight);
        $weightedRate = $total > 0 ? round(($effectiveAttended / $total) * 100, 2) : 100.00;

        return [
            'total_days' => $total,
            'present_days' => $present,
            'absent_days' => $absent,
            'late_days' => $late,
            'excused_days' => $excused,
            'attendance_rate' => $rate,
            'weighted_rate' => $weightedRate,
            'unweighted_rate' => $rate,
            'late_weight' => $lateWeight,
            'effective_attended' => $effectiveAttended,
            // Aliases for view compatibility
            'percentage' => $weightedRate,
            'present' => $present,
            'absent' => $absent,
            'late' => $late,
            'excused' => $excused,
            'total' => $total,
        ];
    }

    /**
     * Alias for getStudentAttendanceSummary.
     *
     * @return array{total_days: int, present_days: int, absent_days: int, late_days: int, excused_days: int, attendance_rate: float}
     */
    public function getStudentTermSummary(int $studentId, int $termId, ?int $classSubjectId = null): array
    {
        return $this->getStudentAttendanceSummary($studentId, $termId, $classSubjectId);
    }

    /**
     * Alias for getStudentAttendanceHistory by term.
     *
     * @return array<int, AttendanceRecord>
     */
    public function getStudentAttendanceForTerm(int $studentId, int $termId): array
    {
        return $this->getStudentAttendanceHistory($studentId, $termId);
    }

    /**
     * Get detailed attendance log for a student in a term.
     *
     * @return array<int, AttendanceRecord>
     */
    public function getStudentAttendanceHistory(int $studentId, int $termId, ?int $classSubjectId = null): array
    {
        $sql = "
            SELECT ar.*, u.name AS student_name, s.admission_number, m.name AS marker_name, up.name AS updater_name
            FROM attendance_records ar
            JOIN students s ON s.id = ar.student_id
            JOIN users u ON u.id = s.user_id
            JOIN users m ON m.id = ar.marked_by
            LEFT JOIN users up ON up.id = ar.updated_by
            WHERE ar.student_id = :student_id
              AND ar.term_id = :term_id
              AND ((:class_subject_id IS NULL AND ar.class_subject_id IS NULL) OR ar.class_subject_id = :class_subject_id_match)
            ORDER BY ar.date DESC, ar.period_number ASC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':student_id', $studentId, PDO::PARAM_INT);
        $stmt->bindValue(':term_id', $termId, PDO::PARAM_INT);
        $stmt->bindValue(':class_subject_id', $classSubjectId, $classSubjectId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindValue(':class_subject_id_match', $classSubjectId, $classSubjectId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->execute();

        $results = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $results[] = AttendanceRecord::fromArray($row);
        }
        return $results;
    }

    /**
     * Get class-level attendance matrix for admin/teacher reports.
     *
     * @return array<string, mixed>
     */
    public function getClassAttendanceReport(
        int $classId,
        int $termId,
        ?string $startDate = null,
        ?string $endDate = null
    ): array {
        $sql = "
            SELECT 
                ar.date,
                COUNT(ar.id) AS total_students,
                SUM(CASE WHEN ar.status = 'present' THEN 1 ELSE 0 END) AS present_count,
                SUM(CASE WHEN ar.status = 'absent' THEN 1 ELSE 0 END) AS absent_count,
                SUM(CASE WHEN ar.status = 'late' THEN 1 ELSE 0 END) AS late_count,
                SUM(CASE WHEN ar.status = 'excused' THEN 1 ELSE 0 END) AS excused_count
            FROM attendance_records ar
            WHERE ar.class_id = :class_id
              AND ar.term_id = :term_id
              AND ar.class_subject_id IS NULL
        ";

        $params = [
            ':class_id' => $classId,
            ':term_id' => $termId,
        ];

        if ($startDate !== null && $startDate !== '') {
            $sql .= " AND ar.date >= :start_date";
            $params[':start_date'] = $startDate;
        }

        if ($endDate !== null && $endDate !== '') {
            $sql .= " AND ar.date <= :end_date";
            $params[':end_date'] = $endDate;
        }

        $sql .= " GROUP BY ar.date ORDER BY ar.date DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $lateWeight = $this->getLateWeight();
        foreach ($rows as &$row) {
            $tot = (int)($row['total_students'] ?? 0);
            $pres = (int)($row['present_count'] ?? 0);
            $lt = (int)($row['late_count'] ?? 0);
            $row['unweighted_rate'] = $tot > 0 ? round((($pres + $lt) / $tot) * 100, 1) : 0.0;
            $row['weighted_rate'] = $tot > 0 ? round((($pres + ($lt * $lateWeight)) / $tot) * 100, 1) : 0.0;
            $row['late_weight'] = $lateWeight;
        }
        unset($row);

        return $rows;
    }

    /**
     * Synchronize student_term_summaries attendance counts for a student.
     */
    public function syncStudentTermSummaryAttendance(int $studentId, int $termId): void
    {
        // Compute daily attendance counts (class_subject_id IS NULL)
        $summary = $this->getStudentAttendanceSummary($studentId, $termId, null);

        // Update student_term_summaries if summary record exists
        $sql = "
            UPDATE student_term_summaries
            SET attendance_present_count = :present_count,
                attendance_total_count = :total_count,
                updated_at = CURRENT_TIMESTAMP
            WHERE student_id = :student_id AND term_id = :term_id
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':present_count' => $summary['present_days'] + $summary['late_days'],
            ':total_count' => $summary['total_days'],
            ':student_id' => $studentId,
            ':term_id' => $termId,
        ]);
    }
}
