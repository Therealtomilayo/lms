<?php
/**
 * Result Fee Clearance Lock Screen (Parent Portal)
 * 
 * @var \App\Models\Student $student
 * @var \App\Models\AcademicTerm|null $term
 * @var \App\Models\AcademicSession|null $session
 * @var array $unpaidItems
 * @var \App\Models\FeeInvoice|null $invoice
 * @var string $backUrl
 */
$this->layout('layouts/parent', [
    'title' => 'Result Locked Pending Bursary Clearance — Claret LMS',
]);

$totalUnpaidRequired = array_sum(array_column($unpaidItems, 'amount'));
?>

<div class="max-w-2xl mx-auto space-y-6 py-4">
    <!-- Back Bar -->
    <div>
        <a href="<?= htmlspecialchars($backUrl, ENT_QUOTES, 'UTF-8') ?>" class="inline-flex items-center gap-2 text-xs font-bold text-slate-500 hover:text-slate-800 transition">
            <i data-lucide="arrow-left" class="w-4 h-4"></i>
            <span>Back to Grades Overview</span>
        </a>
    </div>

    <!-- Main Lock Card -->
    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
        <!-- Header banner -->
        <div class="bg-rose-50/80 border-b border-rose-100 p-6 sm:p-8 text-center space-y-3">
            <div class="size-16 rounded-3xl bg-rose-100 border border-rose-200 text-rose-700 flex items-center justify-center mx-auto shadow-xs">
                <i data-lucide="lock" class="w-8 h-8"></i>
            </div>
            <div>
                <span class="inline-block px-3 py-1 rounded-full bg-rose-100/70 border border-rose-200 text-rose-800 text-[10px] font-black uppercase tracking-wider mb-2">
                    Bursary Clearance Required
                </span>
                <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">Academic Result Locked</h1>
                <p class="text-xs text-slate-600 max-w-md mx-auto mt-1">
                    Viewing the terminal report card for <strong><?= htmlspecialchars($student->name, ENT_QUOTES, 'UTF-8') ?></strong> (<?= htmlspecialchars($term->name ?? 'Selected Term', ENT_QUOTES, 'UTF-8') ?>) is restricted pending settlement of mandatory fee components.
                </p>
            </div>
        </div>

        <!-- Details & Unpaid Mandatory Items -->
        <div class="p-6 sm:p-8 space-y-6">
            <div class="grid grid-cols-2 gap-4 p-4 rounded-2xl bg-slate-50 border border-slate-100 text-xs">
                <div>
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Student</span>
                    <strong class="text-slate-800 text-sm"><?= htmlspecialchars($student->name, ENT_QUOTES, 'UTF-8') ?></strong>
                    <div class="font-mono text-slate-500 text-[11px]"><?= htmlspecialchars($student->admissionNumber, ENT_QUOTES, 'UTF-8') ?></div>
                </div>
                <div>
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Academic Term</span>
                    <strong class="text-slate-800 text-sm"><?= htmlspecialchars($term->name ?? '—', ENT_QUOTES, 'UTF-8') ?></strong>
                    <div class="text-slate-500 text-[11px]"><?= htmlspecialchars($session->name ?? '—', ENT_QUOTES, 'UTF-8') ?></div>
                </div>
            </div>

            <div class="space-y-3">
                <div class="flex items-center justify-between text-xs">
                    <span class="font-bold text-slate-800">Unsettled Mandatory Fee Components:</span>
                    <span class="text-rose-600 font-bold"><?= count($unpaidItems) ?> component(s)</span>
                </div>

                <div class="rounded-2xl border border-slate-200 divide-y divide-slate-100 overflow-hidden text-xs">
                    <?php foreach ($unpaidItems as $item): ?>
                        <div class="p-3.5 flex items-center justify-between hover:bg-slate-50 transition">
                            <div class="flex items-center gap-3">
                                <span class="size-2 rounded-full bg-rose-500"></span>
                                <div>
                                    <div class="font-bold text-slate-900"><?= htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8') ?></div>
                                    <span class="text-[10px] text-slate-400"><?= htmlspecialchars($item['category_name'] ?? 'Levy', ENT_QUOTES, 'UTF-8') ?></span>
                                </div>
                            </div>
                            <div class="font-mono font-bold text-slate-900">
                                ₦<?= number_format((float)$item['amount'], 2) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <div class="p-3.5 bg-slate-50/80 flex items-center justify-between font-bold text-xs">
                        <span class="text-slate-700">Total Mandatory Outstanding:</span>
                        <span class="font-mono text-rose-700 text-sm font-black">₦<?= number_format($totalUnpaidRequired, 2) ?></span>
                    </div>
                </div>
            </div>

            <!-- Action buttons -->
            <div class="pt-2 flex flex-col sm:flex-row items-center gap-3">
                <?php if ($invoice): ?>
                    <a href="/parent/fees/invoices/<?= $invoice->id ?>" class="w-full sm:w-auto flex-1 inline-flex items-center justify-center gap-2 px-6 py-3.5 rounded-2xl bg-brand-700 hover:bg-brand-800 text-white font-bold text-xs transition shadow-xs">
                        <i data-lucide="credit-card" class="w-4 h-4"></i>
                        <span>Settle Fees on Invoice #<?= htmlspecialchars($invoice->invoiceNumber, ENT_QUOTES, 'UTF-8') ?></span>
                    </a>
                <?php else: ?>
                    <a href="/parent/fees" class="w-full sm:w-auto flex-1 inline-flex items-center justify-center gap-2 px-6 py-3.5 rounded-2xl bg-brand-700 hover:bg-brand-800 text-white font-bold text-xs transition shadow-xs">
                        <i data-lucide="file-text" class="w-4 h-4"></i>
                        <span>View Fee Invoices</span>
                    </a>
                <?php endif; ?>
                <a href="<?= htmlspecialchars($backUrl, ENT_QUOTES, 'UTF-8') ?>" class="w-full sm:w-auto inline-flex items-center justify-center px-5 py-3.5 rounded-2xl border border-slate-200 text-slate-700 hover:bg-slate-50 font-bold text-xs transition">
                    Cancel
                </a>
            </div>

            <div class="text-center text-[11px] text-slate-400">
                <i data-lucide="info" class="w-3.5 h-3.5 inline mr-1 text-slate-400"></i>
                Results for previously cleared terms remain permanently accessible. Only the unsettled term is restricted.
            </div>
        </div>
    </div>
</div>
