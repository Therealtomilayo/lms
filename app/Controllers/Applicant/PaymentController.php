<?php

declare(strict_types=1);

namespace App\Controllers\Applicant;

use App\Controllers\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Models\AdmissionWard;
use App\Services\AdmissionService;

/**
 * Controller for Applicant Ward Application Fee Payments (Paystack Gateway & Simulation)
 */
class PaymentController extends Controller
{
    private AdmissionService $admissionService;

    public function __construct(?AdmissionService $admissionService = null)
    {
        parent::__construct();
        $this->admissionService = $admissionService ?? new AdmissionService();
    }

    /**
     * Display Application Fee Checkout Screen: GET /applicant/payment/{wardId}
     */
    public function showCheckout(Request $request, string $wardId): Response
    {
        $user = $this->requireAuthContext($request);
        $wId = (int)$wardId;
        $ward = $this->admissionService->getWard($wId, $user->id);

        if (!$ward) {
            return $this->notFound('Ward record not found or access denied.');
        }

        if ($ward->paymentStatus === AdmissionWard::PAYMENT_PAID) {
            return $this->redirectWithSuccess(
                "/applicant/wards/{$wId}/documents",
                'Application fee has already been paid for this prospective ward.'
            );
        }

        $session = $this->admissionService->getActiveSession();
        $app = $this->admissionService->getApplicantActiveApplication($user->id);
        $isSimulated = (bool)$request->query('simulated', false);
        $reference = (string)$request->query('reference', '');

        return $this->view('applicant/payment/checkout', [
            'title' => 'Application Fee Payment — ' . $ward->getFullName(),
            'user' => $user,
            'ward' => $ward,
            'session' => $session,
            'application' => $app,
            'isSimulated' => $isSimulated,
            'reference' => $reference,
        ]);
    }

    /**
     * Initiate Paystack Checkout: POST /applicant/payment/{wardId}/checkout
     */
    public function checkout(Request $request, string $wardId): Response
    {
        $user = $this->requireAuthContext($request);
        $wId = (int)$wardId;

        $host = $_SERVER['HTTP_HOST'] ?? 'lms.test';
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $callbackUrl = "{$scheme}://{$host}/applicant/payment/callback";

        $result = $this->admissionService->initializeWardPayment($wId, $user->id, $callbackUrl);
        if (!$result->isSuccess()) {
            return $this->redirectWithError("/applicant/payment/{$wId}", $result->getMessage());
        }

        $data = $result->getData();
        if (!empty($data['already_paid'])) {
            return $this->redirectWithSuccess("/applicant/wards/{$wId}/documents", 'Application fee already paid.');
        }

        $authorizationUrl = $data['authorization_url'] ?? '';
        if (empty($authorizationUrl)) {
            return $this->redirectWithError("/applicant/payment/{$wId}", 'Could not establish checkout session.');
        }

        return $this->redirect($authorizationUrl);
    }

    /**
     * Process Paystack Callback: GET /applicant/payment/callback
     */
    public function callback(Request $request): Response
    {
        $user = $this->requireAuthContext($request);
        $reference = (string)($request->query('reference') ?? $request->query('trxref') ?? '');

        if (empty($reference)) {
            return $this->redirectWithError('/applicant/application', 'Missing payment reference.');
        }

        // If simulated reference, verify via simulator
        if (str_starts_with($reference, 'ADM-') && $request->query('simulated')) {
            $result = $this->admissionService->simulateSuccessfulWardPayment($reference, $user->id);
        } else {
            // Live / Sandbox Paystack verification
            $result = $this->admissionService->verifyPaystackWardPayment($reference);
        }

        if (!$result->isSuccess()) {
            return $this->redirectWithError('/applicant/application', 'Payment verification failed: ' . $result->getMessage());
        }

        $wardId = $result->getData()['ward_id'] ?? null;
        if ($wardId) {
            return $this->redirectWithSuccess(
                "/applicant/wards/{$wardId}/documents",
                'Payment received and verified successfully! You may now upload required documents.'
            );
        }

        return $this->redirectWithSuccess('/applicant/application', 'Application fee payment verified successfully.');
    }

    /**
     * Sandbox/Testing Simulation POST: POST /applicant/payment/simulate
     */
    public function simulate(Request $request): Response
    {
        $user = $this->requireAuthContext($request);
        $reference = (string)$request->post('reference', '');

        if (empty($reference)) {
            return $this->redirectWithError('/applicant/application', 'Missing payment reference.');
        }

        $result = $this->admissionService->simulateSuccessfulWardPayment($reference, $user->id);
        if (!$result->isSuccess()) {
            return $this->redirectWithError('/applicant/application', $result->getMessage());
        }

        $wardId = $result->getData()['ward_id'] ?? null;
        if ($wardId) {
            return $this->redirectWithSuccess(
                "/applicant/wards/{$wardId}/documents",
                'Simulated test payment confirmed! Application unlocked for document upload.'
            );
        }

        return $this->redirectWithSuccess('/applicant/application', 'Payment confirmed successfully.');
    }
}
