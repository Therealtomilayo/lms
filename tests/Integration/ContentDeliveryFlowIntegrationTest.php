<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Core\Exceptions\AuthorizationException;
use App\Core\Exceptions\ResourceNotFoundException;
use App\Core\UserContext;
use App\Models\ContentItem;
use App\Models\FileRecord;
use App\Models\User;
use App\Policies\ContentPolicy;
use App\Policies\FilePolicy;
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
use App\Services\GuardianService;
use App\Services\UserService;
use PDO;
use PHPUnit\Framework\TestCase;

final class ContentDeliveryFlowIntegrationTest extends TestCase
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

    private UserService $userService;
    private EnrollmentService $enrollmentService;
    private GuardianService $guardianService;
    private FileStorageService $fileStorageService;
    private ContentService $contentService;

    private string $tempUploadDir;

    private User $adminUser;
    private User $teacherUser;
    private User $studentUser;
    private User $otherStudentUser;
    private User $parentUser;

    private int $teacherId;
    private int $studentId;
    private int $otherStudentId;
    private int $parentId;
    private int $sessionId;
    private int $classSubjectId;

    protected function setUp(): void
    {
        $this->tempUploadDir = sys_get_temp_dir() . '/lms_flow_uploads_' . uniqid();
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
        ");

        $this->userRepo = new UserRepository($this->pdo);
        $this->studentRepo = new StudentRepository($this->pdo);
        $this->parentRepo = new ParentRepository($this->pdo);
        $this->teacherRepo = new TeacherRepository($this->pdo);
        $this->academicRepo = new AcademicRepository($this->pdo);
        $this->enrollmentRepo = new EnrollmentRepository($this->pdo);
        $this->fileRepo = new FileRepository($this->pdo);
        $this->contentRepo = new ContentRepository($this->pdo, $this->fileRepo, $this->academicRepo, $this->teacherRepo);

        $this->userService = new UserService($this->userRepo, $this->studentRepo, $this->teacherRepo, $this->parentRepo);
        $this->enrollmentService = new EnrollmentService($this->enrollmentRepo, $this->studentRepo, $this->academicRepo);
        $this->guardianService = new GuardianService($this->parentRepo, $this->studentRepo, $this->userRepo);
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

        // Setup base academic hierarchy & users
        $this->adminUser = $this->userRepo->create([
            'uuid' => 'admin-u',
            'name' => 'Admin User',
            'email' => 'admin@claret.edu',
            'password_hash' => 'hash',
            'status' => 'active',
        ], ['admin']);

        $this->teacherUser = $this->userRepo->create([
            'uuid' => 'teacher-u',
            'name' => 'Mr. Physics Teacher',
            'email' => 'physics@claret.edu',
            'password_hash' => 'hash',
            'status' => 'active',
        ], ['teacher']);
        $teacher = $this->teacherRepo->createTeacher($this->teacherUser->id, 'STF-PHY-01');
        $this->teacherId = $teacher->id;

        $this->studentUser = $this->userRepo->create([
            'uuid' => 'student-u',
            'name' => 'Kelechi Student',
            'email' => 'kelechi@claret.edu',
            'password_hash' => 'hash',
            'status' => 'active',
        ], ['student']);
        $student = $this->studentRepo->create($this->studentUser->id, 'STD-2026-PHY-01', null, 'male');
        $this->studentId = $student->id;

        $this->otherStudentUser = $this->userRepo->create([
            'uuid' => 'other-std-u',
            'name' => 'Unenrolled Student',
            'email' => 'other@claret.edu',
            'password_hash' => 'hash',
            'status' => 'active',
        ], ['student']);
        $otherStudent = $this->studentRepo->create($this->otherStudentUser->id, 'STD-2026-UNEN-01', null, 'female');
        $this->otherStudentId = $otherStudent->id;

        $this->parentUser = $this->userRepo->create([
            'uuid' => 'parent-u',
            'name' => 'Mrs. Kelechi Parent',
            'email' => 'kelechi.parent@gmail.com',
            'password_hash' => 'hash',
            'status' => 'active',
        ], ['parent']);
        $parent = $this->parentRepo->create($this->parentUser->id);
        $this->parentId = $parent->id;
        $this->parentRepo->linkStudent($this->parentId, $this->studentId, 'Mother');

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

        // Enroll student in class & subject
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
    }

    public function testCompletePhase4ContentAndFileDeliveryLifecycle(): void
    {
        $teacherContext = UserContext::fromUser($this->teacherUser);
        $studentContext = UserContext::fromUser($this->studentUser);
        $otherStudentContext = UserContext::fromUser($this->otherStudentUser);
        $parentContext = UserContext::fromUser($this->parentUser);
        $adminContext = UserContext::fromUser($this->adminUser);

        // 1. Teacher creates draft lesson note with an attached PDF document
        $dummyPdfPath = tempnam(sys_get_temp_dir(), 'testpdf');
        file_put_contents($dummyPdfPath, '%PDF-1.4 Thermodynamics Lecture Notes');

        $fakeUpload = [
            'name' => 'Thermodynamics_Week_1.pdf',
            'type' => 'application/pdf',
            'tmp_name' => $dummyPdfPath,
            'error' => UPLOAD_ERR_OK,
            'size' => filesize($dummyPdfPath),
        ];

        $createResult = $this->contentService->createContent([
            'class_subject_id' => $this->classSubjectId,
            'title' => 'Introduction to Thermodynamics',
            'topic' => 'Thermal Physics',
            'type' => 'document',
            'description' => 'Comprehensive lecture notes covering the First Law of Thermodynamics.',
            'publish_now' => 0, // Draft
        ], $fakeUpload, $teacherContext);

        if (file_exists($dummyPdfPath)) {
            unlink($dummyPdfPath);
        }

        $this->assertTrue($createResult->isSuccess());
        /** @var ContentItem $draftItem */
        $draftItem = $createResult->getData()['content_item'];
        $this->assertNotNull($draftItem);
        $this->assertTrue($draftItem->isDraft());
        $this->assertNotNull($draftItem->fileId);

        // 2. Draft content item must NOT be visible to student
        $this->expectException(ResourceNotFoundException::class);
        $this->contentService->getContentItem($draftItem->id, $studentContext);
    }

    public function testStudentDeniedAccessToDraftFile(): void
    {
        $teacherContext = UserContext::fromUser($this->teacherUser);
        $studentContext = UserContext::fromUser($this->studentUser);

        $dummyPdfPath = tempnam(sys_get_temp_dir(), 'testpdf2');
        file_put_contents($dummyPdfPath, '%PDF-1.4 Draft Confidential Exam');

        $fakeUpload = [
            'name' => 'Draft_Exam.pdf',
            'type' => 'application/pdf',
            'tmp_name' => $dummyPdfPath,
            'error' => UPLOAD_ERR_OK,
            'size' => filesize($dummyPdfPath),
        ];

        $createResult = $this->contentService->createContent([
            'class_subject_id' => $this->classSubjectId,
            'title' => 'Confidential Draft Exam',
            'type' => 'document',
            'publish_now' => 0, // Draft
        ], $fakeUpload, $teacherContext);

        if (file_exists($dummyPdfPath)) {
            unlink($dummyPdfPath);
        }

        $item = $createResult->getData()['content_item'];
        $fileId = $item->fileId;

        // Student tries to download draft file directly -> ResourceNotFoundException (masked 404)
        $this->expectException(ResourceNotFoundException::class);
        $this->fileStorageService->getFileForDownload($fileId, $studentContext);
    }

    public function testPublishedContentDeliveryAndDownload(): void
    {
        $teacherContext = UserContext::fromUser($this->teacherUser);
        $studentContext = UserContext::fromUser($this->studentUser);
        $otherStudentContext = UserContext::fromUser($this->otherStudentUser);
        $parentContext = UserContext::fromUser($this->parentUser);
        $adminContext = UserContext::fromUser($this->adminUser);

        $dummyPdfPath = tempnam(sys_get_temp_dir(), 'testpdf3');
        file_put_contents($dummyPdfPath, '%PDF-1.4 Published Physics Study Guide');

        $fakeUpload = [
            'name' => 'Physics_Guide.pdf',
            'type' => 'application/pdf',
            'tmp_name' => $dummyPdfPath,
            'error' => UPLOAD_ERR_OK,
            'size' => filesize($dummyPdfPath),
        ];

        $createResult = $this->contentService->createContent([
            'class_subject_id' => $this->classSubjectId,
            'title' => 'Physics Study Guide 2026',
            'topic' => 'Exam Prep',
            'type' => 'document',
            'publish_now' => 1, // Published
        ], $fakeUpload, $teacherContext);

        if (file_exists($dummyPdfPath)) {
            unlink($dummyPdfPath);
        }

        $item = $createResult->getData()['content_item'];
        $this->assertTrue($item->isPublished());
        $fileId = $item->fileId;

        // 1. Enrolled student can view and download
        $studentView = $this->contentService->getContentItem($item->id, $studentContext);
        $this->assertTrue($studentView->isSuccess());

        $downloadInfo = $this->fileStorageService->getFileForDownload($fileId, $studentContext);
        $this->assertSame('Physics_Guide.pdf', $downloadInfo['file']->originalName);
        $this->assertFileExists($downloadInfo['path']);

        // 2. Parent of enrolled student can view and download
        $parentView = $this->contentService->getContentItem($item->id, $parentContext);
        $this->assertTrue($parentView->isSuccess());
        $parentDownload = $this->fileStorageService->getFileForDownload($fileId, $parentContext);
        $this->assertSame('Physics_Guide.pdf', $parentDownload['file']->originalName);

        // 3. Admin can view and download
        $adminDownload = $this->fileStorageService->getFileForDownload($fileId, $adminContext);
        $this->assertSame('Physics_Guide.pdf', $adminDownload['file']->originalName);

        // 4. Unenrolled student is denied access
        $unenrolledDenied = false;
        try {
            $this->contentService->getContentItem($item->id, $otherStudentContext);
        } catch (ResourceNotFoundException | AuthorizationException $e) {
            $unenrolledDenied = true;
        }
        $this->assertTrue($unenrolledDenied, 'Unenrolled student should be denied content access.');

        // 5. Unenrolled student download is denied
        $downloadDenied = false;
        try {
            $this->fileStorageService->getFileForDownload($fileId, $otherStudentContext);
        } catch (ResourceNotFoundException | AuthorizationException $e) {
            $downloadDenied = true;
        }
        $this->assertTrue($downloadDenied, 'Unenrolled student should be denied file download.');
    }
}
