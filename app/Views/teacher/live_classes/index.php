<div class="space-y-6">
    <!-- Header Card -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <!-- Breadcrumbs -->
                <nav class="flex items-center gap-2 text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">
                    <a href="/teacher/dashboard" class="text-slate-400 hover:text-emerald-600 transition">Faculty Portal</a>
                    <span class="text-slate-300">/</span>
                    <span class="text-slate-700">Live Online Classes</span>
                </nav>
                <div class="flex flex-wrap items-center gap-3">
                    <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight leading-tight">
                        Live Online Class Hub
                    </h1>
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-bold bg-emerald-50 text-emerald-800 border border-emerald-200">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        SRS §31 Verified
                    </span>
                </div>
                <p class="text-xs text-slate-500 mt-1">
                    Schedule and host synchronous lessons via Google Meet, Zoom, or Teams with automated student attendance verification.
                </p>
            </div>

            <div class="flex items-center gap-2.5">
                <button type="button" onclick="document.getElementById('scheduleModal').classList.remove('hidden')" 
                        class="inline-flex items-center gap-2 px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl shadow-xs transition duration-200">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    <span>Schedule Live Class</span>
                </button>
            </div>
        </div>
    </div>

    <!-- 4-Card KPI Summary Metrics Strip (Visual Continuity with Classes & Dashboard) -->
    <?php
        $totalSessions = count($liveClasses);
        $activeSessions = count(array_filter($liveClasses, fn($c) => $c->isInProgress() || $c->isScheduled()));
        $completedSessions = count(array_filter($liveClasses, fn($c) => $c->isCompleted()));
        $totalAttendees = array_sum(array_map(fn($c) => (int)$c->attendeesCount, $liveClasses));
    ?>
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Total Sessions -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs">
            <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Total Classes</p>
            <div class="flex items-baseline gap-2 mt-1">
                <h3 class="text-2xl font-extrabold text-slate-900"><?= number_format($totalSessions) ?></h3>
                <span class="text-xs font-semibold text-slate-500">sessions</span>
            </div>
            <span class="text-[11px] font-medium text-slate-500 mt-1 block">
                Recorded virtual lessons
            </span>
        </div>

        <!-- Active / Scheduled -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs">
            <p class="text-xs font-bold uppercase tracking-wider text-emerald-600">Active / Upcoming</p>
            <div class="flex items-baseline gap-2 mt-1">
                <h3 class="text-2xl font-extrabold text-slate-900"><?= number_format($activeSessions) ?></h3>
                <span class="text-xs font-semibold text-slate-500">scheduled</span>
            </div>
            <span class="text-[11px] font-medium text-emerald-600 mt-1 block">
                Awaiting / in-progress
            </span>
        </div>

        <!-- Completed Sessions -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs">
            <p class="text-xs font-bold uppercase tracking-wider text-sky-600">Completed</p>
            <div class="flex items-baseline gap-2 mt-1">
                <h3 class="text-2xl font-extrabold text-slate-900"><?= number_format($completedSessions) ?></h3>
                <span class="text-xs font-semibold text-slate-500">concluded</span>
            </div>
            <span class="text-[11px] font-medium text-sky-600 mt-1 block">
                Archived with attendance logs
            </span>
        </div>

        <!-- Total Attendees -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs">
            <p class="text-xs font-bold uppercase tracking-wider text-amber-600">Verified Attendees</p>
            <div class="flex items-baseline gap-2 mt-1">
                <h3 class="text-2xl font-extrabold text-slate-900"><?= number_format($totalAttendees) ?></h3>
                <span class="text-xs font-semibold text-slate-500">joins</span>
            </div>
            <span class="text-[11px] font-medium text-amber-600 mt-1 block">
                Immutable student records
            </span>
        </div>
    </div>

    <!-- Active & Scheduled Classes -->
    <div class="space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
            <div>
                <h2 class="text-base font-bold text-slate-900">
                    Scheduled & Active Sessions (<?= count($liveClasses) ?>)
                </h2>
                <p class="text-xs text-slate-500">Live video classrooms for your assigned cohorts.</p>
            </div>
            <span class="text-xs font-semibold text-slate-600 bg-white px-3 py-1.5 rounded-xl border border-slate-200 shadow-xs">
                Session: <?= htmlspecialchars($currentSession?->name ?? 'Current') ?> • Term: <?= htmlspecialchars($currentTerm?->name ?? 'Current') ?>
            </span>
        </div>

        <?php if (empty($liveClasses)): ?>
            <div class="bg-white rounded-2xl border border-slate-200 p-12 text-center shadow-xs">
                <div class="w-16 h-16 rounded-2xl bg-emerald-50 text-emerald-600 mx-auto flex items-center justify-center mb-4 border border-emerald-100">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                    </svg>
                </div>
                <h3 class="text-base font-bold text-slate-900">No Live Classes Scheduled</h3>
                <p class="text-xs text-slate-500 mt-1.5 max-w-sm mx-auto">
                    You haven't scheduled any online synchronous classes yet. Click "Schedule Live Class" above to set up a Google Meet, Zoom, or Teams lesson.
                </p>
                <button type="button" onclick="document.getElementById('scheduleModal').classList.remove('hidden')" 
                        class="mt-4 inline-flex items-center gap-1.5 px-4 py-2 text-xs font-bold bg-emerald-50 text-emerald-800 border border-emerald-200 rounded-xl hover:bg-emerald-100 transition">
                    Schedule Your First Class
                </button>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                <?php foreach ($liveClasses as $lc): ?>
                    <div class="bg-white rounded-2xl border <?= $lc->isInProgress() ? 'border-rose-300 ring-2 ring-rose-500/10 shadow-sm' : 'border-slate-200 shadow-xs' ?> p-5 flex flex-col justify-between transition hover:shadow-md hover:border-slate-300 space-y-4" id="class-card-<?= $lc->id ?>">
                        <!-- Header / Status Badge -->
                        <div class="space-y-2.5">
                            <div class="flex items-center justify-between gap-2">
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs border <?= $lc->getPlatformColorClass() ?>">
                                    <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                    </svg>
                                    <span><?= htmlspecialchars($lc->getPlatformLabel()) ?></span>
                                </span>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs border <?= $lc->getStatusBadgeClass() ?>">
                                    <?php if ($lc->isInProgress()): ?>
                                        <span class="w-1.5 h-1.5 rounded-full bg-rose-500 animate-pulse mr-1.5"></span>
                                        <span>Live Now</span>
                                    <?php else: ?>
                                        <span><?= ucfirst(str_replace('_', ' ', $lc->status)) ?></span>
                                    <?php endif; ?>
                                </span>
                            </div>

                            <h3 class="text-base font-bold text-slate-900 leading-snug">
                                <?= htmlspecialchars($lc->title) ?>
                            </h3>

                            <div class="flex items-center gap-2 text-xs font-semibold text-slate-600">
                                <span class="bg-slate-100 px-2 py-0.5 rounded text-slate-700 font-medium">
                                    <?= htmlspecialchars($lc->className ?? 'Class') ?>
                                </span>
                                <span>•</span>
                                <span class="text-emerald-700 font-bold">
                                    <?= htmlspecialchars($lc->subjectName ?? 'Subject') ?>
                                </span>
                            </div>

                            <?php if (!empty($lc->description)): ?>
                                <p class="text-xs text-slate-500 line-clamp-2">
                                    <?= htmlspecialchars($lc->description) ?>
                                </p>
                            <?php endif; ?>
                        </div>

                        <!-- Timing & Meeting Meta -->
                        <div class="pt-3 border-t border-slate-100 space-y-2 text-xs">
                            <div class="flex items-center gap-2 text-slate-600">
                                <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                <span><?= htmlspecialchars($lc->formatSchedule()) ?> (<?= $lc->durationMinutes ?>m)</span>
                            </div>

                            <?php if (!empty($lc->meetingPasscode)): ?>
                                <div class="flex items-center gap-2 text-slate-600">
                                    <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                    </svg>
                                    <span>Passcode: <code class="bg-slate-100 border border-slate-200 px-1.5 py-0.5 rounded font-mono font-bold text-slate-800"><?= htmlspecialchars($lc->meetingPasscode) ?></code></span>
                                </div>
                            <?php endif; ?>

                            <div class="flex items-center justify-between text-xs pt-1">
                                <a href="/teacher/live-classes/<?= $lc->id ?>/attendees" class="inline-flex items-center gap-1.5 text-xs font-bold text-emerald-700 hover:text-emerald-800 transition">
                                    <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                                    </svg>
                                    <span><?= $lc->attendeesCount ?> Verified Attendee<?= $lc->attendeesCount === 1 ? '' : 's' ?></span>
                                </a>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="pt-3 border-t border-slate-100 flex items-center justify-between gap-2 flex-wrap">
                            <div class="flex items-center gap-2">
                                <a href="<?= htmlspecialchars($lc->meetingLink) ?>" target="_blank" rel="noopener noreferrer" 
                                   class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold bg-slate-900 hover:bg-slate-800 text-white rounded-lg transition shadow-xs">
                                    <span>Open Room</span>
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                    </svg>
                                </a>

                                <?php if ($lc->isScheduled()): ?>
                                    <form method="POST" action="/teacher/live-classes/<?= $lc->id ?>/start" class="inline">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-bold bg-rose-50 text-rose-800 border border-rose-200 hover:bg-rose-100 rounded-lg transition" title="Start class now and broadcast live banner to students">
                                            <span>Go Live</span>
                                        </button>
                                    </form>
                                <?php elseif ($lc->isInProgress()): ?>
                                    <form method="POST" action="/teacher/live-classes/<?= $lc->id ?>/end" class="inline">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-bold bg-slate-100 text-slate-700 border border-slate-200 hover:bg-slate-200 rounded-lg transition" title="Conclude class session">
                                            <span>Conclude</span>
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>

                            <div class="flex items-center gap-1.5">
                                <?php if (!$lc->isCompleted()): ?>
                                    <form method="POST" action="/teacher/live-classes/<?= $lc->id ?>/cancel" onsubmit="return confirm('Cancel this online class? Students will be notified.');" class="inline">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="p-1.5 text-slate-400 hover:text-amber-600 transition" title="Cancel Class">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                                            </svg>
                                        </button>
                                    </form>
                                <?php endif; ?>

                                <form method="POST" action="/teacher/live-classes/<?= $lc->id ?>/delete" onsubmit="return confirm('Permanently delete this scheduled class entry?');" class="inline">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="p-1.5 text-slate-400 hover:text-rose-600 transition" title="Delete Entry">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Schedule Live Class Modal -->
    <div id="scheduleModal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-slate-900/50 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl border border-slate-200 shadow-2xl max-w-lg w-full overflow-hidden transition-all">
            <!-- Modal Header -->
            <div class="p-5 border-b border-slate-100 flex items-center justify-between">
                <div>
                    <h3 class="text-base font-bold text-slate-900">Schedule Live Class</h3>
                    <p class="text-xs text-slate-500">Provide meeting room link and student cohort details.</p>
                </div>
                <button type="button" onclick="document.getElementById('scheduleModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600 p-1">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <!-- Modal Form -->
            <form method="POST" action="/teacher/live-classes" class="p-5 space-y-4">
                <?= csrf_field() ?>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                        Class Subject <span class="text-rose-500">*</span>
                    </label>
                    <select name="class_subject_id" required class="w-full text-xs font-semibold rounded-xl border border-slate-200 bg-white text-slate-900 focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 py-2.5 px-3">
                        <option value="">Select subject cohort...</option>
                        <?php foreach ($classSubjects as $cs): ?>
                            <option value="<?= $cs->id ?>">
                                <?= htmlspecialchars(($cs->className ?? 'Class') . ' — ' . ($cs->subjectName ?? 'Subject')) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                        Lesson Topic / Title <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" name="title" required placeholder="e.g. Quadratic Equations & Roots Analysis" 
                           class="w-full text-xs font-semibold rounded-xl border border-slate-200 bg-white text-slate-900 focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 py-2.5 px-3">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                            Platform <span class="text-rose-500">*</span>
                        </label>
                        <select name="platform" id="platformSelect" class="w-full text-xs font-semibold rounded-xl border border-slate-200 bg-white text-slate-900 focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 py-2.5 px-3">
                            <option value="google_meet">Google Meet</option>
                            <option value="zoom">Zoom Meeting</option>
                            <option value="microsoft_teams">Microsoft Teams</option>
                            <option value="other">Other External Room</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                            Passcode / PIN (Optional)
                        </label>
                        <input type="text" name="meeting_passcode" placeholder="e.g. CLARET2026" 
                               class="w-full text-xs font-semibold rounded-xl border border-slate-200 bg-white text-slate-900 focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 py-2.5 px-3 font-mono">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                        External Meeting URL <span class="text-rose-500">*</span>
                    </label>
                    <input type="url" name="meeting_link" required placeholder="https://meet.google.com/xyz-abcd-efg" 
                           class="w-full text-xs font-semibold rounded-xl border border-slate-200 bg-white text-slate-900 focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 py-2.5 px-3">
                    <p class="text-[11px] text-slate-400 mt-1">
                        Must start with <code>https://</code>. LMS auto-stamps student attendance when they click join.
                    </p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                            Date <span class="text-rose-500">*</span>
                        </label>
                        <input type="date" name="scheduled_date" required value="<?= date('Y-m-d') ?>" 
                               class="w-full text-xs font-semibold rounded-xl border border-slate-200 bg-white text-slate-900 focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 py-2.5 px-3">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                            Start Time <span class="text-rose-500">*</span>
                        </label>
                        <input type="time" name="start_time" required value="<?= date('H:i', strtotime('+1 hour')) ?>" 
                               class="w-full text-xs font-semibold rounded-xl border border-slate-200 bg-white text-slate-900 focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 py-2.5 px-3">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                            Duration <span class="text-rose-500">*</span>
                        </label>
                        <select name="duration_minutes" class="w-full text-xs font-semibold rounded-xl border border-slate-200 bg-white text-slate-900 focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 py-2.5 px-3">
                            <option value="30">30 mins</option>
                            <option value="40" selected>40 mins</option>
                            <option value="45">45 mins</option>
                            <option value="60">60 mins</option>
                            <option value="90">90 mins</option>
                            <option value="120">120 mins</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                        Lesson Brief / Preparation Notes
                    </label>
                    <textarea name="description" rows="2" placeholder="Topics to cover, materials required..." 
                              class="w-full text-xs font-semibold rounded-xl border border-slate-200 bg-white text-slate-900 focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 py-2 px-3"></textarea>
                </div>

                <!-- Modal Actions -->
                <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-2.5">
                    <button type="button" onclick="document.getElementById('scheduleModal').classList.add('hidden')" 
                            class="px-4 py-2.5 text-xs font-semibold text-slate-600 hover:text-slate-800 transition">
                        Cancel
                    </button>
                    <button type="submit" 
                            class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl shadow-xs transition">
                        Schedule Session
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
