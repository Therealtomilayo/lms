-- Migration: 0034_add_stage_to_grading_scales_and_academic_stages_table
-- Description: Creates academic_stages table and adds stage column to grading_scales for stage-based grading configuration

CREATE TABLE IF NOT EXISTS `academic_stages` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `stage_key` VARCHAR(50) NOT NULL UNIQUE,
    `name` VARCHAR(100) NOT NULL,
    `rank_order` INT NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_academic_stages_rank` (`rank_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Populate standard institutional stages
INSERT INTO `academic_stages` (`stage_key`, `name`, `rank_order`) VALUES
('creche', 'Creche / Daycare', 1),
('nursery', 'Nursery', 2),
('Pre Nusery', 'Pre-Nursery', 3),
('primary', 'Primary', 4),
('junior_secondary', 'Junior Secondary', 5),
('senior_secondary', 'Senior Secondary', 6)
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`), `rank_order` = VALUES(`rank_order`);

-- Add stage column to grading_scales if not exists
SET @dbname = DATABASE();
SET @tablename = "grading_scales";
SET @columnname = "stage";
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      TABLE_SCHEMA = @dbname
      AND TABLE_NAME = @tablename
      AND COLUMN_NAME = @columnname
  ) > 0,
  "SELECT 1",
  "ALTER TABLE `grading_scales` ADD COLUMN `stage` VARCHAR(50) NULL AFTER `name`, ADD INDEX `idx_grading_scales_stage` (`stage`);"
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Update existing grading scales stage mappings
UPDATE `grading_scales` SET `stage` = 'senior_secondary' WHERE `name` LIKE '%Secondary%' AND `name` LIKE '%WAEC%' AND (`stage` IS NULL OR `stage` = '');
UPDATE `grading_scales` SET `stage` = 'junior_secondary' WHERE `name` LIKE '%Junior Secondary%' AND (`stage` IS NULL OR `stage` = '');

-- Update Junior Secondary academic levels to use Stage Default scale instead of hardcoded Scale 3 (WAEC)
UPDATE `academic_levels` SET `grading_scale_id` = NULL WHERE `stage` = 'junior_secondary' AND `grading_scale_id` = 3;
