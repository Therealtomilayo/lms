<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\UserContext;
use App\Models\ClassSubject;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\Term;
use App\Models\User;
use App\Policies\QuestionPolicy;
use App\Policies\QuizPolicy;
use App\Repositories\AcademicRepository;
use App\Repositories\EnrollmentRepository;
use App\Repositories\QuizRepository;
use App\Repositories\StudentRepository;
use App\Repositories\TeacherRepository;
use PHPUnit\Framework\TestCase;

final class QuizPolicyTest extends TestCase
{
    private UserContext $adminContext;
    private UserContext $teacherContext;
    private UserContext $otherTeacherContext;
    private UserContext $studentContext;
    private UserContext $otherStudentContext;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminContext = UserContext::fromUser(new User(id: 1, uuid: 'u1', name: 'Admin', email: 'admin@claret.edu', passwordHash: 'x', roles: ['admin']));
        $this->teacherContext = UserContext::fromUser(new User(id: 2, uuid: 'u2', name: 'Teacher 1', email: 't1@claret.edu', passwordHash: 'x', roles: ['teacher']));
        $this->otherTeacherContext = UserContext::fromUser(new User(id: 3, uuid: 'u3', name: 'Teacher 2', email: 't2@claret.edu', passwordHash: 'x', roles: ['teacher']));
        $this->studentContext = UserContext::fromUser(new User(id: 4, uuid: 'u4', name: 'Student 1', email: 's1@claret.edu', passwordHash: 'x', roles: ['student']));
        $this->otherStudentContext = UserContext::fromUser(new User(id: 5, uuid: 'u5', name: 'Student 2', email: 's2@claret.edu', passwordHash: 'x', roles: ['student']));
    }

    public function testQuestionBankManagementAuthorization(): void
    {
        $teacher = new Teacher(id: 10, userId: 2, staffId: 'T001', createdAt: 'now');
        $otherTeacher = new Teacher(id: 11, userId: 3, staffId: 'T002', createdAt: 'now');

        $assignedClassSubject = new ClassSubject(id: 50, sessionId: 1, classId: 5, subjectId: 20, teacherId: 10, status: 'active');

        $teacherRepo = $this->createMock(TeacherRepository::class);
        $teacherRepo->method('findTeacherByUserId')->willReturnCallback(fn($uid) => match ($uid) {
            2 => $teacher,
            3 => $otherTeacher,
            default => null,
        });

        $academicRepo = $this->createMock(AcademicRepository::class);
        $academicRepo->method('findClassSubjectsByTeacherId')->willReturnCallback(fn($tid) => match ($tid) {
            10 => [$assignedClassSubject],
            default => [],
        });

        // Admin can manage any subject question bank
        $this->assertTrue(QuestionPolicy::canManageQuestionBank($this->adminContext, 20, $teacherRepo, $academicRepo));

        // Teacher can manage assigned subject (20) but not unassigned (21)
        $this->assertTrue(QuestionPolicy::canManageQuestionBank($this->teacherContext, 20, $teacherRepo, $academicRepo));
        $this->assertFalse(QuestionPolicy::canManageQuestionBank($this->teacherContext, 21, $teacherRepo, $academicRepo));

        // Other teacher cannot manage subject 20
        $this->assertFalse(QuestionPolicy::canManageQuestionBank($this->otherTeacherContext, 20, $teacherRepo, $academicRepo));

        // Student cannot manage question bank
        $this->assertFalse(QuestionPolicy::canManageQuestionBank($this->studentContext, 20, $teacherRepo, $academicRepo));
    }

    public function testQuizCreationAndCrossSessionInvariant(): void
    {
        $teacher = new Teacher(id: 10, userId: 2, staffId: 'T001', createdAt: 'now');
        $classSubject = new ClassSubject(id: 50, sessionId: 1, classId: 5, subjectId: 20, teacherId: 10, status: 'active');
        $term = new Term(id: 2, sessionId: 1, name: 'Term 1', startDate: '2026-01-01', endDate: '2026-04-01', status: 'active');
        $otherSessionTerm = new Term(id: 3, sessionId: 2, name: 'Term 2 in session 2', startDate: '2027-01-01', endDate: '2027-04-01', status: 'active');

        $teacherRepo = $this->createMock(TeacherRepository::class);
        $teacherRepo->method('findTeacherByUserId')->willReturnCallback(fn($uid) => match ($uid) {
            2 => $teacher,
            default => null,
        });

        $academicRepo = $this->createMock(AcademicRepository::class);
        $academicRepo->method('findClassSubjectById')->willReturnCallback(fn($id) => $id === 50 ? $classSubject : null);
        $academicRepo->method('findTermById')->willReturnCallback(fn($id) => match ($id) {
            2 => $term,
            3 => $otherSessionTerm,
            default => null,
        });

        // Valid match (class_subject session 1, term session 1)
        $this->assertTrue(QuizPolicy::canCreateQuiz($this->teacherContext, 50, 2, $academicRepo, $teacherRepo));

        // Mismatched cross-session invariant
        $this->assertFalse(QuizPolicy::canCreateQuiz($this->teacherContext, 50, 3, $academicRepo, $teacherRepo));

        // Other teacher cannot create quiz
        $this->assertFalse(QuizPolicy::canCreateQuiz($this->otherTeacherContext, 50, 2, $academicRepo, $teacherRepo));
    }

    public function testStudentQuizParticipationAndTimerChecks(): void
    {
        $student = new Student(id: 100, userId: 4, admissionNumber: 'STD001', currentClassId: 5);
        $otherStudent = new Student(id: 101, userId: 5, admissionNumber: 'STD002', currentClassId: 5);

        $quiz = new Quiz(
            id: 10,
            classSubjectId: 50,
            termId: 2,
            assessmentCategoryId: null,
            teacherId: 10,
            title: 'Biology Test',
            timeLimitMinutes: 20,
            maxAttempts: 1,
            isPublished: true
        );

        $studentRepo = $this->createMock(StudentRepository::class);
        $studentRepo->method('findByUserId')->willReturnCallback(fn($uid) => match ($uid) {
            4 => $student,
            5 => $otherStudent,
            default => null,
        });

        $enrollmentRepo = $this->createMock(EnrollmentRepository::class);
        $enrollmentRepo->method('isStudentEnrolledInSubject')->willReturnCallback(fn($sid, $csid) => $sid === 100 && $csid === 50);

        $quizRepo = $this->createMock(QuizRepository::class);
        $quizRepo->method('getActiveAttempt')->willReturn(null);
        $quizRepo->method('getAttemptCount')->willReturnCallback(fn($qid, $sid) => match ($sid) {
            100 => 0, // 0 attempts taken, max is 1
            101 => 1,
            default => 0,
        });

        // Enrolled student can start
        $this->assertTrue(QuizPolicy::canStartAttempt($this->studentContext, $quiz, $studentRepo, $enrollmentRepo, $quizRepo));

        // Unenrolled student cannot start
        $this->assertFalse(QuizPolicy::canStartAttempt($this->otherStudentContext, $quiz, $studentRepo, $enrollmentRepo, $quizRepo));

        // Test attempt execution with timer
        $activeAttempt = new QuizAttempt(
            id: 500,
            uuid: 'att-1',
            quizId: 10,
            studentId: 100,
            attemptNumber: 1,
            startedAt: date('Y-m-d H:i:s', time() - (5 * 60)), // Started 5 mins ago (time limit 20 mins)
            status: QuizAttempt::STATUS_IN_PROGRESS
        );

        $expiredAttempt = new QuizAttempt(
            id: 501,
            uuid: 'att-2',
            quizId: 10,
            studentId: 100,
            attemptNumber: 1,
            startedAt: date('Y-m-d H:i:s', time() - (30 * 60)), // Started 30 mins ago
            status: QuizAttempt::STATUS_IN_PROGRESS
        );

        // Owner can take unexpired attempt
        $this->assertTrue(QuizPolicy::canTakeAttempt($this->studentContext, $activeAttempt, $quiz, $studentRepo));

        // Owner cannot take expired attempt
        $this->assertFalse(QuizPolicy::canTakeAttempt($this->studentContext, $expiredAttempt, $quiz, $studentRepo));

        // Other student cannot take someone else's attempt
        $this->assertFalse(QuizPolicy::canTakeAttempt($this->otherStudentContext, $activeAttempt, $quiz, $studentRepo));
    }
}
