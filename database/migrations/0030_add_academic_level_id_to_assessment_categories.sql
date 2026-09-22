-- Migration: 0030_add_academic_level_id_to_assessment_categories.sql
-- Description: Adds academic_level_id column to assessment_categories to allow level-specific continuous assessment weights

ALTER TABLE `assessment_categories` 
ADD COLUMN `academic_level_id` BIGINT UNSIGNED NULL AFTER `term_id`;

ALTER TABLE `assessment_categories` 
ADD CONSTRAINT `fk_assessment_cat_level_id` 
FOREIGN KEY (`academic_level_id`) REFERENCES `academic_levels` (`id`) ON DELETE CASCADE;

ALTER TABLE `assessment_categories` 
ADD INDEX `idx_assessment_cat_level` (`academic_level_id`);
