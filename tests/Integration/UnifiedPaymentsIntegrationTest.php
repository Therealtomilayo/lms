<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Core\Database;
use App\Core\Request;
use App\Core\Session;
use App\Controllers\PaymentController;
use App\Models\Payment;
use App\Repositories\AcademicRepository;
use App\Repositories\AdmissionRepository;
use App\Repositories\ParentRepository;
use App\Repositories\PaymentRepository;
use App\Repositories\StudentRepository;
use App\Repositories\UserRepository;
use App\Services\AdmissionService;
use App\Services\PaymentService;
use PDO;
use PHPUnit\Framework\TestCase;

class UnifiedPaymentsIntegrationTest extends TestCase
{
    private PDO $db;
    private PaymentRepository $paymentRepo;
    private AdmissionRepository $admissionRepo;
    private UserRepository $userRepo;
    private AdmissionService $admissionService;
    private PaymentService $paymentService;
    private PaymentController $controller;

    private int $applicantUserId;
    private int $adminUserId;
    private int $applicationId;
    private int $wardId;
    private string $admissionPaymentRef;

    protected function setUp(): void
    {
        Session::destroy();
        $this->db = Database::getConnection();
        $this->paymentRepo = new PaymentRepository($this->db);
        $this->admissionRepo = new AdmissionRepository($this->db);
        $this->userRepo = new UserRepository($this->db);
        $academicRepo = new AcademicRepository($this->db);
        $studentRepo = new StudentRepository($this->db);
        $parentRepo = new ParentRepository($this->db);

        $this->admissionService = new AdmissionService(
            $this->admissionRepo,
            $this->userRepo,
            $academicRepo,
            $studentRepo,
            $parentRepo,
            $this->db
        );
        $this->paymentService = new PaymentService($this->paymentRepo);
        $this->controller = new PaymentController(null, $this->paymentService, $this->paymentRepo);

        // 1. Create Applicant User
        $appEmail = 'applicant_pay_' . uniqid() . '@test.com';
        $res = $this->admissionService->registerApplicant('Adewale Adeleke', $appEmail, 'Secret123!', '08012345678');
        $this->assertTrue($res->isSuccess());
        $this->applicantUserId = (int)$res->getData()['user']->id;

        // 2. Create Admin User
        $adminEmail = 'admin_pay_' . uniqid() . '@test.com';
        $admin = $this->userRepo->create([
            'uuid' => 'admin-uuid-' . uniqid(),
            'name' => 'Bursar Admin',
            'email' => $adminEmail,
            'phone' => '08099887766',
            'password_hash' => password_hash('Secret123!', PASSWORD_BCRYPT),
            'status' => 'active',
            'must_change_password' => 0,
        ], ['admin']);
        $this->adminUserId = (int)$admin->id;

        // 3. Application Docket & Ward
        $application = $this->admissionService->getApplicantActiveApplication($this->applicantUserId);
        $this->assertNotNull($application);
        $this->applicationId = (int)$application->id;

        $levels = $this->admissionService->getAcademicLevels();
        $levelId = !empty($levels) ? (int)$levels[0]->id : 1;

        $wardRes = $this->admissionService->addWard($this->applicantUserId, [
            'first_name' => 'Chinedu',
            'last_name' => 'Adeleke',
            'gender' => 'male',
            'date_of_birth' => '2012-05-15',
            'applying_for_level_id' => $levelId,
            'class_grade' => 'JSS 1',
        ]);
        $this->assertTrue($wardRes->isSuccess());
        $this->wardId = (int)$wardRes->getData()->id;

        // 4. Initialize and simulate successful payment for ward
        $initResult = $this->admissionService->initializeWardPayment(
            $this->wardId,
            $this->applicantUserId,
            'https://lms.test/applicant/payment/callback'
        );
        $this->assertTrue($initResult->isSuccess());
        $this->admissionPaymentRef = $initResult->getData()['reference'];

        $simResult = $this->admissionService->simulateSuccessfulWardPayment(
            $this->admissionPaymentRef,
            $this->applicantUserId
        );
        $this->assertTrue($simResult->isSuccess());
    }

    protected function tearDown(): void
    {
        Session::destroy();
        if ($this->applicationId) {
            $this->db->prepare("DELETE FROM admission_payments WHERE application_id = ?")->execute([$this->applicationId]);
            $this->db->prepare("DELETE FROM admission_status_history WHERE application_id = ?")->execute([$this->applicationId]);
            $this->db->prepare("DELETE FROM admission_wards WHERE application_id = ?")->execute([$this->applicationId]);
            $this->db->prepare("DELETE FROM admission_applications WHERE id = ?")->execute([$this->applicationId]);
        }
        if ($this->applicantUserId) {
            $this->db->prepare("DELETE FROM user_roles WHERE user_id = ?")->execute([$this->applicantUserId]);
            $this->db->prepare("DELETE FROM users WHERE id = ?")->execute([$this->applicantUserId]);
        }
        if ($this->adminUserId) {
            $this->db->prepare("DELETE FROM user_roles WHERE user_id = ?")->execute([$this->adminUserId]);
            $this->db->prepare("DELETE FROM users WHERE id = ?")->execute([$this->adminUserId]);
        }
    }

    public function testAdmissionPaymentIsIncludedInPaymentRepositoryAllPayments(): void
    {
        $payments = $this->paymentRepo->getAllPayments(limit: 50);
        $this->assertNotEmpty($payments);

        $found = null;
        foreach ($payments as $p) {
            if ($p->reference === $this->admissionPaymentRef) {
                $found = $p;
                break;
            }
        }

        $this->assertNotNull($found, "Admission payment reference {$this->admissionPaymentRef} must appear in getAllPayments.");
        $this->assertSame(Payment::PURPOSE_ADMISSION, $found->purpose);
        $this->assertSame('successful', $found->status);
        $this->assertSame('Chinedu Adeleke', $found->studentName);
        $this->assertSame('Adewale Adeleke', $found->payerName);
        $this->assertSame(10000.0, (float)$found->amount);
        $this->assertStringContainsString('Admission Application Fee', $found->metadata['item_description'] ?? '');
    }

    public function testAdmissionPaymentIsIncludedInUserPaymentHistory(): void
    {
        $userPayments = $this->paymentRepo->getPaymentsForUser($this->applicantUserId);
        $this->assertCount(1, $userPayments);
        $this->assertSame($this->admissionPaymentRef, $userPayments[0]->reference);
        $this->assertSame(Payment::PURPOSE_ADMISSION, $userPayments[0]->purpose);
        $this->assertSame('successful', $userPayments[0]->status);
    }

    public function testFindByReferenceReturnsAdmissionPaymentEntity(): void
    {
        $payment = $this->paymentRepo->findByReference($this->admissionPaymentRef);
        $this->assertNotNull($payment);
        $this->assertSame($this->admissionPaymentRef, $payment->reference);
        $this->assertSame(Payment::PURPOSE_ADMISSION, $payment->purpose);
        $this->assertTrue($payment->isSuccessful());
        $this->assertSame('₦10,000.00', $payment->getFormattedAmount());
        $this->assertStringStartsWith('REC-', $payment->getReceiptNumber());
    }

    public function testPaymentServiceGeneratesPrintableReceiptForAdmissionPayment(): void
    {
        $receipt = $this->paymentService->getReceiptData($this->admissionPaymentRef);
        $this->assertNotNull($receipt);
        $this->assertInstanceOf(Payment::class, $receipt['payment']);
        $this->assertSame($this->admissionPaymentRef, $receipt['payment']->reference);
        $this->assertNull($receipt['pin'], 'Admission payment receipt should not contain a scratch-card PIN.');
        $this->assertStringContainsString('Admission Application Fee', $receipt['item_title']);
        $this->assertStringStartsWith('REC-', $receipt['receipt_number']);
    }

    public function testSummaryStatsIncludesAdmissionPayments(): void
    {
        $stats = $this->paymentRepo->getSummaryStats();
        $this->assertGreaterThanOrEqual(1, $stats['total_transactions']);
        $this->assertGreaterThanOrEqual(1, $stats['admission_payments_count']);
        $this->assertGreaterThanOrEqual(10000.0, $stats['total_volume']);
    }

    public function testPurposeFilteringInRepository(): void
    {
        $admPayments = $this->paymentRepo->getAllPayments(purpose: 'admission');
        $this->assertNotEmpty($admPayments);
        foreach ($admPayments as $p) {
            $this->assertSame('admission', $p->purpose);
        }

        $countAdm = $this->paymentRepo->countPayments(purpose: 'admission');
        $this->assertGreaterThanOrEqual(1, $countAdm);
    }

    public function testApplicantCanAccessPaymentHistoryView(): void
    {
        $applicant = $this->userRepo->findById($this->applicantUserId);
        $request = new Request([], [], ['REQUEST_URI' => '/payments/history', 'REQUEST_METHOD' => 'GET']);
        $request->setAttribute('_user_context', \App\Core\UserContext::fromUser($applicant));
        
        $response = $this->controller->history($request);

        $this->assertSame(200, $response->getStatusCode());
        $content = $response->getContent();
        $this->assertStringContainsString('Payment History', $content);
        $this->assertStringContainsString($this->admissionPaymentRef, $content);
        $this->assertStringContainsString('Admission Fee', $content);
        $this->assertStringContainsString('Chinedu Adeleke', $content);
    }

    public function testAdminLedgerViewShowsUnifiedPaymentsWithAdmissionBadges(): void
    {
        $admin = $this->userRepo->findById($this->adminUserId);
        $request = new Request([], [], ['REQUEST_URI' => '/admin/payments', 'REQUEST_METHOD' => 'GET']);
        $request->setAttribute('_user_context', \App\Core\UserContext::fromUser($admin));

        $response = $this->controller->adminLedger($request);

        $this->assertSame(200, $response->getStatusCode());
        $content = $response->getContent();
        $this->assertStringContainsString('Payments &amp; Revenue Ledger', $content);
        $this->assertStringContainsString($this->admissionPaymentRef, $content);
        $this->assertStringContainsString('Admission Fee', $content);
        $this->assertStringContainsString('Chinedu Adeleke', $content);
    }
}
