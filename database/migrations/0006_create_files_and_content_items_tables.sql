-- Migration: 0006_create_files_and_content_items_tables
-- Description: Centralized protected file metadata and class-subject content delivery items

CREATE TABLE IF NOT EXISTS `files` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `uuid` CHAR(36) NOT NULL UNIQUE,
    `storage_key` VARCHAR(255) NOT NULL UNIQUE,
    `original_name` VARCHAR(255) NOT NULL,
    `mime_type` VARCHAR(120) NOT NULL,
    `size_bytes` BIGINT UNSIGNED NOT NULL,
    `sha256` VARCHAR(64) NOT NULL,
    `uploaded_by` BIGINT UNSIGNED NOT NULL,
    `owner_type` VARCHAR(50) NOT NULL,
    `owner_id` BIGINT UNSIGNED NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `deleted_at` DATETIME NULL DEFAULT NULL,
    INDEX `idx_files_uuid` (`uuid`),
    INDEX `idx_files_storage_key` (`storage_key`),
    INDEX `idx_files_uploaded_by` (`uploaded_by`),
    INDEX `idx_files_owner` (`owner_type`, `owner_id`),
    INDEX `idx_files_deleted_at` (`deleted_at`),
    CONSTRAINT `fk_files_uploaded_by` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `content_items` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `class_subject_id` BIGINT UNSIGNED NOT NULL,
    `teacher_id` BIGINT UNSIGNED NOT NULL,
    `topic` VARCHAR(100) NULL DEFAULT NULL,
    `title` VARCHAR(200) NOT NULL,
    `description` TEXT NULL DEFAULT NULL,
    `type` ENUM('note', 'video', 'link', 'document') NOT NULL,
    `file_id` BIGINT UNSIGNED NULL DEFAULT NULL,
    `external_url` VARCHAR(2048) NULL DEFAULT NULL,
    `published_at` DATETIME NULL DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_content_items_class_subject` (`class_subject_id`),
    INDEX `idx_content_items_teacher` (`teacher_id`),
    INDEX `idx_content_items_file` (`file_id`),
    INDEX `idx_content_items_published_at` (`published_at`),
    CONSTRAINT `fk_content_items_class_subject_id` FOREIGN KEY (`class_subject_id`) REFERENCES `class_subjects` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_content_items_teacher_id` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_content_items_file_id` FOREIGN KEY (`file_id`) REFERENCES `files` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
