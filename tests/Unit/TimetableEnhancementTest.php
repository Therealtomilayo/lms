<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Exceptions\DomainRuleException;
use App\Core\Exceptions\ValidationException;
use App\Core\UserContext;
use App\Services\TimetableService;
use App\Utils\PeriodSchedule;
use PDO;
use PHPUnit\Framework\TestCase;

class TimetableEnhancementTest extends TestCase
{
    private PDO $db;
    private TimetableService $service;

    protected function setUp(): void
    {
        $this->db = new PDO('sqlite::memory:');
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

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

        $this->service = new TimetableService(db: $this->db);
    }

    private function makeAdminContext(): UserContext
    {
        return new UserContext(
            id: 1,
            uuid: 'admin-uuid',
            name: 'System Admin',
            email: 'admin@claret.edu',
            roles: ['admin']
        );
    }

    private function seedFixtures(): void
    {
        $this->db->exec("INSERT INTO users (id, name, email, status) VALUES (1, 'Admin', 'admin@claret.edu', 'active')");
        $this->db->exec("INSERT INTO users (id, name, email, status) VALUES (10, 'Teacher One', 't1@test.com', 'active')");
        $this->db->exec("INSERT INTO users (id, name, email, status) VALUES (20, 'Teacher Two', 't2@test.com', 'active')");

        $this->db->exec("INSERT INTO teachers (id, user_id, staff_id) VALUES (1, 10, 'T001')");
        $this->db->exec("INSERT INTO teachers (id, user_id, staff_id) VALUES (2, 20, 'T002')");

        $this->db->exec("INSERT INTO academic_levels (id, name, stage, rank_order) VALUES (1, 'JSS', 'secondary', 1)");
        $this->db->exec("INSERT INTO sessions (id, name, is_current) VALUES (1, '2026/2027', 1)");
        $this->db->exec("INSERT INTO sessions (id, name, is_current) VALUES (2, '2027/2028', 0)");

        $this->db->exec("INSERT INTO terms (id, session_id, name, is_current) VALUES (1, 1, 'First Term', 1)");
        $this->db->exec("INSERT INTO terms (id, session_id, name, is_current) VALUES (2, 1, 'Second Term', 0)");
        $this->db->exec("INSERT INTO terms (id, session_id, name, is_current) VALUES (3, 2, 'Next Session Term 1', 0)");

        $this->db->exec("INSERT INTO classes (id, name, academic_level_id) VALUES (1, 'JSS 1A', 1)");
        $this->db->exec("INSERT INTO classes (id, name, academic_level_id) VALUES (2, 'JSS 1B', 1)");

        $this->db->exec("INSERT INTO subjects (id, name, code) VALUES (1, 'Mathematics', 'MTH')");
        $this->db->exec("INSERT INTO subjects (id, name, code) VALUES (2, 'English Language', 'ENG')");

        $this->db->exec("INSERT INTO class_subjects (id, session_id, class_id, subject_id, teacher_id) VALUES (1, 1, 1, 1, 1)");
        $this->db->exec("INSERT INTO class_subjects (id, session_id, class_id, subject_id, teacher_id) VALUES (2, 1, 1, 2, 2)");
        $this->db->exec("INSERT INTO class_subjects (id, session_id, class_id, subject_id, teacher_id) VALUES (3, 1, 2, 1, 1)");
    }

    // ─── PeriodSchedule Unit Tests ──────────────────────────────────────────

    public function testPeriodScheduleDefinitions(): void
    {
        $all = PeriodSchedule::getAllPeriods();
        $this->assertCount(10, $all);

        $instructional = PeriodSchedule::getInstructionalPeriods();
        $this->assertCount(8, $instructional);

        $p1 = PeriodSchedule::getPeriodByNumber(1);
        $this->assertNotNull($p1);
        $this->assertSame('08:00', $p1['start_time']);
        $this->assertSame('08:45', $p1['end_time']);

        $p8 = PeriodSchedule::getPeriodByNumber(8);
        $this->assertNotNull($p8);
        $this->assertSame('14:20', $p8['start_time']);
        $this->assertSame('15:05', $p8['end_time']);
    }

    public function testPeriodScheduleDayNormalization(): void
    {
        $this->assertSame('mon', PeriodSchedule::normalizeDay('Monday'));
        $this->assertSame('mon', PeriodSchedule::normalizeDay('mon'));
        $this->assertSame('mon', PeriodSchedule::normalizeDay('MON'));
        $this->assertSame('fri', PeriodSchedule::normalizeDay('Friday'));
        $this->assertNull(PeriodSchedule::normalizeDay('Funday'));
    }

    public function testPeriodScheduleTimeNormalization(): void
    {
        $this->assertSame('08:00:00', PeriodSchedule::normalizeTime('8:00'));
        $this->assertSame('08:45:00', PeriodSchedule::normalizeTime('08:45:00'));
        $this->assertSame('14:20:00', PeriodSchedule::normalizeTime('14:20'));
        $this->assertNull(PeriodSchedule::normalizeTime('25:99'));
    }

    public function testPeriodScheduleMatching(): void
    {
        $match = PeriodSchedule::matchPeriod('08:00:00', '08:45:00');
        $this->assertNotNull($match);
        $this->assertSame(1, $match['period_number']);

        $noMatch = PeriodSchedule::matchPeriod('16:00:00', '17:00:00');
        $this->assertNull($noMatch);
    }

    // ─── CSV Template Generation ────────────────────────────────────────────

    public function testGenerateSampleCsv(): void
    {
        $this->seedFixtures();
        $csv = $this->service->generateSampleCsv(classId: 1, sessionId: 1);

        $this->assertStringContainsString('class_name,day_of_week,period_number,subject_code,department,room,slot_type,custom_title', $csv);
        $this->assertStringContainsString('JSS 1A', $csv);
        $this->assertStringContainsString('Monday', $csv);
        $this->assertStringContainsString('ENG', $csv);
    }

    // ─── CSV Bulk Import Tests ──────────────────────────────────────────────

    public function testImportFromCsvSuccess(): void
    {
        $this->seedFixtures();
        $admin = $this->makeAdminContext();

        $csv = "class_name,day_of_week,period_number,subject_code,room\n";
        $csv .= "JSS 1A,Monday,1,MTH,Room 101\n";
        $csv .= "JSS 1A,Monday,2,ENG,Room 101\n";
        $csv .= "JSS 1A,Tuesday,1,ENG,Room 102\n";

        $result = $this->service->importFromCsv($csv, termId: 1, actor: $admin);

        $this->assertSame(3, $result['imported_count']);
        $this->assertSame(1, $result['classes_count']);

        // Verify DB records
        $slots = $this->service->getClassTimetable(1, 1, $admin)['slots'];
        $this->assertCount(3, $slots);
    }

    public function testImportFromCsvRejectsMissingHeaders(): void
    {
        $this->seedFixtures();
        $admin = $this->makeAdminContext();

        $csv = "invalid,header,only\n1,2,3";

        $this->expectException(ValidationException::class);
        $this->service->importFromCsv($csv, termId: 1, actor: $admin);
    }

    public function testImportFromCsvRejectsUnknownClass(): void
    {
        $this->seedFixtures();
        $admin = $this->makeAdminContext();

        $csv = "class_name,day_of_week,period_number,subject_code,room\n";
        $csv .= "NonExistentClass,Monday,1,MTH,Room 101\n";

        $this->expectException(ValidationException::class);
        $this->service->importFromCsv($csv, termId: 1, actor: $admin);
    }

    public function testImportFromCsvRejectsInternalDuplicateSlots(): void
    {
        $this->seedFixtures();
        $admin = $this->makeAdminContext();

        // Two rows scheduling same class at same period
        $csv = "class_name,day_of_week,period_number,subject_code,room\n";
        $csv .= "JSS 1A,Monday,1,MTH,Room 101\n";
        $csv .= "JSS 1A,Monday,1,ENG,Room 102\n";

        $this->expectException(ValidationException::class);
        $this->service->importFromCsv($csv, termId: 1, actor: $admin);
    }

    public function testImportFromCsvWithDefaultClassFallback(): void
    {
        $this->seedFixtures();
        $admin = $this->makeAdminContext();

        // CSV without class_name column
        $csv = "day_of_week,period_number,subject_code,room\n";
        $csv .= "Monday,1,MTH,Room 101\n";
        $csv .= "Tuesday,2,ENG,Room 102\n";

        $result = $this->service->importFromCsv($csv, termId: 1, actor: $admin, defaultClassId: 1);

        $this->assertSame(2, $result['imported_count']);
        $slots = $this->service->getClassTimetable(1, 1, $admin)['slots'];
        $this->assertCount(2, $slots);
    }

    public function testImportFromCsvWithColumnMapping(): void
    {
        $this->seedFixtures();
        $admin = $this->makeAdminContext();

        // Custom non-standard header names
        $csv = "Weekday,PeriodSlot,Course,Venue\n";
        $csv .= "Wednesday,1,MTH,Lab A\n";

        $mapping = [
            'day' => 0,
            'period' => 1,
            'subject' => 2,
            'room' => 3,
        ];

        $result = $this->service->importFromCsv($csv, termId: 1, actor: $admin, defaultClassId: 1, columnMapping: $mapping);

        $this->assertSame(1, $result['imported_count']);
        $slots = $this->service->getClassTimetable(1, 1, $admin)['slots'];
        $this->assertCount(1, $slots);
    }

    public function testRequestGetFileAlias(): void
    {
        $req = new \App\Core\Request(
            files: ['csv_file' => ['name' => 'test.csv', 'tmp_name' => '/tmp/test.csv', 'error' => 0]]
        );

        $this->assertNotNull($req->getFile('csv_file'));
        $this->assertSame('test.csv', $req->getFile('csv_file')['name']);
    }

    // ─── Term Timetable Cloning Tests ────────────────────────────────────────

    public function testCloneTermTimetableCrossSessionRejection(): void
    {
        $this->seedFixtures();
        $admin = $this->makeAdminContext();

        // Term 1 is session 1, Term 3 is session 2
        $this->expectException(DomainRuleException::class);
        $this->service->cloneTermTimetable(
            sourceTermId: 1,
            targetTermId: 3,
            classId: 1,
            overwrite: false,
            actor: $admin
        );
    }

    public function testCloneTermTimetableSuccess(): void
    {
        $this->seedFixtures();
        $admin = $this->makeAdminContext();

        // Create slot in Term 1
        $this->service->createSlot([
            'term_id' => 1,
            'class_subject_id' => 1,
            'day_of_week' => 'mon',
            'start_time' => '08:00',
            'end_time' => '08:45',
            'room' => 'Room 101',
        ], $admin);

        // Clone to Term 2 (both in Session 1)
        $result = $this->service->cloneTermTimetable(
            sourceTermId: 1,
            targetTermId: 2,
            classId: 1,
            overwrite: false,
            actor: $admin
        );

        $this->assertSame(1, $result['cloned_count']);
        $this->assertSame(0, $result['skipped_count']);

        // Verify slot in Term 2
        $term2Slots = $this->service->getClassTimetable(1, 2, $admin)['slots'];
        $this->assertCount(1, $term2Slots);
        $this->assertSame('08:00:00', $term2Slots[0]->startTime);
    }

    public function testCloneTermTimetableWithOverwrite(): void
    {
        $this->seedFixtures();
        $admin = $this->makeAdminContext();

        // Create slot in Term 1 (Period 1 Math)
        $this->service->createSlot([
            'term_id' => 1,
            'class_subject_id' => 1,
            'day_of_week' => 'mon',
            'start_time' => '08:00',
            'end_time' => '08:45',
            'room' => 'Room 101',
        ], $admin);

        // Create slot in Term 2 (Period 2 English)
        $this->service->createSlot([
            'term_id' => 2,
            'class_subject_id' => 2,
            'day_of_week' => 'mon',
            'start_time' => '08:45',
            'end_time' => '09:30',
            'room' => 'Room 202',
        ], $admin);

        // Clone with overwrite: true
        $result = $this->service->cloneTermTimetable(
            sourceTermId: 1,
            targetTermId: 2,
            classId: 1,
            overwrite: true,
            actor: $admin
        );

        $this->assertSame(1, $result['cloned_count']);

        // Only Period 1 Math should remain in Term 2
        $term2Slots = $this->service->getClassTimetable(1, 2, $admin)['slots'];
        $this->assertCount(1, $term2Slots);
        $this->assertSame('08:00:00', $term2Slots[0]->startTime);
    }
}
