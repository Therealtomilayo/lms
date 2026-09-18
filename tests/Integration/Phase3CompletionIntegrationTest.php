<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Controllers\Admin\AttendanceController as AdminAttendanceController;
use App\Controllers\Admin\BadgeController as AdminBadgeController;
use App\Controllers\Admin\DiscussionController as AdminDiscussionController;
use App\Controllers\Student\DiscussionController as StudentDiscussionController;
use App\Core\AuthenticatorInterface;
use App\Core\Exceptions\AuthorizationException;
use App\Core\Exceptions\ValidationException;
use App\Core\Request;
use App\Core\Response;
use App\Core\UserContext;
use App\Models\User;
use App\Repositories\AcademicRepository;
use App\Repositories\AttendanceRepository;
use App\Repositories\BadgeRepository;
use App\Repositories\DiscussionRepository;
use App\Repositories\ParentRepository;
use App\Repositories\StudentRepository;
use App\Repositories\TeacherRepository;
use App\Services\AttendanceService;
use App\Services\BadgeService;
use App\Services\DiscussionService;
use PDO;
use PHPUnit\Framework\TestCase;

/**
 * Integration Test for Full Phase 3 Completion:
 * - Configurable Weighted Late Attendance Formula (SRS §26)
 * - Badges & Rewards Gamification (SRS §33, ADMIN-34)
 * - Class Group Communication Feeds & Child Safeguarding (SRS §47, ADMIN-35)
 */
final class Phase3CompletionIntegrationTest extends TestCase
{
    private PDO $pdo;
    private AttendanceRepository $attendanceRepo;
    private BadgeRepository $badgeRepo;
    private DiscussionRepository $discussionRepo;
    private AcademicRepository $academicRepo;
    private StudentRepository $studentRepo;
    private TeacherRepository $teacherRepo;
    private ParentRepository $parentRepo;

    private AttendanceService $attendanceService;
    private BadgeService $badgeService;
    private DiscussionService $discussionService;

    private UserContext $adminContext;
    private UserContext $teacherContext;
    private UserContext $studentContext;
    private UserContext $parentContext;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pdo = new PDO('sqlite::memory:', null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        $this->createSchema();

        $this->attendanceRepo = new AttendanceRepository($this->pdo);
        $this->badgeRepo = new BadgeRepository($this->pdo);
        $this->discussionRepo = new DiscussionRepository($this->pdo);
        $this->academicRepo = new AcademicRepository($this->pdo);
        $this->studentRepo = new StudentRepository($this->pdo);
        $this->teacherRepo = new TeacherRepository($this->pdo);
        $this->parentRepo = new ParentRepository($this->pdo);

        $this->attendanceService = new AttendanceService($this->attendanceRepo, null, null, $this->academicRepo, $this->pdo);
        $this->badgeService = new BadgeService($this->badgeRepo, $this->studentRepo, null, $this->academicRepo);
        $this->discussionService = new DiscussionService($this->discussionRepo, $this->academicRepo, $this->teacherRepo, $this->studentRepo, $this->parentRepo);

        $this->seedFoundation();

        $this->adminContext = UserContext::fromUser(new User(id: 1, uuid: 'uuid-admin', name: 'Admin User', email: 'admin@claret.edu.ng', roles: ['admin']));
        $this->teacherContext = UserContext::fromUser(new User(id: 2, uuid: 'uuid-teacher', name: 'Teacher User', email: 'teacher@claret.edu.ng', roles: ['teacher']));
        $this->studentContext = UserContext::fromUser(new User(id: 3, uuid: 'uuid-student', name: 'Student User', email: 'student@claret.edu.ng', roles: ['student']));
        $this->parentContext = UserContext::fromUser(new User(id: 4, uuid: 'uuid-parent', name: 'Parent User', email: 'parent@claret.edu.ng', roles: ['parent']));
    }

    private function createSchema(): void
    {
        $this->pdo->exec("
            CREATE TABLE `system_settings` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `setting_key` VARCHAR(100) NOT NULL UNIQUE,
                `setting_value` TEXT NULL,
                `is_secret` INTEGER NOT NULL DEFAULT 0,
                `updated_by` INTEGER NULL,
                `updated_at` TEXT NOT NULL
            );

            CREATE TABLE `users` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `uuid` VARCHAR(36) NOT NULL UNIQUE,
                `name` VARCHAR(255) NOT NULL,
                `email` VARCHAR(255) NOT NULL UNIQUE,
                `phone` VARCHAR(50) NULL,
                `password_hash` VARCHAR(255) NOT NULL,
                `status` VARCHAR(20) NOT NULL DEFAULT 'active',
                `must_change_password` INTEGER NOT NULL DEFAULT 0,
                `created_at` TEXT NOT NULL,
                `updated_at` TEXT NOT NULL
            );

            CREATE TABLE `sessions` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `name` VARCHAR(100) NOT NULL,
                `code` VARCHAR(50) NULL,
                `start_date` TEXT NOT NULL,
                `end_date` TEXT NOT NULL,
                `status` VARCHAR(20) NOT NULL DEFAULT 'active',
                `created_at` TEXT NOT NULL,
                `updated_at` TEXT NOT NULL
            );

            CREATE TABLE `academic_sessions` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `name` VARCHAR(100) NOT NULL,
                `code` VARCHAR(50) NOT NULL,
                `start_date` TEXT NOT NULL,
                `end_date` TEXT NOT NULL,
                `status` VARCHAR(20) NOT NULL DEFAULT 'active',
                `is_active` INTEGER NOT NULL DEFAULT 1,
                `created_at` TEXT NOT NULL,
                `updated_at` TEXT NOT NULL
            );

            CREATE TABLE `terms` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `session_id` INTEGER NOT NULL,
                `name` VARCHAR(100) NOT NULL,
                `code` VARCHAR(50) NULL,
                `start_date` TEXT NOT NULL,
                `end_date` TEXT NOT NULL,
                `grading_starts_at` TEXT NULL,
                `grading_ends_at` TEXT NULL,
                `status` VARCHAR(20) NOT NULL DEFAULT 'active',
                `is_active` INTEGER NOT NULL DEFAULT 1,
                `created_at` TEXT NOT NULL,
                `updated_at` TEXT NOT NULL
            );

            CREATE TABLE `classes` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `name` VARCHAR(100) NOT NULL,
                `section_arm` VARCHAR(20) NOT NULL,
                `academic_level_id` INTEGER NOT NULL DEFAULT 1,
                `status` VARCHAR(20) NOT NULL DEFAULT 'active',
                `created_at` TEXT NOT NULL,
                `updated_at` TEXT NOT NULL
            );

            CREATE TABLE `subjects` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `name` VARCHAR(100) NOT NULL,
                `code` VARCHAR(20) NOT NULL,
                `status` VARCHAR(20) NOT NULL DEFAULT 'active',
                `is_active` INTEGER NOT NULL DEFAULT 1,
                `created_at` TEXT NOT NULL,
                `updated_at` TEXT NOT NULL
            );

            CREATE TABLE `class_subjects` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `class_id` INTEGER NOT NULL,
                `subject_id` INTEGER NOT NULL,
                `teacher_id` INTEGER NULL,
                `session_id` INTEGER NOT NULL,
                `status` VARCHAR(20) NOT NULL DEFAULT 'active',
                `created_at` TEXT NOT NULL,
                `updated_at` TEXT NOT NULL
            );

            CREATE TABLE `students` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `user_id` INTEGER NOT NULL UNIQUE,
                `admission_number` VARCHAR(50) NOT NULL UNIQUE,
                `current_class_id` INTEGER NULL,
                `status` VARCHAR(20) NOT NULL DEFAULT 'active',
                `created_at` TEXT NOT NULL,
                `updated_at` TEXT NOT NULL
            );

            CREATE TABLE `attendance_records` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `session_id` INTEGER,
                `term_id` INTEGER,
                `class_id` INTEGER,
                `class_subject_id` INTEGER NULL,
                `student_id` INTEGER,
                `date` TEXT,
                `period_number` INTEGER NULL,
                `status` TEXT,
                `marked_by` INTEGER,
                `updated_by` INTEGER NULL,
                `correction_reason` TEXT NULL,
                `created_at` TEXT,
                `updated_at` TEXT
            );

            CREATE TABLE `student_term_summaries` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `student_id` INTEGER NOT NULL,
                `term_id` INTEGER NOT NULL,
                `class_id` INTEGER NOT NULL DEFAULT 1,
                `attendance_present_count` INTEGER NOT NULL DEFAULT 0,
                `attendance_total_count` INTEGER NOT NULL DEFAULT 0,
                `updated_at` TEXT NOT NULL
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
                `created_at` TEXT NOT NULL
            );

            CREATE TABLE `student_badges` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `student_id` INTEGER NOT NULL,
                `badge_id` INTEGER NOT NULL,
                `awarded_by` INTEGER NOT NULL,
                `class_subject_id` INTEGER NULL,
                `session_id` INTEGER NULL,
                `term_id` INTEGER NULL,
                `reason` TEXT NOT NULL,
                `awarded_at` TEXT NOT NULL
            );

            CREATE TABLE `audit_logs` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `actor_user_id` INTEGER NULL,
                `action` VARCHAR(100) NOT NULL,
                `entity_type` VARCHAR(100) NOT NULL,
                `entity_id` INTEGER NULL,
                `before_json` TEXT NULL,
                `after_json` TEXT NULL,
                `metadata_json` TEXT NULL,
                `created_at` TEXT NOT NULL
            );

            CREATE TABLE `teachers` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `user_id` INTEGER NOT NULL UNIQUE,
                `staff_id` VARCHAR(50) NOT NULL UNIQUE,
                `status` VARCHAR(20) NOT NULL DEFAULT 'active',
                `created_at` TEXT NOT NULL,
                `updated_at` TEXT NOT NULL
            );

            CREATE TABLE `parents` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `user_id` INTEGER NOT NULL UNIQUE,
                `occupation` VARCHAR(100) NULL,
                `created_at` TEXT NOT NULL,
                `updated_at` TEXT NOT NULL
            );

            CREATE TABLE `parent_students` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `parent_id` INTEGER NOT NULL,
                `student_id` INTEGER NOT NULL,
                `relationship` VARCHAR(50) NOT NULL DEFAULT 'guardian',
                `created_at` TEXT NOT NULL
            );

            CREATE TABLE `class_discussions` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `class_subject_id` INTEGER NOT NULL,
                `user_id` INTEGER NOT NULL,
                `title` VARCHAR(255) NOT NULL,
                `content` TEXT NOT NULL,
                `is_pinned` INTEGER NOT NULL DEFAULT 0,
                `is_locked` INTEGER NOT NULL DEFAULT 0,
                `created_at` TEXT NOT NULL,
                `updated_at` TEXT NOT NULL
            );

            CREATE TABLE `class_discussion_replies` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `discussion_id` INTEGER NOT NULL,
                `user_id` INTEGER NOT NULL,
                `content` TEXT NOT NULL,
                `created_at` TEXT NOT NULL,
                `updated_at` TEXT NOT NULL
            );
        ");
    }

    private function seedFoundation(): void
    {
        $now = date('Y-m-d H:i:s');
        $this->pdo->exec("
            INSERT INTO users (id, uuid, name, email, phone, password_hash, status, must_change_password, created_at, updated_at) VALUES
            (1, 'u-admin', 'Administrator', 'admin@claret.edu.ng', '08000000001', 'hash', 'active', 0, '{$now}', '{$now}'),
            (2, 'u-teacher', 'John Teacher', 'teacher@claret.edu.ng', '08000000002', 'hash', 'active', 0, '{$now}', '{$now}'),
            (3, 'u-student', 'Alice Scholar', 'student@claret.edu.ng', '08000000003', 'hash', 'active', 0, '{$now}', '{$now}'),
            (4, 'u-parent', 'Parent User', 'parent@claret.edu.ng', '08000000004', 'hash', 'active', 0, '{$now}', '{$now}');

            INSERT INTO teachers (id, user_id, staff_id, status, created_at, updated_at)
            VALUES (1, 2, 'TCH-001', 'active', '{$now}', '{$now}');

            INSERT INTO parents (id, user_id, occupation, created_at, updated_at)
            VALUES (1, 4, 'Engineer', '{$now}', '{$now}');

            INSERT INTO parent_students (id, parent_id, student_id, relationship, created_at)
            VALUES (1, 1, 1, 'father', '{$now}');

            INSERT INTO sessions (id, name, code, start_date, end_date, status, created_at, updated_at)
            VALUES (1, '2026/2027 Academic Session', '2026-2027', '2026-09-01', '2027-07-31', 'active', '{$now}', '{$now}');

            INSERT INTO academic_sessions (id, name, code, start_date, end_date, status, is_active, created_at, updated_at)
            VALUES (1, '2026/2027 Academic Session', '2026-2027', '2026-09-01', '2027-07-31', 'active', 1, '{$now}', '{$now}');

            INSERT INTO terms (id, session_id, name, code, start_date, end_date, status, is_active, created_at, updated_at)
            VALUES (1, 1, 'First Term', '2026-T1', '2026-09-01', '2026-12-15', 'active', 1, '{$now}', '{$now}');

            INSERT INTO classes (id, name, section_arm, academic_level_id, status, created_at, updated_at)
            VALUES (1, 'JSS 1A', 'A', 1, 'active', '{$now}', '{$now}');

            INSERT INTO subjects (id, name, code, status, is_active, created_at, updated_at)
            VALUES (1, 'Mathematics', 'MTH101', 'active', 1, '{$now}', '{$now}');

            INSERT INTO class_subjects (id, class_id, subject_id, teacher_id, session_id, status, created_at, updated_at)
            VALUES (1, 1, 1, 1, 1, 'active', '{$now}', '{$now}');

            INSERT INTO students (id, user_id, admission_number, current_class_id, status, created_at, updated_at)
            VALUES (1, 3, 'CIS/2026/001', 1, 'active', '{$now}', '{$now}');
        ");

        $this->badgeRepo->ensureDefaultBadgesExist();
    }

    // =========================================================================
    // 1. Configurable Weighted Late Attendance Tests (SRS §26)
    // =========================================================================

    public function testDefaultAttendanceLateWeightIsSixtyPercent(): void
    {
        $weight = $this->attendanceRepo->getLateWeight();
        $this->assertEquals(0.60, $weight);

        $policy = $this->attendanceService->getAttendancePolicySettings();
        $this->assertEquals(0.60, $policy['late_weight']);
        $this->assertEquals(60.0, $policy['late_weight_percentage']);
    }

    public function testWeightedLateAttendanceFormulaCalculation(): void
    {
        // 10 records: 7 present, 2 late, 1 absent
        // With default weight 0.60:
        // effective attended = 7 + (2 * 0.60) = 8.20
        // weighted_rate = (8.20 / 10) * 100 = 82.0%
        // unweighted_rate = ((7 + 2) / 10) * 100 = 90.0%
        $statuses = ['present', 'present', 'present', 'present', 'present', 'present', 'present', 'late', 'late', 'absent'];
        foreach ($statuses as $idx => $st) {
            $date = '2026-09-' . str_pad((string)($idx + 1), 2, '0', STR_PAD_LEFT);
            $this->pdo->exec("
                INSERT INTO attendance_records (session_id, term_id, class_id, student_id, date, status, marked_by)
                VALUES (1, 1, 1, 1, '{$date}', '{$st}', 2)
            ");
        }

        $summary = $this->attendanceRepo->getStudentAttendanceSummary(1, 1);
        $this->assertSame(10, $summary['total_days']);
        $this->assertSame(7, $summary['present_days']);
        $this->assertSame(2, $summary['late_days']);
        $this->assertSame(1, $summary['absent_days']);

        $this->assertEquals(90.0, $summary['unweighted_rate']);
        $this->assertEquals(82.0, $summary['weighted_rate']);
        $this->assertEquals(0.60, $summary['late_weight']);
    }

    public function testAdminCanUpdateLateAttendanceWeightPolicy(): void
    {
        // Change late weight from 60% to 50%
        $updated = $this->attendanceService->updateAttendancePolicy(0.50, $this->adminContext);
        $this->assertEquals(0.50, $updated['late_weight']);
        $this->assertEquals(50.0, $updated['late_weight_percentage']);

        // Check persistence in system_settings
        $weightInRepo = $this->attendanceRepo->getLateWeight();
        $this->assertEquals(0.50, $weightInRepo);

        // Seed 10 records: 6 present, 4 late -> effective = 6 + (4 * 0.5) = 8.0 -> 80.0%
        $statuses = ['present', 'present', 'present', 'present', 'present', 'present', 'late', 'late', 'late', 'late'];
        foreach ($statuses as $idx => $st) {
            $date = '2026-10-' . str_pad((string)($idx + 1), 2, '0', STR_PAD_LEFT);
            $this->pdo->exec("
                INSERT INTO attendance_records (session_id, term_id, class_id, student_id, date, status, marked_by)
                VALUES (1, 1, 1, 1, '{$date}', '{$st}', 2)
            ");
        }

        $summary = $this->attendanceRepo->getStudentAttendanceSummary(1, 1);
        $this->assertEquals(80.0, $summary['weighted_rate']);
        $this->assertEquals(100.0, $summary['unweighted_rate']);
    }

    public function testAttendancePolicyWeightValidationAndPermissions(): void
    {
        // Non-admin cannot update attendance policy
        $this->expectException(AuthorizationException::class);
        $this->attendanceService->updateAttendancePolicy(0.70, $this->teacherContext);
    }

    public function testAttendancePolicyWeightRangeValidation(): void
    {
        // Cannot set negative weight
        $this->expectException(ValidationException::class);
        $this->attendanceService->updateAttendancePolicy(-0.1, $this->adminContext);
    }

    public function testClassAttendanceReportContainsWeightedMetrics(): void
    {
        $this->pdo->exec("
            INSERT INTO attendance_records (session_id, term_id, class_id, student_id, date, status, marked_by)
            VALUES 
            (1, 1, 1, 1, '2026-09-10', 'present', 2),
            (1, 1, 1, 1, '2026-09-11', 'late', 2);
        ");

        $report = $this->attendanceRepo->getClassAttendanceReport(1, 1);
        $this->assertNotEmpty($report);
        $firstRow = $report[0];
        $this->assertArrayHasKey('weighted_rate', $firstRow);
        $this->assertArrayHasKey('unweighted_rate', $firstRow);
        $this->assertArrayHasKey('late_weight', $firstRow);
    }

    // =========================================================================
    // 2. Badges & Rewards Gamification Tests (SRS §33, ADMIN-34)
    // =========================================================================

    public function testDefaultSystemBadgesAreSeeded(): void
    {
        $badges = $this->badgeService->getAllBadges();
        $this->assertNotEmpty($badges);
        $slugs = array_map(fn($b) => $b->slug, $badges);

        $this->assertContains('academic-excellence', $slugs);
        $this->assertContains('course-completer', $slugs);
        $this->assertContains('star-of-punctuality', $slugs);
    }

    public function testAdminCanAwardAndRevokeBadge(): void
    {
        $badge = $this->badgeRepo->findBySlug('academic-excellence');
        $this->assertNotNull($badge);

        // Award badge
        $awarded = $this->badgeService->awardBadge(
            studentId: 1,
            badgeId: $badge->id,
            reason: 'Top score of 98% on Mathematics First Term Examination.',
            actor: $this->adminContext,
            classSubjectId: 1
        );

        $this->assertSame(1, $awarded->studentId);
        $this->assertSame($badge->id, $awarded->badgeId);
        $this->assertSame('Top score of 98% on Mathematics First Term Examination.', $awarded->reason);

        // Verify student has badge
        $studentBadges = $this->badgeService->getStudentBadges(1);
        $this->assertCount(1, $studentBadges);

        // Verify school-wide awarded badges registry
        $allAwarded = $this->badgeService->getAllAwardedBadges();
        $this->assertCount(1, $allAwarded);

        // Revoke badge
        $revoked = $this->badgeService->revokeBadge($awarded->id, $this->adminContext);
        $this->assertTrue($revoked);

        $this->assertEmpty($this->badgeService->getStudentBadges(1));
    }

    public function testCannotAwardDuplicateBadgeForSameSubject(): void
    {
        $badge = $this->badgeRepo->findBySlug('star-of-punctuality');
        $this->assertNotNull($badge);

        $this->badgeService->awardBadge(
            studentId: 1,
            badgeId: $badge->id,
            reason: 'First award for perfect attendance.',
            actor: $this->adminContext,
            classSubjectId: 1
        );

        $this->expectException(ValidationException::class);
        $this->badgeService->awardBadge(
            studentId: 1,
            badgeId: $badge->id,
            reason: 'Duplicate award attempt.',
            actor: $this->adminContext,
            classSubjectId: 1
        );
    }

    // =========================================================================
    // 3. Class Group Discussions & Child Safeguarding (SRS §47, ADMIN-35)
    // =========================================================================

    public function testClassDiscussionThreadLifecycleAndAdminModeration(): void
    {
        // 1. Create topic
        $discussionId = $this->discussionService->createDiscussion(
            classSubjectId: 1,
            title: 'Week 3 Algebra Discussion - Quadratic Formulations',
            content: 'Please post your solutions to problem set 3 here for group discussion.',
            actor: $this->teacherContext
        );

        $discussion = $this->discussionRepo->findDiscussionById($discussionId);
        $this->assertNotNull($discussion);
        $this->assertSame('Week 3 Algebra Discussion - Quadratic Formulations', $discussion->title);
        $this->assertFalse($discussion->isPinned);
        $this->assertFalse($discussion->isLocked);

        // 2. Student replies to topic
        $replyId = $this->discussionService->addReply(
            discussionId: $discussion->id,
            content: 'I used the factoring method for question 2 and got roots x=3 and x=-5.',
            actor: $this->studentContext
        );
        $this->assertGreaterThan(0, $replyId);

        // 3. Admin posts reply
        $adminReplyId = $this->discussionService->addReply(
            discussionId: $discussion->id,
            content: 'Official administrative note: Problem sets must be submitted by Friday 4pm.',
            actor: $this->adminContext
        );
        $this->assertGreaterThan(0, $adminReplyId);

        // 4. Verify thread details with replies
        $bundle = $this->discussionService->getDiscussionThread($discussion->id, $this->adminContext);
        $this->assertCount(2, $bundle['discussion']->replies);

        // 5. Admin pins topic
        $this->discussionService->togglePin($discussion->id, true, $this->adminContext);
        $pinnedDiscussion = $this->discussionRepo->findDiscussionById($discussion->id);
        $this->assertTrue($pinnedDiscussion->isPinned);

        // 6. Admin locks topic
        $this->discussionService->toggleLock($discussion->id, true, $this->adminContext);
        $lockedDiscussion = $this->discussionRepo->findDiscussionById($discussion->id);
        $this->assertTrue($lockedDiscussion->isLocked);

        // 7. Non-moderator cannot reply to locked topic
        try {
            $this->discussionService->addReply($discussion->id, 'Should fail', $this->studentContext);
            $this->fail('Expected DomainRuleException for replying to locked topic.');
        } catch (\App\Core\Exceptions\DomainRuleException $e) {
            $this->assertStringContainsString('locked', $e->getMessage());
        }

        // 8. Admin moderates and deletes reply
        $deletedReply = $this->discussionService->deleteReply($replyId, $this->adminContext);
        $this->assertTrue($deletedReply);
        $this->assertNull($this->discussionRepo->findReplyById($replyId));

        $afterDeleteBundle = $this->discussionService->getDiscussionThread($discussion->id, $this->adminContext);
        $this->assertCount(1, $afterDeleteBundle['discussion']->replies);

        // 9. Admin deletes entire thread
        $deletedThread = $this->discussionService->deleteDiscussion($discussion->id, $this->adminContext);
        $this->assertTrue($deletedThread);
        $this->assertNull($this->discussionRepo->findDiscussionById($discussion->id));
    }

    public function testChildSafeguardingStrictlyNoPrivateMessaging(): void
    {
        // Read index.php to verify NO private 1-on-1 teacher-student message routes exist
        $indexContent = file_get_contents(dirname(__DIR__, 2) . '/public/index.php');

        // Check that chat / message / private dm routes do not exist
        $this->assertStringNotContainsString('/messages/private', $indexContent);
        $this->assertStringNotContainsString('/chat/direct', $indexContent);
        $this->assertStringNotContainsString('/teacher/students/{id}/chat', $indexContent);
        $this->assertStringNotContainsString('/student/teachers/{id}/chat', $indexContent);

        // Verify that discussions are strictly class_subject group scoped
        $this->assertStringContainsString('/teacher/subjects/{classSubjectId}/discussions', $indexContent);
        $this->assertStringContainsString('/student/subjects/{classSubjectId}/discussions', $indexContent);
        $this->assertStringContainsString('/parent/children/{studentId}/discussions', $indexContent);
        $this->assertStringContainsString('/admin/discussions', $indexContent);
    }

    public function testAdminDiscussionControllerShowRendersThreadSuccessfully(): void
    {
        // 1. Create a discussion topic
        $discussionId = $this->discussionService->createDiscussion(
            classSubjectId: 1,
            title: 'Algebra Review Discussion',
            content: 'Discuss solutions to week 2 problems.',
            actor: $this->teacherContext
        );

        // 2. Add reply
        $this->discussionService->addReply($discussionId, 'First reply from student.', $this->studentContext);

        // 3. Mock authenticator with admin user
        $mockAuth = $this->createMock(AuthenticatorInterface::class);
        $mockAuth->method('getUserContext')->willReturn($this->adminContext);
        $mockAuth->method('user')->willReturn($this->adminContext);

        $adminController = new AdminDiscussionController(
            discussionService: $this->discussionService,
            academicRepo: $this->academicRepo,
            authenticator: $mockAuth
        );

        $request = new Request();
        $request->setAttribute('classSubjectId', 1);
        $request->setAttribute('discussionId', $discussionId);

        $response = $adminController->show($request, 1, $discussionId);

        $this->assertSame(200, $response->getStatusCode());
        $body = $response->getBody();
        $this->assertStringContainsString('Algebra Review Discussion', $body);
        $this->assertStringContainsString('First reply from student.', $body);
        $this->assertStringContainsString('Class Discussion Moderation', $body);
    }

    public function testStudentDiscussionControllerShowRendersThreadAndSuccessAlertSuccessfully(): void
    {
        // 1. Create a discussion topic
        $discussionId = $this->discussionService->createDiscussion(
            classSubjectId: 1,
            title: 'Quadratic Equations Clarification',
            content: 'How do we solve question 4 on quadratic formulae?',
            actor: $this->studentContext
        );

        // 2. Add reply
        $this->discussionService->addReply($discussionId, 'Check textbook page 42 for the discriminant rule.', $this->teacherContext);

        // 3. Mock authenticator with student user
        $mockAuth = $this->createMock(AuthenticatorInterface::class);
        $mockAuth->method('getUserContext')->willReturn($this->studentContext);
        $mockAuth->method('user')->willReturn($this->studentContext);

        $studentController = new StudentDiscussionController(
            authenticator: $mockAuth,
            discussionService: $this->discussionService,
            studentRepo: $this->studentRepo,
            academicRepo: $this->academicRepo
        );

        $_GET['success'] = 'Discussion question posted.';

        $request = new Request();
        $request->setAttribute('classSubjectId', 1);
        $request->setAttribute('discussionId', $discussionId);

        $response = $studentController->show($request, 1, $discussionId);

        $this->assertSame(200, $response->getStatusCode());
        $body = $response->getBody();
        $this->assertStringContainsString('Quadratic Equations Clarification', $body);
        $this->assertStringContainsString('Check textbook page 42', $body);
        $this->assertStringContainsString('Discussion question posted.', $body);

        unset($_GET['success']);
    }
}
