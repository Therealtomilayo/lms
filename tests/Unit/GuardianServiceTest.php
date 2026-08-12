<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Exceptions\DomainRuleException;
use App\Core\Exceptions\ResourceNotFoundException;
use App\Models\ParentProfile;
use App\Models\Student;
use App\Models\User;
use App\Repositories\ParentRepository;
use App\Repositories\StudentRepository;
use App\Repositories\UserRepository;
use App\Services\GuardianService;
use PHPUnit\Framework\TestCase;

final class GuardianServiceTest extends TestCase
{
    public function testCreateParentProfileForParentUser(): void
    {
        $mockParentRepo = $this->createMock(ParentRepository::class);
        $mockStudentRepo = $this->createMock(StudentRepository::class);
        $mockUserRepo = $this->createMock(UserRepository::class);

        $user = new User(id: 10, uuid: 'u10', name: 'Parent User', email: 'parent@claret.edu', passwordHash: 'x', roles: ['parent']);
        $profile = new ParentProfile(id: 1, userId: 10);

        $mockUserRepo->method('findById')->with(10)->willReturn($user);
        $mockParentRepo->method('findByUserId')->with(10)->willReturn(null);
        $mockParentRepo->method('create')->with(10)->willReturn($profile);

        $service = new GuardianService($mockParentRepo, $mockStudentRepo, $mockUserRepo);
        $res = $service->createParentProfile(10);

        $this->assertTrue($res->isSuccess());
        $this->assertSame(1, $res->getData()->id);
    }

    public function testCannotCreateParentProfileForNonParentUser(): void
    {
        $mockParentRepo = $this->createMock(ParentRepository::class);
        $mockStudentRepo = $this->createMock(StudentRepository::class);
        $mockUserRepo = $this->createMock(UserRepository::class);

        $user = new User(id: 10, uuid: 'u10', name: 'Student User', email: 'st@claret.edu', passwordHash: 'x', roles: ['student']);
        $mockUserRepo->method('findById')->with(10)->willReturn($user);

        $service = new GuardianService($mockParentRepo, $mockStudentRepo, $mockUserRepo);

        $this->expectException(DomainRuleException::class);
        $this->expectExceptionMessage("does not have the 'parent' role");
        $service->createParentProfile(10);
    }

    public function testLinkGuardianToStudent(): void
    {
        $mockParentRepo = $this->createMock(ParentRepository::class);
        $mockStudentRepo = $this->createMock(StudentRepository::class);
        $mockUserRepo = $this->createMock(UserRepository::class);

        $parent = new ParentProfile(id: 2, userId: 10);
        $student = new Student(id: 5, userId: 20, admissionNumber: 'STD-1');

        $mockParentRepo->method('findById')->with(2)->willReturn($parent);
        $mockStudentRepo->method('findById')->with(5)->willReturn($student);
        $mockParentRepo->expects($this->once())->method('linkStudent')->with(2, 5, 'Father');

        $service = new GuardianService($mockParentRepo, $mockStudentRepo, $mockUserRepo);
        $res = $service->linkGuardian(2, 5, 'Father');

        $this->assertTrue($res->isSuccess());
    }
}
