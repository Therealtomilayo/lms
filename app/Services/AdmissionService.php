<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Config;
use App\Core\Database;
use App\Core\Session;
use App\DTO\ServiceResult;
use App\Models\AdmissionApplication;
use App\Models\AdmissionPayment;
use App\Models\AdmissionSession;
use App\Models\AdmissionWard;
use App\Models\User;
use App\Repositories\AcademicRepository;
use App\Repositories\AdmissionRepository;
use App\Repositories\UserRepository;

/**
 * Core Application Service for Prospective Student Admissions & Applicant Management
 */
class AdmissionService
{
    private AdmissionRepository $admissionRepo;
    private UserRepository $userRepo;
    private AcademicRepository $academicRepo;
    private \App\Repositories\StudentRepository $studentRepo;
    private \App\Repositories\ParentRepository $parentRepo;
    private \PDO $pdo;

    public function __construct(
        ?AdmissionRepository $admissionRepo = null,
        ?UserRepository $userRepo = null,
        ?AcademicRepository $academicRepo = null,
        ?\App\Repositories\StudentRepository $studentRepo = null,
        ?\App\Repositories\ParentRepository $parentRepo = null,
        ?\PDO $pdo = null
    ) {
        $this->admissionRepo = $admissionRepo ?? new AdmissionRepository();
        $this->userRepo = $userRepo ?? new UserRepository();
        $this->academicRepo = $academicRepo ?? new AcademicRepository();
        $this->studentRepo = $studentRepo ?? new \App\Repositories\StudentRepository();
        $this->parentRepo = $parentRepo ?? new \App\Repositories\ParentRepository();
        $this->pdo = $pdo ?? Database::getInstance();
    }

    public function getActiveSession(): ?AdmissionSession
    {
        return $this->admissionRepo->getActiveAdmissionSession();
    }

    public function isAdmissionOpen(): bool
    {
        $session = $this->getActiveSession();
        return $session !== null && $session->isOpen();
    }

    public function generateApplicationNumber(?AdmissionSession $session = null): string
    {
        $year = date('Y');
        if ($session && !empty($session->academicSessionName)) {
            // E.g. "2026/2027" -> "2026"
            $parts = explode('/', $session->academicSessionName);
            if (!empty($parts[0])) {
                $year = trim($parts[0]);
            }
        }

        $nextSeq = $this->admissionRepo->getNextSequentialNumber();
        return sprintf('APP-%s-%06d', $year, $nextSeq);
    }

    /**
     * Register a new prospective parent/guardian applicant and establish their application docket.
     */
    public function registerApplicant(
        string $name,
        string $email,
        string $password,
        ?string $phone = null,
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): ServiceResult {
        $email = strtolower(trim($email));
        $name = trim($name);
        $phone = $phone ? trim($phone) : null;

        // 1. Availability check
        $session = $this->getActiveSession();
        if (!$session || !$session->isOpen()) {
            return ServiceResult::failure([
                'general' => ['Online admissions are currently closed. Please check back during open enrollment periods.']
            ], 'ADMISSION_CLOSED');
        }

        // 2. Validate email uniqueness
        $existing = $this->userRepo->findByEmail($email);
        if ($existing) {
            return ServiceResult::failure([
                'email' => ['An account with this email address already exists. Please sign in instead.']
            ], 'EMAIL_TAKEN');
        }

        if (mb_strlen($password) < 8) {
            return ServiceResult::failure([
                'password' => ['Password must be at least 8 characters long.']
            ], 'WEAK_PASSWORD');
        }

        return Database::transaction(function () use ($name, $email, $password, $phone, $session, $ipAddress, $userAgent) {
            // 3. Create user identity with 'applicant' role
            $uuid = sprintf(
                '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                mt_rand(0, 0xffff),
                mt_rand(0, 0xffff),
                mt_rand(0, 0xffff),
                mt_rand(0, 0x0fff) | 0x4000,
                mt_rand(0, 0x3fff) | 0x8000,
                mt_rand(0, 0xffff),
                mt_rand(0, 0xffff),
                mt_rand(0, 0xffff)
            );
            $passwordHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

            $user = $this->userRepo->create([
                'uuid' => $uuid,
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'password_hash' => $passwordHash,
                'status' => 'active',
                'must_change_password' => 0,
            ], ['applicant']);

            // 4. Create initial admission application docket
            $appNumber = $this->generateApplicationNumber($session);
            $application = $this->admissionRepo->createApplication(
                sessionId: $session->id,
                applicantUserId: $user->id,
                appNumber: $appNumber
            );

            // Log creation in status history
            $this->admissionRepo->logStatusHistory(
                applicationId: $application->id,
                fromStatus: 'none',
                toStatus: AdmissionApplication::STATUS_DRAFT,
                changedBy: $user->id,
                comment: 'Applicant registered account and initialized admission docket.'
            );

            // 5. Establish authenticated session
            Session::start();
            Session::regenerate();

            $sessionLifetime = (int)Config::get('session.lifetime', 7200);
            $expiresAt = date('Y-m-d H:i:s', time() + $sessionLifetime);
            $rawToken = bin2hex(random_bytes(32));
            $sessionHash = hash('sha256', $rawToken);

            $userAgentHash = $userAgent ? hash('sha256', $userAgent) : null;
            $ipHash = $ipAddress ? hash('sha256', $ipAddress) : null;

            $this->userRepo->createSession(
                userId: $user->id,
                sessionHash: $sessionHash,
                expiresAt: $expiresAt,
                userAgentHash: $userAgentHash,
                ipHash: $ipHash
            );

            Session::set('user_id', $user->id);
            Session::set('session_hash', $sessionHash);
            Session::set('user_name', $user->name);
            Session::set('user_email', $user->email);
            Session::set('user_roles', ['applicant']);

            return ServiceResult::success([
                'user' => $user,
                'application' => $application,
                'session' => $session,
            ]);
        });
    }

    /**
     * Get consolidated data for the applicant dashboard.
     */
    public function getApplicantDashboardData(int $applicantUserId): array
    {
        $session = $this->getActiveSession();
        $applications = $this->admissionRepo->getApplicationsByApplicant($applicantUserId);

        // If applicant has no application docket for the active session, create one
        if (empty($applications) && $session && $session->isOpen()) {
            $appNumber = $this->generateApplicationNumber($session);
            $app = $this->admissionRepo->createApplication($session->id, $applicantUserId, $appNumber);
            $applications = [$app];
        }

        return [
            'activeSession' => $session,
            'isAdmissionOpen' => $this->isAdmissionOpen(),
            'applications' => $applications,
        ];
    }

    /**
     * Get all available academic levels for ward application forms.
     */
    public function getAcademicLevels(): array
    {
        return $this->academicRepo->getAllLevels();
    }

    /**
     * Get or create the applicant's application docket for the currently active session.
     */
    public function getApplicantActiveApplication(int $applicantUserId): ?AdmissionApplication
    {
        $session = $this->getActiveSession();
        if (!$session) {
            return null;
        }

        $app = $this->admissionRepo->findApplicationByApplicantAndSession($applicantUserId, $session->id);
        if (!$app && $session->isOpen()) {
            $appNumber = $this->generateApplicationNumber($session);
            $app = $this->admissionRepo->createApplication($session->id, $applicantUserId, $appNumber);
        }

        return $app;
    }

    /**
     * Retrieve a ward ensuring IDOR tenant isolation (ward must belong to the applicant).
     */
    public function getWard(int $wardId, int $applicantUserId): ?AdmissionWard
    {
        $ward = $this->admissionRepo->findWardById($wardId);
        if (!$ward) {
            return null;
        }

        $app = $this->admissionRepo->findApplicationById($ward->applicationId);
        if (!$app || $app->applicantUserId !== $applicantUserId) {
            return null;
        }

        return $ward;
    }

    /**
     * Add a prospective ward to the applicant's current application docket.
     */
    public function addWard(int $applicantUserId, array $data): ServiceResult
    {
        $app = $this->getApplicantActiveApplication($applicantUserId);
        if (!$app) {
            return ServiceResult::error('No active admission session application docket found.');
        }

        if ($app->status !== AdmissionApplication::STATUS_DRAFT) {
            return ServiceResult::error('Cannot add new wards to an application that has already been submitted.');
        }

        $required = ['first_name', 'last_name', 'date_of_birth', 'gender', 'applying_for_level_id', 'class_grade'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                return ServiceResult::error("The {$field} field is required.");
            }
        }

        // Validate academic level
        $level = $this->academicRepo->findLevelById((int)$data['applying_for_level_id']);
        if (!$level) {
            return ServiceResult::error('Invalid academic level selected.');
        }

        $ward = $this->admissionRepo->addWard($app->id, $data);
        return ServiceResult::success($ward);
    }

    /**
     * Update an existing prospective ward's details.
     */
    public function updateWard(int $wardId, int $applicantUserId, array $data): ServiceResult
    {
        $ward = $this->getWard($wardId, $applicantUserId);
        if (!$ward) {
            return ServiceResult::error('Ward not found or access denied.');
        }

        $app = $this->admissionRepo->findApplicationById($ward->applicationId);
        if (!$app || $app->status !== AdmissionApplication::STATUS_DRAFT) {
            return ServiceResult::error('Cannot edit ward details once the application is submitted.');
        }

        if (!empty($data['applying_for_level_id'])) {
            $level = $this->academicRepo->findLevelById((int)$data['applying_for_level_id']);
            if (!$level) {
                return ServiceResult::error('Invalid academic level selected.');
            }
        }

        $this->admissionRepo->updateWard($wardId, $data);
        $updated = $this->admissionRepo->findWardById($wardId);

        return ServiceResult::success($updated);
    }

    /**
     * Delete a ward from the application docket (allowed only if unpaid and draft).
     */
    public function deleteWard(int $wardId, int $applicantUserId): ServiceResult
    {
        $ward = $this->getWard($wardId, $applicantUserId);
        if (!$ward) {
            return ServiceResult::error('Ward not found or access denied.');
        }

        $app = $this->admissionRepo->findApplicationById($ward->applicationId);
        if (!$app || $app->status !== AdmissionApplication::STATUS_DRAFT) {
            return ServiceResult::error('Cannot delete ward from a submitted application.');
        }

        if ($ward->paymentStatus === AdmissionWard::PAYMENT_PAID) {
            return ServiceResult::error('Cannot delete a ward whose application fee has already been paid.');
        }

        $this->admissionRepo->deleteWard($wardId);
        return ServiceResult::success(['deleted_ward_id' => $wardId]);
    }

    /**
     * Initialize Paystack checkout transaction for a specific prospective ward fee.
     */
    public function initializeWardPayment(int $wardId, int $applicantUserId, string $callbackUrl): ServiceResult
    {
        $ward = $this->getWard($wardId, $applicantUserId);
        if (!$ward) {
            return ServiceResult::error('Ward not found or access denied.');
        }

        if ($ward->paymentStatus === AdmissionWard::PAYMENT_PAID) {
            return ServiceResult::success([
                'already_paid' => true,
                'message' => 'Application fee has already been paid for this ward.',
            ]);
        }

        $app = $this->admissionRepo->findApplicationById($ward->applicationId);
        $session = $this->getActiveSession();
        if (!$session || !$session->isOpen()) {
            return ServiceResult::error('Admissions are currently closed. Fee payment cannot proceed.');
        }

        $user = $this->userRepo->findById($applicantUserId);
        if (!$user) {
            return ServiceResult::error('Applicant user record not found.');
        }

        $amount = (float)$session->applicationFee;
        $reference = sprintf('ADM-W%d-%s-%s', $wardId, date('YmdHis'), strtoupper(bin2hex(random_bytes(3))));

        $payment = $this->admissionRepo->createPayment([
            'application_id' => $app->id,
            'ward_id' => $wardId,
            'reference' => $reference,
            'user_id' => $applicantUserId,
            'amount' => $amount,
            'currency' => $session->currency,
            'channel' => 'paystack',
            'status' => AdmissionPayment::STATUS_PENDING,
            'metadata' => [
                'ward_name' => $ward->getFullName(),
                'class_grade' => $ward->classGrade,
                'application_number' => $app->applicationNumber,
                'session_title' => $session->title,
                'applicant_email' => $user->email,
            ],
        ]);

        $secretKey = (string)(Config::get('paystack.secret_key') ?: (getenv('PAYSTACK_SECRET_KEY') ?: ''));
        $paystackUrl = rtrim((string)Config::get('paystack.payment_url', 'https://api.paystack.co'), '/');

        // Check if Paystack secret key is configured
        if (!empty($secretKey) && !str_contains($secretKey, 'sk_test_placeholder')) {
            $amountInKobo = (int)round($amount * 100);
            $payload = [
                'email' => $user->email,
                'amount' => $amountInKobo,
                'reference' => $reference,
                'callback_url' => $callbackUrl,
                'metadata' => [
                    'payment_id' => $payment->id,
                    'ward_id' => $wardId,
                    'application_id' => $app->id,
                    'ward_name' => $ward->getFullName(),
                ],
            ];

            $ch = curl_init($paystackUrl . '/transaction/initialize');
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

            if (!$err && $httpCode === 200) {
                $body = json_decode((string)$response, true);
                if (!empty($body['status']) && !empty($body['data']['authorization_url'])) {
                    return ServiceResult::success([
                        'authorization_url' => $body['data']['authorization_url'],
                        'reference' => $reference,
                        'payment' => $payment,
                    ]);
                }
            }
        }

        // Fallback / Sandbox mode: Provide direct simulated checkout callback
        return ServiceResult::success([
            'authorization_url' => '/applicant/payment/' . $wardId . '?simulated=1&reference=' . urlencode($reference),
            'reference' => $reference,
            'payment' => $payment,
            'is_sandbox' => true,
        ]);
    }

    /**
     * Simulate successful payment confirmation (development and automated testing).
     */
    public function simulateSuccessfulWardPayment(string $reference, int $applicantUserId): ServiceResult
    {
        $payment = $this->admissionRepo->findPaymentByReference($reference);
        if (!$payment) {
            return ServiceResult::error("Payment transaction {$reference} not found.");
        }

        if ($payment->userId !== $applicantUserId) {
            return ServiceResult::error('Unauthorized access to payment reference.');
        }

        if ($payment->isSuccessful()) {
            return ServiceResult::success([
                'payment' => $payment,
                'already_processed' => true,
            ]);
        }

        $now = date('Y-m-d H:i:s');
        $gatewayRef = 'SIM_PSTK_' . strtoupper(bin2hex(random_bytes(6)));

        $this->admissionRepo->updatePaymentStatus($payment->id, AdmissionPayment::STATUS_SUCCESSFUL, $gatewayRef, $now);
        $this->admissionRepo->updateWardPaymentStatus($payment->wardId, AdmissionWard::PAYMENT_PAID);

        return ServiceResult::success([
            'payment' => $payment,
            'ward_id' => $payment->wardId,
        ]);
    }

    /**
     * Verify payment directly with Paystack API.
     */
    public function verifyPaystackWardPayment(string $reference): ServiceResult
    {
        $payment = $this->admissionRepo->findPaymentByReference($reference);
        if (!$payment) {
            return ServiceResult::error("Payment transaction {$reference} not found.");
        }

        if ($payment->isSuccessful()) {
            return ServiceResult::success([
                'payment' => $payment,
                'already_processed' => true,
            ]);
        }

        $secretKey = (string)(Config::get('paystack.secret_key') ?: (getenv('PAYSTACK_SECRET_KEY') ?: ''));
        if (empty($secretKey)) {
            return ServiceResult::error('Paystack secret key is not configured.');
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
            return ServiceResult::error("Paystack verification error: {$err}");
        }

        $body = json_decode((string)$response, true);
        if ($httpCode !== 200 || empty($body['status']) || ($body['data']['status'] ?? '') !== 'success') {
            return ServiceResult::error($body['message'] ?? 'Payment verification failed on Paystack.');
        }

        $data = $body['data'] ?? [];
        $returnedCurrency = strtoupper((string)($data['currency'] ?? ''));
        if ($returnedCurrency !== 'NGN') {
            return ServiceResult::error("Currency mismatch: expected NGN, received {$returnedCurrency}.");
        }

        $expectedKobo = (int)round($payment->amount * 100);
        $actualKobo = (int)($data['amount'] ?? 0);
        if ($actualKobo < $expectedKobo) {
            return ServiceResult::error("Underpayment detected: expected {$expectedKobo} kobo, received {$actualKobo} kobo.");
        }

        $now = date('Y-m-d H:i:s');
        $gatewayRef = (string)($data['id'] ?? $reference);

        $this->admissionRepo->updatePaymentStatus($payment->id, AdmissionPayment::STATUS_SUCCESSFUL, $gatewayRef, $now);
        $this->admissionRepo->updateWardPaymentStatus($payment->wardId, AdmissionWard::PAYMENT_PAID);

        return ServiceResult::success([
            'payment' => $payment,
            'ward_id' => $payment->wardId,
        ]);
    }

    /**
     * Attach an uploaded document to a paid prospective ward.
     */
    public function attachDocumentToWard(int $wardId, int $applicantUserId, string $documentType, int $fileId): ServiceResult
    {
        $ward = $this->getWard($wardId, $applicantUserId);
        if (!$ward) {
            return ServiceResult::error('Ward not found or access denied.');
        }

        if ($ward->paymentStatus !== AdmissionWard::PAYMENT_PAID) {
            return ServiceResult::error('Application fee must be paid before uploading documents.');
        }

        $validDocTypes = [
            'birth_certificate' => 'birth_certificate_file_id',
            'passport_photo' => 'passport_photo_file_id',
            'previous_report' => 'previous_report_file_id',
        ];

        if (!array_key_exists($documentType, $validDocTypes)) {
            return ServiceResult::error("Invalid document type: {$documentType}.");
        }

        $column = $validDocTypes[$documentType];
        $this->admissionRepo->updateWard($wardId, [$column => $fileId]);

        return ServiceResult::success([
            'ward_id' => $wardId,
            'document_type' => $documentType,
            'file_id' => $fileId,
        ]);
    }

    /**
     * Submit application docket once all wards are paid and have required documents.
     */
    public function submitApplication(int $applicationId, int $applicantUserId): ServiceResult
    {
        $app = $this->admissionRepo->findApplicationById($applicationId);
        if (!$app || $app->applicantUserId !== $applicantUserId) {
            return ServiceResult::error('Application docket not found or access denied.');
        }

        if ($app->status !== AdmissionApplication::STATUS_DRAFT) {
            return ServiceResult::error('This application has already been submitted.');
        }

        $wards = $this->admissionRepo->getWardsForApplication($applicationId);
        if (empty($wards)) {
            return ServiceResult::error('You must add at least one prospective ward before submitting.');
        }

        foreach ($wards as $ward) {
            if ($ward->paymentStatus !== AdmissionWard::PAYMENT_PAID) {
                return ServiceResult::error("Application fee for {$ward->getFullName()} has not been paid. Please complete payment before submitting.");
            }

            if (empty($ward->birthCertificateFileId)) {
                return ServiceResult::error("Please upload the birth certificate for {$ward->getFullName()} before submitting.");
            }

            if (empty($ward->passportPhotoFileId)) {
                return ServiceResult::error("Please upload the passport photograph for {$ward->getFullName()} before submitting.");
            }
        }

        // Lock and transition application to SUBMITTED
        $this->admissionRepo->updateApplicationStatus($applicationId, AdmissionApplication::STATUS_SUBMITTED);

        // Record in status transition history
        $this->admissionRepo->logStatusHistory(
            applicationId: $applicationId,
            fromStatus: AdmissionApplication::STATUS_DRAFT,
            toStatus: AdmissionApplication::STATUS_SUBMITTED,
            changedBy: $applicantUserId,
            comment: 'Applicant finalized details, uploaded documents, and submitted application docket.'
        );

        return ServiceResult::success([
            'application_id' => $applicationId,
            'status' => AdmissionApplication::STATUS_SUBMITTED,
        ]);
    }

    /* -------------------------------------------------------------------------
     * ADMINISTRATIVE ADMISSIONS MANAGEMENT & CONVERSION ENGINE
     * ------------------------------------------------------------------------- */

    /**
     * @return AdmissionApplication[]
     */
    public function getAllApplications(array $filters = []): array
    {
        return $this->admissionRepo->getFilteredApplications($filters);
    }

    /**
     * Retrieve complete dossier for administrative review.
     */
    public function getApplicationDossier(int $applicationId): ?array
    {
        $app = $this->admissionRepo->findApplicationById($applicationId);
        if (!$app) {
            return null;
        }

        $session = $this->admissionRepo->findSessionById($app->admissionSessionId);
        $applicant = $this->userRepo->findById($app->applicantUserId);
        $wards = $this->admissionRepo->getWardsForApplication($applicationId);
        $statusHistory = $this->admissionRepo->getStatusHistory($applicationId);

        $fileRepo = new \App\Repositories\FileRepository($this->pdo);

        $wardDetails = [];
        foreach ($wards as $ward) {
            $birthCert = $ward->birthCertificateFileId ? $fileRepo->findById($ward->birthCertificateFileId) : null;
            $passport = $ward->passportPhotoFileId ? $fileRepo->findById($ward->passportPhotoFileId) : null;
            $report = $ward->previousReportFileId ? $fileRepo->findById($ward->previousReportFileId) : null;
            $payment = $this->admissionRepo->getSuccessfulPaymentForWard($ward->id);

            $wardDetails[] = [
                'ward' => $ward,
                'birth_cert' => $birthCert,
                'passport' => $passport,
                'previous_report' => $report,
                'payment' => $payment,
            ];
        }

        $classes = $this->academicRepo->getAllClasses();

        return [
            'application' => $app,
            'session' => $session,
            'applicant' => $applicant,
            'wards' => $wardDetails,
            'history' => $statusHistory,
            'classes' => $classes,
        ];
    }

    /**
     * Move application to under_review or rejected.
     */
    public function updateApplicationStatus(
        int $applicationId,
        string $status,
        int $adminUserId,
        ?string $comment = null
    ): ServiceResult {
        $app = $this->admissionRepo->findApplicationById($applicationId);
        if (!$app) {
            return ServiceResult::error('Application docket not found.');
        }

        $allowedTransitions = [
            AdmissionApplication::STATUS_SUBMITTED => [AdmissionApplication::STATUS_UNDER_REVIEW, AdmissionApplication::STATUS_REJECTED],
            AdmissionApplication::STATUS_UNDER_REVIEW => [AdmissionApplication::STATUS_REJECTED],
        ];

        if (!isset($allowedTransitions[$app->status]) || !in_array($status, $allowedTransitions[$app->status], true)) {
            return ServiceResult::error("Invalid status transition from {$app->status} to {$status}.");
        }

        if ($status === AdmissionApplication::STATUS_REJECTED && empty(trim((string)$comment))) {
            return ServiceResult::error('A detailed rejection reason is required when rejecting an application.');
        }

        $this->admissionRepo->updateApplicationStatus(
            id: $applicationId,
            status: $status,
            rejectionReason: $status === AdmissionApplication::STATUS_REJECTED ? trim((string)$comment) : null,
            reviewedBy: $adminUserId
        );

        $this->admissionRepo->logStatusHistory(
            applicationId: $applicationId,
            fromStatus: $app->status,
            toStatus: $status,
            changedBy: $adminUserId,
            comment: $comment ?: ($status === AdmissionApplication::STATUS_UNDER_REVIEW ? 'Admissions officer commenced review of application docket.' : null)
        );

        return ServiceResult::success([
            'application_id' => $applicationId,
            'status' => $status,
        ]);
    }

    /**
     * Atomically approve application docket:
     * 1. Converts each registered ward to a student record with STD-xxxxx admission number.
     * 2. Establishes parent identity and links student(s) to parent.
     * 3. Transitions docket to APPROVED.
     */
    public function approveApplication(
        int $applicationId,
        int $adminUserId,
        ?string $comment = null,
        array $wardClassAllocations = []
    ): ServiceResult {
        $app = $this->admissionRepo->findApplicationById($applicationId);
        if (!$app) {
            return ServiceResult::error('Application docket not found.');
        }

        if (!in_array($app->status, [AdmissionApplication::STATUS_SUBMITTED, AdmissionApplication::STATUS_UNDER_REVIEW], true)) {
            return ServiceResult::error("Cannot approve application in '{$app->status}' status.");
        }

        $wards = $this->admissionRepo->getWardsForApplication($applicationId);
        if (empty($wards)) {
            return ServiceResult::error('Application has no registered wards.');
        }

        // Verify all wards have completed payment
        foreach ($wards as $ward) {
            if ($ward->paymentStatus !== AdmissionWard::PAYMENT_PAID) {
                return ServiceResult::error("Ward {$ward->getFullName()} has unpaid application fees. Approval cannot proceed.");
            }
        }

        $this->pdo->beginTransaction();
        try {
            // 1. Ensure applicant user has a 'parent' profile and role
            $parent = $this->parentRepo->findByUserId($app->applicantUserId);
            if (!$parent) {
                $parent = $this->parentRepo->create($app->applicantUserId);
            }
            $this->userRepo->addRole($app->applicantUserId, 'parent');

            $enrolledStudents = [];

            // 2. Convert each ward to an active student
            foreach ($wards as $ward) {
                // If ward already converted, skip
                if (!empty($ward->convertedStudentId)) {
                    continue;
                }

                // Generate STD-xxxxx admission number
                $admNumber = $this->admissionRepo->generateStudentAdmissionNumber();

                // Deterministic student email
                $studentEmail = strtolower($admNumber) . '@student.claret.edu.ng';

                $uuid = sprintf(
                    '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                    mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                    mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000,
                    mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
                );

                $defaultPassword = 'Claret@' . date('Y') . '!';
                $passwordHash = password_hash($defaultPassword, PASSWORD_BCRYPT, ['cost' => 12]);

                $studentUser = $this->userRepo->create([
                    'uuid' => $uuid,
                    'name' => $ward->getFullName(),
                    'email' => $studentEmail,
                    'phone' => null,
                    'password_hash' => $passwordHash,
                    'status' => 'active',
                    'must_change_password' => 1,
                ], ['student']);

                $classId = !empty($wardClassAllocations[$ward->id]) ? (int)$wardClassAllocations[$ward->id] : null;

                // Create student profile
                $student = $this->studentRepo->create(
                    userId: $studentUser->id,
                    admissionNumber: $admNumber,
                    dateOfBirth: $ward->dateOfBirth,
                    gender: $ward->gender,
                    currentClassId: $classId
                );

                // Link parent and student
                $this->parentRepo->linkStudent($parent->id, $student->id, 'Parent');

                // Link ward converted student ID
                $this->admissionRepo->linkWardConvertedStudent($ward->id, $student->id);

                $enrolledStudents[] = [
                    'ward_id' => $ward->id,
                    'ward_name' => $ward->getFullName(),
                    'admission_number' => $admNumber,
                    'student_id' => $student->id,
                    'student_email' => $studentEmail,
                ];
            }

            // 3. Update application status to APPROVED
            $this->admissionRepo->updateApplicationStatus(
                id: $applicationId,
                status: AdmissionApplication::STATUS_APPROVED,
                rejectionReason: null,
                reviewedBy: $adminUserId
            );

            // 4. Log status history
            $this->admissionRepo->logStatusHistory(
                applicationId: $applicationId,
                fromStatus: $app->status,
                toStatus: AdmissionApplication::STATUS_APPROVED,
                changedBy: $adminUserId,
                comment: $comment ?: 'Admissions officer approved docket and converted prospective ward(s) to matriculated student(s).'
            );

            $this->pdo->commit();

            return ServiceResult::success([
                'application_id' => $applicationId,
                'status' => AdmissionApplication::STATUS_APPROVED,
                'enrolled_students' => $enrolledStudents,
            ]);
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            return ServiceResult::error('Failed to approve application: ' . $e->getMessage());
        }
    }

    /**
     * @return AdmissionSession[]
     */
    public function getAllSessions(): array
    {
        return $this->admissionRepo->getAllSessions();
    }

    public function createSession(array $data, int $adminUserId): ServiceResult
    {
        if (empty($data['academic_session_id']) || empty($data['title']) || !isset($data['application_fee']) || empty($data['opens_at']) || empty($data['closes_at'])) {
            return ServiceResult::error('Please fill in all required fields.');
        }

        $data['created_by'] = $adminUserId;
        $session = $this->admissionRepo->createSession($data);

        return ServiceResult::success($session);
    }

    public function updateSession(int $sessionId, array $data): ServiceResult
    {
        $session = $this->admissionRepo->findSessionById($sessionId);
        if (!$session) {
            return ServiceResult::error('Admission session not found.');
        }

        $this->admissionRepo->updateSession($sessionId, $data);
        $updated = $this->admissionRepo->findSessionById($sessionId);

        return ServiceResult::success($updated);
    }
}
