<?php
/**
 * User Payment & Transaction History View
 * 
 * @var array $payments List of Payment models
 * @var string $role User role
 */
$layout = match($role) {
    'parent' => 'layouts/parent',
    'student' => 'layouts/student',
    'applicant' => 'layouts/applicant',
    default => 'layouts/admin'
};
$this->layout($layout);
?>

<div class="space-y-6 max-w-7xl mx-auto">
    <!-- Breadcrumb & Header -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs font-semibold text-slate-500 mb-1">
                <a href="<?= $role === 'applicant' ? '/applicant/dashboard' : '/dashboard' ?>" class="hover:text-brand-600 transition">Dashboard</a>
                <span>&rsaquo;</span>
                <span class="text-slate-800">Financial Records</span>
                <span>&rsaquo;</span>
                <span class="text-brand-600">Payment History</span>
            </div>
            <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">Payment & Transaction Ledger</h1>
            <p class="text-xs text-slate-500 mt-1">Review all completed receipts, payment references, application fees, and scratch-card PIN allocations.</p>
        </div>
        <div>
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                Paystack Gateway Enabled
            </span>
        </div>
    </div>

    <!-- Overview KPI Cards -->
    <?php
    $successful = array_filter($payments, fn($p) => $p->status === 'successful');
    $totalAmount = array_sum(array_map(fn($p) => $p->amount, $successful));
    ?>
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs">
            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Total Amount Paid</span>
            <div class="text-2xl font-black text-slate-900 mt-1">₦<?= number_format($totalAmount, 2) ?></div>
            <p class="text-[11px] text-slate-500 mt-0.5">Cumulative verified volume</p>
        </div>
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs">
            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Successful Transactions</span>
            <div class="text-2xl font-black text-emerald-600 mt-1"><?= count($successful) ?></div>
            <p class="text-[11px] text-slate-500 mt-0.5">Verified & confirmed payments</p>
        </div>
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs">
            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Total Records</span>
            <div class="text-2xl font-black text-brand-600 mt-1"><?= count($payments) ?></div>
            <p class="text-[11px] text-slate-500 mt-0.5">Transactions initiated</p>
        </div>
    </div>

    <!-- Payments Data Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="p-5 border-b border-slate-100 flex items-center justify-between">
            <h3 class="text-sm font-bold text-slate-900">Transaction History</h3>
            <span class="text-xs text-slate-500"><?= count($payments) ?> transactions found</span>
        </div>

        <?php if (empty($payments)): ?>
            <div class="p-12 text-center space-y-3">
                <div class="w-12 h-12 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center mx-auto">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                </div>
                <h4 class="text-sm font-bold text-slate-800">No Transactions Found</h4>
                <p class="text-xs text-slate-500 max-w-sm mx-auto">You have not initiated any online payments or result PIN purchases yet.</p>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-slate-700">
                    <thead class="bg-slate-50 text-[11px] uppercase tracking-wider text-slate-500 font-bold border-b border-slate-200">
                        <tr>
                            <th class="py-3.5 px-4">Reference</th>
                            <th class="py-3.5 px-4">Item & Purpose</th>
                            <th class="py-3.5 px-4">Beneficiary</th>
                            <th class="py-3.5 px-4">Amount</th>
                            <th class="py-3.5 px-4">Channel</th>
                            <th class="py-3.5 px-4">Status</th>
                            <th class="py-3.5 px-4">Date</th>
                            <th class="py-3.5 px-4 text-right">Receipt</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-medium">
                        <?php foreach ($payments as $pay): ?>
                            <tr class="hover:bg-slate-50/80 transition">
                                <td class="py-3.5 px-4 font-mono font-bold text-slate-900">
                                    <?= e($pay->reference) ?>
                                </td>
                                <td class="py-3.5 px-4">
                                    <div class="flex items-center gap-1.5 mb-1">
                                        <?php if ($pay->purpose === 'admission'): ?>
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-purple-50 text-purple-700 border border-purple-200">Admission Fee</span>
                                        <?php elseif ($pay->purpose === 'result_pin'): ?>
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-sky-50 text-sky-700 border border-sky-200">Result PIN</span>
                                        <?php else: ?>
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-700 border border-slate-200"><?= e(ucwords(str_replace('_', ' ', $pay->purpose))) ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <span class="font-bold text-slate-900 block"><?= e($pay->metadata['item_description'] ?? 'Payment') ?></span>
                                    <span class="text-[10px] text-slate-400"><?= e($pay->sessionName ?? '') ?><?= !empty($pay->termName) && $pay->termName !== 'Admission Application' ? ' &bull; ' . e($pay->termName) : '' ?></span>
                                </td>
                                <td class="py-3.5 px-4">
                                    <span class="font-semibold text-slate-900 block"><?= e($pay->studentName ?? ($pay->studentId ? 'Student #' . $pay->studentId : 'Prospective Ward')) ?></span>
                                    <span class="text-[10px] font-mono text-slate-400"><?= e($pay->studentAdmissionNumber ?? '') ?></span>
                                </td>
                                <td class="py-3.5 px-4 font-bold text-slate-900">
                                    <?= e($pay->getFormattedAmount()) ?>
                                </td>
                                <td class="py-3.5 px-4 capitalize text-slate-600">
                                    <?= e(str_replace('_', ' ', $pay->channel)) ?>
                                </td>
                                <td class="py-3.5 px-4">
                                    <?php if ($pay->status === 'successful'): ?>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">
                                            Successful
                                        </span>
                                    <?php elseif ($pay->status === 'pending'): ?>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800">
                                            Pending
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-800">
                                            Failed
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3.5 px-4 text-slate-500 whitespace-nowrap">
                                    <?= date('M j, Y g:ia', strtotime($pay->paidAt ?? $pay->createdAt)) ?>
                                </td>
                                <td class="py-3.5 px-4 text-right">
                                    <?php if ($pay->status === 'successful'): ?>
                                        <a href="/payments/<?= e($pay->reference) ?>/receipt" 
                                           target="_blank"
                                           class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-semibold text-brand-700 bg-brand-50 hover:bg-brand-100 border border-brand-200 rounded-lg transition"
                                           title="View & Print Official Receipt">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                            Receipt
                                        </a>
                                    <?php elseif ($pay->status === 'pending'): ?>
                                        <a href="/payments/<?= e($pay->reference) ?>/receipt" 
                                           target="_blank"
                                           class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-semibold text-amber-700 bg-amber-50 hover:bg-amber-100 border border-amber-200 rounded-lg transition"
                                           title="View Invoice / Pending Receipt">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                            Receipt
                                        </a>
                                    <?php else: ?>
                                        <a href="/payments/<?= e($pay->reference) ?>/receipt" 
                                           target="_blank"
                                           class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-semibold text-slate-600 bg-slate-50 hover:bg-slate-100 border border-slate-200 rounded-lg transition"
                                           title="View Transaction Record">
                                            Receipt
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
