<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title ?? 'Claret LMS') ?></title>
    <!-- Tailwind CSS (compiled typography and colors adhering to 08-ui-design-system.md) -->
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
<body class="h-full flex flex-col justify-center py-12 sm:px-6 lg:px-8 bg-slate-50 text-slate-800">
    <!-- Skip to content for accessibility -->
    <a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-4 bg-brand-600 text-white px-4 py-2 rounded-md z-50">
        Skip to main content
    </a>

    <div class="sm:mx-auto sm:w-full sm:max-w-md text-center">
        <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-white p-2 shadow-sm border border-slate-200 mb-4">
            <img src="/assets/img/logo.png" alt="Claret Academy Logo" class="w-full h-full object-contain" onerror="this.parentElement.innerHTML='<span class=\'font-bold text-brand-700 text-xl\'>CL</span>'">
        </div>
        <h1 class="text-2xl font-bold tracking-tight text-slate-900">Claret Academy</h1>
        <p class="text-sm text-slate-600 mt-1">Learning Management System</p>
    </div>

    <main id="main-content" class="mt-8 sm:mx-auto sm:w-full sm:max-w-md px-4 sm:px-0">
        <!-- Global Flash Messages -->
        <?php if (has_flash('success')): ?>
            <div role="status" class="mb-4 rounded-lg bg-success-100 p-4 border border-green-200 text-success-700 flex items-start gap-3">
                <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                <div class="text-sm font-medium"><?= e(flash('success')) ?></div>
            </div>
        <?php endif; ?>

        <?php if (has_flash('error')): ?>
            <div role="alert" class="mb-4 rounded-lg bg-danger-100 p-4 border border-red-200 text-danger-700 flex items-start gap-3">
                <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                </svg>
                <div class="text-sm font-medium"><?= e(flash('error')) ?></div>
            </div>
        <?php endif; ?>

        <?php if (has_flash('warning')): ?>
            <div role="alert" class="mb-4 rounded-lg bg-warning-100 p-4 border border-amber-200 text-warning-800 flex items-start gap-3">
                <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                </svg>
                <div class="text-sm font-medium"><?= e(flash('warning')) ?></div>
            </div>
        <?php endif; ?>

        <div class="bg-white py-8 px-6 shadow-sm border border-slate-200 rounded-xl sm:px-10">
            <?= $content ?? '' ?>
        </div>
    </main>

    <footer class="mt-8 text-center text-xs text-slate-500">
        &copy; <?= date('Y') ?> Claret Academy LMS. All rights reserved.
    </footer>
</body>
</html>
