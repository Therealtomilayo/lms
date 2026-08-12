-- Migration: 0008_create_questions_quizzes_and_cbt_tables
-- Description: Question bank, quizzes, CBT attempts, and student answers lifecycle tables

CREATE TABLE IF NOT EXISTS `questions` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `subject_id` BIGINT UNSIGNED NOT NULL,
    `topic` VARCHAR(150) NULL DEFAULT NULL,
    `question_text` TEXT NOT NULL,
    `type` ENUM('mcq', 'short_answer') NOT NULL DEFAULT 'mcq',
    `default_points` DECIMAL(5,2) NOT NULL DEFAULT 1.00,
    `created_by` BIGINT UNSIGNED NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_questions_subject` (`subject_id`),
    INDEX `idx_questions_topic` (`topic`),
    INDEX `idx_questions_type` (`type`),
    INDEX `idx_questions_created_by` (`created_by`),
    CONSTRAINT `fk_questions_subject_id` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_questions_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `question_options` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `question_id` BIGINT UNSIGNED NOT NULL,
    `option_text` TEXT NOT NULL,
    `is_correct` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_question_options_question` (`question_id`),
    CONSTRAINT `fk_question_options_question_id` FOREIGN KEY (`question_id`) REFERENCES `questions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `quizzes` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `class_subject_id` BIGINT UNSIGNED NOT NULL,
    `term_id` BIGINT UNSIGNED NOT NULL,
    `assessment_category_id` BIGINT UNSIGNED NULL DEFAULT NULL,
    `teacher_id` BIGINT UNSIGNED NOT NULL,
    `title` VARCHAR(200) NOT NULL,
    `instructions` TEXT NULL DEFAULT NULL,
    `time_limit_minutes` INT UNSIGNED NOT NULL DEFAULT 0,
    `max_attempts` INT UNSIGNED NOT NULL DEFAULT 1,
    `is_published` TINYINT(1) NOT NULL DEFAULT 0,
    `published_at` DATETIME NULL DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_quizzes_class_subject_term` (`class_subject_id`, `term_id`),
    INDEX `idx_quizzes_teacher` (`teacher_id`),
    INDEX `idx_quizzes_published` (`is_published`),
    CONSTRAINT `fk_quizzes_class_subject_id` FOREIGN KEY (`class_subject_id`) REFERENCES `class_subjects` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_quizzes_term_id` FOREIGN KEY (`term_id`) REFERENCES `terms` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_quizzes_teacher_id` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `quiz_questions` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `quiz_id` BIGINT UNSIGNED NOT NULL,
    `question_id` BIGINT UNSIGNED NOT NULL,
    `points` DECIMAL(5,2) NOT NULL DEFAULT 1.00,
    `sort_order` INT NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_quiz_questions` (`quiz_id`, `question_id`),
    INDEX `idx_quiz_questions_quiz` (`quiz_id`),
    INDEX `idx_quiz_questions_question` (`question_id`),
    CONSTRAINT `fk_quiz_questions_quiz_id` FOREIGN KEY (`quiz_id`) REFERENCES `quizzes` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_quiz_questions_question_id` FOREIGN KEY (`question_id`) REFERENCES `questions` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `quiz_attempts` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `uuid` VARCHAR(36) NOT NULL UNIQUE,
    `quiz_id` BIGINT UNSIGNED NOT NULL,
    `student_id` BIGINT UNSIGNED NOT NULL,
    `attempt_number` INT UNSIGNED NOT NULL DEFAULT 1,
    `started_at` DATETIME NOT NULL,
    `submitted_at` DATETIME NULL DEFAULT NULL,
    `score` DECIMAL(5,2) NULL DEFAULT NULL,
    `max_score` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    `status` ENUM('in_progress', 'submitted', 'graded') NOT NULL DEFAULT 'in_progress',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_quiz_attempts_student` (`quiz_id`, `student_id`, `attempt_number`),
    INDEX `idx_quiz_attempts_quiz` (`quiz_id`),
    INDEX `idx_quiz_attempts_student` (`student_id`),
    INDEX `idx_quiz_attempts_status` (`status`),
    CONSTRAINT `fk_quiz_attempts_quiz_id` FOREIGN KEY (`quiz_id`) REFERENCES `quizzes` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_quiz_attempts_student_id` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `quiz_answers` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `attempt_id` BIGINT UNSIGNED NOT NULL,
    `question_id` BIGINT UNSIGNED NOT NULL,
    `selected_option_id` BIGINT UNSIGNED NULL DEFAULT NULL,
    `text_answer` TEXT NULL DEFAULT NULL,
    `points_awarded` DECIMAL(5,2) NULL DEFAULT NULL,
    `teacher_comment` TEXT NULL DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_quiz_answers_attempt_question` (`attempt_id`, `question_id`),
    INDEX `idx_quiz_answers_attempt` (`attempt_id`),
    INDEX `idx_quiz_answers_question` (`question_id`),
    INDEX `idx_quiz_answers_option` (`selected_option_id`),
    CONSTRAINT `fk_quiz_answers_attempt_id` FOREIGN KEY (`attempt_id`) REFERENCES `quiz_attempts` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_quiz_answers_question_id` FOREIGN KEY (`question_id`) REFERENCES `questions` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_quiz_answers_option_id` FOREIGN KEY (`selected_option_id`) REFERENCES `question_options` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
