<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
        <div>
            <h1 class="text-xl font-bold text-slate-900">Broadcast Announcements Hub</h1>
            <p class="text-sm text-slate-600 mt-1">Manage institutional, class-level, and subject-specific announcements.</p>
        </div>
        <div>
            <a href="/admin/announcements/create" class="inline-flex items-center px-4 py-2 bg-brand-600 text-white rounded-lg text-sm font-medium hover:bg-brand-700 transition">
                + Broadcast Announcement
            </a>
        </div>
    </div>

    <!-- Announcements List -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-4 bg-slate-50 border-b border-slate-200 font-semibold text-sm text-slate-800">
            All Broadcast Announcements
        </div>

        <?php if (empty($announcements)): ?>
            <div class="p-8 text-center text-slate-500 text-sm">
                No announcements have been broadcasted yet.
            </div>
        <?php else: ?>
            <div class="divide-y divide-slate-200">
                <?php foreach ($announcements as $a): ?>
                    <div class="p-5 flex flex-col md:flex-row md:items-center justify-between gap-4 hover:bg-slate-50">
                        <div class="space-y-1.5 max-w-3xl">
                            <div class="flex items-center gap-2">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-brand-50 text-brand-700 border border-brand-200">
                                    <?= htmlspecialchars($a->targetName ?? 'School-wide') ?>
                                </span>
                                <?php if ($a->isActive()): ?>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-emerald-100 text-emerald-800">Active</span>
                                <?php elseif ($a->isExpired()): ?>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-slate-100 text-slate-600">Expired</span>
                                <?php else: ?>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-amber-100 text-amber-800">Scheduled</span>
                                <?php endif; ?>
                                <span class="text-xs text-slate-400">
                                    Published: <?= htmlspecialchars(date('M d, Y', strtotime($a->publishedAt ?? $a->createdAt))) ?>
                                </span>
                            </div>
                            <h3 class="text-base font-bold text-slate-900"><?= htmlspecialchars($a->title) ?></h3>
                            <p class="text-sm text-slate-600 line-clamp-2"><?= htmlspecialchars($a->body) ?></p>
                            <div class="text-xs text-slate-500">
                                Posted by: <span class="font-medium"><?= htmlspecialchars($a->authorName ?? 'Admin') ?></span>
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            <a href="/admin/announcements/<?= (int)$a->id ?>/edit" class="px-3 py-1.5 text-xs font-medium text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50">
                                Edit
                            </a>
                            <form method="POST" action="/admin/announcements/<?= (int)$a->id ?>/delete" onsubmit="return confirm('Are you sure you want to delete this announcement?');">
                                <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($_SESSION['_csrf_token'] ?? '') ?>">
                                <button type="submit" class="px-3 py-1.5 text-xs font-medium text-rose-700 bg-rose-50 border border-rose-200 rounded-lg hover:bg-rose-100">
                                    Delete
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
