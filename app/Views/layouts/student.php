<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title ?? 'Student Learning Portal — Claret LMS') ?></title>
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
<body class="h-full bg-slate-50 text-slate-800 flex">
    <!-- Skip to content for accessibility -->
    <a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-4 bg-brand-600 text-white px-4 py-2 rounded-md z-50">
        Skip to main content
    </a>

    <!-- Sidebar Navigation -->
    <aside class="w-64 bg-slate-900 text-slate-200 flex flex-col flex-shrink-0 min-h-screen">
        <div class="p-5 border-b border-slate-800 flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg overflow-hidden bg-white flex items-center justify-center p-1 flex-shrink-0 shadow-sm">
                <img src="/assets/img/logo.png" alt="Claret Academy Logo" class="w-full h-full object-contain" onerror="this.parentElement.innerHTML='<span class=\'font-bold text-slate-900 text-sm\'>CL</span>'">
            </div>
            <div>
                <h2 class="font-bold text-white leading-tight">Claret Academy</h2>
                <span class="text-xs text-cyan-400 font-medium tracking-wide uppercase">Student Portal</span>
            </div>
        </div>

        <nav class="flex-1 p-4 space-y-1.5 overflow-y-auto" aria-label="Main Student Navigation">
            <a href="/student/dashboard" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-slate-300 hover:bg-slate-800 hover:text-white transition">
                <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                Dashboard
            </a>

            <div class="pt-4 pb-1 px-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                Academics & Learning
            </div>

            <a href="/student/subjects" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-slate-300 hover:bg-slate-800 hover:text-white transition">
                <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                My Enrolled Subjects
            </a>

            <a href="/student/content" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-slate-300 hover:bg-slate-800 hover:text-white transition">
                <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                Learning Materials
            </a>

            <a href="/student/timetable" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-slate-300 hover:bg-slate-800 hover:text-white transition">
                <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                Class Timetable
            </a>
        </nav>

        <!-- Student Profile Footer -->
        <div class="p-4 border-t border-slate-800 bg-slate-950 flex items-center justify-between">
            <div class="flex items-center gap-3 min-w-0">
                <div class="w-8 h-8 rounded-full bg-cyan-700 flex items-center justify-center text-white font-bold text-xs flex-shrink-0">
                    <?= e(substr($_SESSION['user_name'] ?? 'S', 0, 1)) ?>
                </div>
                <div class="truncate">
                    <p class="text-xs font-medium text-white truncate"><?= e($_SESSION['user_name'] ?? 'Student') ?></p>
                    <p class="text-[10px] text-slate-400 truncate"><?= e($_SESSION['user_email'] ?? '') ?></p>
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
        <!-- Top App Bar -->
        <header class="bg-white border-b border-slate-200 px-8 py-4 flex items-center justify-between flex-shrink-0">
            <div>
                <h1 class="text-xl font-bold text-slate-900 leading-tight"><?= e($headerTitle ?? 'Student Learning Center') ?></h1>
                <p class="text-xs text-slate-500"><?= e($headerSubtitle ?? 'Access lesson notes, lecture videos, and course documents') ?></p>
            </div>
            <div class="flex items-center gap-3">
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-cyan-50 text-cyan-700 border border-cyan-200">
                    <span class="w-1.5 h-1.5 rounded-full bg-cyan-500 mr-1.5"></span>
                    Enrolled Learner
                </span>
            </div>
        </header>

        <!-- Main Body -->
        <main id="main-content" class="flex-1 overflow-y-auto p-8 bg-slate-50">
            <!-- Flash Notifications -->
            <?php if (!empty($_SESSION['flash_success'])): ?>
                <div class="mb-6 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 flex items-start gap-3 shadow-sm">
                    <svg class="w-5 h-5 text-emerald-600 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                    <div class="text-sm font-medium"><?= e($_SESSION['flash_success']) ?></div>
                </div>
            <?php endif; ?>

            <?php if (!empty($_SESSION['flash_error'])): ?>
                <div class="mb-6 p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 flex items-start gap-3 shadow-sm">
                    <svg class="w-5 h-5 text-rose-600 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
                    <div class="text-sm font-medium"><?= e($_SESSION['flash_error']) ?></div>
                </div>
            <?php endif; ?>

            <!-- Injected View Content -->
            <?= $content ?? '' ?>
        </main>
    </div>
</body>
</html>
