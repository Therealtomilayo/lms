<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Models\ClassResultSubmission;
use PDO;

/**
 * Repository for Managing Form Teacher Class Result Submissions & Admin Approval
 */
final class ResultSubmissionRepository
{
    private readonly PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::getInstance();
    }

    public function findSubmission(int $classId, int $termId): ?ClassResultSubmission
    {
        $stmt = $this->pdo->prepare('
            SELECT crs.*, 
                   u.name AS teacher_name, 
                   c.name AS class_name, 
                   c.section_arm, 
                   t.name AS term_name
            FROM `class_result_submissions` crs
            LEFT JOIN `teachers` tch ON tch.id = crs.submitted_by
            LEFT JOIN `users` u ON u.id = tch.user_id
            LEFT JOIN `classes` c ON c.id = crs.class_id
            LEFT JOIN `terms` t ON t.id = crs.term_id
            WHERE crs.class_id = :class_id AND crs.term_id = :term_id
            LIMIT 1
        ');
        $stmt->execute([
            ':class_id' => $classId,
            ':term_id' => $termId,
        ]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? ClassResultSubmission::fromArray($row) : null;
    }

    public function submit(int $classId, int $termId, int $teacherId, ?string $notes = null): ?ClassResultSubmission
    {
        $now = date('Y-m-d H:i:s');
        $existing = $this->findSubmission($classId, $termId);

        if ($existing) {
            $stmt = $this->pdo->prepare('
                UPDATE `class_result_submissions`
                SET `submitted_by` = :submitted_by,
                    `submitted_at` = :submitted_at,
                    `status` = \'submitted\',
                    `notes` = :notes,
                    `reviewed_by` = NULL,
                    `reviewed_at` = NULL,
                    `updated_at` = :updated_at
                WHERE `id` = :id
            ');
            $stmt->execute([
                ':submitted_by' => $teacherId,
                ':submitted_at' => $now,
                ':notes' => $notes,
                ':updated_at' => $now,
                ':id' => $existing->id,
            ]);
        } else {
            $stmt = $this->pdo->prepare('
                INSERT INTO `class_result_submissions`
                (`class_id`, `term_id`, `submitted_by`, `submitted_at`, `status`, `notes`, `created_at`, `updated_at`)
                VALUES
                (:class_id, :term_id, :submitted_by, :submitted_at, \'submitted\', :notes, :created_at, :updated_at)
            ');
            $stmt->execute([
                ':class_id' => $classId,
                ':term_id' => $termId,
                ':submitted_by' => $teacherId,
                ':submitted_at' => $now,
                ':notes' => $notes,
                ':created_at' => $now,
                ':updated_at' => $now,
            ]);
        }

        return $this->findSubmission($classId, $termId);
    }

    public function approve(int $classId, int $termId, int $adminUserId): bool
    {
        $now = date('Y-m-d H:i:s');
        $stmt = $this->pdo->prepare('
            UPDATE `class_result_submissions`
            SET `status` = \'approved\',
                `reviewed_by` = :reviewed_by,
                `reviewed_at` = :reviewed_at,
                `updated_at` = :updated_at
            WHERE `class_id` = :class_id AND `term_id` = :term_id
        ');
        return $stmt->execute([
            ':reviewed_by' => $adminUserId,
            ':reviewed_at' => $now,
            ':updated_at' => $now,
            ':class_id' => $classId,
            ':term_id' => $termId,
        ]);
    }

    public function reject(int $classId, int $termId, int $adminUserId, ?string $reason = null): bool
    {
        $now = date('Y-m-d H:i:s');
        $stmt = $this->pdo->prepare('
            UPDATE `class_result_submissions`
            SET `status` = \'rejected\',
                `notes` = :notes,
                `reviewed_by` = :reviewed_by,
                `reviewed_at` = :reviewed_at,
                `updated_at` = :updated_at
            WHERE `class_id` = :class_id AND `term_id` = :term_id
        ');
        return $stmt->execute([
            ':notes' => $reason,
            ':reviewed_by' => $adminUserId,
            ':reviewed_at' => $now,
            ':updated_at' => $now,
            ':class_id' => $classId,
            ':term_id' => $termId,
        ]);
    }

    /**
     * @return array<int, ClassResultSubmission> [class_id => ClassResultSubmission]
     */
    public function getSubmissionsByTerm(int $termId): array
    {
        $stmt = $this->pdo->prepare('
            SELECT crs.*, 
                   u.name AS teacher_name, 
                   c.name AS class_name, 
                   c.section_arm, 
                   t.name AS term_name
            FROM `class_result_submissions` crs
            LEFT JOIN `teachers` tch ON tch.id = crs.submitted_by
            LEFT JOIN `users` u ON u.id = tch.user_id
            LEFT JOIN `classes` c ON c.id = crs.class_id
            LEFT JOIN `terms` t ON t.id = crs.term_id
            WHERE crs.term_id = :term_id
        ');
        $stmt->execute([':term_id' => $termId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $map = [];
        foreach ($rows as $r) {
            $sub = ClassResultSubmission::fromArray($r);
            $map[$sub->classId] = $sub;
        }

        return $map;
    }
}
