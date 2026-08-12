<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use Throwable;

/**
 * Deterministic Database Migration Runner
 */
class Migrator
{
    private PDO $pdo;
    private string $migrationsPath;

    public function __construct(?PDO $pdo = null, ?string $migrationsPath = null)
    {
        $this->pdo = $pdo ?? Database::getInstance();
        $this->migrationsPath = $migrationsPath ?? dirname(__DIR__, 2) . '/database/migrations';
    }

    public static function createDatabaseIfNotExists(): void
    {
        $driver = Config::get('database.driver', 'mysql');
        if ($driver !== 'mysql') {
            return;
        }

        $host = Config::get('database.host', '127.0.0.1');
        $port = (int)Config::get('database.port', 3306);
        $database = (string)Config::get('database.database', 'lms');
        $username = (string)Config::get('database.username', 'root');
        $password = (string)Config::get('database.password', '');
        $charset = (string)Config::get('database.charset', 'utf8mb4');
        $collation = (string)Config::get('database.collation', 'utf8mb4_unicode_ci');

        $dsn = sprintf('mysql:host=%s;port=%d;charset=%s', $host, $port, $charset);
        $pdo = new PDO($dsn, $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);

        $safeDbName = str_replace('`', '``', $database);
        $pdo->exec(sprintf('CREATE DATABASE IF NOT EXISTS `%s` CHARACTER SET %s COLLATE %s;', $safeDbName, $charset, $collation));
    }

    public function ensureMigrationTable(): void
    {
        $driver = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

        if ($driver === 'sqlite') {
            $sql = "CREATE TABLE IF NOT EXISTS `migrations` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `migration` VARCHAR(255) NOT NULL UNIQUE,
                `batch` INTEGER NOT NULL,
                `applied_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            );";
        } else {
            $sql = "CREATE TABLE IF NOT EXISTS `migrations` (
                `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `migration` VARCHAR(255) NOT NULL UNIQUE,
                `batch` INT UNSIGNED NOT NULL,
                `applied_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        }

        $this->pdo->exec($sql);
    }

    public function getAppliedMigrations(): array
    {
        $this->ensureMigrationTable();

        $stmt = $this->pdo->query("SELECT `migration` FROM `migrations` ORDER BY `id` ASC");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function getNextBatchNumber(): int
    {
        $this->ensureMigrationTable();

        $stmt = $this->pdo->query("SELECT MAX(`batch`) as max_batch FROM `migrations`");
        $max = $stmt->fetchColumn();

        return $max !== false && $max !== null ? ((int)$max + 1) : 1;
    }

    public function run(): array
    {
        $this->ensureMigrationTable();
        $applied = $this->getAppliedMigrations();
        $batch = $this->getNextBatchNumber();

        if (!is_dir($this->migrationsPath)) {
            return [];
        }

        $files = glob($this->migrationsPath . '/*.sql');
        sort($files, SORT_NATURAL);

        $executed = [];

        foreach ($files as $file) {
            $migrationName = basename($file);

            if (in_array($migrationName, $applied, true)) {
                continue;
            }

            $sql = file_get_contents($file);
            if ($sql === false || trim($sql) === '') {
                continue;
            }

            // Execute SQL script
            $this->pdo->exec($sql);

            // Record migration
            $stmt = $this->pdo->prepare("INSERT INTO `migrations` (`migration`, `batch`) VALUES (:migration, :batch)");
            $stmt->execute([
                ':migration' => $migrationName,
                ':batch' => $batch,
            ]);

            $executed[] = $migrationName;
        }

        return $executed;
    }
}
