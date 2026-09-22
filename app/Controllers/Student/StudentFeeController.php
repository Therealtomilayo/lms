<?php

declare(strict_types=1);

namespace App\Controllers\Student;

use App\Controllers\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\UserContext;
use App\Repositories\FeeRepository;
use App\Repositories\StudentRepository;
use App\Services\FeeInvoiceService;
use App\Services\PaymentService;

class StudentFeeController extends Controller
{
    private FeeRepository $feeRepo;
    private StudentRepository $studentRepo;
    private FeeInvoiceService $feeService;
    private PaymentService $paymentService;

    public function __construct(
        ?FeeRepository $feeRepo = null,
        ?StudentRepository $studentRepo = null,
        ?FeeInvoiceService $feeService = null,
        ?PaymentService $paymentService = null
    ) {
        $this->feeRepo = $feeRepo ?? new FeeRepository();
        $this->studentRepo = $studentRepo ?? new StudentRepository();
        $this->feeService = $feeService ?? new FeeInvoiceService();
        $this->paymentService = $paymentService ?? new PaymentService();
    }

    public function index(Request $request): Response
    {
        $userContext = $request->getAttribute('user_context') ?? $this->getUserContext($request);
        if (!$userContext instanceof UserContext || !$userContext->hasRole('student')) {
            return $this->forbidden('Access denied. Student credentials required.');
        }

        $student = $this->studentRepo->findByUserId($userContext->getUserId());
        if (!$student) {
            return $this->redirectWithFlash('/student/dashboard', 'error', 'Student profile not found.');
        }

        $invoices = $this->feeRepo->getInvoicesForStudent($student->id);

        return $this->view('student/fees/index', [
            'student' => $student,
            'invoices' => $invoices,
        ], 200, 'layouts/student');
    }

    public function show(Request $request, string $id): Response
    {
        $userContext = $request->getAttribute('user_context') ?? $this->getUserContext($request);
        if (!$userContext instanceof UserContext || !$userContext->hasRole('student')) {
            return $this->forbidden('Access denied.');
        }

        $student = $this->studentRepo->findByUserId($userContext->getUserId());
        if (!$student) {
            return $this->redirectWithFlash('/student/dashboard', 'error', 'Student profile not found.');
        }

        $invoice = $this->feeRepo->findInvoiceById((int)$id);
        if (!$invoice || $invoice->studentId !== $student->id) {
            return $this->forbidden('Access denied.');
        }

        return $this->view('parent/fees/show', [
            'invoice' => $invoice,
            'isStudent' => true,
        ], 200, 'layouts/student');
    }

    /**
     * Initiate online Paystack checkout for student fee payment
     */
    public function checkout(Request $request, string $id): Response
    {
        $userContext = $request->getAttribute('user_context') ?? $this->getUserContext($request);
        if (!$userContext instanceof UserContext || !$userContext->hasRole('student')) {
            return $this->forbidden('Access denied.');
        }

        $student = $this->studentRepo->findByUserId($userContext->getUserId());
        if (!$student) {
            return $this->redirectWithFlash('/student/dashboard', 'error', 'Student profile not found.');
        }

        $invoice = $this->feeRepo->findInvoiceById((int)$id);
        if (!$invoice || $invoice->studentId !== $student->id) {
            return $this->forbidden('Access denied.');
        }

        // Checkbox-based itemized payment selection
        $selectedItemIds = (array)$request->input('selected_items', []);
        $selectAll = $request->input('select_all') === '1';

        $amount = 0.0;
        $validItemIds = [];

        $unpaidItemsMap = [];
        foreach ($invoice->items as $it) {
            if (!$it->isPaid) {
                $unpaidItemsMap[$it->id] = $it;
            }
        }

        if ($selectAll || empty($selectedItemIds)) {
            $amount = $invoice->balanceDue;
            $validItemIds = array_keys($unpaidItemsMap);
        } else {
            foreach ($selectedItemIds as $itemId) {
                $itemId = (int)$itemId;
                if (isset($unpaidItemsMap[$itemId])) {
                    $item = $unpaidItemsMap[$itemId];
                    $amount += $item->amount;
                    $validItemIds[] = $item->id;
                }
            }

            if (empty($validItemIds) || $amount <= 0.0) {
                return $this->redirectWithFlash("/student/fees/invoices/{$invoice->id}", 'error', 'Please select at least one fee component to pay.');
            }
        }

        $amount = min($amount, $invoice->balanceDue);

        $baseUrl = $request->getBaseUrl();
        $callbackUrl = $baseUrl . '/student/fees/verify';

        $res = $this->feeService->initiateInvoicePayment($invoice, $amount, $userContext, $callbackUrl, $validItemIds);

        if (!$res->isSuccess()) {
            return $this->redirectWithFlash("/student/fees/invoices/{$invoice->id}", 'error', $res->getError() ?? 'Unable to initiate payment.');
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
        return $this->redirect("/student/fees/verify?reference={$payment->reference}");
    }

    /**
     * Paystack verification callback for student payments
     */
    public function verify(Request $request): Response
    {
        $userContext = $request->getAttribute('user_context') ?? $this->getUserContext($request);
        if (!$userContext instanceof UserContext || !$userContext->hasRole('student')) {
            return $this->forbidden('Access denied.');
        }

        $reference = (string)$request->query('reference', '');
        if (empty($reference)) {
            return $this->redirectWithFlash('/student/fees', 'error', 'Invalid payment reference.');
        }

        $res = $this->feeService->verifyInvoicePayment($reference);

        if (!$res->isSuccess()) {
            return $this->redirectWithFlash('/student/fees', 'error', $res->getError() ?? 'Payment verification failed.');
        }

        $data = $res->getData();
        $payment = $data['payment'];

        return $this->redirectWithFlash("/payments/receipt/{$payment->id}", 'success', 'Payment confirmed successfully. Official receipt issued.');
    }
}
