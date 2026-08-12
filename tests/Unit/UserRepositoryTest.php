<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Repositories\UserRepository;
use PDO;
use PHPUnit\Framework\TestCase;

final class UserRepositoryTest extends TestCase
{
    private PDO $pdo;
    private UserRepository $repo;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:', null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        // Create in-memory tables
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

        $this->repo = new UserRepository($this->pdo);
    }

    public function testCreateAndFindUserWithMultiRole(): void
    {
        $user = $this->repo->create([
            'uuid' => 'u-101',
            'name' => 'Dr. Jane Smith',
            'email' => 'jane@claret.edu',
            'password_hash' => password_hash('Pass1234!', PASSWORD_DEFAULT),
            'status' => 'active',
            'must_change_password' => 1,
        ], ['teacher', 'parent']);

        $this->assertSame(1, $user->id);
        $this->assertSame('jane@claret.edu', $user->email);
        $this->assertTrue($user->mustChangePassword);
        $this->assertSame(['teacher', 'parent'], $user->roles);

        $found = $this->repo->findByEmail('JANE@CLARET.EDU');
        $this->assertNotNull($found);
        $this->assertSame('Dr. Jane Smith', $found->name);
        $this->assertTrue($found->hasRole('teacher'));
        $this->assertTrue($found->hasRole('parent'));
    }

    public function testSessionLifecycle(): void
    {
        $user = $this->repo->create([
            'uuid' => 'u-102',
            'name' => 'Student Alice',
            'email' => 'alice@claret.edu',
            'password_hash' => password_hash('Pass1234!', PASSWORD_DEFAULT),
        ], ['student']);

        $sessionHash = hash('sha256', 'random-token');
        $future = date('Y-m-d H:i:s', time() + 3600);

        $this->repo->createSession($user->id, $sessionHash, $future);

        $session = $this->repo->findSession($sessionHash);
        $this->assertNotNull($session);
        $this->assertSame(1, (int)$session['user_id']);

        $this->repo->revokeSession($sessionHash);
        $revoked = $this->repo->findSession($sessionHash);
        $this->assertNull($revoked);
    }

    public function testPasswordResetTokenLifecycle(): void
    {
        $user = $this->repo->create([
            'uuid' => 'u-103',
            'name' => 'Admin Bob',
            'email' => 'bob@claret.edu',
            'password_hash' => password_hash('Pass1234!', PASSWORD_DEFAULT),
        ], ['admin']);

        $tokenHash = hash('sha256', 'reset-token-123');
        $future = date('Y-m-d H:i:s', time() + 3600);

        $this->repo->createPasswordResetToken($user->id, $tokenHash, $future, '127.0.0.1');

        $record = $this->repo->findValidPasswordResetToken($tokenHash);
        $this->assertNotNull($record);
        $this->assertSame('bob@claret.edu', $record['email']);

        $this->repo->markPasswordResetTokenUsed((int)$record['id']);
        $used = $this->repo->findValidPasswordResetToken($tokenHash);
        $this->assertNull($used);
    }
}
