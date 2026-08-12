<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Core\UserContext;
use App\Models\AcademicSession;
use App\Models\ClassEnrollment;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\User;
use App\Policies\AcademicPolicy;
use App\Repositories\AcademicRepository;
use App\Repositories\EnrollmentRepository;
use App\Repositories\ImportRepository;
use App\Repositories\ParentRepository;
use App\Repositories\StudentRepository;
use App\Repositories\TeacherRepository;
use App\Repositories\UserRepository;
use App\Services\EnrollmentService;
use App\Services\GuardianService;
use App\Services\ImportService;
use App\Services\TeacherAssignmentService;
use App\Services\UserService;
use PDO;
use PHPUnit\Framework\TestCase;

final class StudentEnrollmentFlowIntegrationTest extends TestCase
{
    private PDO $pdo;
    private UserRepository $userRepo;
    private StudentRepository $studentRepo;
    private ParentRepository $parentRepo;
    private TeacherRepository $teacherRepo;
    private AcademicRepository $academicRepo;
    private EnrollmentRepository $enrollmentRepo;
    private ImportRepository $importRepo;

    private UserService $userService;
    private EnrollmentService $enrollmentService;
    private GuardianService $guardianService;
    private ImportService $importService;

    private User $adminUser;

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

            CREATE TABLE `imports` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `uploaded_by` INTEGER NOT NULL,
                `type` VARCHAR(50) NOT NULL,
                `original_name` VARCHAR(255) NOT NULL,
                `sha256` VARCHAR(64) NOT NULL,
                `status` VARCHAR(20) NOT NULL DEFAULT 'validated',
                `total_rows` INTEGER NOT NULL DEFAULT 0,
                `valid_rows` INTEGER NOT NULL DEFAULT 0,
                `invalid_rows` INTEGER NOT NULL DEFAULT 0,
                `committed_at` DATETIME NULL,
                `created_at` DATETIME NOT NULL
            );

            CREATE TABLE `import_errors` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `import_id` INTEGER NOT NULL,
                `row_number` INTEGER NOT NULL,
                `raw_data_json` TEXT NOT NULL,
                `errors_json` TEXT NOT NULL,
                `created_at` DATETIME NOT NULL
            );
        ");

        $this->userRepo = new UserRepository($this->pdo);
        $this->studentRepo = new StudentRepository($this->pdo);
        $this->parentRepo = new ParentRepository($this->pdo);
        $this->teacherRepo = new TeacherRepository($this->pdo);
        $this->academicRepo = new AcademicRepository($this->pdo);
        $this->enrollmentRepo = new EnrollmentRepository($this->pdo);
        $this->importRepo = new ImportRepository($this->pdo);

        $this->userService = new UserService($this->userRepo, $this->studentRepo, $this->teacherRepo, $this->parentRepo);
        $this->enrollmentService = new EnrollmentService($this->enrollmentRepo, $this->studentRepo, $this->academicRepo);
        $this->guardianService = new GuardianService($this->parentRepo, $this->studentRepo, $this->userRepo);
        $this->importService = new ImportService(
            $this->importRepo,
            $this->userRepo,
            $this->studentRepo,
            $this->teacherRepo,
            $this->parentRepo,
            $this->academicRepo,
            $this->enrollmentRepo,
            $this->pdo
        );

        $now = date('Y-m-d H:i:s');
        $this->adminUser = $this->userRepo->create([
            'uuid' => 'admin-uuid-001',
            'name' => 'School Admin',
            'email' => 'admin@claret.edu.ng',
            'password_hash' => 'hash',
            'status' => 'active',
            'must_change_password' => 0,
        ], ['admin']);
    }

    public function testEndToEndStudentOnboardingAndEnrollment(): void
    {
        $actor = UserContext::fromUser($this->adminUser);

        // 1. Setup Academic Foundation
        $level = $this->academicRepo->createLevel(['name' => 'Junior Secondary', 'stage' => 'secondary', 'rank_order' => 1]);
        $class = $this->academicRepo->createClass(['academic_level_id' => $level->id, 'name' => 'JSS 1 Gold', 'section_arm' => 'Gold']);
        $session = $this->academicRepo->createSession(['name' => '2026/2027', 'start_date' => '2026-09-01', 'end_date' => '2027-07-31', 'status' => 'active']);
        $subject = $this->academicRepo->createSubject(['code' => 'MTH101', 'name' => 'Mathematics', 'category' => 'General']);

        // Create teacher
        $teacherUser = $this->userRepo->create([
            'uuid' => 'tch-uuid',
            'name' => 'Mr. Maths Teacher',
            'email' => 'maths@claret.edu',
            'password_hash' => 'hash',
            'status' => 'active',
        ], ['teacher']);
        $teacher = $this->teacherRepo->createTeacher($teacherUser->id, 'TCH-001');

        // Map class subject
        $classSubject = $this->academicRepo->createClassSubject([
            'session_id' => $session->id,
            'class_id' => $class->id,
            'subject_id' => $subject->id,
            'teacher_id' => $teacher->id,
            'status' => 'active',
        ]);

        // 2. Create Student Account via UserService
        $resStudent = $this->userService->createUser([
            'name' => 'Emeka Okafor',
            'email' => 'emeka@claret.edu.ng',
            'password' => 'Password123!',
            'roles' => ['student'],
            'admission_number' => 'STD-2026-001',
            'gender' => 'male',
        ], $actor);
        $this->assertTrue($resStudent->isSuccess());

        $student = $this->studentRepo->findByAdmissionNumber('STD-2026-001');
        $this->assertNotNull($student);
        $this->assertSame('Emeka Okafor', $student->user?->name);

        // 3. Create Parent Account and Link to Student
        $resParent = $this->userService->createUser([
            'name' => 'Chief Okafor',
            'email' => 'chief.okafor@gmail.com',
            'password' => 'Password123!',
            'roles' => ['parent'],
        ], $actor);
        $this->assertTrue($resParent->isSuccess());

        $parent = $this->parentRepo->findByUserId($resParent->getData()->id);
        $this->assertNotNull($parent);

        $resLink = $this->guardianService->linkGuardian($parent->id, $student->id, 'Father');
        $this->assertTrue($resLink->isSuccess());

        // Verify parent policy
        $this->assertTrue(AcademicPolicy::parentCanViewStudent($parent->id, $student->id, $this->parentRepo));

        // 4. Enroll Student in Class
        $resEnroll = $this->enrollmentService->enrollStudentInClass($student->id, $class->id, $session->id);
        $this->assertTrue($resEnroll->isSuccess());

        // Verify Student is in Roster
        $roster = $this->enrollmentRepo->getClassRoster($class->id, $session->id);
        $this->assertCount(1, $roster);
        $this->assertSame('Emeka Okafor', $roster[0]->student?->user?->name);

        // Verify Auto-Enrolled into Subject MTH101
        $this->assertTrue(
            AcademicPolicy::studentCanAccessClassSubject($student->id, $classSubject->id, $session->id, $this->enrollmentRepo)
        );

        $subjectEnrollments = $this->enrollmentRepo->getStudentSubjectEnrollments($student->id, $session->id);
        $this->assertCount(1, $subjectEnrollments);
        $this->assertSame('Mathematics', $subjectEnrollments[0]->classSubject?->subject?->name);
    }

    public function testBulkCsvImportCommit(): void
    {
        $actor = UserContext::fromUser($this->adminUser);

        $csv = "name,email,admission_number,gender\n" .
               "Student One,st1@claret.edu.ng,STD-BULK-001,male\n" .
               "Student Two,st2@claret.edu.ng,STD-BULK-002,female\n";

        $valResult = $this->importService->validateCsv($csv, 'students', 'students.csv', $this->adminUser->id);
        $this->assertTrue($valResult->isSuccess());

        $data = $valResult->getData();
        $batchId = $data['batch']->id;
        $validRows = $data['valid_rows'];

        $this->assertCount(2, $validRows);

        $commitResult = $this->importService->commitImport($batchId, $validRows, $actor);
        $this->assertTrue($commitResult->isSuccess());

        $st1 = $this->studentRepo->findByAdmissionNumber('STD-BULK-001');
        $st2 = $this->studentRepo->findByAdmissionNumber('STD-BULK-002');

        $this->assertNotNull($st1);
        $this->assertNotNull($st2);
        $this->assertSame('Student One', $st1->user?->name);
        $this->assertSame('Student Two', $st2->user?->name);
    }
}
