-- Migration: 0031_create_class_result_submissions_table.sql
-- Description: Store terminal class result submissions by form teachers for admin assessment and approval

CREATE TABLE IF NOT EXISTS `class_result_submissions` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `class_id` BIGINT UNSIGNED NOT NULL,
    `term_id` BIGINT UNSIGNED NOT NULL,
    `submitted_by` BIGINT UNSIGNED NOT NULL,
    `submitted_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `status` ENUM('submitted', 'approved', 'rejected') NOT NULL DEFAULT 'submitted',
    `notes` TEXT NULL DEFAULT NULL,
    `reviewed_by` BIGINT UNSIGNED NULL DEFAULT NULL,
    `reviewed_at` DATETIME NULL DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_class_term_submission` (`class_id`, `term_id`),
    INDEX `idx_crs_status` (`status`),
    CONSTRAINT `fk_crs_class_id` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_crs_term_id` FOREIGN KEY (`term_id`) REFERENCES `terms` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_crs_submitted_by` FOREIGN KEY (`submitted_by`) REFERENCES `teachers` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_crs_reviewed_by` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
