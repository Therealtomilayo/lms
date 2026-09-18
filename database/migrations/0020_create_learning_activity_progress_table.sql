-- Migration: 0020_create_learning_activity_progress_table
-- Description: Creates the unified learning activity progress table for tracking reading and activity completion (Phase 2)

CREATE TABLE IF NOT EXISTS `learning_activity_progress` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `student_id` BIGINT UNSIGNED NOT NULL,
    `activity_type` ENUM('document', 'document_section', 'quiz', 'assignment') NOT NULL,
    `activity_id` BIGINT UNSIGNED NOT NULL,
    `last_page` INT UNSIGNED NULL DEFAULT NULL,
    `total_pages` INT UNSIGNED NULL DEFAULT NULL,
    `pages_read_json` JSON NULL DEFAULT NULL,
    `progress_percent` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    `is_completed` TINYINT(1) NOT NULL DEFAULT 0,
    `completed_at` DATETIME NULL DEFAULT NULL,
    `last_accessed_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_student_activity` (`student_id`, `activity_type`, `activity_id`),
    INDEX `idx_lap_student` (`student_id`),
    INDEX `idx_lap_activity` (`activity_type`, `activity_id`),
    INDEX `idx_lap_completed` (`is_completed`),
    CONSTRAINT `fk_lap_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
