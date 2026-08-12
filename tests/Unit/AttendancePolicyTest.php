<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\UserContext;
use App\Models\AttendanceRecord;
use App\Models\User;
use App\Policies\AttendancePolicy;
use PDO;
use PHPUnit\Framework\TestCase;

class AttendancePolicyTest extends TestCase
{
    private PDO $db;
    private AttendancePolicy $policy;

    protected function setUp(): void
    {
        $this->db = new PDO('sqlite::memory:');
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Schema setup
        $this->db->exec("
            CREATE TABLE users (id INTEGER PRIMARY KEY, email TEXT, status TEXT);
            CREATE TABLE teachers (id INTEGER PRIMARY KEY, user_id INTEGER);
            CREATE TABLE students (id INTEGER PRIMARY KEY, user_id INTEGER);
            CREATE TABLE parents (id INTEGER PRIMARY KEY, user_id INTEGER);
            CREATE TABLE parent_student (id INTEGER PRIMARY KEY, parent_id INTEGER, student_id INTEGER);
            CREATE TABLE class_subjects (id INTEGER PRIMARY KEY, class_id INTEGER, subject_id INTEGER, teacher_id INTEGER);
        ");

        $this->policy = new AttendancePolicy($this->db);
    }

    public function testAdminCanAlwaysMarkAndEdit(): void
    {
        $admin = UserContext::fromUser(User::fromArray([
            'id' => 1,
            'email' => 'admin@test.com',
            'status' => 'active',
            'roles' => ['admin']
        ]));

        $this->assertTrue($this->policy->canMark($admin, 10, null));
        $this->assertTrue($this->policy->canMark($admin, 10, 5));

        $record = AttendanceRecord::fromArray([
            'id' => 1,
            'session_id' => 1,
            'term_id' => 1,
            'class_id' => 10,
            'student_id' => 1,
            'date' => '2026-01-01',
            'status' => 'present',
            'marked_by' => 1,
        ]);

        $this->assertTrue($this->policy->canEdit($admin, $record));
    }

    public function testTeacherCanMarkOnlyAllocatedClassAndSubject(): void
    {
        // Teacher user #2 -> teacher #100
        $this->db->exec("INSERT INTO teachers (id, user_id) VALUES (100, 2)");
        $this->db->exec("INSERT INTO class_subjects (id, class_id, subject_id, teacher_id) VALUES (50, 10, 1, 100)");

        $teacherContext = UserContext::fromUser(User::fromArray([
            'id' => 2,
            'email' => 'teacher@test.com',
            'status' => 'active',
            'roles' => ['teacher']
        ]));

        // Can mark allocated class
        $this->assertTrue($this->policy->canMark($teacherContext, 10, null));
        $this->assertTrue($this->policy->canMark($teacherContext, 10, 50));

        // Cannot mark unallocated class or subject
        $this->assertFalse($this->policy->canMark($teacherContext, 99, null));
        $this->assertFalse($this->policy->canMark($teacherContext, 10, 999));
    }

    public function testTeacherEditGracePeriodEnforcement(): void
    {
        $this->db->exec("INSERT INTO teachers (id, user_id) VALUES (100, 2)");
        $this->db->exec("INSERT INTO class_subjects (id, class_id, subject_id, teacher_id) VALUES (50, 10, 1, 100)");

        $teacherContext = UserContext::fromUser(User::fromArray([
            'id' => 2,
            'email' => 'teacher@test.com',
            'status' => 'active',
            'roles' => ['teacher']
        ]));

        $today = date('Y-m-d');
        $todayRecord = AttendanceRecord::fromArray([
            'id' => 1,
            'session_id' => 1,
            'term_id' => 1,
            'class_id' => 10,
            'class_subject_id' => 50,
            'student_id' => 1,
            'date' => $today,
            'status' => 'present',
            'marked_by' => 2,
        ]);

        $this->assertTrue($this->policy->canEdit($teacherContext, $todayRecord));

        // Historical record (1 month ago)
        $pastRecord = AttendanceRecord::fromArray([
            'id' => 2,
            'session_id' => 1,
            'term_id' => 1,
            'class_id' => 10,
            'class_subject_id' => 50,
            'student_id' => 1,
            'date' => '2025-01-01',
            'status' => 'present',
            'marked_by' => 2,
        ]);

        $this->assertFalse($this->policy->canEdit($teacherContext, $pastRecord));
    }

    public function testStudentAndParentVisibilityPolicy(): void
    {
        // Student #5 (user #10), Parent #8 (user #20) linked to student #5
        $this->db->exec("INSERT INTO students (id, user_id) VALUES (5, 10)");
        $this->db->exec("INSERT INTO parents (id, user_id) VALUES (8, 20)");
        $this->db->exec("INSERT INTO parent_student (parent_id, student_id) VALUES (8, 5)");

        $studentContext = UserContext::fromUser(User::fromArray([
            'id' => 10,
            'email' => 'student@test.com',
            'status' => 'active',
            'roles' => ['student']
        ]));

        $parentContext = UserContext::fromUser(User::fromArray([
            'id' => 20,
            'email' => 'parent@test.com',
            'status' => 'active',
            'roles' => ['parent']
        ]));

        $this->assertTrue($this->policy->canView($studentContext, 5, 1));
        $this->assertFalse($this->policy->canView($studentContext, 99, 1));

        $this->assertTrue($this->policy->canView($parentContext, 5, 1));
        $this->assertFalse($this->policy->canView($parentContext, 99, 1));
    }
}
