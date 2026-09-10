<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Controllers\PublicResultCheckerController;
use App\Core\Config;
use App\Core\Request;
use App\Core\Response;
use App\Core\UserContext;
use App\DTO\ServiceResult;
use App\Models\Payment;
use App\Models\ResultAccessPin;
use App\Repositories\AcademicRepository;
use App\Repositories\PaymentRepository;
use App\Repositories\ResultPinRepository;
use App\Repositories\ResultPublicationRepository;
use App\Repositories\StudentRepository;
use App\Services\PaymentService;
use App\Services\ReportCardService;
use App\Services\ResultPinService;
use PDO;
use PHPUnit\Framework\TestCase;

class ResultPinAndPaymentIntegrationTest extends TestCase
{
    private PDO $db;
    private PaymentRepository $paymentRepo;
    private ResultPinRepository $pinRepo;
    private ResultPinService $pinService;
    private PaymentService $paymentService;
    private StudentRepository $studentRepo;
    private AcademicRepository $academicRepo;
    private ResultPublicationRepository $publicationRepo;

    private int $userId = 1;
    private int $studentId = 1;
    private int $sessionId = 1;
    private int $termId = 1;

    protected function setUp(): void
    {
        Config::reset();
        Config::load(dirname(__DIR__, 2) . '/config/.env');

        $this->db = new PDO('sqlite::memory:');
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $this->db->exec("
            CREATE TABLE users (
                id INTEGER PRIMARY KEY AUTOINCREMENT, 
                uuid TEXT DEFAULT 'user-uuid-1',
                name TEXT, 
                email TEXT, 
                phone TEXT DEFAULT '08012345678',
                status TEXT DEFAULT 'active'
            );

            CREATE TABLE students (
                id INTEGER PRIMARY KEY AUTOINCREMENT, 
                user_id INTEGER, 
                admission_number TEXT, 
                date_of_birth TEXT DEFAULT '2010-01-01',
                gender TEXT DEFAULT 'male',
                current_class_id INTEGER DEFAULT 1
            );

            CREATE TABLE sessions (
                id INTEGER PRIMARY KEY AUTOINCREMENT, 
                name TEXT, 
                start_date TEXT DEFAULT '2025-09-01',
                end_date TEXT DEFAULT '2026-07-31',
                status TEXT DEFAULT 'active', 
                is_current INTEGER DEFAULT 1
            );

            CREATE TABLE terms (
                id INTEGER PRIMARY KEY AUTOINCREMENT, 
                session_id INTEGER, 
                name TEXT, 
                start_date TEXT DEFAULT '2026-05-01',
                end_date TEXT DEFAULT '2026-07-31',
                status TEXT DEFAULT 'active', 
                is_current INTEGER DEFAULT 1
            );

            CREATE TABLE classes (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT,
                section_arm TEXT,
                academic_level_id INTEGER DEFAULT 1,
                status TEXT DEFAULT 'active'
            );

            CREATE TABLE class_enrollments (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                session_id INTEGER,
                class_id INTEGER,
                student_id INTEGER,
                status TEXT DEFAULT 'active'
            );

            CREATE TABLE payments (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                reference TEXT UNIQUE NOT NULL,
                user_id INTEGER NOT NULL,
                student_id INTEGER NOT NULL,
                session_id INTEGER NOT NULL,
                term_id INTEGER NOT NULL,
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

            CREATE TABLE result_access_pins (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                serial_number TEXT UNIQUE NOT NULL,
                pin_code TEXT NOT NULL,
                pin_hash TEXT UNIQUE NOT NULL,
                payment_id INTEGER NULL,
                student_id INTEGER NULL,
                session_id INTEGER NULL,
                term_id INTEGER NULL,
                created_by INTEGER NULL,
                max_uses INTEGER NOT NULL DEFAULT 5,
                times_used INTEGER NOT NULL DEFAULT 0,
                status TEXT NOT NULL DEFAULT 'active',
                first_used_at TEXT NULL,
                last_used_at TEXT NULL,
                created_at TEXT DEFAULT CURRENT_TIMESTAMP,
                updated_at TEXT DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE result_publications (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                term_id INTEGER NOT NULL,
                session_id INTEGER NULL,
                class_id INTEGER NULL,
                status TEXT NOT NULL DEFAULT 'published',
                published_at TEXT NULL,
                published_by INTEGER NULL,
                unpublished_at TEXT NULL,
                unpublished_by INTEGER NULL,
                approved_by INTEGER NULL,
                reason TEXT NULL
            );

            CREATE TABLE parents (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                phone TEXT NULL,
                address TEXT NULL,
                created_at TEXT DEFAULT CURRENT_TIMESTAMP,
                updated_at TEXT DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE parent_student (
                parent_id INTEGER NOT NULL,
                student_id INTEGER NOT NULL,
                relationship_type TEXT DEFAULT 'parent',
                PRIMARY KEY (parent_id, student_id)
            );
        ");

        // Seed basic fixtures
        $this->db->exec("
            INSERT INTO users (id, name, email) VALUES (1, 'John Doe Parent', 'parent@claret.edu');
            INSERT INTO students (id, user_id, admission_number, current_class_id) VALUES (1, 1, 'STU/2026/001', 1);
            INSERT INTO sessions (id, name, is_current) VALUES (1, '2025/2026', 1);
            INSERT INTO terms (id, session_id, name, is_current) VALUES (1, 1, 'Third Term', 1);
            INSERT INTO classes (id, name, section_arm) VALUES (1, 'JSS 1', 'Gold');
            INSERT INTO class_enrollments (session_id, class_id, student_id) VALUES (1, 1, 1);
            INSERT INTO result_publications (term_id, session_id, status) VALUES (1, 1, 'published');
        ");

        $this->paymentRepo = new PaymentRepository($this->db);
        $this->pinRepo = new ResultPinRepository($this->db);
        $this->studentRepo = new StudentRepository($this->db);
        $this->academicRepo = new AcademicRepository($this->db);
        $this->publicationRepo = new ResultPublicationRepository($this->db);
        $enrollmentRepo = new \App\Repositories\EnrollmentRepository($this->db);

        $this->pinService = new ResultPinService($this->pinRepo, $this->studentRepo, $this->academicRepo, $enrollmentRepo);
        $this->paymentService = new PaymentService(
            $this->paymentRepo,
            $this->pinRepo,
            $this->pinService,
            $this->studentRepo,
            $this->academicRepo
        );
    }

    public function testPaystackConfigurationKeysAreLoaded(): void
    {
        $pubKey = $this->paymentService->getPublicKey();
        $secKey = $this->paymentService->getSecretKey();

        $this->assertNotEmpty($pubKey, 'Paystack public key should be configured.');
        $this->assertNotEmpty($secKey, 'Paystack secret key should be configured.');
        $this->assertStringStartsWith('pk_test_', $pubKey);
        $this->assertStringStartsWith('sk_test_', $secKey);
    }

    public function testBulkUnassignedPinGeneration(): void
    {
        $pins = $this->pinService->generateBulkPins(
            qty: 5,
            maxUses: 5,
            sessionId: $this->sessionId,
            termId: $this->termId,
            adminId: $this->userId
        );

        $this->assertCount(5, $pins);

        foreach ($pins as $pin) {
            $this->assertMatchesRegularExpression('/^[23456789ABCDEFGHJKMNPQRSTUVWXYZ]{4}-[23456789ABCDEFGHJKMNPQRSTUVWXYZ]{4}-[23456789ABCDEFGHJKMNPQRSTUVWXYZ]{4}$/', $pin->getFormattedPin());
            $this->assertStringStartsWith('SN', $pin->serialNumber);
            $this->assertSame(5, $pin->maxUses);
            $this->assertSame(0, $pin->timesUsed);
            $this->assertSame('active', $pin->status);
            $this->assertNull($pin->studentId, 'Bulk PINs start unassigned.');
        }
    }

    public function testClassCohortPinGeneration(): void
    {
        $count = $this->pinService->generateForClassCohort(
            classId: 1,
            maxUses: 5,
            sessionId: $this->sessionId,
            termId: $this->termId,
            adminId: $this->userId
        );

        $this->assertSame(1, $count);

        $pins = $this->pinRepo->getPagedPins(10, 0);
        $this->assertCount(1, $pins);
        $this->assertSame($this->studentId, $pins[0]->studentId);
    }

    public function testSimulatedPaymentGeneratesPinAndLogsTransaction(): void
    {
        $actor = new UserContext(
            id: $this->userId,
            uuid: 'user-uuid-1',
            name: 'John Doe Parent',
            email: 'parent@claret.edu',
            roles: ['parent']
        );

        // Step 1: Initiate Payment
        $initResult = $this->paymentService->initiatePinPayment(
            actor: $actor,
            studentId: $this->studentId,
            sessionId: $this->sessionId,
            termId: $this->termId,
            amount: 1500.0
        );

        $this->assertTrue($initResult->success);
        /** @var Payment $payment */
        $payment = $initResult->data;
        $this->assertStringStartsWith('PAY-', $payment->reference);
        $this->assertSame('pending', $payment->status);
        $this->assertSame(1500.0, (float)$payment->amount);

        // Step 2: Simulate Payment Confirmation
        $simResult = $this->paymentService->simulateSuccessfulPayment($payment->reference);
        $this->assertTrue($simResult->success);

        $data = $simResult->data;
        /** @var Payment $confirmedPayment */
        $confirmedPayment = $data['payment'];
        /** @var ResultAccessPin $generatedPin */
        $generatedPin = $data['pin'];

        $this->assertSame('successful', $confirmedPayment->status);
        $this->assertNotNull($confirmedPayment->paidAt);
        $this->assertNotNull($generatedPin);
        $this->assertSame($this->studentId, $generatedPin->studentId);
        $this->assertSame(5, $generatedPin->maxUses);
        $this->assertSame(0, $generatedPin->timesUsed);
        $this->assertSame(5, $generatedPin->getRemainingUses());

        // Step 3: Receipt Dossier contains PIN and Attempts Left
        $receipt = $this->paymentService->getReceiptData($payment->reference);
        $this->assertNotNull($receipt);
        $this->assertSame(5, $receipt['attempts_remaining']);
        $this->assertSame($generatedPin->serialNumber, $receipt['pin']->serialNumber);
        $this->assertSame($generatedPin->getFormattedPin(), $receipt['pin']->getFormattedPin());
    }

    public function testPinVerificationAndUsageDeduction(): void
    {
        // 1. Generate unassigned bulk PIN
        $pins = $this->pinService->generateBulkPins(1, 5, $this->sessionId, $this->termId, $this->userId);
        $pin = $pins[0];
        $rawCode = $pin->getFormattedPin();

        // 2. First verification: binds to student STU/2026/001 and decrements to 4 uses
        $v1 = $this->pinService->verifyAndConsumePin('STU/2026/001', $rawCode, $this->sessionId, $this->termId);
        $this->assertTrue($v1->success);
        $this->assertSame(4, $v1->data['remaining_uses']);

        // Check active clearance in student access check
        $cleared = $this->pinService->checkStudentAccess($this->studentId, $this->sessionId, $this->termId);
        $this->assertNotNull($cleared);
        $this->assertSame(4, $cleared->getRemainingUses());

        // 3. Second student tries to use the same PIN -> REJECTED (already bound)
        $this->db->exec("INSERT INTO users (id, name, email) VALUES (2, 'Jane Student', 'jane@claret.edu')");
        $this->db->exec("INSERT INTO students (id, user_id, admission_number) VALUES (2, 2, 'STU/2026/002')");
        $v2 = $this->pinService->verifyAndConsumePin('STU/2026/002', $rawCode, $this->sessionId, $this->termId);
        $this->assertFalse($v2->success);
        $this->assertStringContainsString('already bound to another student', $v2->error);

        // 4. Consume until depleted
        for ($i = 0; $i < 4; $i++) {
            $res = $this->pinService->verifyAndConsumePin('STU/2026/001', $rawCode, $this->sessionId, $this->termId);
            $this->assertTrue($res->success);
        }

        // 5. Next attempt -> REJECTED (depleted)
        $vExhausted = $this->pinService->verifyAndConsumePin('STU/2026/001', $rawCode, $this->sessionId, $this->termId);
        $this->assertFalse($vExhausted->success);
        $this->assertStringContainsString('exceeded its maximum allowed views', $vExhausted->error);
    }

    public function testRevokedPinIsBlocked(): void
    {
        $pins = $this->pinService->generateBulkPins(1, 5, $this->sessionId, $this->termId, $this->userId);
        $pin = $pins[0];

        $this->pinRepo->revoke($pin->id);

        $res = $this->pinService->verifyAndConsumePin('STU/2026/001', $pin->getFormattedPin(), $this->sessionId, $this->termId);
        $this->assertFalse($res->success);
        $this->assertStringContainsString('revoked', $res->error);
    }

    public function testPublicResultCheckerRejectsInvalidPin(): void
    {
        $controller = new PublicResultCheckerController(
            pinService: $this->pinService,
            studentRepository: $this->studentRepo,
            academicRepository: $this->academicRepo,
            publicationRepository: $this->publicationRepo
        );

        $request = new Request(
            queryParams: [],
            postParams: [
                'admission_number' => 'STU/2026/001',
                'pin_code' => 'INVALID-PIN-1234',
                'session_id' => 1,
                'term_id' => 1,
            ],
            serverParams: ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/results/check']
        );

        $response = $controller->verify($request);
        $this->assertTrue($response->isRedirect());
        $this->assertSame('/results/check', $response->getHeader('Location'));
    }

    public function testPublicResultCheckerRejectsShortPin(): void
    {
        $controller = new PublicResultCheckerController(
            pinService: $this->pinService,
            studentRepository: $this->studentRepo,
            academicRepository: $this->academicRepo,
            publicationRepository: $this->publicationRepo
        );

        $request = new Request(
            queryParams: [],
            postParams: [
                'admission_number' => 'STU/2026/001',
                'pin_code' => 'SHORT-PIN', // less than 12 alphanumeric characters
                'session_id' => 1,
                'term_id' => 1,
            ],
            serverParams: ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/results/check']
        );

        $response = $controller->verify($request);
        $this->assertTrue($response->isRedirect());
        $this->assertSame('/results/check', $response->getHeader('Location'));
    }

    public function testCheckoutEnforcesBackendPrice(): void
    {
        $userContext = new \App\Core\UserContext(
            id: 1,
            uuid: 'user-uuid-1',
            name: 'John Doe Parent',
            email: 'parent@claret.edu',
            roles: ['parent']
        );
        $mockAuth = $this->createMock(\App\Core\AuthenticatorInterface::class);
        $mockAuth->method('user')->willReturn($userContext);
        $mockAuth->method('getUserContext')->willReturn($userContext);

        $controller = new \App\Controllers\PaymentController(
            authenticator: $mockAuth,
            paymentService: $this->paymentService,
            paymentRepository: $this->paymentRepo
        );

        // Attempt price manipulation by posting amount = 10.00
        $request = new Request(
            queryParams: [],
            postParams: [
                'student_id' => 1,
                'session_id' => 1,
                'term_id' => 1,
                'amount' => 10.00, // Attacker tries to pay ₦10 instead of ₦1,500
            ],
            serverParams: ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/payments/checkout/pin', 'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest']
        );

        $response = $controller->checkoutPin($request);
        $body = json_decode($response->getContent(), true);

        $this->assertTrue($body['success']);
        // Backend strictly enforced 1500.00
        $this->assertSame(1500.0, (float)$body['amount']);
    }

    public function testSessionBasedReportCardClearance(): void
    {
        $parentRepo = new \App\Repositories\ParentRepository($this->db);
        $this->db->exec("INSERT INTO parents (id, user_id, phone, address) VALUES (1, 1, '08012345678', 'Claret St')");
        $this->db->exec("INSERT INTO parent_student (parent_id, student_id, relationship_type) VALUES (1, 1, 'father')");

        $userContext = new \App\Core\UserContext(
            id: 1,
            uuid: 'user-uuid-1',
            name: 'John Doe Parent',
            email: 'parent@claret.edu',
            roles: ['parent']
        );
        $mockAuth = $this->createMock(\App\Core\AuthenticatorInterface::class);
        $mockAuth->method('user')->willReturn($userContext);
        $mockAuth->method('getUserContext')->willReturn($userContext);

        $gradebookRepo = new \App\Repositories\GradebookRepository($this->db);
        $mockReportCardService = $this->createMock(\App\Services\ReportCardService::class);
        $mockReportCardService->method('getReportCardData')->willReturn([
            'student' => $this->studentRepo->findById(1),
            'results' => [],
            'subject_results' => [],
            'class_stats' => ['class_avg' => 70, 'highest_avg' => 95, 'lowest_avg' => 50],
            'summary' => null,
            'settings' => [],
            'term' => $this->academicRepo->findTermById(1),
            'session' => $this->academicRepo->findSessionById(1),
            'class' => null,
            'subjects' => [],
            'skills' => [],
            'remarks' => [],
            'attendance' => null,
        ]);

        $parentController = new \App\Controllers\Parent\ReportCardController(
            authenticator: $mockAuth,
            reportCardService: $mockReportCardService,
            gradebookRepo: $gradebookRepo,
            publicationRepo: $this->publicationRepo,
            parentRepo: $parentRepo,
            studentRepo: $this->studentRepo,
            academicRepo: $this->academicRepo,
            pinService: $this->pinService
        );

        // Publish results for term 1
        $this->publicationRepo->publish(1, null, 1);

        // 1. Initial visit without entering PIN -> PIN Gate returned
        $req1 = new Request(
            queryParams: ['term_id' => 1],
            serverParams: ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/parent/children/1/grades/report-card']
        );
        $res1 = $parentController->show($req1, 1, 1);
        $this->assertStringContainsString('Scratch-Card PIN Required', $res1->getContent());

        // 2. Generate PIN and submit unlock form
        $pins = $this->pinService->generateBulkPins(1, 5, $this->sessionId, $this->termId, $this->userId);
        $pin = $pins[0];
        $formattedPin = $pin->getFormattedPin();

        $unlockReq = new Request(
            queryParams: [],
            postParams: [
                'pin_code' => $formattedPin,
                'session_id' => 1,
                'term_id' => 1,
            ],
            serverParams: ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/parent/children/1/grades/unlock']
        );
        $unlockRes = $parentController->unlock($unlockReq, 1);
        $this->assertTrue($unlockRes->isRedirect());

        // 3. Now viewing report card in this active session succeeds and shows remaining uses
        $res2 = $parentController->show($req1, 1, 1);
        $this->assertStringContainsString('REPORT CARD', strtoupper($res2->getContent()));
        $this->assertStringContainsString('4', $res2->getContent()); // 4 views remaining

        // 4. Simulate Logout and re-login (clearing active session key)
        \App\Core\Session::remove('_unlocked_pin_1_1');

        // 5. Fresh login MUST require PIN Gate again
        $res3 = $parentController->show($req1, 1, 1);
        $this->assertStringContainsString('Scratch-Card PIN Required', $res3->getContent());

        // 6. Submitting PIN in new session unlocks it for that session and consumes 1 view
        $unlockRes2 = $parentController->unlock($unlockReq, 1);
        $this->assertTrue($unlockRes2->isRedirect());

        $res4 = $parentController->show($req1, 1, 1);
        $this->assertStringContainsString('REPORT CARD', strtoupper($res4->getContent()));
        $this->assertStringContainsString('3', $res4->getContent());

        $refreshedPin = $this->pinRepo->findById($pin->id);
        $this->assertSame(2, $refreshedPin->timesUsed);
        $this->assertSame(3, $refreshedPin->getRemainingUses());
    }

    public function testPublicResultCheckerPRGAndRefreshProtection(): void
    {
        $mockReportCardService = $this->createMock(\App\Services\ReportCardService::class);
        $mockReportCardService->method('getReportCardData')->willReturn([
            'student' => $this->studentRepo->findById(1),
            'results' => [],
            'subject_results' => [],
            'class_stats' => ['class_avg' => 70, 'highest_avg' => 95, 'lowest_avg' => 50],
            'summary' => null,
            'settings' => [],
            'term' => $this->academicRepo->findTermById(1),
            'session' => $this->academicRepo->findSessionById(1),
            'class' => null,
            'subjects' => [],
            'skills' => [],
            'remarks' => [],
            'attendance' => null,
        ]);

        $controller = new PublicResultCheckerController(
            pinService: $this->pinService,
            studentRepository: $this->studentRepo,
            academicRepository: $this->academicRepo,
            publicationRepository: $this->publicationRepo,
            reportCardService: $mockReportCardService
        );

        $this->publicationRepo->publish(1, null, 1);

        $pins = $this->pinService->generateBulkPins(1, 5, $this->sessionId, $this->termId, $this->userId);
        $pin = $pins[0];

        // 1. Submit PIN via POST to /results/check
        $postReq = new Request(
            queryParams: [],
            postParams: [
                'admission_number' => 'STU/2026/001',
                'pin_code' => $pin->getFormattedPin(),
                'session_id' => 1,
                'term_id' => 1,
            ],
            serverParams: ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/results/check']
        );

        $postRes = $controller->verify($postReq);

        // PRG Pattern: Redirects to /results/view?student_id=1&term_id=1
        $this->assertTrue($postRes->isRedirect());
        $this->assertSame('/results/view?student_id=1&term_id=1', $postRes->getHeader('Location'));

        // Consumed exactly 1 view attempt
        $checkedPin = $this->pinRepo->findById($pin->id);
        $this->assertSame(1, $checkedPin->timesUsed);
        $this->assertSame(4, $checkedPin->getRemainingUses());

        // 2. Browser loads GET /results/view?student_id=1&term_id=1
        $getReq = new Request(
            queryParams: ['student_id' => 1, 'term_id' => 1],
            serverParams: ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/results/view']
        );
        $getRes1 = $controller->viewReport($getReq);
        $this->assertSame(200, $getRes1->getStatusCode());
        $this->assertStringContainsString('REPORT CARD', strtoupper($getRes1->getContent()));

        // 3. User refreshes browser (F5 / reload) -> loads GET /results/view again!
        $getRes2 = $controller->viewReport($getReq);
        $this->assertSame(200, $getRes2->getStatusCode());
        $this->assertStringContainsString('REPORT CARD', strtoupper($getRes2->getContent()));

        // Verification: Refresh did NOT increment the count! Times used remains 1.
        $afterRefreshPin = $this->pinRepo->findById($pin->id);
        $this->assertSame(1, $afterRefreshPin->timesUsed);
        $this->assertSame(4, $afterRefreshPin->getRemainingUses());
    }

    public function testPaymentRepositorySearchAndStatusFilter(): void
    {
        $ref1 = 'PAY-202609-TEST1111';
        $ref2 = 'PAY-202609-TEST2222';

        $this->paymentRepo->create([
            'reference' => $ref1,
            'user_id' => $this->userId,
            'student_id' => $this->studentId,
            'session_id' => $this->sessionId,
            'term_id' => $this->termId,
            'purpose' => 'Result Access PIN',
            'amount' => 2500.0,
            'currency' => 'NGN',
            'channel' => 'paystack',
            'status' => 'success',
            'gateway_reference' => 'gw_1',
            'paid_at' => date('Y-m-d H:i:s'),
        ]);

        $this->paymentRepo->create([
            'reference' => $ref2,
            'user_id' => $this->userId,
            'student_id' => $this->studentId,
            'session_id' => $this->sessionId,
            'term_id' => $this->termId,
            'purpose' => 'Result Access PIN',
            'amount' => 2500.0,
            'currency' => 'NGN',
            'channel' => 'paystack',
            'status' => 'pending',
            'gateway_reference' => null,
            'paid_at' => null,
        ]);

        // Search by reference query
        $foundByRef = $this->paymentRepo->getAllPayments(limit: 10, offset: 0, status: null, search: 'TEST1111');
        $this->assertCount(1, $foundByRef);
        $this->assertSame($ref1, $foundByRef[0]->reference);

        $countByRef = $this->paymentRepo->countPayments(status: null, search: 'TEST1111');
        $this->assertSame(1, $countByRef);

        // Filter by status
        $pendingPayments = $this->paymentRepo->getAllPayments(limit: 10, offset: 0, status: 'pending', search: null);
        $this->assertGreaterThanOrEqual(1, count($pendingPayments));
        $this->assertSame('pending', $pendingPayments[0]->status);

        // Filter by both status and search query
        $matched = $this->paymentRepo->getAllPayments(limit: 10, offset: 0, status: 'pending', search: 'TEST2222');
        $this->assertCount(1, $matched);
        $this->assertSame($ref2, $matched[0]->reference);

        // Filter when search doesn't match
        $none = $this->paymentRepo->getAllPayments(limit: 10, offset: 0, status: 'success', search: 'NONEXISTENT');
        $this->assertCount(0, $none);
        $this->assertSame(0, $this->paymentRepo->countPayments(status: 'success', search: 'NONEXISTENT'));
    }
}

