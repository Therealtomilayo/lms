<div class="space-y-6">
    <!-- Header Card -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <!-- Breadcrumbs -->
                <nav class="flex items-center gap-2 text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">
                    <a href="/teacher/dashboard" class="text-slate-400 hover:text-emerald-600 transition">Faculty Portal</a>
                    <span class="text-slate-300">/</span>
                    <span class="text-slate-700">Announcements & Bulletins</span>
                </nav>
                <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight leading-tight">
                    Class & School Announcements
                </h1>
                <p class="text-xs text-slate-500 mt-1">
                    Broadcast instructional updates, homework reminders, and administrative notifications to assigned student groups.
                </p>
            </div>

            <div class="flex items-center gap-2.5">
                <?php $this->include('components/button', [
                    'label' => 'Post Announcement',
                    'variant' => 'primary',
                    'href' => '/teacher/announcements/create',
                    'icon' => '<svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>'
                ]); ?>
            </div>
        </div>
    </div>

    <!-- Feed & My Publications Layout -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Announcements Feed (2 Cols) -->
        <div class="lg:col-span-2 space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="text-xs font-bold uppercase tracking-wider text-slate-700">Live Campus & Class Feed</h2>
                <span class="text-xs font-bold text-emerald-700 bg-emerald-50 px-2.5 py-0.5 rounded-md border border-emerald-200">
                    <?= count($feed) ?> Active Bulletin<?= count($feed) === 1 ? '' : 's' ?>
                </span>
            </div>

            <?php if (empty($feed)): ?>
                <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-12 text-center">
                    <div class="w-16 h-16 rounded-2xl bg-emerald-50 text-emerald-600 mx-auto flex items-center justify-center mb-4">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/>
                        </svg>
                    </div>
                    <h3 class="text-base font-bold text-slate-900">No Active Announcements</h3>
                    <p class="text-xs text-slate-500 mt-1.5 max-w-sm mx-auto">
                        There are currently no active bulletins targeted to your profile or class cohorts.
                    </p>
                </div>
            <?php else: ?>
                <div class="space-y-4">
                    <?php foreach ($feed as $item): ?>
                        <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-xs space-y-3 hover:border-slate-300 transition">
                            <div class="flex items-center justify-between gap-2 flex-wrap">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold bg-emerald-50 text-emerald-800 border border-emerald-200">
                                    <?= htmlspecialchars($item->targetName ?? 'School-wide') ?>
                                </span>
                                <span class="text-[11px] font-semibold text-slate-400">
                                    <?= date('M d, Y · g:i A', strtotime($item->publishedAt ?? $item->createdAt)) ?>
                                </span>
                            </div>

                            <h3 class="text-base font-bold text-slate-900 leading-snug">
                                <?= htmlspecialchars($item->title) ?>
                            </h3>

                            <p class="text-xs text-slate-700 whitespace-pre-line leading-relaxed">
                                <?= htmlspecialchars($item->body) ?>
                            </p>

                            <div class="text-[11px] text-slate-400 pt-3 border-t border-slate-100 flex items-center justify-between">
                                <span>Author: <strong class="text-slate-600 font-semibold"><?= htmlspecialchars($item->authorName ?? 'Faculty Member') ?></strong></span>
                                <?php if (!empty($item->expiresAt)): ?>
                                    <span>Expires: <?= date('M d, Y', strtotime($item->expiresAt)) ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- My Broadcasts Sidebar (1 Col) -->
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="text-xs font-bold uppercase tracking-wider text-slate-700">My Publications</h2>
                <span class="text-xs font-semibold text-slate-400">
                    <?= count($myAnnouncements) ?> posted
                </span>
            </div>

            <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs space-y-3">
                <?php if (empty($myAnnouncements)): ?>
                    <p class="text-xs text-slate-400 py-6 text-center">
                        You haven't posted any announcements yet.
                    </p>
                <?php else: ?>
                    <div class="divide-y divide-slate-100">
                        <?php foreach ($myAnnouncements as $my): ?>
                            <div class="py-3.5 first:pt-0 last:pb-0 space-y-1">
                                <h4 class="font-bold text-xs text-slate-900 leading-snug">
                                    <?= htmlspecialchars($my->title) ?>
                                </h4>
                                <div class="text-[11px] text-slate-400 flex items-center justify-between">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md bg-slate-100 text-slate-700 font-semibold text-[10px]">
                                        <?= htmlspecialchars($my->targetName ?? 'Class') ?>
                                    </span>
                                    <span><?= date('M d, Y', strtotime($my->createdAt)) ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
