<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\TimetableSlot;
use PHPUnit\Framework\TestCase;

class TimetableSlotTest extends TestCase
{
    private function makeSlot(array $overrides = []): TimetableSlot
    {
        return TimetableSlot::fromArray(array_merge([
            'id'               => 1,
            'term_id'          => 10,
            'class_subject_id' => 20,
            'day_of_week'      => 'mon',
            'start_time'       => '08:00:00',
            'end_time'         => '09:30:00',
            'room'             => 'Hall A',
            'created_at'       => '2026-09-01 08:00:00',
            'updated_at'       => '2026-09-01 08:00:00',
        ], $overrides));
    }

    // ─── fromArray ────────────────────────────────────────────────────────

    public function testFromArraySetsAllFields(): void
    {
        $slot = $this->makeSlot();

        $this->assertSame(1, $slot->id);
        $this->assertSame(10, $slot->termId);
        $this->assertSame(20, $slot->classSubjectId);
        $this->assertSame('mon', $slot->dayOfWeek);
        $this->assertSame('08:00:00', $slot->startTime);
        $this->assertSame('09:30:00', $slot->endTime);
        $this->assertSame('Hall A', $slot->room);
    }

    public function testFromArrayNormalizesShortTimeFormat(): void
    {
        $slot = TimetableSlot::fromArray([
            'id'               => 2,
            'term_id'          => 1,
            'class_subject_id' => 1,
            'day_of_week'      => 'tue',
            'start_time'       => '09:00',   // HH:MM, no seconds
            'end_time'         => '10:30',
        ]);

        $this->assertSame('09:00:00', $slot->startTime);
        $this->assertSame('10:30:00', $slot->endTime);
    }

    public function testFromArrayNormalizeDayToLowercase(): void
    {
        $slot = TimetableSlot::fromArray([
            'id'               => 3,
            'term_id'          => 1,
            'class_subject_id' => 1,
            'day_of_week'      => 'WED',
            'start_time'       => '08:00:00',
            'end_time'         => '09:00:00',
        ]);

        $this->assertSame('wed', $slot->dayOfWeek);
    }

    public function testFromArrayNullRoomWhenEmpty(): void
    {
        $slot = TimetableSlot::fromArray([
            'id'               => 4,
            'term_id'          => 1,
            'class_subject_id' => 1,
            'day_of_week'      => 'mon',
            'start_time'       => '08:00:00',
            'end_time'         => '09:00:00',
            'room'             => '',
        ]);

        $this->assertNull($slot->room);
    }

    // ─── getDayLabel ─────────────────────────────────────────────────────

    public function testGetDayLabelReturnsFullDayName(): void
    {
        $days = [
            'mon' => 'Monday',
            'tue' => 'Tuesday',
            'wed' => 'Wednesday',
            'thu' => 'Thursday',
            'fri' => 'Friday',
            'sat' => 'Saturday',
            'sun' => 'Sunday',
        ];

        foreach ($days as $abbr => $label) {
            $slot = $this->makeSlot(['day_of_week' => $abbr]);
            $this->assertSame($label, $slot->getDayLabel(), "Expected '{$label}' for abbreviation '{$abbr}'");
        }
    }

    public function testGetDayLabelFallsBackToUcfirst(): void
    {
        $slot = $this->makeSlot(['day_of_week' => 'holiday']);
        $this->assertSame('Holiday', $slot->getDayLabel());
    }

    // ─── getFormattedTimeRange ────────────────────────────────────────────

    public function testGetFormattedTimeRangeProducesReadableOutput(): void
    {
        $slot = $this->makeSlot(['start_time' => '08:00:00', 'end_time' => '09:30:00']);
        $range = $slot->getFormattedTimeRange();

        $this->assertStringContainsString('8:00 AM', $range);
        $this->assertStringContainsString('9:30 AM', $range);
        $this->assertStringContainsString('–', $range);
    }

    public function testAfternoonTimesFormatCorrectly(): void
    {
        $slot = $this->makeSlot(['start_time' => '13:00:00', 'end_time' => '14:30:00']);
        $range = $slot->getFormattedTimeRange();

        $this->assertStringContainsString('PM', $range);
    }

    // ─── getDurationMinutes ───────────────────────────────────────────────

    public function testDurationMinutesFor90MinuteClass(): void
    {
        $slot = $this->makeSlot(['start_time' => '08:00:00', 'end_time' => '09:30:00']);
        $this->assertSame(90, $slot->getDurationMinutes());
    }

    public function testDurationMinutesFor60MinuteClass(): void
    {
        $slot = $this->makeSlot(['start_time' => '10:00:00', 'end_time' => '11:00:00']);
        $this->assertSame(60, $slot->getDurationMinutes());
    }

    public function testDurationMinutesReturnZeroWhenEndBeforeStart(): void
    {
        $slot = $this->makeSlot(['start_time' => '10:00:00', 'end_time' => '09:00:00']);
        $this->assertSame(0, $slot->getDurationMinutes());
    }

    // ─── toArray ─────────────────────────────────────────────────────────

    public function testToArrayContainsAllExpectedKeys(): void
    {
        $slot = $this->makeSlot();
        $arr = $slot->toArray();

        $this->assertArrayHasKey('id', $arr);
        $this->assertArrayHasKey('term_id', $arr);
        $this->assertArrayHasKey('class_subject_id', $arr);
        $this->assertArrayHasKey('day_of_week', $arr);
        $this->assertArrayHasKey('day_label', $arr);
        $this->assertArrayHasKey('start_time', $arr);
        $this->assertArrayHasKey('end_time', $arr);
        $this->assertArrayHasKey('time_range', $arr);
        $this->assertArrayHasKey('duration_minutes', $arr);
        $this->assertArrayHasKey('room', $arr);
    }

    public function testToArrayRoundtripsValues(): void
    {
        $slot = $this->makeSlot();
        $arr = $slot->toArray();

        $this->assertSame(1, $arr['id']);
        $this->assertSame(10, $arr['term_id']);
        $this->assertSame('mon', $arr['day_of_week']);
        $this->assertSame('Monday', $arr['day_label']);
        $this->assertSame(90, $arr['duration_minutes']);
        $this->assertSame('Hall A', $arr['room']);
    }

    // ─── Constants ───────────────────────────────────────────────────────

    public function testDayConstantsDefinedCorrectly(): void
    {
        $this->assertSame('mon', TimetableSlot::DAY_MON);
        $this->assertSame('tue', TimetableSlot::DAY_TUE);
        $this->assertSame('wed', TimetableSlot::DAY_WED);
        $this->assertSame('thu', TimetableSlot::DAY_THU);
        $this->assertSame('fri', TimetableSlot::DAY_FRI);
        $this->assertSame('sat', TimetableSlot::DAY_SAT);
        $this->assertSame('sun', TimetableSlot::DAY_SUN);
    }

    public function testDaysArrayContainsAll7Days(): void
    {
        $this->assertCount(7, TimetableSlot::DAYS);
    }
}
