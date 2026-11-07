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

        $studentId = (int)($request->post('student_id', 0) ?: $request->query('student_id', 0));
        $sessionId = (int)($request->post('session_id', 0) ?: $request->query('session_id', 0));
        $termId = (int)($request->post('term_id', 0) ?: $request->query('term_id', 0));

        // Dynamic fallback resolution for academic session and term
        $academicRepo = new \App\Repositories\AcademicRepository();
        if ($termId <= 0) {
            $activeTerm = $academicRepo->getCurrentTerm();
            $termId = $activeTerm ? (int)$activeTerm->id : 0;
            if ($sessionId <= 0 && $activeTerm) {
                $sessionId = (int)$activeTerm->sessionId;
            }
        }
        if ($sessionId <= 0 && $termId > 0) {
            $term = $academicRepo->findTermById($termId);
            $sessionId = $term ? (int)$term->sessionId : 0;
        }
        if ($sessionId <= 0) {
            $activeSession = $academicRepo->getCurrentSession();
            $sessionId = $activeSession ? (int)$activeSession->id : 0;
        }

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
                'public_key' => $this->paymentService->getPublicKey(),
                'authorization_url' => $authorizationUrl,
                'access_code' => $accessCode,
                'payer_email' => $payment->metadata['payer_email'] ?? 'parent@claret.edu',
            ]);
        }

        if ($authorizationUrl) {
            return Response::redirect($authorizationUrl);
        }

        return $this->redirectWithError("/parent/children/{$studentId}/grades?term_id={$termId}", 'Payment initialization failed. Please try again.');
    }

    /**
     * Direct purchase entrypoint for Scratch-Card PIN via Paystack checkout.
     * Route: GET|POST /parent/scratch-cards/buy
     */
    public function buyScratchCard(Request $request): Response
    {
        $userContext = $this->getUserContext($request);
        if (!$userContext) {
            return Response::redirect('/login');
        }

        $studentId = (int)($request->post('student_id') ?: $request->query('student_id', 0));
        $termId = (int)($request->post('term_id') ?: $request->query('term_id', 0));
        $sessionId = (int)($request->post('session_id') ?: $request->query('session_id', 0));

        // If student is not specified, resolve from parent's wards
        if ($studentId <= 0) {
            $parentRepo = new \App\Repositories\ParentRepository();
            $parent = $parentRepo->findByUserId((int)$userContext->id);
            if ($parent) {
                $children = $parentRepo->getChildren((int)$parent->id);
                if (!empty($children)) {
                    $studentId = (int)$children[0]->id;
                }
            }
        }

        $academicRepo = new \App\Repositories\AcademicRepository();
        if ($termId <= 0) {
            $activeTerm = $academicRepo->getCurrentTerm();
            $termId = $activeTerm ? (int)$activeTerm->id : 0;
            if ($sessionId <= 0 && $activeTerm) {
                $sessionId = (int)$activeTerm->sessionId;
            }
        }

        if ($sessionId <= 0) {
            $term = $termId > 0 ? $academicRepo->findTermById($termId) : null;
            $sessionId = $term ? (int)$term->sessionId : 0;
            if ($sessionId <= 0) {
                $activeSession = $academicRepo->getCurrentSession();
                $sessionId = $activeSession ? (int)$activeSession->id : 0;
            }
        }

        if ($studentId <= 0 || $sessionId <= 0 || $termId <= 0) {
            \App\Core\Session::start();
            \App\Core\Session::flash('error', 'Unable to initiate scratch card purchase: missing student or academic term details.');
            return Response::redirect('/parent/dashboard');
        }

        // Initialize payment with Paystack
        $amount = PaymentService::PIN_PRICE_NGN;
        $result = $this->paymentService->initiatePinPayment($userContext, $studentId, $sessionId, $termId, $amount);
        if (!$result->success) {
            \App\Core\Session::start();
            \App\Core\Session::flash('error', $result->error ?? 'Could not initiate payment.');
            return Response::redirect("/parent/children/{$studentId}/grades?term_id={$termId}");
        }

        $payment = $result->data;
        $callbackUrl = rtrim($request->getBaseUrl(), '/') . '/payments/callback';
        $paystackInit = $this->paymentService->initializePaystackTransaction($payment, $callbackUrl);

        if ($paystackInit->success && !empty($paystackInit->data['authorization_url'])) {
            // Secure direct routing to Paystack hosted payment page
            return Response::redirect($paystackInit->data['authorization_url']);
        }

        \App\Core\Session::start();
        \App\Core\Session::flash('error', $paystackInit->error ?? 'Paystack payment gateway is currently unreachable. Please try again or visit the school bursary.');
        return Response::redirect("/parent/children/{$studentId}/grades/report-card?term_id={$termId}");
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
     * User payment history (accessible to parents, students, and applicants).
     */
    public function history(Request $request): Response
    {
        $userContext = $this->getUserContext($request);
        if (!$userContext) {
            return Response::redirect('/login');
        }

        if (!$userContext->hasAnyRole(['parent', 'student', 'applicant', 'admin', 'super_admin'])) {
            return $this->forbidden('Only parents, students, and applicants can access personal transaction history. Administrators can view the financial ledger at /admin/payments.');
        }

        $studentId = $userContext->isStudent() ? $userContext->getStudentId() : null;
        $payments = $this->paymentRepository->getPaymentsForUser($userContext->getUserId(), limit: 100, studentId: $studentId);

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
        $rawPurpose = $request->query('purpose');
        $purpose = ($rawPurpose !== null && trim($rawPurpose) !== '') ? trim($rawPurpose) : null;
        $rawSearch = $request->query('q');
        $search = ($rawSearch !== null && trim($rawSearch) !== '') ? trim($rawSearch) : null;
        $page = max(1, (int)$request->query('page', 1));
        $limit = 25;
        $offset = ($page - 1) * $limit;

        $payments = $this->paymentRepository->getAllPayments($limit, $offset, $status, $search, $purpose);
        $total = $this->paymentRepository->countPayments($status, $search, $purpose);
        $stats = $this->paymentRepository->getSummaryStats();

        return $this->view('admin/payments/index', [
            'payments' => $payments,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'stats' => $stats,
            'status' => $status,
            'purpose' => $purpose,
            'search' => $search,
            'role' => 'admin',
            'roleLabel' => $userContext->getPrimaryRoleLabel(),
            'headerTitle' => 'Payments & Revenue Ledger',
            'headerSubtitle' => 'Track all online card transactions, PIN purchases, application fees, and payment clearances.',
        ]);
    }
}
