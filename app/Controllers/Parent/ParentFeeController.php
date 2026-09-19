<?php

declare(strict_types=1);

namespace App\Controllers\Parent;

use App\Controllers\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\UserContext;
use App\Repositories\FeeRepository;
use App\Repositories\ParentRepository;
use App\Repositories\PaymentRepository;
use App\Repositories\StudentRepository;
use App\Services\FeeInvoiceService;
use App\Services\PaymentService;

class ParentFeeController extends Controller
{
    private FeeInvoiceService $feeService;
    private FeeRepository $feeRepo;
    private ParentRepository $parentRepo;
    private StudentRepository $studentRepo;
    private PaymentRepository $paymentRepo;
    private PaymentService $paymentService;

    public function __construct(
        ?FeeInvoiceService $feeService = null,
        ?FeeRepository $feeRepo = null,
        ?ParentRepository $parentRepo = null,
        ?StudentRepository $studentRepo = null,
        ?PaymentRepository $paymentRepo = null,
        ?PaymentService $paymentService = null
    ) {
        $this->feeRepo = $feeRepo ?? new FeeRepository();
        $this->parentRepo = $parentRepo ?? new ParentRepository();
        $this->studentRepo = $studentRepo ?? new StudentRepository();
        $this->paymentRepo = $paymentRepo ?? new PaymentRepository();
        $this->feeService = $feeService ?? new FeeInvoiceService($this->feeRepo, $this->paymentRepo);
        $this->paymentService = $paymentService ?? new PaymentService($this->paymentRepo);
    }

    /**
     * Parent Fee Portal Dashboard & Multi-Child Overview
     */
    public function index(Request $request): Response
    {
        $userContext = $request->getAttribute('user_context') ?? $this->getUserContext($request);
        if (!$userContext instanceof UserContext || !$userContext->hasRole('parent')) {
            return $this->forbidden('Access denied. Parent credentials required.');
        }

        $parent = $this->parentRepo->findByUserId($userContext->getUserId());
        if (!$parent) {
            return $this->redirectWithFlash('/parent/dashboard', 'error', 'Parent profile not found.');
        }

        $linkedStudents = $this->parentRepo->getLinkedStudents($parent->id);
        $invoices = $this->feeRepo->getInvoicesForParent($parent->id);

        $selectedStudentId = (int)$request->query('student_id', 0);
        if ($selectedStudentId > 0) {
            $invoices = array_values(array_filter($invoices, fn($inv) => $inv->studentId === $selectedStudentId));
        }

        $totalOutstanding = 0.0;
        $totalPaid = 0.0;
        foreach ($invoices as $inv) {
            $totalOutstanding += $inv->balanceDue;
            $totalPaid += $inv->amountPaid;
        }

        return $this->view('parent/fees/index', [
            'parent' => $parent,
            'linkedStudents' => $linkedStudents,
            'children' => $linkedStudents,
            'selectedChild' => !empty($linkedStudents) ? $linkedStudents[0] : null,
            'invoices' => $invoices,
            'selectedStudentId' => $selectedStudentId,
            'totalOutstanding' => $totalOutstanding,
            'totalPaid' => $totalPaid,
            'title' => 'School Fees & Invoices — Claret Parent Portal',
            'headerTitle' => 'School Fees & Invoices',
            'headerSubtitle' => 'Review termly tuition dockets, pay in installments via Paystack, and download official receipts.',
        ]);
    }

    /**
     * Detailed Invoice View for a child
     */
    public function show(Request $request, string $id): Response
    {
        $userContext = $request->getAttribute('user_context') ?? $this->getUserContext($request);
        if (!$userContext instanceof UserContext || !$userContext->hasRole('parent')) {
            return $this->forbidden('Access denied.');
        }

        $parent = $this->parentRepo->findByUserId($userContext->getUserId());
        if (!$parent) {
            return $this->redirectWithFlash('/parent/dashboard', 'error', 'Parent profile not found.');
        }

        $invoice = $this->feeRepo->findInvoiceById((int)$id);
        if (!$invoice) {
            return $this->redirectWithFlash('/parent/fees', 'error', 'Invoice not found.');
        }

        // Security check: ensure student is linked to this parent
        if (!$this->parentRepo->isLinkedToStudent($parent->id, $invoice->studentId)) {
            return $this->forbidden('Access denied. You do not have permission to view this invoice.');
        }

        $linkedStudents = $this->parentRepo->getLinkedStudents($parent->id);

        return $this->view('parent/fees/show', [
            'invoice' => $invoice,
            'parent' => $parent,
            'children' => $linkedStudents,
            'selectedChild' => !empty($linkedStudents) ? $linkedStudents[0] : null,
            'title' => 'Invoice ' . $invoice->invoiceNumber . ' — Claret School Fees',
            'headerTitle' => 'Invoice ' . $invoice->invoiceNumber,
            'headerSubtitle' => 'Fee docket and online payment gateway',
        ]);
    }

    /**
     * Initiate online Paystack checkout for fee payment (full or installment)
     */
    public function checkout(Request $request, string $id): Response
    {
        $userContext = $request->getAttribute('user_context') ?? $this->getUserContext($request);
        if (!$userContext instanceof UserContext || !$userContext->hasRole('parent')) {
            return $this->forbidden('Access denied.');
        }

        $parent = $this->parentRepo->findByUserId($userContext->getUserId());
        if (!$parent) {
            return $this->redirectWithFlash('/parent/dashboard', 'error', 'Parent profile not found.');
        }

        $invoice = $this->feeRepo->findInvoiceById((int)$id);
        if (!$invoice) {
            return $this->redirectWithFlash('/parent/fees', 'error', 'Invoice not found.');
        }

        if (!$this->parentRepo->isLinkedToStudent($parent->id, $invoice->studentId)) {
            return $this->forbidden('Access denied.');
        }

        $mode = (string)$request->input('payment_mode', 'full');
        $amount = ($mode === 'full') ? $invoice->balanceDue : (float)$request->input('custom_amount', 0.0);

        $baseUrl = $request->getBaseUrl();
        $callbackUrl = $baseUrl . '/parent/fees/verify';

        $res = $this->feeService->initiateInvoicePayment($invoice, $amount, $userContext, $callbackUrl);

        if (!$res->isSuccess()) {
            return $this->redirectWithFlash("/parent/fees/invoices/{$invoice->id}", 'error', $res->getError() ?? 'Unable to initiate payment.');
        }

        /** @var \App\Models\Payment $payment */
        $payment = $res->getData();

        // If in live mode with valid secret key, redirect to Paystack; otherwise simulate checkout
        $secretKey = $this->paymentService->getSecretKey();
        if (!empty($secretKey) && !str_starts_with($secretKey, 'mock_') && !str_starts_with($secretKey, 'sk_test_mock')) {
            $pstkRes = $this->paymentService->initializePaystackTransaction($payment, $callbackUrl);
            if ($pstkRes->isSuccess()) {
                $authUrl = $pstkRes->getData()['authorization_url'] ?? null;
                if ($authUrl) {
                    return $this->redirect($authUrl);
                }
            }
        }

        // Automatic simulation callback
        return $this->redirect("/parent/fees/verify?reference={$payment->reference}");
    }

    /**
     * Paystack verification callback
     */
    public function verify(Request $request): Response
    {
        $userContext = $request->getAttribute('user_context') ?? $this->getUserContext($request);
        if (!$userContext instanceof UserContext || !$userContext->hasRole('parent')) {
            return $this->forbidden('Access denied.');
        }

        $reference = (string)$request->query('reference', '');
        if (empty($reference)) {
            return $this->redirectWithFlash('/parent/fees', 'error', 'Invalid payment reference.');
        }

        $res = $this->feeService->verifyInvoicePayment($reference);

        if (!$res->isSuccess()) {
            return $this->redirectWithFlash('/parent/fees', 'error', $res->getError() ?? 'Payment verification failed.');
        }

        $data = $res->getData();
        $payment = $data['payment'];

        return $this->redirectWithFlash("/payments/receipt/{$payment->id}", 'success', 'Payment confirmed successfully. Official receipt issued.');
    }
}
