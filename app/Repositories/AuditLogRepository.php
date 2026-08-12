<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Models\AuditLog;
use PDO;

/**
 * Repository for Querying and Exploring Immutable Audit Records
 */
class AuditLogRepository
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::getConnection();
    }

    /**
     * Paginate audit log entries with optional filters.
     */
    public function paginate(int $page = 1, int $perPage = 25, array $filters = []): array
    {
        $page = max(1, $page);
        $perPage = max(1, min(100, $perPage));
        $offset = ($page - 1) * $perPage;

        $where = [];
        $params = [];

        if (!empty($filters['action'])) {
            $where[] = "a.action = :action";
            $params[':action'] = $filters['action'];
        }

        if (!empty($filters['entity_type'])) {
            $where[] = "a.entity_type = :entity_type";
            $params[':entity_type'] = $filters['entity_type'];
        }

        if (!empty($filters['actor_user_id'])) {
            $where[] = "a.actor_user_id = :actor_user_id";
            $params[':actor_user_id'] = (int)$filters['actor_user_id'];
        }

        $whereClause = !empty($where) ? ('WHERE ' . implode(' AND ', $where)) : '';

        // Total count
        $countSql = "SELECT COUNT(*) FROM audit_logs a {$whereClause}";
        $countStmt = $this->db->prepare($countSql);
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        // Data query
        $sql = "
            SELECT 
                a.*,
                u.name as actor_name,
                u.email as actor_email
            FROM audit_logs a
            LEFT JOIN users u ON a.actor_user_id = u.id
            {$whereClause}
            ORDER BY a.id DESC
            LIMIT :limit OFFSET :offset
        ";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $logs = array_map(fn($row) => new AuditLog($row), $rows);

        $totalPages = (int)ceil($total / $perPage);

        return [
            'data' => $logs,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => max(1, $totalPages),
        ];
    }

    /**
     * Find single audit log entry by ID.
     */
    public function findById(int $id): ?AuditLog
    {
        $sql = "
            SELECT 
                a.*,
                u.name as actor_name,
                u.email as actor_email
            FROM audit_logs a
            LEFT JOIN users u ON a.actor_user_id = u.id
            WHERE a.id = :id
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? new AuditLog($row) : null;
    }

    /**
     * Get distinct actions for filtering.
     */
    public function getDistinctActions(): array
    {
        $stmt = $this->db->query("SELECT DISTINCT action FROM audit_logs ORDER BY action ASC");
        return $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
    }

    /**
     * Get distinct entity types for filtering.
     */
    public function getDistinctEntityTypes(): array
    {
        $stmt = $this->db->query("SELECT DISTINCT entity_type FROM audit_logs ORDER BY entity_type ASC");
        return $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
    }
}
