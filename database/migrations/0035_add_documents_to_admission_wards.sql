-- Migration: 0035_add_documents_to_admission_wards
-- Description: Adds parent_passport_file_id, authorized_picker_passport_file_id, and immunization_record_file_id to admission_wards table

ALTER TABLE `admission_wards`
    ADD COLUMN `parent_passport_file_id` BIGINT UNSIGNED NULL AFTER `previous_report_file_id`,
    ADD COLUMN `authorized_picker_passport_file_id` BIGINT UNSIGNED NULL AFTER `parent_passport_file_id`,
    ADD COLUMN `immunization_record_file_id` BIGINT UNSIGNED NULL AFTER `authorized_picker_passport_file_id`,
    ADD CONSTRAINT `fk_adm_ward_parent_passport` FOREIGN KEY (`parent_passport_file_id`) REFERENCES `files` (`id`) ON DELETE SET NULL,
    ADD CONSTRAINT `fk_adm_ward_picker_passport` FOREIGN KEY (`authorized_picker_passport_file_id`) REFERENCES `files` (`id`) ON DELETE SET NULL,
    ADD CONSTRAINT `fk_adm_ward_immunization` FOREIGN KEY (`immunization_record_file_id`) REFERENCES `files` (`id`) ON DELETE SET NULL;
