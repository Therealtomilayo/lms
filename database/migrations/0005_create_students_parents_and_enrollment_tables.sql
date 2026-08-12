-- Migration: 0005_create_students_parents_and_enrollment_tables
-- Description: Creates students, parents, parent_student, class_enrollments, student_subject_enrollments, imports, and import_errors tables

CREATE TABLE IF NOT EXISTS `students` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` BIGINT UNSIGNED NOT NULL UNIQUE,
    `admission_number` VARCHAR(50) NOT NULL UNIQUE,
    `date_of_birth` DATE NULL,
    `gender` ENUM('male', 'female', 'other') NULL,
    `current_class_id` BIGINT UNSIGNED NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_students_user_id` (`user_id`),
    INDEX `idx_students_admission_number` (`admission_number`),
    INDEX `idx_students_current_class_id` (`current_class_id`),
    CONSTRAINT `fk_students_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_students_current_class_id` FOREIGN KEY (`current_class_id`) REFERENCES `classes` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `parents` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` BIGINT UNSIGNED NOT NULL UNIQUE,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_parents_user_id` (`user_id`),
    CONSTRAINT `fk_parents_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `parent_student` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `parent_id` BIGINT UNSIGNED NOT NULL,
    `student_id` BIGINT UNSIGNED NOT NULL,
    `relationship_type` VARCHAR(50) NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_parent_student` (`parent_id`, `student_id`),
    INDEX `idx_parent_student_parent` (`parent_id`),
    INDEX `idx_parent_student_student` (`student_id`),
    CONSTRAINT `fk_parent_student_parent_id` FOREIGN KEY (`parent_id`) REFERENCES `parents` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_parent_student_student_id` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `class_enrollments` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `student_id` BIGINT UNSIGNED NOT NULL,
    `class_id` BIGINT UNSIGNED NOT NULL,
    `session_id` BIGINT UNSIGNED NOT NULL,
    `status` ENUM('active', 'promoted', 'repeating', 'transferred', 'withdrawn') NOT NULL DEFAULT 'active',
    `enrolled_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_student_session` (`student_id`, `session_id`),
    INDEX `idx_class_enrollments_lookup` (`class_id`, `session_id`, `status`),
    INDEX `idx_class_enrollments_student` (`student_id`),
    CONSTRAINT `fk_class_enrollments_student_id` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_class_enrollments_class_id` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_class_enrollments_session_id` FOREIGN KEY (`session_id`) REFERENCES `sessions` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `student_subject_enrollments` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `student_id` BIGINT UNSIGNED NOT NULL,
    `class_subject_id` BIGINT UNSIGNED NOT NULL,
    `session_id` BIGINT UNSIGNED NOT NULL,
    `is_elective` TINYINT(1) NOT NULL DEFAULT 0,
    `status` ENUM('active', 'dropped') NOT NULL DEFAULT 'active',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_student_class_subject` (`student_id`, `class_subject_id`),
    INDEX `idx_student_subject_enrollments_lookup` (`student_id`, `session_id`, `status`),
    INDEX `idx_student_subject_enrollments_class_subject` (`class_subject_id`),
    CONSTRAINT `fk_student_subject_enrollments_student_id` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_student_subject_enrollments_class_subject_id` FOREIGN KEY (`class_subject_id`) REFERENCES `class_subjects` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_student_subject_enrollments_session_id` FOREIGN KEY (`session_id`) REFERENCES `sessions` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `imports` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `uploaded_by` BIGINT UNSIGNED NOT NULL,
    `type` ENUM('students', 'teachers', 'parents') NOT NULL,
    `original_name` VARCHAR(255) NOT NULL,
    `sha256` VARCHAR(64) NOT NULL,
    `status` ENUM('uploaded', 'validated', 'committed', 'failed') NOT NULL DEFAULT 'uploaded',
    `total_rows` INT NOT NULL DEFAULT 0,
    `valid_rows` INT NOT NULL DEFAULT 0,
    `invalid_rows` INT NOT NULL DEFAULT 0,
    `committed_at` DATETIME NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_imports_uploaded_by` (`uploaded_by`),
    INDEX `idx_imports_status` (`status`),
    CONSTRAINT `fk_imports_uploaded_by` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `import_errors` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `import_id` BIGINT UNSIGNED NOT NULL,
    `row_number` INT NOT NULL,
    `raw_data_json` TEXT NOT NULL,
    `errors_json` TEXT NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_import_errors_import_id` (`import_id`),
    CONSTRAINT `fk_import_errors_import_id` FOREIGN KEY (`import_id`) REFERENCES `imports` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
