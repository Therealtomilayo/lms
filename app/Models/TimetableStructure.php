<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Domain Timetable Structure Entity
 * Represents a complete bell schedule configuration (e.g. High School or Grade School).
 */
final class TimetableStructure
{
    /**
     * @param array<int, TimetableStructurePeriod> $periods
     * @param array<int, string> $days
     */
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $slug,
        public readonly string $stageKey,
        public readonly bool $isDefault = false,
        public readonly bool $isActive = true,
        public readonly array $days = ['mon', 'tue', 'wed', 'thu', 'fri'],
        public readonly ?string $description = null,
        public readonly ?string $createdAt = null,
        public readonly ?string $updatedAt = null,
        public readonly array $periods = []
    ) {
    }

    /**
     * @param array<string, mixed> $data
     * @param array<int, TimetableStructurePeriod> $periods
     */
    public static function fromArray(array $data, array $periods = []): self
    {
        $days = ['mon', 'tue', 'wed', 'thu', 'fri'];
        if (!empty($data['days_json'])) {
            $decoded = is_string($data['days_json']) ? json_decode($data['days_json'], true) : $data['days_json'];
            if (is_array($decoded)) {
                $days = $decoded;
            }
        } elseif (isset($data['days']) && is_array($data['days'])) {
            $days = $data['days'];
        }

        return new self(
            id: (int)($data['id'] ?? 0),
            name: (string)($data['name'] ?? 'Bell Schedule'),
            slug: (string)($data['slug'] ?? 'default'),
            stageKey: (string)($data['stage_key'] ?? 'all'),
            isDefault: (bool)($data['is_default'] ?? false),
            isActive: (bool)($data['is_active'] ?? true),
            days: $days,
            description: isset($data['description']) ? (string)$data['description'] : null,
            createdAt: isset($data['created_at']) ? (string)$data['created_at'] : null,
            updatedAt: isset($data['updated_at']) ? (string)$data['updated_at'] : null,
            periods: $periods
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'stage_key' => $this->stageKey,
            'is_default' => $this->isDefault,
            'is_active' => $this->isActive,
            'days' => $this->days,
            'description' => $this->description,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
            'periods' => array_map(fn(TimetableStructurePeriod $p) => $p->toArray(), $this->periods),
        ];
    }

    /**
     * @return array<int, TimetableStructurePeriod>
     */
    public function getInstructionalPeriods(): array
    {
        $instructional = [];
        foreach ($this->periods as $period) {
            if ($period->isInstructional() && $period->periodNumber !== null) {
                $instructional[$period->periodNumber] = $period;
            }
        }
        return $instructional;
    }

    /**
     * @return array<int, TimetableStructurePeriod>
     */
    public function getAllPeriods(): array
    {
        return $this->periods;
    }

    public function getMaxPeriodNumber(): int
    {
        $max = 0;
        foreach ($this->periods as $period) {
            if ($period->periodNumber !== null && $period->periodNumber > $max) {
                $max = $period->periodNumber;
            }
        }
        return $max > 0 ? $max : 8;
    }

    public function getPeriodByNumber(int $periodNumber): ?TimetableStructurePeriod
    {
        foreach ($this->periods as $period) {
            if ($period->periodNumber === $periodNumber) {
                return $period;
            }
        }
        return null;
    }

    public function matchesStage(string $stage): bool
    {
        $clean = strtolower(trim($stage));
        if ($this->stageKey === 'all') {
            return true;
        }

        $targets = array_map('trim', explode(',', strtolower($this->stageKey)));
        return in_array($clean, $targets, true);
    }
}
