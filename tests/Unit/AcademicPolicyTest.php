<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\UserContext;
use App\Models\User;
use App\Policies\AcademicPolicy;
use PHPUnit\Framework\TestCase;

final class AcademicPolicyTest extends TestCase
{
    public function testSuperAdminAndAdminCanManageAcademicEntities(): void
    {
        $admin = new User(id: 1, uuid: 'u1', name: 'Admin', email: 'admin@claret.edu', passwordHash: 'x', roles: ['admin']);
        $superAdmin = new User(id: 2, uuid: 'u2', name: 'Super Admin', email: 'sa@claret.edu', passwordHash: 'x', roles: ['super_admin']);

        $adminContext = UserContext::fromUser($admin);
        $superAdminContext = UserContext::fromUser($superAdmin);

        $this->assertTrue(AcademicPolicy::canManageSessionsAndTerms($adminContext));
        $this->assertTrue(AcademicPolicy::canManageSessionsAndTerms($superAdminContext));
        $this->assertTrue(AcademicPolicy::canManageAcademicStructure($adminContext));
        $this->assertTrue(AcademicPolicy::canManageAcademicStructure($superAdminContext));
    }

    public function testTeachersStudentsAndParentsCannotManageAcademicEntities(): void
    {
        $admin = new User(id: 1, uuid: 'u1', name: 'Admin', email: 'admin@claret.edu', passwordHash: 'x', roles: ['admin']);
        $teacher = new User(id: 3, uuid: 'u3', name: 'Teacher', email: 'teacher@claret.edu', passwordHash: 'x', roles: ['teacher']);
        $student = new User(id: 4, uuid: 'u4', name: 'Student', email: 'student@claret.edu', passwordHash: 'x', roles: ['student']);
        $parent = new User(id: 5, uuid: 'u5', name: 'Parent', email: 'parent@claret.edu', passwordHash: 'x', roles: ['parent']);

        $adminContext = UserContext::fromUser($admin);
        $teacherContext = UserContext::fromUser($teacher);
        $studentContext = UserContext::fromUser($student);
        $parentContext = UserContext::fromUser($parent);

        $this->assertFalse(AcademicPolicy::canManageSessionsAndTerms($teacherContext));
        $this->assertFalse(AcademicPolicy::canManageSessionsAndTerms($studentContext));
        $this->assertFalse(AcademicPolicy::canManageSessionsAndTerms($parentContext));

        $this->assertFalse(AcademicPolicy::canManageAcademicStructure($teacherContext));
        $this->assertFalse(AcademicPolicy::canManageAcademicStructure($studentContext));
        $this->assertFalse(AcademicPolicy::canManageAcademicStructure($parentContext));

        // But all authenticated users can view
        $this->assertTrue(AcademicPolicy::canViewSessionsAndTerms($teacherContext));
        $this->assertTrue(AcademicPolicy::canViewAcademicStructure($studentContext));
        $this->assertTrue(AcademicPolicy::canViewClassSubjects($teacherContext));
        $this->assertTrue(AcademicPolicy::canViewClassSubjects($studentContext));

        // Manage class subjects
        $this->assertTrue(AcademicPolicy::canManageClassSubjects($adminContext));
        $this->assertFalse(AcademicPolicy::canManageClassSubjects($teacherContext));
    }
}
