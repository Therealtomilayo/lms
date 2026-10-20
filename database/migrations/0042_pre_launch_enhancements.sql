-- Migration: 0042_pre_launch_enhancements.sql
-- Description: Pre-launch database enhancements for Claret LMS:
--   1. Expands announcements scope ENUM to include 'teachers'
--   2. Adds stage column to assessment_categories for EYFS, Primary, JSS, SSS grouping
--   3. Adds house column to students (Blue, Green, Red, Yellow)
--   4. Adds next_resumption_date to terms
--   5. Adds report_card_template to academic_levels ('grade_school', 'high_school')

SET FOREIGN_KEY_CHECKS = 0;

-- 1. announcements: Add 'teachers' to scope ENUM
ALTER TABLE `announcements` 
MODIFY COLUMN `scope` ENUM('school', 'teachers', 'class', 'class_subject') NOT NULL DEFAULT 'school';

-- 2. assessment_categories: Add stage column if not exists
SET @dbname = DATABASE();
SET @col_exists = (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'assessment_categories' AND COLUMN_NAME = 'stage'
);
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE `assessment_categories` ADD COLUMN `stage` ENUM(\'eyfs\', \'primary\', \'junior_secondary\', \'senior_secondary\') NULL DEFAULT NULL AFTER `academic_level_id`;', 
    'SELECT 1;'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 3. students: Add house column if not exists
SET @col_exists = (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'students' AND COLUMN_NAME = 'house'
);
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE `students` ADD COLUMN `house` VARCHAR(50) NULL DEFAULT \'Blue\' AFTER `use_school_bus`;', 
    'SELECT 1;'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 4. terms: Add next_resumption_date if not exists
SET @col_exists = (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'terms' AND COLUMN_NAME = 'next_resumption_date'
);
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE `terms` ADD COLUMN `next_resumption_date` DATE NULL DEFAULT NULL AFTER `grading_ends_at`;', 
    'SELECT 1;'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 5. academic_levels: Add report_card_template if not exists
SET @col_exists = (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'academic_levels' AND COLUMN_NAME = 'report_card_template'
);
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE `academic_levels` ADD COLUMN `report_card_template` ENUM(\'grade_school\', \'high_school\') NOT NULL DEFAULT \'grade_school\' AFTER `grading_scale_id`;', 
    'SELECT 1;'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 6. Configure High School levels (JSS 1-3, SS 1-3) to use 'high_school' report card template
UPDATE `academic_levels` 
SET `report_card_template` = 'high_school' 
WHERE `stage` IN ('junior_secondary', 'senior_secondary');

-- 7. Distribute student sports houses evenly (Blue, Green, Red, Yellow)
UPDATE `students` SET `house` = CASE (id % 4)
    WHEN 0 THEN 'Blue'
    WHEN 1 THEN 'Green'
    WHEN 2 THEN 'Red'
    WHEN 3 THEN 'Yellow'
END WHERE `house` IS NULL OR `house` = '' OR `house` = 'Blue';

-- 8. Seed default next resumption date on Term 4 (Autumn Term)
UPDATE `terms` 
SET `next_resumption_date` = '2027-01-11' 
WHERE `id` = 4 AND `next_resumption_date` IS NULL;

-- 9. Seed High School Assessment Categories for Term 4 (EUA 20%, TEST 5%, SPAT 5%, NOTE 5%, ASSIGNMENT 5%, ETA 60% = 100%)
INSERT IGNORE INTO `assessment_categories` (`session_id`, `term_id`, `stage`, `name`, `weight_percentage`, `max_points`, `created_at`, `updated_at`) VALUES
(2, 4, 'junior_secondary', 'EUA', 20.00, 20.00, NOW(), NOW()),
(2, 4, 'junior_secondary', 'TEST', 5.00, 5.00, NOW(), NOW()),
(2, 4, 'junior_secondary', 'SPAT / Project', 5.00, 5.00, NOW(), NOW()),
(2, 4, 'junior_secondary', 'Note', 5.00, 5.00, NOW(), NOW()),
(2, 4, 'junior_secondary', 'Assignment', 5.00, 5.00, NOW(), NOW()),
(2, 4, 'junior_secondary', 'ETA (Examination)', 60.00, 60.00, NOW(), NOW()),

(2, 4, 'senior_secondary', 'EUA', 20.00, 20.00, NOW(), NOW()),
(2, 4, 'senior_secondary', 'TEST', 5.00, 5.00, NOW(), NOW()),
(2, 4, 'senior_secondary', 'SPAT / Project', 5.00, 5.00, NOW(), NOW()),
(2, 4, 'senior_secondary', 'Note', 5.00, 5.00, NOW(), NOW()),
(2, 4, 'senior_secondary', 'Assignment', 5.00, 5.00, NOW(), NOW()),
(2, 4, 'senior_secondary', 'ETA (Examination)', 60.00, 60.00, NOW(), NOW());

-- Also set stage on Grade School categories
UPDATE `assessment_categories` 
SET `stage` = 'primary' 
WHERE `stage` IS NULL AND `session_id` = 2 AND `term_id` = 4;

SET FOREIGN_KEY_CHECKS = 1;
