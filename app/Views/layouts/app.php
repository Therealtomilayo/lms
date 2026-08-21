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
<body class="h-full bg-slate-50 text-slate-800 flex flex-col md:flex-row overflow-x-hidden">
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
    <div class="flex-1 flex flex-col min-h-screen overflow-hidden">
        
        <!-- Unified Header -->
        <?php $this->include('layouts/components/header', [
            'role' => $role,
            'roleLabel' => $roleLabel,
            'headerTitle' => $headerTitle ?? $title ?? 'Dashboard',
            'headerSubtitle' => $headerSubtitle ?? null,
            'activeChild' => $selectedChild ?? (!empty($children) ? $children[0] : null)
        ]); ?>

        <!-- Content Area -->
        <main id="main-content" class="flex-1 overflow-y-auto p-6 md:p-8 bg-slate-50 focus:outline-none" tabindex="-1">
            
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

    <!-- Reusable Vanilla Modal & Overlay Javascript Utility -->
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
            }
        };

        // Listen for ESC key to close any active modal safely
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                const openModals = document.querySelectorAll('.lms-modal:not(.hidden)');
                openModals.forEach(modal => window.LMS.hideModal(modal.id));
            }
        });
    </script>
</body>
</html>
