-- Migration: 0039_enhance_fee_breakdown_and_bus_tracking.sql
-- Description: Adds applicability targeting to fee structure items, adds use_school_bus to students, backfills bus status, and seeds missing institutional fee categories

SET @dbname = DATABASE();

-- 1. Add applicability to fee_structure_items
SET @col_exists1 = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = @dbname 
      AND TABLE_NAME = 'fee_structure_items' 
      AND COLUMN_NAME = 'applicability'
);

SET @sql1 = IF(@col_exists1 = 0, 
    'ALTER TABLE `fee_structure_items` ADD COLUMN `applicability` ENUM(\'all\', \'new_students_only\', \'bus_users_only\', \'optional\') NOT NULL DEFAULT \'all\' AFTER `is_required_for_result`;', 
    'SELECT 1;'
);
PREPARE stmt1 FROM @sql1;
EXECUTE stmt1;
DEALLOCATE PREPARE stmt1;

-- 2. Add use_school_bus to students
SET @col_exists2 = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = @dbname 
      AND TABLE_NAME = 'students' 
      AND COLUMN_NAME = 'use_school_bus'
);

SET @sql2 = IF(@col_exists2 = 0, 
    'ALTER TABLE `students` ADD COLUMN `use_school_bus` TINYINT(1) NOT NULL DEFAULT 0 AFTER `current_class_id`;', 
    'SELECT 1;'
);
PREPARE stmt2 FROM @sql2;
EXECUTE stmt2;
DEALLOCATE PREPARE stmt2;

-- 3. Backfill use_school_bus from admission_wards to students
UPDATE `students` s
INNER JOIN `admission_wards` w ON w.converted_student_id = s.id
SET s.use_school_bus = w.use_school_bus
WHERE w.converted_student_id IS NOT NULL;

-- 4. Seed standard categories if missing
INSERT IGNORE INTO `fee_categories` (`id`, `name`, `description`, `is_active`) VALUES
(8, 'Books & Learning Materials', 'Textbooks, workbooks, stationery, and instructional packs', 1),
(9, 'Health & Medical Services', 'Institutional infirmary care, first aid, and basic student health checks', 1),
(10, 'School Bus & Transportation', 'Dedicated campus bus shuttle and daily transit service', 1);
