-- Migration: 0021_create_document_sections_table
-- Description: Creates logical document sections table for partitioning PDF learning materials into navigable subsections without altering original files (Phase 3)

CREATE TABLE IF NOT EXISTS `document_sections` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `content_item_id` BIGINT UNSIGNED NOT NULL,
    `title` VARCHAR(200) NOT NULL,
    `start_page` INT UNSIGNED NOT NULL,
    `end_page` INT UNSIGNED NOT NULL,
    `sequence_order` INT UNSIGNED NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_ds_content_item` (`content_item_id`),
    INDEX `idx_ds_order` (`content_item_id`, `sequence_order`),
    CONSTRAINT `fk_ds_content_item` FOREIGN KEY (`content_item_id`) REFERENCES `content_items` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
