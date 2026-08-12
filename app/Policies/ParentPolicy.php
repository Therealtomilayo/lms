<?php

declare(strict_types=1);

namespace App\Policies;

use App\Core\UserContext;
use App\Repositories\ParentRepository;
use App\Repositories\ResultPublicationRepository;

/**
 * Authorization Policy for Parent Portal and Linked Student Resources
 */
final class ParentPolicy
{
    /**
     * Determine if the user can view/access a student's profile or portal.
     */
    public static function canViewStudent(
        UserContext $user,
        int $studentId,
        ?ParentRepository $parentRepo = null
    ): bool {
        if ($user->isSuperAdmin() || $user->isAdmin()) {
            return true;
        }

        if (!$user->isParent()) {
            return false;
        }

        $repo = $parentRepo ?? new ParentRepository();
        $parentId = $user->getParentId($repo);
        if ($parentId === null) {
            return false;
        }

        return $repo->isLinkedToStudent($parentId, $studentId);
    }

    /**
     * Determine if the user can view a student's published report card or term results.
     */
    public static function canViewReportCard(
        UserContext $user,
        int $studentId,
        int $termId,
        ?ParentRepository $parentRepo = null,
        ?ResultPublicationRepository $pubRepo = null
    ): bool {
        if ($user->isSuperAdmin() || $user->isAdmin()) {
            return true;
        }

        if (!self::canViewStudent($user, $studentId, $parentRepo)) {
            return false;
        }

        $publicationRepo = $pubRepo ?? new ResultPublicationRepository();
        return $publicationRepo->isPublished($termId);
    }

    /**
     * Determine if the user can view a student's attendance records.
     */
    public static function canViewAttendance(
        UserContext $user,
        int $studentId,
        ?ParentRepository $parentRepo = null
    ): bool {
        return self::canViewStudent($user, $studentId, $parentRepo);
    }

    /**
     * Determine if the user can view a student's coursework assignments.
     */
    public static function canViewAssignments(
        UserContext $user,
        int $studentId,
        ?ParentRepository $parentRepo = null
    ): bool {
        return self::canViewStudent($user, $studentId, $parentRepo);
    }

    /**
     * Alias for canViewAssignments.
     */
    public static function canViewCoursework(
        UserContext $user,
        int $studentId,
        ?ParentRepository $parentRepo = null
    ): bool {
        return self::canViewAssignments($user, $studentId, $parentRepo);
    }

    /**
     * Determine if the user can view announcements targeted to a student.
     */
    public static function canViewAnnouncements(
        UserContext $user,
        int $studentId,
        ?ParentRepository $parentRepo = null
    ): bool {
        return self::canViewStudent($user, $studentId, $parentRepo);
    }
}
