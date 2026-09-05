<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Controllers\Admin\BatchRemarkController as AdminBatchRemarkController;
use App\Controllers\Admin\SkillController;
use App\Controllers\Teacher\BatchRemarkController as TeacherBatchRemarkController;
use App\Core\AuthenticatorInterface;
use App\Core\Request;
use App\Core\Session;
use App\Core\UserContext;
use App\Models\User;
use App\Repositories\AcademicRepository;
use App\Repositories\EnrollmentRepository;
use App\Repositories\GradebookRepository;
use App\Repositories\SkillRepository;
use App\Repositories\StudentRepository;
use App\Repositories\TeacherRepository;
use App\Services\ReportCardService;
use PDO;
use PHPUnit\Framework\TestCase;

final class SkillAndRemarkIntegrationTest extends TestCase
{
    private PDO $pdo;
    private SkillRepository $skillRepo;
    private GradebookRepository $gradebookRepo;
    private AcademicRepository $academicRepo;
    private EnrollmentRepository $enrollmentRepo;
    private StudentRepository $studentRepo;
    private TeacherRepository $teacherRepo;
    private ReportCardService $reportCardService;

    private int $adminUserId;
    private int $teacherUserId;
    private int $studentUserId;
    private int $sessionId;
    private int $termId;
    private int $classId;
    private int $studentId;
    private int $teacherId;

    protected function setUp(): void
    {
        Session::destroy();
        Session::start();

        $this->pdo = new PDO('sqlite::memory:', null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        $this->pdo->exec("
            CREATE TABLE `users` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `uuid` VARCHAR(36) NOT NULL UNIQUE,
                `name` VARCHAR(120) NOT NULL,
                `email` VARCHAR(150) NOT NULL UNIQUE,
                `phone` VARCHAR(20) NULL,
                `password_hash` VARCHAR(255) NOT NULL,
                `role` VARCHAR(20) NOT NULL,
                `must_change_password` INTEGER NOT NULL DEFAULT 0,
                `status` VARCHAR(20) NOT NULL DEFAULT 'active',
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE `roles` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `name` VARCHAR(50) NOT NULL UNIQUE,
                `description` VARCHAR(255) NULL
            );

            CREATE TABLE `user_roles` (
                `user_id` INTEGER NOT NULL,
                `role_id` INTEGER NOT NULL,
                PRIMARY KEY (`user_id`, `role_id`)
            );

            CREATE TABLE `sessions` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `name` VARCHAR(100) NOT NULL,
                `start_date` DATE NOT NULL,
                `end_date` DATE NOT NULL,
                `is_current` INTEGER NOT NULL DEFAULT 0,
                `status` VARCHAR(20) NOT NULL DEFAULT 'active',
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE `terms` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `session_id` INTEGER NOT NULL,
                `name` VARCHAR(50) NOT NULL,
                `term_number` INTEGER NOT NULL,
                `start_date` DATE NOT NULL,
                `end_date` DATE NOT NULL,
                `is_current` INTEGER NOT NULL DEFAULT 0,
                `status` VARCHAR(20) NOT NULL DEFAULT 'active',
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE `academic_levels` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `name` VARCHAR(50) NOT NULL,
                `code` VARCHAR(20) NOT NULL UNIQUE,
                `stage` VARCHAR(50) NULL DEFAULT 'junior_secondary',
                `rank_order` INTEGER NOT NULL DEFAULT 1,
                `grading_scale_id` INTEGER NULL,
                `description` VARCHAR(255) NULL,
                `status` VARCHAR(20) NOT NULL DEFAULT 'active',
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE `classes` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `academic_level_id` INTEGER NOT NULL,
                `name` VARCHAR(100) NOT NULL,
                `section_arm` VARCHAR(50) NULL,
                `capacity` INTEGER NOT NULL DEFAULT 30,
                `status` VARCHAR(20) NOT NULL DEFAULT 'active',
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE `students` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `user_id` INTEGER NOT NULL UNIQUE,
                `admission_number` VARCHAR(50) NOT NULL UNIQUE,
                `current_class_id` INTEGER NULL,
                `admission_date` DATE NOT NULL,
                `gender` VARCHAR(10) NOT NULL DEFAULT 'male',
                `date_of_birth` DATE NULL,
                `address` TEXT NULL,
                `status` VARCHAR(20) NOT NULL DEFAULT 'active',
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE `teachers` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `user_id` INTEGER NOT NULL UNIQUE,
                `staff_id` VARCHAR(50) NOT NULL UNIQUE,
                `qualification` VARCHAR(100) NULL,
                `employment_date` DATE NOT NULL,
                `status` VARCHAR(20) NOT NULL DEFAULT 'active',
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE `student_enrollments` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `student_id` INTEGER NOT NULL,
                `class_id` INTEGER NOT NULL,
                `term_id` INTEGER NOT NULL,
                `status` VARCHAR(20) NOT NULL DEFAULT 'enrolled',
                `enrolled_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE `subjects` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `name` VARCHAR(100) NOT NULL,
                `code` VARCHAR(20) NOT NULL UNIQUE,
                `status` VARCHAR(20) NOT NULL DEFAULT 'active',
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE `class_subjects` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `class_id` INTEGER NOT NULL,
                `subject_id` INTEGER NOT NULL,
                `teacher_id` INTEGER NULL,
                `session_id` INTEGER NOT NULL,
                `status` VARCHAR(20) NOT NULL DEFAULT 'active',
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE `student_term_summaries` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `student_id` INTEGER NOT NULL,
                `term_id` INTEGER NOT NULL,
                `class_id` INTEGER NOT NULL,
                `total_score` DECIMAL(8,2) NULL,
                `average_score` DECIMAL(5,2) NULL,
                `gpa` DECIMAL(4,2) NULL,
                `rank_in_class` INTEGER NULL,
                `attendance_present_count` INTEGER NOT NULL DEFAULT 0,
                `attendance_total_count` INTEGER NOT NULL DEFAULT 0,
                `class_teacher_remark` TEXT NULL,
                `principal_remark` TEXT NULL,
                `promotion_status` VARCHAR(20) NOT NULL DEFAULT 'pending',
                `is_locked` INTEGER NOT NULL DEFAULT 0,
                `locked_at` DATETIME NULL,
                `locked_by` INTEGER NULL,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                UNIQUE(`student_id`, `term_id`, `class_id`)
            );

            CREATE TABLE `term_results` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `student_id` INTEGER NOT NULL,
                `class_subject_id` INTEGER NOT NULL,
                `term_id` INTEGER NOT NULL,
                `computed_score` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
                `grade_letter` VARCHAR(5) NULL,
                `grade_point` DECIMAL(4,2) NULL,
                `remark` VARCHAR(255) NULL,
                `rank_in_class` INTEGER NULL,
                `is_locked` INTEGER NOT NULL DEFAULT 0,
                `locked_at` DATETIME NULL,
                `locked_by` INTEGER NULL,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE `skills` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `name` VARCHAR(100) NOT NULL,
                `category` VARCHAR(30) NOT NULL,
                `display_order` INTEGER NOT NULL DEFAULT 1,
                `status` VARCHAR(20) NOT NULL DEFAULT 'active',
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE `student_skill_ratings` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `student_id` INTEGER NOT NULL,
                `term_id` INTEGER NOT NULL,
                `skill_id` INTEGER NOT NULL,
                `rating` INTEGER NOT NULL DEFAULT 3,
                `recorded_by` INTEGER NULL,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                UNIQUE(`student_id`, `term_id`, `skill_id`)
            );

            CREATE TABLE `remark_presets` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `type` VARCHAR(30) NOT NULL DEFAULT 'teacher',
                `category` VARCHAR(50) NOT NULL DEFAULT 'general',
                `text` TEXT NOT NULL,
                `is_active` INTEGER NOT NULL DEFAULT 1,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE `system_settings` (
                `setting_key` VARCHAR(100) PRIMARY KEY,
                `setting_value` TEXT NULL,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            );
        ");

        // Seed basic fixtures
        $this->pdo->exec("
            INSERT INTO `roles` (`id`, `name`) VALUES (1, 'super_admin'), (2, 'teacher'), (3, 'student');

            INSERT INTO `users` (`id`, `uuid`, `name`, `email`, `password_hash`, `role`, `status`) VALUES
            (1, 'adm-uuid-001', 'School Admin', 'admin@claret.edu', 'hash', 'super_admin', 'active'),
            (2, 'tch-uuid-002', 'Mr. Emmanuel Form Teacher', 'emmanuel@claret.edu', 'hash', 'teacher', 'active'),
            (3, 'stu-uuid-003', 'Chinedu Okeke', 'chinedu@claret.edu', 'hash', 'student', 'active');

            INSERT INTO `user_roles` (`user_id`, `role_id`) VALUES (1, 1), (2, 2), (3, 3);

            INSERT INTO `teachers` (`id`, `user_id`, `staff_id`, `employment_date`) VALUES
            (1, 2, 'TCH-001', '2025-01-01');

            INSERT INTO `academic_levels` (`id`, `name`, `code`) VALUES
            (1, 'Junior Secondary', 'JSS');

            INSERT INTO `sessions` (`id`, `name`, `start_date`, `end_date`, `is_current`) VALUES
            (1, '2026/2027 Academic Session', '2026-09-01', '2027-07-20', 1);

            INSERT INTO `terms` (`id`, `session_id`, `name`, `term_number`, `start_date`, `end_date`, `is_current`) VALUES
            (1, 1, 'First Term', 1, '2026-09-01', '2026-12-15', 1);

            INSERT INTO `classes` (`id`, `academic_level_id`, `name`, `section_arm`) VALUES
            (1, 1, 'JSS 1', 'Gold');

            INSERT INTO `students` (`id`, `user_id`, `admission_number`, `current_class_id`, `admission_date`) VALUES
            (1, 3, 'CLT/2026/001', 1, '2026-09-01');

            INSERT INTO `student_enrollments` (`student_id`, `class_id`, `term_id`) VALUES
            (1, 1, 1);
        ");

        $this->adminUserId = 1;
        $this->teacherUserId = 2;
        $this->studentUserId = 3;
        $this->sessionId = 1;
        $this->termId = 1;
        $this->classId = 1;
        $this->studentId = 1;
        $this->teacherId = 1;

        $this->skillRepo = new SkillRepository($this->pdo);
        $this->gradebookRepo = new GradebookRepository($this->pdo);
        $this->academicRepo = new AcademicRepository($this->pdo);
        $this->enrollmentRepo = new EnrollmentRepository($this->pdo);
        $this->studentRepo = new StudentRepository($this->pdo);
        $this->teacherRepo = new TeacherRepository($this->pdo);
        $this->reportCardService = new ReportCardService(
            $this->gradebookRepo,
            $this->studentRepo,
            $this->academicRepo,
            $this->teacherRepo,
            $this->skillRepo
        );
    }

    private function createMockAuthenticator(int $userId): AuthenticatorInterface
    {
        $userRow = $this->pdo->query("SELECT * FROM `users` WHERE `id` = {$userId}")->fetch();
        $rolesStmt = $this->pdo->query("
            SELECT r.name FROM `roles` r 
            JOIN `user_roles` ur ON r.id = ur.role_id 
            WHERE ur.user_id = {$userId}
        ");
        $roles = $rolesStmt->fetchAll(PDO::FETCH_COLUMN) ?: [$userRow['role']];
        $user = User::fromArray($userRow, $roles);
        $context = UserContext::fromUser($user);

        $auth = $this->createMock(AuthenticatorInterface::class);
        $auth->method('check')->willReturn(true);
        $auth->method('user')->willReturn($context);
        $auth->method('getUserContext')->willReturn($context);

        return $auth;
    }

    public function testSkillsCatalogCRUD(): void
    {
        // 1. Create psychomotor and affective skills
        $psyId = $this->skillRepo->createSkill([
            'name' => 'Handwriting & Presentation',
            'category' => 'psychomotor',
            'display_order' => 1,
            'status' => 'active',
        ]);
        $this->assertGreaterThan(0, $psyId);

        $affId = $this->skillRepo->createSkill([
            'name' => 'Punctuality & Attendance',
            'category' => 'affective',
            'display_order' => 2,
            'status' => 'active',
        ]);
        $this->assertGreaterThan(0, $affId);

        // 2. Query skills
        $allSkills = $this->skillRepo->getAllSkills();
        $this->assertCount(2, $allSkills);

        $psyOnly = $this->skillRepo->getAllSkills('psychomotor');
        $this->assertCount(1, $psyOnly);
        $this->assertSame('Handwriting & Presentation', $psyOnly[0]->name);
        $this->assertTrue($psyOnly[0]->isPsychomotor());

        $affOnly = $this->skillRepo->getAllSkills('affective');
        $this->assertCount(1, $affOnly);
        $this->assertSame('Punctuality & Attendance', $affOnly[0]->name);
        $this->assertTrue($affOnly[0]->isAffective());

        // 3. Update skill
        $updated = $this->skillRepo->updateSkill($psyId, [
            'name' => 'Neat Handwriting & Penmanship',
            'category' => 'psychomotor',
            'display_order' => 1,
            'status' => 'active',
        ]);
        $this->assertTrue($updated);

        $reloaded = $this->skillRepo->getSkillById($psyId);
        $this->assertSame('Neat Handwriting & Penmanship', $reloaded?->name);

        // 4. Delete skill
        $deleted = $this->skillRepo->deleteSkill($affId);
        $this->assertTrue($deleted);
        $this->assertNull($this->skillRepo->getSkillById($affId));
    }

    public function testRemarkPresetsManagement(): void
    {
        // 1. Create presets for teacher and principal
        $teacherPresetId = $this->skillRepo->createRemarkPreset([
            'type' => 'teacher',
            'category' => 'excellent',
            'text' => 'An exceptional, dedicated pupil who participates actively in all coursework.',
            'is_active' => 1,
        ]);
        $this->assertGreaterThan(0, $teacherPresetId);

        $principalPresetId = $this->skillRepo->createRemarkPreset([
            'type' => 'principal',
            'category' => 'excellent',
            'text' => 'Outstanding terminal performance. Commendable discipline!',
            'is_active' => 1,
        ]);
        $this->assertGreaterThan(0, $principalPresetId);

        // 2. Filter by type
        $teacherPresets = $this->skillRepo->getRemarkPresets('teacher');
        $this->assertCount(1, $teacherPresets);
        $this->assertSame('excellent', $teacherPresets[0]->category);

        $principalPresets = $this->skillRepo->getRemarkPresets('principal');
        $this->assertCount(1, $principalPresets);

        // 3. Update preset
        $this->skillRepo->updateRemarkPreset($teacherPresetId, [
            'type' => 'teacher',
            'category' => 'good',
            'text' => 'Has made steady progress throughout the term.',
            'is_active' => 1,
        ]);

        $updatedPreset = $this->skillRepo->getRemarkPresetById($teacherPresetId);
        $this->assertSame('good', $updatedPreset?->category);
        $this->assertStringContainsString('steady progress', $updatedPreset?->text ?? '');

        // 4. Delete preset
        $this->skillRepo->deleteRemarkPreset($principalPresetId);
        $this->assertEmpty($this->skillRepo->getRemarkPresets('principal'));
    }

    public function testStudentSkillRatingsAndMatrix(): void
    {
        $skill1Id = $this->skillRepo->createSkill(['name' => 'Crafts & Projects', 'category' => 'psychomotor', 'display_order' => 1]);
        $skill2Id = $this->skillRepo->createSkill(['name' => 'Politeness', 'category' => 'affective', 'display_order' => 2]);

        // 1. Save single rating
        $saved = $this->skillRepo->saveStudentRating($this->studentId, $this->termId, $skill1Id, 5, $this->adminUserId);
        $this->assertTrue($saved);

        // 2. Batch save ratings
        $batchData = [
            $this->studentId => [
                $skill1Id => 5,
                $skill2Id => 4,
            ]
        ];
        $batchSuccess = $this->skillRepo->batchSaveRatings($this->termId, $batchData, $this->adminUserId);
        $this->assertTrue($batchSuccess);

        // 3. Retrieve student ratings joined with skill metadata
        $ratings = $this->skillRepo->getStudentRatings($this->studentId, $this->termId);
        $this->assertCount(2, $ratings);
        $ratingsBySkillName = [];
        foreach ($ratings as $r) {
            $ratingsBySkillName[$r->skill?->name] = $r->rating;
        }
        $this->assertSame(5, $ratingsBySkillName['Crafts & Projects']);
        $this->assertSame(4, $ratingsBySkillName['Politeness']);

        // 4. Retrieve class matrix
        $matrix = $this->skillRepo->getClassRatingsMatrix($this->classId, $this->termId);
        $this->assertArrayHasKey($this->studentId, $matrix);
        $this->assertSame(5, $matrix[$this->studentId][$skill1Id]);
        $this->assertSame(4, $matrix[$this->studentId][$skill2Id]);
    }

    public function testBatchUpdateRemarksInGradebook(): void
    {
        // 1. Batch update remarks for student
        $remarks = [
            $this->studentId => [
                'teacher_remark' => 'A consistently brilliant scholar.',
                'principal_remark' => 'Promoted to JSS 2 with distinction.',
            ]
        ];
        $this->gradebookRepo->batchUpdateRemarks($this->termId, $this->classId, $remarks);

        // Verify summary was created and remarks are present
        $summary = $this->gradebookRepo->findStudentTermSummary($this->studentId, $this->termId);
        $this->assertNotNull($summary);
        $this->assertSame('A consistently brilliant scholar.', $summary->classTeacherRemark);
        $this->assertSame('Promoted to JSS 2 with distinction.', $summary->principalRemark);

        // 2. Partial update from teacher only (leaves principal remark intact)
        $teacherOnly = [
            $this->studentId => [
                'teacher_remark' => 'Updated teacher comment: Outstanding conduct.',
            ]
        ];
        $this->gradebookRepo->batchUpdateRemarks($this->termId, $this->classId, $teacherOnly);

        $reloaded = $this->gradebookRepo->findStudentTermSummary($this->studentId, $this->termId);
        $this->assertSame('Updated teacher comment: Outstanding conduct.', $reloaded?->classTeacherRemark);
        $this->assertSame('Promoted to JSS 2 with distinction.', $reloaded?->principalRemark);
    }

    public function testReportCardServiceIntegratesDynamicRatingsAndCustomRemarks(): void
    {
        // 1. Setup skills & ratings
        $psyId = $this->skillRepo->createSkill(['name' => 'Sports & PE', 'category' => 'psychomotor']);
        $affId = $this->skillRepo->createSkill(['name' => 'Honesty & Integrity', 'category' => 'affective']);
        $this->skillRepo->saveStudentRating($this->studentId, $this->termId, $psyId, 5);
        $this->skillRepo->saveStudentRating($this->studentId, $this->termId, $affId, 4);

        // 2. Setup custom remarks
        $this->gradebookRepo->batchUpdateRemarks($this->termId, $this->classId, [
            $this->studentId => [
                'teacher_remark' => 'Chinedu has maintained top rank in every academic evaluation.',
                'principal_remark' => 'An exemplary ambassador for Claret Academy.',
            ]
        ]);

        // 3. Generate report card data
        $reportData = $this->reportCardService->getReportCardData($this->studentId, $this->termId);

        // Verify structure
        $this->assertArrayHasKey('psychomotor_ratings', $reportData);
        $this->assertArrayHasKey('affective_ratings', $reportData);
        $this->assertNotEmpty($reportData['psychomotor_ratings']);
        $this->assertNotEmpty($reportData['affective_ratings']);

        $this->assertSame('Sports & PE', $reportData['psychomotor_ratings'][0]['name']);
        $this->assertSame(5, $reportData['psychomotor_ratings'][0]['rating']);

        $this->assertSame('Honesty & Integrity', $reportData['affective_ratings'][0]['name']);
        $this->assertSame(4, $reportData['affective_ratings'][0]['rating']);

        $this->assertSame('Chinedu has maintained top rank in every academic evaluation.', $reportData['summary']->classTeacherRemark);
        $this->assertSame('An exemplary ambassador for Claret Academy.', $reportData['summary']->principalRemark);
    }

    public function testAdminSkillControllerEndpoints(): void
    {
        $auth = $this->createMockAuthenticator($this->adminUserId);
        $controller = new SkillController($auth, $this->skillRepo);

        // 1. GET /admin/skills
        $req = new Request([], [], ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/admin/skills']);
        $res = $controller->index($req);
        $this->assertSame(200, $res->getStatusCode());

        // 2. POST /admin/skills (create skill)
        $storeReq = new Request([], [
            'name' => 'Creative Robotics & Coding',
            'category' => 'psychomotor',
            'display_order' => '3',
            'status' => 'active',
        ], ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/admin/skills']);
        $storeRes = $controller->storeSkill($storeReq);
        $this->assertSame(302, $storeRes->getStatusCode());
        $this->assertStringContainsString('/admin/skills', $storeRes->getHeader('Location'));

        $skills = $this->skillRepo->getAllSkills('psychomotor');
        $this->assertCount(1, $skills);
        $this->assertSame('Creative Robotics & Coding', $skills[0]->name);
    }

    public function testAdminBatchRemarkControllerEndpoints(): void
    {
        $auth = $this->createMockAuthenticator($this->adminUserId);
        $controller = new AdminBatchRemarkController(
            $auth,
            $this->gradebookRepo,
            $this->skillRepo,
            $this->academicRepo,
            $this->studentRepo,
            $this->enrollmentRepo
        );

        // 1. GET /admin/results/comments
        $getReq = new Request([
            'session_id' => (string)$this->sessionId,
            'term_id' => (string)$this->termId,
            'class_id' => (string)$this->classId,
        ], [], ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/admin/results/comments']);
        $getRes = $controller->index($getReq);
        $this->assertSame(200, $getRes->getStatusCode());

        // 2. POST /admin/results/comments (save comments & ratings)
        $postReq = new Request([], [
            'session_id' => (string)$this->sessionId,
            'term_id' => (string)$this->termId,
            'class_id' => (string)$this->classId,
            'comments' => [
                $this->studentId => [
                    'teacher_remark' => 'Superb critical thinking and leadership skills.',
                    'principal_remark' => 'Keep up the magnificent effort!',
                ]
            ],
        ], ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/admin/results/comments']);
        $postRes = $controller->save($postReq);
        $this->assertSame(302, $postRes->getStatusCode());

        $summary = $this->gradebookRepo->findStudentTermSummary($this->studentId, $this->termId);
        $this->assertSame('Superb critical thinking and leadership skills.', $summary?->classTeacherRemark);
        $this->assertSame('Keep up the magnificent effort!', $summary?->principalRemark);
    }

    public function testTeacherBatchRemarkControllerEndpoints(): void
    {
        $auth = $this->createMockAuthenticator($this->teacherUserId);
        $controller = new TeacherBatchRemarkController(
            $auth,
            $this->teacherRepo,
            $this->gradebookRepo,
            $this->skillRepo,
            $this->academicRepo,
            $this->studentRepo,
            $this->enrollmentRepo
        );

        // 1. GET /teacher/results/comments
        $getReq = new Request([
            'session_id' => (string)$this->sessionId,
            'term_id' => (string)$this->termId,
            'class_id' => (string)$this->classId,
        ], [], ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/teacher/results/comments']);
        $getRes = $controller->index($getReq);
        $this->assertSame(200, $getRes->getStatusCode());

        // 2. POST /teacher/results/comments
        $postReq = new Request([], [
            'session_id' => (string)$this->sessionId,
            'term_id' => (string)$this->termId,
            'class_id' => (string)$this->classId,
            'comments' => [
                $this->studentId => [
                    'teacher_remark' => 'Excellent participation during group sessions.',
                ]
            ],
        ], ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/teacher/results/comments']);
        $postRes = $controller->save($postReq);
        $this->assertSame(302, $postRes->getStatusCode());

        $summary = $this->gradebookRepo->findStudentTermSummary($this->studentId, $this->termId);
        $this->assertSame('Excellent participation during group sessions.', $summary?->classTeacherRemark);
    }
}
