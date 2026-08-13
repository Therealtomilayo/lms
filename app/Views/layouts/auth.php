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
<body class="h-full flex flex-col justify-center py-12 sm:px-6 lg:px-8 bg-slate-50 text-slate-800">
    <!-- Skip to content accessibility helper -->
    <a href="#main-content" 
       class="sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-4 bg-brand-600 text-white px-4 py-2 rounded-md z-50 focus:ring-2 focus:ring-offset-2 focus:ring-brand-500">
        Skip to main content
    </a>

    <!-- Brand / School Header -->
    <div class="sm:mx-auto sm:w-full sm:max-w-md text-center">
        <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-white p-2 shadow-xs border border-slate-200 mb-4">
            <img src="/assets/img/logo.png" alt="Claret Academy Logo" class="w-full h-full object-contain" onerror="this.parentElement.innerHTML='<span class=\'font-bold text-brand-700 text-xl\'>CL</span>'">
        </div>
        <h1 class="text-2xl font-bold tracking-tight text-slate-900 leading-tight">Claret Academy</h1>
        <p class="text-sm text-slate-500 mt-1">Learning Management System</p>
    </div>

    <!-- Main Content Container -->
    <main id="main-content" class="mt-8 sm:mx-auto sm:w-full sm:max-w-md px-4 sm:px-0 focus:outline-none" tabindex="-1">
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
        <div class="bg-white py-8 px-6 shadow-sm border border-slate-200 rounded-xl sm:px-10">
            <?= $content ?? '' ?>
        </div>
    </main>

    <footer class="mt-8 text-center text-xs text-slate-400">
        &copy; <?= date('Y') ?> Claret Academy LMS. All rights reserved.
    </footer>
</body>
</html>
