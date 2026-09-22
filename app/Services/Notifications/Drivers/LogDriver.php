<?php

declare(strict_types=1);

namespace App\Services\Notifications\Drivers;

use App\DTO\NotificationResult;
use App\Services\Notifications\NotificationChannelInterface;

/**
 * Driver that outputs notifications directly to storage/logs for development & auditing
 */
final class LogDriver implements NotificationChannelInterface
{
    private string $logPath;

    public function __construct(?string $logPath = null)
    {
        $this->logPath = $logPath ?? dirname(__DIR__, 4) . '/storage/logs/notifications.log';
    }

    public function send(string $recipient, string $content, array $options = []): NotificationResult
    {
        $dir = dirname($this->logPath);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        $timestamp = date('Y-m-d H:i:s');
        $channel = $options['channel'] ?? 'notification';
        $subject = isset($options['subject']) ? " | Subject: {$options['subject']}" : '';
        $messageId = 'LOG-' . bin2hex(random_bytes(8));

        $logEntry = sprintf(
            "[%s] [%s] [%s] Recipient: %s%s\nContent: %s\n----------------------------------------\n",
            $timestamp,
            strtoupper((string)$channel),
            $messageId,
            $recipient,
            $subject,
            trim($content)
        );

        @file_put_contents($this->logPath, $logEntry, FILE_APPEND | LOCK_EX);

        return NotificationResult::success(
            gateway: 'log',
            messageId: $messageId,
            metadata: ['logged_to' => $this->logPath]
        );
    }

    public function getName(): string
    {
        return 'log';
    }
}
