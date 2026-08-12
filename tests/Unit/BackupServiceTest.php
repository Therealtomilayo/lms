<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\BackupService;
use InvalidArgumentException;
use PDO;
use PHPUnit\Framework\TestCase;

class BackupServiceTest extends TestCase
{
    private string $tempBackupDir;
    private PDO $pdo;
    private BackupService $backupService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tempBackupDir = sys_get_temp_dir() . '/lms_backups_' . uniqid();
        @mkdir($this->tempBackupDir, 0755, true);

        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Create sample schema & rows
        $this->pdo->exec("
            CREATE TABLE test_users (
                id INTEGER PRIMARY KEY,
                name TEXT NOT NULL,
                email TEXT NOT NULL
            );
            INSERT INTO test_users (id, name, email) VALUES (1, 'Alice', 'alice@example.com');
            INSERT INTO test_users (id, name, email) VALUES (2, 'Bob', 'bob@example.com');
        ");

        $this->backupService = new BackupService($this->pdo, $this->tempBackupDir);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->tempBackupDir)) {
            $files = scandir($this->tempBackupDir);
            foreach ($files as $file) {
                if ($file !== '.' && $file !== '..') {
                    @unlink($this->tempBackupDir . '/' . $file);
                }
            }
            @rmdir($this->tempBackupDir);
        }
        parent::tearDown();
    }

    public function testCreateBackupGeneratesSqlAndChecksum(): void
    {
        $result = $this->backupService->createBackup();

        $this->assertArrayHasKey('filename', $result);
        $this->assertArrayHasKey('size_bytes', $result);
        $this->assertArrayHasKey('sha256', $result);
        $this->assertArrayHasKey('created_at', $result);

        $backupFilePath = $this->tempBackupDir . '/' . $result['filename'];
        $this->assertFileExists($backupFilePath);
        $this->assertFileExists($backupFilePath . '.meta.json');

        $content = file_get_contents($backupFilePath);
        $this->assertStringContainsString('LMS Database Dump', $content);
        $this->assertStringContainsString('test_users', $content);
        $this->assertStringContainsString('alice@example.com', $content);
    }

    public function testListBackupsReturnsMetadata(): void
    {
        $this->backupService->createBackup();
        $list = $this->backupService->listBackups();

        $this->assertCount(1, $list);
        $this->assertStringStartsWith('backup_', $list[0]['filename']);
        $this->assertNotEmpty($list[0]['sha256']);
        $this->assertNotEmpty($list[0]['size_formatted']);
    }

    public function testGetBackupPathRejectsPathTraversal(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->backupService->getBackupPath('../../etc/passwd.sql');
    }

    public function testVerifyBackupChecksIntegrity(): void
    {
        $result = $this->backupService->createBackup();
        $verification = $this->backupService->verifyBackup($result['filename']);

        $this->assertTrue($verification['valid']);
        $this->assertSame($result['sha256'], $verification['sha256']);
    }
}
