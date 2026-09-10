<?php
/**
 * Result Access Scratch-Card PIN Gate View
 * Claret Brand Theme: Burgundy (#7B3046), Deep Wine (#5C2233), and Brand Blue (#0C9DD5)
 * Displayed to Parents and Students when terminal results require security PIN clearance.
 * 
 * @var \App\Models\Student $student
 * @var \App\Models\AcademicSession $currentSession
 * @var \App\Models\AcademicTerm $currentTerm
 * @var string $unlockUrl
 * @var string $backUrl
 * @var string|null $error
 * @var string|null $success
 * @var bool $isParent
 */
$csrfToken = \App\Core\Csrf::getToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scratch-Card PIN Required — Claret Academy</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://js.paystack.co/v1/inline.js"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            50: '#FDF4F6',
                            100: '#F9E5EA',
                            600: '#0C9DD5',
                            700: '#7B3046',
                            800: '#5C2233',
                            900: '#3D1520',
                        }
                    }
                }
            }
        }
    </script>
    <style>
        .pin-input {
            letter-spacing: 0.25em;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            text-transform: uppercase;
        }
    </style>
</head>
<body class="bg-slate-100 text-slate-900 min-h-screen p-4 sm:p-8 font-sans antialiased flex flex-col justify-between">
    <div class="max-w-3xl w-full mx-auto space-y-6 my-auto">
        <!-- Top Navigation -->
        <div class="flex items-center justify-between">
            <a href="<?= e($backUrl) ?>" class="inline-flex items-center gap-1.5 text-xs font-bold text-slate-600 hover:text-brand-700 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Back to Gradebook Overview
            </a>
            <a href="/payments/history" class="inline-flex items-center gap-1.5 text-xs font-bold text-brand-700 hover:text-brand-800 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Payment & PIN Receipts History
            </a>
        </div>

        <!-- Main Gate Card -->
        <div class="bg-white rounded-3xl border border-slate-200 shadow-xl overflow-hidden">
            <!-- Header Banner in Claret Burgundy -->
            <div class="bg-gradient-to-r from-brand-900 via-brand-800 to-slate-900 text-white p-6 sm:p-8">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <div class="w-14 h-14 rounded-2xl bg-white/100 backdrop-blur-sm border border-white/20 flex items-center justify-center text-white flex-shrink-0 shadow-inner">
            <div class="logo-wrap">
                <img src="<?= htmlspecialchars($school['logo'] ?? '/assets/img/logo.png') ?>" 
                     alt="School Crest" 
                     onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                <div class="logo-fallback" style="display:none;">CL</div>
            </div>                        </div>
                        <div>
                            <span class="inline-block px-2.5 py-0.5 rounded-full bg-white/15 border border-white/20 text-brand-100 text-[10px] font-bold uppercase tracking-wider mb-1">
                                Examination Security Clearance
                            </span>
                            <h1 class="text-xl sm:text-2xl font-black tracking-tight">Scratch-Card PIN Required</h1>
                            <p class="text-xs text-slate-300">Access to official terminal report card and printable dossier is restricted.</p>
                        </div>
                    </div>
                    <div class="sm:text-right bg-white/5 sm:bg-transparent p-3 sm:p-0 rounded-xl border border-white/10 sm:border-0 text-xs text-slate-300 space-y-0.5">
                        <div class="font-bold text-white"><?= e($student->name) ?></div>
                        <div class="font-mono text-sky-300 text-[11px]">ID: <?= e($student->admissionNumber) ?></div>
                        <div class="text-[11px] text-slate-400"><?= e($currentSession->name ?? '') ?> &bull; <?= e($currentTerm->name ?? '') ?></div>
                    </div>
                </div>
            </div>

            <!-- Flash Error / Success Alerts -->
            <div class="p-6 sm:p-8 space-y-6">
                <?php if (!empty($error)): ?>
                    <div class="p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-xs flex items-start gap-3">
                        <svg class="w-5 h-5 text-rose-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <div>
                            <strong class="font-bold block">Access Verification Failed</strong>
                            <p class="mt-0.5"><?= e($error) ?></p>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (!empty($success)): ?>
                    <div class="p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs flex items-start gap-3">
                        <svg class="w-5 h-5 text-emerald-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        <div>
                            <strong class="font-bold block">Success</strong>
                            <p class="mt-0.5"><?= e($success) ?></p>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Two Ways to Unlock: Dual Columns -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    
                    <!-- Option 1: Physical Scratch Card PIN Entry -->
                    <div class="rounded-2xl border border-slate-200 p-6 bg-slate-50/70 flex flex-col justify-between space-y-4">
                        <div class="space-y-3">
                            <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-slate-200 text-slate-700 text-[10px] font-bold uppercase tracking-wider">
                                <span>Option A</span> &bull; <span>Physical Card</span>
                            </div>
                            <h2 class="text-base font-bold text-slate-900">Enter Scratch-Card PIN</h2>
                            <p class="text-xs text-slate-500 leading-relaxed">
                                If you purchased a physical scratch card from the school bursary, scratch gently to reveal your 12-character PIN code.
                            </p>

                            <form id="result-gate-form" action="<?= e($unlockUrl) ?>" method="POST" class="space-y-3 pt-2">
                                <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                <input type="hidden" name="term_id" value="<?= (int)$currentTerm->id ?>">
                                <input type="hidden" name="session_id" value="<?= (int)$currentSession->id ?>">

                                <div>
                                    <label for="gate_pin_code" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                                        Scratch PIN Code <span class="text-rose-500">*</span>
                                    </label>
                                    <input type="text" 
                                           id="gate_pin_code" 
                                           name="pin_code" 
                                           required 
                                           minlength="14"
                                           maxlength="14" 
                                           pattern="[A-Za-z0-9]{4}-[A-Za-z0-9]{4}-[A-Za-z0-9]{4}"
                                           title="Please enter your 12-character PIN code in format: XXXX-XXXX-XXXX"
                                           placeholder="XXXX-XXXX-XXXX" 
                                           class="pin-input w-full bg-white border border-slate-300 focus:border-brand-700 focus:ring-2 focus:ring-brand-700/20 rounded-xl px-4 py-3 text-base font-bold text-slate-900 transition outline-none shadow-xs">
                                </div>

                                <button type="submit" 
                                        class="w-full py-3 px-4 bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs rounded-xl shadow transition flex items-center justify-center gap-2 cursor-pointer">
                                    <svg class="w-4 h-4 text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"/></svg>
                                    Unlock Report Card
                                </button>
                            </form>
                        </div>
                        <div class="text-[11px] text-slate-400 border-t border-slate-200 pt-3">
                            Allows up to <strong>5 terminal views</strong>. Bound to this student on first use.
                        </div>
                    </div>

                    <!-- Option 2: Buy PIN Online (Paystack) in Claret Brand Tones -->
                    <div class="rounded-2xl border-2 border-brand-700/30 bg-brand-50/30 p-6 flex flex-col justify-between space-y-4 relative overflow-hidden">
                        <div class="space-y-3 relative">
                            <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-brand-100 text-brand-800 text-[10px] font-bold uppercase tracking-wider">
                                <span>Option B</span> &bull; <span>Instant Online</span>
                            </div>
                            <div class="flex items-baseline justify-between">
                                <h2 class="text-base font-bold text-slate-900">Buy PIN Online</h2>
                                <span class="text-xl font-black text-brand-700">₦1,500.00</span>
                            </div>
                            <p class="text-xs text-slate-600 leading-relaxed">
                                Don't have a physical card? Pay online securely via Paystack. Your PIN is instantly generated, revealed on screen with 1-click copy, and logged to your receipt history.
                            </p>

                            <div class="pt-2 space-y-2">
                                <button type="button" 
                                        onclick="openPinCheckoutModal(<?= (int)$student->id ?>, <?= (int)$currentSession->id ?>, <?= (int)$currentTerm->id ?>, '<?= e(addslashes($student->name)) ?>', '<?= e(addslashes($currentSession->name . ' - ' . $currentTerm->name)) ?>', 1500)" 
                                        class="w-full py-3.5 px-4 bg-gradient-to-r from-brand-700 to-brand-800 hover:from-brand-800 hover:to-brand-900 text-white font-bold text-xs rounded-xl shadow-lg shadow-brand-900/30 transition flex items-center justify-center gap-2 cursor-pointer border border-brand-600/30">
                                    <svg class="w-4 h-4 text-sky-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                                    Pay Online (Paystack Checkout)
                                </button>
                                <p class="text-[11px] text-center text-slate-500">Supports Card, Bank Transfer, USSD & Paystack Sandbox.</p>
                            </div>
                        </div>

                        <div class="text-[11px] text-brand-800 border-t border-brand-200/60 pt-3">
                            &bull; Instant reveal &bull; Downloadable receipt &bull; PIN saved forever in history.
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <footer class="text-center text-xs text-slate-400">
            &copy; <?= date('Y') ?> Claret Academy &bull; Continuous Assessment & Result Security
        </footer>
    </div>

    <!-- Reusable Paystack Checkout Modal Component -->
    <?php include dirname(__DIR__) . '/payments/checkout_modal.php'; ?>

    <!-- Auto-hyphenation mask for PIN code -->
    <script>
        const pinInput = document.getElementById('gate_pin_code');
        if (pinInput) {
            pinInput.addEventListener('input', function(e) {
                let v = e.target.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
                v = v.slice(0, 12);
                const parts = [];
                for (let i = 0; i < v.length; i += 4) {
                    parts.push(v.slice(i, i + 4));
                }
                e.target.value = parts.join('-');
            });
        }
    </script>
</body>
</html>
