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
<header class="bg-white border-b border-slate-200 min-h-[64px] flex items-center justify-between px-6 py-3.5 flex-shrink-0 shadow-xs z-20 print:hidden no-print">
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

        <!-- Announcements Notification Bell Link -->
        <?php
        $notificationUrl = '/notifications';
        
        $headerUnreadCount = 0;
        try {
            $hUserId = $_SESSION['user_id'] ?? null;
            if ($hUserId) {
                $hUserRepo = new \App\Repositories\UserRepository();
                $hUser = $hUserRepo->findById((int)$hUserId);
                if ($hUser) {
                    $hUserCtx = \App\Core\UserContext::fromUser($hUser);
                    $hAnnRepo = new \App\Repositories\AnnouncementRepository();
                    $headerUnreadCount = $hAnnRepo->getUnreadCount($hUserCtx, $role === 'parent' && !empty($activeChild) ? (int)$activeChild->id : null);
                }
            }
        } catch (\Throwable $e) {
            $headerUnreadCount = 0;
        }
        ?>
        <a href="<?= e($notificationUrl) ?>" 
           class="relative p-2 text-slate-500 hover:text-slate-800 hover:bg-slate-100 rounded-xl transition flex items-center justify-center" 
           title="Notifications & Bulletins (<?= $headerUnreadCount ?> unread)">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
            </svg>
            <?php if ($headerUnreadCount > 0): ?>
                <span class="absolute top-1 right-1 flex h-4 min-w-[16px] px-1 items-center justify-center rounded-full bg-rose-500 text-[9px] font-extrabold text-white ring-2 ring-white shadow-xs">
                    <?= $headerUnreadCount > 9 ? '9+' : $headerUnreadCount ?>
                </span>
            <?php endif; ?>
        </a>

        <!-- Role Badge -->
        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-brand-100 text-brand-700 border border-blue-200">
            <?= e($roleLabel) ?>
        </span>

        <!-- Profile & Password Links -->
        <div class="border-l border-slate-200 pl-3.5 flex items-center gap-2.5">
            <a href="/profile" class="text-sm font-medium text-slate-600 hover:text-brand-600 focus:outline-none focus:underline transition">
                Profile
            </a>
            <span class="text-slate-300 text-xs">&bull;</span>
            <a href="/profile/password" class="text-sm font-medium text-slate-600 hover:text-brand-600 focus:outline-none focus:underline transition">
                Password
            </a>
        </div>
    </div>
</header>
