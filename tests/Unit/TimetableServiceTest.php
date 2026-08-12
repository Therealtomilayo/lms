<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Exceptions\AuthorizationException;
use App\Core\Exceptions\ValidationException;
use App\Core\UserContext;
use App\Services\TimetableService;
use PDO;
use PHPUnit\Framework\TestCase;

class TimetableServiceTest extends TestCase
{
    private PDO $db;
    private TimetableService $service;

    protected function setUp(): void
    {
        $this->db = new PDO('sqlite::memory:');
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Comprehensive schema matching all repositories used
        $this->db->exec("
            CREATE TABLE users (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, email TEXT, status TEXT, phone TEXT, password_hash TEXT, must_change_password INTEGER DEFAULT 0, uuid TEXT DEFAULT '', created_at TEXT, updated_at TEXT);

            CREATE TABLE audit_logs (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                actor_user_id INTEGER,
                action TEXT,
                entity_type TEXT,
                entity_id INTEGER,
                before_json TEXT,
                after_json TEXT,
                metadata_json TEXT,
                created_at TEXT
            );

            CREATE TABLE sessions (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, start_date TEXT, end_date TEXT, status TEXT DEFAULT 'active', is_current INTEGER DEFAULT 1);
            CREATE TABLE terms (id INTEGER PRIMARY KEY AUTOINCREMENT, session_id INTEGER, name TEXT, start_date TEXT, end_date TEXT, status TEXT DEFAULT 'active', is_current INTEGER DEFAULT 1);
            CREATE TABLE academic_levels (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, stage TEXT DEFAULT 'secondary', rank_order INTEGER DEFAULT 1, grading_scale_id INTEGER DEFAULT 1);
            CREATE TABLE classes (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, section_arm TEXT, academic_level_id INTEGER DEFAULT 1, status TEXT DEFAULT 'active');
            CREATE TABLE subjects (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, code TEXT, description TEXT, status TEXT DEFAULT 'active');
            CREATE TABLE teachers (id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER, staff_id TEXT, staff_no TEXT);
            CREATE TABLE class_subjects (id INTEGER PRIMARY KEY AUTOINCREMENT, session_id INTEGER, class_id INTEGER, subject_id INTEGER, teacher_id INTEGER, status TEXT DEFAULT 'active');

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

        // Use null params so TimetableService wires its own repos using our PDO
        $this->service = new TimetableService(db: $this->db);
    }

    private function makeContext(int $userId, string $role, string $email = 'user@test.com'): UserContext
    {
        return new UserContext(
            id: $userId,
            uuid: 'uuid-' . $userId,
            name: 'Test User',
            email: $email,
            roles: [$role]
        );
    }

    private function seedBasicFixtures(): void
    {
        $this->db->exec("INSERT INTO users (id, name, email, status) VALUES (10, 'Teacher One', 'teacher1@test.com', 'active')");
        $this->db->exec("INSERT INTO teachers (id, user_id, staff_id) VALUES (1, 10, 'T001')");
        $this->db->exec("INSERT INTO academic_levels (id, name, stage, rank_order) VALUES (1, 'JSS', 'secondary', 1)");
        $this->db->exec("INSERT INTO sessions (id, name, is_current) VALUES (1, '2026/2027', 1)");
        $this->db->exec("INSERT INTO terms (id, session_id, name, is_current) VALUES (1, 1, 'First Term', 1)");
        $this->db->exec("INSERT INTO classes (id, name, academic_level_id) VALUES (1, 'JSS 1A', 1)");
        $this->db->exec("INSERT INTO subjects (id, name, code) VALUES (1, 'Math', 'MTH')");
        $this->db->exec("INSERT INTO class_subjects (id, session_id, class_id, subject_id, teacher_id) VALUES (1, 1, 1, 1, 1)");
    }

    // ─── Authorization ────────────────────────────────────────────────────

    public function testNonAdminCannotCreateSlot(): void
    {
        $this->seedBasicFixtures();
        $teacher = $this->makeContext(10, 'teacher');

        $this->expectException(AuthorizationException::class);
        $this->service->createSlot([
            'term_id' => 1,
            'class_subject_id' => 1,
            'day_of_week' => 'mon',
            'start_time' => '08:00',
            'end_time' => '09:00',
        ], $teacher);
    }

    public function testStudentCannotCreateSlot(): void
    {
        $this->seedBasicFixtures();
        $student = $this->makeContext(20, 'student');

        $this->expectException(AuthorizationException::class);
        $this->service->createSlot([
            'term_id' => 1,
            'class_subject_id' => 1,
            'day_of_week' => 'tue',
            'start_time' => '10:00',
            'end_time' => '11:00',
        ], $student);
    }

    // ─── Cross-session invariant ──────────────────────────────────────────

    public function testCrossSessionIntegrityRejected(): void
    {
        $this->db->exec("INSERT INTO users (id, name, email, status) VALUES (10, 'Teacher One', 'teacher1@test.com', 'active')");
        $this->db->exec("INSERT INTO teachers (id, user_id, staff_id) VALUES (1, 10, 'T001')");
        $this->db->exec("INSERT INTO academic_levels (id, name, stage, rank_order) VALUES (1, 'JSS', 'secondary', 1)");

        // Session 1 (Active) with Term 1
        $this->db->exec("INSERT INTO sessions (id, name, is_current) VALUES (1, '2026/2027', 1)");
        $this->db->exec("INSERT INTO terms (id, session_id, name, is_current) VALUES (1, 1, 'First Term', 1)");

        // Session 2 (Previous) with a class subject
        $this->db->exec("INSERT INTO sessions (id, name, is_current) VALUES (2, '2025/2026', 0)");
        $this->db->exec("INSERT INTO classes (id, name, academic_level_id) VALUES (1, 'JSS 1A', 1)");
        $this->db->exec("INSERT INTO subjects (id, name, code) VALUES (1, 'Math', 'MTH')");
        $this->db->exec("INSERT INTO class_subjects (id, session_id, class_id, subject_id, teacher_id) VALUES (1, 2, 1, 1, 1)");

        $admin = $this->makeContext(1, 'admin');

        $this->expectException(\App\Core\Exceptions\DomainRuleException::class);
        $this->service->createSlot([
            'term_id' => 1,         // Session 1
            'class_subject_id' => 1, // Session 2
            'day_of_week' => 'mon',
            'start_time' => '08:00',
            'end_time' => '09:00',
        ], $admin);
    }

    // ─── Successful create and audit ─────────────────────────────────────

    public function testCreateSlotPersistsAndAudits(): void
    {
        $this->seedBasicFixtures();
        $admin = $this->makeContext(1, 'admin');

        $slot = $this->service->createSlot([
            'term_id' => 1,
            'class_subject_id' => 1,
            'day_of_week' => 'mon',
            'start_time' => '08:00',
            'end_time' => '09:00',
            'room' => 'Hall 1',
        ], $admin);

        $this->assertNotNull($slot->id);
        $this->assertSame('08:00:00', $slot->startTime);
        $this->assertSame('09:00:00', $slot->endTime);
        $this->assertSame('mon', $slot->dayOfWeek);
        $this->assertSame('Hall 1', $slot->room);

        // Verify audit log
        $stmt = $this->db->query("SELECT action, entity_type FROM audit_logs WHERE entity_type = 'timetable_slots'");
        $log = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->assertNotEmpty($log);
        $this->assertSame('TIMETABLE_SLOT_CREATED', $log['action']);
    }

    // ─── Delete ───────────────────────────────────────────────────────────

    public function testDeleteSlotRemovesFromDatabase(): void
    {
        $this->seedBasicFixtures();
        $admin = $this->makeContext(1, 'admin');

        $slot = $this->service->createSlot([
            'term_id' => 1,
            'class_subject_id' => 1,
            'day_of_week' => 'wed',
            'start_time' => '10:00',
            'end_time' => '11:00',
        ], $admin);

        $deleted = $this->service->deleteSlot((int)$slot->id, $admin);
        $this->assertTrue($deleted);

        // Slot must no longer be findable
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM timetable_slots WHERE id = ?");
        $stmt->execute([(int)$slot->id]);
        $this->assertSame(0, (int)$stmt->fetchColumn());
    }

    // ─── Update ───────────────────────────────────────────────────────────

    public function testUpdateSlotChangesValues(): void
    {
        $this->seedBasicFixtures();
        $admin = $this->makeContext(1, 'admin');

        $slot = $this->service->createSlot([
            'term_id' => 1,
            'class_subject_id' => 1,
            'day_of_week' => 'fri',
            'start_time' => '09:00',
            'end_time' => '10:00',
            'room' => 'Room A',
        ], $admin);

        $updated = $this->service->updateSlot((int)$slot->id, [
            'term_id' => 1,
            'class_subject_id' => 1,
            'day_of_week' => 'fri',
            'start_time' => '09:30',
            'end_time' => '10:30',
            'room' => 'Room B',
        ], $admin);

        $this->assertSame('09:30:00', $updated->startTime);
        $this->assertSame('10:30:00', $updated->endTime);
        $this->assertSame('Room B', $updated->room);
    }

    // ─── Conflict rejection through service ──────────────────────────────

    public function testCreateSlotRejectsConflictingPeriod(): void
    {
        $this->seedBasicFixtures();
        $admin = $this->makeContext(1, 'admin');

        $this->service->createSlot([
            'term_id' => 1,
            'class_subject_id' => 1,
            'day_of_week' => 'tue',
            'start_time' => '08:00',
            'end_time' => '10:00',
            'room' => 'Room 1',
        ], $admin);

        $this->expectException(ValidationException::class);
        $this->service->createSlot([
            'term_id' => 1,
            'class_subject_id' => 1,
            'day_of_week' => 'tue',
            'start_time' => '09:00',
            'end_time' => '11:00',
            'room' => 'Room 2',
        ], $admin);
    }
}
