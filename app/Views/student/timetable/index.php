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
                        <div class="relative">
                            <select name="term_id" id="term_id" onchange="this.form.submit()" 
                                    class="px-3.5 py-2 pr-8 text-xs font-bold rounded-xl border border-slate-300 bg-slate-50 text-slate-800 focus:bg-white focus:border-sky-500 focus:ring-sky-500 transition cursor-pointer appearance-none shadow-2xs">
                                <?php foreach ($terms as $t): ?>
                                    <option value="<?= (int)$t->id ?>" <?= $selectedTerm && (int)$selectedTerm->id === (int)$t->id ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($t->name) ?> <?= $t->isCurrent ? '★ Current' : '' ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-slate-400">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </div>
                        </div>
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

        $standardPeriods = $standardPeriods ?? \App\Utils\PeriodSchedule::getAllPeriods();
        $standardDays = $standardDays ?? \App\Utils\PeriodSchedule::getDays();

        $slotMatchesPeriod = function($slot, $period) {
            $slotStart = substr($slot->startTime, 0, 5);
            $slotEnd = substr($slot->endTime, 0, 5);
            if ($slotStart === $period['start_time']) {
                return true;
            }
            return ($slotStart < $period['end_time'] && $slotEnd > $period['start_time']);
        };
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

    <!-- Timetable Container with View Switcher -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6 space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between border-b border-slate-100 pb-4 gap-3">
            <div>
                <h2 class="text-base font-bold text-slate-900">Weekly Learning Schedule</h2>
                <p class="text-xs text-slate-500">Overview of all instructional periods for your class.</p>
            </div>

            <div class="flex items-center gap-3">
                <span class="text-xs font-bold text-slate-400 font-mono"><?= $totalSlots ?> Scheduled Periods</span>

                <!-- Segmented View Toggle Switch -->
                <div class="inline-flex rounded-xl bg-slate-100 p-1 border border-slate-200 shadow-2xs">
                    <button
                        type="button"
                        id="btn-view-matrix"
                        onclick="switchStudentTimetableView('matrix')"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold transition cursor-pointer bg-white text-slate-900 shadow-xs"
                    >
                        <svg class="w-3.5 h-3.5 text-sky-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                        </svg>
                        Table Matrix
                    </button>
                    <button
                        type="button"
                        id="btn-view-cards"
                        onclick="switchStudentTimetableView('cards')"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold transition cursor-pointer text-slate-600 hover:text-slate-900"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                        Day Cards
                    </button>
                </div>
            </div>
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

            <!-- VIEW 1: TABLE MATRIX VIEW -->
            <div id="student-matrix-view" class="space-y-4">
                <div class="overflow-x-auto rounded-xl border border-slate-200">
                    <table class="w-full border-collapse text-left text-xs">
                        <thead>
                            <tr class="bg-slate-50 text-slate-700 font-bold border-b border-slate-200">
                                <th class="p-3 w-28 text-center sticky left-0 bg-slate-50 z-10 border-r border-slate-200 uppercase tracking-wider text-[11px]">
                                    Day
                                </th>
                                <?php foreach ($standardPeriods as $period): ?>
                                    <th class="p-2.5 text-center min-w-[130px] border-r border-slate-200 last:border-r-0 <?= $period['type'] === 'break' ? 'bg-amber-50/70 text-amber-900 min-w-[70px] w-20' : '' ?>">
                                        <div class="font-bold text-xs"><?= htmlspecialchars($period['short_name']) ?></div>
                                        <div class="text-[10px] font-normal text-slate-500 mt-0.5">
                                            <?= htmlspecialchars($period['start_time']) ?>-<?= htmlspecialchars($period['end_time']) ?>
                                        </div>
                                    </th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200">
                            <?php foreach ($standardDays as $dayKey => $dayName): ?>
                                <?php $daySlots = $grid[$dayKey] ?? []; ?>
                                <tr class="hover:bg-slate-50/40 transition">
                                    <!-- Row Day Header -->
                                    <td class="p-3 font-bold text-slate-800 text-center sticky left-0 bg-white border-r border-slate-200 z-10 shadow-2xs">
                                        <span class="block text-xs uppercase tracking-wider"><?= substr($dayName, 0, 3) ?></span>
                                        <span class="block text-[10px] font-normal text-slate-400 mt-0.5"><?= $dayName ?></span>
                                    </td>

                                    <!-- Period Columns -->
                                    <?php foreach ($standardPeriods as $period): ?>
                                        <?php if ($period['type'] === 'break'): ?>
                                            <!-- Institutional Break Cell -->
                                            <td class="p-1.5 text-center bg-slate-50/80 border-r border-slate-200 text-slate-400 select-none">
                                                <span class="text-[9px] font-bold uppercase tracking-wider block">
                                                    <?= htmlspecialchars($period['short_name']) ?>
                                                </span>
                                            </td>
                                        <?php else: ?>
                                            <!-- Instructional Period Cell -->
                                            <?php
                                            $slotForPeriod = null;
                                            foreach ($daySlots as $slot) {
                                                if ($slotMatchesPeriod($slot, $period)) {
                                                    $slotForPeriod = $slot;
                                                    break;
                                                }
                                            }
                                            ?>
                                            <?php if ($slotForPeriod): ?>
                                                <?php
                                                    $subName = $slotForPeriod->subjectName ?: ($slotForPeriod->classSubject?->subjectName ?: 'Subject');
                                                    $subCode = $slotForPeriod->subjectCode ?: ($slotForPeriod->classSubject?->subjectCode ?: 'SUB');
                                                    $teacherName = $slotForPeriod->teacherName ?: ($slotForPeriod->classSubject?->teacherName ?: 'Teacher');
                                                ?>
                                                <td class="p-2 border-r border-slate-200 align-top bg-white">
                                                    <div class="p-2.5 rounded-xl border border-sky-200 bg-sky-50/60 hover:bg-sky-50 transition shadow-2xs flex flex-col justify-between h-full min-h-[96px]">
                                                        <div>
                                                            <div class="flex items-center justify-between gap-1 mb-1">
                                                                <span class="px-1.5 py-0.5 rounded text-[10px] font-mono font-bold bg-sky-700 text-white uppercase">
                                                                    <?= htmlspecialchars(substr($subCode, 0, 8)) ?>
                                                                </span>
                                                                <?php if ($slotForPeriod->room): ?>
                                                                    <span class="px-1.5 py-0.5 rounded text-[9px] font-semibold bg-purple-100 text-purple-700 truncate max-w-[55px]" title="Room: <?= htmlspecialchars($slotForPeriod->room) ?>">
                                                                        <?= htmlspecialchars($slotForPeriod->room) ?>
                                                                    </span>
                                                                <?php endif; ?>
                                                            </div>
                                                            <div class="font-bold text-slate-900 text-xs leading-tight line-clamp-1" title="<?= htmlspecialchars($subName) ?>">
                                                                <?= htmlspecialchars($subName) ?>
                                                            </div>
                                                            <div class="text-[10px] text-slate-600 mt-1 line-clamp-1 flex items-center gap-1" title="Teacher: <?= htmlspecialchars($teacherName) ?>">
                                                                <svg class="w-3 h-3 text-slate-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                                                </svg>
                                                                <?= htmlspecialchars($teacherName) ?>
                                                            </div>
                                                        </div>
                                                        <div class="mt-2 pt-1.5 border-t border-sky-200/60 flex items-center justify-between text-[10px] text-slate-400 font-mono">
                                                            <span><?= substr($slotForPeriod->startTime, 0, 5) ?>-<?= substr($slotForPeriod->endTime, 0, 5) ?></span>
                                                            <span><?= $slotForPeriod->getDurationMinutes() ?>m</span>
                                                        </div>
                                                    </div>
                                                </td>
                                            <?php else: ?>
                                                <!-- Free / Study Period Cell -->
                                                <td class="p-1.5 border-r border-slate-200 align-middle text-center bg-slate-50/20 text-slate-300">
                                                    <span class="text-[10px] font-semibold block text-slate-300">Free</span>
                                                </td>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- VIEW 2: DAY CARDS VIEW -->
            <div id="student-cards-view" class="space-y-6 hidden">
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

<script>
function switchStudentTimetableView(mode) {
    const matrixView = document.getElementById('student-matrix-view');
    const cardsView = document.getElementById('student-cards-view');
    const btnMatrix = document.getElementById('btn-view-matrix');
    const btnCards = document.getElementById('btn-view-cards');

    if (!matrixView || !cardsView) return;

    if (mode === 'cards') {
        matrixView.classList.add('hidden');
        cardsView.classList.remove('hidden');
        btnCards.className = 'inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold transition cursor-pointer bg-white text-slate-900 shadow-xs';
        btnMatrix.className = 'inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold transition cursor-pointer text-slate-600 hover:text-slate-900';
    } else {
        matrixView.classList.remove('hidden');
        cardsView.classList.add('hidden');
        btnMatrix.className = 'inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold transition cursor-pointer bg-white text-slate-900 shadow-xs';
        btnCards.className = 'inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold transition cursor-pointer text-slate-600 hover:text-slate-900';
    }
    localStorage.setItem('claret_student_timetable_view_mode', mode);
}

document.addEventListener('DOMContentLoaded', function() {
    const saved = localStorage.getItem('claret_student_timetable_view_mode');
    if (saved === 'cards') {
        switchStudentTimetableView('cards');
    }
});
</script>
