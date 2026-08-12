<?php

declare(strict_types=1);

namespace App\Policies;

use App\Core\UserContext;
use App\Repositories\ParentRepository;
use App\Repositories\StudentRepository;

/**
 * Authorization Policy for Term Results, Publications, and Report Cards
 */
final class ResultPolicy
{
    public static function canReview(UserContext $user): bool
    {
        return $user->isSuperAdmin() || $user->isAdmin();
    }

    public static function canPublish(UserContext $user): bool
    {
        return $user->isSuperAdmin() || $user->isAdmin();
    }

    public static function canUnpublish(UserContext $user): bool
    {
        return $user->isSuperAdmin() || $user->isAdmin();
    }

    public static function canViewStudentResults(
        UserContext $user,
        int $studentId,
        bool $isPublished,
        ?ParentRepository $parentRepo = null,
        ?StudentRepository $studentRepo = null
    ): bool {
        // Super Admin & Admin can always view
        if ($user->isSuperAdmin() || $user->isAdmin()) {
            return true;
        }

        // Students & Parents can strictly NEVER view unpublished results
        if (!$isPublished) {
            return false;
        }

        // Student can only view their own results
        if ($user->isStudent()) {
            return $user->getStudentId($studentRepo) === $studentId;
        }

        // Parent can only view results of their linked children
        if ($user->isParent()) {
            $parentId = $user->getParentId($parentRepo);
            if ($parentId === null || $parentRepo === null) {
                return false;
            }
            return $parentRepo->isLinkedToStudent($parentId, $studentId);
        }

        return false;
    }
}
