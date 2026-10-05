<?php

declare(strict_types=1);

/**
 * Claret LMS — Master Periodic Scheduled Task Runner (Cron Job)
 *
 * Usage:
 *   CLI: php bin/cron.php
 *   CLI with forced backup: php bin/cron.php --backup
 *   cPanel / Crontab: * * * * * /usr/bin/php /path/to/lms/bin/cron.php >/dev/null 2>&1
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    echo "Forbidden: CLI access only.\n";
    exit(1);
}

$startTime = microtime(true);

require_once dirname(__DIR__) . '/vendor/autoload.php';
require_once dirname(__DIR__) . '/config/config.php';

use App\Core\Database;
use App\Services\BackupService;
use App\Services\NotificationService;

echo "=======================================================\n";
echo "   CLARET INTERNATIONAL SCHOOL LMS — SCHEDULED TASKS   \n";
echo "=======================================================\n";
echo "Started At: " . date('Y-m-d H:i:s T') . "\n\n";

$tasksExecuted = 0;
$tasksFailed = 0;
$db = Database::getInstance();

// -----------------------------------------------------------------------------
// TASK 1: Purge Expired Password Reset Tokens & Stale Sessions
// -----------------------------------------------------------------------------
echo "[Task 1/4] Purging expired authentication tokens...\n";
try {
    $stmt = $db->prepare("DELETE FROM password_reset_tokens WHERE expires_at < NOW()");
    $stmt->execute();
    $purgedTokens = $stmt->rowCount();
    echo "  -> Cleaned {$purgedTokens} expired reset tokens.\n";
    $tasksExecuted++;
} catch (Throwable $e) {
    echo "  -> Error cleaning tokens: " . $e->getMessage() . "\n";
    $tasksFailed++;
}

// -----------------------------------------------------------------------------
// TASK 2: Scan for Low Attendance Thresholds (< 75% Statutory Rule)
// -----------------------------------------------------------------------------
echo "\n[Task 2/4] Scanning student attendance compliance...\n";
try {
    // Find active term
    $termStmt = $db->query("
        SELECT t.id, t.name 
        FROM terms t 
        JOIN sessions s ON s.id = t.session_id 
        WHERE t.status = 'active' OR (s.status = 'active' AND t.status != 'archived')
        ORDER BY (t.status = 'active') DESC, t.id DESC 
        LIMIT 1
    ");
    $currentTerm = $termStmt->fetch(PDO::FETCH_ASSOC);

    if ($currentTerm) {
        $termId = (int)$currentTerm['id'];
        // Query aggregate attendance per student for current term
        $attQuery = "
            SELECT 
                s.id AS student_id,
                u.name AS student_name,
                c.name AS class_name,
                COUNT(ar.id) AS total_sessions,
                SUM(CASE WHEN ar.status = 'present' THEN 1 ELSE 0 END) AS present_count,
                SUM(CASE WHEN ar.status = 'late' THEN 1 ELSE 0 END) AS late_count,
                SUM(CASE WHEN ar.status = 'absent' THEN 1 ELSE 0 END) AS absent_count
            FROM students s
            JOIN users u ON u.id = s.user_id
            JOIN class_enrollments ce ON ce.student_id = s.id AND ce.status = 'active'
            JOIN classes c ON c.id = ce.class_id
            LEFT JOIN attendance_records ar ON ar.student_id = s.id AND ar.term_id = :term_id
            WHERE u.status = 'active'
            GROUP BY s.id, u.name, c.name
            HAVING total_sessions >= 10
        ";
        $attStmt = $db->prepare($attQuery);
        $attStmt->execute(['term_id' => $termId]);
        $rows = $attStmt->fetchAll(PDO::FETCH_ASSOC);

        $flaggedCount = 0;
        foreach ($rows as $row) {
            $total = (int)$row['total_sessions'];
            if ($total <= 0) continue;
            // Weighted formula: present = 100%, late = 60%
            $weightedRate = round((((int)$row['present_count'] * 1.0) + ((int)$row['late_count'] * 0.6)) / $total * 100, 1);
            if ($weightedRate < 75.0) {
                $flaggedCount++;
            }
        }
        echo "  -> Scanned " . count($rows) . " active students in term '{$currentTerm['name']}'. Flagged {$flaggedCount} below 75% minimum.\n";
    } else {
        echo "  -> No active term currently designated. Skipping attendance scan.\n";
    }
    $tasksExecuted++;
} catch (Throwable $e) {
    echo "  -> Error scanning attendance: " . $e->getMessage() . "\n";
    $tasksFailed++;
}

// -----------------------------------------------------------------------------
// TASK 3: Check Overdue Fee Invoices
// -----------------------------------------------------------------------------
echo "\n[Task 3/4] Checking pending and overdue school fee invoices...\n";
try {
    // Check if fee_invoices table exists
    $tableCheck = $db->query("SHOW TABLES LIKE 'fee_invoices'")->fetch();
    if ($tableCheck) {
        $invStmt = $db->query("
            SELECT COUNT(*) AS overdue_count 
            FROM fee_invoices 
            WHERE status IN ('unpaid', 'partially_paid') 
              AND due_date IS NOT NULL 
              AND due_date < CURDATE()
        ");
        $overdueCount = (int)($invStmt->fetch(PDO::FETCH_ASSOC)['overdue_count'] ?? 0);
        echo "  -> Verified invoices: {$overdueCount} overdue invoice(s) currently outstanding.\n";
    } else {
        echo "  -> fee_invoices table not yet initialized.\n";
    }
    $tasksExecuted++;
} catch (Throwable $e) {
    echo "  -> Error checking invoices: " . $e->getMessage() . "\n";
    $tasksFailed++;
}

// -----------------------------------------------------------------------------
// TASK 4: Nightly Automated Database Backup
// -----------------------------------------------------------------------------
$runBackup = in_array('--backup', $argv ?? [], true);
$currentHour = (int)date('G');
// Run backup if explicitly requested via CLI flag or at 02:00 AM WAT
if ($runBackup || ($currentHour === 2 && (int)date('i') <= 5)) {
    echo "\n[Task 4/4] Generating scheduled database backup...\n";
    try {
        $backupService = new BackupService();
        $backupResult = $backupService->createBackup();
        echo "  -> Backup generated successfully: {$backupResult['filename']} (" . number_format($backupResult['size_bytes'] / 1024, 1) . " KB)\n";
        $tasksExecuted++;
    } catch (Throwable $e) {
        echo "  -> Backup error: " . $e->getMessage() . "\n";
        $tasksFailed++;
    }
} else {
    echo "\n[Task 4/4] Automated database backup scheduled for 02:00 AM WAT (use --backup to force now).\n";
}

$duration = round(microtime(true) - $startTime, 3);
$memory = round(memory_get_peak_usage(true) / 1024 / 1024, 2);

echo "\n=======================================================\n";
echo "Cron completed in {$duration}s | Peak Memory: {$memory} MB\n";
echo "Tasks Succeeded: {$tasksExecuted} | Failures: {$tasksFailed}\n";
echo "=======================================================\n";

exit($tasksFailed > 0 ? 1 : 0);
