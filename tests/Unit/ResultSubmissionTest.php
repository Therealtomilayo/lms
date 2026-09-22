<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Controllers\Teacher\ResultOverviewController;
use App\Core\AuthenticatorInterface;
use App\Core\Exceptions\AuthorizationException;
use App\Core\Request;
use App\Core\UserContext;
use App\Models\AcademicLevel;
use App\Models\Classes;
use App\Models\Session as AcademicSession;
use App\Models\Teacher;
use App\Models\Term;
use App\Models\User;
use App\Repositories\AcademicRepository;
use App\Repositories\EnrollmentRepository;
use App\Repositories\GradebookRepository;
use App\Repositories\ResultPublicationRepository;
use App\Repositories\ResultSubmissionRepository;
use App\Repositories\TeacherRepository;
use PDO;
use PHPUnit\Framework\TestCase;

final class ResultSubmissionTest extends TestCase
{
    private PDO $pdo;
    private ResultSubmissionRepository $submissionRepo;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:', null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        $this->pdo->exec("
            CREATE TABLE `users` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `uuid` VARCHAR(36) NOT NULL,
                `name` VARCHAR(120) NOT NULL,
                `email` VARCHAR(150) NOT NULL UNIQUE
            );

            CREATE TABLE `teachers` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `user_id` INTEGER NOT NULL UNIQUE,
                `staff_id` VARCHAR(50) NOT NULL UNIQUE
            );

            CREATE TABLE `classes` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `name` VARCHAR(100) NOT NULL,
                `section_arm` VARCHAR(50) NULL,
                `form_teacher_id` INTEGER NULL
            );

            CREATE TABLE `terms` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `name` VARCHAR(50) NOT NULL
            );

            CREATE TABLE `class_result_submissions` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `class_id` INTEGER NOT NULL,
                `term_id` INTEGER NOT NULL,
                `submitted_by` INTEGER NOT NULL,
                `submitted_at` DATETIME NOT NULL,
                `status` VARCHAR(20) NOT NULL DEFAULT 'submitted',
                `notes` TEXT NULL,
                `reviewed_by` INTEGER NULL,
                `reviewed_at` DATETIME NULL,
                `created_at` DATETIME NOT NULL,
                `updated_at` DATETIME NOT NULL,
                UNIQUE (`class_id`, `term_id`)
            );
        ");

        $this->submissionRepo = new ResultSubmissionRepository($this->pdo);

        // Seed basic fixtures
        $this->pdo->exec("
            INSERT INTO `users` (`id`, `uuid`, `name`, `email`) VALUES (1, 'u-1', 'Teacher John', 'john@claret.test');
            INSERT INTO `users` (`id`, `uuid`, `name`, `email`) VALUES (2, 'u-2', 'Admin User', 'admin@claret.test');
            INSERT INTO `teachers` (`id`, `user_id`, `staff_id`) VALUES (1, 1, 'STF/001');
            INSERT INTO `classes` (`id`, `name`, `section_arm`, `form_teacher_id`) VALUES (1, 'Junior Secondary 1', 'A', 1);
            INSERT INTO `terms` (`id`, `name`) VALUES (1, 'First Term');
        ");
    }

    public function testSubmitAndFindClassResultSubmission(): void
    {
        $submission = $this->submissionRepo->submit(1, 1, 1, 'All CA and exams compiled successfully.');

        $this->assertNotNull($submission);
        $this->assertSame(1, $submission->classId);
        $this->assertSame(1, $submission->termId);
        $this->assertSame(1, $submission->submittedBy);
        $this->assertSame('submitted', $submission->status);
        $this->assertSame('All CA and exams compiled successfully.', $submission->notes);
        $this->assertSame('Teacher John', $submission->teacherName);
        $this->assertSame('Junior Secondary 1', $submission->className);
        $this->assertSame('A', $submission->sectionArm);
        $this->assertSame('First Term', $submission->termName);
        $this->assertTrue($submission->isSubmitted());
        $this->assertFalse($submission->isApproved());

        $found = $this->submissionRepo->findSubmission(1, 1);
        $this->assertNotNull($found);
        $this->assertSame($submission->id, $found->id);
    }

    public function testApproveAndRejectSubmission(): void
    {
        $this->submissionRepo->submit(1, 1, 1, 'Initial submission.');

        $approved = $this->submissionRepo->approve(1, 1, 2);
        $this->assertTrue($approved);

        $found = $this->submissionRepo->findSubmission(1, 1);
        $this->assertNotNull($found);
        $this->assertTrue($found->isApproved());
        $this->assertSame(2, $found->reviewedBy);
        $this->assertNotNull($found->reviewedAt);

        $rejected = $this->submissionRepo->reject(1, 1, 2, 'Need corrections in basic science scores.');
        $this->assertTrue($rejected);

        $foundAgain = $this->submissionRepo->findSubmission(1, 1);
        $this->assertNotNull($foundAgain);
        $this->assertTrue($foundAgain->isRejected());
        $this->assertSame('Need corrections in basic science scores.', $foundAgain->notes);
    }

    public function testGetSubmissionsByTerm(): void
    {
        $this->submissionRepo->submit(1, 1, 1, 'Class 1 submitted');

        $map = $this->submissionRepo->getSubmissionsByTerm(1);
        $this->assertArrayHasKey(1, $map);
        $this->assertSame('submitted', $map[1]->status);
    }
}
