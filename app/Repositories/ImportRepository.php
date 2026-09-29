<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Models\ImportBatch;
use App\Models\User;
use PDO;

/**
 * Data Access Layer for CSV Import Batches and Import Errors
 */
class ImportRepository
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::getInstance();
    }

    public function findById(int $id): ?ImportBatch
    {
        $sql = 'SELECT i.*, u.name as uploader_name, u.email as uploader_email
                FROM `imports` i
                JOIN `users` u ON u.id = i.uploaded_by
                WHERE i.id = :id LIMIT 1';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        $uploader = User::fromArray([
            'id' => $row['uploaded_by'],
            'name' => $row['uploader_name'],
            'email' => $row['uploader_email'],
        ]);

        $errors = $this->getErrorsForImport($id);

        return ImportBatch::fromArray($row, $uploader, $errors);
    }

    private ?bool $hasSessionColumn = null;

    private function hasSessionColumn(): bool
    {
        if ($this->hasSessionColumn !== null) {
            return $this->hasSessionColumn;
        }

        try {
            $driver = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
            if ($driver === 'sqlite') {
                $stmt = $this->pdo->query("PRAGMA table_info(`imports`)");
                if ($stmt) {
                    $cols = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'name');
                    return $this->hasSessionColumn = in_array('session_id', $cols, true);
                }
            } else {
                $stmt = $this->pdo->query("SHOW COLUMNS FROM `imports` LIKE 'session_id'");
                return $this->hasSessionColumn = ($stmt && $stmt->fetch() !== false);
            }
        } catch (\Throwable) {
        }

        return $this->hasSessionColumn = true;
    }

    public function create(
        int $uploadedBy,
        string $type,
        string $originalName,
        string $sha256,
        int $totalRows = 0,
        int $validRows = 0,
        int $invalidRows = 0,
        string $status = 'uploaded',
        ?int $sessionId = null
    ): ImportBatch {
        $now = date('Y-m-d H:i:s');
        if ($this->hasSessionColumn()) {
            $sql = 'INSERT INTO `imports` (`uploaded_by`, `type`, `session_id`, `original_name`, `sha256`, `status`, `total_rows`, `valid_rows`, `invalid_rows`, `created_at`)
                    VALUES (:uploaded_by, :type, :session_id, :original_name, :sha256, :status, :total_rows, :valid_rows, :invalid_rows, :created_at)';
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':uploaded_by' => $uploadedBy,
                ':type' => $type,
                ':session_id' => $sessionId,
                ':original_name' => $originalName,
                ':sha256' => $sha256,
                ':status' => $status,
                ':total_rows' => $totalRows,
                ':valid_rows' => $validRows,
                ':invalid_rows' => $invalidRows,
                ':created_at' => $now,
            ]);
        } else {
            $sql = 'INSERT INTO `imports` (`uploaded_by`, `type`, `original_name`, `sha256`, `status`, `total_rows`, `valid_rows`, `invalid_rows`, `created_at`)
                    VALUES (:uploaded_by, :type, :original_name, :sha256, :status, :total_rows, :valid_rows, :invalid_rows, :created_at)';
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':uploaded_by' => $uploadedBy,
                ':type' => $type,
                ':original_name' => $originalName,
                ':sha256' => $sha256,
                ':status' => $status,
                ':total_rows' => $totalRows,
                ':valid_rows' => $validRows,
                ':invalid_rows' => $invalidRows,
                ':created_at' => $now,
            ]);
        }

        $importId = (int)$this->pdo->lastInsertId();

        return $this->findById($importId);
    }

    public function addError(int $importId, int $rowNumber, array $rawData, array $errors): void
    {
        $now = date('Y-m-d H:i:s');
        $sql = 'INSERT INTO `import_errors` (`import_id`, `row_number`, `raw_data_json`, `errors_json`, `created_at`)
                VALUES (:import_id, :row_number, :raw_data_json, :errors_json, :created_at)';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':import_id' => $importId,
            ':row_number' => $rowNumber,
            ':raw_data_json' => json_encode($rawData, JSON_UNESCAPED_UNICODE),
            ':errors_json' => json_encode($errors, JSON_UNESCAPED_UNICODE),
            ':created_at' => $now,
        ]);
    }

    public function getErrorsForImport(int $importId): array
    {
        $sql = 'SELECT * FROM `import_errors` WHERE `import_id` = :import_id ORDER BY `row_number` ASC';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':import_id' => $importId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $errors = [];
        foreach ($rows as $row) {
            $errors[] = [
                'id' => (int)$row['id'],
                'import_id' => (int)$row['import_id'],
                'row_number' => (int)$row['row_number'],
                'raw_data' => json_decode($row['raw_data_json'] ?? '{}', true) ?: [],
                'errors' => json_decode($row['errors_json'] ?? '[]', true) ?: [],
                'created_at' => $row['created_at'],
            ];
        }

        return $errors;
    }

    public function markCommitted(int $importId): bool
    {
        $now = date('Y-m-d H:i:s');
        $sql = 'UPDATE `imports` SET `status` = "committed", `committed_at` = :now WHERE `id` = :id';
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':id' => $importId,
            ':now' => $now,
        ]);
    }

    public function markFailed(int $importId): bool
    {
        $sql = 'UPDATE `imports` SET `status` = "failed" WHERE `id` = :id';
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([':id' => $importId]);
    }

    /**
     * @return ImportBatch[]
     */
    public function getRecentImports(int $limit = 20): array
    {
        $sql = 'SELECT i.*, u.name as uploader_name, u.email as uploader_email
                FROM `imports` i
                JOIN `users` u ON u.id = i.uploaded_by
                ORDER BY i.id DESC
                LIMIT :limit';

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $list = [];
        foreach ($rows as $row) {
            $uploader = User::fromArray([
                'id' => $row['uploaded_by'],
                'name' => $row['uploader_name'],
                'email' => $row['uploader_email'],
            ]);
            $list[] = ImportBatch::fromArray($row, $uploader);
        }

        return $list;
    }

    /* -------------------------------------------------------------------------
     * CLASS MAPPINGS PERSISTENCE
     * ------------------------------------------------------------------------- */

    public function findClassMapping(string $normalizedPattern): ?array
    {
        $sql = 'SELECT * FROM `import_class_mappings` WHERE `normalized_pattern` = :pattern LIMIT 1';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':pattern' => trim($normalizedPattern)]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public function saveClassMapping(string $rawPattern, string $normalizedPattern, int $canonicalClassId, int $createdBy): bool
    {
        $sql = 'INSERT INTO `import_class_mappings` (`raw_pattern`, `normalized_pattern`, `canonical_class_id`, `created_by`, `created_at`, `updated_at`)
                VALUES (:raw_pattern, :normalized_pattern, :canonical_class_id, :created_by, NOW(), NOW())
                ON DUPLICATE KEY UPDATE 
                    `canonical_class_id` = VALUES(`canonical_class_id`),
                    `raw_pattern` = VALUES(`raw_pattern`),
                    `updated_at` = NOW()';

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':raw_pattern' => trim($rawPattern),
            ':normalized_pattern' => trim($normalizedPattern),
            ':canonical_class_id' => $canonicalClassId,
            ':created_by' => $createdBy,
        ]);
    }

    public function getAllClassMappings(): array
    {
        $sql = 'SELECT icm.*, c.name as class_name, c.section_arm 
                FROM `import_class_mappings` icm
                JOIN `classes` c ON c.id = icm.canonical_class_id
                ORDER BY icm.normalized_pattern ASC';
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    private ?bool $hasProcessedChunksColumn = null;

    private function hasProcessedChunksColumn(): bool
    {
        if ($this->hasProcessedChunksColumn !== null) {
            return $this->hasProcessedChunksColumn;
        }

        try {
            $driver = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
            if ($driver === 'sqlite') {
                $stmt = $this->pdo->query("PRAGMA table_info(`imports`)");
                if ($stmt) {
                    $cols = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'name');
                    return $this->hasProcessedChunksColumn = in_array('processed_chunks_json', $cols, true);
                }
            } else {
                $stmt = $this->pdo->query("SHOW COLUMNS FROM `imports` LIKE 'processed_chunks_json'");
                return $this->hasProcessedChunksColumn = ($stmt && $stmt->fetch() !== false);
            }
        } catch (\Throwable) {
        }

        return $this->hasProcessedChunksColumn = true;
    }

    /* -------------------------------------------------------------------------
     * CHUNK IDEMPOTENCY & METRICS
     * ------------------------------------------------------------------------- */

    public function isChunkProcessed(int $importId, int $chunkNumber): bool
    {
        if (!$this->hasProcessedChunksColumn()) {
            return false;
        }

        $sql = 'SELECT `processed_chunks_json` FROM `imports` WHERE `id` = :id LIMIT 1';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $importId]);
        $json = $stmt->fetchColumn();

        if (!$json) {
            return false;
        }

        $chunks = json_decode((string)$json, true) ?: [];
        return in_array($chunkNumber, $chunks, true);
    }

    public function recordChunkProcessed(int $importId, int $chunkNumber, int $validCount, int $invalidCount): bool
    {
        if ($this->hasProcessedChunksColumn()) {
            $sql = 'SELECT `processed_chunks_json`, `valid_rows`, `invalid_rows` FROM `imports` WHERE `id` = :id LIMIT 1';
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':id' => $importId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$row) {
                return false;
            }

            $chunks = json_decode((string)($row['processed_chunks_json'] ?? '[]'), true) ?: [];
            if (!in_array($chunkNumber, $chunks, true)) {
                $chunks[] = $chunkNumber;
            }

            $newValid = (int)$row['valid_rows'] + $validCount;
            $newInvalid = (int)$row['invalid_rows'] + $invalidCount;

            $updateSql = 'UPDATE `imports` 
                          SET `processed_chunks_json` = :chunks_json, 
                              `valid_rows` = :valid_rows, 
                              `invalid_rows` = :invalid_rows,
                              `status` = "validated"
                          WHERE `id` = :id';

            $updateStmt = $this->pdo->prepare($updateSql);
            return $updateStmt->execute([
                ':chunks_json' => json_encode($chunks),
                ':valid_rows' => $newValid,
                ':invalid_rows' => $newInvalid,
                ':id' => $importId,
            ]);
        } else {
            $sql = 'SELECT `valid_rows`, `invalid_rows` FROM `imports` WHERE `id` = :id LIMIT 1';
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':id' => $importId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$row) {
                return false;
            }

            $newValid = (int)$row['valid_rows'] + $validCount;
            $newInvalid = (int)$row['invalid_rows'] + $invalidCount;

            $updateSql = 'UPDATE `imports` 
                          SET `valid_rows` = :valid_rows, 
                              `invalid_rows` = :invalid_rows,
                              `status` = "validated"
                          WHERE `id` = :id';

            $updateStmt = $this->pdo->prepare($updateSql);
            return $updateStmt->execute([
                ':valid_rows' => $newValid,
                ':invalid_rows' => $newInvalid,
                ':id' => $importId,
            ]);
        }
    }

    public function findBySha256(string $sha256): ?ImportBatch
    {
        $sql = 'SELECT i.*, u.name as uploader_name, u.email as uploader_email
                FROM `imports` i
                JOIN `users` u ON u.id = i.uploaded_by
                WHERE i.sha256 = :sha256
                ORDER BY i.id DESC
                LIMIT 1';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':sha256' => $sha256]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        $uploader = User::fromArray([
            'id' => $row['uploaded_by'],
            'name' => $row['uploader_name'],
            'email' => $row['uploader_email'],
        ]);

        return ImportBatch::fromArray($row, $uploader);
    }
}
