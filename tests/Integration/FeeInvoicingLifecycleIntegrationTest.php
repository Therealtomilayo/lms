<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Core\Database;
use App\Core\UserContext;
use App\DTO\ServiceResult;
use App\Models\FeeInvoice;
use App\Models\Payment;
use App\Repositories\AcademicRepository;
use App\Repositories\FeeRepository;
use App\Repositories\ParentRepository;
use App\Repositories\PaymentRepository;
use App\Repositories\StudentRepository;
use App\Repositories\UserRepository;
use App\Services\FeeInvoiceService;
use App\Services\PaymentService;
use PDO;
use PHPUnit\Framework\TestCase;

class FeeInvoicingLifecycleIntegrationTest extends TestCase
{
    private PDO $pdo;
    private FeeRepository $feeRepo;
    private FeeInvoiceService $feeService;
    private PaymentRepository $paymentRepo;
    private PaymentService $paymentService;
    private UserRepository $userRepo;
    private StudentRepository $studentRepo;
    private ParentRepository $parentRepo;
    private AcademicRepository $academicRepo;

    private int $sessionId;
    private int $termId;
    private int $classId;
    private int $academicLevelId;
    private int $studentId;
    private int $studentUserId;
    private int $parentId;
    private int $parentUserId;
    private int $adminUserId;

    protected function setUp(): void
    {
        $this->pdo = Database::getConnection();
        $this->feeRepo = new FeeRepository($this->pdo);
        $this->paymentRepo = new PaymentRepository($this->pdo);
        $this->studentRepo = new StudentRepository($this->pdo);
        $this->parentRepo = new ParentRepository($this->pdo);
        $this->academicRepo = new AcademicRepository($this->pdo);
        $this->userRepo = new UserRepository($this->pdo);
        $this->paymentService = new PaymentService($this->paymentRepo);

        $this->feeService = new FeeInvoiceService(
            $this->feeRepo,
            $this->paymentRepo,
            $this->studentRepo,
            $this->academicRepo,
            $this->parentRepo,
            $this->paymentService,
            $this->pdo
        );

        // 1. Resolve Session & Term
        $session = $this->academicRepo->findSessionById(2) ?? $this->academicRepo->getAllSessions()[0];
        $this->sessionId = (int)$session->id;

        $terms = $this->academicRepo->getAllTerms();
        $term = !empty($terms) ? $terms[0] : null;
        $this->termId = $term ? (int)$term->id : 1;

        // 2. Resolve Class and Level
        $classes = $this->academicRepo->getAllClasses();
        $class = !empty($classes) ? $classes[0] : null;
        $this->classId = $class ? (int)$class->id : 1;
        $this->academicLevelId = $class ? (int)$class->academicLevelId : 1;

        // 3. Create Admin User
        $admin = $this->userRepo->create([
            'uuid' => 'admin-fee-' . uniqid(),
            'name' => 'Bursar Test Admin',
            'email' => 'bursar_' . uniqid() . '@claret.edu.ng',
            'phone' => '08099887766',
            'password_hash' => password_hash('Secret123!', PASSWORD_BCRYPT),
            'status' => 'active',
            'must_change_password' => 0,
        ], ['admin']);
        $this->adminUserId = (int)$admin->id;

        // 4. Create Parent User & Profile
        $parentUser = $this->userRepo->create([
            'uuid' => 'parent-fee-' . uniqid(),
            'name' => 'Alhaji Danjuma Parent',
            'email' => 'danjuma_' . uniqid() . '@example.com',
            'phone' => '08033221100',
            'password_hash' => password_hash('Secret123!', PASSWORD_BCRYPT),
            'status' => 'active',
            'must_change_password' => 0,
        ], ['parent']);
        $this->parentUserId = (int)$parentUser->id;
        $parentProfile = $this->parentRepo->create($this->parentUserId);
        $this->parentId = (int)$parentProfile->id;

        // 5. Create Student User & Profile
        $studentUser = $this->userRepo->create([
            'uuid' => 'student-fee-' . uniqid(),
            'name' => 'Zainab Danjuma',
            'email' => 'zainab_' . uniqid() . '@claret.edu.ng',
            'phone' => '08055443322',
            'password_hash' => password_hash('Secret123!', PASSWORD_BCRYPT),
            'status' => 'active',
            'must_change_password' => 0,
        ], ['student']);
        $this->studentUserId = (int)$studentUser->id;

        $admNumber = 'STD-FEE-' . strtoupper(bin2hex(random_bytes(3)));
        $student = $this->studentRepo->create(
            userId: $this->studentUserId,
            admissionNumber: $admNumber,
            dateOfBirth: '2014-06-15',
            gender: 'female',
            currentClassId: $this->classId
        );
        $this->studentId = (int)$student->id;

        // Link Parent to Student
        $this->parentRepo->linkStudent($this->parentId, $this->studentId, 'Father');

        // Enroll Student in Session & Class
        $this->pdo->prepare('INSERT INTO `class_enrollments` (`student_id`, `class_id`, `session_id`, `status`, `enrolled_at`)
            VALUES (?, ?, ?, "active", NOW())')->execute([$this->studentId, $this->classId, $this->sessionId]);

        // Clean any fee data from previous aborted test runs for this test user/student
        $this->pdo->prepare('DELETE FROM `fee_invoice_items` WHERE `invoice_id` IN (SELECT id FROM `fee_invoices` WHERE `created_by` = ? OR `student_id` = ?)')
            ->execute([$this->adminUserId, $this->studentId]);
        $this->pdo->prepare('DELETE FROM `fee_invoices` WHERE `created_by` = ? OR `student_id` = ?')
            ->execute([$this->adminUserId, $this->studentId]);
        $this->pdo->prepare('DELETE FROM `fee_structure_items` WHERE `fee_structure_id` IN (SELECT id FROM `fee_structures` WHERE `created_by` = ?)')
            ->execute([$this->adminUserId]);
        $this->pdo->prepare('DELETE FROM `fee_structures` WHERE `created_by` = ?')
            ->execute([$this->adminUserId]);
    }

    protected function tearDown(): void
    {
        // Cleanup test artifacts
        $this->pdo->prepare('DELETE FROM `payments` WHERE `student_id` = ? OR `user_id` IN (?, ?) OR `invoice_id` IN (SELECT id FROM `fee_invoices` WHERE `created_by` = ?)')->execute([$this->studentId, $this->studentUserId, $this->parentUserId, $this->adminUserId]);
        $this->pdo->prepare('DELETE FROM `fee_invoices` WHERE `student_id` = ? OR `created_by` = ?')->execute([$this->studentId, $this->adminUserId]);
        $this->pdo->prepare('DELETE FROM `class_enrollments` WHERE `student_id` = ?')->execute([$this->studentId]);
        $this->pdo->prepare('DELETE FROM `parent_student` WHERE `student_id` = ?')->execute([$this->studentId]);
        $this->pdo->prepare('DELETE FROM `students` WHERE `id` = ?')->execute([$this->studentId]);
        $this->pdo->prepare('DELETE FROM `parents` WHERE `id` = ?')->execute([$this->parentId]);
        $this->pdo->prepare('DELETE FROM `fee_structure_items` WHERE `fee_structure_id` IN (SELECT `id` FROM `fee_structures` WHERE `created_by` = ?)')->execute([$this->adminUserId]);
        $this->pdo->prepare('DELETE FROM `fee_structures` WHERE `created_by` = ?')->execute([$this->adminUserId]);
        $this->pdo->prepare('DELETE FROM `users` WHERE `id` IN (?, ?, ?)')->execute([$this->studentUserId, $this->parentUserId, $this->adminUserId]);
    }

    public function testFeeCategoriesCatalog(): void
    {
        $categories = $this->feeRepo->getAllCategories(true);
        $this->assertNotEmpty($categories);

        $names = array_map(fn($c) => $c->name, $categories);
        $this->assertContains('Tuition Fee', $names);
        $this->assertContains('Development Levy', $names);
    }

    public function testFeeStructureCreationAndMatching(): void
    {
        $structureData = [
            'session_id' => $this->sessionId,
            'term_id' => $this->termId,
            'academic_level_id' => $this->academicLevelId,
            'class_id' => null,
            'title' => 'Test Termly Fee Structure ' . uniqid(),
            'currency' => 'NGN',
            'due_date' => date('Y-m-d', strtotime('+30 days')),
            'is_active' => 1,
        ];

        $items = [
            ['fee_category_id' => 1, 'name' => 'Tuition Fee Component', 'amount' => 60000.00, 'is_compulsory' => 1],
            ['fee_category_id' => 2, 'name' => 'Campus Maintenance Levy', 'amount' => 15000.00, 'is_compulsory' => 1],
            ['fee_category_id' => 3, 'name' => 'ICT & Computer Lab Fee', 'amount' => 10000.00, 'is_compulsory' => 1],
        ];

        $res = $this->feeService->configureFeeStructure($structureData, $items, $this->adminUserId);
        $this->assertTrue($res->isSuccess());

        /** @var \App\Models\FeeStructure $structure */
        $structure = $res->getData();
        $this->assertEquals(85000.00, $structure->getTotalAmount());
        $this->assertCount(3, $structure->items);

        // Test matching algorithm
        $matched = $this->feeRepo->findMatchingStructure($this->sessionId, $this->termId, $this->academicLevelId, $this->classId);
        $this->assertNotNull($matched);
        $this->assertEquals($structure->id, $matched->id);
    }

    public function testBatchInvoicingIdempotencyAndFinancialLifecycle(): void
    {
        // 1. Configure Structure
        $structureData = [
            'session_id' => $this->sessionId,
            'term_id' => $this->termId,
            'academic_level_id' => $this->academicLevelId,
            'class_id' => $this->classId,
            'title' => 'Class Specific Tuition Schedule ' . uniqid(),
            'currency' => 'NGN',
            'due_date' => date('Y-m-d', strtotime('+14 days')),
            'is_active' => 1,
        ];

        $items = [
            ['fee_category_id' => 1, 'name' => 'Core Tuition', 'amount' => 50000.00, 'is_compulsory' => 1],
            ['fee_category_id' => 4, 'name' => 'PTA Statutory Dues', 'amount' => 10000.00, 'is_compulsory' => 1],
        ];

        $this->feeService->configureFeeStructure($structureData, $items, $this->adminUserId);

        // 2. First Run: Batch Generate Invoices
        $genRes1 = $this->feeService->batchGenerateInvoices($this->sessionId, $this->termId, null, $this->classId, $this->adminUserId);
        $this->assertTrue($genRes1->isSuccess());
        $this->assertGreaterThanOrEqual(1, $genRes1->getData()['created_count']);

        // 3. Second Run: Verify Idempotency (Cannot double bill)
        $genRes2 = $this->feeService->batchGenerateInvoices($this->sessionId, $this->termId, null, $this->classId, $this->adminUserId);
        $this->assertTrue($genRes2->isSuccess());
        $this->assertEquals(0, $genRes2->getData()['created_count']);
        $this->assertGreaterThanOrEqual(1, $genRes2->getData()['skipped_count']);

        // 4. Inspect Created Invoice
        $invoice = $this->feeRepo->findInvoiceForStudentTerm($this->studentId, $this->sessionId, $this->termId);
        $this->assertNotNull($invoice);
        $this->assertEquals(60000.00, $invoice->totalAmount);
        $this->assertEquals(0.00, $invoice->amountPaid);
        $this->assertEquals(60000.00, $invoice->balanceDue);
        $this->assertEquals(FeeInvoice::STATUS_UNPAID, $invoice->status);
        $this->assertCount(2, $invoice->items);

        // 5. Partial Payment (40% = 24,000 NGN)
        $parentActor = new UserContext($this->parentUserId, 'uuid-parent', 'Alhaji Danjuma', 'parent@test.com', ['parent']);
        $initPartial = $this->feeService->initiateInvoicePayment($invoice, 24000.00, $parentActor, 'http://localhost/parent/fees/verify');
        $this->assertTrue($initPartial->isSuccess());

        /** @var Payment $partialPayment */
        $partialPayment = $initPartial->getData();
        $this->assertEquals(24000.00, $partialPayment->amount);
        $this->assertEquals(Payment::STATUS_PENDING, $partialPayment->status);

        // Confirm Partial Payment
        $confirmPartial = $this->feeService->verifyInvoicePayment($partialPayment->reference);
        $this->assertTrue($confirmPartial->isSuccess());

        $refreshed1 = $this->feeRepo->findInvoiceById($invoice->id);
        $this->assertEquals(24000.00, $refreshed1->amountPaid);
        $this->assertEquals(36000.00, $refreshed1->balanceDue);
        $this->assertEquals(FeeInvoice::STATUS_PARTIALLY_PAID, $refreshed1->status);
        $this->assertTrue($refreshed1->isPartiallyPaid());
        $this->assertFalse($refreshed1->isPaid());

        // 6. Complete Balance Clearance (Remaining 36,000 NGN)
        $initFinal = $this->feeService->initiateInvoicePayment($refreshed1, 36000.00, $parentActor, 'http://localhost/parent/fees/verify');
        $this->assertTrue($initFinal->isSuccess());

        $confirmFinal = $this->feeService->verifyInvoicePayment($initFinal->getData()->reference);
        $this->assertTrue($confirmFinal->isSuccess());

        $refreshed2 = $this->feeRepo->findInvoiceById($invoice->id);
        $this->assertEquals(60000.00, $refreshed2->amountPaid);
        $this->assertEquals(0.00, $refreshed2->balanceDue);
        $this->assertEquals(FeeInvoice::STATUS_PAID, $refreshed2->status);
        $this->assertTrue($refreshed2->isPaid());
        $this->assertFalse($refreshed2->isPartiallyPaid());
    }

    public function testBursaryManualOfflinePaymentRecording(): void
    {
        // 1. Create structure & single invoice
        $structure = $this->feeRepo->createStructure([
            'session_id' => $this->sessionId,
            'term_id' => $this->termId,
            'academic_level_id' => null,
            'class_id' => $this->classId,
            'title' => 'Manual Offline Schedule ' . uniqid(),
            'currency' => 'NGN',
            'created_by' => $this->adminUserId,
        ], [
            ['fee_category_id' => 1, 'name' => 'Tuition', 'amount' => 45000.00],
        ]);

        $invoice = $this->feeRepo->createInvoice([
            'invoice_number' => $this->feeRepo->generateInvoiceNumber($this->sessionId),
            'student_id' => $this->studentId,
            'parent_id' => $this->parentId,
            'class_id' => $this->classId,
            'session_id' => $this->sessionId,
            'term_id' => $this->termId,
            'created_by' => $this->adminUserId,
        ], [
            ['name' => 'Tuition', 'amount' => 45000.00, 'fee_category_id' => 1],
        ]);

        $adminActor = new UserContext($this->adminUserId, 'uuid-admin', 'Bursar', 'bursar@claret.edu.ng', ['admin']);

        // Record Manual Bank Transfer
        $res = $this->feeService->recordManualPayment(
            $invoice->id,
            45000.00,
            'bank_transfer',
            'ZENITH-TELLER-881920',
            'Paid directly at Zenith Bank Mabushi Branch',
            $adminActor
        );

        $this->assertTrue($res->isSuccess());
        $data = $res->getData();

        /** @var Payment $payment */
        $payment = $data['payment'];
        $this->assertEquals('bank_transfer', $payment->channel);
        $this->assertEquals('ZENITH-TELLER-881920', $payment->gatewayReference);
        $this->assertEquals(Payment::STATUS_SUCCESSFUL, $payment->status);

        /** @var FeeInvoice $updatedInvoice */
        $updatedInvoice = $data['invoice'];
        $this->assertEquals(45000.00, $updatedInvoice->amountPaid);
        $this->assertEquals(0.00, $updatedInvoice->balanceDue);
        $this->assertTrue($updatedInvoice->isPaid());

        // Verify Printable Receipt Data Integration
        $receiptData = $this->paymentService->getReceiptData($payment->reference);
        $this->assertNotNull($receiptData);
        $this->assertEquals($payment->reference, $receiptData['payment']->reference);
        $this->assertNotEmpty($receiptData['receipt_number']);
    }

    public function testParentInvoiceAccessAuthorization(): void
    {
        // 1. Create invoice for student linked to parent
        $invoice = $this->feeRepo->createInvoice([
            'invoice_number' => $this->feeRepo->generateInvoiceNumber($this->sessionId),
            'student_id' => $this->studentId,
            'parent_id' => $this->parentId,
            'class_id' => $this->classId,
            'session_id' => $this->sessionId,
            'term_id' => $this->termId,
            'created_by' => $this->adminUserId,
        ], [
            ['name' => 'Tuition', 'amount' => 30000.00, 'fee_category_id' => 1],
        ]);

        // Parent should see this invoice in their list
        $parentInvoices = $this->feeRepo->getInvoicesForParent($this->parentId);
        $this->assertNotEmpty($parentInvoices);
        $this->assertEquals($invoice->id, $parentInvoices[0]->id);

        // An unlinked parent should NOT see this invoice
        $unlinkedParent = $this->parentRepo->create($this->adminUserId);
        $unlinkedInvoices = $this->feeRepo->getInvoicesForParent((int)$unlinkedParent->id);
        $this->assertEmpty($unlinkedInvoices);
        $this->pdo->prepare('DELETE FROM `parents` WHERE id = ?')->execute([$unlinkedParent->id]);
    }
}
