<?php 
$isPublication = !empty($message) && (str_contains(strtolower($message), 'publication') || str_contains(strtolower($message), 'published') || str_contains(strtolower($message), 'released'));
$pageTitle = $isPublication ? 'Results Pending Publication — Claret LMS' : '403 — Access Restricted';
$this->layout('layouts/auth', ['title' => $pageTitle]); 
?>

<div class="text-center py-2 space-y-4">
    <?php if ($isPublication): ?>
        <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-amber-50 text-amber-600 border border-amber-200 shadow-xs mb-1">
            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>
        <div>
            <span class="inline-block px-3 py-1 rounded-full bg-amber-100 text-amber-800 text-[10px] font-black uppercase tracking-wider">
                Pending Institutional Release
            </span>
            <h2 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight leading-snug mt-1">
                Terminal Results Not Yet Released
            </h2>
        </div>
        <div class="bg-amber-50/70 border border-amber-200/80 rounded-xl p-4 text-xs text-amber-900 leading-relaxed text-left space-y-2">
            <div class="flex items-start gap-2.5">
                <svg class="w-4 h-4 text-amber-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <div class="flex-1 font-medium">
                    <?= e($message ?? 'Terminal results and report cards are currently undergoing moderation and review by school administration.') ?>
                </div>
            </div>
            <p class="text-[11px] text-amber-800 font-normal pl-6">
                Once grades have been approved and published by school leadership, report cards will be immediately accessible here.
            </p>
        </div>
    <?php else: ?>
        <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-rose-50 text-rose-600 border border-rose-200 shadow-xs mb-1">
            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
            </svg>
        </div>
        <div>
            <span class="inline-block px-3 py-1 rounded-full bg-rose-100 text-rose-800 text-[10px] font-black uppercase tracking-wider">
                Authorization Restricted
            </span>
            <h2 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight leading-snug mt-1">
                Access Denied
            </h2>
        </div>
        <div class="bg-rose-50/70 border border-rose-200/80 rounded-xl p-4 text-xs text-rose-900 leading-relaxed text-left flex items-start gap-2.5">
            <svg class="w-4 h-4 text-rose-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
            <div class="flex-1 font-medium">
                <?= e($message ?? 'You do not have administrative or parental permissions to view this resource.') ?>
            </div>
        </div>
    <?php endif; ?>

    <div class="pt-2 flex flex-col sm:flex-row items-center justify-center gap-3">
        <a href="<?= e($returnUrl ?? '/dashboard') ?>" 
           class="w-full sm:w-auto inline-flex items-center justify-center gap-2 py-2.5 px-6 rounded-xl text-xs font-black text-white bg-[#7B3046] hover:bg-[#8D3850] shadow-sm transition active:scale-95 cursor-pointer">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            <span>Return to Overview</span>
        </a>
        <a href="/dashboard" 
           class="w-full sm:w-auto inline-flex items-center justify-center gap-2 py-2.5 px-5 rounded-xl text-xs font-bold text-slate-700 bg-slate-100 hover:bg-slate-200 border border-slate-200 transition">
            <span>Main Dashboard</span>
        </a>
    </div>
</div>

