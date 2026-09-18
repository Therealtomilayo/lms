<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Models\ActivityProgress;
use PDO;

/**
 * Repository for Learning Activity Progress & Completion Tracking
 * Fully compatible with MySQL and SQLite (for automated testing).
 */
class ActivityProgressRepository
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::getInstance();
    }

    /**
     * Find progress record for a student and specific activity.
     */
    public function findByStudentAndActivity(int $studentId, string $activityType, int $activityId): ?ActivityProgress
    {
        $stmt = $this->pdo->prepare('
            SELECT * FROM `learning_activity_progress`
            WHERE `student_id` = :student_id
              AND `activity_type` = :activity_type
              AND `activity_id` = :activity_id
            LIMIT 1
        ');

        $stmt->execute([
            ':student_id' => $studentId,
            ':activity_type' => $activityType,
            ':activity_id' => $activityId,
        ]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? ActivityProgress::fromArray($row) : null;
    }

    /**
     * Find document reading progress for a student.
     */
    public function findDocumentProgress(int $studentId, int $contentItemId): ?ActivityProgress
    {
        return $this->findByStudentAndActivity($studentId, ActivityProgress::TYPE_DOCUMENT, $contentItemId);
    }

    /**
     * Bulk fetch document progress records for a student across multiple content items (avoids N+1 queries).
     *
     * @param int[] $contentItemIds
     * @return array<int, ActivityProgress> Map of content_item_id => ActivityProgress
     */
    public function getDocumentProgressMap(int $studentId, array $contentItemIds): array
    {
        if (empty($contentItemIds)) {
            return [];
        }

        $cleanIds = array_values(array_unique(array_filter(array_map('intval', $contentItemIds), fn($id) => $id > 0)));
        if (empty($cleanIds)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($cleanIds), '?'));
        $sql = "
            SELECT * FROM `learning_activity_progress`
            WHERE `student_id` = ?
              AND `activity_type` = ?
              AND `activity_id` IN ({$placeholders})
        ";

        $params = array_merge([$studentId, ActivityProgress::TYPE_DOCUMENT], $cleanIds);
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        $map = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $progress = ActivityProgress::fromArray($row);
            $map[$progress->activityId] = $progress;
        }

        return $map;
    }

    /**
     * Find section reading progress for a student.
     */
    public function findSectionProgress(int $studentId, int $sectionId): ?ActivityProgress
    {
        return $this->findByStudentAndActivity($studentId, ActivityProgress::TYPE_DOCUMENT_SECTION, $sectionId);
    }

    /**
     * Bulk fetch section progress records for a student across multiple section IDs.
     *
     * @param int[] $sectionIds
     * @return array<int, ActivityProgress> Map of section_id => ActivityProgress
     */
    public function getSectionProgressMap(int $studentId, array $sectionIds): array
    {
        if (empty($sectionIds)) {
            return [];
        }

        $cleanIds = array_values(array_unique(array_filter(array_map('intval', $sectionIds), fn($id) => $id > 0)));
        if (empty($cleanIds)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($cleanIds), '?'));
        $sql = "
            SELECT * FROM `learning_activity_progress`
            WHERE `student_id` = ?
              AND `activity_type` = ?
              AND `activity_id` IN ({$placeholders})
        ";

        $params = array_merge([$studentId, ActivityProgress::TYPE_DOCUMENT_SECTION], $cleanIds);
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        $map = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $progress = ActivityProgress::fromArray($row);
            $map[$progress->activityId] = $progress;
        }

        return $map;
    }

    /**
     * Synchronize and derive section progress from authoritative document pages read.
     */
    public function syncSectionProgress(
        int $studentId,
        \App\Models\DocumentSection $section,
        array $documentPagesRead
    ): ActivityProgress {
        $now = date('Y-m-d H:i:s');
        $calc = $section->calculateProgress($documentPagesRead);
        $existing = $this->findSectionProgress($studentId, $section->id);

        $wasCompleted = $existing?->isCompleted() ?? false;
        $isCompleted = $wasCompleted || $calc['is_completed'];

        $completedAt = $existing?->completedAt;
        if ($isCompleted && $completedAt === null) {
            $completedAt = $now;
        }

        $pagesReadJson = json_encode($calc['pages_read']);

        if ($existing) {
            $stmt = $this->pdo->prepare('
                UPDATE `learning_activity_progress`
                SET `total_pages` = :total_pages,
                    `pages_read_json` = :pages_read_json,
                    `progress_percent` = :progress_percent,
                    `is_completed` = :is_completed,
                    `completed_at` = :completed_at,
                    `last_accessed_at` = :last_accessed_at,
                    `updated_at` = :updated_at
                WHERE `id` = :id
            ');
            $stmt->execute([
                ':total_pages' => $calc['total_pages'],
                ':pages_read_json' => $pagesReadJson,
                ':progress_percent' => $calc['progress_percent'],
                ':is_completed' => $isCompleted ? 1 : 0,
                ':completed_at' => $completedAt,
                ':last_accessed_at' => $now,
                ':updated_at' => $now,
                ':id' => $existing->id,
            ]);

            return $this->findSectionProgress($studentId, $section->id);
        }

        $stmt = $this->pdo->prepare('
            INSERT INTO `learning_activity_progress` (
                `student_id`, `activity_type`, `activity_id`, `last_page`, `total_pages`,
                `pages_read_json`, `progress_percent`, `is_completed`, `completed_at`,
                `last_accessed_at`, `created_at`, `updated_at`
            ) VALUES (
                :student_id, :activity_type, :activity_id, :last_page, :total_pages,
                :pages_read_json, :progress_percent, :is_completed, :completed_at,
                :last_accessed_at, :created_at, :updated_at
            )
        ');

        $stmt->execute([
            ':student_id' => $studentId,
            ':activity_type' => ActivityProgress::TYPE_DOCUMENT_SECTION,
            ':activity_id' => $section->id,
            ':last_page' => $section->startPage,
            ':total_pages' => $calc['total_pages'],
            ':pages_read_json' => $pagesReadJson,
            ':progress_percent' => $calc['progress_percent'],
            ':is_completed' => $isCompleted ? 1 : 0,
            ':completed_at' => $completedAt,
            ':last_accessed_at' => $now,
            ':created_at' => $now,
            ':updated_at' => $now,
        ]);

        return $this->findSectionProgress($studentId, $section->id);
    }

    /**
     * Clean up section progress records when a section is deleted.
     */
    public function deleteSectionProgress(int $sectionId): void
    {
        $stmt = $this->pdo->prepare('
            DELETE FROM `learning_activity_progress`
            WHERE `activity_type` = :activity_type
              AND `activity_id` = :activity_id
        ');
        $stmt->execute([
            ':activity_type' => ActivityProgress::TYPE_DOCUMENT_SECTION,
            ':activity_id' => $sectionId,
        ]);
    }

    /**
     * Authoritatively record completion for a non-document activity (e.g. quiz, assignment).
     */
    public function recordActivityCompletion(
        int $studentId,
        string $activityType,
        int $activityId,
        float $progressPercent = 100.0
    ): ActivityProgress {
        $now = date('Y-m-d H:i:s');
        $existing = $this->findByStudentAndActivity($studentId, $activityType, $activityId);

        if ($existing) {
            $stmt = $this->pdo->prepare('
                UPDATE `learning_activity_progress`
                SET `progress_percent` = :progress_percent,
                    `is_completed` = 1,
                    `completed_at` = COALESCE(`completed_at`, :completed_at),
                    `last_accessed_at` = :last_accessed_at,
                    `updated_at` = :updated_at
                WHERE `id` = :id
            ');
            $stmt->execute([
                ':progress_percent' => $progressPercent,
                ':completed_at' => $now,
                ':last_accessed_at' => $now,
                ':updated_at' => $now,
                ':id' => $existing->id,
            ]);

            return $this->findByStudentAndActivity($studentId, $activityType, $activityId);
        }

        $stmt = $this->pdo->prepare('
            INSERT INTO `learning_activity_progress` (
                `student_id`, `activity_type`, `activity_id`, `progress_percent`,
                `is_completed`, `completed_at`, `last_accessed_at`, `created_at`, `updated_at`
            ) VALUES (
                :student_id, :activity_type, :activity_id, :progress_percent,
                1, :completed_at, :last_accessed_at, :created_at, :updated_at
            )
        ');
        $stmt->execute([
            ':student_id' => $studentId,
            ':activity_type' => $activityType,
            ':activity_id' => $activityId,
            ':progress_percent' => $progressPercent,
            ':completed_at' => $now,
            ':last_accessed_at' => $now,
            ':created_at' => $now,
            ':updated_at' => $now,
        ]);

        return $this->findByStudentAndActivity($studentId, $activityType, $activityId);
    }

    /**
     * Upsert document reading progress with set-union unique page merging and 90% completion logic.
     *
     * @param int $studentId
     * @param int $contentItemId
     * @param int $lastPage
     * @param int $totalPages
     * @param int[] $newPagesNewlyViewed
     * @return ActivityProgress
     */
    public function recordDocumentReadingProgress(
        int $studentId,
        int $contentItemId,
        int $lastPage,
        int $totalPages,
        array $newPagesNewlyViewed = []
    ): ActivityProgress {
        $now = date('Y-m-d H:i:s');
        $existing = $this->findDocumentProgress($studentId, $contentItemId);

        // Sanitize total pages
        $effectiveTotalPages = $totalPages > 0
            ? $totalPages
            : ($existing?->totalPages ?? 1);

        if ($effectiveTotalPages < 1) {
            $effectiveTotalPages = 1;
        }

        // Sanitize last page
        $clampedLastPage = max(1, min($lastPage, $effectiveTotalPages));

        // Sanitize incoming newly reported pages
        $sanitizedNewPages = array_filter(
            array_map('intval', $newPagesNewlyViewed),
            fn(int $p) => $p >= 1 && $p <= $effectiveTotalPages
        );

        // Also ensure current last page is included in the page set
        $sanitizedNewPages[] = $clampedLastPage;

        // Merge using Set-Union semantics with existing pages
        $existingPages = $existing ? $existing->pagesRead : [];
        $mergedPages = array_values(array_unique(array_merge($existingPages, $sanitizedNewPages)));
        sort($mergedPages, SORT_NUMERIC);

        // Calculate progress percentage server-side
        $uniqueCount = count($mergedPages);
        $progressPercent = round(($uniqueCount / $effectiveTotalPages) * 100, 2);
        $progressPercent = min(100.00, max(0.00, $progressPercent));

        // Determine completion state (90% threshold rule)
        if ($progressPercent >= ActivityProgress::COMPLETION_THRESHOLD_PERCENT) {
            $isCompleted = 1;
            // Retain original completion timestamp if already completed; otherwise set now
            $completedAt = $existing?->isCompleted ? $existing->completedAt : $now;
        } else {
            // Once completed, it cannot be reverted by stale client requests
            if ($existing?->isCompleted) {
                $isCompleted = 1;
                $completedAt = $existing->completedAt;
            } else {
                $isCompleted = 0;
                $completedAt = null;
            }
        }

        $pagesJson = json_encode($mergedPages);

        if ($existing) {
            $stmt = $this->pdo->prepare('
                UPDATE `learning_activity_progress`
                SET `last_page` = :last_page,
                    `total_pages` = :total_pages,
                    `pages_read_json` = :pages_read_json,
                    `progress_percent` = :progress_percent,
                    `is_completed` = :is_completed,
                    `completed_at` = :completed_at,
                    `last_accessed_at` = :last_accessed_at,
                    `updated_at` = :updated_at
                WHERE `id` = :id
            ');

            $stmt->execute([
                ':last_page' => $clampedLastPage,
                ':total_pages' => $effectiveTotalPages,
                ':pages_read_json' => $pagesJson,
                ':progress_percent' => $progressPercent,
                ':is_completed' => $isCompleted,
                ':completed_at' => $completedAt,
                ':last_accessed_at' => $now,
                ':updated_at' => $now,
                ':id' => $existing->id,
            ]);

            return new ActivityProgress(
                id: $existing->id,
                studentId: $studentId,
                activityType: ActivityProgress::TYPE_DOCUMENT,
                activityId: $contentItemId,
                lastPage: $clampedLastPage,
                totalPages: $effectiveTotalPages,
                pagesRead: $mergedPages,
                progressPercent: $progressPercent,
                isCompleted: (bool)$isCompleted,
                completedAt: $completedAt,
                lastAccessedAt: $now,
                createdAt: $existing->createdAt,
                updatedAt: $now
            );
        }

        $stmt = $this->pdo->prepare('
            INSERT INTO `learning_activity_progress` (
                `student_id`,
                `activity_type`,
                `activity_id`,
                `last_page`,
                `total_pages`,
                `pages_read_json`,
                `progress_percent`,
                `is_completed`,
                `completed_at`,
                `last_accessed_at`,
                `created_at`,
                `updated_at`
            ) VALUES (
                :student_id,
                :activity_type,
                :activity_id,
                :last_page,
                :total_pages,
                :pages_read_json,
                :progress_percent,
                :is_completed,
                :completed_at,
                :last_accessed_at,
                :created_at,
                :updated_at
            )
        ');

        $stmt->execute([
            ':student_id' => $studentId,
            ':activity_type' => ActivityProgress::TYPE_DOCUMENT,
            ':activity_id' => $contentItemId,
            ':last_page' => $clampedLastPage,
            ':total_pages' => $effectiveTotalPages,
            ':pages_read_json' => $pagesJson,
            ':progress_percent' => $progressPercent,
            ':is_completed' => $isCompleted,
            ':completed_at' => $completedAt,
            ':last_accessed_at' => $now,
            ':created_at' => $now,
            ':updated_at' => $now,
        ]);

        $id = (int)$this->pdo->lastInsertId();

        return new ActivityProgress(
            id: $id,
            studentId: $studentId,
            activityType: ActivityProgress::TYPE_DOCUMENT,
            activityId: $contentItemId,
            lastPage: $clampedLastPage,
            totalPages: $effectiveTotalPages,
            pagesRead: $mergedPages,
            progressPercent: $progressPercent,
            isCompleted: (bool)$isCompleted,
            completedAt: $completedAt,
            lastAccessedAt: $now,
            createdAt: $now,
            updatedAt: $now
        );
    }

    /**
     * Bounded batch retrieval of learning activity progress for an entire cohort.
     * Prevents N+1 database queries when calculating student/cohort progression.
     *
     * @param int[] $studentIds
     * @param array<int, array{activity_type?: string, type?: string, activity_id?: int, id?: int}> $activityTuples
     * @return array<int, array<string, ActivityProgress>> Map of [student_id]["activity_type:activity_id"] => ActivityProgress
     */
    public function getProgressMapForCohort(array $studentIds, array $activityTuples): array
    {
        $cleanStudentIds = array_values(array_unique(array_filter(array_map('intval', $studentIds), fn($id) => $id > 0)));
        if (empty($cleanStudentIds) || empty($activityTuples)) {
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

        $studentPlaceholders = implode(',', array_fill(0, count($cleanStudentIds), '?'));
        $params = $cleanStudentIds;

        $typeClauses = [];
        foreach ($byType as $type => $idsMap) {
            $ids = array_keys($idsMap);
            $idPlaceholders = implode(',', array_fill(0, count($ids), '?'));
            $typeClauses[] = "(`activity_type` = ? AND `activity_id` IN ({$idPlaceholders}))";
            $params[] = $type;
            foreach ($ids as $id) {
                $params[] = $id;
            }
        }

        $typeSql = implode(' OR ', $typeClauses);
        $sql = "
            SELECT * FROM `learning_activity_progress`
            WHERE `student_id` IN ({$studentPlaceholders})
              AND ({$typeSql})
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        $map = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $progress = ActivityProgress::fromArray($row);
            $key = "{$progress->activityType}:{$progress->activityId}";
            $map[$progress->studentId][$key] = $progress;
        }

        return $map;
    }

    /**
     * Delete all progress records for an activity (e.g. on activity deletion).
     */
    public function deleteProgressForActivity(string $activityType, int $activityId): int
    {
        try {
            $stmt = $this->pdo->prepare('
                DELETE FROM `learning_activity_progress`
                WHERE `activity_type` = :activity_type
                  AND `activity_id` = :activity_id
            ');
            $stmt->execute([
                ':activity_type' => $activityType,
                ':activity_id' => $activityId,
            ]);

            return $stmt->rowCount();
        } catch (\PDOException $e) {
            if (str_contains($e->getMessage(), 'no such table: learning_activity_progress') || str_contains($e->getMessage(), "doesn't exist")) {
                return 0;
            }
            throw $e;
        }
    }

    /**
     * Delete all progress records for multiple activities of the same type (e.g. document sections).
     *
     * @param string $activityType
     * @param int[] $activityIds
     */
    public function deleteProgressForActivities(string $activityType, array $activityIds): int
    {
        $cleanIds = array_values(array_unique(array_filter(array_map('intval', $activityIds), fn($id) => $id > 0)));
        if (empty($cleanIds)) {
            return 0;
        }

        try {
            $placeholders = implode(',', array_fill(0, count($cleanIds), '?'));
            $stmt = $this->pdo->prepare("
                DELETE FROM `learning_activity_progress`
                WHERE `activity_type` = ? AND `activity_id` IN ({$placeholders})
            ");
            $stmt->execute(array_merge([$activityType], $cleanIds));

            return $stmt->rowCount();
        } catch (\PDOException $e) {
            if (str_contains($e->getMessage(), 'no such table: learning_activity_progress') || str_contains($e->getMessage(), "doesn't exist")) {
                return 0;
            }
            throw $e;
        }
    }
}

