-- Migration: 0009_create_gradebook_and_results_tables
-- Description: Configurable assessment categories, student scores, grading scales, term results, term summaries, and result publications

CREATE TABLE IF NOT EXISTS `grading_scales` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `description` TEXT NULL DEFAULT NULL,
    `is_default` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_grading_scales_default` (`is_default`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `grade_boundaries` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `grading_scale_id` BIGINT UNSIGNED NOT NULL,
    `letter` VARCHAR(5) NOT NULL,
    `min_score` DECIMAL(5,2) NOT NULL,
    `max_score` DECIMAL(5,2) NOT NULL,
    `grade_point` DECIMAL(3,2) NULL DEFAULT NULL,
    `remark` VARCHAR(100) NULL DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_grade_boundaries_scale` (`grading_scale_id`),
    INDEX `idx_grade_boundaries_scores` (`min_score`, `max_score`),
    CONSTRAINT `fk_grade_boundaries_scale_id` FOREIGN KEY (`grading_scale_id`) REFERENCES `grading_scales` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `assessment_categories` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `session_id` BIGINT UNSIGNED NOT NULL,
    `term_id` BIGINT UNSIGNED NOT NULL,
    `class_subject_id` BIGINT UNSIGNED NULL DEFAULT NULL,
    `name` VARCHAR(100) NOT NULL,
    `weight_percentage` DECIMAL(5,2) NOT NULL,
    `max_points` DECIMAL(5,2) NOT NULL DEFAULT 100.00,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_assessment_cat_term` (`session_id`, `term_id`),
    INDEX `idx_assessment_cat_class_subject` (`class_subject_id`),
    CONSTRAINT `fk_assessment_categories_session_id` FOREIGN KEY (`session_id`) REFERENCES `sessions` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_assessment_categories_term_id` FOREIGN KEY (`term_id`) REFERENCES `terms` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_assessment_categories_class_subject_id` FOREIGN KEY (`class_subject_id`) REFERENCES `class_subjects` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `student_assessment_scores` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `assessment_category_id` BIGINT UNSIGNED NOT NULL,
    `student_id` BIGINT UNSIGNED NOT NULL,
    `class_subject_id` BIGINT UNSIGNED NOT NULL,
    `raw_score` DECIMAL(5,2) NOT NULL,
    `recorded_by` BIGINT UNSIGNED NOT NULL,
    `recorded_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_student_assessment_score` (`assessment_category_id`, `student_id`, `class_subject_id`),
    INDEX `idx_student_scores_class_subject` (`class_subject_id`),
    INDEX `idx_student_scores_student` (`student_id`),
    CONSTRAINT `fk_student_scores_category_id` FOREIGN KEY (`assessment_category_id`) REFERENCES `assessment_categories` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_student_scores_student_id` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_student_scores_class_subject_id` FOREIGN KEY (`class_subject_id`) REFERENCES `class_subjects` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_student_scores_recorded_by` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `term_results` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `student_id` BIGINT UNSIGNED NOT NULL,
    `class_subject_id` BIGINT UNSIGNED NOT NULL,
    `term_id` BIGINT UNSIGNED NOT NULL,
    `computed_score` DECIMAL(5,2) NOT NULL,
    `grade_letter` VARCHAR(5) NOT NULL,
    `grade_point` DECIMAL(3,2) NULL DEFAULT NULL,
    `remark` VARCHAR(100) NULL DEFAULT NULL,
    `breakdown_json` JSON NOT NULL,
    `is_locked` TINYINT(1) NOT NULL DEFAULT 0,
    `locked_at` DATETIME NULL DEFAULT NULL,
    `locked_by` BIGINT UNSIGNED NULL DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_term_results_student_cs_term` (`student_id`, `class_subject_id`, `term_id`),
    INDEX `idx_term_results_class_subject` (`class_subject_id`, `term_id`),
    INDEX `idx_term_results_student` (`student_id`),
    CONSTRAINT `fk_term_results_student_id` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_term_results_class_subject_id` FOREIGN KEY (`class_subject_id`) REFERENCES `class_subjects` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_term_results_term_id` FOREIGN KEY (`term_id`) REFERENCES `terms` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_term_results_locked_by` FOREIGN KEY (`locked_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `student_term_summaries` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `student_id` BIGINT UNSIGNED NOT NULL,
    `term_id` BIGINT UNSIGNED NOT NULL,
    `class_id` BIGINT UNSIGNED NOT NULL,
    `total_score` DECIMAL(7,2) NULL DEFAULT NULL,
    `average_score` DECIMAL(5,2) NULL DEFAULT NULL,
    `gpa` DECIMAL(3,2) NULL DEFAULT NULL,
    `rank_in_class` INT NULL DEFAULT NULL,
    `attendance_present_count` INT NOT NULL DEFAULT 0,
    `attendance_total_count` INT NOT NULL DEFAULT 0,
    `class_teacher_remark` TEXT NULL DEFAULT NULL,
    `principal_remark` TEXT NULL DEFAULT NULL,
    `promotion_status` ENUM('pending', 'promoted', 'repeating', 'transferred', 'withdrawn', 'not_applicable') NOT NULL DEFAULT 'pending',
    `is_locked` TINYINT(1) NOT NULL DEFAULT 0,
    `locked_at` DATETIME NULL DEFAULT NULL,
    `locked_by` BIGINT UNSIGNED NULL DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_student_term_summary` (`student_id`, `term_id`),
    INDEX `idx_student_term_summaries_class_term` (`class_id`, `term_id`),
    CONSTRAINT `fk_student_term_summaries_student_id` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_student_term_summaries_term_id` FOREIGN KEY (`term_id`) REFERENCES `terms` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_student_term_summaries_class_id` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_student_term_summaries_locked_by` FOREIGN KEY (`locked_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `result_publications` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `term_id` BIGINT UNSIGNED NOT NULL,
    `class_id` BIGINT UNSIGNED NULL DEFAULT NULL,
    `published_by` BIGINT UNSIGNED NOT NULL,
    `published_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `unpublished_at` DATETIME NULL DEFAULT NULL,
    `status` ENUM('published', 'unpublished', 'archived') NOT NULL DEFAULT 'published',
    `reason` TEXT NULL DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_result_publications_lookup` (`term_id`, `class_id`, `status`),
    CONSTRAINT `fk_result_publications_term_id` FOREIGN KEY (`term_id`) REFERENCES `terms` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_result_publications_class_id` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_result_publications_published_by` FOREIGN KEY (`published_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
