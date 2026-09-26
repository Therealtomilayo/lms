-- Migration: 0036_add_status_and_decision_note_to_admission_wards
-- Description: Adds status and decision_note columns to admission_wards table for per-ward admission decisions

ALTER TABLE `admission_wards`
    ADD COLUMN `status` ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending' AFTER `payment_status`,
    ADD COLUMN `decision_note` TEXT NULL AFTER `status`;

-- Backfill existing converted wards as approved
UPDATE `admission_wards`
SET `status` = 'approved'
WHERE `converted_student_id` IS NOT NULL;
