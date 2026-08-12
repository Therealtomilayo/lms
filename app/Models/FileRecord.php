<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Domain File Record Entity
 * Represents centralized file metadata for protected storage outside the public root.
 */
final class FileRecord
{
    public function __construct(
        public readonly int $id,
        public readonly string $uuid,
        public readonly string $storageKey,
        public readonly string $originalName,
        public readonly string $mimeType,
        public readonly int $sizeBytes,
        public readonly string $sha256,
        public readonly int $uploadedBy,
        public readonly ?string $ownerType = null,
        public readonly ?int $ownerId = null,
        public readonly ?string $createdAt = null,
        public readonly ?string $deletedAt = null,
        public readonly ?User $uploader = null
    ) {
    }

    public static function fromArray(array $data, ?User $uploader = null): self
    {
        return new self(
            id: (int)$data['id'],
            uuid: (string)$data['uuid'],
            storageKey: (string)$data['storage_key'],
            originalName: (string)$data['original_name'],
            mimeType: (string)$data['mime_type'],
            sizeBytes: (int)$data['size_bytes'],
            sha256: (string)$data['sha256'],
            uploadedBy: (int)$data['uploaded_by'],
            ownerType: isset($data['owner_type']) ? (string)$data['owner_type'] : null,
            ownerId: isset($data['owner_id']) ? (int)$data['owner_id'] : null,
            createdAt: isset($data['created_at']) ? (string)$data['created_at'] : null,
            deletedAt: isset($data['deleted_at']) ? (string)$data['deleted_at'] : null,
            uploader: $uploader
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'storage_key' => $this->storageKey,
            'original_name' => $this->originalName,
            'mime_type' => $this->mimeType,
            'size_bytes' => $this->sizeBytes,
            'sha256' => $this->sha256,
            'uploaded_by' => $this->uploadedBy,
            'owner_type' => $this->ownerType,
            'owner_id' => $this->ownerId,
            'created_at' => $this->createdAt,
            'deleted_at' => $this->deletedAt,
            'formatted_size' => $this->getFormattedSize(),
        ];
    }

    public function isDeleted(): bool
    {
        return $this->deletedAt !== null;
    }

    public function getFormattedSize(): string
    {
        $bytes = $this->sizeBytes;
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        }
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        }
        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 1) . ' KB';
        }

        return $bytes . ' B';
    }

    public function isPdf(): bool
    {
        return $this->mimeType === 'application/pdf' || str_ends_with(strtolower($this->originalName), '.pdf');
    }

    public function isImage(): bool
    {
        return str_starts_with($this->mimeType, 'image/');
    }

    public function isAudio(): bool
    {
        return str_starts_with($this->mimeType, 'audio/');
    }

    public function isVideo(): bool
    {
        return str_starts_with($this->mimeType, 'video/');
    }

    public function isDocument(): bool
    {
        return in_array($this->mimeType, [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-powerpoint',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'text/plain',
            'text/csv',
            'application/rtf',
        ], true);
    }
}
