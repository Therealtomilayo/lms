<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Controllers\NotificationController;
use App\Core\AuthenticatorInterface;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\UserContext;
use App\Models\Announcement;
use App\Models\ParentProfile;
use App\Models\Student;
use App\Models\User;
use App\Repositories\AnnouncementRepository;
use App\Repositories\ParentRepository;
use App\Repositories\UserRepository;
use PDO;
use PHPUnit\Framework\TestCase;

final class NotificationIntegrationTest extends TestCase
{
    private PDO $pdo;
    private UserRepository $userRepo;
    private AnnouncementRepository $announcementRepo;
    private ParentRepository $parentRepo;

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

            CREATE TABLE `teachers` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `user_id` INTEGER NOT NULL UNIQUE,
                `staff_id` VARCHAR(50) NOT NULL UNIQUE,
                `title` VARCHAR(20) NULL,
                `created_at` DATETIME NOT NULL,
                `updated_at` DATETIME NOT NULL
            );

            CREATE TABLE `class_enrollments` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `student_id` INTEGER NOT NULL,
                `class_id` INTEGER NOT NULL,
                `session_id` INTEGER NOT NULL,
                `status` VARCHAR(20) NOT NULL DEFAULT 'active',
                `created_at` DATETIME NOT NULL
            );

            CREATE TABLE `student_subject_enrollments` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `student_id` INTEGER NOT NULL,
                `class_subject_id` INTEGER NOT NULL,
                `status` VARCHAR(20) NOT NULL DEFAULT 'active',
                `created_at` DATETIME NOT NULL
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

            CREATE TABLE `announcements` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `author_id` INTEGER NOT NULL,
                `scope` VARCHAR(20) NOT NULL DEFAULT 'school',
                `scope_id` INTEGER NULL,
                `title` VARCHAR(255) NOT NULL,
                `body` TEXT NOT NULL,
                `published_at` DATETIME NULL,
                `expires_at` DATETIME NULL,
                `created_at` DATETIME NOT NULL,
                `updated_at` DATETIME NOT NULL
            );

            CREATE TABLE `announcement_reads` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `announcement_id` INTEGER NOT NULL,
                `user_id` INTEGER NOT NULL,
                `read_at` DATETIME NOT NULL,
                `created_at` DATETIME NOT NULL,
                UNIQUE (`announcement_id`, `user_id`)
            );
        ");

        $this->userRepo = new UserRepository($this->pdo);
        $this->announcementRepo = new AnnouncementRepository($this->pdo);
        $this->parentRepo = new ParentRepository($this->pdo);
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

        $controller = new NotificationController($mockAuth, $this->announcementRepo, $this->userRepo, $this->parentRepo);
        $request = new Request([], [], ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/notifications']);

        $response = $controller->index($request);

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('/login', $response->getHeader('Location'));
    }

    public function testAuthenticatedFeedRendersSuccessfully(): void
    {
        $admin = $this->createUser('Administrator', 'admin@claret.edu', ['admin']);
        
        // Create an announcement
        $this->announcementRepo->create([
            'author_id' => $admin->id,
            'scope' => 'school',
            'title' => 'Resumption Assembly Protocol',
            'body' => 'All students and staff must attend morning assembly by 07:45 AM.',
            'published_at' => date('Y-m-d H:i:s', strtotime('-1 hour')),
        ]);

        $mockAuth = $this->createMock(AuthenticatorInterface::class);
        $mockAuth->method('user')->willReturn(UserContext::fromUser($admin));

        $controller = new NotificationController($mockAuth, $this->announcementRepo, $this->userRepo, $this->parentRepo);
        $request = new Request([], [], ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/notifications']);

        $response = $controller->index($request);

        $this->assertEquals(200, $response->getStatusCode());
        $content = $response->getContent();
        $this->assertStringContainsString('Campus Notifications &amp; Bulletins', $content);
        $this->assertStringContainsString('Resumption Assembly Protocol', $content);
        $this->assertStringContainsString('All students and staff must attend morning assembly', $content);
        $this->assertStringContainsString('School-Wide', $content);
    }

    public function testMarkSingleNotificationAsRead(): void
    {
        $teacher = $this->createUser('Teacher User', 'teacher@claret.edu', ['teacher']);
        
        $annId = $this->announcementRepo->create([
            'author_id' => $teacher->id,
            'scope' => 'school',
            'title' => 'Mid-Term Break Schedule',
            'body' => 'Mid-term break commences on Friday.',
            'published_at' => date('Y-m-d H:i:s', strtotime('-2 hours')),
        ]);

        $mockAuth = $this->createMock(AuthenticatorInterface::class);
        $mockAuth->method('user')->willReturn(UserContext::fromUser($teacher));

        $controller = new NotificationController($mockAuth, $this->announcementRepo, $this->userRepo, $this->parentRepo);
        $request = new Request(
            queryParams: [],
            postParams: ['redirect_to' => '/notifications'],
            serverParams: ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => "/notifications/{$annId}/read"]
        );
        $request->setAttribute('id', (string)$annId);

        $response = $controller->markAsRead($request);

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('/notifications', $response->getHeader('Location'));

        // Verify announcement is marked read
        $feed = $this->announcementRepo->getFeedForUser(UserContext::fromUser($teacher));
        $this->assertTrue((bool)$feed[0]->isRead);
    }

    public function testMarkAllNotificationsAsRead(): void
    {
        $admin = $this->createUser('Admin Principal', 'principal@claret.edu', ['admin']);
        
        $this->announcementRepo->create([
            'author_id' => $admin->id,
            'scope' => 'school',
            'title' => 'Notice 1',
            'body' => 'Body 1',
            'published_at' => date('Y-m-d H:i:s', strtotime('-3 hours')),
        ]);
        $this->announcementRepo->create([
            'author_id' => $admin->id,
            'scope' => 'school',
            'title' => 'Notice 2',
            'body' => 'Body 2',
            'published_at' => date('Y-m-d H:i:s', strtotime('-2 hours')),
        ]);

        $userContext = UserContext::fromUser($admin);
        $this->assertEquals(2, $this->announcementRepo->getUnreadCount($userContext));

        $mockAuth = $this->createMock(AuthenticatorInterface::class);
        $mockAuth->method('user')->willReturn($userContext);

        $controller = new NotificationController($mockAuth, $this->announcementRepo, $this->userRepo, $this->parentRepo);
        $request = new Request(
            queryParams: [],
            postParams: ['redirect_to' => '/notifications'],
            serverParams: ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/notifications/read-all']
        );

        $response = $controller->markAllAsRead($request);

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('/notifications', $response->getHeader('Location'));

        // Unread count should now be 0
        $this->assertEquals(0, $this->announcementRepo->getUnreadCount($userContext));
    }

    public function testParentRoleLoadsLinkedChildrenInView(): void
    {
        $parentUser = $this->createUser('Guardian Parent', 'parent@claret.edu', ['parent']);
        $stmt = $this->pdo->prepare("INSERT INTO `parents` (`user_id`, `address`, `created_at`, `updated_at`) VALUES (?, 'Owerri', datetime('now'), datetime('now'))");
        $stmt->execute([$parentUser->id]);
        $parentId = (int)$this->pdo->lastInsertId();

        $studentUser = $this->createUser('Child Student', 'child@claret.edu', ['student']);
        $stmt = $this->pdo->prepare("INSERT INTO `students` (`user_id`, `admission_number`, `status`, `created_at`, `updated_at`) VALUES (?, 'STD/2026/011', 'active', datetime('now'), datetime('now'))");
        $stmt->execute([$studentUser->id]);
        $studentId = (int)$this->pdo->lastInsertId();

        $this->pdo->exec("INSERT INTO `parent_student` (`parent_id`, `student_id`, `relationship_type`, `is_primary`, `created_at`) VALUES ({$parentId}, {$studentId}, 'Father', 1, datetime('now'))");

        $mockAuth = $this->createMock(AuthenticatorInterface::class);
        $mockAuth->method('user')->willReturn(UserContext::fromUser($parentUser));

        $controller = new NotificationController($mockAuth, $this->announcementRepo, $this->userRepo, $this->parentRepo);
        $request = new Request([], [], ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/notifications']);

        $response = $controller->index($request);

        $this->assertEquals(200, $response->getStatusCode());
        $content = $response->getContent();
        $this->assertStringContainsString('Child Student', $content);
    }
}
