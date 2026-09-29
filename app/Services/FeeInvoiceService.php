<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Config;
use App\Core\Database;
use App\Core\UserContext;
use App\DTO\ServiceResult;
use App\Models\FeeInvoice;
use App\Models\FeeStructure;
use App\Models\Payment;
use App\Repositories\AcademicRepository;
use App\Repositories\FeeRepository;
use App\Repositories\ParentRepository;
use App\Repositories\PaymentRepository;
use App\Repositories\StudentRepository;
use PDO;

class FeeInvoiceService
{
    private FeeRepository $feeRepo;
    private PaymentRepository $paymentRepo;
    private StudentRepository $studentRepo;
    private AcademicRepository $academicRepo;
    private ParentRepository $parentRepo;
    private PaymentService $paymentService;
    private PDO $pdo;

    public function __construct(
        ?FeeRepository $feeRepo = null,
        ?PaymentRepository $paymentRepo = null,
        ?StudentRepository $studentRepo = null,
        ?AcademicRepository $academicRepo = null,
        ?ParentRepository $parentRepo = null,
        ?PaymentService $paymentService = null,
        ?PDO $pdo = null
    ) {
        $this->feeRepo = $feeRepo ?? new FeeRepository();
        $this->paymentRepo = $paymentRepo ?? new PaymentRepository();
        $this->studentRepo = $studentRepo ?? new StudentRepository();
        $this->academicRepo = $academicRepo ?? new AcademicRepository();
        $this->parentRepo = $parentRepo ?? new ParentRepository();
        $this->paymentService = $paymentService ?? new PaymentService($this->paymentRepo);
        $this->pdo = $pdo ?? Database::getConnection();
    }

    public function getFeeRepository(): FeeRepository
    {
        return $this->feeRepo;
    }

    /**
     * Configure or update a fee schedule with line items.
     */
    public function configureFeeStructure(array $data, array $items, int $userId): ServiceResult
    {
        $sessionId = (int)($data['session_id'] ?? 0);
        $termId = (int)($data['term_id'] ?? 0);
        $title = trim((string)($data['title'] ?? ''));

        if ($sessionId <= 0 || $termId <= 0 || empty($title)) {
            return ServiceResult::error('Session, Term, and Fee Structure Title are required.');
        }

        if (empty($items)) {
            return ServiceResult::error('A fee structure must contain at least one fee component.');
        }

        $data['created_by'] = $userId;
        $structure = $this->feeRepo->createStructure($data, $items);

        return ServiceResult::success($structure);
    }

    /**
     * Batch generate invoices for all enrolled students in a class or academic level.
     * Guaranteed IDEMPOTENT: skips students already billed for the session & term.
     */
    public function batchGenerateInvoices(
        int $sessionId,
        int $termId,
        ?int $levelId = null,
        ?int $classId = null,
        int $userId = 0
    ): ServiceResult {
        $session = $this->academicRepo->findSessionById($sessionId);
        $term = $this->academicRepo->findTermById($termId);

        if (!$session || !$term) {
            return ServiceResult::error('Invalid Academic Session or Term.');
        }

        // Fetch target enrolled students (from active class_enrollments or active students with current_class_id)
        $sessionIdInt = (int)$sessionId;
        $sql = "SELECT DISTINCT s.id as student_id, 
                       COALESCE(ce.class_id, s.current_class_id) as class_id, 
                       c.academic_level_id, 
                       s.user_id as student_user_id
                FROM `students` s
                JOIN `users` u ON u.id = s.user_id AND u.status = 'active'
                LEFT JOIN `class_enrollments` ce ON ce.student_id = s.id AND ce.status = 'active'
                JOIN `classes` c ON c.id = COALESCE(ce.class_id, s.current_class_id)
                WHERE (ce.session_id = {$sessionIdInt} OR (ce.id IS NULL AND s.current_class_id IS NOT NULL))";

        if ($classId !== null) {
            $sql .= ' AND COALESCE(ce.class_id, s.current_class_id) = ' . (int)$classId;
        } elseif ($levelId !== null) {
            $sql .= ' AND c.academic_level_id = ' . (int)$levelId;
        }

        $stmt = $this->pdo->query($sql);
        $enrollments = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        if (empty($enrollments)) {
            return ServiceResult::error('No active student enrollments or class allocations found matching the criteria.');
        }

        $createdCount = 0;
        $skippedCount = 0;
        $failedCount = 0;

        foreach ($enrollments as $enr) {
            $studentId = (int)$enr['student_id'];
            $stClassId = (int)$enr['class_id'];
            $stLevelId = (int)$enr['academic_level_id'];

            // Ensure active enrollment row exists in class_enrollments
            $now = date('Y-m-d H:i:s');
            $driver = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
            if ($driver === 'mysql') {
                $this->pdo->prepare('INSERT INTO `class_enrollments` (`student_id`, `class_id`, `session_id`, `status`, `enrolled_at`, `created_at`, `updated_at`)
                    VALUES (?, ?, ?, "active", ?, ?, ?)
                    ON DUPLICATE KEY UPDATE `class_id` = VALUES(`class_id`), `status` = "active"')
                    ->execute([$studentId, $stClassId, $sessionId, $now, $now, $now]);
            } else {
                $check = $this->pdo->prepare('SELECT id FROM `class_enrollments` WHERE `student_id` = ? AND `session_id` = ?');
                $check->execute([$studentId, $sessionId]);
                if ($existingEnrId = $check->fetchColumn()) {
                    $this->pdo->prepare('UPDATE `class_enrollments` SET `class_id` = ?, `status` = "active", `updated_at` = ? WHERE `id` = ?')
                        ->execute([$stClassId, $now, $existingEnrId]);
                } else {
                    $this->pdo->prepare('INSERT INTO `class_enrollments` (`student_id`, `class_id`, `session_id`, `status`, `enrolled_at`, `created_at`, `updated_at`)
                        VALUES (?, ?, ?, "active", ?, ?, ?)')
                        ->execute([$studentId, $stClassId, $sessionId, $now, $now, $now]);
                }
            }

            // 1. Idempotency check: verify if already billed
            $existing = $this->feeRepo->findInvoiceForStudentTerm($studentId, $sessionId, $termId);
            if ($existing !== null) {
                $skippedCount++;
                continue;
            }

            // 2. Resolve matching fee structure
            $structure = $this->feeRepo->findMatchingStructure($sessionId, $termId, $stLevelId, $stClassId);
            if (!$structure || empty($structure->items)) {
                $failedCount++;
                continue;
            }

            // 3. Resolve primary parent/guardian if linked
            $parentStmt = $this->pdo->prepare('SELECT parent_id FROM `parent_student` WHERE student_id = :s LIMIT 1');
            $parentStmt->execute([':s' => $studentId]);
            $parentId = $parentStmt->fetchColumn();
            $resolvedParentId = $parentId ? (int)$parentId : null;

            // 4. Generate unique invoice number
            $invoiceNumber = $this->feeRepo->generateInvoiceNumber($sessionId);

            // 5. Build snapshot line items adapted to student context (bus ridership, new entrant uniforms)
            $itemsData = $this->buildStudentInvoiceItems($structure, $studentId);

            $invoiceData = [
                'invoice_number' => $invoiceNumber,
                'student_id' => $studentId,
                'parent_id' => $resolvedParentId,
                'class_id' => $stClassId,
                'session_id' => $sessionId,
                'term_id' => $termId,
                'discount_amount' => 0.00,
                'amount_paid' => 0.00,
                'due_date' => $structure->dueDate,
                'notes' => "Termly Tuition & Levies — {$structure->title}",
                'created_by' => $userId,
            ];

            try {
                $this->feeRepo->createInvoice($invoiceData, $itemsData);
                $createdCount++;
            } catch (\Throwable) {
                $failedCount++;
            }
        }

        return ServiceResult::success([
            'created_count' => $createdCount,
            'skipped_count' => $skippedCount,
            'failed_count' => $failedCount,
            'total_processed' => count($enrollments),
        ]);
    }

    /**
     * Initiate online Paystack checkout for a student fee invoice.
     * Supports either full payment or custom installment amounts.
     */
    public function initiateInvoicePayment(
        FeeInvoice $invoice,
        float $amount,
        UserContext $actor,
        string $callbackUrl,
        array $selectedItemIds = []
    ): ServiceResult {
        if ($invoice->isPaid()) {
            return ServiceResult::error('This invoice is already fully paid.');
        }

        $amount = round($amount, 2);
        if ($amount < 100.0) {
            return ServiceResult::error('Minimum payment amount is ₦100.00.');
        }

        if ($amount > $invoice->balanceDue) {
            return ServiceResult::error(sprintf('Payment amount (₦%s) exceeds outstanding balance (₦%s).', number_format($amount, 2), number_format($invoice->balanceDue, 2)));
        }

        $reference = 'SCH-' . date('Ym') . '-' . strtoupper(bin2hex(random_bytes(4)));

        $payment = $this->paymentRepo->create([
            'reference' => $reference,
            'user_id' => $actor->getUserId(),
            'student_id' => $invoice->studentId,
            'session_id' => $invoice->sessionId,
            'term_id' => $invoice->termId,
            'invoice_id' => $invoice->id,
            'purpose' => Payment::PURPOSE_SCHOOL_FEES,
            'amount' => $amount,
            'currency' => 'NGN',
            'channel' => 'paystack_simulated',
            'status' => Payment::STATUS_PENDING,
            'metadata' => [
                'payer_name' => $actor->name,
                'payer_email' => $actor->email,
                'student_name' => $invoice->studentName,
                'admission_number' => $invoice->admissionNumber,
                'invoice_number' => $invoice->invoiceNumber,
                'session_name' => $invoice->sessionName,
                'term_name' => $invoice->termName,
                'class_name' => $invoice->className,
                'item_description' => "School Fees Payment ({$invoice->invoiceNumber} — {$invoice->studentName})",
                'is_partial' => ($amount < $invoice->balanceDue),
                'selected_item_ids' => array_values(array_filter(array_map('intval', $selectedItemIds))),
            ],
        ]);

        return ServiceResult::success($payment);
    }

    /**
     * Confirm / verify successful payment for school fees and update invoice financials.
     */
    public function verifyInvoicePayment(string $reference): ServiceResult
    {
        $payment = $this->paymentRepo->findByReference($reference);
        if (!$payment) {
            return ServiceResult::error("Payment transaction {$reference} not found.");
        }

        if ($payment->isSuccessful()) {
            $invoice = $payment->invoiceId ? $this->feeRepo->findInvoiceById($payment->invoiceId) : null;
            return ServiceResult::success([
                'payment' => $payment,
                'invoice' => $invoice,
                'already_processed' => true,
            ]);
        }

        $invoice = $payment->invoiceId ? $this->feeRepo->findInvoiceById($payment->invoiceId) : null;
        if (!$invoice) {
            return ServiceResult::error('Associated fee invoice not found.');
        }

        $now = date('Y-m-d H:i:s');
        $gatewayRef = 'MOCK_PSTK_' . strtoupper(bin2hex(random_bytes(6)));

        // 1. Mark payment as successful
        $this->paymentRepo->updateStatus($payment->id, Payment::STATUS_SUCCESSFUL, $gatewayRef, $now);

        // 2. Re-calculate invoice financials
        $newAmountPaid = $invoice->amountPaid + $payment->amount;
        $newBalance = max(0.0, $invoice->totalAmount - $newAmountPaid);

        $newStatus = FeeInvoice::STATUS_PARTIALLY_PAID;
        if ($newBalance <= 0.0) {
            $newStatus = FeeInvoice::STATUS_PAID;
        }

        $this->feeRepo->updateInvoiceFinancials($invoice->id, $newAmountPaid, $newBalance, $newStatus);

        // 3. Mark selected line items as paid if specified
        $selectedItemIds = $payment->metadata['selected_item_ids'] ?? [];
        if (!empty($selectedItemIds) && is_array($selectedItemIds)) {
            $this->feeRepo->markInvoiceItemsPaid($invoice->id, $selectedItemIds);
        }

        // If invoice is fully paid, ensure all items are marked paid
        if ($newStatus === FeeInvoice::STATUS_PAID) {
            $this->feeRepo->markAllInvoiceItemsPaid($invoice->id);
        }

        $refreshedPayment = $this->paymentRepo->findById($payment->id);
        $refreshedInvoice = $this->feeRepo->findInvoiceById($invoice->id);

        if ($refreshedPayment && $refreshedInvoice) {
            $this->dispatchFeeReceiptNotification($refreshedPayment, $refreshedInvoice);
        }

        return ServiceResult::success([
            'payment' => $refreshedPayment,
            'invoice' => $refreshedInvoice,
            'already_processed' => false,
        ]);
    }

    /**
     * Record a manual payment logged by the Bursar (Cash, Bank Transfer, or POS).
     */
    public function recordManualPayment(
        int $invoiceId,
        float $amount,
        string $channel,
        ?string $referenceNumber,
        ?string $notes,
        UserContext $actor
    ): ServiceResult {
        $invoice = $this->feeRepo->findInvoiceById($invoiceId);
        if (!$invoice) {
            return ServiceResult::error('Fee invoice not found.');
        }

        if ($invoice->isPaid()) {
            return ServiceResult::error('This invoice is already fully paid.');
        }

        $amount = round($amount, 2);
        if ($amount <= 0.0) {
            return ServiceResult::error('Amount must be greater than zero.');
        }

        if ($amount > $invoice->balanceDue) {
            return ServiceResult::error(sprintf('Payment amount (₦%s) exceeds balance due (₦%s).', number_format($amount, 2), number_format($invoice->balanceDue, 2)));
        }

        $now = date('Y-m-d H:i:s');
        $systemRef = 'BUR-' . date('Ym') . '-' . strtoupper(bin2hex(random_bytes(4)));
        $channel = in_array($channel, ['bank_transfer', 'pos', 'cash'], true) ? $channel : 'bank_transfer';

        // 1. Create successful payment record
        $payment = $this->paymentRepo->create([
            'reference' => $systemRef,
            'user_id' => $actor->getUserId(),
            'student_id' => $invoice->studentId,
            'session_id' => $invoice->sessionId,
            'term_id' => $invoice->termId,
            'invoice_id' => $invoice->id,
            'purpose' => Payment::PURPOSE_SCHOOL_FEES,
            'amount' => $amount,
            'currency' => 'NGN',
            'channel' => $channel,
            'status' => Payment::STATUS_SUCCESSFUL,
            'gateway_reference' => $referenceNumber ? trim($referenceNumber) : 'MANUAL_BURSARY_' . date('YmdHis'),
            'paid_at' => $now,
            'metadata' => [
                'recorded_by' => $actor->name,
                'recorder_id' => $actor->getUserId(),
                'student_name' => $invoice->studentName,
                'admission_number' => $invoice->admissionNumber,
                'invoice_number' => $invoice->invoiceNumber,
                'channel_label' => strtoupper(str_replace('_', ' ', $channel)),
                'bursary_notes' => $notes ? trim($notes) : null,
                'item_description' => "Bursary Payment ({$invoice->invoiceNumber} — {$invoice->studentName})",
            ],
        ]);

        // 2. Update invoice balance and status
        $newAmountPaid = $invoice->amountPaid + $amount;
        $newBalance = max(0.0, $invoice->totalAmount - $newAmountPaid);
        $newStatus = ($newBalance <= 0.0) ? FeeInvoice::STATUS_PAID : FeeInvoice::STATUS_PARTIALLY_PAID;

        $this->feeRepo->updateInvoiceFinancials($invoice->id, $newAmountPaid, $newBalance, $newStatus);

        // If invoice is fully paid, mark all line items as paid
        if ($newStatus === FeeInvoice::STATUS_PAID) {
            $this->feeRepo->markAllInvoiceItemsPaid($invoice->id);
        }

        $refreshedInvoice = $this->feeRepo->findInvoiceById($invoice->id);

        if ($payment && $refreshedInvoice) {
            $this->dispatchFeeReceiptNotification($payment, $refreshedInvoice);
        }

        return ServiceResult::success([
            'payment' => $payment,
            'invoice' => $refreshedInvoice,
        ]);
    }

    /**
     * Dispatch multi-channel fee receipt notification (SMS & Email)
     */
    private function dispatchFeeReceiptNotification(Payment $payment, FeeInvoice $invoice): void
    {
        try {
            $notificationService = new NotificationService();
            $meta = $payment->metadata ?? [];
            $payerName = $meta['payer_name'] ?? 'Parent / Guardian';
            $payerEmail = $meta['payer_email'] ?? null;
            $payerPhone = $meta['payer_phone'] ?? null;

            if (empty($payerPhone) || empty($payerEmail)) {
                $user = (new \App\Repositories\UserRepository($this->pdo))->findById($payment->userId);
                if ($user) {
                    if (empty($payerEmail)) $payerEmail = $user->email;
                    if (empty($payerPhone)) $payerPhone = $user->phone;
                    if ($payerName === 'Parent / Guardian' && !empty($user->name)) $payerName = $user->name;
                }
            }

            if (!empty($payerPhone) || !empty($payerEmail)) {
                $notificationService->sendFeePaymentReceipt(
                    payerPhone: $payerPhone,
                    payerEmail: $payerEmail,
                    payerName: $payerName,
                    studentName: $invoice->studentName ?? ($meta['student_name'] ?? 'Student'),
                    invoiceNumber: $invoice->invoiceNumber ?? ($meta['invoice_number'] ?? 'INV'),
                    reference: $payment->reference,
                    amountPaid: (float)$payment->amount,
                    balanceRemaining: (float)$invoice->balanceDue,
                    termName: $invoice->termName ?? ($meta['term_name'] ?? 'Current Term'),
                    userId: $payment->userId
                );
            }
        } catch (\Throwable $e) {
            error_log("Failed to dispatch fee receipt notification: " . $e->getMessage());
        }
    }

    /**
     * Synchronize existing student invoices when a fee structure schedule is modified.
     * Preserves already paid fee components strictly:
     *  - Paid items remain paid and are not marked outstanding or modified.
     *  - Unpaid items have their amounts and attributes updated.
     *  - Newly added items along the term are inserted as unpaid.
     *  - Invoice totals (subtotal, total_amount, balance_due, status) are accurately recomputed.
     *
     * @return ServiceResult
     */
    public function syncStructureInvoices(int $structureId): ServiceResult
    {
        $structure = $this->feeRepo->findStructureById($structureId);
        if (!$structure) {
            return ServiceResult::error('Fee structure not found.');
        }

        $invoices = $this->feeRepo->getInvoicesForStructureScope(
            $structure->sessionId,
            $structure->termId,
            $structure->academicLevelId,
            $structure->classId
        );

        $syncedCount = 0;
        $structureItems = $structure->items;

        foreach ($invoices as $inv) {
            $existingItems = $this->feeRepo->getItemsForInvoice($inv->id);

            // Index existing invoice items by lowercase name
            $existingByName = [];
            foreach ($existingItems as $it) {
                $key = mb_strtolower(trim($it->name));
                $existingByName[$key] = $it;
            }

            $matchedItemIds = [];

            // 1. Process each component in the updated structure
            foreach ($structureItems as $structItem) {
                $key = mb_strtolower(trim($structItem->name));

                if (isset($existingByName[$key])) {
                    $existing = $existingByName[$key];
                    $matchedItemIds[] = $existing->id;

                    $compulsoryFlag = !empty($structItem->isCompulsory) ? 1 : 0;
                    $reqResultFlag = !empty($structItem->isRequiredForResult) ? 1 : 0;

                    if ($existing->isPaid || (float)$existing->paidAmount > 0.0) {
                        // Already paid: DO NOT modify paid amount or reset status to unpaid!
                        // Only sync metadata flags (compulsory, result-lock)
                        $this->feeRepo->updateInvoiceItemFlags(
                            $existing->id,
                            $compulsoryFlag,
                            $reqResultFlag
                        );
                    } else {
                        // Unpaid: safely update amount, name, and result-lock settings
                        $this->feeRepo->updateInvoiceItem($existing->id, [
                            'name' => trim($structItem->name),
                            'fee_category_id' => $structItem->feeCategoryId,
                            'amount' => (float)$structItem->amount,
                            'is_compulsory' => $compulsoryFlag,
                            'is_required_for_result' => $reqResultFlag,
                        ]);
                    }
                } else {
                    // New component added along the term: insert as unpaid invoice item
                    $compulsoryFlag = !empty($structItem->isCompulsory) ? 1 : 0;
                    $reqResultFlag = !empty($structItem->isRequiredForResult) ? 1 : 0;

                    $this->feeRepo->addInvoiceItem($inv->id, [
                        'fee_category_id' => $structItem->feeCategoryId,
                        'name' => trim($structItem->name),
                        'amount' => (float)$structItem->amount,
                        'is_compulsory' => $compulsoryFlag,
                        'is_required_for_result' => $reqResultFlag,
                        'is_paid' => 0,
                        'paid_amount' => 0.00,
                    ]);
                }
            }

            // 2. Remove items that were deleted from the structure ONLY IF they are unpaid
            foreach ($existingItems as $existing) {
                if (!in_array($existing->id, $matchedItemIds, true)) {
                    if (!$existing->isPaid && (float)$existing->paidAmount <= 0.0) {
                        $this->feeRepo->deleteInvoiceItem($existing->id);
                    }
                }
            }

            // 3. Recalculate invoice totals
            $refreshedItems = $this->feeRepo->getItemsForInvoice($inv->id);
            $newSubtotal = 0.0;
            foreach ($refreshedItems as $it) {
                $newSubtotal += (float)$it->amount;
            }

            $newTotal = max(0.0, $newSubtotal - (float)$inv->discountAmount);

            // Fetch authoritative total amount paid from actual successful payments
            $totalPayments = $this->feeRepo->getTotalPaidForInvoice($inv->id);
            $currentAmountPaid = max((float)$totalPayments, (float)$inv->amountPaid);

            $newBalance = max(0.0, $newTotal - $currentAmountPaid);

            $newStatus = FeeInvoice::STATUS_UNPAID;
            if ($newBalance <= 0.0) {
                $newStatus = FeeInvoice::STATUS_PAID;
            } elseif ($currentAmountPaid > 0.0) {
                $newStatus = FeeInvoice::STATUS_PARTIALLY_PAID;
            }

            $this->feeRepo->updateInvoiceTotals($inv->id, $newSubtotal, $newTotal, $currentAmountPaid, $newBalance, $newStatus);
            $syncedCount++;
        }

        return ServiceResult::success([
            'synced_count' => $syncedCount,
            'structure_id' => $structureId,
        ]);
    }

    /**
     * Ensure a student has an invoice provisioned for the given session and term.
     * If session or term are not provided, defaults to the active academic session & term.
     * If an invoice already exists, updates parent_id if previously unlinked, and returns the invoice.
     */
    public function ensureInvoiceForStudent(int $studentId, ?int $sessionId = null, ?int $termId = null, int $userId = 0): ?FeeInvoice
    {
        if ($sessionId === null) {
            $activeSession = $this->academicRepo->findActiveSession();
            $sessionId = $activeSession ? (int)$activeSession->id : null;
        }

        if ($termId === null && $sessionId !== null) {
            // Check if there are already invoices generated in this session (e.g. admin batch-generated once)
            $invTermStmt = $this->pdo->prepare('SELECT term_id FROM `fee_invoices` WHERE `session_id` = :s ORDER BY id DESC LIMIT 1');
            $invTermStmt->execute([':s' => $sessionId]);
            $invoicedTermId = $invTermStmt->fetchColumn();

            if ($invoicedTermId) {
                $termId = (int)$invoicedTermId;
            } else {
                $activeTerm = $this->academicRepo->findActiveTermForSession($sessionId);
                if (!$activeTerm) {
                    $terms = $this->academicRepo->findTermsBySession($sessionId);
                    $activeTerm = $terms[0] ?? null;
                }
                $termId = $activeTerm ? (int)$activeTerm->id : null;
            }
        }

        if (!$sessionId || !$termId) {
            return null;
        }

        // 1. Resolve parent ID if linked
        $parentStmt = $this->pdo->prepare('SELECT parent_id FROM `parent_student` WHERE student_id = :s ORDER BY id ASC LIMIT 1');
        $parentStmt->execute([':s' => $studentId]);
        $parentId = $parentStmt->fetchColumn();
        $resolvedParentId = $parentId ? (int)$parentId : null;

        // 2. Check if invoice already exists
        $existing = $this->feeRepo->findInvoiceForStudentTerm($studentId, $sessionId, $termId);
        if ($existing !== null) {
            if ($resolvedParentId && (empty($existing->parentId) || (int)$existing->parentId !== (int)$resolvedParentId)) {
                $this->pdo->prepare('UPDATE `fee_invoices` SET `parent_id` = ? WHERE `id` = ?')
                    ->execute([$resolvedParentId, $existing->id]);
                $existing = $this->feeRepo->findInvoiceById($existing->id);
            }
            return $existing;
        }

        // 3. Resolve student class and academic level
        $studentStmt = $this->pdo->prepare('SELECT current_class_id FROM `students` WHERE `id` = :id');
        $studentStmt->execute([':id' => $studentId]);
        $stClassId = (int)$studentStmt->fetchColumn();

        if ($stClassId <= 0) {
            $enrStmt = $this->pdo->prepare('SELECT class_id FROM `class_enrollments` WHERE `student_id` = :id AND `session_id` = :ses ORDER BY id DESC LIMIT 1');
            $enrStmt->execute([':id' => $studentId, ':ses' => $sessionId]);
            $stClassId = (int)$enrStmt->fetchColumn();
        }

        if ($stClassId <= 0) {
            return null;
        }

        $lvlStmt = $this->pdo->prepare('SELECT academic_level_id FROM `classes` WHERE `id` = :id');
        $lvlStmt->execute([':id' => $stClassId]);
        $lvl = $lvlStmt->fetchColumn();
        $stLevelId = $lvl ? (int)$lvl : null;

        // 4. Resolve matching fee structure
        $structure = $this->feeRepo->findMatchingStructure($sessionId, $termId, $stLevelId, $stClassId);
        if (!$structure || empty($structure->items)) {
            return null;
        }

        // 5. Ensure valid creator user ID
        if ($userId <= 0) {
            $adminUserStmt = $this->pdo->query("SELECT user_id FROM `user_roles` WHERE role IN ('super_admin', 'admin') AND is_active = 1 ORDER BY user_id ASC LIMIT 1");
            $adminId = $adminUserStmt ? $adminUserStmt->fetchColumn() : null;
            if (!$adminId) {
                $firstUser = $this->pdo->query('SELECT id FROM `users` ORDER BY id ASC LIMIT 1');
                $adminId = $firstUser ? $firstUser->fetchColumn() : 11;
            }
            $userId = (int)$adminId;
        }

        // 6. Generate unique invoice number
        $invoiceNumber = $this->feeRepo->generateInvoiceNumber($sessionId);

        // 7. Build snapshot line items adapted to student context (bus ridership, new entrant uniforms)
        $itemsData = $this->buildStudentInvoiceItems($structure, $studentId);

        $invoiceData = [
            'invoice_number' => $invoiceNumber,
            'student_id' => $studentId,
            'parent_id' => $resolvedParentId,
            'class_id' => $stClassId,
            'session_id' => $sessionId,
            'term_id' => $termId,
            'discount_amount' => 0.00,
            'amount_paid' => 0.00,
            'due_date' => $structure->dueDate,
            'notes' => "Termly Tuition & Levies — {$structure->title}",
            'created_by' => $userId,
        ];

        try {
            return $this->feeRepo->createInvoice($invoiceData, $itemsData);
        } catch (\Throwable $e) {
            error_log('Failed to ensure invoice for student ' . $studentId . ': ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Build invoice line items adapted to student context:
     * 1. Uniforms: Compulsory for new applicants/entrants; omitted for returning students unless manually added.
     * 2. School Bus: Included on all invoices; compulsory if use_school_bus = 1, optional (unlocked) if use_school_bus = 0.
     * 3. General items: Follow fee structure item definition.
     */
    private function buildStudentInvoiceItems(FeeStructure $structure, int $studentId): array
    {
        // 1. Check if student uses school bus
        $usesBus = false;
        try {
            $busStmt = $this->pdo->prepare('SELECT use_school_bus FROM `students` WHERE id = :id');
            $busStmt->execute([':id' => $studentId]);
            $usesBus = (int)$busStmt->fetchColumn() === 1;
        } catch (\Throwable) {
            $usesBus = false;
        }

        // 2. Check if student is a new applicant / new entrant
        $isFromAdmission = false;
        try {
            $wardStmt = $this->pdo->prepare('SELECT 1 FROM `admission_wards` WHERE converted_student_id = :id LIMIT 1');
            $wardStmt->execute([':id' => $studentId]);
            $isFromAdmission = (bool)$wardStmt->fetchColumn();
        } catch (\Throwable) {
            $isFromAdmission = false;
        }

        $priorInvoicesStmt = $this->pdo->prepare('SELECT COUNT(*) FROM `fee_invoices` WHERE student_id = :id');
        $priorInvoicesStmt->execute([':id' => $studentId]);
        $priorInvoicesCount = (int)$priorInvoicesStmt->fetchColumn();

        $isNewEntrant = $isFromAdmission || $priorInvoicesCount === 0;

        $itemsData = [];
        foreach ($structure->items as $fsi) {
            $catId = (int)$fsi->feeCategoryId;
            $nameLower = mb_strtolower(trim($fsi->name));
            $applicability = $fsi->applicability ?? 'all';

            $isBusItem = ($applicability === 'bus_users_only')
                || ($catId === 10)
                || str_contains($nameLower, 'bus')
                || str_contains($nameLower, 'transport');

            $isUniformItem = ($applicability === 'new_students_only')
                || ($catId === 6)
                || str_contains($nameLower, 'uniform')
                || str_contains($nameLower, 'sportswear');

            if ($isUniformItem) {
                if ($isNewEntrant) {
                    // Enforce uniforms as compulsory (locks results) for new applicants
                    $itemsData[] = [
                        'fee_category_id' => $catId,
                        'name' => $fsi->name,
                        'amount' => $fsi->amount,
                        'is_compulsory' => 1,
                        'is_required_for_result' => 1,
                    ];
                }
                // Returning students: omit uniforms unless explicitly in structure for all
                continue;
            }

            if ($isBusItem) {
                // Include bus fee on all invoices:
                // Compulsory (locks results) for students who use bus, optional (unlocked) for non-bus students
                $itemsData[] = [
                    'fee_category_id' => $catId,
                    'name' => $fsi->name,
                    'amount' => $fsi->amount,
                    'is_compulsory' => $usesBus ? 1 : 0,
                    'is_required_for_result' => $usesBus ? 1 : 0,
                ];
                continue;
            }

            // General items
            $itemsData[] = [
                'fee_category_id' => $catId,
                'name' => $fsi->name,
                'amount' => $fsi->amount,
                'is_compulsory' => $fsi->isCompulsory ? 1 : 0,
                'is_required_for_result' => $fsi->isRequiredForResult ? 1 : 0,
            ];
        }

        return $itemsData;
    }
}

