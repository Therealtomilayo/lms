<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Models\ResultPublication;
use PDO;

/**
 * Repository for Result Publications
 */
final class ResultPublicationRepository
{
    private readonly PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::getInstance();
    }

    /**
     * @return array<int, ResultPublication>
     */
    public function getByTerm(int $termId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM `result_publications` 
             WHERE `term_id` = :term_id 
             ORDER BY `published_at` DESC, `id` DESC'
        );
        $stmt->execute([':term_id' => $termId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(fn (array $r) => ResultPublication::fromArray($r), $rows);
    }

    public function isPublished(int $termId, ?int $classId = null): bool
    {
        if ($classId !== null) {
            $stmt = $this->pdo->prepare(
                'SELECT COUNT(*) FROM `result_publications` 
                 WHERE `term_id` = :term_id 
                   AND (`class_id` = :class_id OR `class_id` IS NULL)
                   AND `status` = \'published\''
            );
            $stmt->execute([
                ':term_id' => $termId,
                ':class_id' => $classId,
            ]);
        } else {
            $stmt = $this->pdo->prepare(
                'SELECT COUNT(*) FROM `result_publications` 
                 WHERE `term_id` = :term_id 
                   AND `status` = \'published\''
            );
            $stmt->execute([':term_id' => $termId]);
        }

        return (int)$stmt->fetchColumn() > 0;
    }

    public function publish(int $termId, ?int $classId, int $publishedBy, ?string $reason = null): int
    {
        $now = date('Y-m-d H:i:s');

        if ($classId !== null) {
            $stmt = $this->pdo->prepare(
                'SELECT `id` FROM `result_publications` 
                 WHERE `term_id` = :term_id AND `class_id` = :class_id'
            );
            $stmt->execute([':term_id' => $termId, ':class_id' => $classId]);
        } else {
            $stmt = $this->pdo->prepare(
                'SELECT `id` FROM `result_publications` 
                 WHERE `term_id` = :term_id AND `class_id` IS NULL'
            );
            $stmt->execute([':term_id' => $termId]);
        }

        $existingId = $stmt->fetchColumn();

        if ($existingId) {
            $updateStmt = $this->pdo->prepare(
                'UPDATE `result_publications` 
                 SET `status` = \'published\', 
                     `published_by` = :published_by, 
                     `published_at` = :published_at, 
                     `unpublished_at` = NULL, 
                     `reason` = :reason 
                 WHERE `id` = :id'
            );
            $updateStmt->execute([
                ':id' => $existingId,
                ':published_by' => $publishedBy,
                ':published_at' => $now,
                ':reason' => $reason,
            ]);
            return (int)$existingId;
        }

        $insertStmt = $this->pdo->prepare(
            'INSERT INTO `result_publications` 
             (`term_id`, `class_id`, `published_by`, `published_at`, `status`, `reason`) 
             VALUES (:term_id, :class_id, :published_by, :published_at, \'published\', :reason)'
        );
        $insertStmt->execute([
            ':term_id' => $termId,
            ':class_id' => $classId,
            ':published_by' => $publishedBy,
            ':published_at' => $now,
            ':reason' => $reason,
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    public function unpublish(int $termId, ?int $classId, ?string $reason = null): bool
    {
        $now = date('Y-m-d H:i:s');

        if ($classId !== null) {
            $stmt = $this->pdo->prepare(
                'UPDATE `result_publications` 
                 SET `status` = \'unpublished\', `unpublished_at` = :unpublished_at, `reason` = :reason 
                 WHERE `term_id` = :term_id AND `class_id` = :class_id'
            );
            return $stmt->execute([
                ':term_id' => $termId,
                ':class_id' => $classId,
                ':unpublished_at' => $now,
                ':reason' => $reason,
            ]);
        }

        $stmt = $this->pdo->prepare(
            'UPDATE `result_publications` 
             SET `status` = \'unpublished\', `unpublished_at` = :unpublished_at, `reason` = :reason 
             WHERE `term_id` = :term_id'
        );
        return $stmt->execute([
            ':term_id' => $termId,
            ':unpublished_at' => $now,
            ':reason' => $reason,
        ]);
    }
}
