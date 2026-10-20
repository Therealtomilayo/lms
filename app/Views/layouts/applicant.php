<?php
/**
 * Applicant / Prospective Parent Dedicated Minimalist Layout Shell
 * (Requirement 12: Intentionally simple sidebar)
 * 
 * @var string $content Injected view content
 * @var string|null $title Document title
 */
$userContextName = $_SESSION['user_name'] ?? 'Applicant';
$userContextEmail = $_SESSION['user_email'] ?? '';
$userRoles = $_SESSION['user_roles'] ?? [];
$isParent = in_array('parent', $userRoles, true);
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

                <a href="/payments/history" 
                   class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs font-semibold transition-all <?= str_starts_with($currentUri, '/payments/history') ? 'bg-[#7B3046] text-white shadow-sm' : 'text-slate-300 hover:bg-slate-800 hover:text-white' ?>">
                    <i data-lucide="receipt" class="w-4 h-4 shrink-0" style="width:16px;height:16px;"></i>
                    <span>Payment History</span>
                </a>

                <?php if ($isParent): ?>
                    <div class="pt-2">
                        <a href="/parent/dashboard" 
                           class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs font-semibold text-emerald-400 bg-emerald-950/40 border border-emerald-800/50 hover:bg-emerald-900/50 transition-all">
                            <i data-lucide="layout-dashboard" class="w-4 h-4 shrink-0 text-emerald-400" style="width:16px;height:16px;"></i>
                            <span>Switch to Parent Portal</span>
                        </a>
                    </div>
                <?php endif; ?>
            </nav>
        </div>

        <!-- Guardian Identity & Logout -->
        <div class="p-4 border-t border-slate-800 bg-slate-950/20">
            <div class="px-3 py-2 mb-2">
                <span class="block text-xs font-bold text-white truncate"><?= e($userContextName) ?></span>
                <span class="block text-[11px] text-slate-400 truncate"><?= e($userContextEmail) ?></span>
                <span class="inline-block mt-1.5 px-2 py-0.5 rounded text-[10px] font-semibold <?= $isParent ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30' : 'bg-rose-500/20 text-rose-300 border border-rose-500/30' ?>">
                    <?= $isParent ? 'Parent &amp; Applicant' : 'Applicant Account' ?>
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
                <a href="/" class="hidden sm:flex text-xs font-semibold text-[#7B3046] hover:underline items-center gap-1">
                    <span>Public School Website</span>
                    <i data-lucide="arrow-up-right" class="w-3.5 h-3.5" style="width:14px;height:14px;"></i>
                </a>

                <!-- Role Badge / Portal Switcher for Applicant -->
                <div class="relative" id="applicant-switcher-container">
                    <button type="button" 
                            id="applicant-switcher-btn"
                            onclick="document.getElementById('applicant-switcher-dropdown').classList.toggle('hidden')" 
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold bg-rose-50 hover:bg-rose-100 text-rose-800 border border-rose-200 transition cursor-pointer shadow-xs focus:outline-none focus:ring-2 focus:ring-rose-500"
                            title="User session & portal menu">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                        <span><?= $isParent ? 'Parent &amp; Applicant' : 'Admissions Portal' ?></span>
                        <svg class="w-3.5 h-3.5 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>

                    <!-- Dropdown Menu -->
                    <div id="applicant-switcher-dropdown" 
                         class="hidden absolute right-0 mt-2 w-56 rounded-2xl bg-white shadow-xl border border-slate-200 py-2 z-50 divide-y divide-slate-100">
                        <div class="px-3.5 py-2">
                            <p class="text-xs font-bold text-slate-900 truncate"><?= e($userContextName) ?></p>
                            <p class="text-[10px] text-slate-500 truncate"><?= e($userContextEmail) ?></p>
                            <div class="mt-1.5 inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-bold bg-rose-50 text-rose-800 border border-rose-200">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                <span><?= $isParent ? 'Parent &amp; Applicant' : 'Applicant Account' ?></span>
                            </div>
                        </div>

                        <?php if ($isParent): ?>
                            <div class="py-1">
                                <a href="/parent/dashboard" class="flex items-center gap-2 px-3.5 py-1.5 text-xs text-emerald-700 hover:bg-emerald-50 font-semibold transition">
                                    <i data-lucide="layout-dashboard" class="w-4 h-4 text-emerald-600"></i>
                                    <span>Switch to Parent Portal</span>
                                </a>
                            </div>
                        <?php endif; ?>

                        <div class="p-1">
                            <form action="/logout" method="POST" class="w-full">
                                <?= csrf_field() ?>
                                <button type="submit" 
                                        class="w-full flex items-center gap-2 px-3 py-1.5 text-xs font-bold text-rose-600 hover:bg-rose-50 rounded-lg transition cursor-pointer">
                                    <svg class="w-4 h-4 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                                    <span>Sign Out / Log Out</span>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Quick Logout Button -->
                <form action="/logout" method="POST" class="inline-flex">
                    <?= csrf_field() ?>
                    <button type="submit" 
                            title="Sign Out / Log Out" 
                            class="p-2 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-xl transition flex items-center justify-center cursor-pointer min-w-[36px] min-h-[36px]">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                    </button>
                </form>

                <script>
                document.addEventListener('click', function(e) {
                    const c = document.getElementById('applicant-switcher-container');
                    const d = document.getElementById('applicant-switcher-dropdown');
                    if (c && d && !c.contains(e.target)) {
                        d.classList.add('hidden');
                    }
                });
                </script>
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
