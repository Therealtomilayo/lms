<?php
/**
 * Parent Child Bulletins & Announcements View
 * Phase UI-0 Modernization following .ai/08-ui-design-system.md
 * 
 * @var array $children
 * @var object|array|null $selectedChild
 * @var array $feed
 * @var \App\Core\UserContext $user
 * @var string $csrf_token
 */

$childId = is_object($selectedChild) ? (int)$selectedChild->id : (int)($selectedChild['id'] ?? 0);
$childName = is_object($selectedChild) ? $selectedChild->name : ($selectedChild['name'] ?? 'All Wards');
$admissionNumber = is_object($selectedChild) ? ($selectedChild->admissionNumber ?? '') : ($selectedChild['admission_number'] ?? '');
$className = is_object($selectedChild) 
    ? ($selectedChild->className ?: ($selectedChild->currentClass?->name ?: 'JSS 1A'))
    : ($selectedChild['class_name'] ?? 'JSS 1A');

// Feed Statistics
$totalNotices = count($feed);
$unreadCount = 0;
$classCount = 0;
$schoolWideCount = 0;

foreach ($feed as $item) {
    $isRead = is_object($item) ? $item->isRead : !empty($item['read_at']);
    $targetType = is_object($item) ? ($item->targetType ?? '') : ($item['target_type'] ?? '');
    
    if (!$isRead) {
        $unreadCount++;
    }
    if ($targetType === 'class') {
        $classCount++;
    } else {
        $schoolWideCount++;
    }
}
?>

<div class="space-y-6">

    <!-- 1. HEADER CARD WITH BREADCRUMBS, STUDENT INFO & QUICK TOOLBAR -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6">
            
            <!-- Left: Student Particulars -->
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 rounded-2xl bg-brand-700 text-white font-extrabold text-2xl flex items-center justify-center shadow-xs flex-shrink-0">
                    <?= strtoupper(substr($childName, 0, 1)) ?>
                </div>
                <div>
                    <!-- Breadcrumbs -->
                    <div class="flex items-center gap-2 text-xs font-semibold text-slate-400 mb-1">
                        <a href="/parent/dashboard" class="hover:text-brand-600 transition">Dashboard</a>
                        <span>&rsaquo;</span>
                        <?php if ($childId > 0): ?>
                            <a href="/parent/children/<?= $childId ?>" class="hover:text-brand-600 transition">Child Profile</a>
                            <span>&rsaquo;</span>
                        <?php endif; ?>
                        <span class="text-slate-700 font-bold">Official Bulletins</span>
                    </div>

                    <div class="flex flex-wrap items-center gap-2.5">
                        <h1 class="text-xl font-bold text-slate-900 tracking-tight">
                            <?= htmlspecialchars($childName) ?>'s Bulletins
                        </h1>
                        <?php if ($childId > 0): ?>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-brand-50 text-brand-700 border border-brand-200">
                                <?= htmlspecialchars($className) ?>
                            </span>
                            <?php if (!empty($admissionNumber)): ?>
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-slate-100 text-slate-700 font-mono">
                                    Adm: <?= htmlspecialchars($admissionNumber) ?>
                                </span>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                    <p class="text-xs text-slate-500 mt-1">
                        Official school circulars, academic notices, and class-specific announcements.
                    </p>
                </div>
            </div>

            <!-- Right: Quick Navigation Toolbar -->
            <?php if ($childId > 0): ?>
                <div class="flex flex-wrap items-center gap-2">
                    <a href="/parent/children/<?= $childId ?>" 
                       class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-white hover:bg-slate-50 text-slate-700 text-xs font-bold rounded-xl border border-slate-300 shadow-xs transition">
                        <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                        <span>Child Profile</span>
                    </a>

                    <a href="/parent/children/<?= $childId ?>/grades" 
                       class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-white hover:bg-slate-50 text-slate-700 text-xs font-bold rounded-xl border border-slate-300 shadow-xs transition">
                        <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <span>Grades</span>
                    </a>

                    <a href="/parent/children/<?= $childId ?>/assignments" 
                       class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-white hover:bg-slate-50 text-slate-700 text-xs font-bold rounded-xl border border-slate-300 shadow-xs transition">
                        <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                        <span>Coursework</span>
                    </a>

                    <a href="/parent/children/<?= $childId ?>/attendance" 
                       class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-white hover:bg-slate-50 text-slate-700 text-xs font-bold rounded-xl border border-slate-300 shadow-xs transition">
                        <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        <span>Attendance</span>
                    </a>

                    <a href="/parent/children/<?= $childId ?>/timetable" 
                       class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-white hover:bg-slate-50 text-slate-700 text-xs font-bold rounded-xl border border-slate-300 shadow-xs transition">
                        <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span>Timetable</span>
                    </a>
                </div>
            <?php endif; ?>

        </div>
    </div>

    <!-- 2. MULTI-CHILD SELECTOR (IF MULTIPLE WARDS LINKED) -->
    <?php if (count($children) > 1): ?>
        <div class="flex items-center gap-2 border-b border-slate-200 pb-3 overflow-x-auto">
            <span class="text-xs font-bold uppercase tracking-wider text-slate-400 mr-1 flex-shrink-0">Viewing Ward:</span>
            <?php foreach ($children as $c): 
                $cId = is_object($c) ? (int)$c->id : (int)($c['id'] ?? 0);
                $cName = is_object($c) ? $c->name : ($c['name'] ?? '');
                $isActiveChild = ($childId === $cId);
            ?>
                <a href="/parent/children/<?= $cId ?>/announcements"
                   class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-xl text-xs font-bold transition flex-shrink-0 <?= $isActiveChild ? 'bg-brand-700 text-white shadow-xs' : 'bg-white text-slate-700 hover:bg-slate-100 border border-slate-200' ?>">
                    <span class="w-2 h-2 rounded-full <?= $isActiveChild ? 'bg-emerald-400' : 'bg-slate-300' ?>"></span>
                    <span><?= htmlspecialchars($cName) ?>'s Feed</span>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- 3. OVERVIEW STATS STRIP -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        
        <!-- Total Bulletins -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Active Circulars</span>
                <span class="p-2 rounded-xl bg-brand-50 text-brand-700">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/>
                    </svg>
                </span>
            </div>
            <div class="mt-3">
                <div class="text-2xl font-extrabold text-slate-900">
                    <?= $totalNotices ?>
                </div>
                <p class="text-xs text-slate-500 mt-1">
                    Published school circulars
                </p>
            </div>
        </div>

        <!-- Unread Bulletins -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Pending Review</span>
                <span class="p-2 rounded-xl <?= $unreadCount > 0 ? 'bg-amber-50 text-amber-700' : 'bg-emerald-50 text-emerald-700' ?>">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                    </svg>
                </span>
            </div>
            <div class="mt-3">
                <div class="text-2xl font-extrabold <?= $unreadCount > 0 ? 'text-amber-700' : 'text-emerald-700' ?>">
                    <?= $unreadCount ?>
                </div>
                <p class="text-xs text-slate-500 mt-1">
                    <?= $unreadCount > 0 ? 'Unread notices awaiting review' : 'All notices up to date' ?>
                </p>
            </div>
        </div>

        <!-- Class Specific -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Class Notices</span>
                <span class="p-2 rounded-xl bg-indigo-50 text-indigo-700">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                </span>
            </div>
            <div class="mt-3">
                <div class="text-2xl font-extrabold text-indigo-700">
                    <?= $classCount ?>
                </div>
                <p class="text-xs text-slate-500 mt-1">
                    Targeted to <?= htmlspecialchars($className) ?>
                </p>
            </div>
        </div>

        <!-- School-Wide -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500">School-Wide</span>
                <span class="p-2 rounded-xl bg-sky-50 text-sky-700">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/>
                    </svg>
                </span>
            </div>
            <div class="mt-3">
                <div class="text-2xl font-extrabold text-sky-700">
                    <?= $schoolWideCount ?>
                </div>
                <p class="text-xs text-slate-500 mt-1">
                    General institutional circulars
                </p>
            </div>
        </div>

    </div>

    <!-- 4. FEED HEADER & INTERACTIVE FILTER PILLS -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h3 class="text-base font-bold text-slate-900">
                Notice Register & Circular Feed
            </h3>
            <p class="text-xs text-slate-500 mt-0.5">
                Official broadcasts sorted in reverse chronological order.
            </p>
        </div>

        <div class="flex items-center gap-1.5 bg-slate-100 p-1 rounded-xl" role="tablist">
            <button type="button" onclick="filterBulletins('all')" id="btn-filter-all"
                    class="bulletin-filter-btn px-3 py-1 rounded-lg text-xs font-bold bg-white text-slate-800 shadow-xs transition">
                All (<?= $totalNotices ?>)
            </button>
            <?php if ($unreadCount > 0): ?>
                <button type="button" onclick="filterBulletins('unread')" id="btn-filter-unread"
                        class="bulletin-filter-btn px-3 py-1 rounded-lg text-xs font-bold text-slate-600 hover:text-slate-900 transition">
                    Unread (<?= $unreadCount ?>)
                </button>
            <?php endif; ?>
            <button type="button" onclick="filterBulletins('class')" id="btn-filter-class"
                    class="bulletin-filter-btn px-3 py-1 rounded-lg text-xs font-bold text-slate-600 hover:text-slate-900 transition">
                Class Only (<?= $classCount ?>)
            </button>
            <button type="button" onclick="filterBulletins('school')" id="btn-filter-school"
                    class="bulletin-filter-btn px-3 py-1 rounded-lg text-xs font-bold text-slate-600 hover:text-slate-900 transition">
                School-Wide (<?= $schoolWideCount ?>)
            </button>
        </div>
    </div>

    <!-- 5. BULLETIN FEED CARDS -->
    <?php if (empty($feed)): ?>
        <div class="bg-white p-12 text-center rounded-2xl border border-slate-200 shadow-xs">
            <div class="w-12 h-12 rounded-2xl bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-3">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                </svg>
            </div>
            <h4 class="text-sm font-bold text-slate-900">No Active Announcements</h4>
            <p class="text-xs text-slate-500 max-w-sm mx-auto mt-1">
                There are no official bulletins or circulars published for <?= htmlspecialchars($childName) ?> at this time.
            </p>
        </div>
    <?php else: ?>
        <div class="space-y-4" id="bulletins-container">
            <?php foreach ($feed as $item): 
                $isRead = is_object($item) ? $item->isRead : !empty($item['read_at']);
                $targetType = is_object($item) ? ($item->targetType ?? '') : ($item['target_type'] ?? '');
                $targetName = is_object($item) ? ($item->targetName ?? 'School-wide') : ($item['target_name'] ?? 'School-wide');
                $pubDate = is_object($item) ? ($item->publishedAt ?? $item->createdAt) : ($item['published_at'] ?? $item['created_at']);
                $title = is_object($item) ? $item->title : $item['title'];
                $body = is_object($item) ? $item->body : $item['body'];
                $authorName = is_object($item) ? ($item->authorName ?? 'School Administration') : ($item['author_name'] ?? 'School Administration');
                $itemId = is_object($item) ? (int)$item->id : (int)$item['id'];

                $isClassTarget = ($targetType === 'class');
            ?>
                <div class="bulletin-card p-6 rounded-2xl border transition-all duration-200 shadow-xs <?= $isRead ? 'bg-white border-slate-200 hover:border-slate-300' : 'bg-white border-brand-300 border-l-4 border-l-brand-700' ?>"
                     data-read="<?= $isRead ? 'read' : 'unread' ?>"
                     data-type="<?= $isClassTarget ? 'class' : 'school' ?>">
                    
                    <!-- Card Top Header -->
                    <div class="flex flex-wrap items-center justify-between gap-3 pb-3 border-b border-slate-100">
                        <div class="flex items-center gap-2">
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold <?= $isClassTarget ? 'bg-indigo-50 text-indigo-700 border border-indigo-200' : 'bg-sky-50 text-sky-700 border border-sky-200' ?>">
                                <span class="w-1.5 h-1.5 rounded-full <?= $isClassTarget ? 'bg-indigo-500' : 'bg-sky-500' ?>"></span>
                                <span><?= htmlspecialchars($targetName) ?></span>
                            </span>

                            <?php if (!$isRead): ?>
                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-brand-50 text-brand-700 border border-brand-200">
                                    <span class="w-1.5 h-1.5 rounded-full bg-brand-600 animate-pulse"></span>
                                    <span>New Notice</span>
                                </span>
                            <?php endif; ?>
                        </div>

                        <div class="flex items-center gap-2 text-xs text-slate-400 font-medium">
                            <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span><?= htmlspecialchars(date('D, M d, Y \a\t h:i A', strtotime($pubDate))) ?></span>
                        </div>
                    </div>

                    <!-- Title & Content -->
                    <div class="mt-4">
                        <h3 class="text-lg font-bold text-slate-900 tracking-tight">
                            <?= htmlspecialchars($title) ?>
                        </h3>
                        <div class="text-sm text-slate-700 mt-2.5 whitespace-pre-line leading-relaxed">
                            <?= htmlspecialchars($body) ?>
                        </div>
                    </div>

                    <!-- Footer: Author Signature & Mark as Read -->
                    <div class="mt-5 pt-3.5 border-t border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs">
                        <div class="flex items-center gap-2 text-slate-500">
                            <span class="font-semibold text-slate-400">Issued by:</span>
                            <span class="font-bold text-slate-800 bg-slate-50 px-2.5 py-1 rounded-lg border border-slate-200">
                                <?= htmlspecialchars($authorName) ?>
                            </span>
                        </div>

                        <div>
                            <?php if (!$isRead): ?>
                                <form method="POST" action="/parent/announcements/<?= $itemId ?>/read" class="inline">
                                    <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                                    <button type="submit" 
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold bg-brand-50 hover:bg-brand-100 text-brand-700 border border-brand-200 transition">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                        </svg>
                                        <span>Acknowledge & Mark Read</span>
                                    </button>
                                </form>
                            <?php else: ?>
                                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold text-emerald-700 bg-emerald-50 border border-emerald-200">
                                    <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    <span>Acknowledged</span>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>

                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- 6. OFFICIAL SCHOOL COMMUNICATION ADVISORY CARD -->
    <div class="bg-brand-50 border border-brand-100 rounded-2xl p-5 flex flex-col sm:flex-row sm:items-start gap-4 shadow-xs">
        <div class="w-10 h-10 rounded-xl bg-brand-700 text-white flex items-center justify-center flex-shrink-0 shadow-xs">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>
        <div>
            <h4 class="text-sm font-bold text-brand-900">
                Official Claret Communication & Parent-School Partnership Policy
            </h4>
            <p class="text-xs text-slate-600 mt-1 leading-relaxed">
                All bulletins published on this portal are official instruments of Claret International School. Urgent emergency notifications, terminal holiday releases, and examination dates are also broadcast via parent SMS.
            </p>
            <div class="mt-2 text-xs text-brand-700 font-semibold flex flex-wrap gap-4">
                <span>&bull; Contact the School Registry for inquiries: info@claret.edu</span>
                <span>&bull; PTA Meetings are scheduled termly; formal circulars are delivered 14 days in advance.</span>
            </div>
        </div>
    </div>

</div>

<!-- Interactive Client-side Filter Script -->
<script>
    function filterBulletins(filter) {
        const cards = document.querySelectorAll('.bulletin-card');
        const buttons = document.querySelectorAll('.bulletin-filter-btn');

        // Toggle active button styling
        buttons.forEach(btn => {
            btn.classList.remove('bg-white', 'text-slate-800', 'shadow-xs');
            btn.classList.add('text-slate-600');
        });

        const activeBtn = document.getElementById('btn-filter-' + filter);
        if (activeBtn) {
            activeBtn.classList.remove('text-slate-600');
            activeBtn.classList.add('bg-white', 'text-slate-800', 'shadow-xs');
        }

        // Show/hide cards
        cards.forEach(card => {
            const isRead = card.getAttribute('data-read');
            const type = card.getAttribute('data-type');

            if (filter === 'all') {
                card.style.display = '';
            } else if (filter === 'unread') {
                card.style.display = (isRead === 'unread') ? '' : 'none';
            } else if (filter === 'class') {
                card.style.display = (type === 'class') ? '' : 'none';
            } else if (filter === 'school') {
                card.style.display = (type === 'school') ? '' : 'none';
            }
        });
    }
</script>
