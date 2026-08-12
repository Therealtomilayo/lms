<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\UserContext;
use App\Models\User;
use PHPUnit\Framework\TestCase;

final class UserContextTest extends TestCase
{
    public function testMultiRoleResolutionAndHelpers(): void
    {
        $user = new User(
            id: 1,
            uuid: 'uuid-1234',
            name: 'Teacher Parent John',
            email: 'john@claret.edu',
            phone: '1234567890',
            passwordHash: 'hash',
            status: 'active',
            mustChangePassword: true,
            roles: ['teacher', 'parent']
        );

        $context = UserContext::fromUser($user);

        $this->assertSame(1, $context->id);
        $this->assertSame('uuid-1234', $context->uuid);
        $this->assertTrue($context->mustChangePassword);
        $this->assertTrue($context->hasRole('teacher'));
        $this->assertTrue($context->hasRole('parent'));
        $this->assertFalse($context->hasRole('admin'));
        $this->assertTrue($context->hasAnyRole(['admin', 'teacher']));
        $this->assertFalse($context->hasAnyRole(['admin', 'student']));
        $this->assertTrue($context->isTeacher());
        $this->assertTrue($context->isParent());
        $this->assertFalse($context->isAdmin());
        $this->assertFalse($context->isSuperAdmin());
    }
}
