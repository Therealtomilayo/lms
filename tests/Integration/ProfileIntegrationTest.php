<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Controllers\ProfileController;
use App\Core\AuthenticatorInterface;
use App\Core\Request;
use App\Core\Response;
use App\Core\Router;
use App\Core\Session;
use App\Core\UserContext;
use App\Middleware\AuthMiddleware;
use App\Models\AcademicSession;
use App\Models\ParentProfile;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use App\Repositories\AcademicRepository;
use App\Repositories\ParentRepository;
use App\Repositories\StudentRepository;
use App\Repositories\TeacherRepository;
use App\Repositories\UserRepository;
use PDO;
use PHPUnit\Framework\TestCase;

final class ProfileIntegrationTest extends TestCase
{
    private PDO $pdo;
    private UserRepository $userRepo;
    private StudentRepository $studentRepo;
    private TeacherRepository $teacherRepo;
    private ParentRepository $parentRepo;
    private AcademicRepository $academicRepo;

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

            CREATE TABLE `teachers` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `user_id` INTEGER NOT NULL UNIQUE,
                `staff_id` VARCHAR(50) NOT NULL UNIQUE,
                `title` VARCHAR(20) NULL,
                `created_at` DATETIME NOT NULL,
                `updated_at` DATETIME NOT NULL
            );

            CREATE TABLE `academic_levels` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `name` VARCHAR(50) NOT NULL,
                `stage` VARCHAR(20) NOT NULL DEFAULT 'junior',
                `rank_order` INTEGER NOT NULL,
                `created_at` DATETIME NOT NULL
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

            CREATE TABLE `students` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `user_id` INTEGER NOT NULL UNIQUE,
                `admission_number` VARCHAR(50) NOT NULL UNIQUE,
                `current_class_id` INTEGER NULL,
                `status` VARCHAR(20) NOT NULL DEFAULT 'active',
                `created_at` DATETIME NOT NULL,
                `updated_at` DATETIME NOT NULL
            );

            CREATE TABLE `parents` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `user_id` INTEGER NOT NULL UNIQUE,
                `address` TEXT NULL,
                `created_at` DATETIME NOT NULL,
                `updated_at` DATETIME NOT NULL
            );

            CREATE TABLE `parent_student` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `parent_id` INTEGER NOT NULL,
                `student_id` INTEGER NOT NULL,
                `relationship_type` VARCHAR(30) NOT NULL DEFAULT 'guardian',
                `is_primary` INTEGER NOT NULL DEFAULT 1,
                `created_at` DATETIME NOT NULL,
                UNIQUE(`parent_id`, `student_id`)
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

            CREATE TABLE `subjects` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `name` VARCHAR(100) NOT NULL,
                `code` VARCHAR(20) NOT NULL UNIQUE,
                `category` VARCHAR(50) NOT NULL DEFAULT 'general',
                `status` VARCHAR(20) NOT NULL DEFAULT 'active',
                `created_at` DATETIME NOT NULL
            );

            CREATE TABLE `class_subjects` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `class_id` INTEGER NOT NULL,
                `subject_id` INTEGER NOT NULL,
                `teacher_id` INTEGER NOT NULL,
                `session_id` INTEGER NOT NULL,
                `status` VARCHAR(20) NOT NULL DEFAULT 'active',
                `created_at` DATETIME NOT NULL
            );
        ");

        $this->userRepo = new UserRepository($this->pdo);
        $this->studentRepo = new StudentRepository($this->pdo);
        $this->teacherRepo = new TeacherRepository($this->pdo);
        $this->parentRepo = new ParentRepository($this->pdo);
        $this->academicRepo = new AcademicRepository($this->pdo);
    }

    private function createUser(string $name, string $email, array $roles): User
    {
        $uuid = sprintf('usr-%04x%04x', mt_rand(0, 0xffff), mt_rand(0, 0xffff));
        $stmt = $this->pdo->prepare("
            INSERT INTO `users` (`uuid`, `name`, `email`, `phone`, `password_hash`, `status`, `must_change_password`, `created_at`, `updated_at`)
            VALUES (:uuid, :name, :email, '08012345678', 'hash', 'active', 0, '2026-09-01 08:00:00', '2026-09-01 08:00:00')
        ");
        $stmt->execute([
            ':uuid' => $uuid,
            ':name' => $name,
            ':email' => $email,
        ]);
        $userId = (int)$this->pdo->lastInsertId();

        foreach ($roles as $role) {
            $stmtRole = $this->pdo->prepare("
                INSERT INTO `user_roles` (`user_id`, `role`, `is_active`, `created_at`)
                VALUES (:uid, :role, 1, '2026-09-01 08:00:00')
            ");
            $stmtRole->execute([':uid' => $userId, ':role' => $role]);
        }

        return $this->userRepo->findById($userId);
    }

    public function testGuestAccessRedirectsToLogin(): void
    {
        $mockAuth = $this->createMock(AuthenticatorInterface::class);
        $mockAuth->method('user')->willReturn(null);

        $controller = new ProfileController($mockAuth, $this->userRepo, $this->studentRepo, $this->teacherRepo, $this->parentRepo, $this->academicRepo);
        $request = new Request([], [], ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/profile']);

        $response = $controller->show($request);

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('/login', $response->getHeader('Location'));
    }

    public function testAdminProfileRendersSuccessfully(): void
    {
        $admin = $this->createUser('Admin User', 'admin@claret.edu', ['admin']);
        $mockAuth = $this->createMock(AuthenticatorInterface::class);
        $mockAuth->method('user')->willReturn(UserContext::fromUser($admin));

        $controller = new ProfileController($mockAuth, $this->userRepo, $this->studentRepo, $this->teacherRepo, $this->parentRepo, $this->academicRepo);
        $request = new Request([], [], ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/profile']);

        $response = $controller->show($request);

        $this->assertEquals(200, $response->getStatusCode());
        $body = $response->getContent();
        $this->assertStringContainsString('Admin User', $body);
        $this->assertStringContainsString('admin@claret.edu', $body);
        $this->assertStringContainsString('Administrator', $body);
        $this->assertStringContainsString('Administrative Privileges', $body);
        $this->assertStringContainsString('/profile/password', $body);
    }

    public function testTeacherProfileRendersWithTeachingAllocations(): void
    {
        $teacherUser = $this->createUser('Dr. Emeka Eze', 'emeka.eze@claret.edu', ['teacher']);
        
        // Seed teacher record
        $stmt = $this->pdo->prepare("INSERT INTO `teachers` (`user_id`, `staff_id`, `title`, `created_at`, `updated_at`) VALUES (?, 'TCH-042', 'Dr.', datetime('now'), datetime('now'))");
        $stmt->execute([$teacherUser->id]);
        $teacherId = (int)$this->pdo->lastInsertId();

        // Seed session & subject & class
        $this->pdo->exec("INSERT INTO `sessions` (`name`, `start_date`, `end_date`, `status`, `created_at`, `updated_at`) VALUES ('2026/2027', '2026-09-01', '2027-07-20', 'active', datetime('now'), datetime('now'))");
        $sessionId = (int)$this->pdo->lastInsertId();

        $this->pdo->exec("INSERT INTO `academic_levels` (`name`, `stage`, `rank_order`, `created_at`) VALUES ('JSS 1', 'junior', 1, datetime('now'))");
        $this->pdo->exec("INSERT INTO `classes` (`name`, `section_arm`, `academic_level_id`, `status`, `created_at`, `updated_at`) VALUES ('JSS 1A', 'A', 1, 'active', datetime('now'), datetime('now'))");
        $classId = (int)$this->pdo->lastInsertId();

        $this->pdo->exec("INSERT INTO `subjects` (`name`, `code`, `category`, `status`, `created_at`) VALUES ('Mathematics', 'MTH101', 'core', 'active', datetime('now'))");
        $subjectId = (int)$this->pdo->lastInsertId();

        $this->pdo->exec("INSERT INTO `class_subjects` (`class_id`, `subject_id`, `teacher_id`, `session_id`, `status`, `created_at`) VALUES ({$classId}, {$subjectId}, {$teacherId}, {$sessionId}, 'active', datetime('now'))");

        $mockAuth = $this->createMock(AuthenticatorInterface::class);
        $mockAuth->method('user')->willReturn(UserContext::fromUser($teacherUser));

        $controller = new ProfileController($mockAuth, $this->userRepo, $this->studentRepo, $this->teacherRepo, $this->parentRepo, $this->academicRepo);
        $request = new Request([], [], ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/profile']);

        $response = $controller->show($request);

        $this->assertEquals(200, $response->getStatusCode());
        $body = $response->getContent();
        $this->assertStringContainsString('Dr. Emeka Eze', $body);
        $this->assertStringContainsString('TCH-042', $body);
        $this->assertStringContainsString('Subject Teacher', $body);
        $this->assertStringContainsString('Mathematics', $body);
        $this->assertStringContainsString('MTH101', $body);
    }

    public function testStudentProfileRendersWithAdmissionAndClass(): void
    {
        $studentUser = $this->createUser('Chidi Okoro', 'chidi@claret.edu', ['student']);

        $this->pdo->exec("INSERT INTO `academic_levels` (`name`, `stage`, `rank_order`, `created_at`) VALUES ('JSS 1', 'junior', 1, datetime('now'))");
        $this->pdo->exec("INSERT INTO `classes` (`name`, `section_arm`, `academic_level_id`, `status`, `created_at`, `updated_at`) VALUES ('JSS 1B', 'B', 1, 'active', datetime('now'), datetime('now'))");
        $classId = (int)$this->pdo->lastInsertId();

        $stmt = $this->pdo->prepare("INSERT INTO `students` (`user_id`, `admission_number`, `current_class_id`, `status`, `created_at`, `updated_at`) VALUES (?, 'STD/2026/099', ?, 'active', datetime('now'), datetime('now'))");
        $stmt->execute([$studentUser->id, $classId]);

        $mockAuth = $this->createMock(AuthenticatorInterface::class);
        $mockAuth->method('user')->willReturn(UserContext::fromUser($studentUser));

        $controller = new ProfileController($mockAuth, $this->userRepo, $this->studentRepo, $this->teacherRepo, $this->parentRepo, $this->academicRepo);
        $request = new Request([], [], ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/profile']);

        $response = $controller->show($request);

        $this->assertEquals(200, $response->getStatusCode());
        $body = $response->getContent();
        $this->assertStringContainsString('Chidi Okoro', $body);
        $this->assertStringContainsString('STD/2026/099', $body);
        $this->assertStringContainsString('JSS 1B', $body);
        $this->assertStringContainsString('Student Candidate', $body);
    }

    public function testParentProfileRendersWithLinkedWards(): void
    {
        $parentUser = $this->createUser('Chief Okafor', 'okafor@claret.edu', ['parent']);
        $stmt = $this->pdo->prepare("INSERT INTO `parents` (`user_id`, `address`, `created_at`, `updated_at`) VALUES (?, '12 Okigwe Rd, Owerri', datetime('now'), datetime('now'))");
        $stmt->execute([$parentUser->id]);
        $parentId = (int)$this->pdo->lastInsertId();

        // Create ward
        $wardUser = $this->createUser('Ngozi Okafor', 'ngozi@claret.edu', ['student']);
        $stmt = $this->pdo->prepare("INSERT INTO `students` (`user_id`, `admission_number`, `status`, `created_at`, `updated_at`) VALUES (?, 'STD/2026/050', 'active', datetime('now'), datetime('now'))");
        $stmt->execute([$wardUser->id]);
        $studentId = (int)$this->pdo->lastInsertId();

        // Link parent & student
        $this->pdo->exec("INSERT INTO `parent_student` (`parent_id`, `student_id`, `relationship_type`, `is_primary`, `created_at`) VALUES ({$parentId}, {$studentId}, 'Father', 1, datetime('now'))");

        $mockAuth = $this->createMock(AuthenticatorInterface::class);
        $mockAuth->method('user')->willReturn(UserContext::fromUser($parentUser));

        $controller = new ProfileController($mockAuth, $this->userRepo, $this->studentRepo, $this->teacherRepo, $this->parentRepo, $this->academicRepo);
        $request = new Request([], [], ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/profile']);

        $response = $controller->show($request);

        $this->assertEquals(200, $response->getStatusCode());
        $body = $response->getContent();
        $this->assertStringContainsString('Chief Okafor', $body);
        $this->assertStringContainsString('Ngozi Okafor', $body);
        $this->assertStringContainsString('STD/2026/050', $body);
        $this->assertStringContainsString('Guardian / Parent', $body);
        $this->assertStringContainsString("/parent/children/{$studentId}", $body);
    }
}
