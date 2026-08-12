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

    public function create(
        int $uploadedBy,
        string $type,
        string $originalName,
        string $sha256,
        int $totalRows = 0,
        int $validRows = 0,
        int $invalidRows = 0,
        string $status = 'validated'
    ): ImportBatch {
        $now = date('Y-m-d H:i:s');
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
}
