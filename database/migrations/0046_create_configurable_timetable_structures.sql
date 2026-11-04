-- Migration: 0046_create_configurable_timetable_structures
-- Description: Dynamic timetable structures, bell schedule templates, stage mapping, and departmental slot enhancements

SET @dbname = DATABASE();

-- 1. Create timetable_structures table (Templates)
CREATE TABLE IF NOT EXISTS `timetable_structures` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `slug` VARCHAR(50) NOT NULL UNIQUE,
    `stage_key` VARCHAR(50) NOT NULL DEFAULT 'all',
    `is_default` TINYINT(1) NOT NULL DEFAULT 0,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `days_json` JSON NOT NULL,
    `description` TEXT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_tt_struct_stage` (`stage_key`),
    INDEX `idx_tt_struct_active` (`is_active`, `is_default`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Create timetable_structure_periods table (Bell Schedule Blocks)
CREATE TABLE IF NOT EXISTS `timetable_structure_periods` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `structure_id` BIGINT UNSIGNED NOT NULL,
    `period_number` INT UNSIGNED NULL COMMENT 'Null for breaks and assemblies, 1..N for instructional periods',
    `label` VARCHAR(100) NOT NULL COMMENT 'e.g. Period 1, Assembly and Roll Call, Break Time',
    `short_label` VARCHAR(50) NOT NULL COMMENT 'e.g. P1, Assembly, Break, Lunch, Activities',
    `type` ENUM('instructional', 'break', 'assembly', 'activity', 'sport') NOT NULL DEFAULT 'instructional',
    `start_time` TIME NOT NULL,
    `end_time` TIME NOT NULL,
    `sort_order` INT UNSIGNED NOT NULL DEFAULT 1,
    `spans_all_classes` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 if this block spans vertically across all classes in view',
    `day_rules_json` JSON NULL COMMENT 'Optional day-specific overrides, e.g. Wed Sport',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_tt_sp_struct_order` (`structure_id`, `sort_order`),
    INDEX `idx_tt_sp_times` (`structure_id`, `start_time`, `end_time`),
    CONSTRAINT `fk_tt_sp_structure` FOREIGN KEY (`structure_id`) REFERENCES `timetable_structures` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Enhance classes table with optional custom timetable structure override
SET @col_classes_struct = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = @dbname 
      AND TABLE_NAME = 'classes' 
      AND COLUMN_NAME = 'timetable_structure_id'
);
SET @sql_classes_struct = IF(@col_classes_struct = 0, 
    'ALTER TABLE `classes` ADD COLUMN `timetable_structure_id` BIGINT UNSIGNED NULL AFTER `section_arm`, ADD CONSTRAINT `fk_classes_timetable_structure` FOREIGN KEY (`timetable_structure_id`) REFERENCES `timetable_structures` (`id`) ON DELETE SET NULL;', 
    'SELECT 1;'
);
PREPARE stmt_classes_struct FROM @sql_classes_struct;
EXECUTE stmt_classes_struct;
DEALLOCATE PREPARE stmt_classes_struct;

-- 4. Enhance timetable_slots table for departmental electives and custom activities
-- Add class_id to timetable_slots if not exists and backfill from class_subjects
SET @col_slots_class = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = @dbname 
      AND TABLE_NAME = 'timetable_slots' 
      AND COLUMN_NAME = 'class_id'
);
SET @sql_slots_class = IF(@col_slots_class = 0, 
    'ALTER TABLE `timetable_slots` ADD COLUMN `class_id` BIGINT UNSIGNED NULL AFTER `term_id`, ADD CONSTRAINT `fk_slots_class_id` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE;', 
    'SELECT 1;'
);
PREPARE stmt_slots_class FROM @sql_slots_class;
EXECUTE stmt_slots_class;
DEALLOCATE PREPARE stmt_slots_class;

-- Backfill class_id from existing class_subjects
UPDATE `timetable_slots` ts 
JOIN `class_subjects` cs ON cs.id = ts.class_subject_id 
SET ts.class_id = cs.class_id 
WHERE ts.class_id IS NULL AND ts.class_subject_id IS NOT NULL;

-- Modify class_subject_id to be NULLABLE
ALTER TABLE `timetable_slots` MODIFY COLUMN `class_subject_id` BIGINT UNSIGNED NULL;

-- Add structure_period_id to timetable_slots if not exists
SET @col_slots_sp = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = @dbname 
      AND TABLE_NAME = 'timetable_slots' 
      AND COLUMN_NAME = 'structure_period_id'
);
SET @sql_slots_sp = IF(@col_slots_sp = 0, 
    'ALTER TABLE `timetable_slots` ADD COLUMN `structure_period_id` BIGINT UNSIGNED NULL AFTER `class_subject_id`, ADD CONSTRAINT `fk_slots_structure_period` FOREIGN KEY (`structure_period_id`) REFERENCES `timetable_structure_periods` (`id`) ON DELETE SET NULL;', 
    'SELECT 1;'
);
PREPARE stmt_slots_sp FROM @sql_slots_sp;
EXECUTE stmt_slots_sp;
DEALLOCATE PREPARE stmt_slots_sp;

-- Add department to timetable_slots if not exists
SET @col_slots_dept = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = @dbname 
      AND TABLE_NAME = 'timetable_slots' 
      AND COLUMN_NAME = 'department'
);
SET @sql_slots_dept = IF(@col_slots_dept = 0, 
    'ALTER TABLE `timetable_slots` ADD COLUMN `department` VARCHAR(50) NULL COMMENT \'e.g. science, commercial, arts, general\' AFTER `room`;', 
    'SELECT 1;'
);
PREPARE stmt_slots_dept FROM @sql_slots_dept;
EXECUTE stmt_slots_dept;
DEALLOCATE PREPARE stmt_slots_dept;

-- Add custom_title to timetable_slots if not exists
SET @col_slots_title = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = @dbname 
      AND TABLE_NAME = 'timetable_slots' 
      AND COLUMN_NAME = 'custom_title'
);
SET @sql_slots_title = IF(@col_slots_title = 0, 
    'ALTER TABLE `timetable_slots` ADD COLUMN `custom_title` VARCHAR(150) NULL COMMENT \'Used for non-subject activity titles e.g. SPORT or DRAMA\' AFTER `department`;', 
    'SELECT 1;'
);
PREPARE stmt_slots_title FROM @sql_slots_title;
EXECUTE stmt_slots_title;
DEALLOCATE PREPARE stmt_slots_title;

-- Add slot_type to timetable_slots if not exists
SET @col_slots_type = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = @dbname 
      AND TABLE_NAME = 'timetable_slots' 
      AND COLUMN_NAME = 'slot_type'
);
SET @sql_slots_type = IF(@col_slots_type = 0, 
    'ALTER TABLE `timetable_slots` ADD COLUMN `slot_type` ENUM(\'instructional\', \'break\', \'assembly\', \'activity\', \'sport\') NOT NULL DEFAULT \'instructional\' AFTER `custom_title`;', 
    'SELECT 1;'
);
PREPARE stmt_slots_type FROM @sql_slots_type;
EXECUTE stmt_slots_type;
DEALLOCATE PREPARE stmt_slots_type;

-- 5. Seed Default High School Timetable Structure (Matching provided timetable)
INSERT INTO `timetable_structures` (`id`, `name`, `slug`, `stage_key`, `is_default`, `is_active`, `days_json`, `description`)
VALUES
(1, 'High School Standard Bell Schedule', 'high-school-default', 'junior_secondary,senior_secondary', 1, 1, 
 '["mon", "tue", "wed", "thu", "fri"]', 
 'Official 9-period bell schedule with Assembly, 30m Break, 10m Short Break, and End-of-Day Activities.')
ON DUPLICATE KEY UPDATE 
    `name` = VALUES(`name`),
    `stage_key` = VALUES(`stage_key`),
    `days_json` = VALUES(`days_json`),
    `description` = VALUES(`description`);

-- 6. Seed Periods for High School Structure (Structure ID: 1)
DELETE FROM `timetable_structure_periods` WHERE `structure_id` = 1;

INSERT INTO `timetable_structure_periods` 
(`structure_id`, `period_number`, `label`, `short_label`, `type`, `start_time`, `end_time`, `sort_order`, `spans_all_classes`)
VALUES
(1, NULL, 'Assembly and Roll Call', 'Assembly', 'assembly', '07:50:00', '08:10:00', 1, 1),
(1, 1,    'Period 1',               'P1',       'instructional', '08:10:00', '08:50:00', 2, 0),
(1, 2,    'Period 2',               'P2',       'instructional', '08:50:00', '09:30:00', 3, 0),
(1, 3,    'Period 3',               'P3',       'instructional', '09:30:00', '10:10:00', 4, 0),
(1, 4,    'Period 4',               'P4',       'instructional', '10:10:00', '10:50:00', 5, 0),
(1, NULL, 'Break Time',             'Break',    'break',         '10:50:00', '11:20:00', 6, 1),
(1, 5,    'Period 5',               'P5',       'instructional', '11:20:00', '12:00:00', 7, 0),
(1, 6,    'Period 6',               'P6',       'instructional', '12:00:00', '12:40:00', 8, 0),
(1, NULL, 'Short Break',            'Recess',   'break',         '12:40:00', '12:50:00', 9, 1),
(1, 7,    'Period 7',               'P7',       'instructional', '12:50:00', '13:30:00', 10, 0),
(1, 8,    'Period 8',               'P8',       'instructional', '13:30:00', '14:10:00', 11, 0),
(1, 9,    'Period 9',               'P9',       'instructional', '14:10:00', '14:50:00', 12, 0),
(1, NULL, 'Activities / Club',      'Club',     'activity',      '14:50:00', '15:00:00', 13, 1);

-- 7. Seed Default Grade School (Primary) Structure (Structure ID: 2 - Matching Official Claret Grade School Timetable)
INSERT INTO `timetable_structures` (`id`, `name`, `slug`, `stage_key`, `is_default`, `is_active`, `days_json`, `description`)
VALUES
(2, 'Grade School (Primary) Bell Schedule', 'grade-school-default', 'primary', 0, 1, 
 '["mon", "tue", "wed", "thu", "fri"]', 
 'Official Grade School schedule with Class Assembly, Reading Drills, 7 x 40m instructional periods, 30m Lunch, Snack & Play breaks, and Transition.')
ON DUPLICATE KEY UPDATE 
    `name` = VALUES(`name`),
    `stage_key` = VALUES(`stage_key`),
    `days_json` = VALUES(`days_json`),
    `description` = VALUES(`description`);

DELETE FROM `timetable_structure_periods` WHERE `structure_id` = 2;

INSERT INTO `timetable_structure_periods` 
(`structure_id`, `period_number`, `label`, `short_label`, `type`, `start_time`, `end_time`, `sort_order`, `spans_all_classes`)
VALUES
(2, NULL, 'Class Assembly',           'Assembly', 'assembly',      '07:50:00', '08:20:00', 1,  1),
(2, NULL, 'Reading/Lit-Num Drills',   'Drills',   'activity',      '08:20:00', '08:30:00', 2,  1),
(2, 1,    'Period 1',                 'P1',       'instructional', '08:30:00', '09:10:00', 3,  0),
(2, 2,    'Period 2',                 'P2',       'instructional', '09:10:00', '09:50:00', 4,  0),
(2, 3,    'Period 3',                 'P3',       'instructional', '09:50:00', '10:30:00', 5,  0),
(2, NULL, 'Lunch Time',               'Lunch',    'break',         '10:30:00', '11:00:00', 6,  1),
(2, 4,    'Period 4',                 'P4',       'instructional', '11:00:00', '11:40:00', 7,  0),
(2, 5,    'Period 5',                 'P5',       'instructional', '11:40:00', '12:20:00', 8,  0),
(2, NULL, 'Snack Time',               'Snack',    'break',         '12:20:00', '12:30:00', 9,  1),
(2, NULL, 'Play Time',                'Play',     'break',         '12:30:00', '12:40:00', 10, 1),
(2, 6,    'Period 6',                 'P6',       'instructional', '12:40:00', '13:20:00', 11, 0),
(2, 7,    'Period 7',                 'P7',       'instructional', '13:20:00', '14:00:00', 12, 0),
(2, NULL, 'Transition / Dismissal',   'Dismiss',  'activity',      '14:00:00', '14:15:00', 13, 1);
