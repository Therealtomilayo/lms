<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Domain entity for External Notification logs
 */
final class ExternalNotification
{
    public function __construct(
        public readonly int $id,
        public readonly string $channel,
        public readonly string $recipient,
        public readonly ?int $userId = null,
        public readonly string $eventType = 'general',
        public readonly ?string $subject = null,
        public readonly string $messageBody = '',
        public readonly string $status = 'sent',
        public readonly string $gatewayProvider = 'log',
        public readonly ?string $gatewayReference = null,
        public readonly ?string $errorMessage = null,
        public readonly ?array $metadata = null,
        public readonly ?string $sentAt = null,
        public readonly ?string $createdAt = null,
        public readonly ?string $updatedAt = null,
        public readonly ?string $userName = null
    ) {
    }

    public static function fromArray(array $data): self
    {
        $meta = null;
        if (isset($data['metadata'])) {
            $meta = is_string($data['metadata']) ? json_decode($data['metadata'], true) : $data['metadata'];
        }

        return new self(
            id: (int)$data['id'],
            channel: (string)$data['channel'],
            recipient: (string)$data['recipient'],
            userId: isset($data['user_id']) && $data['user_id'] !== '' ? (int)$data['user_id'] : null,
            eventType: (string)($data['event_type'] ?? 'general'),
            subject: isset($data['subject']) ? (string)$data['subject'] : null,
            messageBody: (string)($data['message_body'] ?? ''),
            status: (string)($data['status'] ?? 'sent'),
            gatewayProvider: (string)($data['gateway_provider'] ?? 'log'),
            gatewayReference: isset($data['gateway_reference']) ? (string)$data['gateway_reference'] : null,
            errorMessage: isset($data['error_message']) ? (string)$data['error_message'] : null,
            metadata: is_array($meta) ? $meta : null,
            sentAt: isset($data['sent_at']) ? (string)$data['sent_at'] : null,
            createdAt: isset($data['created_at']) ? (string)$data['created_at'] : null,
            updatedAt: isset($data['updated_at']) ? (string)$data['updated_at'] : null,
            userName: isset($data['user_name']) ? (string)$data['user_name'] : null
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'channel' => $this->channel,
            'recipient' => $this->recipient,
            'user_id' => $this->userId,
            'event_type' => $this->eventType,
            'subject' => $this->subject,
            'message_body' => $this->messageBody,
            'status' => $this->status,
            'gateway_provider' => $this->gatewayProvider,
            'gateway_reference' => $this->gatewayReference,
            'error_message' => $this->errorMessage,
            'metadata' => $this->metadata,
            'sent_at' => $this->sentAt,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
            'user_name' => $this->userName,
        ];
    }

    public function isEmail(): bool
    {
        return $this->channel === 'email';
    }

    public function isSms(): bool
    {
        return $this->channel === 'sms';
    }

    public function isWhatsApp(): bool
    {
        return $this->channel === 'whatsapp';
    }

    public function isSent(): bool
    {
        return in_array($this->status, ['sent', 'delivered'], true);
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }
}
