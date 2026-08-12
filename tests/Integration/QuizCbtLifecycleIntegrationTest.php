<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Core\Exceptions\AuthorizationException;
use App\Core\Exceptions\DomainRuleException;
use App\Core\Exceptions\ValidationException;
use App\Core\UserContext;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use App\Policies\QuestionPolicy;
use App\Policies\QuizPolicy;
use App\Repositories\AcademicRepository;
use App\Repositories\EnrollmentRepository;
use App\Repositories\ParentRepository;
use App\Repositories\QuestionBankRepository;
use App\Repositories\QuizRepository;
use App\Repositories\StudentRepository;
use App\Repositories\TeacherRepository;
use App\Repositories\UserRepository;
use App\Services\EnrollmentService;
use App\Services\GuardianService;
use App\Services\QuestionBankService;
use App\Services\QuizService;
use App\Services\UserService;
use PDO;
use PHPUnit\Framework\TestCase;

final class QuizCbtLifecycleIntegrationTest extends TestCase
{
    private PDO $pdo;
    private UserRepository $userRepo;
    private StudentRepository $studentRepo;
    private ParentRepository $parentRepo;
    private TeacherRepository $teacherRepo;
    private AcademicRepository $academicRepo;
    private EnrollmentRepository $enrollmentRepo;
    private QuestionBankRepository $questionBankRepo;
    private QuizRepository $quizRepo;

    private UserService $userService;
    private EnrollmentService $enrollmentService;
    private GuardianService $guardianService;
    private QuestionBankService $questionBankService;
    private QuizService $quizService;

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
    private int $subjectId;
    private int $classSubjectId;

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

            CREATE TABLE `questions` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `subject_id` INTEGER NOT NULL,
                `topic` VARCHAR(150) NULL,
                `question_text` TEXT NOT NULL,
                `type` VARCHAR(30) NOT NULL DEFAULT 'mcq',
                `default_points` DECIMAL(5,2) NOT NULL DEFAULT 1.00,
                `created_by` INTEGER NOT NULL,
                `created_at` DATETIME NOT NULL,
                `updated_at` DATETIME NOT NULL
            );

            CREATE TABLE `question_options` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `question_id` INTEGER NOT NULL,
                `option_text` TEXT NOT NULL,
                `is_correct` INTEGER NOT NULL DEFAULT 0,
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
                `is_published` INTEGER NOT NULL DEFAULT 0,
                `published_at` DATETIME NULL,
                `created_at` DATETIME NOT NULL,
                `updated_at` DATETIME NOT NULL
            );

            CREATE TABLE `quiz_questions` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `quiz_id` INTEGER NOT NULL,
                `question_id` INTEGER NOT NULL,
                `points` DECIMAL(5,2) NOT NULL DEFAULT 1.00,
                `sort_order` INTEGER NOT NULL DEFAULT 0,
                `created_at` DATETIME NOT NULL,
                `updated_at` DATETIME NOT NULL,
                UNIQUE(`quiz_id`, `question_id`)
            );

            CREATE TABLE `quiz_attempts` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `uuid` VARCHAR(36) NOT NULL UNIQUE,
                `quiz_id` INTEGER NOT NULL,
                `student_id` INTEGER NOT NULL,
                `attempt_number` INTEGER NOT NULL DEFAULT 1,
                `started_at` DATETIME NOT NULL,
                `submitted_at` DATETIME NULL,
                `score` DECIMAL(5,2) NULL,
                `max_score` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
                `status` VARCHAR(30) NOT NULL DEFAULT 'in_progress',
                `created_at` DATETIME NOT NULL,
                `updated_at` DATETIME NOT NULL,
                UNIQUE(`quiz_id`, `student_id`, `attempt_number`)
            );

            CREATE TABLE `quiz_answers` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `attempt_id` INTEGER NOT NULL,
                `question_id` INTEGER NOT NULL,
                `selected_option_id` INTEGER NULL,
                `text_answer` TEXT NULL,
                `points_awarded` DECIMAL(5,2) NULL,
                `teacher_comment` TEXT NULL,
                `created_at` DATETIME NOT NULL,
                `updated_at` DATETIME NOT NULL,
                UNIQUE(`attempt_id`, `question_id`)
            );
        ");

        $this->userRepo = new UserRepository($this->pdo);
        $this->studentRepo = new StudentRepository($this->pdo);
        $this->parentRepo = new ParentRepository($this->pdo);
        $this->teacherRepo = new TeacherRepository($this->pdo);
        $this->academicRepo = new AcademicRepository($this->pdo);
        $this->enrollmentRepo = new EnrollmentRepository($this->pdo);
        $this->questionBankRepo = new QuestionBankRepository($this->pdo);
        $this->quizRepo = new QuizRepository($this->pdo);

        $this->userService = new UserService($this->userRepo, $this->studentRepo, $this->teacherRepo, $this->parentRepo);
        $this->enrollmentService = new EnrollmentService($this->enrollmentRepo, $this->studentRepo, $this->academicRepo);
        $this->guardianService = new GuardianService($this->parentRepo, $this->studentRepo, $this->userRepo);
        $this->questionBankService = new QuestionBankService($this->questionBankRepo, $this->academicRepo, $this->teacherRepo);
        $this->quizService = new QuizService(
            quizRepository: $this->quizRepo,
            questionBankRepository: $this->questionBankRepo,
            academicRepository: $this->academicRepo,
            teacherRepository: $this->teacherRepo,
            studentRepository: $this->studentRepo,
            enrollmentRepository: $this->enrollmentRepo,
            parentRepository: $this->parentRepo
        );

        $this->seedTestData();
    }

    private function seedTestData(): void
    {
        $now = date('Y-m-d H:i:s');

        // Admin
        $this->adminUser = $this->userRepo->create([
            'uuid' => 'admin-cbt-uuid',
            'name' => 'Admin Boss',
            'email' => 'admin@claret.edu',
            'password_hash' => password_hash('Pass123', PASSWORD_BCRYPT),
        ], ['admin']);

        // Teachers
        $this->teacherUser = $this->userRepo->create([
            'uuid' => 'teacher-cbt-uuid-1',
            'name' => 'Dr. Faraday',
            'email' => 'faraday@claret.edu',
            'password_hash' => password_hash('Pass123', PASSWORD_BCRYPT),
        ], ['teacher']);
        $teacher = $this->teacherRepo->createTeacher($this->teacherUser->id, 'STF001');
        $this->teacherId = $teacher->id;

        $this->otherTeacherUser = $this->userRepo->create([
            'uuid' => 'teacher-cbt-uuid-2',
            'name' => 'Prof. Turing',
            'email' => 'turing@claret.edu',
            'password_hash' => password_hash('Pass123', PASSWORD_BCRYPT),
        ], ['teacher']);
        $otherTeacher = $this->teacherRepo->createTeacher($this->otherTeacherUser->id, 'STF002');
        $this->otherTeacherId = $otherTeacher->id;

        // Students
        $this->studentUser = $this->userRepo->create([
            'uuid' => 'student-cbt-uuid-1',
            'name' => 'Ada Lovelace',
            'email' => 'ada@student.claret.edu',
            'password_hash' => password_hash('Pass123', PASSWORD_BCRYPT),
        ], ['student']);
        $student = $this->studentRepo->create($this->studentUser->id, 'ADM001', '2010-01-01', 'female');
        $this->studentId = $student->id;

        $this->otherStudentUser = $this->userRepo->create([
            'uuid' => 'student-cbt-uuid-2',
            'name' => 'Charles Babbage',
            'email' => 'babbage@student.claret.edu',
            'password_hash' => password_hash('Pass123', PASSWORD_BCRYPT),
        ], ['student']);
        $otherStudent = $this->studentRepo->create($this->otherStudentUser->id, 'ADM002', '2010-02-02', 'male');
        $this->otherStudentId = $otherStudent->id;

        // Parent
        $this->parentUser = $this->userRepo->create([
            'uuid' => 'parent-cbt-uuid-1',
            'name' => 'Lord Byron',
            'email' => 'byron@parent.claret.edu',
            'password_hash' => password_hash('Pass123', PASSWORD_BCRYPT),
        ], ['parent']);
        $parent = $this->parentRepo->create($this->parentUser->id);
        $this->parentId = $parent->id;
        $this->parentRepo->linkStudent($this->parentId, $this->studentId, 'father');

        // Academic Structure
        $this->pdo->exec("INSERT INTO academic_levels (name, stage, rank_order, created_at, updated_at) VALUES ('Senior Secondary', 'high_school', 1, '{$now}', '{$now}')");
        $levelId = (int)$this->pdo->lastInsertId();

        $this->pdo->exec("INSERT INTO classes (academic_level_id, name, section_arm, status, created_at, updated_at) VALUES ({$levelId}, 'SS 3 Physics', 'Alpha', 'active', '{$now}', '{$now}')");
        $classId = (int)$this->pdo->lastInsertId();

        $this->pdo->exec("INSERT INTO sessions (name, start_date, end_date, is_current, status, created_at, updated_at) VALUES ('2026/2027', '2026-09-01', '2027-07-31', 1, 'active', '{$now}', '{$now}')");
        $this->sessionId = (int)$this->pdo->lastInsertId();

        $this->pdo->exec("INSERT INTO terms (session_id, name, start_date, end_date, is_current, status, created_at, updated_at) VALUES ({$this->sessionId}, 'First Term', '2026-09-01', '2026-12-15', 1, 'active', '{$now}', '{$now}')");
        $this->termId = (int)$this->pdo->lastInsertId();

        $this->pdo->exec("INSERT INTO subjects (code, name, category, status, created_at, updated_at) VALUES ('PHY301', 'Physics', 'Science', 'active', '{$now}', '{$now}')");
        $this->subjectId = (int)$this->pdo->lastInsertId();

        $this->pdo->exec("INSERT INTO class_subjects (session_id, class_id, subject_id, teacher_id, status, created_at, updated_at) VALUES ({$this->sessionId}, {$classId}, {$this->subjectId}, {$this->teacherId}, 'active', '{$now}', '{$now}')");
        $this->classSubjectId = (int)$this->pdo->lastInsertId();

        // Enroll Ada in Physics (Charles remains unenrolled for RBAC testing)
        $this->enrollmentRepo->enrollInSubject($this->studentId, $this->classSubjectId, $this->sessionId);
    }

    public function testCompleteQuestionBankAndQuizCbtLifecycle(): void
    {
        $teacherContext = UserContext::fromUser($this->teacherUser);
        $otherTeacherContext = UserContext::fromUser($this->otherTeacherUser);
        $studentContext = UserContext::fromUser($this->studentUser);
        $otherStudentContext = UserContext::fromUser($this->otherStudentUser);
        $parentContext = UserContext::fromUser($this->parentUser);
        $adminContext = UserContext::fromUser($this->adminUser);

        // =====================================================================
        // STEP 1: Teacher 1 Populates Reusable Question Bank
        // =====================================================================

        // MCQ Question 1
        $q1Res = $this->questionBankService->createQuestion([
            'subject_id' => $this->subjectId,
            'topic' => 'Electricity',
            'question_text' => 'What is the SI unit of electric current?',
            'type' => Question::TYPE_MCQ,
            'default_points' => 2.50,
            'options' => [
                ['option_text' => 'Volt', 'is_correct' => false],
                ['option_text' => 'Ampere', 'is_correct' => true],
                ['option_text' => 'Ohm', 'is_correct' => false],
                ['option_text' => 'Watt', 'is_correct' => false],
            ],
        ], $teacherContext);
        $this->assertTrue($q1Res->success);
        $q1 = $q1Res->data;

        // MCQ Question 2
        $q2Res = $this->questionBankService->createQuestion([
            'subject_id' => $this->subjectId,
            'topic' => 'Optics',
            'question_text' => 'Which lens is thicker in the middle than at the edges?',
            'type' => Question::TYPE_MCQ,
            'default_points' => 2.50,
            'options' => [
                ['option_text' => 'Convex lens', 'is_correct' => true],
                ['option_text' => 'Concave lens', 'is_correct' => false],
                ['option_text' => 'Plano-concave lens', 'is_correct' => false],
            ],
        ], $teacherContext);
        $this->assertTrue($q2Res->success);
        $q2 = $q2Res->data;

        // Short Answer Question 3
        $q3Res = $this->questionBankService->createQuestion([
            'subject_id' => $this->subjectId,
            'topic' => 'Thermodynamics',
            'question_text' => 'State the first law of thermodynamics in your own words.',
            'type' => Question::TYPE_SHORT_ANSWER,
            'default_points' => 5.00,
        ], $teacherContext);
        $this->assertTrue($q3Res->success);
        $q3 = $q3Res->data;

        // Verify unassigned teacher cannot modify Teacher 1's question
        $this->expectException(AuthorizationException::class);
        $this->questionBankService->updateQuestion($q1->id, [
            'question_text' => 'Hacked text',
            'type' => Question::TYPE_MCQ,
            'default_points' => 1.00,
            'options' => [
                ['option_text' => 'A', 'is_correct' => true],
                ['option_text' => 'B', 'is_correct' => false],
            ],
        ], $otherTeacherContext);
    }

    public function testQuizCompositionPublishingAndStudentExecution(): void
    {
        $teacherContext = UserContext::fromUser($this->teacherUser);
        $otherTeacherContext = UserContext::fromUser($this->otherTeacherUser);
        $studentContext = UserContext::fromUser($this->studentUser);
        $otherStudentContext = UserContext::fromUser($this->otherStudentUser);
        $parentContext = UserContext::fromUser($this->parentUser);

        // Create 2 MCQs and 1 Short Answer
        $q1 = $this->questionBankService->createQuestion([
            'subject_id' => $this->subjectId,
            'topic' => 'Electricity',
            'question_text' => 'What is the SI unit of electric current?',
            'type' => Question::TYPE_MCQ,
            'default_points' => 2.50,
            'options' => [
                ['option_text' => 'Volt', 'is_correct' => false],
                ['option_text' => 'Ampere', 'is_correct' => true],
                ['option_text' => 'Ohm', 'is_correct' => false],
            ],
        ], $teacherContext)->data;

        $q2 = $this->questionBankService->createQuestion([
            'subject_id' => $this->subjectId,
            'topic' => 'Optics',
            'question_text' => 'Which lens is converging?',
            'type' => Question::TYPE_MCQ,
            'default_points' => 2.50,
            'options' => [
                ['option_text' => 'Convex', 'is_correct' => true],
                ['option_text' => 'Concave', 'is_correct' => false],
            ],
        ], $teacherContext)->data;

        $q3 = $this->questionBankService->createQuestion([
            'subject_id' => $this->subjectId,
            'topic' => 'Thermodynamics',
            'question_text' => 'Define specific heat capacity.',
            'type' => Question::TYPE_SHORT_ANSWER,
            'default_points' => 5.00,
        ], $teacherContext)->data;

        // =====================================================================
        // STEP 2: Teacher Creates & Configures Quiz Assessment
        // =====================================================================
        $quizRes = $this->quizService->createQuiz([
            'class_subject_id' => $this->classSubjectId,
            'term_id' => $this->termId,
            'title' => 'Physics Mid-Term CBT Exam',
            'instructions' => 'No calculators permitted.',
            'time_limit_minutes' => 30,
            'max_attempts' => 2,
        ], $teacherContext);

        $this->assertTrue($quizRes->success);
        $quiz = $quizRes->data;

        // Attach questions with custom points
        $attachRes = $this->quizService->setQuestions($quiz->id, [
            ['question_id' => $q1->id, 'points' => 2.50, 'sort_order' => 1],
            ['question_id' => $q2->id, 'points' => 2.50, 'sort_order' => 2],
            ['question_id' => $q3->id, 'points' => 5.00, 'sort_order' => 3],
        ], $teacherContext);
        $this->assertTrue($attachRes->success);
        $this->assertSame(10.00, $attachRes->data->getTotalMaxScore());

        // Publish Quiz
        $pubRes = $this->quizService->publishQuiz($quiz->id, true, $teacherContext);
        $this->assertTrue($pubRes->success);
        $this->assertTrue($pubRes->data->isPublished());

        // =====================================================================
        // STEP 3: Student Eligibility Checks
        // =====================================================================
        // Charles (unenrolled) is rejected
        try {
            $this->quizService->startAttempt($quiz->id, $otherStudentContext);
            $this->fail('Unenrolled student should not be able to start quiz');
        } catch (AuthorizationException $e) {
            $this->assertStringContainsString('cannot start', $e->getMessage());
        }

        // Ada (enrolled) starts Attempt #1
        $startRes = $this->quizService->startAttempt($quiz->id, $studentContext);
        $this->assertTrue($startRes->success);
        $attempt1 = $startRes->data;
        $this->assertSame(1, $attempt1->attemptNumber);
        $this->assertSame(10.00, $attempt1->maxScore);
        $this->assertTrue($attempt1->isInProgress());

        // =====================================================================
        // STEP 4: CBT Exam Player Sanitization & Autosaving
        // =====================================================================
        $playerData = $this->quizService->getAttemptForPlayer($attempt1->id, $studentContext);
        $this->assertCount(3, $playerData['questions']);
        // Verify is_correct is stripped from all options for student security
        foreach ($playerData['questions'] as $q) {
            foreach ($q['options'] as $opt) {
                $this->assertArrayNotHasKey('is_correct', $opt);
            }
        }

        // Find correct option for Q1 (Ampere)
        $q1Full = $this->questionBankRepo->findById($q1->id);
        $q1CorrectOpt = $q1Full->getCorrectOption();

        // Autosave answer for Q1
        $save1 = $this->quizService->autosaveAnswer(
            attemptId: $attempt1->id,
            questionId: $q1->id,
            selectedOptionId: $q1CorrectOpt->id,
            textAnswer: null,
            userContext: $studentContext
        );
        $this->assertTrue($save1->success);

        // Autosave short-answer for Q3
        $save3 = $this->quizService->autosaveAnswer(
            attemptId: $attempt1->id,
            questionId: $q3->id,
            selectedOptionId: null,
            textAnswer: 'Energy required to raise 1kg of a substance by 1 Kelvin.',
            userContext: $studentContext
        );
        $this->assertTrue($save3->success);

        // =====================================================================
        // STEP 5: Final Submission & Auto-Grading of Attempt #1
        // =====================================================================
        // Submit attempt with Q2 intentionally left blank or answered wrong
        $submitRes = $this->quizService->submitAttempt($attempt1->id, [], $studentContext);
        $this->assertTrue($submitRes->success);
        $submittedAttempt = $submitRes->data;

        // Auto-graded Q1 = 2.50, Q2 = 0.00, Q3 = pending. Total auto-score = 2.50.
        // Status should be 'submitted' because Q3 requires manual short-answer grading.
        $this->assertSame('submitted', $submittedAttempt->status);
        $this->assertSame(2.50, $submittedAttempt->score);

        // =====================================================================
        // STEP 6: Teacher Manual Grading Queue
        // =====================================================================
        $attemptOverview = $this->quizService->getQuizAttempts($quiz->id, $teacherContext);
        $this->assertCount(1, $attemptOverview['attempts']);

        // Find answer ID for Q3
        $answers = $this->quizRepo->getAnswersByAttemptId($attempt1->id);
        $q3Answer = null;
        foreach ($answers as $a) {
            if ($a->questionId === $q3->id) {
                $q3Answer = $a;
            }
        }
        $this->assertNotNull($q3Answer);

        // Teacher awards 4.50 / 5.00 pts
        $gradeRes = $this->quizService->gradeAttemptShortAnswers($attempt1->id, [
            [
                'answer_id' => $q3Answer->id,
                'points_awarded' => 4.50,
                'teacher_comment' => 'Accurate definition, well stated.',
            ]
        ], $teacherContext);

        $this->assertTrue($gradeRes->success);
        $gradedAttempt = $gradeRes->data;

        // Final score: 2.50 (MCQ 1) + 0.00 (MCQ 2) + 4.50 (Short Answer 3) = 7.00 / 10.00
        $this->assertSame('graded', $gradedAttempt->status);
        $this->assertSame(7.00, $gradedAttempt->score);

        // =====================================================================
        // STEP 7: Student & Parent Result Breakdown Access
        // =====================================================================
        $studentResult = $this->quizService->getAttemptResult($attempt1->id, $studentContext);
        $this->assertSame(7.00, $studentResult['attempt']->score);
        $this->assertCount(3, $studentResult['answers']);

        $parentResult = $this->quizService->getAttemptResult($attempt1->id, $parentContext);
        $this->assertSame(7.00, $parentResult['attempt']->score);

        // =====================================================================
        // STEP 8: Attempt #2 and Maximum Attempts Enforcement
        // =====================================================================
        // Student starts Attempt #2
        $start2 = $this->quizService->startAttempt($quiz->id, $studentContext);
        $this->assertTrue($start2->success);
        $attempt2 = $start2->data;
        $this->assertSame(2, $attempt2->attemptNumber);

        // Submit Attempt #2
        $this->quizService->submitAttempt($attempt2->id, [], $studentContext);

        // Student tries to start Attempt #3 (Limit is 2) -> must be rejected
        try {
            $this->quizService->startAttempt($quiz->id, $studentContext);
            $this->fail('Student should not be able to exceed max attempts');
        } catch (AuthorizationException $e) {
            $this->assertStringContainsString('cannot start', $e->getMessage());
        }

        // =====================================================================
        // STEP 9: Teacher Reset Action
        // =====================================================================
        // Teacher resets Attempt #2
        $resetRes = $this->quizService->resetStudentAttempt($attempt2->id, $teacherContext);
        $this->assertTrue($resetRes->success);

        // Student can now take attempt #2 again
        $retryRes = $this->quizService->startAttempt($quiz->id, $studentContext);
        $this->assertTrue($retryRes->success);
        $this->assertSame(2, $retryRes->data->attemptNumber);
    }
}
