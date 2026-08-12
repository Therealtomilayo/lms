<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Config;
use App\Core\Request;
use App\Core\Response;

/**
 * Injects Standard Security Headers into HTTP Responses
 */
class SecurityHeadersMiddleware
{
    public function handle(Request $request, callable $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        // MIME-type sniffing prevention
        $response->setHeader('X-Content-Type-Options', 'nosniff');

        // Clickjacking protection
        $response->setHeader('X-Frame-Options', 'SAMEORIGIN');

        // Referrer policy
        $response->setHeader('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Browser XSS filter
        $response->setHeader('X-XSS-Protection', '1; mode=block');

        // Permissions policy
        $response->setHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        // Content Security Policy
        $csp = [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.tailwindcss.com",
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com",
            "font-src 'self' https://fonts.gstatic.com data:",
            "img-src 'self' data: blob:",
            "connect-src 'self'",
            "frame-ancestors 'self'",
        ];
        $response->setHeader('Content-Security-Policy', implode('; ', $csp));

        // Strict Transport Security (HTTPS only)
        $isHttps = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on')
            || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
            || ($request->getScheme() === 'https')
            || (Config::get('app.env') === 'production' && filter_var(Config::get('session.secure', false), FILTER_VALIDATE_BOOLEAN));

        if ($isHttps) {
            $response->setHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }
}
