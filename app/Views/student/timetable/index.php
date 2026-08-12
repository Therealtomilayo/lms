<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
        <div>
            <h1 class="text-xl font-bold text-slate-900">My Class Timetable</h1>
            <p class="text-xs text-slate-600 mt-1">Weekly subject schedule, class periods, teacher assignments, and classroom locations for <strong><?= htmlspecialchars($selectedTerm?->name ?? 'Active Term') ?></strong>.</p>
        </div>
        <?php if (!empty($terms)): ?>
            <div class="flex items-center gap-2">
                <form method="GET" action="/student/timetable" class="flex items-center gap-2">
                    <label for="term_id" class="text-xs font-semibold text-slate-600 uppercase tracking-wider">Term:</label>
                    <select name="term_id" id="term_id" onchange="this.form.submit()" class="bg-slate-50 border border-slate-300 text-slate-900 text-xs rounded-lg px-3 py-2 focus:ring-2 focus:ring-brand-500 focus:border-brand-500 font-medium">
                        <?php foreach ($terms as $t): ?>
                            <option value="<?= (int)$t->id ?>" <?= $selectedTerm && (int)$selectedTerm->id === (int)$t->id ? 'selected' : '' ?>>
                                <?= htmlspecialchars($t->name) ?> <?= $t->isCurrent ? '(Current)' : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>
        <?php endif; ?>
    </div>

    <?php if (isset($error) && $error): ?>
        <div class="p-4 bg-rose-50 border border-rose-200 text-rose-800 rounded-xl text-sm font-medium">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <!-- Class Cohort Info Card -->
    <?php if (!empty($scheduleData['class'])): ?>
        <div class="bg-gradient-to-r from-brand-600 to-indigo-700 rounded-xl p-5 text-white shadow-md flex items-center justify-between">
            <div>
                <span class="text-xs uppercase font-bold tracking-widest text-brand-200">Enrolled Class Cohort</span>
                <h2 class="text-lg font-extrabold mt-0.5"><?= htmlspecialchars($scheduleData['class']->name) ?> <?= $scheduleData['class']->sectionArm ? "({$scheduleData['class']->sectionArm})" : '' ?></h2>
            </div>
            <span class="px-3 py-1 bg-white/15 backdrop-blur-sm rounded-full text-xs font-medium border border-white/20">
                <?= count($scheduleData['slots'] ?? []) ?> Scheduled Periods
            </span>
        </div>
    <?php endif; ?>

    <!-- Weekly Timetable Grid -->
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

    <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm space-y-6">
        <div class="flex items-center justify-between pb-4 border-b border-slate-200">
            <div>
                <h2 class="text-base font-bold text-slate-900">Weekly Schedule Matrix</h2>
                <p class="text-xs text-slate-500">Your instructional periods organized day-by-day.</p>
            </div>
        </div>

        <?php if ($totalSlots === 0): ?>
            <div class="text-center py-12 border-2 border-dashed border-slate-200 rounded-xl">
                <svg class="mx-auto h-12 w-12 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                <h3 class="mt-2 text-sm font-medium text-slate-900">No scheduled periods</h3>
                <p class="mt-1 text-xs text-slate-500">There are no classes scheduled for your enrolled cohort in this academic term.</p>
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
                    <div class="border border-slate-200 rounded-xl overflow-hidden bg-slate-50/50">
                        <div class="px-4 py-2.5 bg-slate-100/80 border-b border-slate-200 flex items-center justify-between">
                            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-800 flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-brand-500"></span>
                                <?= $dayLabel ?>
                            </h3>
                            <span class="text-[11px] font-semibold text-slate-600">
                                <?= count($daySlots) ?> <?= count($daySlots) === 1 ? 'Period' : 'Periods' ?>
                            </span>
                        </div>

                        <div class="p-4">
                            <?php if (empty($daySlots)): ?>
                                <p class="text-xs text-slate-400 italic py-1">No lessons scheduled for <?= $dayLabel ?>.</p>
                            <?php else: ?>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                    <?php foreach ($daySlots as $slot): ?>
                                        <div class="p-4 bg-white rounded-lg border border-slate-200 shadow-sm flex flex-col justify-between hover:border-brand-300 transition">
                                            <div>
                                                <div class="flex items-center justify-between gap-2">
                                                    <span class="px-2 py-0.5 rounded text-[11px] font-bold bg-brand-50 text-brand-700 border border-brand-200">
                                                        <?= htmlspecialchars($slot->classSubject?->subjectCode ?: 'SUB') ?>
                                                    </span>
                                                    <?php if ($slot->room): ?>
                                                        <span class="px-2 py-0.5 rounded text-[11px] font-medium bg-purple-50 text-purple-700 border border-purple-200">
                                                            Room: <?= htmlspecialchars($slot->room) ?>
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                                <h4 class="text-sm font-bold text-slate-900 mt-2">
                                                    <?= htmlspecialchars($slot->classSubject?->subjectName ?: 'Subject') ?>
                                                </h4>
                                                <p class="text-xs text-slate-500 mt-0.5">
                                                    Teacher: <strong class="text-slate-700"><?= htmlspecialchars($slot->classSubject?->teacherName ?? 'Unassigned') ?></strong>
                                                </p>
                                            </div>

                                            <div class="mt-3 pt-2.5 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
                                                <span class="font-semibold text-brand-600 flex items-center gap-1">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                                    <?= htmlspecialchars($slot->getFormattedTimeRange()) ?>
                                                </span>
                                                <span><?= $slot->getDurationMinutes() ?> mins</span>
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
