<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Models\LiveClass;
use App\Models\LiveClassAttendee;
use PDO;

class LiveClassRepository
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::getInstance();
    }

    /**
     * Find a live class by ID with class, subject, and teacher details.
     */
    public function findById(int $id, ?int $studentId = null): ?LiveClass
    {
        $hasJoinedSelect = $studentId !== null
            ? '(SELECT COUNT(1) FROM live_class_attendees lca WHERE lca.live_class_id = lc.id AND lca.student_id = :student_id) > 0 AS has_joined,'
            : '0 AS has_joined,';

        $sql = "SELECT 
                    lc.*,
                    c.name AS class_name,
                    c.section_arm,
                    sub.name AS subject_name,
                    u.name AS teacher_name,
                    ses.name AS session_name,
                    tm.name AS term_name,
                    {$hasJoinedSelect}
                    (SELECT COUNT(1) FROM live_class_attendees lca2 WHERE lca2.live_class_id = lc.id) AS attendees_count
                FROM live_classes lc
                JOIN class_subjects cs ON cs.id = lc.class_subject_id
                JOIN classes c ON c.id = cs.class_id
                JOIN subjects sub ON sub.id = cs.subject_id
                JOIN teachers t ON t.id = lc.teacher_id
                JOIN users u ON u.id = t.user_id
                LEFT JOIN sessions ses ON ses.id = lc.session_id
                LEFT JOIN terms tm ON tm.id = lc.term_id
                WHERE lc.id = :id
                LIMIT 1";

        $stmt = $this->pdo->prepare($sql);
        $params = [':id' => $id];
        if ($studentId !== null) {
            $params[':student_id'] = $studentId;
        }

        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        if (!empty($row['section_arm'])) {
            $row['class_name'] .= ' (' . $row['section_arm'] . ')';
        }

        return LiveClass::fromArray($row);
    }

    /**
     * Get live classes created by a teacher.
     *
     * @return LiveClass[]
     */
    public function getByTeacher(int $teacherId, ?int $sessionId = null, ?int $termId = null): array
    {
        $where = ['lc.teacher_id = :teacher_id'];
        $params = [':teacher_id' => $teacherId];

        if ($sessionId !== null) {
            $where[] = 'lc.session_id = :session_id';
            $params[':session_id'] = $sessionId;
        }

        if ($termId !== null) {
            $where[] = 'lc.term_id = :term_id';
            $params[':term_id'] = $termId;
        }

        $whereClause = implode(' AND ', $where);

        $sql = "SELECT 
                    lc.*,
                    c.name AS class_name,
                    c.section_arm,
                    sub.name AS subject_name,
                    u.name AS teacher_name,
                    ses.name AS session_name,
                    tm.name AS term_name,
                    0 AS has_joined,
                    (SELECT COUNT(1) FROM live_class_attendees lca WHERE lca.live_class_id = lc.id) AS attendees_count
                FROM live_classes lc
                JOIN class_subjects cs ON cs.id = lc.class_subject_id
                JOIN classes c ON c.id = cs.class_id
                JOIN subjects sub ON sub.id = cs.subject_id
                JOIN teachers t ON t.id = lc.teacher_id
                JOIN users u ON u.id = t.user_id
                LEFT JOIN sessions ses ON ses.id = lc.session_id
                LEFT JOIN terms tm ON tm.id = lc.term_id
                WHERE {$whereClause}
                ORDER BY lc.scheduled_date DESC, lc.start_time DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        $results = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if (!empty($row['section_arm'])) {
                $row['class_name'] .= ' (' . $row['section_arm'] . ')';
            }
            $results[] = LiveClass::fromArray($row);
        }

        return $results;
    }

    /**
     * Get live classes for a specific class subject.
     *
     * @return LiveClass[]
     */
    public function getByClassSubject(int $classSubjectId): array
    {
        $sql = "SELECT 
                    lc.*,
                    c.name AS class_name,
                    c.section_arm,
                    sub.name AS subject_name,
                    u.name AS teacher_name,
                    ses.name AS session_name,
                    tm.name AS term_name,
                    0 AS has_joined,
                    (SELECT COUNT(1) FROM live_class_attendees lca WHERE lca.live_class_id = lc.id) AS attendees_count
                FROM live_classes lc
                JOIN class_subjects cs ON cs.id = lc.class_subject_id
                JOIN classes c ON c.id = cs.class_id
                JOIN subjects sub ON sub.id = cs.subject_id
                JOIN teachers t ON t.id = lc.teacher_id
                JOIN users u ON u.id = t.user_id
                LEFT JOIN sessions ses ON ses.id = lc.session_id
                LEFT JOIN terms tm ON tm.id = lc.term_id
                WHERE lc.class_subject_id = :class_subject_id
                ORDER BY lc.scheduled_date DESC, lc.start_time DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':class_subject_id' => $classSubjectId]);

        $results = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if (!empty($row['section_arm'])) {
                $row['class_name'] .= ' (' . $row['section_arm'] . ')';
            }
            $results[] = LiveClass::fromArray($row);
        }

        return $results;
    }

    /**
     * Get upcoming and active live classes for a student.
     *
     * @return LiveClass[]
     */
    public function getUpcomingForStudent(int $studentId, int $limit = 5): array
    {
        $today = date('Y-m-d');
        $sql = "SELECT 
                    lc.*,
                    c.name AS class_name,
                    c.section_arm,
                    sub.name AS subject_name,
                    u.name AS teacher_name,
                    ses.name AS session_name,
                    tm.name AS term_name,
                    (SELECT COUNT(1) FROM live_class_attendees lca WHERE lca.live_class_id = lc.id AND lca.student_id = :student_id) > 0 AS has_joined,
                    (SELECT COUNT(1) FROM live_class_attendees lca2 WHERE lca2.live_class_id = lc.id) AS attendees_count
                FROM live_classes lc
                JOIN class_subjects cs ON cs.id = lc.class_subject_id
                JOIN classes c ON c.id = cs.class_id
                JOIN subjects sub ON sub.id = cs.subject_id
                JOIN teachers t ON t.id = lc.teacher_id
                JOIN users u ON u.id = t.user_id
                LEFT JOIN sessions ses ON ses.id = lc.session_id
                LEFT JOIN terms tm ON tm.id = lc.term_id
                WHERE lc.is_published = 1
                  AND lc.status IN ('scheduled', 'in_progress')
                  AND lc.scheduled_date >= :today
                  AND (
                      cs.class_id = (SELECT current_class_id FROM students WHERE id = :student_id_class)
                      OR cs.class_id IN (SELECT class_id FROM class_enrollments WHERE student_id = :student_id_enroll AND status = 'active')
                  )
                ORDER BY lc.scheduled_date ASC, lc.start_time ASC
                LIMIT :limit";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':student_id', $studentId, PDO::PARAM_INT);
        $stmt->bindValue(':student_id_class', $studentId, PDO::PARAM_INT);
        $stmt->bindValue(':student_id_enroll', $studentId, PDO::PARAM_INT);
        $stmt->bindValue(':today', $today, PDO::PARAM_STR);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        $results = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if (!empty($row['section_arm'])) {
                $row['class_name'] .= ' (' . $row['section_arm'] . ')';
            }
            $results[] = LiveClass::fromArray($row);
        }

        return $results;
    }

    /**
     * Get all live classes accessible by a student.
     *
     * @return LiveClass[]
     */
    public function getAllForStudent(int $studentId, ?int $sessionId = null, ?int $termId = null): array
    {
        $where = [
            'lc.is_published = 1',
            '(cs.class_id = (SELECT current_class_id FROM students WHERE id = :student_id_class) OR cs.class_id IN (SELECT class_id FROM class_enrollments WHERE student_id = :student_id_enroll AND status = \'active\'))'
        ];
        $params = [
            ':student_id' => $studentId,
            ':student_id_class' => $studentId,
            ':student_id_enroll' => $studentId,
        ];

        if ($sessionId !== null) {
            $where[] = 'lc.session_id = :session_id';
            $params[':session_id'] = $sessionId;
        }

        if ($termId !== null) {
            $where[] = 'lc.term_id = :term_id';
            $params[':term_id'] = $termId;
        }

        $whereClause = implode(' AND ', $where);

        $sql = "SELECT 
                    lc.*,
                    c.name AS class_name,
                    c.section_arm,
                    sub.name AS subject_name,
                    u.name AS teacher_name,
                    ses.name AS session_name,
                    tm.name AS term_name,
                    (SELECT COUNT(1) FROM live_class_attendees lca WHERE lca.live_class_id = lc.id AND lca.student_id = :student_id) > 0 AS has_joined,
                    (SELECT COUNT(1) FROM live_class_attendees lca2 WHERE lca2.live_class_id = lc.id) AS attendees_count
                FROM live_classes lc
                JOIN class_subjects cs ON cs.id = lc.class_subject_id
                JOIN classes c ON c.id = cs.class_id
                JOIN subjects sub ON sub.id = cs.subject_id
                JOIN teachers t ON t.id = lc.teacher_id
                JOIN users u ON u.id = t.user_id
                LEFT JOIN sessions ses ON ses.id = lc.session_id
                LEFT JOIN terms tm ON tm.id = lc.term_id
                WHERE {$whereClause}
                ORDER BY lc.scheduled_date DESC, lc.start_time DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        $results = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if (!empty($row['section_arm'])) {
                $row['class_name'] .= ' (' . $row['section_arm'] . ')';
            }
            $results[] = LiveClass::fromArray($row);
        }

        return $results;
    }

    /**
     * Get all live classes for students associated with a parent.
     *
     * @return array<int, array{student: array, live_classes: LiveClass[]}>
     */
    public function getAllForParent(int $parentId, ?int $sessionId = null, ?int $termId = null): array
    {
        // Find linked students
        $sql = "SELECT s.id, s.admission_number, u.name as student_name, c.name as class_name
                FROM parent_student ps
                JOIN students s ON s.id = ps.student_id
                JOIN users u ON u.id = s.user_id
                LEFT JOIN classes c ON c.id = s.current_class_id
                WHERE ps.parent_id = :parent_id";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':parent_id' => $parentId]);
        $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $data = [];
        foreach ($students as $stu) {
            $stuId = (int)$stu['id'];
            $classes = $this->getAllForStudent($stuId, $sessionId, $termId);
            $data[] = [
                'student' => $stu,
                'live_classes' => $classes,
            ];
        }

        return $data;
    }

    /**
     * Admin view: Institutional list of live classes with filters.
     *
     * @return LiveClass[]
     */
    public function getAllForAdmin(
        ?int $sessionId = null,
        ?int $termId = null,
        ?string $status = null,
        int $limit = 50,
        int $offset = 0
    ): array {
        $where = ['1=1'];
        $params = [];

        if ($sessionId !== null) {
            $where[] = 'lc.session_id = :session_id';
            $params[':session_id'] = $sessionId;
        }

        if ($termId !== null) {
            $where[] = 'lc.term_id = :term_id';
            $params[':term_id'] = $termId;
        }

        if ($status !== null && $status !== '') {
            $where[] = 'lc.status = :status';
            $params[':status'] = $status;
        }

        $whereClause = implode(' AND ', $where);

        $sql = "SELECT 
                    lc.*,
                    c.name AS class_name,
                    c.section_arm,
                    sub.name AS subject_name,
                    u.name AS teacher_name,
                    ses.name AS session_name,
                    tm.name AS term_name,
                    0 AS has_joined,
                    (SELECT COUNT(1) FROM live_class_attendees lca WHERE lca.live_class_id = lc.id) AS attendees_count
                FROM live_classes lc
                JOIN class_subjects cs ON cs.id = lc.class_subject_id
                JOIN classes c ON c.id = cs.class_id
                JOIN subjects sub ON sub.id = cs.subject_id
                JOIN teachers t ON t.id = lc.teacher_id
                JOIN users u ON u.id = t.user_id
                LEFT JOIN sessions ses ON ses.id = lc.session_id
                LEFT JOIN terms tm ON tm.id = lc.term_id
                WHERE {$whereClause}
                ORDER BY lc.scheduled_date DESC, lc.start_time DESC
                LIMIT :limit OFFSET :offset";

        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $results = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if (!empty($row['section_arm'])) {
                $row['class_name'] .= ' (' . $row['section_arm'] . ')';
            }
            $results[] = LiveClass::fromArray($row);
        }

        return $results;
    }

    /**
     * Count for admin pagination.
     */
    public function countAllForAdmin(?int $sessionId = null, ?int $termId = null, ?string $status = null): int
    {
        $where = ['1=1'];
        $params = [];

        if ($sessionId !== null) {
            $where[] = 'lc.session_id = :session_id';
            $params[':session_id'] = $sessionId;
        }

        if ($termId !== null) {
            $where[] = 'lc.term_id = :term_id';
            $params[':term_id'] = $termId;
        }

        if ($status !== null && $status !== '') {
            $where[] = 'lc.status = :status';
            $params[':status'] = $status;
        }

        $whereClause = implode(' AND ', $where);

        $sql = "SELECT COUNT(1) FROM live_classes lc WHERE {$whereClause}";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return (int)$stmt->fetchColumn();
    }

    /**
     * Create a new scheduled live class.
     */
    public function create(array $data): LiveClass
    {
        $sql = "INSERT INTO live_classes (
                    session_id, term_id, class_subject_id, teacher_id,
                    title, description, platform, meeting_link,
                    meeting_passcode, scheduled_date, start_time,
                    duration_minutes, status, is_published, created_at, updated_at
                ) VALUES (
                    :session_id, :term_id, :class_subject_id, :teacher_id,
                    :title, :description, :platform, :meeting_link,
                    :meeting_passcode, :scheduled_date, :start_time,
                    :duration_minutes, :status, :is_published, :created_at, :updated_at
                )";

        $now = date('Y-m-d H:i:s');
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':session_id' => $data['session_id'] ?? null,
            ':term_id' => $data['term_id'] ?? null,
            ':class_subject_id' => $data['class_subject_id'],
            ':teacher_id' => $data['teacher_id'],
            ':title' => $data['title'],
            ':description' => $data['description'] ?? null,
            ':platform' => $data['platform'] ?? LiveClass::PLATFORM_GOOGLE_MEET,
            ':meeting_link' => $data['meeting_link'],
            ':meeting_passcode' => $data['meeting_passcode'] ?? null,
            ':scheduled_date' => $data['scheduled_date'],
            ':start_time' => $data['start_time'],
            ':duration_minutes' => $data['duration_minutes'] ?? 40,
            ':status' => $data['status'] ?? LiveClass::STATUS_SCHEDULED,
            ':is_published' => isset($data['is_published']) ? (int)$data['is_published'] : 1,
            ':created_at' => $now,
            ':updated_at' => $now,
        ]);

        $id = (int)$this->pdo->lastInsertId();
        return $this->findById($id);
    }

    /**
     * Update an existing live class.
     */
    public function update(int $id, array $data): bool
    {
        $fields = [];
        $params = [':id' => $id, ':updated_at' => date('Y-m-d H:i:s')];

        $allowed = [
            'class_subject_id', 'title', 'description', 'platform',
            'meeting_link', 'meeting_passcode', 'scheduled_date',
            'start_time', 'duration_minutes', 'status', 'is_published',
            'session_id', 'term_id'
        ];

        foreach ($allowed as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = "`{$field}` = :{$field}";
                $params[":{$field}"] = $data[$field];
            }
        }

        if (empty($fields)) {
            return false;
        }

        $fields[] = "`updated_at` = :updated_at";
        $setClause = implode(', ', $fields);

        $sql = "UPDATE live_classes SET {$setClause} WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Update status helper.
     */
    public function updateStatus(int $id, string $status): bool
    {
        $sql = "UPDATE live_classes SET status = :status, updated_at = :updated_at WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':id' => $id,
            ':status' => $status,
            ':updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Delete a live class.
     */
    public function delete(int $id): bool
    {
        $sql = "DELETE FROM live_classes WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }

    /**
     * Record attendance when student clicks "Join Live Class" (SRS §31).
     */
    public function recordAttendance(
        int $liveClassId,
        int $studentId,
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): bool {
        $now = date('Y-m-d H:i:s');
        $cleanAgent = $userAgent ? substr($userAgent, 0, 500) : null;

        if ($this->hasStudentJoined($liveClassId, $studentId)) {
            $sql = "UPDATE live_class_attendees 
                    SET last_seen_at = :last_seen_at, ip_address = :ip_address, user_agent = :user_agent
                    WHERE live_class_id = :live_class_id AND student_id = :student_id";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([
                ':live_class_id' => $liveClassId,
                ':student_id' => $studentId,
                ':last_seen_at' => $now,
                ':ip_address' => $ipAddress,
                ':user_agent' => $cleanAgent,
            ]);
        }

        $sql = "INSERT INTO live_class_attendees (
                    live_class_id, student_id, joined_at, last_seen_at, ip_address, user_agent
                ) VALUES (
                    :live_class_id, :student_id, :joined_at, :last_seen_at, :ip_address, :user_agent
                )";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':live_class_id' => $liveClassId,
            ':student_id' => $studentId,
            ':joined_at' => $now,
            ':last_seen_at' => $now,
            ':ip_address' => $ipAddress,
            ':user_agent' => $cleanAgent,
        ]);
    }

    /**
     * Check if a student has joined a live class.
     */
    public function hasStudentJoined(int $liveClassId, int $studentId): bool
    {
        $stmt = $this->pdo->prepare("SELECT 1 FROM live_class_attendees WHERE live_class_id = :lc_id AND student_id = :student_id LIMIT 1");
        $stmt->execute([':lc_id' => $liveClassId, ':student_id' => $studentId]);
        return (bool)$stmt->fetchColumn();
    }

    /**
     * Get attendee roster for a live class.
     *
     * @return LiveClassAttendee[]
     */
    public function getAttendees(int $liveClassId): array
    {
        $sql = "SELECT 
                    lca.*,
                    u.name AS student_name,
                    s.admission_number,
                    c.name AS class_name
                FROM live_class_attendees lca
                JOIN students s ON s.id = lca.student_id
                JOIN users u ON u.id = s.user_id
                LEFT JOIN classes c ON c.id = s.current_class_id
                WHERE lca.live_class_id = :live_class_id
                ORDER BY lca.joined_at ASC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':live_class_id' => $liveClassId]);

        $attendees = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $attendees[] = LiveClassAttendee::fromArray($row);
        }

        return $attendees;
    }

    /**
     * Count total attendees.
     */
    public function countAttendees(int $liveClassId): int
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(1) FROM live_class_attendees WHERE live_class_id = :lc_id");
        $stmt->execute([':lc_id' => $liveClassId]);
        return (int)$stmt->fetchColumn();
    }
}
