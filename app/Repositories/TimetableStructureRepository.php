<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Models\TimetableStructure;
use App\Models\TimetableStructurePeriod;
use PDO;

/**
 * Timetable Structure Repository
 * Database access for configurable timetable bell schedules and period definitions.
 */
class TimetableStructureRepository
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::getConnection();
    }

    /**
     * Find structure by ID including loaded periods.
     */
    public function findById(int $id): ?TimetableStructure
    {
        $stmt = $this->db->prepare("SELECT * FROM timetable_structures WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        $periods = $this->findPeriods($id);
        return TimetableStructure::fromArray($row, $periods);
    }

    /**
     * Find structure by slug.
     */
    public function findBySlug(string $slug): ?TimetableStructure
    {
        $stmt = $this->db->prepare("SELECT * FROM timetable_structures WHERE slug = :slug LIMIT 1");
        $stmt->execute([':slug' => $slug]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        $periods = $this->findPeriods((int)$row['id']);
        return TimetableStructure::fromArray($row, $periods);
    }

    /**
     * Get all active timetable structures.
     *
     * @return array<int, TimetableStructure>
     */
    public function findAll(bool $activeOnly = true): array
    {
        try {
            $sql = "SELECT * FROM timetable_structures";
            if ($activeOnly) {
                $sql .= " WHERE is_active = 1";
            }
            $sql .= " ORDER BY is_default DESC, id ASC";

            $stmt = $this->db->query($sql);
            $structures = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $periods = $this->findPeriods((int)$row['id']);
                $structures[] = TimetableStructure::fromArray($row, $periods);
            }

            return $structures;
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * Find the system default structure.
     */
    public function findDefault(): ?TimetableStructure
    {
        try {
            $stmt = $this->db->prepare("SELECT * FROM timetable_structures WHERE is_default = 1 AND is_active = 1 LIMIT 1");
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$row) {
                $fallback = $this->findAll();
                return !empty($fallback) ? $fallback[0] : null;
            }

            $periods = $this->findPeriods((int)$row['id']);
            return TimetableStructure::fromArray($row, $periods);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Find structure matching an academic stage key (e.g. junior_secondary, senior_secondary, primary, eyfs).
     */
    public function findForStage(string $stageKey): ?TimetableStructure
    {
        try {
            $clean = strtolower(trim($stageKey));
            $all = $this->findAll();

            foreach ($all as $structure) {
                if ($structure->matchesStage($clean)) {
                    return $structure;
                }
            }
        } catch (\Throwable) {
            // Ignore in SQLite test environments without timetable_structures table
        }

        return $this->findDefault();
    }

    /**
     * Resolve the active timetable structure for a specific class cohort.
     * Hierarchy: Class-explicit structure -> Academic stage structure -> System default.
     */
    public function findForClass(int $classId): ?TimetableStructure
    {
        try {
            $stmt = $this->db->prepare("
                SELECT c.timetable_structure_id, al.stage AS stage_key
                FROM classes c
                LEFT JOIN academic_levels al ON al.id = c.academic_level_id
                WHERE c.id = :class_id
                LIMIT 1
            ");
            $stmt->execute([':class_id' => $classId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($row && !empty($row['timetable_structure_id'])) {
                $custom = $this->findById((int)$row['timetable_structure_id']);
                if ($custom && $custom->isActive) {
                    return $custom;
                }
            }

            if ($row && !empty($row['stage_key'])) {
                $stageStructure = $this->findForStage((string)$row['stage_key']);
                if ($stageStructure) {
                    return $stageStructure;
                }
            }
        } catch (\Throwable) {
            // Fallback for in-memory SQLite test databases without timetable_structure_id column
            try {
                $stmt = $this->db->prepare("
                    SELECT al.stage AS stage_key
                    FROM classes c
                    LEFT JOIN academic_levels al ON al.id = c.academic_level_id
                    WHERE c.id = :class_id
                    LIMIT 1
                ");
                $stmt->execute([':class_id' => $classId]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($row && !empty($row['stage_key'])) {
                    $stageStructure = $this->findForStage((string)$row['stage_key']);
                    if ($stageStructure) {
                        return $stageStructure;
                    }
                }
            } catch (\Throwable) {
                // Table might not exist or stage might not be present
            }
        }

        return $this->findDefault();
    }

    /**
     * Alias for findForClass.
     */
    public function findByClassId(int $classId): ?TimetableStructure
    {
        return $this->findForClass($classId);
    }

    /**
     * Alias for findForStage.
     */
    public function findByStage(string $stageKey): ?TimetableStructure
    {
        return $this->findForStage($stageKey);
    }

    /**
     * Get all periods for a structure in chronological / sort order.
     *
     * @return array<int, TimetableStructurePeriod>
     */
    public function findPeriods(int $structureId): array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM timetable_structure_periods
            WHERE structure_id = :structure_id
            ORDER BY sort_order ASC, start_time ASC
        ");
        $stmt->execute([':structure_id' => $structureId]);

        $periods = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $periods[] = TimetableStructurePeriod::fromArray($row);
        }

        return $periods;
    }

    /**
     * Create a new timetable structure.
     */
    public function create(array $data): TimetableStructure
    {
        $stmt = $this->db->prepare("
            INSERT INTO timetable_structures (
                name, slug, stage_key, is_default, is_active, days_json, description, created_at, updated_at
            ) VALUES (
                :name, :slug, :stage_key, :is_default, :is_active, :days_json, :description, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
            )
        ");

        $daysJson = isset($data['days']) ? json_encode($data['days']) : (isset($data['days_json']) ? (string)$data['days_json'] : '["mon", "tue", "wed", "thu", "fri"]');

        $stmt->execute([
            ':name' => (string)($data['name'] ?? 'New Schedule'),
            ':slug' => (string)($data['slug'] ?? ('schedule-' . time())),
            ':stage_key' => (string)($data['stage_key'] ?? 'all'),
            ':is_default' => !empty($data['is_default']) ? 1 : 0,
            ':is_active' => isset($data['is_active']) ? (int)(bool)$data['is_active'] : 1,
            ':days_json' => $daysJson,
            ':description' => isset($data['description']) ? (string)$data['description'] : null,
        ]);

        $id = (int)$this->db->lastInsertId();
        if (!empty($data['periods']) && is_array($data['periods'])) {
            $this->savePeriods($id, $data['periods']);
        }

        return $this->findById($id) ?? throw new \RuntimeException("Failed to create timetable structure #{$id}");
    }

    /**
     * Update an existing timetable structure.
     */
    public function update(int $id, array $data): TimetableStructure
    {
        $existing = $this->findById($id);
        if (!$existing) {
            throw new \InvalidArgumentException("Timetable structure #{$id} does not exist.");
        }

        $name = (string)($data['name'] ?? $existing->name);
        $slug = (string)($data['slug'] ?? $existing->slug);
        $stageKey = (string)($data['stage_key'] ?? $existing->stageKey);
        $isDefault = isset($data['is_default']) ? (!empty($data['is_default']) ? 1 : 0) : ($existing->isDefault ? 1 : 0);
        $isActive = isset($data['is_active']) ? (!empty($data['is_active']) ? 1 : 0) : ($existing->isActive ? 1 : 0);
        $description = array_key_exists('description', $data) ? $data['description'] : $existing->description;

        $daysJson = isset($data['days']) 
            ? json_encode($data['days']) 
            : (isset($data['days_json']) ? (string)$data['days_json'] : json_encode($existing->days));

        $stmt = $this->db->prepare("
            UPDATE timetable_structures SET
                name = :name,
                slug = :slug,
                stage_key = :stage_key,
                is_default = :is_default,
                is_active = :is_active,
                days_json = :days_json,
                description = :description,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = :id
        ");

        $stmt->execute([
            ':name' => $name,
            ':slug' => $slug,
            ':stage_key' => $stageKey,
            ':is_default' => $isDefault,
            ':is_active' => $isActive,
            ':days_json' => $daysJson,
            ':description' => $description,
            ':id' => $id,
        ]);

        if (isset($data['periods']) && is_array($data['periods'])) {
            $this->savePeriods($id, $data['periods']);
        }

        return $this->findById($id) ?? throw new \RuntimeException("Failed to retrieve updated timetable structure #{$id}");
    }

    /**
     * Replace periods for a structure with a new set of period definitions.
     */
    public function savePeriods(int $structureId, array $periodsData): void
    {
        $this->db->prepare("DELETE FROM timetable_structure_periods WHERE structure_id = :id")->execute([':id' => $structureId]);

        $insertStmt = $this->db->prepare("
            INSERT INTO timetable_structure_periods (
                structure_id, period_number, label, short_label, type, start_time, end_time, sort_order, spans_all_classes, day_rules_json, created_at, updated_at
            ) VALUES (
                :structure_id, :period_number, :label, :short_label, :type, :start_time, :end_time, :sort_order, :spans_all_classes, :day_rules_json, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
            )
        ");

        $sortOrder = 1;
        foreach ($periodsData as $p) {
            $startTime = (string)($p['start_time'] ?? ($p['start'] ?? '08:00:00'));
            if (strlen($startTime) === 5) $startTime .= ':00';
            $endTime = (string)($p['end_time'] ?? ($p['end'] ?? '08:45:00'));
            if (strlen($endTime) === 5) $endTime .= ':00';

            $periodNumber = isset($p['period_number']) && $p['period_number'] !== null && $p['period_number'] !== '' ? (int)$p['period_number'] : null;
            $type = (string)($p['type'] ?? 'instructional');

            $insertStmt->execute([
                ':structure_id' => $structureId,
                ':period_number' => $periodNumber,
                ':label' => (string)($p['label'] ?? ($p['name'] ?? 'Period')),
                ':short_label' => (string)($p['short_label'] ?? ($p['short_name'] ?? 'P')),
                ':type' => $type,
                ':start_time' => $startTime,
                ':end_time' => $endTime,
                ':sort_order' => (int)($p['sort_order'] ?? $sortOrder),
                ':spans_all_classes' => !empty($p['spans_all_classes']) ? 1 : 0,
                ':day_rules_json' => !empty($p['day_rules']) ? json_encode($p['day_rules']) : null,
            ]);
            $sortOrder++;
        }
    }

    /**
     * Delete a structure and its periods.
     */
    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM timetable_structures WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }
}
