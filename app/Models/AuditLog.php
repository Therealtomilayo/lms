<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Domain Model for Immutable Audit Log Records
 */
class AuditLog
{
    public ?int $id;
    public ?int $actorUserId;
    public ?string $actorName;
    public ?string $actorEmail;
    public string $action;
    public string $entityType;
    public int $entityId;
    public ?array $beforeJson;
    public ?array $afterJson;
    public ?array $metadataJson;
    public ?string $ipHash;
    public ?string $userAgentHash;
    public ?string $requestId;
    public string $createdAt;

    public function __construct(array $attributes = [])
    {
        $this->id = isset($attributes['id']) ? (int)$attributes['id'] : null;
        $this->actorUserId = isset($attributes['actor_user_id']) ? (int)$attributes['actor_user_id'] : null;
        $this->actorName = $attributes['actor_name'] ?? null;
        $this->actorEmail = $attributes['actor_email'] ?? null;
        $this->action = (string)($attributes['action'] ?? '');
        $this->entityType = (string)($attributes['entity_type'] ?? '');
        $this->entityId = (int)($attributes['entity_id'] ?? 0);

        $this->beforeJson = is_string($attributes['before_json'] ?? null)
            ? json_decode($attributes['before_json'], true)
            : ($attributes['before_json'] ?? null);

        $this->afterJson = is_string($attributes['after_json'] ?? null)
            ? json_decode($attributes['after_json'], true)
            : ($attributes['after_json'] ?? null);

        $this->metadataJson = is_string($attributes['metadata_json'] ?? null)
            ? json_decode($attributes['metadata_json'], true)
            : ($attributes['metadata_json'] ?? null);

        $this->ipHash = $attributes['ip_hash'] ?? null;
        $this->userAgentHash = $attributes['user_agent_hash'] ?? null;
        $this->requestId = $attributes['request_id'] ?? null;
        $this->createdAt = (string)($attributes['created_at'] ?? gmdate('Y-m-d H:i:s'));
    }
}
