<?php
/**
 * Application Fee Checkout Screen
 * 
 * @var \App\Models\AdmissionWard $ward
 * @var \App\Models\AdmissionSession $session
 * @var \App\Models\AdmissionApplication $application
 * @var \App\Models\User $user
 * @var bool $isSimulated
 * @var string $reference
 */
$this->layout('layouts/applicant', ['title' => 'Application Fee Payment']);
?>

<div class="max-w-xl mx-auto space-y-6">

    <!-- Top Breadcrumb -->
    <div class="flex items-center gap-2 text-xs text-slate-500">
        <a href="/applicant/application" class="hover:text-[#7B3046] transition-colors flex items-center gap-1">
            <i data-lucide="arrow-left" class="w-3.5 h-3.5" style="width:14px;height:14px;"></i>
            <span>Back to Application Docket</span>
        </a>
        <span>/</span>
        <span class="text-slate-800 font-semibold">Application Fee Checkout</span>
    </div>

    <!-- Main Payment Card -->
    <div class="bg-white rounded-3xl border border-slate-200/80 p-6 sm:p-8 shadow-xl shadow-slate-200/50 relative overflow-hidden">
        
        <!-- Header Crest & Title -->
        <div class="text-center pb-6 border-b border-slate-100">
            <div class="inline-flex size-14 rounded-2xl bg-rose-50 text-[#7B3046] items-center justify-center mb-3 shadow-xs">
                <i data-lucide="credit-card" class="w-7 h-7" style="width:28px;height:28px;"></i>
            </div>
            <h1 class="font-serif text-2xl font-bold text-slate-900 tracking-tight">
                Application Fee Checkout
            </h1>
            <p class="text-xs text-slate-500 mt-1">
                Per-ward screening fee for prospective student admission.
            </p>
        </div>

        <!-- Prospective Ward Itemized Summary -->
        <div class="py-5 space-y-3">
            <div class="flex items-center justify-between text-xs">
                <span class="text-slate-500 font-medium">Prospective Ward</span>
                <strong class="text-slate-800 font-bold"><?= e($ward->getFullName()) ?></strong>
            </div>

            <div class="flex items-center justify-between text-xs">
                <span class="text-slate-500 font-medium">Class Grade</span>
                <span class="text-slate-800 font-semibold"><?= e($ward->classGrade) ?></span>
            </div>

            <div class="flex items-center justify-between text-xs">
                <span class="text-slate-500 font-medium">Application Docket</span>
                <span class="font-mono text-slate-800 font-bold"><?= e($application->applicationNumber) ?></span>
            </div>

            <div class="flex items-center justify-between text-xs">
                <span class="text-slate-500 font-medium">Academic Session</span>
                <span class="text-slate-800 font-medium"><?= e($session->title) ?></span>
            </div>

            <!-- Ledger Breakdown Box -->
            <div class="mt-4 pt-4 border-t border-dashed border-slate-200 bg-slate-50/80 rounded-2xl p-4 space-y-2">
                <div class="flex items-center justify-between text-xs text-slate-600">
                    <span>Admissions Processing &amp; Screening Fee</span>
                    <span class="font-semibold"><?= e($session->getFormattedFee()) ?></span>
                </div>
                <div class="flex items-center justify-between text-xs text-slate-600">
                    <span>Institutional Processing Surcharge</span>
                    <span class="font-semibold">₦0.00</span>
                </div>
                <div class="pt-2 border-t border-slate-200 flex items-center justify-between">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-700">Total Amount Payable</span>
                    <span class="text-xl font-extrabold text-[#7B3046]">
                        <?= e($session->getFormattedFee()) ?>
                    </span>
                </div>
            </div>
        </div>

        <!-- Checkout Actions -->
        <div class="pt-4 space-y-3">
            <!-- Official Paystack Checkout Trigger Form -->
            <form action="/applicant/payment/<?= $ward->id ?>/checkout" method="POST">
                <?= csrf_field() ?>
                <button type="submit" 
                        class="group relative w-full inline-flex items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-[#7B3046] via-[#8B3650] to-[#A33D5E] h-11 px-6 text-sm font-semibold text-white shadow-md shadow-[#7B3046]/20 transition-all duration-200 ease-out hover:shadow-lg hover:shadow-[#7B3046]/30 active:scale-[0.99] cursor-pointer">
                    <span>Pay with Paystack</span>
                    <span class="inline-flex items-center justify-center max-w-0 opacity-0 -translate-x-2 group-hover:max-w-[20px] group-hover:opacity-100 group-hover:translate-x-0 transition-all duration-200 ease-out overflow-hidden">
                        <i data-lucide="arrow-right" class="w-4 h-4 shrink-0" style="width:16px;height:16px;"></i>
                    </span>
                </button>
            </form>

            <!-- Instant Sandbox Simulation Form (Always available in local dev/test or when simulated flag is present) -->
            <div class="pt-3 border-t border-slate-100">
                <div class="rounded-xl bg-amber-50/70 border border-amber-200 p-3.5 text-center">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-amber-800 block mb-1">
                        Development / Sandbox Mode
                    </span>
                    <p class="text-xs text-amber-900 mb-2.5">
                        Test the payment verification flow instantly without needing live card credentials.
                    </p>
                    <form action="/applicant/payment/simulate" method="POST">
                        <?= csrf_field() ?>
                        <input type="hidden" name="reference" value="<?= e($reference ?: 'ADM-W' . $ward->id . '-' . date('YmdHis') . '-TEST') ?>">
                        <button type="submit" 
                                class="w-full inline-flex items-center justify-center gap-1.5 rounded-xl bg-amber-600 text-white h-9 px-4 text-xs font-semibold hover:bg-amber-700 transition-colors cursor-pointer shadow-xs">
                            <i data-lucide="check" class="w-3.5 h-3.5" style="width:14px;height:14px;"></i>
                            <span>Simulate Instant Payment Confirmation</span>
                        </button>
                    </form>
                </div>
            </div>

            <!-- Trust Badge -->
            <div class="text-center pt-2">
                <div class="inline-flex items-center gap-2 text-[11px] text-slate-400">
                    <i data-lucide="shield-check" class="w-3.5 h-3.5 text-emerald-500" style="width:14px;height:14px;"></i>
                    <span>256-Bit SSL Encrypted &amp; Verified by Paystack</span>
                </div>
            </div>
        </div>

    </div>

</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        if (window.lucide) {
            lucide.createIcons();
        }
    });
</script>
