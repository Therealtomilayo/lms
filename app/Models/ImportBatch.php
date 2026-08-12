<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Domain Import Batch Entity
 */
final class ImportBatch
{
    public const STATUS_UPLOADED = 'uploaded';
    public const STATUS_VALIDATED = 'validated';
    public const STATUS_COMMITTED = 'committed';
    public const STATUS_FAILED = 'failed';

    public function __construct(
        public readonly int $id,
        public readonly int $uploadedBy,
        public readonly string $type,
        public readonly string $originalName,
        public readonly string $sha256,
        public readonly string $status = self::STATUS_UPLOADED,
        public readonly int $totalRows = 0,
        public readonly int $validRows = 0,
        public readonly int $invalidRows = 0,
        public readonly ?string $committedAt = null,
        public readonly ?string $createdAt = null,
        public readonly ?User $uploader = null,
        public readonly array $errors = []
    ) {
    }

    public static function fromArray(array $data, ?User $uploader = null, array $errors = []): self
    {
        return new self(
            id: (int)$data['id'],
            uploadedBy: (int)$data['uploaded_by'],
            type: (string)$data['type'],
            originalName: (string)$data['original_name'],
            sha256: (string)$data['sha256'],
            status: (string)($data['status'] ?? self::STATUS_UPLOADED),
            totalRows: (int)($data['total_rows'] ?? 0),
            validRows: (int)($data['valid_rows'] ?? 0),
            invalidRows: (int)($data['invalid_rows'] ?? 0),
            committedAt: isset($data['committed_at']) ? (string)$data['committed_at'] : null,
            createdAt: isset($data['created_at']) ? (string)$data['created_at'] : null,
            uploader: $uploader,
            errors: $errors
        );
    }

    public function isCommitted(): bool
    {
        return $this->status === self::STATUS_COMMITTED;
    }

    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    public function isValidated(): bool
    {
        return $this->status === self::STATUS_VALIDATED;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'uploaded_by' => $this->uploadedBy,
            'type' => $this->type,
            'original_name' => $this->originalName,
            'sha256' => $this->sha256,
            'status' => $this->status,
            'total_rows' => $this->totalRows,
            'valid_rows' => $this->validRows,
            'invalid_rows' => $this->invalidRows,
            'committed_at' => $this->committedAt,
            'created_at' => $this->createdAt,
        ];
    }
}
