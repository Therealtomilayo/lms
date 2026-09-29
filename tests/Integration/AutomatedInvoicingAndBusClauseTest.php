<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Models\FeeStructure;
use App\Models\FeeStructureItem;
use App\Models\Student;
use App\Models\User;
use App\Repositories\EnrollmentRepository;
use App\Repositories\FeeRepository;
use App\Repositories\StudentRepository;
use App\Services\EnrollmentService;
use App\Services\FeeInvoiceService;
use App\Services\NotificationService;
use PDO;
use PHPUnit\Framework\TestCase;

class AutomatedInvoicingAndBusClauseTest extends TestCase
{
    private PDO $pdo;
    private FeeRepository $feeRepo;
    private StudentRepository $studentRepo;
    private FeeInvoiceService $feeInvoiceService;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $this->pdo->exec("
            CREATE TABLE `users` (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                uuid TEXT,
                name TEXT,
                email TEXT,
                phone TEXT,
                password_hash TEXT,
                status TEXT DEFAULT 'active',
                must_change_password INTEGER DEFAULT 0,
                created_at TEXT,
                updated_at TEXT
            );

            CREATE TABLE `students` (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER,
                admission_number TEXT,
                date_of_birth TEXT,
                gender TEXT,
                current_class_id INTEGER,
                use_school_bus INTEGER DEFAULT 0,
                created_at TEXT,
                updated_at TEXT
            );

            CREATE TABLE `academic_levels` (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT
            );

            CREATE TABLE `parents` (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER
            );

            CREATE TABLE `parent_student` (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                parent_id INTEGER,
                student_id INTEGER
            );

            CREATE TABLE `classes` (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                academic_level_id INTEGER DEFAULT 1,
                name TEXT,
                section_arm TEXT,
                status TEXT DEFAULT 'active'
            );

            CREATE TABLE `sessions` (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT,
                start_date TEXT,
                end_date TEXT,
                status TEXT DEFAULT 'active',
                is_current INTEGER DEFAULT 1
            );

            CREATE TABLE `terms` (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                session_id INTEGER,
                name TEXT,
                status TEXT DEFAULT 'active',
                is_current INTEGER DEFAULT 1
            );

            CREATE TABLE `fee_categories` (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT,
                is_active INTEGER DEFAULT 1
            );

            CREATE TABLE `fee_structures` (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                session_id INTEGER,
                term_id INTEGER,
                academic_level_id INTEGER,
                class_id INTEGER,
                title TEXT,
                currency TEXT DEFAULT 'NGN',
                due_date TEXT,
                is_active INTEGER DEFAULT 1,
                created_by INTEGER,
                created_at TEXT,
                updated_at TEXT
            );

            CREATE TABLE `fee_structure_items` (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                fee_structure_id INTEGER,
                fee_category_id INTEGER,
                name TEXT,
                amount REAL,
                is_compulsory INTEGER DEFAULT 1,
                is_required_for_result INTEGER DEFAULT 1,
                applicability TEXT DEFAULT 'all',
                created_at TEXT
            );

            CREATE TABLE `fee_invoices` (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                invoice_number TEXT,
                student_id INTEGER,
                parent_id INTEGER,
                class_id INTEGER,
                session_id INTEGER,
                term_id INTEGER,
                subtotal REAL DEFAULT 0,
                discount_amount REAL DEFAULT 0,
                total_amount REAL DEFAULT 0,
                amount_paid REAL DEFAULT 0,
                balance_due REAL DEFAULT 0,
                status TEXT DEFAULT 'unpaid',
                due_date TEXT,
                notes TEXT,
                created_by INTEGER,
                created_at TEXT,
                updated_at TEXT
            );

            CREATE TABLE `fee_invoice_items` (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                invoice_id INTEGER,
                fee_category_id INTEGER,
                name TEXT,
                amount REAL,
                is_compulsory INTEGER DEFAULT 1,
                is_required_for_result INTEGER DEFAULT 1,
                is_paid INTEGER DEFAULT 0,
                paid_amount REAL DEFAULT 0,
                created_at TEXT
            );

            CREATE TABLE `payments` (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                invoice_id INTEGER,
                user_id INTEGER,
                student_id INTEGER,
                amount REAL,
                status TEXT DEFAULT 'successful',
                created_at TEXT
            );

            CREATE TABLE `admission_wards` (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                application_id INTEGER,
                converted_student_id INTEGER,
                first_name TEXT,
                last_name TEXT,
                use_school_bus INTEGER DEFAULT 0
            );

            CREATE TABLE `class_enrollments` (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                student_id INTEGER,
                class_id INTEGER,
                session_id INTEGER,
                status TEXT DEFAULT 'active',
                enrolled_at TEXT,
                created_at TEXT,
                updated_at TEXT
            );

            CREATE TABLE `external_notifications` (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                channel TEXT,
                recipient TEXT,
                subject TEXT,
                message_body TEXT,
                status TEXT,
                gateway_provider TEXT,
                gateway_reference TEXT,
                error_message TEXT,
                metadata TEXT,
                user_id INTEGER,
                event_type TEXT,
                sent_at TEXT,
                created_at TEXT,
                updated_at TEXT
            );
        ");

        // Seed initial session, term, class, and fee categories
        $this->pdo->exec("
            INSERT INTO `sessions` (id, name, status, is_current) VALUES (1, '2026/2027', 'active', 1);
            INSERT INTO `terms` (id, session_id, name, status, is_current) VALUES (1, 1, '1st Term', 'active', 1);
            INSERT INTO `classes` (id, academic_level_id, name, section_arm, status) VALUES (1, 1, 'JSS 1', 'Gold', 'active');
            INSERT INTO `fee_categories` (id, name) VALUES (1, 'Tuition'), (4, 'Uniforms'), (10, 'School Bus & Transportation');
        ");

        $this->feeRepo = new FeeRepository($this->pdo);
        $this->studentRepo = new StudentRepository($this->pdo);
        $this->feeInvoiceService = new FeeInvoiceService($this->feeRepo, pdo: $this->pdo);
    }

    public function testUniformsAndBusClauseInvoicingLogic(): void
    {
        // 1. Create a fee structure with Tuition, Uniforms (new only), and Bus (bus users only)
        $structure = $this->feeRepo->createStructure([
            'session_id' => 1,
            'term_id' => 1,
            'class_id' => 1,
            'title' => '2026/2027 JSS 1 Fees',
            'created_by' => 1,
        ], [
            [
                'fee_category_id' => 1,
                'name' => 'Tuition Fee',
                'amount' => 50000.00,
                'is_compulsory' => 1,
                'is_required_for_result' => 1,
                'applicability' => 'all',
            ],
            [
                'fee_category_id' => 4,
                'name' => 'School Uniform & Sportswear',
                'amount' => 30000.00,
                'is_compulsory' => 1,
                'is_required_for_result' => 1,
                'applicability' => 'new_students_only',
            ],
            [
                'fee_category_id' => 10,
                'name' => 'School Bus & Transportation',
                'amount' => 40000.00,
                'is_compulsory' => 1,
                'is_required_for_result' => 1,
                'applicability' => 'bus_users_only',
            ],
        ]);

        $this->assertInstanceOf(FeeStructure::class, $structure);

        // 2. Student A: New entrant who uses the school bus
        $this->pdo->exec("INSERT INTO `users` (id, name, email) VALUES (10, 'Student Bus User', 'bus@student.com')");
        $this->pdo->exec("INSERT INTO `students` (id, user_id, admission_number, current_class_id, use_school_bus) VALUES (10, 10, 'STD-001', 1, 1)");
        $this->pdo->exec("INSERT INTO `admission_wards` (id, converted_student_id, first_name, last_name, use_school_bus) VALUES (1, 10, 'Student', 'Bus User', 1)");

        $invoiceA = $this->feeInvoiceService->ensureInvoiceForStudent(10, 1, 1, 1);
        $this->assertNotNull($invoiceA);
        $this->assertInstanceOf(\App\Models\FeeInvoice::class, $invoiceA);

        // Invoice A should have: Tuition (compulsory), Uniform (compulsory), Bus (compulsory)
        $itemsA = $this->feeRepo->getItemsForInvoice($invoiceA->id);
        $this->assertCount(3, $itemsA);

        $uniformItemA = null;
        $busItemA = null;
        foreach ($itemsA as $it) {
            if (str_contains(strtolower($it->name), 'uniform')) $uniformItemA = $it;
            if (str_contains(strtolower($it->name), 'bus')) $busItemA = $it;
        }

        $this->assertNotNull($uniformItemA);
        $this->assertEquals(1, $uniformItemA->isCompulsory, 'Uniform must be compulsory for new student');
        $this->assertEquals(1, $uniformItemA->isRequiredForResult);

        $this->assertNotNull($busItemA);
        $this->assertEquals(1, $busItemA->isCompulsory, 'Bus must be compulsory for bus rider');
        $this->assertEquals(1, $busItemA->isRequiredForResult);

        // 3. Student B: Returning student who does NOT use the school bus
        $this->pdo->exec("INSERT INTO `users` (id, name, email) VALUES (20, 'Returning Student', 'returning@student.com')");
        $this->pdo->exec("INSERT INTO `students` (id, user_id, admission_number, current_class_id, use_school_bus) VALUES (20, 20, 'STD-002', 1, 0)");
        // Give student B a prior invoice to mark them as a returning student (from previous session/term)
        $this->pdo->exec("INSERT INTO `fee_invoices` (id, invoice_number, student_id, session_id, term_id, class_id) VALUES (99, 'INV-PRIOR', 20, 0, 0, 1)");

        $invoiceB = $this->feeInvoiceService->ensureInvoiceForStudent(20, 1, 1, 1);
        $this->assertNotNull($invoiceB);
        $this->assertInstanceOf(\App\Models\FeeInvoice::class, $invoiceB);

        $itemsB = $this->feeRepo->getItemsForInvoice($invoiceB->id);
        
        $uniformItemB = null;
        $busItemB = null;
        foreach ($itemsB as $it) {
            if (str_contains(strtolower($it->name), 'uniform')) $uniformItemB = $it;
            if (str_contains(strtolower($it->name), 'bus')) $busItemB = $it;
        }

        // Returning student must NOT have Uniforms billed automatically
        $this->assertNull($uniformItemB, 'Uniforms must be omitted for returning students');

        // Returning student NOT using bus must have Bus as optional (not compulsory, not locking result)
        $this->assertNotNull($busItemB, 'Bus must be included on invoice');
        $this->assertEquals(0, $busItemB->isCompulsory, 'Bus must be optional (0) for non-bus student');
        $this->assertEquals(0, $busItemB->isRequiredForResult, 'Bus fee must not lock result for non-bus student');
    }

    public function testMultiWardAdmissionRejectionNoticeDispatch(): void
    {
        $logRepo = new \App\Repositories\NotificationLogRepository($this->pdo);
        $notificationService = new NotificationService(logRepo: $logRepo);

        $parentPhone = '+2348012345678';
        $parentEmail = 'parent.multi@example.com';
        $parentName = 'Chief & Mrs. Adeleke';
        $userId = 99;

        // Ward 1 (Accepted)
        $approvedResult = $notificationService->sendAdmissionApprovedNotice(
            parentPhone: $parentPhone,
            parentEmail: $parentEmail,
            parentName: $parentName,
            studentName: 'Tunde Adeleke',
            admissionNumber: 'STD-2026-001',
            className: 'JSS 1 Gold',
            defaultPassword: 'Claret@2026!',
            userId: $userId
        );

        $this->assertNotEmpty($approvedResult['sms']);
        $this->assertNotEmpty($approvedResult['email']);

        // Ward 2 (Rejected - due to cohort capacity/criteria)
        $rejectionReason = 'Class capacity limit reached for Senior Secondary 2 Science track.';
        $rejectedResult = $notificationService->sendAdmissionRejectedNotice(
            parentPhone: $parentPhone,
            parentEmail: $parentEmail,
            parentName: $parentName,
            wardName: 'Bisi Adeleke',
            rejectionReason: $rejectionReason,
            userId: $userId
        );

        $this->assertNotEmpty($rejectedResult['sms']);
        $this->assertNotEmpty($rejectedResult['email']);

        // Query external_notifications to assert both notices were logged for the parent
        $stmt = $this->pdo->prepare("SELECT * FROM `external_notifications` WHERE recipient = :rec ORDER BY id ASC");
        $stmt->execute(['rec' => $parentEmail]);
        $emailLogs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $this->assertCount(2, $emailLogs, 'Parent must receive two distinct emails for the two wards');

        // Verify Approved email
        $this->assertEquals('admission_approved', $emailLogs[0]['event_type']);
        $this->assertStringContainsString('Tunde Adeleke', $emailLogs[0]['subject']);

        // Verify Rejected email
        $this->assertEquals('admission_rejected', $emailLogs[1]['event_type']);
        $this->assertStringContainsString('Bisi Adeleke', $emailLogs[1]['subject']);
        $this->assertStringContainsString('Class capacity limit reached', $emailLogs[1]['message_body']);

        // Query SMS logs
        $stmt = $this->pdo->prepare("SELECT * FROM `external_notifications` WHERE recipient = :phone ORDER BY id ASC");
        $stmt->execute(['phone' => $parentPhone]);
        $smsLogs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $this->assertCount(2, $smsLogs, 'Parent must receive two distinct SMS messages for the two wards');
        $this->assertEquals('admission_approved', $smsLogs[0]['event_type']);
        $this->assertEquals('admission_rejected', $smsLogs[1]['event_type']);
        $this->assertStringContainsString('Bisi Adeleke', $smsLogs[1]['message_body']);
    }
}

