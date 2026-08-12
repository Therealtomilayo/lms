<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Csrf;
use App\Core\Request;
use App\Core\Response;

/**
 * Enforces Cross-Site Request Forgery Protection on State-Changing Requests
 */
class CsrfMiddleware
{
    public function handle(Request $request, callable $next): Response
    {
        $method = $request->getMethod();

        if (in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            $token = $request->input('_csrf_token') ?? $request->header('X-CSRF-TOKEN');

            if (!$token || !Csrf::validate((string)$token)) {
                if ($request->isJson() || $request->isAjax()) {
                    return Response::json([
                        'error' => 'CSRF token mismatch or expired.',
                        'code' => 'CSRF_INVALID',
                    ], 419);
                }

                return Response::html(
                    '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>419 - Page Expired</title></head><body style="font-family: system-ui, sans-serif; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; background: #F8FAFC; color: #1E293B;"><div style="text-align: center; max-width: 480px; padding: 2rem; background: #FFF; border: 1px solid #CBD5E1; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);"><h1 style="color: #B91C1C; margin-bottom: 0.5rem;">419 Page Expired</h1><p style="color: #475569; margin-bottom: 1.5rem;">Your session or security token has expired. Please refresh the page and try again.</p><a href="/login" style="display: inline-block; background: #2563EB; color: #FFF; padding: 0.75rem 1.5rem; text-decoration: none; border-radius: 8px; font-weight: 600;">Return to Login</a></div></body></html>',
                    419
                );
            }
        }

        return $next($request);
    }
}
