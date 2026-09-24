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

    private ?bool $hasStageColumn = null;

    private function supportsStageColumn(): bool
    {
        if ($this->hasStageColumn !== null) {
            return $this->hasStageColumn;
        }

        try {
            $driver = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
            if ($driver === 'sqlite') {
                $stmt = $this->pdo->query("PRAGMA table_info(grading_scales)");
                $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
                foreach ($columns as $col) {
                    if (($col['name'] ?? '') === 'stage') {
                        return $this->hasStageColumn = true;
                    }
                }
                return $this->hasStageColumn = false;
            }

            $stmt = $this->pdo->query("SHOW COLUMNS FROM `grading_scales` LIKE 'stage'");
            return $this->hasStageColumn = (bool)$stmt->fetchColumn();
        } catch (\Throwable) {
            return $this->hasStageColumn = false;
        }
    }

    public function findByStage(string $stage): ?GradingScale
    {
        if (!$this->supportsStageColumn()) {
            return null;
        }

        try {
            $stmt = $this->pdo->prepare('SELECT * FROM `grading_scales` WHERE `stage` = :stage ORDER BY `is_default` DESC, `id` ASC LIMIT 1');
            $stmt->execute([':stage' => trim($stage)]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$row) {
                return null;
            }

            $boundaries = $this->getBoundariesByScaleId((int)$row['id']);
            return GradingScale::fromArray($row, $boundaries);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Resolves the appropriate grading scale using the hierarchy:
     * 1. Direct level override (`academic_levels.grading_scale_id`)
     * 2. Educational stage mapping (`grading_scales.stage`)
     * 3. System default scale (`grading_scales.is_default = 1`)
     */
    public function getScaleForLevel(?int $levelId = null, ?string $stage = null): ?GradingScale
    {
        // 1. Direct level override
        if ($levelId !== null) {
            try {
                $stmt = $this->pdo->prepare('SELECT `grading_scale_id`, `stage` FROM `academic_levels` WHERE `id` = :id');
                $stmt->execute([':id' => $levelId]);
                $lvl = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($lvl) {
                    if (!empty($lvl['grading_scale_id'])) {
                        $scale = $this->findById((int)$lvl['grading_scale_id']);
                        if ($scale !== null) {
                            return $scale;
                        }
                    }
                    if (empty($stage) && !empty($lvl['stage'])) {
                        $stage = (string)$lvl['stage'];
                    }
                }
            } catch (\Throwable) {
                // Table might not exist in isolated unit test
            }
        }

        // 2. Stage mapping
        if (!empty($stage)) {
            $scale = $this->findByStage($stage);
            if ($scale !== null) {
                return $scale;
            }
        }

        // 3. Fallback to system default
        try {
            $defaultScale = $this->getDefaultScale();
            if ($defaultScale !== null) {
                return $defaultScale;
            }
        } catch (\Throwable) {
            // Table might not exist in isolated unit test
        }

        // 4. Fallback to any first available scale
        try {
            $all = $this->getAll();
            if (!empty($all)) {
                return $all[0];
            }
        } catch (\Throwable) {
            // Table might not exist in isolated unit test
        }

        return null;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function createScale(array $data): int
    {
        try {
            if (!empty($data['is_default'])) {
                $this->pdo->exec('UPDATE `grading_scales` SET `is_default` = 0');
            }
        } catch (\Throwable) {
            // Ignore if table not present
        }

        if ($this->supportsStageColumn()) {
            $stmt = $this->pdo->prepare(
                'INSERT INTO `grading_scales` (`name`, `stage`, `description`, `is_default`) 
                 VALUES (:name, :stage, :description, :is_default)'
            );
            $stmt->execute([
                ':name' => $data['name'],
                ':stage' => !empty($data['stage']) ? trim((string)$data['stage']) : null,
                ':description' => $data['description'] ?? null,
                ':is_default' => !empty($data['is_default']) ? 1 : 0,
            ]);
        } else {
            $stmt = $this->pdo->prepare(
                'INSERT INTO `grading_scales` (`name`, `description`, `is_default`) 
                 VALUES (:name, :description, :is_default)'
            );
            $stmt->execute([
                ':name' => $data['name'],
                ':description' => $data['description'] ?? null,
                ':is_default' => !empty($data['is_default']) ? 1 : 0,
            ]);
        }

        return (int)$this->pdo->lastInsertId();
    }

    /**
     * @param array<string, mixed> $data
     */
    public function updateScale(int $id, array $data): bool
    {
        try {
            if (!empty($data['is_default'])) {
                $stmt = $this->pdo->prepare('UPDATE `grading_scales` SET `is_default` = 0 WHERE `id` != :id');
                $stmt->execute([':id' => $id]);
            }
        } catch (\Throwable) {
            // Ignore
        }

        if ($this->supportsStageColumn()) {
            $stmt = $this->pdo->prepare(
                'UPDATE `grading_scales` 
                 SET `name` = :name, `stage` = :stage, `description` = :description, `is_default` = :is_default 
                 WHERE `id` = :id'
            );
            return $stmt->execute([
                ':id' => $id,
                ':name' => $data['name'],
                ':stage' => !empty($data['stage']) ? trim((string)$data['stage']) : null,
                ':description' => $data['description'] ?? null,
                ':is_default' => !empty($data['is_default']) ? 1 : 0,
            ]);
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
