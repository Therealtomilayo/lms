<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Core\Exceptions\DomainRuleException;
use App\Core\UserContext;
use App\Models\AssessmentCategory;
use App\Models\User;
use App\Policies\GradebookPolicy;
use App\Policies\ResultPolicy;
use App\Repositories\AcademicRepository;
use App\Repositories\EnrollmentRepository;
use App\Repositories\GradebookRepository;
use App\Repositories\GradingScaleRepository;
use App\Repositories\ParentRepository;
use App\Repositories\ResultPublicationRepository;
use App\Repositories\StudentRepository;
use App\Repositories\TeacherRepository;
use App\Repositories\UserRepository;
use App\Services\GradebookService;
use App\Services\ReportCardService;
use App\Services\ResultPublicationService;
use PDO;
use PHPUnit\Framework\TestCase;

final class GradebookAndReportCardLifecycleIntegrationTest extends TestCase
{
    private PDO $pdo;
    private UserRepository $userRepo;
    private StudentRepository $studentRepo;
    private ParentRepository $parentRepo;
    private TeacherRepository $teacherRepo;
    private AcademicRepository $academicRepo;
    private EnrollmentRepository $enrollmentRepo;
    private GradingScaleRepository $gradingScaleRepo;
    private GradebookRepository $gradebookRepo;
    private ResultPublicationRepository $publicationRepo;

    private GradebookService $gradebookService;
    private ResultPublicationService $publicationService;
    private ReportCardService $reportCardService;

    private User $adminUser;
    private User $teacherUser;
    private User $unassignedTeacherUser;
    private User $student1User;
    private User $student2User;
    private User $student3User;
    private User $parentUser;
    private User $unrelatedParentUser;

    private int $teacherId;
    private int $unassignedTeacherId;
    private int $student1Id;
    private int $student2Id;
    private int $student3Id;
    private int $parentId;
    private int $unrelatedParentId;
    private int $sessionId;
    private int $termId;
    private int $classId;
    private int $subject1Id;
    private int $subject2Id;
    private int $classSubject1Id;
    private int $classSubject2Id;
    private int $gradingScaleId;

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
                UNIQUE(`user_id`, `role`)
            );

            CREATE TABLE `academic_levels` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `name` VARCHAR(100) NOT NULL,
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
                `is_current` INTEGER NOT NULL DEFAULT 0,
                `status` VARCHAR(20) NOT NULL DEFAULT 'planned',
                `created_at` DATETIME NOT NULL,
                `updated_at` DATETIME NOT NULL
            );

            CREATE TABLE `terms` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `session_id` INTEGER NOT NULL,
                `name` VARCHAR(100) NOT NULL,
                `start_date` DATE NOT NULL,
                `end_date` DATE NOT NULL,
                `is_current` INTEGER NOT NULL DEFAULT 0,
                `status` VARCHAR(20) NOT NULL DEFAULT 'pending',
                `created_at` DATETIME NOT NULL,
                `updated_at` DATETIME NOT NULL
            );

            CREATE TABLE `subjects` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `code` VARCHAR(30) NOT NULL UNIQUE,
                `name` VARCHAR(120) NOT NULL,
                `category` VARCHAR(50) NULL,
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

            CREATE TABLE `students` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `user_id` INTEGER NOT NULL UNIQUE,
                `admission_number` VARCHAR(50) NOT NULL UNIQUE,
                `date_of_birth` DATE NULL,
                `gender` VARCHAR(10) NULL,
                `current_class_id` INTEGER NULL,
                `created_at` DATETIME NOT NULL,
                `updated_at` DATETIME NOT NULL
            );

            CREATE TABLE `parents` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `user_id` INTEGER NOT NULL UNIQUE,
                `created_at` DATETIME NOT NULL,
                `updated_at` DATETIME NOT NULL
            );

            CREATE TABLE `parent_student` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `parent_id` INTEGER NOT NULL,
                `student_id` INTEGER NOT NULL,
                `relationship_type` VARCHAR(50) NULL,
                `created_at` DATETIME NOT NULL,
                UNIQUE(`parent_id`, `student_id`)
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
                UNIQUE(`session_id`, `class_id`, `subject_id`)
            );

            CREATE TABLE `class_enrollments` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `student_id` INTEGER NOT NULL,
                `class_id` INTEGER NOT NULL,
                `session_id` INTEGER NOT NULL,
                `status` VARCHAR(20) NOT NULL DEFAULT 'active',
                `created_at` DATETIME NOT NULL,
                `updated_at` DATETIME NOT NULL,
                UNIQUE(`student_id`, `session_id`)
            );

            CREATE TABLE `student_subject_enrollments` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `student_id` INTEGER NOT NULL,
                `class_subject_id` INTEGER NOT NULL,
                `session_id` INTEGER NOT NULL,
                `is_elective` INTEGER NOT NULL DEFAULT 0,
                `status` VARCHAR(20) NOT NULL DEFAULT 'active',
                `created_at` DATETIME NOT NULL,
                `updated_at` DATETIME NOT NULL,
                UNIQUE(`student_id`, `class_subject_id`, `session_id`)
            );

            CREATE TABLE `grading_scales` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `name` VARCHAR(100) NOT NULL,
                `description` TEXT NULL,
                `is_default` INTEGER NOT NULL DEFAULT 0,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE `grade_boundaries` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `grading_scale_id` INTEGER NOT NULL,
                `letter` VARCHAR(5) NOT NULL,
                `min_score` DECIMAL(5,2) NOT NULL,
                `max_score` DECIMAL(5,2) NOT NULL,
                `grade_point` DECIMAL(4,2) NULL,
                `remark` VARCHAR(100) NULL,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE `assessment_categories` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `session_id` INTEGER NOT NULL,
                `term_id` INTEGER NOT NULL,
                `class_subject_id` INTEGER NULL,
                `name` VARCHAR(100) NOT NULL,
                `weight_percentage` DECIMAL(5,2) NOT NULL,
                `max_points` DECIMAL(5,2) NOT NULL DEFAULT 100.00,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE `student_assessment_scores` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `assessment_category_id` INTEGER NOT NULL,
                `student_id` INTEGER NOT NULL,
                `class_subject_id` INTEGER NOT NULL,
                `raw_score` DECIMAL(5,2) NOT NULL,
                `recorded_by` INTEGER NOT NULL,
                `recorded_at` DATETIME NOT NULL,
                UNIQUE(`assessment_category_id`, `student_id`, `class_subject_id`)
            );

            CREATE TABLE `term_results` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `student_id` INTEGER NOT NULL,
                `class_subject_id` INTEGER NOT NULL,
                `term_id` INTEGER NOT NULL,
                `computed_score` DECIMAL(5,2) NOT NULL,
                `grade_letter` VARCHAR(5) NOT NULL,
                `grade_point` DECIMAL(4,2) NULL,
                `remark` VARCHAR(100) NULL,
                `breakdown_json` TEXT NOT NULL,
                `is_locked` INTEGER NOT NULL DEFAULT 0,
                `locked_at` DATETIME NULL,
                `locked_by` INTEGER NULL,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                UNIQUE(`student_id`, `class_subject_id`, `term_id`)
            );

            CREATE TABLE `student_term_summaries` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `student_id` INTEGER NOT NULL,
                `term_id` INTEGER NOT NULL,
                `class_id` INTEGER NOT NULL,
                `total_score` DECIMAL(8,2) NULL,
                `average_score` DECIMAL(5,2) NULL,
                `gpa` DECIMAL(4,2) NULL,
                `rank_in_class` INTEGER NULL,
                `attendance_present_count` INTEGER NOT NULL DEFAULT 0,
                `attendance_total_count` INTEGER NOT NULL DEFAULT 0,
                `class_teacher_remark` TEXT NULL,
                `principal_remark` TEXT NULL,
                `promotion_status` VARCHAR(20) NOT NULL DEFAULT 'pending',
                `is_locked` INTEGER NOT NULL DEFAULT 0,
                `locked_at` DATETIME NULL,
                `locked_by` INTEGER NULL,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                UNIQUE(`student_id`, `term_id`, `class_id`)
            );

            CREATE TABLE `result_publications` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `term_id` INTEGER NOT NULL,
                `class_id` INTEGER NULL,
                `published_by` INTEGER NOT NULL,
                `published_at` DATETIME NOT NULL,
                `unpublished_at` DATETIME NULL,
                `status` VARCHAR(20) NOT NULL DEFAULT 'published',
                `reason` TEXT NULL,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                UNIQUE(`term_id`, `class_id`)
            );
        ");

        $this->userRepo = new UserRepository($this->pdo);
        $this->studentRepo = new StudentRepository($this->pdo);
        $this->parentRepo = new ParentRepository($this->pdo);
        $this->teacherRepo = new TeacherRepository($this->pdo);
        $this->academicRepo = new AcademicRepository($this->pdo);
        $this->enrollmentRepo = new EnrollmentRepository($this->pdo);
        $this->gradingScaleRepo = new GradingScaleRepository($this->pdo);
        $this->gradebookRepo = new GradebookRepository($this->pdo);
        $this->publicationRepo = new ResultPublicationRepository($this->pdo);

        $this->gradebookService = new GradebookService(
            $this->gradebookRepo,
            $this->gradingScaleRepo,
            $this->enrollmentRepo,
            $this->academicRepo
        );
        $this->publicationService = new ResultPublicationService(
            $this->publicationRepo,
            $this->gradebookRepo
        );
        $this->reportCardService = new ReportCardService(
            $this->gradebookRepo,
            $this->studentRepo,
            $this->academicRepo
        );

        $this->seedFixtureData();
    }

    private function seedFixtureData(): void
    {
        $now = date('Y-m-d H:i:s');

        // Admin User
        $this->pdo->prepare("INSERT INTO users (uuid, name, email, password_hash, status, created_at, updated_at) VALUES ('u-admin', 'Admin User', 'admin@claret.edu', 'hash', 'active', :c, :u)")->execute([':c' => $now, ':u' => $now]);
        $adminUserId = (int)$this->pdo->lastInsertId();
        $this->pdo->exec("INSERT INTO user_roles (user_id, role, created_at) VALUES ({$adminUserId}, 'admin', '{$now}')");
        $this->adminUser = $this->userRepo->findById($adminUserId);

        // Teacher User
        $this->pdo->prepare("INSERT INTO users (uuid, name, email, password_hash, status, created_at, updated_at) VALUES ('u-teach', 'Teacher John', 'john@claret.edu', 'hash', 'active', :c, :u)")->execute([':c' => $now, ':u' => $now]);
        $teacherUserId = (int)$this->pdo->lastInsertId();
        $this->pdo->exec("INSERT INTO user_roles (user_id, role, created_at) VALUES ({$teacherUserId}, 'teacher', '{$now}')");
        $this->pdo->exec("INSERT INTO teachers (user_id, staff_id, created_at, updated_at) VALUES ({$teacherUserId}, 'EMP001', '{$now}', '{$now}')");
        $this->teacherId = (int)$this->pdo->lastInsertId();
        $this->teacherUser = $this->userRepo->findById($teacherUserId);

        // Unassigned Teacher User
        $this->pdo->prepare("INSERT INTO users (uuid, name, email, password_hash, status, created_at, updated_at) VALUES ('u-teach2', 'Teacher Alice', 'alice@claret.edu', 'hash', 'active', :c, :u)")->execute([':c' => $now, ':u' => $now]);
        $unassignedTeacherUserId = (int)$this->pdo->lastInsertId();
        $this->pdo->exec("INSERT INTO user_roles (user_id, role, created_at) VALUES ({$unassignedTeacherUserId}, 'teacher', '{$now}')");
        $this->pdo->exec("INSERT INTO teachers (user_id, staff_id, created_at, updated_at) VALUES ({$unassignedTeacherUserId}, 'EMP002', '{$now}', '{$now}')");
        $this->unassignedTeacherId = (int)$this->pdo->lastInsertId();
        $this->unassignedTeacherUser = $this->userRepo->findById($unassignedTeacherUserId);

        // Student 1 (Top performer)
        $this->pdo->prepare("INSERT INTO users (uuid, name, email, password_hash, status, created_at, updated_at) VALUES ('u-s1', 'Student One', 's1@claret.edu', 'hash', 'active', :c, :u)")->execute([':c' => $now, ':u' => $now]);
        $s1UserId = (int)$this->pdo->lastInsertId();
        $this->pdo->exec("INSERT INTO user_roles (user_id, role, created_at) VALUES ({$s1UserId}, 'student', '{$now}')");
        $this->pdo->exec("INSERT INTO students (user_id, admission_number, created_at, updated_at) VALUES ({$s1UserId}, 'ADM-001', '{$now}', '{$now}')");
        $this->student1Id = (int)$this->pdo->lastInsertId();
        $this->student1User = $this->userRepo->findById($s1UserId);

        // Student 2 (Middle performer)
        $this->pdo->prepare("INSERT INTO users (uuid, name, email, password_hash, status, created_at, updated_at) VALUES ('u-s2', 'Student Two', 's2@claret.edu', 'hash', 'active', :c, :u)")->execute([':c' => $now, ':u' => $now]);
        $s2UserId = (int)$this->pdo->lastInsertId();
        $this->pdo->exec("INSERT INTO user_roles (user_id, role, created_at) VALUES ({$s2UserId}, 'student', '{$now}')");
        $this->pdo->exec("INSERT INTO students (user_id, admission_number, created_at, updated_at) VALUES ({$s2UserId}, 'ADM-002', '{$now}', '{$now}')");
        $this->student2Id = (int)$this->pdo->lastInsertId();
        $this->student2User = $this->userRepo->findById($s2UserId);

        // Student 3 (Tied performer or lower)
        $this->pdo->prepare("INSERT INTO users (uuid, name, email, password_hash, status, created_at, updated_at) VALUES ('u-s3', 'Student Three', 's3@claret.edu', 'hash', 'active', :c, :u)")->execute([':c' => $now, ':u' => $now]);
        $s3UserId = (int)$this->pdo->lastInsertId();
        $this->pdo->exec("INSERT INTO user_roles (user_id, role, created_at) VALUES ({$s3UserId}, 'student', '{$now}')");
        $this->pdo->exec("INSERT INTO students (user_id, admission_number, created_at, updated_at) VALUES ({$s3UserId}, 'ADM-003', '{$now}', '{$now}')");
        $this->student3Id = (int)$this->pdo->lastInsertId();
        $this->student3User = $this->userRepo->findById($s3UserId);

        // Parent User (Linked to Student 1)
        $this->pdo->prepare("INSERT INTO users (uuid, name, email, password_hash, status, created_at, updated_at) VALUES ('u-p1', 'Parent One', 'parent1@claret.edu', 'hash', 'active', :c, :u)")->execute([':c' => $now, ':u' => $now]);
        $p1UserId = (int)$this->pdo->lastInsertId();
        $this->pdo->exec("INSERT INTO user_roles (user_id, role, created_at) VALUES ({$p1UserId}, 'parent', '{$now}')");
        $this->pdo->exec("INSERT INTO parents (user_id, created_at, updated_at) VALUES ({$p1UserId}, '{$now}', '{$now}')");
        $this->parentId = (int)$this->pdo->lastInsertId();
        $this->pdo->exec("INSERT INTO parent_student (parent_id, student_id, relationship_type, created_at) VALUES ({$this->parentId}, {$this->student1Id}, 'Father', '{$now}')");
        $this->parentUser = $this->userRepo->findById($p1UserId);

        // Unrelated Parent User
        $this->pdo->prepare("INSERT INTO users (uuid, name, email, password_hash, status, created_at, updated_at) VALUES ('u-p2', 'Parent Two', 'parent2@claret.edu', 'hash', 'active', :c, :u)")->execute([':c' => $now, ':u' => $now]);
        $p2UserId = (int)$this->pdo->lastInsertId();
        $this->pdo->exec("INSERT INTO user_roles (user_id, role, created_at) VALUES ({$p2UserId}, 'parent', '{$now}')");
        $this->pdo->exec("INSERT INTO parents (user_id, created_at, updated_at) VALUES ({$p2UserId}, '{$now}', '{$now}')");
        $this->unrelatedParentId = (int)$this->pdo->lastInsertId();
        $this->unrelatedParentUser = $this->userRepo->findById($p2UserId);

        // Academic Structure
        $this->pdo->exec("INSERT INTO sessions (name, start_date, end_date, is_current, status, created_at, updated_at) VALUES ('2026/2027', '2026-09-01', '2027-07-31', 1, 'active', '{$now}', '{$now}')");
        $this->sessionId = (int)$this->pdo->lastInsertId();

        $this->pdo->exec("INSERT INTO terms (session_id, name, start_date, end_date, is_current, status, created_at, updated_at) VALUES ({$this->sessionId}, 'First Term', '2026-09-01', '2026-12-15', 1, 'active', '{$now}', '{$now}')");
        $this->termId = (int)$this->pdo->lastInsertId();

        $this->pdo->exec("INSERT INTO academic_levels (name, stage, rank_order, created_at, updated_at) VALUES ('SS 1', 'senior', 1, '{$now}', '{$now}')");
        $levelId = (int)$this->pdo->lastInsertId();

        $this->pdo->exec("INSERT INTO classes (academic_level_id, name, section_arm, status, created_at, updated_at) VALUES ({$levelId}, 'SS 1 Science', 'A', 'active', '{$now}', '{$now}')");
        $this->classId = (int)$this->pdo->lastInsertId();

        $this->pdo->exec("INSERT INTO subjects (name, code, category, status, created_at, updated_at) VALUES ('Mathematics', 'MTH101', 'core', 'active', '{$now}', '{$now}')");
        $this->subject1Id = (int)$this->pdo->lastInsertId();

        $this->pdo->exec("INSERT INTO subjects (name, code, category, status, created_at, updated_at) VALUES ('English Language', 'ENG101', 'core', 'active', '{$now}', '{$now}')");
        $this->subject2Id = (int)$this->pdo->lastInsertId();

        // Assign Teacher John to Mathematics & English for SS 1 Science
        $this->pdo->exec("INSERT INTO class_subjects (class_id, subject_id, teacher_id, session_id, status, created_at, updated_at) VALUES ({$this->classId}, {$this->subject1Id}, {$this->teacherId}, {$this->sessionId}, 'active', '{$now}', '{$now}')");
        $this->classSubject1Id = (int)$this->pdo->lastInsertId();

        $this->pdo->exec("INSERT INTO class_subjects (class_id, subject_id, teacher_id, session_id, status, created_at, updated_at) VALUES ({$this->classId}, {$this->subject2Id}, {$this->teacherId}, {$this->sessionId}, 'active', '{$now}', '{$now}')");
        $this->classSubject2Id = (int)$this->pdo->lastInsertId();

        // Enroll students in class and subjects
        foreach ([$this->student1Id, $this->student2Id, $this->student3Id] as $sId) {
            $this->pdo->exec("INSERT INTO class_enrollments (student_id, class_id, session_id, status, created_at, updated_at) VALUES ({$sId}, {$this->classId}, {$this->sessionId}, 'active', '{$now}', '{$now}')");
            $this->pdo->exec("INSERT INTO student_subject_enrollments (student_id, class_subject_id, session_id, status, created_at, updated_at) VALUES ({$sId}, {$this->classSubject1Id}, {$this->sessionId}, 'active', '{$now}', '{$now}')");
            $this->pdo->exec("INSERT INTO student_subject_enrollments (student_id, class_subject_id, session_id, status, created_at, updated_at) VALUES ({$sId}, {$this->classSubject2Id}, {$this->sessionId}, 'active', '{$now}', '{$now}')");
        }

        // Configure Standard Grading Scale & Boundaries
        $this->gradingScaleId = $this->gradingScaleRepo->createScale([
            'name' => 'Standard Secondary Grading Scale',
            'description' => 'Official 5.0 GPA Scale',
            'is_default' => 1,
        ]);

        $this->gradingScaleRepo->syncBoundaries($this->gradingScaleId, [
            ['letter' => 'A', 'min_score' => 70.0, 'max_score' => 100.0, 'grade_point' => 5.0, 'remark' => 'Excellent'],
            ['letter' => 'B', 'min_score' => 60.0, 'max_score' => 69.99, 'grade_point' => 4.0, 'remark' => 'Very Good'],
            ['letter' => 'C', 'min_score' => 50.0, 'max_score' => 59.99, 'grade_point' => 3.0, 'remark' => 'Credit'],
            ['letter' => 'D', 'min_score' => 45.0, 'max_score' => 49.99, 'grade_point' => 2.0, 'remark' => 'Pass'],
            ['letter' => 'E', 'min_score' => 40.0, 'max_score' => 44.99, 'grade_point' => 1.0, 'remark' => 'Fair'],
            ['letter' => 'F', 'min_score' => 0.0,  'max_score' => 39.99, 'grade_point' => 0.0, 'remark' => 'Fail'],
        ]);
    }

    public function testGradingScaleResolution(): void
    {
        $scale = $this->gradingScaleRepo->getDefaultScale();
        $this->assertNotNull($scale);
        $this->assertCount(6, $scale->boundaries);

        $gradeA = $scale->resolveGrade(85.5);
        $this->assertNotNull($gradeA);
        $this->assertSame('A', $gradeA->letter);
        $this->assertEquals(5.0, $gradeA->gradePoint);

        $gradeB = $scale->resolveGrade(65.0);
        $this->assertNotNull($gradeB);
        $this->assertSame('B', $gradeB->letter);

        $gradeC = $scale->resolveGrade(54.0);
        $this->assertNotNull($gradeC);
        $this->assertSame('C', $gradeC->letter);

        $gradeF = $scale->resolveGrade(32.0);
        $this->assertNotNull($gradeF);
        $this->assertSame('F', $gradeF->letter);
    }

    public function testAssessmentCategoryWeightValidation(): void
    {
        // Case 1: Weights that do not sum to 100% must throw DomainRuleException
        $invalidCategories = [
            AssessmentCategory::fromArray(['id' => 1, 'name' => 'CA 1', 'weight_percentage' => 30.0, 'max_points' => 30.0]),
            AssessmentCategory::fromArray(['id' => 2, 'name' => 'Exam', 'weight_percentage' => 60.0, 'max_points' => 60.0]),
        ];

        $this->expectException(DomainRuleException::class);
        $this->gradebookService->validateCategoryWeights($invalidCategories);
    }

    public function testCompleteGradebookAndReportCardLifecycle(): void
    {
        // 1. Configure valid assessment categories totaling 100%
        $cat1Id = $this->gradebookRepo->createCategory([
            'session_id' => $this->sessionId,
            'term_id' => $this->termId,
            'name' => 'Continuous Assessment 1',
            'weight_percentage' => 20.0,
            'max_points' => 20.0,
        ]);
        $cat2Id = $this->gradebookRepo->createCategory([
            'session_id' => $this->sessionId,
            'term_id' => $this->termId,
            'name' => 'Continuous Assessment 2',
            'weight_percentage' => 20.0,
            'max_points' => 20.0,
        ]);
        $cat3Id = $this->gradebookRepo->createCategory([
            'session_id' => $this->sessionId,
            'term_id' => $this->termId,
            'name' => 'Terminal Examination',
            'weight_percentage' => 60.0,
            'max_points' => 100.0,
        ]);

        $categories = $this->gradebookRepo->getCategoriesByContext($this->sessionId, $this->termId);
        $this->assertCount(3, $categories);
        $this->gradebookService->validateCategoryWeights($categories);

        // 2. Test Authorization Policies for Teacher Score Entry
        $teacherContext = UserContext::fromUser($this->teacherUser);
        $unassignedTeacherContext = UserContext::fromUser($this->unassignedTeacherUser);
        $adminContext = UserContext::fromUser($this->adminUser);

        $classSubject1 = $this->academicRepo->findClassSubjectById($this->classSubject1Id);
        $this->assertNotNull($classSubject1);
        $this->assertTrue(GradebookPolicy::canView($teacherContext, $classSubject1, $this->teacherRepo));
        $this->assertTrue(GradebookPolicy::canSaveScores($teacherContext, $classSubject1, false, $this->teacherRepo));

        $this->assertFalse(GradebookPolicy::canView($unassignedTeacherContext, $classSubject1, $this->teacherRepo));
        $this->assertFalse(GradebookPolicy::canSaveScores($unassignedTeacherContext, $classSubject1, false, $this->teacherRepo));

        // Admin can view and save
        $this->assertTrue(GradebookPolicy::canView($adminContext, $classSubject1, $this->teacherRepo));
        $this->assertTrue(GradebookPolicy::canSaveScores($adminContext, $classSubject1, false, $this->teacherRepo));

        // 3. Teacher Enters Scores for Mathematics
        // Student 1: CA1=18/20 (18%), CA2=19/20 (19%), Exam=85/100 (51%) -> Total = 88% (A)
        // Student 2: CA1=14/20 (14%), CA2=12/20 (12%), Exam=60/100 (36%) -> Total = 62% (B)
        // Student 3: CA1=10/20 (10%), CA2=8/20  (8%),  Exam=40/100 (24%) -> Total = 42% (E)
        $scoresMath = [
            $this->student1Id => [$cat1Id => 18.0, $cat2Id => 19.0, $cat3Id => 85.0],
            $this->student2Id => [$cat1Id => 14.0, $cat2Id => 12.0, $cat3Id => 60.0],
            $this->student3Id => [$cat1Id => 10.0, $cat2Id => 8.0,  $cat3Id => 40.0],
        ];

        $saveResult = $this->gradebookService->saveScores(
            $this->classSubject1Id,
            $this->sessionId,
            $this->termId,
            $scoresMath,
            $this->teacherUser->id
        );
        $this->assertTrue($saveResult->success);

        // Teacher Enters Scores for English Language
        // Student 1: CA1=16/20 (16%), CA2=17/20 (17%), Exam=80/100 (48%) -> Total = 81% (A)
        // Student 2: CA1=15/20 (15%), CA2=15/20 (15%), Exam=70/100 (42%) -> Total = 72% (A)
        // Student 3: CA1=10/20 (10%), CA2=10/20 (10%), Exam=40/100 (24%) -> Total = 44% (E)
        $scoresEng = [
            $this->student1Id => [$cat1Id => 16.0, $cat2Id => 17.0, $cat3Id => 80.0],
            $this->student2Id => [$cat1Id => 15.0, $cat2Id => 15.0, $cat3Id => 70.0],
            $this->student3Id => [$cat1Id => 10.0, $cat2Id => 10.0, $cat3Id => 40.0],
        ];

        $this->gradebookService->saveScores(
            $this->classSubject2Id,
            $this->sessionId,
            $this->termId,
            $scoresEng,
            $this->teacherUser->id
        );

        // 4. Compute Subject Term Results & verify calculations & JSON snapshot stability
        $mathComputeResult = $this->gradebookService->computeClassSubjectResults(
            $this->classSubject1Id,
            $this->sessionId,
            $this->termId,
            false
        );
        $this->assertTrue($mathComputeResult->success);

        $engComputeResult = $this->gradebookService->computeClassSubjectResults(
            $this->classSubject2Id,
            $this->sessionId,
            $this->termId,
            false
        );
        $this->assertTrue($engComputeResult->success);

        $s1Results = $this->gradebookRepo->getTermResultsByStudent($this->student1Id, $this->termId);
        $this->assertCount(2, $s1Results);
        // English Language (first alphabetically)
        $this->assertEquals(81.0, $s1Results[0]->computedScore);
        $this->assertSame('A', $s1Results[0]->gradeLetter);
        $this->assertEquals(5.0, $s1Results[0]->gradePoint);
        $this->assertNotEmpty($s1Results[0]->breakdown);

        // Mathematics (second alphabetically)
        $this->assertEquals(88.0, $s1Results[1]->computedScore);
        $this->assertSame('A', $s1Results[1]->gradeLetter);
        $this->assertEquals(5.0, $s1Results[1]->gradePoint);
        $this->assertNotEmpty($s1Results[1]->breakdown);

        // 5. Compute Class Term Summaries & Deterministic Rankings
        $classSummaryResult = $this->gradebookService->computeClassTermSummaries(
            $this->classId,
            $this->sessionId,
            $this->termId,
            false
        );
        $this->assertTrue($classSummaryResult->success);

        $s1Summary = $this->gradebookRepo->findStudentTermSummary($this->student1Id, $this->termId);
        $s2Summary = $this->gradebookRepo->findStudentTermSummary($this->student2Id, $this->termId);
        $s3Summary = $this->gradebookRepo->findStudentTermSummary($this->student3Id, $this->termId);

        $this->assertNotNull($s1Summary);
        $this->assertNotNull($s2Summary);
        $this->assertNotNull($s3Summary);

        // S1 Avg = (88 + 81) / 2 = 84.5% -> Rank 1
        // S2 Avg = (62 + 72) / 2 = 67.0% -> Rank 2
        // S3 Avg = (42 + 44) / 2 = 43.0% -> Rank 3
        $this->assertSame(1, $s1Summary->rankInClass);
        $this->assertEquals(84.5, $s1Summary->averageScore);

        $this->assertSame(2, $s2Summary->rankInClass);
        $this->assertEquals(67.0, $s2Summary->averageScore);

        $this->assertSame(3, $s3Summary->rankInClass);
        $this->assertEquals(43.0, $s3Summary->averageScore);

        // 6. Test Result Locking
        $this->gradebookRepo->lockClassSubjectResults($this->classSubject1Id, $this->termId, $this->adminUser->id);
        $this->assertTrue($this->gradebookRepo->isClassSubjectLocked($this->classSubject1Id, $this->termId));

        // Teacher trying to modify locked scores must fail policy & service check
        $this->assertFalse(GradebookPolicy::canSaveScores($teacherContext, $classSubject1, true, $this->teacherRepo));

        try {
            $this->gradebookService->saveScores(
                $this->classSubject1Id,
                $this->sessionId,
                $this->termId,
                $scoresMath,
                $this->teacherUser->id
            );
            $this->fail('Expected DomainRuleException when attempting to save scores in locked gradebook.');
        } catch (DomainRuleException $e) {
            $this->assertStringContainsString('locked', $e->getMessage());
        }

        // 7. Test Publication Gating
        $student1Context = UserContext::fromUser($this->student1User);
        $student2Context = UserContext::fromUser($this->student2User);
        $parent1Context = UserContext::fromUser($this->parentUser);
        $unrelatedParentContext = UserContext::fromUser($this->unrelatedParentUser);

        // Initially UNPUBLISHED: Students & Parents are gated
        $this->assertFalse($this->publicationRepo->isPublished($this->termId));
        $this->assertFalse(ResultPolicy::canViewStudentResults($student1Context, $this->student1Id, false, $this->parentRepo, $this->studentRepo));
        $this->assertFalse(ResultPolicy::canViewStudentResults($parent1Context, $this->student1Id, false, $this->parentRepo, $this->studentRepo));

        // Admin publishes results for the term
        $this->assertTrue(ResultPolicy::canPublish($adminContext));
        $publishRes = $this->publicationService->publishResults($this->termId, null, $this->adminUser->id, 'Official Term 1 Release');
        $this->assertTrue($publishRes->success);
        $this->assertTrue($this->publicationRepo->isPublished($this->termId));

        // Now PUBLISHED: Student 1 can view own results
        $this->assertTrue(ResultPolicy::canViewStudentResults($student1Context, $this->student1Id, true, $this->parentRepo, $this->studentRepo));
        // Student 1 cannot view Student 2's results
        $this->assertFalse(ResultPolicy::canViewStudentResults($student1Context, $this->student2Id, true, $this->parentRepo, $this->studentRepo));

        // Parent 1 (linked to Student 1) can view Student 1's results
        $this->assertTrue(ResultPolicy::canViewStudentResults($parent1Context, $this->student1Id, true, $this->parentRepo, $this->studentRepo));
        // Parent 1 cannot view Student 2's results
        $this->assertFalse(ResultPolicy::canViewStudentResults($parent1Context, $this->student2Id, true, $this->parentRepo, $this->studentRepo));

        // Unrelated Parent cannot view Student 1's results
        $this->assertFalse(ResultPolicy::canViewStudentResults($unrelatedParentContext, $this->student1Id, true, $this->parentRepo, $this->studentRepo));

        // 8. Test Report Card Data Generation
        $reportData = $this->reportCardService->getReportCardData($this->student1Id, $this->termId);
        $this->assertArrayHasKey('student', $reportData);
        $this->assertArrayHasKey('summary', $reportData);
        $this->assertArrayHasKey('subject_results', $reportData);
        $this->assertSame(1, $reportData['summary']->rankInClass);
        $this->assertCount(2, $reportData['subject_results']);

        // 9. Admin Unpublishes results
        $unpublishRes = $this->publicationService->unpublishResults($this->termId, null, 'Grade dispute investigation');
        $this->assertTrue($unpublishRes->success);
        $this->assertFalse($this->publicationRepo->isPublished($this->termId));

        // Student and parent are gated again
        $this->assertFalse(ResultPolicy::canViewStudentResults($student1Context, $this->student1Id, false, $this->parentRepo, $this->studentRepo));
        $this->assertFalse(ResultPolicy::canViewStudentResults($parent1Context, $this->student1Id, false, $this->parentRepo, $this->studentRepo));
    }
}
