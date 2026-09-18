<div class="space-y-6">
    <!-- Header Card -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <!-- Breadcrumbs -->
                <nav class="flex items-center gap-2 text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">
                    <a href="/student/dashboard" class="hover:text-sky-600 transition">Student Portal</a>
                    <span class="text-slate-300">/</span>
                    <span class="text-slate-700">Attendance Log</span>
                </nav>
                <div class="flex flex-wrap items-center gap-3">
                    <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight leading-tight">
                        My Attendance Tracker
                    </h1>
                    <?php if ($student && $student->schoolClass): ?>
                        <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold bg-sky-50 text-sky-800 border border-sky-200">
                            <?= htmlspecialchars($student->schoolClass->name) ?>
                        </span>
                    <?php endif; ?>
                </div>
                <p class="text-xs text-slate-500 mt-1">
                    Review your presence statistics, roll call history, and punctuality records.
                </p>
            </div>

            <!-- Term Selector Form -->
            <div>
                <form method="GET" action="/student/attendance" class="flex items-center gap-2">
                    <label for="term_id" class="text-xs font-bold text-slate-700 uppercase tracking-wider">Term:</label>
                    <select name="term_id" id="term_id" onchange="this.form.submit()" 
                            class="px-3.5 py-2 text-xs font-bold rounded-xl border border-slate-300 bg-slate-50 text-slate-800 focus:bg-white focus:border-sky-500 focus:ring-sky-500 transition">
                        <?php foreach ($terms as $t): ?>
                            <option value="<?= (int)$t->id ?>" <?= $selectedTermId === (int)$t->id ? 'selected' : '' ?>>
                                <?= htmlspecialchars($t->name) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>
        </div>
    </div>

    <!-- 4-Card KPI Summary Metrics Strip -->
    <?php
        $weightedRate = (float)($summary['weighted_rate'] ?? $summary['attendance_rate'] ?? 100);
        $unweightedRate = (float)($summary['unweighted_rate'] ?? $summary['attendance_rate'] ?? 100);
        $lateWeight = (float)($summary['late_weight'] ?? 0.6);
        $totalDays = (int)($summary['total_days'] ?? 0);
        $presentDays = (int)($summary['present_days'] ?? 0);
        $lateDays = (int)($summary['late_days'] ?? 0);
        $absentDays = (int)($summary['absent_days'] ?? 0);
    ?>
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Attendance Rate (Weighted SRS §26) -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs">
            <div class="flex items-center justify-between">
                <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Attendance Rate</p>
                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-sky-50 text-sky-700 border border-sky-200" title="School Policy: Late arrivals receive <?= round($lateWeight * 100) ?>% credit">
                    <?= round($lateWeight * 100) ?>% Late Credit
                </span>
            </div>
            <div class="flex items-baseline gap-1 mt-1">
                <h3 class="text-2xl font-extrabold <?= $weightedRate >= 75 ? 'text-emerald-600' : 'text-rose-600' ?>">
                    <?= number_format($weightedRate, 1) ?>%
                </h3>
            </div>
            <span class="text-[11px] font-medium <?= $weightedRate >= 75 ? 'text-emerald-600' : 'text-rose-600' ?> mt-1 block">
                <?= $weightedRate >= 75 ? 'Good Standing (&ge; 75%)' : 'Warning: Below 75%' ?>
            </span>
        </div>

        <!-- Present Days -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs">
            <p class="text-xs font-bold uppercase tracking-wider text-emerald-600">Present</p>
            <div class="flex items-baseline gap-1 mt-1">
                <h3 class="text-2xl font-extrabold text-slate-900"><?= $presentDays ?></h3>
                <span class="text-xs font-semibold text-slate-500">/ <?= $totalDays ?> days</span>
            </div>
            <span class="text-[11px] font-medium text-emerald-600 mt-1 block">
                Confirmed in class
            </span>
        </div>

        <!-- Late Check-ins -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs">
            <p class="text-xs font-bold uppercase tracking-wider text-amber-500">Late Arrivals</p>
            <div class="flex items-baseline gap-1 mt-1">
                <h3 class="text-2xl font-extrabold text-amber-600"><?= $lateDays ?></h3>
                <span class="text-xs font-semibold text-slate-500">sessions</span>
            </div>
            <span class="text-[11px] font-medium text-amber-600 mt-1 block">
                Punctuality logs
            </span>
        </div>

        <!-- Absent -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs">
            <p class="text-xs font-bold uppercase tracking-wider text-rose-500">Absences</p>
            <div class="flex items-baseline gap-1 mt-1">
                <h3 class="text-2xl font-extrabold text-rose-600"><?= $absentDays ?></h3>
                <span class="text-xs font-semibold text-slate-500">missed</span>
            </div>
            <span class="text-[11px] font-medium text-slate-500 mt-1 block">
                Total missed days
            </span>
        </div>
    </div>

    <!-- History Log Table Card -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="p-5 border-b border-slate-100 flex items-center justify-between">
            <h2 class="text-base font-bold text-slate-900">Attendance Roll Call History</h2>
            <span class="text-xs font-bold text-slate-400 font-mono"><?= count($history) ?> Entries</span>
        </div>

        <?php if (empty($history)): ?>
            <div class="p-12 text-center text-slate-500 text-xs">
                <div class="w-12 h-12 rounded-2xl bg-slate-100 text-slate-400 mx-auto flex items-center justify-center mb-3">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>
                <h3 class="text-sm font-bold text-slate-800">No Attendance Records Found</h3>
                <p class="text-xs text-slate-400 mt-1">There are no roll-call records logged for this selected term.</p>
            </div>
        <?php else: ?>
            <div class="divide-y divide-slate-100">
                <?php foreach ($history as $rec): ?>
                    <div class="p-4 sm:p-5 flex items-center justify-between hover:bg-slate-50/50 transition">
                        <div class="space-y-1">
                            <div class="font-bold text-slate-900 text-sm">
                                <?= htmlspecialchars(date('l, F j, Y', strtotime($rec->date))) ?>
                            </div>
                            <div class="text-xs text-slate-500 flex items-center gap-2">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-slate-100 text-slate-700">
                                    <?= $rec->isDaily() ? 'Daily Attendance' : "Period {$rec->periodNumber}" ?>
                                </span>
                                <?php if (!empty($rec->remarks)): ?>
                                    <span class="text-slate-400">&bull;</span>
                                    <span><?= htmlspecialchars($rec->remarks) ?></span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div>
                            <?php if ($rec->status === 'present'): ?>
                                <span class="inline-flex items-center px-3 py-1 rounded-lg text-xs font-bold bg-emerald-50 text-emerald-800 border border-emerald-200">
                                    Present
                                </span>
                            <?php elseif ($rec->status === 'late'): ?>
                                <span class="inline-flex items-center px-3 py-1 rounded-lg text-xs font-bold bg-amber-50 text-amber-800 border border-amber-200">
                                    Late
                                </span>
                            <?php elseif ($rec->status === 'absent'): ?>
                                <span class="inline-flex items-center px-3 py-1 rounded-lg text-xs font-bold bg-rose-50 text-rose-800 border border-rose-200">
                                    Absent
                                </span>
                            <?php else: ?>
                                <span class="inline-flex items-center px-3 py-1 rounded-lg text-xs font-bold bg-sky-50 text-sky-800 border border-sky-200">
                                    Excused
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
