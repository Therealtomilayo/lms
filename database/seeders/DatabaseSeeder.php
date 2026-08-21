<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Core\Database;
use PDO;

/**
 * Master Database Seeder for Claret LMS
 *
 * Populates a complete, demonstrable test and staging dataset
 * compliant with Section 34 of the Implementation Blueprint.
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
            // Disable foreign key checks for clean re-seeding
            $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
            if ($driver === 'mysql') {
                $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
            }

            // 1. System Settings
            $this->seedSystemSettings($pdo);
            $summary['settings'] = 'Configured';

            // 2. Grading Scales
            $scaleId = $this->seedGradingScales($pdo);
            $summary['grading'] = 'Configured';

            // 3. Academic Structure (Levels, Sessions, Terms, Classes, Subjects)
            $levelIds = $this->seedAcademicLevels($pdo, $scaleId);
            $sessionId = $this->seedSession($pdo);
            $termIds = $this->seedTerms($pdo, $sessionId);
            $this->seedAssessmentCategories($pdo, $sessionId, $termIds[0]);
            $classIds = $this->seedClasses($pdo, $levelIds);
            $subjectIds = $this->seedSubjects($pdo);
            $summary['academic_structure'] = 'Configured';

            // 4. Users & Roles (Super Admin, Admin, Teachers, Students, Parents)
            $users = $this->seedUsersAndRoles($pdo);
            $teacherIds = $this->seedTeachers($pdo, $users);
            $studentIds = $this->seedStudents($pdo, $users, $classIds);
            $parentIds = $this->seedParents($pdo, $users);
            $this->seedParentStudentLinks($pdo, $parentIds, $studentIds);
            $summary['users'] = count($users) . ' seeded';

            // 5. Teacher Allocations (Class Subjects)
            $classSubjectIds = $this->seedClassSubjects($pdo, $sessionId, $classIds, $subjectIds, $teacherIds);
            $summary['allocations'] = count($classSubjectIds) . ' class subjects';

            // 6. Enrollments (Class Enrollments & Subject Enrollments)
            $this->seedEnrollments($pdo, $sessionId, $classIds, $studentIds, $classSubjectIds);
            $summary['enrollments'] = 'Configured';

            // 7. Coursework (Content Items, Assignments, Submissions)
            $this->seedCoursework($pdo, $termIds[0], $classSubjectIds, $teacherIds, $studentIds);
            $summary['coursework'] = 'Configured';

            // 8. CBT Quizzes, Questions & Attempts
            $this->seedCbt($pdo, $termIds[0], $classSubjectIds, $teacherIds, $subjectIds, $studentIds);
            $summary['quizzes'] = 'Configured';

            // 9. Attendance & Announcements
            $this->seedAttendanceAndAnnouncements($pdo, $sessionId, $termIds[0], $classIds, $studentIds, $users);
            $summary['attendance_and_announcements'] = 'Configured';

            // 10. Timetables
            $this->seedTimetables($pdo, $termIds[0], $classSubjectIds);
            $summary['timetables'] = 'Configured';

            if ($driver === 'mysql') {
                $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
            }
        });

        return $summary;
    }

    private function seedSystemSettings(PDO $pdo): void
    {
        $settings = [
            'school_name' => 'Claret Academy',
            'school_motto' => 'Excellence, Character, and Knowledge',
            'school_email' => 'info@claret.edu',
            'school_phone' => '+234 800 123 4567',
            'school_address' => '12 Claret Way, Victoria Island, Lagos, Nigeria',
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

    private function seedGradingScales(PDO $pdo): int
    {
        $stmt = $pdo->prepare("SELECT id FROM grading_scales WHERE is_default = 1 LIMIT 1");
        $stmt->execute();
        $scaleId = $stmt->fetchColumn();

        if (!$scaleId) {
            $pdo->prepare("
                INSERT INTO grading_scales (name, description, is_default)
                VALUES ('Standard Secondary Scale (WAEC/NECO)', 'Standard 9-point secondary grading scale', 1)
            ")->execute();
            $scaleId = (int)$pdo->lastInsertId();

            $boundaries = [
                ['grading_scale_id' => $scaleId, 'letter' => 'A1', 'min_score' => 75.00, 'max_score' => 100.00, 'grade_point' => 4.00, 'remark' => 'Excellent'],
                ['grading_scale_id' => $scaleId, 'letter' => 'B2', 'min_score' => 70.00, 'max_score' => 74.99, 'grade_point' => 3.50, 'remark' => 'Very Good'],
                ['grading_scale_id' => $scaleId, 'letter' => 'B3', 'min_score' => 65.00, 'max_score' => 69.99, 'grade_point' => 3.00, 'remark' => 'Good'],
                ['grading_scale_id' => $scaleId, 'letter' => 'C4', 'min_score' => 60.00, 'max_score' => 64.99, 'grade_point' => 2.50, 'remark' => 'Credit'],
                ['grading_scale_id' => $scaleId, 'letter' => 'C5', 'min_score' => 55.00, 'max_score' => 59.99, 'grade_point' => 2.00, 'remark' => 'Credit'],
                ['grading_scale_id' => $scaleId, 'letter' => 'C6', 'min_score' => 50.00, 'max_score' => 54.99, 'grade_point' => 1.50, 'remark' => 'Credit'],
                ['grading_scale_id' => $scaleId, 'letter' => 'D7', 'min_score' => 45.00, 'max_score' => 49.99, 'grade_point' => 1.00, 'remark' => 'Pass'],
                ['grading_scale_id' => $scaleId, 'letter' => 'E8', 'min_score' => 40.00, 'max_score' => 44.99, 'grade_point' => 0.50, 'remark' => 'Pass'],
                ['grading_scale_id' => $scaleId, 'letter' => 'F9', 'min_score' => 0.00, 'max_score' => 39.99, 'grade_point' => 0.00, 'remark' => 'Fail'],
            ];

            $boundStmt = $pdo->prepare("
                INSERT INTO grade_boundaries (grading_scale_id, letter, min_score, max_score, grade_point, remark)
                VALUES (:grading_scale_id, :letter, :min_score, :max_score, :grade_point, :remark)
            ");

            foreach ($boundaries as $b) {
                $boundStmt->execute($b);
            }
        }

        return (int)$scaleId;
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

    private function seedAcademicLevels(PDO $pdo, int $scaleId): array
    {
        $levels = [
            'JSS 1' => ['name' => 'Junior Secondary 1', 'stage' => 'junior_secondary', 'rank_order' => 1],
            'JSS 2' => ['name' => 'Junior Secondary 2', 'stage' => 'junior_secondary', 'rank_order' => 2],
            'SSS 1' => ['name' => 'Senior Secondary 1', 'stage' => 'senior_secondary', 'rank_order' => 3],
        ];

        $levelIds = [];
        $stmt = $pdo->prepare("
            INSERT INTO academic_levels (name, stage, rank_order, grading_scale_id)
            VALUES (:name, :stage, :rank_order, :scale_id)
            ON DUPLICATE KEY UPDATE rank_order = VALUES(rank_order)
        ");

        foreach ($levels as $short => $data) {
            $check = $pdo->prepare("SELECT id FROM academic_levels WHERE name = :name LIMIT 1");
            $check->execute([':name' => $data['name']]);
            $id = $check->fetchColumn();

            if (!$id) {
                $stmt->execute([
                    ':name' => $data['name'],
                    ':stage' => $data['stage'],
                    ':rank_order' => $data['rank_order'],
                    ':scale_id' => $scaleId,
                ]);
                $id = $pdo->lastInsertId();
            }

            $levelIds[$short] = (int)$id;
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
            ON DUPLICATE KEY UPDATE status = VALUES(status)
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

    private function seedClasses(PDO $pdo, array $levelIds): array
    {
        $classes = [
            'JSS 1A' => ['level_id' => $levelIds['JSS 1'], 'name' => 'JSS 1A', 'section_arm' => 'A'],
            'JSS 1B' => ['level_id' => $levelIds['JSS 1'], 'name' => 'JSS 1B', 'section_arm' => 'B'],
            'SSS 1A' => ['level_id' => $levelIds['SSS 1'], 'name' => 'SSS 1A', 'section_arm' => 'A'],
        ];

        $classIds = [];
        $stmt = $pdo->prepare("
            INSERT INTO classes (academic_level_id, name, section_arm, status)
            VALUES (:level_id, :name, :arm, 'active')
        ");

        foreach ($classes as $key => $c) {
            $check = $pdo->prepare("SELECT id FROM classes WHERE name = :name LIMIT 1");
            $check->execute([':name' => $c['name']]);
            $id = $check->fetchColumn();

            if (!$id) {
                $stmt->execute([
                    ':level_id' => $c['level_id'],
                    ':name' => $c['name'],
                    ':arm' => $c['section_arm'],
                ]);
                $id = $pdo->lastInsertId();
            }

            $classIds[$key] = (int)$id;
        }

        return $classIds;
    }

    private function seedSubjects(PDO $pdo): array
    {
        $subjects = [
            'MTH' => ['name' => 'Mathematics', 'code' => 'jss1_mth'],
            'ENG' => ['name' => 'English Language', 'code' => 'jss1_eng'],
            'SCI' => ['name' => 'Basic Science', 'code' => 'jss1_sci'],
            'CIV' => ['name' => 'Civic Education', 'code' => 'jss1_civ'],
        ];

        $subjectIds = [];
        $stmt = $pdo->prepare("
            INSERT INTO subjects (name, code, status)
            VALUES (:name, :code, 'active')
            ON DUPLICATE KEY UPDATE name = VALUES(name)
        ");

        foreach ($subjects as $key => $s) {
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
            'teacher_adebayo' => [
                'name' => 'Dr. Babatunde Adebayo',
                'email' => 'teacher.adebayo@claret.edu',
                'phone' => '+2348000000003',
                'role' => 'teacher',
            ],
            'teacher_okoro' => [
                'name' => 'Mrs. Ngozi Okoro',
                'email' => 'teacher.okoro@claret.edu',
                'phone' => '+2348000000004',
                'role' => 'teacher',
            ],
            'student_john' => [
                'name' => 'John Doe',
                'email' => 'student.john@claret.edu',
                'phone' => '+2348000000005',
                'role' => 'student',
            ],
            'student_mary' => [
                'name' => 'Mary Doe',
                'email' => 'student.mary@claret.edu',
                'phone' => '+2348000000006',
                'role' => 'student',
            ],
            'student_david' => [
                'name' => 'David Smith',
                'email' => 'student.david@claret.edu',
                'phone' => '+2348000000007',
                'role' => 'student',
            ],
            'parent_doe' => [
                'name' => 'Mr. Chukwuma Doe',
                'email' => 'parent.doe@claret.edu',
                'phone' => '+2348000000008',
                'role' => 'parent',
            ],
            'parent_smith' => [
                'name' => 'Mrs. Victoria Smith',
                'email' => 'parent.smith@claret.edu',
                'phone' => '+2348000000009',
                'role' => 'parent',
            ],
        ];

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
            'adebayo' => ['user_id' => $users['teacher_adebayo'], 'staff_id' => 'TCH/2026/001'],
            'okoro' => ['user_id' => $users['teacher_okoro'], 'staff_id' => 'TCH/2026/002'],
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
            }

            $teacherIds[$key] = (int)$id;
        }

        return $teacherIds;
    }

    private function seedStudents(PDO $pdo, array $users, array $classIds): array
    {
        $students = [
            'john' => [
                'user_id' => $users['student_john'],
                'admission_number' => 'STD/2026/001',
                'class_id' => $classIds['JSS 1A'],
                'gender' => 'male',
                'dob' => '2012-05-14',
            ],
            'mary' => [
                'user_id' => $users['student_mary'],
                'admission_number' => 'STD/2026/002',
                'class_id' => $classIds['JSS 1A'],
                'gender' => 'female',
                'dob' => '2013-09-22',
            ],
            'david' => [
                'user_id' => $users['student_david'],
                'admission_number' => 'STD/2026/003',
                'class_id' => $classIds['JSS 1B'],
                'gender' => 'male',
                'dob' => '2012-11-03',
            ],
        ];

        $studentIds = [];
        $stmt = $pdo->prepare("
            INSERT INTO students (user_id, admission_number, current_class_id, gender, date_of_birth)
            VALUES (:user_id, :admission_number, :class_id, :gender, :dob)
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
                ]);
                $id = $pdo->lastInsertId();
            }

            $studentIds[$key] = (int)$id;
        }

        return $studentIds;
    }

    private function seedParents(PDO $pdo, array $users): array
    {
        $parents = [
            'doe' => $users['parent_doe'],
            'smith' => $users['parent_smith'],
        ];

        $parentIds = [];
        $stmt = $pdo->prepare("
            INSERT INTO parents (user_id)
            VALUES (:user_id)
            ON DUPLICATE KEY UPDATE user_id = VALUES(user_id)
        ");

        foreach ($parents as $key => $userId) {
            $check = $pdo->prepare("SELECT id FROM parents WHERE user_id = :uid LIMIT 1");
            $check->execute([':uid' => $userId]);
            $id = $check->fetchColumn();

            if (!$id) {
                $stmt->execute([':user_id' => $userId]);
                $id = $pdo->lastInsertId();
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
        $allocations = [
            'jss1a_mth' => ['class_id' => $classIds['JSS 1A'], 'subject_id' => $subjectIds['MTH'], 'teacher_id' => $teacherIds['adebayo']],
            'jss1a_eng' => ['class_id' => $classIds['JSS 1A'], 'subject_id' => $subjectIds['ENG'], 'teacher_id' => $teacherIds['okoro']],
            'jss1a_sci' => ['class_id' => $classIds['JSS 1A'], 'subject_id' => $subjectIds['SCI'], 'teacher_id' => $teacherIds['adebayo']],
            'jss1b_mth' => ['class_id' => $classIds['JSS 1B'], 'subject_id' => $subjectIds['MTH'], 'teacher_id' => $teacherIds['adebayo']],
        ];

        $classSubjectIds = [];
        $stmt = $pdo->prepare("
            INSERT INTO class_subjects (session_id, class_id, subject_id, teacher_id, status)
            VALUES (:session_id, :class_id, :subject_id, :teacher_id, 'active')
            ON DUPLICATE KEY UPDATE teacher_id = VALUES(teacher_id)
        ");

        foreach ($allocations as $key => $a) {
            $check = $pdo->prepare("
                SELECT id FROM class_subjects
                WHERE session_id = :sid AND class_id = :cid AND subject_id = :subid
                LIMIT 1
            ");
            $check->execute([':sid' => $sessionId, ':cid' => $a['class_id'], ':subid' => $a['subject_id']]);
            $id = $check->fetchColumn();

            if (!$id) {
                $stmt->execute([
                    ':session_id' => $sessionId,
                    ':class_id' => $a['class_id'],
                    ':subject_id' => $a['subject_id'],
                    ':teacher_id' => $a['teacher_id'],
                ]);
                $id = $pdo->lastInsertId();
            }

            $classSubjectIds[$key] = (int)$id;
        }

        return $classSubjectIds;
    }

    private function seedEnrollments(PDO $pdo, int $sessionId, array $classIds, array $studentIds, array $classSubjectIds): void
    {
        // 1. Class Enrollments
        $classEnrStmt = $pdo->prepare("
            INSERT INTO class_enrollments (student_id, class_id, session_id, status)
            VALUES (:student_id, :class_id, :session_id, 'active')
            ON DUPLICATE KEY UPDATE status = 'active'
        ");

        $classEnrStmt->execute([':student_id' => $studentIds['john'], ':class_id' => $classIds['JSS 1A'], ':session_id' => $sessionId]);
        $classEnrStmt->execute([':student_id' => $studentIds['mary'], ':class_id' => $classIds['JSS 1A'], ':session_id' => $sessionId]);
        $classEnrStmt->execute([':student_id' => $studentIds['david'], ':class_id' => $classIds['JSS 1B'], ':session_id' => $sessionId]);

        // 2. Subject Enrollments
        $subEnrStmt = $pdo->prepare("
            INSERT INTO student_subject_enrollments (student_id, class_subject_id, session_id, is_elective, status)
            VALUES (:student_id, :class_subject_id, :session_id, 0, 'active')
            ON DUPLICATE KEY UPDATE status = 'active'
        ");

        // John & Mary enrolled in JSS 1A MTH, ENG, SCI
        foreach ([$studentIds['john'], $studentIds['mary']] as $stdId) {
            $subEnrStmt->execute([':student_id' => $stdId, ':class_subject_id' => $classSubjectIds['jss1a_mth'], ':session_id' => $sessionId]);
            $subEnrStmt->execute([':student_id' => $stdId, ':class_subject_id' => $classSubjectIds['jss1a_eng'], ':session_id' => $sessionId]);
            $subEnrStmt->execute([':student_id' => $stdId, ':class_subject_id' => $classSubjectIds['jss1a_sci'], ':session_id' => $sessionId]);
        }

        // David enrolled in JSS 1B MTH
        $subEnrStmt->execute([':student_id' => $studentIds['david'], ':class_subject_id' => $classSubjectIds['jss1b_mth'], ':session_id' => $sessionId]);
    }

    private function seedCoursework(PDO $pdo, int $termId, array $classSubjectIds, array $teacherIds, array $studentIds): void
    {
        // 1. Content Items
        $contentStmt = $pdo->prepare("
            INSERT INTO content_items (class_subject_id, teacher_id, topic, title, description, type, published_at)
            VALUES (:cs_id, :teacher_id, :topic, :title, :desc, :type, NOW())
        ");

        $contentStmt->execute([
            ':cs_id' => $classSubjectIds['jss1a_mth'],
            ':teacher_id' => $teacherIds['adebayo'],
            ':topic' => 'Algebra Foundations',
            ':title' => 'Introduction to Algebraic Expressions & Variables',
            ':desc' => 'Comprehensive lecture notes covering basic definitions, simplifying expressions, and substituting values.',
            ':type' => 'note',
        ]);

        $contentStmt->execute([
            ':cs_id' => $classSubjectIds['jss1a_eng'],
            ':teacher_id' => $teacherIds['okoro'],
            ':topic' => 'Grammar & Syntax',
            ':title' => 'Understanding Parts of Speech in Context',
            ':desc' => 'Detailed study guide exploring nouns, verbs, adverbs, and adjectives with sentence examples.',
            ':type' => 'note',
        ]);

        // 2. Assignment
        $asgnStmt = $pdo->prepare("
            INSERT INTO assignments (class_subject_id, term_id, teacher_id, title, instructions, max_score, due_at, status)
            VALUES (:cs_id, :term_id, :teacher_id, :title, :instructions, 20.00, DATE_ADD(NOW(), INTERVAL 7 DAY), 'published')
        ");

        $asgnStmt->execute([
            ':cs_id' => $classSubjectIds['jss1a_mth'],
            ':term_id' => $termId,
            ':teacher_id' => $teacherIds['adebayo'],
            ':title' => 'Algebraic Simplification Exercise 1',
            ':instructions' => 'Complete questions 1 through 10 from Chapter 3 and submit your step-by-step working.',
        ]);
        $assignmentId = (int)$pdo->lastInsertId();

        // 3. Assignment Submission (John Doe)
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

    private function seedCbt(PDO $pdo, int $termId, array $classSubjectIds, array $teacherIds, array $subjectIds, array $studentIds): void
    {
        // 1. Question Bank Items
        $qStmt = $pdo->prepare("
            INSERT INTO questions (subject_id, topic, question_text, type, default_points, created_by)
            VALUES (:subject_id, :topic, :question_text, :type, 5.00, :created_by)
        ");

        // Question 1: MCQ
        $qStmt->execute([
            ':subject_id' => $subjectIds['MTH'],
            ':topic' => 'Linear Equations',
            ':question_text' => 'What is the value of x if 2x + 6 = 14?',
            ':type' => 'mcq',
            ':created_by' => $teacherIds['adebayo'],
        ]);
        $q1Id = (int)$pdo->lastInsertId();

        // MCQ Options
        $optStmt = $pdo->prepare("
            INSERT INTO question_options (question_id, option_text, is_correct)
            VALUES (:qid, :text, :is_correct)
        ");
        $optStmt->execute([':qid' => $q1Id, ':text' => '2', ':is_correct' => 0]);
        $optStmt->execute([':qid' => $q1Id, ':text' => '4', ':is_correct' => 1]);
        $optStmt->execute([':qid' => $q1Id, ':text' => '6', ':is_correct' => 0]);
        $optStmt->execute([':qid' => $q1Id, ':text' => '8', ':is_correct' => 0]);

        // Question 2: Short Answer
        $qStmt->execute([
            ':subject_id' => $subjectIds['MTH'],
            ':topic' => 'Terminology',
            ':question_text' => 'What is the mathematical term for a number that multiplies a variable?',
            ':type' => 'short_answer',
            ':created_by' => $teacherIds['adebayo'],
        ]);
        $q2Id = (int)$pdo->lastInsertId();

        // 2. CBT Quiz
        $quizStmt = $pdo->prepare("
            INSERT INTO quizzes (class_subject_id, term_id, teacher_id, title, instructions, time_limit_minutes, max_attempts, is_published, published_at)
            VALUES (:cs_id, :term_id, :teacher_id, :title, :instructions, 30, 2, 1, NOW())
        ");

        $quizStmt->execute([
            ':cs_id' => $classSubjectIds['jss1a_mth'],
            ':term_id' => $termId,
            ':teacher_id' => $teacherIds['adebayo'],
            ':title' => 'JSS 1 Mathematics CBT Diagnostic Quiz',
            ':instructions' => 'Answer all questions carefully. Time limit is 30 minutes.',
        ]);
        $quizId = (int)$pdo->lastInsertId();

        // 3. Quiz Questions Attachment
        $qqStmt = $pdo->prepare("
            INSERT INTO quiz_questions (quiz_id, question_id, points, sort_order)
            VALUES (:quiz_id, :question_id, :points, :sort_order)
            ON DUPLICATE KEY UPDATE points = VALUES(points)
        ");

        $qqStmt->execute([':quiz_id' => $quizId, ':question_id' => $q1Id, ':points' => 5.00, ':sort_order' => 1]);
        $qqStmt->execute([':quiz_id' => $quizId, ':question_id' => $q2Id, ':points' => 5.00, ':sort_order' => 2]);

        // 4. Completed Attempt (Mary Doe)
        $uuid = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );

        $attStmt = $pdo->prepare("
            INSERT INTO quiz_attempts (uuid, quiz_id, student_id, attempt_number, started_at, submitted_at, score, max_score, status)
            VALUES (:uuid, :quiz_id, :student_id, 1, NOW(), NOW(), 10.00, 10.00, 'graded')
            ON DUPLICATE KEY UPDATE status = 'graded'
        ");
        $attStmt->execute([
            ':uuid' => $uuid,
            ':quiz_id' => $quizId,
            ':student_id' => $studentIds['mary'],
        ]);
    }

    private function seedAttendanceAndAnnouncements(PDO $pdo, int $sessionId, int $termId, array $classIds, array $studentIds, array $users): void
    {
        // 1. Attendance Records (Today for JSS 1A)
        $today = gmdate('Y-m-d');
        $attStmt = $pdo->prepare("
            INSERT INTO attendance_records (session_id, term_id, class_id, student_id, date, status, marked_by)
            VALUES (:session_id, :term_id, :class_id, :student_id, :date, :status, :marked_by)
            ON DUPLICATE KEY UPDATE status = VALUES(status)
        ");

        $attStmt->execute([
            ':session_id' => $sessionId,
            ':term_id' => $termId,
            ':class_id' => $classIds['JSS 1A'],
            ':student_id' => $studentIds['john'],
            ':date' => $today,
            ':status' => 'present',
            ':marked_by' => $users['teacher_adebayo'],
        ]);

        $attStmt->execute([
            ':session_id' => $sessionId,
            ':term_id' => $termId,
            ':class_id' => $classIds['JSS 1A'],
            ':student_id' => $studentIds['mary'],
            ':date' => $today,
            ':status' => 'present',
            ':marked_by' => $users['teacher_adebayo'],
        ]);

        // 2. Announcements
        $annStmt = $pdo->prepare("
            INSERT INTO announcements (author_id, scope, title, body, published_at)
            VALUES (:author_id, 'school', :title, :body, NOW())
        ");

        $annStmt->execute([
            ':author_id' => $users['admin'],
            ':title' => 'Welcome to the 2026/2027 Academic Session',
            ':body' => 'Welcome back students, parents, and staff. We look forward to a successful and productive term.',
        ]);

        $annStmt->execute([
            ':author_id' => $users['super_admin'],
            ':title' => 'Faculty Briefing on Digital Gradebook',
            ':body' => 'All instructional staff are reminded to enter Continuous Assessment scores via the new digital gradebook portal.',
        ]);
    }

    private function seedTimetables(PDO $pdo, int $termId, array $classSubjectIds): void
    {
        $slots = [
            ['cs_id' => $classSubjectIds['jss1a_mth'], 'day' => 'mon', 'start' => '08:30:00', 'end' => '09:30:00', 'room' => 'Room 101'],
            ['cs_id' => $classSubjectIds['jss1a_eng'], 'day' => 'mon', 'start' => '09:30:00', 'end' => '10:30:00', 'room' => 'Room 101'],
            ['cs_id' => $classSubjectIds['jss1a_sci'], 'day' => 'tue', 'start' => '08:30:00', 'end' => '09:30:00', 'room' => 'Science Lab A'],
            ['cs_id' => $classSubjectIds['jss1b_mth'], 'day' => 'mon', 'start' => '10:45:00', 'end' => '11:45:00', 'room' => 'Room 102'],
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
