<?php
/**
 * Official Printable Payment Receipt View
 * 
 * @var \App\Models\Payment $payment
 * @var \App\Models\Student|null $student
 * @var \App\Models\ResultAccessPin|null $pin
 * @var string $receipt_number
 * @var string $item_title
 * @var int $attempts_remaining
 * @var int $max_attempts
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Official Payment Receipt — <?= e($payment->reference) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print {
            body { background: #fff !important; color: #000 !important; }
            .no-print { display: none !important; }
            .print-card { border: none !important; box-shadow: none !important; padding: 0 !important; }
        }
    </style>
</head>
<body class="bg-slate-100 text-slate-900 min-h-screen py-8 px-4 font-sans antialiased">
    <div class="max-w-2xl mx-auto space-y-4">
        <!-- Back and Print Action Bar (Hidden in Print) -->
        <div class="no-print flex items-center justify-between">
            <a href="/payments/history" class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-600 hover:text-slate-900 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Back to Payment History
            </a>
            <button type="button" 
                    onclick="window.print()" 
                    class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold rounded-xl shadow-sm transition inline-flex items-center gap-2 cursor-pointer">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                Print Receipt
            </button>
        </div>

        <!-- Official Receipt Card -->
        <div class="print-card bg-white rounded-3xl border border-slate-200 shadow-xl p-8 sm:p-12 space-y-8">
            <!-- Header Letterhead -->
            <div class="border-b border-slate-200 pb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-center gap-4">
                    <img src="/assets/img/logo.png" alt="School Logo" class="w-16 h-16 object-contain" onerror="this.src='/favicon.ico'; this.onerror=null;">
                    <div>
                        <h1 class="text-xl font-black tracking-tight text-slate-900 uppercase">Claret International School</h1>
                        <p class="text-xs text-slate-500">Motto: Excellence, Integrity & Faith</p>
                        <p class="text-[11px] text-slate-400">P.O. Box 1234, School Campus &bull; bursary@claret.edu</p>
                    </div>
                </div>
                <div class="sm:text-right">
                    <?php if ($payment->isSuccessful()): ?>
                        <span class="inline-block px-3 py-1 bg-emerald-50 border border-emerald-200 text-emerald-700 text-xs font-black uppercase tracking-wider rounded-full">
                            Official Payment Receipt
                        </span>
                    <?php elseif ($payment->isPending()): ?>
                        <span class="inline-block px-3 py-1 bg-amber-50 border border-amber-200 text-amber-700 text-xs font-black uppercase tracking-wider rounded-full">
                            Pending Payment Invoice
                        </span>
                    <?php else: ?>
                        <span class="inline-block px-3 py-1 bg-rose-50 border border-rose-200 text-rose-700 text-xs font-black uppercase tracking-wider rounded-full">
                            Payment Failed
                        </span>
                    <?php endif; ?>
                    <p class="text-xs font-mono font-bold text-slate-900 mt-2">Receipt #: <?= e($receipt_number) ?></p>
                    <p class="text-[11px] text-slate-500">Date: <?= date('M j, Y - g:i A', strtotime($payment->paidAt ?? $payment->createdAt)) ?></p>
                </div>
            </div>

            <!-- Transaction Particulars -->
            <div class="grid grid-cols-2 gap-6 text-xs">
                <div class="space-y-1">
                    <span class="text-[10px] uppercase tracking-wider font-bold text-slate-400">Payer Information</span>
                    <p class="font-bold text-slate-900 text-sm"><?= e($payment->payerName ?? 'Parent / Guardian') ?></p>
                    <p class="text-slate-600"><?= e($payment->payerEmail ?? '') ?></p>
                    <p class="text-slate-500">Channel: <strong class="uppercase"><?= e($payment->channel) ?></strong></p>
                </div>
                <div class="space-y-1">
                    <span class="text-[10px] uppercase tracking-wider font-bold text-slate-400">Beneficiary Student</span>
                    <p class="font-bold text-slate-900 text-sm"><?= e($payment->studentName ?? ($student ? $student->name : 'N/A')) ?></p>
                    <p class="text-slate-600">ID / Adm No: <strong><?= e($payment->studentAdmissionNumber ?? ($student ? $student->admissionNumber : 'N/A')) ?></strong></p>
                    <p class="text-slate-500">Academic Term: <?= e($payment->sessionName ?? '') ?> &bull; <?= e($payment->termName ?? '') ?></p>
                </div>
            </div>

            <!-- Order Table -->
            <div class="border border-slate-200 rounded-2xl overflow-hidden">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 text-[11px] uppercase tracking-wider text-slate-500 font-bold border-b border-slate-200">
                        <tr>
                            <th class="py-3 px-4">Item Description</th>
                            <th class="py-3 px-4">Term</th>
                            <th class="py-3 px-4 text-right">Amount</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-medium">
                        <tr>
                            <td class="py-3.5 px-4 font-bold text-slate-900">
                                <?= e($item_title) ?>
                            </td>
                            <td class="py-3.5 px-4 text-slate-600">
                                <?= e($payment->termName ?? 'Current Term') ?>
                            </td>
                            <td class="py-3.5 px-4 text-right font-black text-slate-900 text-sm">
                                <?= e($payment->getFormattedAmount()) ?>
                            </td>
                        </tr>
                    </tbody>
                    <tfoot class="bg-slate-50 border-t border-slate-200 font-bold">
                        <tr>
                            <td colspan="2" class="py-3 px-4 text-right text-slate-600">Total Paid (NGN):</td>
                            <td class="py-3 px-4 text-right text-base text-emerald-600 font-black"><?= e($payment->getFormattedAmount()) ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- Result Access Scratch-Card PIN Box (Mandatory & Prominent) -->
            <?php if ($pin): ?>
                <div class="bg-gradient-to-br from-slate-900 to-slate-800 text-white rounded-2xl p-6 border border-slate-700 shadow-md space-y-3">
                    <div class="flex items-center justify-between border-b border-white/10 pb-2">
                        <span class="text-[10px] uppercase font-bold tracking-widest text-emerald-400">Official Result Access Security PIN</span>
                        <span class="text-xs text-slate-400 font-mono">Serial: <?= e($pin->serialNumber) ?></span>
                    </div>
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div>
                            <span class="text-[10px] text-slate-400 uppercase font-semibold block">Scratch-Card PIN Code:</span>
                            <div class="flex items-center gap-3 mt-1">
                                <span id="receipt-pin-code" class="text-2xl sm:text-3xl font-mono font-black tracking-widest text-emerald-300 select-all">
                                    <?= e($pin->getFormattedPin()) ?>
                                </span>
                                <button type="button" 
                                        onclick="copyReceiptPin()" 
                                        class="no-print p-2 bg-white/10 hover:bg-white/20 text-white rounded-lg transition inline-flex items-center gap-1.5 text-xs font-semibold cursor-pointer border border-white/20"
                                        title="Copy PIN Code">
                                    <svg class="w-4 h-4 text-emerald-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                    <span id="copy-btn-text">Copy PIN</span>
                                </button>
                            </div>
                            <p id="receipt-copy-feedback" class="text-[11px] text-emerald-400 font-semibold hidden mt-1">Copied to clipboard!</p>
                        </div>
                        <div class="text-right sm:border-l sm:border-white/10 sm:pl-6">
                            <span class="text-[10px] text-slate-400 uppercase font-semibold block">Remaining Attempts:</span>
                            <span class="text-lg font-bold text-white"><?= e($pin->getRemainingUses()) ?> of <?= e($pin->maxUses) ?> views</span>
                        </div>
                    </div>
                    <p class="text-[11px] text-slate-400 pt-1">
                        &bull; Note: Keep this PIN safe. Present or enter this code whenever viewing this student's terminal report card.
                    </p>
                </div>
            <?php endif; ?>

            <!-- Verification Footer & Sign-off -->
            <div class="pt-6 border-t border-slate-200 flex flex-col sm:flex-row sm:items-end justify-between gap-6 text-xs text-slate-500">
                <div class="space-y-1">
                    <p><strong>Transaction Ref:</strong> <span class="font-mono text-slate-700"><?= e($payment->reference) ?></span></p>
                    <p><strong>Gateway Ref:</strong> <span class="font-mono text-slate-700"><?= e($payment->gatewayReference ?? 'SIMULATED_PSTK_DIRECT') ?></span></p>
                    <p class="text-[11px] text-slate-400 mt-2">This is a computer-generated receipt issued by Claret School Information System.</p>
                </div>
                <div class="text-center sm:text-right border-t sm:border-t-0 pt-4 sm:pt-0">
                    <div class="h-10 border-b border-dashed border-slate-300 w-44 mx-auto sm:ml-auto"></div>
                    <span class="text-[11px] font-bold text-slate-600 block mt-1">Bursary / Accounts Department</span>
                    <span class="text-[10px] text-slate-400 block">Authorized Digital Endorsement</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Copy to Clipboard Script -->
    <script>
        function copyReceiptPin() {
            const pinText = document.getElementById('receipt-pin-code')?.innerText.trim();
            if (!pinText) return;
            navigator.clipboard.writeText(pinText).then(() => {
                const fb = document.getElementById('receipt-copy-feedback');
                const btnText = document.getElementById('copy-btn-text');
                if (fb) fb.classList.remove('hidden');
                if (btnText) btnText.textContent = 'Copied!';
                setTimeout(() => {
                    if (fb) fb.classList.add('hidden');
                    if (btnText) btnText.textContent = 'Copy PIN';
                }, 2500);
            });
        }
    </script>
</body>
</html>
