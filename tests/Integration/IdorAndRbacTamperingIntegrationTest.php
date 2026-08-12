<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Controllers\Admin\BackupController;
use App\Controllers\Admin\HealthController;
use App\Controllers\Admin\UserController;
use App\Core\Request;
use App\Core\Response;
use App\Core\UserContext;
use App\Middleware\CsrfMiddleware;
use App\Models\User;
use App\Policies\AssignmentPolicy;
use App\Policies\ParentPolicy;
use App\Repositories\AcademicRepository;
use App\Repositories\ParentRepository;
use App\Repositories\TeacherRepository;
use PDO;
use PHPUnit\Framework\TestCase;

class IdorAndRbacTamperingIntegrationTest extends TestCase
{
    private PDO $pdo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                uuid VARCHAR(36) NOT NULL,
                name VARCHAR(255) NOT NULL,
                email VARCHAR(255) NOT NULL,
                phone VARCHAR(50),
                password_hash VARCHAR(255) NOT NULL DEFAULT '',
                status VARCHAR(20) NOT NULL DEFAULT 'active',
                must_change_password INTEGER NOT NULL DEFAULT 0,
                created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
            );
            CREATE TABLE IF NOT EXISTS parents (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                relationship VARCHAR(50) NOT NULL DEFAULT 'Parent',
                created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
            );
            CREATE TABLE IF NOT EXISTS parent_student (
                parent_id INTEGER NOT NULL,
                student_id INTEGER NOT NULL,
                relationship_type VARCHAR(50) NOT NULL DEFAULT 'Parent',
                PRIMARY KEY (parent_id, student_id)
            );
            CREATE TABLE IF NOT EXISTS students (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                admission_number VARCHAR(50) NOT NULL,
                current_class_id INTEGER,
                created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
            );
            CREATE TABLE IF NOT EXISTS teachers (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                staff_id VARCHAR(50) NOT NULL,
                specialization VARCHAR(100),
                created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
            );
            CREATE TABLE IF NOT EXISTS sessions (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name VARCHAR(100) NOT NULL,
                start_date TEXT NOT NULL,
                end_date TEXT NOT NULL,
                status VARCHAR(20) NOT NULL DEFAULT 'active'
            );
            CREATE TABLE IF NOT EXISTS classes (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name VARCHAR(100) NOT NULL,
                section_arm VARCHAR(50),
                academic_level_id INTEGER NOT NULL,
                status VARCHAR(20) NOT NULL DEFAULT 'active'
            );
            CREATE TABLE IF NOT EXISTS subjects (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name VARCHAR(100) NOT NULL,
                code VARCHAR(50) NOT NULL,
                status VARCHAR(20) NOT NULL DEFAULT 'active'
            );
            CREATE TABLE IF NOT EXISTS class_subjects (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                session_id INTEGER NOT NULL,
                class_id INTEGER NOT NULL,
                subject_id INTEGER NOT NULL,
                teacher_id INTEGER NOT NULL,
                status VARCHAR(20) NOT NULL DEFAULT 'active'
            );
        ");
    }

    public function testStudentCannotAccessAdminEndpoints(): void
    {
        $studentUser = User::fromArray([
            'id' => 10,
            'uuid' => 'u-student-10',
            'name' => 'Student Sam',
            'email' => 'sam@school.edu',
            'role' => 'student',
            'status' => 'active',
        ], ['student']);
        $studentContext = UserContext::fromUser($studentUser);

        // 1. Health Controller
        $healthController = new HealthController();
        $req = new Request(serverParams: ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/admin/health']);
        $req->setAttribute('user_context', $studentContext);
        $res = $healthController->index($req);
        $this->assertSame(403, $res->getStatusCode());

        // 2. Backup Controller
        $backupController = new BackupController();
        $reqBackup = new Request(serverParams: ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/admin/backups']);
        $reqBackup->setAttribute('user_context', $studentContext);
        $resBackup = $backupController->index($reqBackup);
        $this->assertSame(403, $resBackup->getStatusCode());

        // 3. User Administration Controller
        $userController = new UserController();
        $reqUser = new Request(serverParams: ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/admin/users']);
        $reqUser->setAttribute('user_context', $studentContext);
        $resUser = $userController->index($reqUser);
        $this->assertSame(403, $resUser->getStatusCode());
    }

    public function testTeacherCannotAccessSystemHealthOrBackups(): void
    {
        $teacherUser = User::fromArray([
            'id' => 20,
            'uuid' => 'u-teacher-20',
            'name' => 'Teacher Terry',
            'email' => 'terry@school.edu',
            'role' => 'teacher',
            'status' => 'active',
        ], ['teacher']);
        $teacherContext = UserContext::fromUser($teacherUser);

        $healthController = new HealthController();
        $req = new Request(serverParams: ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/admin/health']);
        $req->setAttribute('user_context', $teacherContext);
        $res = $healthController->index($req);
        $this->assertSame(403, $res->getStatusCode());

        $backupController = new BackupController();
        $reqBackup = new Request(serverParams: ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/admin/backups/create']);
        $reqBackup->setAttribute('user_context', $teacherContext);
        $resBackup = $backupController->create($reqBackup);
        $this->assertSame(403, $resBackup->getStatusCode());
    }

    public function testBackupControllerRejectsPathTraversalAttempts(): void
    {
        $adminUser = User::fromArray([
            'id' => 1,
            'uuid' => 'u-admin-1',
            'name' => 'Admin Alex',
            'email' => 'alex@school.edu',
            'role' => 'admin',
            'status' => 'active',
        ], ['admin']);
        $adminContext = UserContext::fromUser($adminUser);

        $backupController = new BackupController();

        // 1. Directory traversal attempt
        $req = new Request(queryParams: ['filename' => '../../etc/passwd'], serverParams: ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/admin/backups/download']);
        $req->setAttribute('user_context', $adminContext);
        $res = $backupController->download($req, '../../etc/passwd');
        $this->assertSame(403, $res->getStatusCode());

        // 2. Hidden file attempt
        $req2 = new Request(queryParams: ['filename' => '.env'], serverParams: ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/admin/backups/download']);
        $req2->setAttribute('user_context', $adminContext);
        $res2 = $backupController->download($req2, '.env');
        $this->assertSame(403, $res2->getStatusCode());
    }

    public function testParentCannotAccessUnlinkedChildData(): void
    {
        $parentUser = User::fromArray([
            'id' => 30,
            'uuid' => 'u-parent-30',
            'name' => 'Parent Patricia',
            'email' => 'patricia@school.edu',
            'role' => 'parent',
            'status' => 'active',
        ], ['parent']);
        $parentContext = UserContext::fromUser($parentUser);

        // Seed user and parent in database without student link
        $this->pdo->exec("INSERT INTO users (id, uuid, name, email) VALUES (30, 'u-parent-30', 'Parent Patricia', 'patricia@school.edu')");
        $this->pdo->exec("INSERT INTO parents (id, user_id) VALUES (1, 30)");

        $parentRepo = new ParentRepository($this->pdo);

        // Child 101 is NOT linked to Parent 30
        $canAccess = ParentPolicy::canViewStudent($parentContext, 101, $parentRepo);
        $this->assertFalse($canAccess);

        // Link Child 101 and recheck
        $this->pdo->exec("INSERT INTO users (id, uuid, name, email) VALUES (101, 'u-student-101', 'Student Child', 'child@school.edu')");
        $this->pdo->exec("INSERT INTO students (id, user_id, admission_number) VALUES (101, 101, 'ADM-101')");
        $this->pdo->exec("INSERT INTO parent_student (parent_id, student_id) VALUES (1, 101)");
        $canAccessAfterLink = ParentPolicy::canViewStudent($parentContext, 101, $parentRepo);
        $this->assertTrue($canAccessAfterLink);
    }

    public function testTeacherCannotManageAssignmentsForUnassignedSubjects(): void
    {
        $teacherUser = User::fromArray([
            'id' => 40,
            'uuid' => 'u-teacher-40',
            'name' => 'Teacher Thomas',
            'email' => 'thomas@school.edu',
            'role' => 'teacher',
            'status' => 'active',
        ], ['teacher']);
        $teacherContext = UserContext::fromUser($teacherUser);

        // Seed user, teacher, sessions, classes, subjects in DB
        $this->pdo->exec("INSERT INTO users (id, uuid, name, email) VALUES (40, 'u-teacher-40', 'Teacher Thomas', 'thomas@school.edu')");
        $this->pdo->exec("INSERT INTO users (id, uuid, name, email) VALUES (999, 'u-teacher-999', 'Teacher Other', 'other@school.edu')");
        $this->pdo->exec("INSERT INTO teachers (id, user_id, staff_id) VALUES (10, 40, 'T-040'), (999, 999, 'T-999')");
        $this->pdo->exec("INSERT INTO sessions (id, name, start_date, end_date, status) VALUES (1, '2026/2027', '2026-09-01', '2027-07-31', 'active')");
        $this->pdo->exec("INSERT INTO classes (id, name, section_arm, academic_level_id, status) VALUES (1, 'Grade 10', 'A', 1, 'active')");
        $this->pdo->exec("INSERT INTO subjects (id, name, code, status) VALUES (1, 'Mathematics', 'MTH101', 'active'), (2, 'Physics', 'PHY101', 'active')");

        // Seed class subjects: subject 5 assigned to teacher 10, subject 99 assigned to teacher 999
        $this->pdo->exec("
            INSERT INTO class_subjects (id, session_id, class_id, subject_id, teacher_id, status)
            VALUES (5, 1, 1, 1, 10, 'active'), (99, 1, 1, 2, 999, 'active')
        ");

        $academicRepo = new AcademicRepository($this->pdo);
        $teacherRepo = new TeacherRepository($this->pdo);

        // Teacher 40 cannot create/manage assignment for unassigned subject 99
        $canManageUnassigned = AssignmentPolicy::canCreateAssignment($teacherContext, 99, null, $academicRepo, $teacherRepo);
        $this->assertFalse($canManageUnassigned);

        // Teacher 40 can create assignment for assigned subject 5
        $canManageAssigned = AssignmentPolicy::canCreateAssignment($teacherContext, 5, null, $academicRepo, $teacherRepo);
        $this->assertTrue($canManageAssigned);
    }

    public function testCsrfMiddlewareRejectsMissingOrMismatchedTokensOnPost(): void
    {
        $middleware = new CsrfMiddleware();

        // Request with no CSRF token
        $requestWithoutToken = new Request(postParams: ['name' => 'Bad Actor'], serverParams: ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/admin/users']);
        $response1 = $middleware->handle($requestWithoutToken, fn() => Response::json(['success' => true]));
        $this->assertSame(419, $response1->getStatusCode());
        $this->assertStringContainsString('Page Expired', $response1->getContent());

        // Request with invalid CSRF token
        $requestWithBadToken = new Request(postParams: ['_token' => 'invalid_csrf_token_value'], serverParams: ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/admin/users']);
        $response2 = $middleware->handle($requestWithBadToken, fn() => Response::json(['success' => true]));
        $this->assertSame(419, $response2->getStatusCode());
    }
}
