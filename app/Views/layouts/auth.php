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
            initUniversalPasswordToggles();
        });

        function initUniversalPasswordToggles() {
            document.querySelectorAll('input[type="password"]').forEach(input => {
                if (input.dataset.toggleInitialized) return;
                input.dataset.toggleInitialized = 'true';
                if (input.nextElementSibling && (input.nextElementSibling.id === 'toggle-password-btn' || input.nextElementSibling.classList.contains('lms-password-toggle-btn'))) {
                    return;
                }
                if (input.parentElement && input.parentElement.querySelector('#toggle-password-btn, .lms-password-toggle-btn')) {
                    return;
                }
                const parent = input.parentElement;
                if (!parent.classList.contains('relative')) {
                    const wrapper = document.createElement('div');
                    wrapper.className = 'relative w-full';
                    parent.insertBefore(wrapper, input);
                    wrapper.appendChild(input);
                }
                input.classList.add('pr-11');
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'lms-password-toggle-btn absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-slate-600 focus:outline-none cursor-pointer';
                btn.setAttribute('aria-label', 'Toggle password visibility');
                btn.innerHTML = '<svg class="eye-open w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg><svg class="eye-closed w-4 h-4 hidden text-[#7B3046]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"/></svg>';
                input.parentElement.appendChild(btn);
            });
        }

        document.addEventListener('click', function(e) {
            const btn = e.target.closest('.lms-password-toggle-btn');
            if (!btn) return;
            e.preventDefault();
            const container = btn.closest('.relative') || btn.parentElement;
            const input = container.querySelector('input');
            if (!input) return;
            const isPassword = input.type === 'password';
            input.type = isPassword ? 'text' : 'password';
            const eyeOpen = btn.querySelector('.eye-open');
            const eyeClosed = btn.querySelector('.eye-closed');
            if (eyeOpen && eyeClosed) {
                if (isPassword) {
                    eyeOpen.classList.add('hidden');
                    eyeClosed.classList.remove('hidden');
                } else {
                    eyeOpen.classList.remove('hidden');
                    eyeClosed.classList.add('hidden');
                }
            }
        });
    </script>
</body>
</html>
