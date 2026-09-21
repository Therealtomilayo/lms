<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Core\UserContext;
use App\Models\FeeInvoice;
use App\Repositories\AcademicRepository;
use App\Repositories\FeeRepository;
use App\Repositories\PaymentRepository;
use App\Services\FeeInvoiceService;
use PDO;
use PHPUnit\Framework\TestCase;

class FeeBursaryLifecycleIntegrationTest extends TestCase
{
    private PDO $pdo;
    private FeeRepository $feeRepo;
    private AcademicRepository $academicRepo;
    private PaymentRepository $paymentRepo;
    private FeeInvoiceService $feeService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $this->createSchema();
        $this->seedData();

        $this->feeRepo = new FeeRepository($this->pdo);
        $this->academicRepo = new AcademicRepository($this->pdo);
        $this->paymentRepo = new PaymentRepository($this->pdo);
        $this->feeService = new FeeInvoiceService(
            feeRepo: $this->feeRepo,
            paymentRepo: $this->paymentRepo,
            academicRepo: $this->academicRepo,
            pdo: $this->pdo
        );
    }

    private function createSchema(): void
    {
        $this->pdo->exec("
            CREATE TABLE users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                email TEXT UNIQUE NOT NULL,
                role TEXT NOT NULL,
                phone TEXT NULL,
                status TEXT DEFAULT 'active',
                created_at TEXT DEFAULT CURRENT_TIMESTAMP,
                updated_at TEXT DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE sessions (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                start_date TEXT,
                end_date TEXT,
                is_current INTEGER DEFAULT 0,
                status TEXT DEFAULT 'active',
                created_at TEXT DEFAULT CURRENT_TIMESTAMP,
                updated_at TEXT DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE terms (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                session_id INTEGER NOT NULL,
                name TEXT NOT NULL,
                start_date TEXT,
                end_date TEXT,
                is_current INTEGER DEFAULT 0,
                status TEXT DEFAULT 'active',
                created_at TEXT DEFAULT CURRENT_TIMESTAMP,
                updated_at TEXT DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE academic_levels (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                created_at TEXT DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE classes (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                academic_level_id INTEGER,
                class_group TEXT,
                capacity INTEGER DEFAULT 40,
                status TEXT DEFAULT 'active',
                created_at TEXT DEFAULT CURRENT_TIMESTAMP,
                updated_at TEXT DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE students (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                admission_number TEXT UNIQUE NOT NULL,
                current_class_id INTEGER,
                enrollment_date TEXT,
                created_at TEXT DEFAULT CURRENT_TIMESTAMP,
                updated_at TEXT DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE class_enrollments (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                student_id INTEGER NOT NULL,
                class_id INTEGER NOT NULL,
                session_id INTEGER NOT NULL,
                status TEXT DEFAULT 'active',
                enrolled_at TEXT,
                created_at TEXT,
                updated_at TEXT,
                UNIQUE(student_id, session_id)
            );

            CREATE TABLE parents (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                address TEXT,
                occupation TEXT,
                emergency_contact TEXT,
                created_at TEXT DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE parent_student (
                parent_id INTEGER NOT NULL,
                student_id INTEGER NOT NULL,
                relationship TEXT DEFAULT 'parent',
                is_emergency_contact INTEGER DEFAULT 1,
                PRIMARY KEY (parent_id, student_id)
            );

            CREATE TABLE fee_categories (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                code TEXT,
                description TEXT,
                created_at TEXT DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE fee_structures (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                session_id INTEGER NOT NULL,
                term_id INTEGER NOT NULL,
                academic_level_id INTEGER NULL,
                class_id INTEGER NULL,
                title TEXT NOT NULL,
                due_date TEXT NULL,
                currency TEXT DEFAULT 'NGN',
                is_active INTEGER DEFAULT 1,
                created_by INTEGER NULL,
                created_at TEXT DEFAULT CURRENT_TIMESTAMP,
                updated_at TEXT DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE fee_structure_items (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                fee_structure_id INTEGER NOT NULL,
                fee_category_id INTEGER NOT NULL,
                name TEXT NOT NULL,
                amount REAL NOT NULL,
                is_compulsory INTEGER DEFAULT 1,
                is_required_for_result INTEGER DEFAULT 1,
                created_at TEXT DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE fee_invoices (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                invoice_number TEXT UNIQUE NOT NULL,
                student_id INTEGER NOT NULL,
                parent_id INTEGER NULL,
                class_id INTEGER NOT NULL,
                session_id INTEGER NOT NULL,
                term_id INTEGER NOT NULL,
                subtotal REAL NOT NULL,
                discount_amount REAL DEFAULT 0.00,
                total_amount REAL NOT NULL,
                amount_paid REAL DEFAULT 0.00,
                balance_due REAL NOT NULL,
                status TEXT NOT NULL DEFAULT 'unpaid',
                due_date TEXT NULL,
                notes TEXT NULL,
                created_by INTEGER NOT NULL,
                created_at TEXT DEFAULT CURRENT_TIMESTAMP,
                updated_at TEXT DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE fee_invoice_items (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                invoice_id INTEGER NOT NULL,
                fee_category_id INTEGER NULL,
                name TEXT NOT NULL,
                amount REAL NOT NULL,
                is_compulsory INTEGER DEFAULT 1,
                is_required_for_result INTEGER DEFAULT 1,
                is_paid INTEGER DEFAULT 0,
                paid_amount REAL DEFAULT 0.00,
                created_at TEXT DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE payments (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                reference TEXT UNIQUE NOT NULL,
                user_id INTEGER NOT NULL,
                student_id INTEGER NOT NULL,
                session_id INTEGER NOT NULL,
                term_id INTEGER NOT NULL,
                invoice_id INTEGER NULL,
                purpose TEXT NOT NULL,
                amount REAL NOT NULL,
                currency TEXT DEFAULT 'NGN',
                channel TEXT NOT NULL,
                status TEXT NOT NULL DEFAULT 'pending',
                gateway_reference TEXT NULL,
                metadata TEXT NULL,
                paid_at TEXT NULL,
                created_at TEXT DEFAULT CURRENT_TIMESTAMP,
                updated_at TEXT DEFAULT CURRENT_TIMESTAMP
            );
        ");
    }

    private function seedData(): void
    {
        $this->pdo->exec("
            INSERT INTO users (id, name, email, role, status) VALUES 
            (1, 'Admin Bursar', 'bursar@claret.test', 'admin', 'active'),
            (2, 'Parent John', 'parent@claret.test', 'parent', 'active'),
            (3, 'Student Chidi', 'chidi@claret.test', 'student', 'active');

            INSERT INTO sessions (id, name, is_current, status) VALUES 
            (1, '2026/2027 Academic Session', 1, 'active');

            INSERT INTO terms (id, session_id, name, is_current, status) VALUES 
            (1, 1, 'First Term', 1, 'active'),
            (2, 1, 'Second Term', 0, 'active');

            INSERT INTO academic_levels (id, name) VALUES (1, 'Senior Secondary');

            INSERT INTO classes (id, name, academic_level_id) VALUES (1, 'SS 1 Gold', 1);

            INSERT INTO students (id, user_id, admission_number, current_class_id) VALUES 
            (1, 3, 'CLT/2026/001', 1);

            INSERT INTO class_enrollments (student_id, class_id, session_id, status) VALUES 
            (1, 1, 1, 'active');

            INSERT INTO parents (id, user_id) VALUES (1, 2);
            INSERT INTO parent_student (parent_id, student_id) VALUES (1, 1);

            INSERT INTO fee_categories (id, name, code) VALUES 
            (1, 'Tuition', 'TUI'),
            (2, 'Development Levy', 'DEV'),
            (3, 'End of Term Party', 'PTY');
        ");
    }

    public function testFeeStructureCreationWithResultRequirementFlag(): void
    {
        $structureData = [
            'session_id' => 1,
            'term_id' => 1,
            'academic_level_id' => 1,
            'title' => 'SS 1 First Term Fees',
            'due_date' => '2026-10-31',
            'is_active' => 1,
        ];

        $items = [
            [
                'fee_category_id' => 1,
                'name' => 'Tuition Fee',
                'amount' => 50000.00,
                'is_compulsory' => 1,
                'is_required_for_result' => 1,
            ],
            [
                'fee_category_id' => 2,
                'name' => 'ICT & Science Levy',
                'amount' => 15000.00,
                'is_compulsory' => 1,
                'is_required_for_result' => 1,
            ],
            [
                'fee_category_id' => 3,
                'name' => 'Optional Excursion',
                'amount' => 10000.00,
                'is_compulsory' => 0,
                'is_required_for_result' => 0,
            ],
        ];

        $structure = $this->feeRepo->createStructure($structureData, $items);
        $this->assertNotNull($structure);
        $this->assertCount(3, $structure->items);
        $this->assertTrue($structure->items[0]->isRequiredForResult);
        $this->assertTrue($structure->items[1]->isRequiredForResult);
        $this->assertFalse($structure->items[2]->isRequiredForResult);
    }

    public function testTermScopedResultLockingAndUnlockingViaItemizedPayment(): void
    {
        // 1. Create structure
        $structure = $this->feeRepo->createStructure([
            'session_id' => 1,
            'term_id' => 1,
            'academic_level_id' => 1,
            'title' => 'SS 1 First Term Fees',
            'is_active' => 1,
        ], [
            ['fee_category_id' => 1, 'name' => 'Tuition', 'amount' => 50000.00, 'is_compulsory' => 1, 'is_required_for_result' => 1],
            ['fee_category_id' => 3, 'name' => 'Yearbook', 'amount' => 5000.00, 'is_compulsory' => 0, 'is_required_for_result' => 0],
        ]);

        // 2. Batch generate invoices
        $genRes = $this->feeService->batchGenerateInvoices(1, 1, 1, 1, 1);
        $this->assertTrue($genRes->isSuccess(), 'Batch generate failed: ' . ($genRes->message ?? json_encode($genRes->errors)));

        $invoice = $this->feeRepo->findInvoiceForStudentTerm(1, 1, 1);
        $this->assertNotNull($invoice);
        $this->assertEquals(55000.00, $invoice->totalAmount);

        // 3. Initially, Term 1 result viewing MUST BE LOCKED because Tuition is unpaid
        $this->assertFalse($this->feeRepo->isStudentClearedForResult(1, 1, 1));
        $unpaidItems = $this->feeRepo->getUnpaidRequiredFeeItems(1, 1, 1);
        $this->assertCount(1, $unpaidItems);
        $this->assertEquals('Tuition', $unpaidItems[0]['name']);

        // 4. Parent initiates itemized payment ONLY for Tuition (₦50,000)
        $tuitionItem = array_values(array_filter($invoice->items, fn($it) => $it->name === 'Tuition'))[0];
        $parentContext = new UserContext(
            id: 2,
            uuid: 'parent-uuid',
            name: 'Parent John',
            email: 'parent@claret.test',
            roles: ['parent']
        );

        $payRes = $this->feeService->initiateInvoicePayment(
            $invoice,
            50000.00,
            $parentContext,
            'https://lms.test/parent/fees/verify',
            [$tuitionItem->id]
        );
        $this->assertTrue($payRes->isSuccess());
        $payment = $payRes->getData();

        // 5. Verify payment
        $verifyRes = $this->feeService->verifyInvoicePayment($payment->reference);
        $this->assertTrue($verifyRes->isSuccess());

        // 6. Now check bursary clearance for Term 1:
        // Mandatory Tuition is paid! Optional Yearbook (₦5,000) is unpaid, but does NOT lock results.
        $this->assertTrue($this->feeRepo->isStudentClearedForResult(1, 1, 1));

        // 7. Now simulate Term 2: Term 2 invoice is issued but unpaid
        $this->feeRepo->createInvoice([
            'invoice_number' => 'INV-2026-00002',
            'student_id' => 1,
            'parent_id' => 1,
            'class_id' => 1,
            'session_id' => 1,
            'term_id' => 2,
            'discount_amount' => 0.00,
            'created_by' => 1,
        ], [
            ['fee_category_id' => 1, 'name' => 'Term 2 Tuition', 'amount' => 50000.00, 'is_compulsory' => 1, 'is_required_for_result' => 1],
        ]);

        // CRITICAL INVARIANT:
        // Term 1 remains CLEARED and accessible indefinitely!
        $this->assertTrue($this->feeRepo->isStudentClearedForResult(1, 1, 1));

        // Term 2 is LOCKED pending Term 2 fee settlement!
        $this->assertFalse($this->feeRepo->isStudentClearedForResult(1, 1, 2));
    }
}
