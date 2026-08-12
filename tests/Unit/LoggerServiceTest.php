<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\LoggerService;
use Exception;
use PHPUnit\Framework\TestCase;

class LoggerServiceTest extends TestCase
{
    private string $tempLogFile;
    private LoggerService $logger;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tempLogFile = sys_get_temp_dir() . '/test_lms_' . uniqid() . '.log';
        $this->logger = new LoggerService($this->tempLogFile);
        LoggerService::resetCorrelationId();
    }

    protected function tearDown(): void
    {
        if (file_exists($this->tempLogFile)) {
            @unlink($this->tempLogFile);
        }
        parent::tearDown();
    }

    public function testLogProducesStructuredRecord(): void
    {
        $record = $this->logger->info('User logged in', ['user_id' => 42]);

        $this->assertSame('info', $record['level']);
        $this->assertSame('User logged in', $record['message']);
        $this->assertNotEmpty($record['correlation_id']);
        $this->assertNotEmpty($record['timestamp']);
        $this->assertSame(42, $record['context']['user_id']);

        $this->assertFileExists($this->tempLogFile);
        $content = file_get_contents($this->tempLogFile);
        $decoded = json_decode($content, true);
        $this->assertIsArray($decoded);
        $this->assertSame('User logged in', $decoded['message']);
    }

    public function testSensitiveKeysAreRedacted(): void
    {
        $payload = [
            'username' => 'testuser',
            'password' => 'secret123',
            'nested' => [
                'token' => 'jwt_token_secret',
                'csrf_token' => 'token456',
                'safe' => 'visible',
            ],
        ];

        $record = $this->logger->warning('Auth failure', $payload);

        $this->assertSame('testuser', $record['context']['username']);
        $this->assertSame('[REDACTED]', $record['context']['password']);
        $this->assertSame('[REDACTED]', $record['context']['nested']['token']);
        $this->assertSame('[REDACTED]', $record['context']['nested']['csrf_token']);
        $this->assertSame('visible', $record['context']['nested']['safe']);
    }

    public function testLogException(): void
    {
        $exception = new Exception('Database connection failed', 500);
        $record = $this->logger->logException($exception, ['query' => 'SELECT 1']);

        $this->assertSame('error', $record['level']);
        $this->assertStringContainsString('Database connection failed', $record['message']);
        $this->assertArrayHasKey('exception', $record['context']);
        $this->assertSame('Exception', $record['context']['exception']['class']);
        $this->assertSame(500, $record['context']['exception']['code']);
    }

    public function testCorrelationIdConsistency(): void
    {
        LoggerService::setCorrelationId('req-custom-12345');
        $record1 = $this->logger->info('Step 1');
        $record2 = $this->logger->info('Step 2');

        $this->assertSame('req-custom-12345', $record1['correlation_id']);
        $this->assertSame('req-custom-12345', $record2['correlation_id']);
    }
}
