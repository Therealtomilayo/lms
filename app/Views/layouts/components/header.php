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
<header class="bg-white border-b border-slate-200 min-h-[64px] flex items-center justify-between px-3.5 sm:px-6 py-3 flex-shrink-0 shadow-xs z-20 print:hidden no-print">
    <!-- Title & Navigation Toggle -->
    <div class="flex items-center gap-2 sm:gap-4 min-w-0 max-w-[55%] sm:max-w-none">
        <!-- Mobile Sidebar Hamburger Toggle -->
        <button type="button" 
                onclick="window.LMS ? window.LMS.toggleSidebar() : (document.getElementById('sidebar-navigation').classList.toggle('hidden'), document.getElementById('sidebar-navigation').classList.toggle('flex'), document.getElementById('sidebar-backdrop')?.classList.toggle('hidden'))" 
                class="md:hidden p-2 -ml-1 sm:-ml-2 rounded-lg text-slate-500 hover:bg-slate-100 hover:text-slate-700 focus:outline-none focus:ring-2 focus:ring-brand-500 min-w-[40px] min-h-[40px] flex items-center justify-center cursor-pointer flex-shrink-0"
                aria-label="Toggle Navigation Sidebar">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
        </button>

        <div class="truncate min-w-0">
            <h1 class="text-base sm:text-lg md:text-xl font-bold text-slate-900 leading-tight truncate">
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
    <div class="flex items-center gap-2 sm:gap-3 flex-shrink-0">
        <?php
        $activeSessionName = null;
        $activeTermName = null;
        $activeTermElapsed = false;
        try {
            $acadRepo = new \App\Repositories\AcademicRepository();
            $currSession = $acadRepo->findActiveSession();
            if ($currSession) {
                $activeSessionName = $currSession->name;
                $currTerm = $acadRepo->findActiveTermForSession((int)$currSession->id);
                if ($currTerm) {
                    $activeTermName = $currTerm->name;
                    if ($currTerm->endDate && strtotime($currTerm->endDate) < strtotime(date('Y-m-d'))) {
                        $activeTermElapsed = true;
                    }
                }
            }
        } catch (\Throwable $e) {}
        ?>

        <!-- Institutional Active Session & Term Indicator -->
        <?php if ($activeTermName): ?>
            <a href="<?= in_array($role, ['admin', 'super_admin'], true) ? '/admin/terms' : '#' ?>" 
               class="hidden md:flex items-center gap-2 px-3 py-1.5 bg-slate-50 hover:bg-slate-100 border border-slate-200 rounded-full text-xs transition group"
               title="<?= $activeTermElapsed ? 'Active term end date has passed. Moderation grace period is currently active.' : 'Current active institutional academic session & term' ?>">
                <span class="flex h-2 w-2 relative">
                    <span class="<?= $activeTermElapsed ? 'bg-amber-400' : 'bg-emerald-400' ?> animate-ping absolute inline-flex h-full w-full rounded-full opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2 w-2 <?= $activeTermElapsed ? 'bg-amber-500' : 'bg-emerald-500' ?>"></span>
                </span>
                <span class="text-slate-600 font-medium tracking-tight">
                    <?= e($activeSessionName ? $activeSessionName . ' • ' : '') ?><strong class="text-slate-800 group-hover:text-brand-700 font-bold"><?= e($activeTermName) ?></strong>
                </span>
                <?php if ($activeTermElapsed): ?>
                    <span class="text-[9px] font-bold uppercase tracking-wider bg-amber-100 text-amber-800 px-1.5 py-0.5 rounded">Grace</span>
                <?php endif; ?>
            </a>
        <?php endif; ?>

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

        // Session-based fallback for available portals if repository query failed or returned empty
        if (empty($availablePortals)) {
            $sessRoles = $_SESSION['user_roles'] ?? (!empty($_SESSION['user_role']) ? [$_SESSION['user_role']] : (!empty($role) ? [$role] : []));
            if (in_array('super_admin', $sessRoles, true) || in_array('admin', $sessRoles, true)) {
                $availablePortals[] = [
                    'key' => 'admin',
                    'name' => in_array('super_admin', $sessRoles, true) ? 'Super Admin Portal' : 'Admin Portal',
                    'route' => '/admin/dashboard',
                    'icon' => '<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>'
                ];
            }
            if (in_array('teacher', $sessRoles, true)) {
                $availablePortals[] = [
                    'key' => 'teacher',
                    'name' => 'Teacher Workspace',
                    'route' => '/teacher/dashboard',
                    'icon' => '<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14zm-4 6v-7.5l4-2.222"/></svg>'
                ];
            }
            if (in_array('parent', $sessRoles, true)) {
                $availablePortals[] = [
                    'key' => 'parent',
                    'name' => 'Parent Portal',
                    'route' => '/parent/dashboard',
                    'icon' => '<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>'
                ];
            }
            if (in_array('student', $sessRoles, true)) {
                $availablePortals[] = [
                    'key' => 'student',
                    'name' => 'Student Portal',
                    'route' => '/student/dashboard',
                    'icon' => '<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>'
                ];
            }
        }

        $effectiveRoleLabel = !empty($roleLabel) ? $roleLabel : (isset($role) ? ucfirst(str_replace('_', ' ', $role)) . ' Portal' : 'User Portal');
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

        <!-- Role Badge / Portal Switcher (Available to all roles with Logout) -->
        <div class="relative" id="portal-switcher-container">
            <button type="button" 
                    id="portal-switcher-btn"
                    onclick="document.getElementById('portal-switcher-dropdown').classList.toggle('hidden')" 
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold bg-brand-50 hover:bg-brand-100 text-brand-700 border border-brand-200 transition cursor-pointer shadow-xs focus:outline-none focus:ring-2 focus:ring-brand-500"
                    title="User session & portal menu">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                <span><?= e($effectiveRoleLabel) ?></span>
                <svg class="w-3.5 h-3.5 text-brand-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </button>

            <!-- Dropdown Menu -->
            <div id="portal-switcher-dropdown" 
                 class="hidden absolute right-0 mt-2 w-56 rounded-2xl bg-white shadow-xl border border-slate-200 py-2 z-50 divide-y divide-slate-100">
                <!-- User Context Header -->
                <div class="px-3.5 py-2">
                    <p class="text-xs font-bold text-slate-900 truncate"><?= e($_SESSION['user_name'] ?? 'User') ?></p>
                    <p class="text-[10px] text-slate-500 truncate"><?= e($_SESSION['user_email'] ?? '') ?></p>
                    <div class="mt-1.5 inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-bold bg-brand-50 text-brand-700 border border-brand-200">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                        <span><?= e($effectiveRoleLabel) ?></span>
                    </div>
                </div>

                <!-- Multiple Portals Switcher (if user has > 1 role) -->
                <?php if (count($availablePortals) > 1): ?>
                    <div class="py-1">
                        <div class="px-3.5 py-1 text-[10px] font-bold uppercase tracking-wider text-slate-400">
                            Switch Portal
                        </div>
                        <?php foreach ($availablePortals as $p): ?>
                            <?php $isCurrent = ($p['key'] === 'admin' && in_array($role, ['admin', 'super_admin'], true)) || ($p['key'] === $role); ?>
                            <a href="<?= e($p['route']) ?>" 
                               class="flex items-center justify-between px-3.5 py-1.5 text-xs font-medium <?= $isCurrent ? 'bg-brand-50 text-brand-700 font-bold' : 'text-slate-700 hover:bg-slate-50 hover:text-slate-900' ?> transition">
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
                <?php endif; ?>

                <!-- Profile & Security Links -->
                <div class="py-1">
                    <a href="/profile" class="flex items-center gap-2 px-3.5 py-1.5 text-xs text-slate-700 hover:bg-slate-50 hover:text-slate-900 transition">
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        <span>My Profile</span>
                    </a>
                    <a href="/profile/password" class="flex items-center gap-2 px-3.5 py-1.5 text-xs text-slate-700 hover:bg-slate-50 hover:text-slate-900 transition">
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
                        <span>Change Password</span>
                    </a>
                </div>

                <!-- Log Out Action Form -->
                <div class="p-1">
                    <form action="/logout" method="POST" class="w-full">
                        <?= csrf_field() ?>
                        <button type="submit" 
                                class="w-full flex items-center gap-2 px-3 py-1.5 text-xs font-bold text-rose-600 hover:bg-rose-50 rounded-lg transition cursor-pointer">
                            <svg class="w-4 h-4 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                            <span>Sign Out / Log Out</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Quick Header Logout Button for Immediate Access & Better UX -->
        <form action="/logout" method="POST" class="inline-flex">
            <?= csrf_field() ?>
            <button type="submit" 
                    title="Sign Out / Log Out" 
                    class="p-2 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-xl transition flex items-center justify-center cursor-pointer min-w-[36px] min-h-[36px]"
                    aria-label="Sign Out">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                </svg>
            </button>
        </form>

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
    </div>
</header>
