<?php

declare(strict_types=1);

namespace App\Policies;

use App\Core\UserContext;
use App\Models\User;

/**
 * Authorization Policy for User Directory and Role Management
 * Governed strictly by .ai/06-rbac-permissions.md and 07-routes-and-screens.md
 */
final class UserPolicy
{
    public static function canListUsers(UserContext $context): bool
    {
        return $context->hasAnyRole(['super_admin', 'admin']);
    }

    public static function canCreateUser(UserContext $context, array $assigningRoles = []): bool
    {
        if ($context->hasRole('super_admin')) {
            return true;
        }

        if ($context->hasRole('admin')) {
            // Admin cannot create a user with the super_admin role
            return !in_array('super_admin', $assigningRoles, true);
        }

        return false;
    }

    public static function canEditUser(UserContext $context, User $targetUser, array $newRoles = []): bool
    {
        if ($context->hasRole('super_admin')) {
            return true;
        }

        if ($context->hasRole('admin')) {
            // Admin cannot edit a super_admin user or grant super_admin role
            if ($targetUser->hasRole('super_admin')) {
                return false;
            }
            if (in_array('super_admin', $newRoles, true)) {
                return false;
            }
            return true;
        }

        return false;
    }

    public static function canChangeUserStatus(UserContext $context, User $targetUser): bool
    {
        // Cannot self-disable
        if ($context->id === $targetUser->id) {
            return false;
        }

        if ($context->hasRole('super_admin')) {
            return true;
        }

        if ($context->hasRole('admin')) {
            // Admin cannot change status of super_admin
            return !$targetUser->hasRole('super_admin');
        }

        return false;
    }

    public static function canResetUserPassword(UserContext $context, User $targetUser): bool
    {
        if ($context->hasRole('super_admin')) {
            return true;
        }

        if ($context->hasRole('admin')) {
            // Admin cannot reset password of super_admin
            return !$targetUser->hasRole('super_admin');
        }

        return false;
    }

    public static function canManageImports(UserContext $context): bool
    {
        return $context->hasAnyRole(['super_admin', 'admin']);
    }
}
