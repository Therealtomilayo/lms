<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Config;
use App\Core\UserContext;
use App\DTO\ServiceResult;
use App\Models\Payment;
use App\Models\ResultAccessPin;
use App\Repositories\AcademicRepository;
use App\Repositories\PaymentRepository;
use App\Repositories\ResultPinRepository;
use App\Repositories\StudentRepository;

/**
 * Service for Paystack-Ready Commerce, Checkout Simulation, Transaction Logging & Receipts
 */
class PaymentService
{
    public const float PIN_PRICE_NGN = 1500.00;

    private PaymentRepository $paymentRepository;
    private ResultPinRepository $pinRepository;
    private ResultPinService $pinService;
    private StudentRepository $studentRepository;
    private AcademicRepository $academicRepository;

    public function __construct(
        ?PaymentRepository $paymentRepository = null,
        ?ResultPinRepository $pinRepository = null,
        ?ResultPinService $pinService = null,
        ?StudentRepository $studentRepository = null,
        ?AcademicRepository $academicRepository = null
    ) {
        $this->paymentRepository = $paymentRepository ?? new PaymentRepository();
        $this->pinRepository = $pinRepository ?? new ResultPinRepository();
        $this->pinService = $pinService ?? new ResultPinService($this->pinRepository);
        $this->studentRepository = $studentRepository ?? new StudentRepository();
        $this->academicRepository = $academicRepository ?? new AcademicRepository();
    }

    public function getPublicKey(): string
    {
        return (string)Config::get('paystack.public_key', '');
    }

    public function getSecretKey(): string
    {
        return (string)Config::get('paystack.secret_key', '');
    }

    public function generateReference(): string
    {
        return 'PAY-' . date('Ym') . '-' . strtoupper(bin2hex(random_bytes(4)));
    }

    /**
     * Initiate online purchase of a Result Access PIN.
     */
    public function initiatePinPayment(
        UserContext $actor,
        int $studentId,
        int $sessionId,
        int $termId,
        float $amount = 1500.0
    ): ServiceResult {
        $student = $this->studentRepository->findById($studentId);
        if (!$student) {
            return ServiceResult::error("Student #{$studentId} not found.");
        }

        $session = $this->academicRepository->findSessionById($sessionId);
        $term = $this->academicRepository->findTermById($termId);
        if (!$session || !$term) {
            return ServiceResult::error("Academic session or term not found.");
        }

        $reference = $this->generateReference();

        $payment = $this->paymentRepository->create([
            'reference' => $reference,
            'user_id' => $actor->getUserId(),
            'student_id' => $studentId,
            'session_id' => $sessionId,
            'term_id' => $termId,
            'purpose' => Payment::PURPOSE_RESULT_PIN,
            'amount' => $amount,
            'currency' => 'NGN',
            'channel' => 'paystack_simulated',
            'status' => Payment::STATUS_PENDING,
            'metadata' => [
                'payer_name' => $actor->name,
                'payer_email' => $actor->email,
                'student_name' => $student->name,
                'admission_number' => $student->admissionNumber,
                'session_name' => $session->name,
                'term_name' => $term->name,
                'item_description' => "Result Access Scratch-Card PIN ({$session->name} - {$term->name})",
            ],
        ]);

        return ServiceResult::success($payment);
    }

    /**
     * Simulate successful payment confirmation (matches Paystack webhook/callback behavior).
     * Automatically provisions a cryptographic PIN, binds it to the student, and updates transaction ledger.
     */
    public function simulateSuccessfulPayment(string $reference): ServiceResult
    {
        $payment = $this->paymentRepository->findByReference($reference);
        if (!$payment) {
            return ServiceResult::error("Payment transaction {$reference} not found.");
        }

        if ($payment->isSuccessful()) {
            // Already processed; find existing PIN
            $existingPin = $this->pinRepository->findByPaymentId($payment->id);
            return ServiceResult::success([
                'payment' => $payment,
                'pin' => $existingPin,
                'already_processed' => true,
            ]);
        }

        $now = date('Y-m-d H:i:s');
        $gatewayRef = 'MOCK_PSTK_' . strtoupper(bin2hex(random_bytes(6)));

        // 1. Mark payment as successful
        $this->paymentRepository->updateStatus($payment->id, Payment::STATUS_SUCCESSFUL, $gatewayRef, $now);

        // 2. Generate and bind Scratch Card PIN
        $pin = $this->pinService->createOnlinePurchasedPin(
            paymentId: $payment->id,
            studentId: $payment->studentId,
            sessionId: $payment->sessionId,
            termId: $payment->termId,
            userId: $payment->userId
        );

        $refreshedPayment = $this->paymentRepository->findById($payment->id);

        return ServiceResult::success([
            'payment' => $refreshedPayment,
            'pin' => $pin,
            'already_processed' => false,
        ]);
    }

    /**
     * Initialize transaction with Paystack API.
     */
    public function initializePaystackTransaction(Payment $payment, string $callbackUrl): ServiceResult
    {
        $secretKey = $this->getSecretKey();
        if (empty($secretKey)) {
            return ServiceResult::error("Paystack secret key is not configured.");
        }

        $url = rtrim((string)Config::get('paystack.payment_url', 'https://api.paystack.co'), '/') . '/transaction/initialize';
        $payload = [
            'email' => $payment->metadata['payer_email'] ?? 'parent@claret.edu',
            'amount' => (int)round($payment->amount * 100), // in kobo
            'reference' => $payment->reference,
            'callback_url' => $callbackUrl,
            'metadata' => array_merge($payment->metadata ?? [], [
                'payment_id' => $payment->id,
                'student_id' => $payment->studentId,
                'session_id' => $payment->sessionId,
                'term_id' => $payment->termId,
            ]),
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $secretKey,
            'Content-Type: application/json',
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        if ($err) {
            return ServiceResult::error("Paystack network communication error: {$err}");
        }

        $body = json_decode((string)$response, true);
        if ($httpCode !== 200 || empty($body['status'])) {
            return ServiceResult::error($body['message'] ?? 'Unable to initialize transaction with Paystack.');
        }

        return ServiceResult::success($body['data']);
    }

    /**
     * Verify payment status directly with Paystack API.
     */
    public function verifyPaystackPayment(string $reference): ServiceResult
    {
        $secretKey = $this->getSecretKey();
        if (empty($secretKey)) {
            return ServiceResult::error("Paystack secret key is not configured.");
        }

        $url = rtrim((string)Config::get('paystack.payment_url', 'https://api.paystack.co'), '/') . '/transaction/verify/' . rawurlencode($reference);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $secretKey,
            'Content-Type: application/json',
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        if ($err) {
            return ServiceResult::error("Paystack verification communication error: {$err}");
        }

        $body = json_decode((string)$response, true);
        if ($httpCode !== 200 || empty($body['status']) || ($body['data']['status'] ?? '') !== 'success') {
            return ServiceResult::error($body['message'] ?? 'Payment verification failed or was not successful.');
        }

        $payment = $this->paymentRepository->findByReference($reference);
        if (!$payment) {
            return ServiceResult::error("Payment transaction {$reference} not found in database.");
        }

        // Strict Backend Security: Verify Currency and Amount Paid against Paystack API Data
        $paystackData = $body['data'] ?? [];
        $returnedCurrency = strtoupper((string)($paystackData['currency'] ?? ''));
        if ($returnedCurrency !== 'NGN') {
            return ServiceResult::error("Security validation failed: Payment currency mismatch. Expected NGN, received {$returnedCurrency}.");
        }

        $expectedKobo = (int)round($payment->amount * 100);
        $actualKobo = (int)($paystackData['amount'] ?? 0);
        if ($actualKobo < $expectedKobo) {
            return ServiceResult::error("Security validation failed: Underpayment detected. Expected {$expectedKobo} kobo, received {$actualKobo} kobo.");
        }

        if ($payment->isSuccessful()) {
            $existingPin = $this->pinRepository->findByPaymentId($payment->id);
            return ServiceResult::success([
                'payment' => $payment,
                'pin' => $existingPin,
                'already_processed' => true,
            ]);
        }

        $gatewayRef = $body['data']['id'] ?? $body['data']['reference'] ?? 'PSTK_' . bin2hex(random_bytes(6));
        $paidAt = isset($body['data']['paid_at']) ? date('Y-m-d H:i:s', strtotime($body['data']['paid_at'])) : date('Y-m-d H:i:s');

        // 1. Mark payment successful
        $this->paymentRepository->updateStatus($payment->id, Payment::STATUS_SUCCESSFUL, (string)$gatewayRef, $paidAt);

        // 2. Generate and bind Scratch Card PIN
        $pin = $this->pinService->createOnlinePurchasedPin(
            paymentId: $payment->id,
            studentId: $payment->studentId,
            sessionId: $payment->sessionId,
            termId: $payment->termId,
            userId: $payment->userId
        );

        $refreshedPayment = $this->paymentRepository->findById($payment->id);

        return ServiceResult::success([
            'payment' => $refreshedPayment,
            'pin' => $pin,
            'already_processed' => false,
        ]);
    }

    /**
     * Get complete printable receipt dossier for a transaction.
     */
    public function getReceiptData(string $reference): ?array
    {
        $payment = $this->paymentRepository->findByReference($reference);
        if (!$payment) {
            return null;
        }

        $student = ($payment->studentId !== null && $payment->studentId > 0) ? $this->studentRepository->findById($payment->studentId) : null;
        $pin = ($payment->purpose === Payment::PURPOSE_RESULT_PIN) ? $this->pinRepository->findByPaymentId($payment->id) : null;

        $defaultTitle = $payment->purpose === Payment::PURPOSE_ADMISSION
            ? ('Admission Application Fee — ' . ($payment->studentName ?? 'Prospective Ward'))
            : 'Result Access Scratch-Card PIN';

        return [
            'payment' => $payment,
            'student' => $student,
            'pin' => $pin,
            'receipt_number' => $payment->getReceiptNumber(),
            'item_title' => $payment->metadata['item_description'] ?? $defaultTitle,
            'attempts_remaining' => $pin ? $pin->getRemainingUses() : 0,
            'max_attempts' => $pin ? $pin->maxUses : 0,
        ];
    }
}
