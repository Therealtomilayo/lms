<?php
/**
 * Parent School Fees & Invoices Overview
 * 
 * @var \App\Models\ParentProfile $parent
 * @var array $linkedStudents
 * @var \App\Models\FeeInvoice[] $invoices
 * @var int $selectedStudentId
 * @var float $totalOutstanding
 * @var float $totalPaid
 */
$this->layout('layouts/parent', [
    'title' => 'School Fees & Invoices — Claret Parent Portal',
]);
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-brand-50 text-brand-700 border border-brand-200">
                    Parent Portal
                </span>
                <span class="text-xs text-slate-500">&bull; Bursary &amp; Tuition</span>
            </div>
            <h1 class="text-2xl font-black tracking-tight text-slate-900 mt-1">School Fees &amp; Invoices</h1>
            <p class="text-xs text-slate-500 mt-0.5">Review termly tuition dockets, pay in installments via Paystack, and download official receipts.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="/payments/history" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-bold transition shadow-xs">
                <i data-lucide="receipt" class="w-4 h-4 text-slate-500"></i>
                All Payment Receipts
            </a>
        </div>
    </div>

    <!-- Summary Metrics Grid -->
    <div class="grid sm:grid-cols-2 gap-4">
        <div class="bg-white rounded-3xl border border-slate-200 p-6 shadow-xs flex items-center justify-between">
            <div>
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block">Total Outstanding Balance</span>
                <div class="text-3xl font-black font-mono mt-1 <?= $totalOutstanding > 0 ? 'text-rose-700' : 'text-emerald-700' ?>">
                    ₦<?= number_format($totalOutstanding, 2) ?>
                </div>
                <p class="text-xs text-slate-500 mt-1">
                    <?= $totalOutstanding > 0 ? 'Pending payment across all your enrolled wards' : 'All accounts are completely up to date!' ?>
                </p>
            </div>
            <div class="size-14 rounded-2xl <?= $totalOutstanding > 0 ? 'bg-rose-50 text-rose-600' : 'bg-emerald-50 text-emerald-600' ?> flex items-center justify-center">
                <i data-lucide="<?= $totalOutstanding > 0 ? 'alert-circle' : 'check-circle' ?>" class="w-7 h-7"></i>
            </div>
        </div>

        <div class="bg-white rounded-3xl border border-slate-200 p-6 shadow-xs flex items-center justify-between">
            <div>
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block">Total Fees Paid to Date</span>
                <div class="text-3xl font-black text-emerald-700 font-mono mt-1">
                    ₦<?= number_format($totalPaid, 2) ?>
                </div>
                <p class="text-xs text-slate-500 mt-1">Successfully processed online &amp; bursary payments</p>
            </div>
            <div class="size-14 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                <i data-lucide="wallet" class="w-7 h-7"></i>
            </div>
        </div>
    </div>

    <!-- Multi-Child Filter Selector -->
    <?php if (count($linkedStudents) > 1): ?>
        <div class="flex items-center gap-2 overflow-x-auto pb-1 text-xs">
            <span class="font-bold text-slate-500">Filter by Ward:</span>
            <a href="/parent/fees" class="px-3.5 py-1.5 rounded-xl font-bold transition <?= $selectedStudentId === 0 ? 'bg-slate-900 text-white' : 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-50' ?>">
                All Children (<?= count($linkedStudents) ?>)
            </a>
            <?php foreach ($linkedStudents as $st): ?>
                <a href="/parent/fees?student_id=<?= $st['id'] ?>" class="px-3.5 py-1.5 rounded-xl font-bold transition <?= $selectedStudentId === (int)$st['id'] ? 'bg-brand-600 text-white' : 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-50' ?>">
                    <?= htmlspecialchars($st['name'], ENT_QUOTES, 'UTF-8') ?>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Invoices List -->
    <?php if (empty($invoices)): ?>
        <div class="bg-white rounded-3xl border border-slate-200 p-12 text-center shadow-xs">
            <div class="size-16 rounded-3xl bg-slate-100 flex items-center justify-center mx-auto text-slate-400 mb-4">
                <i data-lucide="file-check" class="w-8 h-8"></i>
            </div>
            <h3 class="text-base font-bold text-slate-800">No Fee Invoices Available</h3>
            <p class="text-xs text-slate-500 max-w-sm mx-auto mt-1">There are no term bills issued for your enrolled children yet, or all past terms have been archived.</p>
        </div>
    <?php else: ?>
        <div class="grid md:grid-cols-2 gap-5">
            <?php foreach ($invoices as $inv): ?>
                <div class="bg-white rounded-3xl border border-slate-200 p-6 shadow-xs flex flex-col justify-between hover:shadow-md transition">
                    <div class="space-y-4">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <div class="flex items-center gap-2">
                                    <span class="text-xs font-mono font-bold text-slate-400">
                                        <?= htmlspecialchars($inv->invoiceNumber, ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                    <?php if ($inv->isPaid()): ?>
                                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            Paid in Full
                                        </span>
                                    <?php elseif ($inv->isPartiallyPaid()): ?>
                                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-amber-50 text-amber-700 border border-amber-200">
                                            Partially Paid
                                        </span>
                                    <?php else: ?>
                                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-rose-50 text-rose-700 border border-rose-200">
                                            Unpaid
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <h3 class="text-lg font-black text-slate-900 mt-1">
                                    <?= htmlspecialchars($inv->studentName ?? 'Ward', ENT_QUOTES, 'UTF-8') ?>
                                </h3>
                                <div class="text-xs text-slate-500">
                                    <?= htmlspecialchars($inv->className ?? '', ENT_QUOTES, 'UTF-8') ?> &bull; 
                                    <?= htmlspecialchars($inv->termName . ' (' . $inv->sessionName . ')', ENT_QUOTES, 'UTF-8') ?>
                                </div>
                            </div>
                            <div class="text-right">
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Balance Due</span>
                                <div class="text-xl font-black font-mono mt-0.5 <?= $inv->balanceDue > 0 ? 'text-rose-700' : 'text-emerald-700' ?>">
                                    ₦<?= number_format($inv->balanceDue, 2) ?>
                                </div>
                            </div>
                        </div>

                        <!-- Mini breakdown -->
                        <div class="bg-slate-50 rounded-2xl p-3.5 border border-slate-100 space-y-1.5 text-xs">
                            <div class="flex items-center justify-between text-slate-600">
                                <span>Total Term Fees:</span>
                                <span class="font-bold font-mono text-slate-800">₦<?= number_format($inv->totalAmount, 2) ?></span>
                            </div>
                            <div class="flex items-center justify-between text-slate-600">
                                <span>Amount Paid:</span>
                                <span class="font-bold font-mono text-emerald-700">₦<?= number_format($inv->amountPaid, 2) ?></span>
                            </div>
                            <?php if ($inv->dueDate): ?>
                                <div class="flex items-center justify-between text-[11px] text-slate-400 border-t border-slate-200/60 pt-1.5 mt-1.5">
                                    <span>Due Date:</span>
                                    <span class="font-medium text-slate-600"><?= date('M d, Y', strtotime($inv->dueDate)) ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="border-t border-slate-100 pt-4 mt-5 flex items-center justify-between">
                        <span class="text-xs text-slate-400 font-mono">Adm: <?= htmlspecialchars($inv->admissionNumber ?? '—', ENT_QUOTES, 'UTF-8') ?></span>
                        <div class="flex items-center gap-2">
                            <a href="/parent/fees/invoices/<?= $inv->id ?>" class="px-4 py-2 rounded-xl <?= $inv->isPaid() ? 'bg-slate-100 hover:bg-slate-200 text-slate-700' : 'bg-brand-600 hover:bg-brand-700 text-white' ?> text-xs font-bold transition flex items-center gap-1.5">
                                <?= $inv->isPaid() ? 'View Receipt & Details' : 'View &amp; Pay Fees' ?>
                                <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
