<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Controllers\Admin\PromotionController;
use App\Core\Database;
use App\Core\Exceptions\AuthorizationException;
use App\Core\Request;
use App\Core\Session;
use App\Core\UserContext;
use App\Models\ApprovalRequest;
use App\Models\Promotion;
use App\Repositories\AcademicRepository;
use App\Repositories\ApprovalRepository;
use App\Repositories\EnrollmentRepository;
use App\Repositories\GradebookRepository;
use App\Repositories\PromotionRepository;
use App\Repositories\ResultPublicationRepository;
use App\Repositories\StudentRepository;
use App\Services\ApprovalService;
use App\Services\PromotionService;
use App\Services\ReportCardService;
use PDO;
use PHPUnit\Framework\TestCase;

final class PromotionWorkflowIntegrationTest extends TestCase
{
    private PDO $pdo;
    private PromotionRepository $promotionRepo;
    private AcademicRepository $academicRepo;
    private EnrollmentRepository $enrollmentRepo;
    private StudentRepository $studentRepo;
    private ResultPublicationRepository $publicationRepo;
    private GradebookRepository $gradebookRepo;
    private ApprovalRepository $approvalRepo;
    private ApprovalService $approvalService;
    private PromotionService $promotionService;
    private ReportCardService $reportCardService;
    private PromotionController $promotionController;

    private int $adminUserId;
    private int $sessionId;
    private int $nextSessionId;
    private int $term1Id;
    private int $term2Id;
    private int $term3Id;

    // Academic Levels & Classes
    private int $pri5LevelId;
    private int $pri5ClassId;
    private int $jss1LevelId;
    private int $jss2LevelId;
    private int $jss1ClassId;
    private int $jss2ClassId;
    private int $ss3LevelId;
    private int $ss3ClassId;

    // Students
    private int $pri5StudentId;
    private int $ss3StudentId;
    private int $jss1PassingStudentId;
    private int $jss1BorderlineStudentId;
    private int $jss1FailingStudentId;

    protected function setUp(): void
    {
        Session::destroy();
        Session::start();

        $this->pdo = new PDO('sqlite::memory:', null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        Database::setConnection($this->pdo);

        $this->createTables();
        $this->seedTestData();

        $this->promotionRepo = new PromotionRepository($this->pdo);
        $this->academicRepo = new AcademicRepository($this->pdo);
        $this->enrollmentRepo = new EnrollmentRepository($this->pdo);
        $this->studentRepo = new StudentRepository($this->pdo);
        $this->publicationRepo = new ResultPublicationRepository($this->pdo);
        $this->gradebookRepo = new GradebookRepository($this->pdo);
        $this->approvalRepo = new ApprovalRepository($this->pdo);
        $this->approvalService = new ApprovalService($this->approvalRepo, null, $this->pdo);

        $this->promotionService = new PromotionService(
            promotionRepo: $this->promotionRepo,
            academicRepo: $this->academicRepo,
            enrollmentRepo: $this->enrollmentRepo,
            studentRepo: $this->studentRepo,
            publicationRepo: $this->publicationRepo,
            gradebookRepo: $this->gradebookRepo,
            approvalService: $this->approvalService,
            pdo: $this->pdo
        );

        $this->reportCardService = new ReportCardService(
            gradebookRepo: $this->gradebookRepo,
            studentRepo: $this->studentRepo,
            academicRepo: $this->academicRepo,
            promotionService: $this->promotionService,
            promotionRepo: $this->promotionRepo
        );

        $this->promotionController = new PromotionController(
            authenticator: null,
            promotionService: $this->promotionService,
            academicRepo: $this->academicRepo
        );
    }

    protected function tearDown(): void
    {
        Session::destroy();
        Database::reset();
    }

    private function createTables(): void
    {
        $this->pdo->exec("
            CREATE TABLE `users` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `uuid` VARCHAR(36) NOT NULL UNIQUE,
                `name` VARCHAR(120) NOT NULL,
                `email` VARCHAR(150) NOT NULL UNIQUE,
                `phone` VARCHAR(20) NULL,
                `password_hash` VARCHAR(255) NOT NULL,
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

            CREATE TABLE `students` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `user_id` INTEGER NOT NULL UNIQUE,
                `admission_number` VARCHAR(50) NOT NULL UNIQUE,
                `gender` VARCHAR(10) NOT NULL DEFAULT 'other',
                `date_of_birth` DATE NULL,
                `current_class_id` INTEGER NULL,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE `sessions` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `name` VARCHAR(100) NOT NULL,
                `start_date` DATE NULL,
                `end_date` DATE NULL,
                `is_current` INTEGER NOT NULL DEFAULT 0,
                `status` VARCHAR(20) NOT NULL DEFAULT 'active'
            );

            CREATE TABLE `terms` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `name` VARCHAR(100) NOT NULL,
                `session_id` INTEGER NOT NULL,
                `start_date` DATE NULL,
                `end_date` DATE NULL,
                `is_current` INTEGER NOT NULL DEFAULT 0,
                `status` VARCHAR(20) NOT NULL DEFAULT 'active'
            );

            CREATE TABLE `academic_levels` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `name` VARCHAR(100) NOT NULL,
                `stage` VARCHAR(50) NOT NULL DEFAULT 'secondary',
                `category` VARCHAR(50) NOT NULL DEFAULT 'secondary',
                `rank_order` INTEGER NOT NULL DEFAULT 1,
                `grading_scale_id` INTEGER NULL,
                `is_terminal` INTEGER NOT NULL DEFAULT 0,
                `next_level_id` INTEGER NULL
            );

            CREATE TABLE `classes` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `name` VARCHAR(100) NOT NULL,
                `academic_level_id` INTEGER NOT NULL,
                `level_id` INTEGER NULL,
                `section_arm` VARCHAR(50) NOT NULL DEFAULT 'A',
                `capacity` INTEGER NOT NULL DEFAULT 40,
                `status` VARCHAR(20) NOT NULL DEFAULT 'active'
            );

            CREATE TABLE `subjects` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `name` VARCHAR(100) NOT NULL,
                `code` VARCHAR(20) NOT NULL UNIQUE,
                `status` VARCHAR(20) NOT NULL DEFAULT 'active'
            );

            CREATE TABLE `class_subjects` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `class_id` INTEGER NOT NULL,
                `subject_id` INTEGER NOT NULL,
                `session_id` INTEGER NOT NULL,
                `teacher_id` INTEGER NULL,
                `status` VARCHAR(20) NOT NULL DEFAULT 'active'
            );

            CREATE TABLE `class_enrollments` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `student_id` INTEGER NOT NULL,
                `class_id` INTEGER NOT NULL,
                `session_id` INTEGER NOT NULL,
                `status` VARCHAR(20) NOT NULL DEFAULT 'enrolled',
                `enrolled_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE `student_term_summaries` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `student_id` INTEGER NOT NULL,
                `term_id` INTEGER NOT NULL,
                `class_id` INTEGER NOT NULL,
                `total_score` REAL NOT NULL DEFAULT 0,
                `average_score` REAL NOT NULL DEFAULT 0,
                `rank_in_class` INTEGER NULL,
                `promotion_status` VARCHAR(50) NULL,
                `class_teacher_remark` TEXT NULL,
                `principal_remark` TEXT NULL,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE `term_results` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `student_id` INTEGER NOT NULL,
                `term_id` INTEGER NOT NULL,
                `class_subject_id` INTEGER NULL,
                `computed_score` REAL NOT NULL DEFAULT 0,
                `total_score` REAL NOT NULL DEFAULT 0,
                `grade_letter` VARCHAR(5) NULL
            );

            CREATE TABLE `result_publications` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `term_id` INTEGER NOT NULL,
                `class_id` INTEGER NULL,
                `status` VARCHAR(20) NOT NULL DEFAULT 'published',
                `is_published` INTEGER NOT NULL DEFAULT 1,
                `published_at` DATETIME NULL,
                `published_by` INTEGER NULL
            );

            CREATE TABLE `student_promotions` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `student_id` INTEGER NOT NULL,
                `from_session_id` INTEGER NOT NULL,
                `to_session_id` INTEGER NULL,
                `from_class_id` INTEGER NOT NULL,
                `to_class_id` INTEGER NULL,
                `decision` VARCHAR(30) NOT NULL,
                `evaluation_status` VARCHAR(30) NOT NULL DEFAULT 'preliminary',
                `annual_average` REAL NOT NULL DEFAULT 0.0,
                `term_averages` TEXT NULL,
                `approval_request_id` INTEGER NULL,
                `override_reason` TEXT NULL,
                `promoted_by` INTEGER NULL,
                `promoted_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE `approval_requests` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `request_type` VARCHAR(50) NOT NULL,
                `entity_type` VARCHAR(50) NOT NULL,
                `entity_id` INTEGER NULL,
                `requester_id` INTEGER NOT NULL,
                `reviewer_id` INTEGER NULL,
                `status` VARCHAR(20) NOT NULL DEFAULT 'pending',
                `payload` TEXT NULL,
                `review_note` TEXT NULL,
                `reviewed_at` DATETIME NULL,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE `system_settings` (
                `setting_key` VARCHAR(100) PRIMARY KEY,
                `setting_value` TEXT NOT NULL
            );
        ");
    }

    private function seedTestData(): void
    {
        // 1. Admin User
        $this->pdo->exec("
            INSERT INTO users (id, uuid, name, email, password_hash) 
            VALUES (1, 'admin-uuid-1', 'Admin Officer', 'admin@claret.edu', 'hash');
            INSERT INTO user_roles (user_id, role) VALUES (1, 'admin');
        ");
        $this->adminUserId = 1;

        // 2. Sessions (2025/2026 current, 2026/2027 upcoming)
        $this->pdo->exec("
            INSERT INTO sessions (id, name, start_date, end_date, is_current, status) VALUES (1, '2025/2026', '2025-09-01', '2026-07-31', 1, 'active');
            INSERT INTO sessions (id, name, start_date, end_date, is_current, status) VALUES (2, '2026/2027', '2026-09-01', '2027-07-31', 0, 'active');
        ");
        $this->sessionId = 1;
        $this->nextSessionId = 2;

        // 3. Terms for 2025/2026
        $this->pdo->exec("
            INSERT INTO terms (id, name, session_id, start_date, end_date, is_current) VALUES (1, '1st Term', 1, '2025-09-01', '2025-12-15', 0);
            INSERT INTO terms (id, name, session_id, start_date, end_date, is_current) VALUES (2, '2nd Term', 1, '2026-01-10', '2026-04-10', 0);
            INSERT INTO terms (id, name, session_id, start_date, end_date, is_current) VALUES (3, '3rd Term', 1, '2026-04-25', '2026-07-20', 1);
        ");
        $this->term1Id = 1;
        $this->term2Id = 2;
        $this->term3Id = 3;

        // 4. Academic Levels: Primary 5 (Terminal), JSS 1 -> JSS 2, SS 3 (Terminal)
        $this->pdo->exec("
            INSERT INTO academic_levels (id, name, stage, category, rank_order, is_terminal, next_level_id) 
            VALUES (1, 'Primary 5', 'primary', 'primary', 5, 1, NULL);

            INSERT INTO academic_levels (id, name, stage, category, rank_order, is_terminal, next_level_id) 
            VALUES (2, 'Junior Secondary 1', 'secondary', 'secondary', 1, 0, 3);

            INSERT INTO academic_levels (id, name, stage, category, rank_order, is_terminal, next_level_id) 
            VALUES (3, 'Junior Secondary 2', 'secondary', 'secondary', 2, 0, NULL);

            INSERT INTO academic_levels (id, name, stage, category, rank_order, is_terminal, next_level_id) 
            VALUES (4, 'Senior Secondary 3', 'secondary', 'secondary', 6, 1, NULL);
        ");
        $this->pri5LevelId = 1;
        $this->jss1LevelId = 2;
        $this->jss2LevelId = 3;
        $this->ss3LevelId = 4;

        // 5. Classes
        $this->pdo->exec("
            INSERT INTO classes (id, name, academic_level_id, level_id, section_arm) VALUES (1, 'Primary 5 Gold', 1, 1, 'Gold');
            INSERT INTO classes (id, name, academic_level_id, level_id, section_arm) VALUES (2, 'JSS 1 A', 2, 2, 'A');
            INSERT INTO classes (id, name, academic_level_id, level_id, section_arm) VALUES (3, 'JSS 2 A', 3, 3, 'A');
            INSERT INTO classes (id, name, academic_level_id, level_id, section_arm) VALUES (4, 'SS 3 Science', 4, 4, 'Science');
        ");
        $this->pri5ClassId = 1;
        $this->jss1ClassId = 2;
        $this->jss2ClassId = 3;
        $this->ss3ClassId = 4;

        // Subjects & Class Subjects
        $this->pdo->exec("
            INSERT INTO subjects (id, name, code) VALUES (1, 'English Language', 'ENG');
            INSERT INTO class_subjects (id, class_id, subject_id, session_id) VALUES (1, 1, 1, 1);
            INSERT INTO class_subjects (id, class_id, subject_id, session_id) VALUES (2, 2, 1, 1);
            INSERT INTO class_subjects (id, class_id, subject_id, session_id) VALUES (3, 3, 1, 1);
            INSERT INTO class_subjects (id, class_id, subject_id, session_id) VALUES (4, 4, 1, 1);
        ");

        // 6. Students:
        // Pri 5 student (High achiever -> Graduate)
        $this->pdo->exec("
            INSERT INTO users (id, uuid, name, email, password_hash) VALUES (10, 'u-10', 'Pri5 Scholar', 'pri5@claret.edu', 'hash');
            INSERT INTO user_roles (user_id, role) VALUES (10, 'student');
            INSERT INTO students (id, user_id, admission_number, current_class_id) VALUES (1, 10, 'PRI/2026/001', 1);
            INSERT INTO class_enrollments (student_id, class_id, session_id, status) VALUES (1, 1, 1, 'enrolled');
            INSERT INTO student_term_summaries (student_id, term_id, class_id, average_score) VALUES (1, 1, 1, 75.0);
            INSERT INTO student_term_summaries (student_id, term_id, class_id, average_score) VALUES (1, 2, 1, 80.0);
            INSERT INTO student_term_summaries (student_id, term_id, class_id, average_score) VALUES (1, 3, 1, 85.0);
            INSERT INTO term_results (student_id, term_id, class_subject_id, computed_score, total_score, grade_letter) VALUES (1, 3, 1, 85.0, 85.0, 'A');
        ");
        $this->pri5StudentId = 1;

        // SS 3 student (High achiever -> Graduate Alumni)
        $this->pdo->exec("
            INSERT INTO users (id, uuid, name, email, password_hash) VALUES (11, 'u-11', 'SS3 Valedictorian', 'ss3@claret.edu', 'hash');
            INSERT INTO user_roles (user_id, role) VALUES (11, 'student');
            INSERT INTO students (id, user_id, admission_number, current_class_id) VALUES (2, 11, 'SEC/2026/002', 4);
            INSERT INTO class_enrollments (student_id, class_id, session_id, status) VALUES (2, 4, 1, 'enrolled');
            INSERT INTO student_term_summaries (student_id, term_id, class_id, average_score) VALUES (2, 1, 4, 88.0);
            INSERT INTO student_term_summaries (student_id, term_id, class_id, average_score) VALUES (2, 2, 4, 91.0);
            INSERT INTO student_term_summaries (student_id, term_id, class_id, average_score) VALUES (2, 3, 4, 94.0);
            INSERT INTO term_results (student_id, term_id, class_subject_id, computed_score, total_score, grade_letter) VALUES (2, 3, 4, 94.0, 94.0, 'A');
        ");
        $this->ss3StudentId = 2;

        // JSS 1 Passing Student (Avg: 65% -> Promoted to JSS 2)
        $this->pdo->exec("
            INSERT INTO users (id, uuid, name, email, password_hash) VALUES (12, 'u-12', 'JSS1 Passing', 'pass@claret.edu', 'hash');
            INSERT INTO user_roles (user_id, role) VALUES (12, 'student');
            INSERT INTO students (id, user_id, admission_number, current_class_id) VALUES (3, 12, 'SEC/2026/003', 2);
            INSERT INTO class_enrollments (student_id, class_id, session_id, status) VALUES (3, 2, 1, 'enrolled');
            INSERT INTO student_term_summaries (student_id, term_id, class_id, average_score) VALUES (3, 1, 2, 60.0);
            INSERT INTO student_term_summaries (student_id, term_id, class_id, average_score) VALUES (3, 2, 2, 65.0);
            INSERT INTO student_term_summaries (student_id, term_id, class_id, average_score) VALUES (3, 3, 2, 70.0);
            INSERT INTO term_results (student_id, term_id, class_subject_id, computed_score, total_score, grade_letter) VALUES (3, 1, 2, 60.0, 60.0, 'B');
            INSERT INTO term_results (student_id, term_id, class_subject_id, computed_score, total_score, grade_letter) VALUES (3, 2, 2, 65.0, 65.0, 'B');
            INSERT INTO term_results (student_id, term_id, class_subject_id, computed_score, total_score, grade_letter) VALUES (3, 3, 2, 70.0, 70.0, 'A');
        ");
        $this->jss1PassingStudentId = 3;

        // JSS 1 Borderline Student (Avg: 45% -> Borderline Review)
        $this->pdo->exec("
            INSERT INTO users (id, uuid, name, email, password_hash) VALUES (13, 'u-13', 'JSS1 Borderline', 'border@claret.edu', 'hash');
            INSERT INTO user_roles (user_id, role) VALUES (13, 'student');
            INSERT INTO students (id, user_id, admission_number, current_class_id) VALUES (4, 13, 'SEC/2026/004', 2);
            INSERT INTO class_enrollments (student_id, class_id, session_id, status) VALUES (4, 2, 1, 'enrolled');
            INSERT INTO student_term_summaries (student_id, term_id, class_id, average_score) VALUES (4, 1, 2, 42.0);
            INSERT INTO student_term_summaries (student_id, term_id, class_id, average_score) VALUES (4, 2, 2, 46.0);
            INSERT INTO student_term_summaries (student_id, term_id, class_id, average_score) VALUES (4, 3, 2, 47.0);
            INSERT INTO term_results (student_id, term_id, class_subject_id, computed_score, total_score, grade_letter) VALUES (4, 3, 2, 47.0, 47.0, 'P');
        ");
        $this->jss1BorderlineStudentId = 4;

        // JSS 1 Failing Student (Avg: 35% -> Repeating)
        $this->pdo->exec("
            INSERT INTO users (id, uuid, name, email, password_hash) VALUES (14, 'u-14', 'JSS1 Failing', 'fail@claret.edu', 'hash');
            INSERT INTO user_roles (user_id, role) VALUES (14, 'student');
            INSERT INTO students (id, user_id, admission_number, current_class_id) VALUES (5, 14, 'SEC/2026/005', 2);
            INSERT INTO class_enrollments (student_id, class_id, session_id, status) VALUES (5, 2, 1, 'enrolled');
            INSERT INTO student_term_summaries (student_id, term_id, class_id, average_score) VALUES (5, 1, 2, 32.0);
            INSERT INTO student_term_summaries (student_id, term_id, class_id, average_score) VALUES (5, 2, 2, 36.0);
            INSERT INTO student_term_summaries (student_id, term_id, class_id, average_score) VALUES (5, 3, 2, 37.0);
            INSERT INTO term_results (student_id, term_id, class_subject_id, computed_score, total_score, grade_letter) VALUES (5, 3, 2, 37.0, 37.0, 'F');
        ");
        $this->jss1FailingStudentId = 5;
    }

    /**
     * Test 1: Cohort Advancement is locked until 3rd Term results are published.
     */
    public function testCohortAdvancementIsLockedBefore3rdTermPublication(): void
    {
        $cohort = $this->promotionService->evaluateClassCohort($this->jss1ClassId, $this->sessionId);
        $this->assertFalse($cohort['is_final_term_published']);

        // Attempting to execute batch promotion before 3rd term release must be rejected
        $result = $this->promotionService->commitBatchPromotion(
            classId: $this->jss1ClassId,
            fromSessionId: $this->sessionId,
            toSessionId: $this->nextSessionId,
            actorId: $this->adminUserId,
            decisions: []
        );

        $this->assertFalse($result->isSuccess());
        $this->assertStringContainsString('3rd Term results have not yet been approved and officially published', $result->getMessage());
    }

    /**
     * Test 2: Report Card promotion badge is strictly hidden for 1st Term, 2nd Term,
     * and unpublished 3rd Term.
     */
    public function testReportCardPromotionBadgeIsHiddenPriorTo3rdTermRelease(): void
    {
        // 1st Term Report Card
        $reportTerm1 = $this->reportCardService->getReportCardData($this->jss1PassingStudentId, $this->term1Id);
        $this->assertFalse($reportTerm1['is_promotion_visible']);
        $this->assertFalse($reportTerm1['promotion_data']['is_visible']);
        $this->assertStringContainsString('finalized and published exclusively at the conclusion of the 3rd Term', $reportTerm1['promotion_data']['reason']);

        // 3rd Term Report Card BEFORE publication
        $reportTerm3Unpublished = $this->reportCardService->getReportCardData($this->jss1PassingStudentId, $this->term3Id);
        $this->assertFalse($reportTerm3Unpublished['is_promotion_visible']);
        $this->assertFalse($reportTerm3Unpublished['promotion_data']['is_visible']);
        $this->assertStringContainsString('pending official administrative publication', $reportTerm3Unpublished['promotion_data']['reason']);
    }

    /**
     * Test 3: Cumulative annual average computation across all terms.
     */
    public function testCumulativeSessionStatsComputation(): void
    {
        // Passing student: (60.0 + 65.0 + 70.0) / 3 = 65.00
        $stats = $this->promotionRepo->getCumulativeSessionStats($this->jss1PassingStudentId, $this->sessionId);
        $this->assertEquals(65.00, $stats['annual_average']);
        $this->assertEquals(3, $stats['completed_terms_count']);
        $this->assertTrue($stats['has_all_terms']);

        // Borderline student: (42.0 + 46.0 + 47.0) / 3 = 45.00
        $borderStats = $this->promotionRepo->getCumulativeSessionStats($this->jss1BorderlineStudentId, $this->sessionId);
        $this->assertEquals(45.00, $borderStats['annual_average']);

        // Failing student: (32.0 + 36.0 + 37.0) / 3 = 35.00
        $failStats = $this->promotionRepo->getCumulativeSessionStats($this->jss1FailingStudentId, $this->sessionId);
        $this->assertEquals(35.00, $failStats['annual_average']);
    }

    /**
     * Test 4: Publish 3rd Term results -> Unlocks promotion badge on Report Card.
     */
    public function testPublishing3rdTermUnlocksReportCardPromotionBadge(): void
    {
        // Publish 3rd Term results
        $this->pdo->exec("
            INSERT INTO result_publications (term_id, class_id, status, is_published, published_by) 
            VALUES ({$this->term3Id}, NULL, 'published', 1, {$this->adminUserId});
        ");

        $this->assertTrue($this->promotionService->isFinalTermPublished($this->sessionId));

        // Report card for passing student now shows PROMOTED
        $reportData = $this->reportCardService->getReportCardData($this->jss1PassingStudentId, $this->term3Id);
        $this->assertTrue($reportData['is_promotion_visible']);
        $this->assertTrue($reportData['promotion_data']['is_visible']);
        $this->assertEquals('promoted', $reportData['promotion_data']['status']);
        $this->assertEquals('PROMOTED', $reportData['promotion_data']['badge_text']);
        $this->assertEquals(65.00, $reportData['promotion_data']['annual_average']);

        // Report card for Primary 5 terminal student shows GRADUATED
        $pri5Report = $this->reportCardService->getReportCardData($this->pri5StudentId, $this->term3Id);
        $this->assertTrue($pri5Report['is_promotion_visible']);
        $this->assertEquals('graduated', $pri5Report['promotion_data']['status']);
        $this->assertEquals('GRADUATED', $pri5Report['promotion_data']['badge_text']);
    }

    /**
     * Test 5: Terminal class cohort promotion transitions Primary 5 and SS 3 students
     * to 'graduated' alumni status (clearing current_class_id).
     */
    public function testTerminalClassesTransitionToGraduatedAlumniStatus(): void
    {
        // Publish 3rd Term
        $this->pdo->exec("
            INSERT INTO result_publications (term_id, class_id, status, is_published, published_by) 
            VALUES ({$this->term3Id}, NULL, 'published', 1, {$this->adminUserId});
        ");

        // Execute batch for Primary 5 (Terminal level)
        $result = $this->promotionService->commitBatchPromotion(
            classId: $this->pri5ClassId,
            fromSessionId: $this->sessionId,
            toSessionId: $this->nextSessionId,
            actorId: $this->adminUserId,
            decisions: []
        );

        $this->assertTrue($result->isSuccess(), (string)($result->getMessage() ?? ''));
        $this->assertEquals(1, $result->getData()['graduated_count']);

        // Verify database: promotion record is 'graduated'
        $promo = $this->promotionRepo->findByStudentAndSession($this->pri5StudentId, $this->sessionId);
        $this->assertNotNull($promo);
        $this->assertTrue($promo->isGraduated());
        $this->assertEquals('graduated', $promo->decision);
        $this->assertNull($promo->toClassId);

        // Verify student record: current_class_id has been cleared for graduated alumnus
        $student = $this->studentRepo->findById($this->pri5StudentId);
        $this->assertNull($student->currentClassId);

        // Verify previous enrollment status updated to 'graduated'
        $enrollment = $this->enrollmentRepo->findClassEnrollment($this->pri5StudentId, $this->sessionId);
        $this->assertNotNull($enrollment);
        $this->assertEquals('graduated', $enrollment->status);

        // Now test SS 3 (Terminal class)
        $ss3Result = $this->promotionService->commitBatchPromotion(
            classId: $this->ss3ClassId,
            fromSessionId: $this->sessionId,
            toSessionId: $this->nextSessionId,
            actorId: $this->adminUserId,
            decisions: []
        );

        $this->assertTrue($ss3Result->isSuccess());
        $this->assertEquals(1, $ss3Result->getData()['graduated_count']);

        $ss3Promo = $this->promotionRepo->findByStudentAndSession($this->ss3StudentId, $this->sessionId);
        $this->assertNotNull($ss3Promo);
        $this->assertTrue($ss3Promo->isGraduated());

        $ss3Student = $this->studentRepo->findById($this->ss3StudentId);
        $this->assertNull($ss3Student->currentClassId);
    }

    /**
     * Test 6: Intermediate class advancement (JSS 1 -> JSS 2) advances passing students
     * and retains failing students.
     */
    public function testIntermediateClassCohortAdvancement(): void
    {
        // Publish 3rd Term
        $this->pdo->exec("
            INSERT INTO result_publications (term_id, class_id, status, is_published, published_by) 
            VALUES ({$this->term3Id}, NULL, 'published', 1, {$this->adminUserId});
        ");

        // Execute batch for JSS 1 with target class JSS 2 A
        $result = $this->promotionService->commitBatchPromotion(
            classId: $this->jss1ClassId,
            fromSessionId: $this->sessionId,
            toSessionId: $this->nextSessionId,
            actorId: $this->adminUserId,
            decisions: [
                // Passing student promoted to JSS 2 A
                $this->jss1PassingStudentId => ['decision' => 'promoted', 'to_class_id' => $this->jss2ClassId],
                // Failing student repeating JSS 1 A
                $this->jss1FailingStudentId => ['decision' => 'repeating', 'to_class_id' => $this->jss1ClassId],
            ]
        );

        $this->assertTrue($result->isSuccess(), (string)($result->getMessage() ?? ''));
        $data = $result->getData();
        $this->assertEquals(1, $data['promoted_count']);
        $this->assertEquals(1, $data['repeating_count']);

        // Check passing student:
        $passPromo = $this->promotionRepo->findByStudentAndSession($this->jss1PassingStudentId, $this->sessionId);
        $this->assertNotNull($passPromo);
        $this->assertTrue($passPromo->isPromoted());
        $this->assertEquals($this->jss2ClassId, $passPromo->toClassId);

        $updatedPassStudent = $this->studentRepo->findById($this->jss1PassingStudentId);
        $this->assertEquals($this->jss2ClassId, $updatedPassStudent->currentClassId);

        // Check enrollment for next session:
        $newEnrollment = $this->enrollmentRepo->findClassEnrollment($this->jss1PassingStudentId, $this->nextSessionId);
        $this->assertNotNull($newEnrollment);
        $this->assertEquals($this->jss2ClassId, $newEnrollment->classId);

        // Check failing student:
        $failPromo = $this->promotionRepo->findByStudentAndSession($this->jss1FailingStudentId, $this->sessionId);
        $this->assertNotNull($failPromo);
        $this->assertTrue($failPromo->isRepeating());
        $this->assertEquals($this->jss1ClassId, $failPromo->toClassId);

        $updatedFailStudent = $this->studentRepo->findById($this->jss1FailingStudentId);
        $this->assertEquals($this->jss1ClassId, $updatedFailStudent->currentClassId);
    }

    /**
     * Test 7: Staging borderline repetition dispatches to Super Admin two-tier queue.
     */
    public function testStagingBorderlineRepetitionDispatchesToApprovalQueue(): void
    {
        $result = $this->promotionService->stageBorderlineRepetition(
            studentId: $this->jss1BorderlineStudentId,
            classId: $this->jss1ClassId,
            sessionId: $this->sessionId,
            actorId: $this->adminUserId,
            reason: 'Critical deficiency in Mathematics and Basic Science requires repeating cohort.'
        );

        $this->assertTrue($result->isSuccess(), (string)($result->getMessage() ?? ''));

        // Verify an ApprovalRequest with TYPE_STUDENT_REPETITION was created
        $requests = $this->approvalRepo->getRequests(
            status: 'pending',
            requestType: ApprovalRequest::TYPE_STUDENT_REPETITION
        );

        $this->assertCount(1, $requests);
        $req = $requests[0];
        $this->assertEquals(ApprovalRequest::TYPE_STUDENT_REPETITION, $req->requestType);
        $this->assertEquals($this->jss1BorderlineStudentId, $req->payload['student_id']);
        $this->assertStringContainsString('Critical deficiency', $req->payload['reason']);

        // Verify promotion record was staged
        $stagedPromo = $this->promotionRepo->findByStudentAndSession($this->jss1BorderlineStudentId, $this->sessionId);
        $this->assertNotNull($stagedPromo);
        $this->assertEquals(Promotion::STATUS_STAGED_REVIEW, $stagedPromo->evaluationStatus);
        $this->assertEquals(Promotion::DECISION_REPEATING, $stagedPromo->decision);
        $this->assertEquals($req->id, $stagedPromo->approvalRequestId);
    }
}
