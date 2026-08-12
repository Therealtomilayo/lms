<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Core\Exceptions\AuthorizationException;
use App\Core\Exceptions\DomainRuleException;
use App\Core\Exceptions\ValidationException;
use App\Core\UserContext;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\User;
use App\Policies\AssignmentPolicy;
use App\Policies\FilePolicy;
use App\Repositories\AcademicRepository;
use App\Repositories\AssignmentRepository;
use App\Repositories\EnrollmentRepository;
use App\Repositories\FileRepository;
use App\Repositories\ParentRepository;
use App\Repositories\StudentRepository;
use App\Repositories\TeacherRepository;
use App\Repositories\UserRepository;
use App\Services\AssignmentService;
use App\Services\EnrollmentService;
use App\Services\FileStorageService;
use App\Services\GuardianService;
use App\Services\UserService;
use PDO;
use PHPUnit\Framework\TestCase;

final class AssignmentLifecycleIntegrationTest extends TestCase
{
    private PDO $pdo;
    private UserRepository $userRepo;
    private StudentRepository $studentRepo;
    private ParentRepository $parentRepo;
    private TeacherRepository $teacherRepo;
    private AcademicRepository $academicRepo;
    private EnrollmentRepository $enrollmentRepo;
    private FileRepository $fileRepo;
    private AssignmentRepository $assignmentRepo;

    private UserService $userService;
    private EnrollmentService $enrollmentService;
    private GuardianService $guardianService;
    private FileStorageService $fileStorageService;
    private AssignmentService $assignmentService;

    private string $tempUploadDir;

    private User $adminUser;
    private User $teacherUser;
    private User $otherTeacherUser;
    private User $studentUser;
    private User $otherStudentUser;
    private User $parentUser;

    private int $teacherId;
    private int $otherTeacherId;
    private int $studentId;
    private int $otherStudentId;
    private int $parentId;
    private int $sessionId;
    private int $termId;
    private int $classSubjectId;

    protected function setUp(): void
    {
        $this->tempUploadDir = sys_get_temp_dir() . '/lms_assignment_uploads_' . uniqid();
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
                `session_id` INTEGER NOT NULL,
                `class_id` INTEGER NOT NULL,
                `student_id` INTEGER NOT NULL,
                `status` VARCHAR(20) NOT NULL DEFAULT 'active',
                `enrolled_at` DATETIME NULL,
                `created_at` DATETIME NOT NULL,
                `updated_at` DATETIME NOT NULL,
                UNIQUE(`session_id`, `class_id`, `student_id`)
            );

            CREATE TABLE `student_subject_enrollments` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `session_id` INTEGER NOT NULL,
                `class_subject_id` INTEGER NOT NULL,
                `student_id` INTEGER NOT NULL,
                `is_elective` INTEGER NOT NULL DEFAULT 0,
                `status` VARCHAR(20) NOT NULL DEFAULT 'active',
                `created_at` DATETIME NOT NULL,
                `updated_at` DATETIME NOT NULL,
                UNIQUE(`session_id`, `class_subject_id`, `student_id`)
            );

            CREATE TABLE `files` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `uuid` VARCHAR(36) NOT NULL UNIQUE,
                `storage_key` VARCHAR(255) NOT NULL UNIQUE,
                `original_name` VARCHAR(255) NOT NULL,
                `mime_type` VARCHAR(120) NOT NULL,
                `size_bytes` INTEGER NOT NULL,
                `sha256` VARCHAR(64) NOT NULL,
                `owner_type` VARCHAR(50) NOT NULL,
                `owner_id` INTEGER NOT NULL,
                `uploaded_by` INTEGER NOT NULL,
                `created_at` DATETIME NOT NULL,
                `deleted_at` DATETIME NULL
            );

            CREATE TABLE `assignments` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `class_subject_id` INTEGER NOT NULL,
                `term_id` INTEGER NOT NULL,
                `assessment_category_id` INTEGER NULL,
                `teacher_id` INTEGER NOT NULL,
                `topic` VARCHAR(150) NULL,
                `title` VARCHAR(200) NOT NULL,
                `instructions` TEXT NOT NULL,
                `due_at` DATETIME NOT NULL,
                `max_score` NUMERIC(6,2) NOT NULL DEFAULT 100.00,
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
                `file_id` INTEGER NULL,
                `text_response` TEXT NULL,
                `score` NUMERIC(6,2) NULL,
                `teacher_comment` TEXT NULL,
                `graded_at` DATETIME NULL,
                `graded_by` INTEGER NULL,
                `created_at` DATETIME NOT NULL,
                `updated_at` DATETIME NOT NULL,
                UNIQUE(`assignment_id`, `student_id`)
            );
        ");

        $this->userRepo = new UserRepository($this->pdo);
        $this->studentRepo = new StudentRepository($this->pdo);
        $this->parentRepo = new ParentRepository($this->pdo);
        $this->teacherRepo = new TeacherRepository($this->pdo);
        $this->academicRepo = new AcademicRepository($this->pdo);
        $this->enrollmentRepo = new EnrollmentRepository($this->pdo);
        $this->fileRepo = new FileRepository($this->pdo);
        $this->assignmentRepo = new AssignmentRepository($this->pdo);

        $this->fileStorageService = new FileStorageService(
            fileRepository: $this->fileRepo,
            uploadDir: $this->tempUploadDir,
            academicRepository: $this->academicRepo,
            teacherRepository: $this->teacherRepo,
            studentRepository: $this->studentRepo,
            enrollmentRepository: $this->enrollmentRepo,
            parentRepository: $this->parentRepo,
            assignmentRepository: $this->assignmentRepo
        );

        $this->assignmentService = new AssignmentService(
            assignmentRepository: $this->assignmentRepo,
            academicRepository: $this->academicRepo,
            teacherRepository: $this->teacherRepo,
            studentRepository: $this->studentRepo,
            enrollmentRepository: $this->enrollmentRepo,
            parentRepository: $this->parentRepo,
            fileRepository: $this->fileRepo,
            fileStorageService: $this->fileStorageService
        );

        $this->seedInitialData();
    }

    protected function tearDown(): void
    {
        if (is_dir($this->tempUploadDir)) {
            $files = glob($this->tempUploadDir . '/*');
            foreach ($files as $file) {
                if (is_file($file)) unlink($file);
            }
            rmdir($this->tempUploadDir);
        }
    }

    private function seedInitialData(): void
    {
        $now = date('Y-m-d H:i:s');

        // Admin User
        $this->adminUser = $this->userRepo->create([
            'uuid' => 'admin-uuid',
            'name' => 'Admin User',
            'email' => 'admin@claret.edu',
            'password_hash' => password_hash('Pass123', PASSWORD_BCRYPT),
        ], ['admin']);

        // Teachers
        $this->teacherUser = $this->userRepo->create([
            'uuid' => 'teacher-uuid-1',
            'name' => 'Math Teacher',
            'email' => 'math.teacher@claret.edu',
            'password_hash' => password_hash('Pass123', PASSWORD_BCRYPT),
        ], ['teacher']);
        $teacher = $this->teacherRepo->createTeacher($this->teacherUser->id, 'T-MATH-01');
        $this->teacherId = $teacher->id;

        $this->otherTeacherUser = $this->userRepo->create([
            'uuid' => 'teacher-uuid-2',
            'name' => 'Physics Teacher',
            'email' => 'phy.teacher@claret.edu',
            'password_hash' => password_hash('Pass123', PASSWORD_BCRYPT),
        ], ['teacher']);
        $otherTeacher = $this->teacherRepo->createTeacher($this->otherTeacherUser->id, 'T-PHY-01');
        $this->otherTeacherId = $otherTeacher->id;

        // Students
        $this->studentUser = $this->userRepo->create([
            'uuid' => 'student-uuid-1',
            'name' => 'John Doe',
            'email' => 'john.doe@student.claret.edu',
            'password_hash' => password_hash('Pass123', PASSWORD_BCRYPT),
        ], ['student']);
        $student = $this->studentRepo->create($this->studentUser->id, 'STD-2026-001', '2010-05-15', 'male');
        $this->studentId = $student->id;

        $this->otherStudentUser = $this->userRepo->create([
            'uuid' => 'student-uuid-2',
            'name' => 'Jane Smith',
            'email' => 'jane.smith@student.claret.edu',
            'password_hash' => password_hash('Pass123', PASSWORD_BCRYPT),
        ], ['student']);
        $otherStudent = $this->studentRepo->create($this->otherStudentUser->id, 'STD-2026-002', '2010-08-20', 'female');
        $this->otherStudentId = $otherStudent->id;

        // Parent
        $this->parentUser = $this->userRepo->create([
            'uuid' => 'parent-uuid-1',
            'name' => 'Mr. Doe',
            'email' => 'mr.doe@guardian.claret.edu',
            'password_hash' => password_hash('Pass123', PASSWORD_BCRYPT),
        ], ['parent']);
        $parent = $this->parentRepo->create($this->parentUser->id);
        $this->parentId = $parent->id;
        $this->parentRepo->linkStudent($this->parentId, $this->studentId, 'father');

        // Academic Structure
        $this->pdo->exec("INSERT INTO academic_levels (name, stage, rank_order, created_at, updated_at) VALUES ('Senior Secondary 1', 'senior', 1, '{$now}', '{$now}')");
        $levelId = (int)$this->pdo->lastInsertId();

        $this->pdo->exec("INSERT INTO classes (academic_level_id, name, section_arm, status, created_at, updated_at) VALUES ({$levelId}, 'SS 1 Alpha', 'Alpha', 'active', '{$now}', '{$now}')");
        $classId = (int)$this->pdo->lastInsertId();

        $this->pdo->exec("INSERT INTO sessions (name, start_date, end_date, is_current, status, created_at, updated_at) VALUES ('2026/2027', '2026-09-01', '2027-07-20', 1, 'active', '{$now}', '{$now}')");
        $this->sessionId = (int)$this->pdo->lastInsertId();

        $this->pdo->exec("INSERT INTO terms (session_id, name, start_date, end_date, is_current, status, created_at, updated_at) VALUES ({$this->sessionId}, 'First Term', '2026-09-01', '2026-12-15', 1, 'active', '{$now}', '{$now}')");
        $this->termId = (int)$this->pdo->lastInsertId();

        $this->pdo->exec("INSERT INTO subjects (code, name, category, status, created_at, updated_at) VALUES ('MTH101', 'Mathematics', 'core', 'active', '{$now}', '{$now}')");
        $subjectId = (int)$this->pdo->lastInsertId();

        $this->pdo->exec("INSERT INTO class_subjects (session_id, class_id, subject_id, teacher_id, status, created_at, updated_at) VALUES ({$this->sessionId}, {$classId}, {$subjectId}, {$this->teacherId}, 'active', '{$now}', '{$now}')");
        $this->classSubjectId = (int)$this->pdo->lastInsertId();

        // Enroll Student 1 in Class and Subject
        $this->enrollmentRepo->enrollInClass($this->studentId, $classId, $this->sessionId);
        $this->enrollmentRepo->enrollInSubject($this->studentId, $this->classSubjectId, $this->sessionId);
    }

    private function createFakeUploadedFile(string $filename, string $content, string $mimeType): array
    {
        $tmpPath = tempnam(sys_get_temp_dir(), 'test_up_');
        file_put_contents($tmpPath, $content);

        return [
            'name' => $filename,
            'type' => $mimeType,
            'tmp_name' => $tmpPath,
            'error' => UPLOAD_ERR_OK,
            'size' => strlen($content),
        ];
    }

    public function testCompleteAssignmentLifecycleFlow(): void
    {
        $teacherContext = UserContext::fromUser($this->teacherUser);
        $otherTeacherContext = UserContext::fromUser($this->otherTeacherUser);
        $studentContext = UserContext::fromUser($this->studentUser);
        $otherStudentContext = UserContext::fromUser($this->otherStudentUser);
        $parentContext = UserContext::fromUser($this->parentUser);

        // 1. Teacher creates an assignment with reference PDF attachment
        $assignmentFile = $this->createFakeUploadedFile('calculus_worksheet.pdf', '%PDF-1.4 worksheet content', 'application/pdf');
        
        $createResult = $this->assignmentService->createAssignment([
            'class_subject_id' => $this->classSubjectId,
            'term_id' => $this->termId,
            'topic' => 'Differentiation',
            'title' => 'Product and Quotient Rules',
            'instructions' => 'Solve problems 1 through 10 in the attached worksheet.',
            'due_at' => '2026-09-30 23:59:59',
            'max_score' => 50.00,
            'status' => 'published',
        ], $assignmentFile, $teacherContext);

        $this->assertTrue($createResult->success);
        /** @var Assignment $assignment */
        $assignment = $createResult->data;
        $this->assertNotNull($assignment->id);
        $this->assertSame('Product and Quotient Rules', $assignment->title);
        $this->assertSame(50.0, $assignment->maxScore);
        $this->assertNotNull($assignment->fileId);

        // 2. Student 1 views assignments list (should see active differentiation task)
        $studentAssignments = $this->assignmentService->getStudentAssignments($studentContext);
        $this->assertCount(1, $studentAssignments['active']);
        $this->assertSame($assignment->id, $studentAssignments['active'][0]->id);

        // 3. Student 1 downloads the teacher's reference file (Protected File Delivery)
        $downloadFile = $this->fileStorageService->getFileForDownload($assignment->fileId, $studentContext);
        $this->assertSame('calculus_worksheet.pdf', $downloadFile['file']->originalName);

        // 4. Non-enrolled Student 2 attempts to submit -> Authorization Exception
        $this->expectException(AuthorizationException::class);
        $this->assignmentService->submitAssignment($assignment->id, [
            'text_response' => 'Unauthorized submission attempt'
        ], null, $otherStudentContext);
    }

    public function testStudentSubmissionAndTeacherGradingFlow(): void
    {
        $teacherContext = UserContext::fromUser($this->teacherUser);
        $studentContext = UserContext::fromUser($this->studentUser);
        $parentContext = UserContext::fromUser($this->parentUser);

        // Create published assignment
        $createResult = $this->assignmentService->createAssignment([
            'class_subject_id' => $this->classSubjectId,
            'term_id' => $this->termId,
            'topic' => 'Algebra',
            'title' => 'Quadratic Equations Task',
            'instructions' => 'Solve using quadratic formula.',
            'due_at' => '2026-10-15 18:00:00',
            'max_score' => 100.00,
            'status' => 'published',
        ], null, $teacherContext);

        $assignment = $createResult->data;

        // Student submits solution with text response and PDF upload
        $submissionFile = $this->createFakeUploadedFile('john_doe_solution.pdf', '%PDF-1.4 solution content', 'application/pdf');
        $submitResult = $this->assignmentService->submitAssignment($assignment->id, [
            'text_response' => 'All questions solved with step-by-step working.',
        ], $submissionFile, $studentContext);

        $this->assertTrue($submitResult->success);
        /** @var AssignmentSubmission $submission */
        $submission = $submitResult->data;
        $this->assertSame($this->studentId, $submission->studentId);
        $this->assertSame('All questions solved with step-by-step working.', $submission->textResponse);
        $this->assertNotNull($submission->fileId);
        $this->assertFalse($submission->isGraded());
        $this->assertFalse($submission->isLate('2026-10-15 18:00:00'));

        // Teacher grades the submission
        $gradeResult = $this->assignmentService->gradeSubmission(
            $submission->id,
            95.5,
            'Excellent working! Minor algebraic typo on question 4.',
            $teacherContext
        );

        $this->assertTrue($gradeResult->success);
        $gradedSub = $gradeResult->data;
        $this->assertTrue($gradedSub->isGraded());
        $this->assertSame(95.5, $gradedSub->score);
        $this->assertSame('Excellent working! Minor algebraic typo on question 4.', $gradedSub->teacherComment);

        // Teacher download access to student's uploaded solution
        $solutionDownload = $this->fileStorageService->getFileForDownload($submission->fileId, $teacherContext);
        $this->assertSame('john_doe_solution.pdf', $solutionDownload['file']->originalName);

        // Parent views child's coursework and sees the grade & teacher feedback
        $parentOverview = $this->assignmentService->getParentChildAssignments($this->studentId, $parentContext);
        $this->assertCount(1, $parentOverview['assignments']);
        $this->assertArrayHasKey($assignment->id, $parentOverview['submissions']);
        $parentViewedSub = $parentOverview['submissions'][$assignment->id];
        $this->assertSame(95.5, $parentViewedSub->score);
        $this->assertSame('Excellent working! Minor algebraic typo on question 4.', $parentViewedSub->teacherComment);
    }

    public function testHistoricalDataPreservationOnAssignmentDeletion(): void
    {
        $teacherContext = UserContext::fromUser($this->teacherUser);
        $studentContext = UserContext::fromUser($this->studentUser);

        // Create assignment
        $createResult = $this->assignmentService->createAssignment([
            'class_subject_id' => $this->classSubjectId,
            'term_id' => $this->termId,
            'title' => 'Assignment to Archive',
            'instructions' => 'Some task',
            'due_at' => '2026-11-01 00:00:00',
            'max_score' => 100.00,
        ], null, $teacherContext);

        $assignment = $createResult->data;

        // Student submits response
        $this->assignmentService->submitAssignment($assignment->id, [
            'text_response' => 'My response',
        ], null, $studentContext);

        // Teacher deletes assignment -> Should change status to archived to preserve student record
        $this->assignmentService->deleteAssignment($assignment->id, $teacherContext);

        $archivedAssignment = $this->assignmentRepo->findById($assignment->id);
        $this->assertNotNull($archivedAssignment);
        $this->assertSame(Assignment::STATUS_ARCHIVED, $archivedAssignment->status);
    }
}
