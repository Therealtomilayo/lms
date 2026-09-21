-- Migration: 0029_add_fee_item_result_flags.sql
-- Description: Adds is_required_for_result to fee_structure_items, and adds compulsory, result lock, and payment status to fee_invoice_items

SET @col_exists1 = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
      AND TABLE_NAME = 'fee_structure_items' 
      AND COLUMN_NAME = 'is_required_for_result'
);

SET @sql1 = IF(@col_exists1 = 0, 'ALTER TABLE `fee_structure_items` ADD COLUMN `is_required_for_result` TINYINT(1) NOT NULL DEFAULT 1 AFTER `is_compulsory`', 'SELECT 1');
PREPARE stmt1 FROM @sql1;
EXECUTE stmt1;
DEALLOCATE PREPARE stmt1;

SET @col_exists2 = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
      AND TABLE_NAME = 'fee_invoice_items' 
      AND COLUMN_NAME = 'is_required_for_result'
);

SET @sql2 = IF(@col_exists2 = 0, 'ALTER TABLE `fee_invoice_items` ADD COLUMN `is_compulsory` TINYINT(1) NOT NULL DEFAULT 1 AFTER `amount`, ADD COLUMN `is_required_for_result` TINYINT(1) NOT NULL DEFAULT 1 AFTER `is_compulsory`, ADD COLUMN `is_paid` TINYINT(1) NOT NULL DEFAULT 0 AFTER `is_required_for_result`, ADD COLUMN `paid_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER `is_paid`', 'SELECT 1');
PREPARE stmt2 FROM @sql2;
EXECUTE stmt2;
DEALLOCATE PREPARE stmt2;
