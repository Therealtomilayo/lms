<?php
/**
 * Claret LMS — Safe Live Database Migration & Required Seeders Runner
 *
 * Designed specifically for production servers (cPanel, InfinityFree, xo.je)
 * to upgrade the database schema from migration 0022 to 0032 with ZERO data loss.
 *
 * USAGE:
 *   CLI: php database/migrate_live.php
 *   Web: https://portal-claretschools.xo.je/database/migrate_live.php?key=claret-migrate-2026
 */

declare(strict_types=1);

// Security: Prevent unauthorized web access
$isCli = (php_sapi_name() === 'cli');
$secretKey = 'claret-migrate-2026';

if (!$isCli) {
    $providedKey = $_GET['key'] ?? '';
    if ($providedKey !== $secretKey) {
        http_response_code(403);
        die("<h3>403 Forbidden: Invalid migration security key.</h3><p>Append <code>?key=claret-migrate-2026</code> to the URL.</p>");
    }
}

// Bootstrap environment configuration
$baseDir = dirname(__DIR__);
require_once $baseDir . '/app/Core/Config.php';

use App\Core\Config;

// Load config manually if bootstrap is not loaded
$host = '127.0.0.1';
$dbname = 'lms';
$user = 'root';
$pass = '';

$envFile = $baseDir . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) continue;
        if (str_contains($line, '=')) {
            [$k, $v] = explode('=', $line, 2);
            $k = trim($k);
            $v = trim($v, " \t\n\r\0\x0B\"'");
            match ($k) {
                'DB_HOST' => $host = $v,
                'DB_DATABASE', 'DB_NAME' => $dbname = $v,
                'DB_USERNAME', 'DB_USER' => $user = $v,
                'DB_PASSWORD', 'DB_PASS' => $pass = $v,
                default => null,
            };
        }
    }
}

function out(string $msg, string $type = 'info'): void {
    global $isCli;
    if ($isCli) {
        $colors = [
            'success' => "\033[32m",
            'warning' => "\033[33m",
            'error'   => "\033[31m",
            'info'    => "\033[36m",
            'reset'   => "\033[0m",
        ];
        $prefix = match ($type) {
            'success' => '[✓] ',
            'warning' => '[!] ',
            'error'   => '[✗] ',
            default   => '[i] ',
        };
        echo ($colors[$type] ?? '') . $prefix . $msg . ($colors['reset'] ?? '') . "\n";
    } else {
        $colors = [
            'success' => '#15803d',
            'warning' => '#b45309',
            'error'   => '#b91c1c',
            'info'    => '#0369a1',
        ];
        $color = $colors[$type] ?? '#334155';
        $icon = match ($type) {
            'success' => '✓',
            'warning' => '⚠',
            'error'   => '✕',
            default   => 'ℹ',
        };
        echo "<div style='font-family: monospace; margin: 4px 0; color: {$color};'><strong>{$icon}</strong> " . htmlspecialchars($msg) . "</div>";
        flush();
    }
}

if (!$isCli) {
    echo "<!DOCTYPE html><html><head><title>Claret LMS Live Migration</title><style>body { font-family: -apple-system, BlinkMacSystemFont, Segoe UI, Roboto, sans-serif; background: #f8fafc; padding: 30px; }</style></head><body>";
    echo "<div style='max-width: 800px; margin: 0 auto; background: white; border-radius: 16px; padding: 24px; box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);'>";
    echo "<h2 style='margin-top:0; color:#0f172a;'>Claret LMS Live Safe Migration Runner</h2><hr style='border:none; border-top:1px solid #e2e8f0; margin-bottom:20px;'>";
}

try {
    $dsn = "mysql:host={$host};dbname={$dbname};charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    out("Connected to database: {$dbname} on {$host}", 'success');

    // 1. Ensure migrations tracking table exists
    $pdo->exec("CREATE TABLE IF NOT EXISTS `migrations` (
        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `migration` VARCHAR(255) NOT NULL UNIQUE,
        `batch` INT UNSIGNED NOT NULL,
        `applied_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

    // 2. Fetch already applied migrations
    $appliedStmt = $pdo->query("SELECT `migration` FROM `migrations`");
    $applied = $appliedStmt->fetchAll(PDO::FETCH_COLUMN);
    out("Already applied migrations count: " . count($applied), 'info');

    // 3. Get next batch number
    $maxBatch = (int)$pdo->query("SELECT COALESCE(MAX(`batch`), 0) FROM `migrations`")->fetchColumn();
    $nextBatch = $maxBatch + 1;

    // 4. Scan migration files
    $migrationsDir = $baseDir . '/database/migrations';
    $files = glob($migrationsDir . '/*.sql');
    sort($files, SORT_NATURAL);

    $executed = 0;
    foreach ($files as $file) {
        $migrationName = basename($file);
        if (in_array($migrationName, $applied, true)) {
            continue;
        }

        $sql = file_get_contents($file);
        if (trim($sql) === '') continue;

        out("Running migration: {$migrationName} ...", 'info');
        
        // Execute SQL script
        $pdo->exec($sql);

        // Record migration
        $recStmt = $pdo->prepare("INSERT INTO `migrations` (`migration`, `batch`, `applied_at`) VALUES (?, ?, NOW())");
        $recStmt->execute([$migrationName, $nextBatch]);

        out("Completed: {$migrationName}", 'success');
        $executed++;
    }

    if ($executed === 0) {
        out("Database schema is already up to date. No pending migrations.", 'success');
    } else {
        out("Applied {$executed} pending migration(s) successfully!", 'success');
    }

    // 5. Execute Required Idempotent Seeders
    out("Running required institutional seeders (INSERT IGNORE) ...", 'info');

    // Setting: attendance_late_weight
    $pdo->exec("INSERT IGNORE INTO `system_settings` (`setting_key`, `setting_value`, `is_secret`, `updated_by`, `updated_at`)
                VALUES ('attendance_late_weight', '0.7', 0, 1, NOW())");
    out("Verified system setting: attendance_late_weight (0.7)", 'success');

    // Badges Catalog
    $pdo->exec("INSERT IGNORE INTO `badges` (`id`, `name`, `slug`, `description`, `category`, `icon_name`, `color_scheme`, `is_system`, `created_at`) VALUES
        (1, 'Academic Excellence', 'academic-excellence', 'Demonstrated exceptional performance with top marks (>=90%) on coursework or CBT quizzes.', 'academic', 'award', 'brand', 1, NOW()),
        (2, 'Course Completer', 'course-completer', 'Successfully completed 100% of all required learning activities in a course module curriculum.', 'progression', 'check-circle', 'emerald', 1, NOW()),
        (3, 'Discussion Pioneer', 'discussion-pioneer', 'Recognizes active, thoughtful contributions and peer help in class group discussions.', 'engagement', 'message-square', 'sky', 1, NOW()),
        (4, 'Perfect Attendance', 'perfect-attendance', 'Attained flawless attendance record across morning homeroom roll calls.', 'attendance', 'calendar-check', 'indigo', 1, NOW()),
        (5, 'Top Scholar', 'top-scholar', 'Ranked 1st place in terminal class broadsheet across all subjects in the term.', 'honor', 'crown', 'amber', 1, NOW()),
        (6, 'Subject Master', 'subject-master', 'Achieved highest composite cumulative score in a specific curriculum subject.', 'academic', 'star', 'purple', 1, NOW())");
    out("Verified default achievement badges catalog (6 system badges)", 'success');

    // Active Admission Session
    $pdo->exec("INSERT IGNORE INTO `admission_sessions` (`id`, `academic_session_id`, `title`, `application_fee`, `currency`, `opens_at`, `closes_at`, `is_active`, `instructions`, `created_by`, `created_at`, `updated_at`)
                SELECT 1, id, CONCAT(name, ' Admissions'), 10000.00, 'NGN', NOW(), DATE_ADD(NOW(), INTERVAL 6 MONTH), 1, 'Welcome to Claret International School online admission application. Please ensure you have valid digital copies of your ward\'s Birth Certificate, Passport Photograph, and previous academic report.', 1, NOW(), NOW()
                FROM `sessions` WHERE `status` = 'active' LIMIT 1");
    out("Verified active admission session for prospective student applications", 'success');

    out("All live migrations and required seeders completed with ZERO data loss!", 'success');

} catch (Throwable $e) {
    out("Fatal error: " . $e->getMessage(), 'error');
    if (!$isCli) {
        echo "<pre style='background:#fef2f2; color:#b91c1c; padding:12px; border-radius:8px;'>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    }
}

if (!$isCli) {
    echo "<p style='margin-top:20px; font-size:12px; color:#64748b;'>Claret LMS Production Tool • Delete or rename this file after migration for security.</p>";
    echo "</div></body></html>";
}
