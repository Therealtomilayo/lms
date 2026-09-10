<div class="space-y-6">
    <!-- Header Card -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <!-- Breadcrumbs -->
                <nav class="flex items-center gap-2 text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">
                    <a href="/admin/dashboard" class="text-slate-400 hover:text-emerald-600 transition">Admin Console</a>
                    <span class="text-slate-300">/</span>
                    <a href="/admin/live-classes" class="text-slate-400 hover:text-emerald-600 transition">Live Online Classes</a>
                    <span class="text-slate-300">/</span>
                    <span class="text-slate-700">Attendance Audit</span>
                </nav>
                <div class="flex flex-wrap items-center gap-3">
                    <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight leading-tight">
                        <?= htmlspecialchars($liveClass->title) ?>
                    </h1>
                    <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-semibold border <?= $liveClass->getStatusBadgeClass() ?>">
                        <?= ucfirst(str_replace('_', ' ', $liveClass->status)) ?>
                    </span>
                </div>
                <p class="text-xs text-slate-500 mt-1">
                    Instructor: <?= htmlspecialchars($liveClass->teacherName ?? '—') ?> • Class: <?= htmlspecialchars($liveClass->className ?? '—') ?> • <?= htmlspecialchars($liveClass->formatSchedule()) ?>
                </p>
            </div>

            <div class="flex items-center gap-2.5">
                <a href="/admin/live-classes" class="inline-flex items-center gap-2 px-4 py-2 bg-slate-100 text-slate-700 hover:bg-slate-200 text-xs font-bold rounded-xl transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    <span>Back to Directory</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Attendance Audit Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="p-5 border-b border-slate-100 flex items-center justify-between flex-wrap gap-3">
            <div>
                <h2 class="text-base font-bold text-slate-900">
                    Attendee Forensic Audit Trail (<?= count($attendees) ?> Logged)
                </h2>
                <p class="text-xs text-slate-500 mt-0.5">
                    Tamper-evident logs of student video conference entry under SRS §31.
                </p>
            </div>
            <a href="<?= htmlspecialchars($liveClass->meetingLink) ?>" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold bg-slate-100 text-slate-700 hover:bg-slate-200 transition border border-slate-200">
                <span>Inspect Video Room</span>
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                </svg>
            </a>
        </div>

        <?php if (empty($attendees)): ?>
            <div class="p-12 text-center">
                <div class="w-14 h-14 rounded-2xl bg-slate-100 text-slate-400 mx-auto flex items-center justify-center mb-3">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                </div>
                <h3 class="text-sm font-bold text-slate-800">No Attendees Recorded</h3>
                <p class="text-xs text-slate-500 mt-1">No students have joined this online class session yet.</p>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="border-b border-slate-100 bg-slate-50/50 text-slate-500 uppercase tracking-wider font-bold text-[11px]">
                            <th class="py-3 px-4">#</th>
                            <th class="py-3 px-4">Student</th>
                            <th class="py-3 px-4">Admission No.</th>
                            <th class="py-3 px-4">Class</th>
                            <th class="py-3 px-4">Initial Join (WAT)</th>
                            <th class="py-3 px-4">Last Activity</th>
                            <th class="py-3 px-4">Origin IP</th>
                            <th class="py-3 px-4">Client Agent</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-medium">
                        <?php foreach ($attendees as $idx => $att): ?>
                            <tr class="hover:bg-slate-50/50 transition">
                                <td class="py-3 px-4 text-slate-400"><?= $idx + 1 ?></td>
                                <td class="py-3 px-4 font-bold text-slate-900">
                                    <?= htmlspecialchars($att->studentName ?? 'Student #' . $att->studentId) ?>
                                </td>
                                <td class="py-3 px-4 font-mono text-slate-600">
                                    <?= htmlspecialchars($att->admissionNumber ?? '—') ?>
                                </td>
                                <td class="py-3 px-4 text-slate-600">
                                    <?= htmlspecialchars($att->className ?? '—') ?>
                                </td>
                                <td class="py-3 px-4 text-slate-800 font-semibold">
                                    <?= date('d M Y, g:i:s A', strtotime($att->joinedAt)) ?>
                                </td>
                                <td class="py-3 px-4 text-slate-500">
                                    <?= $att->lastSeenAt ? date('g:i:s A', strtotime($att->lastSeenAt)) : '—' ?>
                                </td>
                                <td class="py-3 px-4 font-mono text-slate-500 text-[11px]">
                                    <?= htmlspecialchars($att->ipAddress ?? '127.0.0.1') ?>
                                </td>
                                <td class="py-3 px-4 text-slate-500 text-[11px] max-w-xs truncate" title="<?= htmlspecialchars($att->userAgent ?? '') ?>">
                                    <?= htmlspecialchars($att->userAgent ? substr($att->userAgent, 0, 50) . '...' : '—') ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
