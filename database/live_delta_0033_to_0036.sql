-- ==========================================================================
-- CLARET LMS: ZERO-DATA-LOSS DELTA MIGRATION (0033 -> 0036)
-- Target: Live Production Database
-- Generated: 2026-09-26
-- Safe: 100% additive, Idempotent, NO DROP, NO TRUNCATE, preserves all live records
-- ==========================================================================

SET FOREIGN_KEY_CHECKS = 0;

-- --------------------------------------------------------------------------
-- MIGRATION: 0033_add_demographics_to_students_and_wards.sql
-- --------------------------------------------------------------------------

-- 1. Safely add demographics columns to `students` table if not already existing
SET @dbname = DATABASE();

SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'students' AND COLUMN_NAME = 'state_of_origin');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE `students` ADD COLUMN `state_of_origin` VARCHAR(100) NULL AFTER `gender`;', 'SELECT 1;');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'students' AND COLUMN_NAME = 'lga');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE `students` ADD COLUMN `lga` VARCHAR(100) NULL AFTER `state_of_origin`;', 'SELECT 1;');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'students' AND COLUMN_NAME = 'nationality');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE `students` ADD COLUMN `nationality` VARCHAR(100) NOT NULL DEFAULT \'Nigerian\' AFTER `lga`;', 'SELECT 1;');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'students' AND COLUMN_NAME = 'religion');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE `students` ADD COLUMN `religion` VARCHAR(50) NULL AFTER `nationality`;', 'SELECT 1;');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'students' AND COLUMN_NAME = 'admission_date');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE `students` ADD COLUMN `admission_date` DATE NULL AFTER `religion`;', 'SELECT 1;');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 2. Safely add demographics columns to `admission_wards` table if not already existing
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'admission_wards' AND COLUMN_NAME = 'state_of_origin');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE `admission_wards` ADD COLUMN `state_of_origin` VARCHAR(100) NULL AFTER `gender`;', 'SELECT 1;');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'admission_wards' AND COLUMN_NAME = 'lga');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE `admission_wards` ADD COLUMN `lga` VARCHAR(100) NULL AFTER `state_of_origin`;', 'SELECT 1;');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'admission_wards' AND COLUMN_NAME = 'nationality');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE `admission_wards` ADD COLUMN `nationality` VARCHAR(100) NOT NULL DEFAULT \'Nigerian\' AFTER `lga`;', 'SELECT 1;');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'admission_wards' AND COLUMN_NAME = 'religion');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE `admission_wards` ADD COLUMN `religion` VARCHAR(50) NULL AFTER `nationality`;', 'SELECT 1;');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 3. Synchronize assessment categories max_points to match weight_percentage
UPDATE `assessment_categories`
SET `max_points` = `weight_percentage`
WHERE `weight_percentage` > 0;

-- 4. Insert default institutional system settings if missing
INSERT IGNORE INTO `system_settings` (`setting_key`, `setting_value`, `is_secret`, `updated_at`)
VALUES
    ('school_name', 'Claret International School', 0, NOW()),
    ('school_address', 'Claret Street, Nigeria', 0, NOW()),
    ('school_email', 'admissions@claretschools.xo.je', 0, NOW()),
    ('school_phone', '+234 800 000 0000', 0, NOW()),
    ('current_term_id', '1', 0, NOW()),
    ('current_session_id', '1', 0, NOW());

INSERT IGNORE INTO `migrations` (`migration`, `batch`, `applied_at`)
VALUES ('0033_add_demographics_to_students_and_wards.sql', (SELECT COALESCE(MAX(`batch`), 0) + 1 FROM `migrations` m2), NOW());


-- --------------------------------------------------------------------------
-- MIGRATION: 0034_add_stage_to_grading_scales_and_academic_stages_table.sql
-- --------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `academic_stages` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `stage_key` VARCHAR(50) NOT NULL UNIQUE,
    `name` VARCHAR(100) NOT NULL,
    `rank_order` INT NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `academic_stages` (`stage_key`, `name`, `rank_order`, `created_at`, `updated_at`)
VALUES
    ('early_years', 'Early Years', 1, NOW(), NOW()),
    ('primary', 'Primary / Elementary', 2, NOW(), NOW()),
    ('junior_secondary', 'Junior Secondary (JSS)', 3, NOW(), NOW()),
    ('senior_secondary', 'Senior Secondary (SSS)', 4, NOW(), NOW());

SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'grading_scales' AND COLUMN_NAME = 'stage');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE `grading_scales` ADD COLUMN `stage` ENUM(\'early_years\', \'primary\', \'junior_secondary\', \'senior_secondary\') NULL AFTER `description`;', 'SELECT 1;');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @preparedStatement = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'grading_scales' AND COLUMN_NAME = 'is_default') = 0,
    'ALTER TABLE `grading_scales` ADD COLUMN `is_default` TINYINT(1) NOT NULL DEFAULT 0 AFTER `stage`;',
    'SELECT 1;'
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

SET @preparedStatement = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'academic_levels' AND COLUMN_NAME = 'stage') = 0,
    'ALTER TABLE `academic_levels` ADD COLUMN `stage` ENUM(\'early_years\', \'primary\', \'junior_secondary\', \'senior_secondary\') NULL AFTER `name`;',
    'SELECT 1;'
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Update existing grading scales stage mappings
UPDATE `grading_scales` SET `stage` = 'senior_secondary' WHERE `name` LIKE '%Secondary%' AND `name` LIKE '%WAEC%' AND (`stage` IS NULL OR `stage` = '');
UPDATE `grading_scales` SET `stage` = 'junior_secondary' WHERE `name` LIKE '%Junior Secondary%' AND (`stage` IS NULL OR `stage` = '');

-- Update Junior Secondary academic levels to use Stage Default scale instead of hardcoded Scale 3 (WAEC)
UPDATE `academic_levels` SET `grading_scale_id` = NULL WHERE `stage` = 'junior_secondary' AND `grading_scale_id` = 3;

INSERT IGNORE INTO `migrations` (`migration`, `batch`, `applied_at`)
VALUES ('0034_add_stage_to_grading_scales_and_academic_stages_table.sql', (SELECT COALESCE(MAX(`batch`), 0) + 1 FROM `migrations` m2), NOW());


-- --------------------------------------------------------------------------
-- MIGRATION: 0035_add_documents_to_admission_wards.sql
-- --------------------------------------------------------------------------

SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'admission_wards' AND COLUMN_NAME = 'parent_passport_file_id');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE `admission_wards` ADD COLUMN `parent_passport_file_id` BIGINT UNSIGNED NULL AFTER `previous_report_file_id`, ADD CONSTRAINT `fk_adm_ward_parent_passport` FOREIGN KEY (`parent_passport_file_id`) REFERENCES `files` (`id`) ON DELETE SET NULL;', 'SELECT 1;');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'admission_wards' AND COLUMN_NAME = 'authorized_picker_passport_file_id');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE `admission_wards` ADD COLUMN `authorized_picker_passport_file_id` BIGINT UNSIGNED NULL AFTER `parent_passport_file_id`, ADD CONSTRAINT `fk_adm_ward_picker_passport` FOREIGN KEY (`authorized_picker_passport_file_id`) REFERENCES `files` (`id`) ON DELETE SET NULL;', 'SELECT 1;');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'admission_wards' AND COLUMN_NAME = 'immunization_record_file_id');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE `admission_wards` ADD COLUMN `immunization_record_file_id` BIGINT UNSIGNED NULL AFTER `authorized_picker_passport_file_id`, ADD CONSTRAINT `fk_adm_ward_immunization` FOREIGN KEY (`immunization_record_file_id`) REFERENCES `files` (`id`) ON DELETE SET NULL;', 'SELECT 1;');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

INSERT IGNORE INTO `migrations` (`migration`, `batch`, `applied_at`)
VALUES ('0035_add_documents_to_admission_wards.sql', (SELECT COALESCE(MAX(`batch`), 0) + 1 FROM `migrations` m2), NOW());


-- --------------------------------------------------------------------------
-- MIGRATION: 0036_add_status_and_decision_note_to_admission_wards.sql
-- --------------------------------------------------------------------------

SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'admission_wards' AND COLUMN_NAME = 'status');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE `admission_wards` ADD COLUMN `status` ENUM(\'pending\', \'approved\', \'rejected\') NOT NULL DEFAULT \'pending\' AFTER `payment_status`;', 'SELECT 1;');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'admission_wards' AND COLUMN_NAME = 'decision_note');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE `admission_wards` ADD COLUMN `decision_note` TEXT NULL AFTER `status`;', 'SELECT 1;');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Backfill converted wards as approved
UPDATE `admission_wards`
SET `status` = 'approved'
WHERE `converted_student_id` IS NOT NULL;

INSERT IGNORE INTO `migrations` (`migration`, `batch`, `applied_at`)
VALUES ('0036_add_status_and_decision_note_to_admission_wards.sql', (SELECT COALESCE(MAX(`batch`), 0) + 1 FROM `migrations` m2), NOW());

SET FOREIGN_KEY_CHECKS = 1;
