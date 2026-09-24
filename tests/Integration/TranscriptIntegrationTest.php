<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Controllers\Admin\TranscriptController;
use App\Controllers\PublicTranscriptVerificationController;
use App\Core\AuthenticatorInterface;
use App\Core\Exceptions\AuthorizationException;
use App\Core\Request;
use App\Core\Response;
use App\Core\UserContext;
use App\Models\User;
use App\Repositories\AcademicRepository;
use App\Repositories\GradingScaleRepository;
use App\Repositories\StudentRepository;
use App\Repositories\UserRepository;
use App\Services\TranscriptService;
use PDO;
use PHPUnit\Framework\TestCase;

final class TranscriptIntegrationTest extends TestCase
{
    private PDO $pdo;
    private UserRepository $userRepo;
    private StudentRepository $studentRepo;
    private AcademicRepository $academicRepo;
    private GradingScaleRepository $gradingScaleRepo;
    private TranscriptService $transcriptService;
    private AuthenticatorInterface $adminAuthenticator;
    private AuthenticatorInterface $studentAuthenticator;

    private UserContext $adminContext;
    private UserContext $studentContext;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $this->createSchema();
        $this->seedData();

        $this->userRepo = new UserRepository($this->pdo);
        $this->studentRepo = new StudentRepository($this->pdo);
        $this->academicRepo = new AcademicRepository($this->pdo);
        $this->gradingScaleRepo = new GradingScaleRepository($this->pdo);
        $this->transcriptService = new TranscriptService($this->pdo, $this->studentRepo, $this->gradingScaleRepo);

        // Setup UserContexts
        $adminUser = $this->userRepo->findById(1);
        $this->assertNotNull($adminUser);
        $this->adminContext = UserContext::fromUser($adminUser);

        $studentUser = $this->userRepo->findById(10);
        $this->assertNotNull($studentUser);
        $this->studentContext = UserContext::fromUser($studentUser);

        // Mock Authenticators
        $this->adminAuthenticator = $this->createMock(AuthenticatorInterface::class);
        $this->adminAuthenticator->method('authenticate')->willReturn($this->adminContext);
        $this->adminAuthenticator->method('user')->willReturn($this->adminContext);
        $this->adminAuthenticator->method('getUserContext')->willReturn($this->adminContext);

        $this->studentAuthenticator = $this->createMock(AuthenticatorInterface::class);
        $this->studentAuthenticator->method('authenticate')->willReturn($this->studentContext);
        $this->studentAuthenticator->method('user')->willReturn($this->studentContext);
        $this->studentAuthenticator->method('getUserContext')->willReturn($this->studentContext);
    }

    private function createSchema(): void
    {
        $this->pdo->exec("
            CREATE TABLE roles (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name VARCHAR(50) NOT NULL UNIQUE
            );

            CREATE TABLE permissions (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name VARCHAR(100) NOT NULL UNIQUE
            );

            CREATE TABLE role_permissions (
                role_id INTEGER NOT NULL,
                permission_id INTEGER NOT NULL,
                PRIMARY KEY (role_id, permission_id)
            );

            CREATE TABLE user_roles (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                role VARCHAR(50) NOT NULL,
                is_active INTEGER NOT NULL DEFAULT 1
            );

            CREATE TABLE users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                uuid VARCHAR(36) NOT NULL DEFAULT 'uuid-1',
                name VARCHAR(150) NOT NULL,
                first_name VARCHAR(100) NULL,
                last_name VARCHAR(100) NULL,
                email VARCHAR(150) NOT NULL UNIQUE,
                phone VARCHAR(50) NULL,
                role VARCHAR(50) NOT NULL DEFAULT 'student',
                status VARCHAR(50) NOT NULL DEFAULT 'active',
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE students (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                admission_number VARCHAR(50) NOT NULL,
                admission_date DATE NULL,
                date_of_birth DATE NULL,
                gender VARCHAR(20) NULL,
                status VARCHAR(50) NOT NULL DEFAULT 'active',
                current_class_id INTEGER NULL
            );

            CREATE TABLE sessions (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name VARCHAR(100) NOT NULL,
                start_date DATE NOT NULL,
                end_date DATE NOT NULL,
                status VARCHAR(50) NOT NULL DEFAULT 'active'
            );

            CREATE TABLE terms (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                session_id INTEGER NOT NULL,
                name VARCHAR(100) NOT NULL,
                start_date DATE NOT NULL,
                end_date DATE NOT NULL,
                status VARCHAR(50) NOT NULL DEFAULT 'active'
            );

            CREATE TABLE academic_stages (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name VARCHAR(50) NOT NULL UNIQUE,
                label VARCHAR(100) NOT NULL,
                rank_order INTEGER NOT NULL DEFAULT 0
            );

            CREATE TABLE academic_levels (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name VARCHAR(100) NOT NULL,
                stage VARCHAR(50) NOT NULL,
                rank_order INTEGER NOT NULL DEFAULT 0,
                grading_scale_id INTEGER NULL
            );

            CREATE TABLE classes (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name VARCHAR(100) NOT NULL,
                section_arm VARCHAR(50) NULL,
                academic_level_id INTEGER NOT NULL,
                status VARCHAR(50) NOT NULL DEFAULT 'active'
            );

            CREATE TABLE subjects (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name VARCHAR(150) NOT NULL,
                code VARCHAR(50) NOT NULL
            );

            CREATE TABLE class_subjects (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                class_id INTEGER NOT NULL,
                subject_id INTEGER NOT NULL,
                teacher_id INTEGER NULL
            );

            CREATE TABLE term_results (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                student_id INTEGER NOT NULL,
                class_subject_id INTEGER NOT NULL,
                term_id INTEGER NOT NULL,
                computed_score DECIMAL(5,2) NOT NULL,
                grade_letter VARCHAR(5) NULL,
                remark VARCHAR(100) NULL,
                breakdown_json TEXT NOT NULL DEFAULT '{}',
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE student_term_summaries (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                student_id INTEGER NOT NULL,
                term_id INTEGER NOT NULL,
                class_id INTEGER NOT NULL,
                rank_in_class INTEGER NULL,
                attendance_present_count INTEGER NULL,
                attendance_total_count INTEGER NULL,
                class_teacher_remark TEXT NULL,
                principal_remark TEXT NULL
            );

            CREATE TABLE grading_scales (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name VARCHAR(100) NOT NULL,
                stage VARCHAR(50) NULL,
                is_default INTEGER NOT NULL DEFAULT 0
            );

            CREATE TABLE grade_boundaries (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                grading_scale_id INTEGER NOT NULL,
                letter VARCHAR(5) NOT NULL,
                min_score DECIMAL(5,2) NOT NULL,
                max_score DECIMAL(5,2) NOT NULL,
                remark VARCHAR(100) NULL
            );

            CREATE TABLE system_settings (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                setting_key VARCHAR(100) NOT NULL UNIQUE,
                setting_value TEXT NULL
            );
        ");
    }

    private function seedData(): void
    {
        $this->pdo->exec("
            INSERT INTO roles (id, name) VALUES (1, 'admin'), (2, 'student');
            INSERT INTO permissions (id, name) VALUES (1, 'manage_academic_structure');
            INSERT INTO role_permissions (role_id, permission_id) VALUES (1, 1);

            INSERT INTO users (id, name, first_name, last_name, email, role, status) VALUES
            (1, 'Administrator', 'Admin', 'User', 'admin@claret.edu', 'admin', 'active'),
            (10, 'Chukwuebuka Nnamdi', 'Chukwuebuka', 'Nnamdi', 'ebuka@claret.edu', 'student', 'active');

            INSERT INTO user_roles (user_id, role, is_active) VALUES (1, 'admin', 1), (10, 'student', 1);

            INSERT INTO system_settings (setting_key, setting_value) VALUES
            ('school_name', 'Claret International School'),
            ('school_motto', 'Excellence and Integrity');

            INSERT INTO grading_scales (id, name, stage, is_default) VALUES
            (1, 'Senior Secondary Scale', 'senior_secondary', 0);

            INSERT INTO grade_boundaries (grading_scale_id, letter, min_score, max_score, remark) VALUES
            (1, 'A1', 75.00, 100.00, 'EXCELLENT'),
            (1, 'B2', 70.00, 74.99, 'VERY GOOD'),
            (1, 'B3', 65.00, 69.99, 'GOOD'),
            (1, 'C4', 60.00, 64.99, 'CREDIT'),
            (1, 'C5', 55.00, 59.99, 'CREDIT'),
            (1, 'C6', 50.00, 54.99, 'CREDIT'),
            (1, 'D7', 45.00, 49.99, 'PASS'),
            (1, 'E8', 40.00, 44.99, 'PASS'),
            (1, 'F9', 0.00, 39.99, 'FAIL');

            INSERT INTO academic_levels (id, name, stage, rank_order, grading_scale_id) VALUES
            (1, 'SS 1', 'senior_secondary', 1, 1),
            (2, 'SS 2', 'senior_secondary', 2, 1);

            INSERT INTO sessions (id, name, start_date, end_date, status) VALUES
            (1, '2024/2025', '2024-09-01', '2025-07-20', 'archived');

            INSERT INTO classes (id, name, section_arm, academic_level_id, status) VALUES
            (1, 'SS 1 Science', 'A', 1, 'active'),
            (2, 'SS 2 Science', 'A', 2, 'active');

            INSERT INTO terms (id, session_id, name, start_date, end_date, status) VALUES
            (1, 1, 'First Term', '2024-09-01', '2024-12-15', 'archived');

            INSERT INTO subjects (id, name, code) VALUES
            (1, 'Physics', 'PHY'),
            (2, 'Chemistry', 'CHM');

            INSERT INTO class_subjects (id, class_id, subject_id) VALUES
            (1, 1, 1),
            (2, 1, 2);

            INSERT INTO students (id, user_id, admission_number, admission_date, date_of_birth, gender, status, current_class_id) VALUES
            (5, 10, 'CLT/2024/005', '2024-09-01', '2010-03-20', 'Male', 'active', 1);

            INSERT INTO term_results (student_id, term_id, class_subject_id, computed_score, grade_letter, remark, breakdown_json) VALUES
            (5, 1, 1, 78.00, 'A1', 'EXCELLENT', '{\"ca\": 30, \"exam\": 48}'),
            (5, 1, 2, 72.00, 'B2', 'VERY GOOD', '{\"ca\": 28, \"exam\": 44}');

            INSERT INTO student_term_summaries (student_id, term_id, class_id, rank_in_class, attendance_present_count, attendance_total_count, class_teacher_remark, principal_remark) VALUES
            (5, 1, 1, 1, 60, 60, 'Brilliant scientific aptitude.', 'Exceptional scholar.');
        ");
    }

    public function testAdminCanViewTranscriptIndexDirectory(): void
    {
        $controller = new TranscriptController(
            $this->adminAuthenticator,
            $this->transcriptService,
            $this->studentRepo,
            $this->academicRepo
        );

        $request = new Request([], [], ['REQUEST_METHOD' => 'GET']);
        $response = $controller->index($request);

        $this->assertEquals(200, $response->getStatusCode());
        $body = (string)$response->getBody();
        $this->assertStringContainsString('Student Transcripts', $body);
        $this->assertStringContainsString('Chukwuebuka Nnamdi', $body);
        $this->assertStringContainsString('CLT/2024/005', $body);
    }

    public function testUnauthorizedUserCannotAccessTranscriptIndex(): void
    {
        $this->expectException(AuthorizationException::class);

        $controller = new TranscriptController(
            $this->studentAuthenticator,
            $this->transcriptService,
            $this->studentRepo,
            $this->academicRepo
        );

        $request = new Request([], [], ['REQUEST_METHOD' => 'GET']);
        $controller->index($request);
    }

    public function testAdminCanViewCertifiedTranscriptDossier(): void
    {
        $controller = new TranscriptController(
            $this->adminAuthenticator,
            $this->transcriptService,
            $this->studentRepo,
            $this->academicRepo
        );

        $request = new Request([], [], ['REQUEST_METHOD' => 'GET']);
        $response = $controller->show($request, 5);

        $this->assertEquals(200, $response->getStatusCode());
        $body = (string)$response->getBody();

        // Check Student Name & Admission Number
        $this->assertStringContainsString('Chukwuebuka Nnamdi', $body);
        $this->assertStringContainsString('CLT/2024/005', $body);

        // Check Subject Results & WAEC Senior Secondary Grades (A1, B2)
        $this->assertStringContainsString('Physics', $body);
        $this->assertStringContainsString('Chemistry', $body);
        $this->assertStringContainsString('A1', $body);
        $this->assertStringContainsString('B2', $body);

        // Check Cumulative Stats: Avg = 75.0%
        $this->assertStringContainsString('75.00%', $body);
        $this->assertStringContainsString('DISTINCTION (EXCELLENT)', $body);

        // Check QR code presence (SVG element)
        $this->assertStringContainsString('<svg', $body);

        // Zero GPA compliance: No mention of GPA or CGPA on transcript
        $this->assertStringNotContainsString('CGPA', $body);
        $this->assertStringNotContainsString('Grade Point Average', $body);
    }

    public function testShowReturns404ForNonExistentStudent(): void
    {
        $controller = new TranscriptController(
            $this->adminAuthenticator,
            $this->transcriptService,
            $this->studentRepo,
            $this->academicRepo
        );

        $request = new Request([], [], ['REQUEST_METHOD' => 'GET']);
        $response = $controller->show($request, 9999);

        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testPublicTranscriptVerificationGateway(): void
    {
        // 1. Get valid reference from generated transcript
        $transcript = $this->transcriptService->getStudentTranscriptData(5);
        $validRef = $transcript['verification']['reference'];

        $verificationController = new PublicTranscriptVerificationController($this->transcriptService);

        // 2. Test valid verification
        $request = new Request([], [], ['REQUEST_METHOD' => 'GET']);
        $response = $verificationController->verify($request, $validRef);

        $this->assertEquals(200, $response->getStatusCode());
        $body = (string)$response->getBody();

        $this->assertStringContainsString('Official Record Verified', $body);
        $this->assertStringContainsString('Chukwuebuka Nnamdi', $body);
        $this->assertStringContainsString('CLT/2024/005', $body);
        $this->assertStringContainsString('75.00%', $body);
        $this->assertStringContainsString('DISTINCTION (EXCELLENT)', $body);

        // 3. Test invalid verification
        $invalidResponse = $verificationController->verify($request, 'CLT-TRN-999-XXXX');
        $this->assertEquals(200, $invalidResponse->getStatusCode());
        $invalidBody = (string)$invalidResponse->getBody();

        $this->assertStringContainsString('Record Not Authenticated', $body = $invalidBody);
        $this->assertStringContainsString('Verification Unsuccessful', $invalidBody);
    }
}
