-- Migration: 0017_create_promotions_and_graduation_tables
-- Description: Adds graduation status, progression linkage to academic levels, and creates student_promotions table (SRS §17, §18, §58.3)

-- 1. Extend class_enrollments status enum to support 'graduated'
ALTER TABLE `class_enrollments` 
    MODIFY COLUMN `status` ENUM('active', 'promoted', 'repeating', 'transferred', 'withdrawn', 'graduated') NOT NULL DEFAULT 'active';

-- 2. Extend student_term_summaries promotion_status enum to support 'graduated'
ALTER TABLE `student_term_summaries` 
    MODIFY COLUMN `promotion_status` ENUM('pending', 'promoted', 'repeating', 'transferred', 'withdrawn', 'graduated', 'not_applicable') NOT NULL DEFAULT 'pending';

-- 3. Enhance academic_levels with progression chain and terminal class graduation flags
ALTER TABLE `academic_levels` 
    ADD COLUMN `is_terminal` TINYINT(1) NOT NULL DEFAULT 0 AFTER `rank_order`,
    ADD COLUMN `next_level_id` BIGINT UNSIGNED NULL AFTER `is_terminal`,
    ADD CONSTRAINT `fk_academic_levels_next_level_id` FOREIGN KEY (`next_level_id`) REFERENCES `academic_levels` (`id`) ON DELETE SET NULL;

-- Flag terminal classes: Primary 5 and Senior Secondary 3 (SS 3 / SSS 3)
UPDATE `academic_levels` 
SET `is_terminal` = 1 
WHERE `name` LIKE '%Primary 5%' 
   OR `name` LIKE '%Senior Secondary 3%' 
   OR `name` LIKE '%SS 3%' 
   OR `name` LIKE '%SSS 3%';

-- 4. Create student_promotions table
CREATE TABLE IF NOT EXISTS `student_promotions` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `student_id` BIGINT UNSIGNED NOT NULL,
    `from_session_id` BIGINT UNSIGNED NOT NULL,
    `from_class_id` BIGINT UNSIGNED NOT NULL,
    `to_session_id` BIGINT UNSIGNED NULL,
    `to_class_id` BIGINT UNSIGNED NULL,
    `decision` ENUM('promoted', 'repeating', 'graduated', 'withdrawn') NOT NULL,
    `annual_average` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    `term_averages` JSON NULL,
    `evaluation_status` ENUM('preliminary', 'staged_review', 'finalized') NOT NULL DEFAULT 'preliminary',
    `approval_request_id` BIGINT UNSIGNED NULL,
    `override_reason` TEXT NULL,
    `promoted_by` BIGINT UNSIGNED NOT NULL,
    `promoted_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_student_session_promotion` (`student_id`, `from_session_id`),
    INDEX `idx_promotions_from_class` (`from_class_id`, `from_session_id`),
    INDEX `idx_promotions_decision` (`decision`),
    CONSTRAINT `fk_promotions_student_id` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_promotions_from_session_id` FOREIGN KEY (`from_session_id`) REFERENCES `sessions` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_promotions_from_class_id` FOREIGN KEY (`from_class_id`) REFERENCES `classes` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_promotions_to_session_id` FOREIGN KEY (`to_session_id`) REFERENCES `sessions` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_promotions_to_class_id` FOREIGN KEY (`to_class_id`) REFERENCES `classes` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_promotions_approval_id` FOREIGN KEY (`approval_request_id`) REFERENCES `approval_requests` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_promotions_promoted_by` FOREIGN KEY (`promoted_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
