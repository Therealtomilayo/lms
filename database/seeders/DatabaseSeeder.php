<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Core\Database;
use PDO;

/**
 * Master Database Seeder for Claret LMS
 *
 * Populates a complete, demonstrable test and staging dataset compliant with
 * Nigerian National Educational Curriculum (EYFS, Primary, JSS, SSS),
 * multi-arm classes, specialized departments, assigned form masters & subject teachers,
 * and authentic Nigerian student/parent demographics.
 */
class DatabaseSeeder
{
    private PDO $db;
    private string $defaultPasswordHash;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::getConnection();
        // Default password for all seeded users: Password123!
        $this->defaultPasswordHash = password_hash('Password123!', PASSWORD_DEFAULT);
    }

    public function run(): array
    {
        $summary = [];

        Database::transaction(function (PDO $pdo) use (&$summary) {
            $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
            if ($driver === 'mysql') {
                $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
            }

            // 1. System Settings
            $this->seedSystemSettings($pdo);
            $summary['settings'] = 'Configured';

            // 2. Grading Scales
            $scaleIds = $this->seedGradingScales($pdo);
            $summary['grading'] = 'Configured';

            // 3. Academic Stages & Levels
            $this->seedAcademicStages($pdo);
            $levelIds = $this->seedAcademicLevels($pdo, $scaleIds);
            $sessionId = $this->seedSession($pdo);
            $termIds = $this->seedTerms($pdo, $sessionId);
            $this->seedAssessmentCategories($pdo, $sessionId, $termIds[0]);

            // 4. Subjects (10 for each stage + specialized senior secondary departments)
            $subjectIds = $this->seedSubjects($pdo);
            $summary['subjects'] = count($subjectIds) . ' subjects configured';

            // 5. Users & Roles (Admins, Teachers, Students, Parents)
            $users = $this->seedUsersAndRoles($pdo);
            $teacherIds = $this->seedTeachers($pdo, $users);
            $summary['teachers'] = count($teacherIds) . ' teachers seeded';

            // 6. Classes & Arms (Arm A & B for EYFS-JSS; Science/Art/Commercial for SSS)
            $classIds = $this->seedClasses($pdo, $levelIds, $teacherIds);
            $summary['classes'] = count($classIds) . ' classes and arms configured';

            // 7. Students & Parents (Realistic Nigerian demographics, 10+ students per cohort)
            $studentIds = $this->seedStudents($pdo, $users, $classIds);
            $parentIds = $this->seedParents($pdo, $users);
            $this->seedParentStudentLinks($pdo, $parentIds, $studentIds);
            $summary['students'] = count($studentIds) . ' students seeded';

            // 8. Teacher Allocations (Class Subjects)
            $classSubjectIds = $this->seedClassSubjects($pdo, $sessionId, $classIds, $subjectIds, $teacherIds);
            $summary['allocations'] = count($classSubjectIds) . ' class subjects allocated';

            // 9. Enrollments (Class Enrollments & Subject Enrollments)
            $this->seedEnrollments($pdo, $sessionId, $classIds, $studentIds, $classSubjectIds);
            $summary['enrollments'] = 'Configured';

            // 10. Coursework, Quizzes, Attendance, Timetables
            $this->seedCoursework($pdo, $termIds[0], $classSubjectIds, $teacherIds, $studentIds);
            $this->seedCbt($pdo, $termIds[0], $classSubjectIds, $teacherIds, $subjectIds, $studentIds);
            $this->seedAttendanceAndAnnouncements($pdo, $sessionId, $termIds[0], $classIds, $studentIds, $users);
            $this->seedTimetables($pdo, $termIds[0], $classSubjectIds);

            if ($driver === 'mysql') {
                $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
            }
        });

        return $summary;
    }

    private function seedSystemSettings(PDO $pdo): void
    {
        $settings = [
            'school_name' => 'Claret International School',
            'school_motto' => 'Discipline, Integrity & Ardour',
            'school_email' => 'info@claret.edu.ng',
            'school_phone' => '+234 803 788 1737',
            'school_address' => 'Plot 700 Gitto Street, After Zeus Paradise Hotel & Mall, Mabushi, Abuja, Nigeria',
            'school_website' => 'www.claretschools.org',
            'head_teacher_name' => 'Mrs. N. Okon',
            'head_teacher_title' => 'Head of School',
            'head_teacher_signature_url' => '/assets/img/teacher-signature.png',
            'school_stamp_url' => '/assets/img/claret-stamp.png',
            'academic_year' => '2026/2027',
            'current_term' => 'First Term',
            'timezone' => 'Africa/Lagos',
        ];

        $stmt = $pdo->prepare("
            INSERT INTO system_settings (setting_key, setting_value, is_secret)
            VALUES (:key, :value, 0)
            ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
        ");

        foreach ($settings as $key => $val) {
            $stmt->execute([':key' => $key, ':value' => $val]);
        }
    }

    private function seedGradingScales(PDO $pdo): array
    {
        // 1. SSS Scale (WAEC/NECO 9-point scale)
        $stmt = $pdo->prepare("SELECT id FROM grading_scales WHERE stage = 'senior_secondary' OR name LIKE '%WAEC%' LIMIT 1");
        $stmt->execute();
        $sssScaleId = $stmt->fetchColumn();

        if (!$sssScaleId) {
            $pdo->prepare("
                INSERT INTO grading_scales (name, stage, description, is_default)
                VALUES ('Standard Secondary Scale (WAEC/NECO)', 'senior_secondary', 'Standard 9-point secondary grading scale', 1)
            ")->execute();
            $sssScaleId = (int)$pdo->lastInsertId();

            $boundaries = [
                ['grading_scale_id' => $sssScaleId, 'letter' => 'A1', 'min_score' => 75.00, 'max_score' => 100.00, 'grade_point' => 4.00, 'remark' => 'Excellent'],
                ['grading_scale_id' => $sssScaleId, 'letter' => 'B2', 'min_score' => 70.00, 'max_score' => 74.99, 'grade_point' => 3.50, 'remark' => 'Very Good'],
                ['grading_scale_id' => $sssScaleId, 'letter' => 'B3', 'min_score' => 65.00, 'max_score' => 69.99, 'grade_point' => 3.00, 'remark' => 'Good'],
                ['grading_scale_id' => $sssScaleId, 'letter' => 'C4', 'min_score' => 60.00, 'max_score' => 64.99, 'grade_point' => 2.50, 'remark' => 'Credit'],
                ['grading_scale_id' => $sssScaleId, 'letter' => 'C5', 'min_score' => 55.00, 'max_score' => 59.99, 'grade_point' => 2.00, 'remark' => 'Credit'],
                ['grading_scale_id' => $sssScaleId, 'letter' => 'C6', 'min_score' => 50.00, 'max_score' => 54.99, 'grade_point' => 1.50, 'remark' => 'Credit'],
                ['grading_scale_id' => $sssScaleId, 'letter' => 'D7', 'min_score' => 45.00, 'max_score' => 49.99, 'grade_point' => 1.00, 'remark' => 'Pass'],
                ['grading_scale_id' => $sssScaleId, 'letter' => 'E8', 'min_score' => 40.00, 'max_score' => 44.99, 'grade_point' => 0.50, 'remark' => 'Pass'],
                ['grading_scale_id' => $sssScaleId, 'letter' => 'F9', 'min_score' => 0.00, 'max_score' => 39.99, 'grade_point' => 0.00, 'remark' => 'Fail'],
            ];

            $boundStmt = $pdo->prepare("
                INSERT INTO grade_boundaries (grading_scale_id, letter, min_score, max_score, grade_point, remark)
                VALUES (:grading_scale_id, :letter, :min_score, :max_score, :grade_point, :remark)
            ");
            foreach ($boundaries as $b) {
                $boundStmt->execute($b);
            }
        }

        // 2. JSS Scale (BECE/Junior Secondary Scale)
        $stmt = $pdo->prepare("SELECT id FROM grading_scales WHERE stage = 'junior_secondary' LIMIT 1");
        $stmt->execute();
        $jssScaleId = $stmt->fetchColumn();

        if (!$jssScaleId) {
            $pdo->prepare("
                INSERT INTO grading_scales (name, stage, description, is_default)
                VALUES ('Basic Education Scale (BECE)', 'junior_secondary', 'Standard 5-point Basic Education Scale', 0)
            ")->execute();
            $jssScaleId = (int)$pdo->lastInsertId();

            $boundaries = [
                ['grading_scale_id' => $jssScaleId, 'letter' => 'A', 'min_score' => 70.00, 'max_score' => 100.00, 'grade_point' => 4.00, 'remark' => 'Distinction'],
                ['grading_scale_id' => $jssScaleId, 'letter' => 'B', 'min_score' => 60.00, 'max_score' => 69.99, 'grade_point' => 3.00, 'remark' => 'Upper Credit'],
                ['grading_scale_id' => $jssScaleId, 'letter' => 'C', 'min_score' => 50.00, 'max_score' => 59.99, 'grade_point' => 2.00, 'remark' => 'Credit'],
                ['grading_scale_id' => $jssScaleId, 'letter' => 'P', 'min_score' => 40.00, 'max_score' => 49.99, 'grade_point' => 1.00, 'remark' => 'Pass'],
                ['grading_scale_id' => $jssScaleId, 'letter' => 'F', 'min_score' => 0.00, 'max_score' => 39.99, 'grade_point' => 0.00, 'remark' => 'Fail'],
            ];

            $boundStmt = $pdo->prepare("
                INSERT INTO grade_boundaries (grading_scale_id, letter, min_score, max_score, grade_point, remark)
                VALUES (:grading_scale_id, :letter, :min_score, :max_score, :grade_point, :remark)
            ");
            foreach ($boundaries as $b) {
                $boundStmt->execute($b);
            }
        }

        return [
            'senior_secondary' => (int)$sssScaleId,
            'junior_secondary' => (int)$jssScaleId,
        ];
    }

    private function seedAcademicStages(PDO $pdo): void
    {
        $stages = [
            ['stage_key' => 'eyfs', 'name' => 'Early Years Foundation (EYFS)', 'rank_order' => 1],
            ['stage_key' => 'primary', 'name' => 'Primary School', 'rank_order' => 2],
            ['stage_key' => 'junior_secondary', 'name' => 'Junior Secondary School', 'rank_order' => 3],
            ['stage_key' => 'senior_secondary', 'name' => 'Senior Secondary School', 'rank_order' => 4],
        ];

        // Clean up legacy typo rows
        $pdo->exec("DELETE FROM academic_stages WHERE stage_key IN ('Pre Nusery', 'nursery', 'creche')");

        $stmt = $pdo->prepare("
            INSERT INTO academic_stages (stage_key, name, rank_order)
            VALUES (:stage_key, :name, :rank_order)
            ON DUPLICATE KEY UPDATE name = VALUES(name), rank_order = VALUES(rank_order)
        ");

        foreach ($stages as $s) {
            $stmt->execute($s);
        }
    }

    private function seedAcademicLevels(PDO $pdo, array $scaleIds): array
    {
        $levels = [
            // EYFS
            'Creche' => ['name' => 'Creche / Daycare', 'stage' => 'eyfs', 'rank_order' => 1, 'scale' => null],
            'Pre-Nursery' => ['name' => 'Pre-Nursery', 'stage' => 'eyfs', 'rank_order' => 2, 'scale' => null],
            'Nursery 1' => ['name' => 'Nursery 1', 'stage' => 'eyfs', 'rank_order' => 3, 'scale' => null],
            'Nursery 2' => ['name' => 'Nursery 2', 'stage' => 'eyfs', 'rank_order' => 4, 'scale' => null],
            // Primary
            'Primary 1' => ['name' => 'Primary 1', 'stage' => 'primary', 'rank_order' => 5, 'scale' => null],
            'Primary 2' => ['name' => 'Primary 2', 'stage' => 'primary', 'rank_order' => 6, 'scale' => null],
            'Primary 3' => ['name' => 'Primary 3', 'stage' => 'primary', 'rank_order' => 7, 'scale' => null],
            'Primary 4' => ['name' => 'Primary 4', 'stage' => 'primary', 'rank_order' => 8, 'scale' => null],
            'Primary 5' => ['name' => 'Primary 5', 'stage' => 'primary', 'rank_order' => 9, 'scale' => null],
            'Primary 6' => ['name' => 'Primary 6', 'stage' => 'primary', 'rank_order' => 10, 'scale' => null],
            // Junior Secondary
            'JSS 1' => ['name' => 'JSS 1 (Grade 7 / Year 7)', 'stage' => 'junior_secondary', 'rank_order' => 11, 'scale' => $scaleIds['junior_secondary'] ?? null],
            'JSS 2' => ['name' => 'JSS 2 (Grade 8 / Year 8)', 'stage' => 'junior_secondary', 'rank_order' => 12, 'scale' => $scaleIds['junior_secondary'] ?? null],
            'JSS 3' => ['name' => 'JSS 3 (Grade 9 / Year 9)', 'stage' => 'junior_secondary', 'rank_order' => 13, 'scale' => $scaleIds['junior_secondary'] ?? null],
            // Senior Secondary
            'SS 1' => ['name' => 'SS 1 (Grade 10 / Year 10)', 'stage' => 'senior_secondary', 'rank_order' => 14, 'scale' => $scaleIds['senior_secondary'] ?? null],
            'SS 2' => ['name' => 'SS 2 (Grade 11 / Year 11)', 'stage' => 'senior_secondary', 'rank_order' => 15, 'scale' => $scaleIds['senior_secondary'] ?? null],
            'SS 3' => ['name' => 'SS 3 (Grade 12 / Year 12)', 'stage' => 'senior_secondary', 'rank_order' => 16, 'scale' => $scaleIds['senior_secondary'] ?? null, 'terminal' => 1],
        ];

        $levelIds = [];
        $stmt = $pdo->prepare("
            INSERT INTO academic_levels (name, stage, rank_order, grading_scale_id)
            VALUES (:name, :stage, :rank_order, :scale_id)
        ");

        $updateStmt = $pdo->prepare("
            UPDATE academic_levels SET stage = :stage, rank_order = :rank_order, grading_scale_id = :scale_id
            WHERE id = :id
        ");

        foreach ($levels as $code => $data) {
            // Check by exact name or standard prefix
            $check = $pdo->prepare("SELECT id FROM academic_levels WHERE name = :name OR name LIKE :prefix LIMIT 1");
            $check->execute([':name' => $data['name'], ':prefix' => $code . '%']);
            $id = $check->fetchColumn();

            if (!$id) {
                $stmt->execute([
                    ':name' => $data['name'],
                    ':stage' => $data['stage'],
                    ':rank_order' => $data['rank_order'],
                    ':scale_id' => $data['scale'],
                ]);
                $id = $pdo->lastInsertId();
            } else {
                $updateStmt->execute([
                    ':id' => $id,
                    ':stage' => $data['stage'],
                    ':rank_order' => $data['rank_order'],
                    ':scale_id' => $data['scale'],
                ]);
            }

            $levelIds[$code] = (int)$id;
        }

        return $levelIds;
    }

    private function seedSession(PDO $pdo): int
    {
        $check = $pdo->prepare("SELECT id FROM sessions WHERE name = '2026/2027' LIMIT 1");
        $check->execute();
        $id = $check->fetchColumn();

        if (!$id) {
            $pdo->prepare("
                INSERT INTO sessions (name, start_date, end_date, status)
                VALUES ('2026/2027', '2026-09-01', '2027-07-31', 'active')
            ")->execute();
            $id = $pdo->lastInsertId();
        } else {
            $pdo->exec("UPDATE sessions SET status = 'active' WHERE id = " . (int)$id);
        }

        return (int)$id;
    }

    private function seedTerms(PDO $pdo, int $sessionId): array
    {
        $terms = [
            ['name' => 'First Term', 'start_date' => '2026-09-01', 'end_date' => '2026-12-15', 'status' => 'active'],
            ['name' => 'Second Term', 'start_date' => '2027-01-10', 'end_date' => '2027-04-10', 'status' => 'planning'],
            ['name' => 'Third Term', 'start_date' => '2027-05-02', 'end_date' => '2027-07-25', 'status' => 'planning'],
        ];

        $termIds = [];
        $stmt = $pdo->prepare("
            INSERT INTO terms (session_id, name, start_date, end_date, status)
            VALUES (:session_id, :name, :start_date, :end_date, :status)
            ON DUPLICATE KEY UPDATE status = VALUES(status), start_date = VALUES(start_date), end_date = VALUES(end_date)
        ");

        foreach ($terms as $t) {
            $check = $pdo->prepare("SELECT id FROM terms WHERE session_id = :sid AND name = :name LIMIT 1");
            $check->execute([':sid' => $sessionId, ':name' => $t['name']]);
            $id = $check->fetchColumn();

            if (!$id) {
                $stmt->execute([
                    ':session_id' => $sessionId,
                    ':name' => $t['name'],
                    ':start_date' => $t['start_date'],
                    ':end_date' => $t['end_date'],
                    ':status' => $t['status'],
                ]);
                $id = $pdo->lastInsertId();
            }

            $termIds[] = (int)$id;
        }

        return $termIds;
    }

    private function seedAssessmentCategories(PDO $pdo, int $sessionId, int $termId): void
    {
        $categories = [
            ['session_id' => $sessionId, 'term_id' => $termId, 'name' => 'Continuous Assessment Test 1', 'weight_percentage' => 20.00, 'max_points' => 100.00],
            ['session_id' => $sessionId, 'term_id' => $termId, 'name' => 'Continuous Assessment Test 2', 'weight_percentage' => 20.00, 'max_points' => 100.00],
            ['session_id' => $sessionId, 'term_id' => $termId, 'name' => 'Term Examination', 'weight_percentage' => 60.00, 'max_points' => 100.00],
        ];

        $stmt = $pdo->prepare("
            INSERT INTO assessment_categories (session_id, term_id, name, weight_percentage, max_points)
            VALUES (:session_id, :term_id, :name, :weight_percentage, :max_points)
        ");

        foreach ($categories as $cat) {
            $check = $pdo->prepare("SELECT id FROM assessment_categories WHERE session_id = :sid AND term_id = :tid AND name = :name LIMIT 1");
            $check->execute([':sid' => $sessionId, ':tid' => $termId, ':name' => $cat['name']]);
            if (!$check->fetchColumn()) {
                $stmt->execute($cat);
            }
        }
    }

    private function seedSubjects(PDO $pdo): array
    {
        $allSubjects = [
            // EYFS (10 Subjects)
            'EYFS_NUM' => ['name' => 'Early Numeracy', 'code' => 'eyfs_num'],
            'EYFS_LIT' => ['name' => 'Literacy & Phonics', 'code' => 'eyfs_lit'],
            'EYFS_RHY' => ['name' => 'Rhymes, Songs & Poetry', 'code' => 'eyfs_rhy'],
            'EYFS_SEN' => ['name' => 'Sensorial & Practical Life Skills', 'code' => 'eyfs_sen'],
            'EYFS_HND' => ['name' => 'Handwriting & Fine Motor Skills', 'code' => 'eyfs_hnd'],
            'EYFS_HYG' => ['name' => 'Personal Hygiene & Health Habits', 'code' => 'eyfs_hyg'],
            'EYFS_SOC' => ['name' => 'Social & Emotional Development', 'code' => 'eyfs_soc'],
            'EYFS_SCI' => ['name' => 'Science & Nature Discovery', 'code' => 'eyfs_sci'],
            'EYFS_ART' => ['name' => 'Creative Arts & Coloring', 'code' => 'eyfs_art'],
            'EYFS_REL' => ['name' => 'Moral Instruction & Values', 'code' => 'eyfs_rel'],

            // Primary (10 Subjects)
            'PRM_ENG' => ['name' => 'English Language & Verbal Reasoning', 'code' => 'prm_eng'],
            'PRM_MTH' => ['name' => 'Mathematics & Quantitative Reasoning', 'code' => 'prm_mth'],
            'PRM_BST' => ['name' => 'Basic Science & Technology', 'code' => 'prm_bst'],
            'PRM_CIV' => ['name' => 'Social Studies & Civic Education', 'code' => 'prm_civ'],
            'PRM_AGR' => ['name' => 'Agricultural Science', 'code' => 'prm_agr'],
            'PRM_HEC' => ['name' => 'Home Economics', 'code' => 'prm_hec'],
            'PRM_CCA' => ['name' => 'Cultural & Creative Arts', 'code' => 'prm_cca'],
            'PRM_ICT' => ['name' => 'Computer Studies & ICT', 'code' => 'prm_ict'],
            'PRM_PHE' => ['name' => 'Physical & Health Education', 'code' => 'prm_phe'],
            'PRM_YOR' => ['name' => 'Nigerian Language (Yoruba)', 'code' => 'prm_yor'],

            // Junior Secondary (10 Subjects) - preserves legacy codes
            'MTH'     => ['name' => 'Mathematics', 'code' => 'jss1_mth'],
            'ENG'     => ['name' => 'English Language', 'code' => 'jss1_eng'],
            'SCI'     => ['name' => 'Basic Science', 'code' => 'jss1_sci'],
            'CIV'     => ['name' => 'Civic Education', 'code' => 'jss1_civ'],
            'JSS_BTE' => ['name' => 'Basic Technology', 'code' => 'jss_bte'],
            'JSS_AGR' => ['name' => 'Agricultural Science', 'code' => 'jss_agr'],
            'JSS_BST' => ['name' => 'Business Studies', 'code' => 'jss_bst'],
            'JSS_ICT' => ['name' => 'Computer Studies / ICT', 'code' => 'jss_ict'],
            'JSS_CCA' => ['name' => 'Cultural & Creative Arts', 'code' => 'jss_cca'],
            'JSS_PHE' => ['name' => 'Physical & Health Education', 'code' => 'jss_phe'],

            // Senior Secondary (General - Shared across all arms)
            'SSS_ENG' => ['name' => 'English Language', 'code' => 'sss_eng'],
            'SSS_MTH' => ['name' => 'General Mathematics', 'code' => 'sss_mth'],
            'SSS_CIV' => ['name' => 'Civic Education', 'code' => 'sss_civ'],
            'SSS_ECO' => ['name' => 'Economics', 'code' => 'sss_eco'],

            // Senior Secondary (Science Department Core)
            'SSS_PHY' => ['name' => 'Physics', 'code' => 'sss_phy'],
            'SSS_CHM' => ['name' => 'Chemistry', 'code' => 'sss_chm'],
            'SSS_BIO' => ['name' => 'Biology', 'code' => 'sss_bio'],
            'SSS_FMT' => ['name' => 'Further Mathematics', 'code' => 'sss_fmt'],
            'SSS_TDR' => ['name' => 'Technical Drawing', 'code' => 'sss_tdr'],
            'SSS_AGR' => ['name' => 'Agricultural Science', 'code' => 'sss_agr'],

            // Senior Secondary (Art / Humanities Department Core)
            'SSS_LIT' => ['name' => 'Literature-in-English', 'code' => 'sss_lit'],
            'SSS_GOV' => ['name' => 'Government', 'code' => 'sss_gov'],
            'SSS_HIS' => ['name' => 'History', 'code' => 'sss_his'],
            'SSS_CRS' => ['name' => 'Christian Religious Studies (CRS)', 'code' => 'sss_crs'],
            'SSS_ART' => ['name' => 'Visual Arts', 'code' => 'sss_art'],
            'SSS_NLG' => ['name' => 'Nigerian Language', 'code' => 'sss_nlg'],

            // Senior Secondary (Commercial Department Core)
            'SSS_ACC' => ['name' => 'Financial Accounting', 'code' => 'sss_acc'],
            'SSS_COM' => ['name' => 'Commerce', 'code' => 'sss_com'],
            'SSS_MKT' => ['name' => 'Marketing', 'code' => 'sss_mkt'],
            'SSS_BKP' => ['name' => 'Book Keeping & Store Management', 'code' => 'sss_bkp'],
            'SSS_OFP' => ['name' => 'Office Practice', 'code' => 'sss_ofp'],
            'SSS_DPR' => ['name' => 'Data Processing', 'code' => 'sss_dpr'],
        ];

        $subjectIds = [];
        $stmt = $pdo->prepare("
            INSERT INTO subjects (name, code, status)
            VALUES (:name, :code, 'active')
            ON DUPLICATE KEY UPDATE name = VALUES(name)
        ");

        foreach ($allSubjects as $key => $s) {
            $check = $pdo->prepare("SELECT id FROM subjects WHERE code = :code LIMIT 1");
            $check->execute([':code' => $s['code']]);
            $id = $check->fetchColumn();

            if (!$id) {
                $stmt->execute([':name' => $s['name'], ':code' => $s['code']]);
                $id = $pdo->lastInsertId();
            }

            $subjectIds[$key] = (int)$id;
        }

        return $subjectIds;
    }

    private function seedUsersAndRoles(PDO $pdo): array
    {
        $usersData = [
            'super_admin' => [
                'name' => 'System Super Admin',
                'email' => 'superadmin@claret.edu',
                'phone' => '+2348000000001',
                'role' => 'super_admin',
            ],
            'admin' => [
                'name' => 'School Administrator',
                'email' => 'admin@claret.edu',
                'phone' => '+2348000000002',
                'role' => 'admin',
            ],
            // 15 Nigerian Teachers
            'teacher_adebayo' => ['name' => 'Dr. Babatunde Adebayo', 'email' => 'teacher.adebayo@claret.edu', 'phone' => '+2348031110001', 'role' => 'teacher'],
            'teacher_okoro'   => ['name' => 'Mrs. Ngozi Okoro', 'email' => 'teacher.okoro@claret.edu', 'phone' => '+2348031110002', 'role' => 'teacher'],
            'teacher_danladi' => ['name' => 'Mallam Ibrahim Danladi', 'email' => 'teacher.danladi@claret.edu', 'phone' => '+2348031110003', 'role' => 'teacher'],
            'teacher_adeleke' => ['name' => 'Mr. Olumide Adeleke', 'email' => 'teacher.adeleke@claret.edu', 'phone' => '+2348031110004', 'role' => 'teacher'],
            'teacher_eze'     => ['name' => 'Mrs. Chidinma Eze', 'email' => 'teacher.eze@claret.edu', 'phone' => '+2348031110005', 'role' => 'teacher'],
            'teacher_enahoro' => ['name' => 'Engr. Osasere Enahoro', 'email' => 'teacher.enahoro@claret.edu', 'phone' => '+2348031110006', 'role' => 'teacher'],
            'teacher_awolowo' => ['name' => 'Mrs. Funmilayo Awolowo', 'email' => 'teacher.awolowo@claret.edu', 'phone' => '+2348031110007', 'role' => 'teacher'],
            'teacher_olatunji'=> ['name' => 'Mr. Kayode Olatunji', 'email' => 'teacher.olatunji@claret.edu', 'phone' => '+2348031110008', 'role' => 'teacher'],
            'teacher_bello'   => ['name' => 'Mrs. Amina Bello', 'email' => 'teacher.bello@claret.edu', 'phone' => '+2348031110009', 'role' => 'teacher'],
            'teacher_obi'     => ['name' => 'Mr. Chukwuma Obi', 'email' => 'teacher.obi@claret.edu', 'phone' => '+2348031110010', 'role' => 'teacher'],
            'teacher_enabulele'=> ['name' => 'Mrs. Eseosa Enabulele', 'email' => 'teacher.enabulele@claret.edu', 'phone' => '+2348031110011', 'role' => 'teacher'],
            'teacher_ojo'     => ['name' => 'Mr. Damilola Ojo', 'email' => 'teacher.ojo@claret.edu', 'phone' => '+2348031110012', 'role' => 'teacher'],
            'teacher_aliyu'   => ['name' => 'Mrs. Fatima Aliyu', 'email' => 'teacher.aliyu@claret.edu', 'phone' => '+2348031110013', 'role' => 'teacher'],
            'teacher_obasi'   => ['name' => 'Mr. Ifeanyi Obasi', 'email' => 'teacher.obasi@claret.edu', 'phone' => '+2348031110014', 'role' => 'teacher'],
            'teacher_balogun' => ['name' => 'Mrs. Temitope Balogun', 'email' => 'teacher.balogun@claret.edu', 'phone' => '+2348031110015', 'role' => 'teacher'],

            // Core students for tests compatibility
            'student_john'  => ['name' => 'John Doe', 'email' => 'student.john@claret.edu', 'phone' => '+2348020000001', 'role' => 'student'],
            'student_mary'  => ['name' => 'Mary Doe', 'email' => 'student.mary@claret.edu', 'phone' => '+2348020000002', 'role' => 'student'],
            'student_david' => ['name' => 'David Smith', 'email' => 'student.david@claret.edu', 'phone' => '+2348020000003', 'role' => 'student'],

            // Parents for tests compatibility
            'parent_doe'    => ['name' => 'Mr. Chukwuma Doe', 'email' => 'parent.doe@claret.edu', 'phone' => '+2348020000008', 'role' => 'parent'],
            'parent_smith'  => ['name' => 'Mrs. Victoria Smith', 'email' => 'parent.smith@claret.edu', 'phone' => '+2348020000009', 'role' => 'parent'],
        ];

        // 50 authentic Nigerian students across multiple class cohorts
        $nigerianStudents = [
            // JSS 1A cohort (Yoruba, Igbo, Hausa, Edo)
            'std_adeyemi'  => ['name' => 'Oluwaseun Adeyemi', 'email' => 'oluwaseun.adeyemi@claret.edu.ng', 'phone' => '+2348021000001', 'role' => 'student'],
            'std_nwosu'    => ['name' => 'Chukwudi Nwosu', 'email' => 'chukwudi.nwosu@claret.edu.ng', 'phone' => '+2348021000002', 'role' => 'student'],
            'std_garba'    => ['name' => 'Ibrahim Garba', 'email' => 'ibrahim.garba@claret.edu.ng', 'phone' => '+2348021000003', 'role' => 'student'],
            'std_okonkwo'  => ['name' => 'Chioma Okonkwo', 'email' => 'chioma.okonkwo@claret.edu.ng', 'phone' => '+2348021000004', 'role' => 'student'],
            'std_fashola'  => ['name' => 'Kehinde Fashola', 'email' => 'kehinde.fashola@claret.edu.ng', 'phone' => '+2348021000005', 'role' => 'student'],
            'std_igbinedion'=>['name' => 'Osasere Igbinedion', 'email' => 'osasere.igbinedion@claret.edu.ng', 'phone' => '+2348021000006', 'role' => 'student'],
            'std_sanusi'   => ['name' => 'Hadiza Sanusi', 'email' => 'hadiza.sanusi@claret.edu.ng', 'phone' => '+2348021000007', 'role' => 'student'],
            'std_nnamani'  => ['name' => 'Chinedu Nnamani', 'email' => 'chinedu.nnamani@claret.edu.ng', 'phone' => '+2348021000008', 'role' => 'student'],
            'std_alabi'    => ['name' => 'Ayomide Alabi', 'email' => 'ayomide.alabi@claret.edu.ng', 'phone' => '+2348021000009', 'role' => 'student'],
            'std_akpoveta' => ['name' => 'Oghenekaro Akpoveta', 'email' => 'oghenekaro.akpoveta@claret.edu.ng', 'phone' => '+2348021000010', 'role' => 'student'],

            // JSS 1B cohort
            'std_ogundipe' => ['name' => 'Babatunde Ogundipe', 'email' => 'babatunde.ogundipe@claret.edu.ng', 'phone' => '+2348022000001', 'role' => 'student'],
            'std_okafor'   => ['name' => 'Ngozi Okafor', 'email' => 'ngozi.okafor@claret.edu.ng', 'phone' => '+2348022000002', 'role' => 'student'],
            'std_abubakar' => ['name' => 'Zainab Abubakar', 'email' => 'zainab.abubakar@claret.edu.ng', 'phone' => '+2348022000003', 'role' => 'student'],
            'std_eze'      => ['name' => 'Emeka Eze', 'email' => 'emeka.eze@claret.edu.ng', 'phone' => '+2348022000004', 'role' => 'student'],
            'std_omoruyi'  => ['name' => 'Efe Omoruyi', 'email' => 'efe.omoruyi@claret.edu.ng', 'phone' => '+2348022000005', 'role' => 'student'],
            'std_mohammed' => ['name' => 'Kabir Mohammed', 'email' => 'kabir.mohammed@claret.edu.ng', 'phone' => '+2348022000006', 'role' => 'student'],
            'std_balogun'  => ['name' => 'Folashade Balogun', 'email' => 'folashade.balogun@claret.edu.ng', 'phone' => '+2348022000007', 'role' => 'student'],
            'std_igwe'     => ['name' => 'Uchechukwu Igwe', 'email' => 'uchechukwu.igwe@claret.edu.ng', 'phone' => '+2348022000008', 'role' => 'student'],
            'std_danladi'  => ['name' => 'Amina Danladi', 'email' => 'amina.danladi@claret.edu.ng', 'phone' => '+2348022000009', 'role' => 'student'],
            'std_osagie'   => ['name' => 'Blessing Osagie', 'email' => 'blessing.osagie@claret.edu.ng', 'phone' => '+2348022000010', 'role' => 'student'],

            // SS 1A (Science) cohort
            'std_sci_1'    => ['name' => 'Toluwanimi Adeleke', 'email' => 'toluwanimi.adeleke@claret.edu.ng', 'phone' => '+2348023000001', 'role' => 'student'],
            'std_sci_2'    => ['name' => 'Somtochukwu Okoye', 'email' => 'somtochukwu.okoye@claret.edu.ng', 'phone' => '+2348023000002', 'role' => 'student'],
            'std_sci_3'    => ['name' => 'Farouk Umar', 'email' => 'farouk.umar@claret.edu.ng', 'phone' => '+2348023000003', 'role' => 'student'],
            'std_sci_4'    => ['name' => 'Anjolaoluwa Bakare', 'email' => 'anjolaoluwa.bakare@claret.edu.ng', 'phone' => '+2348023000004', 'role' => 'student'],
            'std_sci_5'    => ['name' => 'Kamsiyochukwu Ani', 'email' => 'kamsiyochukwu.ani@claret.edu.ng', 'phone' => '+2348023000005', 'role' => 'student'],
            'std_sci_6'    => ['name' => 'Usman Dantata', 'email' => 'usman.dantata@claret.edu.ng', 'phone' => '+2348023000006', 'role' => 'student'],
            'std_sci_7'    => ['name' => 'Oghosa Imasuen', 'email' => 'oghosa.imasuen@claret.edu.ng', 'phone' => '+2348023000007', 'role' => 'student'],
            'std_sci_8'    => ['name' => 'Chiamaka Onyekwere', 'email' => 'chiamaka.onyekwere@claret.edu.ng', 'phone' => '+2348023000008', 'role' => 'student'],
            'std_sci_9'    => ['name' => 'Damilare Sowande', 'email' => 'damilare.sowande@claret.edu.ng', 'phone' => '+2348023000009', 'role' => 'student'],
            'std_sci_10'   => ['name' => 'Hauwa Shehu', 'email' => 'hauwa.shehu@claret.edu.ng', 'phone' => '+2348023000010', 'role' => 'student'],

            // SS 1B (Art) cohort
            'std_art_1'    => ['name' => 'Adeola Oyewole', 'email' => 'adeola.oyewole@claret.edu.ng', 'phone' => '+2348024000001', 'role' => 'student'],
            'std_art_2'    => ['name' => 'Obinna Nwachukwu', 'email' => 'obinna.nwachukwu@claret.edu.ng', 'phone' => '+2348024000002', 'role' => 'student'],
            'std_art_3'    => ['name' => 'Aisha Gidado', 'email' => 'aisha.gidado@claret.edu.ng', 'phone' => '+2348024000003', 'role' => 'student'],
            'std_art_4'    => ['name' => 'Titilayo Coker', 'email' => 'titilayo.coker@claret.edu.ng', 'phone' => '+2348024000004', 'role' => 'student'],
            'std_art_5'    => ['name' => 'Chibueze Madu', 'email' => 'chibueze.madu@claret.edu.ng', 'phone' => '+2348024000005', 'role' => 'student'],
            'std_art_6'    => ['name' => 'Mansur Yakubu', 'email' => 'mansur.yakubu@claret.edu.ng', 'phone' => '+2348024000006', 'role' => 'student'],
            'std_art_7'    => ['name' => 'Edosa Aghahowa', 'email' => 'edosa.aghahowa@claret.edu.ng', 'phone' => '+2348024000007', 'role' => 'student'],
            'std_art_8'    => ['name' => 'Nneka Okoli', 'email' => 'nneka.okoli@claret.edu.ng', 'phone' => '+2348024000008', 'role' => 'student'],
            'std_art_9'    => ['name' => 'Oluwatomisin Falana', 'email' => 'oluwatomisin.falana@claret.edu.ng', 'phone' => '+2348024000009', 'role' => 'student'],
            'std_art_10'   => ['name' => 'Fatima Balarabe', 'email' => 'fatima.balarabe@claret.edu.ng', 'phone' => '+2348024000010', 'role' => 'student'],

            // SS 1C (Commercial) cohort
            'std_com_1'    => ['name' => 'Babatunde Akindele', 'email' => 'babatunde.akindele@claret.edu.ng', 'phone' => '+2348025000001', 'role' => 'student'],
            'std_com_2'    => ['name' => 'Kelechi Onuoha', 'email' => 'kelechi.onuoha@claret.edu.ng', 'phone' => '+2348025000002', 'role' => 'student'],
            'std_com_3'    => ['name' => 'Nasir Baffa', 'email' => 'nasir.baffa@claret.edu.ng', 'phone' => '+2348025000003', 'role' => 'student'],
            'std_com_4'    => ['name' => 'Eniola Bankole', 'email' => 'eniola.bankole@claret.edu.ng', 'phone' => '+2348025000004', 'role' => 'student'],
            'std_com_5'    => ['name' => 'Lotanna Umeh', 'email' => 'lotanna.umeh@claret.edu.ng', 'phone' => '+2348025000005', 'role' => 'student'],
            'std_com_6'    => ['name' => 'Mustapha Ribadu', 'email' => 'mustapha.ribadu@claret.edu.ng', 'phone' => '+2348025000006', 'role' => 'student'],
            'std_com_7'    => ['name' => 'Ivie Omoregie', 'email' => 'ivie.omoregie@claret.edu.ng', 'phone' => '+2348025000007', 'role' => 'student'],
            'std_com_8'    => ['name' => 'Oluchi Kalu', 'email' => 'oluchi.kalu@claret.edu.ng', 'phone' => '+2348025000008', 'role' => 'student'],
            'std_com_9'    => ['name' => 'Mobolaji Dawodu', 'email' => 'mobolaji.dawodu@claret.edu.ng', 'phone' => '+2348025000009', 'role' => 'student'],
            'std_com_10'   => ['name' => 'Halima Gambo', 'email' => 'halima.gambo@claret.edu.ng', 'phone' => '+2348025000010', 'role' => 'student'],
        ];

        // Nigerian Parents
        $nigerianParents = [
            'prt_adeyemi'  => ['name' => 'Chief Adebisi Adeyemi', 'email' => 'adebisi.adeyemi@gmail.com', 'phone' => '+2348033000001', 'role' => 'parent'],
            'prt_nwosu'    => ['name' => 'Engr. Emeka Nwosu', 'email' => 'emeka.nwosu@yahoo.com', 'phone' => '+2348033000002', 'role' => 'parent'],
            'prt_garba'    => ['name' => 'Alhaji Musa Garba', 'email' => 'musa.garba@gmail.com', 'phone' => '+2348033000003', 'role' => 'parent'],
            'prt_okonkwo'  => ['name' => 'Dr. Christopher Okonkwo', 'email' => 'chris.okonkwo@gmail.com', 'phone' => '+2348033000004', 'role' => 'parent'],
            'prt_fashola'  => ['name' => 'Barr. Babafemi Fashola', 'email' => 'femi.fashola@law.ng', 'phone' => '+2348033000005', 'role' => 'parent'],
            'prt_igbinedion'=>['name' => 'Chief Osaro Igbinedion', 'email' => 'osaro.igbinedion@yahoo.com', 'phone' => '+2348033000006', 'role' => 'parent'],
            'prt_adeleke'  => ['name' => 'Prof. Adeleke Oladele', 'email' => 'oladele.adeleke@unilag.edu.ng', 'phone' => '+2348033000007', 'role' => 'parent'],
            'prt_okoye'    => ['name' => 'Sir Jude Okoye', 'email' => 'jude.okoye@gmail.com', 'phone' => '+2348033000008', 'role' => 'parent'],
            'prt_oyewole'  => ['name' => 'Pastor Samuel Oyewole', 'email' => 'samuel.oyewole@gmail.com', 'phone' => '+2348033000009', 'role' => 'parent'],
            'prt_akindele' => ['name' => 'Otunba Gbolahan Akindele', 'email' => 'gbolahan.akindele@gmail.com', 'phone' => '+2348033000010', 'role' => 'parent'],
        ];

        $usersData = array_merge($usersData, $nigerianStudents, $nigerianParents);

        $userMap = [];
        $userStmt = $pdo->prepare("
            INSERT INTO users (uuid, name, email, phone, password_hash, status, must_change_password)
            VALUES (:uuid, :name, :email, :phone, :hash, 'active', 0)
        ");

        $roleStmt = $pdo->prepare("
            INSERT INTO user_roles (user_id, role, is_active)
            VALUES (:user_id, :role, 1)
            ON DUPLICATE KEY UPDATE is_active = 1
        ");

        foreach ($usersData as $key => $data) {
            $check = $pdo->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
            $check->execute([':email' => $data['email']]);
            $id = $check->fetchColumn();

            if (!$id) {
                $uuid = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                    mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                    mt_rand(0, 0xffff),
                    mt_rand(0, 0x0fff) | 0x4000,
                    mt_rand(0, 0x3fff) | 0x8000,
                    mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
                );

                $userStmt->execute([
                    ':uuid' => $uuid,
                    ':name' => $data['name'],
                    ':email' => $data['email'],
                    ':phone' => $data['phone'],
                    ':hash' => $this->defaultPasswordHash,
                ]);
                $id = $pdo->lastInsertId();
            }

            $id = (int)$id;
            $roleStmt->execute([':user_id' => $id, ':role' => $data['role']]);
            $userMap[$key] = $id;
        }

        return $userMap;
    }

    private function seedTeachers(PDO $pdo, array $users): array
    {
        $teachers = [
            'adebayo'   => ['user_id' => $users['teacher_adebayo'], 'staff_id' => 'CIS/TCH/001'],
            'okoro'     => ['user_id' => $users['teacher_okoro'], 'staff_id' => 'CIS/TCH/002'],
            'danladi'   => ['user_id' => $users['teacher_danladi'], 'staff_id' => 'CIS/TCH/003'],
            'adeleke'   => ['user_id' => $users['teacher_adeleke'], 'staff_id' => 'CIS/TCH/004'],
            'eze'       => ['user_id' => $users['teacher_eze'], 'staff_id' => 'CIS/TCH/005'],
            'enahoro'   => ['user_id' => $users['teacher_enahoro'], 'staff_id' => 'CIS/TCH/006'],
            'awolowo'   => ['user_id' => $users['teacher_awolowo'], 'staff_id' => 'CIS/TCH/007'],
            'olatunji'  => ['user_id' => $users['teacher_olatunji'], 'staff_id' => 'CIS/TCH/008'],
            'bello'     => ['user_id' => $users['teacher_bello'], 'staff_id' => 'CIS/TCH/009'],
            'obi'       => ['user_id' => $users['teacher_obi'], 'staff_id' => 'CIS/TCH/010'],
            'enabulele' => ['user_id' => $users['teacher_enabulele'], 'staff_id' => 'CIS/TCH/011'],
            'ojo'       => ['user_id' => $users['teacher_ojo'], 'staff_id' => 'CIS/TCH/012'],
            'aliyu'     => ['user_id' => $users['teacher_aliyu'], 'staff_id' => 'CIS/TCH/013'],
            'obasi'     => ['user_id' => $users['teacher_obasi'], 'staff_id' => 'CIS/TCH/014'],
            'balogun'   => ['user_id' => $users['teacher_balogun'], 'staff_id' => 'CIS/TCH/015'],
        ];

        $teacherIds = [];
        $stmt = $pdo->prepare("
            INSERT INTO teachers (user_id, staff_id)
            VALUES (:user_id, :staff_id)
            ON DUPLICATE KEY UPDATE staff_id = VALUES(staff_id)
        ");

        foreach ($teachers as $key => $t) {
            $check = $pdo->prepare("SELECT id FROM teachers WHERE user_id = :uid LIMIT 1");
            $check->execute([':uid' => $t['user_id']]);
            $id = $check->fetchColumn();

            if (!$id) {
                $stmt->execute([':user_id' => $t['user_id'], ':staff_id' => $t['staff_id']]);
                $id = $pdo->lastInsertId();
            } else {
                $pdo->prepare("UPDATE teachers SET staff_id = :staff_id WHERE id = :id")->execute([
                    ':staff_id' => $t['staff_id'],
                    ':id' => $id,
                ]);
            }

            $teacherIds[$key] = (int)$id;
        }

        return $teacherIds;
    }

    private function seedClasses(PDO $pdo, array $levelIds, array $teacherIds): array
    {
        $classes = [
            // EYFS
            'Creche A'       => ['level' => 'Creche', 'name' => 'Creche A', 'arm' => 'A', 'teacher' => 'balogun'],
            'Creche B'       => ['level' => 'Creche', 'name' => 'Creche B', 'arm' => 'B', 'teacher' => 'enabulele'],
            'Pre-Nursery A'  => ['level' => 'Pre-Nursery', 'name' => 'Pre-Nursery A', 'arm' => 'A', 'teacher' => 'aliyu'],
            'Pre-Nursery B'  => ['level' => 'Pre-Nursery', 'name' => 'Pre-Nursery B', 'arm' => 'B', 'teacher' => 'bello'],
            'Nursery 1A'     => ['level' => 'Nursery 1', 'name' => 'Nursery 1A', 'arm' => 'A', 'teacher' => 'awolowo'],
            'Nursery 1B'     => ['level' => 'Nursery 1', 'name' => 'Nursery 1B', 'arm' => 'B', 'teacher' => 'eze'],
            'Nursery 2A'     => ['level' => 'Nursery 2', 'name' => 'Nursery 2A', 'arm' => 'A', 'teacher' => 'enabulele'],
            'Nursery 2B'     => ['level' => 'Nursery 2', 'name' => 'Nursery 2B', 'arm' => 'B', 'teacher' => 'balogun'],

            // Primary 1 - 6 (Arm A and B)
            'Primary 1A'     => ['level' => 'Primary 1', 'name' => 'Primary 1A', 'arm' => 'A', 'teacher' => 'ojo'],
            'Primary 1B'     => ['level' => 'Primary 1', 'name' => 'Primary 1B', 'arm' => 'B', 'teacher' => 'olatunji'],
            'Primary 2A'     => ['level' => 'Primary 2', 'name' => 'Primary 2A', 'arm' => 'A', 'teacher' => 'obasi'],
            'Primary 2B'     => ['level' => 'Primary 2', 'name' => 'Primary 2B', 'arm' => 'B', 'teacher' => 'obi'],
            'Primary 3A'     => ['level' => 'Primary 3', 'name' => 'Primary 3A', 'arm' => 'A', 'teacher' => 'awolowo'],
            'Primary 3B'     => ['level' => 'Primary 3', 'name' => 'Primary 3B', 'arm' => 'B', 'teacher' => 'aliyu'],
            'Primary 4A'     => ['level' => 'Primary 4', 'name' => 'Primary 4A', 'arm' => 'A', 'teacher' => 'danladi'],
            'Primary 4B'     => ['level' => 'Primary 4', 'name' => 'Primary 4B', 'arm' => 'B', 'teacher' => 'bello'],
            'Primary 5A'     => ['level' => 'Primary 5', 'name' => 'Primary 5A', 'arm' => 'A', 'teacher' => 'adeleke'],
            'Primary 5B'     => ['level' => 'Primary 5', 'name' => 'Primary 5B', 'arm' => 'B', 'teacher' => 'eze'],
            'Primary 6A'     => ['level' => 'Primary 6', 'name' => 'Primary 6A', 'arm' => 'A', 'teacher' => 'olatunji'],
            'Primary 6B'     => ['level' => 'Primary 6', 'name' => 'Primary 6B', 'arm' => 'B', 'teacher' => 'ojo'],

            // Junior Secondary (Arm A and B)
            'JSS 1A'         => ['level' => 'JSS 1', 'name' => 'JSS 1A', 'arm' => 'A', 'teacher' => 'adebayo'],
            'JSS 1B'         => ['level' => 'JSS 1', 'name' => 'JSS 1B', 'arm' => 'B', 'teacher' => 'okoro'],
            'JSS 2A'         => ['level' => 'JSS 2', 'name' => 'JSS 2A', 'arm' => 'A', 'teacher' => 'danladi'],
            'JSS 2B'         => ['level' => 'JSS 2', 'name' => 'JSS 2B', 'arm' => 'B', 'teacher' => 'adeleke'],
            'JSS 3A'         => ['level' => 'JSS 3', 'name' => 'JSS 3A', 'arm' => 'A', 'teacher' => 'enahoro'],
            'JSS 3B'         => ['level' => 'JSS 3', 'name' => 'JSS 3B', 'arm' => 'B', 'teacher' => 'obi'],

            // Senior Secondary (3 arms per level: Science, Art, Commercial)
            'SS 1A (Science)'    => ['level' => 'SS 1', 'name' => 'SS 1A (Science)', 'arm' => 'A', 'teacher' => 'enahoro'],
            'SS 1B (Art)'        => ['level' => 'SS 1', 'name' => 'SS 1B (Art)', 'arm' => 'B', 'teacher' => 'awolowo'],
            'SS 1C (Commercial)' => ['level' => 'SS 1', 'name' => 'SS 1C (Commercial)', 'arm' => 'C', 'teacher' => 'obi'],

            'SS 2A (Science)'    => ['level' => 'SS 2', 'name' => 'SS 2A (Science)', 'arm' => 'A', 'teacher' => 'adebayo'],
            'SS 2B (Art)'        => ['level' => 'SS 2', 'name' => 'SS 2B (Art)', 'arm' => 'B', 'teacher' => 'okoro'],
            'SS 2C (Commercial)' => ['level' => 'SS 2', 'name' => 'SS 2C (Commercial)', 'arm' => 'C', 'teacher' => 'olatunji'],

            'SS 3A (Science)'    => ['level' => 'SS 3', 'name' => 'SS 3A (Science)', 'arm' => 'A', 'teacher' => 'enahoro'],
            'SS 3B (Art)'        => ['level' => 'SS 3', 'name' => 'SS 3B (Art)', 'arm' => 'B', 'teacher' => 'awolowo'],
            'SS 3C (Commercial)' => ['level' => 'SS 3', 'name' => 'SS 3C (Commercial)', 'arm' => 'C', 'teacher' => 'adeleke'],
        ];

        $classIds = [];
        $stmt = $pdo->prepare("
            INSERT INTO classes (academic_level_id, name, section_arm, form_teacher_id, status)
            VALUES (:level_id, :name, :arm, :form_teacher_id, 'active')
        ");

        $updateStmt = $pdo->prepare("
            UPDATE classes SET academic_level_id = :level_id, section_arm = :arm, form_teacher_id = :form_teacher_id, status = 'active'
            WHERE id = :id
        ");

        foreach ($classes as $key => $c) {
            $levelId = $levelIds[$c['level']] ?? 0;
            $formTeacherId = $teacherIds[$c['teacher']] ?? null;

            $check = $pdo->prepare("SELECT id FROM classes WHERE name = :name LIMIT 1");
            $check->execute([':name' => $c['name']]);
            $id = $check->fetchColumn();

            if (!$id) {
                $stmt->execute([
                    ':level_id' => $levelId,
                    ':name' => $c['name'],
                    ':arm' => $c['arm'],
                    ':form_teacher_id' => $formTeacherId,
                ]);
                $id = $pdo->lastInsertId();
            } else {
                $updateStmt->execute([
                    ':id' => $id,
                    ':level_id' => $levelId,
                    ':arm' => $c['arm'],
                    ':form_teacher_id' => $formTeacherId,
                ]);
            }

            $classIds[$key] = (int)$id;
        }

        return $classIds;
    }

    private function seedStudents(PDO $pdo, array $users, array $classIds): array
    {
        $students = [
            // Core students for compatibility
            'john' => [
                'user_id' => $users['student_john'],
                'admission_number' => 'CIS/2026/001',
                'class_id' => $classIds['JSS 1A'],
                'gender' => 'male',
                'dob' => '2013-05-14',
                'state' => 'Lagos',
                'lga' => 'Ikeja',
            ],
            'mary' => [
                'user_id' => $users['student_mary'],
                'admission_number' => 'CIS/2026/002',
                'class_id' => $classIds['JSS 1A'],
                'gender' => 'female',
                'dob' => '2013-09-22',
                'state' => 'Lagos',
                'lga' => 'Surulere',
            ],
            'david' => [
                'user_id' => $users['student_david'],
                'admission_number' => 'CIS/2026/003',
                'class_id' => $classIds['JSS 1B'],
                'gender' => 'male',
                'dob' => '2013-11-03',
                'state' => 'Ogun',
                'lga' => 'Abeokuta South',
            ],

            // JSS 1A cohort (10+ students)
            'std_adeyemi'  => ['user_id' => $users['std_adeyemi'], 'admission_number' => 'CIS/2026/101', 'class_id' => $classIds['JSS 1A'], 'gender' => 'male', 'dob' => '2013-03-12', 'state' => 'Osun', 'lga' => 'Osogbo'],
            'std_nwosu'    => ['user_id' => $users['std_nwosu'], 'admission_number' => 'CIS/2026/102', 'class_id' => $classIds['JSS 1A'], 'gender' => 'male', 'dob' => '2013-07-19', 'state' => 'Anambra', 'lga' => 'Onitsha North'],
            'std_garba'    => ['user_id' => $users['std_garba'], 'admission_number' => 'CIS/2026/103', 'class_id' => $classIds['JSS 1A'], 'gender' => 'male', 'dob' => '2013-01-25', 'state' => 'Kano', 'lga' => 'Nassarawa'],
            'std_okonkwo'  => ['user_id' => $users['std_okonkwo'], 'admission_number' => 'CIS/2026/104', 'class_id' => $classIds['JSS 1A'], 'gender' => 'female', 'dob' => '2013-10-08', 'state' => 'Enugu', 'lga' => 'Enugu North'],
            'std_fashola'  => ['user_id' => $users['std_fashola'], 'admission_number' => 'CIS/2026/105', 'class_id' => $classIds['JSS 1A'], 'gender' => 'female', 'dob' => '2013-04-14', 'state' => 'Lagos', 'lga' => 'Lagos Island'],
            'std_igbinedion'=>['user_id' => $users['std_igbinedion'], 'admission_number' => 'CIS/2026/106', 'class_id' => $classIds['JSS 1A'], 'gender' => 'male', 'dob' => '2013-08-30', 'state' => 'Edo', 'lga' => 'Oredo'],
            'std_sanusi'   => ['user_id' => $users['std_sanusi'], 'admission_number' => 'CIS/2026/107', 'class_id' => $classIds['JSS 1A'], 'gender' => 'female', 'dob' => '2013-12-05', 'state' => 'Kaduna', 'lga' => 'Zaria'],
            'std_nnamani'  => ['user_id' => $users['std_nnamani'], 'admission_number' => 'CIS/2026/108', 'class_id' => $classIds['JSS 1A'], 'gender' => 'male', 'dob' => '2013-06-11', 'state' => 'Enugu', 'lga' => 'Nkanu West'],
            'std_alabi'    => ['user_id' => $users['std_alabi'], 'admission_number' => 'CIS/2026/109', 'class_id' => $classIds['JSS 1A'], 'gender' => 'male', 'dob' => '2013-09-17', 'state' => 'Oyo', 'lga' => 'Ibadan North'],
            'std_akpoveta' => ['user_id' => $users['std_akpoveta'], 'admission_number' => 'CIS/2026/110', 'class_id' => $classIds['JSS 1A'], 'gender' => 'male', 'dob' => '2013-02-28', 'state' => 'Delta', 'lga' => 'Ughelli North'],

            // JSS 1B cohort (10+ students)
            'std_ogundipe' => ['user_id' => $users['std_ogundipe'], 'admission_number' => 'CIS/2026/111', 'class_id' => $classIds['JSS 1B'], 'gender' => 'male', 'dob' => '2013-05-02', 'state' => 'Ogun', 'lga' => 'Ijebu Ode'],
            'std_okafor'   => ['user_id' => $users['std_okafor'], 'admission_number' => 'CIS/2026/112', 'class_id' => $classIds['JSS 1B'], 'gender' => 'female', 'dob' => '2013-08-16', 'state' => 'Anambra', 'lga' => 'Awka South'],
            'std_abubakar' => ['user_id' => $users['std_abubakar'], 'admission_number' => 'CIS/2026/113', 'class_id' => $classIds['JSS 1B'], 'gender' => 'female', 'dob' => '2013-11-20', 'state' => 'Sokoto', 'lga' => 'Sokoto North'],
            'std_eze'      => ['user_id' => $users['std_eze'], 'admission_number' => 'CIS/2026/114', 'class_id' => $classIds['JSS 1B'], 'gender' => 'male', 'dob' => '2013-04-09', 'state' => 'Imo', 'lga' => 'Owerri Municipal'],
            'std_omoruyi'  => ['user_id' => $users['std_omoruyi'], 'admission_number' => 'CIS/2026/115', 'class_id' => $classIds['JSS 1B'], 'gender' => 'male', 'dob' => '2013-10-27', 'state' => 'Edo', 'lga' => 'Egor'],
            'std_mohammed' => ['user_id' => $users['std_mohammed'], 'admission_number' => 'CIS/2026/116', 'class_id' => $classIds['JSS 1B'], 'gender' => 'male', 'dob' => '2013-03-03', 'state' => 'Niger', 'lga' => 'Chanchaga'],
            'std_balogun'  => ['user_id' => $users['std_balogun'], 'admission_number' => 'CIS/2026/117', 'class_id' => $classIds['JSS 1B'], 'gender' => 'female', 'dob' => '2013-07-14', 'state' => 'Lagos', 'lga' => 'Agege'],
            'std_igwe'     => ['user_id' => $users['std_igwe'], 'admission_number' => 'CIS/2026/118', 'class_id' => $classIds['JSS 1B'], 'gender' => 'male', 'dob' => '2013-01-18', 'state' => 'Abia', 'lga' => 'Aba South'],
            'std_danladi'  => ['user_id' => $users['std_danladi'], 'admission_number' => 'CIS/2026/119', 'class_id' => $classIds['JSS 1B'], 'gender' => 'female', 'dob' => '2013-09-09', 'state' => 'Plateau', 'lga' => 'Jos North'],
            'std_osagie'   => ['user_id' => $users['std_osagie'], 'admission_number' => 'CIS/2026/120', 'class_id' => $classIds['JSS 1B'], 'gender' => 'female', 'dob' => '2013-12-24', 'state' => 'Edo', 'lga' => 'Ikpoba Okha'],

            // SS 1A (Science) cohort (10 students)
            'std_sci_1'    => ['user_id' => $users['std_sci_1'], 'admission_number' => 'CIS/2026/201', 'class_id' => $classIds['SS 1A (Science)'], 'gender' => 'female', 'dob' => '2010-02-14', 'state' => 'Oyo', 'lga' => 'Oyo East'],
            'std_sci_2'    => ['user_id' => $users['std_sci_2'], 'admission_number' => 'CIS/2026/202', 'class_id' => $classIds['SS 1A (Science)'], 'gender' => 'male', 'dob' => '2010-06-21', 'state' => 'Anambra', 'lga' => 'Nnewi North'],
            'std_sci_3'    => ['user_id' => $users['std_sci_3'], 'admission_number' => 'CIS/2026/203', 'class_id' => $classIds['SS 1A (Science)'], 'gender' => 'male', 'dob' => '2010-09-05', 'state' => 'Katsina', 'lga' => 'Katsina'],
            'std_sci_4'    => ['user_id' => $users['std_sci_4'], 'admission_number' => 'CIS/2026/204', 'class_id' => $classIds['SS 1A (Science)'], 'gender' => 'female', 'dob' => '2010-11-18', 'state' => 'Lagos', 'lga' => 'Kosofe'],
            'std_sci_5'    => ['user_id' => $users['std_sci_5'], 'admission_number' => 'CIS/2026/205', 'class_id' => $classIds['SS 1A (Science)'], 'gender' => 'male', 'dob' => '2010-04-12', 'state' => 'Enugu', 'lga' => 'Udi'],
            'std_sci_6'    => ['user_id' => $users['std_sci_6'], 'admission_number' => 'CIS/2026/206', 'class_id' => $classIds['SS 1A (Science)'], 'gender' => 'male', 'dob' => '2010-08-29', 'state' => 'Kano', 'lga' => 'Fagge'],
            'std_sci_7'    => ['user_id' => $users['std_sci_7'], 'admission_number' => 'CIS/2026/207', 'class_id' => $classIds['SS 1A (Science)'], 'gender' => 'male', 'dob' => '2010-12-03', 'state' => 'Edo', 'lga' => 'Oredo'],
            'std_sci_8'    => ['user_id' => $users['std_sci_8'], 'admission_number' => 'CIS/2026/208', 'class_id' => $classIds['SS 1A (Science)'], 'gender' => 'female', 'dob' => '2010-03-27', 'state' => 'Imo', 'lga' => 'Okigwe'],
            'std_sci_9'    => ['user_id' => $users['std_sci_9'], 'admission_number' => 'CIS/2026/209', 'class_id' => $classIds['SS 1A (Science)'], 'gender' => 'male', 'dob' => '2010-07-15', 'state' => 'Ogun', 'lga' => 'Abeokuta North'],
            'std_sci_10'   => ['user_id' => $users['std_sci_10'], 'admission_number' => 'CIS/2026/210', 'class_id' => $classIds['SS 1A (Science)'], 'gender' => 'female', 'dob' => '2010-10-31', 'state' => 'Borno', 'lga' => 'Maiduguri'],

            // SS 1B (Art) cohort (10 students)
            'std_art_1'    => ['user_id' => $users['std_art_1'], 'admission_number' => 'CIS/2026/301', 'class_id' => $classIds['SS 1B (Art)'], 'gender' => 'female', 'dob' => '2010-01-16', 'state' => 'Osun', 'lga' => 'Ife Central'],
            'std_art_2'    => ['user_id' => $users['std_art_2'], 'admission_number' => 'CIS/2026/302', 'class_id' => $classIds['SS 1B (Art)'], 'gender' => 'male', 'dob' => '2010-05-23', 'state' => 'Anambra', 'lga' => 'Ihiala'],
            'std_art_3'    => ['user_id' => $users['std_art_3'], 'admission_number' => 'CIS/2026/303', 'class_id' => $classIds['SS 1B (Art)'], 'gender' => 'female', 'dob' => '2010-09-11', 'state' => 'Gombe', 'lga' => 'Gombe'],
            'std_art_4'    => ['user_id' => $users['std_art_4'], 'admission_number' => 'CIS/2026/304', 'class_id' => $classIds['SS 1B (Art)'], 'gender' => 'female', 'dob' => '2010-11-04', 'state' => 'Lagos', 'lga' => 'Eti-Osa'],
            'std_art_5'    => ['user_id' => $users['std_art_5'], 'admission_number' => 'CIS/2026/305', 'class_id' => $classIds['SS 1B (Art)'], 'gender' => 'male', 'dob' => '2010-03-19', 'state' => 'Enugu', 'lga' => 'Nsukka'],
            'std_art_6'    => ['user_id' => $users['std_art_6'], 'admission_number' => 'CIS/2026/306', 'class_id' => $classIds['SS 1B (Art)'], 'gender' => 'male', 'dob' => '2010-07-08', 'state' => 'Bauchi', 'lga' => 'Bauchi'],
            'std_art_7'    => ['user_id' => $users['std_art_7'], 'admission_number' => 'CIS/2026/307', 'class_id' => $classIds['SS 1B (Art)'], 'gender' => 'male', 'dob' => '2010-12-14', 'state' => 'Edo', 'lga' => 'Esan West'],
            'std_art_8'    => ['user_id' => $users['std_art_8'], 'admission_number' => 'CIS/2026/308', 'class_id' => $classIds['SS 1B (Art)'], 'gender' => 'female', 'dob' => '2010-04-25', 'state' => 'Anambra', 'lga' => 'Aguata'],
            'std_art_9'    => ['user_id' => $users['std_art_9'], 'admission_number' => 'CIS/2026/309', 'class_id' => $classIds['SS 1B (Art)'], 'gender' => 'female', 'dob' => '2010-08-30', 'state' => 'Ondo', 'lga' => 'Akure South'],
            'std_art_10'   => ['user_id' => $users['std_art_10'], 'admission_number' => 'CIS/2026/310', 'class_id' => $classIds['SS 1B (Art)'], 'gender' => 'female', 'dob' => '2010-10-17', 'state' => 'Kaduna', 'lga' => 'Kaduna North'],

            // SS 1C (Commercial) cohort (10 students)
            'std_com_1'    => ['user_id' => $users['std_com_1'], 'admission_number' => 'CIS/2026/401', 'class_id' => $classIds['SS 1C (Commercial)'], 'gender' => 'male', 'dob' => '2010-02-09', 'state' => 'Lagos', 'lga' => 'Alimosho'],
            'std_com_2'    => ['user_id' => $users['std_com_2'], 'admission_number' => 'CIS/2026/402', 'class_id' => $classIds['SS 1C (Commercial)'], 'gender' => 'male', 'dob' => '2010-06-18', 'state' => 'Abia', 'lga' => 'Umuahia North'],
            'std_com_3'    => ['user_id' => $users['std_com_3'], 'admission_number' => 'CIS/2026/403', 'class_id' => $classIds['SS 1C (Commercial)'], 'gender' => 'male', 'dob' => '2010-09-24', 'state' => 'Kano', 'lga' => 'Dala'],
            'std_com_4'    => ['user_id' => $users['std_com_4'], 'admission_number' => 'CIS/2026/404', 'class_id' => $classIds['SS 1C (Commercial)'], 'gender' => 'female', 'dob' => '2010-11-13', 'state' => 'Ogun', 'lga' => 'Sagamu'],
            'std_com_5'    => ['user_id' => $users['std_com_5'], 'admission_number' => 'CIS/2026/405', 'class_id' => $classIds['SS 1C (Commercial)'], 'gender' => 'male', 'dob' => '2010-03-29', 'state' => 'Anambra', 'lga' => 'Idemili North'],
            'std_com_6'    => ['user_id' => $users['std_com_6'], 'admission_number' => 'CIS/2026/406', 'class_id' => $classIds['SS 1C (Commercial)'], 'gender' => 'male', 'dob' => '2010-07-22', 'state' => 'Adamawa', 'lga' => 'Yola North'],
            'std_com_7'    => ['user_id' => $users['std_com_7'], 'admission_number' => 'CIS/2026/407', 'class_id' => $classIds['SS 1C (Commercial)'], 'gender' => 'female', 'dob' => '2010-12-08', 'state' => 'Edo', 'lga' => 'Uhunmwonde'],
            'std_com_8'    => ['user_id' => $users['std_com_8'], 'admission_number' => 'CIS/2026/408', 'class_id' => $classIds['SS 1C (Commercial)'], 'gender' => 'female', 'dob' => '2010-04-17', 'state' => 'Abia', 'lga' => 'Ohafia'],
            'std_com_9'    => ['user_id' => $users['std_com_9'], 'admission_number' => 'CIS/2026/409', 'class_id' => $classIds['SS 1C (Commercial)'], 'gender' => 'male', 'dob' => '2010-08-05', 'state' => 'Lagos', 'lga' => 'Oshodi-Isolo'],
            'std_com_10'   => ['user_id' => $users['std_com_10'], 'admission_number' => 'CIS/2026/410', 'class_id' => $classIds['SS 1C (Commercial)'], 'gender' => 'female', 'dob' => '2010-10-21', 'state' => 'Kebbi', 'lga' => 'Birnin Kebbi'],
        ];

        $studentIds = [];
        $stmt = $pdo->prepare("
            INSERT INTO students (user_id, admission_number, current_class_id, gender, date_of_birth, state_of_origin, lga, nationality)
            VALUES (:user_id, :admission_number, :class_id, :gender, :dob, :state, :lga, 'Nigerian')
            ON DUPLICATE KEY UPDATE current_class_id = VALUES(current_class_id)
        ");

        foreach ($students as $key => $s) {
            $check = $pdo->prepare("SELECT id FROM students WHERE user_id = :uid LIMIT 1");
            $check->execute([':uid' => $s['user_id']]);
            $id = $check->fetchColumn();

            if (!$id) {
                $stmt->execute([
                    ':user_id' => $s['user_id'],
                    ':admission_number' => $s['admission_number'],
                    ':class_id' => $s['class_id'],
                    ':gender' => $s['gender'],
                    ':dob' => $s['dob'],
                    ':state' => $s['state'],
                    ':lga' => $s['lga'],
                ]);
                $id = $pdo->lastInsertId();
            } else {
                $pdo->prepare("UPDATE students SET current_class_id = :cid, state_of_origin = :st, lga = :lga WHERE id = :id")->execute([
                    ':cid' => $s['class_id'],
                    ':st' => $s['state'],
                    ':lga' => $s['lga'],
                    ':id' => $id,
                ]);
            }

            $studentIds[$key] = (int)$id;
        }

        return $studentIds;
    }

    private function seedParents(PDO $pdo, array $users): array
    {
        $parents = [
            'doe'          => $users['parent_doe'],
            'smith'        => $users['parent_smith'],
            'prt_adeyemi'  => $users['prt_adeyemi'],
            'prt_nwosu'    => $users['prt_nwosu'],
            'prt_garba'    => $users['prt_garba'],
            'prt_okonkwo'  => $users['prt_okonkwo'],
            'prt_fashola'  => $users['prt_fashola'],
            'prt_igbinedion'=> $users['prt_igbinedion'],
            'prt_adeleke'  => $users['prt_adeleke'],
            'prt_okoye'    => $users['prt_okoye'],
            'prt_oyewole'  => $users['prt_oyewole'],
            'prt_akindele' => $users['prt_akindele'],
        ];

        $parentIds = [];
        $getUserStmt = $pdo->prepare("SELECT name, email, phone FROM users WHERE id = :uid LIMIT 1");
        $stmt = $pdo->prepare("
            INSERT INTO parents (user_id, name, phone, email)
            VALUES (:user_id, :name, :phone, :email)
            ON DUPLICATE KEY UPDATE name = VALUES(name), phone = VALUES(phone), email = VALUES(email)
        ");

        foreach ($parents as $key => $userId) {
            $check = $pdo->prepare("SELECT id FROM parents WHERE user_id = :uid LIMIT 1");
            $check->execute([':uid' => $userId]);
            $id = $check->fetchColumn();

            $getUserStmt->execute([':uid' => $userId]);
            $uRow = $getUserStmt->fetch(PDO::FETCH_ASSOC) ?: ['name' => 'Parent', 'phone' => null, 'email' => null];

            if (!$id) {
                $stmt->execute([
                    ':user_id' => $userId,
                    ':name' => $uRow['name'] ?? 'Parent',
                    ':phone' => $uRow['phone'] ?? null,
                    ':email' => $uRow['email'] ?? null,
                ]);
                $id = $pdo->lastInsertId();
            } else {
                $pdo->prepare("UPDATE parents SET name = :name, phone = :phone, email = :email WHERE id = :id")->execute([
                    ':name' => $uRow['name'] ?? 'Parent',
                    ':phone' => $uRow['phone'] ?? null,
                    ':email' => $uRow['email'] ?? null,
                    ':id' => $id,
                ]);
            }

            $parentIds[$key] = (int)$id;
        }

        return $parentIds;
    }

    private function seedParentStudentLinks(PDO $pdo, array $parentIds, array $studentIds): void
    {
        $links = [
            ['parent_id' => $parentIds['doe'], 'student_id' => $studentIds['john'], 'relation' => 'Father'],
            ['parent_id' => $parentIds['doe'], 'student_id' => $studentIds['mary'], 'relation' => 'Father'],
            ['parent_id' => $parentIds['smith'], 'student_id' => $studentIds['david'], 'relation' => 'Mother'],
            ['parent_id' => $parentIds['prt_adeyemi'], 'student_id' => $studentIds['std_adeyemi'], 'relation' => 'Father'],
            ['parent_id' => $parentIds['prt_nwosu'], 'student_id' => $studentIds['std_nwosu'], 'relation' => 'Father'],
            ['parent_id' => $parentIds['prt_garba'], 'student_id' => $studentIds['std_garba'], 'relation' => 'Father'],
            ['parent_id' => $parentIds['prt_okonkwo'], 'student_id' => $studentIds['std_okonkwo'], 'relation' => 'Father'],
            ['parent_id' => $parentIds['prt_fashola'], 'student_id' => $studentIds['std_fashola'], 'relation' => 'Father'],
            ['parent_id' => $parentIds['prt_igbinedion'], 'student_id' => $studentIds['std_igbinedion'], 'relation' => 'Father'],
            ['parent_id' => $parentIds['prt_adeleke'], 'student_id' => $studentIds['std_sci_1'], 'relation' => 'Father'],
            ['parent_id' => $parentIds['prt_okoye'], 'student_id' => $studentIds['std_sci_2'], 'relation' => 'Father'],
            ['parent_id' => $parentIds['prt_oyewole'], 'student_id' => $studentIds['std_art_1'], 'relation' => 'Father'],
            ['parent_id' => $parentIds['prt_akindele'], 'student_id' => $studentIds['std_com_1'], 'relation' => 'Father'],
        ];

        $stmt = $pdo->prepare("
            INSERT INTO parent_student (parent_id, student_id, relationship_type)
            VALUES (:parent_id, :student_id, :relation)
            ON DUPLICATE KEY UPDATE relationship_type = VALUES(relationship_type)
        ");

        foreach ($links as $l) {
            $stmt->execute($l);
        }
    }

    private function seedClassSubjects(PDO $pdo, int $sessionId, array $classIds, array $subjectIds, array $teacherIds): array
    {
        // 10 subjects for JSS 1 (Identical for Arm A and Arm B)
        $jss1Subjects = [
            ['sub' => 'MTH', 'teacher' => 'adebayo'],
            ['sub' => 'ENG', 'teacher' => 'okoro'],
            ['sub' => 'SCI', 'teacher' => 'adebayo'],
            ['sub' => 'CIV', 'teacher' => 'danladi'],
            ['sub' => 'JSS_BTE', 'teacher' => 'enahoro'],
            ['sub' => 'JSS_AGR', 'teacher' => 'adeleke'],
            ['sub' => 'JSS_BST', 'teacher' => 'obi'],
            ['sub' => 'JSS_ICT', 'teacher' => 'olatunji'],
            ['sub' => 'JSS_CCA', 'teacher' => 'awolowo'],
            ['sub' => 'JSS_PHE', 'teacher' => 'ojo'],
        ];

        // 10 subjects for SS 1A (Science): 4 general + 6 science core
        $ss1ScienceSubjects = [
            ['sub' => 'SSS_ENG', 'teacher' => 'okoro'],
            ['sub' => 'SSS_MTH', 'teacher' => 'adebayo'],
            ['sub' => 'SSS_CIV', 'teacher' => 'danladi'],
            ['sub' => 'SSS_ECO', 'teacher' => 'obi'],
            ['sub' => 'SSS_PHY', 'teacher' => 'enahoro'],
            ['sub' => 'SSS_CHM', 'teacher' => 'adeleke'],
            ['sub' => 'SSS_BIO', 'teacher' => 'eze'],
            ['sub' => 'SSS_FMT', 'teacher' => 'adebayo'],
            ['sub' => 'SSS_TDR', 'teacher' => 'enahoro'],
            ['sub' => 'SSS_AGR', 'teacher' => 'danladi'],
        ];

        // 10 subjects for SS 1B (Art): 4 general + 6 art core
        $ss1ArtSubjects = [
            ['sub' => 'SSS_ENG', 'teacher' => 'okoro'],
            ['sub' => 'SSS_MTH', 'teacher' => 'adebayo'],
            ['sub' => 'SSS_CIV', 'teacher' => 'danladi'],
            ['sub' => 'SSS_ECO', 'teacher' => 'obi'],
            ['sub' => 'SSS_LIT', 'teacher' => 'awolowo'],
            ['sub' => 'SSS_GOV', 'teacher' => 'danladi'],
            ['sub' => 'SSS_HIS', 'teacher' => 'awolowo'],
            ['sub' => 'SSS_CRS', 'teacher' => 'eze'],
            ['sub' => 'SSS_ART', 'teacher' => 'ojo'],
            ['sub' => 'SSS_NLG', 'teacher' => 'olatunji'],
        ];

        // 10 subjects for SS 1C (Commercial): 4 general + 6 commercial core
        $ss1CommercialSubjects = [
            ['sub' => 'SSS_ENG', 'teacher' => 'okoro'],
            ['sub' => 'SSS_MTH', 'teacher' => 'adebayo'],
            ['sub' => 'SSS_CIV', 'teacher' => 'danladi'],
            ['sub' => 'SSS_ECO', 'teacher' => 'obi'],
            ['sub' => 'SSS_ACC', 'teacher' => 'obi'],
            ['sub' => 'SSS_COM', 'teacher' => 'adeleke'],
            ['sub' => 'SSS_MKT', 'teacher' => 'olatunji'],
            ['sub' => 'SSS_BKP', 'teacher' => 'obi'],
            ['sub' => 'SSS_OFP', 'teacher' => 'olatunji'],
            ['sub' => 'SSS_DPR', 'teacher' => 'enahoro'],
        ];

        $classSubjectIds = [];
        $stmt = $pdo->prepare("
            INSERT INTO class_subjects (session_id, class_id, subject_id, teacher_id, status)
            VALUES (:session_id, :class_id, :subject_id, :teacher_id, 'active')
            ON DUPLICATE KEY UPDATE teacher_id = VALUES(teacher_id)
        ");

        $allocate = function (string $classKey, array $subjectList) use ($pdo, $sessionId, $classIds, $subjectIds, $teacherIds, $stmt, &$classSubjectIds) {
            if (empty($classIds[$classKey])) return;
            $cid = $classIds[$classKey];

            foreach ($subjectList as $item) {
                if (empty($subjectIds[$item['sub']])) continue;
                $subId = $subjectIds[$item['sub']];
                $tId = $teacherIds[$item['teacher']] ?? null;

                $check = $pdo->prepare("SELECT id FROM class_subjects WHERE session_id = :sid AND class_id = :cid AND subject_id = :subid LIMIT 1");
                $check->execute([':sid' => $sessionId, ':cid' => $cid, ':subid' => $subId]);
                $id = $check->fetchColumn();

                if (!$id) {
                    $stmt->execute([
                        ':session_id' => $sessionId,
                        ':class_id' => $cid,
                        ':subject_id' => $subId,
                        ':teacher_id' => $tId,
                    ]);
                    $id = $pdo->lastInsertId();
                } else if ($tId) {
                    $pdo->prepare("UPDATE class_subjects SET teacher_id = :tid WHERE id = :id")->execute([':tid' => $tId, ':id' => $id]);
                }

                $key = strtolower(str_replace([' ', '(', ')'], ['_', '', ''], $classKey)) . '_' . strtolower($item['sub']);
                $classSubjectIds[$key] = (int)$id;
            }
        };

        // Allocate Arm A and Arm B with exact same 10 subjects for JSS 1
        $allocate('JSS 1A', $jss1Subjects);
        $allocate('JSS 1B', $jss1Subjects);

        // Allocate Senior Secondary Departmental Arms
        $allocate('SS 1A (Science)', $ss1ScienceSubjects);
        $allocate('SS 1B (Art)', $ss1ArtSubjects);
        $allocate('SS 1C (Commercial)', $ss1CommercialSubjects);

        // Backward compatibility keys for existing test assertions
        if (isset($classSubjectIds['jss_1a_mth'])) {
            $classSubjectIds['jss1a_mth'] = $classSubjectIds['jss_1a_mth'];
        }
        if (isset($classSubjectIds['jss_1a_eng'])) {
            $classSubjectIds['jss1a_eng'] = $classSubjectIds['jss_1a_eng'];
        }
        if (isset($classSubjectIds['jss_1a_sci'])) {
            $classSubjectIds['jss1a_sci'] = $classSubjectIds['jss_1a_sci'];
        }
        if (isset($classSubjectIds['jss_1b_mth'])) {
            $classSubjectIds['jss1b_mth'] = $classSubjectIds['jss_1b_mth'];
        }

        return $classSubjectIds;
    }

    private function seedEnrollments(PDO $pdo, int $sessionId, array $classIds, array $studentIds, array $classSubjectIds): void
    {
        $classEnrStmt = $pdo->prepare("
            INSERT INTO class_enrollments (student_id, class_id, session_id, status)
            VALUES (:student_id, :class_id, :session_id, 'active')
            ON DUPLICATE KEY UPDATE status = 'active'
        ");

        $subEnrStmt = $pdo->prepare("
            INSERT INTO student_subject_enrollments (student_id, class_subject_id, session_id, is_elective, status)
            VALUES (:student_id, :class_subject_id, :session_id, 0, 'active')
            ON DUPLICATE KEY UPDATE status = 'active'
        ");

        // Helper to enroll an entire student cohort into their class and its subjects
        $enrollCohort = function (array $stdKeyList, string $classKey, array $subjectKeys) use ($sessionId, $classIds, $studentIds, $classSubjectIds, $classEnrStmt, $subEnrStmt) {
            if (empty($classIds[$classKey])) return;
            $cid = $classIds[$classKey];

            foreach ($stdKeyList as $sKey) {
                if (empty($studentIds[$sKey])) continue;
                $sId = $studentIds[$sKey];

                $classEnrStmt->execute([':student_id' => $sId, ':class_id' => $cid, ':session_id' => $sessionId]);

                foreach ($subjectKeys as $subKey) {
                    $lookup = strtolower(str_replace([' ', '(', ')'], ['_', '', ''], $classKey)) . '_' . strtolower($subKey);
                    if (!empty($classSubjectIds[$lookup])) {
                        $subEnrStmt->execute([':student_id' => $sId, ':class_subject_id' => $classSubjectIds[$lookup], ':session_id' => $sessionId]);
                    }
                }
            }
        };

        // Enroll JSS 1A cohort
        $jss1aStudents = ['john', 'mary', 'std_adeyemi', 'std_nwosu', 'std_garba', 'std_okonkwo', 'std_fashola', 'std_igbinedion', 'std_sanusi', 'std_nnamani', 'std_alabi', 'std_akpoveta'];
        $jss1Subjects = ['MTH', 'ENG', 'SCI', 'CIV', 'JSS_BTE', 'JSS_AGR', 'JSS_BST', 'JSS_ICT', 'JSS_CCA', 'JSS_PHE'];
        $enrollCohort($jss1aStudents, 'JSS 1A', $jss1Subjects);

        // Enroll JSS 1B cohort
        $jss1bStudents = ['david', 'std_ogundipe', 'std_okafor', 'std_abubakar', 'std_eze', 'std_omoruyi', 'std_mohammed', 'std_balogun', 'std_igwe', 'std_danladi', 'std_osagie'];
        $enrollCohort($jss1bStudents, 'JSS 1B', $jss1Subjects);

        // Enroll SS 1A (Science) cohort
        $ss1aStudents = ['std_sci_1', 'std_sci_2', 'std_sci_3', 'std_sci_4', 'std_sci_5', 'std_sci_6', 'std_sci_7', 'std_sci_8', 'std_sci_9', 'std_sci_10'];
        $ss1aSubjects = ['SSS_ENG', 'SSS_MTH', 'SSS_CIV', 'SSS_ECO', 'SSS_PHY', 'SSS_CHM', 'SSS_BIO', 'SSS_FMT', 'SSS_TDR', 'SSS_AGR'];
        $enrollCohort($ss1aStudents, 'SS 1A (Science)', $ss1aSubjects);

        // Enroll SS 1B (Art) cohort
        $ss1bStudents = ['std_art_1', 'std_art_2', 'std_art_3', 'std_art_4', 'std_art_5', 'std_art_6', 'std_art_7', 'std_art_8', 'std_art_9', 'std_art_10'];
        $ss1bSubjects = ['SSS_ENG', 'SSS_MTH', 'SSS_CIV', 'SSS_ECO', 'SSS_LIT', 'SSS_GOV', 'SSS_HIS', 'SSS_CRS', 'SSS_ART', 'SSS_NLG'];
        $enrollCohort($ss1bStudents, 'SS 1B (Art)', $ss1bSubjects);

        // Enroll SS 1C (Commercial) cohort
        $ss1cStudents = ['std_com_1', 'std_com_2', 'std_com_3', 'std_com_4', 'std_com_5', 'std_com_6', 'std_com_7', 'std_com_8', 'std_com_9', 'std_com_10'];
        $ss1cSubjects = ['SSS_ENG', 'SSS_MTH', 'SSS_CIV', 'SSS_ECO', 'SSS_ACC', 'SSS_COM', 'SSS_MKT', 'SSS_BKP', 'SSS_OFP', 'SSS_DPR'];
        $enrollCohort($ss1cStudents, 'SS 1C (Commercial)', $ss1cSubjects);
    }

    private function seedCoursework(PDO $pdo, int $termId, array $classSubjectIds, array $teacherIds, array $studentIds): void
    {
        $targetCsId = $classSubjectIds['jss1a_mth'] ?? ($classSubjectIds['jss_1a_mth'] ?? null);
        $targetTeacherId = $teacherIds['adebayo'] ?? null;
        if (!$targetCsId || !$targetTeacherId) return;

        // Content item
        $contentStmt = $pdo->prepare("
            INSERT INTO content_items (class_subject_id, teacher_id, topic, title, description, type, published_at)
            VALUES (:cs_id, :teacher_id, :topic, :title, :desc, :type, NOW())
        ");

        $contentStmt->execute([
            ':cs_id' => $targetCsId,
            ':teacher_id' => $targetTeacherId,
            ':topic' => 'Algebra Foundations',
            ':title' => 'Introduction to Algebraic Expressions & Variables',
            ':desc' => 'Comprehensive lecture notes covering basic definitions, simplifying expressions, and substituting values.',
            ':type' => 'note',
        ]);

        // Assignment
        $asgnStmt = $pdo->prepare("
            INSERT INTO assignments (class_subject_id, term_id, teacher_id, title, instructions, max_score, due_at, status)
            VALUES (:cs_id, :term_id, :teacher_id, :title, :instructions, 20.00, DATE_ADD(NOW(), INTERVAL 7 DAY), 'published')
        ");

        $asgnStmt->execute([
            ':cs_id' => $targetCsId,
            ':term_id' => $termId,
            ':teacher_id' => $targetTeacherId,
            ':title' => 'Algebraic Simplification Exercise 1',
            ':instructions' => 'Complete questions 1 through 10 from Chapter 3 and submit your step-by-step working.',
        ]);
        $assignmentId = (int)$pdo->lastInsertId();

        // Submission
        if (!empty($studentIds['john'])) {
            $subStmt = $pdo->prepare("
                INSERT INTO assignment_submissions (assignment_id, student_id, text_response, submitted_at)
                VALUES (:assignment_id, :student_id, :text, NOW())
                ON DUPLICATE KEY UPDATE text_response = VALUES(text_response)
            ");
            $subStmt->execute([
                ':assignment_id' => $assignmentId,
                ':student_id' => $studentIds['john'],
                ':text' => "1. 3x + 4x = 7x\n2. 5(2a - 3) = 10a - 15\n3. Simplified working complete.",
            ]);
        }
    }

    private function seedCbt(PDO $pdo, int $termId, array $classSubjectIds, array $teacherIds, array $subjectIds, array $studentIds): void
    {
        $subId = $subjectIds['MTH'] ?? null;
        $tId = $teacherIds['adebayo'] ?? null;
        $csId = $classSubjectIds['jss1a_mth'] ?? ($classSubjectIds['jss_1a_mth'] ?? null);
        if (!$subId || !$tId || !$csId) return;

        $qStmt = $pdo->prepare("
            INSERT INTO questions (subject_id, topic, question_text, type, default_points, created_by)
            VALUES (:subject_id, :topic, :question_text, :type, 5.00, :created_by)
        ");

        $qStmt->execute([
            ':subject_id' => $subId,
            ':topic' => 'Linear Equations',
            ':question_text' => 'What is the value of x if 2x + 6 = 14?',
            ':type' => 'mcq',
            ':created_by' => $tId,
        ]);
        $q1Id = (int)$pdo->lastInsertId();

        $optStmt = $pdo->prepare("INSERT INTO question_options (question_id, option_text, is_correct) VALUES (:qid, :text, :is_correct)");
        $optStmt->execute([':qid' => $q1Id, ':text' => '2', ':is_correct' => 0]);
        $optStmt->execute([':qid' => $q1Id, ':text' => '4', ':is_correct' => 1]);
        $optStmt->execute([':qid' => $q1Id, ':text' => '6', ':is_correct' => 0]);
        $optStmt->execute([':qid' => $q1Id, ':text' => '8', ':is_correct' => 0]);

        $quizStmt = $pdo->prepare("
            INSERT INTO quizzes (class_subject_id, term_id, teacher_id, title, instructions, time_limit_minutes, max_attempts, is_published, published_at)
            VALUES (:cs_id, :term_id, :teacher_id, :title, :instructions, 30, 2, 1, NOW())
        ");
        $quizStmt->execute([
            ':cs_id' => $csId,
            ':term_id' => $termId,
            ':teacher_id' => $tId,
            ':title' => 'JSS 1 Mathematics CBT Diagnostic Quiz',
            ':instructions' => 'Answer all questions carefully. Time limit is 30 minutes.',
        ]);
        $quizId = (int)$pdo->lastInsertId();

        $qqStmt = $pdo->prepare("INSERT INTO quiz_questions (quiz_id, question_id, points, sort_order) VALUES (:quiz_id, :question_id, 5.00, 1) ON DUPLICATE KEY UPDATE points = 5.00");
        $qqStmt->execute([':quiz_id' => $quizId, ':question_id' => $q1Id]);
    }

    private function seedAttendanceAndAnnouncements(PDO $pdo, int $sessionId, int $termId, array $classIds, array $studentIds, array $users): void
    {
        $today = gmdate('Y-m-d');
        $attStmt = $pdo->prepare("
            INSERT INTO attendance_records (session_id, term_id, class_id, student_id, date, status, marked_by)
            VALUES (:session_id, :term_id, :class_id, :student_id, :date, :status, :marked_by)
            ON DUPLICATE KEY UPDATE status = VALUES(status)
        ");

        $cid = $classIds['JSS 1A'] ?? null;
        $mBy = $users['teacher_adebayo'] ?? null;
        if ($cid && $mBy) {
            foreach (['john', 'mary', 'std_adeyemi', 'std_nwosu'] as $sKey) {
                if (!empty($studentIds[$sKey])) {
                    $attStmt->execute([
                        ':session_id' => $sessionId,
                        ':term_id' => $termId,
                        ':class_id' => $cid,
                        ':student_id' => $studentIds[$sKey],
                        ':date' => $today,
                        ':status' => 'present',
                        ':marked_by' => $mBy,
                    ]);
                }
            }
        }

        $annStmt = $pdo->prepare("
            INSERT INTO announcements (author_id, scope, title, body, published_at)
            VALUES (:author_id, 'school', :title, :body, NOW())
        ");
        if (!empty($users['admin'])) {
            $annStmt->execute([
                ':author_id' => $users['admin'],
                ':title' => 'Welcome to the 2026/2027 Academic Session',
                ':body' => 'Welcome back students, parents, and staff. We look forward to a successful and productive term under the unified Nigerian curriculum.',
            ]);
        }
    }

    private function seedTimetables(PDO $pdo, int $termId, array $classSubjectIds): void
    {
        $mthId = $classSubjectIds['jss1a_mth'] ?? ($classSubjectIds['jss_1a_mth'] ?? null);
        $engId = $classSubjectIds['jss1a_eng'] ?? ($classSubjectIds['jss_1a_eng'] ?? null);
        $sciId = $classSubjectIds['jss1a_sci'] ?? ($classSubjectIds['jss_1a_sci'] ?? null);

        if (!$mthId) return;

        $slots = [
            ['cs_id' => $mthId, 'day' => 'mon', 'start' => '08:30:00', 'end' => '09:30:00', 'room' => 'Room 101'],
            ['cs_id' => $engId ?: $mthId, 'day' => 'mon', 'start' => '09:30:00', 'end' => '10:30:00', 'room' => 'Room 101'],
            ['cs_id' => $sciId ?: $mthId, 'day' => 'tue', 'start' => '08:30:00', 'end' => '09:30:00', 'room' => 'Science Lab A'],
        ];

        $stmt = $pdo->prepare("
            INSERT INTO timetable_slots (term_id, class_subject_id, day_of_week, start_time, end_time, room)
            VALUES (:term_id, :cs_id, :day, :start, :end, :room)
        ");

        foreach ($slots as $s) {
            $stmt->execute([
                ':term_id' => $termId,
                ':cs_id' => $s['cs_id'],
                ':day' => $s['day'],
                ':start' => $s['start'],
                ':end' => $s['end'],
                ':room' => $s['room'],
            ]);
        }
    }
}
