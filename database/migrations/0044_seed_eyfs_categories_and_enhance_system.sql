-- Migration: 0044_seed_eyfs_categories_and_enhance_system.sql
-- Description:
--   1. Seeds EYFS assessment categories across active terms.
--   2. Seeds EYFS behavioral and developmental skills.
--   3. Ensures principal settings exist in system_settings.

SET FOREIGN_KEY_CHECKS = 0;

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

-- Ensure skills.stage exists
SET @col_exists = (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'skills' AND COLUMN_NAME = 'stage'
);
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE `skills` ADD COLUMN `stage` ENUM(\'eyfs\', \'primary\', \'junior_secondary\', \'senior_secondary\') NULL DEFAULT NULL, ADD INDEX `idx_skills_stage` (`stage`);', 
    'SELECT 1;'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 1. Insert EYFS assessment categories for every session/term
INSERT INTO `assessment_categories` (`session_id`, `term_id`, `name`, `weight_percentage`, `max_points`, `stage`, `created_at`, `updated_at`)
SELECT t.session_id, t.id, 'Personal & Social Development', 25.00, 25.00, 'eyfs', NOW(), NOW()
FROM `terms` t
WHERE NOT EXISTS (
    SELECT 1 FROM `assessment_categories` ac 
    WHERE ac.term_id = t.id AND ac.stage = 'eyfs' AND ac.name = 'Personal & Social Development'
);

INSERT INTO `assessment_categories` (`session_id`, `term_id`, `name`, `weight_percentage`, `max_points`, `stage`, `created_at`, `updated_at`)
SELECT t.session_id, t.id, 'Communication & Language', 25.00, 25.00, 'eyfs', NOW(), NOW()
FROM `terms` t
WHERE NOT EXISTS (
    SELECT 1 FROM `assessment_categories` ac 
    WHERE ac.term_id = t.id AND ac.stage = 'eyfs' AND ac.name = 'Communication & Language'
);

INSERT INTO `assessment_categories` (`session_id`, `term_id`, `name`, `weight_percentage`, `max_points`, `stage`, `created_at`, `updated_at`)
SELECT t.session_id, t.id, 'Physical & Motor Development', 25.00, 25.00, 'eyfs', NOW(), NOW()
FROM `terms` t
WHERE NOT EXISTS (
    SELECT 1 FROM `assessment_categories` ac 
    WHERE ac.term_id = t.id AND ac.stage = 'eyfs' AND ac.name = 'Physical & Motor Development'
);

INSERT INTO `assessment_categories` (`session_id`, `term_id`, `name`, `weight_percentage`, `max_points`, `stage`, `created_at`, `updated_at`)
SELECT t.session_id, t.id, 'Literacy & Numeracy Readiness', 25.00, 25.00, 'eyfs', NOW(), NOW()
FROM `terms` t
WHERE NOT EXISTS (
    SELECT 1 FROM `assessment_categories` ac 
    WHERE ac.term_id = t.id AND ac.stage = 'eyfs' AND ac.name = 'Literacy & Numeracy Readiness'
);

-- 2. Seed EYFS specific Affective Traits
INSERT IGNORE INTO `skills` (`name`, `category`, `stage`, `display_order`, `status`, `created_at`, `updated_at`) VALUES
('Sharing & Turn-Taking', 'affective', 'eyfs', 1, 'active', NOW(), NOW()),
('Emotional Expression & Confidence', 'affective', 'eyfs', 2, 'active', NOW(), NOW()),
('Independence & Self-Regulation', 'affective', 'eyfs', 3, 'active', NOW(), NOW()),
('Curiosity & Play Engagement', 'affective', 'eyfs', 4, 'active', NOW(), NOW()),
('Following Routines & Instructions', 'affective', 'eyfs', 5, 'active', NOW(), NOW()),
('Social Interaction & Politeness', 'affective', 'eyfs', 6, 'active', NOW(), NOW());

-- 3. Seed EYFS specific Psychomotor Skills
INSERT IGNORE INTO `skills` (`name`, `category`, `stage`, `display_order`, `status`, `created_at`, `updated_at`) VALUES
('Gross Motor (Running, Balance & Climbing)', 'psychomotor', 'eyfs', 1, 'active', NOW(), NOW()),
('Fine Motor (Grip, Holding Utensils & Sorting)', 'psychomotor', 'eyfs', 2, 'active', NOW(), NOW()),
('Sensory Exploration & Texture Play', 'psychomotor', 'eyfs', 3, 'active', NOW(), NOW()),
('Visual-Motor Coordination & Coloring', 'psychomotor', 'eyfs', 4, 'active', NOW(), NOW()),
('Rhythm, Music & Movement', 'psychomotor', 'eyfs', 5, 'active', NOW(), NOW()),
('Self-Care & Personal Hygiene Habits', 'psychomotor', 'eyfs', 6, 'active', NOW(), NOW());

-- 4. Ensure Principal settings are configured
INSERT INTO `system_settings` (`setting_key`, `setting_value`, `is_secret`, `updated_at`) VALUES
('principal_name', 'Rev. Fr. Principal', 0, NOW()),
('principal_title', 'Principal (High School)', 0, NOW()),
('principal_signature_url', '/assets/img/principal-signature.png', 0, NOW())
ON DUPLICATE KEY UPDATE 
    `setting_value` = IF(setting_value IS NULL OR setting_value = '', VALUES(setting_value), setting_value);

SET FOREIGN_KEY_CHECKS = 1;
