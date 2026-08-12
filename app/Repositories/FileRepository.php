<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Models\FileRecord;
use App\Models\User;
use PDO;

/**
 * Data Access Layer for Protected Centralized Files
 */
class FileRepository
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::getInstance();
    }

    public function getPdo(): PDO
    {
        return $this->pdo;
    }

    public function create(
        string $uuid,
        string $storageKey,
        string $originalName,
        string $mimeType,
        int $sizeBytes,
        string $sha256,
        int $uploadedBy,
        string $ownerType,
        int $ownerId
    ): FileRecord {
        $now = date('Y-m-d H:i:s');
        $stmt = $this->pdo->prepare(
            'INSERT INTO `files` (
                `uuid`, `storage_key`, `original_name`, `mime_type`, `size_bytes`, `sha256`, 
                `uploaded_by`, `owner_type`, `owner_id`, `created_at`
            ) VALUES (
                :uuid, :storage_key, :original_name, :mime_type, :size_bytes, :sha256, 
                :uploaded_by, :owner_type, :owner_id, :created_at
            )'
        );

        $stmt->execute([
            ':uuid' => $uuid,
            ':storage_key' => $storageKey,
            ':original_name' => $originalName,
            ':mime_type' => $mimeType,
            ':size_bytes' => $sizeBytes,
            ':sha256' => $sha256,
            ':uploaded_by' => $uploadedBy,
            ':owner_type' => $ownerType,
            ':owner_id' => $ownerId,
            ':created_at' => $now,
        ]);

        $id = (int)$this->pdo->lastInsertId();

        return $this->findById($id);
    }

    public function findById(int $id, bool $includeDeleted = false): ?FileRecord
    {
        $sql = 'SELECT f.*, u.uuid as user_uuid, u.name as user_name, u.email as user_email
                FROM `files` f
                LEFT JOIN `users` u ON u.id = f.uploaded_by
                WHERE f.id = :id';

        if (!$includeDeleted) {
            $sql .= ' AND f.deleted_at IS NULL';
        }

        $sql .= ' LIMIT 1';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        $uploader = !empty($row['user_name']) ? User::fromArray([
            'id' => $row['uploaded_by'],
            'uuid' => $row['user_uuid'] ?? '',
            'name' => $row['user_name'],
            'email' => $row['user_email'] ?? '',
        ]) : null;

        return FileRecord::fromArray($row, $uploader);
    }

    public function findByUuid(string $uuid, bool $includeDeleted = false): ?FileRecord
    {
        $sql = 'SELECT f.*, u.uuid as user_uuid, u.name as user_name, u.email as user_email
                FROM `files` f
                LEFT JOIN `users` u ON u.id = f.uploaded_by
                WHERE f.uuid = :uuid';

        if (!$includeDeleted) {
            $sql .= ' AND f.deleted_at IS NULL';
        }

        $sql .= ' LIMIT 1';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':uuid' => trim($uuid)]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        $uploader = !empty($row['user_name']) ? User::fromArray([
            'id' => $row['uploaded_by'],
            'uuid' => $row['user_uuid'] ?? '',
            'name' => $row['user_name'],
            'email' => $row['user_email'] ?? '',
        ]) : null;

        return FileRecord::fromArray($row, $uploader);
    }

    public function findByStorageKey(string $storageKey, bool $includeDeleted = false): ?FileRecord
    {
        $sql = 'SELECT f.*, u.uuid as user_uuid, u.name as user_name, u.email as user_email
                FROM `files` f
                LEFT JOIN `users` u ON u.id = f.uploaded_by
                WHERE f.storage_key = :storage_key';

        if (!$includeDeleted) {
            $sql .= ' AND f.deleted_at IS NULL';
        }

        $sql .= ' LIMIT 1';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':storage_key' => trim($storageKey)]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        $uploader = !empty($row['user_name']) ? User::fromArray([
            'id' => $row['uploaded_by'],
            'uuid' => $row['user_uuid'] ?? '',
            'name' => $row['user_name'],
            'email' => $row['user_email'] ?? '',
        ]) : null;

        return FileRecord::fromArray($row, $uploader);
    }

    public function softDelete(int $id): bool
    {
        $now = date('Y-m-d H:i:s');
        $stmt = $this->pdo->prepare(
            'UPDATE `files` SET `deleted_at` = :deleted_at WHERE `id` = :id AND `deleted_at` IS NULL'
        );

        return $stmt->execute([
            ':id' => $id,
            ':deleted_at' => $now,
        ]);
    }

    /**
     * @return FileRecord[]
     */
    public function getByOwner(string $ownerType, int $ownerId, bool $includeDeleted = false): array
    {
        $sql = 'SELECT f.*, u.uuid as user_uuid, u.name as user_name, u.email as user_email
                FROM `files` f
                LEFT JOIN `users` u ON u.id = f.uploaded_by
                WHERE f.owner_type = :owner_type AND f.owner_id = :owner_id';

        if (!$includeDeleted) {
            $sql .= ' AND f.deleted_at IS NULL';
        }

        $sql .= ' ORDER BY f.created_at DESC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':owner_type' => $ownerType,
            ':owner_id' => $ownerId,
        ]);

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(function (array $row) {
            $uploader = !empty($row['user_name']) ? User::fromArray([
                'id' => $row['uploaded_by'],
                'uuid' => $row['user_uuid'] ?? '',
                'name' => $row['user_name'],
                'email' => $row['user_email'] ?? '',
            ]) : null;

            return FileRecord::fromArray($row, $uploader);
        }, $rows);
    }

    public function updateOwner(int $id, string $ownerType, int $ownerId): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE `files` SET `owner_type` = :owner_type, `owner_id` = :owner_id WHERE `id` = :id'
        );

        return $stmt->execute([
            ':id' => $id,
            ':owner_type' => $ownerType,
            ':owner_id' => $ownerId,
        ]);
    }
}
