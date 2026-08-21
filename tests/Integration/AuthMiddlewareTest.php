<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Core\AuthenticatorInterface;
use App\Core\Request;
use App\Core\Response;
use App\Core\UserContext;
use App\Middleware\AuthMiddleware;
use App\Middleware\RoleMiddleware;
use App\Models\User;
use PHPUnit\Framework\TestCase;

final class AuthMiddlewareTest extends TestCase
{
    private function createMockAuthenticator(?UserContext $context): AuthenticatorInterface
    {
        return new class($context) implements AuthenticatorInterface {
            public function __construct(private ?UserContext $context) {}
            public function authenticate(Request $request): ?UserContext { return $this->context; }
            public function check(Request $request): bool { return $this->context !== null; }
            public function user(Request $request): ?UserContext { return $this->context; }
            public function getUserContext(?Request $request = null): ?UserContext { return $this->context; }
        };
    }

    public function testRejectsUnauthenticatedRequest(): void
    {
        $middleware = new AuthMiddleware($this->createMockAuthenticator(null));
        $request = new Request([], [], ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/admin/dashboard']);

        $response = $middleware->handle($request, function () {
            return Response::html('Protected Content');
        });

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame('/login', $response->getHeader('Location'));
    }

    public function testEnforcesMustChangePassword(): void
    {
        $user = new User(1, 'u-1', 'Test', 'test@claret.edu', null, 'hash', 'active', true, null, null, ['teacher']);
        $context = UserContext::fromUser($user);

        $middleware = new AuthMiddleware($this->createMockAuthenticator($context));
        $request = new Request([], [], ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/teacher/dashboard']);

        $response = $middleware->handle($request, function () {
            return Response::html('Dashboard');
        });

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame('/profile/password', $response->getHeader('Location'));
    }

    public function testRoleMiddlewareAllowsAuthorizedRole(): void
    {
        $user = new User(1, 'u-1', 'Teacher User', 'teacher@claret.edu', null, 'hash', 'active', false, null, null, ['teacher']);
        $context = UserContext::fromUser($user);

        $roleMiddleware = new RoleMiddleware(['teacher', 'admin'], $this->createMockAuthenticator($context));
        $request = new Request([], [], ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/teacher/classes']);

        $response = $roleMiddleware->handle($request, function () {
            return Response::html('Teacher Classes Roster', 200);
        });

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('Teacher Classes Roster', $response->getContent());
    }

    public function testRoleMiddlewareDeniesUnauthorizedRole(): void
    {
        $user = new User(2, 'u-2', 'Student User', 'student@claret.edu', null, 'hash', 'active', false, null, null, ['student']);
        $context = UserContext::fromUser($user);

        $roleMiddleware = new RoleMiddleware(['admin', 'teacher'], $this->createMockAuthenticator($context));
        $request = new Request([], [], ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/admin/users']);

        $response = $roleMiddleware->handle($request, function () {
            return Response::html('Admin Portal', 200);
        });

        $this->assertSame(403, $response->getStatusCode());
        $this->assertStringContainsString('403 Access Denied', $response->getContent());
    }
}
