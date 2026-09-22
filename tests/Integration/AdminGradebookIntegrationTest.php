<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Controllers\Admin\GradebookController;
use App\Core\AuthenticatorInterface;
use App\Core\Exceptions\AuthorizationException;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\UserContext;
use App\Models\User;
use App\Repositories\AcademicRepository;
use App\Repositories\EnrollmentRepository;
use App\Repositories\GradebookRepository;
use App\Repositories\GradingScaleRepository;
use App\Repositories\UserRepository;
use App\Services\GradebookService;
use PDO;
use PHPUnit\Framework\TestCase;

final class AdminGradebookIntegrationTest extends TestCase
{
    private PDO $pdo;
    private UserRepository $userRepo;
    private AcademicRepository $academicRepo;
    private EnrollmentRepository $enrollmentRepo;
    private GradebookRepository $gradebookRepo;
    private GradingScaleRepository $gradingScaleRepo;
    private GradebookService $gradebookService;

    private int $adminUserId;
    private int $studentUserId;
    private int $teacherUserId;
    private int $sessionId;
    private int $termId;
    private int $classId;
    private int $subjectId;
    private int $teacherId;
    private int $classSubjectId;
    private int $studentId;

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
                `phone` VARCHAR(30) NULL,
                `password_hash` VARCHAR(255) NOT NULL,
                `status` VARCHAR(20) NOT NULL DEFAULT 'active',
                `must_change_password` INTEGER NOT NULL DEFAULT 0,
                `created_at` DATETIME NOT NULL,
                `updated_at` DATETIME NOT NULL
            );

            CREATE TABLE `user_roles` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `user_id` INTEGER NOT NULL,
                `role` VARCHAR(30) NOT NULL,
                `is_active` INTEGER NOT NULL DEFAULT 1,
                `created_at` DATETIME NOT NULL,
                UNIQUE (`user_id`, `role`)
            );

            CREATE TABLE `sessions` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `name` VARCHAR(50) NOT NULL UNIQUE,
                `start_date` DATE NOT NULL,
                `end_date` DATE NOT NULL,
                `status` VARCHAR(20) NOT NULL DEFAULT 'active',
                `created_at` DATETIME NOT NULL,
                `updated_at` DATETIME NOT NULL
            );

            CREATE TABLE `terms` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `session_id` INTEGER NOT NULL,
                `name` VARCHAR(50) NOT NULL,
                `start_date` DATE NOT NULL,
                `end_date` DATE NOT NULL,
                `status` VARCHAR(20) NOT NULL DEFAULT 'active',
                `grading_starts_at` DATETIME NULL,
                `grading_ends_at` DATETIME NULL,
                `created_at` DATETIME NOT NULL,
                `updated_at` DATETIME NOT NULL
            );

            CREATE TABLE `academic_levels` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `name` VARCHAR(50) NOT NULL,
                `stage` VARCHAR(20) NOT NULL DEFAULT 'junior',
                `rank_order` INTEGER NOT NULL,
                `grading_scale_id` INTEGER NULL,
                `created_at` DATETIME NOT NULL,
                `updated_at` DATETIME NOT NULL
            );

            CREATE TABLE `classes` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `name` VARCHAR(50) NOT NULL,
                `section_arm` VARCHAR(20) NOT NULL,
                `academic_level_id` INTEGER NOT NULL,
                `class_teacher_id` INTEGER NULL,
                `status` VARCHAR(20) NOT NULL DEFAULT 'active',
                `created_at` DATETIME NOT NULL,
                `updated_at` DATETIME NOT NULL
            );

            CREATE TABLE `subjects` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `name` VARCHAR(100) NOT NULL,
                `code` VARCHAR(20) NOT NULL UNIQUE,
                `category` VARCHAR(50) NOT NULL DEFAULT 'general',
                `status` VARCHAR(20) NOT NULL DEFAULT 'active',
                `created_at` DATETIME NOT NULL
            );

            CREATE TABLE `teachers` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `user_id` INTEGER NOT NULL UNIQUE,
                `staff_id` VARCHAR(50) NOT NULL UNIQUE,
                `title` VARCHAR(20) NULL,
                `created_at` DATETIME NOT NULL,
                `updated_at` DATETIME NOT NULL
            );

            CREATE TABLE `class_subjects` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `class_id` INTEGER NOT NULL,
                `subject_id` INTEGER NOT NULL,
                `teacher_id` INTEGER NOT NULL,
                `session_id` INTEGER NOT NULL,
                `status` VARCHAR(20) NOT NULL DEFAULT 'active',
                `created_at` DATETIME NOT NULL,
                `updated_at` DATETIME NOT NULL
            );

            CREATE TABLE `students` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `user_id` INTEGER NOT NULL UNIQUE,
                `admission_number` VARCHAR(50) NOT NULL UNIQUE,
                `current_class_id` INTEGER NULL,
                `date_of_birth` DATE NULL,
                `gender` VARCHAR(10) NULL,
                `status` VARCHAR(20) NOT NULL DEFAULT 'active',
                `created_at` DATETIME NOT NULL,
                `updated_at` DATETIME NOT NULL
            );

            CREATE TABLE `class_enrollments` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `student_id` INTEGER NOT NULL,
                `class_id` INTEGER NOT NULL,
                `session_id` INTEGER NOT NULL,
                `status` VARCHAR(20) NOT NULL DEFAULT 'active',
                `enrolled_at` DATETIME NOT NULL,
                `created_at` DATETIME NOT NULL,
                `updated_at` DATETIME NOT NULL
            );

            CREATE TABLE `student_subject_enrollments` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `student_id` INTEGER NOT NULL,
                `class_subject_id` INTEGER NOT NULL,
                `session_id` INTEGER NOT NULL,
                `status` VARCHAR(20) NOT NULL DEFAULT 'active',
                `created_at` DATETIME NOT NULL
            );

            CREATE TABLE `assessment_categories` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `session_id` INTEGER NOT NULL,
                `term_id` INTEGER NOT NULL,
                `class_subject_id` INTEGER NULL,
                `academic_level_id` INTEGER NULL,
                `name` VARCHAR(50) NOT NULL,
                `weight_percentage` REAL NOT NULL,
                `max_points` REAL NOT NULL DEFAULT 100.0
            );

            CREATE TABLE `student_assessment_scores` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `assessment_category_id` INTEGER NOT NULL,
                `student_id` INTEGER NOT NULL,
                `class_subject_id` INTEGER NOT NULL,
                `raw_score` REAL NOT NULL,
                `recorded_by` INTEGER NOT NULL,
                `recorded_at` DATETIME NOT NULL,
                UNIQUE(`assessment_category_id`, `student_id`, `class_subject_id`)
            );

            CREATE TABLE `term_results` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `student_id` INTEGER NOT NULL,
                `class_subject_id` INTEGER NOT NULL,
                `term_id` INTEGER NOT NULL,
                `computed_score` REAL NOT NULL,
                `grade_letter` VARCHAR(5) NOT NULL,
                `grade_point` REAL NULL,
                `remark` VARCHAR(100) NULL,
                `breakdown_json` TEXT NULL,
                `is_locked` INTEGER NOT NULL DEFAULT 0,
                `locked_at` DATETIME NULL,
                `locked_by` INTEGER NULL,
                UNIQUE(`student_id`, `class_subject_id`, `term_id`)
            );

            CREATE TABLE `student_term_summaries` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `student_id` INTEGER NOT NULL,
                `term_id` INTEGER NOT NULL,
                `class_id` INTEGER NOT NULL,
                `total_score` REAL NULL,
                `average_score` REAL NULL,
                `gpa` REAL NULL,
                `rank_in_class` INTEGER NULL,
                `attendance_present_count` INTEGER NOT NULL DEFAULT 0,
                `attendance_total_count` INTEGER NOT NULL DEFAULT 0,
                `class_teacher_remark` TEXT NULL,
                `principal_remark` TEXT NULL,
                `promotion_status` VARCHAR(20) NOT NULL DEFAULT 'pending',
                `is_locked` INTEGER NOT NULL DEFAULT 0,
                `locked_at` DATETIME NULL,
                `locked_by` INTEGER NULL,
                UNIQUE(`student_id`, `term_id`, `class_id`)
            );
        ");

        $this->userRepo = new UserRepository($this->pdo);
        $this->academicRepo = new AcademicRepository($this->pdo);
        $this->enrollmentRepo = new EnrollmentRepository($this->pdo);
        $this->gradebookRepo = new GradebookRepository($this->pdo);
        $this->gradingScaleRepo = new GradingScaleRepository($this->pdo);
        $this->gradebookService = new GradebookService($this->gradebookRepo, $this->gradingScaleRepo, $this->enrollmentRepo, $this->academicRepo);

        $this->seedData();
    }

    private function seedData(): void
    {
        // 1. Users
        $this->adminUserId = $this->insertUser('Principal Admin', 'admin@claret.test', ['admin']);
        $this->teacherUserId = $this->insertUser('Mr. Okon', 'teacher@claret.test', ['teacher']);
        $this->studentUserId = $this->insertUser('Amara Obi', 'student@claret.test', ['student']);

        // 2. Academic Session & Term
        $this->pdo->exec("
            INSERT INTO `sessions` (`id`, `name`, `start_date`, `end_date`, `status`, `created_at`, `updated_at`)
            VALUES (1, '2026/2027 Session', '2026-09-01', '2027-07-31', 'active', '2026-09-01', '2026-09-01');

            INSERT INTO `terms` (`id`, `session_id`, `name`, `start_date`, `end_date`, `status`, `created_at`, `updated_at`)
            VALUES (1, 1, '1st Term', '2026-09-01', '2026-12-15', 'active', '2026-09-01', '2026-09-01');

            INSERT INTO `academic_levels` (`id`, `name`, `stage`, `rank_order`, `created_at`, `updated_at`)
            VALUES (1, 'Junior Secondary 1', 'junior', 1, '2026-09-01', '2026-09-01');

            INSERT INTO `classes` (`id`, `name`, `section_arm`, `academic_level_id`, `status`, `created_at`, `updated_at`)
            VALUES (1, 'JSS 1', 'Emerald', 1, 'active', '2026-09-01', '2026-09-01');

            INSERT INTO `subjects` (`id`, `name`, `code`, `category`, `status`, `created_at`)
            VALUES (1, 'Mathematics', 'MTH', 'core', 'active', '2026-09-01');

            INSERT INTO `teachers` (`id`, `user_id`, `staff_id`, `created_at`, `updated_at`)
            VALUES (1, {$this->teacherUserId}, 'STF-001', '2026-09-01', '2026-09-01');

            INSERT INTO `class_subjects` (`id`, `class_id`, `subject_id`, `teacher_id`, `session_id`, `status`, `created_at`, `updated_at`)
            VALUES (1, 1, 1, 1, 1, 'active', '2026-09-01', '2026-09-01');

            INSERT INTO `students` (`id`, `user_id`, `admission_number`, `current_class_id`, `status`, `created_at`, `updated_at`)
            VALUES (1, {$this->studentUserId}, 'ADM-2026-001', 1, 'active', '2026-09-01', '2026-09-01');

            INSERT INTO `class_enrollments` (`student_id`, `class_id`, `session_id`, `status`, `enrolled_at`, `created_at`, `updated_at`)
            VALUES (1, 1, 1, 'active', '2026-09-01', '2026-09-01', '2026-09-01');

            INSERT INTO `assessment_categories` (`id`, `session_id`, `term_id`, `class_subject_id`, `name`, `weight_percentage`, `max_points`)
            VALUES 
                (1, 1, 1, NULL, 'CA 1', 20.0, 20.0),
                (2, 1, 1, NULL, 'CA 2', 20.0, 20.0),
                (3, 1, 1, NULL, 'Exam', 60.0, 60.0);

            INSERT INTO `student_assessment_scores` (`assessment_category_id`, `student_id`, `class_subject_id`, `raw_score`, `recorded_by`, `recorded_at`)
            VALUES 
                (1, 1, 1, 18.0, {$this->teacherUserId}, '2026-09-02'),
                (2, 1, 1, 19.0, {$this->teacherUserId}, '2026-09-03'),
                (3, 1, 1, 52.0, {$this->teacherUserId}, '2026-09-04');

            INSERT INTO `term_results` (`student_id`, `class_subject_id`, `term_id`, `computed_score`, `grade_letter`, `grade_point`, `remark`, `breakdown_json`, `is_locked`)
            VALUES (1, 1, 1, 89.0, 'A', 4.0, 'Excellent', '{}', 0);

            INSERT INTO `student_term_summaries` (`student_id`, `term_id`, `class_id`, `total_score`, `average_score`, `rank_in_class`, `promotion_status`)
            VALUES (1, 1, 1, 89.0, 89.0, 1, 'promoted');
        ");

        $this->sessionId = 1;
        $this->termId = 1;
        $this->classId = 1;
        $this->subjectId = 1;
        $this->teacherId = 1;
        $this->classSubjectId = 1;
        $this->studentId = 1;
    }

    private function insertUser(string $name, string $email, array $roles): int
    {
        $uuid = sprintf('usr-%04x%04x', mt_rand(0, 0xffff), mt_rand(0, 0xffff));
        $stmt = $this->pdo->prepare("
            INSERT INTO `users` (`uuid`, `name`, `email`, `phone`, `password_hash`, `status`, `must_change_password`, `created_at`, `updated_at`)
            VALUES (:uuid, :name, :email, '08011223344', 'hash', 'active', 0, '2026-09-01', '2026-09-01')
        ");
        $stmt->execute([':uuid' => $uuid, ':name' => $name, ':email' => $email]);
        $userId = (int)$this->pdo->lastInsertId();

        foreach ($roles as $r) {
            $stmtRole = $this->pdo->prepare("INSERT INTO `user_roles` (`user_id`, `role`, `is_active`, `created_at`) VALUES (?, ?, 1, '2026-09-01')");
            $stmtRole->execute([$userId, $r]);
        }

        return $userId;
    }

    private function makeController(?User $user): GradebookController
    {
        $mockAuth = $this->createMock(AuthenticatorInterface::class);
        $mockAuth->method('user')->willReturn($user ? UserContext::fromUser($user) : null);

        return new GradebookController(
            $mockAuth,
            $this->gradebookService,
            $this->gradebookRepo,
            $this->gradingScaleRepo,
            $this->academicRepo,
            $this->enrollmentRepo
        );
    }

    public function testGuestCannotAccessAdminGradebook(): void
    {
        $controller = $this->makeController(null);
        $request = new Request([], [], ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/admin/gradebook']);

        $this->expectException(AuthorizationException::class);
        $this->expectExceptionMessage('Authentication required.');

        $controller->index($request);
    }

    public function testNonAdminForbiddenFromAdminGradebook(): void
    {
        $studentUser = $this->userRepo->findById($this->studentUserId);
        $controller = $this->makeController($studentUser);
        $request = new Request([], [], ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/admin/gradebook']);

        $this->expectException(AuthorizationException::class);
        $this->expectExceptionMessage('Administrator access required.');

        $controller->index($request);
    }

    public function testAdminCanViewInstitutionalGradebookOverview(): void
    {
        $adminUser = $this->userRepo->findById($this->adminUserId);
        $controller = $this->makeController($adminUser);
        $request = new Request([], [], ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/admin/gradebook']);

        $response = $controller->index($request);
        $this->assertEquals(200, $response->getStatusCode());

        $html = $response->getContent();
        $this->assertStringContainsString('Institutional Gradebook', $html);
        $this->assertStringContainsString('Classes &amp; Arms Gradebook Directory', $html);
        $this->assertStringContainsString('JSS 1', $html);
        $this->assertStringContainsString('Emerald', $html);
        $this->assertStringContainsString('Inspect Gradebook', $html);
    }

    public function testAdminCanViewClassArmBroadsheet(): void
    {
        $adminUser = $this->userRepo->findById($this->adminUserId);
        $controller = $this->makeController($adminUser);
        $request = new Request(
            ['session_id' => '1', 'term_id' => '1'],
            [],
            ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/admin/gradebook/class/1']
        );

        $response = $controller->showClass($request, 1);
        $this->assertEquals(200, $response->getStatusCode());

        $html = $response->getContent();
        $this->assertStringContainsString('Class Master Broadsheet', $html);
        $this->assertStringContainsString('JSS 1', $html);
        $this->assertStringContainsString('Emerald', $html);
        $this->assertStringContainsString('Amara Obi', $html);
        $this->assertStringContainsString('ADM-2026-001', $html);
        $this->assertStringContainsString('MTH', $html);
        $this->assertStringContainsString('89', $html);
    }

    public function testAdminCanInspectIndividualSubjectSheetForClassArm(): void
    {
        $adminUser = $this->userRepo->findById($this->adminUserId);
        $controller = $this->makeController($adminUser);
        $request = new Request(
            ['session_id' => '1', 'term_id' => '1', 'subject_id' => '1'],
            [],
            ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/admin/gradebook/class/1']
        );

        $response = $controller->showClass($request, 1);
        $this->assertEquals(200, $response->getStatusCode());

        $html = $response->getContent();
        $this->assertStringContainsString('Continuous Assessment Sheet', $html);
        $this->assertStringContainsString('Mathematics', $html);
        $this->assertStringContainsString('Mr. Okon', $html);
        $this->assertStringContainsString('Amara Obi', $html);
        $this->assertStringContainsString('CA 1', $html);
        $this->assertStringContainsString('CA 2', $html);
        $this->assertStringContainsString('Exam', $html);
        $this->assertStringContainsString('89', $html); // computed score
        $this->assertStringContainsString('Lock Gradebook', $html);
    }

    public function testAdminCanLockAndUnlockGradebook(): void
    {
        $adminUser = $this->userRepo->findById($this->adminUserId);
        $controller = $this->makeController($adminUser);

        // 1. Lock Gradebook
        $lockRequest = new Request(
            [],
            ['term_id' => '1'],
            ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/admin/gradebook/1/lock']
        );
        $lockResponse = $controller->lock($lockRequest, 1);
        $this->assertEquals(302, $lockResponse->getStatusCode());
        $this->assertStringContainsString('/admin/gradebook/class/1', $lockResponse->getHeader('Location'));

        $this->assertTrue($this->gradebookRepo->isClassSubjectLocked(1, 1));

        // 2. Unlock Gradebook
        $unlockRequest = new Request(
            [],
            ['term_id' => '1'],
            ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/admin/gradebook/1/unlock']
        );
        $unlockResponse = $controller->unlock($unlockRequest, 1);
        $this->assertEquals(302, $unlockResponse->getStatusCode());
        $this->assertStringContainsString('/admin/gradebook/class/1', $unlockResponse->getHeader('Location'));

        $this->assertFalse($this->gradebookRepo->isClassSubjectLocked(1, 1));
    }

    public function testTeacherGradebookReflectsLockedState(): void
    {
        $teacherUser = $this->userRepo->findById($this->teacherUserId);
        $mockAuth = $this->createMock(AuthenticatorInterface::class);
        $mockAuth->method('user')->willReturn(UserContext::fromUser($teacherUser));

        $teacherRepo = new \App\Repositories\TeacherRepository($this->pdo);
        $teacherController = new \App\Controllers\Teacher\GradebookController(
            $mockAuth,
            $this->gradebookService,
            $this->gradebookRepo,
            $this->gradingScaleRepo,
            $this->academicRepo,
            $this->enrollmentRepo,
            $teacherRepo
        );

        // 1. When unlocked, teacher sees "Open for scoring"
        $request = new Request([], [], ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/teacher/gradebook']);
        $resp1 = $teacherController->index($request);
        $this->assertStringContainsString('Open for scoring', $resp1->getContent());

        // 2. Lock class subject
        $this->gradebookRepo->lockClassSubjectResults(1, 1, $this->adminUserId);

        // 3. Teacher now sees "Locked by Admin"
        $resp2 = $teacherController->index($request);
        $this->assertStringContainsString('Locked by Admin', $resp2->getContent());
    }
}
