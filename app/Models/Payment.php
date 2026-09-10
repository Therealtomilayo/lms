<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Domain Payment Entity for Paystack-Ready Commerce & Financial Transactions
 */
final class Payment
{
    public const PURPOSE_RESULT_PIN = 'result_pin';
    public const PURPOSE_SCHOOL_FEES = 'school_fees';
    public const PURPOSE_ADMISSION = 'admission';

    public const STATUS_PENDING = 'pending';
    public const STATUS_SUCCESSFUL = 'successful';
    public const STATUS_FAILED = 'failed';

    public function __construct(
        public readonly int $id,
        public readonly string $reference,
        public readonly int $userId,
        public readonly int $studentId,
        public readonly int $sessionId,
        public readonly int $termId,
        public readonly string $purpose = self::PURPOSE_RESULT_PIN,
        public readonly float $amount = 0.0,
        public readonly string $currency = 'NGN',
        public readonly string $channel = 'simulated',
        public readonly string $status = self::STATUS_PENDING,
        public readonly ?string $gatewayReference = null,
        public readonly ?array $metadata = null,
        public readonly ?string $paidAt = null,
        public readonly string $createdAt = '',
        public readonly ?string $updatedAt = null,
        public readonly ?string $payerName = null,
        public readonly ?string $payerEmail = null,
        public readonly ?string $studentName = null,
        public readonly ?string $studentAdmissionNumber = null,
        public readonly ?string $sessionName = null,
        public readonly ?string $termName = null
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
            reference: (string)$data['reference'],
            userId: (int)$data['user_id'],
            studentId: (int)$data['student_id'],
            sessionId: (int)$data['session_id'],
            termId: (int)$data['term_id'],
            purpose: (string)($data['purpose'] ?? self::PURPOSE_RESULT_PIN),
            amount: (float)($data['amount'] ?? 0.0),
            currency: (string)($data['currency'] ?? 'NGN'),
            channel: (string)($data['channel'] ?? 'simulated'),
            status: (string)($data['status'] ?? self::STATUS_PENDING),
            gatewayReference: $data['gateway_reference'] ?? null,
            metadata: $metadata,
            paidAt: $data['paid_at'] ?? null,
            createdAt: (string)($data['created_at'] ?? ''),
            updatedAt: $data['updated_at'] ?? null,
            payerName: $data['payer_name'] ?? null,
            payerEmail: $data['payer_email'] ?? null,
            studentName: $data['student_name'] ?? null,
            studentAdmissionNumber: $data['student_admission_number'] ?? null,
            sessionName: $data['session_name'] ?? null,
            termName: $data['term_name'] ?? null
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

    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    public function getFormattedAmount(): string
    {
        return '₦' . number_format($this->amount, 2);
    }

    public function getReceiptNumber(): string
    {
        return 'REC-' . strtoupper(substr(hash('crc32b', $this->reference . $this->id), 0, 8));
    }
}
