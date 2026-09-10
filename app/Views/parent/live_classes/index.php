<div class="space-y-6">
    <!-- Header Card -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <!-- Breadcrumbs -->
                <nav class="flex items-center gap-2 text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">
                    <a href="/parent/dashboard" class="text-slate-400 hover:text-emerald-600 transition">Guardian Portal</a>
                    <span class="text-slate-300">/</span>
                    <span class="text-slate-700">Live Online Classes</span>
                </nav>
                <div class="flex flex-wrap items-center gap-3">
                    <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight leading-tight">
                        Virtual Classroom Schedule
                    </h1>
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-bold bg-indigo-50 text-indigo-800 border border-indigo-200">
                        Ward Monitoring
                    </span>
                </div>
                <p class="text-xs text-slate-500 mt-1">
                    Monitor your children's live remote learning sessions, video conference schedules, and participation attendance.
                </p>
            </div>
        </div>
    </div>

    <!-- Ward Live Classes Overview -->
    <?php if (empty($childrenData)): ?>
        <div class="bg-white rounded-2xl border border-slate-200 p-12 text-center shadow-xs">
            <div class="w-16 h-16 rounded-2xl bg-slate-100 text-slate-400 mx-auto flex items-center justify-center mb-4">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                </svg>
            </div>
            <h3 class="text-base font-bold text-slate-900">No Linked Students Found</h3>
            <p class="text-xs text-slate-500 mt-1 max-w-sm mx-auto">
                No active student accounts are currently linked to your guardian profile. Please contact the school registry.
            </p>
        </div>
    <?php else: ?>
        <div class="space-y-8">
            <?php foreach ($childrenData as $childEntry): ?>
                <?php 
                    $stu = $childEntry['student']; 
                    $classes = $childEntry['live_classes'];
                ?>
                <div class="space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-200 pb-3">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-emerald-100 text-emerald-800 font-extrabold flex items-center justify-center text-sm">
                                <?= strtoupper(substr($stu['student_name'] ?? 'S', 0, 1)) ?>
                            </div>
                            <div>
                                <h2 class="text-sm font-extrabold text-slate-900">
                                    <?= htmlspecialchars($stu['student_name'] ?? 'Ward') ?>
                                </h2>
                                <p class="text-xs text-slate-500">
                                    <?= htmlspecialchars($stu['class_name'] ?? 'Cohort') ?> • Adm: <?= htmlspecialchars($stu['admission_number'] ?? '—') ?>
                                </p>
                            </div>
                        </div>
                        <span class="text-xs font-bold text-slate-700 bg-slate-100 px-3 py-1 rounded-lg border border-slate-200">
                            <?= count($classes) ?> Session<?= count($classes) === 1 ? '' : 's' ?>
                        </span>
                    </div>

                    <?php if (empty($classes)): ?>
                        <div class="bg-white rounded-xl border border-slate-200 p-6 text-center text-xs text-slate-500 shadow-xs">
                            No online classes scheduled for this student cohort at the moment.
                        </div>
                    <?php else: ?>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <?php foreach ($classes as $lc): ?>
                                <div class="bg-white rounded-xl border border-slate-200 shadow-xs p-4 flex flex-col justify-between space-y-3 hover:border-slate-300 transition">
                                    <div class="space-y-2">
                                        <div class="flex items-center justify-between gap-2">
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs border <?= $lc->getPlatformColorClass() ?>">
                                                <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                                </svg>
                                                <span><?= htmlspecialchars($lc->getPlatformLabel()) ?></span>
                                            </span>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold border <?= $lc->getStatusBadgeClass() ?>">
                                                <?= ucfirst(str_replace('_', ' ', $lc->status)) ?>
                                            </span>
                                        </div>

                                        <h3 class="text-sm font-bold text-slate-900 leading-snug">
                                            <?= htmlspecialchars($lc->title) ?>
                                        </h3>

                                        <div class="text-xs font-semibold text-emerald-700">
                                            <?= htmlspecialchars($lc->subjectName ?? 'Subject') ?> • <?= htmlspecialchars($lc->teacherName ?? 'Teacher') ?>
                                        </div>
                                    </div>

                                    <div class="pt-2 border-t border-slate-100 space-y-1.5 text-[11px] text-slate-500">
                                        <div class="flex items-center gap-1.5">
                                            <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                            </svg>
                                            <span><?= htmlspecialchars($lc->formatSchedule()) ?></span>
                                        </div>

                                        <?php if (!empty($lc->meetingPasscode)): ?>
                                            <div class="flex items-center gap-1.5">
                                                <span>Passcode: <code class="bg-slate-100 border border-slate-200 px-1.5 py-0.5 rounded font-mono font-bold text-slate-800"><?= htmlspecialchars($lc->meetingPasscode) ?></code></span>
                                            </div>
                                        <?php endif; ?>

                                        <div class="pt-1">
                                            <?php if ($lc->hasJoined): ?>
                                                <span class="inline-flex items-center gap-1 text-[11px] font-bold text-emerald-800 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200">
                                                    <svg class="w-3 h-3 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                                    </svg>
                                                    Ward Joined & Verified
                                                </span>
                                            <?php else: ?>
                                                <span class="text-slate-400 text-[11px]">Not logged yet</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
