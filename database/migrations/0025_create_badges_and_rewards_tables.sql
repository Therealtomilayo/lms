-- Migration 0025: Create Badges and Rewards System (SRS §33, §57 Phase 3)

CREATE TABLE IF NOT EXISTS badges (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    description TEXT NOT NULL,
    category ENUM('academic', 'attendance', 'progression', 'citizenship') NOT NULL DEFAULT 'academic',
    icon_name VARCHAR(50) NOT NULL DEFAULT 'award',
    color_scheme VARCHAR(50) NOT NULL DEFAULT 'brand',
    is_system BOOLEAN NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS student_badges (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id BIGINT UNSIGNED NOT NULL,
    badge_id BIGINT UNSIGNED NOT NULL,
    awarded_by BIGINT UNSIGNED NOT NULL,
    class_subject_id BIGINT UNSIGNED NULL,
    session_id BIGINT UNSIGNED NULL,
    term_id BIGINT UNSIGNED NULL,
    reason TEXT NOT NULL,
    awarded_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_sb_student FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    CONSTRAINT fk_sb_badge FOREIGN KEY (badge_id) REFERENCES badges(id) ON DELETE RESTRICT,
    CONSTRAINT fk_sb_awarded_by FOREIGN KEY (awarded_by) REFERENCES users(id) ON DELETE RESTRICT,
    CONSTRAINT fk_sb_class_subject FOREIGN KEY (class_subject_id) REFERENCES class_subjects(id) ON DELETE SET NULL,
    INDEX idx_sb_student (student_id),
    INDEX idx_sb_badge (badge_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Pre-seed standard school badges
INSERT INTO badges (name, slug, description, category, icon_name, color_scheme, is_system) VALUES
('Academic Excellence', 'academic-excellence', 'Demonstrated exceptional performance with top marks (>=90%) on coursework or CBT quizzes.', 'academic', 'award', 'brand', 1),
('Course Completer', 'course-completer', 'Successfully completed 100% of all required learning activities in a course module curriculum.', 'progression', 'check-circle', 'emerald', 1),
('Star of Punctuality', 'star-of-punctuality', 'Achieved exemplary, unblemished attendance and punctuality across the academic term.', 'attendance', 'clock', 'sky', 1),
('Most Improved Scholar', 'most-improved', 'Commended for outstanding academic growth, dedication, and consistent improvement.', 'academic', 'trending-up', 'purple', 1),
('Master Artisan', 'master-artisan', 'Awarded for exceptional creativity, precision, and diligence on coursework assignments.', 'academic', 'clipboard', 'amber', 1),
('Exemplary Citizen', 'claret-virtue', 'Demonstrated moral leadership, kindness, teamwork, and adherence to Claret core values.', 'citizenship', 'heart', 'rose', 1)
ON DUPLICATE KEY UPDATE name = VALUES(name);
