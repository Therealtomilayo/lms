<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Teacher;
use App\Models\User;
use PHPUnit\Framework\TestCase;

final class TeacherTest extends TestCase
{
    public function testTeacherInstantiationAndToArray(): void
    {
        $teacher = new Teacher(
            id: 5,
            userId: 12,
            staffId: 'STF-001',
            createdAt: '2026-08-01 00:00:00',
            updatedAt: '2026-08-01 00:00:00'
        );

        $this->assertSame(5, $teacher->id);
        $this->assertSame(12, $teacher->userId);
        $this->assertSame('STF-001', $teacher->staffId);

        $array = $teacher->toArray();
        $this->assertSame(5, $array['id']);
        $this->assertSame(12, $array['user_id']);
        $this->assertSame('STF-001', $array['staff_id']);
    }

    public function testTeacherFromArrayWithHydratedUser(): void
    {
        $user = new User(
            id: 12,
            uuid: 'u-12',
            name: 'John Doe',
            email: 'john@claret.edu',
            passwordHash: 'hash',
            roles: ['teacher']
        );

        $teacher = Teacher::fromArray([
            'id' => 5,
            'user_id' => 12,
            'staff_id' => 'STF-001',
        ], $user);

        $this->assertSame(5, $teacher->id);
        $this->assertNotNull($teacher->user);
        $this->assertSame('John Doe', $teacher->user->name);
        $this->assertSame('john@claret.edu', $teacher->user->email);
    }
}
