<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Models\GradeBoundary;
use App\Models\GradingScale;
use PDO;

/**
 * Repository for Grading Scales and Grade Boundaries
 */
final class GradingScaleRepository
{
    private readonly PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::getInstance();
    }

    /**
     * @return array<int, GradingScale>
     */
    public function getAll(): array
    {
        $stmt = $this->pdo->query('SELECT * FROM `grading_scales` ORDER BY `is_default` DESC, `name` ASC');
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $scales = [];
        foreach ($rows as $row) {
            $boundaries = $this->getBoundariesByScaleId((int)$row['id']);
            $scales[] = GradingScale::fromArray($row, $boundaries);
        }

        return $scales;
    }

    public function findById(int $id): ?GradingScale
    {
        $stmt = $this->pdo->prepare('SELECT * FROM `grading_scales` WHERE `id` = :id');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        $boundaries = $this->getBoundariesByScaleId($id);
        return GradingScale::fromArray($row, $boundaries);
    }

    public function getDefaultScale(): ?GradingScale
    {
        $stmt = $this->pdo->query('SELECT * FROM `grading_scales` WHERE `is_default` = 1 LIMIT 1');
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            $stmt = $this->pdo->query('SELECT * FROM `grading_scales` ORDER BY `id` ASC LIMIT 1');
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
        }

        if (!$row) {
            return null;
        }

        $boundaries = $this->getBoundariesByScaleId((int)$row['id']);
        return GradingScale::fromArray($row, $boundaries);
    }

    /**
     * @return array<int, GradeBoundary>
     */
    public function getBoundariesByScaleId(int $scaleId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM `grade_boundaries` 
             WHERE `grading_scale_id` = :scale_id 
             ORDER BY `min_score` DESC'
        );
        $stmt->execute([':scale_id' => $scaleId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(fn (array $r) => GradeBoundary::fromArray($r), $rows);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function createScale(array $data): int
    {
        if (!empty($data['is_default'])) {
            $this->pdo->exec('UPDATE `grading_scales` SET `is_default` = 0');
        }

        $stmt = $this->pdo->prepare(
            'INSERT INTO `grading_scales` (`name`, `description`, `is_default`) 
             VALUES (:name, :description, :is_default)'
        );
        $stmt->execute([
            ':name' => $data['name'],
            ':description' => $data['description'] ?? null,
            ':is_default' => !empty($data['is_default']) ? 1 : 0,
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    /**
     * @param array<string, mixed> $data
     */
    public function updateScale(int $id, array $data): bool
    {
        if (!empty($data['is_default'])) {
            $stmt = $this->pdo->prepare('UPDATE `grading_scales` SET `is_default` = 0 WHERE `id` != :id');
            $stmt->execute([':id' => $id]);
        }

        $stmt = $this->pdo->prepare(
            'UPDATE `grading_scales` 
             SET `name` = :name, `description` = :description, `is_default` = :is_default 
             WHERE `id` = :id'
        );
        return $stmt->execute([
            ':id' => $id,
            ':name' => $data['name'],
            ':description' => $data['description'] ?? null,
            ':is_default' => !empty($data['is_default']) ? 1 : 0,
        ]);
    }

    public function deleteScale(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM `grading_scales` WHERE `id` = :id');
        return $stmt->execute([':id' => $id]);
    }

    /**
     * @param array<int, array<string, mixed>> $boundaries
     */
    public function syncBoundaries(int $scaleId, array $boundaries): void
    {
        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare('DELETE FROM `grade_boundaries` WHERE `grading_scale_id` = :scale_id');
            $stmt->execute([':scale_id' => $scaleId]);

            $insertStmt = $this->pdo->prepare(
                'INSERT INTO `grade_boundaries` 
                 (`grading_scale_id`, `letter`, `min_score`, `max_score`, `grade_point`, `remark`) 
                 VALUES (:scale_id, :letter, :min_score, :max_score, :grade_point, :remark)'
            );

            foreach ($boundaries as $b) {
                $insertStmt->execute([
                    ':scale_id' => $scaleId,
                    ':letter' => (string)$b['letter'],
                    ':min_score' => (float)$b['min_score'],
                    ':max_score' => (float)$b['max_score'],
                    ':grade_point' => isset($b['grade_point']) && $b['grade_point'] !== '' ? (float)$b['grade_point'] : null,
                    ':remark' => isset($b['remark']) && $b['remark'] !== '' ? (string)$b['remark'] : null,
                ]);
            }

            $this->pdo->commit();
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }
}
