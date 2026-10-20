-- Migration: 0043_grading_scales_skills_and_signatures.sql
-- Description:
--   1. Adds stage column to skills table for educational stage grouping (eyfs, primary, junior_secondary, senior_secondary).
--   2. Adds signature_url column to teachers table.
--   3. Configures principal settings (principal_name, principal_title, principal_signature_url) in system_settings.
--   4. Standardizes Claret High School 7-point grading scale (A+, A, B, C, D, P, F) and Primary School scale in grading_scales.

SET FOREIGN_KEY_CHECKS = 0;

-- 1. skills: Add stage column if not exists
SET @dbname = DATABASE();
SET @col_exists = (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'skills' AND COLUMN_NAME = 'stage'
);
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE `skills` ADD COLUMN `stage` ENUM(\'eyfs\', \'primary\', \'junior_secondary\', \'senior_secondary\') NULL DEFAULT NULL AFTER `category`, ADD INDEX `idx_skills_stage` (`stage`);', 
    'SELECT 1;'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 2. teachers: Add signature_url column if not exists
SET @col_exists = (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'teachers' AND COLUMN_NAME = 'signature_url'
);
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE `teachers` ADD COLUMN `signature_url` VARCHAR(255) NULL DEFAULT NULL AFTER `staff_id`;', 
    'SELECT 1;'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 3. system_settings: Configure Principal credentials alongside Head Teacher
INSERT INTO `system_settings` (`setting_key`, `setting_value`, `is_secret`, `updated_at`) VALUES
('principal_name', 'Rev. Fr. Principal', 0, NOW()),
('principal_title', 'Principal (High School)', 0, NOW()),
('principal_signature_url', '/assets/img/principal-signature.png', 0, NOW())
ON DUPLICATE KEY UPDATE 
    `setting_value` = IF(setting_value IS NULL OR setting_value = '', VALUES(setting_value), setting_value);

-- 4. Set existing 15 skills to 'primary' stage
UPDATE `skills` SET `stage` = 'primary' WHERE `stage` IS NULL;

-- 5. Seed High School specific Affective Traits (Junior Secondary & Senior Secondary)
INSERT IGNORE INTO `skills` (`name`, `category`, `stage`, `display_order`, `status`, `created_at`, `updated_at`) VALUES
('Discipline', 'affective', 'junior_secondary', 1, 'active', NOW(), NOW()),
('Punctuality', 'affective', 'junior_secondary', 2, 'active', NOW(), NOW()),
('Neatness', 'affective', 'junior_secondary', 3, 'active', NOW(), NOW()),
('Responsibility', 'affective', 'junior_secondary', 4, 'active', NOW(), NOW()),
('Politeness', 'affective', 'junior_secondary', 5, 'active', NOW(), NOW()),
('Honesty', 'affective', 'junior_secondary', 6, 'active', NOW(), NOW()),
('Attentiveness', 'affective', 'junior_secondary', 7, 'active', NOW(), NOW()),
('Leadership', 'affective', 'junior_secondary', 8, 'active', NOW(), NOW()),
('Spirit of Cooperation', 'affective', 'junior_secondary', 9, 'active', NOW(), NOW()),
('Relationship with Others', 'affective', 'junior_secondary', 10, 'active', NOW(), NOW()),

('Discipline', 'affective', 'senior_secondary', 1, 'active', NOW(), NOW()),
('Punctuality', 'affective', 'senior_secondary', 2, 'active', NOW(), NOW()),
('Neatness', 'affective', 'senior_secondary', 3, 'active', NOW(), NOW()),
('Responsibility', 'affective', 'senior_secondary', 4, 'active', NOW(), NOW()),
('Politeness', 'affective', 'senior_secondary', 5, 'active', NOW(), NOW()),
('Honesty', 'affective', 'senior_secondary', 6, 'active', NOW(), NOW()),
('Attentiveness', 'affective', 'senior_secondary', 7, 'active', NOW(), NOW()),
('Leadership', 'affective', 'senior_secondary', 8, 'active', NOW(), NOW()),
('Spirit of Cooperation', 'affective', 'senior_secondary', 9, 'active', NOW(), NOW()),
('Relationship with Others', 'affective', 'senior_secondary', 10, 'active', NOW(), NOW());

-- 6. Seed High School specific Psychomotor Skills (Junior Secondary & Senior Secondary)
INSERT IGNORE INTO `skills` (`name`, `category`, `stage`, `display_order`, `status`, `created_at`, `updated_at`) VALUES
('Handwriting', 'psychomotor', 'junior_secondary', 1, 'active', NOW(), NOW()),
('Craft', 'psychomotor', 'junior_secondary', 2, 'active', NOW(), NOW()),
('Games', 'psychomotor', 'junior_secondary', 3, 'active', NOW(), NOW()),
('Sports', 'psychomotor', 'junior_secondary', 4, 'active', NOW(), NOW()),
('Handling Tools', 'psychomotor', 'junior_secondary', 5, 'active', NOW(), NOW()),
('Drawing and Painting', 'psychomotor', 'junior_secondary', 6, 'active', NOW(), NOW()),
('Musical Skills', 'psychomotor', 'junior_secondary', 7, 'active', NOW(), NOW()),

('Handwriting', 'psychomotor', 'senior_secondary', 1, 'active', NOW(), NOW()),
('Craft', 'psychomotor', 'senior_secondary', 2, 'active', NOW(), NOW()),
('Games', 'psychomotor', 'senior_secondary', 3, 'active', NOW(), NOW()),
('Sports', 'psychomotor', 'senior_secondary', 4, 'active', NOW(), NOW()),
('Handling Tools', 'psychomotor', 'senior_secondary', 5, 'active', NOW(), NOW()),
('Drawing and Painting', 'psychomotor', 'senior_secondary', 6, 'active', NOW(), NOW()),
('Musical Skills', 'psychomotor', 'senior_secondary', 7, 'active', NOW(), NOW());

-- 7. Configure / Seed Official Claret High School 7-Point Grading Scale in database
INSERT INTO `grading_scales` (`id`, `name`, `stage`, `description`, `is_default`, `created_at`, `updated_at`) VALUES
(6, 'Claret High School 7-Point Grading Scale', 'junior_secondary', 'Standard 7-point evaluation scale for High School dossiers: A+ (Distinction) to F (Fail).', 0, NOW(), NOW())
ON DUPLICATE KEY UPDATE 
    `name` = VALUES(`name`),
    `stage` = VALUES(`stage`),
    `description` = VALUES(`description`);

DELETE FROM `grade_boundaries` WHERE `grading_scale_id` = 6;
INSERT INTO `grade_boundaries` (`grading_scale_id`, `letter`, `min_score`, `max_score`, `grade_point`, `remark`, `created_at`, `updated_at`) VALUES
(6, 'A+', 90.00, 100.00, 5.00, 'Distinction', NOW(), NOW()),
(6, 'A', 80.00, 89.99, 4.00, 'Excellent', NOW(), NOW()),
(6, 'B', 70.00, 79.99, 3.00, 'Very Good', NOW(), NOW()),
(6, 'C', 60.00, 69.99, 2.00, 'Good', NOW(), NOW()),
(6, 'D', 50.00, 59.99, 1.00, 'Fair', NOW(), NOW()),
(6, 'P', 40.00, 49.99, 0.50, 'Pass', NOW(), NOW()),
(6, 'F', 0.00, 39.99, 0.00, 'Fail', NOW(), NOW());

-- Also ensure Primary Scale (ID 2 or 1)
INSERT INTO `grading_scales` (`id`, `name`, `stage`, `description`, `is_default`, `created_at`, `updated_at`) VALUES
(1, 'Claret Grade School Grading Scale', 'primary', 'Standard primary and EYFS school evaluation scale.', 1, NOW(), NOW())
ON DUPLICATE KEY UPDATE 
    `name` = VALUES(`name`),
    `stage` = VALUES(`stage`),
    `description` = VALUES(`description`);

DELETE FROM `grade_boundaries` WHERE `grading_scale_id` = 1;
INSERT INTO `grade_boundaries` (`grading_scale_id`, `letter`, `min_score`, `max_score`, `grade_point`, `remark`, `created_at`, `updated_at`) VALUES
(1, 'A', 70.00, 100.00, 5.00, 'Excellent', NOW(), NOW()),
(1, 'B', 60.00, 69.99, 4.00, 'Very Good', NOW(), NOW()),
(1, 'C', 50.00, 59.99, 3.00, 'Credit', NOW(), NOW()),
(1, 'D', 45.00, 49.99, 2.00, 'Pass', NOW(), NOW()),
(1, 'E', 40.00, 44.99, 1.00, 'Fair', NOW(), NOW()),
(1, 'F', 0.00, 39.99, 0.00, 'Fail', NOW(), NOW());

-- 8. Link academic levels to their official grading scales
-- High School: JSS 1-3, SS 1-3 linked to scale 6
UPDATE `academic_levels` 
SET `grading_scale_id` = 6 
WHERE `stage` IN ('junior_secondary', 'senior_secondary');

-- Grade School: Primary 1-6, Creche, Nursery linked to scale 1
UPDATE `academic_levels` 
SET `grading_scale_id` = 1 
WHERE `stage` IN ('eyfs', 'primary');

SET FOREIGN_KEY_CHECKS = 1;
