-- Migration: 0022_create_activity_prerequisites_table.sql
-- Description: Creates the activity_prerequisites table for Phase 4 LMS learning activity unlocking

CREATE TABLE IF NOT EXISTS `activity_prerequisites` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `activity_type` VARCHAR(50) NOT NULL,
    `activity_id` BIGINT UNSIGNED NOT NULL,
    `prerequisite_activity_type` VARCHAR(50) NOT NULL,
    `prerequisite_activity_id` BIGINT UNSIGNED NOT NULL,
    `requirement_type` VARCHAR(30) NOT NULL DEFAULT 'completion',
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_target_activity` (`activity_type`, `activity_id`),
    INDEX `idx_prereq_activity` (`prerequisite_activity_type`, `prerequisite_activity_id`),
    UNIQUE KEY `uniq_target_prerequisite` (`activity_type`, `activity_id`, `prerequisite_activity_type`, `prerequisite_activity_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
