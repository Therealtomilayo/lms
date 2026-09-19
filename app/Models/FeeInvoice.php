<?php

declare(strict_types=1);

namespace App\Models;

final class FeeInvoice
{
    public const STATUS_UNPAID = 'unpaid';
    public const STATUS_PARTIALLY_PAID = 'partially_paid';
    public const STATUS_PAID = 'paid';
    public const STATUS_OVERDUE = 'overdue';
    public const STATUS_WAIVED = 'waived';

    /**
     * @param FeeInvoiceItem[] $items
     * @param Payment[] $payments
     */
    public function __construct(
        public readonly int $id,
        public readonly string $invoiceNumber,
        public readonly int $studentId,
        public readonly ?int $parentId = null,
        public readonly int $classId = 0,
        public readonly int $sessionId = 0,
        public readonly int $termId = 0,
        public readonly float $subtotal = 0.0,
        public readonly float $discountAmount = 0.0,
        public readonly float $totalAmount = 0.0,
        public readonly float $amountPaid = 0.0,
        public readonly float $balanceDue = 0.0,
        public readonly string $status = self::STATUS_UNPAID,
        public readonly ?string $dueDate = null,
        public readonly ?string $notes = null,
        public readonly int $createdBy = 0,
        public readonly ?string $createdAt = null,
        public readonly ?string $updatedAt = null,
        public readonly ?string $studentName = null,
        public readonly ?string $admissionNumber = null,
        public readonly ?string $parentName = null,
        public readonly ?string $parentEmail = null,
        public readonly ?string $parentPhone = null,
        public readonly ?string $className = null,
        public readonly ?string $sessionName = null,
        public readonly ?string $termName = null,
        public readonly array $items = [],
        public readonly array $payments = []
    ) {
    }

    public static function fromArray(array $data, array $items = [], array $payments = []): self
    {
        return new self(
            id: (int)$data['id'],
            invoiceNumber: (string)$data['invoice_number'],
            studentId: (int)$data['student_id'],
            parentId: isset($data['parent_id']) && $data['parent_id'] !== null && $data['parent_id'] !== '' ? (int)$data['parent_id'] : null,
            classId: (int)($data['class_id'] ?? 0),
            sessionId: (int)($data['session_id'] ?? 0),
            termId: (int)($data['term_id'] ?? 0),
            subtotal: (float)($data['subtotal'] ?? 0.0),
            discountAmount: (float)($data['discount_amount'] ?? 0.0),
            totalAmount: (float)($data['total_amount'] ?? 0.0),
            amountPaid: (float)($data['amount_paid'] ?? 0.0),
            balanceDue: (float)($data['balance_due'] ?? 0.0),
            status: (string)($data['status'] ?? self::STATUS_UNPAID),
            dueDate: $data['due_date'] ?? null,
            notes: $data['notes'] ?? null,
            createdBy: (int)($data['created_by'] ?? 0),
            createdAt: $data['created_at'] ?? null,
            updatedAt: $data['updated_at'] ?? null,
            studentName: $data['student_name'] ?? null,
            admissionNumber: $data['admission_number'] ?? null,
            parentName: $data['parent_name'] ?? null,
            parentEmail: $data['parent_email'] ?? null,
            parentPhone: $data['parent_phone'] ?? null,
            className: $data['class_name'] ?? null,
            sessionName: $data['session_name'] ?? null,
            termName: $data['term_name'] ?? null,
            items: $items,
            payments: $payments
        );
    }

    public function isPaid(): bool
    {
        return $this->status === self::STATUS_PAID || $this->balanceDue <= 0.0;
    }

    public function isPartiallyPaid(): bool
    {
        return $this->status === self::STATUS_PARTIALLY_PAID || ($this->amountPaid > 0 && $this->balanceDue > 0);
    }

    public function isUnpaid(): bool
    {
        return $this->status === self::STATUS_UNPAID && $this->amountPaid <= 0;
    }

    public function isOverdue(): bool
    {
        if ($this->isPaid()) {
            return false;
        }
        if (empty($this->dueDate)) {
            return false;
        }
        return strtotime($this->dueDate) < time();
    }
}
