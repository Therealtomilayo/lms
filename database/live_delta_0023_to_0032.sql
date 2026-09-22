-- ==========================================================================
-- CLARET LMS: ZERO-DATA-LOSS DELTA MIGRATION (0023 -> 0032)
-- Target: Live Production Database (if0_42686580_lms / portal-claretschools.xo.je)
-- Generated: 2026-09-22 09:26:52
-- Safe: 100% additive, NO DROP, NO TRUNCATE, preserves all live records
-- ==========================================================================

SET FOREIGN_KEY_CHECKS = 0;

-- --------------------------------------------------------------------------
-- MIGRATION: 0023_create_course_modules_and_items_tables.sql
-- --------------------------------------------------------------------------
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

INSERT IGNORE INTO `migrations` (`migration`, `batch`, `applied_at`)
VALUES ('0023_create_course_modules_and_items_tables.sql', (SELECT COALESCE(MAX(`batch`), 0) + 1 FROM `migrations` m2), NOW());

-- --------------------------------------------------------------------------
-- MIGRATION: 0024_add_learning_progression_hardening_indexes.sql
-- --------------------------------------------------------------------------
-- Migration: 0024_add_learning_progression_hardening_indexes.sql
-- Description: Composite index for cohort student subject enrollment queries in Phase 7

-- Check and add index on student_subject_enrollments for (class_subject_id, status)
ALTER TABLE `student_subject_enrollments`
    ADD INDEX `idx_sse_class_subject_status` (`class_subject_id`, `status`);

INSERT IGNORE INTO `migrations` (`migration`, `batch`, `applied_at`)
VALUES ('0024_add_learning_progression_hardening_indexes.sql', (SELECT COALESCE(MAX(`batch`), 0) + 1 FROM `migrations` m2), NOW());

-- --------------------------------------------------------------------------
-- MIGRATION: 0025_create_badges_and_rewards_tables.sql
-- --------------------------------------------------------------------------
-- Migration 0025: Create Badges and Rewards System (SRS §33, §57 Phase 3)

CREATE TABLE IF NOT EXISTS badges (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    description TEXT NOT NULL,
    category ENUM('academic', 'attendance', 'progression', 'citizenship') NOT NULL DEFAULT 'academic',
    icon_name VARCHAR(50) NOT NULL DEFAULT 'award',
    color_scheme VARCHAR(50) NOT NULL DEFAULT 'brand',
    is_system BOOLEAN NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS student_badges (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id BIGINT UNSIGNED NOT NULL,
    badge_id BIGINT UNSIGNED NOT NULL,
    awarded_by BIGINT UNSIGNED NOT NULL,
    class_subject_id BIGINT UNSIGNED NULL,
    session_id BIGINT UNSIGNED NULL,
    term_id BIGINT UNSIGNED NULL,
    reason TEXT NOT NULL,
    awarded_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_sb_student FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    CONSTRAINT fk_sb_badge FOREIGN KEY (badge_id) REFERENCES badges(id) ON DELETE RESTRICT,
    CONSTRAINT fk_sb_awarded_by FOREIGN KEY (awarded_by) REFERENCES users(id) ON DELETE RESTRICT,
    CONSTRAINT fk_sb_class_subject FOREIGN KEY (class_subject_id) REFERENCES class_subjects(id) ON DELETE SET NULL,
    INDEX idx_sb_student (student_id),
    INDEX idx_sb_badge (badge_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Pre-seed standard school badges
INSERT INTO badges (name, slug, description, category, icon_name, color_scheme, is_system) VALUES
('Academic Excellence', 'academic-excellence', 'Demonstrated exceptional performance with top marks (>=90%) on coursework or CBT quizzes.', 'academic', 'award', 'brand', 1),
('Course Completer', 'course-completer', 'Successfully completed 100% of all required learning activities in a course module curriculum.', 'progression', 'check-circle', 'emerald', 1),
('Star of Punctuality', 'star-of-punctuality', 'Achieved exemplary, unblemished attendance and punctuality across the academic term.', 'attendance', 'clock', 'sky', 1),
('Most Improved Scholar', 'most-improved', 'Commended for outstanding academic growth, dedication, and consistent improvement.', 'academic', 'trending-up', 'purple', 1),
('Master Artisan', 'master-artisan', 'Awarded for exceptional creativity, precision, and diligence on coursework assignments.', 'academic', 'clipboard', 'amber', 1),
('Exemplary Citizen', 'claret-virtue', 'Demonstrated moral leadership, kindness, teamwork, and adherence to Claret core values.', 'citizenship', 'heart', 'rose', 1)
ON DUPLICATE KEY UPDATE name = VALUES(name);

INSERT IGNORE INTO `migrations` (`migration`, `batch`, `applied_at`)
VALUES ('0025_create_badges_and_rewards_tables.sql', (SELECT COALESCE(MAX(`batch`), 0) + 1 FROM `migrations` m2), NOW());

-- --------------------------------------------------------------------------
-- MIGRATION: 0026_create_class_discussions_tables.sql
-- --------------------------------------------------------------------------
-- Migration 0026: Create Class Discussions and Communication Feeds (SRS §47, §57 Phase 3)
-- Strict Child Safeguarding: No 1-on-1 private teacher-student chat. Group communication only.

CREATE TABLE IF NOT EXISTS class_discussions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    class_subject_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    is_pinned BOOLEAN NOT NULL DEFAULT 0,
    is_locked BOOLEAN NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_cd_class_subject FOREIGN KEY (class_subject_id) REFERENCES class_subjects(id) ON DELETE CASCADE,
    CONSTRAINT fk_cd_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_cd_subject_pinned (class_subject_id, is_pinned, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS class_discussion_replies (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    discussion_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    content TEXT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_cdr_discussion FOREIGN KEY (discussion_id) REFERENCES class_discussions(id) ON DELETE CASCADE,
    CONSTRAINT fk_cdr_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_cdr_discussion (discussion_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `migrations` (`migration`, `batch`, `applied_at`)
VALUES ('0026_create_class_discussions_tables.sql', (SELECT COALESCE(MAX(`batch`), 0) + 1 FROM `migrations` m2), NOW());

-- --------------------------------------------------------------------------
-- MIGRATION: 0027_create_admissions_tables.sql
-- --------------------------------------------------------------------------
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

INSERT IGNORE INTO `migrations` (`migration`, `batch`, `applied_at`)
VALUES ('0027_create_admissions_tables.sql', (SELECT COALESCE(MAX(`batch`), 0) + 1 FROM `migrations` m2), NOW());

-- --------------------------------------------------------------------------
-- MIGRATION: 0028_create_fee_invoicing_tables.sql
-- --------------------------------------------------------------------------
-- Migration: 0028_create_fee_invoicing_tables.sql
-- Description: Creates fee categories, fee structures, structure items, student fee invoices, invoice items, and links payments to invoices (SRS §39, §57 Phase 4)

CREATE TABLE IF NOT EXISTS `fee_categories` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL UNIQUE,
    `description` TEXT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `fee_structures` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `session_id` BIGINT UNSIGNED NOT NULL,
    `term_id` BIGINT UNSIGNED NOT NULL,
    `academic_level_id` BIGINT UNSIGNED NULL,
    `class_id` BIGINT UNSIGNED NULL,
    `title` VARCHAR(150) NOT NULL,
    `currency` VARCHAR(3) NOT NULL DEFAULT 'NGN',
    `due_date` DATE NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_by` BIGINT UNSIGNED NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_fee_structures_term_level` (`session_id`, `term_id`, `academic_level_id`),
    CONSTRAINT `fk_fee_structures_session` FOREIGN KEY (`session_id`) REFERENCES `sessions` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_fee_structures_term` FOREIGN KEY (`term_id`) REFERENCES `terms` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_fee_structures_level` FOREIGN KEY (`academic_level_id`) REFERENCES `academic_levels` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_fee_structures_class` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_fee_structures_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `fee_structure_items` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `fee_structure_id` BIGINT UNSIGNED NOT NULL,
    `fee_category_id` BIGINT UNSIGNED NOT NULL,
    `name` VARCHAR(150) NOT NULL,
    `amount` DECIMAL(10,2) NOT NULL,
    `is_compulsory` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_fsi_structure` (`fee_structure_id`),
    CONSTRAINT `fk_fsi_structure` FOREIGN KEY (`fee_structure_id`) REFERENCES `fee_structures` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_fsi_category` FOREIGN KEY (`fee_category_id`) REFERENCES `fee_categories` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `fee_invoices` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `invoice_number` VARCHAR(50) NOT NULL UNIQUE,
    `student_id` BIGINT UNSIGNED NOT NULL,
    `parent_id` BIGINT UNSIGNED NULL,
    `class_id` BIGINT UNSIGNED NOT NULL,
    `session_id` BIGINT UNSIGNED NOT NULL,
    `term_id` BIGINT UNSIGNED NOT NULL,
    `subtotal` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `discount_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `total_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `amount_paid` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `balance_due` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `status` ENUM('unpaid', 'partially_paid', 'paid', 'overdue', 'waived') NOT NULL DEFAULT 'unpaid',
    `due_date` DATE NULL,
    `notes` TEXT NULL,
    `created_by` BIGINT UNSIGNED NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_student_term_invoice` (`student_id`, `session_id`, `term_id`),
    INDEX `idx_invoices_lookup` (`status`, `session_id`, `term_id`),
    INDEX `idx_invoices_student` (`student_id`),
    INDEX `idx_invoices_parent` (`parent_id`),
    CONSTRAINT `fk_invoices_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_invoices_parent` FOREIGN KEY (`parent_id`) REFERENCES `parents` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_invoices_class` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_invoices_session` FOREIGN KEY (`session_id`) REFERENCES `sessions` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_invoices_term` FOREIGN KEY (`term_id`) REFERENCES `terms` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_invoices_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `fee_invoice_items` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `invoice_id` BIGINT UNSIGNED NOT NULL,
    `fee_category_id` BIGINT UNSIGNED NULL,
    `name` VARCHAR(150) NOT NULL,
    `amount` DECIMAL(10,2) NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_fii_invoice` (`invoice_id`),
    CONSTRAINT `fk_fii_invoice` FOREIGN KEY (`invoice_id`) REFERENCES `fee_invoices` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_fii_category` FOREIGN KEY (`fee_category_id`) REFERENCES `fee_categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add invoice_id to payments table if not present
SET @col_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
      AND TABLE_NAME = 'payments' 
      AND COLUMN_NAME = 'invoice_id'
);

SET @sql = IF(@col_exists = 0, 'ALTER TABLE `payments` ADD COLUMN `invoice_id` BIGINT UNSIGNED NULL AFTER `term_id`', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Seed default institutional fee categories
INSERT IGNORE INTO `fee_categories` (`id`, `name`, `description`, `is_active`) VALUES
(1, 'Tuition Fee', 'Core academic instruction and classroom delivery', 1),
(2, 'Development Levy', 'School physical infrastructure and campus maintenance', 1),
(3, 'ICT & E-Learning Fee', 'Computer lab access, internet, and LMS portal technology', 1),
(4, 'PTA Levy', 'Parent-Teacher Association statutory contribution', 1),
(5, 'Laboratory & Practical Fee', 'Science and vocational laboratory consumables', 1),
(6, 'Uniform & Sportswear', 'Standard school uniform, Friday wear, and sports kits', 1),
(7, 'Co-curricular & Clubs', 'Student clubs, society materials, and leadership training', 1);

INSERT IGNORE INTO `migrations` (`migration`, `batch`, `applied_at`)
VALUES ('0028_create_fee_invoicing_tables.sql', (SELECT COALESCE(MAX(`batch`), 0) + 1 FROM `migrations` m2), NOW());

-- --------------------------------------------------------------------------
-- MIGRATION: 0029_add_fee_item_result_flags.sql
-- --------------------------------------------------------------------------
-- Migration: 0029_add_fee_item_result_flags.sql
-- Description: Adds is_required_for_result to fee_structure_items, and adds compulsory, result lock, and payment status to fee_invoice_items

SET @col_exists1 = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
      AND TABLE_NAME = 'fee_structure_items' 
      AND COLUMN_NAME = 'is_required_for_result'
);

SET @sql1 = IF(@col_exists1 = 0, 'ALTER TABLE `fee_structure_items` ADD COLUMN `is_required_for_result` TINYINT(1) NOT NULL DEFAULT 1 AFTER `is_compulsory`', 'SELECT 1');
PREPARE stmt1 FROM @sql1;
EXECUTE stmt1;
DEALLOCATE PREPARE stmt1;

SET @col_exists2 = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
      AND TABLE_NAME = 'fee_invoice_items' 
      AND COLUMN_NAME = 'is_required_for_result'
);

SET @sql2 = IF(@col_exists2 = 0, 'ALTER TABLE `fee_invoice_items` ADD COLUMN `is_compulsory` TINYINT(1) NOT NULL DEFAULT 1 AFTER `amount`, ADD COLUMN `is_required_for_result` TINYINT(1) NOT NULL DEFAULT 1 AFTER `is_compulsory`, ADD COLUMN `is_paid` TINYINT(1) NOT NULL DEFAULT 0 AFTER `is_required_for_result`, ADD COLUMN `paid_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER `is_paid`', 'SELECT 1');
PREPARE stmt2 FROM @sql2;
EXECUTE stmt2;
DEALLOCATE PREPARE stmt2;

INSERT IGNORE INTO `migrations` (`migration`, `batch`, `applied_at`)
VALUES ('0029_add_fee_item_result_flags.sql', (SELECT COALESCE(MAX(`batch`), 0) + 1 FROM `migrations` m2), NOW());

-- --------------------------------------------------------------------------
-- MIGRATION: 0030_add_academic_level_id_to_assessment_categories.sql
-- --------------------------------------------------------------------------
-- Migration: 0030_add_academic_level_id_to_assessment_categories.sql
-- Description: Adds academic_level_id column to assessment_categories to allow level-specific continuous assessment weights

ALTER TABLE `assessment_categories` 
ADD COLUMN `academic_level_id` BIGINT UNSIGNED NULL AFTER `term_id`;

ALTER TABLE `assessment_categories` 
ADD CONSTRAINT `fk_assessment_cat_level_id` 
FOREIGN KEY (`academic_level_id`) REFERENCES `academic_levels` (`id`) ON DELETE CASCADE;

ALTER TABLE `assessment_categories` 
ADD INDEX `idx_assessment_cat_level` (`academic_level_id`);

INSERT IGNORE INTO `migrations` (`migration`, `batch`, `applied_at`)
VALUES ('0030_add_academic_level_id_to_assessment_categories.sql', (SELECT COALESCE(MAX(`batch`), 0) + 1 FROM `migrations` m2), NOW());

-- --------------------------------------------------------------------------
-- MIGRATION: 0031_create_class_result_submissions_table.sql
-- --------------------------------------------------------------------------
-- Migration: 0031_create_class_result_submissions_table.sql
-- Description: Store terminal class result submissions by form teachers for admin assessment and approval

CREATE TABLE IF NOT EXISTS `class_result_submissions` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `class_id` BIGINT UNSIGNED NOT NULL,
    `term_id` BIGINT UNSIGNED NOT NULL,
    `submitted_by` BIGINT UNSIGNED NOT NULL,
    `submitted_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `status` ENUM('submitted', 'approved', 'rejected') NOT NULL DEFAULT 'submitted',
    `notes` TEXT NULL DEFAULT NULL,
    `reviewed_by` BIGINT UNSIGNED NULL DEFAULT NULL,
    `reviewed_at` DATETIME NULL DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_class_term_submission` (`class_id`, `term_id`),
    INDEX `idx_crs_status` (`status`),
    CONSTRAINT `fk_crs_class_id` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_crs_term_id` FOREIGN KEY (`term_id`) REFERENCES `terms` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_crs_submitted_by` FOREIGN KEY (`submitted_by`) REFERENCES `teachers` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_crs_reviewed_by` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `migrations` (`migration`, `batch`, `applied_at`)
VALUES ('0031_create_class_result_submissions_table.sql', (SELECT COALESCE(MAX(`batch`), 0) + 1 FROM `migrations` m2), NOW());

-- --------------------------------------------------------------------------
-- MIGRATION: 0032_create_external_notifications_table.sql
-- --------------------------------------------------------------------------
-- Migration: 0032_create_external_notifications_table.sql
-- Description: External notification logging and audit trail across Email, SMS, and WhatsApp

CREATE TABLE IF NOT EXISTS `external_notifications` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `channel` ENUM('email', 'sms', 'whatsapp') NOT NULL,
    `recipient` VARCHAR(191) NOT NULL,
    `user_id` BIGINT UNSIGNED NULL DEFAULT NULL,
    `event_type` VARCHAR(80) NOT NULL DEFAULT 'general',
    `subject` VARCHAR(255) NULL DEFAULT NULL,
    `message_body` TEXT NOT NULL,
    `status` ENUM('queued', 'sent', 'failed', 'delivered') NOT NULL DEFAULT 'sent',
    `gateway_provider` VARCHAR(50) NOT NULL DEFAULT 'log',
    `gateway_reference` VARCHAR(191) NULL DEFAULT NULL,
    `error_message` TEXT NULL DEFAULT NULL,
    `metadata` JSON NULL DEFAULT NULL,
    `sent_at` DATETIME NULL DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_ext_notif_channel` (`channel`),
    INDEX `idx_ext_notif_status` (`status`),
    INDEX `idx_ext_notif_recipient` (`recipient`),
    INDEX `idx_ext_notif_user_id` (`user_id`),
    INDEX `idx_ext_notif_event_type` (`event_type`),
    INDEX `idx_ext_notif_created_at` (`created_at`),
    CONSTRAINT `fk_ext_notif_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `migrations` (`migration`, `batch`, `applied_at`)
VALUES ('0032_create_external_notifications_table.sql', (SELECT COALESCE(MAX(`batch`), 0) + 1 FROM `migrations` m2), NOW());

-- ==========================================================================
-- REQUIRED SYSTEM SEEDERS & CONFIGURATION (IDEMPOTENT / INSERT IGNORE)
-- ==========================================================================

-- 1. System Settings: attendance_late_weight
INSERT IGNORE INTO `system_settings` (`setting_key`, `setting_value`, `is_secret`, `updated_by`, `updated_at`)
VALUES ('attendance_late_weight', '0.7', 0, 1, NOW());

-- 2. Default Achievement Badges Catalog
INSERT IGNORE INTO `badges` (`id`, `name`, `slug`, `description`, `category`, `icon_name`, `color_scheme`, `is_system`, `created_at`)
VALUES
(1, 'Academic Excellence', 'academic-excellence', 'Demonstrated exceptional performance with top marks (>=90%) on coursework or CBT quizzes.', 'academic', 'award', 'brand', 1, NOW()),
(2, 'Course Completer', 'course-completer', 'Successfully completed 100% of all required learning activities in a course module curriculum.', 'progression', 'check-circle', 'emerald', 1, NOW()),
(3, 'Discussion Pioneer', 'discussion-pioneer', 'Recognizes active, thoughtful contributions and peer help in class group discussions.', 'engagement', 'message-square', 'sky', 1, NOW()),
(4, 'Perfect Attendance', 'perfect-attendance', 'Attained flawless attendance record across morning homeroom roll calls.', 'attendance', 'calendar-check', 'indigo', 1, NOW()),
(5, 'Top Scholar', 'top-scholar', 'Ranked 1st place in terminal class broadsheet across all subjects in the term.', 'honor', 'crown', 'amber', 1, NOW()),
(6, 'Subject Master', 'subject-master', 'Achieved highest composite cumulative score in a specific curriculum subject.', 'academic', 'star', 'purple', 1, NOW());

-- 3. Default Active Admission Session for Prospective Applicants
INSERT IGNORE INTO `admission_sessions` (`id`, `academic_session_id`, `title`, `application_fee`, `currency`, `opens_at`, `closes_at`, `is_active`, `instructions`, `created_by`, `created_at`, `updated_at`)
SELECT 1, id, CONCAT(name, ' Admissions'), 10000.00, 'NGN', NOW(), DATE_ADD(NOW(), INTERVAL 6 MONTH), 1, 'Welcome to Claret International School online admission application. Please ensure you have valid digital copies of your ward\'s Birth Certificate, Passport Photograph, and previous academic report.', 1, NOW(), NOW()
FROM `sessions` WHERE `status` = 'active' LIMIT 1;

SET FOREIGN_KEY_CHECKS = 1;
