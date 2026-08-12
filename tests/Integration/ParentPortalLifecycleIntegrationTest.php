<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Controllers\Parent\ChildController;
use App\Controllers\Parent\DashboardController;
use App\Controllers\Parent\ReportCardController;
use App\Core\AuthenticatorInterface;
use App\Core\Exceptions\AuthorizationException;
use App\Core\Exceptions\ResourceNotFoundException;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\UserContext;
use App\Models\User;
use App\Policies\ParentPolicy;
use App\Repositories\AcademicRepository;
use App\Repositories\AnnouncementRepository;
use App\Repositories\AssignmentRepository;
use App\Repositories\AttendanceRepository;
use App\Repositories\EnrollmentRepository;
use App\Repositories\GradebookRepository;
use App\Repositories\ParentRepository;
use App\Repositories\ResultPublicationRepository;
use App\Repositories\StudentRepository;
use App\Services\AnnouncementService;
use App\Services\AssignmentService;
use App\Services\ParentService;
use App\Services\ReportCardService;
use PDO;
use PHPUnit\Framework\TestCase;

class ParentPortalLifecycleIntegrationTest extends TestCase
{
    private PDO $db;
    private ParentRepository $parentRepo;
    private StudentRepository $studentRepo;
    private AcademicRepository $academicRepo;
    private EnrollmentRepository $enrollmentRepo;
    private AttendanceRepository $attendanceRepo;
    private AnnouncementRepository $announcementRepo;
    private AnnouncementService $announcementService;
    private GradebookRepository $gradebookRepo;
    private ResultPublicationRepository $publicationRepo;
    private AssignmentRepository $assignmentRepo;
    private AssignmentService $assignmentService;
    private ParentService $parentService;

    // Test Entities
    private int $parentUser1Id;
    private int $parentProfile1Id;
    private int $parentUser2Id;
    private int $parentProfile2Id;
    private int $student1Id;
    private int $student2Id;
    private int $student3Id;
    private int $sessionId;
    private int $termId;
    private int $classId;

    protected function setUp(): void
    {
        $this->db = new PDO('sqlite::memory:');
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Build comprehensive schema for SQLite
        $this->db->exec("
            CREATE TABLE users (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, email TEXT, status TEXT, phone TEXT, password_hash TEXT, must_change_password INTEGER DEFAULT 0, created_at TEXT, updated_at TEXT);
            CREATE TABLE parents (id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER, occupation TEXT, address TEXT, created_at TEXT, updated_at TEXT);
            CREATE TABLE students (id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER, admission_number TEXT, date_of_birth TEXT, gender TEXT, current_class_id INTEGER, created_at TEXT, updated_at TEXT);
            CREATE TABLE parent_student (id INTEGER PRIMARY KEY AUTOINCREMENT, parent_id INTEGER, student_id INTEGER, relationship_type TEXT DEFAULT 'guardian', is_primary INTEGER DEFAULT 1, created_at TEXT);
            
            CREATE TABLE sessions (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, start_date TEXT, end_date TEXT, status TEXT DEFAULT 'active', is_current INTEGER DEFAULT 0);
            CREATE TABLE terms (id INTEGER PRIMARY KEY AUTOINCREMENT, session_id INTEGER, name TEXT, start_date TEXT, end_date TEXT, status TEXT DEFAULT 'active', is_current INTEGER DEFAULT 0);
            CREATE TABLE classes (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, section_arm TEXT, academic_level_id INTEGER, status TEXT DEFAULT 'active');
            CREATE TABLE subjects (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, code TEXT, description TEXT);
            CREATE TABLE teachers (id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER, staff_id TEXT, staff_no TEXT);
            CREATE TABLE class_subjects (id INTEGER PRIMARY KEY AUTOINCREMENT, session_id INTEGER, class_id INTEGER, subject_id INTEGER, teacher_id INTEGER, status TEXT DEFAULT 'active');

            CREATE TABLE class_enrollments (id INTEGER PRIMARY KEY AUTOINCREMENT, session_id INTEGER, class_id INTEGER, student_id INTEGER, status TEXT DEFAULT 'active', enrolled_at TEXT, created_at TEXT, updated_at TEXT);
            CREATE TABLE student_subject_enrollments (id INTEGER PRIMARY KEY AUTOINCREMENT, session_id INTEGER, class_subject_id INTEGER, student_id INTEGER, is_elective INTEGER DEFAULT 0, status TEXT DEFAULT 'active', created_at TEXT, updated_at TEXT);
            
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

            CREATE TABLE result_publications (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                term_id INTEGER NOT NULL,
                class_id INTEGER NULL,
                academic_level_id INTEGER NULL,
                is_published INTEGER NOT NULL DEFAULT 0,
                status TEXT DEFAULT 'unpublished',
                published_at TEXT NULL,
                unpublished_at TEXT NULL,
                published_by INTEGER NULL,
                reason TEXT NULL
            );

            CREATE TABLE student_term_summaries (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                student_id INTEGER NOT NULL,
                term_id INTEGER NOT NULL,
                total_score REAL DEFAULT 0,
                average_score REAL DEFAULT 0,
                gpa REAL NULL,
                rank_in_class INTEGER NULL,
                total_students INTEGER NULL,
                attendance_present_count INTEGER DEFAULT 0,
                attendance_total_count INTEGER DEFAULT 0,
                status TEXT DEFAULT 'draft',
                is_locked INTEGER DEFAULT 0,
                created_at TEXT,
                updated_at TEXT
            );

            CREATE TABLE student_term_results (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                student_id INTEGER NOT NULL,
                term_id INTEGER NOT NULL,
                class_subject_id INTEGER NOT NULL,
                computed_score REAL NOT NULL DEFAULT 0,
                grade_letter TEXT NOT NULL,
                grade_point REAL DEFAULT 0,
                remark TEXT NULL,
                created_at TEXT,
                updated_at TEXT
            );

            CREATE TABLE files (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                uuid TEXT,
                storage_key TEXT,
                original_name TEXT,
                mime_type TEXT,
                size_bytes INTEGER,
                sha256 TEXT,
                uploaded_by INTEGER,
                owner_type TEXT,
                owner_id INTEGER,
                created_at TEXT,
                updated_at TEXT,
                deleted_at TEXT
            );

            CREATE TABLE announcements (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                author_id INTEGER NOT NULL,
                title TEXT NOT NULL,
                body TEXT NOT NULL,
                scope TEXT NOT NULL,
                scope_id INTEGER NULL,
                target_role TEXT NULL,
                target_class_id INTEGER NULL,
                target_subject_id INTEGER NULL,
                is_published INTEGER NOT NULL DEFAULT 1,
                published_at TEXT,
                expires_at TEXT,
                created_at TEXT,
                updated_at TEXT
            );

            CREATE TABLE announcement_reads (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                announcement_id INTEGER NOT NULL,
                user_id INTEGER NOT NULL,
                read_at TEXT NOT NULL
            );

            CREATE TABLE assignments (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                class_subject_id INTEGER NOT NULL,
                teacher_id INTEGER NOT NULL,
                term_id INTEGER NOT NULL,
                title TEXT NOT NULL,
                description TEXT NULL,
                topic TEXT NULL,
                file_id INTEGER NULL,
                max_score REAL NOT NULL DEFAULT 100,
                due_at TEXT NOT NULL,
                status TEXT NOT NULL DEFAULT 'published',
                created_at TEXT,
                updated_at TEXT
            );

            CREATE TABLE assignment_submissions (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                assignment_id INTEGER NOT NULL,
                student_id INTEGER NOT NULL,
                file_id INTEGER NULL,
                student_notes TEXT NULL,
                submitted_at TEXT NOT NULL,
                score REAL NULL,
                feedback TEXT NULL,
                teacher_comment TEXT NULL,
                graded_by INTEGER NULL,
                graded_at TEXT NULL,
                status TEXT NOT NULL DEFAULT 'submitted',
                created_at TEXT,
                updated_at TEXT
            );
        ");

        // Seed Core Setup
        $this->db->exec("
            INSERT INTO sessions (id, name, start_date, end_date, status, is_current) VALUES (1, '2025/2026', '2025-09-01', '2026-07-31', 'active', 1);
            INSERT INTO terms (id, session_id, name, start_date, end_date, status, is_current) VALUES (1, 1, 'First Term', '2025-09-01', '2025-12-15', 'active', 1);
            INSERT INTO classes (id, name, section_arm, academic_level_id, status) VALUES (1, 'JSS 1 Gold', 'Gold', 1, 'active');
            INSERT INTO subjects (id, name, code) VALUES (1, 'Mathematics', 'MTH101'), (2, 'English Language', 'ENG101');
            INSERT INTO teachers (id, user_id, staff_id, staff_no) VALUES (1, 99, 'TCH001', 'TCH001');
            INSERT INTO class_subjects (id, session_id, class_id, subject_id, teacher_id) VALUES (1, 1, 1, 1, 1), (2, 1, 1, 2, 1);
        ");

        // Seed Parents and Students
        $this->db->exec("
            INSERT INTO users (id, name, email, status) VALUES (99, 'Teacher Smith', 'teacher.smith@claret.edu', 'active');
            INSERT INTO users (id, name, email, status) VALUES (10, 'Parent John', 'john.parent@claret.edu', 'active');
            INSERT INTO parents (id, user_id) VALUES (1, 10);

            INSERT INTO users (id, name, email, status) VALUES (20, 'Parent Jane', 'jane.parent@claret.edu', 'active');
            INSERT INTO parents (id, user_id) VALUES (2, 20);

            INSERT INTO users (id, name, email, status) VALUES (101, 'Student Alice', 'alice@claret.edu', 'active');
            INSERT INTO students (id, user_id, admission_number, current_class_id) VALUES (1, 101, 'ADM001', 1);

            INSERT INTO users (id, name, email, status) VALUES (102, 'Student Bob', 'bob@claret.edu', 'active');
            INSERT INTO students (id, user_id, admission_number, current_class_id) VALUES (2, 102, 'ADM002', 1);

            INSERT INTO users (id, name, email, status) VALUES (103, 'Student Charlie', 'charlie@claret.edu', 'active');
            INSERT INTO students (id, user_id, admission_number, current_class_id) VALUES (3, 103, 'ADM003', 1);

            -- Parent 1 is linked to Student 1 (Alice) and Student 2 (Bob)
            INSERT INTO parent_student (parent_id, student_id, relationship_type) VALUES (1, 1, 'father'), (1, 2, 'father');

            -- Parent 2 is linked to Student 3 (Charlie) ONLY
            INSERT INTO parent_student (parent_id, student_id, relationship_type) VALUES (2, 3, 'mother');

            -- Enrollments
            INSERT INTO class_enrollments (session_id, class_id, student_id) VALUES (1, 1, 1), (1, 1, 2), (1, 1, 3);
            INSERT INTO student_subject_enrollments (session_id, class_subject_id, student_id) VALUES (1, 1, 1), (1, 2, 1), (1, 1, 2), (1, 2, 2), (1, 1, 3);

            -- Attendance Records for Student 1
            INSERT INTO attendance_records (session_id, term_id, class_id, student_id, date, status, marked_by)
            VALUES (1, 1, 1, 1, '2025-10-01', 'present', 99),
                   (1, 1, 1, 1, '2025-10-02', 'present', 99),
                   (1, 1, 1, 1, '2025-10-03', 'absent', 99);

            -- Assignments & Submissions
            INSERT INTO assignments (id, class_subject_id, teacher_id, term_id, title, due_at, max_score)
            VALUES (1, 1, 1, 1, 'Math Homework 1', '2025-10-10 12:00:00', 100);

            INSERT INTO assignment_submissions (assignment_id, student_id, submitted_at, score, teacher_comment)
            VALUES (1, 1, '2025-10-09 10:00:00', 95.0, 'Excellent work!');
        ");

        $this->parentUser1Id = 10;
        $this->parentProfile1Id = 1;
        $this->parentUser2Id = 20;
        $this->parentProfile2Id = 2;
        $this->student1Id = 1;
        $this->student2Id = 2;
        $this->student3Id = 3;
        $this->sessionId = 1;
        $this->termId = 1;
        $this->classId = 1;

        // Instantiate Repositories
        $this->parentRepo = new ParentRepository($this->db);
        $this->studentRepo = new StudentRepository($this->db);
        $this->academicRepo = new AcademicRepository($this->db);
        $this->enrollmentRepo = new EnrollmentRepository($this->db);
        $this->attendanceRepo = new AttendanceRepository($this->db);
        $this->announcementRepo = new AnnouncementRepository($this->db);
        $this->gradebookRepo = new GradebookRepository($this->db);
        $this->publicationRepo = new ResultPublicationRepository($this->db);
        $this->assignmentRepo = new AssignmentRepository($this->db);

        $this->announcementService = new AnnouncementService($this->announcementRepo, new \App\Policies\AnnouncementPolicy($this->db), null, $this->academicRepo, $this->db);
        $this->assignmentService = new AssignmentService(
            $this->assignmentRepo,
            $this->academicRepo,
            new \App\Repositories\TeacherRepository($this->db),
            $this->studentRepo,
            $this->enrollmentRepo,
            $this->parentRepo
        );

        $this->parentService = new ParentService(
            $this->parentRepo,
            $this->studentRepo,
            $this->academicRepo,
            $this->enrollmentRepo,
            $this->attendanceRepo,
            $this->announcementRepo,
            $this->announcementService,
            $this->gradebookRepo,
            $this->publicationRepo,
            $this->assignmentRepo,
            $this->assignmentService
        );
    }

    private function createActor(int $userId, string $role = 'parent'): UserContext
    {
        return new UserContext(
            id: $userId,
            uuid: "uuid-{$userId}",
            name: "User {$userId}",
            email: "user{$userId}@claret.edu",
            roles: [$role],
            mustChangePassword: false
        );
    }

    /**
     * Test 1: ParentPolicy predicates enforce strict parent-child authorization and publication gating
     */
    public function testParentPolicyAuthorizationRules(): void
    {
        $parent1 = $this->createActor($this->parentUser1Id, 'parent');
        $parent2 = $this->createActor($this->parentUser2Id, 'parent');
        $nonParent = $this->createActor(999, 'student');

        // Can view linked child
        $this->assertTrue(ParentPolicy::canViewStudent($parent1, $this->student1Id, $this->parentRepo));
        $this->assertTrue(ParentPolicy::canViewStudent($parent1, $this->student2Id, $this->parentRepo));
        
        // Cannot view unlinked child
        $this->assertFalse(ParentPolicy::canViewStudent($parent1, $this->student3Id, $this->parentRepo));
        $this->assertFalse(ParentPolicy::canViewStudent($parent2, $this->student1Id, $this->parentRepo));
        
        // Non-parent cannot view
        $this->assertFalse(ParentPolicy::canViewStudent($nonParent, $this->student1Id, $this->parentRepo));

        // Report Card gating: unpublished vs published
        $this->assertFalse(ParentPolicy::canViewReportCard($parent1, $this->student1Id, $this->termId, $this->parentRepo, $this->publicationRepo));
        
        // Publish results
        $this->publicationRepo->publish($this->termId, 1, 99);
        $this->assertTrue(ParentPolicy::canViewReportCard($parent1, $this->student1Id, $this->termId, $this->parentRepo, $this->publicationRepo));
        
        // Even when published, unlinked parent CANNOT view
        $this->assertFalse(ParentPolicy::canViewReportCard($parent2, $this->student1Id, $this->termId, $this->parentRepo, $this->publicationRepo));

        // Attendance & Coursework policy
        $this->assertTrue(ParentPolicy::canViewAttendance($parent1, $this->student1Id, $this->parentRepo));
        $this->assertFalse(ParentPolicy::canViewAttendance($parent1, $this->student3Id, $this->parentRepo));

        $this->assertTrue(ParentPolicy::canViewCoursework($parent1, $this->student1Id, $this->parentRepo));
        $this->assertFalse(ParentPolicy::canViewCoursework($parent1, $this->student3Id, $this->parentRepo));
    }

    /**
     * Test 2: ParentService aggregates dashboard data and child summaries correctly
     */
    public function testParentServiceDashboardDataAggregation(): void
    {
        $parent1 = $this->createActor($this->parentUser1Id, 'parent');
        $dashboardData = $this->parentService->getDashboardData($parent1);

        $this->assertNotNull($dashboardData['parent']);
        $this->assertCount(2, $dashboardData['children']);
        $this->assertEquals($this->student1Id, $dashboardData['selectedChild']->id);
        
        $summaries = $dashboardData['childrenSummaries'];
        $this->assertArrayHasKey($this->student1Id, $summaries);
        $this->assertArrayHasKey($this->student2Id, $summaries);

        // Verify Attendance Calculation
        $aliceSummary = $summaries[$this->student1Id];
        $this->assertNotNull($aliceSummary['attendanceSummary']);
        $this->assertEquals(2, $aliceSummary['attendanceSummary']['present_days']);
        $this->assertEquals(3, $aliceSummary['attendanceSummary']['total_days']);

        // Verify Coursework
        $this->assertNotEmpty($aliceSummary['recentAssignments']);
        $this->assertEquals('Math Homework 1', $aliceSummary['recentAssignments'][0]['assignment']->title);
        $this->assertEquals(95.0, $aliceSummary['recentAssignments'][0]['submission']->score);

        // Verify Unpublished Results are sealed
        $this->assertFalse($aliceSummary['isResultPublished']);
        $this->assertNull($aliceSummary['termSummary']);
    }

    /**
     * Test 3: Parent cannot access unlinked child overview (Throws AuthorizationException)
     */
    public function testParentCannotAccessUnlinkedChildOverview(): void
    {
        $parent1 = $this->createActor($this->parentUser1Id, 'parent');

        $this->expectException(AuthorizationException::class);
        $this->parentService->getChildOverview($parent1, $this->student3Id);
    }

    /**
     * Test 4: Child switching endpoint validates ownership and updates session
     */
    public function testChildControllerSwitching(): void
    {
        $parent1 = $this->createActor($this->parentUser1Id, 'parent');
        
        $mockAuth = $this->createMock(AuthenticatorInterface::class);
        $mockAuth->method('user')->willReturn($parent1);

        $controller = new ChildController($mockAuth, $this->parentService);

        // 1. Switch to linked child (Student 2)
        $req = new Request(
            queryParams: [],
            postParams: ['redirect_to' => '/parent/dashboard'],
            serverParams: ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/parent/children/2/select']
        );
        $req->setAttribute('studentId', (string)$this->student2Id);
        $response = $controller->select($req);
        
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('/parent/dashboard', $response->getHeader('Location'));
        $this->assertEquals($this->student2Id, Session::get('_selected_child_id'));

        // 2. Attempt to switch to unlinked child (Student 3) -> 403 Forbidden
        $unlinkedReq = new Request(
            queryParams: [],
            postParams: [],
            serverParams: ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/parent/children/3/select']
        );
        $unlinkedReq->setAttribute('studentId', (string)$this->student3Id);
        $forbiddenResponse = $controller->select($unlinkedReq);

        $this->assertEquals(403, $forbiddenResponse->getStatusCode());
    }

    /**
     * Test 5: Single Child Profile Overview rendering
     */
    public function testSingleChildProfileOverview(): void
    {
        $parent1 = $this->createActor($this->parentUser1Id, 'parent');
        
        $mockAuth = $this->createMock(AuthenticatorInterface::class);
        $mockAuth->method('user')->willReturn($parent1);

        $controller = new ChildController($mockAuth, $this->parentService);

        // View linked child
        $req = new Request(
            queryParams: [],
            postParams: [],
            serverParams: ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/parent/children/1']
        );
        $req->setAttribute('studentId', (string)$this->student1Id);
        $response = $controller->show($req);

        $this->assertEquals(200, $response->getStatusCode());
        $body = (string)$response->getContent();
        $this->assertStringContainsString('Student Alice', $body);
        $this->assertStringContainsString('ADM001', $body);
        $this->assertStringContainsString('Enrolled Subjects', $body);

        // View unlinked child -> 403
        $unlinkedReq = new Request(
            queryParams: [],
            postParams: [],
            serverParams: ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/parent/children/3']
        );
        $unlinkedReq->setAttribute('studentId', (string)$this->student3Id);
        $forbiddenResponse = $controller->show($unlinkedReq);
        $this->assertEquals(403, $forbiddenResponse->getStatusCode());
    }

    /**
     * Test 6: Report Card publication sealing and access
     */
    public function testReportCardGatingForParent(): void
    {
        $parent1 = $this->createActor($this->parentUser1Id, 'parent');
        $reportCardService = new ReportCardService(
            $this->gradebookRepo,
            $this->studentRepo,
            $this->academicRepo
        );

        $mockAuth = $this->createMock(AuthenticatorInterface::class);
        $mockAuth->method('user')->willReturn($parent1);

        $controller = new ReportCardController(
            $mockAuth,
            $reportCardService,
            $this->gradebookRepo,
            $this->publicationRepo,
            $this->parentRepo,
            $this->studentRepo,
            $this->academicRepo
        );

        // 1. Unpublished -> Throws AuthorizationException
        $req = new Request(
            queryParams: ['term_id' => '1'],
            postParams: [],
            serverParams: ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/parent/children/1/grades']
        );
        $req->setAttribute('studentId', (string)$this->student1Id);
        
        $this->expectException(AuthorizationException::class);
        $controller->index($req);
    }

    /**
     * Test 7: Parent with zero linked children receives clean empty state
     */
    public function testParentWithZeroChildrenEmptyState(): void
    {
        $this->db->exec("
            INSERT INTO users (id, name, email, status) VALUES (30, 'Parent Empty', 'empty@claret.edu', 'active');
            INSERT INTO parents (id, user_id) VALUES (3, 30);
        ");

        $parentEmpty = $this->createActor(30, 'parent');
        $dashboardData = $this->parentService->getDashboardData($parentEmpty);

        $this->assertNotNull($dashboardData['parent']);
        $this->assertEmpty($dashboardData['children']);
        $this->assertNull($dashboardData['selectedChild']);
        $this->assertEmpty($dashboardData['childrenSummaries']);
    }
}
