<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Core\Csrf;
use App\Core\Request;
use App\Core\Response;
use App\Middleware\CsrfMiddleware;
use PHPUnit\Framework\TestCase;

final class CsrfMiddlewareTest extends TestCase
{
    public function testAllowsGetRequestWithoutToken(): void
    {
        $middleware = new CsrfMiddleware();
        $request = new Request([], [], ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/login']);

        $response = $middleware->handle($request, function () {
            return Response::html('Login Form', 200);
        });

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testRejectsPostRequestWithoutValidToken(): void
    {
        $middleware = new CsrfMiddleware();
        $request = new Request([], ['email' => 'test@claret.edu'], ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/login']);

        $response = $middleware->handle($request, function () {
            return Response::html('Success', 200);
        });

        $this->assertSame(419, $response->getStatusCode());
        $this->assertStringContainsString('419 Page Expired', $response->getContent());
    }

    public function testAllowsPostRequestWithValidToken(): void
    {
        $token = Csrf::generate();
        $middleware = new CsrfMiddleware();
        $request = new Request([], ['_csrf_token' => $token], ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/login']);

        $response = $middleware->handle($request, function () {
            return Response::html('Success', 200);
        });

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('Success', $response->getContent());
    }
}
