-- ==========================================================================
-- CLARET LMS: ZERO-DATA-LOSS LIVE DELTA MIGRATION (0039)
-- Target: Live Production / Staging Database (MySQL 8.0+ / MariaDB 10.4+)
-- Safe: 100% additive, Idempotent, NO DROP, NO TRUNCATE, preserves all live data
-- Description: Enhances fee breakdown with item applicability, tracks student school bus usage, and seeds institutional fee categories
-- ==========================================================================

SET FOREIGN_KEY_CHECKS = 0;
SET @dbname = DATABASE();

-- --------------------------------------------------------------------------
-- MIGRATION: 0039_enhance_fee_breakdown_and_bus_tracking.sql
-- --------------------------------------------------------------------------

-- 1. Safely add `applicability` column to `fee_structure_items`
SET @col_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = @dbname 
      AND TABLE_NAME = 'fee_structure_items' 
      AND COLUMN_NAME = 'applicability'
);

SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE `fee_structure_items` ADD COLUMN `applicability` ENUM(\'all\', \'new_students_only\', \'bus_users_only\', \'optional\') NOT NULL DEFAULT \'all\' AFTER `is_required_for_result`;', 
    'SELECT 1;'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 2. Safely add `use_school_bus` column to `students`
SET @col_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = @dbname 
      AND TABLE_NAME = 'students' 
      AND COLUMN_NAME = 'use_school_bus'
);

SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE `students` ADD COLUMN `use_school_bus` TINYINT(1) NOT NULL DEFAULT 0 AFTER `current_class_id`;', 
    'SELECT 1;'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 3. Backfill `use_school_bus` on `students` from `admission_wards` for converted students
UPDATE `students` s
INNER JOIN `admission_wards` w ON w.converted_student_id = s.id
SET s.use_school_bus = w.use_school_bus
WHERE w.converted_student_id IS NOT NULL;

-- 4. Seed missing institutional fee categories (Books, Health, Bus) if not already present
INSERT IGNORE INTO `fee_categories` (`id`, `name`, `description`, `is_active`) VALUES
(8, 'Books & Learning Materials', 'Textbooks, workbooks, stationery, and instructional packs', 1),
(9, 'Health & Medical Services', 'Institutional infirmary care, first aid, and basic student health checks', 1),
(10, 'School Bus & Transportation', 'Dedicated campus bus shuttle and daily transit service', 1);

-- 5. Record migration 0039 in the migrations audit log
INSERT IGNORE INTO `migrations` (`migration`, `batch`, `applied_at`)
VALUES ('0039_enhance_fee_breakdown_and_bus_tracking.sql', (SELECT COALESCE(MAX(`batch`), 0) + 1 FROM `migrations` m2), NOW());

SET FOREIGN_KEY_CHECKS = 1;

-- ==========================================================================
-- LIVE DELTA MIGRATION (0039) COMPLETED SUCCESSFULLY
-- ==========================================================================
