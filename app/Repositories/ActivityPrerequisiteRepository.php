<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Models\ActivityPrerequisite;
use PDO;

/**
 * Repository for Activity Prerequisites
 * Fully compatible with MySQL and SQLite.
 */
class ActivityPrerequisiteRepository
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

    /**
     * Find prerequisite by ID.
     */
    public function findById(int $id): ?ActivityPrerequisite
    {
        $stmt = $this->pdo->prepare('
            SELECT * FROM `activity_prerequisites`
            WHERE `id` = :id
            LIMIT 1
        ');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? ActivityPrerequisite::fromArray($row) : null;
    }

    /**
     * Find specific relationship between target and prerequisite.
     */
    public function findRelationship(
        string $activityType,
        int $activityId,
        string $prereqType,
        int $prereqId
    ): ?ActivityPrerequisite {
        $stmt = $this->pdo->prepare('
            SELECT * FROM `activity_prerequisites`
            WHERE `activity_type` = :activity_type
              AND `activity_id` = :activity_id
              AND `prerequisite_activity_type` = :prereq_type
              AND `prerequisite_activity_id` = :prereq_id
            LIMIT 1
        ');
        $stmt->execute([
            ':activity_type' => $activityType,
            ':activity_id' => $activityId,
            ':prereq_type' => $prereqType,
            ':prereq_id' => $prereqId,
        ]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? ActivityPrerequisite::fromArray($row) : null;
    }

    /**
     * Get all prerequisites for a target activity.
     * (What does TARGET require?)
     *
     * @return ActivityPrerequisite[]
     */
    public function getPrerequisitesForActivity(string $activityType, int $activityId): array
    {
        $stmt = $this->pdo->prepare('
            SELECT * FROM `activity_prerequisites`
            WHERE `activity_type` = :activity_type
              AND `activity_id` = :activity_id
            ORDER BY `id` ASC
        ');
        $stmt->execute([
            ':activity_type' => $activityType,
            ':activity_id' => $activityId,
        ]);

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return array_map(fn($r) => ActivityPrerequisite::fromArray($r), $rows);
    }

    /**
     * Get all dependent activities for a source activity.
     * (What requires SOURCE?)
     *
     * @return ActivityPrerequisite[]
     */
    public function getDependentsForActivity(string $prereqType, int $prereqId): array
    {
        $stmt = $this->pdo->prepare('
            SELECT * FROM `activity_prerequisites`
            WHERE `prerequisite_activity_type` = :prereq_type
              AND `prerequisite_activity_id` = :prereq_id
            ORDER BY `id` ASC
        ');
        $stmt->execute([
            ':prereq_type' => $prereqType,
            ':prereq_id' => $prereqId,
        ]);

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return array_map(fn($r) => ActivityPrerequisite::fromArray($r), $rows);
    }

    /**
     * Bulk fetch prerequisites for multiple target activities of the same type.
     * Avoids N+1 queries when loading activity listings.
     *
     * @param string $activityType
     * @param int[] $activityIds
     * @return array<int, ActivityPrerequisite[]> Map of activity_id => list of prerequisites
     */
    public function getPrerequisitesMap(string $activityType, array $activityIds): array
    {
        if (empty($activityIds)) {
            return [];
        }

        $cleanIds = array_values(array_unique(array_filter(array_map('intval', $activityIds), fn($id) => $id > 0)));
        if (empty($cleanIds)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($cleanIds), '?'));
        $sql = "
            SELECT * FROM `activity_prerequisites`
            WHERE `activity_type` = ?
              AND `activity_id` IN ({$placeholders})
            ORDER BY `id` ASC
        ";

        $params = array_merge([$activityType], $cleanIds);
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        $map = [];
        foreach ($cleanIds as $id) {
            $map[$id] = [];
        }

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $prereq = ActivityPrerequisite::fromArray($row);
            $map[$prereq->activityId][] = $prereq;
        }

        return $map;
    }

    /**
     * Create a new prerequisite relationship.
     */
    public function create(
        string $activityType,
        int $activityId,
        string $prereqType,
        int $prereqId,
        string $requirementType = ActivityPrerequisite::REQUIREMENT_COMPLETION
    ): ActivityPrerequisite {
        $now = date('Y-m-d H:i:s');
        $stmt = $this->pdo->prepare('
            INSERT INTO `activity_prerequisites` (
                `activity_type`, `activity_id`,
                `prerequisite_activity_type`, `prerequisite_activity_id`,
                `requirement_type`, `created_at`, `updated_at`
            ) VALUES (
                :activity_type, :activity_id,
                :prereq_type, :prereq_id,
                :requirement_type, :created_at, :updated_at
            )
        ');

        $stmt->execute([
            ':activity_type' => $activityType,
            ':activity_id' => $activityId,
            ':prereq_type' => $prereqType,
            ':prereq_id' => $prereqId,
            ':requirement_type' => $requirementType,
            ':created_at' => $now,
            ':updated_at' => $now,
        ]);

        $id = (int)$this->pdo->lastInsertId();

        return new ActivityPrerequisite(
            id: $id,
            activityType: $activityType,
            activityId: $activityId,
            prerequisiteActivityType: $prereqType,
            prerequisiteActivityId: $prereqId,
            requirementType: $requirementType,
            createdAt: $now,
            updatedAt: $now
        );
    }

    /**
     * Delete a prerequisite by ID.
     */
    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM `activity_prerequisites` WHERE `id` = :id');
        return $stmt->execute([':id' => $id]) && $stmt->rowCount() > 0;
    }

    /**
     * Clean up all prerequisite relationships where an activity is either the TARGET or the SOURCE.
     * Called when an activity (e.g. section, document, quiz) is deleted.
     */
    public function deleteForActivity(string $activityType, int $activityId): int
    {
        try {
            $stmt = $this->pdo->prepare('
                DELETE FROM `activity_prerequisites`
                WHERE (`activity_type` = :activity_type AND `activity_id` = :activity_id)
                   OR (`prerequisite_activity_type` = :prereq_type AND `prerequisite_activity_id` = :prereq_id)
            ');
            $stmt->execute([
                ':activity_type' => $activityType,
                ':activity_id' => $activityId,
                ':prereq_type' => $activityType,
                ':prereq_id' => $activityId,
            ]);

            return $stmt->rowCount();
        } catch (\PDOException $e) {
            if (str_contains($e->getMessage(), 'no such table: activity_prerequisites') || str_contains($e->getMessage(), "doesn't exist")) {
                return 0;
            }
            throw $e;
        }
    }

    /**
     * Batch-fetch prerequisite rules for multiple target activities.
     * Prevents N+1 queries when building reports or learning paths.
     *
     * @param array<int, array{activity_type?: string, type?: string, activity_id?: int, id?: int}> $activityTuples
     * @return array<string, ActivityPrerequisite[]> Map of "activity_type:activity_id" => ActivityPrerequisite[]
     */
    public function getPrerequisitesForActivities(array $activityTuples): array
    {
        if (empty($activityTuples)) {
            return [];
        }

        $byType = [];
        foreach ($activityTuples as $tuple) {
            $type = (string)($tuple['activity_type'] ?? $tuple['type'] ?? '');
            $id = (int)($tuple['activity_id'] ?? $tuple['id'] ?? 0);
            if ($type !== '' && $id > 0) {
                $byType[$type][$id] = true;
            }
        }

        if (empty($byType)) {
            return [];
        }

        $params = [];
        $typeClauses = [];
        foreach ($byType as $type => $idsMap) {
            $ids = array_keys($idsMap);
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $typeClauses[] = "(`activity_type` = ? AND `activity_id` IN ({$placeholders}))";
            $params[] = $type;
            foreach ($ids as $id) {
                $params[] = $id;
            }
        }

        $sql = "
            SELECT * FROM `activity_prerequisites`
            WHERE " . implode(' OR ', $typeClauses) . "
            ORDER BY `id` ASC
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        $map = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $prereq = ActivityPrerequisite::fromArray($row);
            $key = "{$prereq->activityType}:{$prereq->activityId}";
            $map[$key][] = $prereq;
        }

        return $map;
    }
}
