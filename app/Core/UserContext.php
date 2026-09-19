<?php

declare(strict_types=1);

namespace App\Core;

use App\Models\User;
use App\Repositories\ParentRepository;
use App\Repositories\StudentRepository;
use App\Repositories\TeacherRepository;

/**
 * Encapsulates the Authenticated User Context for Request Lifecycles
 */
final class UserContext
{
    /**
     * @param string[] $roles
     */
    public function __construct(
        public readonly int $id,
        public readonly string $uuid,
        public readonly string $name,
        public readonly string $email,
        public readonly array $roles,
        public readonly bool $mustChangePassword = false,
        public readonly ?User $user = null
    ) {
    }

    public static function fromUser(User $user): self
    {
        return new self(
            id: $user->id,
            uuid: $user->uuid,
            name: $user->name,
            email: $user->email,
            roles: $user->roles,
            mustChangePassword: $user->mustChangePassword,
            user: $user
        );
    }

    public function __get(string $name): mixed
    {
        if ($name === 'userId') {
            return $this->id;
        }
        if ($name === 'primaryRole') {
            return $this->getPrimaryRole();
        }
        if ($name === 'primaryRoleLabel') {
            return $this->getPrimaryRoleLabel();
        }
        return null;
    }

    public function getPrimaryRole(): string
    {
        if ($this->hasRole('super_admin')) return 'super_admin';
        if ($this->hasRole('admin')) return 'admin';
        if ($this->hasRole('teacher')) return 'teacher';
        if ($this->hasRole('parent')) return 'parent';
        if ($this->hasRole('student')) return 'student';
        if ($this->hasRole('applicant')) return 'applicant';
        return $this->roles[0] ?? 'user';
    }

    public function getPrimaryRoleLabel(): string
    {
        return match ($this->getPrimaryRole()) {
            'super_admin' => 'Super Administrator',
            'admin' => 'Administrator',
            'teacher' => 'Teacher',
            'parent' => 'Parent / Guardian',
            'student' => 'Student',
            'applicant' => 'Applicant / Prospective Parent',
            default => 'User',
        };
    }

    public function getUserId(): int
    {
        return $this->id;
    }

    public function getTeacherId(?TeacherRepository $teacherRepo = null): ?int
    {
        $repo = $teacherRepo ?? new TeacherRepository();
        $teacher = $repo->findTeacherByUserId($this->id);
        return $teacher ? $teacher->id : null;
    }

    public function getStudentId(?StudentRepository $studentRepo = null): ?int
    {
        $repo = $studentRepo ?? new StudentRepository();
        $student = $repo->findByUserId($this->id);
        return $student ? $student->id : null;
    }

    public function getParentId(?ParentRepository $parentRepo = null): ?int
    {
        $repo = $parentRepo ?? new ParentRepository();
        $parent = $repo->findByUserId($this->id);
        return $parent ? $parent->id : null;
    }

    public function isAuthenticated(): bool
    {
        return $this->id > 0;
    }

    public function hasRole(string $role): bool
    {
        return in_array($role, $this->roles, true);
    }

    public function hasAnyRole(array $roles): bool
    {
        foreach ($roles as $role) {
            if ($this->hasRole($role)) {
                return true;
            }
        }

        return false;
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole('super_admin');
    }

    public function isAdmin(): bool
    {
        return $this->hasRole('admin') || $this->hasRole('super_admin');
    }

    public function isTeacher(): bool
    {
        return $this->hasRole('teacher');
    }

    public function isStudent(): bool
    {
        return $this->hasRole('student');
    }

    public function isParent(): bool
    {
        return $this->hasRole('parent');
    }

    public function isApplicant(): bool
    {
        return $this->hasRole('applicant');
    }
}
