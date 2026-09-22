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
