-- Migration: 0024_add_learning_progression_hardening_indexes.sql
-- Description: Composite index for cohort student subject enrollment queries in Phase 7

-- Check and add index on student_subject_enrollments for (class_subject_id, status)
ALTER TABLE `student_subject_enrollments`
    ADD INDEX `idx_sse_class_subject_status` (`class_subject_id`, `status`);
