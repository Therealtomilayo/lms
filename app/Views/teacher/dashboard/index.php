<div class="space-y-6">
    <!-- Welcome Header Card -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <!-- Breadcrumbs -->
                <nav class="flex items-center gap-2 text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">
                    <span class="text-slate-400">Faculty Portal</span>
                    <span class="text-slate-300">/</span>
                    <span class="text-slate-700">Dashboard</span>
                </nav>
                <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight leading-tight">
                    Welcome back, <?= htmlspecialchars($userContext->name) ?>
                </h1>
                <p class="text-xs text-slate-500 mt-1">
                    Claret International School — Faculty & Instructional Command Center.
                </p>
            </div>

            <!-- Active Academic Session & Term Badges -->
            <div class="flex items-center gap-2.5 flex-wrap">
                <?php if ($currentSession): ?>
                    <div class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-slate-100 border border-slate-200 text-xs font-semibold text-slate-700">
                        <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <span><?= htmlspecialchars($currentSession->name) ?> Session</span>
                    </div>
                <?php endif; ?>

                <?php if ($currentTerm): ?>
                    <div class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-emerald-50 border border-emerald-200 text-xs font-bold text-emerald-700">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        <span><?= htmlspecialchars($currentTerm->name) ?> (Active)</span>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- 4-Card Overview Metric Strip -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Assigned Class-Subjects -->
        <a href="/teacher/content" class="bg-white p-5 rounded-xl border border-slate-200 shadow-xs hover:border-emerald-300 hover:shadow-sm transition group">
            <div class="flex items-center justify-between text-slate-400 group-hover:text-emerald-600 transition">
                <p class="text-xs font-semibold uppercase tracking-wider">Assigned Classes</p>
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                </svg>
            </div>
            <div class="flex items-baseline gap-2 mt-2">
                <h3 class="text-2xl font-extrabold text-slate-900 group-hover:text-emerald-600 transition"><?= count($classSubjects) ?></h3>
            </div>
            <span class="text-[11px] font-medium text-slate-500 mt-1 block">Coursework classes &rarr;</span>
        </a>

        <!-- Assignments -->
        <a href="/teacher/assignments" class="bg-white p-5 rounded-xl border border-slate-200 shadow-xs hover:border-emerald-300 hover:shadow-sm transition group">
            <div class="flex items-center justify-between text-slate-400 group-hover:text-emerald-600 transition">
                <p class="text-xs font-semibold uppercase tracking-wider">Assignments</p>
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                </svg>
            </div>
            <div class="flex items-baseline gap-2 mt-2">
                <h3 class="text-2xl font-extrabold text-slate-900 group-hover:text-emerald-600 transition"><?= count($assignments) ?></h3>
            </div>
            <span class="text-[11px] font-medium text-slate-500 mt-1 block">Coursework briefs &rarr;</span>
        </a>

        <!-- Quizzes & CBT -->
        <a href="/teacher/quizzes" class="bg-white p-5 rounded-xl border border-slate-200 shadow-xs hover:border-emerald-300 hover:shadow-sm transition group">
            <div class="flex items-center justify-between text-slate-400 group-hover:text-emerald-600 transition">
                <p class="text-xs font-semibold uppercase tracking-wider">Online Quizzes</p>
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                </svg>
            </div>
            <div class="flex items-baseline gap-2 mt-2">
                <h3 class="text-2xl font-extrabold text-slate-900 group-hover:text-emerald-600 transition"><?= count($quizzes) ?></h3>
            </div>
            <span class="text-[11px] font-medium text-slate-500 mt-1 block">CBT assessments &rarr;</span>
        </a>

        <!-- Today's Periods -->
        <a href="/teacher/timetable" class="bg-white p-5 rounded-xl border border-slate-200 shadow-xs hover:border-emerald-300 hover:shadow-sm transition group">
            <div class="flex items-center justify-between text-slate-400 group-hover:text-emerald-600 transition">
                <p class="text-xs font-semibold uppercase tracking-wider">Today's Periods</p>
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div class="flex items-baseline gap-2 mt-2">
                <h3 class="text-2xl font-extrabold text-slate-900 group-hover:text-emerald-600 transition"><?= count($todaySlots) ?></h3>
            </div>
            <span class="text-[11px] font-medium text-slate-500 mt-1 block">Scheduled for <?= htmlspecialchars($todayDayName) ?> &rarr;</span>
        </a>
    </div>

    <!-- Quick Instructional Actions Grid (6 Actions) -->
    <div>
        <h2 class="text-base font-bold text-slate-900 mb-3 flex items-center gap-2">
            <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
            </svg>
            Quick Actions & Shortcuts
        </h2>

        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
            <!-- 1. Learning Materials -->
            <a href="/teacher/content/create" class="bg-white p-4 rounded-xl border border-slate-200 shadow-xs hover:border-emerald-400 hover:shadow-sm transition text-center space-y-2 group">
                <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center mx-auto group-hover:scale-105 transition-transform">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                </div>
                <h3 class="text-xs font-bold text-slate-900 group-hover:text-emerald-700 transition">Upload Materials</h3>
                <p class="text-[10px] text-slate-400">PDFs, notes & slides</p>
            </a>

            <!-- 2. Create Assignment -->
            <a href="/teacher/assignments/create" class="bg-white p-4 rounded-xl border border-slate-200 shadow-xs hover:border-emerald-400 hover:shadow-sm transition text-center space-y-2 group">
                <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center mx-auto group-hover:scale-105 transition-transform">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                </div>
                <h3 class="text-xs font-bold text-slate-900 group-hover:text-blue-700 transition">New Assignment</h3>
                <p class="text-[10px] text-slate-400">Homework & tasks</p>
            </a>

            <!-- 3. Question Bank -->
            <a href="/teacher/question-bank/create" class="bg-white p-4 rounded-xl border border-slate-200 shadow-xs hover:border-emerald-400 hover:shadow-sm transition text-center space-y-2 group">
                <div class="w-10 h-10 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center mx-auto group-hover:scale-105 transition-transform">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <h3 class="text-xs font-bold text-slate-900 group-hover:text-purple-700 transition">Add Question</h3>
                <p class="text-[10px] text-slate-400">MCQ & bank items</p>
            </a>

            <!-- 4. Daily Attendance -->
            <a href="/teacher/attendance" class="bg-white p-4 rounded-xl border border-slate-200 shadow-xs hover:border-emerald-400 hover:shadow-sm transition text-center space-y-2 group">
                <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center mx-auto group-hover:scale-105 transition-transform">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <h3 class="text-xs font-bold text-slate-900 group-hover:text-amber-700 transition">Mark Attendance</h3>
                <p class="text-[10px] text-slate-400">Class roll-call</p>
            </a>

            <!-- 5. Gradebook -->
            <a href="/teacher/gradebook" class="bg-white p-4 rounded-xl border border-slate-200 shadow-xs hover:border-emerald-400 hover:shadow-sm transition text-center space-y-2 group">
                <div class="w-10 h-10 rounded-xl bg-teal-50 text-teal-600 flex items-center justify-center mx-auto group-hover:scale-105 transition-transform">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                </div>
                <h3 class="text-xs font-bold text-slate-900 group-hover:text-teal-700 transition">Enter Grades</h3>
                <p class="text-[10px] text-slate-400">CA & exam marks</p>
            </a>

            <!-- 6. Timetable -->
            <a href="/teacher/timetable" class="bg-white p-4 rounded-xl border border-slate-200 shadow-xs hover:border-emerald-400 hover:shadow-sm transition text-center space-y-2 group">
                <div class="w-10 h-10 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center mx-auto group-hover:scale-105 transition-transform">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                </div>
                <h3 class="text-xs font-bold text-slate-900 group-hover:text-rose-700 transition">My Timetable</h3>
                <p class="text-[10px] text-slate-400">Weekly schedule</p>
            </a>
        </div>
    </div>

    <!-- 2-Column Schedule & Coursework Section -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left 2-Columns: Today's Schedule & My Classes -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Today's Schedule Card -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                    <h2 class="text-base font-bold text-slate-900 flex items-center gap-2">
                        <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Today's Schedule (<?= htmlspecialchars($todayDayName) ?>)
                    </h2>
                    <a href="/teacher/timetable" class="text-xs font-semibold text-emerald-600 hover:text-emerald-700 transition">
                        Full Timetable &rarr;
                    </a>
                </div>

                <?php if (empty($todaySlots)): ?>
                    <div class="p-8 text-center text-xs text-slate-500">
                        <div class="w-12 h-12 rounded-xl bg-slate-50 text-slate-400 flex items-center justify-center mx-auto mb-2">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        </div>
                        <p class="font-medium text-slate-600">No teaching periods scheduled for today.</p>
                        <p class="text-[11px] text-slate-400 mt-0.5">Use this time for lesson preparation or grading coursework.</p>
                    </div>
                <?php else: ?>
                    <div class="divide-y divide-slate-100 text-xs">
                        <?php foreach ($todaySlots as $slot): ?>
                            <div class="p-4 flex items-center justify-between gap-3 hover:bg-slate-50/70 transition">
                                <div class="flex items-center gap-3 min-w-0">
                                    <div class="w-12 h-10 rounded-lg bg-emerald-50 text-emerald-700 flex flex-col items-center justify-center flex-shrink-0 font-mono text-[11px] font-bold border border-emerald-100">
                                        <span><?= htmlspecialchars(substr($slot->startTime, 0, 5)) ?></span>
                                    </div>
                                    <div class="truncate">
                                        <div class="flex items-center gap-2">
                                            <span class="font-bold text-slate-900 text-sm"><?= htmlspecialchars($slot->subjectName ?? 'Subject') ?></span>
                                            <span class="px-1.5 py-0.5 rounded font-mono text-[10px] font-bold bg-slate-100 text-slate-700">
                                                <?= htmlspecialchars($slot->subjectCode ?? '') ?>
                                            </span>
                                        </div>
                                        <p class="text-[11px] text-slate-500 mt-0.5">
                                            Class: <strong class="text-slate-800"><?= htmlspecialchars($slot->className ?? '') ?><?= !empty($slot->sectionArm) ? ' (' . htmlspecialchars($slot->sectionArm) . ')' : '' ?></strong>
                                            <?php if (!empty($slot->room)): ?>
                                                &bull; Room: <span class="font-mono text-slate-600"><?= htmlspecialchars($slot->room) ?></span>
                                            <?php endif; ?>
                                        </p>
                                    </div>
                                </div>
                                <span class="text-[11px] font-mono text-slate-500 flex-shrink-0">
                                    <?= htmlspecialchars(substr($slot->startTime, 0, 5)) ?> – <?= htmlspecialchars(substr($slot->endTime, 0, 5)) ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- My Assigned Classes & Subjects Card -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                    <h2 class="text-base font-bold text-slate-900">My Assigned Classes & Subjects</h2>
                    <?php $this->include('components/badge', [
                        'label' => count($classSubjects) . ' Allocations',
                        'variant' => 'brand',
                        'class' => 'text-xs font-semibold'
                    ]); ?>
                </div>

                <?php if (empty($classSubjects)): ?>
                    <div class="p-8 text-center text-xs text-slate-500">
                        No subject allocations assigned yet. Please contact the administrator for curriculum assignments.
                    </div>
                <?php else: ?>
                    <div class="p-6 grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <?php foreach ($classSubjects as $cs): ?>
                            <div class="p-4 rounded-xl border border-slate-200 bg-slate-50/50 hover:bg-white hover:border-emerald-300 hover:shadow-xs transition space-y-2.5">
                                <div class="flex items-start justify-between gap-2">
                                    <div>
                                        <span class="text-[10px] font-bold uppercase tracking-wider text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded">
                                            <?= htmlspecialchars($cs->subjectCode ?? 'SUBJ') ?>
                                        </span>
                                        <h3 class="font-bold text-slate-900 text-sm mt-1">
                                            <?= htmlspecialchars($cs->subjectName ?? 'Subject Name') ?>
                                        </h3>
                                        <p class="text-xs text-slate-500">
                                            <?= htmlspecialchars($cs->className ?? 'Class') ?><?= !empty($cs->sectionArm) ? ' (' . htmlspecialchars($cs->sectionArm) . ')' : '' ?>
                                        </p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2 pt-2 border-t border-slate-200/60 text-xs">
                                    <a href="/teacher/gradebook/<?= (int)$cs->id ?>" class="text-emerald-600 hover:text-emerald-700 font-semibold inline-flex items-center gap-1">
                                        Gradebook &rarr;
                                    </a>
                                    <span class="text-slate-300">&bull;</span>
                                    <a href="/teacher/content?class_subject_id=<?= (int)$cs->id ?>" class="text-slate-600 hover:text-slate-900 font-semibold inline-flex items-center gap-1">
                                        Materials &rarr;
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Right 1-Column: Recent Coursework & Announcements -->
        <div class="space-y-6">
            <!-- Active Assignments Widget -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-200 flex items-center justify-between">
                    <h2 class="text-sm font-bold text-slate-900">Recent Assignments</h2>
                    <a href="/teacher/assignments" class="text-xs font-semibold text-emerald-600 hover:text-emerald-700 transition">
                        View All &rarr;
                    </a>
                </div>

                <?php if (empty($assignments)): ?>
                    <div class="p-6 text-center text-xs text-slate-500">
                        No active assignments created. Click "+ New Assignment" above to assign coursework.
                    </div>
                <?php else: ?>
                    <div class="divide-y divide-slate-100 text-xs">
                        <?php foreach (array_slice($assignments, 0, 4) as $assign): ?>
                            <div class="p-4 hover:bg-slate-50 transition space-y-1">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-blue-50 text-blue-700">
                                        <?= htmlspecialchars($assign->subjectCode ?? 'ASSIGNMENT') ?>
                                    </span>
                                    <span class="text-[10px] text-slate-400 font-mono">
                                        Due: <?= !empty($assign->dueAt) ? htmlspecialchars(substr((string)$assign->dueAt, 0, 10)) : 'No deadline' ?>
                                    </span>
                                </div>
                                <h4 class="font-bold text-slate-900 leading-tight">
                                    <a href="/teacher/assignments/<?= (int)$assign->id ?>/submissions" class="hover:text-emerald-600 transition">
                                        <?= htmlspecialchars($assign->title) ?>
                                    </a>
                                </h4>
                                <p class="text-[11px] text-slate-400">
                                    <?= htmlspecialchars($assign->className ?? '') ?><?= !empty($assign->sectionArm) ? ' (' . htmlspecialchars($assign->sectionArm) . ')' : '' ?>
                                </p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Faculty Announcements Widget -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-200 flex items-center justify-between">
                    <h2 class="text-sm font-bold text-slate-900">Announcements</h2>
                    <a href="/teacher/announcements" class="text-xs font-semibold text-emerald-600 hover:text-emerald-700 transition">
                        Feed &rarr;
                    </a>
                </div>

                <?php if (empty($announcements)): ?>
                    <div class="p-6 text-center text-xs text-slate-500">
                        No active announcements broadcast.
                    </div>
                <?php else: ?>
                    <div class="divide-y divide-slate-100 text-xs">
                        <?php foreach ($announcements as $ann): ?>
                            <div class="p-4 hover:bg-slate-50 transition space-y-1">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase <?= $ann->scope === 'school' ? 'bg-emerald-50 text-emerald-700' : 'bg-blue-50 text-blue-700' ?>">
                                        <?= htmlspecialchars($ann->scope) ?>
                                    </span>
                                    <span class="text-[10px] text-slate-400 font-mono"><?= $ann->publishedAt ? htmlspecialchars(substr($ann->publishedAt, 0, 10)) : 'Draft' ?></span>
                                </div>
                                <h4 class="font-bold text-slate-900 leading-tight">
                                    <?= htmlspecialchars($ann->title) ?>
                                </h4>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
