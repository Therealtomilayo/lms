-- Migration: 0010_create_attendance_and_announcements_tables
-- Description: Dual-granularity attendance tracking, announcements, read receipts, and audit logging

CREATE TABLE IF NOT EXISTS `attendance_records` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `session_id` BIGINT UNSIGNED NOT NULL,
    `term_id` BIGINT UNSIGNED NOT NULL,
    `class_id` BIGINT UNSIGNED NOT NULL,
    `class_subject_id` BIGINT UNSIGNED NULL DEFAULT NULL,
    `student_id` BIGINT UNSIGNED NOT NULL,
    `date` DATE NOT NULL,
    `period_number` INT UNSIGNED NULL DEFAULT NULL,
    `status` ENUM('present', 'absent', 'late', 'excused') NOT NULL DEFAULT 'present',
    `marked_by` BIGINT UNSIGNED NOT NULL,
    `updated_by` BIGINT UNSIGNED NULL DEFAULT NULL,
    `correction_reason` TEXT NULL DEFAULT NULL,
    `attendance_context_id` BIGINT UNSIGNED AS (COALESCE(`class_subject_id`, 0)) VIRTUAL,
    `period_slot` INT UNSIGNED AS (COALESCE(`period_number`, 0)) VIRTUAL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_attendance_entry` (`student_id`, `date`, `class_id`, `attendance_context_id`, `period_slot`),
    INDEX `idx_attendance_class_date` (`class_id`, `date`),
    INDEX `idx_attendance_subject_date` (`class_subject_id`, `date`),
    INDEX `idx_attendance_student_term` (`student_id`, `term_id`),
    CONSTRAINT `fk_attendance_session_id` FOREIGN KEY (`session_id`) REFERENCES `sessions` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_attendance_term_id` FOREIGN KEY (`term_id`) REFERENCES `terms` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_attendance_class_id` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_attendance_class_subject_id` FOREIGN KEY (`class_subject_id`) REFERENCES `class_subjects` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_attendance_student_id` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_attendance_marked_by` FOREIGN KEY (`marked_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_attendance_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `announcements` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `author_id` BIGINT UNSIGNED NOT NULL,
    `scope` ENUM('school', 'class', 'class_subject') NOT NULL DEFAULT 'school',
    `scope_id` BIGINT UNSIGNED NULL DEFAULT NULL,
    `title` VARCHAR(255) NOT NULL,
    `body` TEXT NOT NULL,
    `published_at` DATETIME NULL DEFAULT NULL,
    `expires_at` DATETIME NULL DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_announcements_scope` (`scope`, `scope_id`, `published_at`),
    INDEX `idx_announcements_author` (`author_id`),
    CONSTRAINT `fk_announcements_author_id` FOREIGN KEY (`author_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `announcement_reads` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `announcement_id` BIGINT UNSIGNED NOT NULL,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `read_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_announcement_user_read` (`announcement_id`, `user_id`),
    INDEX `idx_reads_user` (`user_id`),
    CONSTRAINT `fk_announcement_reads_announcement_id` FOREIGN KEY (`announcement_id`) REFERENCES `announcements` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_announcement_reads_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `audit_logs` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `actor_user_id` BIGINT UNSIGNED NULL DEFAULT NULL,
    `action` VARCHAR(100) NOT NULL,
    `entity_type` VARCHAR(100) NOT NULL,
    `entity_id` BIGINT UNSIGNED NOT NULL,
    `before_json` JSON NULL DEFAULT NULL,
    `after_json` JSON NULL DEFAULT NULL,
    `metadata_json` JSON NULL DEFAULT NULL,
    `ip_hash` VARCHAR(64) NULL DEFAULT NULL,
    `user_agent_hash` VARCHAR(64) NULL DEFAULT NULL,
    `request_id` VARCHAR(64) NULL DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_audit_logs_entity` (`entity_type`, `entity_id`, `created_at`),
    INDEX `idx_audit_logs_actor` (`actor_user_id`, `created_at`),
    CONSTRAINT `fk_audit_logs_actor_id` FOREIGN KEY (`actor_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
