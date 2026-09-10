<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Domain Student Promotion & Graduation Decision Entity (SRS §17, §18, §58.3)
 */
final class Promotion
{
    public const DECISION_PROMOTED = 'promoted';
    public const DECISION_REPEATING = 'repeating';
    public const DECISION_GRADUATED = 'graduated';
    public const DECISION_WITHDRAWN = 'withdrawn';

    public const STATUS_PRELIMINARY = 'preliminary';
    public const STATUS_STAGED_REVIEW = 'staged_review';
    public const STATUS_FINALIZED = 'finalized';

    public function __construct(
        public readonly int $id,
        public readonly int $studentId,
        public readonly int $fromSessionId,
        public readonly int $fromClassId,
        public readonly ?int $toSessionId = null,
        public readonly ?int $toClassId = null,
        public readonly string $decision = self::DECISION_PROMOTED,
        public readonly float $annualAverage = 0.0,
        public readonly ?array $termAverages = null,
        public readonly string $evaluationStatus = self::STATUS_PRELIMINARY,
        public readonly ?int $approvalRequestId = null,
        public readonly ?string $overrideReason = null,
        public readonly int $promotedBy = 0,
        public readonly string $promotedAt = '',
        public readonly string $createdAt = '',
        public readonly ?string $updatedAt = null,
        public readonly ?string $studentName = null,
        public readonly ?string $studentAdmissionNumber = null,
        public readonly ?string $fromClassName = null,
        public readonly ?string $toClassName = null,
        public readonly ?string $fromSessionName = null,
        public readonly ?string $toSessionName = null
    ) {
    }

    public static function fromArray(array $data): self
    {
        $termAverages = null;
        if (!empty($data['term_averages'])) {
            $termAverages = is_string($data['term_averages'])
                ? json_decode($data['term_averages'], true)
                : (array)$data['term_averages'];
        }

        return new self(
            id: (int)$data['id'],
            studentId: (int)$data['student_id'],
            fromSessionId: (int)$data['from_session_id'],
            fromClassId: (int)$data['from_class_id'],
            toSessionId: isset($data['to_session_id']) ? (int)$data['to_session_id'] : null,
            toClassId: isset($data['to_class_id']) ? (int)$data['to_class_id'] : null,
            decision: (string)($data['decision'] ?? self::DECISION_PROMOTED),
            annualAverage: (float)($data['annual_average'] ?? 0.0),
            termAverages: $termAverages,
            evaluationStatus: (string)($data['evaluation_status'] ?? self::STATUS_PRELIMINARY),
            approvalRequestId: isset($data['approval_request_id']) ? (int)$data['approval_request_id'] : null,
            overrideReason: $data['override_reason'] ?? null,
            promotedBy: (int)($data['promoted_by'] ?? 0),
            promotedAt: (string)($data['promoted_at'] ?? ''),
            createdAt: (string)($data['created_at'] ?? ''),
            updatedAt: $data['updated_at'] ?? null,
            studentName: $data['student_name'] ?? null,
            studentAdmissionNumber: $data['student_admission_number'] ?? null,
            fromClassName: $data['from_class_name'] ?? null,
            toClassName: $data['to_class_name'] ?? null,
            fromSessionName: $data['from_session_name'] ?? null,
            toSessionName: $data['to_session_name'] ?? null
        );
    }

    public function isPromoted(): bool
    {
        return $this->decision === self::DECISION_PROMOTED;
    }

    public function isGraduated(): bool
    {
        return $this->decision === self::DECISION_GRADUATED;
    }

    public function isRepeating(): bool
    {
        return $this->decision === self::DECISION_REPEATING;
    }

    public function isWithdrawn(): bool
    {
        return $this->decision === self::DECISION_WITHDRAWN;
    }

    public function isPreliminary(): bool
    {
        return $this->evaluationStatus === self::STATUS_PRELIMINARY;
    }

    public function isStagedReview(): bool
    {
        return $this->evaluationStatus === self::STATUS_STAGED_REVIEW;
    }

    public function isFinalized(): bool
    {
        return $this->evaluationStatus === self::STATUS_FINALIZED;
    }

    public function getFormattedAverage(): string
    {
        return number_format($this->annualAverage, 2) . '%';
    }

    public function getDecisionBadgeColor(): string
    {
        return match($this->decision) {
            self::DECISION_GRADUATED => 'bg-purple-100 text-purple-800 border-purple-200',
            self::DECISION_PROMOTED  => 'bg-emerald-100 text-emerald-800 border-emerald-200',
            self::DECISION_REPEATING => 'bg-rose-100 text-rose-800 border-rose-200',
            default                  => 'bg-slate-100 text-slate-800 border-slate-200'
        };
    }
}
