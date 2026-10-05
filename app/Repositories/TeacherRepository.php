<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Models\Teacher;
use App\Models\User;
use PDO;

/**
 * Data Access Layer for Teachers
 */
class TeacherRepository
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::getInstance();
    }

    public function getPdo(): PDO
    {
        return $this->pdo;
    }

    public function findTeacherById(int $id): ?Teacher
    {
        $stmt = $this->pdo->prepare(
            'SELECT t.*, u.uuid, u.name as user_name, u.email as user_email, u.phone, u.status as user_status,
                    u.must_change_password, u.created_at as user_created_at, u.updated_at as user_updated_at
             FROM `teachers` t
             JOIN `users` u ON u.id = t.user_id
             WHERE t.id = :id
             LIMIT 1'
        );
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? Teacher::fromArray($row) : null;
    }

    public function findTeacherByUserId(int $userId): ?Teacher
    {
        $stmt = $this->pdo->prepare(
            'SELECT t.*, u.uuid, u.name as user_name, u.email as user_email, u.phone, u.status as user_status,
                    u.must_change_password, u.created_at as user_created_at, u.updated_at as user_updated_at
             FROM `teachers` t
             JOIN `users` u ON u.id = t.user_id
             WHERE t.user_id = :user_id
             LIMIT 1'
        );
        $stmt->execute([':user_id' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? Teacher::fromArray($row) : null;
    }

    public function findByUserId(int $userId): ?Teacher
    {
        return $this->findTeacherByUserId($userId);
    }

    /**
     * Get teaching allocations for a teacher in a given academic session.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getTeachingAllocations(int $teacherId, int $sessionId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT cs.id as class_subject_id, cs.class_id, cs.subject_id, cs.session_id,
                    c.name as class_name, c.section_arm, al.name as academic_level_name,
                    s.name as subject_name, s.code as subject_code
             FROM `class_subjects` cs
             JOIN `classes` c ON c.id = cs.class_id
             LEFT JOIN `academic_levels` al ON al.id = c.academic_level_id
             JOIN `subjects` s ON s.id = cs.subject_id
             WHERE cs.teacher_id = :teacher_id AND cs.session_id = :session_id
             ORDER BY c.name ASC, s.name ASC'
        );
        $stmt->execute([
            ':teacher_id' => $teacherId,
            ':session_id' => $sessionId,
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findTeacherByStaffId(string $staffId): ?Teacher
    {
        $stmt = $this->pdo->prepare(
            'SELECT t.*, u.uuid, u.name as user_name, u.email as user_email, u.phone, u.status as user_status,
                    u.must_change_password, u.created_at as user_created_at, u.updated_at as user_updated_at
             FROM `teachers` t
             JOIN `users` u ON u.id = t.user_id
             WHERE LOWER(t.staff_id) = LOWER(:staff_id)
             LIMIT 1'
        );
        $stmt->execute([':staff_id' => trim($staffId)]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? Teacher::fromArray($row) : null;
    }

    /**
     * @return Teacher[]
     */
    public function getAllTeachers(): array
    {
        $stmt = $this->pdo->query(
            'SELECT t.*, u.uuid, u.name as user_name, u.email as user_email, u.phone, u.status as user_status,
                    u.must_change_password, u.created_at as user_created_at, u.updated_at as user_updated_at
             FROM `teachers` t
             JOIN `users` u ON u.id = t.user_id
             ORDER BY u.name ASC'
        );
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(fn(array $row) => Teacher::fromArray($row), $rows);
    }

    /**
     * Get all teachers with form classes, subject count, and allocations stats.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getAllTeachersWithStats(?string $search = null, ?string $filter = null): array
    {
        $where = [];
        $params = [];

        if (!empty($search)) {
            $where[] = '(u.name LIKE :search_name OR u.email LIKE :search_email OR t.staff_id LIKE :search_staff)';
            $params[':search_name'] = '%' . $search . '%';
            $params[':search_email'] = '%' . $search . '%';
            $params[':search_staff'] = '%' . $search . '%';
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $sql = "SELECT t.*, u.name as user_name, u.email as user_email, u.phone as user_phone, u.status as user_status,
                       u.created_at as user_created_at,
                       (SELECT COUNT(*) FROM `class_subjects` cs WHERE cs.teacher_id = t.id) as subjects_count,
                       (SELECT GROUP_CONCAT(c.name SEPARATOR ', ') FROM `classes` c WHERE c.form_teacher_id = t.id) as form_classes
                FROM `teachers` t
                JOIN `users` u ON u.id = t.user_id
                {$whereClause}
                ORDER BY u.name ASC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        if ($filter === 'form_teachers') {
            $rows = array_filter($rows, fn($r) => !empty($r['form_classes']));
        } elseif ($filter === 'subject_teachers') {
            $rows = array_filter($rows, fn($r) => (int)$r['subjects_count'] > 0);
        }

        return array_values($rows);
    }

    /**
     * Get classes where this teacher is assigned as Form Teacher (Class Master).
     *
     * @return array<int, array<string, mixed>>
     */
    public function getFormClasses(int $teacherId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT c.*, al.name as level_name, al.stage as stage_name,
                    (SELECT COUNT(*) FROM `students` s WHERE s.current_class_id = c.id) as student_count
             FROM `classes` c
             LEFT JOIN `academic_levels` al ON al.id = c.academic_level_id
             WHERE c.form_teacher_id = :teacher_id
             ORDER BY c.name ASC'
        );
        $stmt->execute([':teacher_id' => $teacherId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Get all teaching allocations for a teacher across all sessions or specific session.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getAllocations(int $teacherId, ?int $sessionId = null): array
    {
        $sql = 'SELECT cs.id as class_subject_id, cs.class_id, cs.subject_id, cs.session_id, cs.status,
                       c.name as class_name, c.section_arm, al.name as academic_level_name, al.stage as stage_name,
                       s.name as subject_name, s.code as subject_code,
                       (SELECT COUNT(*) FROM `student_subject_enrollments` sse WHERE sse.class_subject_id = cs.id AND sse.status = \'active\') as student_count
                FROM `class_subjects` cs
                JOIN `classes` c ON c.id = cs.class_id
                LEFT JOIN `academic_levels` al ON al.id = c.academic_level_id
                JOIN `subjects` s ON s.id = cs.subject_id
                WHERE cs.teacher_id = :teacher_id';

        $params = [':teacher_id' => $teacherId];
        if ($sessionId !== null && $sessionId > 0) {
            $sql .= ' AND cs.session_id = :session_id';
            $params[':session_id'] = $sessionId;
        }

        $sql .= ' ORDER BY c.name ASC, s.name ASC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function createTeacher(int $userId, string $staffId): Teacher
    {
        $now = date('Y-m-d H:i:s');
        $stmt = $this->pdo->prepare(
            'INSERT INTO `teachers` (`user_id`, `staff_id`, `created_at`, `updated_at`)
             VALUES (:user_id, :staff_id, :created_at, :updated_at)'
        );

        $stmt->execute([
            ':user_id' => $userId,
            ':staff_id' => trim($staffId),
            ':created_at' => $now,
            ':updated_at' => $now,
        ]);

        $id = (int)$this->pdo->lastInsertId();

        return $this->findTeacherById($id);
    }

    public function updateTeacherStaffId(int $id, string $staffId): bool
    {
        $now = date('Y-m-d H:i:s');
        $stmt = $this->pdo->prepare(
            'UPDATE `teachers` SET `staff_id` = :staff_id, `updated_at` = :updated_at WHERE `id` = :id'
        );

        return $stmt->execute([
            ':id' => $id,
            ':staff_id' => trim($staffId),
            ':updated_at' => $now,
        ]);
    }

    /**
     * Generate a unique teacher staff ID that does not clash with existing records.
     */
    public function generateStaffId(): string
    {
        $stmt = $this->pdo->query("SELECT staff_id FROM `teachers` WHERE `staff_id` LIKE 'TCH-%' ORDER BY `id` DESC");
        $maxNum = 0;
        $existing = [];

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $staffId = trim((string)$row['staff_id']);
            $existing[strtolower($staffId)] = true;
            if (preg_match('/^TCH-(\d+)$/i', $staffId, $matches)) {
                $val = (int)$matches[1];
                if ($val > $maxNum) {
                    $maxNum = $val;
                }
            }
        }

        $candidateNum = $maxNum + 1;
        do {
            $candidate = sprintf('TCH-%04d', $candidateNum);
            $candidateNum++;
        } while (isset($existing[strtolower($candidate)]));

        return $candidate;
    }
}
