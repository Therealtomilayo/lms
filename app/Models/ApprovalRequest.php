<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Domain Approval Request Entity for Super Admin Two-Tier Governance (§6, §58.1-§58.3)
 */
final class ApprovalRequest
{
    public const TYPE_STUDENT_REGISTRATION = 'student_registration';
    public const TYPE_TEACHER_REGISTRATION = 'teacher_registration';
    public const TYPE_STUDENT_REPETITION = 'student_repetition';
    public const TYPE_USER_DELETION = 'user_deletion';
    public const TYPE_ADMIN_ROLE_ASSIGNMENT = 'admin_role_assignment';

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    public function __construct(
        public readonly int $id,
        public readonly string $requestType,
        public readonly string $entityType,
        public readonly int $entityId,
        public readonly int $requesterId,
        public readonly ?int $reviewerId = null,
        public readonly string $status = self::STATUS_PENDING,
        public readonly ?array $payload = null,
        public readonly ?string $rejectionReason = null,
        public readonly string $createdAt = '',
        public readonly ?string $reviewedAt = null,
        public readonly ?string $updatedAt = null,
        public readonly ?string $requesterName = null,
        public readonly ?string $requesterEmail = null,
        public readonly ?string $reviewerName = null,
        public readonly ?string $entityName = null,
        public readonly ?string $entityIdentifier = null
    ) {
    }

    public static function fromArray(array $data): self
    {
        $payload = null;
        if (!empty($data['payload'])) {
            $payload = is_string($data['payload']) ? json_decode($data['payload'], true) : (array)$data['payload'];
        }

        return new self(
            id: (int)$data['id'],
            requestType: (string)$data['request_type'],
            entityType: (string)$data['entity_type'],
            entityId: (int)$data['entity_id'],
            requesterId: (int)$data['requester_id'],
            reviewerId: isset($data['reviewer_id']) ? (int)$data['reviewer_id'] : null,
            status: (string)($data['status'] ?? self::STATUS_PENDING),
            payload: $payload,
            rejectionReason: $data['rejection_reason'] ?? null,
            createdAt: (string)($data['created_at'] ?? ''),
            reviewedAt: $data['reviewed_at'] ?? null,
            updatedAt: $data['updated_at'] ?? null,
            requesterName: $data['requester_name'] ?? null,
            requesterEmail: $data['requester_email'] ?? null,
            reviewerName: $data['reviewer_name'] ?? null,
            entityName: $data['entity_name'] ?? null,
            entityIdentifier: $data['entity_identifier'] ?? null
        );
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    public function getTypeLabel(): string
    {
        return match ($this->requestType) {
            self::TYPE_STUDENT_REGISTRATION => 'Student Registration',
            self::TYPE_TEACHER_REGISTRATION => 'Faculty Registration',
            self::TYPE_STUDENT_REPETITION => 'Student Repetition Request',
            self::TYPE_USER_DELETION => 'User Deletion Request',
            self::TYPE_ADMIN_ROLE_ASSIGNMENT => 'Admin Role Assignment',
            default => ucwords(str_replace('_', ' ', $this->requestType)),
        };
    }

    public function getStatusVariant(): string
    {
        return match ($this->status) {
            self::STATUS_APPROVED => 'success',
            self::STATUS_REJECTED => 'danger',
            default => 'warning',
        };
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'request_type' => $this->requestType,
            'entity_type' => $this->entityType,
            'entity_id' => $this->entityId,
            'requester_id' => $this->requesterId,
            'reviewer_id' => $this->reviewerId,
            'status' => $this->status,
            'payload' => $this->payload,
            'rejection_reason' => $this->rejectionReason,
            'created_at' => $this->createdAt,
            'reviewed_at' => $this->reviewedAt,
            'updated_at' => $this->updatedAt,
            'requester_name' => $this->requesterName,
            'requester_email' => $this->requesterEmail,
            'reviewer_name' => $this->reviewerName,
            'entity_name' => $this->entityName,
            'entity_identifier' => $this->entityIdentifier,
        ];
    }
}
