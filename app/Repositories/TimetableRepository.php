<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Models\TimetableSlot;
use PDO;

/**
 * Timetable Repository
 * Handles database operations for timetable slots, queries by class, teacher, student enrollments, and conflict detection.
 */
class TimetableRepository
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::getConnection();
    }

    /**
     * Find a timetable slot by ID.
     */
    public function findById(int $id): ?TimetableSlot
    {
        $sql = "
            SELECT 
                ts.*,
                cs.session_id,
                cs.class_id,
                cs.subject_id,
                cs.teacher_id,
                cs.status AS class_subject_status,
                sub.name AS subject_name,
                sub.code AS subject_code,
                c.name AS class_name,
                c.section_arm,
                u.name AS teacher_name,
                t.staff_id AS teacher_staff_id,
                tm.name AS term_name,
                tm.session_id AS term_session_id,
                tm.start_date AS term_start_date,
                tm.end_date AS term_end_date,
                tm.status AS term_status,
                tm.is_current AS term_is_current
            FROM timetable_slots ts
            JOIN class_subjects cs ON cs.id = ts.class_subject_id
            JOIN subjects sub ON sub.id = cs.subject_id
            JOIN classes c ON c.id = cs.class_id
            JOIN terms tm ON tm.id = ts.term_id
            LEFT JOIN teachers t ON t.id = cs.teacher_id
            LEFT JOIN users u ON u.id = t.user_id
            WHERE ts.id = :id
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? TimetableSlot::fromArray($row) : null;
    }

    /**
     * Create a new timetable slot.
     *
     * @param array{term_id: int, class_subject_id: int, day_of_week: string, start_time: string, end_time: string, room?: ?string}|TimetableSlot $data
     */
    public function create(array|TimetableSlot $data): TimetableSlot
    {
        if ($data instanceof TimetableSlot) {
            $data = [
                'term_id'          => $data->termId,
                'class_subject_id' => $data->classSubjectId,
                'day_of_week'      => $data->dayOfWeek,
                'start_time'       => $data->startTime,
                'end_time'         => $data->endTime,
                'room'             => $data->room,
            ];
        }

        $startTime = self::normalizeTime((string)$data['start_time']);
        $endTime = self::normalizeTime((string)$data['end_time']);
        $room = isset($data['room']) && trim((string)$data['room']) !== '' ? trim((string)$data['room']) : null;

        $sql = "
            INSERT INTO timetable_slots (
                term_id, class_subject_id, day_of_week, start_time, end_time, room, created_at, updated_at
            ) VALUES (
                :term_id, :class_subject_id, :day_of_week, :start_time, :end_time, :room, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
            )
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':term_id', (int)$data['term_id'], PDO::PARAM_INT);
        $stmt->bindValue(':class_subject_id', (int)$data['class_subject_id'], PDO::PARAM_INT);
        $stmt->bindValue(':day_of_week', strtolower((string)$data['day_of_week']));
        $stmt->bindValue(':start_time', $startTime);
        $stmt->bindValue(':end_time', $endTime);
        $stmt->bindValue(':room', $room, $room === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->execute();

        $slotId = (int)$this->db->lastInsertId();
        return $this->findById($slotId) ?? throw new \RuntimeException("Failed to retrieve created timetable slot #{$slotId}");
    }

    /**
     * Alias for detectConflicts — supports both naming conventions in tests.
     */
    public function detectConflicts(
        int $termId,
        int $classSubjectId,
        string $dayOfWeek,
        string $startTime,
        string $endTime,
        ?string $room = null,
        ?int $excludeSlotId = null
    ): array {
        return $this->findConflicts($termId, $dayOfWeek, $startTime, $endTime, $classSubjectId, $room, $excludeSlotId);
    }

    /**
     * Update an existing timetable slot.
     *
     * @param array{term_id?: int, class_subject_id?: int, day_of_week?: string, start_time?: string, end_time?: string, room?: ?string} $data
     */
    public function update(int $id, array $data): TimetableSlot
    {
        $existing = $this->findById($id);
        if (!$existing) {
            throw new \InvalidArgumentException("Timetable slot #{$id} does not exist.");
        }

        $termId = isset($data['term_id']) ? (int)$data['term_id'] : $existing->termId;
        $classSubjectId = isset($data['class_subject_id']) ? (int)$data['class_subject_id'] : $existing->classSubjectId;
        $dayOfWeek = isset($data['day_of_week']) ? strtolower((string)$data['day_of_week']) : $existing->dayOfWeek;
        $startTime = isset($data['start_time']) ? self::normalizeTime((string)$data['start_time']) : $existing->startTime;
        $endTime = isset($data['end_time']) ? self::normalizeTime((string)$data['end_time']) : $existing->endTime;
        $room = array_key_exists('room', $data)
            ? (isset($data['room']) && trim((string)$data['room']) !== '' ? trim((string)$data['room']) : null)
            : $existing->room;

        $sql = "
            UPDATE timetable_slots
            SET term_id = :term_id,
                class_subject_id = :class_subject_id,
                day_of_week = :day_of_week,
                start_time = :start_time,
                end_time = :end_time,
                room = :room,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = :id
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':term_id', $termId, PDO::PARAM_INT);
        $stmt->bindValue(':class_subject_id', $classSubjectId, PDO::PARAM_INT);
        $stmt->bindValue(':day_of_week', $dayOfWeek);
        $stmt->bindValue(':start_time', $startTime);
        $stmt->bindValue(':end_time', $endTime);
        $stmt->bindValue(':room', $room, $room === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        return $this->findById($id) ?? throw new \RuntimeException("Failed to retrieve updated timetable slot #{$id}");
    }

    /**
     * Delete a timetable slot by ID.
     */
    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM timetable_slots WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    /**
     * Find all timetable slots for a class in a specific term.
     *
     * @return array<int, TimetableSlot>
     */
    public function findByClass(int $classId, int $termId): array
    {
        $sql = "
            SELECT 
                ts.*,
                cs.session_id,
                cs.class_id,
                cs.subject_id,
                cs.teacher_id,
                cs.status AS class_subject_status,
                sub.name AS subject_name,
                sub.code AS subject_code,
                c.name AS class_name,
                c.section_arm,
                u.name AS teacher_name,
                t.staff_id AS teacher_staff_id,
                tm.name AS term_name,
                tm.session_id AS term_session_id,
                tm.start_date AS term_start_date,
                tm.end_date AS term_end_date,
                tm.status AS term_status,
                tm.is_current AS term_is_current
            FROM timetable_slots ts
            JOIN class_subjects cs ON cs.id = ts.class_subject_id
            JOIN subjects sub ON sub.id = cs.subject_id
            JOIN classes c ON c.id = cs.class_id
            JOIN terms tm ON tm.id = ts.term_id
            LEFT JOIN teachers t ON t.id = cs.teacher_id
            LEFT JOIN users u ON u.id = t.user_id
            WHERE cs.class_id = :class_id
              AND ts.term_id = :term_id
            ORDER BY 
                CASE ts.day_of_week
                    WHEN 'mon' THEN 1
                    WHEN 'tue' THEN 2
                    WHEN 'wed' THEN 3
                    WHEN 'thu' THEN 4
                    WHEN 'fri' THEN 5
                    WHEN 'sat' THEN 6
                    WHEN 'sun' THEN 7
                    ELSE 8
                END ASC,
                ts.start_time ASC,
                ts.end_time ASC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':class_id' => $classId,
            ':term_id' => $termId,
        ]);

        $slots = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $slots[] = TimetableSlot::fromArray($row);
        }
        return $slots;
    }

    /**
     * Find all timetable slots for a teacher in a specific term.
     *
     * @return array<int, TimetableSlot>
     */
    public function findByTeacher(int $teacherId, int $termId): array
    {
        $sql = "
            SELECT 
                ts.*,
                cs.session_id,
                cs.class_id,
                cs.subject_id,
                cs.teacher_id,
                cs.status AS class_subject_status,
                sub.name AS subject_name,
                sub.code AS subject_code,
                c.name AS class_name,
                c.section_arm,
                u.name AS teacher_name,
                t.staff_id AS teacher_staff_id,
                tm.name AS term_name,
                tm.session_id AS term_session_id,
                tm.start_date AS term_start_date,
                tm.end_date AS term_end_date,
                tm.status AS term_status,
                tm.is_current AS term_is_current
            FROM timetable_slots ts
            JOIN class_subjects cs ON cs.id = ts.class_subject_id
            JOIN subjects sub ON sub.id = cs.subject_id
            JOIN classes c ON c.id = cs.class_id
            JOIN terms tm ON tm.id = ts.term_id
            LEFT JOIN teachers t ON t.id = cs.teacher_id
            LEFT JOIN users u ON u.id = t.user_id
            WHERE cs.teacher_id = :teacher_id
              AND ts.term_id = :term_id
            ORDER BY 
                CASE ts.day_of_week
                    WHEN 'mon' THEN 1
                    WHEN 'tue' THEN 2
                    WHEN 'wed' THEN 3
                    WHEN 'thu' THEN 4
                    WHEN 'fri' THEN 5
                    WHEN 'sat' THEN 6
                    WHEN 'sun' THEN 7
                    ELSE 8
                END ASC,
                ts.start_time ASC,
                ts.end_time ASC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':teacher_id' => $teacherId,
            ':term_id' => $termId,
        ]);

        $slots = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $slots[] = TimetableSlot::fromArray($row);
        }
        return $slots;
    }

    /**
     * Find timetable slots for an explicit list of class_subject IDs in a term (e.g. for enrolled student subjects).
     *
     * @param array<int> $classSubjectIds
     * @return array<int, TimetableSlot>
     */
    public function findByClassSubjects(array $classSubjectIds, int $termId): array
    {
        if (empty($classSubjectIds)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($classSubjectIds), '?'));

        $sql = "
            SELECT 
                ts.*,
                cs.session_id,
                cs.class_id,
                cs.subject_id,
                cs.teacher_id,
                cs.status AS class_subject_status,
                sub.name AS subject_name,
                sub.code AS subject_code,
                c.name AS class_name,
                c.section_arm,
                u.name AS teacher_name,
                t.staff_id AS teacher_staff_id,
                tm.name AS term_name,
                tm.session_id AS term_session_id,
                tm.start_date AS term_start_date,
                tm.end_date AS term_end_date,
                tm.status AS term_status,
                tm.is_current AS term_is_current
            FROM timetable_slots ts
            JOIN class_subjects cs ON cs.id = ts.class_subject_id
            JOIN subjects sub ON sub.id = cs.subject_id
            JOIN classes c ON c.id = cs.class_id
            JOIN terms tm ON tm.id = ts.term_id
            LEFT JOIN teachers t ON t.id = cs.teacher_id
            LEFT JOIN users u ON u.id = t.user_id
            WHERE ts.term_id = ?
              AND ts.class_subject_id IN ({$placeholders})
            ORDER BY 
                CASE ts.day_of_week
                    WHEN 'mon' THEN 1
                    WHEN 'tue' THEN 2
                    WHEN 'wed' THEN 3
                    WHEN 'thu' THEN 4
                    WHEN 'fri' THEN 5
                    WHEN 'sat' THEN 6
                    WHEN 'sun' THEN 7
                    ELSE 8
                END ASC,
                ts.start_time ASC,
                ts.end_time ASC
        ";

        $stmt = $this->db->prepare($sql);
        $params = array_merge([(int)$termId], array_map('intval', $classSubjectIds));
        $stmt->execute($params);

        $slots = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $slots[] = TimetableSlot::fromArray($row);
        }
        return $slots;
    }

    /**
     * Find potential timetable conflicts based on the half-open interval rule:
     * Overlap Condition: (A_start < B_end) AND (A_end > B_start)
     *
     * Returns an array of conflicting slot records with conflict types:
     * - 'teacher': Same teacher scheduled at the same time in another slot
     * - 'class': Same class cohort scheduled at the same time in another slot
     * - 'room': Same physical room scheduled at the same time in another slot
     *
     * @return array<int, array{slot: TimetableSlot, type: string, message: string}>
     */
    public function findConflicts(
        int $termId,
        string $dayOfWeek,
        string $startTime,
        string $endTime,
        int $classSubjectId,
        ?string $room = null,
        ?int $excludeSlotId = null
    ): array {
        $startTime = self::normalizeTime($startTime);
        $endTime = self::normalizeTime($endTime);
        $dayOfWeek = strtolower(trim($dayOfWeek));
        $room = $room !== null && trim($room) !== '' ? trim($room) : null;

        // Fetch details of target class_subject to compare class_id and teacher_id
        $targetCsStmt = $this->db->prepare("
            SELECT cs.class_id, cs.teacher_id, c.name AS class_name, sub.name AS subject_name, u.name AS teacher_name
            FROM class_subjects cs
            JOIN classes c ON c.id = cs.class_id
            JOIN subjects sub ON sub.id = cs.subject_id
            LEFT JOIN teachers t ON t.id = cs.teacher_id
            LEFT JOIN users u ON u.id = t.user_id
            WHERE cs.id = :cs_id
            LIMIT 1
        ");
        $targetCsStmt->execute([':cs_id' => $classSubjectId]);
        $targetCs = $targetCsStmt->fetch(PDO::FETCH_ASSOC);

        if (!$targetCs) {
            return [];
        }

        $targetClassId = (int)$targetCs['class_id'];
        $targetTeacherId = (int)($targetCs['teacher_id'] ?? 0);

        // Fetch all candidate slots in the same term, same day of week, overlapping the time interval
        // Overlap: :start_time < ts.end_time AND :end_time > ts.start_time
        $sql = "
            SELECT 
                ts.*,
                cs.session_id,
                cs.class_id,
                cs.subject_id,
                cs.teacher_id,
                cs.status AS class_subject_status,
                sub.name AS subject_name,
                sub.code AS subject_code,
                c.name AS class_name,
                c.section_arm,
                u.name AS teacher_name,
                t.staff_id AS teacher_staff_id,
                tm.name AS term_name,
                tm.session_id AS term_session_id,
                tm.start_date AS term_start_date,
                tm.end_date AS term_end_date,
                tm.status AS term_status,
                tm.is_current AS term_is_current
            FROM timetable_slots ts
            JOIN class_subjects cs ON cs.id = ts.class_subject_id
            JOIN subjects sub ON sub.id = cs.subject_id
            JOIN classes c ON c.id = cs.class_id
            JOIN terms tm ON tm.id = ts.term_id
            LEFT JOIN teachers t ON t.id = cs.teacher_id
            LEFT JOIN users u ON u.id = t.user_id
            WHERE ts.term_id = :term_id
              AND ts.day_of_week = :day_of_week
              AND :start_time < ts.end_time
              AND :end_time > ts.start_time
        ";

        if ($excludeSlotId !== null && $excludeSlotId > 0) {
            $sql .= " AND ts.id != :exclude_id";
        }

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':term_id', $termId, PDO::PARAM_INT);
        $stmt->bindValue(':day_of_week', $dayOfWeek);
        $stmt->bindValue(':start_time', $startTime);
        $stmt->bindValue(':end_time', $endTime);
        if ($excludeSlotId !== null && $excludeSlotId > 0) {
            $stmt->bindValue(':exclude_id', $excludeSlotId, PDO::PARAM_INT);
        }
        $stmt->execute();

        $conflicts = [];

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $existingSlot = TimetableSlot::fromArray($row);
            $existingClassId = (int)$row['class_id'];
            $existingTeacherId = (int)($row['teacher_id'] ?? 0);
            $existingRoom = isset($row['room']) && trim((string)$row['room']) !== '' ? trim((string)$row['room']) : null;

            // 1. Class conflict: Same class already has a subject scheduled during this overlapping window
            if ($existingClassId === $targetClassId) {
                $conflicts[] = [
                    'slot' => $existingSlot,
                    'type' => 'class',
                    'message' => "Class conflict: {$row['class_name']} already has '{$row['subject_name']}' scheduled from {$existingSlot->getFormattedTimeRange()}.",
                ];
            }

            // 2. Teacher conflict: Assigned teacher is already teaching another class during this overlapping window
            if ($targetTeacherId > 0 && $existingTeacherId > 0 && $targetTeacherId === $existingTeacherId) {
                $conflicts[] = [
                    'slot' => $existingSlot,
                    'type' => 'teacher',
                    'message' => "Teacher conflict: Instructor {$row['teacher_name']} is already scheduled to teach '{$row['subject_name']}' in {$row['class_name']} from {$existingSlot->getFormattedTimeRange()}.",
                ];
            }

            // 3. Room conflict: The same room is already allocated to another session during this overlapping window
            if ($room !== null && $existingRoom !== null && strcasecmp($room, $existingRoom) === 0) {
                $conflicts[] = [
                    'slot' => $existingSlot,
                    'type' => 'room',
                    'message' => "Room conflict: Room '{$room}' is already allocated to {$row['class_name']} ({$row['subject_name']}) from {$existingSlot->getFormattedTimeRange()}.",
                ];
            }
        }

        return $conflicts;
    }

    /**
     * Normalize time strings to HH:MM:SS format.
     *
     * @throws \App\Core\Exceptions\ValidationException on unparseable or out-of-range values.
     */
    public static function normalizeTime(string $time): string
    {
        $time = trim($time);
        if (preg_match('/^(\d{1,2}):(\d{2})$/', $time, $matches)) {
            $h = (int)$matches[1];
            $m = (int)$matches[2];
            if ($h > 23 || $m > 59) {
                throw new \App\Core\Exceptions\ValidationException(['time' => "Invalid time value: {$time}"]);
            }
            return sprintf('%02d:%02d:00', $h, $m);
        }
        if (preg_match('/^(\d{1,2}):(\d{2}):(\d{2})$/', $time, $matches)) {
            $h = (int)$matches[1];
            $m = (int)$matches[2];
            $s = (int)$matches[3];
            if ($h > 23 || $m > 59 || $s > 59) {
                throw new \App\Core\Exceptions\ValidationException(['time' => "Invalid time value: {$time}"]);
            }
            return sprintf('%02d:%02d:%02d', $h, $m, $s);
        }

        $timestamp = strtotime($time);
        if ($timestamp !== false) {
            return date('H:i:s', $timestamp);
        }

        throw new \App\Core\Exceptions\ValidationException(['time' => "Cannot parse time value: {$time}"]);
    }
}
