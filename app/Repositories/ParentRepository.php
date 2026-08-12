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
 */
class ParentRepository
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::getInstance();
    }

    public function findById(int $id): ?ParentProfile
    {
        $sql = 'SELECT p.*, 
                       u.name as user_name, u.email as user_email, u.phone as user_phone, u.status as user_status
                FROM `parents` p
                JOIN `users` u ON u.id = p.user_id
                WHERE p.id = :id LIMIT 1';

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
        $sql = 'SELECT p.*, 
                       u.name as user_name, u.email as user_email, u.phone as user_phone, u.status as user_status
                FROM `parents` p
                JOIN `users` u ON u.id = p.user_id
                WHERE p.user_id = :user_id LIMIT 1';

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
        $sql = 'INSERT INTO `parents` (`user_id`, `created_at`, `updated_at`) VALUES (:user_id, :created_at, :updated_at)';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':user_id' => $userId,
            ':created_at' => $now,
            ':updated_at' => $now,
        ]);

        $parentId = (int)$this->pdo->lastInsertId();

        return $this->findById($parentId);
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
        $sql = 'SELECT p.*, 
                       u.name as user_name, u.email as user_email, u.phone as user_phone, u.status as user_status,
                       ps.relationship_type
                FROM `parent_student` ps
                JOIN `parents` p ON p.id = ps.parent_id
                JOIN `users` u ON u.id = p.user_id
                WHERE ps.student_id = :student_id
                ORDER BY u.name ASC';

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
        $sql = 'DELETE FROM `parent_student` WHERE `parent_id` = :parent_id AND `student_id` = :student_id';
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':parent_id' => $parentId,
            ':student_id' => $studentId,
        ]);
    }

    /**
     * @return ParentProfile[]
     */
    public function getAll(int $limit = 50, int $offset = 0, ?string $search = null): array
    {
        $where = [];
        $params = [];

        if (!empty($search)) {
            $where[] = '(u.name LIKE :search_name OR u.email LIKE :search_email OR u.phone LIKE :search_phone)';
            $params[':search_name'] = '%' . $search . '%';
            $params[':search_email'] = '%' . $search . '%';
            $params[':search_phone'] = '%' . $search . '%';
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        $sql = "SELECT p.*, 
                       u.name as user_name, u.email as user_email, u.phone as user_phone, u.status as user_status
                FROM `parents` p
                JOIN `users` u ON u.id = p.user_id
                {$whereClause}
                ORDER BY u.name ASC
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
            $where[] = '(u.name LIKE :search_name OR u.email LIKE :search_email OR u.phone LIKE :search_phone)';
            $params[':search_name'] = '%' . $search . '%';
            $params[':search_email'] = '%' . $search . '%';
            $params[':search_phone'] = '%' . $search . '%';
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        $sql = "SELECT COUNT(*) FROM `parents` p JOIN `users` u ON u.id = p.user_id {$whereClause}";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return (int)$stmt->fetchColumn();
    }
}
