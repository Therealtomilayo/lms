<div class="space-y-6">
    <!-- Header Card -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 rounded-2xl bg-sky-500 text-white font-extrabold text-xl flex items-center justify-center shadow-xs flex-shrink-0">
                    <?= strtoupper(substr($user->name ?? 'S', 0, 1)) ?>
                </div>
                <div>
                    <!-- Breadcrumbs -->
                    <nav class="flex items-center gap-2 text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">
                        <span>Student Portal</span>
                        <span class="text-slate-300">/</span>
                        <span class="text-slate-700">Learning Dashboard</span>
                    </nav>
                    <div class="flex flex-wrap items-center gap-2.5">
                        <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight leading-tight">
                            Welcome back, <?= htmlspecialchars($user->name ?? 'Student') ?>!
                        </h1>
                        <?php if ($student && $student->schoolClass): ?>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-lg text-xs font-bold bg-sky-50 text-sky-800 border border-sky-200">
                                <?= htmlspecialchars($student->schoolClass->name) ?><?= !empty($student->schoolClass->sectionArm) ? ' (' . htmlspecialchars($student->schoolClass->sectionArm) . ')' : '' ?>
                            </span>
                        <?php endif; ?>
                    </div>
                    <div class="flex flex-wrap items-center gap-3 text-xs text-slate-500 mt-1">
                        <?php if ($student && !empty($student->admissionNumber)): ?>
                            <span class="font-mono font-bold text-slate-700 bg-slate-100 px-2 py-0.5 rounded-md">
                                Adm: <?= htmlspecialchars($student->admissionNumber) ?>
                            </span>
                        <?php endif; ?>
                        <span>Academic Session: <strong><?= htmlspecialchars($activeSession?->name ?? '2026/2027') ?></strong></span>
                        <span>&bull;</span>
                        <span class="text-emerald-700 font-semibold"><?= htmlspecialchars($activeTerm?->name ?? 'Current Term') ?></span>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-2.5 flex-wrap">
                <a href="/student/grades" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-bold text-slate-700 bg-slate-100 hover:bg-slate-200 transition">
                    <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <span>View Grades</span>
                </a>
                <a href="/student/timetable" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-bold text-white bg-sky-600 hover:bg-sky-700 shadow-xs transition">
                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    <span>My Timetable</span>
                </a>
            </div>
        </div>
    </div>

    <!-- 4-Card KPI Summary Metrics Strip -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Enrolled Subjects -->
        <a href="/student/subjects" class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs hover:border-sky-300 transition group block">
            <p class="text-xs font-bold uppercase tracking-wider text-slate-400 group-hover:text-sky-600 transition">Enrolled Subjects</p>
            <div class="flex items-baseline gap-2 mt-1">
                <h3 class="text-2xl font-extrabold text-slate-900"><?= number_format(count($enrolledSubjects)) ?></h3>
                <span class="text-xs font-semibold text-slate-500">courses</span>
            </div>
            <span class="text-[11px] font-medium text-sky-600 mt-1 block">
                Active term courses &rarr;
            </span>
        </a>

        <!-- Pending Coursework -->
        <a href="/student/assignments" class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs hover:border-amber-300 transition group block">
            <p class="text-xs font-bold uppercase tracking-wider text-amber-500">Pending Tasks</p>
            <div class="flex items-baseline gap-2 mt-1">
                <h3 class="text-2xl font-extrabold text-slate-900"><?= number_format(count($activeAssignments)) ?></h3>
                <span class="text-xs font-semibold text-slate-500">assignments</span>
            </div>
            <span class="text-[11px] font-medium <?= count($activeAssignments) > 0 ? 'text-amber-600 font-bold' : 'text-slate-400' ?> mt-1 block">
                <?= count($activeAssignments) > 0 ? 'Action required &rarr;' : 'All up to date' ?>
            </span>
        </a>

        <!-- Active CBT Quizzes -->
        <a href="/student/quizzes" class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs hover:border-emerald-300 transition group block">
            <p class="text-xs font-bold uppercase tracking-wider text-emerald-600">Available Quizzes</p>
            <div class="flex items-baseline gap-2 mt-1">
                <h3 class="text-2xl font-extrabold text-emerald-600"><?= number_format(count($activeQuizzes)) ?></h3>
                <span class="text-xs font-semibold text-slate-500">online CBTs</span>
            </div>
            <span class="text-[11px] font-medium text-emerald-600 mt-1 block">
                Ready to take &rarr;
            </span>
        </a>

        <!-- Attendance Summary -->
        <a href="/student/attendance" class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs hover:border-sky-300 transition group block">
            <p class="text-xs font-bold uppercase tracking-wider text-slate-400 group-hover:text-sky-600 transition">Term Attendance</p>
            <div class="flex items-baseline gap-2 mt-1">
                <h3 class="text-2xl font-extrabold text-slate-900"><?= number_format((float)($attendanceSummary['percentage'] ?? 100), 1) ?>%</h3>
            </div>
            <span class="text-[11px] font-medium text-slate-500 mt-1 block">
                <?= (int)($attendanceSummary['present'] ?? 0) ?> days present recorded
            </span>
        </a>
    </div>

    <!-- Live Online Classes Widget (SRS §31) -->
    <?php if (!empty($upcomingLiveClasses)): ?>
        <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6 space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-rose-500"></span>
                        <h2 class="text-base font-bold text-slate-900">Live Online Classrooms</h2>
                    </div>
                    <p class="text-xs text-slate-500">Synchronous video lessons scheduled by your subject instructors.</p>
                </div>
                <a href="/student/live-classes" class="text-xs font-bold text-sky-600 hover:text-sky-700 transition">
                    View Schedule (<?= count($upcomingLiveClasses) ?>) &rarr;
                </a>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <?php foreach ($upcomingLiveClasses as $lc): ?>
                    <div class="p-4 rounded-xl border border-slate-200 bg-slate-50/50 hover:bg-white hover:border-slate-300 transition flex flex-col justify-between space-y-3">
                        <div class="space-y-2">
                            <div class="flex items-center justify-between gap-2">
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs border <?= $lc->getPlatformColorClass() ?>">
                                    <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                    </svg>
                                    <span><?= htmlspecialchars($lc->getPlatformLabel()) ?></span>
                                </span>
                                <?php if ($lc->isInProgress()): ?>
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-rose-50 text-rose-800 border border-rose-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-rose-500 animate-pulse"></span>
                                        <span>Live Now</span>
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-amber-50 text-amber-800 border border-amber-200">
                                        Scheduled
                                    </span>
                                <?php endif; ?>
                            </div>
                            <h3 class="text-sm font-bold text-slate-900 leading-snug">
                                <?= htmlspecialchars($lc->title) ?>
                            </h3>
                            <p class="text-[11px] text-slate-500">
                                <?= htmlspecialchars($lc->subjectName ?? 'Subject') ?> &bull; <?= htmlspecialchars($lc->formatSchedule()) ?>
                            </p>
                        </div>

                        <div class="pt-2 border-t border-slate-100 flex items-center justify-between">
                            <?php if ($lc->isJoinable()): ?>
                                <a href="/student/live-classes/<?= $lc->id ?>/join" target="_blank" rel="noopener noreferrer" 
                                   class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-bold text-white bg-sky-600 hover:bg-sky-700 shadow-xs transition">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                    </svg>
                                    <span>Join Live Class</span>
                                </a>
                            <?php else: ?>
                                <a href="/student/live-classes" class="text-xs font-bold text-sky-600 hover:text-sky-700 transition inline-flex items-center gap-1">
                                    <span>View Details</span>
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                    </svg>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Main Content 2-Column Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left Section (2 Cols): Enrolled Subjects & Coursework -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Enrolled Subjects Grid -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6 space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-base font-bold text-slate-900">Enrolled Subjects</h2>
                        <p class="text-xs text-slate-500">Access learning notes, downloads, and syllabus topics.</p>
                    </div>
                    <a href="/student/subjects" class="text-xs font-bold text-sky-600 hover:text-sky-700 transition">
                        View All (<?= count($enrolledSubjects) ?>) &rarr;
                    </a>
                </div>

                <?php if (empty($enrolledSubjects)): ?>
                    <div class="p-8 text-center bg-slate-50 rounded-xl border border-dashed border-slate-200">
                        <p class="text-xs text-slate-500">No subject enrollments recorded for the current academic session.</p>
                    </div>
                <?php else: ?>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <?php foreach (array_slice($enrolledSubjects, 0, 4) as $enrollment): ?>
                            <?php 
                                $cs = $enrollment->classSubject;
                                $sName = $cs?->subject?->name ?? 'Subject';
                                $sCode = $cs?->subject?->code ?? '';
                                $tName = $cs?->teacher?->user?->name ?? ($cs?->teacher?->name ?? 'Subject Teacher');
                            ?>
                            <div class="p-4 rounded-xl border border-slate-200 bg-slate-50/50 hover:bg-white hover:border-slate-300 transition flex flex-col justify-between space-y-3">
                                <div>
                                    <div class="flex items-center justify-between gap-2">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-sky-50 text-sky-800 border border-sky-200">
                                            <?= htmlspecialchars($sCode ?: 'SUB') ?>
                                        </span>
                                    </div>
                                    <h3 class="text-sm font-bold text-slate-900 mt-1.5 leading-snug">
                                        <?= htmlspecialchars($sName) ?>
                                    </h3>
                                    <p class="text-[11px] text-slate-500 mt-0.5">
                                        Teacher: <strong class="text-slate-700"><?= htmlspecialchars($tName) ?></strong>
                                    </p>
                                </div>

                                <div class="pt-2 border-t border-slate-100 flex items-center justify-between">
                                    <a href="/student/subjects/<?= (int)($cs?->id ?? 0) ?>" 
                                       class="text-xs font-bold text-sky-600 hover:text-sky-700 transition inline-flex items-center gap-1">
                                        <span>Open Workspace</span>
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                                        </svg>
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Active Coursework Tasks -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6 space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-base font-bold text-slate-900">Upcoming Coursework & Tasks</h2>
                        <p class="text-xs text-slate-500">Homework and practical projects requiring your submission.</p>
                    </div>
                    <a href="/student/assignments" class="text-xs font-bold text-sky-600 hover:text-sky-700 transition">
                        View All &rarr;
                    </a>
                </div>

                <?php if (empty($activeAssignments)): ?>
                    <div class="p-8 text-center bg-slate-50 rounded-xl border border-dashed border-slate-200">
                        <svg class="w-8 h-8 text-slate-300 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <p class="text-xs font-bold text-slate-700">All Coursework Completed!</p>
                        <p class="text-[11px] text-slate-400 mt-0.5">You have no pending assignments with upcoming deadlines.</p>
                    </div>
                <?php else: ?>
                    <div class="divide-y divide-slate-100">
                        <?php foreach (array_slice($activeAssignments, 0, 3) as $assignment): ?>
                            <?php 
                                $sName = $assignment->classSubject?->subject?->name ?? 'Subject';
                            ?>
                            <div class="py-3.5 first:pt-0 last:pb-0 flex items-center justify-between gap-4 flex-wrap">
                                <div class="space-y-1">
                                    <div class="flex items-center gap-2">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-700">
                                            <?= htmlspecialchars($sName) ?>
                                        </span>
                                        <span class="text-[11px] font-bold text-amber-600">
                                            Due: <?= date('M d, Y · g:i A', strtotime($assignment->dueAt)) ?>
                                        </span>
                                    </div>
                                    <h4 class="font-bold text-xs text-slate-900 leading-snug">
                                        <?= htmlspecialchars($assignment->title) ?>
                                    </h4>
                                </div>

                                <a href="/student/assignments/<?= (int)$assignment->id ?>" 
                                   class="px-3 py-1.5 rounded-xl text-xs font-bold bg-sky-50 text-sky-700 hover:bg-sky-100 transition border border-sky-200">
                                    Submit Assignment &rarr;
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Right Section (1 Col): Today's Schedule & Campus Bulletins -->
        <div class="space-y-6">
            <!-- Today's Schedule -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6 space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-base font-bold text-slate-900">Today's Timetable</h2>
                        <p class="text-[11px] text-slate-400 font-semibold"><?= htmlspecialchars($todayDayName) ?>'s periods</p>
                    </div>
                    <a href="/student/timetable" class="text-xs font-bold text-sky-600 hover:text-sky-700 transition">
                        Full Schedule &rarr;
                    </a>
                </div>

                <?php if (empty($todaySlots)): ?>
                    <div class="p-6 text-center bg-slate-50 rounded-xl border border-dashed border-slate-200">
                        <p class="text-xs text-slate-400 italic">No scheduled class periods for today.</p>
                    </div>
                <?php else: ?>
                    <div class="space-y-2.5">
                        <?php foreach ($todaySlots as $slot): ?>
                            <div class="p-3 bg-slate-50/70 rounded-xl border border-slate-100 flex items-center justify-between gap-2">
                                <div>
                                    <span class="text-[10px] font-bold uppercase tracking-wider text-sky-700">
                                        <?= htmlspecialchars($slot->classSubject?->subjectCode ?: 'SUB') ?>
                                    </span>
                                    <h4 class="text-xs font-bold text-slate-900 leading-tight mt-0.5">
                                        <?= htmlspecialchars($slot->classSubject?->subjectName ?: 'Subject') ?>
                                    </h4>
                                    <?php if ($slot->room): ?>
                                        <span class="text-[10px] text-slate-400 font-medium">Room: <?= htmlspecialchars($slot->room) ?></span>
                                    <?php endif; ?>
                                </div>
                                <span class="font-mono text-[11px] font-bold text-slate-700 bg-white px-2 py-1 rounded-lg border border-slate-200">
                                    <?= htmlspecialchars($slot->getFormattedTimeRange()) ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Bulletins & Announcements Feed -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6 space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-base font-bold text-slate-900">Campus Bulletins</h2>
                        <p class="text-[11px] text-slate-400 font-semibold">Latest updates</p>
                    </div>
                    <a href="/student/announcements" class="text-xs font-bold text-sky-600 hover:text-sky-700 transition">
                        View All &rarr;
                    </a>
                </div>

                <?php if (empty($announcements)): ?>
                    <div class="p-6 text-center bg-slate-50 rounded-xl border border-dashed border-slate-200">
                        <p class="text-xs text-slate-400 italic">No active announcements posted.</p>
                    </div>
                <?php else: ?>
                    <div class="divide-y divide-slate-100">
                        <?php foreach (array_slice($announcements, 0, 3) as $bulletin): ?>
                            <div class="py-3 first:pt-0 last:pb-0 space-y-1">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-sky-50 text-sky-800 border border-sky-200">
                                        <?= htmlspecialchars($bulletin->targetName ?? 'School-wide') ?>
                                    </span>
                                    <span class="text-[10px] text-slate-400">
                                        <?= date('M d', strtotime($bulletin->createdAt)) ?>
                                    </span>
                                </div>
                                <h4 class="font-bold text-xs text-slate-900 leading-snug">
                                    <?= htmlspecialchars($bulletin->title) ?>
                                </h4>
                                <p class="text-[11px] text-slate-600 line-clamp-2 leading-relaxed">
                                    <?= htmlspecialchars($bulletin->body) ?>
                                </p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
