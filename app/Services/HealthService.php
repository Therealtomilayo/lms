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
        try {
            $dbCheck = $this->checkDatabase();
        } catch (Throwable $e) {
            $dbCheck = [
                'status' => 'critical',
                'latency_ms' => null,
                'driver' => null,
                'message' => 'Database check error: ' . $e->getMessage(),
            ];
        }
        $checks['database'] = $dbCheck;
        if ($dbCheck['status'] === 'critical') {
            $overallStatus = 'critical';
        } elseif ($dbCheck['status'] === 'degraded' && $overallStatus !== 'critical') {
            $overallStatus = 'degraded';
        }

        // 2. Storage Check
        try {
            $storageCheck = $this->checkStorage();
        } catch (Throwable $e) {
            $storageCheck = [
                'status' => 'warning',
                'uploads_writable' => false,
                'logs_writable' => false,
                'backups_writable' => false,
                'free_space_mb' => null,
                'total_space_mb' => null,
                'message' => 'Storage check error: ' . $e->getMessage(),
            ];
        }
        $checks['storage'] = $storageCheck;
        if ($storageCheck['status'] === 'critical') {
            $overallStatus = 'critical';
        } elseif ($storageCheck['status'] === 'warning' && $overallStatus === 'healthy') {
            $overallStatus = 'warning';
        }

        // 3. Backup Freshness Check
        try {
            $backupCheck = $this->checkBackups();
        } catch (Throwable $e) {
            $backupCheck = [
                'status' => 'warning',
                'total_backups' => 0,
                'last_backup_at' => null,
                'freshness_hours' => null,
                'message' => 'Backup check error: ' . $e->getMessage(),
            ];
        }
        $checks['backups'] = $backupCheck;
        if ($backupCheck['status'] === 'warning' && $overallStatus === 'healthy') {
            $overallStatus = 'warning';
        }

        // 4. Session & Security Check
        try {
            $sessionCheck = $this->checkSession();
        } catch (Throwable $e) {
            $sessionCheck = [
                'status' => 'healthy',
                'session_active' => false,
                'cookie_name' => 'lms_session',
                'lifetime_seconds' => 7200,
                'secure_cookie' => false,
            ];
        }
        $checks['session'] = $sessionCheck;

        // 5. Environment Check
        try {
            $envCheck = $this->checkEnvironment();
        } catch (Throwable $e) {
            $envCheck = [
                'status' => 'healthy',
                'environment' => 'production',
                'debug' => false,
                'message' => 'Environment check error: ' . $e->getMessage(),
            ];
        }
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
        try {
            $uploadsDir = $this->storagePath . '/uploads';
            $logsDir = $this->storagePath . '/logs';
            $backupsDir = $this->backupPath;

            $uploadsWritable = is_dir($uploadsDir) && is_writable($uploadsDir);
            $logsWritable = is_dir($logsDir) && is_writable($logsDir);
            $backupsWritable = is_dir($backupsDir) && is_writable($backupsDir);

            $freeBytes = false;
            if (\function_exists('disk_free_space')) {
                try {
                    $freeBytes = @\disk_free_space($this->storagePath);
                } catch (Throwable $e) {
                    $freeBytes = false;
                }
            }

            $totalBytes = false;
            if (\function_exists('disk_total_space')) {
                try {
                    $totalBytes = @\disk_total_space($this->storagePath);
                } catch (Throwable $e) {
                    $totalBytes = false;
                }
            }

            $allWritable = $uploadsWritable && $logsWritable && $backupsWritable;

            return [
                'status' => $allWritable ? 'healthy' : 'critical',
                'uploads_writable' => $uploadsWritable,
                'logs_writable' => $logsWritable,
                'backups_writable' => $backupsWritable,
                'free_space_mb' => ($freeBytes !== false && $freeBytes !== null) ? round($freeBytes / 1048576, 2) : null,
                'total_space_mb' => ($totalBytes !== false && $totalBytes !== null) ? round($totalBytes / 1048576, 2) : null,
                'message' => $allWritable ? 'All storage directories writable' : 'One or more storage directories unwritable',
            ];
        } catch (Throwable $e) {
            return [
                'status' => 'warning',
                'uploads_writable' => false,
                'logs_writable' => false,
                'backups_writable' => false,
                'free_space_mb' => null,
                'total_space_mb' => null,
                'message' => 'Storage check error: ' . $e->getMessage(),
            ];
        }
    }

    private function checkBackups(): array
    {
        try {
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
            $count = is_array($files) ? count($files) : 0;

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
                $mtime = @filemtime($file);
                if ($mtime && $mtime > $latestTime) {
                    $latestTime = $mtime;
                }
            }

            $ageHours = $latestTime > 0 ? round((time() - $latestTime) / 3600, 1) : null;
            $status = ($ageHours !== null && $ageHours > 48) ? 'warning' : 'healthy';

            return [
                'status' => $status,
                'total_backups' => $count,
                'last_backup_at' => $latestTime > 0 ? gmdate('Y-m-d\TH:i:s\Z', $latestTime) : null,
                'freshness_hours' => $ageHours,
                'message' => $status === 'healthy' ? 'Recent backup present' : "Latest backup is {$ageHours} hours old",
            ];
        } catch (Throwable $e) {
            return [
                'status' => 'warning',
                'total_backups' => 0,
                'last_backup_at' => null,
                'freshness_hours' => null,
                'message' => 'Backup check error: ' . $e->getMessage(),
            ];
        }
    }

    private function checkSession(): array
    {
        try {
            return [
                'status' => 'healthy',
                'session_active' => session_status() === PHP_SESSION_ACTIVE,
                'cookie_name' => Config::get('session.cookie_name', 'lms_session'),
                'lifetime_seconds' => Config::get('session.lifetime', 7200),
                'secure_cookie' => (bool)Config::get('session.secure', false),
            ];
        } catch (Throwable $e) {
            return [
                'status' => 'healthy',
                'session_active' => false,
                'cookie_name' => 'lms_session',
                'lifetime_seconds' => 7200,
                'secure_cookie' => false,
            ];
        }
    }

    private function checkEnvironment(): array
    {
        try {
            $env = (string)Config::get('app.env', 'production');
            $debug = (bool)Config::get('app.debug', false);

            $hasWarning = ($env === 'production' && $debug === true);

            return [
                'status' => $hasWarning ? 'warning' : 'healthy',
                'environment' => $env,
                'debug' => $debug,
                'message' => $hasWarning ? 'Debug mode enabled in production environment' : 'Environment settings optimal',
            ];
        } catch (Throwable $e) {
            return [
                'status' => 'healthy',
                'environment' => 'production',
                'debug' => false,
                'message' => 'Environment check error: ' . $e->getMessage(),
            ];
        }
    }
}
