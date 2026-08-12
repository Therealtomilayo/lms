<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\HealthService;
use PDO;
use PHPUnit\Framework\TestCase;

class HealthServiceTest extends TestCase
{
    private string $tempDir;
    private HealthService $healthService;
    private PDO $pdo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tempDir = sys_get_temp_dir() . '/lms_health_test_' . uniqid();
        @mkdir($this->tempDir . '/uploads', 0755, true);
        @mkdir($this->tempDir . '/logs', 0755, true);
        @mkdir($this->tempDir . '/backups', 0755, true);

        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $this->healthService = new HealthService(
            $this->pdo,
            $this->tempDir,
            $this->tempDir . '/backups'
        );
    }

    protected function tearDown(): void
    {
        $this->deleteDir($this->tempDir);
        parent::tearDown();
    }

    private function deleteDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $files = scandir($dir);
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            $path = $dir . '/' . $file;
            if (is_dir($path)) {
                $this->deleteDir($path);
            } else {
                @unlink($path);
            }
        }
        @rmdir($dir);
    }

    public function testMinimalPublicPing(): void
    {
        $ping = $this->healthService->ping();

        $this->assertSame('healthy', $ping['status']);
        $this->assertArrayHasKey('timestamp', $ping);
        $this->assertArrayNotHasKey('database', $ping);
        $this->assertArrayNotHasKey('storage', $ping);
    }

    public function testDeepHealthCheckWithHealthyDatabaseAndStorage(): void
    {
        // Place a fresh backup file
        file_put_contents($this->tempDir . '/backups/backup_test.sql', 'CREATE TABLE test (id INT);');

        $result = $this->healthService->checkDeepHealth();

        $this->assertTrue($result->isHealthy());
        $this->assertSame('healthy', $result->status);
        $this->assertArrayHasKey('database', $result->checks);
        $this->assertSame('healthy', $result->checks['database']['status']);
        $this->assertArrayHasKey('storage', $result->checks);
        $this->assertSame('healthy', $result->checks['storage']['status']);
        $this->assertSame('healthy', $result->checks['backups']['status']);
        $this->assertSame(1, $result->checks['backups']['total_backups']);
    }

    public function testBackupWarningWhenNoBackupsExist(): void
    {
        $result = $this->healthService->checkDeepHealth();

        $this->assertSame('warning', $result->checks['backups']['status']);
        $this->assertSame(0, $result->checks['backups']['total_backups']);
    }
}
