<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Core\Exceptions\AuthorizationException;
use App\Core\Exceptions\ValidationException;
use App\Core\UserContext;
use App\Models\User;
use App\Policies\AnnouncementPolicy;
use App\Policies\AttendancePolicy;
use App\Repositories\AcademicRepository;
use App\Repositories\AnnouncementRepository;
use App\Repositories\AttendanceRepository;
use App\Services\AnnouncementService;
use App\Services\AttendanceService;
use App\Services\AuditService;
use PDO;
use PHPUnit\Framework\TestCase;

class AttendanceAndAnnouncementLifecycleIntegrationTest extends TestCase
{
    private PDO $db;
    private AttendanceService $attendanceService;
    private AnnouncementService $announcementService;
    private AttendanceRepository $attendanceRepo;
    private AnnouncementRepository $announcementRepo;
    private AcademicRepository $academicRepo;
    private AuditService $auditService;

    protected function setUp(): void
    {
        $this->db = new PDO('sqlite::memory:');
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Build comprehensive schema for SQLite
        $this->db->exec("
            CREATE TABLE sessions (id INTEGER PRIMARY KEY, name TEXT, start_date TEXT, end_date TEXT, status TEXT DEFAULT 'active', is_current INTEGER DEFAULT 0);
            CREATE TABLE terms (id INTEGER PRIMARY KEY, session_id INTEGER, name TEXT, start_date TEXT, end_date TEXT, status TEXT DEFAULT 'active', is_current INTEGER DEFAULT 0);
            CREATE TABLE classes (id INTEGER PRIMARY KEY, name TEXT, academic_level_id INTEGER);
            CREATE TABLE subjects (id INTEGER PRIMARY KEY, name TEXT, code TEXT);
            CREATE TABLE class_subjects (id INTEGER PRIMARY KEY, class_id INTEGER, subject_id INTEGER, teacher_id INTEGER);
            
            CREATE TABLE users (id INTEGER PRIMARY KEY, name TEXT, email TEXT, status TEXT);
            CREATE TABLE teachers (id INTEGER PRIMARY KEY, user_id INTEGER);
            CREATE TABLE students (id INTEGER PRIMARY KEY, user_id INTEGER, admission_number TEXT);
            CREATE TABLE parents (id INTEGER PRIMARY KEY, user_id INTEGER);
            CREATE TABLE parent_student (id INTEGER PRIMARY KEY, parent_id INTEGER, student_id INTEGER);

            CREATE TABLE class_enrollments (id INTEGER PRIMARY KEY, session_id INTEGER, class_id INTEGER, student_id INTEGER, status TEXT);
            CREATE TABLE student_subject_enrollments (id INTEGER PRIMARY KEY, session_id INTEGER, class_subject_id INTEGER, student_id INTEGER, status TEXT);
            
            CREATE TABLE student_term_summaries (
                id INTEGER PRIMARY KEY,
                student_id INTEGER,
                term_id INTEGER,
                attendance_present_count INTEGER DEFAULT 0,
                attendance_total_count INTEGER DEFAULT 0,
                created_at TEXT,
                updated_at TEXT
            );

            CREATE TABLE attendance_records (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                session_id INTEGER NOT NULL,
                term_id INTEGER NOT NULL,
                class_id INTEGER NOT NULL,
                class_subject_id INTEGER NULL,
                student_id INTEGER NOT NULL,
                date TEXT NOT NULL,
                period_number INTEGER NULL,
                status TEXT NOT NULL DEFAULT 'present',
                marked_by INTEGER NOT NULL,
                updated_by INTEGER NULL,
                correction_reason TEXT NULL,
                created_at TEXT,
                updated_at TEXT
            );

            CREATE TABLE announcements (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                author_id INTEGER NOT NULL,
                scope TEXT NOT NULL,
                scope_id INTEGER NULL,
                title TEXT NOT NULL,
                body TEXT NOT NULL,
                published_at TEXT NULL,
                expires_at TEXT NULL,
                created_at TEXT,
                updated_at TEXT
            );

            CREATE TABLE announcement_reads (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                announcement_id INTEGER NOT NULL,
                user_id INTEGER NOT NULL,
                read_at TEXT NOT NULL,
                created_at TEXT,
                UNIQUE (announcement_id, user_id)
            );

            CREATE TABLE audit_logs (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                actor_user_id INTEGER NULL,
                action TEXT NOT NULL,
                entity_type TEXT NOT NULL,
                entity_id INTEGER NOT NULL,
                before_json TEXT NULL,
                after_json TEXT NULL,
                metadata_json TEXT NULL,
                ip_hash TEXT NULL,
                user_agent_hash TEXT NULL,
                request_id TEXT NULL,
                created_at TEXT
            );
        ");

        // Seed Core Data
        $this->db->exec("INSERT INTO sessions (id, name, is_current) VALUES (1, '2026/2027', 1)");
        $this->db->exec("INSERT INTO terms (id, session_id, name, is_current) VALUES (1, 1, 'First Term', 1)");
        $this->db->exec("INSERT INTO classes (id, name, academic_level_id) VALUES (10, 'Grade 10-A', 1), (20, 'Grade 11-B', 2)");
        $this->db->exec("INSERT INTO subjects (id, name, code) VALUES (1, 'Mathematics', 'MTH101'), (2, 'Physics', 'PHY101')");

        // Users & Roles
        // Admin: user 1
        $this->db->exec("INSERT INTO users (id, name, email, status) VALUES (1, 'Super Admin', 'admin@claret.edu', 'active')");
        
        // Teacher 1: user 2 -> teacher 100 (teaches Math in Grade 10-A)
        $this->db->exec("INSERT INTO users (id, name, email, status) VALUES (2, 'Teacher Math', 'teacher.math@claret.edu', 'active')");
        $this->db->exec("INSERT INTO teachers (id, user_id) VALUES (100, 2)");
        $this->db->exec("INSERT INTO class_subjects (id, class_id, subject_id, teacher_id) VALUES (50, 10, 1, 100)");

        // Teacher 2: user 3 -> teacher 200 (teaches Physics in Grade 11-B)
        $this->db->exec("INSERT INTO users (id, name, email, status) VALUES (3, 'Teacher Physics', 'teacher.physics@claret.edu', 'active')");
        $this->db->exec("INSERT INTO teachers (id, user_id) VALUES (200, 3)");
        $this->db->exec("INSERT INTO class_subjects (id, class_id, subject_id, teacher_id) VALUES (60, 20, 2, 200)");

        // Student 1: user 10 -> student 101 (in Grade 10-A, takes Math)
        $this->db->exec("INSERT INTO users (id, name, email, status) VALUES (10, 'Alice Wonderland', 'alice@claret.edu', 'active')");
        $this->db->exec("INSERT INTO students (id, user_id, admission_number) VALUES (101, 10, 'ADM-001')");
        $this->db->exec("INSERT INTO class_enrollments (session_id, class_id, student_id, status) VALUES (1, 10, 101, 'active')");
        $this->db->exec("INSERT INTO student_subject_enrollments (session_id, class_subject_id, student_id, status) VALUES (1, 50, 101, 'active')");
        $this->db->exec("INSERT INTO student_term_summaries (id, student_id, term_id, attendance_present_count, attendance_total_count) VALUES (1, 101, 1, 0, 0)");

        // Student 2: user 11 -> student 102 (in Grade 10-A, takes Math)
        $this->db->exec("INSERT INTO users (id, name, email, status) VALUES (11, 'Bob Builder', 'bob@claret.edu', 'active')");
        $this->db->exec("INSERT INTO students (id, user_id, admission_number) VALUES (102, 11, 'ADM-002')");
        $this->db->exec("INSERT INTO class_enrollments (session_id, class_id, student_id, status) VALUES (1, 10, 102, 'active')");
        $this->db->exec("INSERT INTO student_subject_enrollments (session_id, class_subject_id, student_id, status) VALUES (1, 50, 102, 'active')");
        $this->db->exec("INSERT INTO student_term_summaries (id, student_id, term_id, attendance_present_count, attendance_total_count) VALUES (2, 102, 1, 0, 0)");

        // Student 3: user 12 -> student 103 (in Grade 11-B, takes Physics)
        $this->db->exec("INSERT INTO users (id, name, email, status) VALUES (12, 'Charlie Chaplin', 'charlie@claret.edu', 'active')");
        $this->db->exec("INSERT INTO students (id, user_id, admission_number) VALUES (103, 12, 'ADM-003')");
        $this->db->exec("INSERT INTO class_enrollments (session_id, class_id, student_id, status) VALUES (1, 20, 103, 'active')");
        $this->db->exec("INSERT INTO student_subject_enrollments (session_id, class_subject_id, student_id, status) VALUES (1, 60, 103, 'active')");

        // Parent 1: user 20 -> parent 301 (linked to Student 1 Alice)
        $this->db->exec("INSERT INTO users (id, name, email, status) VALUES (20, 'Parent Alice', 'parent.alice@claret.edu', 'active')");
        $this->db->exec("INSERT INTO parents (id, user_id) VALUES (301, 20)");
        $this->db->exec("INSERT INTO parent_student (parent_id, student_id) VALUES (301, 101)");

        // Initialize Services & Repos
        $this->attendanceRepo = new AttendanceRepository($this->db);
        $this->announcementRepo = new AnnouncementRepository($this->db);
        $this->academicRepo = new AcademicRepository($this->db);
        $this->auditService = new AuditService($this->db);

        $attendancePolicy = new AttendancePolicy($this->db);
        $announcementPolicy = new AnnouncementPolicy($this->db);

        $this->attendanceService = new AttendanceService(
            attendanceRepo: $this->attendanceRepo,
            policy: $attendancePolicy,
            auditService: $this->auditService,
            academicRepo: $this->academicRepo,
            db: $this->db
        );

        $this->announcementService = new AnnouncementService(
            announcementRepo: $this->announcementRepo,
            policy: $announcementPolicy,
            auditService: $this->auditService,
            academicRepo: $this->academicRepo,
            db: $this->db
        );
    }

    public function testAttendanceFullLifecycle(): void
    {
        $teacherContext = UserContext::fromUser(User::fromArray([
            'id' => 2,
            'email' => 'teacher.math@claret.edu',
            'status' => 'active',
            'roles' => ['teacher']
        ]));

        $today = date('Y-m-d');

        // 1. Get default-present roster for Grade 10-A
        $roster = $this->attendanceService->getRoster(classId: 10, date: $today, user: $teacherContext);
        $this->assertCount(2, $roster);
        $this->assertSame('Alice Wonderland', $roster[0]['student_name']);
        $this->assertSame('present', $roster[0]['status']);
        $this->assertFalse($roster[0]['is_recorded']);

        // 2. Mark Daily Attendance: Alice is Present, Bob is Late
        $records = [
            ['student_id' => 101, 'status' => 'present'],
            ['student_id' => 102, 'status' => 'late'],
        ];

        $this->attendanceService->recordRoster(
            classId: 10,
            date: $today,
            classSubjectId: null,
            periodNumber: null,
            records: $records,
            user: $teacherContext
        );

        // 3. Verify attendance records saved and summary synced in student_term_summaries
        $aliceSummary = $this->attendanceService->getStudentSummary(101, 1, $teacherContext);
        $this->assertSame(1, $aliceSummary['total_days']);
        $this->assertSame(1, $aliceSummary['present_days']);
        $this->assertSame(0, $aliceSummary['late_days']);
        $this->assertSame(100.0, $aliceSummary['attendance_rate']);

        $bobSummary = $this->attendanceService->getStudentSummary(102, 1, $teacherContext);
        $this->assertSame(1, $bobSummary['total_days']);
        $this->assertSame(0, $bobSummary['present_days']);
        $this->assertSame(1, $bobSummary['late_days']);
        $this->assertSame(100.0, $bobSummary['attendance_rate']); // late counts toward attended rate

        // Check term summary row
        $stmt = $this->db->query("SELECT * FROM student_term_summaries WHERE student_id = 101");
        $termRow = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->assertSame(1, (int)$termRow['attendance_present_count']);
        $this->assertSame(1, (int)$termRow['attendance_total_count']);

        // 4. Period Attendance Marking for Class Subject 50 (Math)
        $this->attendanceService->recordRoster(
            classId: 10,
            date: $today,
            classSubjectId: 50,
            periodNumber: 1,
            records: [
                ['student_id' => 101, 'status' => 'present'],
                ['student_id' => 102, 'status' => 'absent'],
            ],
            user: $teacherContext
        );

        $periodRoster = $this->attendanceService->getRoster(classId: 10, date: $today, classSubjectId: 50, periodNumber: 1, user: $teacherContext);
        $this->assertCount(2, $periodRoster);
        $this->assertTrue($periodRoster[0]['is_recorded']);

        // 5. Audit Log verification
        $auditStmt = $this->db->query("SELECT * FROM audit_logs WHERE action = 'attendance.recorded'");
        $auditLogs = $auditStmt->fetchAll(PDO::FETCH_ASSOC);
        $this->assertGreaterThanOrEqual(2, count($auditLogs));
    }

    public function testHistoricalAttendanceEditRules(): void
    {
        $teacherContext = UserContext::fromUser(User::fromArray([
            'id' => 2,
            'email' => 'teacher.math@claret.edu',
            'status' => 'active',
            'roles' => ['teacher']
        ]));

        $adminContext = UserContext::fromUser(User::fromArray([
            'id' => 1,
            'email' => 'admin@claret.edu',
            'status' => 'active',
            'roles' => ['admin']
        ]));

        $pastDate = '2025-01-10'; // > 24 hours ago

        // 1. Teacher cannot mark/edit historical attendance outside grace period
        $this->expectException(AuthorizationException::class);
        $this->attendanceService->recordRoster(
            classId: 10,
            date: $pastDate,
            classSubjectId: null,
            periodNumber: null,
            records: [['student_id' => 101, 'status' => 'present']],
            user: $teacherContext
        );
    }

    public function testAdminHistoricalEditRequiresCorrectionReason(): void
    {
        $adminContext = UserContext::fromUser(User::fromArray([
            'id' => 1,
            'email' => 'admin@claret.edu',
            'status' => 'active',
            'roles' => ['admin']
        ]));

        $pastDate = '2025-01-10';

        // 1. Admin without correction reason must fail
        try {
            $this->attendanceService->recordRoster(
                classId: 10,
                date: $pastDate,
                classSubjectId: null,
                periodNumber: null,
                records: [['student_id' => 101, 'status' => 'present']],
                user: $adminContext,
                correctionReason: '' // Empty
            );
            $this->fail('Expected ValidationException for empty correction reason');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('correction_reason', $e->getErrors());
        }

        // 2. Admin with valid correction reason succeeds
        $this->attendanceService->recordRoster(
            classId: 10,
            date: $pastDate,
            classSubjectId: null,
            periodNumber: null,
            records: [['student_id' => 101, 'status' => 'excused']],
            user: $adminContext,
            correctionReason: 'Medical excuse letter submitted late'
        );

        $record = $this->attendanceRepo->findExistingRecord(101, 10, $pastDate);
        $this->assertNotNull($record);
        $this->assertSame('excused', $record->status);
        $this->assertSame('Medical excuse letter submitted late', $record->correctionReason);
    }

    public function testAnnouncementTargetingAndReadReceipts(): void
    {
        $adminContext = UserContext::fromUser(User::fromArray(['id' => 1, 'email' => 'admin@claret.edu', 'status' => 'active', 'roles' => ['admin']]));
        $teacherContext = UserContext::fromUser(User::fromArray(['id' => 2, 'email' => 'teacher.math@claret.edu', 'status' => 'active', 'roles' => ['teacher']]));
        $aliceContext = UserContext::fromUser(User::fromArray(['id' => 10, 'email' => 'alice@claret.edu', 'status' => 'active', 'roles' => ['student']]));
        $charlieContext = UserContext::fromUser(User::fromArray(['id' => 12, 'email' => 'charlie@claret.edu', 'status' => 'active', 'roles' => ['student']]));
        $parentAliceContext = UserContext::fromUser(User::fromArray(['id' => 20, 'email' => 'parent.alice@claret.edu', 'status' => 'active', 'roles' => ['parent']]));

        // 1. Admin posts School-wide announcement
        $schoolAnn = $this->announcementService->createAnnouncement([
            'title' => 'School Resumption Notice',
            'body' => 'Welcome back to the new term.',
            'scope' => 'school',
        ], $adminContext);

        // 2. Teacher posts Class 10-A announcement
        $classAnn = $this->announcementService->createAnnouncement([
            'title' => 'Grade 10-A Homework Reminder',
            'body' => 'Please bring your notebooks tomorrow.',
            'scope' => 'class',
            'scope_id' => 10,
        ], $teacherContext);

        // 3. Check Alice (Grade 10-A) feed
        $aliceFeed = $this->announcementService->getUserFeed($aliceContext, 101);
        $this->assertCount(2, $aliceFeed); // School-wide + Grade 10-A
        $this->assertSame(2, $this->announcementService->getUnreadCount($aliceContext, 101));

        // 4. Check Charlie (Grade 11-B) feed
        $charlieFeed = $this->announcementService->getUserFeed($charlieContext, 103);
        $this->assertCount(1, $charlieFeed); // Only School-wide, NOT Grade 10-A
        $this->assertSame('School Resumption Notice', $charlieFeed[0]->title);

        // 5. Check Parent of Alice feed
        $parentFeed = $this->announcementService->getUserFeed($parentAliceContext, 101);
        $this->assertCount(2, $parentFeed);

        // 6. Alice marks Class announcement as read
        $this->announcementService->markAsRead($classAnn->id, $aliceContext);
        $this->assertSame(1, $this->announcementService->getUnreadCount($aliceContext, 101));

        // Charlie cannot view or mark Alice's class announcement as read
        $this->expectException(AuthorizationException::class);
        $this->announcementService->markAsRead($classAnn->id, $charlieContext);
    }
}
