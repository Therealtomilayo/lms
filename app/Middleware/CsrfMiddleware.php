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
            // Allow login requests originating from trusted school website domains
            if ($this->isTrustedLoginRequest($request)) {
                return $next($request);
            }

            $token = $request->input('_csrf_token') ?? $request->input('csrf_token') ?? $request->input('_token') ?? $request->header('X-CSRF-TOKEN') ?? $request->header('X-XSRF-TOKEN');

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

    /**
     * Determine if the incoming request is a login submission from an authorized domain
     */
    private function isTrustedLoginRequest(Request $request): bool
    {
        $path = $request->getPath();

        if ($path !== '/login' && $path !== '/api/auth/external-login') {
            return false;
        }

        $origin = $request->header('Origin') ?? $request->header('Referer') ?? '';
        $returnUrl = (string)$request->input('return_url', '');

        $candidateHosts = [];
        if (!empty($origin)) {
            $candidateHosts[] = strtolower((string)parse_url($origin, PHP_URL_HOST));
        }
        if (!empty($returnUrl)) {
            $candidateHosts[] = strtolower((string)parse_url($returnUrl, PHP_URL_HOST));
        }

        $trusted = [
            'claretschools.xo.je',
            'www.claretschools.xo.je',
            'portal-claretschools.xo.je',
            'lms.test',
            'localhost',
            '127.0.0.1',
        ];

        foreach ($candidateHosts as $host) {
            if (empty($host)) {
                continue;
            }
            foreach ($trusted as $t) {
                if ($host === $t || str_ends_with($host, '.' . $t)) {
                    return true;
                }
            }
        }

        return false;
    }
}
