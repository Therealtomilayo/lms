<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Controllers\Admin\ReportController as AdminReportController;
use App\Controllers\Teacher\ResultOverviewController;
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
use App\Repositories\ResultPublicationRepository;
use App\Repositories\ResultSubmissionRepository;
use App\Repositories\StudentRepository;
use App\Repositories\TeacherRepository;
use App\Services\ReportCardService;
use PDO;
use PHPUnit\Framework\TestCase;

final class TeacherResultBroadsheetIntegrationTest extends TestCase
{
    private PDO $pdo;
    private TeacherRepository $teacherRepo;
    private AcademicRepository $academicRepo;
    private GradebookRepository $gradebookRepo;
    private EnrollmentRepository $enrollmentRepo;
    private StudentRepository $studentRepo;
    private ResultPublicationRepository $publicationRepo;
    private ResultSubmissionRepository $submissionRepo;

    private int $teacher1UserId;
    private int $teacher2UserId;
    private int $teacher1Id;
    private int $teacher2Id;
    private int $sessionId;
    private int $termId;
    private int $classId;
    private int $subjectId;
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
        \App\Core\Database::setConnection($this->pdo);

        $this->pdo->exec("
            CREATE TABLE `users` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `uuid` VARCHAR(36) NOT NULL UNIQUE,
                `name` VARCHAR(120) NOT NULL,
                `email` VARCHAR(150) NOT NULL UNIQUE,
                `phone` VARCHAR(30),
                `password_hash` VARCHAR(255) NOT NULL,
                `status` VARCHAR(20) NOT NULL DEFAULT 'active',
                `must_change_password` INTEGER NOT NULL DEFAULT 0,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE `user_roles` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `user_id` INTEGER NOT NULL,
                `role` VARCHAR(30) NOT NULL,
                `is_active` INTEGER NOT NULL DEFAULT 1,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                UNIQUE (`user_id`, `role`)
            );

            CREATE TABLE `teachers` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `user_id` INTEGER NOT NULL UNIQUE,
                `staff_id` VARCHAR(50) NOT NULL UNIQUE,
                `specialization` VARCHAR(100),
                `qualification` VARCHAR(100),
                `bio` TEXT,
                `status` VARCHAR(20) NOT NULL DEFAULT 'active',
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE `academic_levels` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `name` VARCHAR(50) NOT NULL,
                `stage` VARCHAR(20) NOT NULL DEFAULT 'junior',
                `key_stage` VARCHAR(20) NOT NULL DEFAULT 'jss',
                `rank_order` INTEGER NOT NULL DEFAULT 1,
                `order_index` INTEGER NOT NULL DEFAULT 0,
                `grading_scale_id` INTEGER NULL,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE `classes` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `academic_level_id` INTEGER NOT NULL,
                `name` VARCHAR(100) NOT NULL,
                `section_arm` VARCHAR(50) NULL,
                `form_teacher_id` INTEGER NULL,
                `status` VARCHAR(20) NOT NULL DEFAULT 'active',
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE `sessions` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `name` VARCHAR(50) NOT NULL UNIQUE,
                `start_date` DATE NOT NULL,
                `end_date` DATE NOT NULL,
                `status` VARCHAR(20) NOT NULL DEFAULT 'active',
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
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
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE `subjects` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `name` VARCHAR(100) NOT NULL,
                `code` VARCHAR(20) NOT NULL UNIQUE,
                `department` VARCHAR(50) NULL,
                `category` VARCHAR(50) NULL,
                `status` VARCHAR(20) NOT NULL DEFAULT 'active',
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE `class_subjects` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `class_id` INTEGER NOT NULL,
                `subject_id` INTEGER NOT NULL,
                `session_id` INTEGER NOT NULL,
                `teacher_id` INTEGER NULL,
                `status` VARCHAR(20) NOT NULL DEFAULT 'active',
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE `students` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `user_id` INTEGER NOT NULL UNIQUE,
                `admission_number` VARCHAR(50) NOT NULL UNIQUE,
                `current_class_id` INTEGER NULL,
                `date_of_birth` DATE NULL,
                `gender` VARCHAR(10) NULL,
                `status` VARCHAR(20) NOT NULL DEFAULT 'active',
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE `class_enrollments` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `student_id` INTEGER NOT NULL,
                `class_id` INTEGER NOT NULL,
                `session_id` INTEGER NOT NULL,
                `enrolled_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `status` VARCHAR(20) NOT NULL DEFAULT 'active'
            );

            CREATE TABLE `term_results` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `student_id` INTEGER NOT NULL,
                `class_subject_id` INTEGER NOT NULL,
                `term_id` INTEGER NOT NULL,
                `computed_score` DECIMAL(5,2) NOT NULL,
                `grade_letter` VARCHAR(5) NOT NULL,
                `grade_point` DECIMAL(3,2) NULL,
                `remark` VARCHAR(100) NULL,
                `breakdown_json` TEXT NOT NULL,
                `is_locked` INTEGER NOT NULL DEFAULT 0,
                `locked_at` DATETIME NULL,
                `locked_by` INTEGER NULL,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                UNIQUE (`student_id`, `class_subject_id`, `term_id`)
            );

            CREATE TABLE `student_term_summaries` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `student_id` INTEGER NOT NULL,
                `term_id` INTEGER NOT NULL,
                `class_id` INTEGER NOT NULL,
                `total_score` DECIMAL(8,2) NULL,
                `average_score` DECIMAL(5,2) NULL,
                `class_rank` INTEGER NULL,
                `rank_in_class` INTEGER NULL,
                `attendance_present` INTEGER DEFAULT 0,
                `attendance_total` INTEGER DEFAULT 0,
                `class_teacher_remark` TEXT NULL,
                `principal_remark` TEXT NULL,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                UNIQUE (`student_id`, `term_id`)
            );

            CREATE TABLE `class_result_submissions` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `class_id` INTEGER NOT NULL,
                `term_id` INTEGER NOT NULL,
                `submitted_by` INTEGER NOT NULL,
                `submitted_at` DATETIME NOT NULL,
                `status` VARCHAR(20) NOT NULL DEFAULT 'submitted',
                `notes` TEXT NULL,
                `reviewed_by` INTEGER NULL,
                `reviewed_at` DATETIME NULL,
                `created_at` DATETIME NOT NULL,
                `updated_at` DATETIME NOT NULL,
                UNIQUE (`class_id`, `term_id`)
            );

            CREATE TABLE `result_publications` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `term_id` INTEGER NOT NULL,
                `class_id` INTEGER NULL,
                `status` VARCHAR(20) NOT NULL DEFAULT 'published',
                `published_by` INTEGER NOT NULL,
                `published_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE `system_settings` (
                `setting_key` VARCHAR(100) PRIMARY KEY,
                `setting_value` TEXT NULL
            );
        ");

        $this->teacherRepo = new TeacherRepository($this->pdo);
        $this->academicRepo = new AcademicRepository($this->pdo);
        $this->gradebookRepo = new GradebookRepository($this->pdo);
        $this->enrollmentRepo = new EnrollmentRepository($this->pdo);
        $this->studentRepo = new StudentRepository($this->pdo);
        $this->publicationRepo = new ResultPublicationRepository($this->pdo);
        $this->submissionRepo = new ResultSubmissionRepository($this->pdo);

        // Seed core records
        // Teacher 1: Form Teacher for JSS 1 (B)
        $this->pdo->exec("INSERT INTO `users` (`id`, `uuid`, `name`, `email`, `password_hash`, `status`) VALUES (10, 'u-t1', 'Teacher Okoro', 'okoro@claret.edu', 'hash', 'active')");
        $this->pdo->exec("INSERT INTO `user_roles` (`user_id`, `role`) VALUES (10, 'teacher')");
        $this->pdo->exec("INSERT INTO `teachers` (`id`, `user_id`, `staff_id`) VALUES (1, 10, 'STF/OKORO')");
        $this->teacher1UserId = 10;
        $this->teacher1Id = 1;

        // Teacher 2: Subject Teacher David (not form teacher)
        $this->pdo->exec("INSERT INTO `users` (`id`, `uuid`, `name`, `email`, `password_hash`, `status`) VALUES (20, 'u-t2', 'Teacher David', 'david@claret.edu', 'hash', 'active')");
        $this->pdo->exec("INSERT INTO `user_roles` (`user_id`, `role`) VALUES (20, 'teacher')");
        $this->pdo->exec("INSERT INTO `teachers` (`id`, `user_id`, `staff_id`) VALUES (2, 20, 'STF/DAVID')");
        $this->teacher2UserId = 20;
        $this->teacher2Id = 2;

        // Session & Term
        $this->pdo->exec("INSERT INTO `sessions` (`id`, `name`, `start_date`, `end_date`, `status`) VALUES (1, '2026/2027', '2026-09-01', '2027-07-20', 'active')");
        $this->sessionId = 1;
        $this->pdo->exec("INSERT INTO `terms` (`id`, `session_id`, `name`, `start_date`, `end_date`, `status`) VALUES (1, 1, 'First Term', '2026-09-01', '2026-12-15', 'active')");
        $this->termId = 1;

        // Level & Class
        $this->pdo->exec("INSERT INTO `academic_levels` (`id`, `name`, `stage`, `key_stage`, `rank_order`, `order_index`) VALUES (1, 'Junior Secondary 1', 'junior', 'jss', 1, 1)");
        $this->pdo->exec("INSERT INTO `classes` (`id`, `academic_level_id`, `name`, `section_arm`, `form_teacher_id`) VALUES (5, 1, 'JSS 1', 'B', 1)");
        $this->classId = 5;

        // Subject & Class Subject (taught by David, but Okoro is Form Teacher)
        $this->pdo->exec("INSERT INTO `subjects` (`id`, `name`, `code`) VALUES (7, 'Basic Science', 'SCI101')");
        $this->subjectId = 7;
        $this->pdo->exec("INSERT INTO `class_subjects` (`id`, `class_id`, `subject_id`, `session_id`, `teacher_id`) VALUES (15, 5, 7, 1, 2)");
        $this->classSubjectId = 15;

        // Student
        $this->pdo->exec("INSERT INTO `users` (`id`, `uuid`, `name`, `email`, `password_hash`, `status`) VALUES (100, 'u-s1', 'Emeka Nwosu', 'emeka@claret.edu', 'hash', 'active')");
        $this->pdo->exec("INSERT INTO `user_roles` (`user_id`, `role`) VALUES (100, 'student')");
        $this->pdo->exec("INSERT INTO `students` (`id`, `user_id`, `admission_number`, `current_class_id`, `gender`) VALUES (4, 100, 'CIS/2026/004', 5, 'Male')");
        $this->studentId = 4;
        $this->pdo->exec("INSERT INTO `class_enrollments` (`student_id`, `class_id`, `session_id`) VALUES (4, 5, 1)");

        // Term Results & Summary for Student Emeka in Term 1
        $this->pdo->exec("INSERT INTO `term_results` (`student_id`, `class_subject_id`, `term_id`, `computed_score`, `grade_letter`, `breakdown_json`) VALUES (4, 15, 1, 88.5, 'A', '{}')");
        $this->pdo->exec("INSERT INTO `student_term_summaries` (`student_id`, `term_id`, `class_id`, `total_score`, `average_score`, `class_rank`) VALUES (4, 1, 5, 88.5, 88.5, 1)");
    }

    protected function tearDown(): void
    {
        \App\Core\Database::reset();
        parent::tearDown();
    }

    private function createMockAuthenticator(int $userId, string $role): AuthenticatorInterface
    {
        $mock = $this->createMock(AuthenticatorInterface::class);
        $user = User::fromArray([
            'id' => $userId,
            'name' => 'Test User',
            'email' => 'user@claret.edu',
            'roles' => [$role],
        ]);
        $context = UserContext::fromUser($user);
        $mock->method('user')->willReturn($context);
        $mock->method('getUserContext')->willReturn($context);
        return $mock;
    }

    public function testBroadsheetOverviewDisplaysSubjectScoresAndReportButton(): void
    {
        $auth = $this->createMockAuthenticator($this->teacher1UserId, 'teacher');
        $controller = new ResultOverviewController(
            $auth,
            $this->teacherRepo,
            $this->academicRepo,
            $this->gradebookRepo,
            $this->enrollmentRepo,
            $this->publicationRepo,
            $this->submissionRepo
        );

        $request = new Request([], [
            'class_id' => $this->classId,
            'term_id' => $this->termId,
            'session_id' => $this->sessionId,
        ]);

        $response = $controller->overview($request);

        $this->assertSame(200, $response->getStatusCode());
        $body = $response->getBody();

        // 1. Emeka Nwosu appears in broadsheet
        $this->assertStringContainsString('Emeka Nwosu', $body);
        $this->assertStringContainsString('CIS/2026/004', $body);

        // 2. Computed score 88.5 (rounded to 89 or 88.5) and grade 'A' are displayed
        $this->assertStringContainsString('89', $body);
        $this->assertStringContainsString('>A<', $body);

        // 3. PDF Report button is displayed with correct route
        $expectedPdfUrl = "/teacher/reports/student/{$this->studentId}/{$this->termId}.pdf";
        $this->assertStringContainsString($expectedPdfUrl, $body);
        $this->assertStringContainsString('PDF Report', $body);

        // 4. Official Print Header, sign-offs, and tabs
        $this->assertStringContainsString('Official Terminal Academic Broadsheet', $body);
        $this->assertStringContainsString('Form Teacher (Class Master):', $body);
        $this->assertStringContainsString('Principal / Vice-Principal (Academics):', $body);
        $this->assertStringContainsString('/teacher/results/skills', $body);
        $this->assertStringContainsString('@media print', $body);
    }

    public function testClassTeacherCanViewStudentReportCardPdf(): void
    {
        $auth = $this->createMockAuthenticator($this->teacher1UserId, 'teacher');
        $controller = new ResultOverviewController(
            $auth,
            $this->teacherRepo,
            $this->academicRepo,
            $this->gradebookRepo,
            $this->enrollmentRepo,
            $this->publicationRepo,
            $this->submissionRepo
        );

        $request = new Request([], ['class_id' => $this->classId]);
        $response = $controller->reportPdf($request, $this->studentId, $this->termId);

        $this->assertSame(200, $response->getStatusCode());
        $body = $response->getBody();

        // Should render official 3-page report card dossier
        $this->assertStringContainsString('Emeka Nwosu', $body);
        $this->assertStringContainsString('CIS/2026/004', $body);
        $this->assertStringContainsString('Basic Science', $body);
        $this->assertStringContainsString('Official 3-Page Dossier', $body);
    }

    public function testUnauthorizedTeacherCannotViewStudentReportCardPdf(): void
    {
        // Teacher 2 (David) is NOT the form teacher for JSS 1 (B)
        $auth = $this->createMockAuthenticator($this->teacher2UserId, 'teacher');
        $controller = new ResultOverviewController(
            $auth,
            $this->teacherRepo,
            $this->academicRepo,
            $this->gradebookRepo,
            $this->enrollmentRepo,
            $this->publicationRepo,
            $this->submissionRepo
        );

        $this->expectException(AuthorizationException::class);
        $this->expectExceptionMessage('Access denied. Only the assigned Class Teacher or School Administrator may view this report card.');

        $request = new Request();
        $controller->reportPdf($request, $this->studentId, $this->termId);
    }

    public function testAdminReportControllerAllowsClassTeacher(): void
    {
        $auth = $this->createMockAuthenticator($this->teacher1UserId, 'teacher');
        $reportCardService = new ReportCardService(
            $this->gradebookRepo,
            $this->studentRepo,
            $this->academicRepo,
            $this->teacherRepo
        );

        $adminReportController = new AdminReportController(
            $auth,
            $reportCardService,
            $this->academicRepo,
            $this->teacherRepo,
            $this->gradebookRepo
        );

        $request = new Request();
        $response = $adminReportController->pdf($request, $this->studentId, $this->termId);

        $this->assertSame(200, $response->getStatusCode());
        $body = $response->getBody();

        $this->assertStringContainsString('Emeka Nwosu', $body);
        $this->assertStringContainsString('CIS/2026/004', $body);
    }
}
