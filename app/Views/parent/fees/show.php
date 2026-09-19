<?php
/**
 * Parent / Student Fee Invoice Detail & Payment Screen
 * 
 * @var \App\Models\FeeInvoice $invoice
 * @var \App\Models\ParentProfile|null $parent
 * @var bool|null $isStudent
 */
$isStudent = $isStudent ?? false;
$layout = $isStudent ? 'layouts/student' : 'layouts/parent';

$this->layout($layout, [
    'title' => 'Invoice ' . $invoice->invoiceNumber . ' — Claret School Fees',
]);
?>

<div class="space-y-6 max-w-4xl mx-auto">
    <!-- Top Back Bar -->
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            <a href="<?= $isStudent ? '/student/fees' : '/parent/fees' ?>" class="p-2 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-600 transition">
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
            </a>
            <div>
                <div class="flex items-center gap-2">
                    <span class="font-mono text-xs font-bold text-slate-400"><?= htmlspecialchars($invoice->invoiceNumber, ENT_QUOTES, 'UTF-8') ?></span>
                    <?php if ($invoice->isPaid()): ?>
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-emerald-50 text-emerald-700 border border-emerald-200">
                            Paid in Full
                        </span>
                    <?php elseif ($invoice->isPartiallyPaid()): ?>
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-amber-50 text-amber-700 border border-amber-200">
                            Partially Paid
                        </span>
                    <?php else: ?>
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-rose-50 text-rose-700 border border-rose-200">
                            Payment Due
                        </span>
                    <?php endif; ?>
                </div>
                <h1 class="text-2xl font-black tracking-tight text-slate-900 mt-0.5">
                    <?= htmlspecialchars($invoice->studentName ?? 'Ward Invoice', ENT_QUOTES, 'UTF-8') ?>
                </h1>
            </div>
        </div>
        <div class="text-right">
            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Outstanding Balance</span>
            <div class="text-2xl font-black font-mono mt-0.5 <?= $invoice->balanceDue > 0 ? 'text-rose-700' : 'text-emerald-700' ?>">
                ₦<?= number_format($invoice->balanceDue, 2) ?>
            </div>
        </div>
    </div>

    <!-- Ward & Term Summary Card -->
    <div class="bg-white rounded-3xl border border-slate-200 p-6 shadow-xs grid sm:grid-cols-3 gap-4 text-xs">
        <div>
            <span class="text-[10px] uppercase font-bold text-slate-400 block mb-1">Student Ward</span>
            <div class="text-sm font-bold text-slate-900"><?= htmlspecialchars($invoice->studentName ?? '—', ENT_QUOTES, 'UTF-8') ?></div>
            <div class="font-mono text-slate-500 mt-0.5">Adm #: <?= htmlspecialchars($invoice->admissionNumber ?? '—', ENT_QUOTES, 'UTF-8') ?></div>
            <div class="text-brand-600 font-medium mt-0.5"><?= htmlspecialchars($invoice->className ?? '—', ENT_QUOTES, 'UTF-8') ?></div>
        </div>
        <div>
            <span class="text-[10px] uppercase font-bold text-slate-400 block mb-1">Academic Session &amp; Term</span>
            <div class="text-sm font-bold text-slate-900"><?= htmlspecialchars($invoice->termName ?? '—', ENT_QUOTES, 'UTF-8') ?></div>
            <div class="text-slate-500 mt-0.5"><?= htmlspecialchars($invoice->sessionName ?? '—', ENT_QUOTES, 'UTF-8') ?></div>
            <?php if ($invoice->dueDate): ?>
                <div class="text-rose-600 font-semibold mt-0.5">Due Date: <?= date('M d, Y', strtotime($invoice->dueDate)) ?></div>
            <?php endif; ?>
        </div>
        <div class="bg-slate-50 rounded-2xl p-3 border border-slate-100 flex flex-col justify-between">
            <span class="text-[10px] uppercase font-bold text-slate-400 block">Payment Progress</span>
            <div class="w-full bg-slate-200 rounded-full h-2.5 my-1.5 overflow-hidden">
                <?php 
                $pct = $invoice->totalAmount > 0 ? min(100, round(($invoice->amountPaid / $invoice->totalAmount) * 100)) : 0;
                ?>
                <div class="bg-emerald-500 h-2.5 rounded-full" style="width: <?= $pct ?>%"></div>
            </div>
            <div class="flex items-center justify-between text-[11px] text-slate-500">
                <span>Paid: <strong class="text-slate-800 font-mono">₦<?= number_format($invoice->amountPaid, 2) ?></strong></span>
                <span><?= $pct ?>% Complete</span>
            </div>
        </div>
    </div>

    <!-- Payment Action Box (For Parent, if balance remains) -->
    <?php if (!$isStudent && !$invoice->isPaid()): ?>
        <div class="bg-gradient-to-br from-slate-950 via-[#2A0E17] to-slate-900 text-white rounded-3xl p-6 sm:p-8 shadow-xl relative overflow-hidden">
            <div class="relative z-10 space-y-5">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-white/10 pb-4">
                    <div>
                        <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-emerald-500/20 border border-emerald-400/30 text-emerald-300 text-[11px] font-bold">
                            <i data-lucide="shield-check" class="w-3.5 h-3.5"></i>
                            Paystack 256-Bit Encrypted Gateway
                        </div>
                        <h2 class="text-xl font-black mt-2">Complete School Fees Payment</h2>
                        <p class="text-xs text-slate-300 mt-0.5">Pay in full or settle a partial installment via Debit Card, Bank Transfer, or USSD.</p>
                    </div>
                    <div class="text-right">
                        <span class="text-[10px] uppercase tracking-wider text-slate-400 block">Amount Due</span>
                        <div class="text-2xl font-black font-mono text-emerald-400">
                            ₦<?= number_format($invoice->balanceDue, 2) ?>
                        </div>
                    </div>
                </div>

                <form method="POST" action="/parent/fees/invoices/<?= $invoice->id ?>/checkout" class="space-y-4">
                    <?= \App\Core\Csrf::field() ?>

                    <div class="grid sm:grid-cols-2 gap-3 text-xs">
                        <label class="relative flex items-center gap-3 p-4 rounded-2xl border border-white/15 bg-white/5 hover:bg-white/10 cursor-pointer transition">
                            <input type="radio" name="payment_mode" value="full" checked onchange="toggleCustomAmount(false)" class="text-emerald-500 focus:ring-emerald-400">
                            <div>
                                <span class="font-bold block text-white">Pay Full Outstanding Balance</span>
                                <span class="text-emerald-400 font-mono font-bold mt-0.5 block">₦<?= number_format($invoice->balanceDue, 2) ?></span>
                            </div>
                        </label>

                        <label class="relative flex items-center gap-3 p-4 rounded-2xl border border-white/15 bg-white/5 hover:bg-white/10 cursor-pointer transition">
                            <input type="radio" name="payment_mode" value="partial" onchange="toggleCustomAmount(true)" class="text-emerald-500 focus:ring-emerald-400">
                            <div>
                                <span class="font-bold block text-white">Pay Custom Installment</span>
                                <span class="text-slate-400 text-[11px] mt-0.5 block">Choose partial payment amount</span>
                            </div>
                        </label>
                    </div>

                    <div id="custom-amount-container" class="hidden">
                        <label class="block text-xs font-bold text-slate-300 mb-1">Enter Installment Amount (₦)</label>
                        <input type="number" step="0.01" min="100" max="<?= $invoice->balanceDue ?>" name="custom_amount" placeholder="e.g. 50000.00" class="w-full sm:max-w-xs px-4 py-2.5 rounded-xl border border-white/20 bg-white/10 text-white font-mono text-sm focus:outline-none focus:ring-2 focus:ring-emerald-400">
                        <p class="text-[11px] text-slate-400 mt-1">Minimum installment is ₦100.00 up to max balance (₦<?= number_format($invoice->balanceDue, 2) ?>).</p>
                    </div>

                    <div class="pt-2 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div class="flex items-center gap-2 text-[11px] text-slate-400">
                            <i data-lucide="lock" class="w-4 h-4 text-emerald-400"></i>
                            Instant verification &bull; Stamped receipt issued immediately upon payment
                        </div>
                        <button type="submit" class="px-6 py-3 rounded-xl bg-emerald-500 hover:bg-emerald-600 text-slate-950 font-black text-xs transition shadow-lg flex items-center justify-center gap-2">
                            <span>Proceed to Paystack Checkout</span>
                            <i data-lucide="arrow-right" class="w-4 h-4"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    <?php elseif ($invoice->isPaid()): ?>
        <div class="bg-emerald-50 border border-emerald-200 rounded-3xl p-6 text-center space-y-2">
            <div class="size-12 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center mx-auto">
                <i data-lucide="check" class="w-6 h-6"></i>
            </div>
            <h3 class="text-base font-black text-emerald-900">Invoice Fully Paid &amp; Settled</h3>
            <p class="text-xs text-emerald-700 max-w-md mx-auto">All tuition and levy components for this academic term have been completely discharged.</p>
        </div>
    <?php endif; ?>

    <!-- Itemized Breakdown Table -->
    <div class="bg-white rounded-3xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="p-5 border-b border-slate-100 flex items-center justify-between">
            <h3 class="text-sm font-black text-slate-900">Itemized Fee Components</h3>
            <span class="text-xs text-slate-400 font-mono"><?= count($invoice->items) ?> component(s)</span>
        </div>
        <table class="w-full text-left text-xs">
            <thead class="bg-slate-50 text-slate-500 font-bold uppercase tracking-wider text-[10px]">
                <tr>
                    <th class="py-3 px-5">#</th>
                    <th class="py-3 px-5">Category</th>
                    <th class="py-3 px-5">Component Name</th>
                    <th class="py-3 px-5 text-right">Amount</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php foreach ($invoice->items as $idx => $it): ?>
                    <tr>
                        <td class="py-3 px-5 font-mono text-slate-400"><?= $idx + 1 ?></td>
                        <td class="py-3 px-5">
                            <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-slate-100 text-slate-700">
                                <?= htmlspecialchars($it->categoryName ?? 'Levy', ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        </td>
                        <td class="py-3 px-5 font-bold text-slate-800">
                            <?= htmlspecialchars($it->name, ENT_QUOTES, 'UTF-8') ?>
                        </td>
                        <td class="py-3 px-5 text-right font-mono font-bold text-slate-900">
                            ₦<?= number_format($it->amount, 2) ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot class="bg-slate-50/80 font-bold border-t border-slate-100 text-xs">
                <tr>
                    <td colspan="3" class="py-2.5 px-5 text-right text-slate-600">Subtotal:</td>
                    <td class="py-2.5 px-5 text-right font-mono text-slate-900">₦<?= number_format($invoice->subtotal, 2) ?></td>
                </tr>
                <?php if ($invoice->discountAmount > 0): ?>
                    <tr>
                        <td colspan="3" class="py-2 px-5 text-right text-emerald-600">Discount / Waiver:</td>
                        <td class="py-2 px-5 text-right font-mono text-emerald-600">-₦<?= number_format($invoice->discountAmount, 2) ?></td>
                    </tr>
                <?php endif; ?>
                <tr class="text-sm font-black border-t border-slate-200">
                    <td colspan="3" class="py-3 px-5 text-right text-slate-900">Total Billed:</td>
                    <td class="py-3 px-5 text-right font-mono text-brand-700">₦<?= number_format($invoice->totalAmount, 2) ?></td>
                </tr>
                <tr class="text-xs font-bold text-emerald-700">
                    <td colspan="3" class="py-2 px-5 text-right">Amount Paid:</td>
                    <td class="py-2 px-5 text-right font-mono text-emerald-700">-₦<?= number_format($invoice->amountPaid, 2) ?></td>
                </tr>
                <tr class="text-sm font-black border-t border-slate-300 bg-slate-100">
                    <td colspan="3" class="py-3 px-5 text-right text-slate-900">Balance Due:</td>
                    <td class="py-3 px-5 text-right font-mono <?= $invoice->balanceDue > 0 ? 'text-rose-700' : 'text-emerald-700' ?>">
                        ₦<?= number_format($invoice->balanceDue, 2) ?>
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>

    <!-- Payment Receipts Table -->
    <?php if (!empty($invoice->payments)): ?>
        <div class="bg-white rounded-3xl border border-slate-200 shadow-xs overflow-hidden">
            <div class="p-5 border-b border-slate-100 flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-black text-slate-900">Official Payment Receipts</h3>
                    <p class="text-xs text-slate-500">Payments recorded and receipted on this invoice.</p>
                </div>
            </div>
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 text-slate-500 font-bold uppercase tracking-wider text-[10px]">
                    <tr>
                        <th class="py-3 px-5">Date</th>
                        <th class="py-3 px-5">Reference</th>
                        <th class="py-3 px-5">Payment Method</th>
                        <th class="py-3 px-5 text-right">Amount Paid</th>
                        <th class="py-3 px-5 text-right">Receipt</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach ($invoice->payments as $p): ?>
                        <tr>
                            <td class="py-3 px-5 text-slate-600 font-mono text-[11px]">
                                <?= date('M d, Y', strtotime($p->paidAt ?? $p->createdAt)) ?>
                            </td>
                            <td class="py-3 px-5 font-mono font-bold text-slate-800">
                                <?= htmlspecialchars($p->reference, ENT_QUOTES, 'UTF-8') ?>
                            </td>
                            <td class="py-3 px-5">
                                <span class="px-2 py-0.5 rounded-md text-[10px] font-bold uppercase bg-slate-100 text-slate-700">
                                    <?= htmlspecialchars($p->channel, ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            </td>
                            <td class="py-3 px-5 text-right font-mono font-bold text-emerald-700">
                                ₦<?= number_format($p->amount, 2) ?>
                            </td>
                            <td class="py-3 px-5 text-right">
                                <a href="/payments/receipt/<?= $p->id ?>" target="_blank" class="text-brand-600 hover:text-brand-800 font-bold flex items-center justify-end gap-1">
                                    <i data-lucide="printer" class="w-3.5 h-3.5"></i> Print Receipt
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<script>
function toggleCustomAmount(isCustom) {
    const el = document.getElementById('custom-amount-container');
    if (isCustom) {
        el.classList.remove('hidden');
        el.querySelector('input').focus();
    } else {
        el.classList.add('hidden');
    }
}
</script>
