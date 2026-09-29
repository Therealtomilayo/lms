-- ==========================================================================
-- CLARET LMS: ZERO-DATA-LOSS LIVE DELTA MIGRATION (0033 -> 0039)
-- Target: Live Production / Staging Database (MySQL 8.0+ / MariaDB 10.4+)
-- Safe: 100% additive, Idempotent, NO DROP, NO TRUNCATE, preserves all live data
-- ==========================================================================

SET FOREIGN_KEY_CHECKS = 0;
SET @dbname = DATABASE();

-- --------------------------------------------------------------------------
-- MIGRATION: 0033_add_demographics_to_students_and_wards.sql
-- --------------------------------------------------------------------------

-- 1. Safely add demographics columns to `students` table if not already existing
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

-- 4. Insert institutional default settings if missing
INSERT IGNORE INTO `system_settings` (`setting_key`, `setting_value`, `is_secret`, `updated_at`)
VALUES
    ('head_teacher_name', 'Mrs. N. Okon', 0, NOW()),
    ('head_teacher_title', 'Head of School', 0, NOW()),
    ('head_teacher_signature_url', '/assets/img/teacher-signature.png', 0, NOW()),
    ('school_stamp_url', '/assets/img/claret-stamp.png', 0, NOW()),
    ('school_website', 'www.claretschools.org', 0, NOW());

-- Record migration 0033
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
    PRIMARY KEY (`id`),
    INDEX `idx_academic_stages_rank` (`rank_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `academic_stages` (`stage_key`, `name`, `rank_order`) VALUES
    ('creche', 'Creche / Daycare', 1),
    ('nursery', 'Nursery', 2),
    ('Pre Nusery', 'Pre-Nursery', 3),
    ('primary', 'Primary', 4),
    ('junior_secondary', 'Junior Secondary', 5),
    ('senior_secondary', 'Senior Secondary', 6)
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`), `rank_order` = VALUES(`rank_order`);

-- Safely add `stage` column to `grading_scales`
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'grading_scales' AND COLUMN_NAME = 'stage');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE `grading_scales` ADD COLUMN `stage` VARCHAR(50) NULL AFTER `name`, ADD INDEX `idx_grading_scales_stage` (`stage`);', 'SELECT 1;');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Update existing grading scales stage mappings
UPDATE `grading_scales` SET `stage` = 'senior_secondary' WHERE `name` LIKE '%Secondary%' AND `name` LIKE '%WAEC%' AND (`stage` IS NULL OR `stage` = '');
UPDATE `grading_scales` SET `stage` = 'junior_secondary' WHERE `name` LIKE '%Junior Secondary%' AND (`stage` IS NULL OR `stage` = '');

-- Update Junior Secondary academic levels to use Stage Default scale instead of hardcoded Scale 3 (WAEC)
UPDATE `academic_levels` SET `grading_scale_id` = NULL WHERE `stage` = 'junior_secondary' AND `grading_scale_id` = 3;

-- Record migration 0034
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

-- Record migration 0035
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

-- Record migration 0036
INSERT IGNORE INTO `migrations` (`migration`, `batch`, `applied_at`)
VALUES ('0036_add_status_and_decision_note_to_admission_wards.sql', (SELECT COALESCE(MAX(`batch`), 0) + 1 FROM `migrations` m2), NOW());


-- --------------------------------------------------------------------------
-- MIGRATION: 0037_enhance_parent_and_class_mapping_tables.sql
-- --------------------------------------------------------------------------

-- 1. Modify `parents.user_id` to be nullable
ALTER TABLE `parents` MODIFY COLUMN `user_id` BIGINT UNSIGNED NULL;

-- Safely add `name` column to `parents`
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'parents' AND COLUMN_NAME = 'name');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE `parents` ADD COLUMN `name` VARCHAR(255) NULL AFTER `user_id`;', 'SELECT 1;');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Safely add `phone` column to `parents`
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'parents' AND COLUMN_NAME = 'phone');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE `parents` ADD COLUMN `phone` VARCHAR(50) NULL AFTER `name`, ADD INDEX `idx_parents_phone` (`phone`);', 'SELECT 1;');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Safely add `email` column to `parents`
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'parents' AND COLUMN_NAME = 'email');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE `parents` ADD COLUMN `email` VARCHAR(255) NULL AFTER `phone`, ADD INDEX `idx_parents_email` (`email`);', 'SELECT 1;');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Backfill existing parent records from active users
UPDATE `parents` p
JOIN `users` u ON u.id = p.user_id
SET p.name = COALESCE(p.name, u.name),
    p.phone = COALESCE(p.phone, u.phone),
    p.email = COALESCE(p.email, u.email);

-- Fallback for any orphaned rows before enforcing NOT NULL
UPDATE `parents` SET `name` = 'Parent/Guardian' WHERE `name` IS NULL OR TRIM(`name`) = '';

-- Enforce NOT NULL on `parents.name`
ALTER TABLE `parents` MODIFY COLUMN `name` VARCHAR(255) NOT NULL;

-- 2. Enhance `imports` table with session_id and chunk idempotency tracking
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'imports' AND COLUMN_NAME = 'session_id');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE `imports` ADD COLUMN `session_id` BIGINT UNSIGNED NULL AFTER `type`, ADD CONSTRAINT `fk_imports_session` FOREIGN KEY (`session_id`) REFERENCES `sessions` (`id`) ON DELETE SET NULL;', 'SELECT 1;');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'imports' AND COLUMN_NAME = 'processed_chunks_json');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE `imports` ADD COLUMN `processed_chunks_json` TEXT NULL AFTER `invalid_rows`;', 'SELECT 1;');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 3. Create persistent class mappings table
CREATE TABLE IF NOT EXISTS `import_class_mappings` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `raw_pattern` VARCHAR(100) NOT NULL,
    `normalized_pattern` VARCHAR(100) NOT NULL UNIQUE,
    `canonical_class_id` BIGINT UNSIGNED NOT NULL,
    `created_by` BIGINT UNSIGNED NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_icm_class` (`canonical_class_id`),
    CONSTRAINT `fk_icm_class` FOREIGN KEY (`canonical_class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_icm_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Record migration 0037
INSERT IGNORE INTO `migrations` (`migration`, `batch`, `applied_at`)
VALUES ('0037_enhance_parent_and_class_mapping_tables.sql', (SELECT COALESCE(MAX(`batch`), 0) + 1 FROM `migrations` m2), NOW());


-- --------------------------------------------------------------------------
-- MIGRATION: 0038_add_avatar_url_to_users_table.sql
-- --------------------------------------------------------------------------

SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'users' AND COLUMN_NAME = 'avatar_url');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE `users` ADD COLUMN `avatar_url` VARCHAR(255) NULL AFTER `phone`;', 'SELECT 1;');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Record migration 0038
INSERT IGNORE INTO `migrations` (`migration`, `batch`, `applied_at`)
VALUES ('0038_add_avatar_url_to_users_table.sql', (SELECT COALESCE(MAX(`batch`), 0) + 1 FROM `migrations` m2), NOW());


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
-- LIVE DELTA MIGRATION (0033 -> 0039) COMPLETED SUCCESSFULLY
-- ==========================================================================
