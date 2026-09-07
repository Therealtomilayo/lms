<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Models\ApprovalRequest;
use PDO;

/**
 * Repository for Super Admin Two-Tier Governance & Approvals (§6, §58.1-§58.3)
 */
class ApprovalRepository
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::getInstance();
    }

    public function createRequest(
        string $requestType,
        string $entityType,
        int $entityId,
        int $requesterId,
        ?array $payload = null
    ): ApprovalRequest {
        $now = date('Y-m-d H:i:s');
        $jsonPayload = $payload !== null ? json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null;

        $sql = 'INSERT INTO `approval_requests` 
                (`request_type`, `entity_type`, `entity_id`, `requester_id`, `status`, `payload`, `created_at`, `updated_at`)
                VALUES (:request_type, :entity_type, :entity_id, :requester_id, :status, :payload, :created_at, :updated_at)';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':request_type' => $requestType,
            ':entity_type' => $entityType,
            ':entity_id' => $entityId,
            ':requester_id' => $requesterId,
            ':status' => ApprovalRequest::STATUS_PENDING,
            ':payload' => $jsonPayload,
            ':created_at' => $now,
            ':updated_at' => $now,
        ]);

        $id = (int)$this->pdo->lastInsertId();
        return $this->findRequestById($id) ?? new ApprovalRequest(
            id: $id,
            requestType: $requestType,
            entityType: $entityType,
            entityId: $entityId,
            requesterId: $requesterId,
            status: ApprovalRequest::STATUS_PENDING,
            payload: $payload,
            createdAt: $now
        );
    }

    public function findRequestById(int $id): ?ApprovalRequest
    {
        $sql = 'SELECT ar.*,
                       req.name AS requester_name, req.email AS requester_email,
                       rev.name AS reviewer_name,
                       target_u.name AS entity_name, target_u.email AS entity_identifier
                FROM `approval_requests` ar
                JOIN `users` req ON req.id = ar.requester_id
                LEFT JOIN `users` rev ON rev.id = ar.reviewer_id
                LEFT JOIN `users` target_u ON (ar.entity_type = "user" AND target_u.id = ar.entity_id)
                WHERE ar.id = :id
                LIMIT 1';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        return $this->hydrateRow($row);
    }

    public function findPendingByEntity(string $entityType, int $entityId): ?ApprovalRequest
    {
        $sql = 'SELECT ar.*,
                       req.name AS requester_name, req.email AS requester_email,
                       rev.name AS reviewer_name,
                       target_u.name AS entity_name, target_u.email AS entity_identifier
                FROM `approval_requests` ar
                JOIN `users` req ON req.id = ar.requester_id
                LEFT JOIN `users` rev ON rev.id = ar.reviewer_id
                LEFT JOIN `users` target_u ON (ar.entity_type = "user" AND target_u.id = ar.entity_id)
                WHERE ar.entity_type = :entity_type 
                  AND ar.entity_id = :entity_id 
                  AND ar.status = :status
                ORDER BY ar.id DESC
                LIMIT 1';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':entity_type' => $entityType,
            ':entity_id' => $entityId,
            ':status' => ApprovalRequest::STATUS_PENDING,
        ]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? $this->hydrateRow($row) : null;
    }

    /**
     * @return ApprovalRequest[]
     */
    public function getRequests(
        ?string $status = null,
        ?string $requestType = null,
        int $limit = 50,
        int $offset = 0
    ): array {
        $where = [];
        $params = [];

        if ($status !== null && $status !== '') {
            $where[] = 'ar.status = :status';
            $params[':status'] = $status;
        }

        if ($requestType !== null && $requestType !== '') {
            $where[] = 'ar.request_type = :request_type';
            $params[':request_type'] = $requestType;
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $sql = "SELECT ar.*,
                       req.name AS requester_name, req.email AS requester_email,
                       rev.name AS reviewer_name,
                       target_u.name AS entity_name, target_u.email AS entity_identifier
                FROM `approval_requests` ar
                JOIN `users` req ON req.id = ar.requester_id
                LEFT JOIN `users` rev ON rev.id = ar.reviewer_id
                LEFT JOIN `users` target_u ON (ar.entity_type = 'user' AND target_u.id = ar.entity_id)
                {$whereClause}
                ORDER BY CASE WHEN ar.status = 'pending' THEN 0 ELSE 1 END, ar.id DESC
                LIMIT {$limit} OFFSET {$offset}";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        return array_map(fn(array $r) => $this->hydrateRow($r), $rows);
    }

    public function countPending(): int
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM `approval_requests` WHERE `status` = :status');
        $stmt->execute([':status' => ApprovalRequest::STATUS_PENDING]);
        return (int)$stmt->fetchColumn();
    }

    public function getSummaryCounts(): array
    {
        $stmt = $this->pdo->query('
            SELECT 
                COUNT(*) AS total,
                SUM(CASE WHEN `status` = "pending" THEN 1 ELSE 0 END) AS pending_count,
                SUM(CASE WHEN `status` = "approved" THEN 1 ELSE 0 END) AS approved_count,
                SUM(CASE WHEN `status` = "rejected" THEN 1 ELSE 0 END) AS rejected_count,
                SUM(CASE WHEN `request_type` = "student_registration" THEN 1 ELSE 0 END) AS student_reg_count,
                SUM(CASE WHEN `request_type` = "teacher_registration" THEN 1 ELSE 0 END) AS teacher_reg_count,
                SUM(CASE WHEN `request_type` = "student_repetition" THEN 1 ELSE 0 END) AS repetition_count,
                SUM(CASE WHEN `request_type` = "user_deletion" THEN 1 ELSE 0 END) AS user_deletion_count,
                SUM(CASE WHEN `request_type` = "admin_role_assignment" THEN 1 ELSE 0 END) AS role_elevation_count
            FROM `approval_requests`
        ');
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        return [
            'total' => (int)($row['total'] ?? 0),
            'pending' => (int)($row['pending_count'] ?? 0),
            'approved' => (int)($row['approved_count'] ?? 0),
            'rejected' => (int)($row['rejected_count'] ?? 0),
            'student_reg' => (int)($row['student_reg_count'] ?? 0),
            'teacher_reg' => (int)($row['teacher_reg_count'] ?? 0),
            'repetition' => (int)($row['repetition_count'] ?? 0),
            'user_deletion' => (int)($row['user_deletion_count'] ?? 0),
            'role_elevation' => (int)($row['role_elevation_count'] ?? 0),
        ];
    }

    public function approve(int $id, int $reviewerId): bool
    {
        $now = date('Y-m-d H:i:s');
        $stmt = $this->pdo->prepare('
            UPDATE `approval_requests` 
            SET `status` = :status,
                `reviewer_id` = :reviewer_id,
                `reviewed_at` = :reviewed_at,
                `updated_at` = :updated_at
            WHERE `id` = :id AND `status` = :pending_status
        ');

        return $stmt->execute([
            ':id' => $id,
            ':status' => ApprovalRequest::STATUS_APPROVED,
            ':reviewer_id' => $reviewerId,
            ':reviewed_at' => $now,
            ':updated_at' => $now,
            ':pending_status' => ApprovalRequest::STATUS_PENDING,
        ]);
    }

    public function reject(int $id, int $reviewerId, string $rejectionReason): bool
    {
        $now = date('Y-m-d H:i:s');
        $stmt = $this->pdo->prepare('
            UPDATE `approval_requests` 
            SET `status` = :status,
                `reviewer_id` = :reviewer_id,
                `rejection_reason` = :rejection_reason,
                `reviewed_at` = :reviewed_at,
                `updated_at` = :updated_at
            WHERE `id` = :id AND `status` = :pending_status
        ');

        return $stmt->execute([
            ':id' => $id,
            ':status' => ApprovalRequest::STATUS_REJECTED,
            ':reviewer_id' => $reviewerId,
            ':rejection_reason' => $rejectionReason,
            ':reviewed_at' => $now,
            ':updated_at' => $now,
            ':pending_status' => ApprovalRequest::STATUS_PENDING,
        ]);
    }

    private function hydrateRow(array $row): ApprovalRequest
    {
        // Extract candidate entity info from payload if not populated from joins
        $payload = null;
        if (!empty($row['payload'])) {
            $payload = is_string($row['payload']) ? json_decode($row['payload'], true) : (array)$row['payload'];
        }

        if (empty($row['entity_name']) && !empty($payload['name'])) {
            $row['entity_name'] = $payload['name'];
        }

        if (empty($row['entity_identifier'])) {
            $row['entity_identifier'] = $payload['admission_number'] ?? $payload['staff_id'] ?? $payload['email'] ?? null;
        }

        return ApprovalRequest::fromArray($row);
    }
}
