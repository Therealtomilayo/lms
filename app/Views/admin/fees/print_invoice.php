<?php
/**
 * Printable Official Fee Invoice Docket
 * Standardized Letterhead & Layout
 * 
 * @var \App\Models\FeeInvoice $invoice
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice <?= htmlspecialchars($invoice->invoiceNumber, ENT_QUOTES, 'UTF-8') ?> — Claret International School</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print {
            .no-print { display: none !important; }
            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            @page { margin: 12mm; }
        }
    </style>
</head>
<body class="bg-slate-50 text-slate-900 font-sans antialiased p-4 sm:p-8">

    <!-- Action Bar (Hidden on print) -->
    <div class="max-w-3xl mx-auto mb-6 flex items-center justify-between no-print">
        <a href="/admin/fees/invoices/<?= $invoice->id ?>" class="inline-flex items-center gap-2 text-xs font-bold text-slate-600 hover:text-slate-900">
            &larr; Back to Invoice
        </a>
        <button onclick="window.print()" class="px-5 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs shadow-md transition flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
            Print Official Invoice
        </button>
    </div>

    <!-- Official Invoice Container -->
    <div class="max-w-3xl mx-auto bg-white rounded-2xl border border-slate-200 p-8 sm:p-12 shadow-sm relative overflow-hidden">
        
        <!-- Status Watermark if Paid -->
        <?php if ($invoice->isPaid()): ?>
            <div class="absolute -right-12 -top-12 size-44 border-8 border-emerald-500/20 rounded-full flex items-center justify-center transform rotate-12 pointer-events-none">
                <span class="text-emerald-600/30 font-black text-2xl uppercase tracking-widest">PAID</span>
            </div>
        <?php endif; ?>

        <!-- Institutional Letterhead -->
        <div class="border-b-2 border-slate-900 pb-6">
            <div class="flex flex-col sm:flex-row items-center justify-between gap-6">
                <div class="flex items-center gap-4 text-center sm:text-left">
                    <img src="/assets/img/logo.png" alt="School Crest" class="w-20 h-20 object-contain" onerror="this.src='/favicon.ico'; this.onerror=null;">
                    <div>
                        <h1 class="text-2xl font-black tracking-tight text-slate-900 uppercase">Claret International School</h1>
                        <p class="text-xs font-semibold text-slate-600">Motto: Discipline, Integrity &amp; Ardour</p>
                        <p class="text-[11px] text-slate-500 max-w-sm mt-0.5">Plot 700, Gitto Street, Mabushi, Abuja, Federal Capital Territory, 900104, Nigeria</p>
                        <p class="text-[11px] text-slate-400">bursary@claret.edu &bull; https://lms.test</p>
                    </div>
                </div>
                <div class="text-center sm:text-right">
                    <span class="inline-block px-3 py-1 rounded-full text-[11px] font-black tracking-wider uppercase <?= $invoice->isPaid() ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : ($invoice->isPartiallyPaid() ? 'bg-amber-50 text-amber-800 border border-amber-200' : 'bg-rose-50 text-rose-800 border border-rose-200') ?>">
                        <?= $invoice->isPaid() ? 'FULL PAYMENT RECEIVED' : ($invoice->isPartiallyPaid() ? 'PARTIAL PAYMENT RECORDED' : 'PAYMENT DUE') ?>
                    </span>
                    <div class="text-xs font-mono font-bold text-slate-800 mt-2">
                        Invoice #: <?= htmlspecialchars($invoice->invoiceNumber, ENT_QUOTES, 'UTF-8') ?>
                    </div>
                    <div class="text-[11px] text-slate-500">
                        Date: <?= date('F d, Y', strtotime($invoice->createdAt)) ?>
                    </div>
                    <?php if ($invoice->dueDate): ?>
                        <div class="text-[11px] text-rose-600 font-semibold">
                            Due Date: <?= date('F d, Y', strtotime($invoice->dueDate)) ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Student & Bill-To Metadata Grid -->
        <div class="grid grid-cols-2 gap-6 my-6 text-xs">
            <div class="bg-slate-50 rounded-xl p-4 border border-slate-100">
                <span class="text-[10px] uppercase font-bold text-slate-400 block mb-1">Student Billed</span>
                <div class="text-sm font-bold text-slate-900"><?= htmlspecialchars($invoice->studentName ?? '—', ENT_QUOTES, 'UTF-8') ?></div>
                <div class="font-mono text-slate-600 mt-0.5">Adm #: <?= htmlspecialchars($invoice->admissionNumber ?? '—', ENT_QUOTES, 'UTF-8') ?></div>
                <div class="text-slate-600 mt-0.5">Class: <span class="font-bold text-slate-800"><?= htmlspecialchars($invoice->className ?? '—', ENT_QUOTES, 'UTF-8') ?></span></div>
                <div class="text-slate-500 mt-0.5"><?= htmlspecialchars($invoice->sessionName . ' &bull; ' . $invoice->termName, ENT_QUOTES, 'UTF-8') ?></div>
            </div>
            <div class="bg-slate-50 rounded-xl p-4 border border-slate-100">
                <span class="text-[10px] uppercase font-bold text-slate-400 block mb-1">Bill To (Guardian)</span>
                <div class="text-sm font-bold text-slate-900"><?= htmlspecialchars($invoice->parentName ?? 'Parent / Guardian', ENT_QUOTES, 'UTF-8') ?></div>
                <div class="text-slate-600 mt-0.5"><?= htmlspecialchars($invoice->parentEmail ?? '—', ENT_QUOTES, 'UTF-8') ?></div>
                <div class="text-slate-600 mt-0.5"><?= htmlspecialchars($invoice->parentPhone ?? '—', ENT_QUOTES, 'UTF-8') ?></div>
                <div class="text-slate-400 text-[10px] mt-1">Official Institutional Billing Record</div>
            </div>
        </div>

        <!-- Itemized Table -->
        <table class="w-full text-left text-xs mb-6">
            <thead class="bg-slate-100 text-slate-600 uppercase font-bold text-[10px] tracking-wider border-y border-slate-200">
                <tr>
                    <th class="py-2.5 px-3">#</th>
                    <th class="py-2.5 px-3">Fee Category</th>
                    <th class="py-2.5 px-3">Description</th>
                    <th class="py-2.5 px-3 text-right">Amount (₦)</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php foreach ($invoice->items as $idx => $item): ?>
                    <tr>
                        <td class="py-2.5 px-3 font-mono text-slate-400"><?= $idx + 1 ?></td>
                        <td class="py-2.5 px-3 text-slate-600"><?= htmlspecialchars($item->categoryName ?? 'Levy', ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="py-2.5 px-3 font-bold text-slate-800"><?= htmlspecialchars($item->name, ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="py-2.5 px-3 text-right font-mono font-bold text-slate-900">₦<?= number_format($item->amount, 2) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot class="border-t-2 border-slate-200 font-bold text-xs">
                <tr>
                    <td colspan="3" class="py-2 px-3 text-right text-slate-600">Subtotal:</td>
                    <td class="py-2 px-3 text-right font-mono text-slate-900">₦<?= number_format($invoice->subtotal, 2) ?></td>
                </tr>
                <?php if ($invoice->discountAmount > 0): ?>
                    <tr>
                        <td colspan="3" class="py-1.5 px-3 text-right text-emerald-600">Institutional Discount:</td>
                        <td class="py-1.5 px-3 text-right font-mono text-emerald-600">-₦<?= number_format($invoice->discountAmount, 2) ?></td>
                    </tr>
                <?php endif; ?>
                <tr class="text-sm font-black border-t border-slate-300">
                    <td colspan="3" class="py-2.5 px-3 text-right text-slate-900">Total Billed:</td>
                    <td class="py-2.5 px-3 text-right font-mono text-slate-900">₦<?= number_format($invoice->totalAmount, 2) ?></td>
                </tr>
                <tr class="text-xs font-bold text-emerald-700">
                    <td colspan="3" class="py-1.5 px-3 text-right">Total Paid to Date:</td>
                    <td class="py-1.5 px-3 text-right font-mono text-emerald-700">-₦<?= number_format($invoice->amountPaid, 2) ?></td>
                </tr>
                <tr class="text-base font-black border-t-2 border-slate-900 bg-slate-50">
                    <td colspan="3" class="py-3 px-3 text-right text-slate-900">Outstanding Balance Due:</td>
                    <td class="py-3 px-3 text-right font-mono <?= $invoice->balanceDue > 0 ? 'text-rose-700' : 'text-emerald-700' ?>">
                        ₦<?= number_format($invoice->balanceDue, 2) ?>
                    </td>
                </tr>
            </tfoot>
        </table>

        <!-- Payment History if any -->
        <?php if (!empty($invoice->payments)): ?>
            <div class="mb-8">
                <h4 class="text-[11px] uppercase font-bold text-slate-400 tracking-wider mb-2">Payments Received on This Account</h4>
                <table class="w-full text-left text-[11px] border border-slate-200 rounded-xl overflow-hidden">
                    <thead class="bg-slate-50 text-slate-500 font-bold uppercase text-[9px]">
                        <tr>
                            <th class="py-2 px-3">Date</th>
                            <th class="py-2 px-3">Reference</th>
                            <th class="py-2 px-3">Channel</th>
                            <th class="py-2 px-3 text-right">Amount</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php foreach ($invoice->payments as $pay): ?>
                            <tr>
                                <td class="py-2 px-3 text-slate-600 font-mono"><?= date('M d, Y', strtotime($pay->paidAt ?? $pay->createdAt)) ?></td>
                                <td class="py-2 px-3 font-mono font-semibold"><?= htmlspecialchars($pay->reference, ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="py-2 px-3 uppercase text-[10px]"><?= htmlspecialchars($pay->channel, ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="py-2 px-3 text-right font-mono font-bold text-emerald-700">₦<?= number_format($pay->amount, 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <!-- Signature & Disclaimer Section -->
        <div class="border-t border-slate-200 pt-6 mt-6 flex items-end justify-between text-xs text-slate-500">
            <div>
                <p class="font-bold text-slate-700">Payment Instructions:</p>
                <p class="text-[11px] text-slate-500 mt-0.5">Online card &amp; transfer payments can be completed via the Parent Portal at <span class="font-mono">https://lms.test/parent/fees</span>.</p>
                <p class="text-[11px] text-slate-500">For direct bank deposits, present teller slip to the Bursary Unit.</p>
            </div>
            <div class="text-center">
                <div class="w-44 border-b border-slate-400 mb-1"></div>
                <span class="text-[10px] uppercase font-bold text-slate-600 tracking-wider">Authorized Bursar</span>
            </div>
        </div>

    </div>

</body>
</html>
