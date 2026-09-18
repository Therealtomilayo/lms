<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Controllers\Student\SubjectController as StudentSubjectController;
use App\Controllers\Teacher\ModuleController as TeacherModuleController;
use App\Core\AuthenticatorInterface;
use App\Core\Exceptions\AuthorizationException;
use App\Core\Exceptions\DomainRuleException;
use App\Core\Exceptions\ValidationException;
use App\Core\Request;
use App\Core\Response;
use App\Core\UserContext;
use App\Models\ActivityProgress;
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
use App\Services\ContentService;
use App\Services\ModuleService;
use App\Services\PrerequisiteService;
use PDO;
use PHPUnit\Framework\TestCase;

/**
 * Comprehensive Integration Tests for Phase 6: Course/Module Progression and Learning Path
 */
final class CourseProgressionIntegrationTest extends TestCase
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

    private User $teacherUser;
    private User $otherTeacherUser;
    private User $studentUserA;
    private User $studentUserB;

    private int $teacherId;
    private int $otherTeacherId;
    private int $studentIdA;
    private int $studentIdB;
    private int $classSubjectId;
    private int $otherClassSubjectId;

    private int $pdfDocId;
    private int $docxDocId;
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
                `title` VARCHAR(200) NOT NULL,
                `start_page` INTEGER NOT NULL DEFAULT 1,
                `end_page` INTEGER NOT NULL DEFAULT 1,
                `sequence_order` INTEGER NOT NULL DEFAULT 0,
                `created_at` DATETIME NOT NULL,
                `updated_at` DATETIME NOT NULL
            );

            CREATE TABLE `quizzes` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `class_subject_id` INTEGER NOT NULL,
                `term_id` INTEGER NOT NULL,
                `assessment_category_id` INTEGER NULL,
                `teacher_id` INTEGER NOT NULL,
                `title` VARCHAR(200) NOT NULL,
                `instructions` TEXT NULL,
                `time_limit_minutes` INTEGER NOT NULL DEFAULT 0,
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
                `points` NUMERIC NOT NULL DEFAULT 1.00,
                `sort_order` INTEGER NOT NULL DEFAULT 0,
                `created_at` DATETIME NOT NULL,
                `updated_at` DATETIME NOT NULL
            );

            CREATE TABLE `quiz_attempts` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `uuid` VARCHAR(36) NOT NULL,
                `quiz_id` INTEGER NOT NULL,
                `student_id` INTEGER NOT NULL,
                `attempt_number` INTEGER NOT NULL DEFAULT 1,
                `started_at` DATETIME NOT NULL,
                `submitted_at` DATETIME NULL,
                `score` NUMERIC NULL,
                `max_score` NUMERIC NOT NULL DEFAULT 0.00,
                `status` VARCHAR(20) NOT NULL DEFAULT 'in_progress',
                `created_at` DATETIME NOT NULL,
                `updated_at` DATETIME NOT NULL
            );

            CREATE TABLE `assignments` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `class_subject_id` INTEGER NOT NULL,
                `term_id` INTEGER NOT NULL,
                `assessment_category_id` INTEGER NULL,
                `teacher_id` INTEGER NOT NULL,
                `topic` VARCHAR(100) NULL,
                `title` VARCHAR(200) NOT NULL,
                `instructions` TEXT NOT NULL,
                `due_at` DATETIME NOT NULL,
                `max_score` NUMERIC NOT NULL DEFAULT 100.00,
                `file_id` INTEGER NULL,
                `status` VARCHAR(20) NOT NULL DEFAULT 'published',
                `created_at` DATETIME NOT NULL,
                `updated_at` DATETIME NOT NULL
            );

            CREATE TABLE `learning_activity_progress` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `student_id` INTEGER NOT NULL,
                `activity_type` VARCHAR(50) NOT NULL,
                `activity_id` INTEGER NOT NULL,
                `last_page` INTEGER NULL,
                `total_pages` INTEGER NULL,
                `pages_read_json` TEXT NULL,
                `progress_percent` NUMERIC NOT NULL DEFAULT 0.00,
                `is_completed` INTEGER NOT NULL DEFAULT 0,
                `completed_at` DATETIME NULL,
                `last_accessed_at` DATETIME NOT NULL,
                `created_at` DATETIME NOT NULL,
                `updated_at` DATETIME NOT NULL,
                UNIQUE(`student_id`, `activity_type`, `activity_id`)
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
                UNIQUE(`activity_type`, `activity_id`, `prerequisite_activity_type`, `prerequisite_activity_id`)
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
            fileRepo: $this->fileRepo
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
            prerequisiteRepository: $this->prereqRepo
        );
    }

    private function seedData(): void
    {
        $now = date('Y-m-d H:i:s');

        // Users
        $this->teacherUser = $this->userRepo->create([
            'uuid' => 'usr-teach-1',
            'name' => 'Prof. Harrison Clark',
            'email' => 'harrison@example.com',
            'password_hash' => password_hash('Secret123!', PASSWORD_BCRYPT),
            'status' => 'active',
        ], ['teacher']);

        $this->otherTeacherUser = $this->userRepo->create([
            'uuid' => 'usr-teach-2',
            'name' => 'Dr. Victoria Gray',
            'email' => 'victoria@example.com',
            'password_hash' => password_hash('Secret123!', PASSWORD_BCRYPT),
            'status' => 'active',
        ], ['teacher']);

        $this->studentUserA = $this->userRepo->create([
            'uuid' => 'usr-stud-1',
            'name' => 'Tomi Adeyemi',
            'email' => 'tomi@example.com',
            'password_hash' => password_hash('Secret123!', PASSWORD_BCRYPT),
            'status' => 'active',
        ], ['student']);

        $this->studentUserB = $this->userRepo->create([
            'uuid' => 'usr-stud-2',
            'name' => 'Samuel Okafor',
            'email' => 'samuel@example.com',
            'password_hash' => password_hash('Secret123!', PASSWORD_BCRYPT),
            'status' => 'active',
        ], ['student']);

        // Academic Foundation
        $this->pdo->exec("
            INSERT INTO `academic_levels` (`id`, `name`, `stage`, `created_at`, `updated_at`)
            VALUES (1, 'Senior Secondary 1', 'senior_secondary', '{$now}', '{$now}');

            INSERT INTO `classes` (`id`, `academic_level_id`, `name`, `section_arm`, `created_at`, `updated_at`)
            VALUES (1, 1, 'SS 1', 'Gold', '{$now}', '{$now}');

            INSERT INTO `sessions` (`id`, `name`, `start_date`, `end_date`, `is_current`, `status`, `created_at`, `updated_at`)
            VALUES (1, '2026/2027', '2026-09-01', '2027-07-31', 1, 'active', '{$now}', '{$now}');

            INSERT INTO `terms` (`id`, `session_id`, `name`, `start_date`, `end_date`, `is_current`, `status`, `created_at`, `updated_at`)
            VALUES (1, 1, 'First Term', '2026-09-01', '2026-12-15', 1, 'active', '{$now}', '{$now}');

            INSERT INTO `subjects` (`id`, `code`, `name`, `category`, `status`, `created_at`, `updated_at`)
            VALUES (1, 'MTH101', 'General Mathematics', 'core', 'active', '{$now}', '{$now}'),
                   (2, 'ENG101', 'English Language', 'core', 'active', '{$now}', '{$now}');
        ");

        // Teachers & Students
        $t1 = $this->teacherRepo->createTeacher($this->teacherUser->id, 'STF/2026/001');
        $this->teacherId = $t1->id;

        $t2 = $this->teacherRepo->createTeacher($this->otherTeacherUser->id, 'STF/2026/002');
        $this->otherTeacherId = $t2->id;

        $s1 = $this->studentRepo->create($this->studentUserA->id, 'STD/2026/001', currentClassId: 1);
        $this->studentIdA = $s1->id;

        $s2 = $this->studentRepo->create($this->studentUserB->id, 'STD/2026/002', currentClassId: 1);
        $this->studentIdB = $s2->id;

        // Class Subjects
        $this->pdo->exec("
            INSERT INTO `class_subjects` (`id`, `session_id`, `class_id`, `subject_id`, `teacher_id`, `status`, `created_at`, `updated_at`)
            VALUES (1, 1, 1, 1, {$this->teacherId}, 'active', '{$now}', '{$now}'),
                   (2, 1, 1, 2, {$this->otherTeacherId}, 'active', '{$now}', '{$now}');
        ");
        $this->classSubjectId = 1;
        $this->otherClassSubjectId = 2;

        // Student A is enrolled in Subject 1 (Mathematics)
        $this->pdo->exec("
            INSERT INTO `student_subject_enrollments` (`student_id`, `class_subject_id`, `session_id`, `is_elective`, `status`, `created_at`, `updated_at`)
            VALUES ({$this->studentIdA}, {$this->classSubjectId}, 1, 0, 'active', '{$now}', '{$now}');
        ");

        // Files
        $this->pdo->exec("
            INSERT INTO `files` (`id`, `uuid`, `storage_key`, `original_name`, `mime_type`, `size_bytes`, `sha256`, `uploaded_by`, `owner_type`, `owner_id`, `created_at`)
            VALUES (1, 'f-pdf-1', 'materials/calculus.pdf', 'calculus.pdf', 'application/pdf', 10240, 'hash1', {$this->teacherUser->id}, 'content_item', 1, '{$now}'),
                   (2, 'f-docx-1', 'materials/algebra.docx', 'algebra.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 20480, 'hash2', {$this->teacherUser->id}, 'content_item', 2, '{$now}');
        ");

        // Content Items
        $this->pdfDocId = $this->contentRepo->create(
            classSubjectId: $this->classSubjectId,
            teacherId: $this->teacherId,
            topic: 'Calculus',
            title: 'Intro to Differential Calculus',
            description: 'Core reading textbook',
            type: 'document',
            fileId: 1,
            publishedAt: $now
        )->id;

        $this->docxDocId = $this->contentRepo->create(
            classSubjectId: $this->classSubjectId,
            teacherId: $this->teacherId,
            topic: 'Algebra',
            title: 'Linear Algebra Notes',
            description: 'Class lecture notes in DOCX',
            type: 'document',
            fileId: 2,
            publishedAt: $now
        )->id;

        // Quiz
        $this->pdo->exec("
            INSERT INTO `quizzes` (`id`, `class_subject_id`, `term_id`, `teacher_id`, `title`, `instructions`, `time_limit_minutes`, `max_attempts`, `is_published`, `published_at`, `created_at`, `updated_at`)
            VALUES (1, {$this->classSubjectId}, 1, {$this->teacherId}, 'Calculus Mid-Term Quiz', 'Answer all questions', 30, 2, 1, '{$now}', '{$now}', '{$now}');
        ");
        $this->quizId = 1;

        // Assignment
        $this->pdo->exec("
            INSERT INTO `assignments` (`id`, `class_subject_id`, `term_id`, `teacher_id`, `topic`, `title`, `instructions`, `due_at`, `max_score`, `status`, `created_at`, `updated_at`)
            VALUES (1, {$this->classSubjectId}, 1, {$this->teacherId}, 'Calculus', 'Calculus Problem Set 1', 'Solve problems 1-10', '2026-10-01 23:59:59', 100.00, 'published', '{$now}', '{$now}');
        ");
        $this->assignmentId = 1;
    }

    private function createTeacherContext(User $user): UserContext
    {
        return new UserContext(
            id: $user->id,
            uuid: $user->uuid,
            name: $user->name,
            email: $user->email,
            roles: ['teacher']
        );
    }

    private function createStudentContext(User $user): UserContext
    {
        return new UserContext(
            id: $user->id,
            uuid: $user->uuid,
            name: $user->name,
            email: $user->email,
            roles: ['student']
        );
    }

    private function recordDocProgress(int $studentId, int $docId, int $totalPages, float $percent): void
    {
        $pagesCount = (int)ceil(($percent / 100.0) * $totalPages);
        $pages = $pagesCount > 0 ? range(1, $pagesCount) : [];
        $this->progressRepo->recordDocumentReadingProgress(
            studentId: $studentId,
            contentItemId: $docId,
            lastPage: max(1, $pagesCount),
            totalPages: $totalPages,
            newPagesNewlyViewed: $pages
        );
    }

    // =========================================================================
    // MODULES CRUD & ORDERING (Tests 1 - 6)
    // =========================================================================

    public function testTeacherCanCreateModuleSuccessfully(): void
    {
        $actor = $this->createTeacherContext($this->teacherUser);

        $module = $this->moduleService->createModule($this->classSubjectId, [
            'title' => 'Module 1: Fundamental Calculus',
            'description' => 'Understand limits and derivatives',
            'sequence_order' => 1,
            'status' => 'published',
        ], $actor);

        $this->assertGreaterThan(0, $module->id);
        $this->assertSame('Module 1: Fundamental Calculus', $module->title);
        $this->assertSame(1, $module->sequenceOrder);
        $this->assertSame(Module::STATUS_PUBLISHED, $module->status);
    }

    public function testTeacherCanEditModuleSuccessfully(): void
    {
        $actor = $this->createTeacherContext($this->teacherUser);
        $module = $this->moduleService->createModule($this->classSubjectId, [
            'title' => 'Original Title',
        ], $actor);

        $updated = $this->moduleService->updateModule($module->id, [
            'title' => 'Updated Module Title',
            'description' => 'Updated description',
            'status' => 'draft',
        ], $actor);

        $this->assertSame('Updated Module Title', $updated->title);
        $this->assertSame('Updated description', $updated->description);
        $this->assertSame('draft', $updated->status);
    }

    public function testDeleteModuleSafelyBlocksWhenActivitiesAreAttached(): void
    {
        $actor = $this->createTeacherContext($this->teacherUser);
        $module = $this->moduleService->createModule($this->classSubjectId, ['title' => 'Protected Module'], $actor);

        // Attach an activity
        $this->moduleService->addActivityToModule(
            moduleId: $module->id,
            activityType: ModuleItem::TYPE_DOCUMENT,
            activityId: $this->pdfDocId,
            sequenceOrder: 1,
            isRequired: true,
            actor: $actor
        );

        $this->expectException(DomainRuleException::class);
        $this->expectExceptionMessage('it contains 1 learning activity(ies)');

        $this->moduleService->deleteModule($module->id, $actor, false);
    }

    public function testDeleteModuleSafelySucceedsWhenModuleIsEmpty(): void
    {
        $actor = $this->createTeacherContext($this->teacherUser);
        $module = $this->moduleService->createModule($this->classSubjectId, ['title' => 'Empty Module'], $actor);

        $result = $this->moduleService->deleteModule($module->id, $actor, false);
        $this->assertTrue($result);
        $this->assertNull($this->moduleRepo->findModuleById($module->id));
    }

    public function testModuleDeterministicOrderingAndReordering(): void
    {
        $actor = $this->createTeacherContext($this->teacherUser);
        $m1 = $this->moduleService->createModule($this->classSubjectId, ['title' => 'Module A', 'sequence_order' => 1], $actor);
        $m2 = $this->moduleService->createModule($this->classSubjectId, ['title' => 'Module B', 'sequence_order' => 2], $actor);
        $m3 = $this->moduleService->createModule($this->classSubjectId, ['title' => 'Module C', 'sequence_order' => 3], $actor);

        // Reorder: C first, then A, then B
        $this->moduleService->reorderModules($this->classSubjectId, [$m3->id, $m1->id, $m2->id], $actor);

        $ordered = $this->moduleRepo->getModulesByClassSubject($this->classSubjectId, false);
        $this->assertCount(3, $ordered);
        $this->assertSame($m3->id, $ordered[0]->id);
        $this->assertSame(1, $ordered[0]->sequenceOrder);
        $this->assertSame($m1->id, $ordered[1]->id);
        $this->assertSame(2, $ordered[1]->sequenceOrder);
        $this->assertSame($m2->id, $ordered[2]->id);
        $this->assertSame(3, $ordered[2]->sequenceOrder);
    }

    public function testModuleReorderingRejectsForeignModuleId(): void
    {
        $actor = $this->createTeacherContext($this->teacherUser);
        $m1 = $this->moduleService->createModule($this->classSubjectId, ['title' => 'Math Mod'], $actor);

        // Foreign module in English
        $otherActor = $this->createTeacherContext($this->otherTeacherUser);
        $foreignMod = $this->moduleService->createModule($this->otherClassSubjectId, ['title' => 'English Mod'], $otherActor);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage("does not belong to this class subject");

        $this->moduleService->reorderModules($this->classSubjectId, [$m1->id, $foreignMod->id], $actor);
    }

    public function testTeacherCannotModifyAnotherTeachersModule(): void
    {
        $otherActor = $this->createTeacherContext($this->otherTeacherUser);
        $foreignMod = $this->moduleService->createModule($this->otherClassSubjectId, ['title' => 'English Mod'], $otherActor);

        $actor = $this->createTeacherContext($this->teacherUser);

        $this->expectException(AuthorizationException::class);
        $this->expectExceptionMessage('You are not the assigned teacher for this subject');

        $this->moduleService->updateModule($foreignMod->id, ['title' => 'Hijacked'], $actor);
    }

    // =========================================================================
    // ACTIVITY ASSOCIATION & ORDERING (Tests 7 - 10)
    // =========================================================================

    public function testAssignActivityToModule(): void
    {
        $actor = $this->createTeacherContext($this->teacherUser);
        $module = $this->moduleService->createModule($this->classSubjectId, ['title' => 'Module 1'], $actor);

        $item = $this->moduleService->addActivityToModule(
            moduleId: $module->id,
            activityType: ModuleItem::TYPE_DOCUMENT,
            activityId: $this->pdfDocId,
            sequenceOrder: 1,
            isRequired: true,
            actor: $actor
        );

        $this->assertGreaterThan(0, $item->id);
        $this->assertSame($module->id, $item->moduleId);
        $this->assertSame(ModuleItem::TYPE_DOCUMENT, $item->activityType);
        $this->assertSame($this->pdfDocId, $item->activityId);
        $this->assertTrue($item->isRequired);
        $this->assertSame('Intro to Differential Calculus', $item->title);
    }

    public function testDuplicateActivityInSameModuleRejected(): void
    {
        $actor = $this->createTeacherContext($this->teacherUser);
        $module = $this->moduleService->createModule($this->classSubjectId, ['title' => 'Module 1'], $actor);

        $this->moduleService->addActivityToModule(
            moduleId: $module->id,
            activityType: ModuleItem::TYPE_DOCUMENT,
            activityId: $this->pdfDocId,
            sequenceOrder: 1,
            isRequired: true,
            actor: $actor
        );

        $this->expectException(DomainRuleException::class);
        $this->expectExceptionMessage('already attached to this module');

        $this->moduleService->addActivityToModule(
            moduleId: $module->id,
            activityType: ModuleItem::TYPE_DOCUMENT,
            activityId: $this->pdfDocId,
            sequenceOrder: 2,
            isRequired: true,
            actor: $actor
        );
    }

    public function testAssignActivityFromDifferentClassSubjectRejected(): void
    {
        $actor = $this->createTeacherContext($this->teacherUser);
        $module = $this->moduleService->createModule($this->classSubjectId, ['title' => 'Math Module'], $actor);

        // Create English document in other subject
        $otherDocId = $this->contentRepo->create(
            classSubjectId: $this->otherClassSubjectId,
            teacherId: $this->otherTeacherId,
            topic: 'Grammar',
            title: 'Grammar 101',
            description: null,
            type: 'document'
        )->id;

        $this->expectException(DomainRuleException::class);
        $this->expectExceptionMessage('belongs to class subject #2, but this module belongs to class subject #1');

        $this->moduleService->addActivityToModule(
            moduleId: $module->id,
            activityType: ModuleItem::TYPE_DOCUMENT,
            activityId: $otherDocId,
            actor: $actor
        );
    }

    public function testRemoveAndReorderActivitiesWithinModule(): void
    {
        $actor = $this->createTeacherContext($this->teacherUser);
        $module = $this->moduleService->createModule($this->classSubjectId, ['title' => 'Module 1'], $actor);

        $it1 = $this->moduleService->addActivityToModule($module->id, ModuleItem::TYPE_DOCUMENT, $this->pdfDocId, 1, true, $actor);
        $it2 = $this->moduleService->addActivityToModule($module->id, ModuleItem::TYPE_QUIZ, $this->quizId, 2, true, $actor);
        $it3 = $this->moduleService->addActivityToModule($module->id, ModuleItem::TYPE_ASSIGNMENT, $this->assignmentId, 3, true, $actor);

        // Reorder items: Assignment first, then Quiz, then Doc
        $this->moduleService->reorderActivities($module->id, [$it3->id, $it2->id, $it1->id], $actor);

        $reordered = $this->moduleRepo->getItemsByModuleId($module->id);
        $this->assertSame($it3->id, $reordered[0]->id);
        $this->assertSame(1, $reordered[0]->sequenceOrder);
        $this->assertSame($it2->id, $reordered[1]->id);
        $this->assertSame(2, $reordered[1]->sequenceOrder);
        $this->assertSame($it1->id, $reordered[2]->id);
        $this->assertSame(3, $reordered[2]->sequenceOrder);

        // Remove item 2
        $removed = $this->moduleService->removeActivityFromModule($it2->id, $actor);
        $this->assertTrue($removed);
        $this->assertCount(2, $this->moduleRepo->getItemsByModuleId($module->id));
    }

    // =========================================================================
    // PROGRESS DERIVATION & SECTION COUNTING RULE (Tests 11 - 18)
    // =========================================================================

    public function testModuleProgressDerivedCorrectlyFromAuthoritativeActivityProgress(): void
    {
        $actor = $this->createTeacherContext($this->teacherUser);
        $module = $this->moduleService->createModule($this->classSubjectId, ['title' => 'Module 1'], $actor);

        // Attach 4 activities: PDF Doc, DOCX Doc, Quiz, Assignment
        $this->moduleService->addActivityToModule($module->id, ModuleItem::TYPE_DOCUMENT, $this->pdfDocId, 1, true, $actor);
        $this->moduleService->addActivityToModule($module->id, ModuleItem::TYPE_DOCUMENT, $this->docxDocId, 2, true, $actor);
        $this->moduleService->addActivityToModule($module->id, ModuleItem::TYPE_QUIZ, $this->quizId, 3, true, $actor);
        $this->moduleService->addActivityToModule($module->id, ModuleItem::TYPE_ASSIGNMENT, $this->assignmentId, 4, true, $actor);

        // Initially 0% progress
        $path = $this->moduleService->getLearningPathForStudent($this->classSubjectId, $this->studentIdA);
        $this->assertSame(0.0, $path['modules'][0]->progressPercent);
        $this->assertFalse($path['modules'][0]->isCompleted);
        $this->assertSame('Not Started', $path['modules'][0]->computedStatus);

        // Mark PDF complete: 1/4 = 25%
        $this->recordDocProgress($this->studentIdA, $this->pdfDocId, 10, 100.0);

        $path2 = $this->moduleService->getLearningPathForStudent($this->classSubjectId, $this->studentIdA);
        $this->assertSame(25.0, $path2['modules'][0]->progressPercent);
        $this->assertSame(1, $path2['modules'][0]->completedItemsCount);
        $this->assertSame('In Progress', $path2['modules'][0]->computedStatus);

        // Mark Quiz complete: 2/4 = 50%
        $this->progressRepo->recordActivityCompletion($this->studentIdA, ActivityProgress::TYPE_QUIZ, $this->quizId, 100.0);

        $path3 = $this->moduleService->getLearningPathForStudent($this->classSubjectId, $this->studentIdA);
        $this->assertSame(50.0, $path3['modules'][0]->progressPercent);
        $this->assertSame(2, $path3['modules'][0]->completedItemsCount);

        // Mark Assignment complete: 3/4 = 75%
        $this->progressRepo->recordActivityCompletion($this->studentIdA, ActivityProgress::TYPE_ASSIGNMENT, $this->assignmentId, 100.0);

        $path4 = $this->moduleService->getLearningPathForStudent($this->classSubjectId, $this->studentIdA);
        $this->assertSame(75.0, $path4['modules'][0]->progressPercent);
        $this->assertSame(3, $path4['modules'][0]->completedItemsCount);

        // Mark DOCX complete: 4/4 = 100%
        $this->recordDocProgress($this->studentIdA, $this->docxDocId, 5, 100.0);

        $path5 = $this->moduleService->getLearningPathForStudent($this->classSubjectId, $this->studentIdA);
        $this->assertSame(100.0, $path5['modules'][0]->progressPercent);
        $this->assertSame(4, $path5['modules'][0]->completedItemsCount);
        $this->assertTrue($path5['modules'][0]->isCompleted);
        $this->assertSame('Completed', $path5['modules'][0]->computedStatus);
    }

    public function testDuplicateProgressUpdatesDoNotInflateCompletion(): void
    {
        $actor = $this->createTeacherContext($this->teacherUser);
        $module = $this->moduleService->createModule($this->classSubjectId, ['title' => 'Module 1'], $actor);
        $this->moduleService->addActivityToModule($module->id, ModuleItem::TYPE_DOCUMENT, $this->pdfDocId, 1, true, $actor);
        $this->moduleService->addActivityToModule($module->id, ModuleItem::TYPE_QUIZ, $this->quizId, 2, true, $actor);

        // Update PDF progress multiple times
        $this->recordDocProgress($this->studentIdA, $this->pdfDocId, 10, 100.0);
        $this->recordDocProgress($this->studentIdA, $this->pdfDocId, 10, 100.0);
        $this->recordDocProgress($this->studentIdA, $this->pdfDocId, 10, 100.0);

        $path = $this->moduleService->getLearningPathForStudent($this->classSubjectId, $this->studentIdA);
        $this->assertSame(1, $path['modules'][0]->completedItemsCount);
        $this->assertSame(50.0, $path['modules'][0]->progressPercent);
    }

    public function testDocumentSectionsRuleDocumentIsSingleActivityAndSectionsDoNotDoubleCount(): void
    {
        $actor = $this->createTeacherContext($this->teacherUser);
        $module = $this->moduleService->createModule($this->classSubjectId, ['title' => 'Module 1'], $actor);

        // Create 3 sections inside PDF Document
        $sec1 = $this->sectionRepo->create(['content_item_id' => $this->pdfDocId, 'title' => 'Section 1', 'start_page' => 1, 'end_page' => 3, 'sequence_order' => 1]);
        $sec2 = $this->sectionRepo->create(['content_item_id' => $this->pdfDocId, 'title' => 'Section 2', 'start_page' => 4, 'end_page' => 7, 'sequence_order' => 2]);
        $sec3 = $this->sectionRepo->create(['content_item_id' => $this->pdfDocId, 'title' => 'Section 3', 'start_page' => 8, 'end_page' => 10, 'sequence_order' => 3]);

        // Only the document itself is added to the module as the learning activity (Rule C)
        $this->moduleService->addActivityToModule($module->id, ModuleItem::TYPE_DOCUMENT, $this->pdfDocId, 1, true, $actor);

        // Module has 1 total activity
        $path = $this->moduleService->getLearningPathForStudent($this->classSubjectId, $this->studentIdA);
        $this->assertSame(1, $path['total_required_items']);
        $this->assertSame(1, $path['modules'][0]->totalItemsCount);

        // Student reads pages 1-3 (Section 1 read, but whole doc is 30% read, threshold 90% not met)
        $this->recordDocProgress($this->studentIdA, $this->pdfDocId, 10, 30.0);
        $this->progressRepo->syncSectionProgress($this->studentIdA, $sec1, [1, 2, 3]);

        $pathAfterSec1 = $this->moduleService->getLearningPathForStudent($this->classSubjectId, $this->studentIdA);
        $this->assertSame(0, $pathAfterSec1['total_completed_items']);
        $this->assertSame(0.0, $pathAfterSec1['course_progress_percent']);
        $this->assertFalse($pathAfterSec1['modules'][0]->isCompleted);

        // Student finishes reading through page 9 (90% threshold reached for document)
        $this->recordDocProgress($this->studentIdA, $this->pdfDocId, 10, 90.0);
        $this->progressRepo->syncSectionProgress($this->studentIdA, $sec2, range(1, 9));
        $this->progressRepo->syncSectionProgress($this->studentIdA, $sec3, range(1, 9));

        $pathAfterDoc = $this->moduleService->getLearningPathForStudent($this->classSubjectId, $this->studentIdA);
        $this->assertSame(1, $pathAfterDoc['total_completed_items']);
        $this->assertSame(100.0, $pathAfterDoc['course_progress_percent']);
        $this->assertTrue($pathAfterDoc['modules'][0]->isCompleted);
    }

    public function testCourseProgressAcrossMultipleModulesAndCompletionRule(): void
    {
        $actor = $this->createTeacherContext($this->teacherUser);

        // Module 1: 2 activities
        $m1 = $this->moduleService->createModule($this->classSubjectId, ['title' => 'Module 1', 'sequence_order' => 1], $actor);
        $this->moduleService->addActivityToModule($m1->id, ModuleItem::TYPE_DOCUMENT, $this->pdfDocId, 1, true, $actor);
        $this->moduleService->addActivityToModule($m1->id, ModuleItem::TYPE_DOCUMENT, $this->docxDocId, 2, true, $actor);

        // Module 2: 2 activities
        $m2 = $this->moduleService->createModule($this->classSubjectId, ['title' => 'Module 2', 'sequence_order' => 2], $actor);
        $this->moduleService->addActivityToModule($m2->id, ModuleItem::TYPE_QUIZ, $this->quizId, 1, true, $actor);
        $this->moduleService->addActivityToModule($m2->id, ModuleItem::TYPE_ASSIGNMENT, $this->assignmentId, 2, true, $actor);

        // Total 4 required items across 2 modules
        $path1 = $this->moduleService->getLearningPathForStudent($this->classSubjectId, $this->studentIdA);
        $this->assertSame(4, $path1['total_required_items']);
        $this->assertSame(0, $path1['total_completed_items']);
        $this->assertSame(0.0, $path1['course_progress_percent']);
        $this->assertFalse($path1['is_course_completed']);

        // Complete Module 1 activities (2 of 4 done = 50% course progress)
        $this->recordDocProgress($this->studentIdA, $this->pdfDocId, 10, 100.0);
        $this->recordDocProgress($this->studentIdA, $this->docxDocId, 5, 100.0);

        $path2 = $this->moduleService->getLearningPathForStudent($this->classSubjectId, $this->studentIdA);
        $this->assertSame(100.0, $path2['modules'][0]->progressPercent);
        $this->assertTrue($path2['modules'][0]->isCompleted);
        $this->assertSame(0.0, $path2['modules'][1]->progressPercent);
        $this->assertFalse($path2['modules'][1]->isCompleted);
        $this->assertSame(50.0, $path2['course_progress_percent']);
        $this->assertFalse($path2['is_course_completed']);

        // Complete Quiz (3 of 4 = 75% course progress)
        $this->progressRepo->recordActivityCompletion($this->studentIdA, ActivityProgress::TYPE_QUIZ, $this->quizId, 100.0);
        $path3 = $this->moduleService->getLearningPathForStudent($this->classSubjectId, $this->studentIdA);
        $this->assertSame(75.0, $path3['course_progress_percent']);
        $this->assertFalse($path3['is_course_completed']);

        // Complete Assignment (4 of 4 = 100% course completion)
        $this->progressRepo->recordActivityCompletion($this->studentIdA, ActivityProgress::TYPE_ASSIGNMENT, $this->assignmentId, 100.0);
        $path4 = $this->moduleService->getLearningPathForStudent($this->classSubjectId, $this->studentIdA);
        $this->assertSame(100.0, $path4['course_progress_percent']);
        $this->assertTrue($path4['is_course_completed']);
        $this->assertTrue($path4['modules'][1]->isCompleted);
    }

    // =========================================================================
    // PREREQUISITES INTEGRATION (Tests 19 - 21)
    // =========================================================================

    public function testExistingPrerequisitesLockActivitiesInsideModulesAndUnlockUponCompletion(): void
    {
        $actor = $this->createTeacherContext($this->teacherUser);
        $module = $this->moduleService->createModule($this->classSubjectId, ['title' => 'Gated Module'], $actor);

        $this->moduleService->addActivityToModule($module->id, ModuleItem::TYPE_DOCUMENT, $this->pdfDocId, 1, true, $actor);
        $this->moduleService->addActivityToModule($module->id, ModuleItem::TYPE_QUIZ, $this->quizId, 2, true, $actor);

        // Quiz requires PDF Document
        $this->prereqService->createPrerequisite(
            targetType: ActivityProgress::TYPE_QUIZ,
            targetId: $this->quizId,
            prereqType: ActivityProgress::TYPE_DOCUMENT,
            prereqId: $this->pdfDocId
        );

        // Check outline before completing prerequisite
        $path1 = $this->moduleService->getLearningPathForStudent($this->classSubjectId, $this->studentIdA);
        $quizItem = $path1['modules'][0]->items[1];
        $this->assertFalse($quizItem->isUnlocked);
        $this->assertSame('locked', $quizItem->statusState);
        $this->assertNotEmpty($quizItem->unmetPrerequisites);

        // Complete PDF Document
        $this->recordDocProgress($this->studentIdA, $this->pdfDocId, 10, 100.0);

        // Check outline after completion: Quiz is unlocked!
        $path2 = $this->moduleService->getLearningPathForStudent($this->classSubjectId, $this->studentIdA);
        $quizItemUnlocked = $path2['modules'][0]->items[1];
        $this->assertTrue($quizItemUnlocked->isUnlocked);
        $this->assertSame('not_started', $quizItemUnlocked->statusState);
    }

    public function testModuleOrganizationDoesNotBypassPrerequisites(): void
    {
        $actor = $this->createTeacherContext($this->teacherUser);

        // Module 1 has PDF
        $m1 = $this->moduleService->createModule($this->classSubjectId, ['title' => 'Module 1'], $actor);
        $this->moduleService->addActivityToModule($m1->id, ModuleItem::TYPE_DOCUMENT, $this->pdfDocId, 1, true, $actor);

        // Module 2 has Quiz
        $m2 = $this->moduleService->createModule($this->classSubjectId, ['title' => 'Module 2'], $actor);
        $this->moduleService->addActivityToModule($m2->id, ModuleItem::TYPE_QUIZ, $this->quizId, 1, true, $actor);

        // Quiz in Module 2 requires PDF in Module 1
        $this->prereqService->createPrerequisite(
            targetType: ActivityProgress::TYPE_QUIZ,
            targetId: $this->quizId,
            prereqType: ActivityProgress::TYPE_DOCUMENT,
            prereqId: $this->pdfDocId
        );

        $path = $this->moduleService->getLearningPathForStudent($this->classSubjectId, $this->studentIdA);
        $m2Item = $path['modules'][1]->items[0];
        $this->assertFalse($m2Item->isUnlocked);
        $this->assertSame('locked', $m2Item->statusState);
    }

    // =========================================================================
    // SECURITY & ACCESS CONTROL (Tests 22 - 25)
    // =========================================================================

    public function testTeacherControllerAuthorizationEnforcement(): void
    {
        $mockAuth = $this->createMock(AuthenticatorInterface::class);
        $unauthorizedUser = $this->createTeacherContext($this->otherTeacherUser);
        $mockAuth->method('getUserContext')->willReturn($unauthorizedUser);
        $mockAuth->method('user')->willReturn($unauthorizedUser);

        $controller = new TeacherModuleController(
            authenticator: $mockAuth,
            moduleService: $this->moduleService,
            academicRepo: $this->academicRepo,
            teacherRepo: $this->teacherRepo
        );

        $request = new Request([], ['class_subject_id' => $this->classSubjectId, 'title' => 'Hacked Module']);
        $response = $controller->store($request);

        // Blocked with 403 Forbidden
        $this->assertSame(403, $response->getStatusCode());
    }

    public function testStudentCannotAccessAnotherStudentsProgress(): void
    {
        // Student A has progress on PDF Document
        $this->recordDocProgress($this->studentIdA, $this->pdfDocId, 10, 100.0);

        // Student B queries learning path for themselves
        $pathB = $this->moduleService->getLearningPathForStudent($this->classSubjectId, $this->studentIdB);

        // Student B's progress must be 0% and completed items must be 0
        $this->assertSame(0.0, $pathB['course_progress_percent']);
        $this->assertSame(0, $pathB['total_completed_items']);
    }

    public function testStudentSubjectControllerRendersLearningPathForEnrolledStudent(): void
    {
        $actor = $this->createTeacherContext($this->teacherUser);
        $m1 = $this->moduleService->createModule($this->classSubjectId, ['title' => 'Calculus Basics'], $actor);
        $this->moduleService->addActivityToModule($m1->id, ModuleItem::TYPE_DOCUMENT, $this->pdfDocId, 1, true, $actor);

        $mockAuth = $this->createMock(AuthenticatorInterface::class);
        $studentContext = $this->createStudentContext($this->studentUserA);
        $mockAuth->method('getUserContext')->willReturn($studentContext);
        $mockAuth->method('user')->willReturn($studentContext);

        $controller = new StudentSubjectController(
            authenticator: $mockAuth,
            contentService: $this->contentService,
            moduleService: $this->moduleService,
            academicRepo: $this->academicRepo,
            studentRepo: $this->studentRepo,
            enrollmentRepo: $this->enrollmentRepo,
            assignmentRepo: $this->assignmentRepo,
            quizRepo: $this->quizRepo
        );

        $request = new Request();
        $response = $controller->show($request, $this->classSubjectId);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString('Calculus Basics', $response->getBody());
        $this->assertStringContainsString('Learning Progression Path', $response->getBody());
    }

    public function testUnenrolledStudentIsDeniedSubjectAccess(): void
    {
        $mockAuth = $this->createMock(AuthenticatorInterface::class);
        $studentContext = $this->createStudentContext($this->studentUserB); // Not enrolled in Subject 1
        $mockAuth->method('getUserContext')->willReturn($studentContext);
        $mockAuth->method('user')->willReturn($studentContext);

        $controller = new StudentSubjectController(
            authenticator: $mockAuth,
            contentService: $this->contentService,
            moduleService: $this->moduleService,
            academicRepo: $this->academicRepo,
            studentRepo: $this->studentRepo,
            enrollmentRepo: $this->enrollmentRepo,
            assignmentRepo: $this->assignmentRepo,
            quizRepo: $this->quizRepo
        );

        $request = new Request();
        $response = $controller->show($request, $this->classSubjectId);

        $this->assertSame(404, $response->getStatusCode());
    }

    public function testCourseCompletionDoesNotTriggerUndefinedActorWarningAndRendersSubjectCleanly(): void
    {
        $actor = $this->createTeacherContext($this->teacherUser);
        $m1 = $this->moduleService->createModule($this->classSubjectId, ['title' => 'Completion Test Module'], $actor);
        $this->moduleService->addActivityToModule($m1->id, ModuleItem::TYPE_DOCUMENT, $this->pdfDocId, 1, true, $actor);

        // Mark required document as 100% completed
        $this->recordDocProgress($this->studentIdA, $this->pdfDocId, 10, 100.0);

        $mockAuth = $this->createMock(AuthenticatorInterface::class);
        $studentContext = $this->createStudentContext($this->studentUserA);
        $mockAuth->method('getUserContext')->willReturn($studentContext);
        $mockAuth->method('user')->willReturn($studentContext);

        $controller = new StudentSubjectController(
            authenticator: $mockAuth,
            contentService: $this->contentService,
            moduleService: $this->moduleService,
            academicRepo: $this->academicRepo,
            studentRepo: $this->studentRepo,
            enrollmentRepo: $this->enrollmentRepo,
            assignmentRepo: $this->assignmentRepo,
            quizRepo: $this->quizRepo
        );

        $request = new Request();
        $response = $controller->show($request, $this->classSubjectId);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringNotContainsString('Undefined variable $actor', $response->getBody());
        $this->assertStringContainsString('Completion Test Module', $response->getBody());

        // Also directly test getLearningPathForStudent without actor parameter
        $pathWithoutActor = $this->moduleService->getLearningPathForStudent($this->classSubjectId, $this->studentIdA);
        $this->assertTrue($pathWithoutActor['is_course_completed']);
        $this->assertEquals(100.0, $pathWithoutActor['course_progress_percent']);
    }
}
