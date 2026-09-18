<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Exceptions\DomainRuleException;
use App\Core\Exceptions\ResourceNotFoundException;
use App\Core\Exceptions\ValidationException;
use App\Models\ActivityProgress;
use App\Repositories\AcademicRepository;
use App\Repositories\ActivityPrerequisiteRepository;
use App\Repositories\ActivityProgressRepository;
use App\Repositories\AssignmentRepository;
use App\Repositories\ContentRepository;
use App\Repositories\DocumentSectionRepository;
use App\Repositories\QuizRepository;
use App\Services\PrerequisiteService;
use PDO;
use PHPUnit\Framework\TestCase;

final class PrerequisiteServiceTest extends TestCase
{
    private PDO $pdo;
    private ActivityPrerequisiteRepository $prereqRepo;
    private ActivityProgressRepository $progressRepo;
    private PrerequisiteService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pdo = new PDO('sqlite::memory:', null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        // Schema setup
        $this->pdo->exec("
            CREATE TABLE `users` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `name` VARCHAR(120) NOT NULL,
                `email` VARCHAR(150) NOT NULL
            );

            CREATE TABLE `teachers` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `user_id` INTEGER NOT NULL,
                `staff_id` VARCHAR(50) NOT NULL
            );

            CREATE TABLE `classes` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `name` VARCHAR(100) NOT NULL,
                `section_arm` VARCHAR(50) NULL
            );

            CREATE TABLE `subjects` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `name` VARCHAR(100) NOT NULL,
                `code` VARCHAR(20) NOT NULL
            );

            CREATE TABLE `terms` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `name` VARCHAR(50) NOT NULL,
                `session_id` INTEGER NOT NULL,
                `start_date` DATE NOT NULL,
                `end_date` DATE NOT NULL,
                `status` VARCHAR(20) NOT NULL
            );

            CREATE TABLE `class_subjects` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `session_id` INTEGER NOT NULL,
                `class_id` INTEGER NOT NULL,
                `subject_id` INTEGER NOT NULL,
                `teacher_id` INTEGER NOT NULL,
                `status` VARCHAR(20) NOT NULL
            );

            CREATE TABLE `activity_prerequisites` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `activity_type` VARCHAR(50) NOT NULL,
                `activity_id` INTEGER NOT NULL,
                `prerequisite_activity_type` VARCHAR(50) NOT NULL,
                `prerequisite_activity_id` INTEGER NOT NULL,
                `requirement_type` VARCHAR(30) NOT NULL DEFAULT 'completion',
                `created_at` TEXT NOT NULL,
                `updated_at` TEXT NOT NULL,
                UNIQUE (`activity_type`, `activity_id`, `prerequisite_activity_type`, `prerequisite_activity_id`)
            );

            CREATE TABLE `learning_activity_progress` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `student_id` INTEGER NOT NULL,
                `activity_type` VARCHAR(50) NOT NULL,
                `activity_id` INTEGER NOT NULL,
                `last_page` INTEGER NULL DEFAULT NULL,
                `total_pages` INTEGER NULL DEFAULT NULL,
                `pages_read_json` TEXT NULL DEFAULT NULL,
                `progress_percent` REAL NOT NULL DEFAULT 0.00,
                `is_completed` INTEGER NOT NULL DEFAULT 0,
                `completed_at` TEXT NULL DEFAULT NULL,
                `last_accessed_at` TEXT NOT NULL,
                `created_at` TEXT NOT NULL,
                `updated_at` TEXT NOT NULL,
                UNIQUE (`student_id`, `activity_type`, `activity_id`)
            );

            CREATE TABLE `content_items` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `title` VARCHAR(255) NOT NULL,
                `class_subject_id` INTEGER NOT NULL DEFAULT 1,
                `status` VARCHAR(20) NOT NULL DEFAULT 'published'
            );

            CREATE TABLE `document_sections` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `content_item_id` INTEGER NOT NULL,
                `title` VARCHAR(255) NOT NULL,
                `start_page` INTEGER NOT NULL,
                `end_page` INTEGER NOT NULL,
                `sequence_order` INTEGER NOT NULL DEFAULT 1
            );

            CREATE TABLE `quizzes` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `teacher_id` INTEGER NULL DEFAULT 1,
                `class_subject_id` INTEGER NOT NULL DEFAULT 1,
                `term_id` INTEGER NULL DEFAULT 1,
                `title` VARCHAR(255) NOT NULL,
                `status` VARCHAR(20) NOT NULL DEFAULT 'published'
            );

            CREATE TABLE `assignments` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `class_subject_id` INTEGER NOT NULL DEFAULT 1,
                `term_id` INTEGER NOT NULL DEFAULT 1,
                `teacher_id` INTEGER NOT NULL DEFAULT 1,
                `file_id` INTEGER NULL DEFAULT NULL,
                `title` VARCHAR(255) NOT NULL,
                `instructions` TEXT NULL,
                `due_at` TEXT NOT NULL DEFAULT '2026-12-31 23:59:59',
                `max_score` REAL NOT NULL DEFAULT 100.00,
                `status` VARCHAR(20) NOT NULL DEFAULT 'published',
                `created_at` TEXT NOT NULL DEFAULT '2026-09-01 00:00:00',
                `updated_at` TEXT NOT NULL DEFAULT '2026-09-01 00:00:00'
            );
            CREATE TABLE `files` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `uuid` VARCHAR(36) NOT NULL,
                `storage_key` VARCHAR(255) NOT NULL,
                `original_name` VARCHAR(255) NOT NULL,
                `mime_type` VARCHAR(100) NOT NULL,
                `size_bytes` INTEGER NOT NULL,
                `sha256` VARCHAR(64) NOT NULL,
                `uploaded_by` INTEGER NOT NULL,
                `owner_type` VARCHAR(50) NOT NULL,
                `owner_id` INTEGER NOT NULL,
                `created_at` TEXT NOT NULL,
                `deleted_at` TEXT NULL
            );
        ");

        $this->prereqRepo = new ActivityPrerequisiteRepository($this->pdo);
        $this->progressRepo = new ActivityProgressRepository($this->pdo);
        $contentRepo = new ContentRepository($this->pdo);
        $sectionRepo = new DocumentSectionRepository($this->pdo);
        $quizRepo = new QuizRepository($this->pdo);
        $assignmentRepo = new AssignmentRepository($this->pdo);
        $academicRepo = new AcademicRepository($this->pdo);

        $this->service = new PrerequisiteService(
            $this->prereqRepo,
            $this->progressRepo,
            $contentRepo,
            $sectionRepo,
            $quizRepo,
            $assignmentRepo,
            $academicRepo
        );

        // Seed some sample activities and supporting relationships
        $this->pdo->exec("
            INSERT INTO `users` (id, name, email) VALUES (1, 'Teacher Jane', 'jane@claret.edu');
            INSERT INTO `teachers` (id, user_id, staff_id) VALUES (1, 1, 'STF-001');
            INSERT INTO `classes` (id, name, section_arm) VALUES (1, 'JSS 1', 'A');
            INSERT INTO `subjects` (id, name, code) VALUES (1, 'Physics', 'PHY101');
            INSERT INTO `terms` (id, name, session_id, start_date, end_date, status) VALUES (1, 'First Term', 1, '2026-09-01', '2026-12-15', 'active');
            INSERT INTO `class_subjects` (id, session_id, class_id, subject_id, teacher_id, status) VALUES (1, 1, 1, 1, 1, 'active');

            INSERT INTO `content_items` (id, title) VALUES (1, 'Physics Chapter 1');
            INSERT INTO `document_sections` (id, content_item_id, title, start_page, end_page) VALUES (10, 1, 'Section 1.1 Introduction', 1, 5);
            INSERT INTO `document_sections` (id, content_item_id, title, start_page, end_page) VALUES (11, 1, 'Section 1.2 Kinematics', 6, 10);
            INSERT INTO `document_sections` (id, content_item_id, title, start_page, end_page) VALUES (12, 1, 'Section 1.3 Dynamics', 11, 15);
            INSERT INTO `quizzes` (id, title) VALUES (20, 'Kinematics Mastery Quiz');
            INSERT INTO `quizzes` (id, title) VALUES (21, 'Chapter 1 Final Exam');
            INSERT INTO `assignments` (id, title) VALUES (30, 'Problem Set 1');
        ");
    }

    public function testRejectsInvalidActivityType(): void
    {
        $this->expectException(ValidationException::class);
        $this->service->createPrerequisite(
            targetType: 'invalid_type',
            targetId: 10,
            prereqType: ActivityProgress::TYPE_DOCUMENT_SECTION,
            prereqId: 11
        );
    }

    public function testRejectsSelfDependency(): void
    {
        $this->expectException(DomainRuleException::class);
        $this->expectExceptionMessage('An activity cannot require itself as a prerequisite');

        $this->service->createPrerequisite(
            targetType: ActivityProgress::TYPE_DOCUMENT_SECTION,
            targetId: 10,
            prereqType: ActivityProgress::TYPE_DOCUMENT_SECTION,
            prereqId: 10
        );
    }

    public function testRejectsNonExistentActivity(): void
    {
        $this->expectException(ResourceNotFoundException::class);

        $this->service->createPrerequisite(
            targetType: ActivityProgress::TYPE_DOCUMENT_SECTION,
            targetId: 9999,
            prereqType: ActivityProgress::TYPE_DOCUMENT_SECTION,
            prereqId: 10
        );
    }

    public function testRejectsNonExistentPrerequisiteActivity(): void
    {
        $this->expectException(ResourceNotFoundException::class);

        $this->service->createPrerequisite(
            targetType: ActivityProgress::TYPE_DOCUMENT_SECTION,
            targetId: 10,
            prereqType: ActivityProgress::TYPE_DOCUMENT_SECTION,
            prereqId: 8888
        );
    }

    public function testCreatePrerequisiteSuccessfully(): void
    {
        $prereq = $this->service->createPrerequisite(
            targetType: ActivityProgress::TYPE_DOCUMENT_SECTION,
            targetId: 11, // Section 1.2
            prereqType: ActivityProgress::TYPE_DOCUMENT_SECTION,
            prereqId: 10 // Section 1.1
        );

        $this->assertNotNull($prereq->id);
        $this->assertSame(ActivityProgress::TYPE_DOCUMENT_SECTION, $prereq->activityType);
        $this->assertSame(11, $prereq->activityId);
        $this->assertSame(ActivityProgress::TYPE_DOCUMENT_SECTION, $prereq->prerequisiteActivityType);
        $this->assertSame(10, $prereq->prerequisiteActivityId);

        // Duplicate creation throws DomainRuleException
        $this->expectException(DomainRuleException::class);
        $this->expectExceptionMessage('already exists');
        $this->service->createPrerequisite(
            targetType: ActivityProgress::TYPE_DOCUMENT_SECTION,
            targetId: 11,
            prereqType: ActivityProgress::TYPE_DOCUMENT_SECTION,
            prereqId: 10
        );
    }

    public function testDirectCycleDetectionRejection(): void
    {
        // 11 requires 10
        $this->service->createPrerequisite(
            targetType: ActivityProgress::TYPE_DOCUMENT_SECTION,
            targetId: 11,
            prereqType: ActivityProgress::TYPE_DOCUMENT_SECTION,
            prereqId: 10
        );

        // Attempting to make 10 require 11 should be rejected as circular
        $this->expectException(DomainRuleException::class);
        $this->expectExceptionMessage('circular dependency');

        $this->service->createPrerequisite(
            targetType: ActivityProgress::TYPE_DOCUMENT_SECTION,
            targetId: 10,
            prereqType: ActivityProgress::TYPE_DOCUMENT_SECTION,
            prereqId: 11
        );
    }

    public function testTransitiveCycleDetectionRejection(): void
    {
        // Chain: 12 requires 11, 11 requires 10 (10 -> 11 -> 12)
        $this->service->createPrerequisite(
            targetType: ActivityProgress::TYPE_DOCUMENT_SECTION,
            targetId: 11,
            prereqType: ActivityProgress::TYPE_DOCUMENT_SECTION,
            prereqId: 10
        );
        $this->service->createPrerequisite(
            targetType: ActivityProgress::TYPE_DOCUMENT_SECTION,
            targetId: 12,
            prereqType: ActivityProgress::TYPE_DOCUMENT_SECTION,
            prereqId: 11
        );

        // Making 10 require 12 would complete a cycle (10 -> 11 -> 12 -> 10)
        $this->expectException(DomainRuleException::class);
        $this->expectExceptionMessage('circular dependency');

        $this->service->createPrerequisite(
            targetType: ActivityProgress::TYPE_DOCUMENT_SECTION,
            targetId: 10,
            prereqType: ActivityProgress::TYPE_DOCUMENT_SECTION,
            prereqId: 12
        );
    }

    public function testDeletePrerequisite(): void
    {
        $prereq = $this->service->createPrerequisite(
            targetType: ActivityProgress::TYPE_QUIZ,
            targetId: 20,
            prereqType: ActivityProgress::TYPE_DOCUMENT_SECTION,
            prereqId: 11
        );

        $this->assertTrue($this->service->deletePrerequisite($prereq->id));
        $this->assertFalse($this->service->deletePrerequisite($prereq->id)); // already deleted
    }

    public function testActivityWithNoPrerequisitesIsAlwaysUnlocked(): void
    {
        $status = $this->service->getPrerequisiteStatus(
            studentId: 99,
            activityType: ActivityProgress::TYPE_DOCUMENT_SECTION,
            activityId: 10
        );

        $this->assertTrue($status['is_unlocked']);
        $this->assertEmpty($status['prerequisites']);
        $this->assertEmpty($status['unmet']);
        $this->assertTrue($this->service->isActivityUnlocked(99, ActivityProgress::TYPE_DOCUMENT_SECTION, 10));
    }

    public function testActivityLockedWhenPrerequisiteNotCompleted(): void
    {
        // Quiz 20 requires Section 11
        $this->service->createPrerequisite(
            targetType: ActivityProgress::TYPE_QUIZ,
            targetId: 20,
            prereqType: ActivityProgress::TYPE_DOCUMENT_SECTION,
            prereqId: 11
        );

        // Student 1 has NO progress record for Section 11
        $status = $this->service->getPrerequisiteStatus(
            studentId: 1,
            activityType: ActivityProgress::TYPE_QUIZ,
            activityId: 20
        );

        $this->assertFalse($status['is_unlocked']);
        $this->assertCount(1, $status['unmet']);
        $this->assertSame(11, $status['unmet'][0]['activity_id']);
        $this->assertSame(ActivityProgress::TYPE_DOCUMENT_SECTION, $status['unmet'][0]['type']);
        $this->assertFalse($this->service->isActivityUnlocked(1, ActivityProgress::TYPE_QUIZ, 20));

        // Now student has partial progress (e.g. 40%), is_completed = 0
        $now = date('Y-m-d H:i:s');
        $this->pdo->exec("
            INSERT INTO `learning_activity_progress` 
            (student_id, activity_type, activity_id, last_page, total_pages, pages_read_json, progress_percent, is_completed, last_accessed_at, created_at, updated_at)
            VALUES (1, 'document_section', 11, 7, 5, '[6,7]', 40.0, 0, '{$now}', '{$now}', '{$now}');
        ");

        $this->assertFalse($this->service->isActivityUnlocked(1, ActivityProgress::TYPE_QUIZ, 20));

        // Now mark Section 11 as completed
        $this->pdo->exec("
            UPDATE `learning_activity_progress` 
            SET is_completed = 1, progress_percent = 100.0, completed_at = '{$now}'
            WHERE student_id = 1 AND activity_type = 'document_section' AND activity_id = 11;
        ");

        $this->assertTrue($this->service->isActivityUnlocked(1, ActivityProgress::TYPE_QUIZ, 20));
        $statusAfter = $this->service->getPrerequisiteStatus(1, ActivityProgress::TYPE_QUIZ, 20);
        $this->assertTrue($statusAfter['is_unlocked']);
        $this->assertEmpty($statusAfter['unmet']);
    }

    public function testMultiPrerequisitesAllMustBeCompleted(): void
    {
        // Quiz 21 requires Section 11 AND Section 12 AND Problem Set 30
        $this->service->createPrerequisite(ActivityProgress::TYPE_QUIZ, 21, ActivityProgress::TYPE_DOCUMENT_SECTION, 11);
        $this->service->createPrerequisite(ActivityProgress::TYPE_QUIZ, 21, ActivityProgress::TYPE_DOCUMENT_SECTION, 12);
        $this->service->createPrerequisite(ActivityProgress::TYPE_QUIZ, 21, ActivityProgress::TYPE_ASSIGNMENT, 30);

        $now = date('Y-m-d H:i:s');
        // Complete Section 11 only
        $this->pdo->exec("
            INSERT INTO `learning_activity_progress` 
            (student_id, activity_type, activity_id, progress_percent, is_completed, completed_at, last_accessed_at, created_at, updated_at)
            VALUES (1, 'document_section', 11, 100.0, 1, '{$now}', '{$now}', '{$now}', '{$now}');
        ");

        $status = $this->service->getPrerequisiteStatus(1, ActivityProgress::TYPE_QUIZ, 21);
        $this->assertFalse($status['is_unlocked']);
        $this->assertCount(2, $status['unmet']);

        // Complete Section 12 as well
        $this->pdo->exec("
            INSERT INTO `learning_activity_progress` 
            (student_id, activity_type, activity_id, progress_percent, is_completed, completed_at, last_accessed_at, created_at, updated_at)
            VALUES (1, 'document_section', 12, 100.0, 1, '{$now}', '{$now}', '{$now}', '{$now}');
        ");

        $this->assertFalse($this->service->isActivityUnlocked(1, ActivityProgress::TYPE_QUIZ, 21));

        // Complete Problem Set 30
        $this->progressRepo->recordActivityCompletion(1, ActivityProgress::TYPE_ASSIGNMENT, 30);

        $this->assertTrue($this->service->isActivityUnlocked(1, ActivityProgress::TYPE_QUIZ, 21));
        $statusFinal = $this->service->getPrerequisiteStatus(1, ActivityProgress::TYPE_QUIZ, 21);
        $this->assertTrue($statusFinal['is_unlocked']);
        $this->assertEmpty($statusFinal['unmet']);
    }

    public function testStudentIsolation(): void
    {
        // Section 12 requires Section 11
        $this->service->createPrerequisite(ActivityProgress::TYPE_DOCUMENT_SECTION, 12, ActivityProgress::TYPE_DOCUMENT_SECTION, 11);

        // Student 1 completes Section 11
        $this->progressRepo->recordActivityCompletion(1, ActivityProgress::TYPE_DOCUMENT_SECTION, 11);

        // Student 1 is unlocked
        $this->assertTrue($this->service->isActivityUnlocked(1, ActivityProgress::TYPE_DOCUMENT_SECTION, 12));

        // Student 2 has not completed Section 11, must remain locked
        $this->assertFalse($this->service->isActivityUnlocked(2, ActivityProgress::TYPE_DOCUMENT_SECTION, 12));
    }

    public function testGetConfiguredPrerequisitesReturnsDetailedTitles(): void
    {
        $this->service->createPrerequisite(ActivityProgress::TYPE_QUIZ, 20, ActivityProgress::TYPE_DOCUMENT_SECTION, 10);
        $this->service->createPrerequisite(ActivityProgress::TYPE_QUIZ, 20, ActivityProgress::TYPE_ASSIGNMENT, 30);

        $configured = $this->service->getConfiguredPrerequisites(ActivityProgress::TYPE_QUIZ, 20);

        $this->assertCount(2, $configured);
        $titles = array_column($configured, 'title');
        $this->assertContains('Section 1.1 Introduction', $titles);
        $this->assertContains('Problem Set 1', $titles);
    }
}
