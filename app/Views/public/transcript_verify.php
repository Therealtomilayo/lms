<?php
/**
 * Public Transcript Verification Page
 * Secure verification gateway for external evaluators, embassies, universities, and employers.
 *
 * @var bool $isValid
 * @var string $reference
 * @var array<string, mixed>|null $verification
 */
$pageTitle = $title ?? 'Transcript Verification — Claret LMS';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <meta name="description" content="Official cryptographic transcript verification portal for Claret International School.">
    <!-- Favicon Suite -->
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
    <link rel="shortcut icon" href="/favicon.ico">
    <meta name="theme-color" content="#7B3046">
    <script src="https://cdn.tailwindcss.com"></script>
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
        @media print {
            .no-print { display: none !important; }
            body { background: white !important; color: black !important; }
            .print-card { border: 1px solid #ccc !important; box-shadow: none !important; background: white !important; }
        }
    </style>
</head>
<body class="bg-gradient-to-br from-slate-950 via-[#2A0E17] to-slate-900 text-slate-100 min-h-screen flex flex-col justify-between p-4 sm:p-8 font-sans antialiased">

    <!-- Top Navigation / Brand Bar -->
    <header class="max-w-4xl w-full mx-auto flex items-center justify-between py-4 no-print">
        <div class="flex items-center gap-3">
            <img src="/assets/img/logo.png" alt="Logo" class="w-11 bg-white/90 rounded-2xl h-11 object-contain p-1 shadow" onerror="this.src='/favicon.ico'; this.onerror=null;">
            <div>
                <span class="text-sm font-black tracking-tight text-white uppercase block leading-none">Claret International School</span>
                <span class="text-[10px] tracking-wider text-sky-400 font-semibold uppercase">Official Transcript Verification Portal</span>
            </div>
        </div>
        <div class="flex items-center gap-3">
            <a href="/login" class="text-xs font-semibold text-slate-300 hover:text-white transition px-3.5 py-2 rounded-xl border border-white/15 bg-white/5 hover:bg-white/10 backdrop-blur-sm">
                Portal Login &rarr;
            </a>
        </div>
    </header>

    <!-- Main Container -->
    <main class="max-w-xl w-full mx-auto my-6 sm:my-10">
        
        <?php if ($isValid && !empty($verification)): ?>
            <!-- SUCCESS VERIFICATION CARD -->
            <div class="print-card bg-slate-900/90 backdrop-blur-xl border border-emerald-500/30 rounded-3xl p-6 sm:p-8 shadow-2xl space-y-6 relative overflow-hidden">
                <div class="absolute -right-16 -top-16 w-48 h-48 bg-emerald-500/10 rounded-full blur-3xl pointer-events-none"></div>

                <!-- Verified Badge & Header -->
                <div class="text-center space-y-3">
                    <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-emerald-500/15 border border-emerald-500/40 text-emerald-300 text-xs font-bold uppercase tracking-wider shadow-sm">
                        <svg class="w-4 h-4 text-emerald-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                        <span>Official Record Verified</span>
                    </div>

                    <h1 class="text-2xl font-black text-white tracking-tight">Academic Career Transcript</h1>
                    
                    <div class="inline-block px-3 py-1 bg-slate-800/80 rounded-lg border border-slate-700 font-mono text-xs text-sky-300 font-semibold tracking-wider">
                        REF: <?= htmlspecialchars($verification['reference']) ?>
                    </div>
                </div>

                <!-- Student Information Grid -->
                <div class="bg-slate-950/60 rounded-2xl p-5 border border-white/5 space-y-3">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                        <div>
                            <span class="text-slate-400 block uppercase font-medium text-[10px] tracking-wider">Student Full Name</span>
                            <span class="text-white font-bold text-sm block mt-0.5"><?= htmlspecialchars($verification['student_name']) ?></span>
                        </div>
                        <div>
                            <span class="text-slate-400 block uppercase font-medium text-[10px] tracking-wider">Admission Number</span>
                            <span class="text-sky-300 font-bold text-sm block mt-0.5"><?= htmlspecialchars($verification['admission_number']) ?></span>
                        </div>
                        <div>
                            <span class="text-slate-400 block uppercase font-medium text-[10px] tracking-wider">Issuing Institution</span>
                            <span class="text-slate-200 font-semibold block mt-0.5"><?= htmlspecialchars($verification['school_name']) ?></span>
                        </div>
                        <div>
                            <span class="text-slate-400 block uppercase font-medium text-[10px] tracking-wider">Verification Timestamp</span>
                            <span class="text-slate-300 font-mono block mt-0.5"><?= htmlspecialchars($verification['verified_at']) ?></span>
                        </div>
                    </div>
                </div>

                <!-- Cumulative Career Metrics Grid -->
                <div class="space-y-3">
                    <h3 class="text-xs font-bold text-slate-300 uppercase tracking-wider flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5 text-brand-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Certified Career Metrics
                    </h3>

                    <div class="grid grid-cols-3 gap-2.5 text-center">
                        <div class="p-3 bg-slate-800/50 rounded-xl border border-white/5">
                            <span class="block text-[10px] uppercase text-slate-400 font-semibold">Sessions</span>
                            <span class="text-lg font-black text-white"><?= (int)$verification['total_sessions'] ?></span>
                        </div>
                        <div class="p-3 bg-slate-800/50 rounded-xl border border-white/5">
                            <span class="block text-[10px] uppercase text-slate-400 font-semibold">Terms</span>
                            <span class="text-lg font-black text-white"><?= (int)$verification['total_terms'] ?></span>
                        </div>
                        <div class="p-3 bg-slate-800/50 rounded-xl border border-white/5">
                            <span class="block text-[10px] uppercase text-slate-400 font-semibold">Subjects</span>
                            <span class="text-lg font-black text-white"><?= (int)$verification['total_subjects'] ?></span>
                        </div>
                    </div>

                    <div class="p-4 bg-gradient-to-r from-brand-900/60 to-slate-900/80 rounded-2xl border border-brand-700/40 flex items-center justify-between">
                        <div>
                            <span class="block text-[10px] uppercase text-slate-400 font-semibold tracking-wider">Cumulative Career Average</span>
                            <span class="text-2xl font-black text-amber-400 tracking-tight"><?= number_format((float)$verification['cumulative_average'], 2) ?>%</span>
                        </div>
                        <div class="text-right">
                            <span class="block text-[10px] uppercase text-slate-400 font-semibold tracking-wider">Honors Standing</span>
                            <span class="inline-block px-2.5 py-1 mt-0.5 rounded-lg bg-emerald-500/20 text-emerald-300 font-black text-xs border border-emerald-500/30">
                                <?= htmlspecialchars($verification['honors_classification']) ?>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Cryptographic Assurance Note -->
                <div class="p-3.5 rounded-xl bg-slate-950/40 border border-white/5 text-[11px] text-slate-400 leading-relaxed">
                    <p class="flex items-start gap-2">
                        <svg class="w-4 h-4 text-emerald-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                        <span>This digital record has been cryptographically validated against the school central database repository. Certified in compliance with universal secondary school percentage grading standards (Zero GPA).</span>
                    </p>
                </div>

                <!-- Print & Re-verify Actions -->
                <div class="pt-2 flex flex-col sm:flex-row gap-3 no-print">
                    <button type="button" onclick="window.print()" class="flex-1 py-3 px-4 rounded-xl bg-brand-700 hover:bg-brand-800 text-white font-bold text-xs uppercase tracking-wider transition shadow-lg flex items-center justify-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                        Print Verification Proof
                    </button>
                    <a href="/verify/transcript/lookup" onclick="event.preventDefault(); document.getElementById('manual-lookup-modal').classList.toggle('hidden');" class="py-3 px-4 rounded-xl border border-white/15 bg-white/5 hover:bg-white/10 text-slate-300 hover:text-white font-semibold text-xs text-center transition">
                        Verify Another Ref
                    </a>
                </div>

            </div>

        <?php else: ?>
            <!-- INVALID VERIFICATION CARD -->
            <div class="bg-slate-900/90 backdrop-blur-xl border border-rose-500/30 rounded-3xl p-6 sm:p-8 shadow-2xl space-y-6">
                
                <div class="text-center space-y-3">
                    <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-rose-500/15 border border-rose-500/40 text-rose-300 text-xs font-bold uppercase tracking-wider">
                        <svg class="w-4 h-4 text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                        <span>Verification Unsuccessful</span>
                    </div>

                    <h1 class="text-2xl font-black text-white tracking-tight">Record Not Authenticated</h1>
                    
                    <p class="text-xs text-slate-400 max-w-sm mx-auto">
                        The transcript reference code <span class="font-mono text-rose-300 font-bold"><?= htmlspecialchars($reference ?: 'EMPTY') ?></span> does not match any certified student academic record in our secure database.
                    </p>
                </div>

                <div class="p-4 bg-slate-950/60 rounded-2xl border border-white/5 space-y-2 text-xs text-slate-400">
                    <span class="text-slate-300 font-bold uppercase text-[10px] tracking-wider block">Possible Reasons:</span>
                    <ul class="list-disc list-inside space-y-1">
                        <li>Typographical error when entering the reference code.</li>
                        <li>The transcript has been altered or tampered with.</li>
                        <li>The student record has not yet been certified for public lookup.</li>
                    </ul>
                </div>

            </div>
        <?php endif; ?>

        <!-- Manual Lookup Card -->
        <div id="manual-lookup-modal" class="<?= ($isValid && !empty($verification)) ? 'hidden' : '' ?> mt-6 bg-slate-900/60 backdrop-blur-xl border border-white/10 rounded-3xl p-6 shadow-xl space-y-4">
            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-300">Look Up Security Reference</h3>
            <form onsubmit="event.preventDefault(); const ref = document.getElementById('ref-input').value.trim(); if(ref) window.location.href = '/verify/transcript/' + encodeURIComponent(ref);" class="flex gap-2">
                <input type="text" id="ref-input" required placeholder="e.g. CLT-TRN-018-A7F2" class="flex-1 px-4 py-2.5 bg-slate-950/80 border border-white/15 rounded-xl text-white font-mono text-xs uppercase focus:outline-none focus:border-sky-500 placeholder-slate-500">
                <button type="submit" class="px-5 py-2.5 bg-sky-600 hover:bg-sky-700 text-white font-bold text-xs uppercase tracking-wider rounded-xl transition shadow">
                    Verify
                </button>
            </form>
        </div>

    </main>

    <!-- Footer -->
    <footer class="max-w-4xl w-full mx-auto text-center py-4 text-xs text-slate-500 space-y-1 no-print">
        <p>&copy; <?= date('Y') ?> Claret International School &bull; Continuous Academic Dossier Verification</p>
        <p class="text-[11px] text-slate-600">Official Registrar: <a href="mailto:registrar@claret.edu" class="underline hover:text-slate-400">registrar@claret.edu</a></p>
    </footer>

</body>
</html>
