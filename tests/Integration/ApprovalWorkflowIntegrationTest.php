<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Controllers\Admin\ApprovalController;
use App\Controllers\Admin\UserController;
use App\Core\Exceptions\AuthorizationException;
use App\Core\Request;
use App\Core\Session;
use App\Core\UserContext;
use App\Models\ApprovalRequest;
use App\Models\User;
use App\Repositories\ApprovalRepository;
use App\Repositories\UserRepository;
use App\Services\ApprovalService;
use App\Services\UserService;
use PDO;
use PHPUnit\Framework\TestCase;

final class ApprovalWorkflowIntegrationTest extends TestCase
{
    private PDO $pdo;
    private UserRepository $userRepo;
    private ApprovalRepository $approvalRepo;
    private ApprovalService $approvalService;
    private UserService $userService;
    private UserController $userController;
    private ApprovalController $approvalController;

    private int $superAdminUserId;
    private int $adminUserId;

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
                `must_change_password` INTEGER NOT NULL DEFAULT 0,
                `status` VARCHAR(20) NOT NULL DEFAULT 'active',
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE `user_roles` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `user_id` INTEGER NOT NULL,
                `role` VARCHAR(50) NOT NULL,
                `is_active` INTEGER NOT NULL DEFAULT 1,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE `user_sessions` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `user_id` INTEGER NOT NULL,
                `session_hash` VARCHAR(64) NOT NULL UNIQUE,
                `ip_hash` VARCHAR(64) NULL,
                `user_agent_hash` VARCHAR(64) NULL,
                `last_seen_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `expires_at` DATETIME NOT NULL,
                `revoked_at` DATETIME NULL
            );

            CREATE TABLE `classes` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `name` VARCHAR(50) NOT NULL,
                `section_arm` VARCHAR(20) NOT NULL DEFAULT 'A',
                `academic_level_id` INTEGER NOT NULL DEFAULT 1,
                `class_teacher_id` INTEGER NULL,
                `status` VARCHAR(20) NOT NULL DEFAULT 'active',
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE `students` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `user_id` INTEGER NOT NULL,
                `admission_number` VARCHAR(50) NOT NULL UNIQUE,
                `current_class_id` INTEGER NULL,
                `date_of_birth` DATE NULL,
                `gender` VARCHAR(10) NULL,
                `status` VARCHAR(20) NOT NULL DEFAULT 'active',
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE `teachers` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `user_id` INTEGER NOT NULL,
                `staff_id` VARCHAR(50) NOT NULL UNIQUE,
                `status` VARCHAR(20) NOT NULL DEFAULT 'active',
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE `parents` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `user_id` INTEGER NOT NULL,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE `parent_student` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `parent_id` INTEGER NOT NULL,
                `student_id` INTEGER NOT NULL,
                `relationship_type` VARCHAR(50) NULL,
                `is_primary` INTEGER NOT NULL DEFAULT 1,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE `approval_requests` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `request_type` VARCHAR(50) NOT NULL,
                `entity_type` VARCHAR(50) NOT NULL,
                `entity_id` INTEGER NOT NULL,
                `requester_id` INTEGER NOT NULL,
                `reviewer_id` INTEGER NULL,
                `status` VARCHAR(20) NOT NULL DEFAULT 'pending',
                `payload` TEXT NULL,
                `rejection_reason` TEXT NULL,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `reviewed_at` DATETIME NULL,
                `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            );
        ");

        // Seed Super Admin & Admin
        $this->pdo->exec("
            INSERT INTO `users` (`name`, `email`, `password_hash`, `status`, `uuid`) VALUES
            ('Super Administrator', 'super@claret.edu', 'hash', 'active', 'u-super-1'),
            ('Standard Admin', 'admin@claret.edu', 'hash', 'active', 'u-admin-1');
            
            INSERT INTO `user_roles` (`user_id`, `role`, `is_active`) VALUES 
            (1, 'super_admin', 1), (1, 'admin', 1),
            (2, 'admin', 1);
        ");
        $this->superAdminUserId = 1;
        $this->adminUserId = 2;

        // Repositories & Services
        $this->userRepo = new UserRepository($this->pdo);
        $studentRepo = new \App\Repositories\StudentRepository($this->pdo);
        $teacherRepo = new \App\Repositories\TeacherRepository($this->pdo);
        $parentRepo = new \App\Repositories\ParentRepository($this->pdo);
        $this->approvalRepo = new ApprovalRepository($this->pdo);
        $this->approvalService = new ApprovalService($this->approvalRepo, $this->pdo);
        $this->userService = new UserService(
            $this->userRepo,
            $studentRepo,
            $teacherRepo,
            $parentRepo,
            $this->pdo
        );

        $this->userController = new UserController(
            $this->userService,
            $this->userRepo,
            new \App\Repositories\AcademicRepository($this->pdo),
            $this->approvalService
        );

        $this->approvalController = new ApprovalController(
            null,
            $this->approvalRepo,
            $this->approvalService
        );
    }

    protected function tearDown(): void
    {
        Session::destroy();
    }

    private function createUserContext(int $userId, string $name, string $email, array $roles): UserContext
    {
        $user = new User(
            id: $userId,
            uuid: "u-{$userId}",
            name: $name,
            email: $email,
            status: 'active',
            roles: $roles
        );
        return UserContext::fromUser($user, $roles);
    }

    public function testAdminCreatingStudentIsActiveImmediatelyWithoutApproval(): void
    {
        $adminContext = $this->createUserContext($this->adminUserId, 'Standard Admin', 'admin@claret.edu', ['admin']);

        $request = new Request([], [
            'name' => 'Kelechi Iheanacho',
            'email' => 'kelechi@claret.edu',
            'password' => 'SecurePass123!',
            'roles' => ['student'],
            'admission_number' => 'CIS-2026-901',
            'gender' => 'male',
        ], ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/admin/users']);
        $request->setAttribute('user_context', $adminContext);

        $response = $this->userController->store($request);

        // Standard student creation is active immediately
        $this->assertSame(302, $response->getStatusCode());
        $flashSuccess = Session::getFlash('success');
        $this->assertSame('User account created successfully.', $flashSuccess);

        $createdUser = $this->userRepo->findByEmail('kelechi@claret.edu');
        $this->assertNotNull($createdUser);
        $this->assertSame('active', $createdUser->status);

        // Confirm no approval request was staged
        $pendingReq = $this->approvalRepo->findPendingByEntity('user', $createdUser->id);
        $this->assertNull($pendingReq);
    }

    public function testAdminCreatingTeacherIsActiveImmediatelyWithoutApproval(): void
    {
        $adminContext = $this->createUserContext($this->adminUserId, 'Standard Admin', 'admin@claret.edu', ['admin']);

        $request = new Request([], [
            'name' => 'Mrs. Ngozi Okonjo',
            'email' => 'ngozi@claret.edu',
            'password' => 'SecurePass123!',
            'roles' => ['teacher'],
            'staff_id' => 'CIS-T-999',
        ], ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/admin/users']);
        $request->setAttribute('user_context', $adminContext);

        $response = $this->userController->store($request);

        $this->assertSame(302, $response->getStatusCode());
        $createdUser = $this->userRepo->findByEmail('ngozi@claret.edu');
        $this->assertNotNull($createdUser);
        $this->assertSame('active', $createdUser->status);

        $pendingReq = $this->approvalRepo->findPendingByEntity('user', $createdUser->id);
        $this->assertNull($pendingReq);
    }

    public function testAdminCreatingUserWithAdminRoleStagesAdminRoleAssignmentRequest(): void
    {
        $adminContext = $this->createUserContext($this->adminUserId, 'Standard Admin', 'admin@claret.edu', ['admin']);

        $request = new Request([], [
            'name' => 'Prospective Admin',
            'email' => 'prospect@claret.edu',
            'password' => 'SecurePass123!',
            'roles' => ['admin', 'teacher'],
            'staff_id' => 'CIS-T-888',
        ], ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/admin/users']);
        $request->setAttribute('user_context', $adminContext);

        $response = $this->userController->store($request);

        $this->assertSame(302, $response->getStatusCode());
        $flashSuccess = Session::getFlash('success');
        $this->assertStringContainsString('Request to grant Admin role submitted to Super Admin for approval', $flashSuccess);

        $createdUser = $this->userRepo->findByEmail('prospect@claret.edu');
        $this->assertNotNull($createdUser);
        // Created with base teacher role (not admin yet)
        $this->assertFalse($createdUser->hasRole('admin'));
        $this->assertTrue($createdUser->hasRole('teacher'));

        // Confirm approval request was staged
        $pendingReq = $this->approvalRepo->findPendingByEntity('user', $createdUser->id);
        $this->assertNotNull($pendingReq);
        $this->assertSame(ApprovalRequest::TYPE_ADMIN_ROLE_ASSIGNMENT, $pendingReq->requestType);
        $this->assertSame($this->adminUserId, $pendingReq->requesterId);
    }

    public function testAdminRequestingUserDeletionStagesDeletionApprovalRequest(): void
    {
        // 1. Create a user to delete
        $this->pdo->exec("
            INSERT INTO `users` (`name`, `email`, `password_hash`, `status`, `uuid`) VALUES
            ('Withdrawing Student', 'withdraw@claret.edu', 'hash', 'active', 'u-withdraw-1');
        ");
        $withdrawUserId = (int)$this->pdo->lastInsertId();

        $adminContext = $this->createUserContext($this->adminUserId, 'Standard Admin', 'admin@claret.edu', ['admin']);
        $request = new Request([], [
            'reason' => 'Student relocated to another state with family.',
        ], ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => "/admin/users/{$withdrawUserId}/delete"]);
        $request->setAttribute('user_context', $adminContext);

        $response = $this->userController->delete($request, $withdrawUserId);

        $this->assertSame(302, $response->getStatusCode());
        $flashSuccess = Session::getFlash('success');
        $this->assertStringContainsString('Deletion request for Withdrawing Student submitted to Super Admin approval queue', $flashSuccess);

        // User remains active until approved
        $targetUser = $this->userRepo->findById($withdrawUserId);
        $this->assertSame('active', $targetUser->status);

        // Approval request exists
        $pendingReq = $this->approvalRepo->findPendingByEntity('user', $withdrawUserId);
        $this->assertNotNull($pendingReq);
        $this->assertSame(ApprovalRequest::TYPE_USER_DELETION, $pendingReq->requestType);
        $this->assertSame('Student relocated to another state with family.', $pendingReq->payload['reason'] ?? null);
    }

    public function testSuperAdminCanDirectlyDeleteUser(): void
    {
        $this->pdo->exec("
            INSERT INTO `users` (`name`, `email`, `password_hash`, `status`, `uuid`) VALUES
            ('Direct Delete User', 'directdel@claret.edu', 'hash', 'active', 'u-direct-1');
        ");
        $directUserId = (int)$this->pdo->lastInsertId();

        $superAdminContext = $this->createUserContext($this->superAdminUserId, 'Super Admin', 'super@claret.edu', ['super_admin', 'admin']);
        $request = new Request([], [], ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => "/admin/users/{$directUserId}/delete"]);
        $request->setAttribute('user_context', $superAdminContext);

        $response = $this->userController->delete($request, $directUserId);

        $flashError = Session::getFlash('error');
        $flashSuccess = Session::getFlash('success');
        if ($flashError) {
            $this->fail("Delete failed with error: " . $flashError);
        }
        $this->assertNotNull($flashSuccess);
        $this->assertStringContainsString('deleted successfully', $flashSuccess);

        $targetUser = $this->userRepo->findById($directUserId);
        $this->assertSame('inactive', $targetUser->status);

        // No approval request needed for Super Admin
        $pendingReq = $this->approvalRepo->findPendingByEntity('user', $directUserId);
        $this->assertNull($pendingReq);
    }

    public function testSuperAdminCanApproveUserDeletionRequest(): void
    {
        // 1. Create a user
        $this->pdo->exec("
            INSERT INTO `users` (`name`, `email`, `password_hash`, `status`, `uuid`) VALUES
            ('Candidate To Delete', 'delete.me@claret.edu', 'hash', 'active', 'u-del-1');
        ");
        $delUserId = (int)$this->pdo->lastInsertId();

        // 2. Create approval request
        $approvalReq = $this->approvalRepo->createRequest(
            ApprovalRequest::TYPE_USER_DELETION,
            'user',
            $delUserId,
            $this->adminUserId,
            ['name' => 'Candidate To Delete', 'reason' => 'Disciplinary expulsion']
        );

        // 3. Super Admin approves deletion
        $superAdminContext = $this->createUserContext($this->superAdminUserId, 'Super Admin', 'super@claret.edu', ['super_admin']);
        $request = new Request([], [], ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => "/admin/approvals/{$approvalReq->id}/approve"]);
        $request->setAttribute('_user_context', $superAdminContext);

        $response = $this->approvalController->approve($request, $approvalReq->id);

        $this->assertSame(302, $response->getStatusCode());

        // 4. Verify request is approved and user is now inactive
        $updatedReq = $this->approvalRepo->findRequestById($approvalReq->id);
        $this->assertNotNull($updatedReq);
        $this->assertSame(ApprovalRequest::STATUS_APPROVED, $updatedReq->status);

        $updatedUser = $this->userRepo->findById($delUserId);
        $this->assertNotNull($updatedUser);
        $this->assertSame('inactive', $updatedUser->status);
    }

    public function testSuperAdminCanApproveAdminRoleAssignmentRequest(): void
    {
        // 1. Create a teacher user
        $this->pdo->exec("
            INSERT INTO `users` (`name`, `email`, `password_hash`, `status`, `uuid`) VALUES
            ('Promoted Teacher', 'promoted@claret.edu', 'hash', 'active', 'u-prom-1');
        ");
        $promotedUserId = (int)$this->pdo->lastInsertId();
        $this->pdo->exec("
            INSERT INTO `user_roles` (`user_id`, `role`, `is_active`) VALUES
            ({$promotedUserId}, 'teacher', 1);
        ");

        // 2. Stage role elevation request
        $approvalReq = $this->approvalRepo->createRequest(
            ApprovalRequest::TYPE_ADMIN_ROLE_ASSIGNMENT,
            'user',
            $promotedUserId,
            $this->adminUserId,
            ['target_user_id' => $promotedUserId, 'role' => 'admin', 'reason' => 'Appointed Vice Principal']
        );

        // 3. Super Admin approves
        $superAdminContext = $this->createUserContext($this->superAdminUserId, 'Super Admin', 'super@claret.edu', ['super_admin']);
        $request = new Request([], [], ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => "/admin/approvals/{$approvalReq->id}/approve"]);
        $request->setAttribute('_user_context', $superAdminContext);

        $response = $this->approvalController->approve($request, $approvalReq->id);

        $this->assertSame(302, $response->getStatusCode());

        // 4. User now has admin role
        $updatedUser = $this->userRepo->findById($promotedUserId);
        $this->assertNotNull($updatedUser);
        $this->assertTrue($updatedUser->hasRole('admin'));
        $this->assertTrue($updatedUser->hasRole('teacher'));
    }

    public function testSuperAdminCanRejectDeletionRequestWithReason(): void
    {
        $this->pdo->exec("
            INSERT INTO `users` (`name`, `email`, `password_hash`, `status`, `uuid`) VALUES
            ('Retained Student', 'retained@claret.edu', 'hash', 'active', 'u-ret-1');
        ");
        $retainedId = (int)$this->pdo->lastInsertId();

        $approvalReq = $this->approvalRepo->createRequest(
            ApprovalRequest::TYPE_USER_DELETION,
            'user',
            $retainedId,
            $this->adminUserId,
            ['name' => 'Retained Student', 'reason' => 'Requested withdrawal']
        );

        $superAdminContext = $this->createUserContext($this->superAdminUserId, 'Super Admin', 'super@claret.edu', ['super_admin']);
        $request = new Request([], [
            'rejection_reason' => 'Parent rescinded withdrawal request. Student remains enrolled.',
        ], ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => "/admin/approvals/{$approvalReq->id}/reject"]);
        $request->setAttribute('_user_context', $superAdminContext);

        $response = $this->approvalController->reject($request, $approvalReq->id);

        $this->assertSame(302, $response->getStatusCode());

        $updatedReq = $this->approvalRepo->findRequestById($approvalReq->id);
        $this->assertNotNull($updatedReq);
        $this->assertSame(ApprovalRequest::STATUS_REJECTED, $updatedReq->status);
        $this->assertSame('Parent rescinded withdrawal request. Student remains enrolled.', $updatedReq->rejectionReason);

        // User remains active
        $updatedUser = $this->userRepo->findById($retainedId);
        $this->assertNotNull($updatedUser);
        $this->assertSame('active', $updatedUser->status);
    }

    public function testNonSuperAdminIsForbiddenFromApprovals(): void
    {
        $this->expectException(AuthorizationException::class);

        $standardAdmin = $this->createUserContext($this->adminUserId, 'Standard Admin', 'admin@claret.edu', ['admin']);
        $request = new Request([], [], ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/admin/approvals']);
        $request->setAttribute('_user_context', $standardAdmin);

        $this->approvalController->index($request);
    }

    public function testStudentRoleCannotBeCombinedWithOtherRolesOnCreate(): void
    {
        $this->expectException(\App\Core\Exceptions\ValidationException::class);

        $superAdminContext = $this->createUserContext($this->superAdminUserId, 'Super Admin', 'super@claret.edu', ['super_admin']);
        $this->userService->createUser([
            'name' => 'Dual Student Teacher',
            'email' => 'dualstudent@claret.edu',
            'password' => 'Password123!',
            'roles' => ['student', 'teacher'],
        ], $superAdminContext);
    }

    public function testStudentRoleCannotBeCombinedWithOtherRolesOnUpdate(): void
    {
        $this->expectException(\App\Core\Exceptions\ValidationException::class);

        $superAdminContext = $this->createUserContext($this->superAdminUserId, 'Super Admin', 'super@claret.edu', ['super_admin']);
        $result = $this->userService->createUser([
            'name' => 'Solo Student',
            'email' => 'solostudent@claret.edu',
            'password' => 'Password123!',
            'roles' => ['student'],
        ], $superAdminContext);

        $createdUser = $result->data;
        $this->userService->updateUser($createdUser->id, [
            'roles' => ['student', 'admin'],
        ], $superAdminContext);
    }

    public function testAllowedMultiRoleCombinationsPassValidation(): void
    {
        $superAdminContext = $this->createUserContext($this->superAdminUserId, 'Super Admin', 'super@claret.edu', ['super_admin']);
        $result = $this->userService->createUser([
            'name' => 'Admin Teacher Parent',
            'email' => 'multistaff@claret.edu',
            'password' => 'Password123!',
            'roles' => ['admin', 'teacher', 'parent'],
        ], $superAdminContext);

        $this->assertTrue($result->success);
        $user = $result->data;
        $this->assertContains('admin', $user->roles);
        $this->assertContains('teacher', $user->roles);
        $this->assertContains('parent', $user->roles);
    }
}

