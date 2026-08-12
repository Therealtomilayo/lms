-- Migration: 0007_create_assignments_and_submissions_tables
-- Description: Coursework assignments and student submission lifecycle tables

CREATE TABLE IF NOT EXISTS `assignments` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `class_subject_id` BIGINT UNSIGNED NOT NULL,
    `term_id` BIGINT UNSIGNED NOT NULL,
    `assessment_category_id` BIGINT UNSIGNED NULL DEFAULT NULL,
    `teacher_id` BIGINT UNSIGNED NOT NULL,
    `topic` VARCHAR(100) NULL DEFAULT NULL,
    `title` VARCHAR(200) NOT NULL,
    `instructions` TEXT NOT NULL,
    `due_at` DATETIME NOT NULL,
    `max_score` DECIMAL(5,2) NOT NULL DEFAULT 100.00,
    `file_id` BIGINT UNSIGNED NULL DEFAULT NULL,
    `status` ENUM('draft', 'published', 'archived') NOT NULL DEFAULT 'published',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_assignments_class_subject_term` (`class_subject_id`, `term_id`),
    INDEX `idx_assignments_teacher` (`teacher_id`),
    INDEX `idx_assignments_due_at` (`due_at`),
    INDEX `idx_assignments_file` (`file_id`),
    INDEX `idx_assignments_status` (`status`),
    CONSTRAINT `fk_assignments_class_subject_id` FOREIGN KEY (`class_subject_id`) REFERENCES `class_subjects` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_assignments_term_id` FOREIGN KEY (`term_id`) REFERENCES `terms` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_assignments_teacher_id` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_assignments_file_id` FOREIGN KEY (`file_id`) REFERENCES `files` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `assignment_submissions` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `assignment_id` BIGINT UNSIGNED NOT NULL,
    `student_id` BIGINT UNSIGNED NOT NULL,
    `submitted_at` DATETIME NOT NULL,
    `file_id` BIGINT UNSIGNED NULL DEFAULT NULL,
    `text_response` TEXT NULL DEFAULT NULL,
    `score` DECIMAL(5,2) NULL DEFAULT NULL,
    `teacher_comment` TEXT NULL DEFAULT NULL,
    `graded_at` DATETIME NULL DEFAULT NULL,
    `graded_by` BIGINT UNSIGNED NULL DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_assignment_submissions_student` (`assignment_id`, `student_id`),
    INDEX `idx_submissions_assignment` (`assignment_id`),
    INDEX `idx_submissions_student` (`student_id`),
    INDEX `idx_submissions_file` (`file_id`),
    INDEX `idx_submissions_graded_by` (`graded_by`),
    CONSTRAINT `fk_submissions_assignment_id` FOREIGN KEY (`assignment_id`) REFERENCES `assignments` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_submissions_student_id` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_submissions_file_id` FOREIGN KEY (`file_id`) REFERENCES `files` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_submissions_graded_by` FOREIGN KEY (`graded_by`) REFERENCES `teachers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
