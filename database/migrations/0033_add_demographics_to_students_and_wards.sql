-- Migration: 0033_add_demographics_to_students_and_wards
-- Description: Adds state_of_origin, lga, nationality, religion, and admission_date to students and admission_wards, synchronizes assessment category max points to weight percentages, and adds institutional settings keys.

-- 1. Alter students table
ALTER TABLE `students`
    ADD COLUMN `state_of_origin` VARCHAR(100) NULL AFTER `gender`,
    ADD COLUMN `lga` VARCHAR(100) NULL AFTER `state_of_origin`,
    ADD COLUMN `nationality` VARCHAR(100) NOT NULL DEFAULT 'Nigerian' AFTER `lga`,
    ADD COLUMN `religion` VARCHAR(50) NULL AFTER `nationality`,
    ADD COLUMN `admission_date` DATE NULL AFTER `religion`;

-- 2. Alter admission_wards table
ALTER TABLE `admission_wards`
    ADD COLUMN `state_of_origin` VARCHAR(100) NULL AFTER `gender`,
    ADD COLUMN `lga` VARCHAR(100) NULL AFTER `state_of_origin`,
    ADD COLUMN `nationality` VARCHAR(100) NOT NULL DEFAULT 'Nigerian' AFTER `lga`,
    ADD COLUMN `religion` VARCHAR(50) NULL AFTER `nationality`;

-- 3. Synchronize assessment categories max_points to match weight_percentage
UPDATE `assessment_categories`
SET `max_points` = `weight_percentage`
WHERE `weight_percentage` > 0;

-- 4. Insert default system settings if missing
INSERT IGNORE INTO `system_settings` (`setting_key`, `setting_value`, `is_secret`, `updated_at`)
VALUES
    ('head_teacher_name', 'Mrs. N. Okon', 0, NOW()),
    ('head_teacher_title', 'Head of School', 0, NOW()),
    ('head_teacher_signature_url', '/assets/img/teacher-signature.png', 0, NOW()),
    ('school_stamp_url', '/assets/img/claret-stamp.png', 0, NOW()),
    ('school_website', 'www.claretschools.org', 0, NOW());
