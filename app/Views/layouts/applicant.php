<?php
/**
 * Applicant / Prospective Parent Dedicated Minimalist Layout Shell
 * (SRS §10, Requirement 12: Intentionally simple sidebar)
 * 
 * @var string $content Injected view content
 * @var string|null $title Document title
 */
$userContextName = $_SESSION['user_name'] ?? 'Applicant';
$userContextEmail = $_SESSION['user_email'] ?? '';
$currentUri = $_SERVER['REQUEST_URI'] ?? '';
?>
<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-50">
<head>
    <?php $this->include('layouts/components/head', ['title' => $title ?? 'Admissions Portal — Claret']); ?>
</head>
<body class="h-full flex overflow-hidden bg-slate-50 font-sans text-slate-800 antialiased selection:bg-rose-100 selection:text-rose-900">

    <!-- Minimalist Sidebar for Prospective Applicants -->
    <aside class="w-64 bg-[#1E293B] text-slate-300 flex flex-col justify-between shrink-0 border-r border-slate-800 hidden md:flex">
        <div>
            <!-- School Brand Header -->
            <div class="h-20 flex items-center gap-3 px-6 border-b border-slate-800 bg-slate-950/40">
                <img src="/assets/img/logo.png" alt="Logo" class="size-10 object-contain drop-shadow">
                <div>
                    <span class="block font-serif font-bold text-white text-sm tracking-tight leading-tight">Claret International</span>
                    <span class="block text-[10px] font-semibold tracking-wider text-rose-400 uppercase">Admissions Portal</span>
                </div>
            </div>

            <!-- Simplified Navigation Menu -->
            <nav class="p-4 space-y-1.5" aria-label="Applicant Navigation">
                <div class="px-3 py-2 text-[10px] font-bold uppercase tracking-wider text-slate-500">
                    Application Workspace
                </div>

                <a href="/applicant/dashboard" 
                   class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs font-semibold transition-all <?= str_starts_with($currentUri, '/applicant/dashboard') ? 'bg-[#7B3046] text-white shadow-sm' : 'text-slate-300 hover:bg-slate-800 hover:text-white' ?>">
                    <i data-lucide="home" class="w-4 h-4 shrink-0" style="width:16px;height:16px;"></i>
                    <span>Dashboard / Home</span>
                </a>

                <a href="/applicant/application" 
                   class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs font-semibold transition-all <?= str_starts_with($currentUri, '/applicant/application') || str_starts_with($currentUri, '/applicant/wards') || str_starts_with($currentUri, '/applicant/payment') ? 'bg-[#7B3046] text-white shadow-sm' : 'text-slate-300 hover:bg-slate-800 hover:text-white' ?>">
                    <i data-lucide="file-text" class="w-4 h-4 shrink-0" style="width:16px;height:16px;"></i>
                    <span>Application Docket</span>
                </a>

                <a href="/applicant/progress" 
                   class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs font-semibold transition-all <?= str_starts_with($currentUri, '/applicant/progress') ? 'bg-[#7B3046] text-white shadow-sm' : 'text-slate-300 hover:bg-slate-800 hover:text-white' ?>">
                    <i data-lucide="clock" class="w-4 h-4 shrink-0" style="width:16px;height:16px;"></i>
                    <span>Milestone Progress</span>
                </a>
            </nav>
        </div>

        <!-- Guardian Identity & Logout -->
        <div class="p-4 border-t border-slate-800 bg-slate-950/20">
            <div class="px-3 py-2 mb-2">
                <span class="block text-xs font-bold text-white truncate"><?= e($userContextName) ?></span>
                <span class="block text-[11px] text-slate-400 truncate"><?= e($userContextEmail) ?></span>
                <span class="inline-block mt-1.5 px-2 py-0.5 rounded text-[10px] font-semibold bg-rose-500/20 text-rose-300 border border-rose-500/30">
                    Applicant Account
                </span>
            </div>

            <form action="/logout" method="POST" class="pt-1">
                <?= csrf_field() ?>
                <button type="submit" 
                        class="w-full flex items-center gap-2.5 px-3 py-2 rounded-lg text-xs font-semibold text-rose-400 hover:bg-rose-500/10 hover:text-rose-300 transition-colors cursor-pointer">
                    <i data-lucide="log-out" class="w-4 h-4" style="width:16px;height:16px;"></i>
                    <span>Sign Out</span>
                </button>
            </form>
        </div>
    </aside>

    <!-- Main View Area -->
    <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
        <!-- Top Navbar -->
        <header class="h-16 bg-white border-b border-slate-200 flex items-center justify-between px-6 shrink-0">
            <div class="flex items-center gap-3 md:hidden">
                <img src="/assets/img/logo.png" alt="Logo" class="size-8 object-contain">
                <span class="font-serif font-bold text-sm text-slate-900">Claret Admissions</span>
            </div>

            <div class="hidden sm:flex items-center gap-2 text-xs text-slate-500">
                <span>Academic Admissions</span>
                <span>/</span>
                <span class="font-semibold text-slate-800"><?= e($title ?? 'Portal') ?></span>
            </div>

            <div class="flex items-center gap-3">
                <a href="/" class="text-xs font-semibold text-[#7B3046] hover:underline flex items-center gap-1">
                    <span>Public School Website</span>
                    <i data-lucide="arrow-up-right" class="w-3.5 h-3.5" style="width:14px;height:14px;"></i>
                </a>
            </div>
        </header>

        <!-- Dynamic Body Content -->
        <main class="flex-1 overflow-y-auto p-4 sm:p-8 bg-slate-50">
            <div class="max-w-5xl mx-auto">
                <!-- Flash Messages -->
                <?php if (has_flash('success')): ?>
                    <div class="mb-5 rounded-xl bg-emerald-50 border border-emerald-200 p-4 text-sm text-emerald-800 flex items-center gap-3">
                        <i data-lucide="check-circle-2" class="w-5 h-5 text-emerald-600 shrink-0" style="width:20px;height:20px;"></i>
                        <span><?= e(flash('success')) ?></span>
                    </div>
                <?php endif; ?>

                <?php if (has_flash('error')): ?>
                    <div class="mb-5 rounded-xl bg-red-50 border border-red-200 p-4 text-sm text-red-800 flex items-center gap-3">
                        <i data-lucide="alert-circle" class="w-5 h-5 text-red-600 shrink-0" style="width:20px;height:20px;"></i>
                        <span><?= e(flash('error')) ?></span>
                    </div>
                <?php endif; ?>

                <?= $content ?? '' ?>
            </div>
        </main>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            if (window.lucide) {
                lucide.createIcons();
            }
        });
    </script>
</body>
</html>
