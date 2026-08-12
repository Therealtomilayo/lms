<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Exceptions\ValidationException;
use App\Models\TimetableSlot;
use App\Repositories\TimetableRepository;
use PDO;
use PHPUnit\Framework\TestCase;

class TimetableConflictTest extends TestCase
{
    private PDO $db;
    private TimetableRepository $repo;

    protected function setUp(): void
    {
        $this->db = new PDO('sqlite::memory:');
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $this->db->exec("
            CREATE TABLE classes (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, section_arm TEXT, academic_level_id INTEGER, status TEXT DEFAULT 'active');
            CREATE TABLE subjects (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, code TEXT, description TEXT, status TEXT DEFAULT 'active');
            CREATE TABLE teachers (id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER, staff_id TEXT);
            CREATE TABLE users (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, email TEXT, status TEXT, password_hash TEXT, must_change_password INTEGER DEFAULT 0, created_at TEXT, updated_at TEXT);
            CREATE TABLE class_subjects (id INTEGER PRIMARY KEY AUTOINCREMENT, session_id INTEGER, class_id INTEGER, subject_id INTEGER, teacher_id INTEGER, status TEXT DEFAULT 'active');
            CREATE TABLE terms (id INTEGER PRIMARY KEY AUTOINCREMENT, session_id INTEGER, name TEXT, start_date TEXT, end_date TEXT, status TEXT DEFAULT 'active', is_current INTEGER DEFAULT 1);

            CREATE TABLE timetable_slots (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                term_id INTEGER NOT NULL,
                class_subject_id INTEGER NOT NULL,
                day_of_week TEXT NOT NULL,
                start_time TEXT NOT NULL,
                end_time TEXT NOT NULL,
                room TEXT NULL,
                created_at TEXT NULL,
                updated_at TEXT NULL
            );
        ");

        $this->repo = new TimetableRepository($this->db);
    }

    // ─── Time normalization ──────────────────────────────────────────────

    public function testNormalizeTimeHhmm(): void
    {
        $this->assertSame('08:00:00', TimetableRepository::normalizeTime('08:00'));
        $this->assertSame('08:30:00', TimetableRepository::normalizeTime('8:30'));
        $this->assertSame('14:45:00', TimetableRepository::normalizeTime('14:45'));
    }

    public function testNormalizeTimeHhmmss(): void
    {
        $this->assertSame('14:45:00', TimetableRepository::normalizeTime('14:45:00'));
    }

    public function testInvalidHourThrowsValidationException(): void
    {
        $this->expectException(ValidationException::class);
        TimetableRepository::normalizeTime('25:00');
    }

    // ─── Half-open interval boundary tests ──────────────────────────────

    public function testTouchingBoundariesAreAllowed(): void
    {
        $this->db->exec("INSERT INTO classes (name) VALUES ('Class A')");
        $this->db->exec("INSERT INTO subjects (name, code) VALUES ('Math', 'MTH101')");
        $this->db->exec("INSERT INTO users (name, email, status) VALUES ('Teacher A', 't@test.com', 'active')");
        $this->db->exec("INSERT INTO teachers (user_id, staff_id) VALUES (1, 'T001')");
        $this->db->exec("INSERT INTO class_subjects (session_id, class_id, subject_id, teacher_id) VALUES (1, 1, 1, 1)");
        $this->db->exec("INSERT INTO terms (session_id, name) VALUES (1, 'Term 1')");

        // Insert slot from 09:00 to 10:00 using array
        $this->repo->create([
            'term_id' => 1,
            'class_subject_id' => 1,
            'day_of_week' => 'mon',
            'start_time' => '09:00:00',
            'end_time' => '10:00:00',
            'room' => 'Lab 1',
        ]);

        // 10:00–11:00 touches boundary exactly — MUST be allowed (half-open interval)
        $conflicts = $this->repo->detectConflicts(1, 1, 'mon', '10:00:00', '11:00:00', 'Lab 1');
        $this->assertEmpty($conflicts, 'Adjacent touching slots must NOT be flagged as a conflict');

        // 08:00–09:00 touches lower boundary — MUST be allowed
        $conflictsBefore = $this->repo->detectConflicts(1, 1, 'mon', '08:00:00', '09:00:00', 'Lab 1');
        $this->assertEmpty($conflictsBefore, 'Adjacent touching slots from below must NOT be flagged');
    }

    public function testClassConflictDetectedOnOverlap(): void
    {
        $this->db->exec("INSERT INTO classes (name) VALUES ('Class A')");
        $this->db->exec("INSERT INTO subjects (name, code) VALUES ('Math', 'MTH101'), ('English', 'ENG101')");
        $this->db->exec("INSERT INTO users (name, email, status) VALUES ('Teacher A', 'ta@test.com', 'active'), ('Teacher B', 'tb@test.com', 'active')");
        $this->db->exec("INSERT INTO teachers (user_id, staff_id) VALUES (1, 'T001'), (2, 'T002')");
        $this->db->exec("INSERT INTO class_subjects (session_id, class_id, subject_id, teacher_id) VALUES (1, 1, 1, 1), (1, 1, 2, 2)");
        $this->db->exec("INSERT INTO terms (session_id, name) VALUES (1, 'Term 1')");

        // Slot 09:00–10:30 for Class 1, Subject 1
        $this->repo->create([
            'term_id' => 1,
            'class_subject_id' => 1,
            'day_of_week' => 'mon',
            'start_time' => '09:00:00',
            'end_time' => '10:30:00',
            'room' => 'Room 101',
        ]);

        // Attempt 10:00–11:00 for Class 1, Subject 2 — overlaps, should conflict
        $conflicts = $this->repo->detectConflicts(1, 2, 'mon', '10:00:00', '11:00:00', 'Room 102');
        $this->assertNotEmpty($conflicts, 'Overlapping class period must report a conflict');
        $this->assertStringContainsString('Class conflict', $conflicts[0]['message']);
    }

    public function testTeacherDoubleBookingDetected(): void
    {
        $this->db->exec("INSERT INTO classes (name) VALUES ('Class A'), ('Class B')");
        $this->db->exec("INSERT INTO subjects (name, code) VALUES ('Physics', 'PHY101')");
        $this->db->exec("INSERT INTO users (name, email, status) VALUES ('Teacher A', 'ta@test.com', 'active')");
        $this->db->exec("INSERT INTO teachers (user_id, staff_id) VALUES (1, 'T001')");
        // Same teacher teaches Physics in both Class 1 and Class 2
        $this->db->exec("INSERT INTO class_subjects (session_id, class_id, subject_id, teacher_id) VALUES (1, 1, 1, 1), (1, 2, 1, 1)");
        $this->db->exec("INSERT INTO terms (session_id, name) VALUES (1, 'Term 1')");

        // Class 1 slot on Tue 08:00–09:30
        $this->repo->create([
            'term_id' => 1,
            'class_subject_id' => 1,
            'day_of_week' => 'tue',
            'start_time' => '08:00:00',
            'end_time' => '09:30:00',
            'room' => 'Physics Lab',
        ]);

        // Class 2 attempt 09:00–10:00 with same teacher — collision
        $conflicts = $this->repo->detectConflicts(1, 2, 'tue', '09:00:00', '10:00:00', 'Hall B');
        $this->assertNotEmpty($conflicts, 'Teacher double-booking must be reported');
        $this->assertStringContainsString('Teacher conflict', $conflicts[0]['message']);
    }

    public function testRoomCollisionDetected(): void
    {
        $this->db->exec("INSERT INTO classes (name) VALUES ('Class A'), ('Class B')");
        $this->db->exec("INSERT INTO subjects (name, code) VALUES ('Chemistry', 'CHM101'), ('Biology', 'BIO101')");
        $this->db->exec("INSERT INTO users (name, email, status) VALUES ('Teacher A', 'ta@test.com', 'active'), ('Teacher B', 'tb@test.com', 'active')");
        $this->db->exec("INSERT INTO teachers (user_id, staff_id) VALUES (1, 'T001'), (2, 'T002')");
        $this->db->exec("INSERT INTO class_subjects (session_id, class_id, subject_id, teacher_id) VALUES (1, 1, 1, 1), (1, 2, 2, 2)");
        $this->db->exec("INSERT INTO terms (session_id, name) VALUES (1, 'Term 1')");

        // Class 1 in "Science Lab 1" 11:00–12:00
        $this->repo->create([
            'term_id' => 1,
            'class_subject_id' => 1,
            'day_of_week' => 'wed',
            'start_time' => '11:00:00',
            'end_time' => '12:00:00',
            'room' => 'Science Lab 1',
        ]);

        // Class 2 attempts same room 11:30–12:30 (case-insensitive match)
        $conflicts = $this->repo->detectConflicts(1, 2, 'wed', '11:30:00', '12:30:00', 'science lab 1');
        $this->assertNotEmpty($conflicts, 'Room collision must be detected');
        $this->assertStringContainsString('Room conflict', $conflicts[0]['message']);
    }

    public function testSameSlotUpdateExcludedFromConflictCheck(): void
    {
        $this->db->exec("INSERT INTO classes (name) VALUES ('Class A')");
        $this->db->exec("INSERT INTO subjects (name, code) VALUES ('Math', 'MTH101')");
        $this->db->exec("INSERT INTO users (name, email, status) VALUES ('Teacher A', 'ta@test.com', 'active')");
        $this->db->exec("INSERT INTO teachers (user_id, staff_id) VALUES (1, 'T001')");
        $this->db->exec("INSERT INTO class_subjects (session_id, class_id, subject_id, teacher_id) VALUES (1, 1, 1, 1)");
        $this->db->exec("INSERT INTO terms (session_id, name) VALUES (1, 'Term 1')");

        // Create a slot
        $savedSlot = $this->repo->create([
            'term_id' => 1,
            'class_subject_id' => 1,
            'day_of_week' => 'thu',
            'start_time' => '13:00:00',
            'end_time' => '14:00:00',
            'room' => 'Room 201',
        ]);

        // Updating same slot with a slight time change — excluding its own ID must show NO conflict
        $conflicts = $this->repo->detectConflicts(1, 1, 'thu', '13:00:00', '14:30:00', 'Room 201', (int)$savedSlot->id);
        $this->assertEmpty($conflicts, 'A slot updating itself must not self-conflict');
    }

    public function testDifferentDayNoConflict(): void
    {
        $this->db->exec("INSERT INTO classes (name) VALUES ('Class A')");
        $this->db->exec("INSERT INTO subjects (name, code) VALUES ('Math', 'MTH101')");
        $this->db->exec("INSERT INTO users (name, email, status) VALUES ('Teacher A', 'ta@test.com', 'active')");
        $this->db->exec("INSERT INTO teachers (user_id, staff_id) VALUES (1, 'T001')");
        $this->db->exec("INSERT INTO class_subjects (session_id, class_id, subject_id, teacher_id) VALUES (1, 1, 1, 1)");
        $this->db->exec("INSERT INTO terms (session_id, name) VALUES (1, 'Term 1')");

        // Slot on Monday
        $this->repo->create([
            'term_id' => 1,
            'class_subject_id' => 1,
            'day_of_week' => 'mon',
            'start_time' => '08:00:00',
            'end_time' => '09:00:00',
            'room' => 'Room 1',
        ]);

        // Same time/room/subject on Tuesday — different day, should NOT conflict
        $conflicts = $this->repo->detectConflicts(1, 1, 'tue', '08:00:00', '09:00:00', 'Room 1');
        $this->assertEmpty($conflicts, 'Slots on different days must not conflict');
    }
}
