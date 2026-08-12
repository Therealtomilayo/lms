<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\UserContext;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\ClassSubject;
use App\Models\ParentProfile;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\Term;
use App\Models\User;
use App\Policies\AssignmentPolicy;
use App\Repositories\AcademicRepository;
use App\Repositories\AssignmentRepository;
use App\Repositories\EnrollmentRepository;
use App\Repositories\ParentRepository;
use App\Repositories\StudentRepository;
use App\Repositories\TeacherRepository;
use PHPUnit\Framework\TestCase;

final class AssignmentPolicyTest extends TestCase
{
    private UserContext $adminContext;
    private UserContext $teacherContext;
    private UserContext $otherTeacherContext;
    private UserContext $studentContext;
    private UserContext $otherStudentContext;
    private UserContext $parentContext;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminContext = UserContext::fromUser(new User(id: 1, uuid: 'u1', name: 'Admin', email: 'admin@claret.edu', passwordHash: 'x', roles: ['admin']));
        $this->teacherContext = UserContext::fromUser(new User(id: 2, uuid: 'u2', name: 'Teacher 1', email: 't1@claret.edu', passwordHash: 'x', roles: ['teacher']));
        $this->otherTeacherContext = UserContext::fromUser(new User(id: 3, uuid: 'u3', name: 'Teacher 2', email: 't2@claret.edu', passwordHash: 'x', roles: ['teacher']));
        $this->studentContext = UserContext::fromUser(new User(id: 4, uuid: 'u4', name: 'Student 1', email: 's1@claret.edu', passwordHash: 'x', roles: ['student']));
        $this->otherStudentContext = UserContext::fromUser(new User(id: 5, uuid: 'u5', name: 'Student 2', email: 's2@claret.edu', passwordHash: 'x', roles: ['student']));
        $this->parentContext = UserContext::fromUser(new User(id: 6, uuid: 'u6', name: 'Parent 1', email: 'p1@claret.edu', passwordHash: 'x', roles: ['parent']));
    }

    public function testCanCreateAssignment(): void
    {
        $teacher = new Teacher(id: 10, userId: 2, staffId: 'T001', createdAt: 'now');
        $otherTeacher = new Teacher(id: 11, userId: 3, staffId: 'T002', createdAt: 'now');

        $classSubject = new ClassSubject(id: 50, sessionId: 1, classId: 5, subjectId: 20, teacherId: 10, status: 'active');
        $term = new Term(id: 2, sessionId: 1, name: 'Term 1', startDate: '2026-01-01', endDate: '2026-04-01', status: 'active');
        $diffSessionTerm = new Term(id: 3, sessionId: 2, name: 'Term 2 in other session', startDate: '2027-01-01', endDate: '2027-04-01', status: 'active');

        $teacherRepo = $this->createMock(TeacherRepository::class);
        $teacherRepo->method('findTeacherByUserId')->willReturnCallback(fn($uid) => match ($uid) {
            2 => $teacher,
            3 => $otherTeacher,
            default => null,
        });

        $academicRepo = $this->createMock(AcademicRepository::class);
        $academicRepo->method('findClassSubjectById')->willReturnCallback(fn($id) => $id === 50 ? $classSubject : null);
        $academicRepo->method('findTermById')->willReturnCallback(fn($id) => match ($id) {
            2 => $term,
            3 => $diffSessionTerm,
            default => null,
        });

        // Admin can always create
        $this->assertTrue(AssignmentPolicy::canCreateAssignment($this->adminContext, 50, 2, $academicRepo, $teacherRepo));

        // Assigned teacher can create in same session term
        $this->assertTrue(AssignmentPolicy::canCreateAssignment($this->teacherContext, 50, 2, $academicRepo, $teacherRepo));

        // Assigned teacher cannot combine cross-session term
        $this->assertFalse(AssignmentPolicy::canCreateAssignment($this->teacherContext, 50, 3, $academicRepo, $teacherRepo));

        // Other teacher cannot create in this class subject
        $this->assertFalse(AssignmentPolicy::canCreateAssignment($this->otherTeacherContext, 50, 2, $academicRepo, $teacherRepo));

        // Student cannot create
        $this->assertFalse(AssignmentPolicy::canCreateAssignment($this->studentContext, 50, 2, $academicRepo, $teacherRepo));
    }

    public function testCanEditAndDeleteAssignment(): void
    {
        $teacher = new Teacher(id: 10, userId: 2, staffId: 'T001', createdAt: 'now');
        $otherTeacher = new Teacher(id: 11, userId: 3, staffId: 'T002', createdAt: 'now');

        $classSubject = new ClassSubject(id: 50, sessionId: 1, classId: 5, subjectId: 20, teacherId: 10, status: 'active');

        $assignment = new Assignment(
            id: 100,
            classSubjectId: 50,
            termId: 2,
            assessmentCategoryId: null,
            teacherId: 10,
            topic: 'Math',
            title: 'Algebra',
            instructions: 'Solve',
            dueAt: '2026-09-01 00:00:00',
            maxScore: 100.0
        );

        $teacherRepo = $this->createMock(TeacherRepository::class);
        $teacherRepo->method('findTeacherByUserId')->willReturnCallback(fn($uid) => match ($uid) {
            2 => $teacher,
            3 => $otherTeacher,
            default => null,
        });

        $academicRepo = $this->createMock(AcademicRepository::class);
        $academicRepo->method('findClassSubjectById')->willReturnCallback(fn($id) => $id === 50 ? $classSubject : null);

        // Admin can edit/delete
        $this->assertTrue(AssignmentPolicy::canEditAssignment($this->adminContext, $assignment, $academicRepo, $teacherRepo));
        $this->assertTrue(AssignmentPolicy::canDeleteAssignment($this->adminContext, $assignment, $academicRepo, $teacherRepo));

        // Assigned teacher can edit/delete
        $this->assertTrue(AssignmentPolicy::canEditAssignment($this->teacherContext, $assignment, $academicRepo, $teacherRepo));
        $this->assertTrue(AssignmentPolicy::canDeleteAssignment($this->teacherContext, $assignment, $academicRepo, $teacherRepo));

        // Other teacher cannot edit/delete
        $this->assertFalse(AssignmentPolicy::canEditAssignment($this->otherTeacherContext, $assignment, $academicRepo, $teacherRepo));
        $this->assertFalse(AssignmentPolicy::canDeleteAssignment($this->otherTeacherContext, $assignment, $academicRepo, $teacherRepo));
    }

    public function testCanSubmitAndGradeAssignment(): void
    {
        $teacher = new Teacher(id: 10, userId: 2, staffId: 'T001', createdAt: 'now');
        $otherTeacher = new Teacher(id: 11, userId: 3, staffId: 'T002', createdAt: 'now');

        $student1 = new Student(id: 20, userId: 4, admissionNumber: 'STD001', dateOfBirth: '2010-01-01', gender: 'male', createdAt: 'now');
        $student2 = new Student(id: 21, userId: 5, admissionNumber: 'STD002', dateOfBirth: '2010-01-01', gender: 'female', createdAt: 'now');

        $classSubject = new ClassSubject(id: 50, sessionId: 1, classId: 5, subjectId: 20, teacherId: 10, status: 'active');

        $assignment = new Assignment(
            id: 100,
            classSubjectId: 50,
            termId: 2,
            assessmentCategoryId: null,
            teacherId: 10,
            topic: 'Math',
            title: 'Algebra',
            instructions: 'Solve',
            dueAt: '2026-09-01 00:00:00',
            maxScore: 100.0,
            status: Assignment::STATUS_PUBLISHED
        );

        $draftAssignment = new Assignment(
            id: 101,
            classSubjectId: 50,
            termId: 2,
            assessmentCategoryId: null,
            teacherId: 10,
            topic: 'Math',
            title: 'Draft Algebra',
            instructions: 'Solve',
            dueAt: '2026-09-01 00:00:00',
            maxScore: 100.0,
            status: Assignment::STATUS_DRAFT
        );

        $teacherRepo = $this->createMock(TeacherRepository::class);
        $teacherRepo->method('findTeacherByUserId')->willReturnCallback(fn($uid) => match ($uid) {
            2 => $teacher,
            3 => $otherTeacher,
            default => null,
        });

        $academicRepo = $this->createMock(AcademicRepository::class);
        $academicRepo->method('findClassSubjectById')->willReturnCallback(fn($id) => $id === 50 ? $classSubject : null);

        $studentRepo = $this->createMock(StudentRepository::class);
        $studentRepo->method('findByUserId')->willReturnCallback(fn($uid) => match ($uid) {
            4 => $student1,
            5 => $student2,
            default => null,
        });

        $enrollmentRepo = $this->createMock(EnrollmentRepository::class);
        // Student 1 is enrolled in CS 50; Student 2 is NOT
        $enrollmentRepo->method('isStudentEnrolledInSubject')->willReturnCallback(fn($sid, $csid, $sess) => $sid === 20 && $csid === 50);

        // Enrolled student can submit published assignment
        $this->assertTrue(AssignmentPolicy::canSubmitAssignment($this->studentContext, $assignment, $academicRepo, $studentRepo, $enrollmentRepo));

        // Enrolled student cannot submit draft assignment
        $this->assertFalse(AssignmentPolicy::canSubmitAssignment($this->studentContext, $draftAssignment, $academicRepo, $studentRepo, $enrollmentRepo));

        // Non-enrolled student cannot submit
        $this->assertFalse(AssignmentPolicy::canSubmitAssignment($this->otherStudentContext, $assignment, $academicRepo, $studentRepo, $enrollmentRepo));

        // Submission grading check
        $submission = new AssignmentSubmission(
            id: 500,
            assignmentId: 100,
            studentId: 20,
            submittedAt: '2026-08-12 12:00:00',
            assignment: $assignment
        );

        // Admin can grade
        $this->assertTrue(AssignmentPolicy::canGradeSubmission($this->adminContext, $submission, null, $academicRepo, $teacherRepo));

        // Subject teacher can grade
        $this->assertTrue(AssignmentPolicy::canGradeSubmission($this->teacherContext, $submission, null, $academicRepo, $teacherRepo));

        // Other teacher cannot grade
        $this->assertFalse(AssignmentPolicy::canGradeSubmission($this->otherTeacherContext, $submission, null, $academicRepo, $teacherRepo));

        // Student cannot grade
        $this->assertFalse(AssignmentPolicy::canGradeSubmission($this->studentContext, $submission, null, $academicRepo, $teacherRepo));
    }
}
