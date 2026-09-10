<?php

declare(strict_types=1);

namespace App\Models;

use DateTimeImmutable;

/**
 * Domain LiveClass Entity (SRS §31)
 * Represents a scheduled online synchronous class using Google Meet, Zoom, or Microsoft Teams.
 */
final class LiveClass
{
    public const PLATFORM_GOOGLE_MEET = 'google_meet';
    public const PLATFORM_ZOOM = 'zoom';
    public const PLATFORM_MICROSOFT_TEAMS = 'microsoft_teams';
    public const PLATFORM_OTHER = 'other';

    public const STATUS_SCHEDULED = 'scheduled';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    public function __construct(
        public readonly int $id,
        public readonly ?int $sessionId,
        public readonly ?int $termId,
        public readonly int $classSubjectId,
        public readonly int $teacherId,
        public readonly string $title,
        public readonly ?string $description,
        public readonly string $platform,
        public readonly string $meetingLink,
        public readonly ?string $meetingPasscode,
        public readonly string $scheduledDate,
        public readonly string $startTime,
        public readonly int $durationMinutes,
        public readonly string $status = self::STATUS_SCHEDULED,
        public readonly bool $isPublished = true,
        public readonly ?string $createdAt = null,
        public readonly ?string $updatedAt = null,
        public readonly ?string $className = null,
        public readonly ?string $subjectName = null,
        public readonly ?string $teacherName = null,
        public readonly ?string $sessionName = null,
        public readonly ?string $termName = null,
        public readonly int $attendeesCount = 0,
        public readonly bool $hasJoined = false
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            id: (int)($data['id'] ?? 0),
            sessionId: !empty($data['session_id']) ? (int)$data['session_id'] : null,
            termId: !empty($data['term_id']) ? (int)$data['term_id'] : null,
            classSubjectId: (int)($data['class_subject_id'] ?? 0),
            teacherId: (int)($data['teacher_id'] ?? 0),
            title: (string)($data['title'] ?? ''),
            description: isset($data['description']) ? (string)$data['description'] : null,
            platform: (string)($data['platform'] ?? self::PLATFORM_GOOGLE_MEET),
            meetingLink: (string)($data['meeting_link'] ?? ''),
            meetingPasscode: isset($data['meeting_passcode']) && $data['meeting_passcode'] !== '' ? (string)$data['meeting_passcode'] : null,
            scheduledDate: (string)($data['scheduled_date'] ?? date('Y-m-d')),
            startTime: (string)($data['start_time'] ?? '00:00:00'),
            durationMinutes: (int)($data['duration_minutes'] ?? 40),
            status: (string)($data['status'] ?? self::STATUS_SCHEDULED),
            isPublished: (bool)($data['is_published'] ?? true),
            createdAt: isset($data['created_at']) ? (string)$data['created_at'] : null,
            updatedAt: isset($data['updated_at']) ? (string)$data['updated_at'] : null,
            className: isset($data['class_name']) ? (string)$data['class_name'] : null,
            subjectName: isset($data['subject_name']) ? (string)$data['subject_name'] : null,
            teacherName: isset($data['teacher_name']) ? (string)$data['teacher_name'] : null,
            sessionName: isset($data['session_name']) ? (string)$data['session_name'] : null,
            termName: isset($data['term_name']) ? (string)$data['term_name'] : null,
            attendeesCount: (int)($data['attendees_count'] ?? 0),
            hasJoined: (bool)($data['has_joined'] ?? false)
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'session_id' => $this->sessionId,
            'term_id' => $this->termId,
            'class_subject_id' => $this->classSubjectId,
            'teacher_id' => $this->teacherId,
            'title' => $this->title,
            'description' => $this->description,
            'platform' => $this->platform,
            'meeting_link' => $this->meetingLink,
            'meeting_passcode' => $this->meetingPasscode,
            'scheduled_date' => $this->scheduledDate,
            'start_time' => $this->startTime,
            'duration_minutes' => $this->durationMinutes,
            'status' => $this->status,
            'is_published' => $this->isPublished ? 1 : 0,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
            'class_name' => $this->className,
            'subject_name' => $this->subjectName,
            'teacher_name' => $this->teacherName,
            'session_name' => $this->sessionName,
            'term_name' => $this->termName,
            'attendees_count' => $this->attendeesCount,
            'has_joined' => $this->hasJoined,
        ];
    }

    public function isScheduled(): bool
    {
        return $this->status === self::STATUS_SCHEDULED;
    }

    public function isInProgress(): bool
    {
        return $this->status === self::STATUS_IN_PROGRESS;
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function getPlatformLabel(): string
    {
        return match ($this->platform) {
            self::PLATFORM_GOOGLE_MEET => 'Google Meet',
            self::PLATFORM_ZOOM => 'Zoom Meeting',
            self::PLATFORM_MICROSOFT_TEAMS => 'Microsoft Teams',
            default => 'Online Conference',
        };
    }

    public function getPlatformColorClass(): string
    {
        return match ($this->platform) {
            self::PLATFORM_GOOGLE_MEET => 'bg-emerald-50 text-emerald-800 border-emerald-300 font-semibold',
            self::PLATFORM_ZOOM => 'bg-sky-50 text-sky-800 border-sky-300 font-semibold',
            self::PLATFORM_MICROSOFT_TEAMS => 'bg-indigo-50 text-indigo-800 border-indigo-300 font-semibold',
            default => 'bg-slate-100 text-slate-800 border-slate-300 font-semibold',
        };
    }

    public function getStatusBadgeClass(): string
    {
        return match ($this->status) {
            self::STATUS_IN_PROGRESS => 'bg-rose-50 text-rose-800 border-rose-300 font-bold',
            self::STATUS_SCHEDULED => 'bg-amber-50 text-amber-800 border-amber-300 font-semibold',
            self::STATUS_COMPLETED => 'bg-slate-100 text-slate-700 border-slate-300 font-semibold',
            self::STATUS_CANCELLED => 'bg-zinc-100 text-zinc-500 border-zinc-300 font-medium line-through',
            default => 'bg-slate-100 text-slate-700 border-slate-200 font-medium',
        };
    }

    public function getStartDateTime(): DateTimeImmutable
    {
        $time = substr($this->startTime, 0, 5);
        return new DateTimeImmutable("{$this->scheduledDate} {$time}:00");
    }

    public function getEndDateTime(): DateTimeImmutable
    {
        $start = $this->getStartDateTime();
        return $start->modify("+{$this->durationMinutes} minutes");
    }

    public function isJoinable(): bool
    {
        if ($this->isCancelled() || $this->isCompleted()) {
            return false;
        }

        if ($this->isInProgress()) {
            return true;
        }

        // Scheduled: allow joining 15 minutes before start time until 15 minutes after expected end
        $now = new DateTimeImmutable();
        $windowStart = $this->getStartDateTime()->modify('-15 minutes');
        $windowEnd = $this->getEndDateTime()->modify('+15 minutes');

        return $now >= $windowStart && $now <= $windowEnd;
    }

    public function formatSchedule(): string
    {
        $start = $this->getStartDateTime();
        $end = $this->getEndDateTime();

        return $start->format('D, d M Y') . ' • ' . $start->format('g:i A') . ' - ' . $end->format('g:i A');
    }
}
