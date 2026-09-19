<?php
/**
 * Admin Fee Invoice Detail & Bursary Payment Inspector
 * 
 * @var \App\Models\FeeInvoice $invoice
 */
$this->layout('layouts/admin', [
    'title' => 'Invoice ' . $invoice->invoiceNumber . ' — Claret LMS',
]);
?>

<div class="space-y-6 max-w-5xl mx-auto">
    <!-- Top Back Bar & Action Buttons -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            <a href="/admin/fees/invoices" class="p-2 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-600 transition">
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
            </a>
            <div>
                <div class="flex items-center gap-2">
                    <span class="font-mono text-xs font-bold text-slate-400"><?= htmlspecialchars($invoice->invoiceNumber, ENT_QUOTES, 'UTF-8') ?></span>
                    <?php if ($invoice->isPaid()): ?>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-emerald-50 text-emerald-700 border border-emerald-200">
                            Fully Paid
                        </span>
                    <?php elseif ($invoice->isPartiallyPaid()): ?>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-amber-50 text-amber-700 border border-amber-200">
                            Partially Paid
                        </span>
                    <?php else: ?>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-rose-50 text-rose-700 border border-rose-200">
                            Unpaid
                        </span>
                    <?php endif; ?>
                </div>
                <h1 class="text-2xl font-black tracking-tight text-slate-900 mt-0.5">
                    <?= htmlspecialchars($invoice->studentName ?? 'Student Invoice', ENT_QUOTES, 'UTF-8') ?>
                </h1>
            </div>
        </div>
        <div class="flex items-center gap-3">
            <a href="/admin/fees/invoices/<?= $invoice->id ?>/print" target="_blank" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-bold transition shadow-xs">
                <i data-lucide="printer" class="w-4 h-4 text-slate-500"></i>
                Print Invoice Docket
            </a>
            <?php if (!$invoice->isPaid()): ?>
                <button onclick="document.getElementById('record-payment-modal').classList.remove('hidden')" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold transition shadow-sm hover:shadow">
                    <i data-lucide="banknote" class="w-4 h-4"></i>
                    Record Bursary Payment
                </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Overview Details Card -->
    <div class="bg-white rounded-3xl border border-slate-200 p-6 shadow-xs">
        <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-6">
            <div>
                <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Student Details</span>
                <div class="text-sm font-bold text-slate-900 mt-1"><?= htmlspecialchars($invoice->studentName ?? '—', ENT_QUOTES, 'UTF-8') ?></div>
                <div class="text-xs font-mono text-slate-500 mt-0.5">Adm: <?= htmlspecialchars($invoice->admissionNumber ?? '—', ENT_QUOTES, 'UTF-8') ?></div>
                <div class="text-xs text-brand-600 font-medium mt-0.5"><?= htmlspecialchars($invoice->className ?? '—', ENT_QUOTES, 'UTF-8') ?></div>
            </div>

            <div>
                <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Academic Term</span>
                <div class="text-sm font-bold text-slate-900 mt-1"><?= htmlspecialchars($invoice->termName ?? '—', ENT_QUOTES, 'UTF-8') ?></div>
                <div class="text-xs text-slate-500 mt-0.5"><?= htmlspecialchars($invoice->sessionName ?? '—', ENT_QUOTES, 'UTF-8') ?></div>
                <?php if ($invoice->dueDate): ?>
                    <div class="text-xs text-slate-400 mt-0.5">Due: <?= date('M d, Y', strtotime($invoice->dueDate)) ?></div>
                <?php endif; ?>
            </div>

            <div>
                <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Linked Guardian</span>
                <div class="text-sm font-bold text-slate-900 mt-1"><?= htmlspecialchars($invoice->parentName ?? 'Not Linked', ENT_QUOTES, 'UTF-8') ?></div>
                <div class="text-xs text-slate-500 mt-0.5"><?= htmlspecialchars($invoice->parentEmail ?? '', ENT_QUOTES, 'UTF-8') ?></div>
                <div class="text-xs text-slate-500 mt-0.5"><?= htmlspecialchars($invoice->parentPhone ?? '', ENT_QUOTES, 'UTF-8') ?></div>
            </div>

            <div class="bg-slate-50 rounded-2xl p-4 border border-slate-100 flex flex-col justify-between">
                <div>
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Balance Due</span>
                    <div class="text-2xl font-black font-mono mt-1 <?= $invoice->balanceDue > 0 ? 'text-rose-700' : 'text-emerald-700' ?>">
                        ₦<?= number_format($invoice->balanceDue, 2) ?>
                    </div>
                </div>
                <div class="text-[11px] text-slate-500 mt-2">
                    Total: ₦<?= number_format($invoice->totalAmount, 2) ?> &bull; Paid: ₦<?= number_format($invoice->amountPaid, 2) ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Line Items Table -->
    <div class="bg-white rounded-3xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="p-5 border-b border-slate-100 flex items-center justify-between">
            <h3 class="text-sm font-black text-slate-900">Fee Component Breakdown</h3>
            <span class="text-xs font-mono text-slate-400"><?= count($invoice->items) ?> item(s)</span>
        </div>
        <table class="w-full text-left text-xs">
            <thead class="bg-slate-50 text-slate-500 font-bold uppercase tracking-wider text-[10px]">
                <tr>
                    <th class="py-3 px-5">#</th>
                    <th class="py-3 px-5">Fee Category</th>
                    <th class="py-3 px-5">Component Description</th>
                    <th class="py-3 px-5 text-right">Amount (₦)</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php foreach ($invoice->items as $idx => $it): ?>
                    <tr>
                        <td class="py-3.5 px-5 font-mono text-slate-400"><?= $idx + 1 ?></td>
                        <td class="py-3.5 px-5">
                            <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-slate-100 text-slate-700">
                                <?= htmlspecialchars($it->categoryName ?? 'Levy', ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        </td>
                        <td class="py-3.5 px-5 font-bold text-slate-800">
                            <?= htmlspecialchars($it->name, ENT_QUOTES, 'UTF-8') ?>
                        </td>
                        <td class="py-3.5 px-5 text-right font-mono font-bold text-slate-900">
                            ₦<?= number_format($it->amount, 2) ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot class="bg-slate-50/80 font-bold border-t border-slate-100 text-xs">
                <tr>
                    <td colspan="3" class="py-3 px-5 text-right text-slate-600">Subtotal:</td>
                    <td class="py-3 px-5 text-right font-mono text-slate-900">₦<?= number_format($invoice->subtotal, 2) ?></td>
                </tr>
                <?php if ($invoice->discountAmount > 0): ?>
                    <tr>
                        <td colspan="3" class="py-2 px-5 text-right text-emerald-600">Institutional Discount / Waiver:</td>
                        <td class="py-2 px-5 text-right font-mono text-emerald-600">-₦<?= number_format($invoice->discountAmount, 2) ?></td>
                    </tr>
                <?php endif; ?>
                <tr class="text-sm font-black border-t border-slate-200">
                    <td colspan="3" class="py-3.5 px-5 text-right text-slate-900">Total Billed:</td>
                    <td class="py-3.5 px-5 text-right font-mono text-brand-700">₦<?= number_format($invoice->totalAmount, 2) ?></td>
                </tr>
            </tfoot>
        </table>
    </div>

    <!-- Payments Ledger Table -->
    <div class="bg-white rounded-3xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="p-5 border-b border-slate-100 flex items-center justify-between">
            <div>
                <h3 class="text-sm font-black text-slate-900">Transaction &amp; Payment History</h3>
                <p class="text-xs text-slate-500">Official payments recorded against this invoice docket.</p>
            </div>
            <span class="text-xs font-mono font-bold text-emerald-700">
                Total Credited: ₦<?= number_format($invoice->amountPaid, 2) ?>
            </span>
        </div>

        <?php if (empty($invoice->payments)): ?>
            <div class="p-8 text-center text-xs text-slate-500">
                <i data-lucide="receipt" class="w-8 h-8 mx-auto text-slate-300 mb-2"></i>
                No payments have been recorded against this invoice docket yet.
            </div>
        <?php else: ?>
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 text-slate-500 font-bold uppercase tracking-wider text-[10px]">
                    <tr>
                        <th class="py-3 px-5">Date</th>
                        <th class="py-3 px-5">Reference</th>
                        <th class="py-3 px-5">Channel</th>
                        <th class="py-3 px-5">Gateway / Bank Ref</th>
                        <th class="py-3 px-5 text-right">Amount</th>
                        <th class="py-3 px-5 text-right">Receipt</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach ($invoice->payments as $p): ?>
                        <tr class="hover:bg-slate-50/50">
                            <td class="py-3 px-5 text-slate-600 font-mono text-[11px]">
                                <?= date('M d, Y H:i', strtotime($p->paidAt ?? $p->createdAt)) ?>
                            </td>
                            <td class="py-3 px-5 font-mono font-bold text-slate-800">
                                <?= htmlspecialchars($p->reference, ENT_QUOTES, 'UTF-8') ?>
                            </td>
                            <td class="py-3 px-5">
                                <span class="px-2 py-0.5 rounded-md text-[10px] font-bold uppercase bg-slate-100 text-slate-700">
                                    <?= htmlspecialchars($p->channel, ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            </td>
                            <td class="py-3 px-5 font-mono text-[11px] text-slate-500">
                                <?= htmlspecialchars($p->gatewayReference ?? '—', ENT_QUOTES, 'UTF-8') ?>
                            </td>
                            <td class="py-3 px-5 text-right font-mono font-bold text-emerald-700">
                                ₦<?= number_format($p->amount, 2) ?>
                            </td>
                            <td class="py-3 px-5 text-right">
                                <a href="/payments/receipt/<?= $p->id ?>" target="_blank" class="text-brand-600 hover:text-brand-800 font-bold flex items-center justify-end gap-1">
                                    <i data-lucide="printer" class="w-3.5 h-3.5"></i> Receipt
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<!-- Modal: Record Bursary Payment -->
<div id="record-payment-modal" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 hidden">
    <div class="bg-white rounded-3xl border border-slate-200 max-w-md w-full p-6 shadow-2xl space-y-5 animate-in fade-in zoom-in-95 duration-150">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <div>
                <h3 class="text-base font-black text-slate-900">Record Bursary Payment</h3>
                <p class="text-xs text-slate-500">Log bank transfer, cash, or POS payment from guardian.</p>
            </div>
            <button onclick="document.getElementById('record-payment-modal').classList.add('hidden')" class="p-1 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <form method="POST" action="/admin/fees/invoices/<?= $invoice->id ?>/record-payment" class="space-y-4 text-xs">
            <?= \App\Core\Csrf::field() ?>

            <div>
                <label class="block font-bold text-slate-700 mb-1">Amount Paid (₦) <span class="text-rose-500">*</span></label>
                <input type="number" step="0.01" min="100" max="<?= $invoice->balanceDue ?>" name="amount" value="<?= $invoice->balanceDue ?>" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm font-mono font-bold text-slate-900 focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500">
                <p class="text-[11px] text-slate-400 mt-1">Outstanding Balance: <span class="font-bold text-rose-600 font-mono">₦<?= number_format($invoice->balanceDue, 2) ?></span></p>
            </div>

            <div>
                <label class="block font-bold text-slate-700 mb-1">Payment Method / Channel <span class="text-rose-500">*</span></label>
                <select name="channel" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs font-medium text-slate-700 bg-white focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500">
                    <option value="bank_transfer">Direct Bank Transfer / Wire</option>
                    <option value="pos">School POS Terminal</option>
                    <option value="cash">Bursary Cash Desk</option>
                </select>
            </div>

            <div>
                <label class="block font-bold text-slate-700 mb-1">Bank Reference / Teller / POS Auth Code</label>
                <input type="text" name="reference_number" placeholder="e.g. TXN-998822001 or Access Bank Teller #5421" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs text-slate-800 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500">
            </div>

            <div>
                <label class="block font-bold text-slate-700 mb-1">Bursary Audit Notes</label>
                <textarea name="notes" rows="2" placeholder="Optional comments regarding this transaction..." class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs text-slate-800 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500"></textarea>
            </div>

            <div class="flex items-center justify-end gap-2 border-t border-slate-100 pt-4">
                <button type="button" onclick="document.getElementById('record-payment-modal').classList.add('hidden')" class="px-4 py-2 rounded-xl border border-slate-200 text-slate-600 font-bold hover:bg-slate-50 transition">
                    Cancel
                </button>
                <button type="submit" class="px-5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold transition shadow-xs">
                    Confirm &amp; Issue Receipt
                </button>
            </div>
        </form>
    </div>
</div>
