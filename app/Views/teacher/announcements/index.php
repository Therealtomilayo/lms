<div class="max-w-6xl mx-auto space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Announcements</h1>
            <p class="text-sm text-slate-600 mt-1">Broadcast important updates to your classes and view school announcements.</p>
        </div>
        <div>
            <a href="/teacher/announcements/create" class="inline-flex items-center px-4 py-2 bg-brand-600 text-white rounded-lg text-sm font-medium hover:bg-brand-700 transition">
                + New Announcement
            </a>
        </div>
    </div>

    <!-- Feed Section -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Announcements Feed -->
        <div class="lg:col-span-2 space-y-4">
            <h2 class="text-lg font-semibold text-slate-900">Targeted Feed</h2>
            <?php if (empty($feed)): ?>
                <div class="p-8 text-center bg-white rounded-xl border border-slate-200 text-slate-500">
                    No active announcements found.
                </div>
            <?php else: ?>
                <?php foreach ($feed as $item): ?>
                    <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-brand-50 text-brand-700 border border-brand-200">
                                <?= htmlspecialchars($item->targetName ?? 'School-wide') ?>
                            </span>
                            <span class="text-xs text-slate-400">
                                <?= htmlspecialchars(date('M d, Y H:i', strtotime($item->publishedAt ?? $item->createdAt))) ?>
                            </span>
                        </div>
                        <h3 class="text-lg font-bold text-slate-900"><?= htmlspecialchars($item->title) ?></h3>
                        <p class="text-sm text-slate-700 whitespace-pre-line leading-relaxed"><?= htmlspecialchars($item->body) ?></p>
                        <div class="text-xs text-slate-500 pt-2 border-t border-slate-100 flex items-center justify-between">
                            <span>Author: <?= htmlspecialchars($item->authorName ?? 'Staff') ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- My Broadcasts Sidebar -->
        <div class="space-y-4">
            <h2 class="text-lg font-semibold text-slate-900">My Broadcasts</h2>
            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm space-y-4">
                <?php if (empty($myAnnouncements)): ?>
                    <p class="text-xs text-slate-500">You haven't posted any announcements yet.</p>
                <?php else: ?>
                    <div class="divide-y divide-slate-100">
                        <?php foreach ($myAnnouncements as $my): ?>
                            <div class="py-3 first:pt-0 last:pb-0">
                                <div class="font-medium text-sm text-slate-900"><?= htmlspecialchars($my->title) ?></div>
                                <div class="text-xs text-slate-500 mt-1 flex items-center justify-between">
                                    <span><?= htmlspecialchars($my->targetName ?? 'Class') ?></span>
                                    <span><?= htmlspecialchars(date('M d', strtotime($my->createdAt))) ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
