<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\AuthenticatorInterface;
use App\Core\Request;
use App\Core\Response;
use App\Core\WebAuthenticator;

/**
 * Enforces Multi-Role Permissions on Protected Routes
 */
class RoleMiddleware
{
    private array $allowedRoles;
    private AuthenticatorInterface $authenticator;

    public function __construct(array $allowedRoles = [], ?AuthenticatorInterface $authenticator = null)
    {
        $this->allowedRoles = $allowedRoles;
        $this->authenticator = $authenticator ?? new WebAuthenticator();
    }

    public static function allow(array|string $roles): callable
    {
        $roleList = is_array($roles) ? $roles : func_get_args();

        return function (Request $request, callable $next) use ($roleList): Response {
            $middleware = new self($roleList);
            return $middleware->handle($request, $next);
        };
    }

    public function handle(Request $request, callable $next): Response
    {
        $userContext = $this->authenticator->authenticate($request);

        if (!$userContext) {
            if ($request->isJson() || $request->isAjax()) {
                return Response::json(['error' => 'Unauthenticated.'], 401);
            }
            return Response::redirect('/login');
        }

        if (!empty($this->allowedRoles) && !$userContext->hasAnyRole($this->allowedRoles)) {
            if ($request->isJson() || $request->isAjax()) {
                return Response::json([
                    'error' => 'Forbidden. You do not have permission to access this resource.',
                    'code' => 'FORBIDDEN_ROLE',
                ], 403);
            }

            return Response::html(
                '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>403 - Access Denied</title></head><body style="font-family: system-ui, sans-serif; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; background: #F8FAFC; color: #1E293B;"><div style="text-align: center; max-width: 480px; padding: 2rem; background: #FFF; border: 1px solid #CBD5E1; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);"><h1 style="color: #B91C1C; margin-bottom: 0.5rem;">403 Access Denied</h1><p style="color: #475569; margin-bottom: 1.5rem;">You do not possess the required role permissions to access this screen.</p><a href="/login" style="display: inline-block; background: #2563EB; color: #FFF; padding: 0.75rem 1.5rem; text-decoration: none; border-radius: 8px; font-weight: 600;">Return to Dashboard</a></div></body></html>',
                403
            );
        }

        return $next($request);
    }
}
