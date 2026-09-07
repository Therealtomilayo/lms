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
        $availablePortals = [];

        try {
            $hUserId = $_SESSION['user_id'] ?? null;
            if ($hUserId) {
                $hUserRepo = new \App\Repositories\UserRepository();
                $hUser = $hUserRepo->findById((int)$hUserId);
                if ($hUser) {
                    $hUserCtx = \App\Core\UserContext::fromUser($hUser);
                    $hAnnRepo = new \App\Repositories\AnnouncementRepository();
                    $headerUnreadCount = $hAnnRepo->getUnreadCount($hUserCtx, $role === 'parent' && !empty($activeChild) ? (int)$activeChild->id : null);

                    $hRoles = $hUser->roles;
                    if (in_array('super_admin', $hRoles, true) || in_array('admin', $hRoles, true)) {
                        $availablePortals[] = [
                            'key' => 'admin',
                            'name' => in_array('super_admin', $hRoles, true) ? 'Super Admin Portal' : 'Admin Portal',
                            'route' => '/admin/dashboard',
                            'icon' => '<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>'
                        ];
                    }
                    if (in_array('teacher', $hRoles, true)) {
                        $availablePortals[] = [
                            'key' => 'teacher',
                            'name' => 'Teacher Workspace',
                            'route' => '/teacher/dashboard',
                            'icon' => '<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14zm-4 6v-7.5l4-2.222"/></svg>'
                        ];
                    }
                    if (in_array('parent', $hRoles, true)) {
                        $availablePortals[] = [
                            'key' => 'parent',
                            'name' => 'Parent Portal',
                            'route' => '/parent/dashboard',
                            'icon' => '<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>'
                        ];
                    }
                    if (in_array('student', $hRoles, true)) {
                        $availablePortals[] = [
                            'key' => 'student',
                            'name' => 'Student Portal',
                            'route' => '/student/dashboard',
                            'icon' => '<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>'
                        ];
                    }
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

        <!-- Role Badge / Portal Switcher -->
        <?php if (count($availablePortals) > 1): ?>
            <div class="relative" id="portal-switcher-container">
                <button type="button" 
                        id="portal-switcher-btn"
                        onclick="document.getElementById('portal-switcher-dropdown').classList.toggle('hidden')" 
                        class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-brand-50 hover:bg-brand-100 text-brand-700 border border-brand-200 transition cursor-pointer shadow-xs focus:outline-none focus:ring-2 focus:ring-brand-500"
                        title="Click to switch portal view">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                    <span><?= e($roleLabel) ?></span>
                    <svg class="w-3.5 h-3.5 text-brand-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>

                <!-- Dropdown Menu -->
                <div id="portal-switcher-dropdown" 
                     class="hidden absolute right-0 mt-2 w-52 rounded-xl bg-white shadow-xl border border-slate-200 py-1 z-50">
                    <div class="px-3 py-1.5 border-b border-slate-100 flex items-center justify-between">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Switch Portal</span>
                        <span class="text-[10px] text-brand-600 font-semibold"><?= count($availablePortals) ?> roles</span>
                    </div>
                    <div class="py-1">
                        <?php foreach ($availablePortals as $p): ?>
                            <?php $isCurrent = ($p['key'] === 'admin' && in_array($role, ['admin', 'super_admin'], true)) || ($p['key'] === $role); ?>
                            <a href="<?= e($p['route']) ?>" 
                               class="flex items-center justify-between px-3 py-2 text-xs font-medium <?= $isCurrent ? 'bg-brand-50 text-brand-700 font-bold' : 'text-slate-700 hover:bg-slate-50 hover:text-slate-900' ?> transition">
                                <div class="flex items-center gap-2">
                                    <span class="<?= $isCurrent ? 'text-brand-600' : 'text-slate-400' ?>"><?= $p['icon'] ?></span>
                                    <span><?= e($p['name']) ?></span>
                                </div>
                                <?php if ($isCurrent): ?>
                                    <span class="text-[9px] font-bold text-brand-700 bg-brand-100 px-1.5 py-0.5 rounded-full">Current</span>
                                <?php endif; ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <script>
            if (!window.__portalSwitcherBound) {
                window.__portalSwitcherBound = true;
                document.addEventListener('click', function(e) {
                    const container = document.getElementById('portal-switcher-container');
                    const dropdown = document.getElementById('portal-switcher-dropdown');
                    if (container && dropdown && !container.contains(e.target)) {
                        dropdown.classList.add('hidden');
                    }
                });
            }
            </script>
        <?php else: ?>
            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-brand-100 text-brand-700 border border-blue-200">
                <?= e($roleLabel) ?>
            </span>
        <?php endif; ?>

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
