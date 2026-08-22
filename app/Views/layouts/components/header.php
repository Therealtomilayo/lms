<?php
/**
 * Unified Header Layout Component
 * 
 * @var string $role User role
 * @var string $roleLabel Nice display name of role
 * @var string|null $headerTitle Main page title
 * @var string|null $headerSubtitle Extra sub-heading context (optional)
 * @var object|null $activeChild Currently selected student info (optional; parent portal only)
 */
?>
<header class="bg-white border-b border-slate-200 min-h-[64px] flex items-center justify-between px-6 py-3.5 flex-shrink-0 shadow-xs z-20">
    <!-- Title & Navigation Toggle -->
    <div class="flex items-center gap-4 min-w-0">
        <!-- Mobile Sidebar Hamburger Toggle -->
        <button type="button" 
                onclick="window.LMS ? window.LMS.toggleSidebar() : (document.getElementById('sidebar-navigation').classList.toggle('hidden'), document.getElementById('sidebar-navigation').classList.toggle('flex'), document.getElementById('sidebar-backdrop')?.classList.toggle('hidden'))" 
                class="md:hidden p-2 -ml-2 rounded-lg text-slate-500 hover:bg-slate-100 hover:text-slate-700 focus:outline-none focus:ring-2 focus:ring-brand-500 min-w-[44px] min-h-[44px] flex items-center justify-center cursor-pointer"
                aria-label="Toggle Navigation Sidebar">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
        </button>

        <div class="truncate">
            <h1 class="text-lg md:text-xl font-bold text-slate-900 leading-tight truncate">
                <?= e($headerTitle ?? $title ?? 'Dashboard') ?>
            </h1>
            <?php if (!empty($headerSubtitle)): ?>
                <p class="text-xs text-slate-500 mt-0.5 hidden sm:block truncate">
                    <?= e($headerSubtitle) ?>
                </p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Right-aligned Utilities & Badges -->
    <div class="flex items-center gap-3.5 flex-shrink-0">
        <!-- Parent Child Context Status Indicator -->
        <?php if ($role === 'parent' && !empty($activeChild)): ?>
            <div class="hidden sm:flex items-center gap-2 px-3 py-1.5 bg-slate-100 border border-slate-200 rounded-full text-xs">
                <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                <span class="text-slate-500 font-medium">Viewing:</span>
                <strong class="text-slate-800 font-bold"><?= e($activeChild->name) ?></strong>
            </div>
        <?php endif; ?>

        <!-- Role Badge -->
        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-brand-100 text-brand-700 border border-blue-200">
            <?= e($roleLabel) ?>
        </span>

        <!-- Settings Link -->
        <div class="border-l border-slate-200 pl-3.5">
            <a href="/profile/password" class="text-sm font-medium text-slate-600 hover:text-brand-600 focus:outline-none focus:underline transition">
                Change Password
            </a>
        </div>
    </div>
</header>
