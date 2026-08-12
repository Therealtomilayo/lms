<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\UserContext;
use App\Models\User;
use App\Policies\UserPolicy;
use PHPUnit\Framework\TestCase;

final class UserPolicyTest extends TestCase
{
    public function testSuperAdminHasFullPermissions(): void
    {
        $superAdmin = new User(id: 1, uuid: 'u1', name: 'Super Admin', email: 'sa@claret.edu', passwordHash: 'x', roles: ['super_admin']);
        $context = UserContext::fromUser($superAdmin);

        $targetAdmin = new User(id: 2, uuid: 'u2', name: 'Admin', email: 'admin@claret.edu', passwordHash: 'x', roles: ['admin']);
        $targetSuperAdmin = new User(id: 3, uuid: 'u3', name: 'Other SA', email: 'sa2@claret.edu', passwordHash: 'x', roles: ['super_admin']);

        $this->assertTrue(UserPolicy::canListUsers($context));
        $this->assertTrue(UserPolicy::canCreateUser($context, ['super_admin']));
        $this->assertTrue(UserPolicy::canCreateUser($context, ['admin', 'teacher']));
        $this->assertTrue(UserPolicy::canEditUser($context, $targetAdmin, ['super_admin']));
        $this->assertTrue(UserPolicy::canEditUser($context, $targetSuperAdmin));
        $this->assertTrue(UserPolicy::canChangeUserStatus($context, $targetAdmin));
        $this->assertTrue(UserPolicy::canResetUserPassword($context, $targetSuperAdmin));
    }

    public function testAdminCannotModifySuperAdminOrAssignSuperAdminRole(): void
    {
        $admin = new User(id: 2, uuid: 'u2', name: 'Admin', email: 'admin@claret.edu', passwordHash: 'x', roles: ['admin']);
        $context = UserContext::fromUser($admin);

        $superAdmin = new User(id: 1, uuid: 'u1', name: 'Super Admin', email: 'sa@claret.edu', passwordHash: 'x', roles: ['super_admin']);
        $teacher = new User(id: 4, uuid: 'u4', name: 'Teacher', email: 'teacher@claret.edu', passwordHash: 'x', roles: ['teacher']);

        $this->assertTrue(UserPolicy::canListUsers($context));

        // Admin cannot create user with super_admin role
        $this->assertFalse(UserPolicy::canCreateUser($context, ['super_admin']));
        $this->assertTrue(UserPolicy::canCreateUser($context, ['teacher', 'student']));

        // Admin cannot edit super_admin
        $this->assertFalse(UserPolicy::canEditUser($context, $superAdmin));
        // Admin cannot promote teacher to super_admin
        $this->assertFalse(UserPolicy::canEditUser($context, $teacher, ['super_admin']));
        $this->assertTrue(UserPolicy::canEditUser($context, $teacher, ['teacher', 'admin']));

        // Admin cannot change status or reset password of super admin
        $this->assertFalse(UserPolicy::canChangeUserStatus($context, $superAdmin));
        $this->assertFalse(UserPolicy::canResetUserPassword($context, $superAdmin));

        // Admin can manage teacher
        $this->assertTrue(UserPolicy::canChangeUserStatus($context, $teacher));
        $this->assertTrue(UserPolicy::canResetUserPassword($context, $teacher));
    }

    public function testSelfDeactivationIsForbidden(): void
    {
        $admin = new User(id: 2, uuid: 'u2', name: 'Admin', email: 'admin@claret.edu', passwordHash: 'x', roles: ['admin']);
        $context = UserContext::fromUser($admin);

        $this->assertFalse(UserPolicy::canChangeUserStatus($context, $admin));
    }

    public function testNonAdminCannotManageUsers(): void
    {
        $teacher = new User(id: 4, uuid: 'u4', name: 'Teacher', email: 'teacher@claret.edu', passwordHash: 'x', roles: ['teacher']);
        $context = UserContext::fromUser($teacher);

        $student = new User(id: 5, uuid: 'u5', name: 'Student', email: 'student@claret.edu', passwordHash: 'x', roles: ['student']);

        $this->assertFalse(UserPolicy::canListUsers($context));
        $this->assertFalse(UserPolicy::canCreateUser($context, ['student']));
        $this->assertFalse(UserPolicy::canEditUser($context, $student));
        $this->assertFalse(UserPolicy::canChangeUserStatus($context, $student));
        $this->assertFalse(UserPolicy::canManageImports($context));
    }
}
