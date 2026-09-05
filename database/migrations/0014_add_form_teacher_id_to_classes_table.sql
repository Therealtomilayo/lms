-- Migration: 0014_add_form_teacher_id_to_classes_table.sql
-- Description: Add form_teacher_id column to classes table referencing teachers(id)

ALTER TABLE `classes` 
ADD COLUMN `form_teacher_id` BIGINT UNSIGNED NULL AFTER `section_arm`;

ALTER TABLE `classes` 
ADD CONSTRAINT `fk_classes_form_teacher_id` 
FOREIGN KEY (`form_teacher_id`) REFERENCES `teachers` (`id`) ON DELETE SET NULL;

ALTER TABLE `classes` 
ADD INDEX `idx_classes_form_teacher_id` (`form_teacher_id`);
