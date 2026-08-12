<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Controllers\Admin\ClassSubjectController;
use App\Core\Csrf;
use App\Core\Request;
use App\Core\Router;
use App\Core\Session;
use App\Core\UserContext;
use App\Middleware\AuthMiddleware;
use App\Middleware\CsrfMiddleware;
use App\Middleware\RoleMiddleware;
use App\Models\User;
use App\Policies\AcademicPolicy;
use App\Repositories\AcademicRepository;
use App\Repositories\TeacherRepository;
use App\Repositories\UserRepository;
use App\Services\TeacherAssignmentService;
use PDO;
use PHPUnit\Framework\TestCase;

final class ClassSubjectFlowIntegrationTest extends TestCase
{
    private PDO $pdo;
    private UserRepository $userRepository;
    private TeacherRepository $teacherRepository;
    private AcademicRepository $academicRepository;
    private TeacherAssignmentService $assignmentService;
    private Router $router;
    private User $adminUser;
    private User $teacherUser1;
    private User $teacherUser2;

    protected function setUp(): void
    {
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

            CREATE TABLE `academic_levels` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `name` VARCHAR(100) NOT NULL UNIQUE,
                `stage` VARCHAR(50) NOT NULL,
                `rank_order` INTEGER NOT NULL DEFAULT 0,
                `grading_scale_id` INTEGER NULL,
                `created_at` DATETIME NOT NULL,
                `updated_at` DATETIME NOT NULL
            );

            CREATE TABLE `classes` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `academic_level_id` INTEGER NOT NULL,
                `name` VARCHAR(100) NOT NULL,
                `section_arm` VARCHAR(50) NULL,
                `status` VARCHAR(20) NOT NULL DEFAULT 'active',
                `created_at` DATETIME NOT NULL,
                `updated_at` DATETIME NOT NULL
            );

            CREATE TABLE `sessions` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `name` VARCHAR(100) NOT NULL UNIQUE,
                `start_date` DATE NOT NULL,
                `end_date` DATE NOT NULL,
                `status` VARCHAR(20) NOT NULL DEFAULT 'planning',
                `created_at` DATETIME NOT NULL,
                `updated_at` DATETIME NOT NULL
            );

            CREATE TABLE `subjects` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `name` VARCHAR(100) NOT NULL,
                `code` VARCHAR(30) NOT NULL UNIQUE,
                `status` VARCHAR(20) NOT NULL DEFAULT 'active',
                `created_at` DATETIME NOT NULL,
                `updated_at` DATETIME NOT NULL
            );

            CREATE TABLE `teachers` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `user_id` INTEGER NOT NULL UNIQUE,
                `staff_id` VARCHAR(50) NOT NULL UNIQUE,
                `created_at` DATETIME NOT NULL,
                `updated_at` DATETIME NOT NULL
            );

            CREATE TABLE `class_subjects` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `session_id` INTEGER NOT NULL,
                `class_id` INTEGER NOT NULL,
                `subject_id` INTEGER NOT NULL,
                `teacher_id` INTEGER NOT NULL,
                `status` VARCHAR(20) NOT NULL DEFAULT 'active',
                `created_at` DATETIME NOT NULL,
                `updated_at` DATETIME NOT NULL,
                UNIQUE (`session_id`, `class_id`, `subject_id`)
            );
        ");

        $this->userRepository = new UserRepository($this->pdo);
        $this->teacherRepository = new TeacherRepository($this->pdo);
        $this->academicRepository = new AcademicRepository($this->pdo);
        $this->assignmentService = new TeacherAssignmentService(
            $this->academicRepository,
            $this->teacherRepository,
            $this->userRepository
        );

        $now = date('Y-m-d H:i:s');
        $this->pdo->exec("
            INSERT INTO `users` (`id`, `uuid`, `name`, `email`, `password_hash`, `status`, `created_at`, `updated_at`)
            VALUES 
                (1, 'u-admin', 'Admin User', 'admin@claret.edu', 'hash', 'active', '{$now}', '{$now}'),
                (2, 'u-teacher1', 'Dr. Smith', 'smith@claret.edu', 'hash', 'active', '{$now}', '{$now}'),
                (3, 'u-teacher2', 'Mrs. Johnson', 'johnson@claret.edu', 'hash', 'active', '{$now}', '{$now}');

            INSERT INTO `user_roles` (`user_id`, `role`, `is_active`, `created_at`)
            VALUES 
                (1, 'admin', 1, '{$now}'),
                (2, 'teacher', 1, '{$now}'),
                (3, 'teacher', 1, '{$now}');

            INSERT INTO `teachers` (`id`, `user_id`, `staff_id`, `created_at`, `updated_at`)
            VALUES 
                (1, 2, 'STF-001', '{$now}', '{$now}'),
                (2, 3, 'STF-002', '{$now}', '{$now}');

            INSERT INTO `sessions` (`id`, `name`, `start_date`, `end_date`, `status`, `created_at`, `updated_at`)
            VALUES (1, '2026/2027', '2026-09-01', '2027-07-20', 'active', '{$now}', '{$now}');

            INSERT INTO `academic_levels` (`id`, `name`, `stage`, `rank_order`, `created_at`, `updated_at`)
            VALUES (1, 'JSS 1', 'Junior Secondary', 1, '{$now}', '{$now}');

            INSERT INTO `classes` (`id`, `academic_level_id`, `name`, `section_arm`, `status`, `created_at`, `updated_at`)
            VALUES (1, 1, 'JSS 1A', 'A', 'active', '{$now}', '{$now}');

            INSERT INTO `subjects` (`id`, `name`, `code`, `status`, `created_at`, `updated_at`)
            VALUES 
                (1, 'Mathematics', 'MTH101', 'active', '{$now}', '{$now}'),
                (2, 'English Language', 'ENG101', 'active', '{$now}', '{$now}');
        ");

        $this->adminUser = $this->userRepository->findById(1);
        $this->teacherUser1 = $this->userRepository->findById(2);
        $this->teacherUser2 = $this->userRepository->findById(3);

        $this->router = new Router();
        $controller = new ClassSubjectController(
            $this->assignmentService,
            $this->academicRepository,
            $this->teacherRepository
        );

        $adminAuth = [AuthMiddleware::class, RoleMiddleware::allow(['admin', 'super_admin'])];
        $adminFormAuth = [AuthMiddleware::class, RoleMiddleware::allow(['admin', 'super_admin']), CsrfMiddleware::class];

        $this->router->get('/admin/class-subjects', [$controller, 'index'], $adminAuth);
        $this->router->post('/admin/class-subjects', [$controller, 'store'], $adminFormAuth);
        $this->router->post('/admin/class-subjects/{id}', [$controller, 'update'], $adminFormAuth);
        $this->router->post('/admin/class-subjects/{id}/status', [$controller, 'status'], $adminFormAuth);

        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        $_SESSION = [];
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
    }

    private function createAuthRequest(string $method, string $uri, array $body = [], ?User $user = null): Request
    {
        $user = $user ?? $this->adminUser;
        $context = UserContext::fromUser($user);

        if (in_array(strtoupper($method), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            $body['_csrf_token'] = Csrf::generate();
        }

        $query = [];
        $parts = parse_url($uri);
        if (isset($parts['query'])) {
            parse_str($parts['query'], $query);
            $uri = $parts['path'];
        }

        $req = new Request(
            queryParams: $query,
            postParams: $body,
            serverParams: ['REQUEST_METHOD' => strtoupper($method), 'REQUEST_URI' => $uri]
        );

        $req->setAttribute('_user_context', $context);
        return $req;
    }

    public function testAdminCanViewClassSubjectsIndex(): void
    {
        $req = $this->createAuthRequest('GET', '/admin/class-subjects?session_id=1');
        $res = $this->router->dispatch($req);

        $this->assertSame(200, $res->getStatusCode());
        $this->assertStringContainsString('Class Subjects & Teacher Mappings', $res->getContent());
    }

    public function testAdminCanAssignSubjectAndTeacherToClass(): void
    {
        $req = $this->createAuthRequest('POST', '/admin/class-subjects', [
            'session_id' => 1,
            'class_id' => 1,
            'subject_id' => 1,
            'teacher_id' => 1,
            'status' => 'active',
        ]);

        $res = $this->router->dispatch($req);

        $this->assertSame(302, $res->getStatusCode());
        $this->assertStringContainsString('/admin/class-subjects', $res->getHeader('Location'));

        $cs = $this->academicRepository->findClassSubject(1, 1, 1);
        $this->assertNotNull($cs);
        $this->assertSame(1, $cs->teacherId);
        $this->assertSame('Mathematics', $cs->subject?->name);
        $this->assertSame('Dr. Smith', $cs->teacher?->user?->name);
    }

    public function testAdminCanReassignTeacher(): void
    {
        // Setup initial assignment
        $assignResult = $this->assignmentService->assignTeacherToClassSubject([
            'session_id' => 1,
            'class_id' => 1,
            'subject_id' => 1,
            'teacher_id' => 1,
        ]);
        $csId = $assignResult->getData()->id;

        $req = $this->createAuthRequest('POST', "/admin/class-subjects/{$csId}", [
            'session_id' => 1,
            'teacher_id' => 2, // Reassign to Mrs. Johnson
        ]);

        $res = $this->router->dispatch($req);

        $this->assertSame(302, $res->getStatusCode());

        $updatedCs = $this->academicRepository->findClassSubjectById($csId);
        $this->assertSame(2, $updatedCs->teacherId);
        $this->assertSame('Mrs. Johnson', $updatedCs->teacher?->user?->name);
    }

    public function testTeacherClassSubjectAuthorizationPredicate(): void
    {
        $assignResult = $this->assignmentService->assignTeacherToClassSubject([
            'session_id' => 1,
            'class_id' => 1,
            'subject_id' => 1,
            'teacher_id' => 1,
        ]);
        $csId = $assignResult->getData()->id;

        // Teacher 1 (assigned) should have management rights for coursework/grading
        $this->assertTrue(
            AcademicPolicy::teacherCanManageClassSubject(1, $csId, 1, $this->academicRepository)
        );

        // Teacher 2 (not assigned to this subject) should NOT
        $this->assertFalse(
            AcademicPolicy::teacherCanManageClassSubject(2, $csId, 1, $this->academicRepository)
        );

        // Mismatched session check should fail
        $this->assertFalse(
            AcademicPolicy::teacherCanManageClassSubject(1, $csId, 999, $this->academicRepository)
        );
    }

    public function testNonAdminCannotAssignClassSubjects(): void
    {
        $req = $this->createAuthRequest('POST', '/admin/class-subjects', [
            'session_id' => 1,
            'class_id' => 1,
            'subject_id' => 2,
            'teacher_id' => 1,
        ], $this->teacherUser1);

        $res = $this->router->dispatch($req);
        $this->assertSame(403, $res->getStatusCode());
    }
}
