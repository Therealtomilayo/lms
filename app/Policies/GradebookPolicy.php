<?php

declare(strict_types=1);

namespace App\Policies;

use App\Core\UserContext;
use App\Models\ClassSubject;
use App\Repositories\TeacherRepository;

/**
 * Authorization Policy for Gradebook and Score Entry
 */
final class GradebookPolicy
{
    public static function canView(
        UserContext $user,
        ClassSubject $classSubject,
        ?TeacherRepository $teacherRepo = null
    ): bool {
        if ($user->isSuperAdmin() || $user->isAdmin()) {
            return true;
        }

        if ($user->isTeacher()) {
            $teacherId = $user->getTeacherId($teacherRepo);
            return $teacherId !== null && $classSubject->teacherId === $teacherId;
        }

        return false;
    }

    public static function canSaveScores(
        UserContext $user,
        ClassSubject $classSubject,
        bool $isLocked,
        ?TeacherRepository $teacherRepo = null
    ): bool {
        if ($isLocked) {
            return false;
        }

        if ($user->isSuperAdmin() || $user->isAdmin()) {
            return true;
        }

        if ($user->isTeacher()) {
            $teacherId = $user->getTeacherId($teacherRepo);
            return $teacherId !== null && $classSubject->teacherId === $teacherId;
        }

        return false;
    }

    public static function canManageCategories(UserContext $user): bool
    {
        return $user->isSuperAdmin() || $user->isAdmin();
    }

    public static function canManageGradingScales(UserContext $user): bool
    {
        return $user->isSuperAdmin() || $user->isAdmin();
    }
}
