-- Migration: 0045_seed_high_school_categories_all_terms.sql
-- Description: Seed High School (Junior & Senior Secondary) and Primary assessment categories across all terms (Terms 4, 5, 6)

SET NAMES utf8mb4;

-- 0. Ensure assessment_categories columns exist before querying or inserting
SET @dbname = COALESCE(DATABASE(), SCHEMA());

SET @col_exists = (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'assessment_categories' AND COLUMN_NAME = 'academic_level_id'
);
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE `assessment_categories` ADD COLUMN `academic_level_id` BIGINT UNSIGNED NULL DEFAULT NULL;', 
    'SELECT 1;'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists = (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'assessment_categories' AND COLUMN_NAME = 'stage'
);
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE `assessment_categories` ADD COLUMN `stage` ENUM(\'eyfs\', \'primary\', \'junior_secondary\', \'senior_secondary\') NULL DEFAULT NULL, ADD INDEX `idx_ac_stage` (`stage`);', 
    'SELECT 1;'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Seed Junior Secondary for Terms 5 and 6 (Term 4 already exists)
INSERT INTO `assessment_categories` (`session_id`, `term_id`, `academic_level_id`, `stage`, `name`, `weight_percentage`, `max_points`, `created_at`, `updated_at`)
SELECT 2, t.id, NULL, 'junior_secondary', c.name, c.weight_percentage, c.max_points, NOW(), NOW()
FROM `terms` t
CROSS JOIN (
    SELECT 'EUA' AS name, 20.00 AS weight_percentage, 20.00 AS max_points
    UNION ALL SELECT 'TEST', 5.00, 5.00
    UNION ALL SELECT 'SPAT / Project', 5.00, 5.00
    UNION ALL SELECT 'Note', 5.00, 5.00
    UNION ALL SELECT 'Assignment', 5.00, 5.00
    UNION ALL SELECT 'ETA (Examination)', 60.00, 60.00
) c
WHERE t.id IN (5, 6)
  AND NOT EXISTS (
      SELECT 1 FROM `assessment_categories` existing 
      WHERE existing.term_id = t.id 
        AND existing.stage = 'junior_secondary' 
        AND existing.name = c.name
  );

-- Seed Senior Secondary for Terms 5 and 6 (Term 4 already exists)
INSERT INTO `assessment_categories` (`session_id`, `term_id`, `academic_level_id`, `stage`, `name`, `weight_percentage`, `max_points`, `created_at`, `updated_at`)
SELECT 2, t.id, NULL, 'senior_secondary', c.name, c.weight_percentage, c.max_points, NOW(), NOW()
FROM `terms` t
CROSS JOIN (
    SELECT 'EUA' AS name, 20.00 AS weight_percentage, 20.00 AS max_points
    UNION ALL SELECT 'TEST', 5.00, 5.00
    UNION ALL SELECT 'SPAT / Project', 5.00, 5.00
    UNION ALL SELECT 'Note', 5.00, 5.00
    UNION ALL SELECT 'Assignment', 5.00, 5.00
    UNION ALL SELECT 'ETA (Examination)', 60.00, 60.00
) c
WHERE t.id IN (5, 6)
  AND NOT EXISTS (
      SELECT 1 FROM `assessment_categories` existing 
      WHERE existing.term_id = t.id 
        AND existing.stage = 'senior_secondary' 
        AND existing.name = c.name
  );

-- Seed Primary for Terms 5 and 6 (Term 4 already exists)
INSERT INTO `assessment_categories` (`session_id`, `term_id`, `academic_level_id`, `stage`, `name`, `weight_percentage`, `max_points`, `created_at`, `updated_at`)
SELECT 2, t.id, NULL, 'primary', c.name, c.weight_percentage, c.max_points, NOW(), NOW()
FROM `terms` t
CROSS JOIN (
    SELECT 'Class Activity' AS name, 20.00 AS weight_percentage, 20.00 AS max_points
    UNION ALL SELECT 'End of Unit Test', 20.00, 20.00
    UNION ALL SELECT 'SPAT', 10.00, 10.00
    UNION ALL SELECT 'Home Fun', 10.00, 10.00
    UNION ALL SELECT 'End of Term Test', 40.00, 40.00
) c
WHERE t.id IN (5, 6)
  AND NOT EXISTS (
      SELECT 1 FROM `assessment_categories` existing 
      WHERE existing.term_id = t.id 
        AND existing.stage = 'primary' 
        AND existing.name = c.name
  );
