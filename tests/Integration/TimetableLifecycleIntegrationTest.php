<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Core\Exceptions\AuthorizationException;
use App\Core\Exceptions\ValidationException;
use App\Core\UserContext;
use App\Repositories\AcademicRepository;
use App\Repositories\EnrollmentRepository;
use App\Repositories\ParentRepository;
use App\Repositories\StudentRepository;
use App\Repositories\TeacherRepository;
use App\Repositories\TimetableRepository;
use App\Services\AuditService;
use App\Services\TimetableService;
use PDO;
use PHPUnit\Framework\TestCase;

class TimetableLifecycleIntegrationTest extends TestCase
{
    private PDO $db;
    private TimetableService $timetableService;

    // Entity IDs
    private int $adminUserId;
    private int $teacherUser1Id;
    private int $teacherProfile1Id;
    private int $teacherUser2Id;
    private int $teacherProfile2Id;
    private int $studentUser1Id;
    private int $studentProfile1Id;
    private int $studentUser2Id;
    private int $studentProfile2Id;
    private int $parentUserId;
    private int $parentProfileId;
    private int $sessionId;
    private int $termId;
    private int $class1Id;
    private int $class2Id;
    private int $classSubject1Id; // Class 1, Subject 1, Teacher 1
    private int $classSubject2Id; // Class 1, Subject 2, Teacher 2
    private int $classSubject3Id; // Class 2, Subject 1, Teacher 1 (same teacher — for double-booking test)

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
            CREATE TABLE students (id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER, admission_number TEXT, date_of_birth TEXT, gender TEXT, current_class_id INTEGER, created_at TEXT, updated_at TEXT);
            CREATE TABLE parents (id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER, occupation TEXT, address TEXT, created_at TEXT, updated_at TEXT);
            CREATE TABLE parent_student (id INTEGER PRIMARY KEY AUTOINCREMENT, parent_id INTEGER, student_id INTEGER, relationship_type TEXT DEFAULT 'guardian', is_primary INTEGER DEFAULT 1, created_at TEXT);

            CREATE TABLE class_subjects (id INTEGER PRIMARY KEY AUTOINCREMENT, session_id INTEGER, class_id INTEGER, subject_id INTEGER, teacher_id INTEGER, status TEXT DEFAULT 'active');
            CREATE TABLE class_enrollments (id INTEGER PRIMARY KEY AUTOINCREMENT, session_id INTEGER, class_id INTEGER, student_id INTEGER, status TEXT DEFAULT 'active', enrolled_at TEXT, created_at TEXT, updated_at TEXT);
            CREATE TABLE student_subject_enrollments (id INTEGER PRIMARY KEY AUTOINCREMENT, session_id INTEGER, class_subject_id INTEGER, student_id INTEGER, is_elective INTEGER DEFAULT 0, status TEXT DEFAULT 'active', created_at TEXT, updated_at TEXT);

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

        $this->timetableService = new TimetableService(db: $this->db);

        $this->seedFixtureData();
    }

    private function seedFixtureData(): void
    {
        $this->db->exec("INSERT INTO academic_levels (id, name, stage, rank_order) VALUES (1, 'Junior Secondary', 'secondary', 1)");

        // Admin
        $this->db->exec("INSERT INTO users (name, email, status) VALUES ('Super Admin', 'admin@claret.edu', 'active')");
        $this->adminUserId = (int)$this->db->lastInsertId();

        // Teacher 1
        $this->db->exec("INSERT INTO users (name, email, status) VALUES ('Dr. Angela Davis', 'angela@claret.edu', 'active')");
        $this->teacherUser1Id = (int)$this->db->lastInsertId();
        $this->db->exec("INSERT INTO teachers (user_id, staff_id, staff_no) VALUES ({$this->teacherUser1Id}, 'FAC-001', 'FAC-001')");
        $this->teacherProfile1Id = (int)$this->db->lastInsertId();

        // Teacher 2
        $this->db->exec("INSERT INTO users (name, email, status) VALUES ('Prof. John Okon', 'john@claret.edu', 'active')");
        $this->teacherUser2Id = (int)$this->db->lastInsertId();
        $this->db->exec("INSERT INTO teachers (user_id, staff_id, staff_no) VALUES ({$this->teacherUser2Id}, 'FAC-002', 'FAC-002')");
        $this->teacherProfile2Id = (int)$this->db->lastInsertId();

        // Academic structure
        $this->db->exec("INSERT INTO sessions (name, start_date, end_date, status, is_current) VALUES ('2026/2027', '2026-09-01', '2027-07-20', 'active', 1)");
        $this->sessionId = (int)$this->db->lastInsertId();

        $this->db->exec("INSERT INTO terms (session_id, name, start_date, end_date, status, is_current) VALUES ({$this->sessionId}, 'First Term', '2026-09-01', '2026-12-18', 'active', 1)");
        $this->termId = (int)$this->db->lastInsertId();

        $this->db->exec("INSERT INTO classes (name, section_arm, academic_level_id, status) VALUES ('JSS 1', 'Gold', 1, 'active')");
        $this->class1Id = (int)$this->db->lastInsertId();

        $this->db->exec("INSERT INTO classes (name, section_arm, academic_level_id, status) VALUES ('JSS 2', 'Diamond', 1, 'active')");
        $this->class2Id = (int)$this->db->lastInsertId();

        $this->db->exec("INSERT INTO subjects (name, code) VALUES ('Mathematics', 'MTH101')");
        $subjectId1 = (int)$this->db->lastInsertId();

        $this->db->exec("INSERT INTO subjects (name, code) VALUES ('English Language', 'ENG101')");
        $subjectId2 = (int)$this->db->lastInsertId();

        // Class-Subject mappings
        $this->db->exec("INSERT INTO class_subjects (session_id, class_id, subject_id, teacher_id) VALUES ({$this->sessionId}, {$this->class1Id}, {$subjectId1}, {$this->teacherProfile1Id})");
        $this->classSubject1Id = (int)$this->db->lastInsertId();

        $this->db->exec("INSERT INTO class_subjects (session_id, class_id, subject_id, teacher_id) VALUES ({$this->sessionId}, {$this->class1Id}, {$subjectId2}, {$this->teacherProfile2Id})");
        $this->classSubject2Id = (int)$this->db->lastInsertId();

        // Same Teacher 1 teaches Math in Class 2 too (for double-booking tests)
        $this->db->exec("INSERT INTO class_subjects (session_id, class_id, subject_id, teacher_id) VALUES ({$this->sessionId}, {$this->class2Id}, {$subjectId1}, {$this->teacherProfile1Id})");
        $this->classSubject3Id = (int)$this->db->lastInsertId();

        // Students
        $this->db->exec("INSERT INTO users (name, email, status) VALUES ('Emmanuel Obi', 'emmanuel@student.edu', 'active')");
        $this->studentUser1Id = (int)$this->db->lastInsertId();
        $this->db->exec("INSERT INTO students (user_id, admission_number, current_class_id) VALUES ({$this->studentUser1Id}, 'ADM-001', {$this->class1Id})");
        $this->studentProfile1Id = (int)$this->db->lastInsertId();

        $this->db->exec("INSERT INTO users (name, email, status) VALUES ('Chidi Eze', 'chidi@student.edu', 'active')");
        $this->studentUser2Id = (int)$this->db->lastInsertId();
        $this->db->exec("INSERT INTO students (user_id, admission_number, current_class_id) VALUES ({$this->studentUser2Id}, 'ADM-002', {$this->class2Id})");
        $this->studentProfile2Id = (int)$this->db->lastInsertId();

        // Enroll Student 1 in Class 1
        $this->db->exec("INSERT INTO class_enrollments (session_id, class_id, student_id) VALUES ({$this->sessionId}, {$this->class1Id}, {$this->studentProfile1Id})");
        $this->db->exec("INSERT INTO student_subject_enrollments (session_id, class_subject_id, student_id) VALUES ({$this->sessionId}, {$this->classSubject1Id}, {$this->studentProfile1Id})");
        $this->db->exec("INSERT INTO student_subject_enrollments (session_id, class_subject_id, student_id) VALUES ({$this->sessionId}, {$this->classSubject2Id}, {$this->studentProfile1Id})");

        // Enroll Student 2 in Class 2
        $this->db->exec("INSERT INTO class_enrollments (session_id, class_id, student_id) VALUES ({$this->sessionId}, {$this->class2Id}, {$this->studentProfile2Id})");
        $this->db->exec("INSERT INTO student_subject_enrollments (session_id, class_subject_id, student_id) VALUES ({$this->sessionId}, {$this->classSubject3Id}, {$this->studentProfile2Id})");

        // Guardian
        $this->db->exec("INSERT INTO users (name, email, status) VALUES ('Chief Donald Obi', 'donald@guardian.test', 'active')");
        $this->parentUserId = (int)$this->db->lastInsertId();
        $this->db->exec("INSERT INTO parents (user_id) VALUES ({$this->parentUserId})");
        $this->parentProfileId = (int)$this->db->lastInsertId();
        // Link guardian to Student 1
        $this->db->exec("INSERT INTO parent_student (parent_id, student_id, relationship_type) VALUES ({$this->parentProfileId}, {$this->studentProfile1Id}, 'father')");
    }

    private function makeContext(int $userId, string $role, string $email): UserContext
    {
        return new UserContext(
            id: $userId,
            uuid: 'uuid-' . $userId,
            name: 'User',
            email: $email,
            roles: [$role]
        );
    }

    // ─── Full lifecycle ───────────────────────────────────────────────────

    public function testAdminCreatesSlotAndItPersists(): void
    {
        $admin = $this->makeContext($this->adminUserId, 'admin', 'admin@claret.edu');

        $slot = $this->timetableService->createSlot([
            'term_id'          => $this->termId,
            'class_subject_id' => $this->classSubject1Id,
            'day_of_week'      => 'mon',
            'start_time'       => '08:00',
            'end_time'         => '09:00',
            'room'             => 'Room A',
        ], $admin);

        $this->assertNotNull($slot->id);
        $this->assertSame('08:00:00', $slot->startTime);
        $this->assertSame('09:00:00', $slot->endTime);
        $this->assertSame('mon', $slot->dayOfWeek);
        $this->assertSame('Room A', $slot->room);
    }

    public function testConflictingSlotRejected(): void
    {
        $admin = $this->makeContext($this->adminUserId, 'admin', 'admin@claret.edu');

        $this->timetableService->createSlot([
            'term_id'          => $this->termId,
            'class_subject_id' => $this->classSubject1Id,
            'day_of_week'      => 'mon',
            'start_time'       => '08:00',
            'end_time'         => '10:00',
            'room'             => 'Room A',
        ], $admin);

        $this->expectException(ValidationException::class);
        $this->timetableService->createSlot([
            'term_id'          => $this->termId,
            'class_subject_id' => $this->classSubject2Id,
            'day_of_week'      => 'mon',
            'start_time'       => '09:00',
            'end_time'         => '11:00',
            'room'             => 'Room B',
        ], $admin);
    }

    public function testTouchingBoundariesAreAllowed(): void
    {
        $admin = $this->makeContext($this->adminUserId, 'admin', 'admin@claret.edu');

        $slot1 = $this->timetableService->createSlot([
            'term_id'          => $this->termId,
            'class_subject_id' => $this->classSubject1Id,
            'day_of_week'      => 'tue',
            'start_time'       => '08:00',
            'end_time'         => '09:00',
            'room'             => 'Room A',
        ], $admin);

        // English immediately after Math — touching 09:00 boundary — must succeed
        $slot2 = $this->timetableService->createSlot([
            'term_id'          => $this->termId,
            'class_subject_id' => $this->classSubject2Id,
            'day_of_week'      => 'tue',
            'start_time'       => '09:00',
            'end_time'         => '10:00',
            'room'             => 'Room A',
        ], $admin);

        $this->assertNotNull($slot1->id);
        $this->assertNotNull($slot2->id);
    }

    public function testTeacherDoubleBookingRejected(): void
    {
        $admin = $this->makeContext($this->adminUserId, 'admin', 'admin@claret.edu');

        // Teacher 1 teaches Class 1 on Tue 10:00–11:00
        $this->timetableService->createSlot([
            'term_id'          => $this->termId,
            'class_subject_id' => $this->classSubject1Id,
            'day_of_week'      => 'tue',
            'start_time'       => '10:00',
            'end_time'         => '11:00',
            'room'             => 'Room A',
        ], $admin);

        // Teacher 1 also in Class 2 on Tue 10:30–11:30 — must be rejected (double-booking)
        try {
            $this->timetableService->createSlot([
                'term_id'          => $this->termId,
                'class_subject_id' => $this->classSubject3Id,
                'day_of_week'      => 'tue',
                'start_time'       => '10:30',
                'end_time'         => '11:30',
                'room'             => 'Room B',
            ], $admin);
            $this->fail('Expected ValidationException for teacher double-booking');
        } catch (ValidationException $e) {
            $errors = $e->getErrors();
            $allMessages = json_encode($errors);
            $this->assertStringContainsString('Teacher conflict', $allMessages);
        }
    }

    public function testRoomCollisionRejected(): void
    {
        $admin = $this->makeContext($this->adminUserId, 'admin', 'admin@claret.edu');

        // Class 1 in "Auditorium" on Wed 14:00–15:30
        $this->timetableService->createSlot([
            'term_id'          => $this->termId,
            'class_subject_id' => $this->classSubject1Id,
            'day_of_week'      => 'wed',
            'start_time'       => '14:00',
            'end_time'         => '15:30',
            'room'             => 'Auditorium',
        ], $admin);

        // Class 2 attempts same room overlapping — must be rejected
        try {
            $this->timetableService->createSlot([
                'term_id'          => $this->termId,
                'class_subject_id' => $this->classSubject2Id,
                'day_of_week'      => 'wed',
                'start_time'       => '14:30',
                'end_time'         => '16:00',
                'room'             => 'Auditorium',
            ], $admin);
            $this->fail('Expected ValidationException for room collision');
        } catch (ValidationException $e) {
            $errors = $e->getErrors();
            $allMessages = json_encode($errors);
            $this->assertStringContainsString('Room conflict', $allMessages);
        }
    }

    public function testUpdateSlot(): void
    {
        $admin = $this->makeContext($this->adminUserId, 'admin', 'admin@claret.edu');

        $slot = $this->timetableService->createSlot([
            'term_id'          => $this->termId,
            'class_subject_id' => $this->classSubject1Id,
            'day_of_week'      => 'thu',
            'start_time'       => '11:00',
            'end_time'         => '12:00',
            'room'             => 'Room 1',
        ], $admin);

        $updated = $this->timetableService->updateSlot((int)$slot->id, [
            'term_id'          => $this->termId,
            'class_subject_id' => $this->classSubject1Id,
            'day_of_week'      => 'thu',
            'start_time'       => '11:30',
            'end_time'         => '12:30',
            'room'             => 'Room 2',
        ], $admin);

        $this->assertSame('11:30:00', $updated->startTime);
        $this->assertSame('12:30:00', $updated->endTime);
        $this->assertSame('Room 2', $updated->room);
    }

    public function testDeleteSlot(): void
    {
        $admin = $this->makeContext($this->adminUserId, 'admin', 'admin@claret.edu');

        $slot = $this->timetableService->createSlot([
            'term_id'          => $this->termId,
            'class_subject_id' => $this->classSubject1Id,
            'day_of_week'      => 'fri',
            'start_time'       => '09:00',
            'end_time'         => '10:00',
        ], $admin);

        $deleted = $this->timetableService->deleteSlot((int)$slot->id, $admin);
        $this->assertTrue($deleted);

        $stmt = $this->db->prepare("SELECT COUNT(*) FROM timetable_slots WHERE id = ?");
        $stmt->execute([(int)$slot->id]);
        $this->assertSame(0, (int)$stmt->fetchColumn());
    }

    // ─── Teacher view ─────────────────────────────────────────────────────

    public function testTeacherSeesOwnScheduleAndCannotViewAnother(): void
    {
        $admin = $this->makeContext($this->adminUserId, 'admin', 'admin@claret.edu');

        // 2 slots for Teacher 1
        $this->timetableService->createSlot([
            'term_id'          => $this->termId,
            'class_subject_id' => $this->classSubject1Id,
            'day_of_week'      => 'mon',
            'start_time'       => '08:00',
            'end_time'         => '09:00',
            'room'             => 'Room 101',
        ], $admin);

        $this->timetableService->createSlot([
            'term_id'          => $this->termId,
            'class_subject_id' => $this->classSubject3Id, // Teacher 1 in Class 2
            'day_of_week'      => 'fri',
            'start_time'       => '11:00',
            'end_time'         => '12:00',
            'room'             => 'Room 202',
        ], $admin);

        $teacher1 = $this->makeContext($this->teacherUser1Id, 'teacher', 'angela@claret.edu');

        // Teacher 1 can see own timetable
        $schedule = $this->timetableService->getTeacherTimetable($this->teacherProfile1Id, $this->termId, $teacher1);
        $this->assertCount(2, $schedule['slots']);
        $this->assertNotEmpty($schedule['grid']['mon']);
        $this->assertNotEmpty($schedule['grid']['fri']);

        // Teacher 1 cannot view Teacher 2's timetable
        $this->expectException(AuthorizationException::class);
        $this->timetableService->getTeacherTimetable($this->teacherProfile2Id, $this->termId, $teacher1);
    }

    // ─── Student view ─────────────────────────────────────────────────────

    public function testStudentSeesOwnTimetableAndCannotViewAnother(): void
    {
        $admin = $this->makeContext($this->adminUserId, 'admin', 'admin@claret.edu');

        // Create a slot for Class 1
        $this->timetableService->createSlot([
            'term_id'          => $this->termId,
            'class_subject_id' => $this->classSubject1Id,
            'day_of_week'      => 'thu',
            'start_time'       => '09:00',
            'end_time'         => '10:00',
            'room'             => 'Hall 1',
        ], $admin);

        $student1 = $this->makeContext($this->studentUser1Id, 'student', 'emmanuel@student.edu');

        // Student 1 can see own timetable
        $schedule = $this->timetableService->getStudentTimetable($this->studentProfile1Id, $this->termId, $student1);
        $this->assertCount(1, $schedule['slots']);
        $this->assertSame('Hall 1', $schedule['slots'][0]->room);

        // Student 1 cannot view Student 2's timetable
        $this->expectException(AuthorizationException::class);
        $this->timetableService->getStudentTimetable($this->studentProfile2Id, $this->termId, $student1);
    }

    // ─── Parent view ─────────────────────────────────────────────────────

    public function testParentSeesLinkedChildTimetableAndIdorProtected(): void
    {
        $admin = $this->makeContext($this->adminUserId, 'admin', 'admin@claret.edu');

        // Slot for Class 1 (Student 1's class)
        $this->timetableService->createSlot([
            'term_id'          => $this->termId,
            'class_subject_id' => $this->classSubject1Id,
            'day_of_week'      => 'mon',
            'start_time'       => '08:00',
            'end_time'         => '09:00',
            'room'             => 'Room 101',
        ], $admin);

        $parent = $this->makeContext($this->parentUserId, 'parent', 'donald@guardian.test');

        // Parent can see linked child's timetable
        $schedule = $this->timetableService->getStudentTimetable($this->studentProfile1Id, $this->termId, $parent);
        $this->assertCount(1, $schedule['slots']);

        // Parent cannot see unlinked student's timetable
        $this->expectException(AuthorizationException::class);
        $this->timetableService->getStudentTimetable($this->studentProfile2Id, $this->termId, $parent);
    }

    // ─── Audit trail ─────────────────────────────────────────────────────

    public function testAuditTrailRecordedForAllMutations(): void
    {
        $admin = $this->makeContext($this->adminUserId, 'admin', 'admin@claret.edu');

        $slot = $this->timetableService->createSlot([
            'term_id'          => $this->termId,
            'class_subject_id' => $this->classSubject1Id,
            'day_of_week'      => 'tue',
            'start_time'       => '11:00',
            'end_time'         => '12:00',
            'room'             => 'Room 1',
        ], $admin);

        $this->timetableService->updateSlot((int)$slot->id, [
            'term_id'          => $this->termId,
            'class_subject_id' => $this->classSubject1Id,
            'day_of_week'      => 'tue',
            'start_time'       => '11:30',
            'end_time'         => '12:30',
            'room'             => 'Room 2',
        ], $admin);

        $this->timetableService->deleteSlot((int)$slot->id, $admin);

        $stmt = $this->db->query("SELECT action FROM audit_logs WHERE entity_type = 'timetable_slots' ORDER BY id ASC");
        $actions = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $this->assertSame(
            ['TIMETABLE_SLOT_CREATED', 'TIMETABLE_SLOT_UPDATED', 'TIMETABLE_SLOT_DELETED'],
            $actions
        );
    }

    // ─── Non-admin mutation blocked ───────────────────────────────────────

    public function testTeacherCannotCreateSlot(): void
    {
        $teacher = $this->makeContext($this->teacherUser1Id, 'teacher', 'angela@claret.edu');

        $this->expectException(AuthorizationException::class);
        $this->timetableService->createSlot([
            'term_id'          => $this->termId,
            'class_subject_id' => $this->classSubject1Id,
            'day_of_week'      => 'mon',
            'start_time'       => '08:00',
            'end_time'         => '09:00',
        ], $teacher);
    }

    public function testParentCannotCreateSlot(): void
    {
        $parent = $this->makeContext($this->parentUserId, 'parent', 'donald@guardian.test');

        $this->expectException(AuthorizationException::class);
        $this->timetableService->createSlot([
            'term_id'          => $this->termId,
            'class_subject_id' => $this->classSubject1Id,
            'day_of_week'      => 'mon',
            'start_time'       => '08:00',
            'end_time'         => '09:00',
        ], $parent);
    }
}
