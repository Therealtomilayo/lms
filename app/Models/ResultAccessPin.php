<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Domain Result Access Scratch-Card PIN Entity (SRS §38, §39, §40, §58.5)
 */
final class ResultAccessPin
{
    public const STATUS_ACTIVE = 'active';
    public const STATUS_DEPLETED = 'depleted';
    public const STATUS_REVOKED = 'revoked';

    public function __construct(
        public readonly int $id,
        public readonly string $serialNumber,
        public readonly string $pinCode,
        public readonly string $pinHash,
        public readonly ?int $paymentId = null,
        public readonly ?int $studentId = null,
        public readonly ?int $sessionId = null,
        public readonly ?int $termId = null,
        public readonly int $createdBy = 0,
        public readonly int $maxUses = 5,
        public readonly int $timesUsed = 0,
        public readonly string $status = self::STATUS_ACTIVE,
        public readonly ?string $firstUsedAt = null,
        public readonly ?string $lastUsedAt = null,
        public readonly string $createdAt = '',
        public readonly ?string $updatedAt = null,
        public readonly ?string $studentName = null,
        public readonly ?string $studentAdmissionNumber = null,
        public readonly ?string $sessionName = null,
        public readonly ?string $termName = null,
        public readonly ?string $paymentReference = null,
        public readonly ?string $creatorName = null
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            id: (int)$data['id'],
            serialNumber: (string)$data['serial_number'],
            pinCode: (string)$data['pin_code'],
            pinHash: (string)$data['pin_hash'],
            paymentId: isset($data['payment_id']) ? (int)$data['payment_id'] : null,
            studentId: isset($data['student_id']) ? (int)$data['student_id'] : null,
            sessionId: isset($data['session_id']) ? (int)$data['session_id'] : null,
            termId: isset($data['term_id']) ? (int)$data['term_id'] : null,
            createdBy: (int)($data['created_by'] ?? 0),
            maxUses: (int)($data['max_uses'] ?? 5),
            timesUsed: (int)($data['times_used'] ?? 0),
            status: (string)($data['status'] ?? self::STATUS_ACTIVE),
            firstUsedAt: $data['first_used_at'] ?? null,
            lastUsedAt: $data['last_used_at'] ?? null,
            createdAt: (string)($data['created_at'] ?? ''),
            updatedAt: $data['updated_at'] ?? null,
            studentName: $data['student_name'] ?? null,
            studentAdmissionNumber: $data['student_admission_number'] ?? null,
            sessionName: $data['session_name'] ?? null,
            termName: $data['term_name'] ?? null,
            paymentReference: $data['payment_reference'] ?? null,
            creatorName: $data['creator_name'] ?? null
        );
    }

    public function isUsable(): bool
    {
        return $this->status === self::STATUS_ACTIVE && $this->timesUsed < $this->maxUses;
    }

    public function getRemainingUses(): int
    {
        return max(0, $this->maxUses - $this->timesUsed);
    }

    public function getFormattedPin(): string
    {
        $clean = strtoupper(str_replace(['-', ' '], '', $this->pinCode));
        if (strlen($clean) === 12) {
            return substr($clean, 0, 4) . '-' . substr($clean, 4, 4) . '-' . substr($clean, 8, 4);
        }
        return $this->pinCode;
    }

    public function getMaskedPin(): string
    {
        $formatted = $this->getFormattedPin();
        $parts = explode('-', $formatted);
        if (count($parts) === 3) {
            return $parts[0] . '-••••-' . $parts[2];
        }
        return substr($formatted, 0, 4) . '••••' . substr($formatted, -4);
    }
}
