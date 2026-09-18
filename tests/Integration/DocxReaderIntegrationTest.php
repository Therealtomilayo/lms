<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Controllers\FileController;
use App\Controllers\Student\ContentController;
use App\Core\AuthenticatorInterface;
use App\Core\Request;
use App\Core\UserContext;
use App\Models\ActivityPrerequisite;
use App\Models\ActivityProgress;
use App\Models\ContentItem;
use App\Models\User;
use App\Repositories\AcademicRepository;
use App\Repositories\ActivityPrerequisiteRepository;
use App\Repositories\ActivityProgressRepository;
use App\Repositories\ContentRepository;
use App\Repositories\DocumentSectionRepository;
use App\Repositories\EnrollmentRepository;
use App\Repositories\FileRepository;
use App\Repositories\ParentRepository;
use App\Repositories\QuizRepository;
use App\Repositories\StudentRepository;
use App\Repositories\TeacherRepository;
use App\Repositories\UserRepository;
use App\Services\ContentService;
use App\Services\EnrollmentService;
use App\Services\FileStorageService;
use App\Services\PrerequisiteService;
use PDO;
use PHPUnit\Framework\TestCase;

final class DocxReaderIntegrationTest extends TestCase
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
    private ActivityPrerequisiteRepository $prerequisiteRepo;
    private QuizRepository $quizRepo;

    private EnrollmentService $enrollmentService;
    private FileStorageService $fileStorageService;
    private ContentService $contentService;
    private PrerequisiteService $prerequisiteService;

    private string $tempUploadDir;

    private User $teacherUser;
    private User $studentUser;
    private User $otherStudentUser;

    private int $teacherId;
    private int $studentId;
    private int $otherStudentId;
    private int $sessionId;
    private int $classSubjectId;
    private int $docxContentId;
    private int $docxFileId;

    protected function setUp(): void
    {
        $this->tempUploadDir = sys_get_temp_dir() . '/lms_docx_test_' . uniqid();
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
                `file_id` INTEGER NULL,
                `title` VARCHAR(200) NOT NULL,
                `type` VARCHAR(50) NOT NULL DEFAULT 'note',
                `description` TEXT NULL,
                `topic` VARCHAR(100) NULL,
                `external_url` VARCHAR(255) NULL,
                `status` VARCHAR(20) NOT NULL DEFAULT 'draft',
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
                `requirement_type` VARCHAR(50) NOT NULL DEFAULT 'completion',
                `created_at` DATETIME NOT NULL,
                `updated_at` DATETIME NOT NULL,
                UNIQUE(`activity_type`, `activity_id`, `prerequisite_activity_type`, `prerequisite_activity_id`)
            );

            CREATE TABLE `quizzes` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `class_subject_id` INTEGER NOT NULL,
                `teacher_id` INTEGER NOT NULL,
                `title` VARCHAR(200) NOT NULL,
                `description` TEXT NULL,
                `time_limit_minutes` INTEGER NOT NULL DEFAULT 30,
                `passing_score` REAL NOT NULL DEFAULT 50.00,
                `max_attempts` INTEGER NOT NULL DEFAULT 1,
                `status` VARCHAR(20) NOT NULL DEFAULT 'published',
                `created_at` DATETIME NOT NULL,
                `updated_at` DATETIME NOT NULL
            );

            CREATE TABLE `assignments` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `class_subject_id` INTEGER NOT NULL,
                `teacher_id` INTEGER NOT NULL,
                `title` VARCHAR(200) NOT NULL,
                `description` TEXT NULL,
                `max_score` REAL NOT NULL DEFAULT 100.00,
                `due_date` DATETIME NOT NULL,
                `status` VARCHAR(20) NOT NULL DEFAULT 'published',
                `created_at` DATETIME NOT NULL,
                `updated_at` DATETIME NOT NULL
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
        $this->prerequisiteRepo = new ActivityPrerequisiteRepository($this->pdo);
        $this->quizRepo = new QuizRepository($this->pdo);

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
            $this->documentSectionRepo,
            $this->prerequisiteRepo
        );

        $this->prerequisiteService = new PrerequisiteService(
            prereqRepo: $this->prerequisiteRepo,
            progressRepo: $this->activityProgressRepo,
            contentRepo: $this->contentRepo,
            sectionRepo: $this->documentSectionRepo,
            quizRepo: $this->quizRepo
        );

        $this->seedInitialData();
    }

    protected function tearDown(): void
    {
        $this->cleanupDir($this->tempUploadDir);
    }

    private function cleanupDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $files = array_diff(scandir($dir) ?: [], ['.', '..']);
        foreach ($files as $file) {
            $path = "$dir/$file";
            is_dir($path) ? $this->cleanupDir($path) : @unlink($path);
        }
        @rmdir($dir);
    }

    private function seedInitialData(): void
    {
        $now = date('Y-m-d H:i:s');

        // Teacher User
        $this->teacherUser = $this->userRepo->create([
            'uuid' => 'u-teacher-docx',
            'name' => 'Dr. Teacher Docx',
            'email' => 'teacher.docx@school.edu',
            'password_hash' => password_hash('Pass123!', PASSWORD_BCRYPT),
            'status' => 'active',
        ], ['teacher']);
        $teacher = $this->teacherRepo->createTeacher($this->teacherUser->id, 'STF-DOCX-01');
        $this->teacherId = $teacher->id;

        // Student User (Enrolled)
        $this->studentUser = $this->userRepo->create([
            'uuid' => 'u-student-docx',
            'name' => 'Alice Docx',
            'email' => 'alice.docx@school.edu',
            'password_hash' => password_hash('Pass123!', PASSWORD_BCRYPT),
            'status' => 'active',
        ], ['student']);
        $student = $this->studentRepo->create($this->studentUser->id, 'STD-DOCX-01', null, 'female');
        $this->studentId = $student->id;

        // Other Student User (Unenrolled)
        $this->otherStudentUser = $this->userRepo->create([
            'uuid' => 'u-other-docx',
            'name' => 'Bob Other',
            'email' => 'bob.other@school.edu',
            'password_hash' => password_hash('Pass123!', PASSWORD_BCRYPT),
            'status' => 'active',
        ], ['student']);
        $otherStudent = $this->studentRepo->create($this->otherStudentUser->id, 'STD-DOCX-02', null, 'male');
        $this->otherStudentId = $otherStudent->id;

        // Academic Structure
        $level = $this->academicRepo->createLevel(['name' => 'Senior Secondary', 'stage' => 'secondary', 'rank_order' => 1]);
        $class = $this->academicRepo->createClass(['academic_level_id' => $level->id, 'name' => 'SS 1 Literature', 'section_arm' => 'Arts']);
        $session = $this->academicRepo->createSession(['name' => '2026/2027', 'start_date' => '2026-09-01', 'end_date' => '2027-07-31', 'status' => 'active', 'is_current' => 1]);
        $this->sessionId = $session->id;
        $subject = $this->academicRepo->createSubject(['code' => 'LIT101', 'name' => 'Literature in English', 'category' => 'Arts']);

        $classSubject = $this->academicRepo->createClassSubject([
            'session_id' => $session->id,
            'class_id' => $class->id,
            'subject_id' => $subject->id,
            'teacher_id' => $this->teacherId,
            'status' => 'active',
        ]);
        $this->classSubjectId = $classSubject->id;

        // Enroll Student in class & subject
        $this->enrollmentService->enrollStudentInClass($this->studentId, $class->id, $this->sessionId);

        // Upload and create real DOCX Content Item
        $teacherContext = UserContext::fromUser($this->teacherUser);
        $dummyDocxPath = tempnam(sys_get_temp_dir(), 'testdocx');
        $docxPayload = "PK\x03\x04Word Document Dummy Body Stream Content";
        file_put_contents($dummyDocxPath, $docxPayload);

        $fakeDocxUpload = [
            'name' => 'Novel_Study_Guide.docx',
            'type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'tmp_name' => $dummyDocxPath,
            'error' => UPLOAD_ERR_OK,
            'size' => strlen($docxPayload),
        ];

        $createResult = $this->contentService->createContent([
            'class_subject_id' => $this->classSubjectId,
            'title' => 'Novel Study Guide',
            'topic' => 'Literature Analysis',
            'type' => 'document',
            'publish_now' => 1,
        ], $fakeDocxUpload, $teacherContext);
        @unlink($dummyDocxPath);

        $this->assertTrue($createResult->isSuccess(), 'Failed to create DOCX content item');
        $item = $createResult->getData()['content_item'];
        $this->docxContentId = (int)$item->id;
        $this->docxFileId = (int)$item->fileId;
    }

    private function createMockAuthenticator(User|UserContext $user): AuthenticatorInterface
    {
        $context = $user instanceof UserContext ? $user : UserContext::fromUser($user);
        return new class($context) implements AuthenticatorInterface {
            public function __construct(private UserContext $context) {}
            public function authenticate(Request $request): ?UserContext { return $this->context; }
            public function check(Request $request): bool { return true; }
            public function user(Request $request): ?UserContext { return $this->context; }
            public function getUserContext(?Request $request = null): ?UserContext { return $this->context; }
        };
    }

    public function testDocxContentItemIdentifiedAndHasCorrectAttributes(): void
    {
        $actor = UserContext::fromUser($this->studentUser);
        $result = $this->contentService->getContentItem($this->docxContentId, $actor);

        $this->assertTrue($result->isSuccess());
        /** @var ContentItem $item */
        $item = $result->data['content_item'];
        $this->assertSame('Novel Study Guide', $item->title);
        $this->assertSame(ContentItem::TYPE_DOCUMENT, $item->type);
        $this->assertNotNull($item->file);
        $this->assertStringEndsWith('.docx', strtolower($item->file->originalName));
    }

    public function testFileStreamingOverridesOrPreservesOpenXmlMimeType(): void
    {
        $auth = $this->createMockAuthenticator($this->studentUser);
        $controller = new FileController(authenticator: $auth, fileStorageService: $this->fileStorageService);
        $request = new Request(
            queryParams: [],
            postParams: [],
            serverParams: ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => "/files/{$this->docxFileId}/stream"]
        );

        $response = $controller->stream($request, $this->docxFileId);

        $this->assertSame(200, $response->getStatusCode());
        $headers = $response->getHeaders();
        $this->assertArrayHasKey('Content-Type', $headers);
        $this->assertSame(
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            $headers['Content-Type']
        );
        $this->assertSame('inline; filename="Novel_Study_Guide.docx"', $headers['Content-Disposition']);
    }

    public function testEnrolledStudentCanReadDocx(): void
    {
        $auth = $this->createMockAuthenticator($this->studentUser);

        $controller = new ContentController(
            authenticator: $auth,
            contentService: $this->contentService,
            academicRepo: $this->academicRepo,
            studentRepo: $this->studentRepo,
            enrollmentRepo: $this->enrollmentRepo,
            prerequisiteService: $this->prerequisiteService
        );

        $request = new Request(
            queryParams: [],
            postParams: [],
            serverParams: ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => "/student/content/{$this->docxContentId}/read"]
        );
        $response = $controller->read($request, $this->docxContentId);

        $this->assertSame(200, $response->getStatusCode());
        $html = $response->getContent();
        // Verifies the DOCX reader view was rendered
        $this->assertStringContainsString('docx-reader-root', $html);
        $this->assertStringContainsString('Word Document Reader', $html);
        $this->assertStringContainsString('docx-preview.min.js', $html);
        $this->assertStringContainsString('jszip.min.js', $html);
    }

    public function testUnenrolledStudentIsDeniedFromReadingDocx(): void
    {
        $auth = $this->createMockAuthenticator($this->otherStudentUser);

        $controller = new ContentController(
            authenticator: $auth,
            contentService: $this->contentService,
            academicRepo: $this->academicRepo,
            studentRepo: $this->studentRepo,
            enrollmentRepo: $this->enrollmentRepo,
            prerequisiteService: $this->prerequisiteService
        );

        $request = new Request(
            queryParams: [],
            postParams: [],
            serverParams: ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => "/student/content/{$this->docxContentId}/read"]
        );
        $response = $controller->read($request, $this->docxContentId);

        $this->assertSame(404, $response->getStatusCode());
    }

    public function testDocxReadingProgressSavesBlocksAndCalculatesPercent(): void
    {
        $auth = $this->createMockAuthenticator($this->studentUser);

        $controller = new ContentController(
            authenticator: $auth,
            contentService: $this->contentService,
            academicRepo: $this->academicRepo,
            studentRepo: $this->studentRepo,
            enrollmentRepo: $this->enrollmentRepo,
            prerequisiteService: $this->prerequisiteService
        );

        // Read 3 of 5 blocks
        $payload = json_encode([
            'last_page' => 3,
            'total_pages' => 5,
            'viewed_pages' => [1, 2, 3]
        ]);
        $request = new Request(
            queryParams: [],
            postParams: [],
            serverParams: [
                'REQUEST_METHOD' => 'POST',
                'REQUEST_URI' => "/student/content/{$this->docxContentId}/progress",
                'CONTENT_TYPE' => 'application/json',
                'HTTP_CONTENT_TYPE' => 'application/json'
            ],
            files: [],
            rawBody: $payload
        );

        $response = $controller->saveProgress($request, $this->docxContentId);
        $this->assertSame(200, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['success']);
        $this->assertSame(3, $data['data']['last_page']);
        $this->assertSame(5, $data['data']['total_pages']);
        $this->assertSame(3, $data['data']['unique_pages_count']);
        $this->assertEquals(60.0, (float)$data['data']['progress_percent']);
        $this->assertFalse((bool)$data['data']['is_completed']);
        $this->assertNull($data['data']['completed_at']);
    }

    public function testReachingNinetyPercentBlocksMarksDocxCompleted(): void
    {
        $auth = $this->createMockAuthenticator($this->studentUser);

        $controller = new ContentController(
            authenticator: $auth,
            contentService: $this->contentService,
            academicRepo: $this->academicRepo,
            studentRepo: $this->studentRepo,
            enrollmentRepo: $this->enrollmentRepo,
            prerequisiteService: $this->prerequisiteService
        );

        // 9 of 10 blocks is 90% -> should complete!
        $payload = json_encode([
            'last_page' => 9,
            'total_pages' => 10,
            'viewed_pages' => [1, 2, 3, 4, 5, 6, 7, 8, 9]
        ]);
        $request = new Request(
            queryParams: [],
            postParams: [],
            serverParams: [
                'REQUEST_METHOD' => 'POST',
                'REQUEST_URI' => "/student/content/{$this->docxContentId}/progress",
                'CONTENT_TYPE' => 'application/json',
                'HTTP_CONTENT_TYPE' => 'application/json'
            ],
            files: [],
            rawBody: $payload
        );

        $response = $controller->saveProgress($request, $this->docxContentId);
        $this->assertSame(200, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['success']);
        $this->assertTrue((bool)$data['data']['is_completed']);
        $this->assertNotNull($data['data']['completed_at']);
        $this->assertEquals(90.0, (float)$data['data']['progress_percent']);
    }

    public function testClientCannotForgeCompletionOnDocx(): void
    {
        $auth = $this->createMockAuthenticator($this->studentUser);

        $controller = new ContentController(
            authenticator: $auth,
            contentService: $this->contentService,
            academicRepo: $this->academicRepo,
            studentRepo: $this->studentRepo,
            enrollmentRepo: $this->enrollmentRepo,
            prerequisiteService: $this->prerequisiteService
        );

        // Client attempts to send is_completed = 1 with only 1 of 10 blocks
        $payload = json_encode([
            'last_page' => 1,
            'total_pages' => 10,
            'viewed_pages' => [1],
            'is_completed' => 1
        ]);
        $request = new Request(
            queryParams: [],
            postParams: [],
            serverParams: [
                'REQUEST_METHOD' => 'POST',
                'REQUEST_URI' => "/student/content/{$this->docxContentId}/progress",
                'CONTENT_TYPE' => 'application/json',
                'HTTP_CONTENT_TYPE' => 'application/json'
            ],
            files: [],
            rawBody: $payload
        );

        $response = $controller->saveProgress($request, $this->docxContentId);
        $this->assertSame(200, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['success']);
        $this->assertFalse((bool)$data['data']['is_completed']);
        $this->assertNull($data['data']['completed_at']);
        $this->assertEquals(10.0, (float)$data['data']['progress_percent']);
    }

    public function testDocxProgressIsIsolatedPerStudent(): void
    {
        // Enroll Bob in class & subject too
        $this->enrollmentService->enrollStudentInClass($this->otherStudentId, 1, $this->sessionId);

        $actor1 = UserContext::fromUser($this->studentUser);
        $actor2 = UserContext::fromUser($this->otherStudentUser);

        // Student 1 reads 5 of 5 blocks (100%)
        $this->contentService->recordDocumentReadingProgress(
            contentItemId: $this->docxContentId,
            lastPage: 5,
            totalPages: 5,
            newPages: [1, 2, 3, 4, 5],
            actor: $actor1
        );

        // Student 2 has not read anything
        $progress2 = $this->contentService->getDocumentReadingProgress($this->docxContentId, $actor2);
        $this->assertNull($progress2);

        // Student 1 progress is 100% and completed
        $progress1 = $this->contentService->getDocumentReadingProgress($this->docxContentId, $actor1);
        $this->assertNotNull($progress1);
        $this->assertTrue($progress1->isCompleted());
        $this->assertEquals(100.0, (float)$progress1->progressPercent);
    }

    public function testDocxCompletionUnlocksPrerequisiteActivity(): void
    {
        $actor = UserContext::fromUser($this->studentUser);

        // Create a dependent Quiz requiring completion of this DOCX document
        $this->pdo->prepare("INSERT INTO quizzes (class_subject_id, teacher_id, title, status, created_at, updated_at) VALUES (?, ?, 'Docx Comprehension Quiz', 'published', datetime('now'), datetime('now'))")
            ->execute([$this->classSubjectId, $this->teacherId]);
        $quizId = (int)$this->pdo->lastInsertId();

        // Add prerequisite: Quiz #quizId requires completion of DOCX ContentItem #docxContentId
        $this->prerequisiteRepo->create(
            activityType: ActivityProgress::TYPE_QUIZ,
            activityId: $quizId,
            prereqType: ActivityProgress::TYPE_DOCUMENT,
            prereqId: $this->docxContentId
        );

        // Initially, Quiz is LOCKED
        $statusBefore = $this->prerequisiteService->getPrerequisiteStatus(
            studentId: $this->studentId,
            activityType: ActivityProgress::TYPE_QUIZ,
            activityId: $quizId
        );
        $this->assertFalse($statusBefore['is_unlocked']);
        $this->assertCount(1, $statusBefore['unmet']);

        // Student reads DOCX completely (10 of 10 blocks)
        $this->contentService->recordDocumentReadingProgress(
            contentItemId: $this->docxContentId,
            lastPage: 10,
            totalPages: 10,
            newPages: [1, 2, 3, 4, 5, 6, 7, 8, 9, 10],
            actor: $actor
        );

        // Now, Quiz is UNLOCKED
        $statusAfter = $this->prerequisiteService->getPrerequisiteStatus(
            studentId: $this->studentId,
            activityType: ActivityProgress::TYPE_QUIZ,
            activityId: $quizId
        );
        $this->assertTrue($statusAfter['is_unlocked']);
        $this->assertEmpty($statusAfter['unmet']);
    }
}
