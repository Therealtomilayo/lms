<?php
use App\Core\Session;

$userContext = $user ?? null;
$linkedChildren = $children ?? [];
$activeChild = $selectedChild ?? (!empty($linkedChildren) ? $linkedChildren[0] : null);
$activeChildId = $activeChild ? (int)$activeChild->id : 0;
$currentUri = $_SERVER['REQUEST_URI'] ?? '';
?>
<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title ?? 'Guardian Portal — Claret LMS') ?></title>
    <!-- Tailwind CSS (compiled tokens adhering to 08-ui-design-system.md) -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            100: '#DBEAFE',
                            500: '#3B82F6',
                            600: '#0C9DD5',
                            700: '#7B3046',
                        },
                        accent: {
                            500: '#C3456B',
                            600: '#C3456B',
                        },
                        success: {
                            100: '#DCFCE7',
                            700: '#15803D',
                        },
                        warning: {
                            100: '#FEF3C7',
                            800: '#92400E',
                        },
                        danger: {
                            100: '#FEE2E2',
                            700: '#B91C1C',
                        },
                        info: {
                            100: '#E0F2FE',
                            700: '#0369A1',
                        }
                    },
                    fontFamily: {
                        sans: ['Roboto', 'system-ui', '-apple-system', 'BlinkMacSystemFont', 'Segoe UI', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    <style>
        body { font-family: Roboto, system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; }
    </style>
</head>
<body class="h-full bg-slate-50 text-slate-800 flex flex-col md:flex-row">
    <!-- Skip to content for accessibility -->
    <a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-4 bg-brand-600 text-white px-4 py-2 rounded-md z-50">
        Skip to main content
    </a>

    <!-- Mobile Top Navigation Header -->
    <div class="md:hidden bg-slate-900 text-white p-4 flex items-center justify-between border-b border-slate-800">
        <div class="flex items-center gap-3">
            <div class="w-8 h-8 rounded bg-brand-700 flex items-center justify-center font-bold text-white text-xs">
                CL
            </div>
            <div>
                <span class="font-bold text-sm block leading-tight">Claret LMS</span>
                <span class="text-[10px] text-brand-600 uppercase font-semibold">Guardian Portal</span>
            </div>
        </div>
        <button type="button" onclick="document.getElementById('mobile-drawer').classList.toggle('hidden')" class="p-2 text-slate-300 hover:text-white focus:outline-none">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
        </button>
    </div>

    <!-- Sidebar Navigation -->
    <aside id="mobile-drawer" class="hidden md:flex w-full md:w-72 bg-slate-900 text-slate-200 flex-col flex-shrink-0 min-h-screen border-r border-slate-800">
        <!-- Brand Header -->
        <div class="p-5 border-b border-slate-800 flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg overflow-hidden bg-brand-700 flex items-center justify-center p-1 flex-shrink-0 shadow-sm">
                <span class="font-bold text-white text-sm">CL</span>
            </div>
            <div>
                <h2 class="font-bold text-white text-base leading-tight">Claret Academy</h2>
                <span class="text-xs text-brand-600 font-medium tracking-wide uppercase">Guardian Portal</span>
            </div>
        </div>

        <!-- Multi-Child Context Switcher -->
        <?php if (!empty($linkedChildren)): ?>
            <div class="p-4 border-b border-slate-800 bg-slate-950/60">
                <label for="child-switcher-select" class="block text-[11px] font-semibold text-slate-400 uppercase tracking-wider mb-2">
                    Viewing Student Context:
                </label>
                <form action="/parent/children/<?= $activeChildId ?>/select" method="POST" id="child-switcher-form">
                    <?= csrf_field() ?>
                    <input type="hidden" name="redirect_to" value="<?= e($currentUri) ?>">
                    <div class="relative">
                        <select id="child-switcher-select" name="student_id" onchange="this.form.action='/parent/children/' + this.value + '/select'; this.form.submit();"
                                class="w-full bg-slate-800 border border-slate-700 text-white text-xs rounded-lg px-3 py-2.5 appearance-none focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 cursor-pointer pr-8 font-medium">
                            <?php foreach ($linkedChildren as $child): ?>
                                <option value="<?= (int)$child->id ?>" <?= $child->id === $activeChildId ? 'selected' : '' ?>>
                                    <?= e($child->name) ?> (<?= e($child->className ?: $child->admissionNumber) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-slate-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </div>
                    </div>
                </form>
                <?php if ($activeChild): ?>
                    <div class="mt-2.5 flex items-center justify-between text-[11px] text-slate-400 px-1">
                        <span>Adm: <strong class="text-slate-200"><?= e($activeChild->admissionNumber) ?></strong></span>
                        <span>Class: <strong class="text-brand-600"><?= e($activeChild->className ?: 'Assigned') ?></strong></span>
                    </div>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="p-4 border-b border-slate-800 bg-slate-950/40 text-xs text-slate-400">
                <p class="font-medium text-slate-300">No linked students found.</p>
                <p class="text-[11px] mt-1 text-slate-500">Contact the school administration to link your student profile.</p>
            </div>
        <?php endif; ?>

        <!-- Portal Navigation Menu -->
        <nav class="flex-1 p-4 space-y-1.5 overflow-y-auto" aria-label="Main Guardian Navigation">
            <a href="/parent/dashboard" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition <?= str_starts_with($currentUri, '/parent/dashboard') ? 'text-white bg-slate-800 border-l-4 border-brand-600' : 'text-slate-300 hover:bg-slate-800 hover:text-white' ?>">
                <svg class="w-5 h-5 <?= str_starts_with($currentUri, '/parent/dashboard') ? 'text-brand-600' : 'text-slate-400' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                Overview Dashboard
            </a>

            <?php if ($activeChildId > 0): ?>
                <div class="pt-4 pb-1 px-3 text-[11px] font-semibold text-slate-500 uppercase tracking-wider">
                    Student Monitoring
                </div>

                <a href="/parent/children/<?= $activeChildId ?>" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition <?= preg_match('#^/parent/children/\d+$#', $currentUri) ? 'text-white bg-slate-800 border-l-4 border-brand-600' : 'text-slate-300 hover:bg-slate-800 hover:text-white' ?>">
                    <svg class="w-5 h-5 <?= preg_match('#^/parent/children/\d+$#', $currentUri) ? 'text-brand-600' : 'text-slate-400' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    Child Academic Profile
                </a>

                <a href="/parent/children/<?= $activeChildId ?>/grades" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition <?= str_contains($currentUri, '/grades') ? 'text-white bg-slate-800 border-l-4 border-brand-600' : 'text-slate-300 hover:bg-slate-800 hover:text-white' ?>">
                    <svg class="w-5 h-5 <?= str_contains($currentUri, '/grades') ? 'text-brand-600' : 'text-slate-400' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    Grades & Report Cards
                </a>

                <a href="/parent/children/<?= $activeChildId ?>/attendance" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition <?= str_contains($currentUri, '/attendance') ? 'text-white bg-slate-800 border-l-4 border-brand-600' : 'text-slate-300 hover:bg-slate-800 hover:text-white' ?>">
                    <svg class="w-5 h-5 <?= str_contains($currentUri, '/attendance') ? 'text-brand-600' : 'text-slate-400' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    Daily Attendance
                </a>

                <a href="/parent/children/<?= $activeChildId ?>/assignments" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition <?= str_contains($currentUri, '/assignments') ? 'text-white bg-slate-800 border-l-4 border-brand-600' : 'text-slate-300 hover:bg-slate-800 hover:text-white' ?>">
                    <svg class="w-5 h-5 <?= str_contains($currentUri, '/assignments') ? 'text-brand-600' : 'text-slate-400' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
                    Coursework & Tasks
                </a>

                <a href="/parent/children/<?= $activeChildId ?>/announcements" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition <?= str_contains($currentUri, '/announcements') ? 'text-white bg-slate-800 border-l-4 border-brand-600' : 'text-slate-300 hover:bg-slate-800 hover:text-white' ?>">
                    <svg class="w-5 h-5 <?= str_contains($currentUri, '/announcements') ? 'text-brand-600' : 'text-slate-400' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                    Announcements
                </a>

                <a href="/parent/children/<?= $activeChildId ?>/timetable" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition <?= str_contains($currentUri, '/timetable') ? 'text-white bg-slate-800 border-l-4 border-brand-600' : 'text-slate-300 hover:bg-slate-800 hover:text-white' ?>">
                    <svg class="w-5 h-5 <?= str_contains($currentUri, '/timetable') ? 'text-brand-600' : 'text-slate-400' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    Weekly Timetable
                </a>
            <?php endif; ?>
        </nav>

        <!-- Guardian Account Footer -->
        <div class="p-4 border-t border-slate-800 bg-slate-950 flex items-center justify-between">
            <div class="flex items-center gap-3 min-w-0">
                <div class="w-8 h-8 rounded-full bg-brand-700 flex items-center justify-center text-white font-bold text-xs flex-shrink-0">
                    <?= e(substr($userContext?->name ?? $_SESSION['user_name'] ?? 'G', 0, 1)) ?>
                </div>
                <div class="truncate">
                    <p class="text-xs font-medium text-white truncate"><?= e($userContext?->name ?? $_SESSION['user_name'] ?? 'Guardian') ?></p>
                    <p class="text-[10px] text-slate-400 truncate"><?= e($userContext?->email ?? $_SESSION['user_email'] ?? '') ?></p>
                </div>
            </div>
            <form action="/logout" method="POST" class="inline">
                <?= csrf_field() ?>
                <button type="submit" title="Logout" class="p-1.5 text-slate-400 hover:text-rose-400 rounded-lg hover:bg-slate-800 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                </button>
            </form>
        </div>
    </aside>

    <!-- Main Content Area -->
    <div class="flex-1 flex flex-col min-h-screen overflow-hidden">
        <!-- Top Navigation Bar -->
        <header class="bg-white border-b border-slate-200 px-6 py-3.5 flex items-center justify-between flex-shrink-0 shadow-sm">
            <div class="flex items-center gap-3 min-w-0">
                <div>
                    <h1 class="text-lg font-bold text-slate-900 leading-tight truncate"><?= e($headerTitle ?? $title ?? 'Guardian Portal') ?></h1>
                    <p class="text-xs text-slate-500 hidden sm:block"><?= e($headerSubtitle ?? 'Monitor and track your child\'s academic growth, daily attendance, and reports') ?></p>
                </div>
            </div>

            <div class="flex items-center gap-3 flex-shrink-0">
                <?php if ($activeChild): ?>
                    <div class="hidden sm:flex items-center gap-2 px-3 py-1.5 bg-slate-100 border border-slate-200 rounded-full text-xs">
                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                        <span class="text-slate-600 font-medium">Viewing:</span>
                        <strong class="text-slate-900 font-semibold"><?= e($activeChild->name) ?></strong>
                    </div>
                <?php endif; ?>
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-brand-100 text-brand-700 border border-blue-200">
                    Parent / Guardian
                </span>
            </div>
        </header>

        <!-- Main Body -->
        <main id="main-content" class="flex-1 overflow-y-auto p-6 md:p-8 bg-slate-50">
            <!-- Flash Notifications -->
            <?php 
            $flashSuccess = Session::getFlash('success') ?? ($_SESSION['flash_success'] ?? null);
            $flashError = Session::getFlash('error') ?? ($_SESSION['flash_error'] ?? null);
            $flashWarning = Session::getFlash('warning') ?? ($_SESSION['flash_warning'] ?? null);
            ?>

            <?php if (!empty($flashSuccess)): ?>
                <div class="mb-6 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 flex items-start gap-3 shadow-sm">
                    <svg class="w-5 h-5 text-emerald-600 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                    <div class="text-sm font-medium"><?= e($flashSuccess) ?></div>
                </div>
            <?php endif; ?>

            <?php if (!empty($flashError)): ?>
                <div class="mb-6 p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 flex items-start gap-3 shadow-sm">
                    <svg class="w-5 h-5 text-rose-600 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
                    <div class="text-sm font-medium"><?= e($flashError) ?></div>
                </div>
            <?php endif; ?>

            <?php if (!empty($flashWarning)): ?>
                <div class="mb-6 p-4 rounded-xl bg-amber-50 border border-amber-200 text-amber-800 flex items-start gap-3 shadow-sm">
                    <svg class="w-5 h-5 text-amber-600 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                    <div class="text-sm font-medium"><?= e($flashWarning) ?></div>
                </div>
            <?php endif; ?>

            <!-- Injected View Content -->
            <?= $content ?? '' ?>
        </main>
    </div>
</body>
</html>
