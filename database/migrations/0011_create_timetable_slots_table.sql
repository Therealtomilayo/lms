-- Migration: 0011_create_timetable_slots_table
-- Description: Weekly instructional timetable slots for classes, subjects, teachers, rooms, and terms

CREATE TABLE IF NOT EXISTS `timetable_slots` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `term_id` BIGINT UNSIGNED NOT NULL,
    `class_subject_id` BIGINT UNSIGNED NOT NULL,
    `day_of_week` ENUM('mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun') NOT NULL,
    `start_time` TIME NOT NULL,
    `end_time` TIME NOT NULL,
    `room` VARCHAR(50) NULL DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_timetable_term_class_subject` (`term_id`, `class_subject_id`),
    INDEX `idx_timetable_day_time` (`day_of_week`, `start_time`, `end_time`),
    CONSTRAINT `fk_timetable_term_id` FOREIGN KEY (`term_id`) REFERENCES `terms` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_timetable_class_subject_id` FOREIGN KEY (`class_subject_id`) REFERENCES `class_subjects` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
