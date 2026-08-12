<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Domain Timetable Slot Entity
 * Represents a scheduled weekly instructional period.
 */
final class TimetableSlot
{
    public const DAY_MON = 'mon';
    public const DAY_TUE = 'tue';
    public const DAY_WED = 'wed';
    public const DAY_THU = 'thu';
    public const DAY_FRI = 'fri';
    public const DAY_SAT = 'sat';
    public const DAY_SUN = 'sun';

    public const DAYS = [
        self::DAY_MON => 'Monday',
        self::DAY_TUE => 'Tuesday',
        self::DAY_WED => 'Wednesday',
        self::DAY_THU => 'Thursday',
        self::DAY_FRI => 'Friday',
        self::DAY_SAT => 'Saturday',
        self::DAY_SUN => 'Sunday',
    ];

    public function __construct(
        public readonly int $id,
        public readonly int $termId,
        public readonly int $classSubjectId,
        public readonly string $dayOfWeek,
        public readonly string $startTime, // HH:MM:SS
        public readonly string $endTime,   // HH:MM:SS
        public readonly ?string $room = null,
        public readonly ?string $createdAt = null,
        public readonly ?string $updatedAt = null,
        public readonly ?ClassSubject $classSubject = null,
        public readonly ?Term $term = null
    ) {
    }

    public static function fromArray(
        array $data,
        ?ClassSubject $classSubject = null,
        ?Term $term = null
    ): self {
        $startTime = (string)($data['start_time'] ?? '08:00:00');
        if (strlen($startTime) === 5) {
            $startTime .= ':00';
        }

        $endTime = (string)($data['end_time'] ?? '09:00:00');
        if (strlen($endTime) === 5) {
            $endTime .= ':00';
        }

        $classSubject = $classSubject ?? (!empty($data['class_subject_id']) || !empty($data['subject_name']) || !empty($data['class_name']) ? ClassSubject::fromArray([
            'id' => (int)($data['class_subject_id'] ?? $data['id'] ?? 0),
            'session_id' => (int)($data['session_id'] ?? 0),
            'class_id' => (int)($data['class_id'] ?? 0),
            'subject_id' => (int)($data['subject_id'] ?? 0),
            'teacher_id' => (int)($data['teacher_id'] ?? 0),
            'status' => $data['class_subject_status'] ?? 'active',
            'subject_name' => $data['subject_name'] ?? '',
            'subject_code' => $data['subject_code'] ?? '',
            'class_name' => $data['class_name'] ?? '',
            'section_arm' => $data['section_arm'] ?? null,
            'teacher_name' => $data['teacher_name'] ?? null,
            'teacher_staff_id' => $data['teacher_staff_id'] ?? null,
        ]) : null);

        $term = $term ?? (!empty($data['term_name']) ? Term::fromArray([
            'id' => (int)$data['term_id'],
            'session_id' => (int)($data['term_session_id'] ?? ($data['session_id'] ?? 0)),
            'name' => (string)$data['term_name'],
            'start_date' => (string)($data['term_start_date'] ?? ''),
            'end_date' => (string)($data['term_end_date'] ?? ''),
            'status' => (string)($data['term_status'] ?? 'active'),
            'is_current' => (bool)($data['term_is_current'] ?? 0),
        ]) : null);

        return new self(
            id: (int)($data['id'] ?? 0),
            termId: (int)($data['term_id'] ?? 0),
            classSubjectId: (int)($data['class_subject_id'] ?? 0),
            dayOfWeek: strtolower((string)($data['day_of_week'] ?? self::DAY_MON)),
            startTime: $startTime,
            endTime: $endTime,
            room: isset($data['room']) && trim((string)$data['room']) !== '' ? trim((string)$data['room']) : null,
            createdAt: isset($data['created_at']) ? (string)$data['created_at'] : null,
            updatedAt: isset($data['updated_at']) ? (string)$data['updated_at'] : null,
            classSubject: $classSubject,
            term: $term
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'term_id' => $this->termId,
            'class_subject_id' => $this->classSubjectId,
            'day_of_week' => $this->dayOfWeek,
            'day_label' => $this->getDayLabel(),
            'start_time' => $this->startTime,
            'end_time' => $this->endTime,
            'time_range' => $this->getFormattedTimeRange(),
            'duration_minutes' => $this->getDurationMinutes(),
            'room' => $this->room,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
            'class_subject' => $this->classSubject?->toArray(),
        ];
    }

    public function getDayLabel(): string
    {
        return self::DAYS[$this->dayOfWeek] ?? ucfirst($this->dayOfWeek);
    }

    public function getFormattedTimeRange(): string
    {
        $start = date('g:i A', strtotime($this->startTime));
        $end = date('g:i A', strtotime($this->endTime));

        return "{$start} – {$end}";
    }

    public function getDurationMinutes(): int
    {
        $startSeconds = strtotime($this->startTime);
        $endSeconds = strtotime($this->endTime);

        if ($startSeconds === false || $endSeconds === false || $endSeconds <= $startSeconds) {
            return 0;
        }

        return (int)(($endSeconds - $startSeconds) / 60);
    }
}
