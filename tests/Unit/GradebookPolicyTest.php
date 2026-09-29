<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\UserContext;
use App\Models\ClassSubject;
use App\Models\Teacher;
use App\Models\User;
use App\Policies\GradebookPolicy;
use App\Policies\ResultPolicy;
use App\Repositories\ParentRepository;
use App\Repositories\StudentRepository;
use App\Repositories\TeacherRepository;
use PHPUnit\Framework\TestCase;

final class GradebookPolicyTest extends TestCase
{
    public function testSuperAdminAndAdminCanManageAnyGradebook(): void
    {
        $adminUser = User::fromArray([
            'id' => 1,
            'uuid' => 'admin-1',
            'name' => 'Admin User',
            'email' => 'admin@school.test',
            'roles' => ['admin'],
        ]);
        $adminContext = UserContext::fromUser($adminUser);

        $classSubject = ClassSubject::fromArray([
            'id' => 10,
            'session_id' => 1,
            'class_id' => 2,
            'subject_id' => 3,
            'teacher_id' => 99,
        ]);

        $this->assertTrue(GradebookPolicy::canView($adminContext, $classSubject));
        $this->assertTrue(GradebookPolicy::canSaveScores($adminContext, $classSubject, false));
        $this->assertTrue(GradebookPolicy::canManageCategories($adminContext));
        $this->assertTrue(GradebookPolicy::canManageGradingScales($adminContext));
    }

    public function testAssignedTeacherPermissions(): void
    {
        $teacherUser = User::fromArray([
            'id' => 2,
            'uuid' => 'teacher-1',
            'name' => 'John Doe',
            'email' => 'teacher@school.test',
            'roles' => ['teacher'],
        ]);
        $teacherContext = UserContext::fromUser($teacherUser);

        $teacherRepoMock = $this->createMock(TeacherRepository::class);
        $teacherRepoMock->method('findTeacherByUserId')->willReturn(
            Teacher::fromArray([
                'id' => 5,
                'user_id' => 2,
                'staff_id' => 'STF001',
                'user_name' => 'John Doe',
            ])
        );

        $assignedClassSubject = ClassSubject::fromArray([
            'id' => 10,
            'session_id' => 1,
            'class_id' => 2,
            'subject_id' => 3,
            'teacher_id' => 5,
        ]);

        $unassignedClassSubject = ClassSubject::fromArray([
            'id' => 11,
            'session_id' => 1,
            'class_id' => 2,
            'subject_id' => 4,
            'teacher_id' => 99,
        ]);

        // Assigned teacher can view & save unlocked
        $this->assertTrue(GradebookPolicy::canView($teacherContext, $assignedClassSubject, $teacherRepoMock));
        $this->assertTrue(GradebookPolicy::canSaveScores($teacherContext, $assignedClassSubject, false, $teacherRepoMock));

        // Assigned teacher CANNOT save if locked
        $this->assertFalse(GradebookPolicy::canSaveScores($teacherContext, $assignedClassSubject, true, $teacherRepoMock));

        // Teacher cannot manage categories
        $this->assertFalse(GradebookPolicy::canManageCategories($teacherContext));

        // Unassigned teacher cannot view or save
        $this->assertFalse(GradebookPolicy::canView($teacherContext, $unassignedClassSubject, $teacherRepoMock));
        $this->assertFalse(GradebookPolicy::canSaveScores($teacherContext, $unassignedClassSubject, false, $teacherRepoMock));
    }

    public function testResultPolicyStudentAndParentGating(): void
    {
        $studentUser = User::fromArray([
            'id' => 10,
            'uuid' => 'std-1',
            'name' => 'Student 1',
            'email' => 'std1@school.test',
            'roles' => ['student'],
        ]);
        $studentContext = UserContext::fromUser($studentUser);

        $studentRepoMock = $this->createMock(StudentRepository::class);
        $studentRepoMock->method('findByUserId')->willReturn(
            \App\Models\Student::fromArray([
                'id' => 100,
                'user_id' => 10,
                'admission_number' => 'ADM001',
            ])
        );

        // When unpublished: student cannot view
        $this->assertFalse(ResultPolicy::canViewStudentResults($studentContext, 100, false, null, $studentRepoMock));

        // When published: student can view own results
        $this->assertTrue(ResultPolicy::canViewStudentResults($studentContext, 100, true, null, $studentRepoMock));

        // When published: student cannot view other student's results
        $this->assertFalse(ResultPolicy::canViewStudentResults($studentContext, 200, true, null, $studentRepoMock));
    }

    public function testResultPolicyReportCardGating(): void
    {
        // 1. SuperAdmin / Admin can always view
        $adminUser = User::fromArray([
            'id' => 1,
            'name' => 'Admin User',
            'roles' => ['admin'],
        ]);
        $adminContext = UserContext::fromUser($adminUser);
        $this->assertTrue(ResultPolicy::canViewReportCard($adminContext, 10, 5));

        // 2. Class Teacher assigned to class can view
        $teacherUser = User::fromArray([
            'id' => 20,
            'name' => 'Class Teacher Okoro',
            'roles' => ['teacher'],
        ]);
        $teacherContext = UserContext::fromUser($teacherUser);

        $teacherRepoMock = $this->createMock(\App\Repositories\TeacherRepository::class);
        $teacherRepoMock->method('findTeacherByUserId')->with(20)->willReturn(
            \App\Models\Teacher::fromArray([
                'id' => 50,
                'user_id' => 20,
                'staff_id' => 'STF050',
            ])
        );

        $academicRepoMock = $this->createMock(\App\Repositories\AcademicRepository::class);
        $academicRepoMock->method('findClassById')->with(7)->willReturn(
            \App\Models\SchoolClass::fromArray([
                'id' => 7,
                'name' => 'JSS 1 (B)',
                'academic_level_id' => 1,
                'form_teacher_id' => 50,
            ])
        );

        $gradebookRepoMock = $this->createMock(\App\Repositories\GradebookRepository::class);
        $gradebookRepoMock->method('findStudentTermSummary')->with(10, 5)->willReturn(
            \App\Models\StudentTermSummary::fromArray([
                'id' => 101,
                'student_id' => 10,
                'term_id' => 5,
                'class_id' => 7,
                'total_score' => 450,
                'average_score' => 75.0,
            ])
        );

        $this->assertTrue(
            ResultPolicy::canViewReportCard(
                $teacherContext,
                10,
                5,
                $academicRepoMock,
                $teacherRepoMock,
                $gradebookRepoMock
            )
        );

        // 3. Different teacher (not form teacher of class 7) cannot view
        $otherTeacherUser = User::fromArray([
            'id' => 21,
            'name' => 'Subject Teacher David',
            'roles' => ['teacher'],
        ]);
        $otherTeacherContext = UserContext::fromUser($otherTeacherUser);

        $otherTeacherRepoMock = $this->createMock(\App\Repositories\TeacherRepository::class);
        $otherTeacherRepoMock->method('findTeacherByUserId')->with(21)->willReturn(
            \App\Models\Teacher::fromArray([
                'id' => 99,
                'user_id' => 21,
                'staff_id' => 'STF099',
            ])
        );

        $studentRepoMock = $this->createMock(\App\Repositories\StudentRepository::class);
        $studentRepoMock->method('findById')->with(10)->willReturn(null);

        $this->assertFalse(
            ResultPolicy::canViewReportCard(
                $otherTeacherContext,
                10,
                5,
                $academicRepoMock,
                $otherTeacherRepoMock,
                $gradebookRepoMock,
                $studentRepoMock
            )
        );

        // 4. Student/Parent role cannot view report card through faculty policy
        $studentUser = User::fromArray([
            'id' => 10,
            'name' => 'Student',
            'roles' => ['student'],
        ]);
        $studentContext = UserContext::fromUser($studentUser);
        $this->assertFalse(ResultPolicy::canViewReportCard($studentContext, 10, 5));
    }
}

