<?php

declare(strict_types=1);

namespace App\Services\Notifications;

use App\DTO\NotificationResult;

/**
 * Interface contract for external communication drivers (Email, SMS, WhatsApp)
 */
interface NotificationChannelInterface
{
    /**
     * Send a notification through this channel
     *
     * @param string $recipient Email address or phone number (e.g. +234...)
     * @param string $content Message body or plain text
     * @param array $options Additional options (e.g. subject, html, attachments, metadata)
     */
    public function send(string $recipient, string $content, array $options = []): NotificationResult;

    /**
     * Unique identifier for this driver
     */
    public function getName(): string;
}
