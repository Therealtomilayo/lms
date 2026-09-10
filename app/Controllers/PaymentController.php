<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\AuthenticatorInterface;
use App\Core\Request;
use App\Core\Response;
use App\Core\UserContext;
use App\Repositories\PaymentRepository;
use App\Services\PaymentService;

/**
 * Controller for Payment Checkout, Simulation, Transaction History & Receipts
 */
class PaymentController extends Controller
{
    private PaymentService $paymentService;
    private PaymentRepository $paymentRepository;

    public function __construct(
        ?AuthenticatorInterface $authenticator = null,
        ?PaymentService $paymentService = null,
        ?PaymentRepository $paymentRepository = null
    ) {
        parent::__construct($authenticator);
        $this->paymentService = $paymentService ?? new PaymentService();
        $this->paymentRepository = $paymentRepository ?? new PaymentRepository();
    }

    /**
     * Initiate an online PIN purchase.
     */
    public function checkoutPin(Request $request): Response
    {
        $userContext = $this->getUserContext($request);
        if (!$userContext) {
            return Response::redirect('/login');
        }

        $studentId = (int)$request->post('student_id', 0);
        $sessionId = (int)$request->post('session_id', 0);
        $termId = (int)$request->post('term_id', 0);

        // Security: Never trust client-supplied amount for fixed-price items!
        $amount = PaymentService::PIN_PRICE_NGN;

        if ($studentId <= 0 || $sessionId <= 0 || $termId <= 0) {
            return $this->json(['error' => 'Missing required student or academic term parameters.'], 422);
        }

        $result = $this->paymentService->initiatePinPayment($userContext, $studentId, $sessionId, $termId, $amount);
        if (!$result->success) {
            return $this->json(['error' => $result->error], 400);
        }

        $payment = $result->data;

        // Connect directly to live Paystack API to initialize transaction
        $callbackUrl = rtrim($request->getBaseUrl(), '/') . '/payments/callback';
        $paystackInit = $this->paymentService->initializePaystackTransaction($payment, $callbackUrl);
        $authorizationUrl = $paystackInit->success ? ($paystackInit->data['authorization_url'] ?? null) : null;
        $accessCode = $paystackInit->success ? ($paystackInit->data['access_code'] ?? null) : null;

        if ($request->isAjax() || $request->isJson()) {
            return $this->json([
                'success' => true,
                'reference' => $payment->reference,
                'amount' => $payment->amount,
                'formatted_amount' => $payment->getFormattedAmount(),
                'item' => $payment->metadata['item_description'] ?? 'Result Access Scratch-Card PIN',
                'simulate_url' => "/payments/simulate/{$payment->reference}",
                'public_key' => $this->paymentService->getPublicKey(),
                'authorization_url' => $authorizationUrl,
                'access_code' => $accessCode,
                'payer_email' => $payment->metadata['payer_email'] ?? 'parent@claret.edu',
            ]);
        }

        return Response::redirect("/payments/checkout/{$payment->reference}");
    }

    /**
     * Handle Paystack verification callback (redirect from Paystack standard checkout or AJAX from popup).
     */
    public function callback(Request $request): Response
    {
        $userContext = $this->getUserContext($request);
        if (!$userContext) {
            if ($request->isAjax() || $request->isJson()) {
                return $this->json(['error' => 'Unauthenticated.'], 401);
            }
            return Response::redirect('/login');
        }

        $reference = (string)($request->query('reference') ?: ($request->query('trxref') ?: $request->post('reference', '')));
        if (empty($reference)) {
            if ($request->isAjax() || $request->isJson()) {
                return $this->json(['error' => 'Missing payment reference in callback.'], 400);
            }
            return $this->redirectWithError('/payments/history', 'Missing payment reference in callback.');
        }

        $result = $this->paymentService->verifyPaystackPayment($reference);
        if (!$result->success) {
            if ($request->isAjax() || $request->isJson()) {
                return $this->json(['error' => $result->error ?? 'Payment verification failed.'], 400);
            }
            return $this->redirectWithError('/payments/history', $result->error ?? 'Payment verification failed.');
        }

        $payment = $result->data['payment'];
        $pin = $result->data['pin'];

        if ($request->isAjax() || $request->isJson()) {
            return $this->json([
                'success' => true,
                'reference' => $payment->reference,
                'pin_code' => $pin ? $pin->getFormattedPin() : null,
                'serial_number' => $pin?->serialNumber,
                'max_uses' => $pin?->maxUses ?? 5,
                'remaining_uses' => $pin ? $pin->getRemainingUses() : 5,
                'receipt_url' => "/payments/{$payment->reference}/receipt",
            ]);
        }

        return $this->redirectWithSuccess(
            "/payments/{$reference}/receipt",
            'Payment verified successfully with Paystack! Your Result Access PIN is ready.'
        );
    }

    /**
     * Simulate successful payment confirmation (Paystack mock).
     */
    public function simulate(Request $request, string $reference): Response
    {
        $userContext = $this->getUserContext($request);
        if (!$userContext) {
            return Response::redirect('/login');
        }

        // Security: Simulation is strictly forbidden in production
        if (\App\Core\Config::get('app.env') === 'production') {
            if ($request->isAjax() || $request->isJson()) {
                return $this->json(['error' => 'Payment simulation is disabled in production environments.'], 403);
            }
            return Response::forbidden('Payment simulation is disabled in production environments.');
        }

        $result = $this->paymentService->simulateSuccessfulPayment($reference);
        if (!$result->success) {
            if ($request->isAjax() || $request->isJson()) {
                return $this->json(['error' => $result->error], 400);
            }
            return $this->redirectWithError('/payments/history', $result->error ?? 'Payment simulation failed.');
        }

        $payment = $result->data['payment'];
        $pin = $result->data['pin'];

        if ($request->isAjax() || $request->isJson()) {
            return $this->json([
                'success' => true,
                'reference' => $payment->reference,
                'pin_code' => $pin ? $pin->getFormattedPin() : null,
                'serial_number' => $pin?->serialNumber,
                'max_uses' => $pin?->maxUses ?? 5,
                'remaining_uses' => $pin ? $pin->getRemainingUses() : 5,
                'receipt_url' => "/payments/{$payment->reference}/receipt",
            ]);
        }

        return $this->redirectWithSuccess(
            "/payments/{$payment->reference}/receipt",
            "Payment successful! Your Result Access Scratch-Card PIN has been generated."
        );
    }

    /**
     * User payment history (accessible to parents and students).
     */
    public function history(Request $request): Response
    {
        $userContext = $this->getUserContext($request);
        if (!$userContext) {
            return Response::redirect('/login');
        }

        if (!$userContext->hasAnyRole(['parent', 'student'])) {
            return $this->forbidden('Only parents and students can access personal transaction history. Administrators can view the financial ledger at /admin/payments.');
        }

        $payments = $this->paymentRepository->getPaymentsForUser($userContext->getUserId(), limit: 100);

        $children = [];
        $selectedChild = null;
        if ($userContext->isParent()) {
            $parentService = new \App\Services\ParentService();
            $children = $parentService->getLinkedChildren($userContext);
            $session = $request->getSession();
            $selectedId = (int)($session ? $session->get('_selected_child_id', 0) : 0);
            foreach ($children as $child) {
                if ($child->id === $selectedId) {
                    $selectedChild = $child;
                    break;
                }
            }
            if (!$selectedChild && !empty($children)) {
                $selectedChild = $children[0];
            }
        }

        return $this->view('payments/history', [
            'payments' => $payments,
            'role' => $userContext->primaryRole,
            'roleLabel' => $userContext->getPrimaryRoleLabel(),
            'headerTitle' => 'Payment History',
            'headerSubtitle' => 'Review all transaction records, receipts, and scratch-card PIN allocations.',
            'children' => $children,
            'selectedChild' => $selectedChild,
        ]);
    }

    /**
     * Official printable payment receipt.
     */
    public function receipt(Request $request, string $reference): Response
    {
        $userContext = $this->getUserContext($request);
        if (!$userContext) {
            return Response::redirect('/login');
        }

        $receiptData = $this->paymentService->getReceiptData($reference);
        if (!$receiptData) {
            return $this->notFound("Receipt for transaction {$reference} not found.");
        }

        return $this->view('payments/receipt', array_merge($receiptData, [
            'role' => $userContext->primaryRole,
            'roleLabel' => $userContext->getPrimaryRoleLabel(),
            'headerTitle' => 'Official Payment Receipt',
        ]));
    }

    /**
     * Admin payment directory ledger.
     */
    public function adminLedger(Request $request): Response
    {
        $userContext = $this->getUserContext($request);
        if (!$userContext || !$userContext->hasAnyRole(['super_admin', 'admin'])) {
            return $this->forbidden('Only administrators can access the financial payments ledger.');
        }

        $rawStatus = $request->query('status');
        $status = ($rawStatus !== null && trim($rawStatus) !== '') ? trim($rawStatus) : null;
        $rawSearch = $request->query('q');
        $search = ($rawSearch !== null && trim($rawSearch) !== '') ? trim($rawSearch) : null;
        $page = max(1, (int)$request->query('page', 1));
        $limit = 25;
        $offset = ($page - 1) * $limit;

        $payments = $this->paymentRepository->getAllPayments($limit, $offset, $status, $search);
        $total = $this->paymentRepository->countPayments($status, $search);
        $stats = $this->paymentRepository->getSummaryStats();

        return $this->view('admin/payments/index', [
            'payments' => $payments,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'stats' => $stats,
            'status' => $status,
            'search' => $search,
            'role' => 'admin',
            'roleLabel' => $userContext->getPrimaryRoleLabel(),
            'headerTitle' => 'Payments & Revenue Ledger',
            'headerSubtitle' => 'Track all online card transactions, PIN purchases, and payment clearances.',
        ]);
    }
}
