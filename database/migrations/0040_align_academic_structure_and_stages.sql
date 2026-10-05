-- Migration: 0040_align_academic_structure_and_stages
-- Description: Standardize academic stages and levels with Nigerian 4-stage hierarchy (EYFS, Primary, JSS, SSS)

-- 1. Standardize academic_stages
CREATE TABLE IF NOT EXISTS `academic_stages` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `stage_key` VARCHAR(50) NOT NULL UNIQUE,
    `name` VARCHAR(100) NOT NULL,
    `rank_order` INT NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_academic_stages_rank` (`rank_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Clean up any legacy or misspelled stages
DELETE FROM `academic_stages` WHERE `stage_key` IN ('Pre Nusery', 'nursery', 'creche');

INSERT INTO `academic_stages` (`stage_key`, `name`, `rank_order`) VALUES
('eyfs', 'Early Years Foundation (EYFS)', 1),
('primary', 'Primary School', 2),
('junior_secondary', 'Junior Secondary School', 3),
('senior_secondary', 'Senior Secondary School', 4)
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`), `rank_order` = VALUES(`rank_order`);

-- 2. Ensure academic_levels stage columns and constraints are clean
UPDATE `academic_levels` SET `stage` = 'eyfs' WHERE `stage` IN ('Pre Nusery', 'creche', 'nursery');

-- 3. Ensure classes has arm_department or section_arm
-- section_arm is already VARCHAR(20) on classes
