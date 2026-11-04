<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\UserContext;
use App\Repositories\TimetableStructureRepository;
use App\Services\TimetableService;
use PDO;
use PHPUnit\Framework\TestCase;

class ConfigurableTimetableTest extends TestCase
{
    private PDO $db;
    private TimetableService $service;
    private TimetableStructureRepository $structureRepo;

    protected function setUp(): void
    {
        $this->db = new PDO('sqlite::memory:');
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Core schema with configurable timetable columns
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
            CREATE TABLE classes (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, section_arm TEXT, academic_level_id INTEGER DEFAULT 1, timetable_structure_id INTEGER NULL, status TEXT DEFAULT 'active');
            CREATE TABLE subjects (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, code TEXT, description TEXT, status TEXT DEFAULT 'active');
            CREATE TABLE teachers (id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER, staff_id TEXT, staff_no TEXT);
            CREATE TABLE class_subjects (id INTEGER PRIMARY KEY AUTOINCREMENT, session_id INTEGER, class_id INTEGER, subject_id INTEGER, teacher_id INTEGER, status TEXT DEFAULT 'active');

            CREATE TABLE timetable_structures (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                slug TEXT NOT NULL UNIQUE,
                stage_key TEXT NOT NULL DEFAULT 'all',
                is_default INTEGER DEFAULT 0,
                is_active INTEGER DEFAULT 1,
                days_json TEXT NOT NULL DEFAULT '[\"mon\", \"tue\", \"wed\", \"thu\", \"fri\"]',
                description TEXT NULL,
                created_at TEXT NULL,
                updated_at TEXT NULL
            );

            CREATE TABLE timetable_structure_periods (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                structure_id INTEGER NOT NULL,
                period_number INTEGER NULL,
                label TEXT NOT NULL,
                short_label TEXT NOT NULL,
                type TEXT NOT NULL DEFAULT 'instructional',
                start_time TEXT NOT NULL,
                end_time TEXT NOT NULL,
                sort_order INTEGER NOT NULL DEFAULT 1,
                spans_all_classes INTEGER DEFAULT 0,
                day_rules_json TEXT NULL,
                created_at TEXT NULL,
                updated_at TEXT NULL
            );

            CREATE TABLE timetable_slots (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                term_id INTEGER NOT NULL,
                class_id INTEGER NULL,
                structure_period_id INTEGER NULL,
                department TEXT NULL,
                custom_title TEXT NULL,
                slot_type TEXT NOT NULL DEFAULT 'subject',
                class_subject_id INTEGER NULL,
                day_of_week TEXT NOT NULL,
                start_time TEXT NOT NULL,
                end_time TEXT NOT NULL,
                room TEXT NULL,
                created_at TEXT NULL,
                updated_at TEXT NULL
            );
        ");

        $this->seedFixtures();
        $this->structureRepo = new TimetableStructureRepository($this->db);
        $this->service = new TimetableService(db: $this->db, structureRepo: $this->structureRepo);
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

        $this->db->exec("INSERT INTO academic_levels (id, name, stage, rank_order) VALUES (1, 'Senior Secondary', 'senior_secondary', 1)");
        $this->db->exec("INSERT INTO academic_levels (id, name, stage, rank_order) VALUES (2, 'Primary', 'primary', 2)");

        $this->db->exec("INSERT INTO sessions (id, name, is_current) VALUES (1, '2026/2027', 1)");
        $this->db->exec("INSERT INTO terms (id, session_id, name, is_current) VALUES (1, 1, 'First Term', 1)");

        // Structures
        $this->db->exec("INSERT INTO timetable_structures (id, name, slug, stage_key, is_default, is_active) VALUES (1, 'High School Standard Schedule', 'high_school_standard', 'high_school,junior_secondary,senior_secondary', 1, 1)");
        $this->db->exec("INSERT INTO timetable_structures (id, name, slug, stage_key, is_default, is_active) VALUES (2, 'Primary / Grade School Schedule', 'primary_standard', 'primary,grade_school', 0, 1)");

        // High school structure periods (13 blocks: 9 instructional, 2 breaks, 1 assembly, 1 activities)
        $this->db->exec("
            INSERT INTO timetable_structure_periods (id, structure_id, period_number, label, short_label, type, start_time, end_time, sort_order) VALUES
            (1, 1, NULL, 'Morning Assembly', 'ASM', 'assembly', '07:50:00', '08:10:00', 1),
            (2, 1, 1, 'Period 1', 'P1', 'instructional', '08:10:00', '08:50:00', 2),
            (3, 1, 2, 'Period 2', 'P2', 'instructional', '08:50:00', '09:30:00', 3),
            (4, 1, 3, 'Period 3', 'P3', 'instructional', '09:30:00', '10:10:00', 4),
            (5, 1, 4, 'Period 4', 'P4', 'instructional', '10:10:00', '10:50:00', 5),
            (6, 1, NULL, 'Break Time', 'BRK', 'break', '10:50:00', '11:20:00', 6),
            (7, 1, 5, 'Period 5', 'P5', 'instructional', '11:20:00', '12:00:00', 7),
            (8, 1, 6, 'Period 6', 'P6', 'instructional', '12:00:00', '12:40:00', 8),
            (9, 1, NULL, 'Short Break', 'SBRK', 'break', '12:40:00', '12:50:00', 9),
            (10, 1, 7, 'Period 7', 'P7', 'instructional', '12:50:00', '13:30:00', 10),
            (11, 1, 8, 'Period 8', 'P8', 'instructional', '13:30:00', '14:10:00', 11),
            (12, 1, 9, 'Period 9', 'P9', 'instructional', '14:10:00', '14:50:00', 12),
            (13, 1, NULL, 'Activities / Club', 'ACT', 'activity', '14:50:00', '15:00:00', 13);
        ");

        // Primary structure periods (11 blocks: 7 instructional)
        $this->db->exec("
            INSERT INTO timetable_structure_periods (id, structure_id, period_number, label, short_label, type, start_time, end_time, sort_order) VALUES
            (14, 2, NULL, 'Morning Devotion', 'DEV', 'assembly', '08:00:00', '08:20:00', 1),
            (15, 2, 1, 'Period 1', 'P1', 'instructional', '08:20:00', '08:55:00', 2),
            (16, 2, 2, 'Period 2', 'P2', 'instructional', '08:55:00', '09:30:00', 3),
            (17, 2, 3, 'Period 3', 'P3', 'instructional', '09:30:00', '10:05:00', 4),
            (18, 2, 4, 'Period 4', 'P4', 'instructional', '10:05:00', '10:40:00', 5),
            (19, 2, NULL, 'Long Recess', 'REC', 'break', '10:40:00', '11:20:00', 6),
            (20, 2, 5, 'Period 5', 'P5', 'instructional', '11:20:00', '11:55:00', 7),
            (21, 2, 6, 'Period 6', 'P6', 'instructional', '11:55:00', '12:30:00', 8),
            (22, 2, NULL, 'Fruit / Water Break', 'FBRK', 'break', '12:30:00', '12:45:00', 9),
            (23, 2, 7, 'Period 7', 'P7', 'instructional', '12:45:00', '13:20:00', 10),
            (24, 2, NULL, 'Pack-up & Dismissal', 'DIS', 'activity', '13:20:00', '13:30:00', 11);
        ");

        $this->db->exec("INSERT INTO classes (id, name, academic_level_id, timetable_structure_id) VALUES (1, 'SSS 2A', 1, 1)");
        $this->db->exec("INSERT INTO classes (id, name, academic_level_id, timetable_structure_id) VALUES (2, 'Basic 4A', 2, 2)");

        $this->db->exec("INSERT INTO subjects (id, name, code) VALUES (1, 'Chemistry', 'CHEM')");
        $this->db->exec("INSERT INTO subjects (id, name, code) VALUES (2, 'Commerce', 'COM')");
        $this->db->exec("INSERT INTO subjects (id, name, code) VALUES (3, 'Literature', 'LIT')");
        $this->db->exec("INSERT INTO subjects (id, name, code) VALUES (4, 'Mathematics', 'MTH')");

        $this->db->exec("INSERT INTO class_subjects (id, session_id, class_id, subject_id, teacher_id) VALUES (1, 1, 1, 1, 1)");
        $this->db->exec("INSERT INTO class_subjects (id, session_id, class_id, subject_id, teacher_id) VALUES (2, 1, 1, 2, 2)");
        $this->db->exec("INSERT INTO class_subjects (id, session_id, class_id, subject_id, teacher_id) VALUES (3, 1, 1, 3, 1)");
        $this->db->exec("INSERT INTO class_subjects (id, session_id, class_id, subject_id, teacher_id) VALUES (4, 1, 1, 4, 1)");
    }

    public function testStructureRepositoryLoadsHighSchoolAndPrimary(): void
    {
        $hs = $this->structureRepo->findByStage('high_school');
        $this->assertNotNull($hs);
        $this->assertSame('High School Standard Schedule', $hs->name);

        $hsPeriods = $this->structureRepo->findPeriods($hs->id);
        $this->assertCount(13, $hsPeriods);
        $this->assertSame(9, $hs->getMaxPeriodNumber());

        $pri = $this->structureRepo->findByStage('primary');
        $this->assertNotNull($pri);
        $this->assertSame('Primary / Grade School Schedule', $pri->name);

        $priPeriods = $this->structureRepo->findPeriods($pri->id);
        $this->assertCount(11, $priPeriods);
        $this->assertSame(7, $pri->getMaxPeriodNumber());
    }

    public function testStructureRepositoryResolvesByClass(): void
    {
        $class1Structure = $this->structureRepo->findByClassId(1);
        $this->assertNotNull($class1Structure);
        $this->assertSame('high_school_standard', $class1Structure->slug);

        $class2Structure = $this->structureRepo->findByClassId(2);
        $this->assertNotNull($class2Structure);
        $this->assertSame('primary_standard', $class2Structure->slug);
    }

    public function testGenerateSampleCsvIncludesBomAndEnhancedColumns(): void
    {
        $csv = $this->service->generateSampleCsv(stage: 'high_school');

        // Must start with UTF-8 BOM (\xEF\xBB\xBF) for Excel compatibility
        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv);

        // Header must contain all 8 columns
        $this->assertStringContainsString(
            'class_name,day_of_week,period_number,subject_code,department,room,slot_type,custom_title',
            $csv
        );

        // Must demonstrate period 9 (High school requirement)
        $this->assertStringContainsString(',9,', $csv);

        // Must demonstrate elective concurrent split (e.g. Science vs Commercial in same period)
        $this->assertStringContainsString('Science', $csv);
        $this->assertStringContainsString('Commercial', $csv);
    }

    public function testGenerateSampleCsvForGradeSchool(): void
    {
        $csv = $this->service->generateSampleCsv(stage: 'primary');

        // Must start with UTF-8 BOM (\xEF\xBB\xBF) for Excel compatibility
        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv);

        // Header must contain all 8 columns
        $this->assertStringContainsString(
            'class_name,day_of_week,period_number,subject_code,department,room,slot_type,custom_title',
            $csv
        );

        // Grade school has periods up to 7, but not period 9
        $this->assertStringContainsString(',7,', $csv);
        $this->assertStringNotContainsString(',9,', $csv);

        // Must include grade school subjects/activities from the authentic schedule
        $this->assertStringContainsString('MTH', $csv);
        $this->assertStringContainsString('ENG', $csv);
        $this->assertStringContainsString('Mental Math', $csv);
    }

    public function testPeriodScheduleGetPeriodByTimeResolvesCorrectly(): void
    {
        // 08:00 falls in Period 1
        $period = \App\Utils\PeriodSchedule::getPeriodByTime('08:00:00');
        $this->assertNotNull($period);
        $this->assertSame(1, $period['period_number']);

        // 10:35 falls in Period 4
        $period4 = \App\Utils\PeriodSchedule::getPeriodByTime('10:35:00');
        $this->assertNotNull($period4);
        $this->assertSame(4, $period4['period_number']);
    }

    public function testImportFromCsvSupportsPeriod9AndConcurrentElectiveSplits(): void
    {
        $csvContent = implode("\n", [
            'class_name,day_of_week,period_number,subject_code,department,room,slot_type,custom_title',
            // Period 9 regular class
            'SSS 2A,Monday,9,MTH,General,Room 101,instructional,',
            // Period 3 Elective split: Chemistry (Science) & Commerce (Commercial) concurrently in SSS 2A
            'SSS 2A,Tuesday,3,CHEM,Science,Lab 1,instructional,',
            'SSS 2A,Tuesday,3,COM,Commercial,Room 102,instructional,',
            // Activity block
            'SSS 2A,Friday,8,,General,Hall,sport,Clubs & Societies',
        ]);

        $result = $this->service->importFromCsv($csvContent, termId: 1, actor: $this->makeAdminContext());

        $this->assertSame(4, $result['imported_count']);
        $this->assertSame(1, $result['classes_count']);

        // Verify stored in DB
        $slots = $this->db->query("SELECT * FROM timetable_slots WHERE term_id = 1 ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
        $this->assertCount(4, $slots);

        // Period 9 slot verification (14:10 - 14:50)
        $this->assertStringStartsWith('14:10', $slots[0]['start_time']);
        $this->assertStringStartsWith('14:50', $slots[0]['end_time']);

        // Concurrent elective splits verification
        $this->assertSame('tue', $slots[1]['day_of_week']);
        $this->assertSame('Science', $slots[1]['department']);
        $this->assertSame('tue', $slots[2]['day_of_week']);
        $this->assertSame('Commercial', $slots[2]['department']);

        // Activity block verification
        $this->assertSame('sport', $slots[3]['slot_type']);
        $this->assertSame('Clubs & Societies', $slots[3]['custom_title']);
        $this->assertNull($slots[3]['class_subject_id']);
    }

    public function testGetMasterTimetableMatrixStructure(): void
    {
        // Insert a slot for SSS 2A on Monday Period 1 (08:10 - 08:50)
        $this->db->exec("
            INSERT INTO timetable_slots (term_id, class_id, structure_period_id, class_subject_id, day_of_week, start_time, end_time, room, slot_type)
            VALUES (1, 1, 2, 1, 'Monday', '08:10', '08:50', 'Lab 1', 'instructional')
        ");

        $matrix = $this->service->getMasterTimetableMatrix(1, 'high_school');

        $this->assertArrayHasKey('structure', $matrix);
        $this->assertArrayHasKey('classes', $matrix);
        $this->assertArrayHasKey('periods', $matrix);
        $this->assertArrayHasKey('matrix', $matrix);

        // Verify matrix has mon -> Class 1 -> Period 1
        $this->assertArrayHasKey('mon', $matrix['matrix']);
        $this->assertArrayHasKey(1, $matrix['matrix']['mon']);
        $this->assertArrayHasKey(1, $matrix['matrix']['mon'][1]);

        $cellSlots = $matrix['matrix']['mon'][1][1];
        $this->assertCount(1, $cellSlots);
        $this->assertSame('Lab 1', $cellSlots[0]->room);
    }
}
