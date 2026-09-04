<div class="space-y-6">
    <!-- Header Card -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <!-- Breadcrumbs -->
                <nav class="flex items-center gap-2 text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">
                    <a href="/student/dashboard" class="hover:text-sky-600 transition">Student Portal</a>
                    <span class="text-slate-300">/</span>
                    <span class="text-slate-700">Class Timetable</span>
                </nav>
                <div class="flex flex-wrap items-center gap-3">
                    <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight leading-tight">
                        Weekly Class Timetable
                    </h1>
                    <?php if ($student && $student->schoolClass): ?>
                        <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold bg-sky-50 text-sky-800 border border-sky-200">
                            <?= htmlspecialchars($student->schoolClass->name) ?>
                        </span>
                    <?php endif; ?>
                </div>
                <p class="text-xs text-slate-500 mt-1">
                    Instructional periods, subject slots, teacher assignments, and classroom locations for <strong><?= htmlspecialchars($selectedTerm?->name ?? 'Current Term') ?></strong>.
                </p>
            </div>

            <!-- Term Switcher -->
            <?php if (!empty($terms)): ?>
                <div>
                    <form method="GET" action="/student/timetable" class="flex items-center gap-2">
                        <label for="term_id" class="text-xs font-bold text-slate-700 uppercase tracking-wider">Term:</label>
                        <select name="term_id" id="term_id" onchange="this.form.submit()" 
                                class="px-3.5 py-2 text-xs font-bold rounded-xl border border-slate-300 bg-slate-50 text-slate-800 focus:bg-white focus:border-sky-500 focus:ring-sky-500 transition">
                            <?php foreach ($terms as $t): ?>
                                <option value="<?= (int)$t->id ?>" <?= $selectedTerm && (int)$selectedTerm->id === (int)$t->id ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($t->name) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- 4-Card KPI Summary Metrics Strip -->
    <?php
        $totalSlots = count($scheduleData['slots'] ?? []);
        $days = [
            'mon' => 'Monday',
            'tue' => 'Tuesday',
            'wed' => 'Wednesday',
            'thu' => 'Thursday',
            'fri' => 'Friday',
            'sat' => 'Saturday',
            'sun' => 'Sunday',
        ];
        $grid = $scheduleData['grid'] ?? [];
        $activeDaysCount = 0;
        foreach ($days as $dayKey => $dayLabel) {
            if (!empty($grid[$dayKey])) {
                $activeDaysCount++;
            }
        }
    ?>
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Class Cohort -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs">
            <p class="text-xs font-bold uppercase tracking-wider text-sky-600">Assigned Cohort</p>
            <div class="flex items-baseline gap-1 mt-1">
                <h3 class="text-lg font-extrabold text-slate-900 truncate">
                    <?= htmlspecialchars($scheduleData['class']->name ?? $student->schoolClass?->name ?? 'Enrolled') ?>
                </h3>
            </div>
            <span class="text-[11px] font-medium text-slate-500 mt-1 block">
                Primary Classroom
            </span>
        </div>

        <!-- Weekly Periods -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs">
            <p class="text-xs font-bold uppercase tracking-wider text-emerald-600">Weekly Lessons</p>
            <div class="flex items-baseline gap-1 mt-1">
                <h3 class="text-2xl font-extrabold text-emerald-600"><?= $totalSlots ?></h3>
                <span class="text-xs font-semibold text-slate-500">periods</span>
            </div>
            <span class="text-[11px] font-medium text-emerald-600 mt-1 block">
                Total scheduled slots
            </span>
        </div>

        <!-- Active Days -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs">
            <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Class Days</p>
            <div class="flex items-baseline gap-1 mt-1">
                <h3 class="text-2xl font-extrabold text-slate-900"><?= $activeDaysCount ?></h3>
                <span class="text-xs font-semibold text-slate-500">days / wk</span>
            </div>
            <span class="text-[11px] font-medium text-slate-500 mt-1 block">
                Instructional schedule
            </span>
        </div>

        <!-- Academic Term -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs">
            <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Current Term</p>
            <div class="flex items-baseline gap-1 mt-1">
                <h3 class="text-base font-extrabold text-slate-900 truncate"><?= htmlspecialchars($selectedTerm?->name ?? 'Active Term') ?></h3>
            </div>
            <span class="text-[11px] font-medium text-slate-500 mt-1 block">
                <?= htmlspecialchars($activeSession?->name ?? 'Active Session') ?>
            </span>
        </div>
    </div>

    <!-- Timetable Matrix Grid -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6 space-y-6">
        <div class="flex items-center justify-between border-b border-slate-100 pb-4">
            <h2 class="text-base font-bold text-slate-900">Weekly Learning Matrix</h2>
            <span class="text-xs font-bold text-slate-400 font-mono"><?= $totalSlots ?> Scheduled Periods</span>
        </div>

        <?php if ($totalSlots === 0): ?>
            <div class="p-12 text-center text-slate-500 text-xs">
                <div class="w-12 h-12 rounded-2xl bg-slate-100 text-slate-400 mx-auto flex items-center justify-center mb-3">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>
                <h3 class="text-sm font-bold text-slate-800">No Scheduled Periods</h3>
                <p class="text-xs text-slate-400 mt-1">There are no lecture periods published for your cohort in this academic term.</p>
            </div>
        <?php else: ?>
            <div class="space-y-6">
                <?php foreach ($days as $dayKey => $dayLabel): ?>
                    <?php 
                        $daySlots = $grid[$dayKey] ?? []; 
                        if (empty($daySlots) && ($dayKey === 'sat' || $dayKey === 'sun')) {
                            continue;
                        }
                    ?>
                    <div class="border border-slate-200 rounded-2xl overflow-hidden bg-slate-50/50">
                        <div class="px-5 py-3 bg-slate-100 border-b border-slate-200 flex items-center justify-between">
                            <h3 class="text-xs font-extrabold uppercase tracking-wider text-slate-900 flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-sky-500"></span>
                                <span><?= $dayLabel ?></span>
                            </h3>
                            <span class="text-[11px] font-bold text-slate-500">
                                <?= count($daySlots) ?> <?= count($daySlots) === 1 ? 'Period' : 'Periods' ?>
                            </span>
                        </div>

                        <div class="p-5">
                            <?php if (empty($daySlots)): ?>
                                <p class="text-xs text-slate-400 italic py-1">No lessons scheduled for <?= $dayLabel ?>.</p>
                            <?php else: ?>
                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                    <?php foreach ($daySlots as $idx => $slot): ?>
                                        <?php
                                            $periodNum = !empty($slot->periodNumber) ? $slot->periodNumber : ($idx + 1);
                                            $teacherName = $slot->teacherName ?: ($slot->classSubject?->teacherName ?: 'Subject Teacher');
                                            $subjectName = $slot->subjectName ?: ($slot->classSubject?->subjectName ?: 'Subject');
                                        ?>
                                        <div class="p-4 bg-white rounded-xl border border-slate-200 shadow-xs flex flex-col justify-between hover:border-slate-300 transition space-y-3">
                                            <div>
                                                <div class="flex items-center justify-between gap-2 mb-2">
                                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-sky-50 text-sky-800 border border-sky-200">
                                                        Period <?= (int)$periodNum ?>
                                                    </span>
                                                    <span class="text-[11px] font-mono font-bold text-slate-500">
                                                        <?= date('g:i A', strtotime($slot->startTime)) ?> &ndash; <?= date('g:i A', strtotime($slot->endTime)) ?>
                                                    </span>
                                                </div>

                                                <h4 class="font-extrabold text-sm text-slate-900">
                                                    <?= htmlspecialchars($subjectName) ?>
                                                </h4>

                                                <?php if (!empty($slot->subjectCode)): ?>
                                                    <span class="text-[10px] font-mono font-bold text-slate-400">
                                                        <?= htmlspecialchars($slot->subjectCode) ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>

                                            <div class="pt-3 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
                                                <span>Teacher: <strong class="text-slate-800"><?= htmlspecialchars($teacherName) ?></strong></span>
                                                <?php if (!empty($slot->room)): ?>
                                                    <span class="font-semibold text-slate-700 bg-slate-100 px-2 py-0.5 rounded text-[11px]"><?= htmlspecialchars($slot->room) ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
