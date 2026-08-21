<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\UserContext;
use App\Policies\SystemPolicy;
use App\Services\AuditService;
use App\Services\BackupService;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

/**
 * Controller for Database Backup and Archival Administration
 */
class BackupController extends Controller
{
    private BackupService $backupService;
    private SystemPolicy $policy;
    private AuditService $auditService;

    public function __construct(
        ?BackupService $backupService = null,
        ?SystemPolicy $policy = null,
        ?AuditService $auditService = null
    ) {
        $this->backupService = $backupService ?? new BackupService();
        $this->policy = $policy ?? new SystemPolicy();
        $this->auditService = $auditService ?? new AuditService();
    }

    public function index(Request $request): Response
    {
        $userContext = $request->getAttribute('user_context');
        if (!$userContext instanceof UserContext || !$this->policy->viewBackups($userContext)) {
            return $this->forbidden('You are not authorized to access system backups.');
        }

        $backups = $this->backupService->listBackups();

        return Response::html($this->render('admin/backups/index', [
            'title' => 'Database Backups & Archival — Claret Portal',
            'headerTitle' => 'Database Backups & Archival',
            'backups' => $backups,
            'csrf_token' => $request->getSession()->get('_csrf_token', ''),
        ], 'layouts/admin'));
    }

    public function create(Request $request): Response
    {
        $userContext = $request->getAttribute('user_context');
        if (!$userContext instanceof UserContext || !$this->policy->createBackup($userContext)) {
            return $this->forbidden('You are not authorized to generate database backups.');
        }

        try {
            $meta = $this->backupService->createBackup();

            // Log administrative action
            $this->auditService->log(
                'backup.created',
                'backup',
                0,
                $userContext->id,
                null,
                ['filename' => $meta['filename'], 'sha256' => $meta['sha256']]
            );

            return $this->redirectWithSuccess(
                '/admin/backups',
                "Backup created successfully ({$meta['filename']}). SHA-256: " . substr($meta['sha256'], 0, 12) . '...'
            );
        } catch (Throwable $e) {
            return $this->redirectWithError('/admin/backups', 'Failed to generate backup: ' . $e->getMessage());
        }
    }

    public function download(Request $request, string $filename = ''): Response
    {
        $userContext = $request->getAttribute('user_context');
        if (!$userContext instanceof UserContext || !$this->policy->downloadBackup($userContext)) {
            return $this->forbidden('You are not authorized to download database backups.');
        }

        if (empty($filename)) {
            $filename = (string)$request->query('filename', '');
        }

        try {
            $path = $this->backupService->getBackupPath($filename);

            if ($path === null || !file_exists($path)) {
                return $this->notFound('Requested backup archive does not exist.');
            }

            // Verify integrity before serving
            $verification = $this->backupService->verifyBackup($filename);
            if (!$verification['valid']) {
                return $this->serverError('Backup integrity check failed: ' . $verification['message']);
            }

            // Log download action
            $this->auditService->log(
                'backup.downloaded',
                'backup',
                0,
                $userContext->id,
                null,
                ['filename' => $filename]
            );

            return Response::download($path, $filename, 'application/sql');
        } catch (InvalidArgumentException) {
            return $this->forbidden('Invalid filename or path traversal detected.');
        } catch (Throwable $e) {
            return $this->serverError('An error occurred while streaming the backup: ' . $e->getMessage());
        }
    }
}
