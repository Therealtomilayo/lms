<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\UserContext;
use App\Models\User;
use App\Policies\AcademicPolicy;
use App\Repositories\EnrollmentRepository;
use App\Repositories\ParentRepository;
use PHPUnit\Framework\TestCase;

final class AcademicPolicyPhase3Test extends TestCase
{
    public function testEnrollmentPermissions(): void
    {
        $admin = new User(id: 1, uuid: 'u1', name: 'Admin', email: 'admin@claret.edu', passwordHash: 'x', roles: ['admin']);
        $teacher = new User(id: 2, uuid: 'u2', name: 'Teacher', email: 'teacher@claret.edu', passwordHash: 'x', roles: ['teacher']);
        $student = new User(id: 3, uuid: 'u3', name: 'Student', email: 'student@claret.edu', passwordHash: 'x', roles: ['student']);

        $adminCtx = UserContext::fromUser($admin);
        $teacherCtx = UserContext::fromUser($teacher);
        $studentCtx = UserContext::fromUser($student);

        $this->assertTrue(AcademicPolicy::canManageEnrollments($adminCtx));
        $this->assertFalse(AcademicPolicy::canManageEnrollments($teacherCtx));
        $this->assertFalse(AcademicPolicy::canManageEnrollments($studentCtx));

        // Teachers can view enrollments/rosters
        $this->assertTrue(AcademicPolicy::canViewEnrollments($adminCtx));
        $this->assertTrue(AcademicPolicy::canViewEnrollments($teacherCtx));
        $this->assertFalse(AcademicPolicy::canViewEnrollments($studentCtx));

        // Guardian management
        $this->assertTrue(AcademicPolicy::canManageGuardians($adminCtx));
        $this->assertFalse(AcademicPolicy::canManageGuardians($teacherCtx));
    }

    public function testStudentCanAccessClassSubjectPredicate(): void
    {
        $mockRepo = $this->createMock(EnrollmentRepository::class);
        $mockRepo->method('isStudentEnrolledInSubject')
            ->willReturnCallback(function (int $studentId, int $classSubjectId, int $sessionId): bool {
                return $studentId === 10 && $classSubjectId === 20 && $sessionId === 1;
            });

        $this->assertTrue(AcademicPolicy::studentCanAccessClassSubject(10, 20, 1, $mockRepo));
        $this->assertFalse(AcademicPolicy::studentCanAccessClassSubject(10, 99, 1, $mockRepo));
        $this->assertFalse(AcademicPolicy::studentCanAccessClassSubject(55, 20, 1, $mockRepo));
    }

    public function testParentCanViewStudentPredicate(): void
    {
        $mockRepo = $this->createMock(ParentRepository::class);
        $mockRepo->method('isLinked')
            ->willReturnCallback(function (int $parentId, int $studentId): bool {
                return $parentId === 5 && $studentId === 10;
            });

        $this->assertTrue(AcademicPolicy::parentCanViewStudent(5, 10, $mockRepo));
        $this->assertFalse(AcademicPolicy::parentCanViewStudent(5, 99, $mockRepo));
    }
}
