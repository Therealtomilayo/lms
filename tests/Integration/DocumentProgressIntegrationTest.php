<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Controllers\Student\ContentController;
use App\Core\AuthenticatorInterface;
use App\Core\Request;
use App\Core\UserContext;
use App\Models\User;
use App\Repositories\AcademicRepository;
use App\Repositories\ActivityProgressRepository;
use App\Repositories\ContentRepository;
use App\Repositories\EnrollmentRepository;
use App\Repositories\FileRepository;
use App\Repositories\ParentRepository;
use App\Repositories\StudentRepository;
use App\Repositories\TeacherRepository;
use App\Repositories\UserRepository;
use App\Services\ContentService;
use App\Services\EnrollmentService;
use App\Services\FileStorageService;
use App\Repositories\DocumentSectionRepository;
use PDO;
use PHPUnit\Framework\TestCase;

final class DocumentProgressIntegrationTest extends TestCase
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

    private EnrollmentService $enrollmentService;
    private FileStorageService $fileStorageService;
    private ContentService $contentService;

    private string $tempUploadDir;

    private User $teacherUser;
    private User $studentUser;
    private User $otherStudentUser;

    private int $teacherId;
    private int $studentId;
    private int $otherStudentId;
    private int $sessionId;
    private int $classSubjectId;
    private int $pdfContentId;
    private int $nonPdfContentId;

    protected function setUp(): void
    {
        $this->tempUploadDir = sys_get_temp_dir() . '/lms_pdf_progress_test_' . uniqid();
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
            new DocumentSectionRepository($this->pdo)
        );

        $this->seedData();
    }

    private function seedData(): void
    {
        $this->teacherUser = $this->userRepo->create([
            'uuid' => 'teacher-u',
            'name' => 'Mr. Physics Teacher',
            'email' => 'physics@school.edu',
            'password_hash' => 'hash',
            'status' => 'active',
        ], ['teacher']);
        $teacher = $this->teacherRepo->createTeacher($this->teacherUser->id, 'STF-PHY-01');
        $this->teacherId = $teacher->id;

        $this->studentUser = $this->userRepo->create([
            'uuid' => 'student-u',
            'name' => 'Student Alice',
            'email' => 'alice@school.edu',
            'password_hash' => 'hash',
            'status' => 'active',
        ], ['student']);
        $student = $this->studentRepo->create($this->studentUser->id, 'STD-2026-PHY-01', null, 'female');
        $this->studentId = $student->id;

        $this->otherStudentUser = $this->userRepo->create([
            'uuid' => 'other-std-u',
            'name' => 'Student Bob',
            'email' => 'bob@school.edu',
            'password_hash' => 'hash',
            'status' => 'active',
        ], ['student']);
        $otherStudent = $this->studentRepo->create($this->otherStudentUser->id, 'STD-2026-UNEN-01', null, 'male');
        $this->otherStudentId = $otherStudent->id;

        $level = $this->academicRepo->createLevel(['name' => 'Senior Secondary', 'stage' => 'secondary', 'rank_order' => 1]);
        $class = $this->academicRepo->createClass(['academic_level_id' => $level->id, 'name' => 'SS 1 Science', 'section_arm' => 'Science']);
        $session = $this->academicRepo->createSession(['name' => '2026/2027', 'start_date' => '2026-09-01', 'end_date' => '2027-07-31', 'status' => 'active', 'is_current' => 1]);
        $this->sessionId = $session->id;
        $subject = $this->academicRepo->createSubject(['code' => 'PHY101', 'name' => 'Physics', 'category' => 'Science']);

        $classSubject = $this->academicRepo->createClassSubject([
            'session_id' => $session->id,
            'class_id' => $class->id,
            'subject_id' => $subject->id,
            'teacher_id' => $this->teacherId,
            'status' => 'active',
        ]);
        $this->classSubjectId = $classSubject->id;

        // Enroll Alice in class
        $this->enrollmentService->enrollStudentInClass($this->studentId, $class->id, $this->sessionId);

        // Create PDF Content Item
        $teacherContext = UserContext::fromUser($this->teacherUser);
        $dummyPdfPath = tempnam(sys_get_temp_dir(), 'testpdf');
        $pdfContent = str_repeat('%PDF-1.4 Physics Lecture Notes. ', 10);
        file_put_contents($dummyPdfPath, $pdfContent);

        $fakePdfUpload = [
            'name' => 'Quantum_Mechanics.pdf',
            'type' => 'application/pdf',
            'tmp_name' => $dummyPdfPath,
            'error' => UPLOAD_ERR_OK,
            'size' => strlen($pdfContent),
        ];

        $createResult = $this->contentService->createContent([
            'class_subject_id' => $this->classSubjectId,
            'title' => 'Quantum Mechanics 101',
            'topic' => 'Physics',
            'type' => 'document',
            'publish_now' => 1,
        ], $fakePdfUpload, $teacherContext);
        @unlink($dummyPdfPath);

        $this->assertTrue($createResult->isSuccess(), 'Failed to create PDF content item');
        $this->pdfContentId = (int)$createResult->getData()['content_item']->id;

        // Create Non-PDF Note Item
        $nonPdfResult = $this->contentService->createContent([
            'class_subject_id' => $this->classSubjectId,
            'title' => 'Simple Lecture Note',
            'topic' => 'Physics',
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

    private function createStudentController(?User $user): ContentController
    {
        $context = $user ? UserContext::fromUser($user) : null;
        $authenticator = $this->createMock(AuthenticatorInterface::class);
        $authenticator->method('user')->willReturn($context);
        $authenticator->method('getUserContext')->willReturn($context);
        $authenticator->method('check')->willReturn($context !== null);

        return new ContentController(
            $authenticator,
            $this->contentService,
            $this->academicRepo,
            $this->studentRepo,
            $this->enrollmentRepo
        );
    }

    private function createPostRequest(array $data): Request
    {
        $raw = json_encode($data);
        return new Request(
            queryParams: [],
            postParams: $data,
            serverParams: ['REQUEST_METHOD' => 'POST', 'CONTENT_TYPE' => 'application/json'],
            files: [],
            rawBody: $raw
        );
    }

    public function test1NewStudentHasNoDocumentProgress(): void
    {
        $controller = $this->createStudentController($this->studentUser);
        $request = new Request([], [], ['REQUEST_METHOD' => 'GET']);

        $response = $controller->getProgress($request, $this->pdfContentId);
        $this->assertSame(200, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['success']);
        $this->assertNull($data['data']);
    }

    public function test2FirstPageCreatesProgressRecordNotCompleted(): void
    {
        $controller = $this->createStudentController($this->studentUser);
        $request = $this->createPostRequest([
            'current_page' => 1,
            'total_pages' => 10,
            'viewed_pages' => [1],
        ]);

        $response = $controller->saveProgress($request, $this->pdfContentId);
        $this->assertSame(200, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['success']);
        $this->assertSame(1, $data['data']['last_page']);
        $this->assertSame(10, $data['data']['total_pages']);
        $this->assertSame([1], $data['data']['pages_read']);
        $this->assertSame(1, $data['data']['unique_pages_count']);
        $this->assertSame(10.0, (float)$data['data']['progress_percent']);
        $this->assertFalse($data['data']['is_completed']);
        $this->assertNull($data['data']['completed_at']);
    }

    public function test3MultipleUniquePagesIncreasesProgress(): void
    {
        $controller = $this->createStudentController($this->studentUser);
        $request = $this->createPostRequest([
            'current_page' => 5,
            'total_pages' => 10,
            'viewed_pages' => [1, 2, 3, 4, 5],
        ]);

        $response = $controller->saveProgress($request, $this->pdfContentId);
        $data = json_decode($response->getContent(), true);

        $this->assertSame(5, $data['data']['last_page']);
        $this->assertSame(5, $data['data']['unique_pages_count']);
        $this->assertSame(50.0, (float)$data['data']['progress_percent']);
        $this->assertFalse($data['data']['is_completed']);
    }

    public function test4DuplicatePagesCountOnlyOnce(): void
    {
        $controller = $this->createStudentController($this->studentUser);
        $request = $this->createPostRequest([
            'current_page' => 2,
            'total_pages' => 10,
            'viewed_pages' => [1, 2, 2, 2, 3, 3, 1],
        ]);

        $response = $controller->saveProgress($request, $this->pdfContentId);
        $data = json_decode($response->getContent(), true);

        $this->assertSame(3, $data['data']['unique_pages_count']);
        $this->assertSame([1, 2, 3], $data['data']['pages_read']);
        $this->assertSame(30.0, (float)$data['data']['progress_percent']);
    }

    public function test5EightyNinePercentRemainsIncomplete(): void
    {
        // 100 pages document, student reads 89 unique pages -> 89.00% -> NOT completed
        $pages = range(1, 89);
        $controller = $this->createStudentController($this->studentUser);
        $request = $this->createPostRequest([
            'current_page' => 89,
            'total_pages' => 100,
            'viewed_pages' => $pages,
        ]);

        $response = $controller->saveProgress($request, $this->pdfContentId);
        $data = json_decode($response->getContent(), true);

        $this->assertSame(89.0, (float)$data['data']['progress_percent']);
        $this->assertFalse($data['data']['is_completed']);
        $this->assertNull($data['data']['completed_at']);
    }

    public function test6ExactlyNinetyPercentBecomesCompletedWithTimestamp(): void
    {
        // 10 pages document, student reads 9 unique pages -> 90.00% -> Completed!
        $controller = $this->createStudentController($this->studentUser);
        $request = $this->createPostRequest([
            'current_page' => 9,
            'total_pages' => 10,
            'viewed_pages' => [1, 2, 3, 4, 5, 6, 7, 8, 9],
        ]);

        $response = $controller->saveProgress($request, $this->pdfContentId);
        $data = json_decode($response->getContent(), true);

        $this->assertSame(90.0, (float)$data['data']['progress_percent']);
        $this->assertTrue($data['data']['is_completed']);
        $this->assertNotNull($data['data']['completed_at']);
        $firstCompletedAt = $data['data']['completed_at'];

        // Subsequent save to page 10 must keep initial completed_at
        $request2 = $this->createPostRequest([
            'current_page' => 10,
            'total_pages' => 10,
            'viewed_pages' => [10],
        ]);

        $response2 = $controller->saveProgress($request2, $this->pdfContentId);
        $data2 = json_decode($response2->getContent(), true);

        $this->assertSame(100.0, (float)$data2['data']['progress_percent']);
        $this->assertTrue($data2['data']['is_completed']);
        $this->assertSame($firstCompletedAt, $data2['data']['completed_at']);
    }

    public function test7ProgressCannotRegressFromStaleClientData(): void
    {
        $controller = $this->createStudentController($this->studentUser);

        // Step 1: Reader reaches pages 1..8 (80%)
        $request1 = $this->createPostRequest([
            'current_page' => 8,
            'total_pages' => 10,
            'viewed_pages' => [1, 2, 3, 4, 5, 6, 7, 8],
        ]);
        $controller->saveProgress($request1, $this->pdfContentId);

        // Step 2: An older tab sends only pages [1, 2]
        $staleRequest = $this->createPostRequest([
            'current_page' => 2,
            'total_pages' => 10,
            'viewed_pages' => [1, 2],
        ]);

        $response = $controller->saveProgress($staleRequest, $this->pdfContentId);
        $data = json_decode($response->getContent(), true);

        // Result MUST remain union of all pages [1, 2, 3, 4, 5, 6, 7, 8]
        $this->assertSame(8, $data['data']['unique_pages_count']);
        $this->assertSame(80.0, (float)$data['data']['progress_percent']);
        $this->assertSame(2, $data['data']['last_page']);
    }

    public function test8UnenrolledStudentCannotSaveProgress(): void
    {
        $controller = $this->createStudentController($this->otherStudentUser);
        $request = $this->createPostRequest([
            'current_page' => 1,
            'total_pages' => 10,
            'viewed_pages' => [1],
        ]);

        $response = $controller->saveProgress($request, $this->pdfContentId);
        $this->assertSame(404, $response->getStatusCode());
    }

    public function test9UnauthenticatedUserCannotAccessProgressEndpoint(): void
    {
        $controller = $this->createStudentController(null);

        $request = $this->createPostRequest([
            'current_page' => 1,
            'total_pages' => 10,
            'viewed_pages' => [1],
        ]);

        $response = $controller->saveProgress($request, $this->pdfContentId);
        $this->assertSame(403, $response->getStatusCode());
    }

    public function test10InvalidPageValuesAreSafelyIgnoredOrClamped(): void
    {
        $controller = $this->createStudentController($this->studentUser);

        // Submit negative page, 0, strings, out-of-bounds (>10), alongside valid page 2 and 4
        $request = $this->createPostRequest([
            'current_page' => 999, // clamped to total_pages (10)
            'total_pages' => 10,
            'viewed_pages' => [-5, 0, 2, 4, 15, 'hack', null],
        ]);

        $response = $controller->saveProgress($request, $this->pdfContentId);
        $this->assertSame(200, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        // Only 2 and 4 from array are valid, plus clamped last_page 10
        $this->assertSame([2, 4, 10], $data['data']['pages_read']);
        $this->assertSame(3, $data['data']['unique_pages_count']);
        $this->assertSame(30.0, (float)$data['data']['progress_percent']);
        $this->assertSame(10, $data['data']['last_page']); // Clamped to 10
    }

    public function test11InvalidTotalPagesIsRejected(): void
    {
        $controller = $this->createStudentController($this->studentUser);

        $request = $this->createPostRequest([
            'current_page' => 1,
            'total_pages' => 0, // Invalid!
            'viewed_pages' => [1],
        ]);

        $response = $controller->saveProgress($request, $this->pdfContentId);
        $this->assertSame(422, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertFalse($data['success']);
    }

    public function test12NonPdfContentCannotUsePdfProgressEndpoint(): void
    {
        $controller = $this->createStudentController($this->studentUser);

        $request = $this->createPostRequest([
            'current_page' => 1,
            'total_pages' => 5,
            'viewed_pages' => [1],
        ]);

        // nonPdfContentId is a text note, not a PDF
        $response = $controller->saveProgress($request, $this->nonPdfContentId);
        $this->assertSame(400, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertFalse($data['success']);
        $this->assertStringContainsString('PDF', $data['error']);
    }

    public function test13ClientAttemptToForgeProgressPercentAndCompletionIsIgnored(): void
    {
        $controller = $this->createStudentController($this->studentUser);

        // Malicious client tries to send is_completed=true and progress_percent=100 with only page 1
        $request = $this->createPostRequest([
            'current_page' => 1,
            'total_pages' => 10,
            'viewed_pages' => [1],
            'progress_percent' => 100,
            'is_completed' => true,
        ]);

        $response = $controller->saveProgress($request, $this->pdfContentId);
        $data = json_decode($response->getContent(), true);

        // Server authority overrides client forging:
        $this->assertSame(10.0, (float)$data['data']['progress_percent']);
        $this->assertFalse($data['data']['is_completed']);
        $this->assertNull($data['data']['completed_at']);
    }
}
