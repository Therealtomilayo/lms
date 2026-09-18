<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Models\Module;
use App\Models\ModuleItem;
use PDO;

/**
 * Repository for Course Learning Modules and Assigned Activity Items
 */
class ModuleRepository
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::getInstance();
    }

    /**
     * Find a single module by ID.
     */
    public function findModuleById(int $id): ?Module
    {
        $stmt = $this->pdo->prepare('
            SELECT * FROM `modules`
            WHERE `id` = :id
            LIMIT 1
        ');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? Module::fromArray($row) : null;
    }

    /**
     * Get all modules for a class subject, ordered deterministically by sequence_order ASC, id ASC.
     *
     * @return Module[]
     */
    public function getModulesByClassSubject(int $classSubjectId, bool $publishedOnly = false): array
    {
        $sql = '
            SELECT * FROM `modules`
            WHERE `class_subject_id` = :class_subject_id
        ';
        if ($publishedOnly) {
            $sql .= " AND `status` = 'published'";
        }
        $sql .= ' ORDER BY `sequence_order` ASC, `id` ASC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':class_subject_id' => $classSubjectId]);

        $modules = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $modules[] = Module::fromArray($row);
        }

        return $modules;
    }

    /**
     * Determine next sequence order for a new module in a class subject.
     */
    public function getNextModuleSequenceOrder(int $classSubjectId): int
    {
        $stmt = $this->pdo->prepare('
            SELECT COALESCE(MAX(`sequence_order`), 0) + 1 AS `next_order`
            FROM `modules`
            WHERE `class_subject_id` = :class_subject_id
        ');
        $stmt->execute([':class_subject_id' => $classSubjectId]);
        $res = $stmt->fetch(PDO::FETCH_ASSOC);

        return (int)($res['next_order'] ?? 1);
    }

    /**
     * Create a new module.
     */
    public function createModule(array $data): int
    {
        $stmt = $this->pdo->prepare('
            INSERT INTO `modules` (
                `class_subject_id`, `title`, `description`, `sequence_order`, `status`, `created_at`, `updated_at`
            ) VALUES (
                :class_subject_id, :title, :description, :sequence_order, :status, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
            )
        ');
        $stmt->execute([
            ':class_subject_id' => (int)$data['class_subject_id'],
            ':title' => trim((string)$data['title']),
            ':description' => !empty($data['description']) ? trim((string)$data['description']) : null,
            ':sequence_order' => (int)($data['sequence_order'] ?? 1),
            ':status' => (string)($data['status'] ?? Module::STATUS_PUBLISHED),
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    /**
     * Update an existing module.
     */
    public function updateModule(int $id, array $data): bool
    {
        $fields = [];
        $params = [':id' => $id];

        if (array_key_exists('title', $data)) {
            $fields[] = '`title` = :title';
            $params[':title'] = trim((string)$data['title']);
        }
        if (array_key_exists('description', $data)) {
            $fields[] = '`description` = :description';
            $params[':description'] = !empty($data['description']) ? trim((string)$data['description']) : null;
        }
        if (array_key_exists('sequence_order', $data)) {
            $fields[] = '`sequence_order` = :sequence_order';
            $params[':sequence_order'] = (int)$data['sequence_order'];
        }
        if (array_key_exists('status', $data)) {
            $fields[] = '`status` = :status';
            $params[':status'] = (string)$data['status'];
        }

        if (empty($fields)) {
            return false;
        }

        $fields[] = '`updated_at` = CURRENT_TIMESTAMP';
        $sql = 'UPDATE `modules` SET ' . implode(', ', $fields) . ' WHERE `id` = :id';

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Delete a module.
     */
    public function deleteModule(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM `modules` WHERE `id` = :id');
        return $stmt->execute([':id' => $id]);
    }

    /**
     * Reorder modules within a class subject server-authoritatively in a transaction.
     *
     * @param int $classSubjectId
     * @param int[] $orderedModuleIds
     */
    public function reorderModules(int $classSubjectId, array $orderedModuleIds): bool
    {
        if (empty($orderedModuleIds)) {
            return false;
        }

        $inTransaction = $this->pdo->inTransaction();
        if (!$inTransaction) {
            $this->pdo->beginTransaction();
        }

        try {
            $stmt = $this->pdo->prepare('
                UPDATE `modules`
                SET `sequence_order` = :seq, `updated_at` = CURRENT_TIMESTAMP
                WHERE `id` = :id AND `class_subject_id` = :class_subject_id
            ');

            $order = 1;
            foreach ($orderedModuleIds as $id) {
                $stmt->execute([
                    ':seq' => $order++,
                    ':id' => (int)$id,
                    ':class_subject_id' => $classSubjectId,
                ]);
            }

            if (!$inTransaction) {
                $this->pdo->commit();
            }
            return true;
        } catch (\Throwable $e) {
            if (!$inTransaction && $this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    // ==========================================
    // MODULE ITEMS METHODS
    // ==========================================

    /**
     * Find a single module item by ID.
     */
    public function findItemById(int $itemId): ?ModuleItem
    {
        $stmt = $this->pdo->prepare('
            SELECT * FROM `module_items`
            WHERE `id` = :id
            LIMIT 1
        ');
        $stmt->execute([':id' => $itemId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? ModuleItem::fromArray($row) : null;
    }

    /**
     * Find item by module, type, and activity ID.
     */
    public function findItem(int $moduleId, string $activityType, int $activityId): ?ModuleItem
    {
        $stmt = $this->pdo->prepare('
            SELECT * FROM `module_items`
            WHERE `module_id` = :module_id
              AND `activity_type` = :activity_type
              AND `activity_id` = :activity_id
            LIMIT 1
        ');
        $stmt->execute([
            ':module_id' => $moduleId,
            ':activity_type' => $activityType,
            ':activity_id' => $activityId,
        ]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? ModuleItem::fromArray($row) : null;
    }

    /**
     * Get all items assigned to a module, sorted by sequence_order ASC, id ASC.
     *
     * @return ModuleItem[]
     */
    public function getItemsByModuleId(int $moduleId): array
    {
        $stmt = $this->pdo->prepare('
            SELECT * FROM `module_items`
            WHERE `module_id` = :module_id
            ORDER BY `sequence_order` ASC, `id` ASC
        ');
        $stmt->execute([':module_id' => $moduleId]);

        $items = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $items[] = ModuleItem::fromArray($row);
        }

        return $items;
    }

    /**
     * Batch fetch all items for multiple module IDs to prevent N+1 queries.
     *
     * @param int[] $moduleIds
     * @return array<int, ModuleItem[]> Keyed by module_id
     */
    public function getItemsByModuleIds(array $moduleIds): array
    {
        if (empty($moduleIds)) {
            return [];
        }

        $cleanIds = array_values(array_unique(array_filter(array_map('intval', $moduleIds))));
        if (empty($cleanIds)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($cleanIds), '?'));
        $sql = "
            SELECT * FROM `module_items`
            WHERE `module_id` IN ({$placeholders})
            ORDER BY `sequence_order` ASC, `id` ASC
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($cleanIds);

        $grouped = [];
        foreach ($cleanIds as $mId) {
            $grouped[$mId] = [];
        }

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $item = ModuleItem::fromArray($row);
            $grouped[$item->moduleId][] = $item;
        }

        return $grouped;
    }

    /**
     * Determine next sequence order for an item in a module.
     */
    public function getNextItemSequenceOrder(int $moduleId): int
    {
        $stmt = $this->pdo->prepare('
            SELECT COALESCE(MAX(`sequence_order`), 0) + 1 AS `next_order`
            FROM `module_items`
            WHERE `module_id` = :module_id
        ');
        $stmt->execute([':module_id' => $moduleId]);
        $res = $stmt->fetch(PDO::FETCH_ASSOC);

        return (int)($res['next_order'] ?? 1);
    }

    /**
     * Add an activity to a module.
     */
    public function addItem(int $moduleId, string $activityType, int $activityId, ?int $sequenceOrder = null, bool $isRequired = true): int
    {
        if ($sequenceOrder === null || $sequenceOrder <= 0) {
            $sequenceOrder = $this->getNextItemSequenceOrder($moduleId);
        }

        $stmt = $this->pdo->prepare('
            INSERT INTO `module_items` (
                `module_id`, `activity_type`, `activity_id`, `sequence_order`, `is_required`, `created_at`, `updated_at`
            ) VALUES (
                :module_id, :activity_type, :activity_id, :sequence_order, :is_required, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
            )
        ');
        $stmt->execute([
            ':module_id' => $moduleId,
            ':activity_type' => $activityType,
            ':activity_id' => $activityId,
            ':sequence_order' => $sequenceOrder,
            ':is_required' => $isRequired ? 1 : 0,
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    /**
     * Remove an activity from a module by item ID.
     */
    public function removeItem(int $itemId): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM `module_items` WHERE `id` = :id');
        return $stmt->execute([':id' => $itemId]);
    }

    /**
     * Reorder activities within a module in a transaction.
     *
     * @param int $moduleId
     * @param int[] $orderedItemIds
     */
    public function reorderItems(int $moduleId, array $orderedItemIds): bool
    {
        if (empty($orderedItemIds)) {
            return false;
        }

        $inTransaction = $this->pdo->inTransaction();
        if (!$inTransaction) {
            $this->pdo->beginTransaction();
        }

        try {
            $stmt = $this->pdo->prepare('
                UPDATE `module_items`
                SET `sequence_order` = :seq, `updated_at` = CURRENT_TIMESTAMP
                WHERE `id` = :id AND `module_id` = :module_id
            ');

            $order = 1;
            foreach ($orderedItemIds as $id) {
                $stmt->execute([
                    ':seq' => $order++,
                    ':id' => (int)$id,
                    ':module_id' => $moduleId,
                ]);
            }

            if (!$inTransaction) {
                $this->pdo->commit();
            }
            return true;
        } catch (\Throwable $e) {
            if (!$inTransaction && $this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Count items in a module.
     */
    public function countItemsInModule(int $moduleId): int
    {
        $stmt = $this->pdo->prepare('
            SELECT COUNT(*) AS `cnt`
            FROM `module_items`
            WHERE `module_id` = :module_id
        ');
        $stmt->execute([':module_id' => $moduleId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return (int)($row['cnt'] ?? 0);
    }

    /**
     * Delete module item links referencing a deleted activity.
     */
    public function deleteItemsByActivity(string $activityType, int $activityId): int
    {
        try {
            $stmt = $this->pdo->prepare('
                DELETE FROM `module_items`
                WHERE `activity_type` = :activity_type
                  AND `activity_id` = :activity_id
            ');
            $stmt->execute([
                ':activity_type' => $activityType,
                ':activity_id' => $activityId,
            ]);

            return $stmt->rowCount();
        } catch (\PDOException $e) {
            if (str_contains($e->getMessage(), 'no such table: module_items') || str_contains($e->getMessage(), "doesn't exist")) {
                return 0;
            }
            throw $e;
        }
    }
}
