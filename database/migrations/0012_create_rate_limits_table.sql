-- Migration: 0012_create_rate_limits_table
-- Description: Rate limiting tracking table with auto-expiring keys

CREATE TABLE IF NOT EXISTS `rate_limits` (
    `key` VARCHAR(255) PRIMARY KEY,
    `hits` INT UNSIGNED NOT NULL DEFAULT 1,
    `expires_at` INT UNSIGNED NOT NULL,
    INDEX `idx_rate_limits_expires_at` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
