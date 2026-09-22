<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\DTO\NotificationResult;
use App\Models\ExternalNotification;
use App\Repositories\NotificationLogRepository;
use App\Services\NotificationService;
use App\Services\Notifications\Drivers\LogDriver;
use App\Services\Notifications\Drivers\SmtpEmailDriver;
use App\Services\Notifications\Drivers\TermiiSmsDriver;
use App\Services\Notifications\Drivers\TermiiWhatsAppDriver;
use App\Services\Notifications\Drivers\TwilioSmsDriver;
use PDO;
use PHPUnit\Framework\TestCase;

class NotificationServiceTest extends TestCase
{
    private string $tempLogFile;
    private PDO $sqlitePdo;
    private NotificationLogRepository $logRepo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tempLogFile = sys_get_temp_dir() . '/test_notifications_' . uniqid() . '.log';

        // Set up in-memory SQLite for repository testing
        $this->sqlitePdo = new PDO('sqlite::memory:');
        $this->sqlitePdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->sqlitePdo->exec("
            CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                email TEXT NOT NULL,
                phone TEXT NULL
            );

            CREATE TABLE IF NOT EXISTS external_notifications (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                channel TEXT NOT NULL,
                recipient TEXT NOT NULL,
                subject TEXT NULL,
                message_body TEXT NOT NULL,
                gateway_provider TEXT NOT NULL,
                gateway_reference TEXT NULL,
                event_type TEXT NOT NULL DEFAULT 'general',
                status TEXT NOT NULL DEFAULT 'queued',
                error_message TEXT NULL,
                retry_count INTEGER NOT NULL DEFAULT 0,
                user_id INTEGER NULL,
                metadata TEXT NULL,
                sent_at TEXT NULL,
                created_at TEXT NOT NULL,
                updated_at TEXT NOT NULL
            );
        ");

        $this->logRepo = new NotificationLogRepository($this->sqlitePdo);
    }

    protected function tearDown(): void
    {
        if (file_exists($this->tempLogFile)) {
            @unlink($this->tempLogFile);
        }
        parent::tearDown();
    }

    public function testNotificationResultDto(): void
    {
        $success = NotificationResult::success('log', 'MSG-123', ['recipient' => 'test@claret.edu']);
        $this->assertTrue($success->success);
        $this->assertSame('log', $success->gateway);
        $this->assertSame('MSG-123', $success->messageId);
        $this->assertNull($success->error);
        $this->assertSame('test@claret.edu', $success->metadata['recipient']);

        $failure = NotificationResult::failure('termii', 'Insufficient wallet balance');
        $this->assertFalse($failure->success);
        $this->assertSame('termii', $failure->gateway);
        $this->assertSame('Insufficient wallet balance', $failure->error);
        $this->assertNull($failure->messageId);
    }

    public function testLogDriverWritesToFile(): void
    {
        $driver = new LogDriver($this->tempLogFile);
        $result = $driver->send('parent@test.com', 'Hello Parent', ['subject' => 'Test Subject']);

        $this->assertTrue($result->success);
        $this->assertSame('log', $result->gateway);
        $this->assertNotEmpty($result->messageId);

        $this->assertFileExists($this->tempLogFile);
        $content = file_get_contents($this->tempLogFile);
        $this->assertStringContainsString('parent@test.com', $content);
        $this->assertStringContainsString('Hello Parent', $content);
        $this->assertStringContainsString('Test Subject', $content);
    }

    public function testNotificationLogRepositoryOperations(): void
    {
        // 1. Log creation
        $logId = $this->logRepo->log(
            channel: 'sms',
            recipient: '+2348012345678',
            messageBody: 'Your child was absent.',
            subject: null,
            userId: 5,
            eventType: 'student_absence',
            status: 'queued',
            gatewayProvider: 'log',
            metadata: ['student_id' => 10]
        );

        $this->assertGreaterThan(0, $logId);

        // 2. Status update
        $updated = $this->logRepo->updateStatus(
            $logId,
            status: 'sent',
            gatewayReference: 'GW-REF-999'
        );
        $this->assertTrue($updated);

        // 3. Retrieve recent logs
        $logs = $this->logRepo->getRecent(10);
        $this->assertCount(1, $logs);
        $this->assertSame('sent', $logs[0]->status);
        $this->assertSame('sms', $logs[0]->channel);
        $this->assertSame('+2348012345678', $logs[0]->recipient);
        $this->assertSame('student_absence', $logs[0]->eventType);

        // 4. Test stats aggregation
        $stats = $this->logRepo->getStats();
        $this->assertSame(1, $stats['total']);
        $this->assertSame(1, $stats['sent']);
        $this->assertSame(0, $stats['failed']);
        $this->assertSame(1, $stats['sms']);
        $this->assertSame(0, $stats['email']);
    }

    public function testDriversImplementInterface(): void
    {
        $logDriver = new LogDriver();
        $this->assertSame('log', $logDriver->getName());

        $smtpDriver = new SmtpEmailDriver('smtp.mailgun.org', 587, 'user', 'pass');
        $this->assertSame('smtp', $smtpDriver->getName());

        $termiiSms = new TermiiSmsDriver('fake_api_key', 'Claret');
        $this->assertSame('termii', $termiiSms->getName());

        $twilioSms = new TwilioSmsDriver('AC_fake', 'token_fake', '+1234567890');
        $this->assertSame('twilio', $twilioSms->getName());

        $termiiWa = new TermiiWhatsAppDriver('fake_api_key', '2348000000000');
        $this->assertSame('termii_whatsapp', $termiiWa->getName());
    }

    public function testEmailTemplateRendering(): void
    {
        $logDriver = new LogDriver($this->tempLogFile);
        $service = new NotificationService(
            logRepo: $this->logRepo,
            emailDriver: $logDriver,
            smsDriver: $logDriver,
            whatsAppDriver: $logDriver
        );

        // Absence Alert
        $absenceHtml = $service->renderEmailTemplate('absence_alert', [
            'studentName' => 'Chukwuemeka Okonkwo',
            'admissionNumber' => 'STD-2026-0042',
            'className' => 'JSS 1 (Gold)',
            'date' => '2026-09-22',
            'remarks' => 'Unexcused absence',
            'portalUrl' => 'https://lms.test/login',
        ]);
        $this->assertStringContainsString('Chukwuemeka Okonkwo', $absenceHtml);
        $this->assertStringContainsString('STD-2026-0042', $absenceHtml);
        $this->assertStringContainsString('Claret International School', $absenceHtml);

        // Fee Receipt
        $feeHtml = $service->renderEmailTemplate('fee_receipt', [
            'payerName' => 'Chief Adeyemi',
            'studentName' => 'Folake Adeyemi',
            'invoiceNumber' => 'INV-2026-1001',
            'reference' => 'PAY-2026-X89',
            'amountPaid' => 150000.00,
            'balanceRemaining' => 0.00,
            'termName' => 'First Term 2026/2027',
            'paymentDate' => '22 Sep 2026',
            'receiptUrl' => 'https://lms.test/payments',
        ]);
        $this->assertStringContainsString('Chief Adeyemi', $feeHtml);
        $this->assertStringContainsString('150,000.00', $feeHtml);
        $this->assertStringContainsString('PAY-2026-X89', $feeHtml);

        // Admission Approved
        $admHtml = $service->renderEmailTemplate('admission_approved', [
            'parentName' => 'Dr. Ibrahim',
            'studentName' => 'Zainab Ibrahim',
            'admissionNumber' => 'STD-2026-0099',
            'className' => 'SS 1 (Science)',
            'email' => 'zainab@student.claret.edu.ng',
            'defaultPassword' => 'Claret@2026!',
            'portalUrl' => 'https://lms.test/login',
        ]);
        $this->assertStringContainsString('Zainab Ibrahim', $admHtml);
        $this->assertStringContainsString('Claret@2026!', $admHtml);
        $this->assertStringContainsString('STD-2026-0099', $admHtml);

        // Password Reset
        $pwdHtml = $service->renderEmailTemplate('password_reset', [
            'name' => 'Teacher Ngozi',
            'email' => 'ngozi@claret.edu.ng',
            'resetUrl' => 'https://lms.test/reset-password?token=secret123',
        ]);
        $this->assertStringContainsString('Teacher Ngozi', $pwdHtml);
        $this->assertStringContainsString('secret123', $pwdHtml);

        // Result Published
        $resHtml = $service->renderEmailTemplate('result_published', [
            'className' => 'JSS 3 (Silver)',
            'termName' => 'Third Term',
            'sessionName' => '2025/2026',
            'portalUrl' => 'https://lms.test/parent/dashboard',
        ]);
        $this->assertStringContainsString('JSS 3 (Silver)', $resHtml);
        $this->assertStringContainsString('Third Term', $resHtml);
    }

    public function testSpecializedNotificationMethods(): void
    {
        $logDriver = new LogDriver($this->tempLogFile);
        $service = new NotificationService(
            logRepo: $this->logRepo,
            emailDriver: $logDriver,
            smsDriver: $logDriver,
            whatsAppDriver: $logDriver
        );

        // 1. Absence Alert
        $absenceRes = $service->sendAbsenceAlert(
            parentPhone: '+2348011112222',
            parentEmail: 'parent@gmail.com',
            studentName: 'Tunde Bakare',
            admissionNumber: 'STD-2026-001',
            className: 'JSS 1 A',
            date: '2026-09-22',
            remarks: 'Homeroom Roll Call',
            userId: 10
        );
        $this->assertArrayHasKey('sms', $absenceRes);
        $this->assertArrayHasKey('email', $absenceRes);
        $this->assertTrue($absenceRes['sms']->success);
        $this->assertTrue($absenceRes['email']->success);

        // 2. Fee Payment Receipt
        $feeRes = $service->sendFeePaymentReceipt(
            payerPhone: '+2348099998888',
            payerEmail: 'bursary.payer@gmail.com',
            payerName: 'Mr. Okafor',
            studentName: 'Kalu Okafor',
            invoiceNumber: 'INV-2026-005',
            reference: 'PAY-202609-ABCD',
            amountPaid: 85000.00,
            balanceRemaining: 15000.00,
            termName: 'First Term',
            userId: 12
        );
        $this->assertTrue($feeRes['sms']->success);
        $this->assertTrue($feeRes['email']->success);

        // 3. Admission Approved Notice
        $admRes = $service->sendAdmissionApprovedNotice(
            parentPhone: '+2348077776666',
            parentEmail: 'applicant@gmail.com',
            parentName: 'Mrs. Balogun',
            studentName: 'Tomi Balogun',
            admissionNumber: 'STD-2026-0055',
            className: 'JSS 1 (Diamond)',
            defaultPassword: 'Claret@2026!',
            userId: 15
        );
        $this->assertTrue($admRes['sms']->success);
        $this->assertTrue($admRes['email']->success);

        // 4. Password Reset Email
        $pwdRes = $service->sendPasswordResetEmail(
            email: 'user@claret.edu.ng',
            name: 'Staff Member',
            resetToken: 'test_token_abc_123',
            userId: 20
        );
        $this->assertTrue($pwdRes->success);

        // 5. Result Release Broadcast
        $broadcastRes = $service->sendResultReleaseBroadcast(
            phone: '+2348033334444',
            email: 'parent.results@gmail.com',
            className: 'SS 2 (Emerald)',
            termName: 'Second Term',
            sessionName: '2025/2026',
            userId: 25
        );
        $this->assertTrue($broadcastRes['sms']->success);
        $this->assertTrue($broadcastRes['email']->success);

        // 6. Test Diagnostic Console Dispatch
        $testSms = $service->sendTestNotification('sms', '+2348000000000', 'Diagnostic ping');
        $this->assertTrue($testSms->success);

        $testEmail = $service->sendTestNotification('email', 'admin@claret.edu.ng', 'Diagnostic ping');
        $this->assertTrue($testEmail->success);

        $testWa = $service->sendTestNotification('whatsapp', '+2348000000000', 'Diagnostic ping');
        $this->assertTrue($testWa->success);

        // Verify total logs in database
        $logs = $this->logRepo->getRecent(50);
        $this->assertGreaterThanOrEqual(10, count($logs));
    }
}
