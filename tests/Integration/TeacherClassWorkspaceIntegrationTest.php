<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Controllers\Teacher\ClassController;
use App\Core\AuthenticatorInterface;
use App\Core\Exceptions\AuthorizationException;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\UserContext;
use App\Models\User;
use App\Repositories\AcademicRepository;
use App\Repositories\EnrollmentRepository;
use App\Repositories\ParentRepository;
use App\Repositories\TeacherRepository;
use App\Repositories\UserRepository;
use PDO;
use PHPUnit\Framework\TestCase;

final class TeacherClassWorkspaceIntegrationTest extends TestCase
{
    private PDO $pdo;
    private UserRepository $userRepo;
    private AcademicRepository $academicRepo;
    private EnrollmentRepository $enrollmentRepo;
    private TeacherRepository $teacherRepo;
    private ParentRepository $parentRepo;

    private int $adminUserId;
    private int $teacherUserId;
    private int $teacher2UserId;
    private int $studentUserId;
    private int $parentUserId;
    private int $sessionId;
    private int $termId;
    private int $classId;
    private int $subjectId;
    private int $teacherId;
    private int $teacher2Id;
    private int $classSubjectId;
    private int $classSubject2Id;
    private int $studentId;
    private int $parentId;

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

            CREATE TABLE `academic_levels` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `name` VARCHAR(50) NOT NULL,
                `stage` VARCHAR(20) NOT NULL DEFAULT 'junior',
                `rank_order` INTEGER NOT NULL DEFAULT 1,
                `grading_scale_id` INTEGER NULL,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE `classes` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `name` VARCHAR(50) NOT NULL,
                `section_arm` VARCHAR(20) NOT NULL,
                `academic_level_id` INTEGER NOT NULL,
                `class_teacher_id` INTEGER NULL,
                `status` VARCHAR(20) NOT NULL DEFAULT 'active',
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE `subjects` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `name` VARCHAR(100) NOT NULL,
                `code` VARCHAR(20) NOT NULL UNIQUE,
                `category` VARCHAR(50) NOT NULL DEFAULT 'general',
                `status` VARCHAR(20) NOT NULL DEFAULT 'active',
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE `teachers` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `user_id` INTEGER NOT NULL UNIQUE,
                `staff_id` VARCHAR(50) NOT NULL UNIQUE,
                `title` VARCHAR(20) NULL,
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
                `user_id` INTEGER NOT NULL UNIQUE,
                `admission_number` VARCHAR(50) NOT NULL UNIQUE,
                `current_class_id` INTEGER NULL,
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
                `status` VARCHAR(20) NOT NULL DEFAULT 'active',
                `enrolled_at` DATE NOT NULL DEFAULT CURRENT_DATE,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE `student_subject_enrollments` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `student_id` INTEGER NOT NULL,
                `class_subject_id` INTEGER NOT NULL,
                `status` VARCHAR(20) NOT NULL DEFAULT 'active',
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE `parents` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `user_id` INTEGER NOT NULL UNIQUE,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE `parent_student` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `parent_id` INTEGER NOT NULL,
                `student_id` INTEGER NOT NULL,
                `relationship_type` VARCHAR(50) DEFAULT 'father',
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            );
        ");

        $this->userRepo = new UserRepository($this->pdo);
        $this->academicRepo = new AcademicRepository($this->pdo);
        $this->enrollmentRepo = new EnrollmentRepository($this->pdo);
        $this->teacherRepo = new TeacherRepository($this->pdo);
        $this->parentRepo = new ParentRepository($this->pdo);

        $this->seedFixtureData();
    }

    private function seedFixtureData(): void
    {
        // Users
        $stmt = $this->pdo->prepare("
            INSERT INTO `users` (`uuid`, `name`, `email`, `phone`, `password_hash`, `status`) 
            VALUES (:uuid, :name, :email, :phone, 'hashed_pw', 'active')
        ");

        $stmt->execute([':uuid' => 'u-admin-1', ':name' => 'Admin User', ':email' => 'admin@claret.edu', ':phone' => '08011111111']);
        $this->adminUserId = (int)$this->pdo->lastInsertId();

        $stmt->execute([':uuid' => 'u-teacher-1', ':name' => 'Mr. Anthony Ade', ':email' => 'ade@claret.edu', ':phone' => '08022222222']);
        $this->teacherUserId = (int)$this->pdo->lastInsertId();

        $stmt->execute([':uuid' => 'u-teacher-2', ':name' => 'Mrs. Stella Obi', ':email' => 'obi@claret.edu', ':phone' => '08033333333']);
        $this->teacher2UserId = (int)$this->pdo->lastInsertId();

        $stmt->execute([':uuid' => 'u-student-1', ':name' => 'Chidi Okafor', ':email' => 'chidi@student.edu', ':phone' => '08044444444']);
        $this->studentUserId = (int)$this->pdo->lastInsertId();

        $stmt->execute([':uuid' => 'u-parent-1', ':name' => 'Dr. Emeka Okafor', ':email' => 'emeka@guardian.com', ':phone' => '08055555555']);
        $this->parentUserId = (int)$this->pdo->lastInsertId();

        // User Roles
        $this->pdo->exec("
            INSERT INTO `user_roles` (`user_id`, `role`) VALUES
            ({$this->adminUserId}, 'super_admin'),
            ({$this->teacherUserId}, 'teacher'),
            ({$this->teacher2UserId}, 'teacher'),
            ({$this->studentUserId}, 'student'),
            ({$this->parentUserId}, 'parent');
        ");

        // Academic Structure
        $this->pdo->exec("
            INSERT INTO `sessions` (`name`, `start_date`, `end_date`, `status`)
            VALUES ('2025/2026 Academic Session', '2025-09-01', '2026-07-31', 'active');
        ");
        $this->sessionId = (int)$this->pdo->lastInsertId();

        $this->pdo->exec("
            INSERT INTO `terms` (`session_id`, `name`, `start_date`, `end_date`, `status`)
            VALUES ({$this->sessionId}, 'First Term', '2025-09-01', '2025-12-15', 'active');
        ");
        $this->termId = (int)$this->pdo->lastInsertId();

        $this->pdo->exec("
            INSERT INTO `academic_levels` (`name`, `stage`, `rank_order`) VALUES ('Junior Secondary 1', 'junior', 1);
        ");
        $levelId = (int)$this->pdo->lastInsertId();

        $this->pdo->exec("
            INSERT INTO `classes` (`academic_level_id`, `name`, `section_arm`) 
            VALUES ({$levelId}, 'JSS 1 Emerald', 'Emerald');
        ");
        $this->classId = (int)$this->pdo->lastInsertId();

        $this->pdo->exec("
            INSERT INTO `subjects` (`name`, `code`) 
            VALUES ('Basic Mathematics', 'MTH101');
        ");
        $this->subjectId = (int)$this->pdo->lastInsertId();

        // Teachers
        $this->pdo->exec("
            INSERT INTO `teachers` (`user_id`, `staff_id`, `title`)
            VALUES ({$this->teacherUserId}, 'TCH-001', 'Mr.');
        ");
        $this->teacherId = (int)$this->pdo->lastInsertId();

        $this->pdo->exec("
            INSERT INTO `teachers` (`user_id`, `staff_id`, `title`)
            VALUES ({$this->teacher2UserId}, 'TCH-002', 'Mrs.');
        ");
        $this->teacher2Id = (int)$this->pdo->lastInsertId();

        // Class Subjects
        $this->pdo->exec("
            INSERT INTO `class_subjects` (`class_id`, `subject_id`, `teacher_id`, `session_id`)
            VALUES ({$this->classId}, {$this->subjectId}, {$this->teacherId}, {$this->sessionId});
        ");
        $this->classSubjectId = (int)$this->pdo->lastInsertId();

        $this->pdo->exec("
            INSERT INTO `class_subjects` (`class_id`, `subject_id`, `teacher_id`, `session_id`)
            VALUES ({$this->classId}, {$this->subjectId}, {$this->teacher2Id}, {$this->sessionId});
        ");
        $this->classSubject2Id = (int)$this->pdo->lastInsertId();

        // Student Profile & Enrollments
        $this->pdo->exec("
            INSERT INTO `students` (`user_id`, `admission_number`, `current_class_id`, `gender`)
            VALUES ({$this->studentUserId}, 'STD/2026/001', {$this->classId}, 'male');
        ");
        $this->studentId = (int)$this->pdo->lastInsertId();

        $this->pdo->exec("
            INSERT INTO `class_enrollments` (`student_id`, `class_id`, `session_id`, `status`)
            VALUES ({$this->studentId}, {$this->classId}, {$this->sessionId}, 'active');
        ");

        $this->pdo->exec("
            INSERT INTO `student_subject_enrollments` (`student_id`, `class_subject_id`, `status`)
            VALUES ({$this->studentId}, {$this->classSubjectId}, 'active');
        ");

        // Parent Profile & Linking
        $this->pdo->exec("
            INSERT INTO `parents` (`user_id`) VALUES ({$this->parentUserId});
        ");
        $this->parentId = (int)$this->pdo->lastInsertId();

        $this->pdo->exec("
            INSERT INTO `parent_student` (`parent_id`, `student_id`, `relationship_type`)
            VALUES ({$this->parentId}, {$this->studentId}, 'Father');
        ");
    }

    private function createAuthenticator(?UserContext $context): AuthenticatorInterface
    {
        $mock = $this->createMock(AuthenticatorInterface::class);
        $mock->method('user')->willReturn($context);
        $mock->method('getUserContext')->willReturn($context);
        $mock->method('check')->willReturn($context !== null);
        return $mock;
    }

    private function makeUserContext(int $userId, string $role): UserContext
    {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = :id");
        $stmt->execute([':id' => $userId]);
        $row = $stmt->fetch();
        $user = User::fromArray($row, [$role]);
        return UserContext::fromUser($user);
    }

    public function testGuestIsUnauthorized(): void
    {
        $this->expectException(AuthorizationException::class);

        $auth = $this->createAuthenticator(null);
        $controller = new ClassController(
            $auth,
            $this->teacherRepo,
            $this->academicRepo,
            $this->enrollmentRepo,
            $this->parentRepo
        );

        $req = new Request([], [], ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/teacher/classes']);
        $controller->index($req);
    }

    public function testStudentWithoutTeacherProfileThrowsAuthorizationException(): void
    {
        $this->expectException(AuthorizationException::class);

        $studentCtx = $this->makeUserContext($this->studentUserId, 'student');
        $auth = $this->createAuthenticator($studentCtx);

        $controller = new ClassController(
            $auth,
            $this->teacherRepo,
            $this->academicRepo,
            $this->enrollmentRepo,
            $this->parentRepo
        );

        $req = new Request([], [], ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/teacher/classes']);
        $controller->index($req);
    }

    public function testTeacherCanViewAssignedClassesWorkspace(): void
    {
        $teacherCtx = $this->makeUserContext($this->teacherUserId, 'teacher');
        $auth = $this->createAuthenticator($teacherCtx);

        $controller = new ClassController(
            $auth,
            $this->teacherRepo,
            $this->academicRepo,
            $this->enrollmentRepo,
            $this->parentRepo
        );

        $req = new Request([], [], ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/teacher/classes']);
        $response = $controller->index($req);

        $this->assertSame(200, $response->getStatusCode());
        $body = (string)$response->getContent();

        $this->assertStringContainsString('My Classes &amp; Student Rosters', $body);
        $this->assertStringContainsString('JSS 1 Emerald', $body);
        $this->assertStringContainsString('Basic Mathematics', $body);
        $this->assertStringContainsString('MTH101', $body);
        $this->assertStringContainsString('1 Student', $body);
    }

    public function testTeacherCanViewAssignedClassSubjectRoster(): void
    {
        $teacherCtx = $this->makeUserContext($this->teacherUserId, 'teacher');
        $auth = $this->createAuthenticator($teacherCtx);

        $controller = new ClassController(
            $auth,
            $this->teacherRepo,
            $this->academicRepo,
            $this->enrollmentRepo,
            $this->parentRepo
        );

        $req = new Request([], [], ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => "/teacher/classes/{$this->classSubjectId}"]);
        $response = $controller->show($req, $this->classSubjectId);

        $this->assertSame(200, $response->getStatusCode());
        $body = (string)$response->getContent();

        // Assert Student candidate details
        $this->assertStringContainsString('Chidi Okafor', $body);
        $this->assertStringContainsString('STD/2026/001', $body);
        $this->assertStringContainsString('Male', $body);

        // Assert Guardian details
        $this->assertStringContainsString('Dr. Emeka Okafor', $body);
        $this->assertStringContainsString('08055555555', $body);

        // Assert quick action links
        $this->assertStringContainsString("/teacher/gradebook/{$this->classSubjectId}", $body);
    }

    public function testTeacherCannotViewUnassignedClassSubjectRoster(): void
    {
        // Teacher 1 attempts to view Teacher 2's class cohort
        $teacherCtx = $this->makeUserContext($this->teacherUserId, 'teacher');
        $auth = $this->createAuthenticator($teacherCtx);

        $controller = new ClassController(
            $auth,
            $this->teacherRepo,
            $this->academicRepo,
            $this->enrollmentRepo,
            $this->parentRepo
        );

        $req = new Request([], [], ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => "/teacher/classes/{$this->classSubject2Id}"]);
        $response = $controller->show($req, $this->classSubject2Id);

        $this->assertSame(403, $response->getStatusCode());
        $body = (string)$response->getContent();
        $this->assertStringContainsString('You are not authorized to access this class roster.', $body);
    }

    public function testAdminCanViewAnyClassSubjectRoster(): void
    {
        $adminCtx = $this->makeUserContext($this->adminUserId, 'super_admin');
        $auth = $this->createAuthenticator($adminCtx);

        $controller = new ClassController(
            $auth,
            $this->teacherRepo,
            $this->academicRepo,
            $this->enrollmentRepo,
            $this->parentRepo
        );

        $req = new Request([], [], ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => "/teacher/classes/{$this->classSubject2Id}"]);
        $response = $controller->show($req, $this->classSubject2Id);

        $this->assertSame(200, $response->getStatusCode());
        $body = (string)$response->getContent();
        $this->assertStringContainsString('Chidi Okafor', $body);
        $this->assertStringContainsString('Dr. Emeka Okafor', $body);
    }

    public function testNonExistentClassSubjectReturnsNotFound(): void
    {
        $teacherCtx = $this->makeUserContext($this->teacherUserId, 'teacher');
        $auth = $this->createAuthenticator($teacherCtx);

        $controller = new ClassController(
            $auth,
            $this->teacherRepo,
            $this->academicRepo,
            $this->enrollmentRepo,
            $this->parentRepo
        );

        $req = new Request([], [], ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/teacher/classes/99999']);
        $response = $controller->show($req, 99999);

        $this->assertSame(404, $response->getStatusCode());
    }
}
