<?php
/**
 * Result Fee Clearance Lock Screen (Student Portal)
 * 
 * @var \App\Models\Student $student
 * @var \App\Models\AcademicTerm|null $term
 * @var \App\Models\AcademicSession|null $session
 * @var array $unpaidItems
 * @var string $backUrl
 */
$this->layout('layouts/student', [
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
        <div class="bg-rose-50/80 border-b border-rose-100 p-6 sm:p-8 text-center space-y-3">
            <div class="size-16 rounded-3xl bg-rose-100 border border-rose-200 text-rose-700 flex items-center justify-center mx-auto shadow-xs">
                <i data-lucide="lock" class="w-8 h-8"></i>
            </div>
            <div>
                <span class="inline-block px-3 py-1 rounded-full bg-rose-100/70 border border-rose-200 text-rose-800 text-[10px] font-black uppercase tracking-wider mb-2">
                    Bursary Clearance Required
                </span>
                <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">Academic Report Card Restricted</h1>
                <p class="text-xs text-slate-600 max-w-md mx-auto mt-1">
                    Your terminal report card for <?= htmlspecialchars($term->name ?? 'the selected term', ENT_QUOTES, 'UTF-8') ?> is locked pending school fees settlement.
                </p>
            </div>
        </div>

        <div class="p-6 sm:p-8 space-y-6">
            <div class="p-4 rounded-2xl bg-amber-50 border border-amber-200 text-amber-800 text-xs flex items-start gap-3">
                <i data-lucide="alert-circle" class="w-5 h-5 text-amber-600 flex-shrink-0 mt-0.5"></i>
                <div>
                    <strong class="block text-amber-900 font-bold">Please notify your Parent or Guardian</strong>
                    <span>Your parent or guardian can log into the Claret Parent Portal at any time to inspect the fee docket and complete payment online via Paystack. Once cleared, your result unlocks automatically.</span>
                </div>
            </div>

            <div class="space-y-3">
                <span class="font-bold text-slate-800 text-xs">Unsettled Mandatory Fee Components:</span>
                <div class="rounded-2xl border border-slate-200 divide-y divide-slate-100 overflow-hidden text-xs">
                    <?php foreach ($unpaidItems as $item): ?>
                        <div class="p-3.5 flex items-center justify-between hover:bg-slate-50 transition">
                            <span class="font-bold text-slate-900"><?= htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8') ?></span>
                            <span class="font-mono font-bold text-slate-900">₦<?= number_format((float)$item['amount'], 2) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="pt-2 text-center">
                <a href="<?= htmlspecialchars($backUrl, ENT_QUOTES, 'UTF-8') ?>" class="inline-flex items-center justify-center px-6 py-3 rounded-2xl bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs transition">
                    Return to Grades Overview
                </a>
            </div>
        </div>
    </div>
</div>
