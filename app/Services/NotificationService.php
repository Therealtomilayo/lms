<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Config;
use App\DTO\NotificationResult;
use App\Repositories\NotificationLogRepository;
use App\Services\Notifications\Drivers\LogDriver;
use App\Services\Notifications\Drivers\SmtpEmailDriver;
use App\Services\Notifications\Drivers\TermiiSmsDriver;
use App\Services\Notifications\Drivers\TermiiWhatsAppDriver;
use App\Services\Notifications\Drivers\TwilioSmsDriver;
use App\Services\Notifications\NotificationChannelInterface;
use Throwable;

/**
 * Unified Multi-Channel External Notification Gateway Service
 * Orchestrates Email (SMTP/HTML), SMS (Termii/Twilio), and WhatsApp Communications
 */
class NotificationService
{
    private NotificationLogRepository $logRepo;
    private NotificationChannelInterface $emailDriver;
    private NotificationChannelInterface $smsDriver;
    private NotificationChannelInterface $whatsAppDriver;
    private string $viewsPath;

    public function __construct(
        ?NotificationLogRepository $logRepo = null,
        ?NotificationChannelInterface $emailDriver = null,
        ?NotificationChannelInterface $smsDriver = null,
        ?NotificationChannelInterface $whatsAppDriver = null,
        ?string $viewsPath = null
    ) {
        $this->logRepo = $logRepo ?? new NotificationLogRepository();
        $this->emailDriver = $emailDriver ?? $this->resolveEmailDriver();
        $this->smsDriver = $smsDriver ?? $this->resolveSmsDriver();
        $this->whatsAppDriver = $whatsAppDriver ?? $this->resolveWhatsAppDriver();
        $this->viewsPath = $viewsPath ?? dirname(__DIR__) . '/Views';
    }

    private function resolveEmailDriver(): NotificationChannelInterface
    {
        $mailer = strtolower((string)Config::get('mail.mailer', 'log'));
        if ($mailer === 'smtp') {
            return new SmtpEmailDriver();
        }
        return new LogDriver();
    }

    private function resolveSmsDriver(): NotificationChannelInterface
    {
        $gateway = strtolower((string)Config::get('sms.gateway', 'log'));
        if ($gateway === 'termii') {
            return new TermiiSmsDriver();
        }
        if ($gateway === 'twilio') {
            return new TwilioSmsDriver();
        }
        return new LogDriver();
    }

    private function resolveWhatsAppDriver(): NotificationChannelInterface
    {
        $gateway = strtolower((string)Config::get('whatsapp.gateway', 'log'));
        if ($gateway === 'termii') {
            return new TermiiWhatsAppDriver();
        }
        return new LogDriver();
    }

    /**
     * Send an email notification with HTML template and plain text fallback
     */
    public function sendEmail(
        string $toEmail,
        string $subject,
        string $htmlBody,
        ?string $textBody = null,
        ?int $userId = null,
        string $eventType = 'general',
        ?array $metadata = null
    ): NotificationResult {
        $text = $textBody ?? strip_tags(str_replace(['<br>', '<br/>', '<p>', '</p>'], ["\n", "\n", "\n", "\n\n"], $htmlBody));
        
        $logId = $this->logRepo->log(
            channel: 'email',
            recipient: $toEmail,
            messageBody: $text,
            subject: $subject,
            userId: $userId,
            eventType: $eventType,
            status: 'queued',
            gatewayProvider: $this->emailDriver->getName(),
            metadata: $metadata
        );

        try {
            $result = $this->emailDriver->send($toEmail, $text, [
                'subject' => $subject,
                'html' => $htmlBody,
                'metadata' => $metadata,
            ]);

            $status = $result->success ? 'sent' : 'failed';
            $this->logRepo->updateStatus(
                $logId,
                status: $status,
                gatewayReference: $result->messageId,
                errorMessage: $result->error
            );

            return $result;
        } catch (Throwable $e) {
            $this->logRepo->updateStatus($logId, status: 'failed', errorMessage: $e->getMessage());
            return NotificationResult::failure($this->emailDriver->getName(), $e->getMessage());
        }
    }

    /**
     * Send an SMS notification
     */
    public function sendSms(
        string $toPhone,
        string $message,
        ?int $userId = null,
        string $eventType = 'general',
        ?array $metadata = null
    ): NotificationResult {
        $logId = $this->logRepo->log(
            channel: 'sms',
            recipient: $toPhone,
            messageBody: $message,
            userId: $userId,
            eventType: $eventType,
            status: 'queued',
            gatewayProvider: $this->smsDriver->getName(),
            metadata: $metadata
        );

        try {
            $result = $this->smsDriver->send($toPhone, $message, [
                'metadata' => $metadata,
            ]);

            $status = $result->success ? 'sent' : 'failed';
            $this->logRepo->updateStatus(
                $logId,
                status: $status,
                gatewayReference: $result->messageId,
                errorMessage: $result->error
            );

            return $result;
        } catch (Throwable $e) {
            $this->logRepo->updateStatus($logId, status: 'failed', errorMessage: $e->getMessage());
            return NotificationResult::failure($this->smsDriver->getName(), $e->getMessage());
        }
    }

    /**
     * Send a WhatsApp notification
     */
    public function sendWhatsApp(
        string $toPhone,
        string $message,
        ?int $userId = null,
        string $eventType = 'general',
        ?array $metadata = null
    ): NotificationResult {
        $logId = $this->logRepo->log(
            channel: 'whatsapp',
            recipient: $toPhone,
            messageBody: $message,
            userId: $userId,
            eventType: $eventType,
            status: 'queued',
            gatewayProvider: $this->whatsAppDriver->getName(),
            metadata: $metadata
        );

        try {
            $result = $this->whatsAppDriver->send($toPhone, $message, [
                'metadata' => $metadata,
            ]);

            $status = $result->success ? 'sent' : 'failed';
            $this->logRepo->updateStatus(
                $logId,
                status: $status,
                gatewayReference: $result->messageId,
                errorMessage: $result->error
            );

            return $result;
        } catch (Throwable $e) {
            $this->logRepo->updateStatus($logId, status: 'failed', errorMessage: $e->getMessage());
            return NotificationResult::failure($this->whatsAppDriver->getName(), $e->getMessage());
        }
    }

    /**
     * Render an email template wrapped inside the master branded layout
     */
    public function renderEmailTemplate(string $template, array $data, ?string $subject = null): string
    {
        $templatePath = $this->viewsPath . "/emails/{$template}.php";
        $layoutPath = $this->viewsPath . "/emails/layout.php";

        if (!file_exists($templatePath)) {
            return $data['content'] ?? '';
        }

        // Render template body
        extract($data);
        ob_start();
        include $templatePath;
        $content = ob_get_clean();

        // Render in layout if layout exists
        if (file_exists($layoutPath)) {
            ob_start();
            include $layoutPath;
            return (string)ob_get_clean();
        }

        return (string)$content;
    }

    // ==========================================
    // SPECIALIZED EVENT NOTIFICATION TRIGGERS
    // ==========================================

    /**
     * Triggered when a student is recorded absent during homeroom roll call
     */
    public function sendAbsenceAlert(
        ?string $parentPhone,
        ?string $parentEmail,
        string $studentName,
        string $admissionNumber,
        string $className,
        string $date,
        ?string $remarks = null,
        ?int $userId = null
    ): array {
        $results = [];
        $appUrl = (string)Config::get('app.url', 'https://lms.test');

        // 1. Dispatch SMS
        if (!empty($parentPhone)) {
            $smsText = "Dear Parent, {$studentName} ({$admissionNumber}) was marked ABSENT today ({$date}) at Claret International School. Homeroom: {$className}. For inquiries, contact reception.";
            $results['sms'] = $this->sendSms($parentPhone, $smsText, $userId, 'student_absence', [
                'student_name' => $studentName,
                'admission_number' => $admissionNumber,
                'class_name' => $className,
                'date' => $date,
            ]);
        }

        // 2. Dispatch Email
        if (!empty($parentEmail)) {
            $subject = "Notice of Absence: {$studentName} ({$className}) — Claret International School";
            $html = $this->renderEmailTemplate('absence_alert', [
                'studentName' => $studentName,
                'admissionNumber' => $admissionNumber,
                'className' => $className,
                'date' => $date,
                'remarks' => $remarks,
                'portalUrl' => "{$appUrl}/login",
            ], $subject);

            $results['email'] = $this->sendEmail($parentEmail, $subject, $html, null, $userId, 'student_absence', [
                'student_name' => $studentName,
                'date' => $date,
            ]);
        }

        return $results;
    }

    /**
     * Triggered upon successful school fees payment settlement
     */
    public function sendFeePaymentReceipt(
        ?string $payerPhone,
        ?string $payerEmail,
        string $payerName,
        string $studentName,
        string $invoiceNumber,
        string $reference,
        float $amountPaid,
        float $balanceRemaining,
        string $termName,
        ?int $userId = null
    ): array {
        $results = [];
        $appUrl = (string)Config::get('app.url', 'https://lms.test');

        // 1. Dispatch SMS Receipt
        if (!empty($payerPhone)) {
            $formattedAmount = number_format($amountPaid, 2);
            $formattedBal = number_format($balanceRemaining, 2);
            $smsText = "Payment Receipt: Received NGN {$formattedAmount} for {$studentName} (Inv #{$invoiceNumber}, Ref: {$reference}). Bal: NGN {$formattedBal}. Thank you for choosing Claret!";
            $results['sms'] = $this->sendSms($payerPhone, $smsText, $userId, 'fee_payment_receipt', [
                'reference' => $reference,
                'amount' => $amountPaid,
            ]);
        }

        // 2. Dispatch Rich HTML Receipt Email
        if (!empty($payerEmail)) {
            $subject = "Payment Receipt [Ref: {$reference}]: NGN " . number_format($amountPaid, 2) . " — Claret International School";
            $html = $this->renderEmailTemplate('fee_receipt', [
                'payerName' => $payerName,
                'studentName' => $studentName,
                'invoiceNumber' => $invoiceNumber,
                'reference' => $reference,
                'amountPaid' => $amountPaid,
                'balanceRemaining' => $balanceRemaining,
                'termName' => $termName,
                'paymentDate' => date('d M Y, H:i'),
                'receiptUrl' => "{$appUrl}/payments/history",
            ], $subject);

            $results['email'] = $this->sendEmail($payerEmail, $subject, $html, null, $userId, 'fee_payment_receipt', [
                'reference' => $reference,
                'invoice_number' => $invoiceNumber,
            ]);
        }

        return $results;
    }

    /**
     * Triggered when an admission application is approved and student account is created
     */
    public function sendAdmissionApprovedNotice(
        ?string $parentPhone,
        ?string $parentEmail,
        string $parentName,
        string $studentName,
        string $admissionNumber,
        string $className,
        string $defaultPassword = 'Claret@2026!',
        ?int $userId = null
    ): array {
        $results = [];
        $appUrl = (string)Config::get('app.url', 'https://lms.test');

        // 1. Dispatch SMS with credentials notice
        if (!empty($parentPhone)) {
            $smsText = "Congratulations! {$studentName}'s admission to Claret International School is APPROVED. Class: {$className}. Adm No: {$admissionNumber}. Portal: {$appUrl}/login (Default Pass: {$defaultPassword})";
            $results['sms'] = $this->sendSms($parentPhone, $smsText, $userId, 'admission_approved', [
                'admission_number' => $admissionNumber,
                'class_name' => $className,
            ]);
        }

        // 2. Dispatch Official Letter & Credentials Email
        if (!empty($parentEmail)) {
            $subject = "Admission Approved! Welcome to Claret International School — {$studentName} ({$admissionNumber})";
            $html = $this->renderEmailTemplate('admission_approved', [
                'parentName' => $parentName,
                'studentName' => $studentName,
                'admissionNumber' => $admissionNumber,
                'className' => $className,
                'email' => $parentEmail,
                'defaultPassword' => $defaultPassword,
                'portalUrl' => "{$appUrl}/login",
            ], $subject);

            $results['email'] = $this->sendEmail($parentEmail, $subject, $html, null, $userId, 'admission_approved', [
                'admission_number' => $admissionNumber,
            ]);
        }

        return $results;
    }

    /**
     * Triggered when a user requests password reset
     */
    public function sendPasswordResetEmail(
        string $email,
        string $name,
        string $resetToken,
        ?int $userId = null
    ): NotificationResult {
        $appUrl = (string)Config::get('app.url', 'https://lms.test');
        $resetUrl = "{$appUrl}/reset-password?token=" . urlencode($resetToken) . "&email=" . urlencode($email);

        $subject = "Password Reset Instructions — Claret International School Portal";
        $html = $this->renderEmailTemplate('password_reset', [
            'name' => $name,
            'email' => $email,
            'resetUrl' => $resetUrl,
        ], $subject);

        return $this->sendEmail($email, $subject, $html, null, $userId, 'password_reset', [
            'email' => $email,
        ]);
    }

    /**
     * Triggered when terminal results are published for a class arm
     */
    public function sendResultReleaseBroadcast(
        ?string $phone,
        ?string $email,
        string $className,
        string $termName,
        string $sessionName,
        ?int $userId = null
    ): array {
        $results = [];
        $appUrl = (string)Config::get('app.url', 'https://lms.test');

        if (!empty($phone)) {
            $smsText = "Claret Results Alert: Terminal report cards for {$className} ({$termName}) have been published. Check report card at: {$appUrl}/parent/dashboard";
            $results['sms'] = $this->sendSms($phone, $smsText, $userId, 'result_published');
        }

        if (!empty($email)) {
            $subject = "Official Terminal Results Released: {$className} ({$termName}) — Claret International School";
            $html = $this->renderEmailTemplate('result_published', [
                'className' => $className,
                'termName' => $termName,
                'sessionName' => $sessionName,
                'portalUrl' => "{$appUrl}/parent/dashboard",
            ], $subject);

            $results['email'] = $this->sendEmail($email, $subject, $html, null, $userId, 'result_published');
        }

        return $results;
    }

    /**
     * Test notification dispatch across a specific channel
     */
    public function sendTestNotification(string $channel, string $recipient, string $message): NotificationResult
    {
        $channel = strtolower($channel);

        if ($channel === 'email') {
            $subject = "Test Gateway Notification — Claret International School";
            $html = $this->renderEmailTemplate('generic_bulletin', [
                'title' => 'Gateway Diagnostic Test Transmission',
                'bodyText' => $message,
                'actionUrl' => (string)Config::get('app.url', 'https://lms.test'),
                'actionText' => 'Visit Portal',
            ], $subject);

            return $this->sendEmail($recipient, $subject, $html, $message, null, 'test_dispatch', ['is_test' => true]);
        }

        if ($channel === 'sms') {
            return $this->sendSms($recipient, $message, null, 'test_dispatch', ['is_test' => true]);
        }

        if ($channel === 'whatsapp') {
            return $this->sendWhatsApp($recipient, $message, null, 'test_dispatch', ['is_test' => true]);
        }

        return NotificationResult::failure('unknown', "Unsupported channel: {$channel}");
    }
}
