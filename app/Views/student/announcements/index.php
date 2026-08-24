<div class="max-w-4xl mx-auto space-y-6">
    <!-- Header Card -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <!-- Breadcrumbs -->
                <nav class="flex items-center gap-2 text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">
                    <a href="/student/dashboard" class="hover:text-sky-600 transition">Student Portal</a>
                    <span class="text-slate-300">/</span>
                    <span class="text-slate-700">Campus Notices</span>
                </nav>
                <div class="flex flex-wrap items-center gap-3">
                    <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight leading-tight">
                        Announcements & Bulletins
                    </h1>
                    <?php if ($student && $student->schoolClass): ?>
                        <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold bg-sky-50 text-sky-800 border border-sky-200">
                            <?= htmlspecialchars($student->schoolClass->name) ?>
                        </span>
                    <?php endif; ?>
                </div>
                <p class="text-xs text-slate-500 mt-1">
                    Official updates, school newsletters, and class broadcasts tailored for you.
                </p>
            </div>

            <div class="flex items-center gap-2.5 flex-wrap">
                <a href="/student/dashboard" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-bold text-slate-700 bg-slate-100 hover:bg-slate-200 transition">
                    <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                    </svg>
                    <span>Dashboard</span>
                </a>
            </div>
        </div>
    </div>

    <!-- 4-Card KPI Summary Metrics Strip -->
    <?php
        $totalNotices = count($feed);
        $unreadCount = 0;
        foreach ($feed as $item) {
            if (!$item->isRead) {
                $unreadCount++;
            }
        }
        $readCount = $totalNotices - $unreadCount;
    ?>
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <!-- Total Notices -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs">
            <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Total Bulletins</p>
            <div class="flex items-baseline gap-1 mt-1">
                <h3 class="text-2xl font-extrabold text-slate-900"><?= $totalNotices ?></h3>
                <span class="text-xs font-semibold text-slate-500">notices</span>
            </div>
            <span class="text-[11px] font-medium text-slate-500 mt-1 block">
                Broadcast Feed
            </span>
        </div>

        <!-- Unread Notices -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs">
            <p class="text-xs font-bold uppercase tracking-wider text-rose-500">Unread</p>
            <div class="flex items-baseline gap-1 mt-1">
                <h3 class="text-2xl font-extrabold text-rose-600"><?= $unreadCount ?></h3>
                <span class="text-xs font-semibold text-slate-500">new</span>
            </div>
            <span class="text-[11px] font-medium text-rose-600 mt-1 block">
                <?= $unreadCount > 0 ? 'Requires attention' : 'All caught up' ?>
            </span>
        </div>

        <!-- Read Notices -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs">
            <p class="text-xs font-bold uppercase tracking-wider text-emerald-600">Acknowledged</p>
            <div class="flex items-baseline gap-1 mt-1">
                <h3 class="text-2xl font-extrabold text-emerald-600"><?= $readCount ?></h3>
                <span class="text-xs font-semibold text-slate-500">read</span>
            </div>
            <span class="text-[11px] font-medium text-emerald-600 mt-1 block">
                Archived notices
            </span>
        </div>

        <!-- Channels -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs">
            <p class="text-xs font-bold uppercase tracking-wider text-sky-600">Scope</p>
            <div class="flex items-baseline gap-1 mt-1">
                <h3 class="text-base font-extrabold text-slate-900">School & Class</h3>
            </div>
            <span class="text-[11px] font-medium text-slate-500 mt-1 block">
                Active Channels
            </span>
        </div>
    </div>

    <!-- Live Search Filter -->
    <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-xs">
        <div class="relative">
            <input type="text" id="announcement-search" placeholder="Search notices by title, keyword, or sender..." 
                   oninput="filterAnnouncements(this.value)"
                   class="w-full text-xs font-semibold rounded-xl border border-slate-300 bg-slate-50 focus:bg-white focus:border-sky-500 focus:ring-sky-500 py-2.5 pl-10 pr-4 transition">
            <svg class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
        </div>
    </div>

    <!-- Feed List -->
    <?php if (empty($feed)): ?>
        <div class="bg-white p-12 text-center rounded-2xl border border-slate-200 shadow-xs text-slate-500 space-y-3">
            <div class="w-14 h-14 rounded-2xl bg-sky-50 text-sky-600 mx-auto flex items-center justify-center">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                </svg>
            </div>
            <h3 class="text-base font-extrabold text-slate-900">No Announcements</h3>
            <p class="text-xs text-slate-500">You are all caught up! No active notices or bulletins posted at this time.</p>
        </div>
    <?php else: ?>
        <div class="space-y-4" id="announcements-feed-container">
            <?php foreach ($feed as $item): ?>
                <div class="announcement-card p-6 rounded-2xl border transition space-y-3 <?= $item->isRead ? 'bg-white border-slate-200 shadow-xs' : 'bg-sky-50/40 border-sky-200 shadow-xs ring-1 ring-sky-200' ?>" 
                     id="announcement-card-<?= (int)$item->id ?>"
                     data-search="<?= strtolower(htmlspecialchars($item->title . ' ' . $item->body . ' ' . ($item->targetName ?? '') . ' ' . ($item->authorName ?? ''))) ?>">
                    <div class="flex items-center justify-between gap-4 flex-wrap">
                        <div class="flex items-center gap-2">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-[10px] font-extrabold uppercase tracking-wider <?= $item->isRead ? 'bg-slate-100 text-slate-700' : 'bg-sky-600 text-white' ?>">
                                <?= htmlspecialchars($item->targetName ?? 'School-wide') ?>
                            </span>
                            <?php if (!$item->isRead): ?>
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200">
                                    <span class="w-1.5 h-1.5 rounded-full bg-rose-500 animate-ping"></span>
                                    <span>New Notice</span>
                                </span>
                            <?php endif; ?>
                        </div>
                        <span class="text-xs font-semibold text-slate-400 font-mono">
                            <?= htmlspecialchars(date('M d, Y · g:i A', strtotime($item->publishedAt ?? $item->createdAt))) ?>
                        </span>
                    </div>

                    <h3 class="text-base font-extrabold text-slate-900 leading-snug">
                        <?= htmlspecialchars($item->title) ?>
                    </h3>
                    
                    <p class="text-xs text-slate-700 whitespace-pre-line leading-relaxed">
                        <?= htmlspecialchars($item->body) ?>
                    </p>

                    <div class="pt-3 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500 flex-wrap gap-2">
                        <span>Posted by: <strong class="text-slate-800 font-bold"><?= htmlspecialchars($item->authorName ?? 'Administration') ?></strong></span>
                        <?php if (!$item->isRead): ?>
                            <form method="POST" action="/student/announcements/<?= (int)$item->id ?>/read">
                                <?= csrf_field() ?>
                                <button type="submit" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-bold bg-sky-600 hover:bg-sky-700 text-white shadow-xs transition">
                                    <span>Mark as Read</span>
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                </button>
                            </form>
                        <?php else: ?>
                            <span class="text-emerald-700 font-bold flex items-center gap-1 text-[11px]">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                <span>Acknowledged</span>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script>
function filterAnnouncements(query) {
    const q = query.trim().toLowerCase();
    const cards = document.querySelectorAll('.announcement-card');
    cards.forEach(card => {
        const text = card.getAttribute('data-search') || '';
        if (!q || text.includes(q)) {
            card.style.display = 'block';
        } else {
            card.style.display = 'none';
        }
    });
}
</script>
