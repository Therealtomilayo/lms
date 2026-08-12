<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\RateLimitRepository;

/**
 * Service for Rate Limiting and Throttle Management
 */
class RateLimitService
{
    private RateLimitRepository $repository;
    private static bool $enabled = true;

    public function __construct(?RateLimitRepository $repository = null)
    {
        $this->repository = $repository ?? new RateLimitRepository();
    }

    public static function setEnabled(bool $enabled): void
    {
        self::$enabled = $enabled;
    }

    public static function isEnabled(): bool
    {
        return self::$enabled;
    }

    /**
     * Check whether the key has exceeded max allowed attempts.
     */
    public function tooManyAttempts(string $key, int $maxAttempts): bool
    {
        if (!self::$enabled) {
            return false;
        }

        return $this->repository->attempts($key) >= $maxAttempts;
    }

    /**
     * Increment the hit count for the key.
     */
    public function hit(string $key, int $decaySeconds = 60): int
    {
        if (!self::$enabled) {
            return 1;
        }

        return $this->repository->hit($key, $decaySeconds);
    }

    /**
     * Get remaining available attempts before being throttled.
     */
    public function remaining(string $key, int $maxAttempts): int
    {
        if (!self::$enabled) {
            return $maxAttempts;
        }

        $attempts = $this->repository->attempts($key);
        return max(0, $maxAttempts - $attempts);
    }

    /**
     * Get the number of seconds until the key is available again.
     */
    public function availableIn(string $key): int
    {
        return $this->repository->availableIn($key);
    }

    /**
     * Reset/clear the rate limit key.
     */
    public function reset(string $key): void
    {
        $this->repository->reset($key);
    }

    /**
     * Clear all rate limits.
     */
    public function clearAll(): void
    {
        $this->repository->clearAll();
    }

    /**
     * Deterministically generate a rate limit key.
     */
    public function generateKey(string $action, ?string $identifier = null, ?string $ip = null): string
    {
        $parts = [$action];

        if ($identifier !== null && $identifier !== '') {
            $parts[] = 'id:' . md5(strtolower(trim($identifier)));
        }

        if ($ip !== null && $ip !== '') {
            $parts[] = 'ip:' . trim($ip);
        }

        return implode(':', $parts);
    }
}
