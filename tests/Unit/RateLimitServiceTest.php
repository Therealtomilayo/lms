<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Repositories\RateLimitRepository;
use App\Services\RateLimitService;
use PDO;
use PHPUnit\Framework\TestCase;

class RateLimitServiceTest extends TestCase
{
    private PDO $pdo;
    private RateLimitRepository $repository;
    private RateLimitService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $this->pdo->exec("
            CREATE TABLE rate_limits (
                `key` VARCHAR(255) PRIMARY KEY,
                `hits` INTEGER NOT NULL DEFAULT 1,
                `expires_at` INTEGER NOT NULL
            );
        ");

        $this->repository = new RateLimitRepository($this->pdo);
        $this->service = new RateLimitService($this->repository);
        RateLimitService::setEnabled(true);
    }

    public function testHitIncrementsCounter(): void
    {
        $key = 'test:user:1';
        $this->assertSame(1, $this->service->hit($key, 60));
        $this->assertSame(2, $this->service->hit($key, 60));
        $this->assertSame(3, $this->service->hit($key, 60));
        $this->assertSame(3, $this->repository->attempts($key));
    }

    public function testTooManyAttemptsEnforced(): void
    {
        $key = 'test:login:ip';
        $maxAttempts = 3;

        $this->assertFalse($this->service->tooManyAttempts($key, $maxAttempts));
        $this->service->hit($key, 60);
        $this->assertFalse($this->service->tooManyAttempts($key, $maxAttempts));
        $this->service->hit($key, 60);
        $this->assertFalse($this->service->tooManyAttempts($key, $maxAttempts));
        $this->service->hit($key, 60);
        $this->assertTrue($this->service->tooManyAttempts($key, $maxAttempts));
    }

    public function testRemainingCalculatedCorrectly(): void
    {
        $key = 'test:user:2';
        $this->assertSame(5, $this->service->remaining($key, 5));
        $this->service->hit($key, 60);
        $this->assertSame(4, $this->service->remaining($key, 5));
        $this->service->hit($key, 60);
        $this->assertSame(3, $this->service->remaining($key, 5));
    }

    public function testResetClearsKey(): void
    {
        $key = 'test:user:3';
        $this->service->hit($key, 60);
        $this->service->hit($key, 60);
        $this->assertSame(2, $this->repository->attempts($key));

        $this->service->reset($key);
        $this->assertSame(0, $this->repository->attempts($key));
    }

    public function testClearAllClearsAllRecords(): void
    {
        $this->service->hit('key:1', 60);
        $this->service->hit('key:2', 60);
        $this->assertSame(1, $this->repository->attempts('key:1'));
        $this->assertSame(1, $this->repository->attempts('key:2'));

        $this->service->clearAll();
        $this->assertSame(0, $this->repository->attempts('key:1'));
        $this->assertSame(0, $this->repository->attempts('key:2'));
    }

    public function testGenerateKeyIsDeterministic(): void
    {
        $key1 = $this->service->generateKey('login', 'admin@example.com', '192.168.1.1');
        $key2 = $this->service->generateKey('login', 'ADMIN@example.com', '192.168.1.1');
        $this->assertSame($key1, $key2);
        $this->assertStringStartsWith('login:id:', $key1);
        $this->assertStringEndsWith(':ip:192.168.1.1', $key1);
    }

    public function testDisablingRateLimiterBypassesChecks(): void
    {
        RateLimitService::setEnabled(false);
        $key = 'test:disabled';
        for ($i = 0; $i < 10; $i++) {
            $this->service->hit($key, 60);
        }
        $this->assertFalse($this->service->tooManyAttempts($key, 5));
    }

    protected function tearDown(): void
    {
        RateLimitService::setEnabled(true);
        parent::tearDown();
    }
}
