<?php
/**
 * Admin Payments & Revenue Ledger View
 * 
 * @var array $payments
 * @var int $total
 * @var int $page
 * @var int $limit
 * @var array $stats
 * @var string|null $status
 * @var string|null $search
 */
$this->layout('layouts/admin');
?>

<div class="space-y-6 max-w-7xl mx-auto">
    <!-- Header Card -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs font-semibold text-slate-500 mb-1">
                <a href="/admin/dashboard" class="hover:text-brand-600 transition">Dashboard</a>
                <span>&rsaquo;</span>
                <span class="text-slate-800">Financial Management</span>
                <span>&rsaquo;</span>
                <span class="text-brand-600">Payments Ledger</span>
            </div>
            <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">Payments & Revenue Ledger</h1>
            <p class="text-xs text-slate-500 mt-1">Audit all online Scratch-Card PIN transactions, revenue streams, and Paystack settlement references.</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="/admin/results/pins" class="px-4 py-2 text-xs font-bold text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-xl transition">
                Manage PINs
            </a>
        </div>
    </div>

    <!-- 4 KPI Stat Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs">
            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Total Revenue</span>
            <div class="text-2xl font-black text-slate-900 mt-1">₦<?= number_format($stats['total_volume'] ?? 0, 2) ?></div>
            <p class="text-[11px] text-slate-500 mt-0.5">Verified volume</p>
        </div>
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs">
            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Successful Payments</span>
            <div class="text-2xl font-black text-emerald-600 mt-1"><?= $stats['successful_count'] ?? 0 ?></div>
            <p class="text-[11px] text-slate-500 mt-0.5">Transactions completed</p>
        </div>
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs">
            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">PIN Purchases</span>
            <div class="text-2xl font-black text-brand-600 mt-1"><?= $stats['pin_payments_count'] ?? 0 ?></div>
            <p class="text-[11px] text-slate-500 mt-0.5">Online scratch card orders</p>
        </div>
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs">
            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Pending Attempts</span>
            <div class="text-2xl font-black text-amber-600 mt-1"><?= $stats['pending_count'] ?? 0 ?></div>
            <p class="text-[11px] text-slate-500 mt-0.5">Awaiting gateway capture</p>
        </div>
    </div>

    <!-- Filters & Search Toolbar -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-4 flex flex-col sm:flex-row items-center justify-between gap-4">
        <form method="GET" action="/admin/payments" class="flex flex-wrap items-center gap-3 w-full sm:w-auto">
            <input type="text" 
                   name="q" 
                   value="<?= e($search ?? '') ?>" 
                   placeholder="Search reference, student, or payer..."
                   class="text-xs border border-slate-300 rounded-xl px-3.5 py-2 w-full sm:w-64 focus:ring-2 focus:ring-brand-500 focus:border-brand-500">

            <select name="status" class="text-xs border border-slate-300 rounded-xl px-3.5 py-2 focus:ring-2 focus:ring-brand-500">
                <option value="">All Statuses</option>
                <option value="successful" <?= $status === 'successful' ? 'selected' : '' ?>>Successful</option>
                <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>Pending</option>
                <option value="failed" <?= $status === 'failed' ? 'selected' : '' ?>>Failed</option>
            </select>

            <button type="submit" class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold rounded-xl shadow-xs transition">
                Filter
            </button>
            <?php if (!empty($status) || !empty($search)): ?>
                <a href="/admin/payments" class="text-xs text-slate-500 hover:text-slate-800 underline">Reset</a>
            <?php endif; ?>
        </form>
        <span class="text-xs text-slate-500 font-medium">Showing <?= count($payments) ?> of <?= $total ?> records</span>
    </div>

    <!-- Transactions Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
        <?php if (empty($payments)): ?>
            <div class="p-12 text-center text-slate-500 text-xs">No transactions matching your criteria.</div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-slate-700">
                    <thead class="bg-slate-50 text-[11px] uppercase tracking-wider text-slate-500 font-bold border-b border-slate-200">
                        <tr>
                            <th class="py-3.5 px-4">Reference</th>
                            <th class="py-3.5 px-4">Payer</th>
                            <th class="py-3.5 px-4">Student</th>
                            <th class="py-3.5 px-4">Item & Purpose</th>
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
                                    <span class="font-bold text-slate-900 block"><?= e($pay->payerName ?? 'User #' . $pay->userId) ?></span>
                                    <span class="text-[10px] text-slate-400"><?= e($pay->payerEmail ?? '') ?></span>
                                </td>
                                <td class="py-3.5 px-4">
                                    <span class="font-semibold text-slate-900 block"><?= e($pay->studentName ?? 'Student #' . $pay->studentId) ?></span>
                                    <span class="text-[10px] font-mono text-slate-400"><?= e($pay->studentAdmissionNumber ?? '') ?></span>
                                </td>
                                <td class="py-3.5 px-4">
                                    <span class="font-bold text-slate-900 block"><?= e($pay->metadata['item_description'] ?? 'Result Access PIN') ?></span>
                                    <span class="text-[10px] text-slate-400"><?= e($pay->sessionName ?? '') ?> &bull; <?= e($pay->termName ?? '') ?></span>
                                </td>
                                <td class="py-3.5 px-4 font-black text-slate-900">
                                    <?= e($pay->getFormattedAmount()) ?>
                                </td>
                                <td class="py-3.5 px-4 capitalize text-slate-600">
                                    <?= e(str_replace('_', ' ', $pay->channel)) ?>
                                </td>
                                <td class="py-3.5 px-4">
                                    <?php if ($pay->status === 'successful'): ?>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">Successful</span>
                                    <?php elseif ($pay->status === 'pending'): ?>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800">Pending</span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-800">Failed</span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3.5 px-4 text-slate-500 whitespace-nowrap">
                                    <?= date('M j, Y g:ia', strtotime($pay->paidAt ?? $pay->createdAt)) ?>
                                </td>
                                <td class="py-3.5 px-4 text-right">
                                    <a href="/payments/<?= e($pay->reference) ?>/receipt" 
                                       class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-semibold text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-lg transition">
                                        View
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
