-- Migration: 0019_create_live_classes_tables
-- Description: Creates live_classes and live_class_attendees tables for online classes integration (SRS §31)

CREATE TABLE IF NOT EXISTS `live_classes` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `session_id` BIGINT UNSIGNED NULL,
    `term_id` BIGINT UNSIGNED NULL,
    `class_subject_id` BIGINT UNSIGNED NOT NULL,
    `teacher_id` BIGINT UNSIGNED NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `description` TEXT NULL,
    `platform` ENUM('google_meet', 'zoom', 'microsoft_teams', 'other') NOT NULL DEFAULT 'google_meet',
    `meeting_link` VARCHAR(1000) NOT NULL,
    `meeting_passcode` VARCHAR(100) NULL,
    `scheduled_date` DATE NOT NULL,
    `start_time` TIME NOT NULL,
    `duration_minutes` INT UNSIGNED NOT NULL DEFAULT 40,
    `status` ENUM('scheduled', 'in_progress', 'completed', 'cancelled') NOT NULL DEFAULT 'scheduled',
    `is_published` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_live_classes_scheduled` (`scheduled_date`, `start_time`),
    INDEX `idx_live_classes_class_subject` (`class_subject_id`),
    INDEX `idx_live_classes_teacher` (`teacher_id`),
    INDEX `idx_live_classes_status` (`status`),
    CONSTRAINT `fk_live_classes_session` FOREIGN KEY (`session_id`) REFERENCES `sessions` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_live_classes_term` FOREIGN KEY (`term_id`) REFERENCES `terms` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_live_classes_class_subject` FOREIGN KEY (`class_subject_id`) REFERENCES `class_subjects` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_live_classes_teacher` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `live_class_attendees` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `live_class_id` BIGINT UNSIGNED NOT NULL,
    `student_id` BIGINT UNSIGNED NOT NULL,
    `joined_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `last_seen_at` DATETIME NULL,
    `ip_address` VARCHAR(45) NULL,
    `user_agent` VARCHAR(500) NULL,
    UNIQUE KEY `uq_live_class_student` (`live_class_id`, `student_id`),
    INDEX `idx_live_class_attendees_class` (`live_class_id`),
    INDEX `idx_live_class_attendees_student` (`student_id`),
    CONSTRAINT `fk_live_class_attendees_class` FOREIGN KEY (`live_class_id`) REFERENCES `live_classes` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_live_class_attendees_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
