<?php

declare(strict_types=1);

namespace App\Policies;

use App\Core\UserContext;

/**
 * Authorization Policy for System Health, Backups, and Audit Logging
 */
class SystemPolicy
{
    /**
     * Determine whether user can view deep health diagnostics.
     */
    public function viewHealth(?UserContext $user): bool
    {
        if ($user === null) {
            return false;
        }

        return $user->hasAnyRole(['admin', 'super_admin']);
    }

    /**
     * Determine whether user can view and list backups.
     */
    public function viewBackups(?UserContext $user): bool
    {
        if ($user === null) {
            return false;
        }

        return $user->hasAnyRole(['admin', 'super_admin']);
    }

    /**
     * Determine whether user can trigger database backup generation.
     */
    public function createBackup(?UserContext $user): bool
    {
        if ($user === null) {
            return false;
        }

        return $user->hasAnyRole(['admin', 'super_admin']);
    }

    /**
     * Determine whether user can download database backups.
     */
    public function downloadBackup(?UserContext $user): bool
    {
        if ($user === null) {
            return false;
        }

        return $user->hasAnyRole(['admin', 'super_admin']);
    }

    /**
     * Determine whether user can review audit logs.
     */
    public function viewAuditLogs(?UserContext $user): bool
    {
        if ($user === null) {
            return false;
        }

        return $user->hasAnyRole(['admin', 'super_admin']);
    }
}
