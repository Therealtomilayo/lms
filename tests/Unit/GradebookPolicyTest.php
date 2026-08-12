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
}
