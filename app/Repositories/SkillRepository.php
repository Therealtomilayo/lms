<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Models\RemarkPreset;
use App\Models\Skill;
use App\Models\StudentSkillRating;
use PDO;

/**
 * Repository for Skills, Student Skill Ratings, and Remark Presets
 */
final class SkillRepository
{
    private readonly PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::getInstance();
    }

    /**
     * @return array<int, Skill>
     */
    public function getAllSkills(?string $category = null, ?string $status = null): array
    {
        $sql = 'SELECT * FROM `skills` WHERE 1=1';
        $params = [];

        if ($category !== null && $category !== '') {
            $sql .= ' AND `category` = :category';
            $params[':category'] = $category;
        }

        if ($status !== null && $status !== '') {
            $sql .= ' AND `status` = :status';
            $params[':status'] = $status;
        }

        $sql .= ' ORDER BY `category` ASC, `display_order` ASC, `name` ASC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(static fn(array $row) => Skill::fromArray($row), $rows);
    }

    public function getSkillById(int $id): ?Skill
    {
        $stmt = $this->pdo->prepare('SELECT * FROM `skills` WHERE `id` = :id');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? Skill::fromArray($row) : null;
    }

    public function createSkill(array $data): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO `skills` (`name`, `category`, `display_order`, `status`)
             VALUES (:name, :category, :display_order, :status)'
        );

        $stmt->execute([
            ':name' => (string)($data['name'] ?? ''),
            ':category' => (string)($data['category'] ?? 'psychomotor'),
            ':display_order' => (int)($data['display_order'] ?? 1),
            ':status' => (string)($data['status'] ?? 'active'),
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    public function updateSkill(int $id, array $data): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE `skills`
             SET `name` = :name,
                 `category` = :category,
                 `display_order` = :display_order,
                 `status` = :status
             WHERE `id` = :id'
        );

        return $stmt->execute([
            ':id' => $id,
            ':name' => (string)($data['name'] ?? ''),
            ':category' => (string)($data['category'] ?? 'psychomotor'),
            ':display_order' => (int)($data['display_order'] ?? 1),
            ':status' => (string)($data['status'] ?? 'active'),
        ]);
    }

    public function deleteSkill(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM `skills` WHERE `id` = :id');
        return $stmt->execute([':id' => $id]);
    }

    /**
     * Get all skill ratings for a specific student in a term, joined with skill metadata
     *
     * @return array<int, StudentSkillRating>
     */
    public function getStudentRatings(int $studentId, int $termId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT r.*, s.name AS skill_name, s.category AS skill_category, s.display_order, s.status AS skill_status
             FROM `student_skill_ratings` r
             INNER JOIN `skills` s ON s.id = r.skill_id
             WHERE r.student_id = :student_id AND r.term_id = :term_id
             ORDER BY s.category ASC, s.display_order ASC, s.name ASC'
        );
        $stmt->execute([
            ':student_id' => $studentId,
            ':term_id' => $termId,
        ]);

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(static fn(array $row) => StudentSkillRating::fromArray($row), $rows);
    }

    /**
     * Get ratings matrix for an entire class: returns [student_id => [skill_id => rating_int]]
     *
     * @return array<int, array<int, int>>
     */
    public function getClassRatingsMatrix(int $classId, int $termId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT r.student_id, r.skill_id, r.rating
             FROM `student_skill_ratings` r
             INNER JOIN `students` st ON st.id = r.student_id
             WHERE st.current_class_id = :class_id AND r.term_id = :term_id'
        );
        $stmt->execute([
            ':class_id' => $classId,
            ':term_id' => $termId,
        ]);

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $matrix = [];
        foreach ($rows as $row) {
            $studentId = (int)$row['student_id'];
            $skillId = (int)$row['skill_id'];
            $rating = (int)$row['rating'];
            $matrix[$studentId][$skillId] = $rating;
        }

        return $matrix;
    }

    public function saveStudentRating(int $studentId, int $termId, int $skillId, int $rating, ?int $recordedBy = null): bool
    {
        $rating = max(1, min(5, $rating));
        $driver = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

        $sql = $driver === 'sqlite'
            ? 'INSERT INTO `student_skill_ratings` (`student_id`, `term_id`, `skill_id`, `rating`, `recorded_by`)
               VALUES (:student_id, :term_id, :skill_id, :rating, :recorded_by)
               ON CONFLICT(`student_id`, `term_id`, `skill_id`) DO UPDATE SET
                   `rating` = excluded.`rating`,
                   `recorded_by` = excluded.`recorded_by`,
                   `updated_at` = CURRENT_TIMESTAMP'
            : 'INSERT INTO `student_skill_ratings` (`student_id`, `term_id`, `skill_id`, `rating`, `recorded_by`)
               VALUES (:student_id, :term_id, :skill_id, :rating, :recorded_by)
               ON DUPLICATE KEY UPDATE `rating` = VALUES(`rating`), `recorded_by` = VALUES(`recorded_by`), `updated_at` = CURRENT_TIMESTAMP';

        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute([
            ':student_id' => $studentId,
            ':term_id' => $termId,
            ':skill_id' => $skillId,
            ':rating' => $rating,
            ':recorded_by' => $recordedBy,
        ]);
    }

    /**
     * Batch save skill ratings
     * Format of $ratings: [student_id => [skill_id => rating_int]]
     */
    public function batchSaveRatings(int $termId, array $ratings, ?int $recordedBy = null): bool
    {
        if (empty($ratings)) {
            return true;
        }

        $driver = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        $sql = $driver === 'sqlite'
            ? 'INSERT INTO `student_skill_ratings` (`student_id`, `term_id`, `skill_id`, `rating`, `recorded_by`)
               VALUES (:student_id, :term_id, :skill_id, :rating, :recorded_by)
               ON CONFLICT(`student_id`, `term_id`, `skill_id`) DO UPDATE SET
                   `rating` = excluded.`rating`,
                   `recorded_by` = excluded.`recorded_by`,
                   `updated_at` = CURRENT_TIMESTAMP'
            : 'INSERT INTO `student_skill_ratings` (`student_id`, `term_id`, `skill_id`, `rating`, `recorded_by`)
               VALUES (:student_id, :term_id, :skill_id, :rating, :recorded_by)
               ON DUPLICATE KEY UPDATE `rating` = VALUES(`rating`), `recorded_by` = VALUES(`recorded_by`), `updated_at` = CURRENT_TIMESTAMP';

        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare($sql);

            foreach ($ratings as $studentId => $skills) {
                if (!is_array($skills)) {
                    continue;
                }
                foreach ($skills as $skillId => $val) {
                    if ($val === '' || $val === null) {
                        continue;
                    }
                    $ratingVal = max(1, min(5, (int)$val));
                    $stmt->execute([
                        ':student_id' => (int)$studentId,
                        ':term_id' => $termId,
                        ':skill_id' => (int)$skillId,
                        ':rating' => $ratingVal,
                        ':recorded_by' => $recordedBy,
                    ]);
                }
            }

            $this->pdo->commit();
            return true;
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * @return array<int, RemarkPreset>
     */
    public function getRemarkPresets(?string $type = null, bool $activeOnly = true): array
    {
        $sql = 'SELECT * FROM `remark_presets` WHERE 1=1';
        $params = [];

        if ($type !== null && $type !== '') {
            $sql .= ' AND `type` = :type';
            $params[':type'] = $type;
        }

        if ($activeOnly) {
            $sql .= ' AND `is_active` = 1';
        }

        $sql .= ' ORDER BY `type` ASC, `category` ASC, `id` ASC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(static fn(array $row) => RemarkPreset::fromArray($row), $rows);
    }

    public function getRemarkPresetById(int $id): ?RemarkPreset
    {
        $stmt = $this->pdo->prepare('SELECT * FROM `remark_presets` WHERE `id` = :id');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? RemarkPreset::fromArray($row) : null;
    }

    public function createRemarkPreset(array $data): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO `remark_presets` (`type`, `category`, `text`, `is_active`)
             VALUES (:type, :category, :text, :is_active)'
        );

        $stmt->execute([
            ':type' => (string)($data['type'] ?? 'teacher'),
            ':category' => (string)($data['category'] ?? 'general'),
            ':text' => trim((string)($data['text'] ?? '')),
            ':is_active' => !empty($data['is_active']) ? 1 : 0,
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    public function updateRemarkPreset(int $id, array $data): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE `remark_presets`
             SET `type` = :type,
                 `category` = :category,
                 `text` = :text,
                 `is_active` = :is_active
             WHERE `id` = :id'
        );

        return $stmt->execute([
            ':id' => $id,
            ':type' => (string)($data['type'] ?? 'teacher'),
            ':category' => (string)($data['category'] ?? 'general'),
            ':text' => trim((string)($data['text'] ?? '')),
            ':is_active' => !empty($data['is_active']) ? 1 : 0,
        ]);
    }

    public function deleteRemarkPreset(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM `remark_presets` WHERE `id` = :id');
        return $stmt->execute([':id' => $id]);
    }
}
