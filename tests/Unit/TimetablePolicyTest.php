<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\UserContext;
use App\Policies\TimetablePolicy;
use App\Repositories\ParentRepository;
use App\Repositories\StudentRepository;
use App\Repositories\TeacherRepository;
use PHPUnit\Framework\TestCase;

class TimetablePolicyTest extends TestCase
{
    // ─── Helpers ────────────────────────────────────────────────────────

    private function makeContext(int $userId, string $role, string $email = 'user@test.com'): UserContext
    {
        return new UserContext(
            id: $userId,
            uuid: 'uuid-' . $userId,
            name: 'Test User',
            email: $email,
            roles: [$role]
        );
    }

    // ─── canManage ───────────────────────────────────────────────────────

    public function testAdminCanManage(): void
    {
        $this->assertTrue(TimetablePolicy::canManage($this->makeContext(1, 'admin')));
    }

    public function testSuperAdminCanManage(): void
    {
        $this->assertTrue(TimetablePolicy::canManage($this->makeContext(2, 'super_admin')));
    }

    public function testTeacherCannotManage(): void
    {
        $this->assertFalse(TimetablePolicy::canManage($this->makeContext(10, 'teacher')));
    }

    public function testStudentCannotManage(): void
    {
        $this->assertFalse(TimetablePolicy::canManage($this->makeContext(20, 'student')));
    }

    public function testParentCannotManage(): void
    {
        $this->assertFalse(TimetablePolicy::canManage($this->makeContext(30, 'parent')));
    }

    public function testNullCannotManage(): void
    {
        $this->assertFalse(TimetablePolicy::canManage(null));
    }

    // ─── canViewAny ──────────────────────────────────────────────────────

    public function testAdminCanViewAny(): void
    {
        $this->assertTrue(TimetablePolicy::canViewAny($this->makeContext(1, 'admin')));
    }

    public function testTeacherCanViewAny(): void
    {
        $this->assertTrue(TimetablePolicy::canViewAny($this->makeContext(10, 'teacher')));
    }

    public function testStudentCanViewAny(): void
    {
        $this->assertTrue(TimetablePolicy::canViewAny($this->makeContext(20, 'student')));
    }

    public function testParentCanViewAny(): void
    {
        $this->assertTrue(TimetablePolicy::canViewAny($this->makeContext(30, 'parent')));
    }

    public function testNullCannotViewAny(): void
    {
        $this->assertFalse(TimetablePolicy::canViewAny(null));
    }

    // ─── canViewTeacherTimetable ─────────────────────────────────────────

    public function testAdminCanViewAnyTeacherTimetable(): void
    {
        $admin = $this->makeContext(1, 'admin');
        $this->assertTrue(TimetablePolicy::canViewTeacherTimetable($admin, 55));
    }

    public function testTeacherCanViewOwnTimetableOnly(): void
    {
        $teacherUser = $this->makeContext(10, 'teacher');

        $teacherRepo = $this->createMock(TeacherRepository::class);
        $teacherRepo->method('findTeacherByUserId')->willReturnCallback(function (int $uid) {
            if ($uid === 10) {
                return new \App\Models\Teacher(id: 55, userId: 10, staffId: 'T001');
            }
            return null;
        });

        // Own profile ID => allowed
        $this->assertTrue(TimetablePolicy::canViewTeacherTimetable($teacherUser, 55, $teacherRepo));

        // Another teacher's ID => denied
        $this->assertFalse(TimetablePolicy::canViewTeacherTimetable($teacherUser, 99, $teacherRepo));
    }

    public function testStudentCannotViewTeacherTimetable(): void
    {
        $student = $this->makeContext(20, 'student');
        $this->assertFalse(TimetablePolicy::canViewTeacherTimetable($student, 55));
    }

    // ─── canViewStudentTimetable ─────────────────────────────────────────

    public function testAdminCanViewAnyStudentTimetable(): void
    {
        $admin = $this->makeContext(1, 'admin');
        $this->assertTrue(TimetablePolicy::canViewStudentTimetable($admin, 100));
    }

    public function testStudentCanViewOwnTimetableOnly(): void
    {
        $studentUser = $this->makeContext(20, 'student');

        $studentRepo = $this->createMock(StudentRepository::class);
        $studentRepo->method('findByUserId')->willReturnCallback(function (int $uid) {
            if ($uid === 20) {
                return new \App\Models\Student(id: 100, userId: 20, admissionNumber: 'ADM-001');
            }
            return null;
        });

        // Own profile => allowed
        $this->assertTrue(TimetablePolicy::canViewStudentTimetable($studentUser, 100, $studentRepo));

        // Another student's ID => denied
        $this->assertFalse(TimetablePolicy::canViewStudentTimetable($studentUser, 200, $studentRepo));
    }

    public function testParentCanViewLinkedChildOnly(): void
    {
        $parentUser = $this->makeContext(30, 'parent');

        $parentRepo = $this->createMock(ParentRepository::class);
        $parentRepo->method('findByUserId')->willReturnCallback(function (int $uid) {
            if ($uid === 30) {
                return new \App\Models\ParentProfile(id: 77, userId: 30);
            }
            return null;
        });
        $parentRepo->method('isLinkedToStudent')->willReturnCallback(
            fn(int $parentId, int $studentId) => ($parentId === 77 && $studentId === 101)
        );

        $studentRepo = $this->createMock(StudentRepository::class);

        // Linked child => allowed
        $this->assertTrue(TimetablePolicy::canViewStudentTimetable($parentUser, 101, $studentRepo, $parentRepo));

        // Unlinked child => denied
        $this->assertFalse(TimetablePolicy::canViewStudentTimetable($parentUser, 999, $studentRepo, $parentRepo));
    }

    public function testTeacherCannotViewStudentTimetable(): void
    {
        $teacher = $this->makeContext(10, 'teacher');
        $this->assertFalse(TimetablePolicy::canViewStudentTimetable($teacher, 100));
    }
}
