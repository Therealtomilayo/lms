<div class="space-y-6">
    <!-- Header Card -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <!-- Breadcrumbs -->
                <nav class="flex items-center gap-2 text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">
                    <a href="/admin/dashboard" class="text-slate-400 hover:text-emerald-600 transition">Admin Console</a>
                    <span class="text-slate-300">/</span>
                    <span class="text-slate-700">Live Online Classes</span>
                </nav>
                <div class="flex flex-wrap items-center gap-3">
                    <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight leading-tight">
                        Institutional Live Classes Register
                    </h1>
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-bold bg-sky-50 text-sky-800 border border-sky-200">
                        SRS §31 Oversight
                    </span>
                </div>
                <p class="text-xs text-slate-500 mt-1">
                    Administrative governance, audit logging, and moderation of all faculty scheduled virtual conference classrooms.
                </p>
            </div>

            <div class="flex items-center gap-2">
                <span class="text-xs font-bold text-slate-700 bg-slate-100 px-3 py-1.5 rounded-xl border border-slate-200">
                    <?= $total ?> Total Session<?= $total === 1 ? '' : 's' ?>
                </span>
            </div>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-4">
        <form method="GET" action="/admin/live-classes" class="grid grid-cols-1 sm:grid-cols-4 gap-3">
            <div>
                <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">Session</label>
                <select name="session_id" class="w-full text-xs font-semibold rounded-xl border border-slate-200 bg-white text-slate-800 py-2 px-3 focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500">
                    <option value="">All Sessions</option>
                    <?php foreach ($sessions as $s): ?>
                        <option value="<?= $s->id ?>" <?= $selectedSessionId === $s->id ? 'selected' : '' ?>>
                            <?= htmlspecialchars($s->name) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">Term</label>
                <select name="term_id" class="w-full text-xs font-semibold rounded-xl border border-slate-200 bg-white text-slate-800 py-2 px-3 focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500">
                    <option value="">All Terms</option>
                    <?php foreach ($terms as $t): ?>
                        <option value="<?= $t->id ?>" <?= $selectedTermId === $t->id ? 'selected' : '' ?>>
                            <?= htmlspecialchars($t->name) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">Status</label>
                <select name="status" class="w-full text-xs font-semibold rounded-xl border border-slate-200 bg-white text-slate-800 py-2 px-3 focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500">
                    <option value="">All Statuses</option>
                    <option value="scheduled" <?= $selectedStatus === 'scheduled' ? 'selected' : '' ?>>Scheduled</option>
                    <option value="in_progress" <?= $selectedStatus === 'in_progress' ? 'selected' : '' ?>>Live / In Progress</option>
                    <option value="completed" <?= $selectedStatus === 'completed' ? 'selected' : '' ?>>Completed</option>
                    <option value="cancelled" <?= $selectedStatus === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                </select>
            </div>

            <div class="flex items-end gap-2">
                <button type="submit" class="flex-1 py-2 px-4 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl transition shadow-xs">
                    Filter Records
                </button>
                <a href="/admin/live-classes" class="py-2 px-3 bg-slate-100 text-slate-600 text-xs font-semibold rounded-xl hover:bg-slate-200 transition">
                    Reset
                </a>
            </div>
        </form>
    </div>

    <!-- Live Classes Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
        <?php if (empty($liveClasses)): ?>
            <div class="p-12 text-center">
                <div class="w-14 h-14 rounded-2xl bg-slate-100 text-slate-400 mx-auto flex items-center justify-center mb-3">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                    </svg>
                </div>
                <h3 class="text-sm font-bold text-slate-800">No Live Classes Recorded</h3>
                <p class="text-xs text-slate-500 mt-1">No online class sessions match the chosen filter criteria.</p>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="border-b border-slate-100 bg-slate-50/50 text-slate-500 uppercase tracking-wider font-bold text-[11px]">
                            <th class="py-3 px-4">Subject & Title</th>
                            <th class="py-3 px-4">Class</th>
                            <th class="py-3 px-4">Instructor</th>
                            <th class="py-3 px-4">Platform</th>
                            <th class="py-3 px-4">Schedule</th>
                            <th class="py-3 px-4">Status</th>
                            <th class="py-3 px-4 text-center">Attendees</th>
                            <th class="py-3 px-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-medium">
                        <?php foreach ($liveClasses as $lc): ?>
                            <tr class="hover:bg-slate-50/50 transition">
                                <td class="py-3 px-4">
                                    <div class="font-bold text-slate-900"><?= htmlspecialchars($lc->title) ?></div>
                                    <div class="text-[11px] text-emerald-700 font-semibold"><?= htmlspecialchars($lc->subjectName ?? 'Subject') ?></div>
                                </td>
                                <td class="py-3 px-4 text-slate-700">
                                    <?= htmlspecialchars($lc->className ?? '—') ?>
                                </td>
                                <td class="py-3 px-4 text-slate-700">
                                    <?= htmlspecialchars($lc->teacherName ?? '—') ?>
                                </td>
                                <td class="py-3 px-4">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold border <?= $lc->getPlatformColorClass() ?>">
                                        <?= htmlspecialchars($lc->getPlatformLabel()) ?>
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-slate-600">
                                    <div><?= htmlspecialchars($lc->formatSchedule()) ?></div>
                                    <div class="text-[11px] text-slate-400"><?= $lc->durationMinutes ?> mins</div>
                                </td>
                                <td class="py-3 px-4">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold border <?= $lc->getStatusBadgeClass() ?>">
                                        <?= ucfirst(str_replace('_', ' ', $lc->status)) ?>
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <a href="/admin/live-classes/<?= $lc->id ?>/attendees" class="inline-flex items-center gap-1 text-xs font-bold text-emerald-700 hover:text-emerald-800">
                                        <span><?= $lc->attendeesCount ?></span>
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                        </svg>
                                    </a>
                                </td>
                                <td class="py-3 px-4 text-right space-x-1">
                                    <a href="<?= htmlspecialchars($lc->meetingLink) ?>" target="_blank" rel="noopener noreferrer" class="p-1 text-slate-400 hover:text-slate-700 inline-block" title="Inspect Meeting Room">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                        </svg>
                                    </a>

                                    <?php if (!$lc->isCompleted() && !$lc->isCancelled()): ?>
                                        <form method="POST" action="/admin/live-classes/<?= $lc->id ?>/cancel" onsubmit="return confirm('Cancel this online class?');" class="inline">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="p-1 text-slate-400 hover:text-amber-600 transition" title="Administrative Cancel">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                                                </svg>
                                            </button>
                                        </form>
                                    <?php endif; ?>

                                    <form method="POST" action="/admin/live-classes/<?= $lc->id ?>/delete" onsubmit="return confirm('Permanently delete this online class entry?');" class="inline">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="p-1 text-slate-400 hover:text-rose-600 transition" title="Administrative Delete">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <div class="p-4 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
                    <div>Page <?= $page ?> of <?= $totalPages ?> (<?= $total ?> records)</div>
                    <div class="flex items-center gap-1">
                        <?php if ($page > 1): ?>
                            <a href="/admin/live-classes?page=<?= $page - 1 ?>&session_id=<?= $selectedSessionId ?>&term_id=<?= $selectedTermId ?>&status=<?= $selectedStatus ?>" class="px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 transition">Previous</a>
                        <?php endif; ?>
                        <?php if ($page < $totalPages): ?>
                            <a href="/admin/live-classes?page=<?= $page + 1 ?>&session_id=<?= $selectedSessionId ?>&term_id=<?= $selectedTermId ?>&status=<?= $selectedStatus ?>" class="px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 transition">Next</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
