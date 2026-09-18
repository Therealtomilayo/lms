<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Controllers\Parent\ChildController;
use App\Controllers\Student\BadgeController as StudentBadgeController;
use App\Controllers\Student\DiscussionController as StudentDiscussionController;
use App\Controllers\Teacher\BadgeController as TeacherBadgeController;
use App\Controllers\Teacher\DiscussionController as TeacherDiscussionController;
use App\Core\AuthenticatorInterface;
use App\Core\Exceptions\AuthorizationException;
use App\Core\Exceptions\DomainRuleException;
use App\Core\Exceptions\ResourceNotFoundException;
use App\Core\Exceptions\ValidationException;
use App\Core\Request;
use App\Core\Response;
use App\Core\UserContext;
use App\Models\User;
use App\Repositories\AcademicRepository;
use App\Repositories\BadgeRepository;
use App\Repositories\DiscussionRepository;
use App\Repositories\EnrollmentRepository;
use App\Repositories\ParentRepository;
use App\Repositories\StudentRepository;
use App\Repositories\TeacherRepository;
use App\Repositories\UserRepository;
use App\Services\BadgeService;
use App\Services\DiscussionService;
use App\Services\ParentService;
use PDO;
use PHPUnit\Framework\TestCase;

/**
 * Integration Tests for Phase 3: Badges & Rewards Gamification (SRS §33) and Class Discussions (SRS §47)
 */
final class GamificationAndDiscussionIntegrationTest extends TestCase
{
    private PDO $pdo;
    private UserRepository $userRepo;
    private StudentRepository $studentRepo;
    private TeacherRepository $teacherRepo;
    private ParentRepository $parentRepo;
    private AcademicRepository $academicRepo;
    private EnrollmentRepository $enrollmentRepo;
    private BadgeRepository $badgeRepo;
    private DiscussionRepository $discussionRepo;

    private BadgeService $badgeService;
    private DiscussionService $discussionService;
    private ParentService $parentService;

    private User $teacherUser;
    private User $otherTeacherUser;
    private User $studentUserA;
    private User $studentUserB;
    private User $parentUser;
    private User $adminUser;

    private int $teacherId;
    private int $otherTeacherId;
    private int $studentIdA;
    private int $studentIdB;
    private int $parentId;
    private int $classSubjectId;
    private int $otherClassSubjectId;

    protected function setUp(): void
    {
        parent::setUp();

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        $_SESSION = [];

        $this->pdo = new PDO('sqlite::memory:', null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        $this->createTables();
        $this->initializeServices();
        $this->seedData();
        $this->badgeRepo->ensureDefaultBadgesExist();
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
        parent::tearDown();
    }

    private function createTables(): void
    {
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
                UNIQUE(`user_id`, `role`)
            );

            CREATE TABLE `sessions` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `name` VARCHAR(100) NOT NULL,
                `start_date` DATE NOT NULL,
                `end_date` DATE NOT NULL,
                `is_current` INTEGER NOT NULL DEFAULT 0,
                `status` VARCHAR(20) NOT NULL DEFAULT 'active',
                `created_at` DATETIME NOT NULL,
                `updated_at` DATETIME NOT NULL
            );

            CREATE TABLE `academic_sessions` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `name` VARCHAR(100) NOT NULL,
                `start_date` DATE NOT NULL,
                `end_date` DATE NOT NULL,
                `is_current` INTEGER NOT NULL DEFAULT 0,
                `status` VARCHAR(20) NOT NULL DEFAULT 'active',
                `created_at` DATETIME NOT NULL,
                `updated_at` DATETIME NOT NULL
            );

            CREATE TABLE `terms` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `session_id` INTEGER NOT NULL DEFAULT 1,
                `academic_session_id` INTEGER NOT NULL DEFAULT 1,
                `name` VARCHAR(50) NOT NULL,
                `start_date` DATE NOT NULL,
                `end_date` DATE NOT NULL,
                `is_current` INTEGER NOT NULL DEFAULT 0,
                `status` VARCHAR(20) NOT NULL DEFAULT 'active',
                `created_at` DATETIME NOT NULL,
                `updated_at` DATETIME NOT NULL
            );

            CREATE TABLE `academic_levels` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `name` VARCHAR(50) NOT NULL,
                `slug` VARCHAR(50) NOT NULL UNIQUE,
                `created_at` DATETIME NOT NULL,
                `updated_at` DATETIME NOT NULL
            );

            CREATE TABLE `classes` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `academic_level_id` INTEGER NOT NULL,
                `name` VARCHAR(50) NOT NULL,
                `section_arm` VARCHAR(20) NULL,
                `status` VARCHAR(20) NOT NULL DEFAULT 'active',
                `created_at` DATETIME NOT NULL,
                `updated_at` DATETIME NOT NULL
            );

            CREATE TABLE `subjects` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `name` VARCHAR(100) NOT NULL,
                `code` VARCHAR(20) NOT NULL UNIQUE,
                `status` VARCHAR(20) NOT NULL DEFAULT 'active',
                `created_at` DATETIME NOT NULL,
                `updated_at` DATETIME NOT NULL
            );

            CREATE TABLE `class_subjects` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `class_id` INTEGER NOT NULL,
                `subject_id` INTEGER NOT NULL,
                `teacher_id` INTEGER NULL,
                `session_id` INTEGER NOT NULL DEFAULT 1,
                `academic_session_id` INTEGER NOT NULL DEFAULT 1,
                `status` VARCHAR(20) NOT NULL DEFAULT 'active',
                `created_at` DATETIME NOT NULL,
                `updated_at` DATETIME NOT NULL
            );

            CREATE TABLE `students` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `user_id` INTEGER NOT NULL UNIQUE,
                `admission_number` VARCHAR(50) NOT NULL UNIQUE,
                `current_class_id` INTEGER NOT NULL,
                `status` VARCHAR(20) NOT NULL DEFAULT 'active',
                `created_at` DATETIME NOT NULL,
                `updated_at` DATETIME NOT NULL
            );

            CREATE TABLE `teachers` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `user_id` INTEGER NOT NULL UNIQUE,
                `employee_id` VARCHAR(50) NOT NULL UNIQUE,
                `staff_id` VARCHAR(50) NULL,
                `status` VARCHAR(20) NOT NULL DEFAULT 'active',
                `created_at` DATETIME NOT NULL,
                `updated_at` DATETIME NOT NULL
            );

            CREATE TABLE `parents` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `user_id` INTEGER NOT NULL UNIQUE,
                `relationship` VARCHAR(50) NOT NULL DEFAULT 'parent',
                `status` VARCHAR(20) NOT NULL DEFAULT 'active',
                `created_at` DATETIME NOT NULL,
                `updated_at` DATETIME NOT NULL
            );

            CREATE TABLE `parent_student` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `parent_id` INTEGER NOT NULL,
                `student_id` INTEGER NOT NULL,
                `relationship` VARCHAR(50) NOT NULL DEFAULT 'parent',
                `relationship_type` VARCHAR(50) NOT NULL DEFAULT 'father',
                `is_primary` INTEGER NOT NULL DEFAULT 1,
                `created_at` DATETIME NOT NULL,
                UNIQUE(`parent_id`, `student_id`)
            );

            CREATE TABLE `student_subject_enrollments` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `student_id` INTEGER NOT NULL,
                `class_subject_id` INTEGER NOT NULL,
                `session_id` INTEGER NOT NULL DEFAULT 1,
                `academic_session_id` INTEGER NOT NULL DEFAULT 1,
                `status` VARCHAR(20) NOT NULL DEFAULT 'active',
                `created_at` DATETIME NOT NULL,
                `updated_at` DATETIME NOT NULL
            );

            CREATE TABLE `badges` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `name` VARCHAR(100) NOT NULL,
                `slug` VARCHAR(100) NOT NULL UNIQUE,
                `description` TEXT NOT NULL,
                `category` VARCHAR(50) NOT NULL DEFAULT 'academic',
                `icon_name` VARCHAR(50) NOT NULL DEFAULT 'award',
                `color_scheme` VARCHAR(50) NOT NULL DEFAULT 'brand',
                `is_system` INTEGER NOT NULL DEFAULT 1,
                `created_at` DATETIME NOT NULL
            );

            CREATE TABLE `student_badges` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `student_id` INTEGER NOT NULL,
                `badge_id` INTEGER NOT NULL,
                `awarded_by` INTEGER NOT NULL,
                `reason` TEXT NOT NULL,
                `class_subject_id` INTEGER NULL,
                `session_id` INTEGER NULL,
                `term_id` INTEGER NULL,
                `awarded_at` DATETIME NOT NULL
            );

            CREATE TABLE `class_discussions` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `class_subject_id` INTEGER NOT NULL,
                `user_id` INTEGER NOT NULL,
                `title` VARCHAR(255) NOT NULL,
                `content` TEXT NOT NULL,
                `is_pinned` INTEGER NOT NULL DEFAULT 0,
                `is_locked` INTEGER NOT NULL DEFAULT 0,
                `reply_count` INTEGER NOT NULL DEFAULT 0,
                `last_reply_at` DATETIME NULL,
                `created_at` DATETIME NOT NULL,
                `updated_at` DATETIME NOT NULL
            );

            CREATE TABLE `class_discussion_replies` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `discussion_id` INTEGER NOT NULL,
                `user_id` INTEGER NOT NULL,
                `content` TEXT NOT NULL,
                `created_at` DATETIME NOT NULL,
                `updated_at` DATETIME NOT NULL
            );
        ");
    }

    private function initializeServices(): void
    {
        $this->userRepo = new UserRepository($this->pdo);
        $this->studentRepo = new StudentRepository($this->pdo);
        $this->teacherRepo = new TeacherRepository($this->pdo);
        $this->parentRepo = new ParentRepository($this->pdo);
        $this->academicRepo = new AcademicRepository($this->pdo);
        $this->enrollmentRepo = new EnrollmentRepository($this->pdo);
        $this->badgeRepo = new BadgeRepository($this->pdo);
        $this->discussionRepo = new DiscussionRepository($this->pdo);

        $this->badgeService = new BadgeService(
            $this->badgeRepo,
            $this->studentRepo,
            $this->teacherRepo,
            $this->academicRepo,
            $this->enrollmentRepo
        );
        $this->discussionService = new DiscussionService(
            $this->discussionRepo,
            $this->academicRepo,
            $this->teacherRepo,
            $this->studentRepo,
            $this->parentRepo
        );
        $this->parentService = new ParentService(
            parentRepo: $this->parentRepo,
            studentRepo: $this->studentRepo,
            academicRepo: $this->academicRepo,
            enrollmentRepo: $this->enrollmentRepo
        );
    }

    private function seedData(): void
    {
        $now = date('Y-m-d H:i:s');

        // Sessions and Terms
        $this->pdo->exec("INSERT INTO `sessions` (`id`, `name`, `start_date`, `end_date`, `is_current`, `status`, `created_at`, `updated_at`)
            VALUES (1, '2026/2027 Academic Year', '2026-09-01', '2027-07-31', 1, 'active', '{$now}', '{$now}')");
        $this->pdo->exec("INSERT INTO `academic_sessions` (`id`, `name`, `start_date`, `end_date`, `is_current`, `status`, `created_at`, `updated_at`)
            VALUES (1, '2026/2027 Academic Year', '2026-09-01', '2027-07-31', 1, 'active', '{$now}', '{$now}')");
        $this->pdo->exec("INSERT INTO `terms` (`id`, `session_id`, `academic_session_id`, `name`, `start_date`, `end_date`, `is_current`, `status`, `created_at`, `updated_at`)
            VALUES (1, 1, 1, 'First Term', '2026-09-01', '2026-12-18', 1, 'active', '{$now}', '{$now}')");

        // Levels, Classes, Subjects
        $this->pdo->exec("INSERT INTO `academic_levels` (`id`, `name`, `slug`, `created_at`, `updated_at`)
            VALUES (1, 'Junior Secondary', 'junior-secondary', '{$now}', '{$now}')");
        $this->pdo->exec("INSERT INTO `classes` (`id`, `academic_level_id`, `name`, `section_arm`, `status`, `created_at`, `updated_at`)
            VALUES (1, 1, 'JSS 1', 'Gold', 'active', '{$now}', '{$now}')");
        $this->pdo->exec("INSERT INTO `subjects` (`id`, `name`, `code`, `status`, `created_at`, `updated_at`)
            VALUES (1, 'Basic Science', 'SCI101', 'active', '{$now}', '{$now}')");
        $this->pdo->exec("INSERT INTO `subjects` (`id`, `name`, `code`, `status`, `created_at`, `updated_at`)
            VALUES (2, 'Mathematics', 'MTH101', 'active', '{$now}', '{$now}')");

        // Users & Roles
        // Teacher
        $this->pdo->exec("INSERT INTO `users` (`id`, `uuid`, `name`, `email`, `phone`, `password_hash`, `status`, `created_at`, `updated_at`)
            VALUES (1, 'u-teach-1', 'Dr. Victor Frank', 'teacher@claret.edu', '123', 'hash', 'active', '{$now}', '{$now}')");
        $this->pdo->exec("INSERT INTO `user_roles` (`user_id`, `role`, `created_at`) VALUES (1, 'teacher', '{$now}')");
        $this->pdo->exec("INSERT INTO `teachers` (`id`, `user_id`, `employee_id`, `staff_id`, `status`, `created_at`, `updated_at`)
            VALUES (1, 1, 'EMP001', 'STF001', 'active', '{$now}', '{$now}')");
        $this->teacherId = 1;

        // Other Teacher
        $this->pdo->exec("INSERT INTO `users` (`id`, `uuid`, `name`, `email`, `phone`, `password_hash`, `status`, `created_at`, `updated_at`)
            VALUES (2, 'u-teach-2', 'Mrs. Linda Cole', 'linda@claret.edu', '124', 'hash', 'active', '{$now}', '{$now}')");
        $this->pdo->exec("INSERT INTO `user_roles` (`user_id`, `role`, `created_at`) VALUES (2, 'teacher', '{$now}')");
        $this->pdo->exec("INSERT INTO `teachers` (`id`, `user_id`, `employee_id`, `staff_id`, `status`, `created_at`, `updated_at`)
            VALUES (2, 2, 'EMP002', 'STF002', 'active', '{$now}', '{$now}')");
        $this->otherTeacherId = 2;

        // Student A
        $this->pdo->exec("INSERT INTO `users` (`id`, `uuid`, `name`, `email`, `phone`, `password_hash`, `status`, `created_at`, `updated_at`)
            VALUES (3, 'u-stud-1', 'David Chidi', 'david@student.claret.edu', '125', 'hash', 'active', '{$now}', '{$now}')");
        $this->pdo->exec("INSERT INTO `user_roles` (`user_id`, `role`, `created_at`) VALUES (3, 'student', '{$now}')");
        $this->pdo->exec("INSERT INTO `students` (`id`, `user_id`, `admission_number`, `current_class_id`, `status`, `created_at`, `updated_at`)
            VALUES (1, 3, 'ADM-001', 1, 'active', '{$now}', '{$now}')");
        $this->studentIdA = 1;

        // Student B
        $this->pdo->exec("INSERT INTO `users` (`id`, `uuid`, `name`, `email`, `phone`, `password_hash`, `status`, `created_at`, `updated_at`)
            VALUES (4, 'u-stud-2', 'Grace Obi', 'grace@student.claret.edu', '126', 'hash', 'active', '{$now}', '{$now}')");
        $this->pdo->exec("INSERT INTO `user_roles` (`user_id`, `role`, `created_at`) VALUES (4, 'student', '{$now}')");
        $this->pdo->exec("INSERT INTO `students` (`id`, `user_id`, `admission_number`, `current_class_id`, `status`, `created_at`, `updated_at`)
            VALUES (2, 4, 'ADM-002', 1, 'active', '{$now}', '{$now}')");
        $this->studentIdB = 2;

        // Parent
        $this->pdo->exec("INSERT INTO `users` (`id`, `uuid`, `name`, `email`, `phone`, `password_hash`, `status`, `created_at`, `updated_at`)
            VALUES (5, 'u-par-1', 'Chief Chidi', 'chief@parent.claret.edu', '127', 'hash', 'active', '{$now}', '{$now}')");
        $this->pdo->exec("INSERT INTO `user_roles` (`user_id`, `role`, `created_at`) VALUES (5, 'parent', '{$now}')");
        $this->pdo->exec("INSERT INTO `parents` (`id`, `user_id`, `relationship`, `status`, `created_at`, `updated_at`)
            VALUES (1, 5, 'father', 'active', '{$now}', '{$now}')");
        $this->parentId = 1;

        // Link Parent to Student A only
        $this->pdo->exec("INSERT INTO `parent_student` (`parent_id`, `student_id`, `relationship`, `relationship_type`, `is_primary`, `created_at`)
            VALUES (1, 1, 'father', 'father', 1, '{$now}')");

        // Admin User
        $this->pdo->exec("INSERT INTO `users` (`id`, `uuid`, `name`, `email`, `phone`, `password_hash`, `status`, `created_at`, `updated_at`)
            VALUES (6, 'u-admin-1', 'Admin Officer', 'admin@claret.edu', '128', 'hash', 'active', '{$now}', '{$now}')");
        $this->pdo->exec("INSERT INTO `user_roles` (`user_id`, `role`, `created_at`) VALUES (6, 'admin', '{$now}')");

        // Class Subjects:
        // classSubjectId = 1 (Teacher 1 -> Basic Science)
        $this->pdo->exec("INSERT INTO `class_subjects` (`id`, `class_id`, `subject_id`, `teacher_id`, `session_id`, `academic_session_id`, `status`, `created_at`, `updated_at`)
            VALUES (1, 1, 1, 1, 1, 1, 'active', '{$now}', '{$now}')");
        $this->classSubjectId = 1;

        // otherClassSubjectId = 2 (Teacher 2 -> Mathematics)
        $this->pdo->exec("INSERT INTO `class_subjects` (`id`, `class_id`, `subject_id`, `teacher_id`, `session_id`, `academic_session_id`, `status`, `created_at`, `updated_at`)
            VALUES (2, 1, 2, 2, 1, 1, 'active', '{$now}', '{$now}')");
        $this->otherClassSubjectId = 2;

        // Enroll Student A in Class Subject 1
        $this->pdo->exec("INSERT INTO `student_subject_enrollments` (`id`, `student_id`, `class_subject_id`, `session_id`, `academic_session_id`, `status`, `created_at`, `updated_at`)
            VALUES (1, 1, 1, 1, 1, 'active', '{$now}', '{$now}')");

        // Enroll Student B in Class Subject 2 only
        $this->pdo->exec("INSERT INTO `student_subject_enrollments` (`id`, `student_id`, `class_subject_id`, `session_id`, `academic_session_id`, `status`, `created_at`, `updated_at`)
            VALUES (2, 2, 2, 1, 1, 'active', '{$now}', '{$now}')");

        // Instantiate User objects for UserContext
        $this->teacherUser = $this->userRepo->findById(1);
        $this->otherTeacherUser = $this->userRepo->findById(2);
        $this->studentUserA = $this->userRepo->findById(3);
        $this->studentUserB = $this->userRepo->findById(4);
        $this->parentUser = $this->userRepo->findById(5);
        $this->adminUser = $this->userRepo->findById(6);
    }

    private function makeUserContext(User $user): UserContext
    {
        return UserContext::fromUser($user);
    }

    private function makeAuthMock(?UserContext $context): AuthenticatorInterface
    {
        $mock = $this->createMock(AuthenticatorInterface::class);
        $mock->method('user')->willReturn($context);
        $mock->method('getUserContext')->willReturn($context);
        $mock->method('authenticate')->willReturn($context);
        $mock->method('check')->willReturn($context !== null);
        return $mock;
    }

    // =========================================================================
    // 1. BADGES & REWARDS GAMIFICATION TESTS (SRS §33, §57 Phase 3)
    // =========================================================================

    public function testDefaultBadgesSeededAndRetrievable(): void
    {
        $badges = $this->badgeService->getAllBadges();
        $this->assertNotEmpty($badges);
        $this->assertGreaterThanOrEqual(6, count($badges));

        $slugs = array_map(fn($b) => $b->slug, $badges);
        $this->assertContains('perfect-score', $slugs);
        $this->assertContains('course-mastery', $slugs);
        $this->assertContains('top-scholar', $slugs);
    }

    public function testTeacherCanAwardBadgeToEnrolledStudent(): void
    {
        $teacherContext = $this->makeUserContext($this->teacherUser);
        $badge = $this->badgeRepo->findBySlug('top-scholar');
        $this->assertNotNull($badge);

        $awardedBadge = $this->badgeService->awardBadge(
            studentId: $this->studentIdA,
            badgeId: $badge->id,
            reason: 'Consistently outstanding critical thinking in Basic Science experiments.',
            actor: $teacherContext,
            classSubjectId: $this->classSubjectId
        );

        $this->assertInstanceOf(\App\Models\StudentBadge::class, $awardedBadge);
        $this->assertGreaterThan(0, $awardedBadge->id);

        $studentBadges = $this->badgeService->getStudentBadges($this->studentIdA);
        $this->assertCount(1, $studentBadges);
        $this->assertSame($badge->name, $studentBadges[0]->badge->name);
        $this->assertSame('Consistently outstanding critical thinking in Basic Science experiments.', $studentBadges[0]->reason);
        $this->assertSame($this->teacherUser->id, $studentBadges[0]->awardedBy);
    }

    public function testAwardBadgeRejectsDuplicateAwardForSameStudentAndSubject(): void
    {
        $teacherContext = $this->makeUserContext($this->teacherUser);
        $badge = $this->badgeRepo->findBySlug('top-scholar');

        $this->badgeService->awardBadge(
            studentId: $this->studentIdA,
            badgeId: $badge->id,
            reason: 'First award.',
            actor: $teacherContext,
            classSubjectId: $this->classSubjectId
        );

        $this->expectException(ValidationException::class);
        $this->badgeService->awardBadge(
            studentId: $this->studentIdA,
            badgeId: $badge->id,
            reason: 'Duplicate award attempt.',
            actor: $teacherContext,
            classSubjectId: $this->classSubjectId
        );
    }

    public function testAwardBadgeRejectsEmptyReason(): void
    {
        $teacherContext = $this->makeUserContext($this->teacherUser);
        $badge = $this->badgeRepo->findBySlug('top-scholar');

        $this->expectException(ValidationException::class);
        $this->badgeService->awardBadge(
            studentId: $this->studentIdA,
            badgeId: $badge->id,
            reason: '   ',
            actor: $teacherContext,
            classSubjectId: $this->classSubjectId
        );
    }

    public function testAwardBadgeRejectsNonExistentStudent(): void
    {
        $teacherContext = $this->makeUserContext($this->teacherUser);
        $badge = $this->badgeRepo->findBySlug('top-scholar');

        $this->expectException(ResourceNotFoundException::class);
        $this->badgeService->awardBadge(
            studentId: 9999,
            badgeId: $badge->id,
            reason: 'Great job',
            actor: $teacherContext,
            classSubjectId: $this->classSubjectId
        );
    }

    public function testAwardBadgeRejectsUnauthorizedActor(): void
    {
        $studentContext = $this->makeUserContext($this->studentUserA);
        $badge = $this->badgeRepo->findBySlug('top-scholar');

        $this->expectException(AuthorizationException::class);
        $this->badgeService->awardBadge(
            studentId: $this->studentIdA,
            badgeId: $badge->id,
            reason: 'Awarding self',
            actor: $studentContext,
            classSubjectId: $this->classSubjectId
        );
    }

    public function testStudentBadgeControllerIndexReturns200(): void
    {
        $studentContext = $this->makeUserContext($this->studentUserA);
        $authMock = $this->makeAuthMock($studentContext);

        $controller = new StudentBadgeController(
            authenticator: $authMock,
            badgeService: $this->badgeService,
            studentRepo: $this->studentRepo,
            academicRepo: $this->academicRepo
        );

        $request = new Request([], [], ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/student/badges']);
        $request->setAttribute('user_context', $studentContext);
        $response = $controller->index($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString('Honors & Achievement Badges', $response->getBody());
    }

    public function testTeacherBadgeControllerAwardActionSuccess(): void
    {
        $teacherContext = $this->makeUserContext($this->teacherUser);
        $authMock = $this->makeAuthMock($teacherContext);
        $badge = $this->badgeRepo->findBySlug('punctuality-star');

        $controller = new TeacherBadgeController(
            authenticator: $authMock,
            badgeService: $this->badgeService,
            academicRepo: $this->academicRepo,
            teacherRepo: $this->teacherRepo,
            studentRepo: $this->studentRepo
        );

        $request = new Request(
            [],
            [
                'student_id' => (string)$this->studentIdA,
                'badge_id' => (string)$badge->id,
                'class_subject_id' => (string)$this->classSubjectId,
                'reason' => 'Zero tardiness and prompt class submissions throughout the month.',
            ],
            ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/teacher/badges/award']
        );
        $request->setAttribute('user_context', $teacherContext);

        $response = $controller->award($request);

        $this->assertTrue($response->isRedirect());
        $this->assertStringContainsString('/teacher/badges', $response->getHeader('Location'));
    }

    public function testParentCanViewChildBadgesAndRejectsUnlinkedChild(): void
    {
        // First award a badge to student A
        $teacherContext = $this->makeUserContext($this->teacherUser);
        $badge = $this->badgeRepo->findBySlug('top-scholar');
        $this->badgeService->awardBadge(
            studentId: $this->studentIdA,
            badgeId: $badge->id,
            reason: 'Outstanding performance',
            actor: $teacherContext,
            classSubjectId: $this->classSubjectId
        );

        $parentContext = $this->makeUserContext($this->parentUser);
        $authMock = $this->makeAuthMock($parentContext);

        $controller = new ChildController(
            authenticator: $authMock,
            parentService: $this->parentService,
            badgeService: $this->badgeService,
            discussionService: $this->discussionService,
            academicRepo: $this->academicRepo
        );

        // 1. Authorized linked child (Student A)
        $requestA = new Request([], [], ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => "/parent/children/{$this->studentIdA}/badges"]);
        $requestA->setAttribute('user_context', $parentContext);
        $responseA = $controller->badges($requestA, ['studentId' => (string)$this->studentIdA]);
        $this->assertSame(200, $responseA->getStatusCode());
        $this->assertStringContainsString('Badges & Honors', $responseA->getBody());
        $this->assertStringContainsString('Top Scholar', $responseA->getBody());

        // 2. Unauthorized unlinked child (Student B)
        $requestB = new Request([], [], ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => "/parent/children/{$this->studentIdB}/badges"]);
        $requestB->setAttribute('user_context', $parentContext);
        $responseB = $controller->badges($requestB, ['studentId' => (string)$this->studentIdB]);
        $this->assertSame(403, $responseB->getStatusCode());
    }

    // =========================================================================
    // 2. CLASS GROUP DISCUSSIONS TESTS (SRS §47, §57 Phase 3)
    // =========================================================================

    public function testTeacherCanCreateDiscussionTopic(): void
    {
        $teacherContext = $this->makeUserContext($this->teacherUser);

        $discId = $this->discussionService->createDiscussion(
            classSubjectId: $this->classSubjectId,
            title: 'Week 3 Group Reflection on Plant Nutrition',
            content: 'Please summarize photosynthesis vs chemosynthesis before Friday.',
            actor: $teacherContext
        );

        $this->assertGreaterThan(0, $discId);

        $thread = $this->discussionService->getDiscussionThread($discId, $teacherContext);
        $this->assertSame('Week 3 Group Reflection on Plant Nutrition', $thread['discussion']->title);
        $this->assertSame(0, $thread['discussion']->replyCount);
        $this->assertTrue($thread['can_moderate']);
        $this->assertTrue($thread['can_reply']);
    }

    public function testEnrolledStudentCanCreateDiscussionTopic(): void
    {
        $studentContext = $this->makeUserContext($this->studentUserA);

        $discId = $this->discussionService->createDiscussion(
            classSubjectId: $this->classSubjectId,
            title: 'Question regarding Question 4 on Workbook',
            content: 'Is equation balanced before calculating molar masses?',
            actor: $studentContext
        );

        $this->assertGreaterThan(0, $discId);

        $thread = $this->discussionService->getDiscussionThread($discId, $studentContext);
        $this->assertSame('Question regarding Question 4 on Workbook', $thread['discussion']->title);
        $this->assertFalse($thread['can_moderate']); // Students cannot moderate
        $this->assertTrue($thread['can_reply']);
    }

    public function testStudentCannotCreateDiscussionInUnenrolledSubject(): void
    {
        // Student B is enrolled in Class Subject 2, NOT Class Subject 1
        $studentContext = $this->makeUserContext($this->studentUserB);

        $this->expectException(AuthorizationException::class);
        $this->discussionService->createDiscussion(
            classSubjectId: $this->classSubjectId,
            title: 'Unauthorized Question',
            content: 'Should fail',
            actor: $studentContext
        );
    }

    public function testDiscussionValidationRejectsEmptyFields(): void
    {
        $teacherContext = $this->makeUserContext($this->teacherUser);

        $this->expectException(ValidationException::class);
        $this->discussionService->createDiscussion(
            classSubjectId: $this->classSubjectId,
            title: '   ',
            content: 'Content',
            actor: $teacherContext
        );
    }

    public function testTeacherAndStudentCanReplyToDiscussionTopic(): void
    {
        $teacherContext = $this->makeUserContext($this->teacherUser);
        $studentContext = $this->makeUserContext($this->studentUserA);

        $discId = $this->discussionService->createDiscussion(
            classSubjectId: $this->classSubjectId,
            title: 'Lab Requirements',
            content: 'Bring lab coats on Wednesday.',
            actor: $teacherContext
        );

        // Student A replies
        $replyId1 = $this->discussionService->addReply(
            discussionId: $discId,
            content: 'Will safety goggles also be required?',
            actor: $studentContext
        );
        $this->assertGreaterThan(0, $replyId1);

        // Teacher replies back
        $replyId2 = $this->discussionService->addReply(
            discussionId: $discId,
            content: 'Yes, safety goggles are compulsory.',
            actor: $teacherContext
        );
        $this->assertGreaterThan(0, $replyId2);

        $thread = $this->discussionService->getDiscussionThread($discId, $teacherContext);
        $this->assertSame(2, $thread['discussion']->replyCount);
        $this->assertCount(2, $thread['discussion']->replies);
        $this->assertSame('Will safety goggles also be required?', $thread['discussion']->replies[0]->content);
        $this->assertSame('Yes, safety goggles are compulsory.', $thread['discussion']->replies[1]->content);
    }

    public function testTeacherCanPinAndLockTopicAndPreventReplies(): void
    {
        $teacherContext = $this->makeUserContext($this->teacherUser);
        $studentContext = $this->makeUserContext($this->studentUserA);

        $discId = $this->discussionService->createDiscussion(
            classSubjectId: $this->classSubjectId,
            title: 'Announcement: Term Examination Schedule',
            content: 'Exams start on Monday.',
            actor: $teacherContext
        );

        // Teacher pins topic
        $this->discussionService->togglePin($discId, true, $teacherContext);
        $thread = $this->discussionService->getDiscussionThread($discId, $teacherContext);
        $this->assertTrue($thread['discussion']->isPinned);

        // Teacher locks topic
        $this->discussionService->toggleLock($discId, true, $teacherContext);
        $threadLocked = $this->discussionService->getDiscussionThread($discId, $studentContext);
        $this->assertTrue($threadLocked['discussion']->isLocked);
        $this->assertFalse($threadLocked['can_reply']); // Student cannot reply when locked

        // Student attempt to reply must fail with DomainRuleException
        $this->expectException(DomainRuleException::class);
        $this->discussionService->addReply(
            discussionId: $discId,
            content: 'Attempted reply on locked topic',
            actor: $studentContext
        );
    }

    public function testStudentCannotModeratePinLockOrDelete(): void
    {
        $teacherContext = $this->makeUserContext($this->teacherUser);
        $studentContext = $this->makeUserContext($this->studentUserA);

        $discId = $this->discussionService->createDiscussion(
            classSubjectId: $this->classSubjectId,
            title: 'Topic',
            content: 'Content',
            actor: $teacherContext
        );

        // Student attempt to pin
        try {
            $this->discussionService->togglePin($discId, true, $studentContext);
            $this->fail('Student pin should throw AuthorizationException');
        } catch (AuthorizationException) {
            $this->assertTrue(true);
        }

        // Student attempt to delete
        try {
            $this->discussionService->deleteDiscussion($discId, $studentContext);
            $this->fail('Student delete should throw AuthorizationException');
        } catch (AuthorizationException) {
            $this->assertTrue(true);
        }
    }

    public function testTeacherCanDeleteDiscussionAndRepliesAreCascadeDeleted(): void
    {
        $teacherContext = $this->makeUserContext($this->teacherUser);
        $studentContext = $this->makeUserContext($this->studentUserA);

        $discId = $this->discussionService->createDiscussion(
            classSubjectId: $this->classSubjectId,
            title: 'To Be Deleted',
            content: 'Some prompt',
            actor: $teacherContext
        );

        $this->discussionService->addReply($discId, 'A reply here', $studentContext);

        // Delete topic
        $deleted = $this->discussionService->deleteDiscussion($discId, $teacherContext);
        $this->assertTrue($deleted);

        // Verify discussion no longer exists
        $this->expectException(ResourceNotFoundException::class);
        $this->discussionService->getDiscussionThread($discId, $teacherContext);
    }

    public function testTeacherDiscussionControllerActions(): void
    {
        $teacherContext = $this->makeUserContext($this->teacherUser);
        $authMock = $this->makeAuthMock($teacherContext);

        $controller = new TeacherDiscussionController(
            authenticator: $authMock,
            discussionService: $this->discussionService,
            academicRepo: $this->academicRepo,
            teacherRepo: $this->teacherRepo
        );

        // 1. Index
        $requestIndex = new Request([], [], ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => "/teacher/subjects/{$this->classSubjectId}/discussions"]);
        $requestIndex->setAttribute('user_context', $teacherContext);
        $responseIndex = $controller->index($requestIndex, $this->classSubjectId);
        $this->assertSame(200, $responseIndex->getStatusCode());

        // 2. Store
        $requestStore = new Request(
            [],
            ['title' => 'Controller Test Topic', 'content' => 'Test content details'],
            ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => "/teacher/subjects/{$this->classSubjectId}/discussions"]
        );
        $requestStore->setAttribute('user_context', $teacherContext);
        $responseStore = $controller->store($requestStore, $this->classSubjectId);
        $this->assertTrue($responseStore->isRedirect());
    }

    public function testStudentDiscussionControllerActions(): void
    {
        $studentContext = $this->makeUserContext($this->studentUserA);
        $authMock = $this->makeAuthMock($studentContext);

        $controller = new StudentDiscussionController(
            authenticator: $authMock,
            discussionService: $this->discussionService,
            studentRepo: $this->studentRepo,
            academicRepo: $this->academicRepo
        );

        // 1. Index
        $requestIndex = new Request([], [], ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => "/student/subjects/{$this->classSubjectId}/discussions"]);
        $requestIndex->setAttribute('user_context', $studentContext);
        $responseIndex = $controller->index($requestIndex, $this->classSubjectId);
        $this->assertSame(200, $responseIndex->getStatusCode());
        $this->assertStringContainsString('Class Discussions', $responseIndex->getBody());
    }

    public function testParentCanViewChildDiscussionsFeedReadOnly(): void
    {
        // Post a topic in Student A's class subject
        $teacherContext = $this->makeUserContext($this->teacherUser);
        $this->discussionService->createDiscussion(
            classSubjectId: $this->classSubjectId,
            title: 'Parent Visible Discussion Topic',
            content: 'Important revision material.',
            actor: $teacherContext
        );

        $parentContext = $this->makeUserContext($this->parentUser);
        $authMock = $this->makeAuthMock($parentContext);

        $controller = new ChildController(
            authenticator: $authMock,
            parentService: $this->parentService,
            badgeService: $this->badgeService,
            discussionService: $this->discussionService,
            academicRepo: $this->academicRepo
        );

        $request = new Request(
            ['class_subject_id' => (string)$this->classSubjectId],
            [],
            ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => "/parent/children/{$this->studentIdA}/discussions"]
        );
        $request->setAttribute('user_context', $parentContext);

        $response = $controller->discussions($request, ['studentId' => (string)$this->studentIdA]);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString('Class Discussions', $response->getBody());
        $this->assertStringContainsString('Parent Visible Discussion Topic', $response->getBody());
    }
}
