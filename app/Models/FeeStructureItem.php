<?php

declare(strict_types=1);

namespace App\Models;

final class FeeStructureItem
{
    public function __construct(
        public readonly int $id,
        public readonly int $feeStructureId,
        public readonly int $feeCategoryId,
        public readonly string $name,
        public readonly float $amount,
        public readonly bool $isCompulsory = true,
        public readonly ?string $createdAt = null,
        public readonly ?string $categoryName = null
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            id: (int)$data['id'],
            feeStructureId: (int)$data['fee_structure_id'],
            feeCategoryId: (int)$data['fee_category_id'],
            name: (string)$data['name'],
            amount: (float)$data['amount'],
            isCompulsory: (bool)($data['is_compulsory'] ?? true),
            createdAt: $data['created_at'] ?? null,
            categoryName: $data['category_name'] ?? null
        );
    }
}
