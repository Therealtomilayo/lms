<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Domain Entity for an Awarded Student Badge (SRS §33, §57 Phase 3)
 */
#[\AllowDynamicProperties]
final class StudentBadge
{
    public function __construct(
        public readonly int $id,
        public readonly int $studentId,
        public readonly int $badgeId,
        public readonly int $awardedBy,
        public readonly string $reason,
        public readonly ?int $classSubjectId = null,
        public readonly ?int $sessionId = null,
        public readonly ?int $termId = null,
        public readonly ?string $awardedAt = null,
        public ?Badge $badge = null,
        public ?User $awarder = null,
        public ?ClassSubject $classSubject = null
    ) {
    }

    public static function fromArray(array $data, ?Badge $badge = null): self
    {
        return new self(
            id: (int)($data['id'] ?? 0),
            studentId: (int)($data['student_id'] ?? 0),
            badgeId: (int)($data['badge_id'] ?? 0),
            awardedBy: (int)($data['awarded_by'] ?? 0),
            reason: (string)($data['reason'] ?? ''),
            classSubjectId: isset($data['class_subject_id']) && $data['class_subject_id'] !== null ? (int)$data['class_subject_id'] : null,
            sessionId: isset($data['session_id']) && $data['session_id'] !== null ? (int)$data['session_id'] : null,
            termId: isset($data['term_id']) && $data['term_id'] !== null ? (int)$data['term_id'] : null,
            awardedAt: isset($data['awarded_at']) ? (string)$data['awarded_at'] : null,
            badge: $badge
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'student_id' => $this->studentId,
            'badge_id' => $this->badgeId,
            'awarded_by' => $this->awardedBy,
            'reason' => $this->reason,
            'class_subject_id' => $this->classSubjectId,
            'session_id' => $this->sessionId,
            'term_id' => $this->termId,
            'awarded_at' => $this->awardedAt,
            'badge' => $this->badge?->toArray(),
        ];
    }
}
