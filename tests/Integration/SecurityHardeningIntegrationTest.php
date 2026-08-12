<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Core\Request;
use App\Core\Response;
use App\Middleware\RateLimitMiddleware;
use App\Middleware\SecurityHeadersMiddleware;
use App\Repositories\RateLimitRepository;
use App\Services\RateLimitService;
use PDO;
use PHPUnit\Framework\TestCase;

class SecurityHardeningIntegrationTest extends TestCase
{
    private PDO $pdo;
    private RateLimitRepository $rateLimitRepo;
    private RateLimitService $rateLimitService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS rate_limits (
                `key` VARCHAR(255) PRIMARY KEY,
                `hits` INTEGER NOT NULL DEFAULT 1,
                `expires_at` INTEGER NOT NULL
            );
        ");

        $this->rateLimitRepo = new RateLimitRepository($this->pdo);
        $this->rateLimitService = new RateLimitService($this->rateLimitRepo);
        RateLimitService::setEnabled(true);
    }

    protected function tearDown(): void
    {
        RateLimitService::setEnabled(true);
        parent::tearDown();
    }

    public function testSecurityHeadersMiddlewareInjectsProductionHeaders(): void
    {
        $middleware = new SecurityHeadersMiddleware();
        $request = new Request(serverParams: ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/', 'HTTPS' => 'on']);

        $response = $middleware->handle($request, function (Request $req) {
            return Response::html('<h1>Test</h1>');
        });

        $headers = $response->getHeaders();

        $this->assertSame('nosniff', $headers['X-Content-Type-Options']);
        $this->assertSame('SAMEORIGIN', $headers['X-Frame-Options']);
        $this->assertSame('strict-origin-when-cross-origin', $headers['Referrer-Policy']);
        $this->assertStringContainsString('max-age=31536000', $headers['Strict-Transport-Security']);
        $this->assertArrayHasKey('Content-Security-Policy', $headers);
        $this->assertStringContainsString("default-src 'self'", $headers['Content-Security-Policy']);
        $this->assertStringContainsString("script-src 'self'", $headers['Content-Security-Policy']);
    }

    public function testRateLimitingEnforcementAnd429View(): void
    {
        $middleware = new RateLimitMiddleware(
            action: 'test:action',
            maxAttempts: 3,
            decaySeconds: 60,
            service: $this->rateLimitService
        );

        $request = new Request(
            postParams: [],
            serverParams: ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/login', 'REMOTE_ADDR' => '192.168.1.50']
        );

        // Hits 1, 2, 3 succeed
        for ($i = 1; $i <= 3; $i++) {
            $response = $middleware->handle($request, function (Request $req) {
                return Response::json(['status' => 'ok']);
            });
            $this->assertSame(200, $response->getStatusCode());
        }

        // Hit 4 is throttled
        $throttledResponse = $middleware->handle($request, function (Request $req) {
            return Response::json(['status' => 'ok']);
        });

        $this->assertSame(429, $throttledResponse->getStatusCode());
        $this->assertArrayHasKey('Retry-After', $throttledResponse->getHeaders());
        $this->assertStringContainsString('Too Many Requests', $throttledResponse->getContent());
    }

    public function testRateLimitingReturnsJsonForAjaxRequests(): void
    {
        $middleware = new RateLimitMiddleware(
            action: 'test:api',
            maxAttempts: 1,
            decaySeconds: 60,
            service: $this->rateLimitService
        );

        $request = new Request(
            serverParams: [
                'REQUEST_METHOD' => 'POST',
                'REQUEST_URI' => '/api/data',
                'HTTP_ACCEPT' => 'application/json',
                'REMOTE_ADDR' => '10.0.0.1'
            ]
        );

        // First pass
        $response1 = $middleware->handle($request, fn() => Response::json(['ok' => true]));
        $this->assertSame(200, $response1->getStatusCode());

        // Second pass (throttled)
        $response2 = $middleware->handle($request, fn() => Response::json(['ok' => true]));
        $this->assertSame(429, $response2->getStatusCode());
        $data = json_decode($response2->getContent(), true);
        $this->assertIsArray($data);
        $this->assertSame('TOO_MANY_REQUESTS', $data['code']);
    }
}
