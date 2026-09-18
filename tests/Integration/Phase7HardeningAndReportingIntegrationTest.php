<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Controllers\Teacher\ModuleController as TeacherModuleController;
use App\Core\AuthenticatorInterface;
use App\Core\Exceptions\AuthorizationException;
use App\Core\Exceptions\DomainRuleException;
use App\Core\Exceptions\ResourceNotFoundException;
use App\Core\Exceptions\ValidationException;
use App\Core\Request;
use App\Core\Response;
use App\Core\UserContext;
use App\Models\ActivityProgress;
use App\Models\Assignment;
use App\Models\ContentItem;
use App\Models\Module;
use App\Models\ModuleItem;
use App\Models\User;
use App\Repositories\AcademicRepository;
use App\Repositories\ActivityPrerequisiteRepository;
use App\Repositories\ActivityProgressRepository;
use App\Repositories\AssignmentRepository;
use App\Repositories\ContentRepository;
use App\Repositories\DocumentSectionRepository;
use App\Repositories\EnrollmentRepository;
use App\Repositories\FileRepository;
use App\Repositories\ModuleRepository;
use App\Repositories\QuizRepository;
use App\Repositories\StudentRepository;
use App\Repositories\TeacherRepository;
use App\Repositories\UserRepository;
use App\Services\AssignmentService;
use App\Services\ContentService;
use App\Services\ModuleService;
use App\Services\PrerequisiteService;
use App\Services\QuizService;
use PDO;
use PHPUnit\Framework\TestCase;

/**
 * Comprehensive Integration Tests for Phase 7: Reporting, UX Polish & Security/Integrity Hardening
 */
final class Phase7HardeningAndReportingIntegrationTest extends TestCase
{
    private PDO $pdo;
    private UserRepository $userRepo;
    private StudentRepository $studentRepo;
    private TeacherRepository $teacherRepo;
    private AcademicRepository $academicRepo;
    private EnrollmentRepository $enrollmentRepo;
    private ContentRepository $contentRepo;
    private DocumentSectionRepository $sectionRepo;
    private QuizRepository $quizRepo;
    private AssignmentRepository $assignmentRepo;
    private ActivityPrerequisiteRepository $prereqRepo;
    private ActivityProgressRepository $progressRepo;
    private ModuleRepository $moduleRepo;
    private FileRepository $fileRepo;

    private PrerequisiteService $prereqService;
    private ModuleService $moduleService;
    private ContentService $contentService;
    private QuizService $quizService;
    private AssignmentService $assignmentService;

    private User $teacherUser;
    private User $otherTeacherUser;
    private User $adminUser;
    private User $studentUserA;
    private User $studentUserB;
    private User $studentUserC;

    private int $teacherId;
    private int $otherTeacherId;
    private int $studentIdA;
    private int $studentIdB;
    private int $studentIdC;
    private int $classSubjectId;
    private int $otherClassSubjectId;

    private int $pdfDocId;
    private int $docxDocId;
    private int $sectionId;
    private int $quizId;
    private int $assignmentId;

    private int $moduleId1;
    private int $moduleId2;

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
                `status` VARCHAR(20) NOT NULL DEFAULT 'active',
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
                `status` VARCHAR(20) NOT NULL DEFAULT 'active',
                `created_at` DATETIME NOT NULL,
                `updated_at` DATETIME NOT NULL
            );

            CREATE TABLE `subjects` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `code` VARCHAR(20) NOT NULL UNIQUE,
                `name` VARCHAR(100) NOT NULL,
                `category` VARCHAR(50) NOT NULL DEFAULT 'core',
                `status` VARCHAR(20) NOT NULL DEFAULT 'active',
                `created_at` DATETIME NOT NULL,
                `updated_at` DATETIME NOT NULL
            );

            CREATE TABLE `teachers` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `user_id` INTEGER NOT NULL UNIQUE,
                `staff_id` VARCHAR(50) NOT NULL UNIQUE,
                `specialization` VARCHAR(100) NULL,
                `created_at` DATETIME NOT NULL,
                `updated_at` DATETIME NOT NULL
            );

            CREATE TABLE `students` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `user_id` INTEGER NOT NULL UNIQUE,
                `admission_number` VARCHAR(50) NOT NULL UNIQUE,
                `date_of_birth` DATE NULL,
                `gender` VARCHAR(20) NULL,
                `current_class_id` INTEGER NULL,
                `created_at` DATETIME NOT NULL,
                `updated_at` DATETIME NOT NULL
            );

            CREATE TABLE `class_subjects` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `session_id` INTEGER NOT NULL,
                `class_id` INTEGER NOT NULL,
                `subject_id` INTEGER NOT NULL,
                `teacher_id` INTEGER NOT NULL,
                `status` VARCHAR(20) NOT NULL DEFAULT 'active',
                `created_at` DATETIME NOT NULL,
                `updated_at` DATETIME NOT NULL
            );

            CREATE TABLE `student_subject_enrollments` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `student_id` INTEGER NOT NULL,
                `class_subject_id` INTEGER NOT NULL,
                `session_id` INTEGER NOT NULL,
                `is_elective` INTEGER NOT NULL DEFAULT 0,
                `status` VARCHAR(20) NOT NULL DEFAULT 'active',
                `created_at` DATETIME NOT NULL,
                `updated_at` DATETIME NOT NULL
            );

            CREATE TABLE `class_enrollments` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `student_id` INTEGER NOT NULL,
                `class_id` INTEGER NOT NULL,
                `session_id` INTEGER NOT NULL,
                `status` VARCHAR(20) NOT NULL DEFAULT 'active',
                `created_at` DATETIME NOT NULL,
                `updated_at` DATETIME NOT NULL
            );

            CREATE TABLE `files` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `uuid` VARCHAR(36) NOT NULL UNIQUE,
                `storage_key` VARCHAR(255) NOT NULL,
                `original_name` VARCHAR(255) NOT NULL,
                `mime_type` VARCHAR(100) NOT NULL,
                `size_bytes` INTEGER NOT NULL,
                `sha256` VARCHAR(64) NOT NULL,
                `uploaded_by` INTEGER NOT NULL,
                `owner_type` VARCHAR(50) NOT NULL,
                `owner_id` INTEGER NOT NULL,
                `created_at` DATETIME NOT NULL,
                `deleted_at` DATETIME NULL
            );

            CREATE TABLE `content_items` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `class_subject_id` INTEGER NOT NULL,
                `teacher_id` INTEGER NOT NULL,
                `topic` VARCHAR(255) NULL,
                `title` VARCHAR(255) NOT NULL,
                `description` TEXT NULL,
                `type` VARCHAR(50) NOT NULL DEFAULT 'document',
                `file_id` INTEGER NULL,
                `external_url` TEXT NULL,
                `published_at` DATETIME NULL,
                `created_at` DATETIME NOT NULL,
                `updated_at` DATETIME NOT NULL
            );

            CREATE TABLE `document_sections` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `content_item_id` INTEGER NOT NULL,
                `title` VARCHAR(255) NOT NULL,
                `start_page` INTEGER NOT NULL,
                `end_page` INTEGER NOT NULL,
                `sequence_order` INTEGER NOT NULL DEFAULT 1,
                `created_at` DATETIME NOT NULL,
                `updated_at` DATETIME NOT NULL
            );

            CREATE TABLE `quizzes` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `class_subject_id` INTEGER NOT NULL,
                `term_id` INTEGER NOT NULL,
                `teacher_id` INTEGER NOT NULL,
                `assessment_category_id` INTEGER NULL,
                `title` VARCHAR(255) NOT NULL,
                `instructions` TEXT NULL,
                `time_limit_minutes` INTEGER NOT NULL DEFAULT 0,
                `max_attempts` INTEGER NOT NULL DEFAULT 1,
                `is_published` INTEGER NOT NULL DEFAULT 0,
                `published_at` DATETIME NULL,
                `created_at` DATETIME NOT NULL,
                `updated_at` DATETIME NOT NULL
            );

            CREATE TABLE `quiz_questions` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `quiz_id` INTEGER NOT NULL,
                `question_type` VARCHAR(30) NOT NULL DEFAULT 'multiple_choice',
                `question_text` TEXT NOT NULL,
                `points` NUMERIC NOT NULL DEFAULT 1,
                `order_index` INTEGER NOT NULL DEFAULT 0,
                `options_json` TEXT NULL,
                `correct_answer` TEXT NULL,
                `explanation` TEXT NULL,
                `created_at` DATETIME NOT NULL,
                `updated_at` DATETIME NOT NULL
            );

            CREATE TABLE `quiz_attempts` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `quiz_id` INTEGER NOT NULL,
                `student_id` INTEGER NOT NULL,
                `attempt_number` INTEGER NOT NULL DEFAULT 1,
                `started_at` DATETIME NOT NULL,
                `submitted_at` DATETIME NULL,
                `score` NUMERIC NULL,
                `max_score` NUMERIC NOT NULL DEFAULT 0,
                `percentage` NUMERIC NULL,
                `status` VARCHAR(20) NOT NULL DEFAULT 'in_progress',
                `created_at` DATETIME NOT NULL,
                `updated_at` DATETIME NOT NULL
            );

            CREATE TABLE `assignments` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `class_subject_id` INTEGER NOT NULL,
                `teacher_id` INTEGER NOT NULL,
                `term_id` INTEGER NOT NULL,
                `title` VARCHAR(255) NOT NULL,
                `instructions` TEXT NULL,
                `topic` VARCHAR(255) NULL,
                `due_at` DATETIME NOT NULL,
                `max_score` NUMERIC NOT NULL DEFAULT 100,
                `file_id` INTEGER NULL,
                `status` VARCHAR(20) NOT NULL DEFAULT 'published',
                `created_at` DATETIME NOT NULL,
                `updated_at` DATETIME NOT NULL
            );

            CREATE TABLE `assignment_submissions` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `assignment_id` INTEGER NOT NULL,
                `student_id` INTEGER NOT NULL,
                `submitted_at` DATETIME NOT NULL,
                `status` VARCHAR(30) NOT NULL DEFAULT 'submitted',
                `submission_text` TEXT NULL,
                `file_id` INTEGER NULL,
                `grade` NUMERIC NULL,
                `feedback` TEXT NULL,
                `graded_by` INTEGER NULL,
                `graded_at` DATETIME NULL,
                `created_at` DATETIME NOT NULL,
                `updated_at` DATETIME NOT NULL
            );

            CREATE TABLE `activity_prerequisites` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `activity_type` VARCHAR(50) NOT NULL,
                `activity_id` INTEGER NOT NULL,
                `prerequisite_activity_type` VARCHAR(50) NOT NULL,
                `prerequisite_activity_id` INTEGER NOT NULL,
                `created_at` DATETIME NOT NULL,
                UNIQUE(`activity_type`, `activity_id`, `prerequisite_activity_type`, `prerequisite_activity_id`)
            );

            CREATE TABLE `learning_activity_progress` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `student_id` INTEGER NOT NULL,
                `activity_type` VARCHAR(50) NOT NULL,
                `activity_id` INTEGER NOT NULL,
                `progress_percent` NUMERIC NOT NULL DEFAULT 0.00,
                `is_completed` INTEGER NOT NULL DEFAULT 0,
                `completed_at` DATETIME NULL,
                `last_accessed_at` DATETIME NULL,
                `time_spent_seconds` INTEGER NOT NULL DEFAULT 0,
                `last_page` INTEGER NULL,
                `total_pages` INTEGER NULL,
                `pages_read_json` TEXT NULL,
                `raw_score` NUMERIC NULL,
                `max_score` NUMERIC NULL,
                `grade_percent` NUMERIC NULL,
                `attempt_count` INTEGER NOT NULL DEFAULT 0,
                `metadata_json` TEXT NULL,
                `created_at` DATETIME NOT NULL,
                `updated_at` DATETIME NOT NULL,
                UNIQUE(`student_id`, `activity_type`, `activity_id`)
            );

            CREATE TABLE `modules` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `class_subject_id` INTEGER NOT NULL,
                `title` VARCHAR(200) NOT NULL,
                `description` TEXT NULL,
                `sequence_order` INTEGER NOT NULL DEFAULT 1,
                `status` VARCHAR(20) NOT NULL DEFAULT 'published',
                `created_at` DATETIME NOT NULL,
                `updated_at` DATETIME NOT NULL
            );

            CREATE TABLE `module_items` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `module_id` INTEGER NOT NULL,
                `activity_type` VARCHAR(50) NOT NULL,
                `activity_id` INTEGER NOT NULL,
                `sequence_order` INTEGER NOT NULL DEFAULT 1,
                `is_required` INTEGER NOT NULL DEFAULT 1,
                `created_at` DATETIME NOT NULL,
                `updated_at` DATETIME NOT NULL,
                UNIQUE(`module_id`, `activity_type`, `activity_id`)
            );
        ");
    }

    private function initializeServices(): void
    {
        $this->userRepo = new UserRepository($this->pdo);
        $this->studentRepo = new StudentRepository($this->pdo);
        $this->teacherRepo = new TeacherRepository($this->pdo);
        $this->academicRepo = new AcademicRepository($this->pdo);
        $this->enrollmentRepo = new EnrollmentRepository($this->pdo);
        $this->contentRepo = new ContentRepository($this->pdo);
        $this->sectionRepo = new DocumentSectionRepository($this->pdo);
        $this->quizRepo = new QuizRepository($this->pdo);
        $this->assignmentRepo = new AssignmentRepository($this->pdo);
        $this->prereqRepo = new ActivityPrerequisiteRepository($this->pdo);
        $this->progressRepo = new ActivityProgressRepository($this->pdo);
        $this->moduleRepo = new ModuleRepository($this->pdo);
        $this->fileRepo = new FileRepository($this->pdo);

        $this->prereqService = new PrerequisiteService(
            prereqRepo: $this->prereqRepo,
            progressRepo: $this->progressRepo,
            contentRepo: $this->contentRepo,
            sectionRepo: $this->sectionRepo,
            quizRepo: $this->quizRepo,
            assignmentRepo: $this->assignmentRepo,
            academicRepo: $this->academicRepo
        );

        $this->moduleService = new ModuleService(
            pdo: $this->pdo,
            moduleRepo: $this->moduleRepo,
            contentRepo: $this->contentRepo,
            quizRepo: $this->quizRepo,
            assignmentRepo: $this->assignmentRepo,
            progressRepo: $this->progressRepo,
            prereqService: $this->prereqService,
            academicRepo: $this->academicRepo,
            teacherRepo: $this->teacherRepo,
            studentRepo: $this->studentRepo,
            enrollmentRepo: $this->enrollmentRepo,
            fileRepo: $this->fileRepo,
            prereqRepo: $this->prereqRepo
        );

        $this->contentService = new ContentService(
            contentRepository: $this->contentRepo,
            fileRepository: $this->fileRepo,
            academicRepository: $this->academicRepo,
            teacherRepository: $this->teacherRepo,
            studentRepository: $this->studentRepo,
            enrollmentRepository: $this->enrollmentRepo,
            pdo: $this->pdo,
            activityProgressRepository: $this->progressRepo,
            documentSectionRepository: $this->sectionRepo,
            prerequisiteRepository: $this->prereqRepo,
            moduleRepository: $this->moduleRepo
        );

        $this->quizService = new QuizService(
            quizRepository: $this->quizRepo,
            academicRepository: $this->academicRepo,
            teacherRepository: $this->teacherRepo,
            studentRepository: $this->studentRepo,
            enrollmentRepository: $this->enrollmentRepo,
            activityProgressRepository: $this->progressRepo,
            prerequisiteRepository: $this->prereqRepo,
            moduleRepository: $this->moduleRepo
        );

        $this->assignmentService = new AssignmentService(
            assignmentRepository: $this->assignmentRepo,
            academicRepository: $this->academicRepo,
            teacherRepository: $this->teacherRepo,
            studentRepository: $this->studentRepo,
            enrollmentRepository: $this->enrollmentRepo,
            fileRepository: $this->fileRepo,
            activityProgressRepository: $this->progressRepo,
            prerequisiteRepository: $this->prereqRepo,
            moduleRepository: $this->moduleRepo
        );
    }

    private function seedData(): void
    {
        $now = date('Y-m-d H:i:s');

        // Session & Term
        $this->pdo->exec("INSERT INTO `sessions` (`name`, `start_date`, `end_date`, `is_current`, `status`, `created_at`, `updated_at`) VALUES ('2026/2027', '2026-09-01', '2027-07-31', 1, 'active', '{$now}', '{$now}')");
        $sessionId = (int)$this->pdo->lastInsertId();

        $this->pdo->exec("INSERT INTO `terms` (`session_id`, `name`, `start_date`, `end_date`, `is_current`, `status`, `created_at`, `updated_at`) VALUES ({$sessionId}, 'First Term', '2026-09-01', '2026-12-15', 1, 'active', '{$now}', '{$now}')");
        $termId = (int)$this->pdo->lastInsertId();

        // Level & Class
        $this->pdo->exec("INSERT INTO `academic_levels` (`name`, `stage`, `rank_order`, `created_at`, `updated_at`) VALUES ('Senior Secondary 1', 'Senior', 10, '{$now}', '{$now}')");
        $levelId = (int)$this->pdo->lastInsertId();

        $this->pdo->exec("INSERT INTO `classes` (`academic_level_id`, `name`, `section_arm`, `status`, `created_at`, `updated_at`) VALUES ({$levelId}, 'SS1-Gold', 'Gold', 'active', '{$now}', '{$now}')");
        $classId = (int)$this->pdo->lastInsertId();

        // Subject
        $this->pdo->exec("INSERT INTO `subjects` (`code`, `name`, `category`, `status`, `created_at`, `updated_at`) VALUES ('PHY101', 'Physics', 'core', 'active', '{$now}', '{$now}')");
        $subjectId = (int)$this->pdo->lastInsertId();

        // Users & Roles
        $this->teacherUser = $this->createUser('Mr. Teacher Isaac', 'isaac@example.com', 'teacher');
        $this->otherTeacherUser = $this->createUser('Mrs. Other Teacher', 'other@example.com', 'teacher');
        $this->adminUser = $this->createUser('Super Admin', 'admin@example.com', 'super_admin');

        $this->studentUserA = $this->createUser('Alice Johnson', 'alice@example.com', 'student');
        $this->studentUserB = $this->createUser('Bob Smith', 'bob@example.com', 'student');
        $this->studentUserC = $this->createUser('Charlie Brown', 'charlie@example.com', 'student');

        // Teacher records
        $this->pdo->exec("INSERT INTO `teachers` (`user_id`, `staff_id`, `created_at`, `updated_at`) VALUES ({$this->teacherUser->id}, 'STF-001', '{$now}', '{$now}')");
        $this->teacherId = (int)$this->pdo->lastInsertId();

        $this->pdo->exec("INSERT INTO `teachers` (`user_id`, `staff_id`, `created_at`, `updated_at`) VALUES ({$this->otherTeacherUser->id}, 'STF-002', '{$now}', '{$now}')");
        $this->otherTeacherId = (int)$this->pdo->lastInsertId();

        // Student records
        $this->pdo->exec("INSERT INTO `students` (`user_id`, `admission_number`, `current_class_id`, `created_at`, `updated_at`) VALUES ({$this->studentUserA->id}, 'ADM-001', {$classId}, '{$now}', '{$now}')");
        $this->studentIdA = (int)$this->pdo->lastInsertId();

        $this->pdo->exec("INSERT INTO `students` (`user_id`, `admission_number`, `current_class_id`, `created_at`, `updated_at`) VALUES ({$this->studentUserB->id}, 'ADM-002', {$classId}, '{$now}', '{$now}')");
        $this->studentIdB = (int)$this->pdo->lastInsertId();

        $this->pdo->exec("INSERT INTO `students` (`user_id`, `admission_number`, `current_class_id`, `created_at`, `updated_at`) VALUES ({$this->studentUserC->id}, 'ADM-003', {$classId}, '{$now}', '{$now}')");
        $this->studentIdC = (int)$this->pdo->lastInsertId();

        // Class Subjects
        $this->pdo->exec("INSERT INTO `class_subjects` (`session_id`, `class_id`, `subject_id`, `teacher_id`, `status`, `created_at`, `updated_at`) VALUES ({$sessionId}, {$classId}, {$subjectId}, {$this->teacherId}, 'active', '{$now}', '{$now}')");
        $this->classSubjectId = (int)$this->pdo->lastInsertId();

        $this->pdo->exec("INSERT INTO `class_subjects` (`session_id`, `class_id`, `subject_id`, `teacher_id`, `status`, `created_at`, `updated_at`) VALUES ({$sessionId}, {$classId}, {$subjectId}, {$this->otherTeacherId}, 'active', '{$now}', '{$now}')");
        $this->otherClassSubjectId = (int)$this->pdo->lastInsertId();

        // Enroll Student A and Student B in classSubjectId
        $this->pdo->exec("INSERT INTO `student_subject_enrollments` (`student_id`, `class_subject_id`, `session_id`, `status`, `created_at`, `updated_at`) VALUES ({$this->studentIdA}, {$this->classSubjectId}, {$sessionId}, 'active', '{$now}', '{$now}')");
        $this->pdo->exec("INSERT INTO `student_subject_enrollments` (`student_id`, `class_subject_id`, `session_id`, `status`, `created_at`, `updated_at`) VALUES ({$this->studentIdB}, {$this->classSubjectId}, {$sessionId}, 'active', '{$now}', '{$now}')");

        // Class enrollments
        $this->pdo->exec("INSERT INTO `class_enrollments` (`student_id`, `class_id`, `session_id`, `status`, `created_at`, `updated_at`) VALUES ({$this->studentIdA}, {$classId}, {$sessionId}, 'active', '{$now}', '{$now}')");
        $this->pdo->exec("INSERT INTO `class_enrollments` (`student_id`, `class_id`, `session_id`, `status`, `created_at`, `updated_at`) VALUES ({$this->studentIdB}, {$classId}, {$sessionId}, 'active', '{$now}', '{$now}')");

        // Files
        $this->pdo->exec("INSERT INTO `files` (`uuid`, `storage_key`, `original_name`, `mime_type`, `size_bytes`, `sha256`, `uploaded_by`, `owner_type`, `owner_id`, `created_at`) VALUES ('pdf-uuid-1', 'path/to/kinematics.pdf', 'kinematics.pdf', 'application/pdf', 1048576, 'hash1', {$this->teacherUser->id}, 'content_item', 1, '{$now}')");
        $pdfFileId = (int)$this->pdo->lastInsertId();

        $this->pdo->exec("INSERT INTO `files` (`uuid`, `storage_key`, `original_name`, `mime_type`, `size_bytes`, `sha256`, `uploaded_by`, `owner_type`, `owner_id`, `created_at`) VALUES ('docx-uuid-2', 'path/to/dynamics.docx', 'dynamics.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 524288, 'hash2', {$this->teacherUser->id}, 'content_item', 2, '{$now}')");
        $docxFileId = (int)$this->pdo->lastInsertId();

        // Content items
        $this->pdo->exec("INSERT INTO `content_items` (`class_subject_id`, `teacher_id`, `topic`, `title`, `description`, `type`, `file_id`, `published_at`, `created_at`, `updated_at`) VALUES ({$this->classSubjectId}, {$this->teacherId}, 'Mechanics', 'Kinematics PDF Guide', 'Introductory physics reading', 'document', {$pdfFileId}, '{$now}', '{$now}', '{$now}')");
        $this->pdfDocId = (int)$this->pdo->lastInsertId();

        $this->pdo->exec("INSERT INTO `content_items` (`class_subject_id`, `teacher_id`, `topic`, `title`, `description`, `type`, `file_id`, `published_at`, `created_at`, `updated_at`) VALUES ({$this->classSubjectId}, {$this->teacherId}, 'Mechanics', 'Dynamics Notes DOCX', 'Newton laws reading', 'document', {$docxFileId}, '{$now}', '{$now}', '{$now}')");
        $this->docxDocId = (int)$this->pdo->lastInsertId();

        // Section on PDF
        $this->pdo->exec("INSERT INTO `document_sections` (`content_item_id`, `title`, `start_page`, `end_page`, `sequence_order`, `created_at`, `updated_at`) VALUES ({$this->pdfDocId}, 'Chapter 1: Vectors', 1, 5, 1, '{$now}', '{$now}')");
        $this->sectionId = (int)$this->pdo->lastInsertId();

        // Quiz
        $this->pdo->exec("INSERT INTO `quizzes` (`class_subject_id`, `term_id`, `teacher_id`, `title`, `instructions`, `time_limit_minutes`, `max_attempts`, `is_published`, `published_at`, `created_at`, `updated_at`) VALUES ({$this->classSubjectId}, {$termId}, {$this->teacherId}, 'Kinematics Mastery Quiz', 'Answer all questions', 30, 2, 1, '{$now}', '{$now}', '{$now}')");
        $this->quizId = (int)$this->pdo->lastInsertId();

        // Quiz Question
        $this->pdo->exec("INSERT INTO `quiz_questions` (`quiz_id`, `question_type`, `question_text`, `points`, `order_index`, `created_at`, `updated_at`) VALUES ({$this->quizId}, 'multiple_choice', 'What is acceleration?', 10, 1, '{$now}', '{$now}')");

        // Assignment
        $this->pdo->exec("INSERT INTO `assignments` (`class_subject_id`, `teacher_id`, `term_id`, `title`, `instructions`, `due_at`, `max_score`, `status`, `created_at`, `updated_at`) VALUES ({$this->classSubjectId}, {$this->teacherId}, {$termId}, 'Problem Set 1', 'Solve exercises 1-5', '2026-10-15 23:59:59', 50, 'published', '{$now}', '{$now}')");
        $this->assignmentId = (int)$this->pdo->lastInsertId();

        // Course Modules
        $this->pdo->exec("INSERT INTO `modules` (`class_subject_id`, `title`, `description`, `sequence_order`, `status`, `created_at`, `updated_at`) VALUES ({$this->classSubjectId}, 'Unit 1: Introduction to Mechanics', 'Core foundational unit', 1, 'published', '{$now}', '{$now}')");
        $this->moduleId1 = (int)$this->pdo->lastInsertId();

        $this->pdo->exec("INSERT INTO `modules` (`class_subject_id`, `title`, `description`, `sequence_order`, `status`, `created_at`, `updated_at`) VALUES ({$this->classSubjectId}, 'Unit 2: Forces and Dynamics', 'Advanced mechanics', 2, 'published', '{$now}', '{$now}')");
        $this->moduleId2 = (int)$this->pdo->lastInsertId();

        // Module Items:
        // Module 1 has: PDF (Required), Quiz (Required)
        $this->pdo->exec("INSERT INTO `module_items` (`module_id`, `activity_type`, `activity_id`, `sequence_order`, `is_required`, `created_at`, `updated_at`) VALUES ({$this->moduleId1}, 'document', {$this->pdfDocId}, 1, 1, '{$now}', '{$now}')");
        $this->pdo->exec("INSERT INTO `module_items` (`module_id`, `activity_type`, `activity_id`, `sequence_order`, `is_required`, `created_at`, `updated_at`) VALUES ({$this->moduleId1}, 'quiz', {$this->quizId}, 2, 1, '{$now}', '{$now}')");

        // Module 2 has: DOCX (Required), Assignment (Optional)
        $this->pdo->exec("INSERT INTO `module_items` (`module_id`, `activity_type`, `activity_id`, `sequence_order`, `is_required`, `created_at`, `updated_at`) VALUES ({$this->moduleId2}, 'document', {$this->docxDocId}, 1, 1, '{$now}', '{$now}')");
        $this->pdo->exec("INSERT INTO `module_items` (`module_id`, `activity_type`, `activity_id`, `sequence_order`, `is_required`, `created_at`, `updated_at`) VALUES ({$this->moduleId2}, 'assignment', {$this->assignmentId}, 2, 0, '{$now}', '{$now}')");
    }

    private function createUser(string $name, string $email, string $role): User
    {
        $now = date('Y-m-d H:i:s');
        $uuid = bin2hex(random_bytes(16));
        $this->pdo->exec("INSERT INTO `users` (`uuid`, `name`, `email`, `password_hash`, `status`, `created_at`, `updated_at`) VALUES ('{$uuid}', '{$name}', '{$email}', 'hash', 'active', '{$now}', '{$now}')");
        $userId = (int)$this->pdo->lastInsertId();

        $this->pdo->exec("INSERT INTO `user_roles` (`user_id`, `role`, `is_active`, `created_at`) VALUES ({$userId}, '{$role}', 1, '{$now}')");

        return User::fromArray([
            'id' => $userId,
            'uuid' => $uuid,
            'name' => $name,
            'email' => $email,
            'status' => 'active',
            'roles' => [$role],
        ]);
    }

    private function createActor(User $user, string $role): UserContext
    {
        return UserContext::fromUser($user);
    }

    // =========================================================================
    // PART 1: REPORTING TESTS
    // =========================================================================

    public function testCohortProgressionReportCalculatesAuthoritativeMetrics(): void
    {
        $teacherActor = $this->createActor($this->teacherUser, 'teacher');

        // Total required activities across published modules = 3 (PDF in Mod1, Quiz in Mod1, DOCX in Mod2). Assignment is optional.
        // Student A: completes PDF and Quiz (2 of 3 completed = 66.7% -> In Progress)
        $now = date('Y-m-d H:i:s');
        $this->pdo->exec("INSERT INTO `learning_activity_progress` (`student_id`, `activity_type`, `activity_id`, `progress_percent`, `is_completed`, `completed_at`, `last_accessed_at`, `created_at`, `updated_at`) VALUES ({$this->studentIdA}, 'document', {$this->pdfDocId}, 100.0, 1, '{$now}', '{$now}', '{$now}', '{$now}')");
        $this->pdo->exec("INSERT INTO `learning_activity_progress` (`student_id`, `activity_type`, `activity_id`, `progress_percent`, `is_completed`, `completed_at`, `last_accessed_at`, `created_at`, `updated_at`) VALUES ({$this->studentIdA}, 'quiz', {$this->quizId}, 100.0, 1, '{$now}', '{$now}', '{$now}', '{$now}')");

        // Student B: not started (0 completed = 0.0% -> Not Started)
        $report = $this->moduleService->getCohortProgressionReport($this->classSubjectId, $teacherActor);

        $this->assertCount(2, $report['students']);
        $this->assertSame(2, $report['cohort_metrics']['enrolled_student_count']);
        $this->assertSame(1, $report['cohort_metrics']['not_started_count']);
        $this->assertSame(1, $report['cohort_metrics']['in_progress_count']);
        $this->assertSame(0, $report['cohort_metrics']['completed_count']);
        // Avg = (66.7 + 0.0) / 2 = 33.4%
        $this->assertEquals(33.4, $report['cohort_metrics']['average_progress_percentage']);
    }

    public function testCohortProgressionReportEmptyCohort(): void
    {
        $teacherActor = $this->createActor($this->otherTeacherUser, 'teacher');
        $now = date('Y-m-d H:i:s');

        // Create a class offering with 0 student enrollments
        $this->pdo->exec("INSERT INTO `classes` (`academic_level_id`, `name`, `section_arm`, `status`, `created_at`, `updated_at`) VALUES (1, 'Empty Class', 'None', 'active', '{$now}', '{$now}')");
        $emptyClassId = (int)$this->pdo->lastInsertId();

        $this->pdo->exec("INSERT INTO `class_subjects` (`session_id`, `class_id`, `subject_id`, `teacher_id`, `status`, `created_at`, `updated_at`) VALUES (1, {$emptyClassId}, 1, {$this->otherTeacherId}, 'active', '{$now}', '{$now}')");
        $emptyClassSubjectId = (int)$this->pdo->lastInsertId();

        $report = $this->moduleService->getCohortProgressionReport($emptyClassSubjectId, $teacherActor);

        $this->assertSame(0, $report['cohort_metrics']['enrolled_student_count']);
        $this->assertSame(0, $report['cohort_metrics']['not_started_count']);
        $this->assertSame(0, $report['cohort_metrics']['in_progress_count']);
        $this->assertSame(0, $report['cohort_metrics']['completed_count']);
        $this->assertSame(0.0, $report['cohort_metrics']['average_progress_percentage']);
        $this->assertEmpty($report['students']);
    }

    public function testCohortProgressionReportNoModulesOrActivities(): void
    {
        $teacherActor = $this->createActor($this->otherTeacherUser, 'teacher');

        $report = $this->moduleService->getCohortProgressionReport($this->otherClassSubjectId, $teacherActor);

        $this->assertFalse($report['has_modules']);
        $this->assertSame(0, $report['total_required_items']);
    }

    public function testCohortProgressionReportIgnoresOptionalActivitiesInRequiredCount(): void
    {
        $teacherActor = $this->createActor($this->teacherUser, 'teacher');

        $report = $this->moduleService->getCohortProgressionReport($this->classSubjectId, $teacherActor);

        // 4 items attached across modules, but assignment is is_required = 0.
        // Total required items must be strictly 3.
        $this->assertSame(3, $report['total_required_items']);
    }

    public function testCohortProgressionReportUnpublishedModulesExcluded(): void
    {
        $teacherActor = $this->createActor($this->teacherUser, 'teacher');

        // Unpublish Module 2
        $this->pdo->exec("UPDATE `modules` SET `status` = 'draft' WHERE `id` = {$this->moduleId2}");

        $report = $this->moduleService->getCohortProgressionReport($this->classSubjectId, $teacherActor);

        // Now only Module 1 is published, which has 2 required items (PDF, Quiz)
        $this->assertSame(2, $report['total_required_items']);
    }

    public function testCohortProgressionReportDistinguishesStatusesCorrectly(): void
    {
        $teacherActor = $this->createActor($this->teacherUser, 'teacher');
        $now = date('Y-m-d H:i:s');

        // Enroll Student C
        $this->pdo->exec("INSERT INTO `student_subject_enrollments` (`student_id`, `class_subject_id`, `session_id`, `status`, `created_at`, `updated_at`) VALUES ({$this->studentIdC}, {$this->classSubjectId}, 1, 'active', '{$now}', '{$now}')");

        // Student A completes all 3 required activities -> Completed
        $this->pdo->exec("INSERT INTO `learning_activity_progress` (`student_id`, `activity_type`, `activity_id`, `progress_percent`, `is_completed`, `completed_at`, `created_at`, `updated_at`) VALUES ({$this->studentIdA}, 'document', {$this->pdfDocId}, 100.0, 1, '{$now}', '{$now}', '{$now}')");
        $this->pdo->exec("INSERT INTO `learning_activity_progress` (`student_id`, `activity_type`, `activity_id`, `progress_percent`, `is_completed`, `completed_at`, `created_at`, `updated_at`) VALUES ({$this->studentIdA}, 'quiz', {$this->quizId}, 100.0, 1, '{$now}', '{$now}', '{$now}')");
        $this->pdo->exec("INSERT INTO `learning_activity_progress` (`student_id`, `activity_type`, `activity_id`, `progress_percent`, `is_completed`, `completed_at`, `created_at`, `updated_at`) VALUES ({$this->studentIdA}, 'document', {$this->docxDocId}, 100.0, 1, '{$now}', '{$now}', '{$now}')");

        // Student B starts reading PDF (40% read, not completed) -> In Progress
        $this->pdo->exec("INSERT INTO `learning_activity_progress` (`student_id`, `activity_type`, `activity_id`, `progress_percent`, `is_completed`, `last_page`, `created_at`, `updated_at`) VALUES ({$this->studentIdB}, 'document', {$this->pdfDocId}, 40.0, 0, 4, '{$now}', '{$now}')");

        // Student C: no activity progress -> Not Started

        $report = $this->moduleService->getCohortProgressionReport($this->classSubjectId, $teacherActor);

        $this->assertSame(3, $report['cohort_metrics']['enrolled_student_count']);
        $this->assertSame(1, $report['cohort_metrics']['completed_count']);
        $this->assertSame(1, $report['cohort_metrics']['in_progress_count']);
        $this->assertSame(1, $report['cohort_metrics']['not_started_count']);

        $studentMap = [];
        foreach ($report['students'] as $s) {
            $studentMap[$s['student_id']] = $s;
        }

        $this->assertSame('Completed', $studentMap[$this->studentIdA]['status']);
        $this->assertEquals(100.0, $studentMap[$this->studentIdA]['progress_percent']);

        $this->assertSame('In Progress', $studentMap[$this->studentIdB]['status']);
        $this->assertEquals(0.0, $studentMap[$this->studentIdB]['progress_percent']); // 0 of 3 completed, but in progress

        $this->assertSame('Not Started', $studentMap[$this->studentIdC]['status']);
        $this->assertEquals(0.0, $studentMap[$this->studentIdC]['progress_percent']);
    }

    public function testCohortProgressionReportLastActiveTimestampTracking(): void
    {
        $teacherActor = $this->createActor($this->teacherUser, 'teacher');
        $early = '2026-09-10 10:00:00';
        $late = '2026-09-15 16:30:00';

        $this->pdo->exec("INSERT INTO `learning_activity_progress` (`student_id`, `activity_type`, `activity_id`, `progress_percent`, `is_completed`, `last_accessed_at`, `created_at`, `updated_at`) VALUES ({$this->studentIdA}, 'document', {$this->pdfDocId}, 50.0, 0, '{$early}', '{$early}', '{$early}')");
        $this->pdo->exec("INSERT INTO `learning_activity_progress` (`student_id`, `activity_type`, `activity_id`, `progress_percent`, `is_completed`, `last_accessed_at`, `created_at`, `updated_at`) VALUES ({$this->studentIdA}, 'quiz', {$this->quizId}, 20.0, 0, '{$late}', '{$late}', '{$late}')");

        $report = $this->moduleService->getCohortProgressionReport($this->classSubjectId, $teacherActor);

        $studentMap = [];
        foreach ($report['students'] as $s) {
            $studentMap[$s['student_id']] = $s;
        }

        $this->assertSame($late, $studentMap[$this->studentIdA]['last_active_at']);
        $this->assertNull($studentMap[$this->studentIdB]['last_active_at']);
    }

    // =========================================================================
    // PART 2: SECURITY & AUTHORIZATION TESTS
    // =========================================================================

    public function testTeacherAuthorizationEnforcedForCohortReport(): void
    {
        $studentActor = $this->createActor($this->studentUserA, 'student');

        $this->expectException(AuthorizationException::class);
        $this->moduleService->getCohortProgressionReport($this->classSubjectId, $studentActor);
    }

    public function testTeacherCannotAccessCohortReportForUnassignedSubject(): void
    {
        // otherTeacherUser does NOT teach classSubjectId
        $unauthTeacherActor = $this->createActor($this->otherTeacherUser, 'teacher');

        $this->expectException(AuthorizationException::class);
        $this->moduleService->getCohortProgressionReport($this->classSubjectId, $unauthTeacherActor);
    }

    public function testSuperAdminCanAccessCohortReportForAnySubject(): void
    {
        $adminActor = $this->createActor($this->adminUser, 'super_admin');

        $report = $this->moduleService->getCohortProgressionReport($this->classSubjectId, $adminActor);

        $this->assertNotEmpty($report);
        $this->assertSame(2, $report['cohort_metrics']['enrolled_student_count']);
    }

    // =========================================================================
    // PART 3: INDIVIDUAL STUDENT DETAIL TESTS
    // =========================================================================

    public function testIndividualStudentProgressionDetailReport(): void
    {
        $teacherActor = $this->createActor($this->teacherUser, 'teacher');
        $now = date('Y-m-d H:i:s');

        $this->pdo->exec("INSERT INTO `learning_activity_progress` (`student_id`, `activity_type`, `activity_id`, `progress_percent`, `is_completed`, `completed_at`, `created_at`, `updated_at`) VALUES ({$this->studentIdA}, 'document', {$this->pdfDocId}, 100.0, 1, '{$now}', '{$now}', '{$now}')");

        $detail = $this->moduleService->getStudentProgressionDetail($this->classSubjectId, $this->studentIdA, $teacherActor);

        $this->assertSame($this->studentIdA, $detail['student']->id);
        $this->assertSame($this->classSubjectId, $detail['class_subject']->id);
        $this->assertNotEmpty($detail['learning_path']['modules']);
        $this->assertEquals(33.3, $detail['learning_path']['course_progress_percent']);
    }

    public function testStudentProgressionDetailRejectsUnenrolledStudent(): void
    {
        $teacherActor = $this->createActor($this->teacherUser, 'teacher');

        // Student C is NOT enrolled in classSubjectId
        $this->expectException(AuthorizationException::class);
        $this->moduleService->getStudentProgressionDetail($this->classSubjectId, $this->studentIdC, $teacherActor);
    }

    public function testStudentProgressionDetailRejectsInvalidStudentId(): void
    {
        $teacherActor = $this->createActor($this->teacherUser, 'teacher');

        $this->expectException(ResourceNotFoundException::class);
        $this->moduleService->getStudentProgressionDetail($this->classSubjectId, 999999, $teacherActor);
    }

    public function testStudentProgressionDetailRejectsInvalidClassSubjectId(): void
    {
        $teacherActor = $this->createActor($this->teacherUser, 'teacher');

        $this->expectException(ResourceNotFoundException::class);
        $this->moduleService->getStudentProgressionDetail(999999, $this->studentIdA, $teacherActor);
    }

    // =========================================================================
    // PART 4: STUDENT RESUME TARGET TESTS
    // =========================================================================

    public function testStudentResumeTargetReturnsInProgressPdfWithPage(): void
    {
        $now = date('Y-m-d H:i:s');
        // Student A was reading PDF, reached page 4 of 12 (33.3%)
        $this->pdo->exec("INSERT INTO `learning_activity_progress` (`student_id`, `activity_type`, `activity_id`, `progress_percent`, `is_completed`, `last_page`, `total_pages`, `created_at`, `updated_at`) VALUES ({$this->studentIdA}, 'document', {$this->pdfDocId}, 33.3, 0, 4, 12, '{$now}', '{$now}')");

        $target = $this->moduleService->getStudentResumeTarget($this->classSubjectId, $this->studentIdA);

        $this->assertNotNull($target);
        $this->assertSame('resume', $target['type']);
        $this->assertSame('document', $target['activity_type']);
        $this->assertSame($this->pdfDocId, $target['activity_id']);
        $this->assertSame(4, $target['last_page']);
        $this->assertSame(12, $target['total_pages']);
        $this->assertSame('Resume Reading (Page 4)', $target['label']);
    }

    public function testStudentResumeTargetReturnsFirstUnlockedWhenNoneInProgress(): void
    {
        // Student B has 0 progress on any item.
        // First unlocked incomplete activity in sequence is the PDF in Module 1.
        $target = $this->moduleService->getStudentResumeTarget($this->classSubjectId, $this->studentIdB);

        $this->assertNotNull($target);
        $this->assertSame('next', $target['type']);
        $this->assertSame('document', $target['activity_type']);
        $this->assertSame($this->pdfDocId, $target['activity_id']);
        $this->assertSame('Start Reading', $target['label']);
    }

    public function testStudentResumeTargetReturnsNullWhenCourseCompleted(): void
    {
        $now = date('Y-m-d H:i:s');
        // Complete all required items for Student A: PDF, Quiz, DOCX
        $this->pdo->exec("INSERT INTO `learning_activity_progress` (`student_id`, `activity_type`, `activity_id`, `progress_percent`, `is_completed`, `completed_at`, `created_at`, `updated_at`) VALUES ({$this->studentIdA}, 'document', {$this->pdfDocId}, 100.0, 1, '{$now}', '{$now}', '{$now}')");
        $this->pdo->exec("INSERT INTO `learning_activity_progress` (`student_id`, `activity_type`, `activity_id`, `progress_percent`, `is_completed`, `completed_at`, `created_at`, `updated_at`) VALUES ({$this->studentIdA}, 'quiz', {$this->quizId}, 100.0, 1, '{$now}', '{$now}', '{$now}')");
        $this->pdo->exec("INSERT INTO `learning_activity_progress` (`student_id`, `activity_type`, `activity_id`, `progress_percent`, `is_completed`, `completed_at`, `created_at`, `updated_at`) VALUES ({$this->studentIdA}, 'document', {$this->docxDocId}, 100.0, 1, '{$now}', '{$now}', '{$now}')");

        $target = $this->moduleService->getStudentResumeTarget($this->classSubjectId, $this->studentIdA);

        $this->assertNull($target);
    }

    public function testStudentResumeTargetReturnsNullWhenNoModules(): void
    {
        // otherClassSubjectId has no modules
        $target = $this->moduleService->getStudentResumeTarget($this->otherClassSubjectId, $this->studentIdA);

        $this->assertNull($target);
    }

    // =========================================================================
    // PART 5: POLYMORPHIC DELETION INTEGRITY TESTS
    // =========================================================================

    public function testPolymorphicCleanupOnContentItemDeletion(): void
    {
        $teacherActor = $this->createActor($this->teacherUser, 'teacher');
        $now = date('Y-m-d H:i:s');

        // Set up prerequisites referencing the document
        $this->pdo->exec("INSERT INTO `activity_prerequisites` (`activity_type`, `activity_id`, `prerequisite_activity_type`, `prerequisite_activity_id`, `created_at`) VALUES ('quiz', {$this->quizId}, 'document', {$this->pdfDocId}, '{$now}')");

        // Set up progress for the document and section
        $this->pdo->exec("INSERT INTO `learning_activity_progress` (`student_id`, `activity_type`, `activity_id`, `progress_percent`, `is_completed`, `created_at`, `updated_at`) VALUES ({$this->studentIdA}, 'document', {$this->pdfDocId}, 50.0, 0, '{$now}', '{$now}')");
        $this->pdo->exec("INSERT INTO `learning_activity_progress` (`student_id`, `activity_type`, `activity_id`, `progress_percent`, `is_completed`, `created_at`, `updated_at`) VALUES ({$this->studentIdA}, 'document_section', {$this->sectionId}, 100.0, 1, '{$now}', '{$now}')");

        // Verify rows exist before deletion
        $this->assertSame(1, (int)$this->pdo->query("SELECT COUNT(*) FROM `module_items` WHERE `activity_type` = 'document' AND `activity_id` = {$this->pdfDocId}")->fetchColumn());
        $this->assertSame(1, (int)$this->pdo->query("SELECT COUNT(*) FROM `activity_prerequisites` WHERE `prerequisite_activity_type` = 'document' AND `prerequisite_activity_id` = {$this->pdfDocId}")->fetchColumn());
        $this->assertSame(1, (int)$this->pdo->query("SELECT COUNT(*) FROM `learning_activity_progress` WHERE `activity_type` = 'document' AND `activity_id` = {$this->pdfDocId}")->fetchColumn());
        $this->assertSame(1, (int)$this->pdo->query("SELECT COUNT(*) FROM `document_sections` WHERE `content_item_id` = {$this->pdfDocId}")->fetchColumn());

        // Perform deletion
        $this->contentService->deleteContent($this->pdfDocId, $teacherActor);

        // Verify all polymorphic and dependent references were completely cleaned
        $this->assertSame(0, (int)$this->pdo->query("SELECT COUNT(*) FROM `content_items` WHERE `id` = {$this->pdfDocId}")->fetchColumn());
        $this->assertSame(0, (int)$this->pdo->query("SELECT COUNT(*) FROM `document_sections` WHERE `id` = {$this->sectionId}")->fetchColumn());
        $this->assertSame(0, (int)$this->pdo->query("SELECT COUNT(*) FROM `module_items` WHERE `activity_type` = 'document' AND `activity_id` = {$this->pdfDocId}")->fetchColumn());
        $this->assertSame(0, (int)$this->pdo->query("SELECT COUNT(*) FROM `activity_prerequisites` WHERE `prerequisite_activity_type` = 'document' AND `prerequisite_activity_id` = {$this->pdfDocId}")->fetchColumn());
        $this->assertSame(0, (int)$this->pdo->query("SELECT COUNT(*) FROM `learning_activity_progress` WHERE `activity_type` = 'document' AND `activity_id` = {$this->pdfDocId}")->fetchColumn());
        $this->assertSame(0, (int)$this->pdo->query("SELECT COUNT(*) FROM `learning_activity_progress` WHERE `activity_type` = 'document_section' AND `activity_id` = {$this->sectionId}")->fetchColumn());
    }

    public function testPolymorphicCleanupOnDocumentSectionDeletionPreservesParentDocProgress(): void
    {
        $teacherActor = $this->createActor($this->teacherUser, 'teacher');
        $now = date('Y-m-d H:i:s');

        // Set up progress for both parent document and section
        $this->pdo->exec("INSERT INTO `learning_activity_progress` (`student_id`, `activity_type`, `activity_id`, `progress_percent`, `is_completed`, `created_at`, `updated_at`) VALUES ({$this->studentIdA}, 'document', {$this->pdfDocId}, 75.0, 0, '{$now}', '{$now}')");
        $this->pdo->exec("INSERT INTO `learning_activity_progress` (`student_id`, `activity_type`, `activity_id`, `progress_percent`, `is_completed`, `created_at`, `updated_at`) VALUES ({$this->studentIdA}, 'document_section', {$this->sectionId}, 100.0, 1, '{$now}', '{$now}')");

        // Delete section
        $this->contentService->deleteSection($this->sectionId, $teacherActor);

        // Verify section row and section progress are removed
        $this->assertSame(0, (int)$this->pdo->query("SELECT COUNT(*) FROM `document_sections` WHERE `id` = {$this->sectionId}")->fetchColumn());
        $this->assertSame(0, (int)$this->pdo->query("SELECT COUNT(*) FROM `learning_activity_progress` WHERE `activity_type` = 'document_section' AND `activity_id` = {$this->sectionId}")->fetchColumn());

        // Crucial rule: Parent document progress is PRESERVED intact!
        $parentProgress = $this->pdo->query("SELECT `progress_percent` FROM `learning_activity_progress` WHERE `activity_type` = 'document' AND `activity_id` = {$this->pdfDocId}")->fetchColumn();
        $this->assertEquals(75.0, (float)$parentProgress);
    }

    public function testPolymorphicCleanupOnQuizDeletion(): void
    {
        $teacherActor = $this->createActor($this->teacherUser, 'teacher');
        $now = date('Y-m-d H:i:s');

        // Progress row for quiz
        $this->pdo->exec("INSERT INTO `learning_activity_progress` (`student_id`, `activity_type`, `activity_id`, `progress_percent`, `is_completed`, `created_at`, `updated_at`) VALUES ({$this->studentIdA}, 'quiz', {$this->quizId}, 100.0, 1, '{$now}', '{$now}')");

        // Verify module item and progress exist
        $this->assertSame(1, (int)$this->pdo->query("SELECT COUNT(*) FROM `module_items` WHERE `activity_type` = 'quiz' AND `activity_id` = {$this->quizId}")->fetchColumn());
        $this->assertSame(1, (int)$this->pdo->query("SELECT COUNT(*) FROM `learning_activity_progress` WHERE `activity_type` = 'quiz' AND `activity_id` = {$this->quizId}")->fetchColumn());

        // Delete quiz
        $this->quizService->deleteQuiz($this->quizId, $teacherActor);

        // Verify all references were cleaned
        $this->assertSame(0, (int)$this->pdo->query("SELECT COUNT(*) FROM `quizzes` WHERE `id` = {$this->quizId}")->fetchColumn());
        $this->assertSame(0, (int)$this->pdo->query("SELECT COUNT(*) FROM `module_items` WHERE `activity_type` = 'quiz' AND `activity_id` = {$this->quizId}")->fetchColumn());
        $this->assertSame(0, (int)$this->pdo->query("SELECT COUNT(*) FROM `learning_activity_progress` WHERE `activity_type` = 'quiz' AND `activity_id` = {$this->quizId}")->fetchColumn());
    }

    public function testPolymorphicCleanupOnAssignmentDeletion(): void
    {
        $teacherActor = $this->createActor($this->teacherUser, 'teacher');
        $now = date('Y-m-d H:i:s');

        // Progress row for assignment
        $this->pdo->exec("INSERT INTO `learning_activity_progress` (`student_id`, `activity_type`, `activity_id`, `progress_percent`, `is_completed`, `created_at`, `updated_at`) VALUES ({$this->studentIdA}, 'assignment', {$this->assignmentId}, 100.0, 1, '{$now}', '{$now}')");

        // Verify module item and progress exist
        $this->assertSame(1, (int)$this->pdo->query("SELECT COUNT(*) FROM `module_items` WHERE `activity_type` = 'assignment' AND `activity_id` = {$this->assignmentId}")->fetchColumn());
        $this->assertSame(1, (int)$this->pdo->query("SELECT COUNT(*) FROM `learning_activity_progress` WHERE `activity_type` = 'assignment' AND `activity_id` = {$this->assignmentId}")->fetchColumn());

        // Delete assignment
        $this->assignmentService->deleteAssignment($this->assignmentId, $teacherActor);

        // Verify all references were cleaned
        $this->assertSame(0, (int)$this->pdo->query("SELECT COUNT(*) FROM `assignments` WHERE `id` = {$this->assignmentId}")->fetchColumn());
        $this->assertSame(0, (int)$this->pdo->query("SELECT COUNT(*) FROM `module_items` WHERE `activity_type` = 'assignment' AND `activity_id` = {$this->assignmentId}")->fetchColumn());
        $this->assertSame(0, (int)$this->pdo->query("SELECT COUNT(*) FROM `learning_activity_progress` WHERE `activity_type` = 'assignment' AND `activity_id` = {$this->assignmentId}")->fetchColumn());
    }

    // =========================================================================
    // PART 6: PERFORMANCE / QUERY BOUNDING TESTS
    // =========================================================================

    public function testCohortReportBoundedQueryPerformance(): void
    {
        $teacherActor = $this->createActor($this->teacherUser, 'teacher');
        $now = date('Y-m-d H:i:s');

        // Enroll 10 additional students to test scalability
        for ($i = 4; $i <= 13; $i++) {
            $user = $this->createUser("Student {$i}", "student{$i}@example.com", 'student');
            $this->pdo->exec("INSERT INTO `students` (`user_id`, `admission_number`, `created_at`, `updated_at`) VALUES ({$user->id}, 'ADM-0{$i}', '{$now}', '{$now}')");
            $sId = (int)$this->pdo->lastInsertId();
            $this->pdo->exec("INSERT INTO `student_subject_enrollments` (`student_id`, `class_subject_id`, `session_id`, `status`, `created_at`, `updated_at`) VALUES ({$sId}, {$this->classSubjectId}, 1, 'active', '{$now}', '{$now}')");
        }

        // Generate report and verify all 12 students are returned accurately
        $report = $this->moduleService->getCohortProgressionReport($this->classSubjectId, $teacherActor);

        $this->assertSame(12, $report['cohort_metrics']['enrolled_student_count']);
        $this->assertCount(12, $report['students']);
    }

    // =========================================================================
    // PART 7: CONTROLLER & HTTP LAYER TESTS
    // =========================================================================

    public function testModuleControllerProgressActionReturns200ForTeacher(): void
    {
        $controller = new TeacherModuleController(
            academicRepo: $this->academicRepo,
            teacherRepo: $this->teacherRepo,
            moduleService: $this->moduleService
        );

        $request = new Request([], [], ['REQUEST_METHOD' => 'GET']);
        $request->setAttribute('_user_context', UserContext::fromUser($this->teacherUser));
        $response = $controller->progress($request, $this->classSubjectId);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString('Learning Progression Report', $response->getBody());
        $this->assertStringContainsString('Alice Johnson', $response->getBody());
    }

    public function testModuleControllerProgressActionReturns403ForUnauthorizedTeacher(): void
    {
        $controller = new TeacherModuleController(
            academicRepo: $this->academicRepo,
            teacherRepo: $this->teacherRepo,
            moduleService: $this->moduleService
        );

        // otherTeacherUser does NOT teach classSubjectId
        $request = new Request([], [], ['REQUEST_METHOD' => 'GET']);
        $request->setAttribute('_user_context', UserContext::fromUser($this->otherTeacherUser));
        $response = $controller->progress($request, $this->classSubjectId);

        $this->assertSame(403, $response->getStatusCode());
    }

    public function testModuleControllerStudentProgressActionReturns200ForEnrolledStudent(): void
    {
        $controller = new TeacherModuleController(
            academicRepo: $this->academicRepo,
            teacherRepo: $this->teacherRepo,
            moduleService: $this->moduleService
        );

        $request = new Request([], [], ['REQUEST_METHOD' => 'GET']);
        $request->setAttribute('_user_context', UserContext::fromUser($this->teacherUser));
        $response = $controller->studentProgress($request, $this->classSubjectId, $this->studentIdA);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString('Alice Johnson', $response->getBody());
        $this->assertStringContainsString('Module Breakdown', $response->getBody());
    }

    public function testModuleControllerStudentProgressActionReturns404ForNonExistentStudent(): void
    {
        $controller = new TeacherModuleController(
            academicRepo: $this->academicRepo,
            teacherRepo: $this->teacherRepo,
            moduleService: $this->moduleService
        );

        $request = new Request([], [], ['REQUEST_METHOD' => 'GET']);
        $request->setAttribute('_user_context', UserContext::fromUser($this->teacherUser));
        $response = $controller->studentProgress($request, $this->classSubjectId, 999999);

        $this->assertSame(404, $response->getStatusCode());
    }

    public function testModuleControllerProgressActionRejectsNonPositiveIntegerId(): void
    {
        $controller = new TeacherModuleController(
            academicRepo: $this->academicRepo,
            teacherRepo: $this->teacherRepo,
            moduleService: $this->moduleService
        );

        $request = new Request([], [], ['REQUEST_METHOD' => 'GET']);
        $request->setAttribute('_user_context', UserContext::fromUser($this->teacherUser));
        $response = $controller->progress($request, 0);

        $this->assertSame(404, $response->getStatusCode());
    }
}
