<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\UserContext;
use App\Models\Announcement;
use App\Models\User;
use App\Policies\AnnouncementPolicy;
use PDO;
use PHPUnit\Framework\TestCase;

class AnnouncementPolicyTest extends TestCase
{
    private PDO $db;
    private AnnouncementPolicy $policy;

    protected function setUp(): void
    {
        $this->db = new PDO('sqlite::memory:');
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $this->db->exec("
            CREATE TABLE users (id INTEGER PRIMARY KEY, email TEXT, status TEXT);
            CREATE TABLE teachers (id INTEGER PRIMARY KEY, user_id INTEGER);
            CREATE TABLE students (id INTEGER PRIMARY KEY, user_id INTEGER);
            CREATE TABLE class_subjects (id INTEGER PRIMARY KEY, class_id INTEGER, subject_id INTEGER, teacher_id INTEGER);
            CREATE TABLE class_enrollments (id INTEGER PRIMARY KEY, student_id INTEGER, class_id INTEGER, status TEXT);
            CREATE TABLE student_subject_enrollments (id INTEGER PRIMARY KEY, student_id INTEGER, class_subject_id INTEGER, status TEXT);
        ");

        $this->policy = new AnnouncementPolicy($this->db);
    }

    public function testAdminCanCreateAndManageAnyAnnouncement(): void
    {
        $admin = UserContext::fromUser(User::fromArray([
            'id' => 1,
            'email' => 'admin@test.com',
            'status' => 'active',
            'roles' => ['admin']
        ]));

        $this->assertTrue($this->policy->canCreate($admin, 'school', null));
        $this->assertTrue($this->policy->canCreate($admin, 'class', 10));
        $this->assertTrue($this->policy->canCreate($admin, 'class_subject', 5));

        $announcement = Announcement::fromArray([
            'id' => 1,
            'author_id' => 99,
            'scope' => 'school',
            'title' => 'Test',
            'body' => 'Body',
        ]);

        $this->assertTrue($this->policy->canManage($admin, $announcement));
    }

    public function testTeacherAnnouncementPermissions(): void
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

        // Cannot create school-wide announcement
        $this->assertFalse($this->policy->canCreate($teacherContext, 'school', null));

        // Can create for taught class and class_subject
        $this->assertTrue($this->policy->canCreate($teacherContext, 'class', 10));
        $this->assertTrue($this->policy->canCreate($teacherContext, 'class_subject', 50));

        // Cannot create for untaught class or subject
        $this->assertFalse($this->policy->canCreate($teacherContext, 'class', 99));
        $this->assertFalse($this->policy->canCreate($teacherContext, 'class_subject', 999));

        // Can manage own announcement
        $ownAnnouncement = Announcement::fromArray([
            'id' => 10,
            'author_id' => 2,
            'scope' => 'class',
            'scope_id' => 10,
        ]);
        $this->assertTrue($this->policy->canManage($teacherContext, $ownAnnouncement));

        // Cannot manage another teacher's announcement
        $otherAnnouncement = Announcement::fromArray([
            'id' => 11,
            'author_id' => 99,
            'scope' => 'class',
            'scope_id' => 10,
        ]);
        $this->assertFalse($this->policy->canManage($teacherContext, $otherAnnouncement));
    }
}
