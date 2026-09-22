<?php

declare(strict_types=1);

namespace App\DTO;

/**
 * Result DTO for outbound notification deliveries
 */
final class NotificationResult
{
    public function __construct(
        public readonly bool $success,
        public readonly string $gateway,
        public readonly ?string $messageId = null,
        public readonly ?string $error = null,
        public readonly array $metadata = []
    ) {
    }

    public static function success(string $gateway, ?string $messageId = null, array $metadata = []): self
    {
        return new self(
            success: true,
            gateway: $gateway,
            messageId: $messageId,
            error: null,
            metadata: $metadata
        );
    }

    public static function failure(string $gateway, string $error, array $metadata = []): self
    {
        return new self(
            success: false,
            gateway: $gateway,
            messageId: null,
            error: $error,
            metadata: $metadata
        );
    }
}
