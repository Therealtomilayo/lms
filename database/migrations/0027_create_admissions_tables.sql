-- Migration: 0027_create_admissions_tables.sql
-- Description: Online Admission and Prospective Student Application Portal (SRS §10, §57 Phase 4)

-- 1. Extend user_roles ENUM to support prospective applicants
ALTER TABLE `user_roles` MODIFY COLUMN `role` ENUM('super_admin', 'admin', 'teacher', 'student', 'parent', 'applicant') NOT NULL;

-- 2. Admission Sessions Configuration Table
CREATE TABLE IF NOT EXISTS `admission_sessions` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `academic_session_id` BIGINT UNSIGNED NOT NULL,
    `title` VARCHAR(150) NOT NULL,
    `application_fee` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `currency` VARCHAR(3) NOT NULL DEFAULT 'NGN',
    `opens_at` DATETIME NOT NULL,
    `closes_at` DATETIME NOT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `required_documents_json` JSON NULL,
    `instructions` TEXT NULL,
    `created_by` BIGINT UNSIGNED NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_adm_sessions_dates` (`opens_at`, `closes_at`, `is_active`),
    INDEX `idx_adm_sessions_academic_session` (`academic_session_id`),
    CONSTRAINT `fk_adm_sessions_academic_session` FOREIGN KEY (`academic_session_id`) REFERENCES `sessions` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_adm_sessions_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Admission Applications Docket Table (Guardian Level)
CREATE TABLE IF NOT EXISTS `admission_applications` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `application_number` VARCHAR(50) NOT NULL UNIQUE,
    `admission_session_id` BIGINT UNSIGNED NOT NULL,
    `applicant_user_id` BIGINT UNSIGNED NOT NULL,
    `status` ENUM('draft', 'submitted', 'under_review', 'approved', 'rejected') NOT NULL DEFAULT 'draft',
    `rejection_reason` TEXT NULL,
    `submitted_at` DATETIME NULL,
    `reviewed_at` DATETIME NULL,
    `reviewed_by` BIGINT UNSIGNED NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_adm_app_status` (`status`),
    INDEX `idx_adm_app_applicant` (`applicant_user_id`),
    INDEX `idx_adm_app_session` (`admission_session_id`),
    CONSTRAINT `fk_adm_app_session` FOREIGN KEY (`admission_session_id`) REFERENCES `admission_sessions` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_adm_app_applicant` FOREIGN KEY (`applicant_user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_adm_app_reviewer` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Admission Wards Table (Ward-level details, fee status per ward)
CREATE TABLE IF NOT EXISTS `admission_wards` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `application_id` BIGINT UNSIGNED NOT NULL,
    `first_name` VARCHAR(100) NOT NULL,
    `middle_name` VARCHAR(100) NULL,
    `last_name` VARCHAR(100) NOT NULL,
    `date_of_birth` DATE NOT NULL,
    `gender` ENUM('male', 'female') NOT NULL,
    `applying_for_level_id` BIGINT UNSIGNED NOT NULL,
    `class_grade` VARCHAR(50) NOT NULL,
    `curriculum_choice` VARCHAR(50) NULL,
    `use_school_bus` TINYINT(1) NOT NULL DEFAULT 0,
    `previous_school` VARCHAR(255) NULL,
    `last_grade_passed` VARCHAR(50) NULL,
    `medical_notes` TEXT NULL,
    `passport_photo_file_id` BIGINT UNSIGNED NULL,
    `birth_certificate_file_id` BIGINT UNSIGNED NULL,
    `previous_report_file_id` BIGINT UNSIGNED NULL,
    `payment_status` ENUM('unpaid', 'pending', 'paid') NOT NULL DEFAULT 'unpaid',
    `converted_student_id` BIGINT UNSIGNED NULL UNIQUE,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_adm_ward_app` (`application_id`),
    INDEX `idx_adm_ward_level` (`applying_for_level_id`),
    INDEX `idx_adm_ward_payment` (`payment_status`),
    INDEX `idx_adm_ward_converted` (`converted_student_id`),
    CONSTRAINT `fk_adm_ward_app` FOREIGN KEY (`application_id`) REFERENCES `admission_applications` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_adm_ward_level` FOREIGN KEY (`applying_for_level_id`) REFERENCES `academic_levels` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_adm_ward_passport` FOREIGN KEY (`passport_photo_file_id`) REFERENCES `files` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_adm_ward_birth_cert` FOREIGN KEY (`birth_certificate_file_id`) REFERENCES `files` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_adm_ward_prev_report` FOREIGN KEY (`previous_report_file_id`) REFERENCES `files` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_adm_ward_student` FOREIGN KEY (`converted_student_id`) REFERENCES `students` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Admission Payments Ledger (Per Ward Fee Enforcement)
CREATE TABLE IF NOT EXISTS `admission_payments` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `application_id` BIGINT UNSIGNED NOT NULL,
    `ward_id` BIGINT UNSIGNED NOT NULL,
    `reference` VARCHAR(64) NOT NULL UNIQUE,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `amount` DECIMAL(10,2) NOT NULL,
    `currency` VARCHAR(3) NOT NULL DEFAULT 'NGN',
    `channel` VARCHAR(30) NOT NULL DEFAULT 'paystack',
    `status` ENUM('pending', 'successful', 'failed') NOT NULL DEFAULT 'pending',
    `gateway_reference` VARCHAR(100) NULL,
    `metadata` JSON NULL,
    `paid_at` DATETIME NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_adm_pay_ref` (`reference`),
    INDEX `idx_adm_pay_app` (`application_id`),
    INDEX `idx_adm_pay_ward` (`ward_id`),
    INDEX `idx_adm_pay_status` (`status`),
    CONSTRAINT `fk_adm_pay_app` FOREIGN KEY (`application_id`) REFERENCES `admission_applications` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_adm_pay_ward` FOREIGN KEY (`ward_id`) REFERENCES `admission_wards` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_adm_pay_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Admission Status Transition History
CREATE TABLE IF NOT EXISTS `admission_status_history` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `application_id` BIGINT UNSIGNED NOT NULL,
    `from_status` VARCHAR(30) NOT NULL,
    `to_status` VARCHAR(30) NOT NULL,
    `comment` TEXT NULL,
    `changed_by` BIGINT UNSIGNED NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_adm_hist_app` (`application_id`),
    CONSTRAINT `fk_adm_hist_app` FOREIGN KEY (`application_id`) REFERENCES `admission_applications` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_adm_hist_user` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
