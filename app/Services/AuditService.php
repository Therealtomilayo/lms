<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use PDO;

/**
 * Audit Logging Service
 */
class AuditService
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::getConnection();
    }

    /**
     * Record an append-only audit log entry.
     */
    public function log(
        string $action,
        string $entityType,
        int $entityId,
        ?int $actorUserId = null,
        ?array $before = null,
        ?array $after = null,
        ?array $metadata = null
    ): bool {
        $sql = "
            INSERT INTO audit_logs (
                actor_user_id, action, entity_type, entity_id,
                before_json, after_json, metadata_json, created_at
            ) VALUES (
                :actor_user_id, :action, :entity_type, :entity_id,
                :before_json, :after_json, :metadata_json, CURRENT_TIMESTAMP
            )
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':actor_user_id', $actorUserId, $actorUserId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindValue(':action', $action);
        $stmt->bindValue(':entity_type', $entityType);
        $stmt->bindValue(':entity_id', $entityId, PDO::PARAM_INT);
        $stmt->bindValue(':before_json', $before !== null ? json_encode($before, JSON_THROW_ON_ERROR) : null);
        $stmt->bindValue(':after_json', $after !== null ? json_encode($after, JSON_THROW_ON_ERROR) : null);
        $stmt->bindValue(':metadata_json', $metadata !== null ? json_encode($metadata, JSON_THROW_ON_ERROR) : null);

        return $stmt->execute();
    }
}
