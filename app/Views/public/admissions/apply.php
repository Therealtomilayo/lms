<?php
/**
 * Public Admission Gateway: /apply
 * 
 * @var \App\Models\AdmissionSession|null $session Active admission session
 * @var bool $isOpen Whether admission is currently open
 */
$this->layout('layouts/auth', [
    'title' => 'Online Admissions — Claret International School',
    'wideLayout' => true,
]);
?>

<div class="mx-auto w-full max-w-5xl rounded-3xl bg-white shadow-2xl ring-1 ring-slate-900/5 overflow-hidden grid lg:grid-cols-12 min-h-[600px]">

    <!-- Left Column: School Campus Imagery & Information -->
    <div class="relative hidden lg:flex lg:col-span-5 flex-col justify-between p-10 text-white overflow-hidden bg-slate-950 select-none">
        <img src="/assets/img/Claret-International-School-12-1024x576.jpg" 
             alt="Claret International School Campus" 
             class="absolute inset-0 h-full w-full object-cover opacity-35 transform scale-105 transition-transform duration-1000 ease-out hover:scale-100">
        
        <div class="absolute inset-0 bg-gradient-to-t from-[#2A0B14] via-[#5F2234]/85 to-[#1C2A39]/80"></div>
        <div class="absolute -top-24 -left-24 w-72 h-72 rounded-full bg-rose-500/20 blur-3xl pointer-events-none"></div>

        <!-- Top Brand Crest -->
        <div class="relative z-10">
            <a href="/" class="inline-flex items-center gap-3.5 group">
                <div class="size-14 rounded-2xl bg-white/80 backdrop-blur-md p-2 ring-1 ring-white/20 shadow-lg flex items-center justify-center transition-transform group-hover:scale-105">
                    <img src="/assets/img/logo.png" alt="Claret Crest" class="h-full w-full object-contain">
                </div>
                <div>
                    <span class="block font-serif font-bold text-white text-lg tracking-tight leading-tight">Claret International</span>
                    <span class="block text-[11px] font-semibold tracking-[0.2em] text-pink-200 uppercase">School • Abuja</span>
                </div>
            </a>
        </div>

        <!-- Center Inspiration -->
        <div class="relative z-10 my-auto py-8">
            <h2 class="font-serif text-3xl font-bold leading-snug tracking-tight text-white">
                Admissions &amp; Online Application
            </h2>
            <p class="mt-3 text-sm text-slate-200/90 leading-relaxed">
                Take the first step toward academic excellence, moral rectitude, and global competence at Claret International School.
            </p>

            <div class="mt-6 space-y-3 text-xs text-slate-200/90">
                <div class="flex items-center gap-2.5">
                    <div class="size-6 rounded-full bg-white/15 flex items-center justify-center text-pink-300 font-bold shrink-0">1</div>
                    <span>Create Guardian / Applicant Account</span>
                </div>
                <div class="flex items-center gap-2.5">
                    <div class="size-6 rounded-full bg-white/15 flex items-center justify-center text-pink-300 font-bold shrink-0">2</div>
                    <span>Pay Application Fee securely per ward</span>
                </div>
                <div class="flex items-center gap-2.5">
                    <div class="size-6 rounded-full bg-white/15 flex items-center justify-center text-pink-300 font-bold shrink-0">3</div>
                    <span>Complete prospective ward details &amp; upload documents</span>
                </div>
                <div class="flex items-center gap-2.5">
                    <div class="size-6 rounded-full bg-white/15 flex items-center justify-center text-pink-300 font-bold shrink-0">4</div>
                    <span>Track review progress until admission decision</span>
                </div>
            </div>
        </div>

        <!-- Bottom Campus Contact -->
        <div class="relative z-10 pt-4 border-t border-white/10 flex items-center justify-between text-xs text-white/70">
            <span>Mabushi, Abuja FCT</span>
            <span>+234 803 788 1737</span>
        </div>
    </div>

    <!-- Right Column: Admission Status & Action -->
    <div class="lg:col-span-7 p-8 sm:p-12 lg:p-14 flex flex-col justify-center bg-white relative">
        
        <!-- Mobile Crest (Shown only on small screens) -->
        <div class="flex items-center gap-3 mb-8 lg:hidden">
            <a href="/" class="size-12 rounded-xl bg-slate-50 p-2 border border-slate-200 shadow-xs flex items-center justify-center">
                <img src="/assets/img/logo.png" alt="Claret Crest" class="h-full w-full object-contain">
            </a>
            <div>
                <span class="block font-serif font-bold text-slate-900 text-base leading-tight">Claret International School</span>
                <span class="block text-[11px] font-semibold tracking-wider text-[#7B3046] uppercase">Admissions Portal</span>
            </div>
        </div>

        <?php if ($isOpen && $session): ?>
            <!-- ADMISSION IS OPEN -->
            <div class="mb-8">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200 mb-3.5">
                    <span class="size-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span>Admissions Currently Open</span>
                </div>
                
                <h1 class="font-serif text-2xl sm:text-3xl font-bold text-slate-900 tracking-tight">
                    <?= e($session->title) ?>
                </h1>
                
                <p class="text-sm text-slate-500 mt-2 leading-relaxed">
                    Applications are now open for Nursery, Primary, and Secondary education for the <?= e($session->academicSessionName ?? 'current') ?> academic session.
                </p>
            </div>

            <!-- Admission Session Parameters Card -->
            <div class="rounded-2xl bg-slate-50/80 border border-slate-200/80 p-5 mb-8 space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <span class="block text-[11px] font-bold uppercase tracking-wider text-slate-400">Application Fee</span>
                        <span class="text-2xl font-extrabold text-[#7B3046]"><?= e($session->getFormattedFee()) ?></span>
                        <span class="text-xs text-slate-500 block">per prospective ward</span>
                    </div>
                    <div>
                        <span class="block text-[11px] font-bold uppercase tracking-wider text-slate-400">Application Window</span>
                        <span class="text-sm font-semibold text-slate-800 block mt-0.5">
                            <?= date('M j, Y', strtotime($session->opensAt)) ?> – <?= date('M j, Y', strtotime($session->closesAt)) ?>
                        </span>
                        <span class="text-xs text-slate-500 block">Closing at <?= date('h:i A', strtotime($session->closesAt)) ?></span>
                    </div>
                </div>

                <?php if (!empty($session->instructions)): ?>
                    <div class="pt-3 border-t border-slate-200 text-xs text-slate-600 leading-relaxed">
                        <?= nl2br(e($session->instructions)) ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Action Buttons -->
            <div class="space-y-3">
                <a href="/apply/register" 
                   class="group relative w-full inline-flex items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-[#7B3046] via-[#8B3650] to-[#A33D5E] h-11 px-5 text-sm font-semibold text-white shadow-md shadow-[#7B3046]/20 transition-all duration-200 ease-out hover:shadow-lg hover:shadow-[#7B3046]/30 active:scale-[0.99] cursor-pointer">
                    <span>Start Online Application</span>
                    <span class="inline-flex items-center justify-center max-w-0 opacity-0 -translate-x-2 group-hover:max-w-[20px] group-hover:opacity-100 group-hover:translate-x-0 transition-all duration-200 ease-out overflow-hidden">
                        <i data-lucide="arrow-right" class="w-4 h-4 shrink-0" style="width:16px;height:16px;"></i>
                    </span>
                </a>

                <div class="text-center pt-2">
                    <p class="text-xs text-slate-500">
                        Already started an application? 
                        <a href="/login" class="font-bold text-[#7B3046] hover:underline ml-1">Sign in to your portal</a>
                    </p>
                </div>
            </div>

        <?php else: ?>
            <!-- ADMISSION IS CLOSED -->
            <div class="mb-8">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-semibold bg-amber-50 text-amber-800 border border-amber-200 mb-3.5">
                    <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                    <span>Admissions Closed</span>
                </div>
                
                <h1 class="font-serif text-2xl sm:text-3xl font-bold text-slate-900 tracking-tight">
                    Applications Currently Closed
                </h1>
                
                <p class="text-sm text-slate-500 mt-2 leading-relaxed">
                    Online prospective student applications are not being accepted at this time.
                </p>
            </div>

            <?php if ($session): ?>
                <div class="rounded-2xl bg-amber-50/50 border border-amber-200/80 p-5 mb-8 text-sm text-amber-900 space-y-2">
                    <p class="font-bold"><?= e($session->title) ?></p>
                    <p class="text-xs text-amber-800">
                        Configured Period: <?= date('F j, Y', strtotime($session->opensAt)) ?> to <?= date('F j, Y', strtotime($session->closesAt)) ?>.
                    </p>
                </div>
            <?php endif; ?>

            <div class="space-y-3">
                <a href="/login" 
                   class="group relative w-full inline-flex items-center justify-center gap-2 rounded-xl bg-slate-900 h-11 px-5 text-sm font-semibold text-white transition-all duration-200 hover:bg-slate-800 cursor-pointer">
                    <span>Sign In to Existing Account</span>
                    <span class="inline-flex items-center justify-center max-w-0 opacity-0 -translate-x-2 group-hover:max-w-[20px] group-hover:opacity-100 group-hover:translate-x-0 transition-all duration-200 ease-out overflow-hidden">
                        <i data-lucide="arrow-right" class="w-4 h-4 shrink-0" style="width:16px;height:16px;"></i>
                    </span>
                </a>

                <div class="text-center pt-2">
                    <a href="/" class="text-xs font-bold text-[#7B3046] hover:underline">
                        Return to Claret School Website
                    </a>
                </div>
            </div>
        <?php endif; ?>

        <!-- Footer Notice -->
        <div class="mt-10 pt-6 border-t border-slate-100 flex items-center justify-between text-xs text-slate-400">
            <span>&copy; <?= date('Y') ?> Claret International School</span>
            <a href="/" class="hover:text-slate-600 transition-colors">claret.edu</a>
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
