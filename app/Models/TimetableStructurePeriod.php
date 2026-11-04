<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Domain Timetable Structure Period Entity
 * Represents a single bell schedule block or period within a timetable structure.
 */
final class TimetableStructurePeriod
{
    public const TYPE_INSTRUCTIONAL = 'instructional';
    public const TYPE_BREAK = 'break';
    public const TYPE_ASSEMBLY = 'assembly';
    public const TYPE_ACTIVITY = 'activity';
    public const TYPE_SPORT = 'sport';

    public function __construct(
        public readonly int $id,
        public readonly int $structureId,
        public readonly ?int $periodNumber,
        public readonly string $label,
        public readonly string $shortLabel,
        public readonly string $type,
        public readonly string $startTime, // HH:MM:SS
        public readonly string $endTime,   // HH:MM:SS
        public readonly int $sortOrder = 1,
        public readonly bool $spansAllClasses = false,
        public readonly ?array $dayRules = null,
        public readonly ?string $createdAt = null,
        public readonly ?string $updatedAt = null
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $startTime = (string)($data['start_time'] ?? '08:00:00');
        if (strlen($startTime) === 5) {
            $startTime .= ':00';
        }

        $endTime = (string)($data['end_time'] ?? '08:45:00');
        if (strlen($endTime) === 5) {
            $endTime .= ':00';
        }

        $dayRules = null;
        if (!empty($data['day_rules_json'])) {
            $decoded = is_string($data['day_rules_json']) ? json_decode($data['day_rules_json'], true) : $data['day_rules_json'];
            if (is_array($decoded)) {
                $dayRules = $decoded;
            }
        } elseif (isset($data['day_rules']) && is_array($data['day_rules'])) {
            $dayRules = $data['day_rules'];
        }

        return new self(
            id: (int)($data['id'] ?? 0),
            structureId: (int)($data['structure_id'] ?? 0),
            periodNumber: isset($data['period_number']) && $data['period_number'] !== null && $data['period_number'] !== '' ? (int)$data['period_number'] : null,
            label: (string)($data['label'] ?? 'Period'),
            shortLabel: (string)($data['short_label'] ?? ($data['period_number'] ? 'P' . $data['period_number'] : 'Period')),
            type: (string)($data['type'] ?? self::TYPE_INSTRUCTIONAL),
            startTime: $startTime,
            endTime: $endTime,
            sortOrder: (int)($data['sort_order'] ?? 1),
            spansAllClasses: (bool)($data['spans_all_classes'] ?? false),
            dayRules: $dayRules,
            createdAt: isset($data['created_at']) ? (string)$data['created_at'] : null,
            updatedAt: isset($data['updated_at']) ? (string)$data['updated_at'] : null
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'structure_id' => $this->structureId,
            'period_number' => $this->periodNumber,
            'number' => $this->periodNumber,
            'label' => $this->label,
            'short_name' => $this->shortLabel,
            'short_label' => $this->shortLabel,
            'name' => $this->label,
            'type' => $this->type,
            'is_break' => $this->isBreak(),
            'start_time' => substr($this->startTime, 0, 5),
            'end_time' => substr($this->endTime, 0, 5),
            'start' => $this->startTime,
            'end' => $this->endTime,
            'display_time' => $this->getFormattedTimeRange(),
            'duration_minutes' => $this->getDurationMinutes(),
            'sort_order' => $this->sortOrder,
            'spans_all_classes' => $this->spansAllClasses,
            'day_rules' => $this->dayRules,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }

    public function __get(string $name): mixed
    {
        return match ($name) {
            'periodName', 'name' => $this->label,
            'isBreak' => $this->isBreak(),
            'shortName' => $this->shortLabel,
            default => null,
        };
    }

    public function isBreak(): bool
    {
        return $this->type === self::TYPE_BREAK;
    }

    public function isInstructional(): bool
    {
        return $this->type === self::TYPE_INSTRUCTIONAL;
    }

    public function getFormattedTimeRange(): string
    {
        $start = date('g:i A', strtotime($this->startTime));
        $end = date('g:i A', strtotime($this->endTime));

        return "{$start} – {$end}";
    }

    public function getDurationMinutes(): int
    {
        $startSec = strtotime($this->startTime);
        $endSec = strtotime($this->endTime);

        if ($startSec === false || $endSec === false || $endSec <= $startSec) {
            return 0;
        }

        return (int)(($endSec - $startSec) / 60);
    }
}
