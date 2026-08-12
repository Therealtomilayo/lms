<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/vendor/autoload.php';
require_once dirname(__DIR__) . '/config/config.php';

use App\Services\BackupService;

echo "=== Claret LMS Database Backup Runner ===\n";
echo "Timestamp: " . gmdate('Y-m-d H:i:s') . " UTC\n\n";

try {
    $backupService = new BackupService();
    echo "Creating database dump...\n";
    $result = $backupService->createBackup();

    echo "SUCCESS: Backup created successfully.\n";
    echo "Filename:   " . $result['filename'] . "\n";
    echo "Size:       " . $result['size_bytes'] . " bytes\n";
    echo "SHA-256:    " . $result['sha256'] . "\n";
    echo "Created At: " . $result['created_at'] . "\n\n";
    exit(0);
} catch (Throwable $e) {
    echo "ERROR: Backup failed: " . $e->getMessage() . "\n";
    exit(1);
}
