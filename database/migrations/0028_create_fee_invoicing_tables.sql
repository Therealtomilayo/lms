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
