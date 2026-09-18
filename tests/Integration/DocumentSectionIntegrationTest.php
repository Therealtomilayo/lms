<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Core\Exceptions\AuthorizationException;
use App\Core\Exceptions\DomainRuleException;
use App\Core\Exceptions\ValidationException;
use App\Core\UserContext;
use App\Models\User;
use App\Repositories\AcademicRepository;
use App\Repositories\ActivityProgressRepository;
use App\Repositories\ContentRepository;
use App\Repositories\DocumentSectionRepository;
use App\Repositories\EnrollmentRepository;
use App\Repositories\FileRepository;
use App\Repositories\ParentRepository;
use App\Repositories\StudentRepository;
use App\Repositories\TeacherRepository;
use App\Repositories\UserRepository;
use App\Services\ContentService;
use App\Services\EnrollmentService;
use App\Services\FileStorageService;
use PDO;
use PHPUnit\Framework\TestCase;

final class DocumentSectionIntegrationTest extends TestCase
{
    private PDO $pdo;
    private UserRepository $userRepo;
    private StudentRepository $studentRepo;
    private ParentRepository $parentRepo;
    private TeacherRepository $teacherRepo;
    private AcademicRepository $academicRepo;
    private EnrollmentRepository $enrollmentRepo;
    private FileRepository $fileRepo;
    private ContentRepository $contentRepo;
    private ActivityProgressRepository $activityProgressRepo;
    private DocumentSectionRepository $documentSectionRepo;

    private EnrollmentService $enrollmentService;
    private FileStorageService $fileStorageService;
    private ContentService $contentService;

    private string $tempUploadDir;

    private User $teacherUser;
    private User $otherTeacherUser;
    private User $studentUser;
    private User $otherStudentUser;

    private int $teacherId;
    private int $otherTeacherId;
    private int $studentId;
    private int $otherStudentId;
    private int $sessionId;
    private int $classSubjectId;
    private int $pdfContentId;
    private int $nonPdfContentId;

    protected function setUp(): void
    {
        $this->tempUploadDir = sys_get_temp_dir() . '/lms_doc_section_test_' . uniqid();
        if (!is_dir($this->tempUploadDir)) {
            mkdir($this->tempUploadDir, 0755, true);
        }

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

            CREATE TABLE `class_enrollments` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `student_id` INTEGER NOT NULL,
                `class_id` INTEGER NOT NULL,
                `session_id` INTEGER NOT NULL,
                `status` VARCHAR(20) NOT NULL DEFAULT 'active',
                `enrolled_at` DATETIME NOT NULL,
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
                UNIQUE(`student_id`, `class_subject_id`)
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
                `owner_type` VARCHAR(50) NULL,
                `owner_id` INTEGER NULL,
                `deleted_at` DATETIME NULL,
                `created_at` DATETIME NOT NULL
            );

            CREATE TABLE `content_items` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `class_subject_id` INTEGER NOT NULL,
                `teacher_id` INTEGER NOT NULL,
                `topic` VARCHAR(100) NULL,
                `title` VARCHAR(200) NOT NULL,
                `description` TEXT NULL,
                `type` VARCHAR(20) NOT NULL DEFAULT 'note',
                `file_id` INTEGER NULL,
                `external_url` VARCHAR(500) NULL,
                `published_at` DATETIME NULL,
                `created_at` DATETIME NOT NULL,
                `updated_at` DATETIME NOT NULL
            );

            CREATE TABLE `learning_activity_progress` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `student_id` INTEGER NOT NULL,
                `activity_type` VARCHAR(50) NOT NULL,
                `activity_id` INTEGER NOT NULL,
                `last_page` INTEGER NOT NULL DEFAULT 1,
                `total_pages` INTEGER NOT NULL DEFAULT 1,
                `pages_read_json` TEXT NOT NULL,
                `progress_percent` REAL NOT NULL DEFAULT 0.00,
                `is_completed` INTEGER NOT NULL DEFAULT 0,
                `completed_at` DATETIME NULL,
                `last_accessed_at` DATETIME NOT NULL,
                `created_at` DATETIME NOT NULL,
                `updated_at` DATETIME NOT NULL,
                UNIQUE(`student_id`, `activity_type`, `activity_id`)
            );

            CREATE TABLE `document_sections` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `content_item_id` INTEGER NOT NULL,
                `title` VARCHAR(200) NOT NULL,
                `start_page` INTEGER NOT NULL,
                `end_page` INTEGER NOT NULL,
                `sequence_order` INTEGER NOT NULL DEFAULT 1,
                `created_at` DATETIME NOT NULL,
                `updated_at` DATETIME NOT NULL
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
        ");

        $this->userRepo = new UserRepository($this->pdo);
        $this->studentRepo = new StudentRepository($this->pdo);
        $this->parentRepo = new ParentRepository($this->pdo);
        $this->teacherRepo = new TeacherRepository($this->pdo);
        $this->academicRepo = new AcademicRepository($this->pdo);
        $this->enrollmentRepo = new EnrollmentRepository($this->pdo);
        $this->fileRepo = new FileRepository($this->pdo);
        $this->contentRepo = new ContentRepository($this->pdo);
        $this->activityProgressRepo = new ActivityProgressRepository($this->pdo);
        $this->documentSectionRepo = new DocumentSectionRepository($this->pdo);

        $this->enrollmentService = new EnrollmentService(
            $this->enrollmentRepo,
            $this->studentRepo,
            $this->academicRepo
        );

        $this->fileStorageService = new FileStorageService(
            fileRepository: $this->fileRepo,
            contentRepository: $this->contentRepo,
            uploadDir: $this->tempUploadDir,
            maxSizeBytes: 26214400,
            academicRepository: $this->academicRepo,
            teacherRepository: $this->teacherRepo,
            studentRepository: $this->studentRepo,
            enrollmentRepository: $this->enrollmentRepo,
            parentRepository: $this->parentRepo
        );

        $this->contentService = new ContentService(
            $this->contentRepo,
            $this->fileRepo,
            $this->fileStorageService,
            $this->academicRepo,
            $this->teacherRepo,
            $this->studentRepo,
            $this->enrollmentRepo,
            $this->parentRepo,
            $this->pdo,
            $this->activityProgressRepo,
            $this->documentSectionRepo
        );

        $this->seedData();
    }

    private function seedData(): void
    {
        // Teacher 1
        $this->teacherUser = $this->userRepo->create([
            'uuid' => 'teacher-u-1',
            'name' => 'Dr. Biology Teacher',
            'email' => 'bio@school.edu',
            'password_hash' => 'hash',
            'status' => 'active',
        ], ['teacher']);
        $teacher = $this->teacherRepo->createTeacher($this->teacherUser->id, 'STF-BIO-01');
        $this->teacherId = $teacher->id;

        // Teacher 2 (unauthorized for Teacher 1 content)
        $this->otherTeacherUser = $this->userRepo->create([
            'uuid' => 'teacher-u-2',
            'name' => 'Mr. Math Teacher',
            'email' => 'math@school.edu',
            'password_hash' => 'hash',
            'status' => 'active',
        ], ['teacher']);
        $otherTeacher = $this->teacherRepo->createTeacher($this->otherTeacherUser->id, 'STF-MTH-01');
        $this->otherTeacherId = $otherTeacher->id;

        // Student 1 (Enrolled)
        $this->studentUser = $this->userRepo->create([
            'uuid' => 'student-u-1',
            'name' => 'Student Alice',
            'email' => 'alice@school.edu',
            'password_hash' => 'hash',
            'status' => 'active',
        ], ['student']);
        $student = $this->studentRepo->create($this->studentUser->id, 'STD-2026-BIO-01', null, 'female');
        $this->studentId = $student->id;

        // Student 2 (Unenrolled)
        $this->otherStudentUser = $this->userRepo->create([
            'uuid' => 'other-std-u',
            'name' => 'Student Bob',
            'email' => 'bob@school.edu',
            'password_hash' => 'hash',
            'status' => 'active',
        ], ['student']);
        $otherStudent = $this->studentRepo->create($this->otherStudentUser->id, 'STD-2026-UNEN-01', null, 'male');
        $this->otherStudentId = $otherStudent->id;

        // Academic Structure
        $level = $this->academicRepo->createLevel(['name' => 'Senior Secondary', 'stage' => 'secondary', 'rank_order' => 1]);
        $class = $this->academicRepo->createClass(['academic_level_id' => $level->id, 'name' => 'SS 1 Biology', 'section_arm' => 'Science']);
        $session = $this->academicRepo->createSession(['name' => '2026/2027', 'start_date' => '2026-09-01', 'end_date' => '2027-07-31', 'status' => 'active', 'is_current' => 1]);
        $this->sessionId = $session->id;
        $subject = $this->academicRepo->createSubject(['code' => 'BIO101', 'name' => 'Biology', 'category' => 'Science']);

        $classSubject = $this->academicRepo->createClassSubject([
            'session_id' => $session->id,
            'class_id' => $class->id,
            'subject_id' => $subject->id,
            'teacher_id' => $this->teacherId,
            'status' => 'active',
        ]);
        $this->classSubjectId = $classSubject->id;

        // Enroll Student 1
        $this->enrollmentService->enrollStudentInClass($this->studentId, $class->id, $this->sessionId);

        // Create PDF Content Item
        $teacherContext = UserContext::fromUser($this->teacherUser);
        $dummyPdfPath = tempnam(sys_get_temp_dir(), 'testpdf');
        $pdfContent = str_repeat('%PDF-1.4 Biology Lecture Notes. ', 10);
        file_put_contents($dummyPdfPath, $pdfContent);

        $fakePdfUpload = [
            'name' => 'Cell_Biology.pdf',
            'type' => 'application/pdf',
            'tmp_name' => $dummyPdfPath,
            'error' => UPLOAD_ERR_OK,
            'size' => strlen($pdfContent),
        ];

        $createResult = $this->contentService->createContent([
            'class_subject_id' => $this->classSubjectId,
            'title' => 'Cell Biology 101',
            'topic' => 'Biology',
            'type' => 'document',
            'publish_now' => 1,
        ], $fakePdfUpload, $teacherContext);
        @unlink($dummyPdfPath);

        $this->assertTrue($createResult->isSuccess(), 'Failed to create PDF content item');
        $this->pdfContentId = (int)$createResult->getData()['content_item']->id;

        // Create Non-PDF Note Item
        $nonPdfResult = $this->contentService->createContent([
            'class_subject_id' => $this->classSubjectId,
            'title' => 'Biology Overview Note',
            'topic' => 'Biology',
            'type' => 'note',
            'description' => 'Text note without PDF attachment',
            'publish_now' => 1,
        ], null, $teacherContext);

        $this->assertTrue($nonPdfResult->isSuccess(), 'Failed to create non-PDF content item');
        $this->nonPdfContentId = (int)$nonPdfResult->getData()['content_item']->id;
    }

    protected function tearDown(): void
    {
        if (is_dir($this->tempUploadDir)) {
            $files = glob($this->tempUploadDir . '/*');
            if ($files) {
                foreach ($files as $file) {
                    if (is_file($file)) {
                        @unlink($file);
                    }
                }
            }
            @rmdir($this->tempUploadDir);
        }
        parent::tearDown();
    }

    private function createActor(User $user, string $role): UserContext
    {
        return new UserContext(
            id: $user->id,
            uuid: $user->uuid,
            name: $user->name,
            email: $user->email,
            roles: [$role],
            mustChangePassword: false,
            user: $user
        );
    }

    // =========================================================================
    // SECTION CRUD & VALIDATION TESTS
    // =========================================================================

    public function test1TeacherCanCreateValidSection(): void
    {
        $teacherActor = $this->createActor($this->teacherUser, 'teacher');

        $result = $this->contentService->createSection(
            contentItemId: $this->pdfContentId,
            data: [
                'title' => 'Introduction to Cells',
                'start_page' => 1,
                'end_page' => 5,
                'sequence_order' => 1,
            ],
            actor: $teacherActor
        );

        $this->assertTrue($result->isSuccess());
        $section = $result->data['section'];
        $this->assertSame('Introduction to Cells', $section->title);
        $this->assertSame(1, $section->startPage);
        $this->assertSame(5, $section->endPage);
        $this->assertSame(1, $section->sequenceOrder);
        $this->assertSame(5, $section->getTotalPages());
    }

    public function test2TeacherCanUpdateSection(): void
    {
        $teacherActor = $this->createActor($this->teacherUser, 'teacher');

        $createRes = $this->contentService->createSection(
            $this->pdfContentId,
            ['title' => 'Section 1', 'start_page' => 1, 'end_page' => 4, 'sequence_order' => 1],
            $teacherActor
        );
        $sectionId = (int)$createRes->data['section']->id;

        $updateRes = $this->contentService->updateSection(
            $sectionId,
            ['title' => 'Updated Introduction', 'start_page' => 1, 'end_page' => 5, 'sequence_order' => 2],
            $teacherActor
        );

        $this->assertTrue($updateRes->isSuccess());
        $updated = $updateRes->data['section'];
        $this->assertSame('Updated Introduction', $updated->title);
        $this->assertSame(5, $updated->endPage);
        $this->assertSame(2, $updated->sequenceOrder);
    }

    public function test3TeacherCanDeleteSection(): void
    {
        $teacherActor = $this->createActor($this->teacherUser, 'teacher');

        $createRes = $this->contentService->createSection(
            $this->pdfContentId,
            ['title' => 'Temp Section', 'start_page' => 1, 'end_page' => 3, 'sequence_order' => 1],
            $teacherActor
        );
        $sectionId = (int)$createRes->data['section']->id;

        $deleteRes = $this->contentService->deleteSection($sectionId, $teacherActor);
        $this->assertTrue($deleteRes->isSuccess());

        $sections = $this->documentSectionRepo->getByContentItemId($this->pdfContentId);
        $this->assertEmpty($sections);
    }

    public function test4UnauthorizedTeacherCannotModifyAnotherTeachersContent(): void
    {
        $otherTeacherActor = $this->createActor($this->otherTeacherUser, 'teacher');

        $this->expectException(AuthorizationException::class);
        $this->contentService->createSection(
            contentItemId: $this->pdfContentId,
            data: ['title' => 'Sneaky Section', 'start_page' => 1, 'end_page' => 5],
            actor: $otherTeacherActor
        );
    }

    public function test5InvalidStartPageRejected(): void
    {
        $teacherActor = $this->createActor($this->teacherUser, 'teacher');

        $this->expectException(ValidationException::class);
        $this->contentService->createSection(
            $this->pdfContentId,
            ['title' => 'Zero Page', 'start_page' => 0, 'end_page' => 5],
            $teacherActor
        );
    }

    public function test6InvalidEndPageRejected(): void
    {
        $teacherActor = $this->createActor($this->teacherUser, 'teacher');

        $this->expectException(ValidationException::class);
        $this->contentService->createSection(
            $this->pdfContentId,
            ['title' => 'Negative End Page', 'start_page' => 1, 'end_page' => -2],
            $teacherActor
        );
    }

    public function test7StartPageGreaterThanEndPageRejected(): void
    {
        $teacherActor = $this->createActor($this->teacherUser, 'teacher');

        $this->expectException(ValidationException::class);
        $this->contentService->createSection(
            $this->pdfContentId,
            ['title' => 'Inverted Range', 'start_page' => 10, 'end_page' => 5],
            $teacherActor
        );
    }

    public function test8OverlappingSectionsRejected(): void
    {
        $teacherActor = $this->createActor($this->teacherUser, 'teacher');

        // Create Section 1: pages 1–5
        $this->contentService->createSection(
            $this->pdfContentId,
            ['title' => 'Section 1', 'start_page' => 1, 'end_page' => 5, 'sequence_order' => 1],
            $teacherActor
        );

        // Attempt overlapping Section 2: pages 5–10 (page 5 overlap) -> MUST FAIL
        $this->expectException(ValidationException::class);
        $this->contentService->createSection(
            $this->pdfContentId,
            ['title' => 'Section 2', 'start_page' => 5, 'end_page' => 10, 'sequence_order' => 2],
            $teacherActor
        );
    }

    public function test8bOverlappingSubrangeRejected(): void
    {
        $teacherActor = $this->createActor($this->teacherUser, 'teacher');

        // Create Section 1: pages 1–7
        $this->contentService->createSection(
            $this->pdfContentId,
            ['title' => 'Section 1', 'start_page' => 1, 'end_page' => 7, 'sequence_order' => 1],
            $teacherActor
        );

        // Attempt overlapping Section 2: pages 5–10 -> MUST FAIL
        $this->expectException(ValidationException::class);
        $this->contentService->createSection(
            $this->pdfContentId,
            ['title' => 'Section 2', 'start_page' => 5, 'end_page' => 10, 'sequence_order' => 2],
            $teacherActor
        );
    }

    public function test9GapsBetweenSectionsAllowed(): void
    {
        $teacherActor = $this->createActor($this->teacherUser, 'teacher');

        // Section 1: pages 1–5
        $res1 = $this->contentService->createSection(
            $this->pdfContentId,
            ['title' => 'Section 1', 'start_page' => 1, 'end_page' => 5, 'sequence_order' => 1],
            $teacherActor
        );
        $this->assertTrue($res1->isSuccess());

        // Section 2: pages 8–12 (gap on pages 6 and 7) -> VALID
        $res2 = $this->contentService->createSection(
            $this->pdfContentId,
            ['title' => 'Section 2', 'start_page' => 8, 'end_page' => 12, 'sequence_order' => 2],
            $teacherActor
        );
        $this->assertTrue($res2->isSuccess());

        $sections = $this->documentSectionRepo->getByContentItemId($this->pdfContentId);
        $this->assertCount(2, $sections);
    }

    public function test10NonPdfContentCannotHavePdfSections(): void
    {
        $teacherActor = $this->createActor($this->teacherUser, 'teacher');

        $this->expectException(DomainRuleException::class);
        $this->contentService->createSection(
            $this->nonPdfContentId, // type = 'note'
            ['title' => 'Invalid Section', 'start_page' => 1, 'end_page' => 2],
            $teacherActor
        );
    }

    public function test11SectionsSortedBySequenceOrder(): void
    {
        $teacherActor = $this->createActor($this->teacherUser, 'teacher');

        // Create Section B first with sequence order 2
        $this->contentService->createSection(
            $this->pdfContentId,
            ['title' => 'Section B', 'start_page' => 6, 'end_page' => 10, 'sequence_order' => 2],
            $teacherActor
        );

        // Create Section A second with sequence order 1
        $this->contentService->createSection(
            $this->pdfContentId,
            ['title' => 'Section A', 'start_page' => 1, 'end_page' => 5, 'sequence_order' => 1],
            $teacherActor
        );

        $sections = $this->contentService->getSectionsForContent($this->pdfContentId);
        $this->assertCount(2, $sections);
        $this->assertSame('Section A', $sections[0]->title);
        $this->assertSame('Section B', $sections[1]->title);
    }

    // =========================================================================
    // SECTION PROGRESS DERIVATION & SYNCHRONIZATION TESTS
    // =========================================================================

    public function test12ZeroPagesReadHasZeroSectionProgress(): void
    {
        $teacherActor = $this->createActor($this->teacherUser, 'teacher');
        $this->contentService->createSection($this->pdfContentId, ['title' => 'Sec 1', 'start_page' => 1, 'end_page' => 5], $teacherActor);

        $sectionsWithProgress = $this->contentService->getDocumentSectionsWithProgress($this->pdfContentId, $this->studentId);
        $this->assertCount(1, $sectionsWithProgress);
        $this->assertSame(0.0, $sectionsWithProgress[0]->progressPercent);
        $this->assertFalse($sectionsWithProgress[0]->isCompleted);
    }

    public function test13PartialSectionCalculatesCorrectPercentage(): void
    {
        $teacherActor = $this->createActor($this->teacherUser, 'teacher');
        $studentActor = $this->createActor($this->studentUser, 'student');

        // Section 1: pages 1–4 (4 pages)
        $this->contentService->createSection($this->pdfContentId, ['title' => 'Sec 1', 'start_page' => 1, 'end_page' => 4], $teacherActor);

        // Student reads pages 1 and 2 of 10-page document
        $this->contentService->recordDocumentReadingProgress(
            contentItemId: $this->pdfContentId,
            lastPage: 2,
            totalPages: 10,
            newPages: [1, 2],
            actor: $studentActor
        );

        $sections = $this->contentService->getDocumentSectionsWithProgress($this->pdfContentId, $this->studentId);
        // 2 / 4 = 50%
        $this->assertSame(50.0, $sections[0]->progressPercent);
        $this->assertFalse($sections[0]->isCompleted);
    }

    public function test14DuplicateDocumentPagesDoNotInflateSectionProgress(): void
    {
        $teacherActor = $this->createActor($this->teacherUser, 'teacher');
        $studentActor = $this->createActor($this->studentUser, 'student');

        $this->contentService->createSection($this->pdfContentId, ['title' => 'Sec 1', 'start_page' => 1, 'end_page' => 5], $teacherActor);

        // Student sends page 1 multiple times
        $this->contentService->recordDocumentReadingProgress($this->pdfContentId, 1, 10, [1, 1, 1, 1], $studentActor);

        $sections = $this->contentService->getDocumentSectionsWithProgress($this->pdfContentId, $this->studentId);
        // 1 / 5 = 20%
        $this->assertSame(20.0, $sections[0]->progressPercent);
        $this->assertFalse($sections[0]->isCompleted);
    }

    public function test15DocumentPagesOutsideSectionDoNotCountTowardsSection(): void
    {
        $teacherActor = $this->createActor($this->teacherUser, 'teacher');
        $studentActor = $this->createActor($this->studentUser, 'student');

        // Section: pages 6–10
        $this->contentService->createSection($this->pdfContentId, ['title' => 'Sec 2', 'start_page' => 6, 'end_page' => 10], $teacherActor);

        // Student reads pages 1, 2, 3, 4, 5 (outside section)
        $this->contentService->recordDocumentReadingProgress($this->pdfContentId, 5, 10, [1, 2, 3, 4, 5], $studentActor);

        $sections = $this->contentService->getDocumentSectionsWithProgress($this->pdfContentId, $this->studentId);
        // 0 / 5 = 0%
        $this->assertSame(0.0, $sections[0]->progressPercent);
        $this->assertFalse($sections[0]->isCompleted);
    }

    public function test16SectionCompletionThresholdStrictlyNinetyPercent(): void
    {
        $teacherActor = $this->createActor($this->teacherUser, 'teacher');
        $studentActor = $this->createActor($this->studentUser, 'student');

        // Section: 10 pages (pages 1–10)
        $this->contentService->createSection($this->pdfContentId, ['title' => '10-page Sec', 'start_page' => 1, 'end_page' => 10], $teacherActor);

        // Read 8 pages = 80% -> incomplete
        $this->contentService->recordDocumentReadingProgress($this->pdfContentId, 8, 20, range(1, 8), $studentActor);
        $sections = $this->contentService->getDocumentSectionsWithProgress($this->pdfContentId, $this->studentId);
        $this->assertSame(80.0, $sections[0]->progressPercent);
        $this->assertFalse($sections[0]->isCompleted);

        // Read 9 pages = 90% -> completed!
        $this->contentService->recordDocumentReadingProgress($this->pdfContentId, 9, 20, [9], $studentActor);
        $sections = $this->contentService->getDocumentSectionsWithProgress($this->pdfContentId, $this->studentId);
        $this->assertSame(90.0, $sections[0]->progressPercent);
        $this->assertTrue($sections[0]->isCompleted);
        $this->assertNotNull($sections[0]->completedAt);
    }

    public function test17SectionCompletedAtIsImmutableAndCannotRegress(): void
    {
        $teacherActor = $this->createActor($this->teacherUser, 'teacher');
        $studentActor = $this->createActor($this->studentUser, 'student');

        $this->contentService->createSection($this->pdfContentId, ['title' => 'Sec 1', 'start_page' => 1, 'end_page' => 10], $teacherActor);

        // Complete the section
        $this->contentService->recordDocumentReadingProgress($this->pdfContentId, 9, 20, range(1, 9), $studentActor);
        $sections = $this->contentService->getDocumentSectionsWithProgress($this->pdfContentId, $this->studentId);
        $firstCompletedAt = $sections[0]->completedAt;
        $this->assertNotNull($firstCompletedAt);

        // Stale client sends only page 1
        $this->contentService->recordDocumentReadingProgress($this->pdfContentId, 1, 20, [1], $studentActor);
        $sections = $this->contentService->getDocumentSectionsWithProgress($this->pdfContentId, $this->studentId);
        $this->assertTrue($sections[0]->isCompleted);
        $this->assertSame($firstCompletedAt, $sections[0]->completedAt);
    }

    // =========================================================================
    // MANDATORY PHASE 3 CROSS-CHECK TEST
    // =========================================================================

    /**
     * Required test specification:
     * Document = 20 pages
     * Sections:
     *   - Section 1 = pages 1–5
     *   - Section 2 = pages 6–12
     *   - Section 3 = pages 13–20
     * Document pages read:
     *   [1, 2, 3, 4, 5, 20]
     *
     * Expected results:
     *   - Document: 6 / 20 = 30%
     *   - Section 1: 5 / 5 = 100% (Completed)
     *   - Section 2: 0 / 7 = 0%   (Incomplete)
     *   - Section 3: 1 / 8 = 12.5% (Incomplete)
     */
    public function testMandatoryPhase3CrossCheckMathematicalVerification(): void
    {
        $teacherActor = $this->createActor($this->teacherUser, 'teacher');
        $studentActor = $this->createActor($this->studentUser, 'student');

        // Create Section 1 (pages 1–5)
        $this->contentService->createSection($this->pdfContentId, [
            'title' => 'Section 1',
            'start_page' => 1,
            'end_page' => 5,
            'sequence_order' => 1
        ], $teacherActor);

        // Create Section 2 (pages 6–12)
        $this->contentService->createSection($this->pdfContentId, [
            'title' => 'Section 2',
            'start_page' => 6,
            'end_page' => 12,
            'sequence_order' => 2
        ], $teacherActor);

        // Create Section 3 (pages 13–20)
        $this->contentService->createSection($this->pdfContentId, [
            'title' => 'Section 3',
            'start_page' => 13,
            'end_page' => 20,
            'sequence_order' => 3
        ], $teacherActor);

        // Student reads pages [1, 2, 3, 4, 5, 20] in a 20-page PDF
        $progressRes = $this->contentService->recordDocumentReadingProgress(
            contentItemId: $this->pdfContentId,
            lastPage: 20,
            totalPages: 20,
            newPages: [1, 2, 3, 4, 5, 20],
            actor: $studentActor
        );

        $this->assertTrue($progressRes->isSuccess());

        // 1. Verify Document-level progress: 6 / 20 * 100 = 30.0%
        $this->assertSame(30.0, $progressRes->data['progress_percent']);
        $this->assertSame(6, $progressRes->data['unique_pages_count']);
        $this->assertSame(20, $progressRes->data['total_pages']);
        $this->assertFalse($progressRes->data['is_completed']);

        // 2. Verify Section-level progress
        $sections = $this->contentService->getDocumentSectionsWithProgress($this->pdfContentId, $this->studentId);
        $this->assertCount(3, $sections);

        // Section 1: 5 / 5 = 100%
        $sec1 = $sections[0];
        $this->assertSame('Section 1', $sec1->title);
        $this->assertSame(100.0, $sec1->progressPercent);
        $this->assertTrue($sec1->isCompleted);
        $this->assertNotNull($sec1->completedAt);

        // Section 2: 0 / 7 = 0%
        $sec2 = $sections[1];
        $this->assertSame('Section 2', $sec2->title);
        $this->assertSame(0.0, $sec2->progressPercent);
        $this->assertFalse($sec2->isCompleted);

        // Section 3: 1 / 8 = 12.5%
        $sec3 = $sections[2];
        $this->assertSame('Section 3', $sec3->title);
        $this->assertSame(12.5, $sec3->progressPercent);
        $this->assertFalse($sec3->isCompleted);
    }

    // =========================================================================
    // SECURITY & ISOLATION TESTS
    // =========================================================================

    public function testSecurityUnenrolledStudentCannotSaveProgress(): void
    {
        $otherStudentActor = $this->createActor($this->otherStudentUser, 'student');

        $this->expectException(\App\Core\Exceptions\ResourceNotFoundException::class);
        $this->contentService->recordDocumentReadingProgress(
            contentItemId: $this->pdfContentId,
            lastPage: 1,
            totalPages: 10,
            newPages: [1],
            actor: $otherStudentActor
        );
    }

    public function testCrossStudentProgressIsolation(): void
    {
        $teacherActor = $this->createActor($this->teacherUser, 'teacher');
        $studentActor = $this->createActor($this->studentUser, 'student');

        $this->contentService->createSection($this->pdfContentId, ['title' => 'Sec 1', 'start_page' => 1, 'end_page' => 5], $teacherActor);

        // Student 1 reads pages 1 to 5
        $this->contentService->recordDocumentReadingProgress($this->pdfContentId, 5, 10, [1, 2, 3, 4, 5], $studentActor);

        // Student 1 has 100% on Section 1
        $s1Sections = $this->contentService->getDocumentSectionsWithProgress($this->pdfContentId, $this->studentId);
        $this->assertSame(100.0, $s1Sections[0]->progressPercent);
        $this->assertTrue($s1Sections[0]->isCompleted);

        // Student 2 has 0% on Section 1
        $s2Sections = $this->contentService->getDocumentSectionsWithProgress($this->pdfContentId, $this->otherStudentId);
        $this->assertSame(0.0, $s2Sections[0]->progressPercent);
        $this->assertFalse($s2Sections[0]->isCompleted);
    }
}
