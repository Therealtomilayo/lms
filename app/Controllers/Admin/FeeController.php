<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\UserContext;
use App\Repositories\AcademicRepository;
use App\Repositories\FeeRepository;
use App\Services\FeeInvoiceService;

class FeeController extends Controller
{
    private FeeInvoiceService $feeService;
    private FeeRepository $feeRepo;
    private AcademicRepository $academicRepo;

    public function __construct(
        ?FeeInvoiceService $feeService = null,
        ?FeeRepository $feeRepo = null,
        ?AcademicRepository $academicRepo = null
    ) {
        $this->feeRepo = $feeRepo ?? new FeeRepository();
        $this->feeService = $feeService ?? new FeeInvoiceService($this->feeRepo);
        $this->academicRepo = $academicRepo ?? new AcademicRepository();
    }

    /**
     * List fee structures & configure schedules
     */
    public function structures(Request $request): Response
    {
        $userContext = $request->getAttribute('user_context') ?? $this->getUserContext($request);
        if (!$userContext instanceof UserContext || !$userContext->hasAnyRole(['admin', 'super_admin'])) {
            return $this->forbidden('Access denied.');
        }

        $sessionId = (int)$request->query('session_id', 0);
        $termId = (int)$request->query('term_id', 0);

        $sessions = $this->academicRepo->getAllSessions();
        $terms = $this->academicRepo->getAllTerms();
        $academicLevels = $this->academicRepo->getAllLevels();
        $classes = $this->academicRepo->getAllClasses();
        $categories = $this->feeRepo->getAllCategories(true);

        $structures = $this->feeRepo->getAllStructures(
            $sessionId > 0 ? $sessionId : null,
            $termId > 0 ? $termId : null
        );

        return $this->view('admin/fees/structures', [
            'structures' => $structures,
            'sessions' => $sessions,
            'terms' => $terms,
            'academicLevels' => $academicLevels,
            'classes' => $classes,
            'categories' => $categories,
            'selectedSessionId' => $sessionId,
            'selectedTermId' => $termId,
        ], 200, 'layouts/admin');
    }

    /**
     * Store a newly configured fee structure
     */
    public function storeStructure(Request $request): Response
    {
        $userContext = $request->getAttribute('user_context') ?? $this->getUserContext($request);
        if (!$userContext instanceof UserContext || !$userContext->hasAnyRole(['admin', 'super_admin'])) {
            return $this->forbidden('Access denied.');
        }

        $data = [
            'session_id' => (int)$request->input('session_id'),
            'term_id' => (int)$request->input('term_id'),
            'academic_level_id' => $request->input('academic_level_id') !== '' ? (int)$request->input('academic_level_id') : null,
            'class_id' => $request->input('class_id') !== '' ? (int)$request->input('class_id') : null,
            'title' => trim((string)$request->input('title')),
            'due_date' => $request->input('due_date') ?: null,
            'is_active' => $request->has('is_active') ? 1 : 0,
        ];

        // Process dynamic item rows
        $categoryIds = (array)$request->input('category_id', []);
        $itemNames = (array)$request->input('item_name', []);
        $itemAmounts = (array)$request->input('item_amount', []);
        $isCompulsory = (array)$request->input('is_compulsory', []);

        $items = [];
        foreach ($itemNames as $i => $name) {
            $amt = (float)($itemAmounts[$i] ?? 0.0);
            if ($amt > 0 || !empty($name)) {
                $items[] = [
                    'fee_category_id' => (int)($categoryIds[$i] ?? 1),
                    'name' => trim((string)$name),
                    'amount' => $amt,
                    'is_compulsory' => !empty($isCompulsory[$i]) ? 1 : 0,
                ];
            }
        }

        $res = $this->feeService->configureFeeStructure($data, $items, $userContext->getUserId());

        if (!$res->isSuccess()) {
            return $this->redirectWithFlash('/admin/fees/structures', 'error', $res->getError() ?? 'Failed to save fee structure.');
        }

        // Auto-generate invoices for enrolled students immediately (defaults to true)
        $autoBill = $request->input('generate_invoices', '1') === '1';
        $billedMsg = '';
        if ($autoBill) {
            $genRes = $this->feeService->batchGenerateInvoices(
                $data['session_id'],
                $data['term_id'],
                $data['academic_level_id'],
                $data['class_id'],
                $userContext->getUserId()
            );
            if ($genRes->isSuccess()) {
                $genData = $genRes->getData();
                $count = (int)($genData['created_count'] ?? 0);
                if ($count > 0) {
                    $billedMsg = " {$count} student invoice(s) generated immediately.";
                }
            }
        }

        return $this->redirectWithFlash('/admin/fees/structures', 'success', 'Fee schedule successfully created and activated.' . $billedMsg);
    }

    /**
     * Toggle fee structure active/inactive status
     */
    public function toggleStructure(Request $request, string $id): Response
    {
        $userContext = $request->getAttribute('user_context') ?? $this->getUserContext($request);
        if (!$userContext instanceof UserContext || !$userContext->hasAnyRole(['admin', 'super_admin'])) {
            return $this->forbidden('Access denied.');
        }

        $structureId = (int)$id;
        $this->feeRepo->toggleStructureStatus($structureId);

        return $this->redirectWithFlash('/admin/fees/structures', 'success', 'Fee schedule status updated.');
    }

    /**
     * Bursary Invoicing Ledger & Financial Reports
     */
    public function invoices(Request $request): Response
    {
        $userContext = $request->getAttribute('user_context') ?? $this->getUserContext($request);
        if (!$userContext instanceof UserContext || !$userContext->hasAnyRole(['admin', 'super_admin'])) {
            return $this->forbidden('Access denied.');
        }

        $filters = [
            'session_id' => (int)$request->query('session_id', 0),
            'term_id' => (int)$request->query('term_id', 0),
            'class_id' => (int)$request->query('class_id', 0),
            'status' => (string)$request->query('status', ''),
            'query' => (string)$request->query('q', ''),
        ];

        $page = max(1, (int)$request->query('page', 1));
        $limit = 25;
        $offset = ($page - 1) * $limit;

        $invoices = $this->feeRepo->filterInvoices($filters, $limit, $offset);
        $totalCount = $this->feeRepo->countFilteredInvoices($filters);
        $totalPages = (int)ceil($totalCount / $limit);

        $summary = $this->feeRepo->getBursarySummary(
            $filters['session_id'] > 0 ? $filters['session_id'] : null,
            $filters['term_id'] > 0 ? $filters['term_id'] : null
        );

        $sessions = $this->academicRepo->getAllSessions();
        $terms = $this->academicRepo->getAllTerms();
        $classes = $this->academicRepo->getAllClasses();
        $academicLevels = $this->academicRepo->getAllLevels();

        return $this->view('admin/fees/invoices', [
            'invoices' => $invoices,
            'summary' => $summary,
            'filters' => $filters,
            'sessions' => $sessions,
            'terms' => $terms,
            'classes' => $classes,
            'academicLevels' => $academicLevels,
            'page' => $page,
            'totalPages' => $totalPages,
            'totalCount' => $totalCount,
        ], 200, 'layouts/admin');
    }

    /**
     * Batch invoice generation for a class or level
     */
    public function generateInvoices(Request $request): Response
    {
        $userContext = $request->getAttribute('user_context') ?? $this->getUserContext($request);
        if (!$userContext instanceof UserContext || !$userContext->hasAnyRole(['admin', 'super_admin'])) {
            return $this->forbidden('Access denied.');
        }

        $sessionId = (int)$request->input('session_id');
        $termId = (int)$request->input('term_id');
        $levelId = $request->input('academic_level_id') !== '' ? (int)$request->input('academic_level_id') : null;
        $classId = $request->input('class_id') !== '' ? (int)$request->input('class_id') : null;

        $res = $this->feeService->batchGenerateInvoices($sessionId, $termId, $levelId, $classId, $userContext->getUserId());

        if (!$res->isSuccess()) {
            return $this->redirectWithFlash('/admin/fees/invoices', 'error', $res->getError() ?? 'Invoice generation failed.');
        }

        $data = $res->getData();
        $msg = sprintf(
            'Batch invoicing complete: %d generated, %d previously billed (skipped), %d unbilled (no matching fee schedule).',
            $data['created_count'],
            $data['skipped_count'],
            $data['failed_count']
        );

        return $this->redirectWithFlash('/admin/fees/invoices', 'success', $msg);
    }

    /**
     * Inspect a single student fee invoice
     */
    public function showInvoice(Request $request, string $id): Response
    {
        $userContext = $request->getAttribute('user_context') ?? $this->getUserContext($request);
        if (!$userContext instanceof UserContext || !$userContext->hasAnyRole(['admin', 'super_admin'])) {
            return $this->forbidden('Access denied.');
        }

        $invoice = $this->feeRepo->findInvoiceById((int)$id);
        if (!$invoice) {
            return $this->redirectWithFlash('/admin/fees/invoices', 'error', 'Invoice not found.');
        }

        return $this->view('admin/fees/show', [
            'invoice' => $invoice,
        ], 200, 'layouts/admin');
    }

    /**
     * Record a manual payment logged by the Bursar
     */
    public function recordPayment(Request $request, string $id): Response
    {
        $userContext = $request->getAttribute('user_context') ?? $this->getUserContext($request);
        if (!$userContext instanceof UserContext || !$userContext->hasAnyRole(['admin', 'super_admin'])) {
            return $this->forbidden('Access denied.');
        }

        $invoiceId = (int)$id;
        $amount = (float)$request->input('amount');
        $channel = (string)$request->input('channel', 'bank_transfer');
        $ref = (string)$request->input('reference_number', '');
        $notes = (string)$request->input('notes', '');

        $res = $this->feeService->recordManualPayment($invoiceId, $amount, $channel, $ref, $notes, $userContext);

        if (!$res->isSuccess()) {
            return $this->redirectWithFlash("/admin/fees/invoices/{$invoiceId}", 'error', $res->getError() ?? 'Failed to record payment.');
        }

        return $this->redirectWithFlash("/admin/fees/invoices/{$invoiceId}", 'success', 'Payment recorded successfully. Official receipt issued.');
    }

    /**
     * Printable view of student fee invoice docket
     */
    public function printInvoice(Request $request, string $id): Response
    {
        $userContext = $request->getAttribute('user_context') ?? $this->getUserContext($request);
        if (!$userContext instanceof UserContext || !$userContext->hasAnyRole(['admin', 'super_admin'])) {
            return $this->forbidden('Access denied.');
        }

        $invoice = $this->feeRepo->findInvoiceById((int)$id);
        if (!$invoice) {
            return $this->redirectWithFlash('/admin/fees/invoices', 'error', 'Invoice not found.');
        }

        return $this->view('admin/fees/print_invoice', [
            'invoice' => $invoice,
        ], 200, 'layouts/plain');
    }
}
