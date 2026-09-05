<?php
/**
 * Unified Notifications & Bulletins Index View
 * AUTH-06 — Shared across Admin, Teacher, Student, and Parent Portals
 * Adheres strictly to .ai/08-ui-design-system.md
 * 
 * @var \App\Models\User $user Authenticated user model
 * @var \App\Core\UserContext $userContext
 * @var array $roles
 * @var array $feed Array of \App\Models\Announcement items
 * @var int $unreadCount
 * @var array $linkedChildren
 * @var object|null $activeChild
 * @var int|null $activeChildId
 */

$roles = $roles ?? [];
$dashboardUrl = '/login';
if (in_array('super_admin', $roles, true) || in_array('admin', $roles, true)) {
    $dashboardUrl = '/admin/dashboard';
} elseif (in_array('teacher', $roles, true)) {
    $dashboardUrl = '/teacher/dashboard';
} elseif (in_array('student', $roles, true)) {
    $dashboardUrl = '/student/dashboard';
} elseif (in_array('parent', $roles, true)) {
    $dashboardUrl = '/parent/dashboard';
}

$feed = $feed ?? [];
$totalBulletins = count($feed);

// Calculate metrics
$schoolWideCount = 0;
$scopedCount = 0;
$readCount = 0;

foreach ($feed as $item) {
    if ($item->scope === 'school') {
        $schoolWideCount++;
    } else {
        $scopedCount++;
    }
    if (!empty($item->isRead)) {
        $readCount++;
    }
}
?>

<div class="space-y-6">

    <!-- 1. HEADER CARD WITH BREADCRUMBS, TITLE & ACTIONS -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6">
            
            <!-- Left: Title & Context -->
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 rounded-2xl bg-brand-700 text-white flex items-center justify-center shadow-xs flex-shrink-0">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                    </svg>
                </div>
                <div>
                    <!-- Breadcrumbs -->
                    <div class="flex items-center gap-2 text-xs font-semibold text-slate-400 mb-1">
                        <a href="<?= e($dashboardUrl) ?>" class="hover:text-brand-600 transition">Dashboard</a>
                        <span>&rsaquo;</span>
                        <span class="text-slate-700 font-bold">Notifications &amp; Bulletins</span>
                    </div>

                    <div class="flex flex-wrap items-center gap-2.5">
                        <h1 class="text-xl font-bold text-slate-900 tracking-tight">
                            Campus Notifications &amp; Bulletins
                        </h1>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-slate-100 text-slate-700 border border-slate-200">
                            <?= $totalBulletins ?> <?= $totalBulletins === 1 ? 'Notice' : 'Notices' ?>
                        </span>
                        <?php if ($unreadCount > 0): ?>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-rose-50 text-rose-700 border border-rose-200 animate-pulse">
                                <?= $unreadCount ?> Unread
                            </span>
                        <?php else: ?>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                All Read
                            </span>
                        <?php endif; ?>
                    </div>
                    <p class="text-xs text-slate-500 mt-1">
                        Official administrative directives, homeroom notices, and academic reminders.
                    </p>
                </div>
            </div>

            <!-- Right: Action Toolbar -->
            <div class="flex flex-wrap items-center gap-2">
                <?php if ($unreadCount > 0): ?>
                    <form action="/notifications/read-all" method="POST" class="inline">
                        <?= csrf_field() ?>
                        <?php if (!empty($activeChildId)): ?>
                            <input type="hidden" name="child_id" value="<?= (int)$activeChildId ?>">
                        <?php endif; ?>
                        <input type="hidden" name="redirect_to" value="/notifications<?= !empty($activeChildId) ? "?child_id={$activeChildId}" : '' ?>">
                        <button type="submit" 
                                class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-brand-50 hover:bg-brand-100 text-brand-700 text-xs font-bold rounded-xl border border-brand-200 transition cursor-pointer">
                            <svg class="w-4 h-4 text-brand-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                            </svg>
                            <span>Mark All as Read</span>
                        </button>
                    </form>
                <?php endif; ?>

                <a href="<?= e($dashboardUrl) ?>" 
                   class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-white hover:bg-slate-50 text-slate-700 text-xs font-bold rounded-xl border border-slate-300 shadow-xs transition">
                    <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                    </svg>
                    <span>Dashboard</span>
                </a>
            </div>

        </div>
    </div>

    <!-- 2. MULTI-WARD SWITCHER TABS (PARENT PORTAL ONLY) -->
    <?php if (in_array('parent', $roles, true) && !empty($linkedChildren) && count($linkedChildren) > 1): ?>
        <div class="flex items-center gap-2 overflow-x-auto pb-1">
            <span class="text-xs font-bold uppercase tracking-wider text-slate-400 mr-1 flex-shrink-0">Ward Context:</span>
            <?php foreach ($linkedChildren as $child): 
                $cId = (int)$child->id;
                $isActiveChild = ($activeChildId === $cId);
            ?>
                <a href="/notifications?child_id=<?= $cId ?>" 
                   class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-xl text-xs font-bold transition flex-shrink-0 <?= $isActiveChild ? 'bg-brand-700 text-white shadow-xs' : 'bg-white text-slate-700 hover:bg-slate-100 border border-slate-200' ?>">
                    <span class="w-2 h-2 rounded-full <?= $isActiveChild ? 'bg-emerald-400' : 'bg-slate-300' ?>"></span>
                    <span><?= htmlspecialchars($child->name) ?> (<?= htmlspecialchars($child->className ?: 'Class') ?>)</span>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- 3. 4-CARD OVERVIEW STATS STRIP -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        
        <!-- Total Notices -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Total Bulletins</span>
                <span class="p-2 rounded-xl bg-brand-50 text-brand-700">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/>
                    </svg>
                </span>
            </div>
            <div class="mt-3">
                <div class="text-2xl font-extrabold text-slate-900">
                    <?= $totalBulletins ?>
                </div>
                <p class="text-xs text-slate-500 mt-1">
                    Published announcements on record
                </p>
            </div>
        </div>

        <!-- Unread Notices -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Unread Notices</span>
                <span class="p-2 rounded-xl bg-rose-50 text-rose-700">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                    </svg>
                </span>
            </div>
            <div class="mt-3">
                <div class="text-2xl font-extrabold text-slate-900">
                    <?= $unreadCount ?>
                </div>
                <p class="text-xs text-slate-500 mt-1">
                    <?= $unreadCount > 0 ? 'Requires your acknowledgement' : 'All notices acknowledged' ?>
                </p>
            </div>
        </div>

        <!-- School-Wide Directives -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500">School-Wide</span>
                <span class="p-2 rounded-xl bg-indigo-50 text-indigo-700">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                </span>
            </div>
            <div class="mt-3">
                <div class="text-2xl font-extrabold text-slate-900">
                    <?= $schoolWideCount ?>
                </div>
                <p class="text-xs text-slate-500 mt-1">
                    Institutional policy &amp; calendar alerts
                </p>
            </div>
        </div>

        <!-- Scoped Notices -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Cohort / Coursework</span>
                <span class="p-2 rounded-xl bg-purple-50 text-purple-700">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                    </svg>
                </span>
            </div>
            <div class="mt-3">
                <div class="text-2xl font-extrabold text-slate-900">
                    <?= $scopedCount ?>
                </div>
                <p class="text-xs text-slate-500 mt-1">
                    Targeted homeroom &amp; lesson notices
                </p>
            </div>
        </div>

    </div>

    <!-- 4. INTERACTIVE SEARCH & FILTER TOOLBAR -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-5 flex flex-col md:flex-row md:items-center justify-between gap-4">
        
        <!-- Search Input -->
        <div class="relative flex-1 max-w-md">
            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>
            <input type="text" id="notification-search" 
                   oninput="filterNotifications()"
                   placeholder="Search notices by title, author, or keyword..." 
                   class="w-full pl-10 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white transition">
        </div>

        <!-- Live Filter Buttons -->
        <div class="flex items-center gap-1.5 bg-slate-100 p-1 rounded-xl flex-wrap" role="tablist">
            <button type="button" onclick="setFilter('all')" id="btn-filter-all"
                    class="filter-tab-btn px-3 py-1.5 rounded-lg text-xs font-bold bg-white text-slate-800 shadow-xs transition">
                All (<?= $totalBulletins ?>)
            </button>
            <button type="button" onclick="setFilter('unread')" id="btn-filter-unread"
                    class="filter-tab-btn px-3 py-1.5 rounded-lg text-xs font-bold text-slate-600 hover:text-slate-900 transition">
                Unread (<?= $unreadCount ?>)
            </button>
            <button type="button" onclick="setFilter('read')" id="btn-filter-read"
                    class="filter-tab-btn px-3 py-1.5 rounded-lg text-xs font-bold text-slate-600 hover:text-slate-900 transition">
                Read (<?= $readCount ?>)
            </button>
            <button type="button" onclick="setFilter('school')" id="btn-filter-school"
                    class="filter-tab-btn px-3 py-1.5 rounded-lg text-xs font-bold text-slate-600 hover:text-slate-900 transition">
                School-Wide (<?= $schoolWideCount ?>)
            </button>
            <button type="button" onclick="setFilter('scoped')" id="btn-filter-scoped"
                    class="filter-tab-btn px-3 py-1.5 rounded-lg text-xs font-bold text-slate-600 hover:text-slate-900 transition">
                Scoped (<?= $scopedCount ?>)
            </button>
        </div>

    </div>

    <!-- 5. NOTIFICATION FEED STREAM -->
    <?php if (empty($feed)): ?>
        <!-- Global Empty State -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-12 text-center">
            <div class="w-14 h-14 rounded-2xl bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-3">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                </svg>
            </div>
            <h3 class="text-sm font-bold text-slate-900">No Notifications Published</h3>
            <p class="text-xs text-slate-500 max-w-sm mx-auto mt-1">
                There are no active notifications or announcements broadcast for your account at this time.
            </p>
        </div>
    <?php else: ?>
        <div class="space-y-4" id="notifications-container">
            <?php foreach ($feed as $item): 
                $isRead = !empty($item->isRead);
                $isSchool = ($item->scope === 'school');
                $isScoped = !$isSchool;
                $pubDate = !empty($item->publishedAt) ? date('M d, Y · h:i A', strtotime($item->publishedAt)) : date('M d, Y', strtotime($item->createdAt));
                $authorName = $item->authorName ?? 'Administration';
                $title = $item->title ?? 'Untitled Announcement';
                $body = $item->body ?? '';
            ?>
                <div class="notification-card bg-white rounded-2xl border transition-all duration-200 p-5 <?= $isRead ? 'border-slate-200 opacity-90' : 'border-brand-300 shadow-xs ring-1 ring-brand-100' ?>"
                     data-read="<?= $isRead ? 'read' : 'unread' ?>"
                     data-scope="<?= $isSchool ? 'school' : 'scoped' ?>"
                     data-search-text="<?= htmlspecialchars(strtolower($title . ' ' . $authorName . ' ' . $body)) ?>">
                    
                    <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4">
                        
                        <!-- Left: Author, Scope & Date -->
                        <div class="flex items-start gap-3 min-w-0">
                            <div class="w-10 h-10 rounded-xl bg-brand-700 text-white font-extrabold text-xs flex items-center justify-center flex-shrink-0 shadow-xs">
                                <?= strtoupper(substr($authorName, 0, 1)) ?>
                            </div>
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2 mb-1">
                                    <span class="font-bold text-slate-900 text-xs truncate">
                                        <?= htmlspecialchars($authorName) ?>
                                    </span>

                                    <!-- Scope Badge -->
                                    <?php if ($isSchool): ?>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-700 border border-slate-200">
                                            School-Wide
                                        </span>
                                    <?php elseif ($item->scope === 'class' && !empty($item->className)): ?>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-brand-50 text-brand-700 border border-brand-200">
                                            Class: <?= htmlspecialchars($item->className) ?>
                                        </span>
                                    <?php elseif ($item->scope === 'class_subject'): ?>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-purple-50 text-purple-700 border border-purple-200">
                                            <?= htmlspecialchars($item->csSubjectName ?: 'Subject') ?> &bull; <?= htmlspecialchars($item->csClassName ?: 'Cohort') ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-brand-50 text-brand-700 border border-brand-200">
                                            Targeted Notice
                                        </span>
                                    <?php endif; ?>

                                    <!-- Unread Dot Badge -->
                                    <?php if (!$isRead): ?>
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-rose-50 text-rose-700 border border-rose-200">
                                            <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                            <span>New</span>
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center gap-1 text-[10px] font-semibold text-slate-400">
                                            <svg class="w-3.5 h-3.5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                            </svg>
                                            <span>Read</span>
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <span class="text-[11px] text-slate-400 font-mono">
                                    Published: <?= $pubDate ?>
                                </span>
                            </div>
                        </div>

                        <!-- Right: Action Button -->
                        <?php if (!$isRead): ?>
                            <form action="/notifications/<?= (int)$item->id ?>/read" method="POST" class="flex-shrink-0">
                                <?= csrf_field() ?>
                                <input type="hidden" name="redirect_to" value="/notifications<?= !empty($activeChildId) ? "?child_id={$activeChildId}" : '' ?>">
                                <button type="submit" 
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold rounded-xl bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 shadow-xs hover:text-brand-700 transition cursor-pointer">
                                    <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    <span>Mark as Read</span>
                                </button>
                            </form>
                        <?php endif; ?>

                    </div>

                    <!-- Bulletin Body Content -->
                    <div class="mt-3.5 pt-3 border-t border-slate-100">
                        <h3 class="text-sm font-bold text-slate-900 leading-snug">
                            <?= htmlspecialchars($title) ?>
                        </h3>
                        <div class="text-xs text-slate-600 mt-2 leading-relaxed whitespace-pre-line">
                            <?= nl2br(htmlspecialchars($body)) ?>
                        </div>
                    </div>

                </div>
            <?php endforeach; ?>

            <!-- Client-side No Search Results Card -->
            <div id="no-search-results" class="hidden bg-white rounded-2xl border border-slate-200 shadow-xs p-8 text-center">
                <p class="text-xs font-bold text-slate-700">No matching notifications found</p>
                <p class="text-xs text-slate-400 mt-1">Try refining your search query or selecting another filter tab.</p>
            </div>
        </div>
    <?php endif; ?>

</div>

<!-- Client-side Search & Tab Filter Script -->
<script>
    let currentFilter = 'all';

    function setFilter(filterKey) {
        currentFilter = filterKey;

        // Update button active state
        document.querySelectorAll('.filter-tab-btn').forEach(btn => {
            btn.classList.remove('bg-white', 'text-slate-800', 'shadow-xs');
            btn.classList.add('text-slate-600');
        });

        const activeBtn = document.getElementById('btn-filter-' + filterKey);
        if (activeBtn) {
            activeBtn.classList.remove('text-slate-600');
            activeBtn.classList.add('bg-white', 'text-slate-800', 'shadow-xs');
        }

        filterNotifications();
    }

    function filterNotifications() {
        const query = (document.getElementById('notification-search')?.value || '').toLowerCase().trim();
        const cards = document.querySelectorAll('.notification-card');
        let visibleCount = 0;

        cards.forEach(card => {
            const isRead = card.getAttribute('data-read');
            const scope = card.getAttribute('data-scope');
            const searchText = card.getAttribute('data-search-text') || '';

            // Match tab filter
            let tabMatches = false;
            if (currentFilter === 'all') {
                tabMatches = true;
            } else if (currentFilter === 'unread' && isRead === 'unread') {
                tabMatches = true;
            } else if (currentFilter === 'read' && isRead === 'read') {
                tabMatches = true;
            } else if (currentFilter === 'school' && scope === 'school') {
                tabMatches = true;
            } else if (currentFilter === 'scoped' && scope === 'scoped') {
                tabMatches = true;
            }

            // Match search query
            const queryMatches = (query === '' || searchText.includes(query));

            if (tabMatches && queryMatches) {
                card.style.display = '';
                visibleCount++;
            } else {
                card.style.display = 'none';
            }
        });

        const noResults = document.getElementById('no-search-results');
        if (noResults) {
            noResults.style.display = (visibleCount === 0 && cards.length > 0) ? 'block' : 'none';
        }
    }
</script>
