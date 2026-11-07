<?php
/**
 * Reusable Paystack-Ready Checkout Modal Component
 * Claret Brand Design: Burgundy (#7B3046), Deep Wine (#5C2233), Brand Blue (#0C9DD5)
 */
?>
<div id="pin-checkout-modal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="relative w-full max-w-md bg-white rounded-3xl shadow-2xl border border-slate-200 overflow-hidden transform transition-all">
        <!-- Claret Branded Header -->
        <div class="bg-gradient-to-r from-brand-900 via-brand-800 to-slate-900 text-white p-6 relative">
            <button type="button" onclick="closePinCheckoutModal()" class="absolute top-4 right-4 text-white/70 hover:text-white p-1 rounded-lg">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-white/100 flex items-center justify-center border border-white/20 shadow-inner">
            <div class="logo-wrap">
                <img src="<?= htmlspecialchars($school['logo'] ?? '/assets/img/logo.png') ?>" 
                     alt="School Crest" 
                     onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                <div class="logo-fallback" style="display:none;">CL</div>
            </div>                </div>
                <div>
                    <h3 class="text-base font-bold text-white">Claret International School Online Payments</h3>
                    <p class="text-xs text-brand-200">Official Result Scratch-Card PIN Gateway</p>
                </div>
            </div>
            <div class="mt-4 pt-3 border-t border-white/15 flex items-center justify-between">
                <span class="text-xs text-slate-300 font-medium">Standard Processing Fee:</span>
                <span class="text-xl font-black text-white" id="checkout-display-amount">₦1,500.00</span>
            </div>
        </div>

        <!-- Initial Checkout State -->
        <div id="checkout-form-state" class="p-6 space-y-4">
            <div class="bg-slate-50 p-3.5 rounded-2xl border border-slate-200 text-xs text-slate-600 space-y-1">
                <p><strong>Item:</strong> <span id="checkout-item-name" class="text-slate-800">Result Access Scratch-Card PIN</span></p>
                <p><strong>Student:</strong> <span id="checkout-student-name" class="text-slate-800 font-semibold">Loading...</span></p>
                <p><strong>Term:</strong> <span id="checkout-term-name" class="text-slate-800">Active Academic Term</span></p>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Select Payment Method</label>
                <div class="grid grid-cols-2 gap-3">
                    <label class="flex items-center gap-2 p-3 border-2 border-brand-700 bg-brand-50/50 rounded-xl cursor-pointer">
                        <input type="radio" name="pay_channel" value="card" checked class="text-brand-700 focus:ring-brand-700">
                        <span class="text-xs font-bold text-slate-800">Card / USSD</span>
                    </label>
                    <label class="flex items-center gap-2 p-3 border border-slate-200 rounded-xl cursor-pointer hover:bg-slate-50">
                        <input type="radio" name="pay_channel" value="transfer" class="text-brand-700 focus:ring-brand-700">
                        <span class="text-xs font-bold text-slate-800">Bank Transfer</span>
                    </label>
                </div>
            </div>

            <div class="space-y-2 pt-2">
                <!-- Primary Paystack Inline Checkout Button -->
                <button type="button" 
                        id="btn-paystack-popup"
                        onclick="executePaystackPopupPayment()" 
                        class="w-full py-3.5 px-4 bg-brand-700 hover:bg-brand-800 text-white font-bold rounded-xl shadow-md transition flex items-center justify-center gap-2 cursor-pointer border border-brand-600">
                    <svg class="w-4 h-4 text-sky-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                    <span>Pay ₦1,500 with Paystack</span>
                </button>
            </div>
            <p class="text-[11px] text-center text-slate-400">Secured with Paystack 256-bit encryption &bull; Card details never stored.</p>
        </div>

        <!-- Success State (Reveals Generated PIN with Copy Button) -->
        <div id="checkout-success-state" class="hidden p-6 sm:p-7 text-center space-y-4">
            <div class="w-14 h-14 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-600 flex items-center justify-center mx-auto shadow-xs">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
            </div>
            <div class="space-y-1">
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-100 text-emerald-800 text-[10px] font-bold uppercase tracking-wider">
                    Payment Verified Successfully
                </span>
                <h4 class="text-xl font-black text-slate-900 tracking-tight">PIN Activated &amp; Ready</h4>
                <p class="text-xs text-slate-500 max-w-xs mx-auto leading-relaxed">Your scratch-card PIN is linked to your child and can now be used to unlock the terminal report card.</p>
            </div>

            <!-- Redesigned, Eye-Friendly Voucher Card -->
            <div class="bg-gradient-to-b from-slate-50 to-white rounded-2xl border-2 border-dashed border-brand-300 p-4 sm:p-5 shadow-xs text-left space-y-3.5">
                <div class="flex items-center justify-between border-b border-slate-200/80 pb-2.5">
                    <span class="text-[11px] uppercase font-extrabold tracking-wider text-brand-800 flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-brand-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                        </svg>
                        Official Result Access PIN
                    </span>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                        Active &bull; Authorized
                    </span>
                </div>

                <!-- Clean PIN Ticket Strip (Light, soft background, deep crisp font) -->
                <div class="bg-white rounded-xl border border-slate-200 p-3 sm:p-3.5 shadow-inner flex items-center justify-between gap-2 sm:gap-3">
                    <div class="min-w-0">
                        <span class="text-[10px] uppercase font-bold text-slate-400 tracking-wider block mb-0.5">12-Character PIN Code</span>
                        <span id="revealed-pin-code" class="text-xl sm:text-2xl font-mono font-black tracking-widest text-brand-900 select-all block leading-tight">
                            XXXX-XXXX-XXXX
                        </span>
                    </div>
                    <button type="button" 
                            id="btn-copy-pin" 
                            onclick="copyPinToClipboard()" 
                            class="inline-flex items-center justify-center gap-1.5 px-3 py-2 bg-slate-100 hover:bg-slate-200 active:bg-slate-300 text-slate-700 hover:text-slate-900 font-bold text-xs rounded-xl border border-slate-200 shadow-2xs transition shrink-0 cursor-pointer" 
                            title="Copy PIN Code">
                        <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                        </svg>
                        <span id="copy-btn-text">Copy PIN</span>
                    </button>
                </div>
                <p id="copy-feedback" class="text-[11px] text-emerald-600 font-bold hidden flex items-center gap-1">
                    <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    PIN copied to clipboard!
                </p>

                <!-- Serial and Views Remaining Badges -->
                <div class="grid grid-cols-2 gap-2 text-xs pt-0.5">
                    <div class="bg-white p-2 sm:p-2.5 rounded-lg border border-slate-200">
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Serial Number</span>
                        <strong id="revealed-pin-serial" class="font-mono text-slate-800 text-[11px] block truncate">SN-XXXXXXXX</strong>
                    </div>
                    <div class="bg-white p-2 sm:p-2.5 rounded-lg border border-slate-200">
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Remaining Views</span>
                        <strong id="revealed-pin-views" class="text-emerald-700 font-bold text-[11px] block">5 of 5 views</strong>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="space-y-2 pt-1">
                <button type="button" 
                        id="btn-auto-unlock-result"
                        onclick="applyAndUnlockResult()" 
                        class="w-full py-3.5 px-4 bg-gradient-to-r from-brand-700 to-brand-800 hover:from-brand-800 hover:to-brand-900 text-white font-bold text-xs rounded-xl shadow-lg shadow-brand-900/20 transition cursor-pointer flex items-center justify-center gap-2">
                    <svg class="w-4 h-4 text-sky-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"/></svg>
                    <span>Unlock &amp; View Report Card</span>
                </button>
                <div>
                    <a id="btn-view-receipt" 
                       href="#" 
                       target="_blank" 
                       class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-600 hover:text-brand-800 transition">
                        <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                        <span>Print Official Payment Receipt</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let currentCheckoutPayload = {
    studentId: 0,
    sessionId: 0,
    termId: 0,
    studentName: '',
    termName: '',
    amount: 1500.0,
    reference: null,
    generatedPin: null
};

function openPinCheckoutModal(studentId, sessionId, termId, studentName, termName, amount = 1500) {
    currentCheckoutPayload = { studentId, sessionId, termId, studentName, termName, amount, reference: null, generatedPin: null };
    document.getElementById('checkout-student-name').textContent = studentName || 'Enrolled Student';
    document.getElementById('checkout-term-name').textContent = termName || 'Current Term';
    document.getElementById('checkout-display-amount').textContent = '₦' + Number(amount).toLocaleString('en-NG', {minimumFractionDigits: 2});
    document.getElementById('checkout-form-state').classList.remove('hidden');
    document.getElementById('checkout-success-state').classList.add('hidden');
    document.getElementById('pin-checkout-modal').classList.remove('hidden');
}

function closePinCheckoutModal() {
    document.getElementById('pin-checkout-modal').classList.add('hidden');
}

/**
 * Real Paystack Inline Checkout using Public Key & Backend Verification
 */
async function executePaystackPopupPayment() {
    const btn = document.getElementById('btn-paystack-popup');
    btn.disabled = true;
    btn.innerHTML = '<span>Initializing Gateway...</span>';

    try {
        const initRes = await fetch('/payments/checkout/pin', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
            body: new URLSearchParams({
                student_id: currentCheckoutPayload.studentId,
                session_id: currentCheckoutPayload.sessionId,
                term_id: currentCheckoutPayload.termId,
                _csrf_token: document.querySelector('input[name="_csrf_token"]')?.value || ''
            })
        });

        const initData = await initRes.json();
        if (!initData.success) {
            alert(initData.error || 'Unable to initiate payment.');
            btn.disabled = false;
            btn.innerHTML = '<span>Pay ₦1,500 with Paystack</span>';
            return;
        }

        const publicKey = initData.public_key;
        const ref = initData.reference;
        const payerEmail = initData.payer_email || 'parent@claret.edu';
        const authUrl = initData.authorization_url;
        const accessCode = initData.access_code;

        if (typeof PaystackPop === 'undefined') {
            if (authUrl) {
                // If Paystack inline JS is blocked, redirect directly to official Paystack hosted checkout URL
                window.location.href = authUrl;
                return;
            }
            alert('Paystack secure gateway could not be loaded. Please check your internet connection.');
            btn.disabled = false;
            btn.innerHTML = '<span>Pay ₦1,500 with Paystack</span>';
            return;
        }

        const paystackConfig = {
            key: publicKey,
            email: payerEmail,
            amount: 150000, // ₦1,500 in kobo
            currency: 'NGN',
            ref: ref,
            callback: function(response) {
                // Verify with backend directly
                verifyPaystackBackend(response.reference || ref);
            },
            onClose: function() {
                btn.disabled = false;
                btn.innerHTML = '<span>Pay ₦1,500 with Paystack</span>';
            }
        };

        if (accessCode) {
            paystackConfig.access_code = accessCode;
        }

        const handler = PaystackPop.setup(paystackConfig);
        handler.openIframe();
    } catch (err) {
        alert('Network or server error during checkout initialization.');
        btn.disabled = false;
        btn.innerHTML = '<span>Pay ₦1,500 with Paystack</span>';
    }
}

async function verifyPaystackBackend(ref) {
    try {
        const res = await fetch('/payments/callback?reference=' + encodeURIComponent(ref), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const data = await res.json();
        if (data.success) {
            displayCheckoutSuccess(data);
        } else {
            alert(data.error || 'Paystack payment verification failed.');
            const btn = document.getElementById('btn-paystack-popup');
            btn.disabled = false;
            btn.innerHTML = '<span>Pay ₦1,500 with Paystack</span>';
        }
    } catch (e) {
        alert('Could not verify payment with server.');
    }
}

function displayCheckoutSuccess(data) {
    currentCheckoutPayload.generatedPin = data.pin_code;
    document.getElementById('revealed-pin-code').textContent = data.pin_code;
    document.getElementById('revealed-pin-serial').textContent = data.serial_number;
    document.getElementById('revealed-pin-views').textContent = data.remaining_uses + ' of ' + data.max_uses;
    document.getElementById('btn-view-receipt').href = data.receipt_url;

    document.getElementById('checkout-form-state').classList.add('hidden');
    document.getElementById('checkout-success-state').classList.remove('hidden');
}

function copyPinToClipboard() {
    const pin = currentCheckoutPayload.generatedPin;
    if (!pin) return;
    navigator.clipboard.writeText(pin).then(() => {
        const fb = document.getElementById('copy-feedback');
        const btnText = document.getElementById('copy-btn-text');
        if (fb) fb.classList.remove('hidden');
        if (btnText) btnText.textContent = 'Copied!';
        setTimeout(() => {
            if (fb) fb.classList.add('hidden');
            if (btnText) btnText.textContent = 'Copy PIN';
        }, 2500);
    });
}

function applyAndUnlockResult() {
    const pin = currentCheckoutPayload.generatedPin;
    const pinInput = document.getElementById('pin_code') || document.getElementById('gate_pin_code');
    if (pinInput && pin) {
        pinInput.value = pin;
    }
    const gateForm = document.getElementById('result-gate-form');
    if (gateForm) {
        gateForm.submit();
    } else {
        window.location.reload();
    }
}
</script>
