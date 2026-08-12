<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Config;
use App\Core\Database;
use App\DTO\HealthCheckResult;
use PDO;
use Throwable;

/**
 * Diagnostic Service for Monitoring Subsystem Health & Integrity
 */
class HealthService
{
    private ?PDO $db;
    private string $storagePath;
    private string $backupPath;

    public function __construct(?PDO $db = null, ?string $storagePath = null, ?string $backupPath = null)
    {
        $this->db = $db;
        $baseStorage = Config::get('storage.path') ?: (dirname(__DIR__, 2) . '/storage');
        $this->storagePath = $storagePath ?? $baseStorage;
        $this->backupPath = $backupPath ?? ($baseStorage . '/backups');
    }

    /**
     * Minimal public health ping.
     */
    public function ping(): array
    {
        return [
            'status' => 'healthy',
            'timestamp' => gmdate('Y-m-d\TH:i:s\Z'),
        ];
    }

    /**
     * Run deep system health diagnostics.
     */
    public function checkDeepHealth(): HealthCheckResult
    {
        $checks = [];
        $overallStatus = 'healthy';

        // 1. Database Check
        $dbCheck = $this->checkDatabase();
        $checks['database'] = $dbCheck;
        if ($dbCheck['status'] === 'critical') {
            $overallStatus = 'critical';
        } elseif ($dbCheck['status'] === 'degraded' && $overallStatus !== 'critical') {
            $overallStatus = 'degraded';
        }

        // 2. Storage Check
        $storageCheck = $this->checkStorage();
        $checks['storage'] = $storageCheck;
        if ($storageCheck['status'] === 'critical') {
            $overallStatus = 'critical';
        } elseif ($storageCheck['status'] === 'warning' && $overallStatus === 'healthy') {
            $overallStatus = 'warning';
        }

        // 3. Backup Freshness Check
        $backupCheck = $this->checkBackups();
        $checks['backups'] = $backupCheck;
        if ($backupCheck['status'] === 'warning' && $overallStatus === 'healthy') {
            $overallStatus = 'warning';
        }

        // 4. Session & Security Check
        $sessionCheck = $this->checkSession();
        $checks['session'] = $sessionCheck;

        // 5. Environment Check
        $envCheck = $this->checkEnvironment();
        $checks['environment'] = $envCheck;
        if ($envCheck['status'] === 'warning' && $overallStatus === 'healthy') {
            $overallStatus = 'warning';
        }

        return new HealthCheckResult($overallStatus, $checks);
    }

    private function checkDatabase(): array
    {
        $startTime = microtime(true);

        try {
            $pdo = $this->db ?? Database::getConnection();
            $stmt = $pdo->query("SELECT 1");
            $stmt->fetch();
            $latencyMs = round((microtime(true) - $startTime) * 1000, 2);

            return [
                'status' => $latencyMs > 500 ? 'degraded' : 'healthy',
                'latency_ms' => $latencyMs,
                'driver' => $pdo->getAttribute(PDO::ATTR_DRIVER_NAME),
                'message' => 'Database connection operational',
            ];
        } catch (Throwable $e) {
            return [
                'status' => 'critical',
                'latency_ms' => null,
                'driver' => null,
                'message' => 'Database connection failed: ' . $e->getMessage(),
            ];
        }
    }

    private function checkStorage(): array
    {
        $uploadsDir = $this->storagePath . '/uploads';
        $logsDir = $this->storagePath . '/logs';
        $backupsDir = $this->backupPath;

        $uploadsWritable = is_dir($uploadsDir) && is_writable($uploadsDir);
        $logsWritable = is_dir($logsDir) && is_writable($logsDir);
        $backupsWritable = is_dir($backupsDir) && is_writable($backupsDir);

        $freeBytes = @disk_free_space($this->storagePath);
        $totalBytes = @disk_total_space($this->storagePath);

        $allWritable = $uploadsWritable && $logsWritable && $backupsWritable;

        return [
            'status' => $allWritable ? 'healthy' : 'critical',
            'uploads_writable' => $uploadsWritable,
            'logs_writable' => $logsWritable,
            'backups_writable' => $backupsWritable,
            'free_space_mb' => $freeBytes !== false ? round($freeBytes / 1048576, 2) : null,
            'total_space_mb' => $totalBytes !== false ? round($totalBytes / 1048576, 2) : null,
            'message' => $allWritable ? 'All storage directories writable' : 'One or more storage directories unwritable',
        ];
    }

    private function checkBackups(): array
    {
        if (!is_dir($this->backupPath)) {
            return [
                'status' => 'warning',
                'total_backups' => 0,
                'last_backup_at' => null,
                'freshness_hours' => null,
                'message' => 'Backup directory does not exist yet',
            ];
        }

        $files = glob($this->backupPath . '/*.sql*');
        $count = count($files);

        if ($count === 0) {
            return [
                'status' => 'warning',
                'total_backups' => 0,
                'last_backup_at' => null,
                'freshness_hours' => null,
                'message' => 'No database backups found',
            ];
        }

        $latestTime = 0;
        foreach ($files as $file) {
            $mtime = filemtime($file);
            if ($mtime > $latestTime) {
                $latestTime = $mtime;
            }
        }

        $ageHours = round((time() - $latestTime) / 3600, 1);
        $status = $ageHours > 48 ? 'warning' : 'healthy';

        return [
            'status' => $status,
            'total_backups' => $count,
            'last_backup_at' => gmdate('Y-m-d\TH:i:s\Z', $latestTime),
            'freshness_hours' => $ageHours,
            'message' => $status === 'healthy' ? 'Recent backup present' : "Latest backup is {$ageHours} hours old",
        ];
    }

    private function checkSession(): array
    {
        return [
            'status' => 'healthy',
            'session_active' => session_status() === PHP_SESSION_ACTIVE,
            'cookie_name' => Config::get('session.cookie_name', 'lms_session'),
            'lifetime_seconds' => Config::get('session.lifetime', 7200),
            'secure_cookie' => (bool)Config::get('session.secure', false),
        ];
    }

    private function checkEnvironment(): array
    {
        $env = (string)Config::get('app.env', 'production');
        $debug = (bool)Config::get('app.debug', false);

        $hasWarning = ($env === 'production' && $debug === true);

        return [
            'status' => $hasWarning ? 'warning' : 'healthy',
            'environment' => $env,
            'debug' => $debug,
            'message' => $hasWarning ? 'Debug mode enabled in production environment' : 'Environment settings optimal',
        ];
    }
}
