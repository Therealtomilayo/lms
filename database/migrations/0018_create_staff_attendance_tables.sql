-- Migration: 0018_create_staff_attendance_tables
-- Description: Creates staff attendance, geofence breaches, and default geofence configurations

CREATE TABLE IF NOT EXISTS `staff_attendance` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `session_id` BIGINT UNSIGNED NULL,
    `term_id` BIGINT UNSIGNED NULL,
    `attendance_date` DATE NOT NULL,
    `clock_in_at` DATETIME NOT NULL,
    `clock_out_at` DATETIME NULL,
    `clock_in_latitude` DECIMAL(10, 8) NOT NULL,
    `clock_in_longitude` DECIMAL(11, 8) NOT NULL,
    `clock_in_distance_meters` INT UNSIGNED NOT NULL,
    `clock_out_latitude` DECIMAL(10, 8) NULL,
    `clock_out_longitude` DECIMAL(11, 8) NULL,
    `clock_out_distance_meters` INT UNSIGNED NULL,
    `status` ENUM('present', 'late', 'half_day', 'absent', 'excused') NOT NULL DEFAULT 'present',
    `is_late` TINYINT(1) NOT NULL DEFAULT 0,
    `is_early_departure` TINYINT(1) NOT NULL DEFAULT 0,
    `work_duration_minutes` INT UNSIGNED NULL,
    `device_fingerprint` VARCHAR(255) NULL,
    `ip_address` VARCHAR(45) NULL,
    `user_agent` VARCHAR(500) NULL,
    `notes` TEXT NULL,
    `is_offline_sync` TINYINT(1) NOT NULL DEFAULT 0,
    `synced_at` DATETIME NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_staff_attendance_user_date` (`user_id`, `attendance_date`),
    INDEX `idx_staff_attendance_date` (`attendance_date`),
    INDEX `idx_staff_attendance_user` (`user_id`),
    INDEX `idx_staff_attendance_status` (`status`),
    CONSTRAINT `fk_staff_attendance_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `staff_attendance_breaches` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `attempt_type` ENUM('clock_in', 'clock_out') NOT NULL DEFAULT 'clock_in',
    `attempted_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `latitude` DECIMAL(10, 8) NOT NULL,
    `longitude` DECIMAL(11, 8) NOT NULL,
    `distance_meters` INT UNSIGNED NOT NULL,
    `allowed_radius_meters` INT UNSIGNED NOT NULL,
    `ip_address` VARCHAR(45) NULL,
    `device_fingerprint` VARCHAR(255) NULL,
    `user_agent` VARCHAR(500) NULL,
    `failure_reason` VARCHAR(255) NOT NULL,
    INDEX `idx_breaches_user` (`user_id`),
    INDEX `idx_breaches_date` (`attempted_at`),
    CONSTRAINT `fk_breaches_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed default system settings for geofence and workday rules if not present
INSERT INTO `system_settings` (`setting_key`, `setting_value`, `is_secret`)
VALUES 
    ('staff_geofence_latitude', '9.08820000', 0),
    ('staff_geofence_longitude', '7.46410000', 0),
    ('staff_geofence_radius_meters', '250', 0),
    ('staff_workday_start_time', '08:00', 0),
    ('staff_workday_late_threshold', '08:15', 0),
    ('staff_workday_end_time', '15:30', 0),
    ('staff_geofence_enforced', '1', 0)
ON DUPLICATE KEY UPDATE `setting_value` = `setting_value`;
