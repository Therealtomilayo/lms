<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Models\ParentProfile;
use App\Models\Student;
use App\Models\User;
use PDO;

/**
 * Data Access Layer for Parent Profiles and Guardian-Student Links
 * Safely supports both contact-only parents (user_id IS NULL) and
 * activated portal user parents (user_id IS NOT NULL).
 */
class ParentRepository
{
    private PDO $pdo;
    private ?bool $hasContactColumns = null;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::getInstance();
    }

    /**
     * Check dynamically whether the parents table contains contact columns (name, phone, email).
     * Provides seamless compatibility across MySQL with migration 0037 and SQLite in-memory test databases.
     */
    private function hasContactColumns(): bool
    {
        if ($this->hasContactColumns !== null) {
            return $this->hasContactColumns;
        }

        try {
            $driver = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
            if ($driver === 'sqlite') {
                $stmt = $this->pdo->query("PRAGMA table_info(`parents`)");
                if ($stmt) {
                    $cols = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'name');
                    return $this->hasContactColumns = in_array('name', $cols, true);
                }
            } else {
                $stmt = $this->pdo->query("SHOW COLUMNS FROM `parents` LIKE 'name'");
                return $this->hasContactColumns = ($stmt && $stmt->fetch() !== false);
            }
        } catch (\Throwable) {
        }

        return $this->hasContactColumns = true;
    }

    private function getSelectFields(): string
    {
        if ($this->hasContactColumns()) {
            return "COALESCE(NULLIF(TRIM(u.name), ''), NULLIF(TRIM(p.name), '')) as user_name,
                    COALESCE(NULLIF(TRIM(u.email), ''), NULLIF(TRIM(p.email), '')) as user_email,
                    COALESCE(NULLIF(TRIM(u.phone), ''), NULLIF(TRIM(p.phone), '')) as user_phone,
                    u.status as user_status";
        }

        return "u.name as user_name,
                u.email as user_email,
                u.phone as user_phone,
                u.status as user_status";
    }

    private function getOrderByName(): string
    {
        if ($this->hasContactColumns()) {
            return "COALESCE(NULLIF(TRIM(u.name), ''), NULLIF(TRIM(p.name), ''))";
        }

        return "u.name";
    }

    public function findById(int $id): ?ParentProfile
    {
        $select = $this->getSelectFields();
        $sql = "SELECT p.*, {$select}
                FROM `parents` p
                LEFT JOIN `users` u ON u.id = p.user_id
                WHERE p.id = :id LIMIT 1";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        $students = $this->getLinkedStudents($id);

        return ParentProfile::fromArray($row, null, $students);
    }

    public function findByUserId(int $userId): ?ParentProfile
    {
        $select = $this->getSelectFields();
        $sql = "SELECT p.*, {$select}
                FROM `parents` p
                JOIN `users` u ON u.id = p.user_id
                WHERE p.user_id = :user_id LIMIT 1";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        $students = $this->getLinkedStudents((int)$row['id']);

        return ParentProfile::fromArray($row, null, $students);
    }

    public function create(int $userId): ParentProfile
    {
        $now = date('Y-m-d H:i:s');
        if ($this->hasContactColumns()) {
            $uStmt = $this->pdo->prepare('SELECT name, phone, email FROM `users` WHERE `id` = :id');
            $uStmt->execute([':id' => $userId]);
            $u = $uStmt->fetch(PDO::FETCH_ASSOC) ?: ['name' => 'Parent/Guardian', 'phone' => null, 'email' => null];

            $sql = 'INSERT INTO `parents` (`user_id`, `name`, `phone`, `email`, `created_at`, `updated_at`) 
                    VALUES (:user_id, :name, :phone, :email, :created_at, :updated_at)';

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':user_id' => $userId,
                ':name' => !empty($u['name']) ? trim($u['name']) : 'Parent/Guardian',
                ':phone' => !empty($u['phone']) ? trim($u['phone']) : null,
                ':email' => !empty($u['email']) ? strtolower(trim($u['email'])) : null,
                ':created_at' => $now,
                ':updated_at' => $now,
            ]);
        } else {
            $sql = 'INSERT INTO `parents` (`user_id`, `created_at`, `updated_at`) 
                    VALUES (:user_id, :created_at, :updated_at)';

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':user_id' => $userId,
                ':created_at' => $now,
                ':updated_at' => $now,
            ]);
        }

        $parentId = (int)$this->pdo->lastInsertId();

        return $this->findById($parentId);
    }

    public function createContactOnly(string $name, ?string $phone = null, ?string $email = null): ParentProfile
    {
        $now = date('Y-m-d H:i:s');
        $cleanName = trim($name) !== '' ? trim($name) : 'Parent/Guardian';
        $cleanPhone = !empty($phone) ? trim($phone) : null;
        $cleanEmail = !empty($email) ? strtolower(trim($email)) : null;

        if ($this->hasContactColumns()) {
            $sql = 'INSERT INTO `parents` (`user_id`, `name`, `phone`, `email`, `created_at`, `updated_at`) 
                    VALUES (NULL, :name, :phone, :email, :created_at, :updated_at)';

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':name' => $cleanName,
                ':phone' => $cleanPhone,
                ':email' => $cleanEmail,
                ':created_at' => $now,
                ':updated_at' => $now,
            ]);
        } else {
            $sql = 'INSERT INTO `parents` (`user_id`, `created_at`, `updated_at`) 
                    VALUES (NULL, :created_at, :updated_at)';

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':created_at' => $now,
                ':updated_at' => $now,
            ]);
        }

        $parentId = (int)$this->pdo->lastInsertId();

        return $this->findById($parentId);
    }

    /**
     * Find candidate parents by phone or email.
     *
     * @return ParentProfile[]
     */
    public function findCandidates(?string $phone, ?string $email = null): array
    {
        $cleanPhone = !empty($phone) ? trim($phone) : null;
        $cleanEmail = !empty($email) ? strtolower(trim($email)) : null;

        if ($cleanPhone === null && $cleanEmail === null) {
            return [];
        }

        $where = [];
        $params = [];

        if ($cleanPhone !== null) {
            if ($this->hasContactColumns()) {
                $where[] = "COALESCE(NULLIF(TRIM(u.phone), ''), NULLIF(TRIM(p.phone), '')) = :phone";
            } else {
                $where[] = "u.phone = :phone";
            }
            $params[':phone'] = $cleanPhone;
        }

        if ($cleanEmail !== null) {
            if ($this->hasContactColumns()) {
                $where[] = "LOWER(COALESCE(NULLIF(TRIM(u.email), ''), NULLIF(TRIM(p.email), ''))) = :email";
            } else {
                $where[] = "LOWER(u.email) = :email";
            }
            $params[':email'] = $cleanEmail;
        }

        if (empty($where)) {
            return [];
        }

        $select = $this->getSelectFields();
        $sql = "SELECT p.*, {$select}
                FROM `parents` p
                LEFT JOIN `users` u ON u.id = p.user_id
                WHERE " . implode(' OR ', $where) . "
                ORDER BY p.id ASC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $list = [];
        foreach ($rows as $row) {
            $students = $this->getLinkedStudents((int)$row['id']);
            $list[] = ParentProfile::fromArray($row, null, $students);
        }

        return $list;
    }

    public function linkUser(int $parentId, int $userId): bool
    {
        $now = date('Y-m-d H:i:s');
        $stmt = $this->pdo->prepare('UPDATE `parents` SET `user_id` = :user_id, `updated_at` = :updated_at WHERE `id` = :id');
        return $stmt->execute([
            ':user_id' => $userId,
            ':updated_at' => $now,
            ':id' => $parentId,
        ]);
    }

    /**
     * @return Student[]
     */
    public function getLinkedStudents(int $parentId): array
    {
        $sql = 'SELECT s.*, 
                       u.name as user_name, u.email as user_email, u.phone as user_phone, u.status as user_status,
                       c.name as class_name, c.section_arm, c.academic_level_id, c.status as class_status,
                       ps.relationship_type
                FROM `parent_student` ps
                JOIN `students` s ON s.id = ps.student_id
                JOIN `users` u ON u.id = s.user_id
                LEFT JOIN `classes` c ON c.id = s.current_class_id
                WHERE ps.parent_id = :parent_id
                ORDER BY u.name ASC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':parent_id' => $parentId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $students = [];
        foreach ($rows as $row) {
            $students[] = Student::fromArray($row);
        }

        return $students;
    }

    /**
     * @return ParentProfile[]
     */
    public function getGuardiansForStudent(int $studentId): array
    {
        $select = $this->getSelectFields();
        $order = $this->getOrderByName();
        $sql = "SELECT p.*, {$select}, ps.relationship_type
                FROM `parent_student` ps
                JOIN `parents` p ON p.id = ps.parent_id
                LEFT JOIN `users` u ON u.id = p.user_id
                WHERE ps.student_id = :student_id
                ORDER BY {$order} ASC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':student_id' => $studentId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $guardians = [];
        foreach ($rows as $row) {
            $guardians[] = ParentProfile::fromArray($row);
        }

        return $guardians;
    }

    public function isLinked(int $parentId, int $studentId): bool
    {
        $sql = 'SELECT 1 FROM `parent_student` WHERE `parent_id` = :parent_id AND `student_id` = :student_id LIMIT 1';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':parent_id' => $parentId,
            ':student_id' => $studentId,
        ]);

        return (bool)$stmt->fetchColumn();
    }

    public function isLinkedToStudent(int $parentId, int $studentId): bool
    {
        return $this->isLinked($parentId, $studentId);
    }

    public function linkStudent(int $parentId, int $studentId, ?string $relationshipType = null): bool
    {
        $now = date('Y-m-d H:i:s');
        if ($this->isLinked($parentId, $studentId)) {
            $sql = 'UPDATE `parent_student` SET `relationship_type` = :relationship_type WHERE `parent_id` = :parent_id AND `student_id` = :student_id';
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([
                ':parent_id' => $parentId,
                ':student_id' => $studentId,
                ':relationship_type' => $relationshipType ?: null,
            ]);
        }

        $sql = 'INSERT INTO `parent_student` (`parent_id`, `student_id`, `relationship_type`, `created_at`)
                VALUES (:parent_id, :student_id, :relationship_type, :created_at)';

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':parent_id' => $parentId,
            ':student_id' => $studentId,
            ':relationship_type' => $relationshipType ?: null,
            ':created_at' => $now,
        ]);
    }

    public function unlinkStudent(int $parentId, int $studentId): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM `parent_student` WHERE `parent_id` = :parent_id AND `student_id` = :student_id');
        return $stmt->execute([
            ':parent_id' => $parentId,
            ':student_id' => $studentId,
        ]);
    }

    /**
     * @return ParentProfile[]
     */
    public function getAll(int $limit = 50, int $offset = 0): array
    {
        $select = $this->getSelectFields();
        $order = $this->getOrderByName();
        $sql = "SELECT p.*, {$select}
                FROM `parents` p
                LEFT JOIN `users` u ON u.id = p.user_id
                ORDER BY {$order} ASC
                LIMIT :limit OFFSET :offset";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $parents = [];
        foreach ($rows as $row) {
            $students = $this->getLinkedStudents((int)$row['id']);
            $parents[] = ParentProfile::fromArray($row, null, $students);
        }

        return $parents;
    }

    /**
     * Search parents by name, email, or phone.
     *
     * @return ParentProfile[]
     */
    public function search(string $search, int $limit = 50, int $offset = 0): array
    {
        $select = $this->getSelectFields();
        $order = $this->getOrderByName();
        $where = [];
        $params = [];

        if (!empty($search)) {
            if ($this->hasContactColumns()) {
                $where[] = "(COALESCE(NULLIF(TRIM(u.name), ''), NULLIF(TRIM(p.name), '')) LIKE :search_name 
                          OR COALESCE(NULLIF(TRIM(u.email), ''), NULLIF(TRIM(p.email), '')) LIKE :search_email 
                          OR COALESCE(NULLIF(TRIM(u.phone), ''), NULLIF(TRIM(p.phone), '')) LIKE :search_phone)";
            } else {
                $where[] = "(u.name LIKE :search_name OR u.email LIKE :search_email OR u.phone LIKE :search_phone)";
            }
            $params[':search_name'] = '%' . $search . '%';
            $params[':search_email'] = '%' . $search . '%';
            $params[':search_phone'] = '%' . $search . '%';
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        $sql = "SELECT p.*, {$select}
                FROM `parents` p
                LEFT JOIN `users` u ON u.id = p.user_id
                {$whereClause}
                ORDER BY {$order} ASC
                LIMIT :limit OFFSET :offset";

        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $parents = [];

        foreach ($rows as $row) {
            $students = $this->getLinkedStudents((int)$row['id']);
            $parents[] = ParentProfile::fromArray($row, null, $students);
        }

        return $parents;
    }

    public function countAll(?string $search = null): int
    {
        $where = [];
        $params = [];

        if (!empty($search)) {
            if ($this->hasContactColumns()) {
                $where[] = "(COALESCE(NULLIF(TRIM(u.name), ''), NULLIF(TRIM(p.name), '')) LIKE :search_name 
                          OR COALESCE(NULLIF(TRIM(u.email), ''), NULLIF(TRIM(p.email), '')) LIKE :search_email 
                          OR COALESCE(NULLIF(TRIM(u.phone), ''), NULLIF(TRIM(p.phone), '')) LIKE :search_phone)";
            } else {
                $where[] = "(u.name LIKE :search_name OR u.email LIKE :search_email OR u.phone LIKE :search_phone)";
            }
            $params[':search_name'] = '%' . $search . '%';
            $params[':search_email'] = '%' . $search . '%';
            $params[':search_phone'] = '%' . $search . '%';
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        $sql = "SELECT COUNT(*) FROM `parents` p LEFT JOIN `users` u ON u.id = p.user_id {$whereClause}";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return (int)$stmt->fetchColumn();
    }
}
