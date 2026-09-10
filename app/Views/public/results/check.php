<?php
/**
 * Public Self-Service Result Checker Gateway
 * Claret Brand Theme: Burgundy (#7B3046), Deep Wine (#5C2233), and Brand Blue (#0C9DD5)
 * 
 * @var \App\Models\AcademicSession|null $currentSession
 * @var \App\Models\AcademicTerm|null $currentTerm
 * @var array<\App\Models\AcademicSession> $sessions
 * @var array<\App\Models\AcademicTerm> $terms
 * @var string|null $error
 * @var string|null $success
 */
$csrfToken = \App\Core\Csrf::getToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Result Portal — Check Terminal Report Card | Claret Academy</title>
    <meta name="description" content="Access and verify terminal report cards securely using your Student ID and Scratch-Card PIN.">
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
        .pin-input {
            letter-spacing: 0.25em;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            text-transform: uppercase;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-slate-950 via-[#2A0E17] to-slate-900 text-slate-100 min-h-screen flex flex-col justify-between p-4 sm:p-8 font-sans antialiased">
    
    <!-- Top Navigation / Brand Bar -->
    <header class="max-w-4xl w-full mx-auto flex items-center justify-between py-4">
        <div class="flex items-center gap-3">
            <img src="/assets/img/logo.png" alt="Logo" class="w-11 h-11 object-contain drop-shadow" onerror="this.src='/favicon.ico'; this.onerror=null;">
            <div>
                <span class="text-sm font-black tracking-tight text-white uppercase block leading-none">Claret Academy</span>
                <span class="text-[10px] tracking-wider text-sky-400 font-semibold uppercase">Official Result Portal</span>
            </div>
        </div>
        <div class="flex items-center gap-3">
            <a href="/login" class="text-xs font-semibold text-slate-300 hover:text-white transition px-3.5 py-2 rounded-xl border border-white/15 bg-white/5 hover:bg-white/10 backdrop-blur-sm">
                Staff & Parent Login &rarr;
            </a>
        </div>
    </header>

    <!-- Main Container -->
    <main class="max-w-md w-full mx-auto my-8">
        <div class="bg-slate-900/90 backdrop-blur-xl border border-white/10 rounded-3xl p-6 sm:p-8 shadow-2xl space-y-6">
            
            <!-- Header Section -->
            <div class="text-center space-y-2">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-brand-500/15 border border-brand-500/30 text-brand-100 text-xs font-semibold">
                    <span class="w-2 h-2 rounded-full bg-sky-400 animate-pulse"></span>
                    <span>Active: <?= e($currentSession->name ?? '2025/2026') ?> &bull; <?= e($currentTerm->name ?? 'Current Term') ?></span>
                </div>
                <h1 class="text-2xl font-black text-white tracking-tight">Check Terminal Result</h1>
                <p class="text-xs text-slate-400">Enter your Student ID and 12-character Scratch-Card PIN to view and print your official term report card.</p>
            </div>

            <!-- Flash Error / Success Alerts -->
            <?php if (!empty($error)): ?>
                <div class="p-3.5 rounded-2xl bg-rose-500/15 border border-rose-500/30 text-rose-200 text-xs flex items-start gap-2.5">
                    <svg class="w-4 h-4 text-rose-400 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span><?= e($error) ?></span>
                </div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="p-3.5 rounded-2xl bg-emerald-500/15 border border-emerald-500/30 text-emerald-200 text-xs flex items-start gap-2.5">
                    <svg class="w-4 h-4 text-emerald-400 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    <span><?= e($success) ?></span>
                </div>
            <?php endif; ?>

            <!-- Verification Form -->
            <form action="/results/check" method="POST" class="space-y-4" autocomplete="off">
                <!-- Both CSRF parameter names supported for bulletproof token delivery -->
                <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                
                <div>
                    <label for="admission_number" class="block text-xs font-bold text-slate-300 mb-1.5 uppercase tracking-wider">
                        Student ID / Admission Number <span class="text-rose-400">*</span>
                    </label>
                    <div class="relative">
                        <input type="text" 
                               id="admission_number" 
                               name="admission_number" 
                               required 
                               minlength="3"
                               placeholder="e.g. STU/2026/001" 
                               class="w-full bg-slate-800/90 border border-slate-700 focus:border-brand-600 focus:ring-2 focus:ring-brand-600/30 text-white placeholder-slate-500 rounded-xl px-4 py-3 text-sm transition outline-none">
                    </div>
                </div>

                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label for="pin_code" class="block text-xs font-bold text-slate-300 uppercase tracking-wider">
                            Scratch Card PIN <span class="text-rose-400">*</span>
                        </label>
                        <span class="text-[10px] text-slate-400 font-mono">12 Characters</span>
                    </div>
                    <div class="relative">
                        <input type="text" 
                               id="pin_code" 
                               name="pin_code" 
                               required 
                               minlength="14"
                               maxlength="14" 
                               pattern="[A-Za-z0-9]{4}-[A-Za-z0-9]{4}-[A-Za-z0-9]{4}"
                               title="Please enter your 12-character PIN code in format: XXXX-XXXX-XXXX"
                               placeholder="XXXX-XXXX-XXXX" 
                               class="pin-input w-full bg-slate-800/90 border border-slate-700 focus:border-sky-400 focus:ring-2 focus:ring-sky-400/30 text-sky-300 placeholder-slate-600 rounded-xl px-4 py-3 text-base font-bold transition outline-none shadow-inner">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label for="session_id" class="block text-[11px] font-bold text-slate-400 mb-1 uppercase tracking-wider">Academic Session</label>
                        <select id="session_id" name="session_id" class="w-full bg-slate-800/90 border border-slate-700 text-white rounded-xl px-3 py-2.5 text-xs outline-none focus:border-brand-600">
                            <?php foreach ($sessions as $s): ?>
                                <option value="<?= $s->id ?>" <?= ($currentSession && $currentSession->id === $s->id) ? 'selected' : '' ?>>
                                    <?= e($s->name) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="term_id" class="block text-[11px] font-bold text-slate-400 mb-1 uppercase tracking-wider">Term</label>
                        <select id="term_id" name="term_id" class="w-full bg-slate-800/90 border border-slate-700 text-white rounded-xl px-3 py-2.5 text-xs outline-none focus:border-brand-600">
                            <?php foreach ($terms as $t): ?>
                                <option value="<?= $t->id ?>" <?= ($currentTerm && $currentTerm->id === $t->id) ? 'selected' : '' ?>>
                                    <?= e($t->name) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <button type="submit" 
                        class="w-full py-3.5 px-4 bg-gradient-to-r from-brand-700 to-brand-800 hover:from-brand-800 hover:to-brand-900 text-white font-bold text-sm rounded-xl shadow-lg shadow-brand-950/50 transition flex items-center justify-center gap-2 cursor-pointer border border-brand-600/30">
                    <svg class="w-4 h-4 text-sky-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Check Report Card
                </button>
            </form>

            <!-- Card Purchase / Online Access Callout -->
            <div class="border-t border-slate-800 pt-5 text-center space-y-2">
                <p class="text-xs text-slate-400">Don't have a physical Scratch Card PIN?</p>
                <button type="button" 
                        onclick="document.getElementById('buyPinNoticeModal').classList.remove('hidden')" 
                        class="inline-flex items-center gap-1.5 text-xs font-bold text-sky-400 hover:text-sky-300 transition underline underline-offset-4 cursor-pointer">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                    Buy PIN Online via Paystack (₦1,500)
                </button>
            </div>

            <!-- PIN Policy Security Footnote -->
            <div class="bg-slate-800/40 rounded-xl p-3 border border-slate-800 text-[11px] text-slate-400 text-center leading-relaxed">
                <strong class="text-slate-300">Security Rule:</strong> Each scratch card grants a maximum of <strong>5 access views</strong>. On first verification, the PIN becomes permanently locked to your Student ID.
            </div>

        </div>
    </main>

    <!-- Buy PIN Modal Notice for Public Checker -->
    <div id="buyPinNoticeModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm">
        <div class="bg-slate-900 border border-slate-700 rounded-3xl max-w-sm w-full p-6 text-center space-y-4 shadow-2xl">
            <div class="w-12 h-12 rounded-2xl bg-brand-700/30 border border-brand-600/40 text-sky-300 mx-auto flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
            </div>
            <h3 class="text-lg font-bold text-white">Instant Online PIN Purchase</h3>
            <p class="text-xs text-slate-400 leading-relaxed">
                Enrolled parents and students can purchase and instantly reveal their Result PIN directly from the <strong class="text-slate-200">Parent / Student Portal</strong> with payment history receipts and live view counters.
            </p>
            <div class="space-y-2 pt-2">
                <a href="/login" class="block w-full py-2.5 px-4 bg-gradient-to-r from-brand-700 to-brand-800 hover:from-brand-800 hover:to-brand-900 text-white font-bold text-xs rounded-xl shadow transition">
                    Log in to Parent / Student Portal
                </a>
                <button type="button" onclick="document.getElementById('buyPinNoticeModal').classList.add('hidden')" class="block w-full py-2 px-4 text-xs text-slate-400 hover:text-white transition cursor-pointer">
                    Dismiss
                </button>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="max-w-4xl w-full mx-auto text-center py-4 text-xs text-slate-500">
        &copy; <?= date('Y') ?> Claret Academy &bull; Continuous Assessment & Result Security System
    </footer>

    <!-- Auto-formatting PIN Input Mask (XXXX-XXXX-XXXX) -->
    <script>
        const pinInput = document.getElementById('pin_code');
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
