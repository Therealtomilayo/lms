<?php
/**
 * Master Application Shell Layout
 * 
 * @var string $content Child template output
 * @var string $role Role code (admin|teacher|student|parent)
 * @var string $roleLabel Role human display label
 * @var string|null $roleBadgeColor Role badge color CSS class (optional)
 * @var string|null $title Document title
 * @var string|null $headerTitle Section header title
 * @var string|null $headerSubtitle Section header subtitle
 */
?>
<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-50">
<head>
    <?php $this->include('layouts/components/head', ['title' => $title ?? null]); ?>
</head>
<body class="h-full bg-slate-50 text-slate-800 flex flex-col md:flex-row overflow-x-hidden print:block print:h-auto print:overflow-visible print:bg-white print:p-0">
    <!-- Skip to main content accessibility link -->
    <a href="#main-content" 
       class="sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-4 bg-brand-600 text-white px-4 py-2 rounded-md z-50 focus:ring-2 focus:ring-offset-2 focus:ring-brand-500">
        Skip to main content
    </a>

    <!-- Unified Sidebar Navigation -->
    <?php $this->include('layouts/components/sidebar', [
        'role' => $role,
        'roleLabel' => $roleLabel,
        'roleBadgeColor' => $roleBadgeColor ?? null,
        'children' => $children ?? [],
        'selectedChild' => $selectedChild ?? null
    ]); ?>

    <!-- Main View Panel -->
    <div class="flex-1 flex flex-col min-h-screen overflow-hidden print:block print:min-h-0 print:overflow-visible print:w-full">
        
        <!-- Unified Header -->
        <?php $this->include('layouts/components/header', [
            'role' => $role,
            'roleLabel' => $roleLabel,
            'headerTitle' => $headerTitle ?? $title ?? 'Dashboard',
            'headerSubtitle' => $headerSubtitle ?? null,
            'activeChild' => $selectedChild ?? (!empty($children) ? $children[0] : null)
        ]); ?>

        <!-- Content Area -->
        <main id="main-content" class="flex-1 overflow-y-auto p-6 md:p-8 bg-slate-50 focus:outline-none print:block print:overflow-visible print:p-0 print:m-0 print:bg-white print:w-full" tabindex="-1">
            
            <!-- Global Flash Messages -->
            <?php 
            use App\Core\Session;
            $flashSuccess = Session::getFlash('success') ?? ($_SESSION['flash_success'] ?? null);
            $flashError = Session::getFlash('error') ?? ($_SESSION['flash_error'] ?? null);
            $flashWarning = Session::getFlash('warning') ?? ($_SESSION['flash_warning'] ?? null);
            $flashInfo = Session::getFlash('info') ?? ($_SESSION['flash_info'] ?? null);
            $flashErrors = Session::getFlash('errors') ?? ($_SESSION['flash_errors'] ?? []);
            ?>

            <?php if (!empty($flashSuccess)): ?>
                <div class="mb-6">
                    <?php $this->include('components/alert', ['type' => 'success', 'message' => e($flashSuccess), 'dismissible' => true]); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($flashError)): ?>
                <div class="mb-6">
                    <?php $this->include('components/alert', ['type' => 'error', 'message' => e($flashError), 'dismissible' => true]); ?>
                </div>
            <?php elseif (!empty($flashErrors)): ?>
                <div class="mb-6 space-y-2">
                    <?php foreach ($flashErrors as $errVal): ?>
                        <?php if (is_array($errVal)): ?>
                            <?php foreach ($errVal as $errMsg): ?>
                                <?php $this->include('components/alert', ['type' => 'error', 'message' => e($errMsg), 'dismissible' => true]); ?>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <?php $this->include('components/alert', ['type' => 'error', 'message' => e($errVal), 'dismissible' => true]); ?>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($flashWarning)): ?>
                <div class="mb-6">
                    <?php $this->include('components/alert', ['type' => 'warning', 'message' => e($flashWarning), 'dismissible' => true]); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($flashInfo)): ?>
                <div class="mb-6">
                    <?php $this->include('components/alert', ['type' => 'info', 'message' => e($flashInfo), 'dismissible' => true]); ?>
                </div>
            <?php endif; ?>

            <!-- Injected View Page Content -->
            <?= $content ?? '' ?>

        </main>
    </div>

    <!-- Reusable Vanilla Modal, Sidebar & Overlay Javascript Utility -->
    <script>
        window.LMS = {
            showModal(id) {
                const modal = document.getElementById(id);
                if (modal) {
                    modal.classList.remove('hidden');
                    modal.classList.add('flex');
                    document.body.classList.add('overflow-hidden');
                    // Focus on the first interactive element or close button for accessibility
                    const focusable = modal.querySelectorAll('button, [href], input, select, textarea, [tabindex="0"]');
                    if (focusable.length > 0) {
                        focusable[0].focus();
                    }
                }
            },
            hideModal(id) {
                const modal = document.getElementById(id);
                if (modal) {
                    modal.classList.remove('flex');
                    modal.classList.add('hidden');
                    document.body.classList.remove('overflow-hidden');
                }
            },
            toggleSidebar(show) {
                const sidebar = document.getElementById('sidebar-navigation');
                const backdrop = document.getElementById('sidebar-backdrop');
                if (!sidebar) return;

                const isCurrentlyHidden = sidebar.classList.contains('hidden');
                const shouldOpen = show !== undefined ? Boolean(show) : isCurrentlyHidden;

                if (shouldOpen) {
                    sidebar.classList.remove('hidden');
                    sidebar.classList.add('flex');
                    if (backdrop) {
                        backdrop.classList.remove('hidden');
                    }
                    document.body.classList.add('overflow-hidden', 'md:overflow-auto');
                } else {
                    sidebar.classList.add('hidden');
                    sidebar.classList.remove('flex');
                    if (backdrop) {
                        backdrop.classList.add('hidden');
                    }
                    document.body.classList.remove('overflow-hidden', 'md:overflow-auto');
                }
            }
        };

        // Listen for ESC key to close any active modal or mobile sidebar safely
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                const openModals = document.querySelectorAll('.lms-modal:not(.hidden)');
                openModals.forEach(modal => window.LMS.hideModal(modal.id));

                if (window.innerWidth < 768) {
                    window.LMS.toggleSidebar(false);
                }
            }
        });

        // Universal Password Visibility Eye Toggle across all password inputs
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

        document.addEventListener('DOMContentLoaded', initUniversalPasswordToggles);
        initUniversalPasswordToggles();
    </script>
</body>
</html>
