<?php

declare(strict_types=1);

namespace App\DTO;

/**
 * Data Transfer Object for System Health Check Results
 */
class HealthCheckResult
{
    public string $status;
    public array $checks;
    public string $timestamp;

    public function __construct(string $status, array $checks = [], ?string $timestamp = null)
    {
        $this->status = $status;
        $this->checks = $checks;
        $this->timestamp = $timestamp ?? gmdate('Y-m-d\TH:i:s\Z');
    }

    public function isHealthy(): bool
    {
        return $this->status === 'healthy';
    }

    public function toArray(): array
    {
        return [
            'status' => $this->status,
            'timestamp' => $this->timestamp,
            'checks' => $this->checks,
        ];
    }
}
