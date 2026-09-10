<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Core\Config;
use App\Models\LiveClass;
use App\Repositories\AcademicRepository;
use App\Repositories\EnrollmentRepository;
use App\Repositories\LiveClassRepository;
use App\Repositories\ParentRepository;
use App\Repositories\StudentRepository;
use App\Repositories\TeacherRepository;
use App\Services\LiveClassService;
use PDO;
use PHPUnit\Framework\TestCase;

class LiveClassIntegrationTest extends TestCase
{
    private PDO $db;
    private LiveClassRepository $liveClassRepo;
    private TeacherRepository $teacherRepo;
    private StudentRepository $studentRepo;
    private ParentRepository $parentRepo;
    private AcademicRepository $academicRepo;
    private EnrollmentRepository $enrollmentRepo;
    private LiveClassService $liveClassService;

    private int $teacherUserId = 10;
    private int $otherTeacherUserId = 11;
    private int $studentUserId = 20;
    private int $unauthorizedStudentUserId = 21;
    private int $parentUserId = 30;
    private int $adminUserId = 1;

    private int $sessionId = 1;
    private int $termId = 1;
    private int $classId = 1;
    private int $subjectId = 1;
    private int $classSubjectId = 1;

    protected function setUp(): void
    {
        Config::reset();
        Config::load(dirname(__DIR__, 2) . '/config/.env');

        $this->db = new PDO('sqlite::memory:');
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // SQLite schema setup
        $this->db->exec("
            CREATE TABLE users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                uuid TEXT DEFAULT 'test-uuid',
                name TEXT NOT NULL,
                email TEXT NOT NULL,
                phone TEXT NULL,
                status TEXT NOT NULL DEFAULT 'active',
                must_change_password INTEGER DEFAULT 0,
                created_at TEXT DEFAULT CURRENT_TIMESTAMP,
                updated_at TEXT DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE user_roles (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                role TEXT NOT NULL
            );

            CREATE TABLE sessions (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                start_date TEXT NOT NULL,
                end_date TEXT NOT NULL,
                status TEXT NOT NULL DEFAULT 'active',
                created_at TEXT DEFAULT CURRENT_TIMESTAMP,
                updated_at TEXT DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE terms (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                session_id INTEGER NOT NULL,
                name TEXT NOT NULL,
                start_date TEXT NOT NULL,
                end_date TEXT NOT NULL,
                status TEXT NOT NULL DEFAULT 'active',
                created_at TEXT DEFAULT CURRENT_TIMESTAMP,
                updated_at TEXT DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE academic_levels (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                code TEXT NOT NULL,
                rank_order INTEGER NOT NULL DEFAULT 1,
                created_at TEXT DEFAULT CURRENT_TIMESTAMP,
                updated_at TEXT DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE classes (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                academic_level_id INTEGER NOT NULL,
                name TEXT NOT NULL,
                section_arm TEXT NULL,
                status TEXT NOT NULL DEFAULT 'active',
                created_at TEXT DEFAULT CURRENT_TIMESTAMP,
                updated_at TEXT DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE subjects (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                code TEXT NOT NULL,
                status TEXT NOT NULL DEFAULT 'active',
                created_at TEXT DEFAULT CURRENT_TIMESTAMP,
                updated_at TEXT DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE teachers (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL UNIQUE,
                staff_id TEXT NOT NULL UNIQUE,
                created_at TEXT DEFAULT CURRENT_TIMESTAMP,
                updated_at TEXT DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE class_subjects (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                session_id INTEGER NOT NULL,
                class_id INTEGER NOT NULL,
                subject_id INTEGER NOT NULL,
                teacher_id INTEGER NOT NULL,
                status TEXT NOT NULL DEFAULT 'active',
                created_at TEXT DEFAULT CURRENT_TIMESTAMP,
                updated_at TEXT DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE students (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL UNIQUE,
                admission_number TEXT NOT NULL UNIQUE,
                date_of_birth TEXT NULL,
                gender TEXT NULL,
                current_class_id INTEGER NULL,
                created_at TEXT DEFAULT CURRENT_TIMESTAMP,
                updated_at TEXT DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE class_enrollments (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                student_id INTEGER NOT NULL,
                class_id INTEGER NOT NULL,
                session_id INTEGER NOT NULL,
                status TEXT NOT NULL DEFAULT 'active',
                enrolled_at TEXT DEFAULT CURRENT_TIMESTAMP,
                created_at TEXT DEFAULT CURRENT_TIMESTAMP,
                updated_at TEXT DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE parents (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL UNIQUE,
                created_at TEXT DEFAULT CURRENT_TIMESTAMP,
                updated_at TEXT DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE parent_student (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                parent_id INTEGER NOT NULL,
                student_id INTEGER NOT NULL,
                relationship_type TEXT DEFAULT 'parent',
                created_at TEXT DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE live_classes (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                session_id INTEGER NULL,
                term_id INTEGER NULL,
                class_subject_id INTEGER NOT NULL,
                teacher_id INTEGER NOT NULL,
                title TEXT NOT NULL,
                description TEXT NULL,
                platform TEXT NOT NULL DEFAULT 'google_meet',
                meeting_link TEXT NOT NULL,
                meeting_passcode TEXT NULL,
                scheduled_date TEXT NOT NULL,
                start_time TEXT NOT NULL,
                duration_minutes INTEGER NOT NULL DEFAULT 40,
                status TEXT NOT NULL DEFAULT 'scheduled',
                is_published INTEGER NOT NULL DEFAULT 1,
                created_at TEXT DEFAULT CURRENT_TIMESTAMP,
                updated_at TEXT DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE live_class_attendees (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                live_class_id INTEGER NOT NULL,
                student_id INTEGER NOT NULL,
                joined_at TEXT DEFAULT CURRENT_TIMESTAMP,
                last_seen_at TEXT NULL,
                ip_address TEXT NULL,
                user_agent TEXT NULL,
                UNIQUE (live_class_id, student_id)
            );
        ");

        // Seed Users
        $this->db->exec("
            INSERT INTO users (id, name, email) VALUES 
                (1, 'System Administrator', 'admin@claret.edu.ng'),
                (10, 'Dr. Emily Stones', 'teacher@claret.edu.ng'),
                (11, 'Mr. John Colleague', 'colleague@claret.edu.ng'),
                (20, 'Tobi Student', 'tobi@claret.edu.ng'),
                (21, 'Other Student', 'other@claret.edu.ng'),
                (30, 'Chief Parent', 'parent@claret.edu.ng');

            INSERT INTO user_roles (user_id, role) VALUES 
                (1, 'admin'),
                (10, 'teacher'),
                (11, 'teacher'),
                (20, 'student'),
                (21, 'student'),
                (30, 'parent');

            INSERT INTO sessions (id, name, start_date, end_date, status) VALUES 
                (1, '2026/2027 Academic Session', '2026-09-01', '2027-07-31', 'active');

            INSERT INTO terms (id, session_id, name, start_date, end_date, status) VALUES 
                (1, 1, 'First Term', '2026-09-01', '2026-12-18', 'active');

            INSERT INTO academic_levels (id, name, code, rank_order) VALUES 
                (1, 'Senior Secondary 1', 'SS1', 10);

            INSERT INTO classes (id, academic_level_id, name, section_arm) VALUES 
                (1, 1, 'SS1 Gold', 'Gold'),
                (2, 1, 'SS1 Silver', 'Silver');

            INSERT INTO subjects (id, name, code) VALUES 
                (1, 'Further Mathematics', 'FMA');

            INSERT INTO teachers (id, user_id, staff_id) VALUES 
                (1, 10, 'STF-0010'),
                (2, 11, 'STF-0011');

            INSERT INTO class_subjects (id, session_id, class_id, subject_id, teacher_id) VALUES 
                (1, 1, 1, 1, 1);

            INSERT INTO students (id, user_id, admission_number, current_class_id) VALUES 
                (1, 20, 'CLARET/2026/001', 1),
                (2, 21, 'CLARET/2026/002', 2);

            INSERT INTO class_enrollments (student_id, class_id, session_id, status) VALUES 
                (1, 1, 1, 'active'),
                (2, 2, 1, 'active');

            INSERT INTO parents (id, user_id) VALUES 
                (1, 30);

            INSERT INTO parent_student (parent_id, student_id, relationship_type) VALUES 
                (1, 1, 'father');
        ");

        $this->liveClassRepo = new LiveClassRepository($this->db);
        $this->teacherRepo = new TeacherRepository($this->db);
        $this->studentRepo = new StudentRepository($this->db);
        $this->parentRepo = new ParentRepository($this->db);
        $this->academicRepo = new AcademicRepository($this->db);
        $this->enrollmentRepo = new EnrollmentRepository($this->db);

        $this->liveClassService = new LiveClassService(
            liveClassRepository: $this->liveClassRepo,
            teacherRepository: $this->teacherRepo,
            studentRepository: $this->studentRepo,
            parentRepository: $this->parentRepo,
            academicRepository: $this->academicRepo,
            enrollmentRepository: $this->enrollmentRepo
        );
    }

    public function testTeacherCanScheduleLiveClassWithPlatformAutoDetection(): void
    {
        $result = $this->liveClassService->createLiveClass($this->teacherUserId, [
            'class_subject_id' => $this->classSubjectId,
            'title' => 'Advanced Integration & Calculus',
            'description' => 'Preparation for mid-term CBT assessment',
            'meeting_link' => 'https://meet.google.com/xyz-abcd-efg',
            'meeting_passcode' => 'CLARET123',
            'scheduled_date' => '2026-09-12',
            'start_time' => '10:00',
            'duration_minutes' => 45,
        ]);

        $this->assertTrue($result->isSuccess());
        $liveClass = $result->data;
        $this->assertInstanceOf(LiveClass::class, $liveClass);
        $this->assertSame('google_meet', $liveClass->platform);
        $this->assertSame('Advanced Integration & Calculus', $liveClass->title);
        $this->assertSame(LiveClass::STATUS_SCHEDULED, $liveClass->status);
        $this->assertSame(45, $liveClass->durationMinutes);
    }

    public function testSchedulingRejectsNonHttpsMeetingUrls(): void
    {
        $result = $this->liveClassService->createLiveClass($this->teacherUserId, [
            'class_subject_id' => $this->classSubjectId,
            'title' => 'Insecure Room Test',
            'meeting_link' => 'http://insecure.example.com/room',
            'scheduled_date' => '2026-09-12',
            'start_time' => '10:00',
        ]);

        $this->assertFalse($result->isSuccess());
        $this->assertArrayHasKey('meeting_link', $result->errors);
    }

    public function testUnauthorizedTeacherCannotScheduleForAnotherTeacherSubject(): void
    {
        $result = $this->liveClassService->createLiveClass($this->otherTeacherUserId, [
            'class_subject_id' => $this->classSubjectId,
            'title' => 'Hijack Class Attempt',
            'meeting_link' => 'https://zoom.us/j/1234567890',
            'scheduled_date' => '2026-09-12',
            'start_time' => '11:00',
        ]);

        $this->assertFalse($result->isSuccess());
        $this->assertSame('UNAUTHORIZED_TEACHER', $result->errorCode);
    }

    public function testTeacherCanTransitionClassStateFromScheduledToInProgressAndCompleted(): void
    {
        $createResult = $this->liveClassService->createLiveClass($this->teacherUserId, [
            'class_subject_id' => $this->classSubjectId,
            'title' => 'State Lifecycle Test',
            'meeting_link' => 'https://teams.microsoft.com/l/meetup-join/12345',
            'scheduled_date' => '2026-09-10',
            'start_time' => '09:00',
            'duration_minutes' => 60,
        ]);

        $classId = $createResult->data->id;

        // 1. Start Class (Go Live)
        $startResult = $this->liveClassService->startLiveClass($this->teacherUserId, $classId);
        $this->assertTrue($startResult->isSuccess());
        $this->assertSame(LiveClass::STATUS_IN_PROGRESS, $startResult->data->status);
        $this->assertTrue($startResult->data->isInProgress());
        $this->assertTrue($startResult->data->isJoinable());

        // 2. Conclude Class
        $endResult = $this->liveClassService->endLiveClass($this->teacherUserId, $classId);
        $this->assertTrue($endResult->isSuccess());
        $this->assertSame(LiveClass::STATUS_COMPLETED, $endResult->data->status);
        $this->assertTrue($endResult->data->isCompleted());
        $this->assertFalse($endResult->data->isJoinable());
    }

    public function testEnrolledStudentJoiningLiveClassRecordsAttendanceAndReturnsMeetingLink(): void
    {
        $createResult = $this->liveClassService->createLiveClass($this->teacherUserId, [
            'class_subject_id' => $this->classSubjectId,
            'title' => 'Synchronous Attendance Test',
            'meeting_link' => 'https://meet.google.com/abc-defg-hij',
            'meeting_passcode' => '998877',
            'scheduled_date' => date('Y-m-d'),
            'start_time' => date('H:i:s'),
            'duration_minutes' => 40,
        ]);

        $classId = $createResult->data->id;
        $this->liveClassService->startLiveClass($this->teacherUserId, $classId);

        // Student joins
        $joinResult = $this->liveClassService->joinLiveClass(
            studentUserId: $this->studentUserId,
            liveClassId: $classId,
            ipAddress: '197.210.65.12',
            userAgent: 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)'
        );

        $this->assertTrue($joinResult->isSuccess());
        $this->assertSame('https://meet.google.com/abc-defg-hij', $joinResult->data['meeting_link']);
        $this->assertSame('998877', $joinResult->data['meeting_passcode']);

        // Verify Attendance Record in DB
        $attendeesResult = $this->liveClassService->getLiveClassAttendees($classId, $this->teacherUserId);
        $this->assertTrue($attendeesResult->isSuccess());
        $attendees = $attendeesResult->data['attendees'];

        $this->assertCount(1, $attendees);
        $this->assertSame('Tobi Student', $attendees[0]->studentName);
        $this->assertSame('CLARET/2026/001', $attendees[0]->admissionNumber);
        $this->assertSame('197.210.65.12', $attendees[0]->ipAddress);

        // Re-joining updates last_seen_at without duplicate record
        $rejoinResult = $this->liveClassService->joinLiveClass(
            studentUserId: $this->studentUserId,
            liveClassId: $classId,
            ipAddress: '197.210.65.12',
            userAgent: 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)'
        );
        $this->assertTrue($rejoinResult->isSuccess());

        $attendeesAfterRejoin = $this->liveClassService->getLiveClassAttendees($classId, $this->teacherUserId)->data['attendees'];
        $this->assertCount(1, $attendeesAfterRejoin);
    }

    public function testStudentCannotJoinCancelledClass(): void
    {
        $createResult = $this->liveClassService->createLiveClass($this->teacherUserId, [
            'class_subject_id' => $this->classSubjectId,
            'title' => 'Cancelled Session Test',
            'meeting_link' => 'https://meet.google.com/canc-elle-dtst',
            'scheduled_date' => date('Y-m-d'),
            'start_time' => '14:00',
        ]);

        $classId = $createResult->data->id;
        $this->liveClassService->cancelLiveClass($this->teacherUserId, $classId);

        $joinResult = $this->liveClassService->joinLiveClass($this->studentUserId, $classId);
        $this->assertFalse($joinResult->isSuccess());
        $this->assertSame('CLASS_CANCELLED', $joinResult->errorCode);
    }

    public function testStudentCohortAccessIsolation(): void
    {
        $createResult = $this->liveClassService->createLiveClass($this->teacherUserId, [
            'class_subject_id' => $this->classSubjectId, // Class 1 (SS1 Gold)
            'title' => 'SS1 Gold Exclusive Lecture',
            'meeting_link' => 'https://meet.google.com/gold-cohort-only',
            'scheduled_date' => date('Y-m-d'),
            'start_time' => '10:00',
        ]);

        $classId = $createResult->data->id;

        // Student 1 (in Class 1) can see the class
        $student1Classes = $this->liveClassService->getStudentLiveClasses($this->studentUserId)->data;
        $this->assertCount(1, $student1Classes);

        // Student 2 (in Class 2 - SS1 Silver) cannot see the class
        $student2Classes = $this->liveClassService->getStudentLiveClasses($this->unauthorizedStudentUserId)->data;
        $this->assertCount(0, $student2Classes);
    }

    public function testParentCanViewLinkedWardLiveClasses(): void
    {
        $this->liveClassService->createLiveClass($this->teacherUserId, [
            'class_subject_id' => $this->classSubjectId,
            'title' => 'Parent Visible Class',
            'meeting_link' => 'https://meet.google.com/par-enti-view',
            'scheduled_date' => date('Y-m-d'),
            'start_time' => '12:00',
        ]);

        $parentData = $this->liveClassService->getParentLiveClasses($this->parentUserId)->data;
        $this->assertCount(1, $parentData);
        $this->assertSame('Tobi Student', $parentData[0]['student']['student_name']);
        $this->assertCount(1, $parentData[0]['live_classes']);
        $this->assertSame('Parent Visible Class', $parentData[0]['live_classes'][0]->title);
    }

    public function testAdminCanAuditAndModerateLiveClasses(): void
    {
        $createResult = $this->liveClassService->createLiveClass($this->teacherUserId, [
            'class_subject_id' => $this->classSubjectId,
            'title' => 'Admin Monitored Class',
            'meeting_link' => 'https://zoom.us/j/9988776655',
            'scheduled_date' => date('Y-m-d'),
            'start_time' => '15:00',
        ]);

        $classId = $createResult->data->id;

        // Admin gets directory
        $adminResult = $this->liveClassService->getAdminLiveClasses();
        $this->assertTrue($adminResult->isSuccess());
        $this->assertGreaterThanOrEqual(1, $adminResult->data['total']);

        // Admin cancels class
        $cancelResult = $this->liveClassService->cancelLiveClass($this->adminUserId, $classId, true);
        $this->assertTrue($cancelResult->isSuccess());
        $this->assertSame(LiveClass::STATUS_CANCELLED, $cancelResult->data->status);

        // Admin views attendee audit log
        $attendeeAudit = $this->liveClassService->getLiveClassAttendees($classId, $this->adminUserId, true);
        $this->assertTrue($attendeeAudit->isSuccess());
    }
}
