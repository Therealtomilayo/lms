<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Exceptions\DomainRuleException;
use App\Core\Exceptions\ValidationException;
use App\Models\AcademicSession;
use App\Models\ClassSubject;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Repositories\AcademicRepository;
use App\Repositories\TeacherRepository;
use App\Repositories\UserRepository;
use App\Services\TeacherAssignmentService;
use PDO;
use PHPUnit\Framework\TestCase;

final class TeacherAssignmentServiceTest extends TestCase
{
    private PDO $pdo;
    private UserRepository $userRepository;
    private TeacherRepository $teacherRepository;
    private AcademicRepository $academicRepository;
    private TeacherAssignmentService $service;

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
                UNIQUE (`user_id`, `role`)
            );

            CREATE TABLE `academic_levels` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `name` VARCHAR(100) NOT NULL UNIQUE,
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
                `status` VARCHAR(20) NOT NULL DEFAULT 'planning',
                `created_at` DATETIME NOT NULL,
                `updated_at` DATETIME NOT NULL
            );

            CREATE TABLE `subjects` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `name` VARCHAR(100) NOT NULL,
                `code` VARCHAR(30) NOT NULL UNIQUE,
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
                UNIQUE (`session_id`, `class_id`, `subject_id`)
            );
        ");

        $this->userRepository = new UserRepository($this->pdo);
        $this->teacherRepository = new TeacherRepository($this->pdo);
        $this->academicRepository = new AcademicRepository($this->pdo);
        $this->service = new TeacherAssignmentService(
            $this->academicRepository,
            $this->teacherRepository,
            $this->userRepository
        );
    }

    public function testCreateTeacherProfileSuccessfully(): void
    {
        $now = date('Y-m-d H:i:s');
        $this->pdo->exec("
            INSERT INTO `users` (`id`, `uuid`, `name`, `email`, `password_hash`, `status`, `created_at`, `updated_at`)
            VALUES (1, 'u1', 'Teacher Jane', 'jane@claret.edu', 'hash', 'active', '{$now}', '{$now}');
            INSERT INTO `user_roles` (`user_id`, `role`, `is_active`, `created_at`)
            VALUES (1, 'teacher', 1, '{$now}');
        ");

        $result = $this->service->createTeacher(1, 'STF-101');
        $this->assertTrue($result->isSuccess());
        $this->assertSame('STF-101', $result->getData()->staffId);
    }

    public function testCreateTeacherFailsIfUserLacksTeacherRole(): void
    {
        $now = date('Y-m-d H:i:s');
        $this->pdo->exec("
            INSERT INTO `users` (`id`, `uuid`, `name`, `email`, `password_hash`, `status`, `created_at`, `updated_at`)
            VALUES (2, 'u2', 'Student Jane', 'student@claret.edu', 'hash', 'active', '{$now}', '{$now}');
            INSERT INTO `user_roles` (`user_id`, `role`, `is_active`, `created_at`)
            VALUES (2, 'student', 1, '{$now}');
        ");

        $this->expectException(DomainRuleException::class);
        $this->service->createTeacher(2, 'STF-102');
    }

    public function testAssignTeacherToClassSubject(): void
    {
        $now = date('Y-m-d H:i:s');
        $this->pdo->exec("
            INSERT INTO `users` (`id`, `uuid`, `name`, `email`, `password_hash`, `status`, `created_at`, `updated_at`)
            VALUES (1, 'u1', 'Teacher Jane', 'jane@claret.edu', 'hash', 'active', '{$now}', '{$now}');
            INSERT INTO `user_roles` (`user_id`, `role`, `is_active`, `created_at`)
            VALUES (1, 'teacher', 1, '{$now}');
            INSERT INTO `teachers` (`id`, `user_id`, `staff_id`, `created_at`, `updated_at`)
            VALUES (1, 1, 'STF-101', '{$now}', '{$now}');

            INSERT INTO `sessions` (`id`, `name`, `start_date`, `end_date`, `status`, `created_at`, `updated_at`)
            VALUES (1, '2026/2027', '2026-09-01', '2027-07-20', 'active', '{$now}', '{$now}');

            INSERT INTO `academic_levels` (`id`, `name`, `stage`, `rank_order`, `created_at`, `updated_at`)
            VALUES (1, 'JSS 1', 'Junior Secondary', 1, '{$now}', '{$now}');

            INSERT INTO `classes` (`id`, `academic_level_id`, `name`, `section_arm`, `status`, `created_at`, `updated_at`)
            VALUES (1, 1, 'JSS 1A', 'A', 'active', '{$now}', '{$now}');

            INSERT INTO `subjects` (`id`, `name`, `code`, `status`, `created_at`, `updated_at`)
            VALUES (1, 'Mathematics', 'MTH101', 'active', '{$now}', '{$now}');
        ");

        $result = $this->service->assignTeacherToClassSubject([
            'session_id' => 1,
            'class_id' => 1,
            'subject_id' => 1,
            'teacher_id' => 1,
        ]);

        $this->assertTrue($result->isSuccess());
        /** @var ClassSubject $cs */
        $cs = $result->getData();
        $this->assertSame(1, $cs->sessionId);
        $this->assertSame(1, $cs->classId);
        $this->assertSame(1, $cs->subjectId);
        $this->assertSame(1, $cs->teacherId);
        $this->assertSame('active', $cs->status);
    }

    public function testAssignTeacherFailsOnDuplicateInSameSession(): void
    {
        $now = date('Y-m-d H:i:s');
        $this->pdo->exec("
            INSERT INTO `users` (`id`, `uuid`, `name`, `email`, `password_hash`, `status`, `created_at`, `updated_at`)
            VALUES (1, 'u1', 'Teacher Jane', 'jane@claret.edu', 'hash', 'active', '{$now}', '{$now}');
            INSERT INTO `user_roles` (`user_id`, `role`, `is_active`, `created_at`)
            VALUES (1, 'teacher', 1, '{$now}');
            INSERT INTO `teachers` (`id`, `user_id`, `staff_id`, `created_at`, `updated_at`)
            VALUES (1, 1, 'STF-101', '{$now}', '{$now}');

            INSERT INTO `sessions` (`id`, `name`, `start_date`, `end_date`, `status`, `created_at`, `updated_at`)
            VALUES (1, '2026/2027', '2026-09-01', '2027-07-20', 'active', '{$now}', '{$now}');

            INSERT INTO `academic_levels` (`id`, `name`, `stage`, `rank_order`, `created_at`, `updated_at`)
            VALUES (1, 'JSS 1', 'Junior Secondary', 1, '{$now}', '{$now}');

            INSERT INTO `classes` (`id`, `academic_level_id`, `name`, `section_arm`, `status`, `created_at`, `updated_at`)
            VALUES (1, 1, 'JSS 1A', 'A', 'active', '{$now}', '{$now}');

            INSERT INTO `subjects` (`id`, `name`, `code`, `status`, `created_at`, `updated_at`)
            VALUES (1, 'Mathematics', 'MTH101', 'active', '{$now}', '{$now}');
        ");

        $this->service->assignTeacherToClassSubject([
            'session_id' => 1,
            'class_id' => 1,
            'subject_id' => 1,
            'teacher_id' => 1,
        ]);

        $this->expectException(DomainRuleException::class);
        $this->service->assignTeacherToClassSubject([
            'session_id' => 1,
            'class_id' => 1,
            'subject_id' => 1,
            'teacher_id' => 1,
        ]);
    }

    public function testReassignTeacherAndToggleStatus(): void
    {
        $now = date('Y-m-d H:i:s');
        $this->pdo->exec("
            INSERT INTO `users` (`id`, `uuid`, `name`, `email`, `password_hash`, `status`, `created_at`, `updated_at`)
            VALUES 
                (1, 'u1', 'Teacher Jane', 'jane@claret.edu', 'hash', 'active', '{$now}', '{$now}'),
                (2, 'u2', 'Teacher Mark', 'mark@claret.edu', 'hash', 'active', '{$now}', '{$now}');

            INSERT INTO `user_roles` (`user_id`, `role`, `is_active`, `created_at`)
            VALUES 
                (1, 'teacher', 1, '{$now}'),
                (2, 'teacher', 1, '{$now}');

            INSERT INTO `teachers` (`id`, `user_id`, `staff_id`, `created_at`, `updated_at`)
            VALUES 
                (1, 1, 'STF-101', '{$now}', '{$now}'),
                (2, 2, 'STF-102', '{$now}', '{$now}');

            INSERT INTO `sessions` (`id`, `name`, `start_date`, `end_date`, `status`, `created_at`, `updated_at`)
            VALUES (1, '2026/2027', '2026-09-01', '2027-07-20', 'active', '{$now}', '{$now}');

            INSERT INTO `academic_levels` (`id`, `name`, `stage`, `rank_order`, `created_at`, `updated_at`)
            VALUES (1, 'JSS 1', 'Junior Secondary', 1, '{$now}', '{$now}');

            INSERT INTO `classes` (`id`, `academic_level_id`, `name`, `section_arm`, `status`, `created_at`, `updated_at`)
            VALUES (1, 1, 'JSS 1A', 'A', 'active', '{$now}', '{$now}');

            INSERT INTO `subjects` (`id`, `name`, `code`, `status`, `created_at`, `updated_at`)
            VALUES (1, 'Mathematics', 'MTH101', 'active', '{$now}', '{$now}');
        ");

        $assignResult = $this->service->assignTeacherToClassSubject([
            'session_id' => 1,
            'class_id' => 1,
            'subject_id' => 1,
            'teacher_id' => 1,
        ]);

        $csId = $assignResult->getData()->id;

        // Reassign to Teacher 2
        $reassignResult = $this->service->reassignTeacher($csId, 2);
        $this->assertTrue($reassignResult->isSuccess());
        $this->assertSame(2, $reassignResult->getData()->teacherId);

        // Deactivate
        $statusResult = $this->service->updateClassSubjectStatus($csId, 'inactive');
        $this->assertTrue($statusResult->isSuccess());
        $this->assertFalse($statusResult->getData()->isActive());
    }
}
