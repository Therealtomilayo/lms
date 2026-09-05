-- Migration: 0013_create_skills_ratings_and_remark_presets_tables
-- Description: Affective and Psychomotor skills catalog, term-scoped student ratings, and configurable remark suggestion presets

CREATE TABLE IF NOT EXISTS `skills` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `category` VARCHAR(30) NOT NULL, -- 'psychomotor' or 'affective'
    `display_order` INT NOT NULL DEFAULT 1,
    `status` VARCHAR(20) NOT NULL DEFAULT 'active',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_skills_cat_status` (`category`, `status`, `display_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `student_skill_ratings` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `student_id` BIGINT UNSIGNED NOT NULL,
    `term_id` BIGINT UNSIGNED NOT NULL,
    `skill_id` BIGINT UNSIGNED NOT NULL,
    `rating` TINYINT UNSIGNED NOT NULL DEFAULT 3, -- 1 to 5 scale
    `recorded_by` BIGINT UNSIGNED NULL DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_student_term_skill` (`student_id`, `term_id`, `skill_id`),
    INDEX `idx_skill_ratings_term_student` (`term_id`, `student_id`),
    INDEX `idx_skill_ratings_skill` (`skill_id`),
    CONSTRAINT `fk_skill_ratings_student_id` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_skill_ratings_term_id` FOREIGN KEY (`term_id`) REFERENCES `terms` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_skill_ratings_skill_id` FOREIGN KEY (`skill_id`) REFERENCES `skills` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_skill_ratings_recorded_by` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `remark_presets` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `type` VARCHAR(30) NOT NULL DEFAULT 'teacher', -- 'teacher', 'principal', 'general'
    `category` VARCHAR(50) NOT NULL DEFAULT 'general', -- 'excellent', 'good', 'average', 'improvement', 'conduct'
    `text` TEXT NOT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_remark_presets_type_active` (`type`, `is_active`, `category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Pre-populate Standard Nigerian Secondary School Psychomotor Skills
INSERT INTO `skills` (`name`, `category`, `display_order`, `status`) VALUES
('Crafts & Practical Project', 'psychomotor', 1, 'active'),
('Drawing & Creative Design', 'psychomotor', 2, 'active'),
('Games & Team Collaboration', 'psychomotor', 3, 'active'),
('Handwriting & Presentation', 'psychomotor', 4, 'active'),
('Musical & Cultural Skills', 'psychomotor', 5, 'active'),
('Sports & Physical Education', 'psychomotor', 6, 'active'),
('Laboratory & Scientific Practical', 'psychomotor', 7, 'active');

-- Pre-populate Standard Nigerian Secondary School Affective Traits
INSERT INTO `skills` (`name`, `category`, `display_order`, `status`) VALUES
('Punctuality & Time Management', 'affective', 1, 'active'),
('Neatness & Personal Hygiene', 'affective', 2, 'active'),
('Politeness & Courtesy', 'affective', 3, 'active'),
('Honesty & Moral Uprightness', 'affective', 4, 'active'),
('Relationship with Peers & Staff', 'affective', 5, 'active'),
('Attentiveness & Class Focus', 'affective', 6, 'active'),
('Leadership & Responsibility', 'affective', 7, 'active'),
('Self-Control & Discipline', 'affective', 8, 'active');

-- Pre-populate Configurable Teacher Remark Presets
INSERT INTO `remark_presets` (`type`, `category`, `text`, `is_active`) VALUES
('teacher', 'excellent', 'A bright, respectful and hardworking pupil who shows great potential. Keep up the excellent attitude.', 1),
('teacher', 'excellent', 'An intelligent and enthusiastic pupil who participates actively in all learning activities. Outstanding term!', 1),
('teacher', 'good', 'Has made commendable progress this term and behaves well. Steady practice will yield even higher results.', 1),
('teacher', 'good', 'A quiet and well-behaved pupil making gradual progress in coursework. Regular revision is encouraged.', 1),
('teacher', 'average', 'Pleasant and cooperative in class, but needs to maintain consistency and put more effort into difficult topics.', 1),
('teacher', 'improvement', 'Capable of better performance with increased attentiveness during lessons and daily study routine.', 1),
('teacher', 'improvement', 'Needs to pay closer attention to classroom instructions and complete coursework assignments punctually.', 1);

-- Pre-populate Configurable Principal Remark Presets
INSERT INTO `remark_presets` (`type`, `category`, `text`, `is_active`) VALUES
('principal', 'excellent', 'An outstanding performance throughout the academic term. Highly commendable!', 1),
('principal', 'excellent', 'Consistently excellent work and exemplary conduct. Keep striving for the heights.', 1),
('principal', 'good', 'A very good result. Maintain this positive standard in the upcoming term.', 1),
('principal', 'good', 'A promising student who works diligently. Well done.', 1),
('principal', 'average', 'A satisfactory outcome, but there is clear capacity for higher attainment with greater dedication.', 1),
('principal', 'improvement', 'Work harder next term to attain your true academic potential.', 1),
('principal', 'improvement', 'More serious commitment to academic studies and school discipline is required.', 1);
