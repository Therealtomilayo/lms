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
    private bool $hasTsClassId = true;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::getConnection();
        $this->checkColumns();
    }

    private function checkColumns(): void
    {
        $driver = $this->db->getAttribute(PDO::ATTR_DRIVER_NAME);
        if ($driver === 'sqlite') {
            try {
                $cols = $this->db->query("PRAGMA table_info(timetable_slots)")->fetchAll(PDO::FETCH_ASSOC);
                $names = array_column($cols, 'name');
                $this->hasTsClassId = in_array('class_id', $names, true);
            } catch (\Throwable) {
                $this->hasTsClassId = false;
            }
        } else {
            $this->hasTsClassId = true;
        }
    }

    /**
     * Find a timetable slot by ID.
     */
    public function findById(int $id): ?TimetableSlot
    {
        $classIdCol = $this->hasTsClassId ? "COALESCE(ts.class_id, cs.class_id) AS class_id" : "cs.class_id AS class_id";
        $classesJoin = $this->hasTsClassId ? "LEFT JOIN classes c ON c.id = COALESCE(ts.class_id, cs.class_id)" : "JOIN classes c ON c.id = cs.class_id";

        $sql = "
            SELECT 
                ts.*,
                cs.session_id,
                {$classIdCol},
                cs.subject_id,
                cs.teacher_id,
                cs.status AS class_subject_status,
                COALESCE(sub.name, " . ($this->hasTsClassId ? "ts.custom_title, " : "") . "'General Activity') AS subject_name,
                COALESCE(sub.code, " . ($this->hasTsClassId ? "ts.custom_title, " : "") . "'') AS subject_code,
                c.name AS class_name,
                c.section_arm,
                u.name AS teacher_name,
                t.staff_id AS teacher_staff_id,
                tm.name AS term_name,
                tm.session_id AS term_session_id,
                tm.start_date AS term_start_date,
                tm.end_date AS term_end_date,
                tm.status AS term_status,
                (tm.status = 'active') AS term_is_current
            FROM timetable_slots ts
            LEFT JOIN class_subjects cs ON cs.id = ts.class_subject_id
            LEFT JOIN subjects sub ON sub.id = cs.subject_id
            {$classesJoin}
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
     * @param array{term_id: int, class_subject_id?: ?int, class_id?: ?int, structure_period_id?: ?int, department?: ?string, custom_title?: ?string, slot_type?: string, day_of_week: string, start_time: string, end_time: string, room?: ?string}|TimetableSlot $data
     */
    public function create(array|TimetableSlot $data): TimetableSlot
    {
        if ($data instanceof TimetableSlot) {
            $data = [
                'term_id'             => $data->termId,
                'class_id'            => $data->classId,
                'class_subject_id'    => $data->classSubjectId,
                'structure_period_id' => $data->structurePeriodId,
                'department'          => $data->department,
                'custom_title'        => $data->customTitle,
                'slot_type'           => $data->slotType,
                'day_of_week'         => $data->dayOfWeek,
                'start_time'          => $data->startTime,
                'end_time'            => $data->endTime,
                'room'                => $data->room,
            ];
        }

        $classSubjectId = isset($data['class_subject_id']) && $data['class_subject_id'] !== null && $data['class_subject_id'] !== '' ? (int)$data['class_subject_id'] : null;
        $classId = isset($data['class_id']) && $data['class_id'] !== null && $data['class_id'] !== '' ? (int)$data['class_id'] : null;

        if ($classId === null && $classSubjectId !== null) {
            $fetchC = $this->db->prepare("SELECT class_id FROM class_subjects WHERE id = :cs_id LIMIT 1");
            $fetchC->execute([':cs_id' => $classSubjectId]);
            $foundC = $fetchC->fetchColumn();
            if ($foundC) {
                $classId = (int)$foundC;
            }
        }

        $startTime = self::normalizeTime((string)$data['start_time']);
        $endTime = self::normalizeTime((string)$data['end_time']);
        $room = isset($data['room']) && trim((string)$data['room']) !== '' ? trim((string)$data['room']) : null;

        if ($this->hasTsClassId) {
            $structurePeriodId = isset($data['structure_period_id']) && $data['structure_period_id'] !== null && $data['structure_period_id'] !== '' ? (int)$data['structure_period_id'] : null;
            $department = isset($data['department']) && trim((string)$data['department']) !== '' ? trim((string)$data['department']) : null;
            $customTitle = isset($data['custom_title']) && trim((string)$data['custom_title']) !== '' ? trim((string)$data['custom_title']) : null;
            $slotType = (string)($data['slot_type'] ?? 'instructional');

            $sql = "
                INSERT INTO timetable_slots (
                    term_id, class_id, class_subject_id, structure_period_id, department, custom_title, slot_type, day_of_week, start_time, end_time, room, created_at, updated_at
                ) VALUES (
                    :term_id, :class_id, :class_subject_id, :structure_period_id, :department, :custom_title, :slot_type, :day_of_week, :start_time, :end_time, :room, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
                )
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':term_id', (int)$data['term_id'], PDO::PARAM_INT);
            $stmt->bindValue(':class_id', $classId, $classId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
            $stmt->bindValue(':class_subject_id', $classSubjectId, $classSubjectId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
            $stmt->bindValue(':structure_period_id', $structurePeriodId, $structurePeriodId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
            $stmt->bindValue(':department', $department, $department === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
            $stmt->bindValue(':custom_title', $customTitle, $customTitle === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
            $stmt->bindValue(':slot_type', $slotType, PDO::PARAM_STR);
            $stmt->bindValue(':day_of_week', strtolower((string)$data['day_of_week']));
            $stmt->bindValue(':start_time', $startTime);
            $stmt->bindValue(':end_time', $endTime);
            $stmt->bindValue(':room', $room, $room === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
            $stmt->execute();
        } else {
            $sql = "
                INSERT INTO timetable_slots (
                    term_id, class_subject_id, day_of_week, start_time, end_time, room, created_at, updated_at
                ) VALUES (
                    :term_id, :class_subject_id, :day_of_week, :start_time, :end_time, :room, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
                )
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':term_id', (int)$data['term_id'], PDO::PARAM_INT);
            $stmt->bindValue(':class_subject_id', (int)($classSubjectId ?? 0), PDO::PARAM_INT);
            $stmt->bindValue(':day_of_week', strtolower((string)$data['day_of_week']));
            $stmt->bindValue(':start_time', $startTime);
            $stmt->bindValue(':end_time', $endTime);
            $stmt->bindValue(':room', $room, $room === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
            $stmt->execute();
        }

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
        ?int $excludeSlotId = null,
        ?string $department = null
    ): array {
        return $this->findConflicts($termId, $dayOfWeek, $startTime, $endTime, $classSubjectId, $room, $excludeSlotId, null, $department);
    }

    /**
     * Update an existing timetable slot.
     *
     * @param array{term_id?: int, class_subject_id?: ?int, class_id?: ?int, structure_period_id?: ?int, department?: ?string, custom_title?: ?string, slot_type?: string, day_of_week?: string, start_time?: string, end_time?: string, room?: ?string} $data
     */
    public function update(int $id, array $data): TimetableSlot
    {
        $existing = $this->findById($id);
        if (!$existing) {
            throw new \InvalidArgumentException("Timetable slot #{$id} does not exist.");
        }

        $termId = isset($data['term_id']) ? (int)$data['term_id'] : $existing->termId;
        $classSubjectId = array_key_exists('class_subject_id', $data)
            ? (isset($data['class_subject_id']) && $data['class_subject_id'] !== '' ? (int)$data['class_subject_id'] : null)
            : $existing->classSubjectId;
        $classId = array_key_exists('class_id', $data)
            ? (isset($data['class_id']) && $data['class_id'] !== '' ? (int)$data['class_id'] : null)
            : $existing->classId;
        $structurePeriodId = array_key_exists('structure_period_id', $data)
            ? (isset($data['structure_period_id']) && $data['structure_period_id'] !== '' ? (int)$data['structure_period_id'] : null)
            : $existing->structurePeriodId;
        $department = array_key_exists('department', $data)
            ? (isset($data['department']) && trim((string)$data['department']) !== '' ? trim((string)$data['department']) : null)
            : $existing->department;
        $customTitle = array_key_exists('custom_title', $data)
            ? (isset($data['custom_title']) && trim((string)$data['custom_title']) !== '' ? trim((string)$data['custom_title']) : null)
            : $existing->customTitle;
        $slotType = (string)($data['slot_type'] ?? $existing->slotType);

        $dayOfWeek = isset($data['day_of_week']) ? strtolower((string)$data['day_of_week']) : $existing->dayOfWeek;
        $startTime = isset($data['start_time']) ? self::normalizeTime((string)$data['start_time']) : $existing->startTime;
        $endTime = isset($data['end_time']) ? self::normalizeTime((string)$data['end_time']) : $existing->endTime;
        $room = array_key_exists('room', $data)
            ? (isset($data['room']) && trim((string)$data['room']) !== '' ? trim((string)$data['room']) : null)
            : $existing->room;

        if ($this->hasTsClassId) {
            $sql = "
                UPDATE timetable_slots
                SET term_id = :term_id,
                    class_id = :class_id,
                    class_subject_id = :class_subject_id,
                    structure_period_id = :structure_period_id,
                    department = :department,
                    custom_title = :custom_title,
                    slot_type = :slot_type,
                    day_of_week = :day_of_week,
                    start_time = :start_time,
                    end_time = :end_time,
                    room = :room,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = :id
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':term_id', $termId, PDO::PARAM_INT);
            $stmt->bindValue(':class_id', $classId, $classId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
            $stmt->bindValue(':class_subject_id', $classSubjectId, $classSubjectId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
            $stmt->bindValue(':structure_period_id', $structurePeriodId, $structurePeriodId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
            $stmt->bindValue(':department', $department, $department === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
            $stmt->bindValue(':custom_title', $customTitle, $customTitle === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
            $stmt->bindValue(':slot_type', $slotType, PDO::PARAM_STR);
            $stmt->bindValue(':day_of_week', $dayOfWeek);
            $stmt->bindValue(':start_time', $startTime);
            $stmt->bindValue(':end_time', $endTime);
            $stmt->bindValue(':room', $room, $room === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
        } else {
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
            $stmt->bindValue(':class_subject_id', (int)($classSubjectId ?? 0), PDO::PARAM_INT);
            $stmt->bindValue(':day_of_week', $dayOfWeek);
            $stmt->bindValue(':start_time', $startTime);
            $stmt->bindValue(':end_time', $endTime);
            $stmt->bindValue(':room', $room, $room === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
        }

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
        $classIdCol = $this->hasTsClassId ? "COALESCE(ts.class_id, cs.class_id) AS class_id" : "cs.class_id AS class_id";
        $classesJoin = $this->hasTsClassId ? "LEFT JOIN classes c ON c.id = COALESCE(ts.class_id, cs.class_id)" : "JOIN classes c ON c.id = cs.class_id";
        $subjectNameCol = $this->hasTsClassId ? "COALESCE(sub.name, ts.custom_title, 'General Activity') AS subject_name" : "sub.name AS subject_name";
        $subjectCodeCol = $this->hasTsClassId ? "COALESCE(sub.code, ts.custom_title, '') AS subject_code" : "sub.code AS subject_code";
        $whereClass = $this->hasTsClassId ? "COALESCE(ts.class_id, cs.class_id) = :class_id" : "cs.class_id = :class_id";

        $sql = "
            SELECT 
                ts.*,
                cs.session_id,
                {$classIdCol},
                cs.subject_id,
                cs.teacher_id,
                cs.status AS class_subject_status,
                {$subjectNameCol},
                {$subjectCodeCol},
                c.name AS class_name,
                c.section_arm,
                u.name AS teacher_name,
                t.staff_id AS teacher_staff_id,
                tm.name AS term_name,
                tm.session_id AS term_session_id,
                tm.start_date AS term_start_date,
                tm.end_date AS term_end_date,
                tm.status AS term_status,
                (tm.status = 'active') AS term_is_current
            FROM timetable_slots ts
            LEFT JOIN class_subjects cs ON cs.id = ts.class_subject_id
            LEFT JOIN subjects sub ON sub.id = cs.subject_id
            {$classesJoin}
            JOIN terms tm ON tm.id = ts.term_id
            LEFT JOIN teachers t ON t.id = cs.teacher_id
            LEFT JOIN users u ON u.id = t.user_id
            WHERE {$whereClass}
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
     * Find all timetable slots for an entire term.
     *
     * @return array<int, TimetableSlot>
     */
    public function findByTerm(int $termId): array
    {
        $classIdCol = $this->hasTsClassId ? "COALESCE(ts.class_id, cs.class_id) AS class_id" : "cs.class_id AS class_id";
        $classesJoin = $this->hasTsClassId ? "LEFT JOIN classes c ON c.id = COALESCE(ts.class_id, cs.class_id)" : "JOIN classes c ON c.id = cs.class_id";
        $subjectNameCol = $this->hasTsClassId ? "COALESCE(sub.name, ts.custom_title, 'General Activity') AS subject_name" : "sub.name AS subject_name";
        $subjectCodeCol = $this->hasTsClassId ? "COALESCE(sub.code, ts.custom_title, '') AS subject_code" : "sub.code AS subject_code";
        $orderClassCol = $this->hasTsClassId ? "COALESCE(ts.class_id, cs.class_id)" : "cs.class_id";

        $sql = "
            SELECT 
                ts.*,
                cs.session_id,
                {$classIdCol},
                cs.subject_id,
                cs.teacher_id,
                cs.status AS class_subject_status,
                {$subjectNameCol},
                {$subjectCodeCol},
                c.name AS class_name,
                c.section_arm,
                u.name AS teacher_name,
                t.staff_id AS teacher_staff_id,
                tm.name AS term_name,
                tm.session_id AS term_session_id,
                tm.start_date AS term_start_date,
                tm.end_date AS term_end_date,
                tm.status AS term_status,
                (tm.status = 'active') AS term_is_current
            FROM timetable_slots ts
            LEFT JOIN class_subjects cs ON cs.id = ts.class_subject_id
            LEFT JOIN subjects sub ON sub.id = cs.subject_id
            {$classesJoin}
            JOIN terms tm ON tm.id = ts.term_id
            LEFT JOIN teachers t ON t.id = cs.teacher_id
            LEFT JOIN users u ON u.id = t.user_id
            WHERE ts.term_id = :term_id
            ORDER BY 
                {$orderClassCol} ASC,
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
        $stmt->execute([':term_id' => $termId]);

        $slots = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $slots[] = TimetableSlot::fromArray($row);
        }
        return $slots;
    }

    /**
     * Find all timetable slots for a teacher in a specific term (or all terms if null).
     *
     * @return array<int, TimetableSlot>
     */
    public function findByTeacher(int $teacherId, ?int $termId = null): array
    {
        $termClause = $termId !== null ? "AND ts.term_id = :term_id" : "";
        $classIdCol = $this->hasTsClassId ? "COALESCE(ts.class_id, cs.class_id) AS class_id" : "cs.class_id AS class_id";
        $classesJoin = $this->hasTsClassId ? "LEFT JOIN classes c ON c.id = COALESCE(ts.class_id, cs.class_id)" : "JOIN classes c ON c.id = cs.class_id";
        $subjectNameCol = $this->hasTsClassId ? "COALESCE(sub.name, ts.custom_title, 'General Activity') AS subject_name" : "sub.name AS subject_name";
        $subjectCodeCol = $this->hasTsClassId ? "COALESCE(sub.code, ts.custom_title, '') AS subject_code" : "sub.code AS subject_code";

        $sql = "
            SELECT 
                ts.*,
                cs.session_id,
                {$classIdCol},
                cs.subject_id,
                cs.teacher_id,
                cs.status AS class_subject_status,
                {$subjectNameCol},
                {$subjectCodeCol},
                c.name AS class_name,
                c.section_arm,
                u.name AS teacher_name,
                t.staff_id AS teacher_staff_id,
                tm.name AS term_name,
                tm.session_id AS term_session_id,
                tm.start_date AS term_start_date,
                tm.end_date AS term_end_date,
                tm.status AS term_status,
                (tm.status = 'active') AS term_is_current
            FROM timetable_slots ts
            JOIN class_subjects cs ON cs.id = ts.class_subject_id
            LEFT JOIN subjects sub ON sub.id = cs.subject_id
            {$classesJoin}
            JOIN terms tm ON tm.id = ts.term_id
            LEFT JOIN teachers t ON t.id = cs.teacher_id
            LEFT JOIN users u ON u.id = t.user_id
            WHERE cs.teacher_id = :teacher_id
              {$termClause}
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

        $params = [':teacher_id' => $teacherId];
        if ($termId !== null) {
            $params[':term_id'] = $termId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

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
        $classIdCol = $this->hasTsClassId ? "COALESCE(ts.class_id, cs.class_id) AS class_id" : "cs.class_id AS class_id";
        $classesJoin = $this->hasTsClassId ? "LEFT JOIN classes c ON c.id = COALESCE(ts.class_id, cs.class_id)" : "JOIN classes c ON c.id = cs.class_id";
        $subjectNameCol = $this->hasTsClassId ? "COALESCE(sub.name, ts.custom_title, 'General Activity') AS subject_name" : "sub.name AS subject_name";
        $subjectCodeCol = $this->hasTsClassId ? "COALESCE(sub.code, ts.custom_title, '') AS subject_code" : "sub.code AS subject_code";

        $sql = "
            SELECT 
                ts.*,
                cs.session_id,
                {$classIdCol},
                cs.subject_id,
                cs.teacher_id,
                cs.status AS class_subject_status,
                {$subjectNameCol},
                {$subjectCodeCol},
                c.name AS class_name,
                c.section_arm,
                u.name AS teacher_name,
                t.staff_id AS teacher_staff_id,
                tm.name AS term_name,
                tm.session_id AS term_session_id,
                tm.start_date AS term_start_date,
                tm.end_date AS term_end_date,
                tm.status AS term_status,
                (tm.status = 'active') AS term_is_current
            FROM timetable_slots ts
            JOIN class_subjects cs ON cs.id = ts.class_subject_id
            LEFT JOIN subjects sub ON sub.id = cs.subject_id
            {$classesJoin}
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
     * - 'class': Same class cohort scheduled at the same time in another slot (exempt when departmental splits differ)
     * - 'room': Same physical room scheduled at the same time in another slot
     *
     * @return array<int, array{slot: TimetableSlot, type: string, message: string}>
     */
    public function findConflicts(
        int $termId,
        string $dayOfWeek,
        string $startTime,
        string $endTime,
        ?int $classSubjectId = null,
        ?string $room = null,
        ?int $excludeSlotId = null,
        ?int $classId = null,
        ?string $department = null
    ): array {
        $startTime = self::normalizeTime($startTime);
        $endTime = self::normalizeTime($endTime);
        $dayOfWeek = strtolower(trim($dayOfWeek));
        $room = $room !== null && trim($room) !== '' ? trim($room) : null;
        $department = $department !== null && trim($department) !== '' ? strtolower(trim($department)) : null;

        $targetClassId = $classId;
        $targetTeacherId = 0;

        if ($classSubjectId !== null && $classSubjectId > 0) {
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

            if ($targetCs) {
                if ($targetClassId === null) {
                    $targetClassId = (int)$targetCs['class_id'];
                }
                $targetTeacherId = (int)($targetCs['teacher_id'] ?? 0);
            }
        }

        $classIdCol = $this->hasTsClassId ? "COALESCE(ts.class_id, cs.class_id) AS class_id" : "cs.class_id AS class_id";
        $classesJoin = $this->hasTsClassId ? "LEFT JOIN classes c ON c.id = COALESCE(ts.class_id, cs.class_id)" : "JOIN classes c ON c.id = cs.class_id";
        $subjectNameCol = $this->hasTsClassId ? "COALESCE(sub.name, ts.custom_title, 'General Activity') AS subject_name" : "sub.name AS subject_name";
        $subjectCodeCol = $this->hasTsClassId ? "COALESCE(sub.code, ts.custom_title, '') AS subject_code" : "sub.code AS subject_code";

        // Fetch candidate overlapping slots in the same term and day
        $sql = "
            SELECT 
                ts.*,
                cs.session_id,
                {$classIdCol},
                cs.subject_id,
                cs.teacher_id,
                cs.status AS class_subject_status,
                {$subjectNameCol},
                {$subjectCodeCol},
                c.name AS class_name,
                c.section_arm,
                u.name AS teacher_name,
                t.staff_id AS teacher_staff_id,
                tm.name AS term_name,
                tm.session_id AS term_session_id,
                tm.start_date AS term_start_date,
                tm.end_date AS term_end_date,
                tm.status AS term_status,
                (tm.status = 'active') AS term_is_current
            FROM timetable_slots ts
            LEFT JOIN class_subjects cs ON cs.id = ts.class_subject_id
            LEFT JOIN subjects sub ON sub.id = cs.subject_id
            {$classesJoin}
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
            $existingClassId = (int)($row['class_id'] ?? 0);
            $existingTeacherId = (int)($row['teacher_id'] ?? 0);
            $existingRoom = isset($row['room']) && trim((string)$row['room']) !== '' ? trim((string)$row['room']) : null;
            $existingDept = isset($row['department']) && trim((string)$row['department']) !== '' ? strtolower(trim((string)$row['department'])) : null;

            // 1. Class conflict check
            if ($targetClassId !== null && $existingClassId > 0 && $existingClassId === $targetClassId) {
                // If both slots specify distinct departments (e.g. Science vs Commercial in SS 1), it is a valid elective split!
                $isAllowedSplit = ($department !== null && $existingDept !== null && $department !== $existingDept);

                if (!$isAllowedSplit) {
                    $conflicts[] = [
                        'slot' => $existingSlot,
                        'type' => 'class',
                        'message' => "Class conflict: {$row['class_name']} already has '{$row['subject_name']}' scheduled from {$existingSlot->getFormattedTimeRange()}.",
                    ];
                }
            }

            // 2. Teacher conflict check
            if ($targetTeacherId > 0 && $existingTeacherId > 0 && $targetTeacherId === $existingTeacherId) {
                $conflicts[] = [
                    'slot' => $existingSlot,
                    'type' => 'teacher',
                    'message' => "Teacher conflict: Instructor {$row['teacher_name']} is already scheduled to teach '{$row['subject_name']}' in {$row['class_name']} from {$existingSlot->getFormattedTimeRange()}.",
                ];
            }

            // 3. Room conflict check
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

    /**
     * Delete all timetable slots for a class in a specific term.
     */
    public function deleteByClassAndTerm(int $classId, int $termId): int
    {
        if ($this->hasTsClassId) {
            $sql = "
                DELETE FROM timetable_slots
                WHERE term_id = :term_id
                  AND (class_id = :class_id OR class_subject_id IN (SELECT id FROM class_subjects WHERE class_id = :class_id_cs))
            ";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':class_id' => $classId,
                ':class_id_cs' => $classId,
                ':term_id' => $termId,
            ]);
            return $stmt->rowCount();
        }

        $sql = "
            DELETE FROM timetable_slots
            WHERE term_id = :term_id
              AND class_subject_id IN (SELECT id FROM class_subjects WHERE class_id = :class_id)
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':class_id' => $classId,
            ':term_id' => $termId,
        ]);
        return $stmt->rowCount();
    }

    /**
     * Batch insert multiple timetable slots.
     *
     * @param array<int, array{term_id: int, class_id?: ?int, class_subject_id?: ?int, structure_period_id?: ?int, department?: ?string, custom_title?: ?string, slot_type?: ?string, day_of_week: string, start_time: string, end_time: string, room: ?string}> $slotsData
     * @return int Number of inserted rows
     */
    public function batchCreate(array $slotsData): int
    {
        if (empty($slotsData)) {
            return 0;
        }

        if ($this->hasTsClassId) {
            $sql = "INSERT INTO timetable_slots (term_id, class_id, class_subject_id, structure_period_id, department, custom_title, slot_type, day_of_week, start_time, end_time, room, created_at, updated_at) VALUES ";
            $placeholders = [];
            $values = [];

            foreach ($slotsData as $i => $row) {
                $placeholders[] = "(:term_{$i}, :cid_{$i}, :cs_{$i}, :sp_{$i}, :dept_{$i}, :title_{$i}, :stype_{$i}, :day_{$i}, :start_{$i}, :end_{$i}, :room_{$i}, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)";
                $values[":term_{$i}"] = (int)$row['term_id'];
                $values[":cid_{$i}"] = !empty($row['class_id']) ? (int)$row['class_id'] : null;
                $values[":cs_{$i}"] = !empty($row['class_subject_id']) ? (int)$row['class_subject_id'] : null;
                $values[":sp_{$i}"] = !empty($row['structure_period_id']) ? (int)$row['structure_period_id'] : null;
                $values[":dept_{$i}"] = !empty($row['department']) ? trim((string)$row['department']) : null;
                $values[":title_{$i}"] = !empty($row['custom_title']) ? trim((string)$row['custom_title']) : null;
                $values[":stype_{$i}"] = !empty($row['slot_type']) ? trim((string)$row['slot_type']) : 'instructional';
                $values[":day_{$i}"] = strtolower(trim((string)$row['day_of_week']));
                $values[":start_{$i}"] = self::normalizeTime((string)$row['start_time']);
                $values[":end_{$i}"] = self::normalizeTime((string)$row['end_time']);
                $values[":room_{$i}"] = !empty($row['room']) ? trim((string)$row['room']) : null;
            }

            $sql .= implode(', ', $placeholders);
            $stmt = $this->db->prepare($sql);
            foreach ($values as $key => $val) {
                if ($val === null) {
                    $stmt->bindValue($key, null, PDO::PARAM_NULL);
                } elseif (is_int($val)) {
                    $stmt->bindValue($key, $val, PDO::PARAM_INT);
                } else {
                    $stmt->bindValue($key, $val, PDO::PARAM_STR);
                }
            }

            $stmt->execute();
            return count($slotsData);
        }

        // Fallback for legacy SQLite unit tests schemas
        $sql = "INSERT INTO timetable_slots (term_id, class_subject_id, day_of_week, start_time, end_time, room, created_at, updated_at) VALUES ";
        $placeholders = [];
        $values = [];

        foreach ($slotsData as $i => $row) {
            $placeholders[] = "(:term_{$i}, :cs_{$i}, :day_{$i}, :start_{$i}, :end_{$i}, :room_{$i}, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)";
            $values[":term_{$i}"] = (int)$row['term_id'];
            $values[":cs_{$i}"] = (int)($row['class_subject_id'] ?? 0);
            $values[":day_{$i}"] = strtolower(trim((string)$row['day_of_week']));
            $values[":start_{$i}"] = self::normalizeTime((string)$row['start_time']);
            $values[":end_{$i}"] = self::normalizeTime((string)$row['end_time']);
            $values[":room_{$i}"] = !empty($row['room']) ? trim((string)$row['room']) : null;
        }

        $sql .= implode(', ', $placeholders);
        $stmt = $this->db->prepare($sql);
        foreach ($values as $key => $val) {
            if ($val === null) {
                $stmt->bindValue($key, null, PDO::PARAM_NULL);
            } elseif (is_int($val)) {
                $stmt->bindValue($key, $val, PDO::PARAM_INT);
            } else {
                $stmt->bindValue($key, $val, PDO::PARAM_STR);
            }
        }

        $stmt->execute();
        return count($slotsData);
    }
}

