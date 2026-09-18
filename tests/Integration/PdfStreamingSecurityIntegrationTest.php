<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Controllers\FileController;
use App\Controllers\Student\ContentController;
use App\Core\AuthenticatorInterface;
use App\Core\Request;
use App\Core\UserContext;
use App\Models\User;
use App\Repositories\AcademicRepository;
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
use PDO;
use PHPUnit\Framework\TestCase;

final class PdfStreamingSecurityIntegrationTest extends TestCase
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

    protected function setUp(): void
    {
        $this->tempUploadDir = sys_get_temp_dir() . '/lms_pdf_stream_test_' . uniqid();
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
            $this->pdo
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

        // Enroll Alice in class & subject
        $this->enrollmentService->enrollStudentInClass($this->studentId, $class->id, $this->sessionId);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->tempUploadDir)) {
            $files = glob($this->tempUploadDir . '/*');
            if ($files) {
                foreach ($files as $file) {
                    if (is_file($file)) {
                        unlink($file);
                    }
                }
            }
            rmdir($this->tempUploadDir);
        }
        parent::tearDown();
    }

    private function createAuthenticator(?User $currentUser = null): AuthenticatorInterface
    {
        $context = $currentUser ? UserContext::fromUser($currentUser) : null;
        $mock = $this->createMock(AuthenticatorInterface::class);
        $mock->method('user')->willReturn($context);
        $mock->method('getUserContext')->willReturn($context);
        $mock->method('check')->willReturn($context !== null);
        $mock->method('authenticate')->willReturn($context);
        return $mock;
    }

    public function testEnrolledStudentCanStreamPublishedPdfWithFullAndRangeRequests(): void
    {
        $teacherContext = UserContext::fromUser($this->teacherUser);

        $dummyPdfPath = tempnam(sys_get_temp_dir(), 'testpdf');
        $pdfContent = str_repeat('%PDF-1.4 Physics Lecture Notes Header. ', 3);
        file_put_contents($dummyPdfPath, $pdfContent);
        $pdfSize = strlen($pdfContent);

        $fakeUpload = [
            'name' => 'Physics_Week_1.pdf',
            'type' => 'application/pdf',
            'tmp_name' => $dummyPdfPath,
            'error' => UPLOAD_ERR_OK,
            'size' => $pdfSize,
        ];

        $createResult = $this->contentService->createContent([
            'class_subject_id' => $this->classSubjectId,
            'title' => 'Physics Chapter 1',
            'topic' => 'Mechanics',
            'type' => 'document',
            'publish_now' => 1,
        ], $fakeUpload, $teacherContext);

        if (file_exists($dummyPdfPath)) {
            unlink($dummyPdfPath);
        }

        $this->assertTrue($createResult->isSuccess());
        $contentItem = $createResult->getData()['content_item'];
        $fileId = (int)$contentItem->fileId;

        // 1. Enrolled student streams full PDF (HTTP 200)
        $auth = $this->createAuthenticator($this->studentUser);
        $controller = new FileController($auth, $this->fileStorageService);

        $req200 = new Request([], [], ['REQUEST_METHOD' => 'GET']);
        $resp200 = $controller->stream($req200, $fileId);

        $this->assertSame(200, $resp200->getStatusCode());
        $this->assertSame('bytes', $resp200->getHeader('Accept-Ranges'));
        $this->assertSame('application/pdf', $resp200->getHeader('Content-Type'));
        $this->assertStringContainsString('inline; filename="Physics_Week_1.pdf"', $resp200->getHeader('Content-Disposition') ?? '');
        $this->assertSame((string)$pdfSize, $resp200->getHeader('Content-Length'));

        // 2. Enrolled student streams partial byte range (HTTP 206)
        $req206 = new Request([], [], [
            'REQUEST_METHOD' => 'GET',
            'HTTP_RANGE' => 'bytes=0-15',
        ]);
        $resp206 = $controller->stream($req206, $fileId);

        $this->assertSame(206, $resp206->getStatusCode());
        $this->assertSame('bytes', $resp206->getHeader('Accept-Ranges'));
        $this->assertSame('bytes 0-15/' . $pdfSize, $resp206->getHeader('Content-Range'));
        $this->assertSame('16', $resp206->getHeader('Content-Length'));
        $this->assertSame(0, $resp206->getStreamStart());
        $this->assertSame(16, $resp206->getStreamLength());

        // 3. Existing download continues to work untouched with attachment disposition
        $respDownload = $controller->download($req200, $fileId);
        $this->assertSame(200, $respDownload->getStatusCode());
        $this->assertStringContainsString('attachment; filename="Physics_Week_1.pdf"', $respDownload->getHeader('Content-Disposition') ?? '');
    }

    public function testUnenrolledStudentIsDeniedAccessToStream(): void
    {
        $teacherContext = UserContext::fromUser($this->teacherUser);

        $dummyPdfPath = tempnam(sys_get_temp_dir(), 'testpdf');
        file_put_contents($dummyPdfPath, '%PDF-1.4 Secret Physics Notes');

        $fakeUpload = [
            'name' => 'Secret_Physics.pdf',
            'type' => 'application/pdf',
            'tmp_name' => $dummyPdfPath,
            'error' => UPLOAD_ERR_OK,
            'size' => filesize($dummyPdfPath),
        ];

        $createResult = $this->contentService->createContent([
            'class_subject_id' => $this->classSubjectId,
            'title' => 'Secret Physics',
            'type' => 'document',
            'publish_now' => 1,
        ], $fakeUpload, $teacherContext);

        if (file_exists($dummyPdfPath)) {
            unlink($dummyPdfPath);
        }

        $contentItem = $createResult->getData()['content_item'];
        $fileId = (int)$contentItem->fileId;

        // Unenrolled student tries to stream the file
        $auth = $this->createAuthenticator($this->otherStudentUser);
        $controller = new FileController($auth, $this->fileStorageService);

        $req = new Request([], [], ['REQUEST_METHOD' => 'GET']);
        $resp = $controller->stream($req, $fileId);

        // Expect masked 404
        $this->assertSame(404, $resp->getStatusCode());
    }

    public function testUnauthenticatedUserIsRedirectedToLogin(): void
    {
        $auth = $this->createAuthenticator(null);
        $controller = new FileController($auth, $this->fileStorageService);

        $req = new Request([], [], ['REQUEST_METHOD' => 'GET']);
        $resp = $controller->stream($req, 999);

        $this->assertSame(302, $resp->getStatusCode());
        $this->assertSame('/login', $resp->getHeader('Location'));
    }

    public function testDedicatedReaderViewRendersForEnrolledStudent(): void
    {
        $teacherContext = UserContext::fromUser($this->teacherUser);

        $dummyPdfPath = tempnam(sys_get_temp_dir(), 'testpdf');
        file_put_contents($dummyPdfPath, '%PDF-1.4 Newton Laws Lesson');

        $fakeUpload = [
            'name' => 'Newton_Laws.pdf',
            'type' => 'application/pdf',
            'tmp_name' => $dummyPdfPath,
            'error' => UPLOAD_ERR_OK,
            'size' => filesize($dummyPdfPath),
        ];

        $createResult = $this->contentService->createContent([
            'class_subject_id' => $this->classSubjectId,
            'title' => 'Newton Laws of Motion',
            'type' => 'document',
            'publish_now' => 1,
        ], $fakeUpload, $teacherContext);

        if (file_exists($dummyPdfPath)) {
            unlink($dummyPdfPath);
        }

        $contentItem = $createResult->getData()['content_item'];

        // Enrolled student opens reader
        $auth = $this->createAuthenticator($this->studentUser);
        $studentContentController = new ContentController(
            $auth,
            $this->contentService,
            $this->academicRepo,
            $this->studentRepo,
            $this->enrollmentRepo
        );

        $req = new Request([], [], ['REQUEST_METHOD' => 'GET']);
        $resp = $studentContentController->read($req, $contentItem->id);

        $this->assertSame(200, $resp->getStatusCode());
        $this->assertStringContainsString('Online Document Reader', $resp->getContent());
        $this->assertStringContainsString('pdf.min.js', $resp->getContent());
        $this->assertStringContainsString("/files/{$contentItem->fileId}/stream", $resp->getContent());

        // Unenrolled student opens reader
        $otherAuth = $this->createAuthenticator($this->otherStudentUser);
        $otherController = new ContentController(
            $otherAuth,
            $this->contentService,
            $this->academicRepo,
            $this->studentRepo,
            $this->enrollmentRepo
        );

        $otherResp = $otherController->read($req, $contentItem->id);
        $this->assertSame(404, $otherResp->getStatusCode());
    }
}
