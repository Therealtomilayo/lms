<div class="space-y-6">
    <!-- Header Card -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <!-- Breadcrumbs -->
                <nav class="flex items-center gap-2 text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">
                    <a href="/teacher/dashboard" class="text-slate-400 hover:text-emerald-600 transition">Faculty Portal</a>
                    <span class="text-slate-300">/</span>
                    <span class="text-slate-700">Teaching Timetable</span>
                </nav>
                <div class="flex flex-wrap items-center gap-3">
                    <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight leading-tight">
                        Faculty Weekly Teaching Schedule
                    </h1>
                </div>
                <p class="text-xs text-slate-500 mt-1">
                    Your assigned weekly teaching periods, classroom allocations, and scheduled lecture halls.
                </p>
            </div>

            <?php if (!empty($terms)): ?>
                <form method="GET" action="/teacher/timetable" class="flex items-center gap-2">
                    <label for="term_id" class="text-xs font-bold uppercase tracking-wider text-slate-500">Term:</label>
                    <select name="term_id" id="term_id" onchange="this.form.submit()" 
                            class="bg-slate-50 border border-slate-300 text-slate-900 text-xs font-bold rounded-xl px-3 py-2 focus:ring-emerald-500 focus:border-emerald-500 transition">
                        <?php foreach ($terms as $t): ?>
                            <option value="<?= (int)$t->id ?>" <?= $selectedTerm && (int)$selectedTerm->id === (int)$t->id ? 'selected' : '' ?>>
                                <?= htmlspecialchars($t->name) ?> <?= $t->isCurrent ? '(Current)' : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <?php if (isset($error) && $error): ?>
        <div class="p-4 bg-rose-50 border border-rose-200 text-rose-800 rounded-2xl text-xs font-semibold">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <!-- Schedule Matrix Calculation -->
    <?php
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
    $totalSlots = count($scheduleData['slots'] ?? []);
    ?>

    <!-- 4-Card KPI Summary Metrics Strip -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Total Periods -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs">
            <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Weekly Periods</p>
            <div class="flex items-baseline gap-2 mt-1">
                <h3 class="text-2xl font-extrabold text-slate-900"><?= number_format($totalSlots) ?></h3>
                <span class="text-xs font-semibold text-slate-500">slots</span>
            </div>
            <span class="text-[11px] font-medium text-slate-500 mt-1 block">
                Scheduled instructional periods
            </span>
        </div>

        <!-- Active Term -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs">
            <p class="text-xs font-bold uppercase tracking-wider text-emerald-600">Timetable Term</p>
            <div class="flex items-baseline gap-2 mt-1">
                <h3 class="text-lg font-extrabold text-slate-900 truncate"><?= htmlspecialchars($selectedTerm?->name ?? 'Current Term') ?></h3>
            </div>
            <span class="text-[11px] font-medium text-emerald-600 mt-1 block">
                Active scheduling term
            </span>
        </div>

        <!-- Instructional Days -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs">
            <p class="text-xs font-bold uppercase tracking-wider text-amber-500">Teaching Days</p>
            <div class="flex items-baseline gap-2 mt-1">
                <h3 class="text-2xl font-extrabold text-amber-600">5 Days</h3>
            </div>
            <span class="text-[11px] font-medium text-amber-600/90 mt-1 block">
                Monday to Friday
            </span>
        </div>

        <!-- Status -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs">
            <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Faculty Schedule</p>
            <div class="flex items-baseline gap-2 mt-1">
                <h3 class="text-lg font-extrabold text-slate-900">Synchronized</h3>
            </div>
            <span class="text-[11px] font-medium text-slate-500 mt-1 block">
                Official institution roster
            </span>
        </div>
    </div>

    <!-- Timetable Days Grid Container -->
    <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-xs space-y-6">
        <div class="flex items-center justify-between pb-4 border-b border-slate-100">
            <div>
                <h2 class="text-base font-bold text-slate-900">Weekly Schedule Breakdown</h2>
                <p class="text-xs text-slate-500">Overview of all assigned subject periods across the week.</p>
            </div>
            <span class="px-3 py-1 bg-emerald-50 text-emerald-800 border border-emerald-200 rounded-full text-xs font-bold">
                <?= $totalSlots ?> <?= $totalSlots === 1 ? 'Period' : 'Periods' ?> Total
            </span>
        </div>

        <?php if ($totalSlots === 0): ?>
            <div class="text-center py-12 border-2 border-dashed border-slate-200 rounded-2xl">
                <svg class="mx-auto h-12 w-12 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                <h3 class="mt-2 text-sm font-bold text-slate-900">No teaching periods scheduled</h3>
                <p class="mt-1 text-xs text-slate-500">You do not have any teaching slots scheduled for this academic term.</p>
            </div>
        <?php else: ?>
            <div class="space-y-4">
                <?php foreach ($days as $dayKey => $dayLabel): ?>
                    <?php 
                    $daySlots = $grid[$dayKey] ?? []; 
                    if (empty($daySlots) && ($dayKey === 'sat' || $dayKey === 'sun')) {
                        continue;
                    }
                    ?>
                    <div class="border border-slate-200 rounded-2xl overflow-hidden bg-slate-50/40">
                        <div class="px-4 py-3 bg-slate-100/70 border-b border-slate-200 flex items-center justify-between">
                            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-800 flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-emerald-600"></span>
                                <?= $dayLabel ?>
                            </h3>
                            <span class="text-[11px] font-bold text-slate-600">
                                <?= count($daySlots) ?> <?= count($daySlots) === 1 ? 'Period' : 'Periods' ?>
                            </span>
                        </div>

                        <div class="p-4">
                            <?php if (empty($daySlots)): ?>
                                <p class="text-xs text-slate-400 italic py-1">No teaching slots allocated for <?= $dayLabel ?>.</p>
                            <?php else: ?>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                    <?php foreach ($daySlots as $slot): ?>
                                        <div class="p-4 bg-white rounded-xl border border-slate-200 shadow-xs flex flex-col justify-between hover:border-slate-300 transition">
                                            <div>
                                                <div class="flex items-center justify-between gap-2">
                                                    <span class="px-2 py-0.5 rounded text-[11px] font-bold bg-emerald-50 text-emerald-800 border border-emerald-200">
                                                        <?= htmlspecialchars($slot->classSubject?->subjectCode ?: 'SUB') ?>
                                                    </span>
                                                    <?php if ($slot->room): ?>
                                                        <span class="px-2 py-0.5 rounded text-[11px] font-semibold bg-slate-100 text-slate-700">
                                                            Room: <?= htmlspecialchars($slot->room) ?>
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                                <h4 class="text-sm font-bold text-slate-900 mt-2">
                                                    <?= htmlspecialchars($slot->classSubject?->subjectName ?: 'Subject') ?>
                                                </h4>
                                                <p class="text-xs font-semibold text-slate-600 mt-0.5">
                                                    Class: <strong class="text-emerald-700"><?= htmlspecialchars($slot->classSubject?->className ?? 'Class') ?></strong>
                                                </p>
                                            </div>

                                            <div class="mt-3 pt-2.5 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
                                                <span class="font-bold text-slate-800 flex items-center gap-1.5">
                                                    <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                    </svg>
                                                    <?= htmlspecialchars($slot->getFormattedTimeRange()) ?>
                                                </span>
                                                <span class="font-medium"><?= $slot->getDurationMinutes() ?> mins</span>
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
