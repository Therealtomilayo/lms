<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Domain Entity for Admission Application Fee Payments (Per-Ward)
 */
final class AdmissionPayment
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_SUCCESSFUL = 'successful';
    public const STATUS_FAILED = 'failed';

    public function __construct(
        public readonly int $id,
        public readonly int $applicationId,
        public readonly int $wardId,
        public readonly string $reference,
        public readonly int $userId,
        public readonly float $amount,
        public readonly string $currency = 'NGN',
        public readonly string $channel = 'paystack',
        public readonly string $status = self::STATUS_PENDING,
        public readonly ?string $gatewayReference = null,
        public readonly ?array $metadata = null,
        public readonly ?string $paidAt = null,
        public readonly ?string $createdAt = null,
        public readonly ?string $updatedAt = null
    ) {
    }

    public static function fromArray(array $data): self
    {
        $metadata = null;
        if (!empty($data['metadata'])) {
            $metadata = is_string($data['metadata']) ? json_decode($data['metadata'], true) : (array)$data['metadata'];
        }

        return new self(
            id: (int)$data['id'],
            applicationId: (int)$data['application_id'],
            wardId: (int)$data['ward_id'],
            reference: (string)$data['reference'],
            userId: (int)$data['user_id'],
            amount: (float)$data['amount'],
            currency: (string)($data['currency'] ?? 'NGN'),
            channel: (string)($data['channel'] ?? 'paystack'),
            status: (string)($data['status'] ?? self::STATUS_PENDING),
            gatewayReference: $data['gateway_reference'] ?? null,
            metadata: $metadata,
            paidAt: $data['paid_at'] ?? null,
            createdAt: $data['created_at'] ?? null,
            updatedAt: $data['updated_at'] ?? null
        );
    }

    public function isSuccessful(): bool
    {
        return $this->status === self::STATUS_SUCCESSFUL;
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function getFormattedAmount(): string
    {
        return '₦' . number_format($this->amount, 2);
    }
}
