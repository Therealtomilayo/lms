<?php
/**
 * Parent / Student Fee Invoice Detail & Itemized Payment Screen
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

$unpaidItems = array_filter($invoice->items, fn($it) => !$it->isPaid);
$hasUnpaid = !empty($unpaidItems);
?>

<div class="space-y-6 max-w-4xl mx-auto pb-12">
    <!-- Breadcrumb & Top Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            <a href="<?= $isStudent ? '/student/fees' : '/parent/fees' ?>" class="p-2.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-600 transition shadow-2xs">
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
            </a>
            <div>
                <div class="flex items-center gap-2">
                    <span class="font-mono text-xs font-bold text-slate-500"><?= htmlspecialchars($invoice->invoiceNumber, ENT_QUOTES, 'UTF-8') ?></span>
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
        <div class="text-left sm:text-right bg-white sm:bg-transparent p-3 sm:p-0 rounded-2xl border sm:border-0 border-slate-200">
            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Outstanding Balance</span>
            <div class="text-2xl font-black font-mono mt-0.5 <?= $invoice->balanceDue > 0 ? 'text-rose-700' : 'text-emerald-700' ?>">
                ₦<?= number_format($invoice->balanceDue, 2) ?>
            </div>
        </div>
    </div>

    <!-- Ward & Term Academic Summary Card -->
    <div class="bg-white rounded-3xl border border-slate-200 p-6 shadow-xs grid sm:grid-cols-3 gap-6 text-xs">
        <div>
            <span class="text-[10px] uppercase font-bold text-slate-400 tracking-wider block mb-1">Student Ward</span>
            <div class="text-sm font-bold text-slate-900"><?= htmlspecialchars($invoice->studentName ?? '—', ENT_QUOTES, 'UTF-8') ?></div>
            <div class="font-mono text-slate-500 mt-0.5">Adm #: <?= htmlspecialchars($invoice->admissionNumber ?? '—', ENT_QUOTES, 'UTF-8') ?></div>
            <div class="text-brand-700 font-semibold mt-0.5"><?= htmlspecialchars($invoice->className ?? '—', ENT_QUOTES, 'UTF-8') ?></div>
        </div>
        <div>
            <span class="text-[10px] uppercase font-bold text-slate-400 tracking-wider block mb-1">Academic Session &amp; Term</span>
            <div class="text-sm font-bold text-slate-900"><?= htmlspecialchars($invoice->termName ?? '—', ENT_QUOTES, 'UTF-8') ?></div>
            <div class="text-slate-500 mt-0.5"><?= htmlspecialchars($invoice->sessionName ?? '—', ENT_QUOTES, 'UTF-8') ?></div>
            <?php if ($invoice->dueDate): ?>
                <div class="text-rose-600 font-bold mt-0.5 flex items-center gap-1">
                    <i data-lucide="calendar" class="w-3.5 h-3.5"></i>
                    Due: <?= date('M d, Y', strtotime($invoice->dueDate)) ?>
                </div>
            <?php endif; ?>
        </div>
        <div class="bg-slate-50 rounded-2xl p-3.5 border border-slate-100 flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-[10px] uppercase font-bold text-slate-500">Settlement Status</span>
                <?php 
                $pct = $invoice->totalAmount > 0 ? min(100, round(($invoice->amountPaid / $invoice->totalAmount) * 100)) : 0;
                ?>
                <span class="text-[11px] font-bold text-slate-700"><?= $pct ?>%</span>
            </div>
            <div class="w-full bg-slate-200 rounded-full h-2.5 my-2 overflow-hidden">
                <div class="bg-brand-600 h-2.5 rounded-full transition-all duration-300" style="width: <?= $pct ?>%"></div>
            </div>
            <div class="flex items-center justify-between text-[11px] text-slate-500">
                <span>Paid: <strong class="text-slate-800 font-mono">₦<?= number_format($invoice->amountPaid, 2) ?></strong></span>
                <span>Total: <strong class="text-slate-700 font-mono">₦<?= number_format($invoice->totalAmount, 2) ?></strong></span>
            </div>
        </div>
    </div>

    <!-- Settlement Banner if fully paid -->
    <?php if ($invoice->isPaid()): ?>
        <div class="bg-emerald-50 border border-emerald-200 rounded-3xl p-6 text-center space-y-2">
            <div class="size-12 rounded-2xl bg-emerald-100 text-emerald-700 flex items-center justify-center mx-auto shadow-2xs">
                <i data-lucide="check-circle-2" class="w-6 h-6"></i>
            </div>
            <h3 class="text-base font-black text-emerald-900">Invoice Fully Settled</h3>
            <p class="text-xs text-emerald-700 max-w-md mx-auto">All mandatory tuition and levy components for this term have been cleared. Academic result viewing is completely unlocked.</p>
        </div>
    <?php endif; ?>

    <!-- Itemized Fee Component Checklist & Form -->
    <form id="fee-checkout-form" method="POST" action="/parent/fees/invoices/<?= $invoice->id ?>/checkout" class="space-y-6">
        <?= \App\Core\Csrf::field() ?>

        <div class="bg-white rounded-3xl border border-slate-200 shadow-xs overflow-hidden">
            <div class="p-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                <div>
                    <h3 class="text-sm font-black text-slate-900">Itemized Fee Components</h3>
                    <p class="text-xs text-slate-500">Select the specific fee components you wish to pay now. Total increments automatically.</p>
                </div>
                <?php if (!$isStudent && $hasUnpaid): ?>
                    <div class="flex items-center gap-3 text-xs">
                        <button type="button" onclick="selectAllItems(true)" class="font-bold text-brand-700 hover:text-brand-800 transition">
                            Select All
                        </button>
                        <span class="text-slate-300">|</span>
                        <button type="button" onclick="selectAllItems(false)" class="font-bold text-slate-500 hover:text-slate-700 transition">
                            Clear Selection
                        </button>
                    </div>
                <?php endif; ?>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 text-slate-500 font-bold uppercase tracking-wider text-[10px]">
                        <tr>
                            <?php if (!$isStudent && $hasUnpaid): ?>
                                <th class="py-3 px-4 w-10 text-center">
                                    <input type="checkbox" id="header-select-all" checked onchange="toggleAllCheckboxes(this)" class="rounded border-slate-300 text-brand-600 focus:ring-brand-500 cursor-pointer">
                                </th>
                            <?php else: ?>
                                <th class="py-3 px-4 w-10 text-center">#</th>
                            <?php endif; ?>
                            <th class="py-3 px-4">Component</th>
                            <th class="py-3 px-4">Category</th>
                            <th class="py-3 px-4">Clearance Rule</th>
                            <th class="py-3 px-4 text-center">Status</th>
                            <th class="py-3 px-5 text-right">Amount</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php foreach ($invoice->items as $idx => $it): ?>
                            <tr class="hover:bg-slate-50/70 transition <?= $it->isPaid ? 'bg-slate-50/40 text-slate-400' : '' ?>">
                                <?php if (!$isStudent && $hasUnpaid): ?>
                                    <td class="py-3.5 px-4 text-center">
                                        <?php if ($it->isPaid): ?>
                                            <i data-lucide="check" class="w-4 h-4 text-emerald-600 mx-auto"></i>
                                        <?php else: ?>
                                            <input type="checkbox" 
                                                   name="selected_items[]" 
                                                   value="<?= $it->id ?>" 
                                                   data-amount="<?= $it->amount ?>"
                                                   data-required="<?= $it->isRequiredForResult ? '1' : '0' ?>"
                                                   checked
                                                   onchange="updateCalculatedTotal()"
                                                   class="item-checkbox rounded border-slate-300 text-brand-600 focus:ring-brand-500 cursor-pointer">
                                        <?php endif; ?>
                                    </td>
                                <?php else: ?>
                                    <td class="py-3.5 px-4 font-mono text-slate-400 text-center"><?= $idx + 1 ?></td>
                                <?php endif; ?>

                                <td class="py-3.5 px-4">
                                    <div class="font-bold text-slate-900 <?= $it->isPaid ? 'line-through text-slate-400' : '' ?>">
                                        <?= htmlspecialchars($it->name, ENT_QUOTES, 'UTF-8') ?>
                                    </div>
                                    <?php if (!$it->isCompulsory): ?>
                                        <span class="text-[10px] text-slate-400 font-medium">Optional component</span>
                                    <?php endif; ?>
                                </td>

                                <td class="py-3.5 px-4">
                                    <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-slate-100 text-slate-700">
                                        <?= htmlspecialchars($it->categoryName ?? 'Levy', ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                </td>

                                <td class="py-3.5 px-4">
                                    <?php if ($it->isRequiredForResult): ?>
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200">
                                            <i data-lucide="lock" class="w-3 h-3 text-rose-500"></i>
                                            Required for Results
                                        </span>
                                    <?php else: ?>
                                        <span class="text-[11px] text-slate-400">Non-academic</span>
                                    <?php endif; ?>
                                </td>

                                <td class="py-3.5 px-4 text-center">
                                    <?php if ($it->isPaid): ?>
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            <i data-lucide="check" class="w-3 h-3"></i> Settled
                                        </span>
                                    <?php else: ?>
                                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-600">
                                            Unpaid
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <td class="py-3.5 px-5 text-right font-mono font-bold text-slate-900 <?= $it->isPaid ? 'text-slate-400' : '' ?>">
                                    ₦<?= number_format($it->amount, 2) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot class="bg-slate-50 font-bold border-t border-slate-200 text-xs">
                        <tr>
                            <td colspan="<?= (!$isStudent && $hasUnpaid) ? 5 : 4 ?>" class="py-2.5 px-5 text-right text-slate-600">Subtotal:</td>
                            <td class="py-2.5 px-5 text-right font-mono text-slate-900">₦<?= number_format($invoice->subtotal, 2) ?></td>
                        </tr>
                        <?php if ($invoice->discountAmount > 0): ?>
                            <tr>
                                <td colspan="<?= (!$isStudent && $hasUnpaid) ? 5 : 4 ?>" class="py-2 px-5 text-right text-emerald-600">Scholarship / Waiver:</td>
                                <td class="py-2 px-5 text-right font-mono text-emerald-600">-₦<?= number_format($invoice->discountAmount, 2) ?></td>
                            </tr>
                        <?php endif; ?>
                        <tr class="text-sm font-black border-t border-slate-200">
                            <td colspan="<?= (!$isStudent && $hasUnpaid) ? 5 : 4 ?>" class="py-3 px-5 text-right text-slate-900">Total Billed:</td>
                            <td class="py-3 px-5 text-right font-mono text-brand-700">₦<?= number_format($invoice->totalAmount, 2) ?></td>
                        </tr>
                        <tr class="text-xs font-bold text-emerald-700">
                            <td colspan="<?= (!$isStudent && $hasUnpaid) ? 5 : 4 ?>" class="py-2 px-5 text-right">Settled Amount:</td>
                            <td class="py-2 px-5 text-right font-mono text-emerald-700">-₦<?= number_format($invoice->amountPaid, 2) ?></td>
                        </tr>
                        <tr class="text-sm font-black border-t border-slate-200 bg-slate-100/80">
                            <td colspan="<?= (!$isStudent && $hasUnpaid) ? 5 : 4 ?>" class="py-3 px-5 text-right text-slate-900">Outstanding Balance:</td>
                            <td class="py-3 px-5 text-right font-mono <?= $invoice->balanceDue > 0 ? 'text-rose-700' : 'text-emerald-700' ?>">
                                ₦<?= number_format($invoice->balanceDue, 2) ?>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <!-- Institutional Payment Summary Box (Clean Institutional Design) -->
        <?php if (!$isStudent && $hasUnpaid): ?>
            <div class="bg-white rounded-3xl border border-slate-200 p-6 sm:p-7 shadow-xs space-y-6">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-100 pb-5">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="size-2 rounded-full bg-emerald-500 animate-pulse"></span>
                            <span class="text-xs font-bold text-slate-600">Online Bursary Payment</span>
                        </div>
                        <h3 class="text-lg font-black text-slate-900 mt-1">Payment Selection Summary</h3>
                        <p class="text-xs text-slate-500 mt-0.5">Confirm the fee components to discharge via card, bank transfer, or USSD.</p>
                    </div>
                    <div class="text-left sm:text-right bg-slate-50 sm:bg-transparent p-4 sm:p-0 rounded-2xl border sm:border-0 border-slate-200">
                        <span class="text-[10px] uppercase font-bold text-slate-400 tracking-wider block">Total Payable Now</span>
                        <div id="display-selected-total" class="text-2xl sm:text-3xl font-black font-mono text-brand-700 mt-0.5">
                            ₦<?= number_format($invoice->balanceDue, 2) ?>
                        </div>
                        <span id="display-selected-count" class="text-[11px] text-slate-500 font-medium block mt-0.5">
                            <?= count($unpaidItems) ?> component(s) selected
                        </span>
                    </div>
                </div>

                <!-- Academic Result Clearance Status Indicator -->
                <div id="clearance-status-banner" class="p-4 rounded-2xl border transition-all duration-200 text-xs flex items-start gap-3 bg-emerald-50/70 border-emerald-200 text-emerald-800">
                    <i id="clearance-status-icon" data-lucide="unlock" class="w-5 h-5 text-emerald-600 flex-shrink-0 mt-0.5"></i>
                    <div>
                        <div id="clearance-status-title" class="font-bold text-emerald-950">Academic Result Clearance: Cleared upon payment</div>
                        <div id="clearance-status-desc" class="text-[11px] text-emerald-700 mt-0.5">
                            All fee components required for viewing your ward's <?= htmlspecialchars($invoice->termName ?? 'term') ?> report card are included in this payment.
                        </div>
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pt-1">
                    <div class="flex items-center gap-2 text-xs text-slate-500">
                        <i data-lucide="shield-check" class="w-4 h-4 text-emerald-600"></i>
                        <span>Direct gateway verification with instant stamped digital receipt.</span>
                    </div>
                    <button type="submit" id="submit-checkout-btn" class="px-7 py-3.5 rounded-2xl bg-brand-700 hover:bg-brand-800 text-white font-bold text-xs transition shadow-sm hover:shadow flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed">
                        <span>Proceed to Paystack Checkout</span>
                        <i data-lucide="arrow-right" class="w-4 h-4"></i>
                    </button>
                </div>
            </div>
        <?php endif; ?>
    </form>

    <!-- Payment Receipts Ledger -->
    <?php if (!empty($invoice->payments)): ?>
        <div class="bg-white rounded-3xl border border-slate-200 shadow-xs overflow-hidden">
            <div class="p-5 border-b border-slate-100 flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-black text-slate-900">Official Payment Receipts</h3>
                    <p class="text-xs text-slate-500">Verified transactions credited to this docket.</p>
                </div>
                <span class="text-xs font-mono font-bold text-slate-400"><?= count($invoice->payments) ?> receipt(s)</span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 text-slate-500 font-bold uppercase tracking-wider text-[10px]">
                        <tr>
                            <th class="py-3 px-5">Date</th>
                            <th class="py-3 px-5">Reference</th>
                            <th class="py-3 px-5">Channel</th>
                            <th class="py-3 px-5 text-right">Amount Paid</th>
                            <th class="py-3 px-5 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php foreach ($invoice->payments as $p): ?>
                            <tr>
                                <td class="py-3.5 px-5 text-slate-600 font-mono text-[11px]">
                                    <?= date('M d, Y', strtotime($p->paidAt ?? $p->createdAt)) ?>
                                </td>
                                <td class="py-3.5 px-5 font-mono font-bold text-slate-800">
                                    <?= htmlspecialchars($p->reference, ENT_QUOTES, 'UTF-8') ?>
                                </td>
                                <td class="py-3.5 px-5">
                                    <span class="px-2 py-0.5 rounded-md text-[10px] font-bold uppercase bg-slate-100 text-slate-700">
                                        <?= htmlspecialchars(str_replace('_', ' ', $p->channel), ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                </td>
                                <td class="py-3.5 px-5 text-right font-mono font-bold text-emerald-700">
                                    ₦<?= number_format($p->amount, 2) ?>
                                </td>
                                <td class="py-3.5 px-5 text-right">
                                    <a href="/payments/receipt/<?= $p->id ?>" target="_blank" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-slate-200 hover:bg-slate-50 text-brand-700 font-bold text-xs transition">
                                        <i data-lucide="printer" class="w-3.5 h-3.5"></i>
                                        <span>Receipt</span>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
function updateCalculatedTotal() {
    const checkboxes = document.querySelectorAll('.item-checkbox');
    const headerCheck = document.getElementById('header-select-all');
    let total = 0;
    let selectedCount = 0;
    let unselectedRequired = 0;

    checkboxes.forEach(cb => {
        if (cb.checked) {
            total += parseFloat(cb.dataset.amount || 0);
            selectedCount++;
        } else {
            if (cb.dataset.required === '1') {
                unselectedRequired++;
            }
        }
    });

    if (headerCheck) {
        headerCheck.checked = (selectedCount === checkboxes.length) && checkboxes.length > 0;
        headerCheck.indeterminate = (selectedCount > 0 && selectedCount < checkboxes.length);
    }

    const totalDisplay = document.getElementById('display-selected-total');
    const countDisplay = document.getElementById('display-selected-count');
    const submitBtn = document.getElementById('submit-checkout-btn');

    if (totalDisplay) {
        totalDisplay.innerText = '₦' + total.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }
    if (countDisplay) {
        countDisplay.innerText = selectedCount + ' component(s) selected';
    }
    if (submitBtn) {
        submitBtn.disabled = (selectedCount === 0 || total <= 0);
    }

    // Update Result Clearance status indicator
    const banner = document.getElementById('clearance-status-banner');
    const title = document.getElementById('clearance-status-title');
    const desc = document.getElementById('clearance-status-desc');
    const icon = document.getElementById('clearance-status-icon');

    if (banner && title && desc) {
        if (unselectedRequired === 0) {
            banner.className = 'p-4 rounded-2xl border transition-all duration-200 text-xs flex items-start gap-3 bg-emerald-50/70 border-emerald-200 text-emerald-800';
            title.className = 'font-bold text-emerald-950';
            title.innerText = 'Academic Result Clearance: Cleared upon payment';
            desc.className = 'text-[11px] text-emerald-700 mt-0.5';
            desc.innerText = 'All fee components required for viewing your ward\'s report card are included in this payment.';
            if (icon) {
                icon.setAttribute('data-lucide', 'unlock');
                icon.className = 'w-5 h-5 text-emerald-600 flex-shrink-0 mt-0.5';
            }
        } else {
            banner.className = 'p-4 rounded-2xl border transition-all duration-200 text-xs flex items-start gap-3 bg-amber-50/70 border-amber-200 text-amber-800';
            title.className = 'font-bold text-amber-950';
            title.innerText = 'Academic Result Clearance: Remaining Locked (' + unselectedRequired + ' mandatory component(s) excluded)';
            desc.className = 'text-[11px] text-amber-700 mt-0.5';
            desc.innerText = 'Report card access for this term requires settlement of all mandatory components.';
            if (icon) {
                icon.setAttribute('data-lucide', 'lock');
                icon.className = 'w-5 h-5 text-amber-600 flex-shrink-0 mt-0.5';
            }
        }
        if (window.lucide) lucide.createIcons();
    }
}

function selectAllItems(select) {
    document.querySelectorAll('.item-checkbox').forEach(cb => {
        cb.checked = select;
    });
    updateCalculatedTotal();
}

function toggleAllCheckboxes(master) {
    selectAllItems(master.checked);
}

document.addEventListener('DOMContentLoaded', () => {
    updateCalculatedTotal();
});
</script>
