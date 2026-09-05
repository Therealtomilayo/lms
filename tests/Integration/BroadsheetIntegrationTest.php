<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Controllers\Admin\ResultReviewController;
use App\Core\Exceptions\AuthorizationException;
use App\Core\Request;
use App\Core\Session;
use App\Core\UserContext;
use App\Models\User;
use App\Repositories\AcademicRepository;
use App\Repositories\EnrollmentRepository;
use App\Repositories\GradebookRepository;
use App\Repositories\ResultPublicationRepository;
use App\Services\GradebookService;
use PDO;
use PHPUnit\Framework\TestCase;

final class BroadsheetIntegrationTest extends TestCase
{
    private PDO $pdo;
    private GradebookRepository $gradebookRepo;
    private AcademicRepository $academicRepo;
    private EnrollmentRepository $enrollmentRepo;
    private ResultPublicationRepository $publicationRepo;
    private ResultReviewController $controller;

    private int $adminUserId;
    private int $teacherUserId;
    private int $teacherId;
    private int $sessionId;
    private int $termId;
    private int $classId;
    private int $studentId;
    private int $subjectId;
    private int $classSubjectId;

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

            CREATE TABLE `academic_levels` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `name` VARCHAR(100) NOT NULL,
                `stage` VARCHAR(50) NOT NULL,
                `rank_order` INTEGER NOT NULL DEFAULT 1,
                `grading_scale_id` INTEGER NULL,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE `classes` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `academic_level_id` INTEGER NOT NULL,
                `name` VARCHAR(100) NOT NULL,
                `section_arm` VARCHAR(20) NULL,
                `form_teacher_id` INTEGER NULL,
                `status` VARCHAR(20) NOT NULL DEFAULT 'active',
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE `sessions` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `name` VARCHAR(100) NOT NULL UNIQUE,
                `start_date` DATE NOT NULL,
                `end_date` DATE NOT NULL,
                `status` VARCHAR(20) NOT NULL DEFAULT 'planning',
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE `terms` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `session_id` INTEGER NOT NULL,
                `name` VARCHAR(100) NOT NULL,
                `start_date` DATE NOT NULL,
                `end_date` DATE NOT NULL,
                `grading_starts_at` DATETIME NULL,
                `grading_ends_at` DATETIME NULL,
                `status` VARCHAR(20) NOT NULL DEFAULT 'active',
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

            CREATE TABLE `teachers` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `user_id` INTEGER NOT NULL,
                `staff_id` VARCHAR(50) NOT NULL UNIQUE,
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

            CREATE TABLE `students` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `user_id` INTEGER NOT NULL,
                `admission_number` VARCHAR(50) NOT NULL UNIQUE,
                `current_class_id` INTEGER NOT NULL,
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
                `status` VARCHAR(20) NOT NULL DEFAULT 'enrolled',
                `enrolled_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE `term_results` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `student_id` INTEGER NOT NULL,
                `class_subject_id` INTEGER NOT NULL,
                `term_id` INTEGER NOT NULL,
                `session_id` INTEGER NOT NULL,
                `ca_total` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
                `exam_score` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
                `total_score` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
                `computed_score` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
                `grade_letter` VARCHAR(5) NOT NULL DEFAULT 'F',
                `subject_rank` INTEGER NULL,
                `class_min_score` DECIMAL(5,2) NULL,
                `class_max_score` DECIMAL(5,2) NULL,
                `class_avg_score` DECIMAL(5,2) NULL,
                `teacher_comment` TEXT NULL,
                `is_locked` INTEGER NOT NULL DEFAULT 0,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                UNIQUE (`student_id`, `class_subject_id`, `term_id`)
            );

            CREATE TABLE `student_term_summaries` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `student_id` INTEGER NOT NULL,
                `term_id` INTEGER NOT NULL,
                `class_id` INTEGER NOT NULL,
                `total_score` DECIMAL(8,2) NULL DEFAULT NULL,
                `average_score` DECIMAL(5,2) NULL DEFAULT NULL,
                `gpa` DECIMAL(3,2) NULL DEFAULT NULL,
                `rank_in_class` INTEGER NULL DEFAULT NULL,
                `attendance_present_count` INTEGER NOT NULL DEFAULT 0,
                `attendance_total_count` INTEGER NOT NULL DEFAULT 0,
                `class_teacher_remark` TEXT NULL DEFAULT NULL,
                `principal_remark` TEXT NULL DEFAULT NULL,
                `promotion_status` VARCHAR(20) NOT NULL DEFAULT 'pending',
                `is_locked` INTEGER NOT NULL DEFAULT 0,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                UNIQUE (`student_id`, `term_id`)
            );

            CREATE TABLE `result_publications` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `term_id` INTEGER NOT NULL,
                `class_id` INTEGER NULL DEFAULT NULL,
                `published_by` INTEGER NOT NULL,
                `published_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `unpublished_at` DATETIME NULL DEFAULT NULL,
                `status` VARCHAR(20) NOT NULL DEFAULT 'published',
                `reason` TEXT NULL DEFAULT NULL,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            );
        ");

        $now = date('Y-m-d H:i:s');

        // 1. Create Users
        $this->pdo->exec("
            INSERT INTO `users` (`name`, `email`, `password_hash`, `role`, `status`, `uuid`) VALUES
            ('System Administrator', 'admin@claret.edu', 'hash', 'admin', 'active', 'u-admin-1'),
            ('Mr. David Teacher', 'teacher@claret.edu', 'hash', 'teacher', 'active', 'u-teacher-1'),
            ('Chukwudi Okafor', 'chukwudi@claret.edu', 'hash', 'student', 'active', 'u-student-1');
        ");
        $this->adminUserId = 1;
        $this->teacherUserId = 2;
        $studentUserId = 3;

        // Roles
        $this->pdo->exec("
            INSERT INTO `roles` (`id`, `name`) VALUES (1, 'admin'), (2, 'teacher'), (3, 'student');
            INSERT INTO `user_roles` (`user_id`, `role_id`) VALUES (1, 1), (2, 2), (3, 3);
        ");

        // Teacher
        $this->pdo->exec("INSERT INTO `teachers` (`user_id`, `staff_id`) VALUES (2, 'CIS-T-001');");
        $this->teacherId = (int)$this->pdo->lastInsertId();

        // Academic Structure
        $this->pdo->exec("INSERT INTO `academic_levels` (`name`, `stage`, `rank_order`) VALUES ('JSS 1', 'Junior Secondary', 1);");
        $levelId = (int)$this->pdo->lastInsertId();

        $this->pdo->exec("INSERT INTO `classes` (`academic_level_id`, `name`, `section_arm`, `form_teacher_id`) VALUES ({$levelId}, 'JSS 1A', 'A', {$this->teacherId});");
        $this->classId = (int)$this->pdo->lastInsertId();

        $this->pdo->exec("INSERT INTO `sessions` (`name`, `start_date`, `end_date`, `status`) VALUES ('2026/2027', '2026-09-01', '2027-07-31', 'active');");
        $this->sessionId = (int)$this->pdo->lastInsertId();

        $this->pdo->exec("INSERT INTO `terms` (`session_id`, `name`, `start_date`, `end_date`, `status`) VALUES ({$this->sessionId}, 'First Term', '2026-09-01', '2026-12-18', 'active');");
        $this->termId = (int)$this->pdo->lastInsertId();

        $this->pdo->exec("INSERT INTO `subjects` (`name`, `code`) VALUES ('Mathematics', 'MTH101');");
        $this->subjectId = (int)$this->pdo->lastInsertId();

        $this->pdo->exec("INSERT INTO `class_subjects` (`class_id`, `subject_id`, `teacher_id`, `session_id`) VALUES ({$this->classId}, {$this->subjectId}, {$this->teacherId}, {$this->sessionId});");
        $this->classSubjectId = (int)$this->pdo->lastInsertId();

        // Student & Enrollment
        $this->pdo->exec("INSERT INTO `students` (`user_id`, `admission_number`, `current_class_id`, `gender`) VALUES ({$studentUserId}, 'CIS-2026-001', {$this->classId}, 'male');");
        $this->studentId = (int)$this->pdo->lastInsertId();

        $this->pdo->exec("INSERT INTO `class_enrollments` (`student_id`, `class_id`, `session_id`, `status`) VALUES ({$this->studentId}, {$this->classId}, {$this->sessionId}, 'active');");

        // Term Results & Summary
        $this->pdo->exec("
            INSERT INTO `term_results` 
            (`student_id`, `class_subject_id`, `term_id`, `session_id`, `ca_total`, `exam_score`, `total_score`, `computed_score`, `grade_letter`, `subject_rank`, `teacher_comment`) 
            VALUES ({$this->studentId}, {$this->classSubjectId}, {$this->termId}, {$this->sessionId}, 35.0, 50.0, 85.0, 85.0, 'A', 1, 'Outstanding comprehension');

            INSERT INTO `student_term_summaries`
            (`student_id`, `term_id`, `class_id`, `total_score`, `average_score`, `gpa`, `rank_in_class`, `class_teacher_remark`, `principal_remark`)
            VALUES ({$this->studentId}, {$this->termId}, {$this->classId}, 85.0, 85.0, 4.0, 1, 'Diligent and focused student.', 'Keep up the stellar standard.');
        ");

        // Instantiate dependencies
        $this->academicRepo = new AcademicRepository($this->pdo);
        $this->gradebookRepo = new GradebookRepository($this->pdo);
        $this->enrollmentRepo = new EnrollmentRepository($this->pdo);
        $this->publicationRepo = new ResultPublicationRepository($this->pdo);

        $this->controller = new ResultReviewController(
            null,
            new GradebookService($this->gradebookRepo),
            $this->gradebookRepo,
            $this->publicationRepo,
            $this->academicRepo,
            $this->enrollmentRepo
        );
    }

    protected function tearDown(): void
    {
        Session::destroy();
    }

    private function createAdminRequest(string $method, string $uri, array $query = []): Request
    {
        $adminUser = new User(
            id: $this->adminUserId,
            uuid: 'u-admin-1',
            name: 'System Administrator',
            email: 'admin@claret.edu',
            status: 'active',
            roles: ['admin']
        );

        $request = new Request($query, [], ['REQUEST_METHOD' => $method, 'REQUEST_URI' => $uri]);
        $request->setAttribute('_user_context', UserContext::fromUser($adminUser, ['admin']));

        return $request;
    }

    public function testAdminCanViewClassBroadsheetMatrix(): void
    {
        $request = $this->createAdminRequest('GET', '/admin/results/broadsheet', [
            'term_id' => $this->termId,
            'class_id' => $this->classId,
        ]);

        $response = $this->controller->broadsheet($request);

        $this->assertSame(200, $response->getStatusCode());
        $body = $response->getContent();

        // Verify key layout and broadsheet elements
        $this->assertStringContainsString('Class Broadsheet Matrix', $body);
        $this->assertStringContainsString('JSS 1A', $body);
        $this->assertStringContainsString('Arm A', $body);
        $this->assertStringContainsString('Mr. David Teacher', $body); // Form teacher name
        $this->assertStringContainsString('MTH101', $body); // Subject code in header
        $this->assertStringContainsString('Chukwudi Okafor', $body); // Student candidate
        $this->assertStringContainsString('CIS-2026-001', $body); // Admission no
        $this->assertStringContainsString('85', $body); // Total score
        $this->assertStringContainsString('Export CSV', $body);
        $this->assertStringContainsString('Print Broadsheet', $body);
    }

    public function testAdminCanExportBroadsheetCsv(): void
    {
        $request = $this->createAdminRequest('GET', '/admin/results/broadsheet/export', [
            'term_id' => $this->termId,
            'class_id' => $this->classId,
        ]);

        $response = $this->controller->exportBroadsheet($request);

        $this->assertSame(200, $response->getStatusCode());
        $headers = $response->getHeaders();

        $this->assertStringContainsString('text/csv', $headers['Content-Type'] ?? '');
        $this->assertStringContainsString('attachment; filename="Broadsheet_JSS_1A', $headers['Content-Disposition'] ?? '');

        $csv = $response->getContent();
        $this->assertStringContainsString('CLARET INTERNATIONAL SCHOOL', $csv);
        $this->assertStringContainsString('OFFICIAL TERMINAL BROADSHEET MATRIX', $csv);
        $this->assertStringContainsString('Chukwudi Okafor', $csv);
        $this->assertStringContainsString('CIS-2026-001', $csv);
        $this->assertStringContainsString('MTH101 Total', $csv);
        $this->assertStringContainsString('85.0', $csv);
        $this->assertStringContainsString('Diligent and focused student.', $csv);
    }

    public function testNonAdminIsForbiddenFromBroadsheet(): void
    {
        $this->expectException(AuthorizationException::class);

        $teacherUser = new User(
            id: $this->teacherUserId,
            uuid: 'u-teacher-1',
            name: 'Mr. David Teacher',
            email: 'teacher@claret.edu',
            status: 'active',
            roles: ['teacher']
        );

        $request = new Request([], [], ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/admin/results/broadsheet']);
        $request->setAttribute('_user_context', UserContext::fromUser($teacherUser, ['teacher']));

        $this->controller->broadsheet($request);
    }
}
