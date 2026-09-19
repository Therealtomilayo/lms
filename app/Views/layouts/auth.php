<?php
/**
 * Authentication Layout Shell
 * 
 * @var string $content Injected view content
 * @var string|null $title Document title
 */
?>
<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-50">
<head>
    <?php $this->include('layouts/components/head', ['title' => $title ?? null]); ?>
</head>
<body class="min-h-full flex flex-col justify-center bg-slate-50 text-slate-800 antialiased selection:bg-rose-100 selection:text-rose-900">
    <!-- Skip to content accessibility helper -->
    <a href="#main-content" 
       class="sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-4 bg-[#7B3046] text-white px-4 py-2 rounded-full z-50 focus:ring-2 focus:ring-offset-2 focus:ring-[#7B3046] text-xs font-semibold shadow-lg">
        Skip to main content
    </a>

    <?php if (!empty($wideLayout)): ?>
        <!-- Modern Wide Responsive Container (e.g. Split-Screen Hero Cards) -->
        <main id="main-content" class="w-full flex-1 flex flex-col justify-center py-6 sm:py-10 px-4 sm:px-6 lg:px-8 focus:outline-none" tabindex="-1">
            <!-- Global Flash Alerts -->
            <div class="mx-auto w-full max-w-5xl mb-4">
                <?php if (has_flash('success')): ?>
                    <div class="mb-3">
                        <?php $this->include('components/alert', ['type' => 'success', 'message' => e(flash('success')), 'dismissible' => true]); ?>
                    </div>
                <?php endif; ?>

                <?php if (has_flash('error')): ?>
                    <div class="mb-3">
                        <?php $this->include('components/alert', ['type' => 'error', 'message' => e(flash('error')), 'dismissible' => true]); ?>
                    </div>
                <?php endif; ?>

                <?php if (has_flash('warning')): ?>
                    <div class="mb-3">
                        <?php $this->include('components/alert', ['type' => 'warning', 'message' => e(flash('warning')), 'dismissible' => true]); ?>
                    </div>
                <?php endif; ?>
            </div>

            <?= $content ?? '' ?>
        </main>
    <?php else: ?>
        <!-- Standard Centered Card Shell for Simpler Password Reset & Single Forms -->
        <div class="sm:mx-auto sm:w-full sm:max-w-md text-center pt-8">
            <a href="/" class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-white p-2.5 shadow-sm ring-1 ring-slate-200 hover:scale-105 transition-transform duration-200 mb-4">
                <img src="/assets/img/logo.png" alt="Claret International School Crest" class="w-full h-full object-contain">
            </a>
            <h1 class="font-serif text-2xl font-bold tracking-tight text-slate-900 leading-tight">Claret International School</h1>
            <p class="text-xs font-semibold uppercase tracking-wider text-rose-700 mt-1">Institutional Portal</p>
        </div>

        <main id="main-content" class="mt-6 sm:mx-auto sm:w-full sm:max-w-md px-4 sm:px-0 focus:outline-none" tabindex="-1">
            <!-- Global Flash Alerts -->
            <?php if (has_flash('success')): ?>
                <div class="mb-4">
                    <?php $this->include('components/alert', ['type' => 'success', 'message' => e(flash('success')), 'dismissible' => true]); ?>
                </div>
            <?php endif; ?>

            <?php if (has_flash('error')): ?>
                <div class="mb-4">
                    <?php $this->include('components/alert', ['type' => 'error', 'message' => e(flash('error')), 'dismissible' => true]); ?>
                </div>
            <?php endif; ?>

            <?php if (has_flash('warning')): ?>
                <div class="mb-4">
                    <?php $this->include('components/alert', ['type' => 'warning', 'message' => e(flash('warning')), 'dismissible' => true]); ?>
                </div>
            <?php endif; ?>

            <!-- Form Card Wrapper -->
            <div class="bg-white py-8 px-6 shadow-xl shadow-slate-200/50 border border-slate-200/80 rounded-2xl sm:px-10">
                <?= $content ?? '' ?>
            </div>
        </main>

        <footer class="mt-8 mb-6 text-center text-xs text-slate-400">
            &copy; <?= date('Y') ?> Claret International School LMS. All rights reserved.
        </footer>
    <?php endif; ?>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            if (window.lucide) {
                window.lucide.createIcons();
            }
        });
    </script>
</body>
</html>
