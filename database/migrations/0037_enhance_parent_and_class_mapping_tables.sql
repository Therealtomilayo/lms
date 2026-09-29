-- Migration: 0037_enhance_parent_and_class_mapping_tables
-- Description: Decouple parents from mandatory users, add contact fields, session scope and chunk tracking to imports, and persistent class mappings

-- 1. Alter parents table: Step A - Make user_id nullable and add contact fields
ALTER TABLE `parents`
    MODIFY COLUMN `user_id` BIGINT UNSIGNED NULL,
    ADD COLUMN `name` VARCHAR(255) NULL AFTER `user_id`,
    ADD COLUMN `phone` VARCHAR(50) NULL AFTER `name`,
    ADD COLUMN `email` VARCHAR(255) NULL AFTER `phone`,
    ADD INDEX `idx_parents_phone` (`phone`),
    ADD INDEX `idx_parents_email` (`email`);

-- Step B - Backfill existing parent records from active users
UPDATE `parents` p
JOIN `users` u ON u.id = p.user_id
SET p.name = u.name,
    p.phone = u.phone,
    p.email = u.email;

-- Step C - Fallback for any orphaned rows before enforcing NOT NULL
UPDATE `parents` SET `name` = 'Parent/Guardian' WHERE `name` IS NULL OR TRIM(`name`) = '';

-- Step D - Enforce NOT NULL on parents.name
ALTER TABLE `parents`
    MODIFY COLUMN `name` VARCHAR(255) NOT NULL;

-- 2. Enhance imports table with target academic session and chunk idempotency tracking
ALTER TABLE `imports`
    ADD COLUMN `session_id` BIGINT UNSIGNED NULL AFTER `type`,
    ADD COLUMN `processed_chunks_json` TEXT NULL AFTER `invalid_rows`,
    ADD CONSTRAINT `fk_imports_session` FOREIGN KEY (`session_id`) REFERENCES `sessions` (`id`) ON DELETE SET NULL;

-- 3. Create persistent class mappings table
CREATE TABLE IF NOT EXISTS `import_class_mappings` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `raw_pattern` VARCHAR(100) NOT NULL,
    `normalized_pattern` VARCHAR(100) NOT NULL UNIQUE,
    `canonical_class_id` BIGINT UNSIGNED NOT NULL,
    `created_by` BIGINT UNSIGNED NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_icm_class` (`canonical_class_id`),
    CONSTRAINT `fk_icm_class` FOREIGN KEY (`canonical_class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_icm_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
