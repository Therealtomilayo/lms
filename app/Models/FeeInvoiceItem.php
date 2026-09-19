<?php

declare(strict_types=1);

namespace App\Models;

final class FeeInvoiceItem
{
    public function __construct(
        public readonly int $id,
        public readonly int $invoiceId,
        public readonly ?int $feeCategoryId,
        public readonly string $name,
        public readonly float $amount,
        public readonly ?string $createdAt = null,
        public readonly ?string $categoryName = null
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            id: (int)$data['id'],
            invoiceId: (int)$data['invoice_id'],
            feeCategoryId: isset($data['fee_category_id']) && $data['fee_category_id'] !== null && $data['fee_category_id'] !== '' ? (int)$data['fee_category_id'] : null,
            name: (string)$data['name'],
            amount: (float)$data['amount'],
            createdAt: $data['created_at'] ?? null,
            categoryName: $data['category_name'] ?? null
        );
    }
}
