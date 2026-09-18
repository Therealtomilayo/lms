-- Migration: 0023_create_course_modules_and_items_tables.sql
-- Description: Creates the modules and module_items tables for Phase 6 Course/Module Progression and Learning Path

CREATE TABLE IF NOT EXISTS `modules` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `class_subject_id` BIGINT UNSIGNED NOT NULL,
    `title` VARCHAR(200) NOT NULL,
    `description` TEXT NULL DEFAULT NULL,
    `sequence_order` INT UNSIGNED NOT NULL DEFAULT 1,
    `status` ENUM('published', 'draft') NOT NULL DEFAULT 'published',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_modules_class_subject_order` (`class_subject_id`, `sequence_order`),
    INDEX `idx_modules_status` (`status`),
    CONSTRAINT `fk_modules_class_subject_id` FOREIGN KEY (`class_subject_id`) REFERENCES `class_subjects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `module_items` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `module_id` BIGINT UNSIGNED NOT NULL,
    `activity_type` ENUM('document', 'quiz', 'assignment') NOT NULL,
    `activity_id` BIGINT UNSIGNED NOT NULL,
    `sequence_order` INT UNSIGNED NOT NULL DEFAULT 1,
    `is_required` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_module_activity` (`module_id`, `activity_type`, `activity_id`),
    INDEX `idx_module_items_order` (`module_id`, `sequence_order`),
    INDEX `idx_module_items_activity` (`activity_type`, `activity_id`),
    CONSTRAINT `fk_module_items_module_id` FOREIGN KEY (`module_id`) REFERENCES `modules` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
