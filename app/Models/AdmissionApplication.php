<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Domain Entity for Primary Admission Application Dossier
 */
final class AdmissionApplication
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_UNDER_REVIEW = 'under_review';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    /**
     * @param AdmissionWard[] $wards
     */
    public function __construct(
        public readonly int $id,
        public readonly string $applicationNumber,
        public readonly int $admissionSessionId,
        public readonly int $applicantUserId,
        public readonly string $status = self::STATUS_DRAFT,
        public readonly ?string $rejectionReason = null,
        public readonly ?string $submittedAt = null,
        public readonly ?string $reviewedAt = null,
        public readonly ?int $reviewedBy = null,
        public readonly ?string $createdAt = null,
        public readonly ?string $updatedAt = null,
        public readonly ?User $applicant = null,
        public readonly ?AdmissionSession $admissionSession = null,
        public readonly array $wards = []
    ) {
    }

    public static function fromArray(
        array $data,
        ?User $applicant = null,
        ?AdmissionSession $session = null,
        array $wards = []
    ): self {
        return new self(
            id: (int)$data['id'],
            applicationNumber: (string)$data['application_number'],
            admissionSessionId: (int)$data['admission_session_id'],
            applicantUserId: (int)$data['applicant_user_id'],
            status: (string)($data['status'] ?? self::STATUS_DRAFT),
            rejectionReason: $data['rejection_reason'] ?? null,
            submittedAt: $data['submitted_at'] ?? null,
            reviewedAt: $data['reviewed_at'] ?? null,
            reviewedBy: !empty($data['reviewed_by']) ? (int)$data['reviewed_by'] : null,
            createdAt: $data['created_at'] ?? null,
            updatedAt: $data['updated_at'] ?? null,
            applicant: $applicant,
            admissionSession: $session,
            wards: $wards
        );
    }

    public function __get(string $name): mixed
    {
        if ($name === 'sessionId') {
            return $this->admissionSessionId;
        }
        return null;
    }

    public function __isset(string $name): bool
    {
        return $name === 'sessionId' || isset($this->{$name});
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isSubmitted(): bool
    {
        return $this->status === self::STATUS_SUBMITTED;
    }

    public function isUnderReview(): bool
    {
        return $this->status === self::STATUS_UNDER_REVIEW;
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    public function isLockedForEditing(): bool
    {
        return in_array($this->status, [self::STATUS_SUBMITTED, self::STATUS_UNDER_REVIEW, self::STATUS_APPROVED, self::STATUS_REJECTED], true);
    }

    public function allWardsPaid(): bool
    {
        if (empty($this->wards)) {
            return false;
        }

        foreach ($this->wards as $ward) {
            if (!$ward->isPaid()) {
                return false;
            }
        }

        return true;
    }

    public function getStatusBadge(): array
    {
        return match ($this->status) {
            self::STATUS_DRAFT => ['label' => 'Draft', 'class' => 'bg-slate-100 text-slate-700 border-slate-200'],
            self::STATUS_SUBMITTED => ['label' => 'Submitted', 'class' => 'bg-blue-50 text-blue-700 border-blue-200'],
            self::STATUS_UNDER_REVIEW => ['label' => 'Under Review', 'class' => 'bg-amber-50 text-amber-700 border-amber-200'],
            self::STATUS_APPROVED => ['label' => 'Approved', 'class' => 'bg-emerald-50 text-emerald-700 border-emerald-200'],
            self::STATUS_REJECTED => ['label' => 'Application Rejected', 'class' => 'bg-rose-50 text-rose-700 border-rose-200'],
            default => ['label' => ucfirst($this->status), 'class' => 'bg-slate-100 text-slate-700 border-slate-200'],
        };
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'application_number' => $this->applicationNumber,
            'admission_session_id' => $this->admissionSessionId,
            'applicant_user_id' => $this->applicantUserId,
            'status' => $this->status,
            'rejection_reason' => $this->rejectionReason,
            'submitted_at' => $this->submittedAt,
            'reviewed_at' => $this->reviewedAt,
            'reviewed_by' => $this->reviewedBy,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
            'wards' => array_map(fn(AdmissionWard $w) => $w->toArray(), $this->wards),
        ];
    }
}
