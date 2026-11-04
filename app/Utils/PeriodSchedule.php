<?php

declare(strict_types=1);

namespace App\Utils;

/**
 * Institutional bell schedule and period mapping for Claret International School.
 *
 * Structured around standard 45-minute academic blocks with a 20-minute mid-morning recess
 * and a 45-minute midday lunch break (8 instructional periods total).
 */
class PeriodSchedule
{
    /**
     * Instructional periods indexed 1 through 8.
     *
     * @var array<int, array{period_number: int, number: int, name: string, label: string, short_name: string, start_time: string, end_time: string, start: string, end: string, display_time: string, is_break: bool, type: string, duration_minutes: int}>
     */
    public const INSTRUCTIONAL_PERIODS = [
        1 => [
            'period_number' => 1,
            'number' => 1,
            'name' => 'Period 1',
            'label' => 'Period 1',
            'short_name' => 'P1',
            'start_time' => '08:00',
            'end_time' => '08:45',
            'start' => '08:00:00',
            'end' => '08:45:00',
            'display_time' => '8:00 – 8:45 AM',
            'is_break' => false,
            'type' => 'instructional',
            'duration_minutes' => 45,
        ],
        2 => [
            'period_number' => 2,
            'number' => 2,
            'name' => 'Period 2',
            'label' => 'Period 2',
            'short_name' => 'P2',
            'start_time' => '08:45',
            'end_time' => '09:30',
            'start' => '08:45:00',
            'end' => '09:30:00',
            'display_time' => '8:45 – 9:30 AM',
            'is_break' => false,
            'type' => 'instructional',
            'duration_minutes' => 45,
        ],
        3 => [
            'period_number' => 3,
            'number' => 3,
            'name' => 'Period 3',
            'label' => 'Period 3',
            'short_name' => 'P3',
            'start_time' => '09:50',
            'end_time' => '10:35',
            'start' => '09:50:00',
            'end' => '10:35:00',
            'display_time' => '9:50 – 10:35 AM',
            'is_break' => false,
            'type' => 'instructional',
            'duration_minutes' => 45,
        ],
        4 => [
            'period_number' => 4,
            'number' => 4,
            'name' => 'Period 4',
            'label' => 'Period 4',
            'short_name' => 'P4',
            'start_time' => '10:35',
            'end_time' => '11:20',
            'start' => '10:35:00',
            'end' => '11:20:00',
            'display_time' => '10:35 – 11:20 AM',
            'is_break' => false,
            'type' => 'instructional',
            'duration_minutes' => 45,
        ],
        5 => [
            'period_number' => 5,
            'number' => 5,
            'name' => 'Period 5',
            'label' => 'Period 5',
            'short_name' => 'P5',
            'start_time' => '12:05',
            'end_time' => '12:50',
            'start' => '12:05:00',
            'end' => '12:50:00',
            'display_time' => '12:05 – 12:50 PM',
            'is_break' => false,
            'type' => 'instructional',
            'duration_minutes' => 45,
        ],
        6 => [
            'period_number' => 6,
            'number' => 6,
            'name' => 'Period 6',
            'label' => 'Period 6',
            'short_name' => 'P6',
            'start_time' => '12:50',
            'end_time' => '13:35',
            'start' => '12:50:00',
            'end' => '13:35:00',
            'display_time' => '12:50 – 1:35 PM',
            'is_break' => false,
            'type' => 'instructional',
            'duration_minutes' => 45,
        ],
        7 => [
            'period_number' => 7,
            'number' => 7,
            'name' => 'Period 7',
            'label' => 'Period 7',
            'short_name' => 'P7',
            'start_time' => '13:35',
            'end_time' => '14:20',
            'start' => '13:35:00',
            'end' => '14:20:00',
            'display_time' => '1:35 – 2:20 PM',
            'is_break' => false,
            'type' => 'instructional',
            'duration_minutes' => 45,
        ],
        8 => [
            'period_number' => 8,
            'number' => 8,
            'name' => 'Period 8',
            'label' => 'Period 8',
            'short_name' => 'P8',
            'start_time' => '14:20',
            'end_time' => '15:05',
            'start' => '14:20:00',
            'end' => '15:05:00',
            'display_time' => '2:20 – 3:05 PM',
            'is_break' => false,
            'type' => 'instructional',
            'duration_minutes' => 45,
        ],
    ];

    /**
     * All chronological schedule blocks including recess and lunch break.
     *
     * @var array<int, array{period_number: ?int, number: int, name: string, label: string, short_name: string, start_time: string, end_time: string, start: string, end: string, display_time: string, is_break: bool, type: string, duration_minutes: int}>
     */
    public const CHRONOLOGICAL_BLOCKS = [
        1 => [
            'period_number' => 1,
            'number' => 1,
            'name' => 'Period 1',
            'label' => 'Period 1',
            'short_name' => 'P1',
            'start_time' => '08:00',
            'end_time' => '08:45',
            'start' => '08:00:00',
            'end' => '08:45:00',
            'display_time' => '8:00 – 8:45 AM',
            'is_break' => false,
            'type' => 'instructional',
            'duration_minutes' => 45,
        ],
        2 => [
            'period_number' => 2,
            'number' => 2,
            'name' => 'Period 2',
            'label' => 'Period 2',
            'short_name' => 'P2',
            'start_time' => '08:45',
            'end_time' => '09:30',
            'start' => '08:45:00',
            'end' => '09:30:00',
            'display_time' => '8:45 – 9:30 AM',
            'is_break' => false,
            'type' => 'instructional',
            'duration_minutes' => 45,
        ],
        3 => [
            'period_number' => null,
            'number' => 3,
            'name' => 'Short Recess',
            'label' => 'Short Recess',
            'short_name' => 'Recess',
            'start_time' => '09:30',
            'end_time' => '09:50',
            'start' => '09:30:00',
            'end' => '09:50:00',
            'display_time' => '9:30 – 9:50 AM',
            'is_break' => true,
            'type' => 'break',
            'duration_minutes' => 20,
        ],
        4 => [
            'period_number' => 3,
            'number' => 4,
            'name' => 'Period 3',
            'label' => 'Period 3',
            'short_name' => 'P3',
            'start_time' => '09:50',
            'end_time' => '10:35',
            'start' => '09:50:00',
            'end' => '10:35:00',
            'display_time' => '9:50 – 10:35 AM',
            'is_break' => false,
            'type' => 'instructional',
            'duration_minutes' => 45,
        ],
        5 => [
            'period_number' => 4,
            'number' => 5,
            'name' => 'Period 4',
            'label' => 'Period 4',
            'short_name' => 'P4',
            'start_time' => '10:35',
            'end_time' => '11:20',
            'start' => '10:35:00',
            'end' => '11:20:00',
            'display_time' => '10:35 – 11:20 AM',
            'is_break' => false,
            'type' => 'instructional',
            'duration_minutes' => 45,
        ],
        6 => [
            'period_number' => null,
            'number' => 6,
            'name' => 'Lunch Break',
            'label' => 'Lunch Break',
            'short_name' => 'Lunch',
            'start_time' => '11:20',
            'end_time' => '12:05',
            'start' => '11:20:00',
            'end' => '12:05:00',
            'display_time' => '11:20 AM – 12:05 PM',
            'is_break' => true,
            'type' => 'break',
            'duration_minutes' => 45,
        ],
        7 => [
            'period_number' => 5,
            'number' => 7,
            'name' => 'Period 5',
            'label' => 'Period 5',
            'short_name' => 'P5',
            'start_time' => '12:05',
            'end_time' => '12:50',
            'start' => '12:05:00',
            'end' => '12:50:00',
            'display_time' => '12:05 – 12:50 PM',
            'is_break' => false,
            'type' => 'instructional',
            'duration_minutes' => 45,
        ],
        8 => [
            'period_number' => 6,
            'number' => 8,
            'name' => 'Period 6',
            'label' => 'Period 6',
            'short_name' => 'P6',
            'start_time' => '12:50',
            'end_time' => '13:35',
            'start' => '12:50:00',
            'end' => '13:35:00',
            'display_time' => '12:50 – 1:35 PM',
            'is_break' => false,
            'type' => 'instructional',
            'duration_minutes' => 45,
        ],
        9 => [
            'period_number' => 7,
            'number' => 9,
            'name' => 'Period 7',
            'label' => 'Period 7',
            'short_name' => 'P7',
            'start_time' => '13:35',
            'end_time' => '14:20',
            'start' => '13:35:00',
            'end' => '14:20:00',
            'display_time' => '1:35 – 2:20 PM',
            'is_break' => false,
            'type' => 'instructional',
            'duration_minutes' => 45,
        ],
        10 => [
            'period_number' => 8,
            'number' => 10,
            'name' => 'Period 8',
            'label' => 'Period 8',
            'short_name' => 'P8',
            'start_time' => '14:20',
            'end_time' => '15:05',
            'start' => '14:20:00',
            'end' => '15:05:00',
            'display_time' => '2:20 – 3:05 PM',
            'is_break' => false,
            'type' => 'instructional',
            'duration_minutes' => 45,
        ],
    ];

    private static ?\App\Repositories\TimetableStructureRepository $structureRepo = null;
    private static array $classStructureCache = [];

    public static function getStructureRepo(): \App\Repositories\TimetableStructureRepository
    {
        if (self::$structureRepo === null) {
            self::$structureRepo = new \App\Repositories\TimetableStructureRepository();
        }
        return self::$structureRepo;
    }

    public static function setStructureRepo(?\App\Repositories\TimetableStructureRepository $repo): void
    {
        self::$structureRepo = $repo;
        self::$classStructureCache = [];
    }

    public static function getStructureForClass(int $classId): ?\App\Models\TimetableStructure
    {
        if (!isset(self::$classStructureCache[$classId])) {
            try {
                self::$classStructureCache[$classId] = self::getStructureRepo()->findForClass($classId);
            } catch (\Throwable) {
                self::$classStructureCache[$classId] = null;
            }
        }
        return self::$classStructureCache[$classId];
    }

    public static function getStructureForStage(string $stageKey): ?\App\Models\TimetableStructure
    {
        try {
            return self::getStructureRepo()->findForStage($stageKey);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Get all defined period blocks in chronological order (including breaks).
     *
     * @return array<int, array{period_number: ?int, number: ?int, name: string, label: string, short_name: string, start_time: string, end_time: string, start: string, end: string, display_time: string, is_break: bool, type: string, duration_minutes: int}>
     */
    public static function getAllPeriods(?int $classId = null): array
    {
        if ($classId !== null && $classId > 0) {
            $struct = self::getStructureForClass($classId);
            if ($struct && !empty($struct->periods)) {
                $result = [];
                foreach ($struct->periods as $idx => $p) {
                    $result[$idx + 1] = $p->toArray();
                }
                return $result;
            }
        }

        return self::CHRONOLOGICAL_BLOCKS;
    }

    /**
     * Get only instructional teaching periods.
     *
     * @return array<int, array{period_number: int, number: int, name: string, label: string, short_name: string, start_time: string, end_time: string, start: string, end: string, display_time: string, is_break: bool, type: string, duration_minutes: int}>
     */
    public static function getInstructionalPeriods(?int $classId = null): array
    {
        if ($classId !== null && $classId > 0) {
            $struct = self::getStructureForClass($classId);
            if ($struct) {
                $inst = $struct->getInstructionalPeriods();
                if (!empty($inst)) {
                    $result = [];
                    foreach ($inst as $num => $p) {
                        $result[$num] = $p->toArray();
                    }
                    return $result;
                }
            }
        }

        return self::INSTRUCTIONAL_PERIODS;
    }

    /**
     * Find an instructional period definition by period number.
     *
     * @return array{period_number: int, number: int, name: string, label: string, short_name: string, start_time: string, end_time: string, start: string, end: string, display_time: string, is_break: bool, type: string, duration_minutes: int}|null
     */
    public static function getPeriodByNumber(int $periodNumber, ?int $classId = null): ?array
    {
        if ($classId !== null && $classId > 0) {
            $instructional = self::getInstructionalPeriods($classId);
            return $instructional[$periodNumber] ?? null;
        }

        return self::INSTRUCTIONAL_PERIODS[$periodNumber] ?? null;
    }

    /**
     * Find a period block matching or containing a specific time.
     *
     * @return array{period_number: ?int, number: int, name: string, label: string, short_name: string, start_time: string, end_time: string, start: string, end: string, display_time: string, is_break: bool, type: string, duration_minutes: int}|null
     */
    public static function getPeriodByTime(string $time, ?int $classId = null): ?array
    {
        $timeNorm = self::normalizeTime($time);
        if (!$timeNorm) {
            return null;
        }

        $allPeriods = self::getAllPeriods($classId);

        // 1. Exact start time match
        foreach ($allPeriods as $block) {
            if ($block['start'] === $timeNorm || substr($block['start'], 0, 5) === substr($timeNorm, 0, 5)) {
                return $block;
            }
        }

        // 2. Interval match: start <= time < end
        $tSec = strtotime($timeNorm);
        foreach ($allPeriods as $block) {
            $sSec = strtotime($block['start']);
            $eSec = strtotime($block['end']);
            if ($tSec !== false && $sSec !== false && $eSec !== false && $tSec >= $sSec && $tSec < $eSec) {
                return $block;
            }
        }

        return null;
    }

    /**
     * Attempt to match a time window against defined periods.
     *
     * @return array{period_number: ?int, number: int, name: string, label: string, short_name: string, start_time: string, end_time: string, start: string, end: string, display_time: string, is_break: bool, type: string, duration_minutes: int}|null
     */
    public static function matchPeriod(string $startTime, string $endTime, ?int $classId = null): ?array
    {
        $startNorm = self::normalizeTime($startTime);
        $endNorm = self::normalizeTime($endTime);

        if (!$startNorm || !$endNorm) {
            return null;
        }

        $allPeriods = self::getAllPeriods($classId);
        foreach ($allPeriods as $block) {
            if ($block['start'] === $startNorm && $block['end'] === $endNorm) {
                return $block;
            }
        }

        return null;
    }

    /**
     * Normalize time string to standard HH:MM:SS format (or null if invalid).
     */
    public static function normalizeTime(string $time): ?string
    {
        $time = trim($time);
        if (preg_match('/^(\d{1,2}):(\d{2})$/', $time, $matches)) {
            $h = (int)$matches[1];
            $m = (int)$matches[2];
            if ($h >= 0 && $h <= 23 && $m >= 0 && $m <= 59) {
                return sprintf('%02d:%02d:00', $h, $m);
            }
            return null;
        }
        if (preg_match('/^(\d{1,2}):(\d{2}):(\d{2})$/', $time, $matches)) {
            $h = (int)$matches[1];
            $m = (int)$matches[2];
            $s = (int)$matches[3];
            if ($h >= 0 && $h <= 23 && $m >= 0 && $m <= 59 && $s >= 0 && $s <= 59) {
                return sprintf('%02d:%02d:%02d', $h, $m, $s);
            }
            return null;
        }
        return null;
    }

    /**
     * Canonical week days for instructional schedules.
     *
     * @return array<string, string>
     */
    public static function getDays(): array
    {
        return [
            'mon' => 'Monday',
            'tue' => 'Tuesday',
            'wed' => 'Wednesday',
            'thu' => 'Thursday',
            'fri' => 'Friday',
        ];
    }

    /**
     * Normalize a day string (e.g. 'Monday', 'mon', 'MON', '1') into canonical enum key ('mon', 'tue', etc.).
     */
    public static function normalizeDay(string $day): ?string
    {
        $clean = strtolower(trim($day));

        return match ($clean) {
            'mon', 'monday', '1' => 'mon',
            'tue', 'tuesday', 'tues', '2' => 'tue',
            'wed', 'wednesday', '3' => 'wed',
            'thu', 'thursday', 'thur', 'thurs', '4' => 'thu',
            'fri', 'friday', '5' => 'fri',
            'sat', 'saturday', '6' => 'sat',
            'sun', 'sunday', '7', '0' => 'sun',
            default => null,
        };
    }
}
