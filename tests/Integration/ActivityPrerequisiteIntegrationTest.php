<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Controllers\Student\AssignmentController;
use App\Controllers\Student\ContentController as StudentContentController;
use App\Controllers\Student\QuizAttemptController;
use App\Controllers\Student\SubmissionController;
use App\Controllers\Teacher\ContentController as TeacherContentController;
use App\Controllers\Teacher\QuizController as TeacherQuizController;
use App\Core\AuthenticatorInterface;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\UserContext;
use App\Models\ActivityProgress;
use App\Models\User;
use App\Repositories\AcademicRepository;
use App\Repositories\ActivityPrerequisiteRepository;
use App\Repositories\ActivityProgressRepository;
use App\Repositories\AssignmentRepository;
use App\Repositories\ContentRepository;
use App\Repositories\DocumentSectionRepository;
use App\Repositories\EnrollmentRepository;
use App\Repositories\FileRepository;
use App\Repositories\ParentRepository;
use App\Repositories\QuestionBankRepository;
use App\Repositories\QuizRepository;
use App\Repositories\StudentRepository;
use App\Repositories\TeacherRepository;
use App\Repositories\UserRepository;
use App\Services\AssignmentService;
use App\Services\ContentService;
use App\Services\EnrollmentService;
use App\Services\FileStorageService;
use App\Services\PrerequisiteService;
use App\Services\QuizService;
use PDO;
use PHPUnit\Framework\TestCase;

final class ActivityPrerequisiteIntegrationTest extends TestCase
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
    private FileRepository $fileRepo;

    private PrerequisiteService $prerequisiteService;
    private ContentService $contentService;
    private QuizService $quizService;
    private AssignmentService $assignmentService;

    private User $teacherUser;
    private User $otherTeacherUser;
    private User $studentUserA;
    private User $studentUserB;

    private int $teacherId;
    private int $otherTeacherId;
    private int $studentIdA;
    private int $studentIdB;
    private int $classSubjectId;
    private int $pdfContentId;
    private int $section1Id;
    private int $section2Id;
    private int $quizId;
    private int $assignmentId;

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
                `status` VARCHAR(20) NOT NULL DEFAULT 'enrolled',
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

            CREATE TABLE `questions` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `subject_id` INTEGER NOT NULL,
                `topic` VARCHAR(150) NULL,
                `type` VARCHAR(30) NOT NULL DEFAULT 'multiple_choice',
                `question_text` TEXT NOT NULL,
                `default_points` REAL NOT NULL DEFAULT 1.00,
                `created_by` INTEGER NOT NULL DEFAULT 1,
                `created_at` DATETIME NOT NULL,
                `updated_at` DATETIME NOT NULL
            );

            CREATE TABLE `question_options` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `question_id` INTEGER NOT NULL,
                `option_text` TEXT NOT NULL,
                `is_correct` INTEGER NOT NULL DEFAULT 0,
                `created_at` DATETIME NOT NULL,
                `updated_at` DATETIME NOT NULL
            );

            CREATE TABLE `quizzes` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `teacher_id` INTEGER NOT NULL,
                `class_subject_id` INTEGER NOT NULL,
                `term_id` INTEGER NOT NULL,
                `assessment_category_id` INTEGER NULL,
                `title` VARCHAR(255) NOT NULL,
                `instructions` TEXT NULL,
                `time_limit_minutes` INTEGER NOT NULL DEFAULT 30,
                `max_attempts` INTEGER NOT NULL DEFAULT 1,
                `is_published` INTEGER NOT NULL DEFAULT 1,
                `published_at` DATETIME NULL,
                `created_at` DATETIME NOT NULL,
                `updated_at` DATETIME NOT NULL
            );

            CREATE TABLE `quiz_questions` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `quiz_id` INTEGER NOT NULL,
                `question_id` INTEGER NOT NULL,
                `points` DECIMAL(5,2) NOT NULL DEFAULT 1.00,
                `sort_order` INTEGER NOT NULL DEFAULT 1,
                `created_at` DATETIME NOT NULL,
                `updated_at` DATETIME NOT NULL,
                UNIQUE(`quiz_id`, `question_id`)
            );

            CREATE TABLE `quiz_attempts` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `uuid` VARCHAR(36) NOT NULL,
                `quiz_id` INTEGER NOT NULL,
                `student_id` INTEGER NOT NULL,
                `attempt_number` INTEGER NOT NULL DEFAULT 1,
                `started_at` DATETIME NOT NULL,
                `submitted_at` DATETIME NULL,
                `score` REAL NULL,
                `max_score` REAL NOT NULL DEFAULT 100.0,
                `percentage` REAL NULL,
                `is_passed` INTEGER NOT NULL DEFAULT 0,
                `status` VARCHAR(20) NOT NULL DEFAULT 'in_progress',
                `graded_by` INTEGER NULL,
                `graded_at` DATETIME NULL,
                `created_at` DATETIME NOT NULL,
                `updated_at` DATETIME NOT NULL
            );

            CREATE TABLE `quiz_answers` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `attempt_id` INTEGER NOT NULL,
                `question_id` INTEGER NOT NULL,
                `selected_option_id` INTEGER NULL,
                `text_answer` TEXT NULL,
                `points_awarded` REAL NULL,
                `teacher_comment` TEXT NULL,
                `created_at` DATETIME NOT NULL,
                `updated_at` DATETIME NOT NULL,
                UNIQUE (`attempt_id`, `question_id`)
            );

            CREATE TABLE `assignments` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `class_subject_id` INTEGER NOT NULL,
                `term_id` INTEGER NOT NULL,
                `teacher_id` INTEGER NOT NULL,
                `file_id` INTEGER NULL,
                `topic` VARCHAR(255) NULL,
                `title` VARCHAR(255) NOT NULL,
                `instructions` TEXT NULL,
                `due_at` DATETIME NOT NULL,
                `max_score` REAL NOT NULL DEFAULT 100.00,
                `status` VARCHAR(20) NOT NULL DEFAULT 'published',
                `assessment_category_id` INTEGER NULL,
                `created_at` DATETIME NOT NULL,
                `updated_at` DATETIME NOT NULL
            );

            CREATE TABLE `assignment_submissions` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `assignment_id` INTEGER NOT NULL,
                `student_id` INTEGER NOT NULL,
                `file_id` INTEGER NULL,
                `submission_text` TEXT NULL,
                `submitted_at` DATETIME NOT NULL,
                `status` VARCHAR(20) NOT NULL DEFAULT 'submitted',
                `score` REAL NULL,
                `feedback` TEXT NULL,
                `graded_by` INTEGER NULL,
                `graded_at` DATETIME NULL,
                `created_at` DATETIME NOT NULL,
                `updated_at` DATETIME NOT NULL,
                UNIQUE (`assignment_id`, `student_id`)
            );

            CREATE TABLE `activity_prerequisites` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `activity_type` VARCHAR(50) NOT NULL,
                `activity_id` INTEGER NOT NULL,
                `prerequisite_activity_type` VARCHAR(50) NOT NULL,
                `prerequisite_activity_id` INTEGER NOT NULL,
                `requirement_type` VARCHAR(30) NOT NULL DEFAULT 'completion',
                `created_at` DATETIME NOT NULL,
                `updated_at` DATETIME NOT NULL,
                UNIQUE (`activity_type`, `activity_id`, `prerequisite_activity_type`, `prerequisite_activity_id`)
            );

            CREATE TABLE `learning_activity_progress` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `student_id` INTEGER NOT NULL,
                `activity_type` VARCHAR(50) NOT NULL,
                `activity_id` INTEGER NOT NULL,
                `last_page` INTEGER NULL,
                `total_pages` INTEGER NULL,
                `pages_read_json` TEXT NULL,
                `progress_percent` REAL NOT NULL DEFAULT 0.00,
                `is_completed` INTEGER NOT NULL DEFAULT 0,
                `completed_at` DATETIME NULL,
                `last_accessed_at` DATETIME NOT NULL,
                `created_at` DATETIME NOT NULL,
                `updated_at` DATETIME NOT NULL,
                UNIQUE (`student_id`, `activity_type`, `activity_id`)
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
        $this->fileRepo = new FileRepository($this->pdo);

        $this->prerequisiteService = new PrerequisiteService(
            $this->prereqRepo,
            $this->progressRepo,
            $this->contentRepo,
            $this->sectionRepo,
            $this->quizRepo,
            $this->assignmentRepo,
            $this->academicRepo
        );

        $tempUpload = sys_get_temp_dir() . '/lms_prereq_test_' . uniqid();
        if (!is_dir($tempUpload)) {
            mkdir($tempUpload, 0755, true);
        }

        $fileStorage = new FileStorageService(
            fileRepository: $this->fileRepo,
            contentRepository: $this->contentRepo,
            uploadDir: $tempUpload,
            maxSizeBytes: 26214400,
            academicRepository: $this->academicRepo,
            teacherRepository: $this->teacherRepo,
            studentRepository: $this->studentRepo,
            enrollmentRepository: $this->enrollmentRepo
        );

        $this->contentService = new ContentService(
            contentRepository: $this->contentRepo,
            fileRepository: $this->fileRepo,
            fileStorageService: $fileStorage,
            academicRepository: $this->academicRepo,
            teacherRepository: $this->teacherRepo,
            studentRepository: $this->studentRepo,
            enrollmentRepository: $this->enrollmentRepo,
            parentRepository: null,
            pdo: $this->pdo,
            activityProgressRepository: $this->progressRepo,
            documentSectionRepository: $this->sectionRepo,
            prerequisiteRepository: $this->prereqRepo
        );

        $questionBankRepo = new QuestionBankRepository($this->pdo);
        $this->quizService = new QuizService(
            quizRepository: $this->quizRepo,
            questionBankRepository: $questionBankRepo,
            academicRepository: $this->academicRepo,
            teacherRepository: $this->teacherRepo,
            studentRepository: $this->studentRepo,
            enrollmentRepository: $this->enrollmentRepo,
            parentRepository: null,
            activityProgressRepository: $this->progressRepo,
            prerequisiteRepository: $this->prereqRepo
        );

        $this->assignmentService = new AssignmentService(
            assignmentRepository: $this->assignmentRepo,
            academicRepository: $this->academicRepo,
            teacherRepository: $this->teacherRepo,
            studentRepository: $this->studentRepo,
            enrollmentRepository: $this->enrollmentRepo,
            parentRepository: null,
            fileRepository: $this->fileRepo,
            fileStorageService: $fileStorage,
            activityProgressRepository: $this->progressRepo,
            prerequisiteRepository: $this->prereqRepo
        );
    }

    private function seedData(): void
    {
        $now = date('Y-m-d H:i:s');

        // Users
        $this->teacherUser = $this->userRepo->create([
            'uuid' => 't-u-1',
            'name' => 'Prof. Xavier',
            'email' => 'xavier@school.edu',
            'password_hash' => 'hash',
            'status' => 'active',
        ], ['teacher']);
        $teacher = $this->teacherRepo->createTeacher($this->teacherUser->id, 'STF-XAVIER');
        $this->teacherId = $teacher->id;

        $this->otherTeacherUser = $this->userRepo->create([
            'uuid' => 't-u-2',
            'name' => 'Prof. Magneto',
            'email' => 'magneto@school.edu',
            'password_hash' => 'hash',
            'status' => 'active',
        ], ['teacher']);
        $otherTeacher = $this->teacherRepo->createTeacher($this->otherTeacherUser->id, 'STF-MAGNETO');
        $this->otherTeacherId = $otherTeacher->id;

        $this->studentUserA = $this->userRepo->create([
            'uuid' => 's-u-1',
            'name' => 'Student Alice',
            'email' => 'alice@school.edu',
            'password_hash' => 'hash',
            'status' => 'active',
        ], ['student']);
        $studentA = $this->studentRepo->create($this->studentUserA->id, 'STD-ALICE');
        $this->studentIdA = $studentA->id;

        $this->studentUserB = $this->userRepo->create([
            'uuid' => 's-u-2',
            'name' => 'Student Bob',
            'email' => 'bob@school.edu',
            'password_hash' => 'hash',
            'status' => 'active',
        ], ['student']);
        $studentB = $this->studentRepo->create($this->studentUserB->id, 'STD-BOB');
        $this->studentIdB = $studentB->id;

        // Academic Setup
        $this->pdo->exec("INSERT INTO academic_levels (name, stage, rank_order, created_at, updated_at) VALUES ('Grade 10', 'high_school', 1, '{$now}', '{$now}')");
        $this->pdo->exec("INSERT INTO classes (academic_level_id, name, section_arm, status, created_at, updated_at) VALUES (1, '10 A', 'A', 'active', '{$now}', '{$now}')");
        $this->pdo->exec("INSERT INTO sessions (name, start_date, end_date, is_current, status, created_at, updated_at) VALUES ('2026/2027', '2026-09-01', '2027-06-30', 1, 'active', '{$now}', '{$now}')");
        $this->pdo->exec("INSERT INTO terms (session_id, name, start_date, end_date, is_current, status, created_at, updated_at) VALUES (1, 'Term 1', '2026-09-01', '2026-12-15', 1, 'active', '{$now}', '{$now}')");
        $this->pdo->exec("INSERT INTO subjects (code, name, category, status, created_at, updated_at) VALUES ('PHY101', 'Physics', 'Science', 'active', '{$now}', '{$now}')");
        $this->pdo->exec("INSERT INTO class_subjects (session_id, class_id, subject_id, teacher_id, status, created_at, updated_at) VALUES (1, 1, 1, {$this->teacherId}, 'active', '{$now}', '{$now}')");
        $this->classSubjectId = (int)$this->pdo->lastInsertId();

        // Enroll Alice & Bob
        $this->enrollmentRepo->enrollInSubject($this->studentIdA, $this->classSubjectId, 1);
        $this->enrollmentRepo->enrollInSubject($this->studentIdB, $this->classSubjectId, 1);

        // Dummy PDF file record
        $this->pdo->exec("
            INSERT INTO files (uuid, storage_key, original_name, mime_type, size_bytes, sha256, uploaded_by, owner_type, owner_id, created_at)
            VALUES ('file-pdf-uuid', 'storage/dummy.pdf', 'physics_courseware.pdf', 'application/pdf', 10240, 'fakehash', {$this->teacherUser->id}, 'content_item', 1, '{$now}')
        ");
        $fileId = (int)$this->pdo->lastInsertId();

        // Content Item (Document)
        $this->pdo->exec("
            INSERT INTO content_items (class_subject_id, teacher_id, title, description, type, file_id, published_at, created_at, updated_at)
            VALUES ({$this->classSubjectId}, {$this->teacherId}, 'Mechanics & Heat Textbook', 'Core textbook', 'document', {$fileId}, '{$now}', '{$now}', '{$now}')
        ");
        $this->pdfContentId = (int)$this->pdo->lastInsertId();

        // Sections
        $this->pdo->exec("
            INSERT INTO document_sections (content_item_id, title, start_page, end_page, sequence_order, created_at, updated_at)
            VALUES ({$this->pdfContentId}, 'Section 1: Kinematics', 1, 5, 1, '{$now}', '{$now}')
        ");
        $this->section1Id = (int)$this->pdo->lastInsertId();

        $this->pdo->exec("
            INSERT INTO document_sections (content_item_id, title, start_page, end_page, sequence_order, created_at, updated_at)
            VALUES ({$this->pdfContentId}, 'Section 2: Dynamics', 6, 10, 2, '{$now}', '{$now}')
        ");
        $this->section2Id = (int)$this->pdo->lastInsertId();

        // Quiz
        $this->pdo->exec("
            INSERT INTO quizzes (teacher_id, class_subject_id, term_id, title, instructions, time_limit_minutes, max_attempts, is_published, published_at, created_at, updated_at)
            VALUES ({$this->teacherId}, {$this->classSubjectId}, 1, 'Kinematics Mastery Quiz', 'Test your knowledge', 20, 2, 1, '{$now}', '{$now}', '{$now}')
        ");
        $this->quizId = (int)$this->pdo->lastInsertId();

        $this->pdo->exec("
            INSERT INTO questions (subject_id, type, question_text, default_points, created_by, created_at, updated_at)
            VALUES (1, 'multiple_choice', 'What is velocity?', 1.00, {$this->teacherUser->id}, '{$now}', '{$now}');
            INSERT INTO question_options (question_id, option_text, is_correct, created_at, updated_at)
            VALUES (1, 'Rate of displacement', 1, '{$now}', '{$now}');
            INSERT INTO quiz_questions (quiz_id, question_id, points, sort_order, created_at, updated_at)
            VALUES ({$this->quizId}, 1, 1.00, 1, '{$now}', '{$now}');
        ");

        // Assignment
        $this->pdo->exec("
            INSERT INTO assignments (class_subject_id, term_id, teacher_id, title, instructions, due_at, max_score, status, created_at, updated_at)
            VALUES ({$this->classSubjectId}, 1, {$this->teacherId}, 'Kinematics Problem Set', 'Submit written solutions', '2026-12-31 23:59:59', 100.0, 'published', '{$now}', '{$now}')
        ");
        $this->assignmentId = (int)$this->pdo->lastInsertId();
    }

    private function createMockAuthenticator(UserContext $userContext): AuthenticatorInterface
    {
        return new class($userContext) implements AuthenticatorInterface {
            public function __construct(private UserContext $context) {}
            public function authenticate(Request $request): ?UserContext
            {
                return $this->context;
            }
            public function check(Request $request): bool
            {
                return true;
            }
            public function user(Request $request): ?UserContext
            {
                return $this->context;
            }
            public function getUserContext(?Request $request = null): ?UserContext
            {
                return $this->context;
            }
        };
    }

    private function createRequest(string $method, string $uri, array $postData = [], array $queryParams = []): Request
    {
        return new Request(
            queryParams: $queryParams,
            postParams: $postData,
            serverParams: [
                'REQUEST_METHOD' => strtoupper($method),
                'REQUEST_URI' => $uri,
            ]
        );
    }

    // =========================================================================
    // 1. DIRECT URL BYPASS BLOCKED ON QUIZZES, CONTENT READ & ASSIGNMENTS
    // =========================================================================

    public function testDirectUrlBypassAttemptOnLockedQuizIsBlocked(): void
    {
        // Require Section 1 for Quiz
        $this->prerequisiteService->createPrerequisite(
            ActivityProgress::TYPE_QUIZ,
            $this->quizId,
            ActivityProgress::TYPE_DOCUMENT_SECTION,
            $this->section1Id
        );

        // Student Alice has NOT completed Section 1
        $auth = $this->createMockAuthenticator(UserContext::fromUser($this->studentUserA));
        $controller = new QuizAttemptController(
            authenticator: $auth,
            quizService: $this->quizService,
            prerequisiteService: $this->prerequisiteService,
            studentRepository: $this->studentRepo
        );

        $request = $this->createRequest('POST', "/student/quizzes/{$this->quizId}/attempts");
        $response = $controller->start($request, $this->quizId);

        // Must redirect back to quiz show with error
        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame("/student/quizzes/{$this->quizId}", $response->getHeader('Location'));
        $this->assertTrue(Session::hasFlash('error'));
        $this->assertStringContainsString('Access denied', (string)Session::getFlash('error'));

        // Verify zero attempts created in database
        $count = (int)$this->pdo->query("SELECT COUNT(*) FROM quiz_attempts WHERE quiz_id = {$this->quizId}")->fetchColumn();
        $this->assertSame(0, $count);
    }

    public function testDirectUrlBypassToLockedSectionPageIsBlocked(): void
    {
        // Section 2 requires Section 1
        $this->prerequisiteService->createPrerequisite(
            ActivityProgress::TYPE_DOCUMENT_SECTION,
            $this->section2Id,
            ActivityProgress::TYPE_DOCUMENT_SECTION,
            $this->section1Id
        );

        // Student Alice tries to read page 7 (which falls in Section 2: pages 6-10) directly via ?page=7
        $auth = $this->createMockAuthenticator(UserContext::fromUser($this->studentUserA));
        $controller = new StudentContentController(
            authenticator: $auth,
            contentService: $this->contentService,
            academicRepo: $this->academicRepo,
            studentRepo: $this->studentRepo,
            enrollmentRepo: $this->enrollmentRepo,
            prerequisiteService: $this->prerequisiteService
        );

        $request = $this->createRequest('GET', "/student/content/{$this->pdfContentId}/read", queryParams: ['page' => '7']);
        $response = $controller->read($request, $this->pdfContentId);

        // Must redirect to content show page with lock error
        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame("/student/content/{$this->pdfContentId}", $response->getHeader('Location'));
        $this->assertTrue(Session::hasFlash('error'));
        $this->assertStringContainsString('currently locked', (string)Session::getFlash('error'));
    }

    public function testDirectSubmissionBypassToLockedAssignmentIsBlocked(): void
    {
        // Assignment requires Section 1
        $this->prerequisiteService->createPrerequisite(
            ActivityProgress::TYPE_ASSIGNMENT,
            $this->assignmentId,
            ActivityProgress::TYPE_DOCUMENT_SECTION,
            $this->section1Id
        );

        // Student Alice attempts to POST submission directly
        $auth = $this->createMockAuthenticator(UserContext::fromUser($this->studentUserA));
        $controller = new SubmissionController(
            authenticator: $auth,
            assignmentService: $this->assignmentService,
            prerequisiteService: $this->prerequisiteService,
            studentRepository: $this->studentRepo
        );

        $request = $this->createRequest('POST', "/student/assignments/{$this->assignmentId}/submit", ['submission_text' => 'Direct bypass attempt']);
        $response = $controller->store($request, $this->assignmentId);

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame("/student/assignments/{$this->assignmentId}", $response->getHeader('Location'));
        $this->assertTrue(Session::hasFlash('error'));
        $this->assertStringContainsString('Access denied', (string)Session::getFlash('error'));

        // Verify zero submissions recorded
        $count = (int)$this->pdo->query("SELECT COUNT(*) FROM assignment_submissions WHERE assignment_id = {$this->assignmentId}")->fetchColumn();
        $this->assertSame(0, $count);
    }

    // =========================================================================
    // 2. PREREQUISITE PROGRESS COMPLETION AND UNLOCKING
    // =========================================================================

    public function testCompletingSectionUnlocksDependentQuizAndSection(): void
    {
        // Section 2 requires Section 1
        $this->prerequisiteService->createPrerequisite(
            ActivityProgress::TYPE_DOCUMENT_SECTION,
            $this->section2Id,
            ActivityProgress::TYPE_DOCUMENT_SECTION,
            $this->section1Id
        );

        // Quiz requires Section 1
        $this->prerequisiteService->createPrerequisite(
            ActivityProgress::TYPE_QUIZ,
            $this->quizId,
            ActivityProgress::TYPE_DOCUMENT_SECTION,
            $this->section1Id
        );

        $this->assertFalse($this->prerequisiteService->isActivityUnlocked($this->studentIdA, ActivityProgress::TYPE_DOCUMENT_SECTION, $this->section2Id));
        $this->assertFalse($this->prerequisiteService->isActivityUnlocked($this->studentIdA, ActivityProgress::TYPE_QUIZ, $this->quizId));

        // Alice completes Section 1 (marks 100% or completion recorded)
        $this->progressRepo->recordActivityCompletion($this->studentIdA, ActivityProgress::TYPE_DOCUMENT_SECTION, $this->section1Id);

        // Both activities are now unlocked for Alice!
        $this->assertTrue($this->prerequisiteService->isActivityUnlocked($this->studentIdA, ActivityProgress::TYPE_DOCUMENT_SECTION, $this->section2Id));
        $this->assertTrue($this->prerequisiteService->isActivityUnlocked($this->studentIdA, ActivityProgress::TYPE_QUIZ, $this->quizId));

        // Now direct attempt on Quiz succeeds
        $auth = $this->createMockAuthenticator(UserContext::fromUser($this->studentUserA));
        $quizController = new QuizAttemptController(
            authenticator: $auth,
            quizService: $this->quizService,
            prerequisiteService: $this->prerequisiteService,
            studentRepository: $this->studentRepo
        );

        $request = $this->createRequest('POST', "/student/quizzes/{$this->quizId}/attempts");
        $response = $quizController->start($request, $this->quizId);

        $this->assertSame(302, $response->getStatusCode());
        $this->assertNull(Session::getFlash('error'), 'Unexpected start error: ' . (string)Session::getFlash('error'));
        $this->assertStringContainsString('/student/quiz-attempts/', (string)$response->getHeader('Location'));
    }

    // =========================================================================
    // 3. MULTI-PREREQUISITE UNLOCKING
    // =========================================================================

    public function testMultiPrerequisitesRequireAllToUnlock(): void
    {
        // Assignment requires BOTH Section 1 AND Section 2
        $this->prerequisiteService->createPrerequisite(
            ActivityProgress::TYPE_ASSIGNMENT,
            $this->assignmentId,
            ActivityProgress::TYPE_DOCUMENT_SECTION,
            $this->section1Id
        );
        $this->prerequisiteService->createPrerequisite(
            ActivityProgress::TYPE_ASSIGNMENT,
            $this->assignmentId,
            ActivityProgress::TYPE_DOCUMENT_SECTION,
            $this->section2Id
        );

        // Complete Section 1 only
        $this->progressRepo->recordActivityCompletion($this->studentIdA, ActivityProgress::TYPE_DOCUMENT_SECTION, $this->section1Id);
        $this->assertFalse($this->prerequisiteService->isActivityUnlocked($this->studentIdA, ActivityProgress::TYPE_ASSIGNMENT, $this->assignmentId));

        // Complete Section 2
        $this->progressRepo->recordActivityCompletion($this->studentIdA, ActivityProgress::TYPE_DOCUMENT_SECTION, $this->section2Id);
        $this->assertTrue($this->prerequisiteService->isActivityUnlocked($this->studentIdA, ActivityProgress::TYPE_ASSIGNMENT, $this->assignmentId));
    }

    // =========================================================================
    // 4. STUDENT ISOLATION
    // =========================================================================

    public function testStudentIsolationAliceCompletionDoesNotUnlockBob(): void
    {
        $this->prerequisiteService->createPrerequisite(
            ActivityProgress::TYPE_QUIZ,
            $this->quizId,
            ActivityProgress::TYPE_DOCUMENT_SECTION,
            $this->section1Id
        );

        // Alice completes Section 1
        $this->progressRepo->recordActivityCompletion($this->studentIdA, ActivityProgress::TYPE_DOCUMENT_SECTION, $this->section1Id);

        // Alice is unlocked
        $this->assertTrue($this->prerequisiteService->isActivityUnlocked($this->studentIdA, ActivityProgress::TYPE_QUIZ, $this->quizId));

        // Bob remains locked
        $this->assertFalse($this->prerequisiteService->isActivityUnlocked($this->studentIdB, ActivityProgress::TYPE_QUIZ, $this->quizId));
    }

    // =========================================================================
    // 5. DELETION CLEANUP
    // =========================================================================

    public function testDeletingPrerequisiteCleansUpRelationshipsAndUnlocks(): void
    {
        // Quiz requires Section 2
        $this->prerequisiteService->createPrerequisite(
            ActivityProgress::TYPE_QUIZ,
            $this->quizId,
            ActivityProgress::TYPE_DOCUMENT_SECTION,
            $this->section2Id
        );

        $this->assertFalse($this->prerequisiteService->isActivityUnlocked($this->studentIdA, ActivityProgress::TYPE_QUIZ, $this->quizId));

        // Teacher deletes Section 2
        $teacherContext = UserContext::fromUser($this->teacherUser);
        $delResult = $this->contentService->deleteSection($this->section2Id, $teacherContext);
        $this->assertTrue($delResult->isSuccess());

        // Relationships referencing Section 2 should be cleaned up
        $prereqs = $this->prereqRepo->getPrerequisitesForActivity(ActivityProgress::TYPE_QUIZ, $this->quizId);
        $this->assertEmpty($prereqs);

        // Quiz has no more prerequisites, so it is unlocked!
        $this->assertTrue($this->prerequisiteService->isActivityUnlocked($this->studentIdA, ActivityProgress::TYPE_QUIZ, $this->quizId));
    }

    // =========================================================================
    // 6. TEACHER MANAGEMENT ENDPOINTS & RBAC
    // =========================================================================

    public function testTeacherCanAddAndDeletePrerequisiteViaController(): void
    {
        $auth = $this->createMockAuthenticator(UserContext::fromUser($this->teacherUser));
        $controller = new TeacherQuizController(
            authenticator: $auth,
            quizService: $this->quizService,
            quizRepository: $this->quizRepo,
            academicRepository: $this->academicRepo,
            teacherRepository: $this->teacherRepo,
            prerequisiteService: $this->prerequisiteService,
            contentRepository: $this->contentRepo,
            sectionRepository: $this->sectionRepo
        );

        // 1. Add Section 1 as prerequisite for Quiz
        $addReq = $this->createRequest('POST', "/teacher/quizzes/{$this->quizId}/prerequisites", [
            'prerequisite_type' => ActivityProgress::TYPE_DOCUMENT_SECTION,
            'prerequisite_id' => (string)$this->section1Id,
        ]);
        $res = $controller->addPrerequisite($addReq, $this->quizId);

        $this->assertSame(302, $res->getStatusCode());
        $this->assertTrue(Session::hasFlash('success'));

        $configured = $this->prerequisiteService->getConfiguredPrerequisites(ActivityProgress::TYPE_QUIZ, $this->quizId);
        $this->assertCount(1, $configured);
        $prereqRelId = (int)$configured[0]['id'];

        // 2. Delete the prerequisite
        $delReq = $this->createRequest('POST', "/teacher/quizzes/{$this->quizId}/prerequisites/{$prereqRelId}/delete");
        $delRes = $controller->deletePrerequisite($delReq, $this->quizId, $prereqRelId);

        $this->assertSame(302, $delRes->getStatusCode());
        $this->assertTrue(Session::hasFlash('success'));

        $configuredAfter = $this->prerequisiteService->getConfiguredPrerequisites(ActivityProgress::TYPE_QUIZ, $this->quizId);
        $this->assertEmpty($configuredAfter);
    }

    public function testTeacherEndpointRejectsCycleCreation(): void
    {
        // ContentController addPrerequisite: make Section 2 depend on Section 1
        $auth = $this->createMockAuthenticator(UserContext::fromUser($this->teacherUser));
        $controller = new TeacherContentController(
            authenticator: $auth,
            contentService: $this->contentService,
            academicRepository: $this->academicRepo,
            teacherRepository: $this->teacherRepo,
            prerequisiteService: $this->prerequisiteService
        );

        // Section 2 requires Section 1
        $this->prerequisiteService->createPrerequisite(
            ActivityProgress::TYPE_DOCUMENT_SECTION,
            $this->section2Id,
            ActivityProgress::TYPE_DOCUMENT_SECTION,
            $this->section1Id
        );

        // Attempting to make Section 1 require Section 2 should fail with error in session
        $req = $this->createRequest('POST', "/teacher/content/{$this->pdfContentId}/prerequisites", [
            'target_type' => ActivityProgress::TYPE_DOCUMENT_SECTION,
            'target_id' => (string)$this->section1Id,
            'prerequisite_type' => ActivityProgress::TYPE_DOCUMENT_SECTION,
            'prerequisite_id' => (string)$this->section2Id,
        ]);

        $res = $controller->addPrerequisite($req, $this->pdfContentId);
        $this->assertSame(302, $res->getStatusCode());
        $this->assertTrue(Session::hasFlash('error'));
        $this->assertStringContainsString('circular dependency', strtolower((string)Session::getFlash('error')));
    }

    public function testUnauthorizedTeacherCannotManagePrerequisites(): void
    {
        $otherAuth = $this->createMockAuthenticator(UserContext::fromUser($this->otherTeacherUser));
        $controller = new TeacherQuizController(
            authenticator: $otherAuth,
            quizService: $this->quizService,
            quizRepository: $this->quizRepo,
            academicRepository: $this->academicRepo,
            teacherRepository: $this->teacherRepo,
            prerequisiteService: $this->prerequisiteService,
            contentRepository: $this->contentRepo,
            sectionRepository: $this->sectionRepo
        );

        $req = $this->createRequest('POST', "/teacher/quizzes/{$this->quizId}/prerequisites", [
            'prerequisite_type' => ActivityProgress::TYPE_DOCUMENT_SECTION,
            'prerequisite_id' => (string)$this->section1Id,
        ]);

        $res = $controller->addPrerequisite($req, $this->quizId);
        $this->assertSame(403, $res->getStatusCode());
    }
}
