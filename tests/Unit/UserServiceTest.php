<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Exceptions\DomainRuleException;
use App\Core\Exceptions\ValidationException;
use App\Core\UserContext;
use App\Models\User;
use App\Repositories\ParentRepository;
use App\Repositories\StudentRepository;
use App\Repositories\TeacherRepository;
use App\Repositories\UserRepository;
use App\Services\UserService;
use PHPUnit\Framework\TestCase;

final class UserServiceTest extends TestCase
{
    public function testSuperAdminCanCreateUserWithAnyRole(): void
    {
        $mockUserRepo = $this->createMock(UserRepository::class);
        $mockStudentRepo = $this->createMock(StudentRepository::class);
        $mockTeacherRepo = $this->createMock(TeacherRepository::class);
        $mockParentRepo = $this->createMock(ParentRepository::class);

        $superAdmin = new User(id: 1, uuid: 'sa-1', name: 'Super Admin', email: 'sa@claret.edu', passwordHash: 'x', roles: ['super_admin']);
        $actor = UserContext::fromUser($superAdmin);

        $createdUser = new User(id: 10, uuid: 'new-u', name: 'New Admin', email: 'admin2@claret.edu', passwordHash: 'x', roles: ['admin']);

        $mockUserRepo->method('findByEmail')->with('admin2@claret.edu')->willReturn(null);
        $mockUserRepo->method('create')->willReturn($createdUser);

        $service = new UserService($mockUserRepo, $mockStudentRepo, $mockTeacherRepo, $mockParentRepo);

        $res = $service->createUser([
            'name' => 'New Admin',
            'email' => 'admin2@claret.edu',
            'password' => 'Password123!',
            'roles' => ['admin'],
        ], $actor);

        $this->assertTrue($res->isSuccess());
        $this->assertSame(10, $res->getData()->id);
    }

    public function testAdminCannotCreateSuperAdmin(): void
    {
        $mockUserRepo = $this->createMock(UserRepository::class);
        $mockStudentRepo = $this->createMock(StudentRepository::class);
        $mockTeacherRepo = $this->createMock(TeacherRepository::class);
        $mockParentRepo = $this->createMock(ParentRepository::class);

        $admin = new User(id: 2, uuid: 'a-1', name: 'Admin', email: 'admin@claret.edu', passwordHash: 'x', roles: ['admin']);
        $actor = UserContext::fromUser($admin);

        $service = new UserService($mockUserRepo, $mockStudentRepo, $mockTeacherRepo, $mockParentRepo);

        $this->expectException(DomainRuleException::class);
        $service->createUser([
            'name' => 'Attempted SA',
            'email' => 'attempt@claret.edu',
            'password' => 'Password123!',
            'roles' => ['super_admin'],
        ], $actor);
    }

    public function testCannotCreateUserWithInvalidEmailOrShortPassword(): void
    {
        $mockUserRepo = $this->createMock(UserRepository::class);
        $mockStudentRepo = $this->createMock(StudentRepository::class);
        $mockTeacherRepo = $this->createMock(TeacherRepository::class);
        $mockParentRepo = $this->createMock(ParentRepository::class);

        $superAdmin = new User(id: 1, uuid: 'sa-1', name: 'Super Admin', email: 'sa@claret.edu', passwordHash: 'x', roles: ['super_admin']);
        $actor = UserContext::fromUser($superAdmin);

        $service = new UserService($mockUserRepo, $mockStudentRepo, $mockTeacherRepo, $mockParentRepo);

        $this->expectException(ValidationException::class);
        $service->createUser([
            'name' => 'Bad User',
            'email' => 'not-an-email',
            'password' => '123',
            'roles' => ['teacher'],
        ], $actor);
    }
}
