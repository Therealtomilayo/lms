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
}
