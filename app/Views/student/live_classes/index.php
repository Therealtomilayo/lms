<div class="space-y-6">
    <!-- Header Card -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <!-- Breadcrumbs -->
                <nav class="flex items-center gap-2 text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">
                    <a href="/student/dashboard" class="text-slate-400 hover:text-emerald-600 transition">Student Portal</a>
                    <span class="text-slate-300">/</span>
                    <span class="text-slate-700">Live Online Classes</span>
                </nav>
                <div class="flex flex-wrap items-center gap-3">
                    <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight leading-tight">
                        Online Virtual Classrooms
                    </h1>
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-bold bg-rose-50 text-rose-800 border border-rose-200">
                        <span class="w-2 h-2 rounded-full bg-rose-500 animate-pulse"></span>
                        Synchronous Learning Hub
                    </span>
                </div>
                <p class="text-xs text-slate-500 mt-1">
                    Attend scheduled lessons with your teachers via Google Meet, Zoom, or Teams. Your attendance is automatically verified when you join.
                </p>
            </div>

            <div class="flex items-center gap-2">
                <span class="text-xs font-bold text-slate-700 bg-slate-100 px-3 py-1.5 rounded-xl border border-slate-200">
                    <?= htmlspecialchars($student->className ?? 'Enrolled Cohort') ?>
                </span>
            </div>
        </div>
    </div>

    <!-- Active & Upcoming Classes -->
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <h2 class="text-base font-bold text-slate-900 flex items-center gap-2">
                <span>Active & Upcoming Lessons</span>
                <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800">
                    <?= count($upcomingClasses) ?>
                </span>
            </h2>
        </div>

        <?php if (empty($upcomingClasses)): ?>
            <div class="bg-white rounded-2xl border border-slate-200 p-12 text-center shadow-xs">
                <div class="w-16 h-16 rounded-2xl bg-slate-100 text-slate-400 mx-auto flex items-center justify-center mb-4">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                    </svg>
                </div>
                <h3 class="text-base font-bold text-slate-900">No Live Classes Right Now</h3>
                <p class="text-xs text-slate-500 mt-1 max-w-sm mx-auto">
                    Your subject teachers haven't scheduled any upcoming online sessions for your class at this time. Check back before your class periods.
                </p>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                <?php foreach ($upcomingClasses as $lc): ?>
                    <div class="bg-white rounded-2xl border <?= $lc->isInProgress() ? 'border-rose-400 ring-2 ring-rose-500/10 shadow-sm' : 'border-slate-200 shadow-xs' ?> p-5 flex flex-col justify-between transition hover:shadow-md hover:border-slate-300 space-y-4" id="student-live-<?= $lc->id ?>">
                        <!-- Card Header -->
                        <div class="space-y-3">
                            <div class="flex items-center justify-between gap-2">
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs border <?= $lc->getPlatformColorClass() ?>">
                                    <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                    </svg>
                                    <span><?= htmlspecialchars($lc->getPlatformLabel()) ?></span>
                                </span>

                                <?php if ($lc->isInProgress()): ?>
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-extrabold bg-rose-600 text-white animate-pulse">
                                        <span class="w-2 h-2 rounded-full bg-white"></span>
                                        LIVE NOW
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs border <?= $lc->getStatusBadgeClass() ?>">
                                        Scheduled
                                    </span>
                                <?php endif; ?>
                            </div>

                            <h3 class="text-base font-bold text-slate-900 leading-snug">
                                <?= htmlspecialchars($lc->title) ?>
                            </h3>

                            <div class="flex items-center gap-2 text-xs font-semibold text-slate-600">
                                <span class="text-emerald-700 font-bold">
                                    <?= htmlspecialchars($lc->subjectName ?? 'Subject') ?>
                                </span>
                                <span>•</span>
                                <span>Instructor: <?= htmlspecialchars($lc->teacherName ?? 'Teacher') ?></span>
                            </div>

                            <?php if (!empty($lc->description)): ?>
                                <p class="text-xs text-slate-500 line-clamp-2">
                                    <?= htmlspecialchars($lc->description) ?>
                                </p>
                            <?php endif; ?>
                        </div>

                        <!-- Schedule & Meta -->
                        <div class="pt-3 border-t border-slate-100 space-y-2 text-xs">
                            <div class="flex items-center gap-2 text-slate-600 font-medium">
                                <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                <span><?= htmlspecialchars($lc->formatSchedule()) ?></span>
                            </div>

                            <?php if (!empty($lc->meetingPasscode)): ?>
                                <div class="flex items-center gap-2 text-slate-600">
                                    <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                    </svg>
                                    <span>Passcode: <code class="bg-slate-100 border border-slate-200 px-1.5 py-0.5 rounded font-mono font-bold text-slate-800"><?= htmlspecialchars($lc->meetingPasscode) ?></code></span>
                                </div>
                            <?php endif; ?>

                            <?php if ($lc->hasJoined): ?>
                                <div class="inline-flex items-center gap-1.5 text-xs font-bold text-emerald-800 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200">
                                    <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    <span>Your attendance has been recorded</span>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- 1-Tap Join Button -->
                        <div class="pt-3 border-t border-slate-100">
                            <?php if ($lc->isJoinable()): ?>
                                <a href="/student/live-classes/<?= $lc->id ?>/join" target="_blank" rel="noopener noreferrer" 
                                   class="w-full flex items-center justify-center gap-2 px-4 py-3 bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold text-xs rounded-xl shadow-xs transition duration-200 active:scale-98">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                    </svg>
                                    <span>Join Live Class (Auto-Verify Attendance)</span>
                                </a>
                            <?php else: ?>
                                <button type="button" disabled class="w-full flex items-center justify-center gap-2 px-4 py-2.5 bg-slate-100 text-slate-400 font-bold text-xs rounded-xl cursor-not-allowed">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    <span>Room Opens 15m Before Schedule</span>
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Past Sessions -->
    <?php if (!empty($pastClasses)): ?>
        <div class="space-y-4 pt-4 border-t border-slate-200">
            <h2 class="text-sm font-bold uppercase tracking-wider text-slate-600">
                Concluded Lessons (<?= count($pastClasses) ?>)
            </h2>
            <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-xs">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="border-b border-slate-100 bg-slate-50/50 text-slate-500 uppercase tracking-wider font-bold text-[11px]">
                                <th class="py-3 px-4">Subject</th>
                                <th class="py-3 px-4">Lesson Title</th>
                                <th class="py-3 px-4">Instructor</th>
                                <th class="py-3 px-4">Schedule</th>
                                <th class="py-3 px-4">Status</th>
                                <th class="py-3 px-4 text-right">Attendance</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php foreach ($pastClasses as $past): ?>
                                <tr class="hover:bg-slate-50/50 transition">
                                    <td class="py-3 px-4 font-bold text-slate-900">
                                        <?= htmlspecialchars($past->subjectName ?? 'Subject') ?>
                                    </td>
                                    <td class="py-3 px-4 text-slate-700">
                                        <?= htmlspecialchars($past->title) ?>
                                    </td>
                                    <td class="py-3 px-4 text-slate-600">
                                        <?= htmlspecialchars($past->teacherName ?? 'Teacher') ?>
                                    </td>
                                    <td class="py-3 px-4 text-slate-500">
                                        <?= htmlspecialchars($past->formatSchedule()) ?>
                                    </td>
                                    <td class="py-3 px-4">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold border <?= $past->getStatusBadgeClass() ?>">
                                            <?= ucfirst($past->status) ?>
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 text-right">
                                        <?php if ($past->hasJoined): ?>
                                            <span class="inline-flex items-center gap-1 text-[11px] font-bold text-emerald-800 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200">
                                                <svg class="w-3 h-3 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                                </svg>
                                                Attended
                                            </span>
                                        <?php else: ?>
                                            <span class="text-slate-400 text-[11px]">Not logged</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>
