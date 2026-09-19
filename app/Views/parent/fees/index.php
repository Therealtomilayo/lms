<?php
/**
 * Parent School Fees & Invoices Overview — Claret LMS
 * Refined institutional UI adhering to 08-ui-design-system.md
 * 
 * @var \App\Models\ParentProfile $parent
 * @var \App\Models\Student[] $linkedStudents
 * @var \App\Models\FeeInvoice[] $invoices
 * @var int $selectedStudentId
 * @var float $totalOutstanding
 * @var float $totalPaid
 */
$this->layout('layouts/parent', [
    'title' => 'School Fees & Invoices — Claret Parent Portal',
    'headerTitle' => 'School Fees & Invoices',
    'headerSubtitle' => 'Review termly tuition dockets, pay in installments via Paystack, and download official receipts.',
]);

$parentName = $parent->userName ?? ($parent->user?->name ?? 'Guardian');
$unpaidCount = 0;
$partiallyPaidCount = 0;
$paidCount = 0;

foreach ($invoices as $inv) {
    if ($inv->isPaid()) {
        $paidCount++;
    } elseif ($inv->isPartiallyPaid()) {
        $partiallyPaidCount++;
    } else {
        $unpaidCount++;
    }
}
?>

<div class="space-y-6">
    <!-- Header Hero Card -->
    <div class="bg-white rounded-2xl border border-slate-200/90 shadow-xs p-6">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-5">
            <div class="flex items-start sm:items-center gap-4">
                <div class="size-14 rounded-2xl bg-[#7B3046] text-white font-extrabold text-xl flex items-center justify-center shadow-xs flex-shrink-0">
                    <?= strtoupper(substr($parentName, 0, 1)) ?>
                </div>
                <div>
                    <nav class="flex items-center gap-2 text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">
                        <span>Guardian Portal</span>
                        <span class="text-slate-300">/</span>
                        <span class="text-slate-700">Bursary &amp; Tuition</span>
                    </nav>
                    <div class="flex flex-wrap items-center gap-2.5">
                        <h1 class="text-2xl font-black text-slate-900 tracking-tight leading-tight">
                            School Fees &amp; Invoices
                        </h1>
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold bg-[#FDF2F4] text-[#7B3046] border border-[#F5C5CF]">
                            <span class="size-1.5 rounded-full bg-[#7B3046]"></span>
                            Family Bursary Ledger
                        </span>
                    </div>
                    <p class="text-xs text-slate-500 mt-1">
                        Review termly fee schedules for your enrolled wards, make instant partial or full payments via Paystack, and print stamped receipts.
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-2.5 self-start md:self-center flex-shrink-0">
                <a href="/payments/history" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-bold transition shadow-xs">
                    <i data-lucide="receipt" class="w-4 h-4 text-slate-500"></i>
                    <span>Payment History</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Financial KPI Summary Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <!-- Outstanding Balance Card -->
        <div class="bg-white rounded-2xl border <?= $totalOutstanding > 0 ? 'border-rose-200/80 bg-gradient-to-br from-white via-rose-50/20 to-white' : 'border-slate-200' ?> p-5 shadow-xs flex items-center justify-between">
            <div class="min-w-0">
                <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Outstanding Balance</span>
                <div class="text-2xl sm:text-3xl font-black font-mono mt-1 truncate <?= $totalOutstanding > 0 ? 'text-rose-600' : 'text-emerald-600' ?>">
                    ₦<?= number_format($totalOutstanding, 2) ?>
                </div>
                <div class="flex items-center gap-1.5 mt-1 text-xs text-slate-500">
                    <?php if ($totalOutstanding > 0): ?>
                        <span class="size-2 rounded-full bg-rose-500"></span>
                        <span><?= $unpaidCount + $partiallyPaidCount ?> term docket(s) pending payment</span>
                    <?php else: ?>
                        <span class="size-2 rounded-full bg-emerald-500"></span>
                        <span class="text-emerald-700 font-medium">All accounts completely settled</span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="size-12 rounded-xl <?= $totalOutstanding > 0 ? 'bg-rose-50 text-rose-600 border border-rose-100' : 'bg-emerald-50 text-emerald-600 border border-emerald-100' ?> flex items-center justify-center flex-shrink-0 ml-3">
                <i data-lucide="<?= $totalOutstanding > 0 ? 'alert-circle' : 'check-circle-2' ?>" class="w-6 h-6"></i>
            </div>
        </div>

        <!-- Total Paid Card -->
        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs flex items-center justify-between">
            <div class="min-w-0">
                <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Total Discharged to Date</span>
                <div class="text-2xl sm:text-3xl font-black text-emerald-600 font-mono mt-1 truncate">
                    ₦<?= number_format($totalPaid, 2) ?>
                </div>
                <div class="flex items-center gap-1.5 mt-1 text-xs text-slate-500">
                    <span class="size-2 rounded-full bg-emerald-500"></span>
                    <span>Verified Paystack &amp; bursary receipts</span>
                </div>
            </div>
            <div class="size-12 rounded-xl bg-emerald-50 text-emerald-600 border border-emerald-100 flex items-center justify-center flex-shrink-0 ml-3">
                <i data-lucide="wallet" class="w-6 h-6"></i>
            </div>
        </div>

        <!-- Invoices Counter Card -->
        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs flex items-center justify-between">
            <div class="min-w-0">
                <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Billing Dockets</span>
                <div class="text-2xl sm:text-3xl font-black text-slate-900 font-mono mt-1">
                    <?= count($invoices) ?> <span class="text-xs font-bold text-slate-400 font-sans tracking-normal">Invoice(s)</span>
                </div>
                <div class="flex items-center gap-1.5 mt-1 text-xs text-slate-500">
                    <span class="text-slate-600 font-medium"><?= count($linkedStudents) ?> Enrolled Ward(s) linked</span>
                </div>
            </div>
            <div class="size-12 rounded-xl bg-[#FDF2F4] text-[#7B3046] border border-[#F5C5CF] flex items-center justify-center flex-shrink-0 ml-3">
                <i data-lucide="file-text" class="w-6 h-6"></i>
            </div>
        </div>
    </div>

    <!-- Multi-Child Ward Filter Selector -->
    <?php if (count($linkedStudents) > 1): ?>
        <div class="bg-white rounded-2xl border border-slate-200/90 p-4 shadow-xs">
            <div class="flex items-center gap-2 overflow-x-auto pb-1 text-xs">
                <span class="font-bold text-slate-500 mr-1 flex items-center gap-1.5 flex-shrink-0">
                    <i data-lucide="filter" class="w-3.5 h-3.5 text-slate-400"></i>
                    Filter by Ward:
                </span>
                <a href="/parent/fees" class="px-3.5 py-1.5 rounded-xl font-bold transition flex-shrink-0 <?= $selectedStudentId === 0 ? 'bg-[#7B3046] text-white shadow-xs' : 'bg-slate-50 border border-slate-200 text-slate-700 hover:bg-slate-100' ?>">
                    All Wards (<?= count($linkedStudents) ?>)
                </a>
                <?php foreach ($linkedStudents as $st): ?>
                    <?php
                        $stId = is_object($st) ? (int)$st->id : (int)($st['id'] ?? 0);
                        $stName = is_object($st) ? ($st->userName ?? $st->user?->name ?? 'Ward') : ($st['name'] ?? 'Ward');
                        $stClass = is_object($st) ? ($st->className ?? '') : ($st['class_name'] ?? '');
                    ?>
                    <a href="/parent/fees?student_id=<?= $stId ?>" class="px-3.5 py-1.5 rounded-xl font-bold transition flex items-center gap-1.5 flex-shrink-0 <?= $selectedStudentId === $stId ? 'bg-[#7B3046] text-white shadow-xs' : 'bg-slate-50 border border-slate-200 text-slate-700 hover:bg-slate-100' ?>">
                        <span class="size-2 rounded-full <?= $selectedStudentId === $stId ? 'bg-white' : 'bg-brand-500' ?>"></span>
                        <span><?= htmlspecialchars($stName, ENT_QUOTES, 'UTF-8') ?></span>
                        <?php if (!empty($stClass)): ?>
                            <span class="text-[10px] opacity-75">(<?= htmlspecialchars($stClass, ENT_QUOTES, 'UTF-8') ?>)</span>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Invoices List -->
    <?php if (empty($invoices)): ?>
        <div class="bg-white rounded-3xl border border-slate-200 p-12 text-center shadow-xs">
            <div class="size-16 rounded-3xl bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-4">
                <i data-lucide="file-check" class="w-8 h-8"></i>
            </div>
            <h3 class="text-base font-bold text-slate-800">No Fee Invoices Available</h3>
            <p class="text-xs text-slate-500 max-w-sm mx-auto mt-1">
                There are no term bills issued for your enrolled children yet, or all previous terms have been settled and archived.
            </p>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <?php foreach ($invoices as $inv): ?>
                <?php 
                    $pct = $inv->totalAmount > 0 ? min(100, round(($inv->amountPaid / $inv->totalAmount) * 100)) : 0;
                    $isOverdue = $inv->dueDate && strtotime($inv->dueDate) < time() && !$inv->isPaid();
                ?>
                <div class="bg-white rounded-2xl border border-slate-200/90 p-6 shadow-xs flex flex-col justify-between hover:border-slate-300 hover:shadow-md transition">
                    <div class="space-y-4">
                        <!-- Invoice Header -->
                        <div class="flex items-start justify-between gap-3 border-b border-slate-100 pb-4">
                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="font-mono text-xs font-bold text-slate-500 bg-slate-100 px-2.5 py-0.5 rounded-md">
                                        <?= htmlspecialchars($inv->invoiceNumber, ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                    <?php if ($inv->isPaid()): ?>
                                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            Paid in Full
                                        </span>
                                    <?php elseif ($inv->isPartiallyPaid()): ?>
                                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-amber-50 text-amber-700 border border-amber-200">
                                            Partially Paid (<?= $pct ?>%)
                                        </span>
                                    <?php elseif ($isOverdue): ?>
                                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-rose-100 text-rose-800 border border-rose-300">
                                            Overdue
                                        </span>
                                    <?php else: ?>
                                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-rose-50 text-rose-700 border border-rose-200">
                                            Payment Due
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <h3 class="text-lg font-black text-slate-900 mt-2">
                                    <?= htmlspecialchars($inv->studentName ?? 'Ward', ENT_QUOTES, 'UTF-8') ?>
                                </h3>
                                <div class="text-xs text-slate-500 mt-0.5 flex flex-wrap items-center gap-1.5">
                                    <span class="font-semibold text-slate-700"><?= htmlspecialchars($inv->className ?? '', ENT_QUOTES, 'UTF-8') ?></span>
                                    <span>&bull;</span>
                                    <span><?= htmlspecialchars($inv->termName . ' (' . $inv->sessionName . ')', ENT_QUOTES, 'UTF-8') ?></span>
                                </div>
                            </div>
                            <div class="text-right flex-shrink-0">
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Balance Due</span>
                                <div class="text-xl sm:text-2xl font-black font-mono mt-0.5 <?= $inv->balanceDue > 0 ? 'text-rose-600' : 'text-emerald-600' ?>">
                                    ₦<?= number_format($inv->balanceDue, 2) ?>
                                </div>
                            </div>
                        </div>

                        <!-- Progress Bar & Breakdown -->
                        <div class="space-y-2">
                            <div class="flex items-center justify-between text-xs">
                                <span class="text-slate-500 font-medium">Payment Clearance:</span>
                                <span class="font-bold text-slate-700 font-mono"><?= $pct ?>%</span>
                            </div>
                            <div class="w-full bg-slate-100 rounded-full h-2 overflow-hidden">
                                <div class="bg-emerald-500 h-2 rounded-full transition-all duration-300" style="width: <?= $pct ?>%"></div>
                            </div>
                        </div>

                        <!-- Financial Ledger Summary -->
                        <div class="bg-slate-50 rounded-xl p-3.5 border border-slate-100 space-y-1.5 text-xs">
                            <div class="flex items-center justify-between text-slate-600">
                                <span>Total Term Fees:</span>
                                <span class="font-bold font-mono text-slate-800">₦<?= number_format($inv->totalAmount, 2) ?></span>
                            </div>
                            <div class="flex items-center justify-between text-slate-600">
                                <span>Amount Cleared:</span>
                                <span class="font-bold font-mono text-emerald-600">₦<?= number_format($inv->amountPaid, 2) ?></span>
                            </div>
                            <?php if ($inv->dueDate): ?>
                                <div class="flex items-center justify-between text-[11px] <?= $isOverdue ? 'text-rose-600 font-bold' : 'text-slate-500' ?> border-t border-slate-200/60 pt-1.5 mt-1.5">
                                    <span class="flex items-center gap-1">
                                        <i data-lucide="calendar" class="w-3.5 h-3.5"></i>
                                        Due Date:
                                    </span>
                                    <span><?= date('M d, Y', strtotime($inv->dueDate)) ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Footer Action Controls -->
                    <div class="border-t border-slate-100 pt-4 mt-5 flex flex-wrap items-center justify-between gap-3">
                        <span class="text-xs text-slate-400 font-mono">
                            Adm: <?= htmlspecialchars($inv->admissionNumber ?? '—', ENT_QUOTES, 'UTF-8') ?>
                        </span>
                        <div class="flex items-center gap-2">
                            <a href="/parent/fees/invoices/<?= $inv->id ?>" class="px-4 py-2 rounded-xl <?= $inv->isPaid() ? 'bg-slate-100 hover:bg-slate-200 text-slate-700' : 'bg-[#7B3046] hover:bg-[#5F2234] text-white shadow-xs' ?> text-xs font-bold transition flex items-center gap-1.5">
                                <span><?= $inv->isPaid() ? 'View Statement & Receipt' : 'Pay via Paystack' ?></span>
                                <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Institutional Bursary & Payment Security Notice -->
    <div class="bg-white rounded-2xl border border-slate-200/90 p-5 shadow-xs text-xs text-slate-600 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            <div class="size-10 rounded-xl bg-emerald-50 text-emerald-600 border border-emerald-100 flex items-center justify-center flex-shrink-0">
                <i data-lucide="shield-check" class="w-5 h-5"></i>
            </div>
            <div>
                <strong class="text-slate-900 block">Bank-Grade 256-bit Encrypted Payments</strong>
                <span>All online payments are securely processed by Paystack. Official receipts are issued immediately with institutional validation stamps.</span>
            </div>
        </div>
        <div class="text-slate-400 text-[11px] sm:text-right flex-shrink-0">
            For bank wire confirmations, contact the Bursary Office:<br>
            <strong class="text-slate-600">bursar@claret.edu.ng</strong>
        </div>
    </div>
</div>
