<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Services\RateLimitService;

/**
 * Middleware for Throttling HTTP Requests
 */
class RateLimitMiddleware
{
    private string $action;
    private int $maxAttempts;
    private int $decaySeconds;
    private RateLimitService $service;

    public function __construct(
        string $action = 'global',
        int $maxAttempts = 60,
        int $decaySeconds = 60,
        ?RateLimitService $service = null
    ) {
        $this->action = $action;
        $this->maxAttempts = $maxAttempts;
        $this->decaySeconds = $decaySeconds;
        $this->service = $service ?? new RateLimitService();
    }

    public static function throttle(string $action, int $maxAttempts = 60, int $decaySeconds = 60): callable
    {
        return function (Request $request, callable $next) use ($action, $maxAttempts, $decaySeconds): Response {
            $middleware = new self($action, $maxAttempts, $decaySeconds);
            return $middleware->handle($request, $next);
        };
    }

    public function handle(Request $request, callable $next): Response
    {
        if (!RateLimitService::isEnabled()) {
            return $next($request);
        }

        $ip = $request->getIp() ?? '127.0.0.1';
        $user = $request->getAttribute('user_context');
        $userId = $user ? (string)$user->id : null;

        $key = $this->service->generateKey($this->action, $userId, $ip);

        if ($this->service->tooManyAttempts($key, $this->maxAttempts)) {
            $retryAfter = $this->service->availableIn($key);

            if ($request->isJson() || $request->isAjax()) {
                $response = Response::json([
                    'error' => 'Too Many Requests. Please slow down and try again later.',
                    'code' => 'TOO_MANY_REQUESTS',
                    'retry_after' => $retryAfter,
                ], 429);
                $response->setHeader('Retry-After', (string)$retryAfter);
                return $response;
            }

            $view = new View();
            $html = $view->render('errors/429', [
                'retryAfter' => $retryAfter,
            ]);

            $response = Response::html($html, 429);
            $response->setHeader('Retry-After', (string)$retryAfter);
            return $response;
        }

        $this->service->hit($key, $this->decaySeconds);

        return $next($request);
    }
}
