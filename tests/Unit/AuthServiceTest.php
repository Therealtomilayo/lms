<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Session;
use App\Repositories\UserRepository;
use App\Services\AuthService;
use PDO;
use PHPUnit\Framework\TestCase;

final class AuthServiceTest extends TestCase
{
    private PDO $pdo;
    private UserRepository $userRepository;
    private AuthService $authService;

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

            CREATE TABLE `user_sessions` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `user_id` INTEGER NOT NULL,
                `session_hash` VARCHAR(64) NOT NULL UNIQUE,
                `expires_at` DATETIME NOT NULL,
                `last_seen_at` DATETIME NOT NULL,
                `user_agent_hash` VARCHAR(64) NULL,
                `ip_hash` VARCHAR(64) NULL,
                `revoked_at` DATETIME NULL
            );

            CREATE TABLE `password_reset_tokens` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `user_id` INTEGER NOT NULL,
                `token_hash` VARCHAR(64) NOT NULL UNIQUE,
                `expires_at` DATETIME NOT NULL,
                `requested_ip` VARCHAR(45) NULL,
                `used_at` DATETIME NULL,
                `created_at` DATETIME NOT NULL
            );
        ");

        $this->userRepository = new UserRepository($this->pdo);
        $this->authService = new AuthService($this->userRepository);
    }

    public function testLoginSuccessAndMultiRoleResolution(): void
    {
        $this->userRepository->create([
            'uuid' => 'u-1',
            'name' => 'Admin Super',
            'email' => 'super@claret.edu',
            'password_hash' => password_hash('Secret123!', PASSWORD_DEFAULT),
            'status' => 'active',
            'must_change_password' => 0,
        ], ['super_admin', 'teacher']);

        $res = $this->authService->login('super@claret.edu', 'Secret123!', '127.0.0.1', 'PHPUnit');

        $this->assertTrue($res->isSuccess());
        $this->assertSame('/admin/dashboard', $res->data['redirect']);
        $this->assertSame(['super_admin', 'teacher'], $res->data['roles']);
        $this->assertFalse($res->data['must_change_password']);
        $this->assertSame(1, Session::get('user_id'));
    }

    public function testLoginMustChangePasswordRedirect(): void
    {
        $this->userRepository->create([
            'uuid' => 'u-2',
            'name' => 'New Teacher',
            'email' => 'teacher@claret.edu',
            'password_hash' => password_hash('TempPassword123!', PASSWORD_DEFAULT),
            'status' => 'active',
            'must_change_password' => 1,
        ], ['teacher']);

        $res = $this->authService->login('teacher@claret.edu', 'TempPassword123!');

        $this->assertTrue($res->isSuccess());
        $this->assertSame('/profile/password', $res->data['redirect']);
        $this->assertTrue($res->data['must_change_password']);
    }

    public function testLoginFailureInvalidPassword(): void
    {
        $this->userRepository->create([
            'uuid' => 'u-3',
            'name' => 'Student Sam',
            'email' => 'sam@claret.edu',
            'password_hash' => password_hash('CorrectPassword!', PASSWORD_DEFAULT),
            'status' => 'active',
        ], ['student']);

        $res = $this->authService->login('sam@claret.edu', 'WrongPassword');

        $this->assertTrue($res->isFailure());
        $this->assertSame('INVALID_CREDENTIALS', $res->errorCode);
    }

    public function testLoginFailureSuspendedAccount(): void
    {
        $this->userRepository->create([
            'uuid' => 'u-4',
            'name' => 'Suspended User',
            'email' => 'suspended@claret.edu',
            'password_hash' => password_hash('Pass123!', PASSWORD_DEFAULT),
            'status' => 'suspended',
        ], ['student']);

        $res = $this->authService->login('suspended@claret.edu', 'Pass123!');

        $this->assertTrue($res->isFailure());
        $this->assertSame('ACCOUNT_SUSPENDED', $res->errorCode);
    }

    public function testLogoutInvalidatesSession(): void
    {
        $this->userRepository->create([
            'uuid' => 'u-5',
            'name' => 'Parent Pat',
            'email' => 'pat@claret.edu',
            'password_hash' => password_hash('Pass123!', PASSWORD_DEFAULT),
        ], ['parent']);

        $loginRes = $this->authService->login('pat@claret.edu', 'Pass123!');
        $this->assertTrue($loginRes->isSuccess());

        $sessionHash = Session::get('session_hash');
        $this->assertNotEmpty($sessionHash);

        $logoutRes = $this->authService->logout($sessionHash);
        $this->assertTrue($logoutRes->isSuccess());
        $this->assertNull(Session::get('user_id'));
    }

    public function testPasswordChangeWorkflow(): void
    {
        $user = $this->userRepository->create([
            'uuid' => 'u-6',
            'name' => 'Teacher Tim',
            'email' => 'tim@claret.edu',
            'password_hash' => password_hash('OldPassword123!', PASSWORD_DEFAULT),
            'must_change_password' => 1,
        ], ['teacher']);

        // Wrong current password
        $failRes = $this->authService->changePassword($user->id, 'WrongOld!', 'BrandNewPass123!');
        $this->assertTrue($failRes->isFailure());

        // Same password reuse
        $reuseRes = $this->authService->changePassword($user->id, 'OldPassword123!', 'OldPassword123!');
        $this->assertTrue($reuseRes->isFailure());

        // Success
        $successRes = $this->authService->changePassword($user->id, 'OldPassword123!', 'BrandNewPass123!');
        $this->assertTrue($successRes->isSuccess());

        // Verify updated user in database
        $updatedUser = $this->userRepository->findById($user->id);
        $this->assertFalse($updatedUser->mustChangePassword);
        $this->assertTrue(password_verify('BrandNewPass123!', $updatedUser->passwordHash));
    }

    public function testPasswordResetWorkflow(): void
    {
        $user = $this->userRepository->create([
            'uuid' => 'u-7',
            'name' => 'Student Stella',
            'email' => 'stella@claret.edu',
            'password_hash' => password_hash('OldStellaPass!', PASSWORD_DEFAULT),
        ], ['student']);

        // Request reset
        $reqRes = $this->authService->requestPasswordReset('stella@claret.edu');
        $this->assertTrue($reqRes->isSuccess());
        $token = $reqRes->data['token'];
        $this->assertNotEmpty($token);

        // Validate token
        $valRes = $this->authService->validatePasswordResetToken($token);
        $this->assertTrue($valRes->isSuccess());
        $this->assertSame($user->id, $valRes->data['user_id']);

        // Reset password
        $resetRes = $this->authService->resetPassword($token, 'StellaNewSecret99!');
        $this->assertTrue($resetRes->isSuccess());

        // Second attempt with same token should fail (token used)
        $secondVal = $this->authService->validatePasswordResetToken($token);
        $this->assertTrue($secondVal->isFailure());

        // Login with new password
        $loginRes = $this->authService->login('stella@claret.edu', 'StellaNewSecret99!');
        $this->assertTrue($loginRes->isSuccess());
    }
}
