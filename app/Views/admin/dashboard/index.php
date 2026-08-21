<div class="space-y-6">
    <!-- Welcome Header Card -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <!-- Breadcrumbs -->
                <nav class="flex items-center gap-2 text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">
                    <span class="text-slate-400">Admin</span>
                    <span class="text-slate-300">/</span>
                    <span class="text-slate-700">Dashboard</span>
                </nav>
                <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight leading-tight">
                    Welcome back, <?= htmlspecialchars($userContext->name) ?>
                </h1>
                <p class="text-xs text-slate-500 mt-1">
                    Claret International School — School Management & Administration Command Center.
                </p>
            </div>

            <!-- Active Academic Session & Term Badges -->
            <div class="flex items-center gap-2.5 flex-wrap">
                <?php if ($currentSession): ?>
                    <div class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-slate-100 border border-slate-200 text-xs font-semibold text-slate-700">
                        <svg class="w-4 h-4 text-brand-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <span><?= htmlspecialchars($currentSession->name) ?> Session</span>
                    </div>
                <?php endif; ?>

                <?php if ($currentTerm): ?>
                    <div class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-brand-50 border border-brand-200 text-xs font-bold text-brand-700">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        <span><?= htmlspecialchars($currentTerm->name) ?> (Active)</span>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- 4-Card Overview Metric Strip -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Students Card -->
        <a href="/admin/users?role=student" class="bg-white p-5 rounded-xl border border-slate-200 shadow-xs hover:border-brand-300 hover:shadow-sm transition group">
            <div class="flex items-center justify-between text-slate-400 group-hover:text-brand-600 transition">
                <p class="text-xs font-semibold uppercase tracking-wider">Enrolled Students</p>
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>
            </div>
            <div class="flex items-baseline gap-2 mt-2">
                <h3 class="text-2xl font-extrabold text-slate-900 group-hover:text-brand-600 transition"><?= number_format($studentCount) ?></h3>
            </div>
            <span class="text-[11px] font-medium text-slate-500 mt-1 block">Active student profiles &rarr;</span>
        </a>

        <!-- Teachers Card -->
        <a href="/admin/users?role=teacher" class="bg-white p-5 rounded-xl border border-slate-200 shadow-xs hover:border-brand-300 hover:shadow-sm transition group">
            <div class="flex items-center justify-between text-slate-400 group-hover:text-brand-600 transition">
                <p class="text-xs font-semibold uppercase tracking-wider">Teaching Staff</p>
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z" />
                </svg>
            </div>
            <div class="flex items-baseline gap-2 mt-2">
                <h3 class="text-2xl font-extrabold text-slate-900 group-hover:text-brand-600 transition"><?= number_format($teacherCount) ?></h3>
            </div>
            <span class="text-[11px] font-medium text-slate-500 mt-1 block">Subject & class educators &rarr;</span>
        </a>

        <!-- Classes Card -->
        <a href="/admin/classes" class="bg-white p-5 rounded-xl border border-slate-200 shadow-xs hover:border-brand-300 hover:shadow-sm transition group">
            <div class="flex items-center justify-between text-slate-400 group-hover:text-brand-600 transition">
                <p class="text-xs font-semibold uppercase tracking-wider">Classes & Arms</p>
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                </svg>
            </div>
            <div class="flex items-baseline gap-2 mt-2">
                <h3 class="text-2xl font-extrabold text-slate-900 group-hover:text-brand-600 transition"><?= number_format($classCount) ?></h3>
            </div>
            <span class="text-[11px] font-medium text-slate-500 mt-1 block">Configured academic arms &rarr;</span>
        </a>

        <!-- Subjects Card -->
        <a href="/admin/subjects" class="bg-white p-5 rounded-xl border border-slate-200 shadow-xs hover:border-brand-300 hover:shadow-sm transition group">
            <div class="flex items-center justify-between text-slate-400 group-hover:text-brand-600 transition">
                <p class="text-xs font-semibold uppercase tracking-wider">Curriculum Subjects</p>
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                </svg>
            </div>
            <div class="flex items-baseline gap-2 mt-2">
                <h3 class="text-2xl font-extrabold text-slate-900 group-hover:text-brand-600 transition"><?= number_format($subjectCount) ?></h3>
            </div>
            <span class="text-[11px] font-medium text-slate-500 mt-1 block">Active course subjects &rarr;</span>
        </a>
    </div>

    <!-- Quick Operations Command Hub (6 Modules Grid) -->
    <div>
        <h2 class="text-base font-bold text-slate-900 mb-3 flex items-center gap-2">
            <svg class="w-4 h-4 text-brand-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
            </svg>
            School Management Quick Navigation
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <!-- 1. People & Directory -->
            <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs space-y-3">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-sm">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-slate-900">People & Enrollment</h3>
                        <p class="text-[11px] text-slate-400">Users, Guardians & Imports</p>
                    </div>
                </div>
                <div class="space-y-1.5 pt-2 border-t border-slate-100 text-xs">
                    <a href="/admin/users" class="flex items-center justify-between py-1 px-2 rounded-lg hover:bg-slate-50 text-slate-700 hover:text-brand-600 font-medium transition">
                        <span>User Directory</span>
                        <span class="text-slate-400">&rarr;</span>
                    </a>
                    <a href="/admin/enrollments" class="flex items-center justify-between py-1 px-2 rounded-lg hover:bg-slate-50 text-slate-700 hover:text-brand-600 font-medium transition">
                        <span>Class Enrollments</span>
                        <span class="text-slate-400">&rarr;</span>
                    </a>
                    <a href="/admin/imports/users" class="flex items-center justify-between py-1 px-2 rounded-lg hover:bg-slate-50 text-slate-700 hover:text-brand-600 font-medium transition">
                        <span>CSV Bulk Importer</span>
                        <span class="text-slate-400">&rarr;</span>
                    </a>
                </div>
            </div>

            <!-- 2. Academic Scheduling -->
            <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs space-y-3">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center font-bold text-sm">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-slate-900">Academic & Timetable</h3>
                        <p class="text-[11px] text-slate-400">Class Timetable Matrix</p>
                    </div>
                </div>
                <div class="space-y-1.5 pt-2 border-t border-slate-100 text-xs">
                    <a href="/admin/timetable" class="flex items-center justify-between py-1 px-2 rounded-lg hover:bg-slate-50 text-slate-700 hover:text-brand-600 font-medium transition">
                        <span>Timetables List & Builder</span>
                        <span class="text-slate-400">&rarr;</span>
                    </a>
                    <a href="/admin/class-subjects" class="flex items-center justify-between py-1 px-2 rounded-lg hover:bg-slate-50 text-slate-700 hover:text-brand-600 font-medium transition">
                        <span>Teacher Subject Mapping</span>
                        <span class="text-slate-400">&rarr;</span>
                    </a>
                    <a href="/admin/sessions" class="flex items-center justify-between py-1 px-2 rounded-lg hover:bg-slate-50 text-slate-700 hover:text-brand-600 font-medium transition">
                        <span>Academic Sessions & Terms</span>
                        <span class="text-slate-400">&rarr;</span>
                    </a>
                </div>
            </div>

            <!-- 3. Attendance & Registers -->
            <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs space-y-3">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-teal-50 text-teal-600 flex items-center justify-center font-bold text-sm">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-slate-900">Attendance Oversight</h3>
                        <p class="text-[11px] text-slate-400">Daily Registers & Reporting</p>
                    </div>
                </div>
                <div class="space-y-1.5 pt-2 border-t border-slate-100 text-xs">
                    <a href="/admin/attendance" class="flex items-center justify-between py-1 px-2 rounded-lg hover:bg-slate-50 text-slate-700 hover:text-brand-600 font-medium transition">
                        <span>Daily Class Registers</span>
                        <span class="text-slate-400">&rarr;</span>
                    </a>
                    <a href="/admin/attendance/report" class="flex items-center justify-between py-1 px-2 rounded-lg hover:bg-slate-50 text-slate-700 hover:text-brand-600 font-medium transition">
                        <span>Attendance Analytics Report</span>
                        <span class="text-slate-400">&rarr;</span>
                    </a>
                </div>
            </div>

            <!-- 4. Grading & Results -->
            <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs space-y-3">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center font-bold text-sm">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-slate-900">Grading & Results</h3>
                        <p class="text-[11px] text-slate-400">Scales, Review & Publication</p>
                    </div>
                </div>
                <div class="space-y-1.5 pt-2 border-t border-slate-100 text-xs">
                    <a href="/admin/results/review" class="flex items-center justify-between py-1 px-2 rounded-lg hover:bg-slate-50 text-slate-700 hover:text-brand-600 font-medium transition">
                        <span>Term Results Review</span>
                        <span class="text-slate-400">&rarr;</span>
                    </a>
                    <a href="/admin/grading-scales" class="flex items-center justify-between py-1 px-2 rounded-lg hover:bg-slate-50 text-slate-700 hover:text-brand-600 font-medium transition">
                        <span>Grading Scales (A1–F9)</span>
                        <span class="text-slate-400">&rarr;</span>
                    </a>
                    <a href="/admin/assessment-categories" class="flex items-center justify-between py-1 px-2 rounded-lg hover:bg-slate-50 text-slate-700 hover:text-brand-600 font-medium transition">
                        <span>Assessment Categories</span>
                        <span class="text-slate-400">&rarr;</span>
                    </a>
                </div>
            </div>

            <!-- 5. Announcements -->
            <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs space-y-3">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center font-bold text-sm">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/></svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-slate-900">Communication</h3>
                        <p class="text-[11px] text-slate-400">Broadcasts & Notices</p>
                    </div>
                </div>
                <div class="space-y-1.5 pt-2 border-t border-slate-100 text-xs">
                    <a href="/admin/announcements" class="flex items-center justify-between py-1 px-2 rounded-lg hover:bg-slate-50 text-slate-700 hover:text-brand-600 font-medium transition">
                        <span>Announcements Feed</span>
                        <span class="text-slate-400">&rarr;</span>
                    </a>
                    <a href="/admin/announcements/create" class="flex items-center justify-between py-1 px-2 rounded-lg hover:bg-slate-50 text-slate-700 hover:text-brand-600 font-medium transition">
                        <span>Broadcast New Announcement</span>
                        <span class="text-slate-400">&rarr;</span>
                    </a>
                </div>
            </div>

            <!-- 6. System & Security -->
            <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs space-y-3">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-slate-100 text-slate-700 flex items-center justify-center font-bold text-sm">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-slate-900">System & Security</h3>
                        <p class="text-[11px] text-slate-400">Health, Backups & Audit</p>
                    </div>
                </div>
                <div class="space-y-1.5 pt-2 border-t border-slate-100 text-xs">
                    <a href="/admin/health" class="flex items-center justify-between py-1 px-2 rounded-lg hover:bg-slate-50 text-slate-700 hover:text-brand-600 font-medium transition">
                        <span>System Health & Diagnostics</span>
                        <span class="text-slate-400">&rarr;</span>
                    </a>
                    <a href="/admin/backups" class="flex items-center justify-between py-1 px-2 rounded-lg hover:bg-slate-50 text-slate-700 hover:text-brand-600 font-medium transition">
                        <span>Database Backups & Dumps</span>
                        <span class="text-slate-400">&rarr;</span>
                    </a>
                    <a href="/admin/audit-logs" class="flex items-center justify-between py-1 px-2 rounded-lg hover:bg-slate-50 text-slate-700 hover:text-brand-600 font-medium transition">
                        <span>Audit Trail & Event Log</span>
                        <span class="text-slate-400">&rarr;</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- 2-Column Activity & Intelligence Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left: Recent System Audit Events (2 Columns) -->
        <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                <h2 class="text-base font-bold text-slate-900 flex items-center gap-2">
                    <svg class="w-4 h-4 text-brand-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Recent System Audit Trail
                </h2>
                <a href="/admin/audit-logs" class="text-xs font-semibold text-brand-600 hover:text-brand-700 transition">
                    View All Logs &rarr;
                </a>
            </div>

            <?php if (empty($recentAudit)): ?>
                <div class="p-8 text-center text-xs text-slate-500">
                    No recent audit events recorded yet.
                </div>
            <?php else: ?>
                <div class="divide-y divide-slate-100 text-xs">
                    <?php foreach ($recentAudit as $log): ?>
                        <div class="p-4 flex items-center justify-between gap-3 hover:bg-slate-50/70 transition">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="w-8 h-8 rounded-lg bg-slate-100 text-slate-600 flex items-center justify-center font-bold text-xs flex-shrink-0">
                                    <?= htmlspecialchars(substr($log->actorName ?: 'SYS', 0, 1)) ?>
                                </div>
                                <div class="truncate">
                                    <div class="flex items-center gap-2">
                                        <span class="font-bold text-slate-900 truncate"><?= htmlspecialchars($log->actorName ?: 'System') ?></span>
                                        <span class="px-1.5 py-0.5 rounded font-mono text-[10px] font-bold bg-brand-50 text-brand-700">
                                            <?= htmlspecialchars($log->action) ?>
                                        </span>
                                    </div>
                                    <p class="text-[11px] text-slate-400 mt-0.5">
                                        Target: <span class="font-mono font-medium text-slate-600"><?= htmlspecialchars($log->entityType) ?> #<?= (int)$log->entityId ?></span>
                                    </p>
                                </div>
                            </div>
                            <span class="text-[11px] font-mono text-slate-400 flex-shrink-0">
                                <?= htmlspecialchars(substr($log->createdAt, 0, 16)) ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Right: Recent Announcements & System Health (1 Column) -->
        <div class="space-y-6">
            <!-- Recent Announcements Card -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-200 flex items-center justify-between">
                    <h2 class="text-sm font-bold text-slate-900">Broadcasts</h2>
                    <a href="/admin/announcements" class="text-xs font-semibold text-brand-600 hover:text-brand-700 transition">
                        Feed &rarr;
                    </a>
                </div>

                <?php if (empty($recentAnnouncements)): ?>
                    <div class="p-6 text-center text-xs text-slate-500">
                        No active announcements published.
                    </div>
                <?php else: ?>
                    <div class="divide-y divide-slate-100 text-xs">
                        <?php foreach ($recentAnnouncements as $ann): ?>
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

            <!-- Health Snapshot Card -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-5 space-y-3">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-bold text-slate-900">System Telemetry</h3>
                    <?php $this->include('components/badge', [
                        'label' => strtoupper($health->status),
                        'variant' => $health->status === 'healthy' ? 'success' : 'warning',
                        'class' => 'text-[10px] font-bold font-mono px-2 py-0.5'
                    ]); ?>
                </div>
                <div class="space-y-2 text-xs text-slate-600 pt-1">
                    <div class="flex justify-between py-1 border-b border-slate-100">
                        <span class="text-slate-500">Database Status</span>
                        <span class="font-semibold text-emerald-600">Operational</span>
                    </div>
                    <div class="flex justify-between py-1 border-b border-slate-100">
                        <span class="text-slate-500">Storage & Logs</span>
                        <span class="font-semibold text-emerald-600">100% Writable</span>
                    </div>
                </div>
                <div class="pt-2">
                    <a href="/admin/health" class="text-xs text-brand-600 hover:text-brand-700 font-semibold block text-right">
                        Full Diagnostics &rarr;
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
