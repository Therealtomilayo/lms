<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Config;
use App\Core\Database;
use InvalidArgumentException;
use PDO;
use RuntimeException;
use Throwable;

/**
 * Service for Database Backup Generation, Checksum Verification, and Integrity Validation
 */
class BackupService
{
    private PDO $db;
    private string $backupDir;

    public function __construct(?PDO $db = null, ?string $backupDir = null)
    {
        $this->db = $db ?? Database::getConnection();
        $baseStorage = Config::get('storage.path') ?: (dirname(__DIR__, 2) . '/storage');
        $this->backupDir = $backupDir ?? ($baseStorage . '/backups');
    }

    public function getBackupDir(): string
    {
        return $this->backupDir;
    }

    /**
     * Generate a new database dump with SHA-256 checksum and metadata.
     */
    public function createBackup(): array
    {
        if (!is_dir($this->backupDir)) {
            if (!@mkdir($this->backupDir, 0755, true) && !is_dir($this->backupDir)) {
                throw new RuntimeException("Failed to create backup directory: {$this->backupDir}");
            }
        }

        $timestamp = gmdate('Ymd_His');
        $random = bin2hex(random_bytes(4));
        $dbName = (string)Config::get('database.database', 'lms');
        $cleanDbName = preg_replace('/[^a-zA-Z0-9_-]/', '', $dbName);
        $filename = "backup_{$cleanDbName}_{$timestamp}_{$random}.sql";
        $filepath = $this->backupDir . '/' . $filename;

        $handle = fopen($filepath, 'w');
        if ($handle === false) {
            throw new RuntimeException("Cannot open backup file for writing: {$filepath}");
        }

        try {
            $driver = $this->db->getAttribute(PDO::ATTR_DRIVER_NAME);

            fwrite($handle, "-- LMS Database Dump\n");
            fwrite($handle, "-- Generated: " . gmdate('Y-m-d H:i:s') . " UTC\n");
            fwrite($handle, "-- Driver: " . $driver . "\n\n");

            if ($driver === 'sqlite') {
                $this->dumpSqlite($handle);
            } else {
                $this->dumpMysql($handle);
            }

            fclose($handle);
        } catch (Throwable $e) {
            fclose($handle);
            if (file_exists($filepath)) {
                @unlink($filepath);
            }
            throw new RuntimeException("Backup failed: " . $e->getMessage(), 0, $e);
        }

        $checksum = hash_file('sha256', $filepath);
        $sizeBytes = filesize($filepath);

        $meta = [
            'filename' => $filename,
            'size_bytes' => $sizeBytes,
            'sha256' => $checksum,
            'created_at' => gmdate('Y-m-d\TH:i:s\Z'),
        ];

        file_put_contents($filepath . '.meta.json', json_encode($meta, JSON_PRETTY_PRINT));

        return $meta;
    }

    private function dumpMysql($handle): void
    {
        fwrite($handle, "SET FOREIGN_KEY_CHECKS=0;\n");
        fwrite($handle, "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n");
        fwrite($handle, "SET time_zone = \"+00:00\";\n\n");

        $tablesStmt = $this->db->query("SHOW TABLES");
        $tables = $tablesStmt->fetchAll(PDO::FETCH_COLUMN);

        foreach ($tables as $table) {
            fwrite($handle, "-- --------------------------------------------------------\n");
            fwrite($handle, "-- Table structure for table `{$table}`\n");
            fwrite($handle, "-- --------------------------------------------------------\n");
            fwrite($handle, "DROP TABLE IF EXISTS `{$table}`;\n");

            $createStmt = $this->db->query("SHOW CREATE TABLE `{$table}`");
            $createRow = $createStmt->fetch(PDO::FETCH_ASSOC);
            $createSql = $createRow['Create Table'] ?? '';
            fwrite($handle, $createSql . ";\n\n");

            fwrite($handle, "-- Dumping data for table `{$table}`\n");
            $rowsStmt = $this->db->query("SELECT * FROM `{$table}`");
            $rowsStmt->setFetchMode(PDO::FETCH_ASSOC);

            while ($row = $rowsStmt->fetch()) {
                $keys = array_map(fn($k) => "`{$k}`", array_keys($row));
                $values = array_map(function ($v) {
                    if ($v === null) {
                        return 'NULL';
                    }
                    return $this->db->quote((string)$v);
                }, array_values($row));

                $insertSql = sprintf(
                    "INSERT INTO `%s` (%s) VALUES (%s);\n",
                    $table,
                    implode(', ', $keys),
                    implode(', ', $values)
                );
                fwrite($handle, $insertSql);
            }
            fwrite($handle, "\n");
        }

        fwrite($handle, "SET FOREIGN_KEY_CHECKS=1;\n");
    }

    private function dumpSqlite($handle): void
    {
        $tablesStmt = $this->db->query("SELECT name, sql FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");
        $tables = $tablesStmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($tables as $table) {
            $name = $table['name'];
            $sql = $table['sql'];

            fwrite($handle, "DROP TABLE IF EXISTS `{$name}`;\n");
            fwrite($handle, $sql . ";\n\n");

            $rowsStmt = $this->db->query("SELECT * FROM `{$name}`");
            while ($row = $rowsStmt->fetch(PDO::FETCH_ASSOC)) {
                $keys = array_map(fn($k) => "`{$k}`", array_keys($row));
                $values = array_map(function ($v) {
                    if ($v === null) {
                        return 'NULL';
                    }
                    return $this->db->quote((string)$v);
                }, array_values($row));

                $insertSql = sprintf(
                    "INSERT INTO `%s` (%s) VALUES (%s);\n",
                    $name,
                    implode(', ', $keys),
                    implode(', ', $values)
                );
                fwrite($handle, $insertSql);
            }
            fwrite($handle, "\n");
        }
    }

    /**
     * List all available backup files with metadata.
     */
    public function listBackups(): array
    {
        if (!is_dir($this->backupDir)) {
            return [];
        }

        $files = glob($this->backupDir . '/*.sql');
        rsort($files); // latest first

        $backups = [];
        foreach ($files as $file) {
            $filename = basename($file);
            $metaFile = $file . '.meta.json';

            $sizeBytes = filesize($file);
            $createdAt = gmdate('Y-m-d\TH:i:s\Z', filemtime($file));
            $sha256 = null;

            if (file_exists($metaFile)) {
                $metaContent = json_decode((string)file_get_contents($metaFile), true);
                if (is_array($metaContent)) {
                    $sha256 = $metaContent['sha256'] ?? null;
                    $createdAt = $metaContent['created_at'] ?? $createdAt;
                }
            }

            if ($sha256 === null) {
                $sha256 = hash_file('sha256', $file);
            }

            $backups[] = [
                'filename' => $filename,
                'size_bytes' => $sizeBytes,
                'size_formatted' => $this->formatBytes($sizeBytes),
                'sha256' => $sha256,
                'created_at' => $createdAt,
            ];
        }

        return $backups;
    }

    /**
     * Retrieve the verified absolute file path for a safe backup filename.
     */
    public function getBackupPath(string $filename): ?string
    {
        // Enforce strict alphanumeric + underscore + dot filename
        if (!preg_match('/^[a-zA-Z0-9_.-]+\.sql$/', $filename)) {
            throw new InvalidArgumentException("Invalid backup filename format.");
        }

        // Prevent path traversal
        if (str_contains($filename, '..') || str_contains($filename, '/') || str_contains($filename, '\\')) {
            throw new InvalidArgumentException("Path traversal prohibited.");
        }

        $fullPath = realpath($this->backupDir . '/' . $filename);
        $realBackupDir = realpath($this->backupDir);

        if ($fullPath === false || $realBackupDir === false || !str_starts_with($fullPath, $realBackupDir)) {
            return null;
        }

        if (!file_exists($fullPath)) {
            return null;
        }

        return $fullPath;
    }

    /**
     * Verify backup integrity and checksum.
     */
    public function verifyBackup(string $filename): array
    {
        $path = $this->getBackupPath($filename);
        if ($path === null) {
            return [
                'valid' => false,
                'message' => 'Backup file not found',
            ];
        }

        $calculatedHash = hash_file('sha256', $path);
        $metaFile = $path . '.meta.json';

        if (file_exists($metaFile)) {
            $meta = json_decode((string)file_get_contents($metaFile), true);
            $expectedHash = $meta['sha256'] ?? null;

            if ($expectedHash !== null && !hash_equals($expectedHash, $calculatedHash)) {
                return [
                    'valid' => false,
                    'message' => 'Checksum mismatch. Backup file may be corrupted.',
                    'calculated_sha256' => $calculatedHash,
                    'expected_sha256' => $expectedHash,
                ];
            }
        }

        // Basic structural validation: check for SQL content
        $sample = (string)file_get_contents($path, false, null, 0, 512);
        if (!str_contains($sample, 'LMS Database Dump') && !str_contains($sample, 'TABLE')) {
            return [
                'valid' => false,
                'message' => 'File header does not match valid LMS database dump format.',
            ];
        }

        return [
            'valid' => true,
            'message' => 'Backup checksum and structure verified successfully.',
            'sha256' => $calculatedHash,
            'size_bytes' => filesize($path),
        ];
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 2) . ' MB';
        }
        if ($bytes >= 1024) {
            return round($bytes / 1024, 2) . ' KB';
        }
        return $bytes . ' B';
    }
}
