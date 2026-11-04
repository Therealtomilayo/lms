<div class="space-y-6">
    <!-- Header Card -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <!-- Breadcrumbs -->
                <nav class="flex items-center gap-2 text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">
                    <a href="/teacher/dashboard" class="text-slate-400 hover:text-emerald-600 transition">Teacher Portal</a>
                    <span class="text-slate-300">/</span>
                    <span class="text-slate-700">Teaching Timetable</span>
                </nav>
                <div class="flex flex-wrap items-center gap-3">
                    <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight leading-tight">
                        Teacher Weekly Teaching Schedule
                    </h1>
                </div>
                <p class="text-xs text-slate-500 mt-1">
                    Your assigned weekly teaching periods, classroom allocations, and scheduled lecture halls.
                </p>
            </div>

            <?php if (!empty($terms)): ?>
                <form method="GET" action="/teacher/timetable" class="flex items-center gap-2">
                    <label for="term_id" class="text-xs font-bold uppercase tracking-wider text-slate-500">Term:</label>
                    <div class="relative">
                        <select name="term_id" id="term_id" onchange="this.form.submit()" 
                                class="bg-slate-50 border border-slate-300 text-slate-900 text-xs font-bold rounded-xl px-3 py-2 pr-8 focus:ring-emerald-500 focus:border-emerald-500 transition cursor-pointer appearance-none">
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
    $allSlots = $scheduleData['slots'] ?? [];

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
            <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Teaching Schedule</p>
            <div class="flex items-baseline gap-2 mt-1">
                <h3 class="text-lg font-extrabold text-slate-900">Synchronized</h3>
            </div>
            <span class="text-[11px] font-medium text-slate-500 mt-1 block">
                Official institution roster
            </span>
        </div>
    </div>

    <!-- Timetable Container with View Switcher -->
    <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-xs space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-4 border-b border-slate-100 gap-3">
            <div>
                <h2 class="text-base font-bold text-slate-900">Weekly Schedule</h2>
                <p class="text-xs text-slate-500">Overview of all assigned subject periods across the week.</p>
            </div>

            <div class="flex items-center gap-3">
                <span class="px-3 py-1 bg-emerald-50 text-emerald-800 border border-emerald-200 rounded-full text-xs font-bold">
                    <?= $totalSlots ?> <?= $totalSlots === 1 ? 'Period' : 'Periods' ?> Total
                </span>

                <!-- Segmented View Toggle Switch -->
                <div class="inline-flex rounded-xl bg-slate-100 p-1 border border-slate-200 shadow-2xs">
                    <button
                        type="button"
                        id="btn-view-matrix"
                        onclick="switchTeacherTimetableView('matrix')"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold transition cursor-pointer bg-white text-slate-900 shadow-xs"
                    >
                        <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                        </svg>
                        Table Matrix
                    </button>
                    <button
                        type="button"
                        id="btn-view-cards"
                        onclick="switchTeacherTimetableView('cards')"
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
            <div class="text-center py-12 border-2 border-dashed border-slate-200 rounded-2xl">
                <svg class="mx-auto h-12 w-12 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                <h3 class="mt-2 text-sm font-bold text-slate-900">No teaching periods scheduled</h3>
                <p class="mt-1 text-xs text-slate-500">You do not have any teaching slots scheduled for this academic term.</p>
            </div>
        <?php else: ?>

            <!-- VIEW 1: TABLE MATRIX VIEW -->
            <div id="teacher-matrix-view" class="space-y-4">
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
                                                <td class="p-2 border-r border-slate-200 align-top bg-white">
                                                    <div class="p-2.5 rounded-xl border border-emerald-200 bg-emerald-50/60 hover:bg-emerald-50 transition shadow-2xs flex flex-col justify-between h-full min-h-[96px]">
                                                        <div>
                                                            <div class="flex items-center justify-between gap-1 mb-1">
                                                                <span class="px-1.5 py-0.5 rounded text-[10px] font-mono font-bold bg-emerald-700 text-white uppercase">
                                                                    <?= htmlspecialchars(substr($slotForPeriod->classSubject?->subjectCode ?: 'SUB', 0, 8)) ?>
                                                                </span>
                                                                <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-800">
                                                                    <?= htmlspecialchars($slotForPeriod->classSubject?->className ?? 'Class') ?>
                                                                </span>
                                                            </div>
                                                            <div class="font-bold text-slate-900 text-xs leading-tight line-clamp-1" title="<?= htmlspecialchars($slotForPeriod->classSubject?->subjectName ?: 'Subject') ?>">
                                                                <?= htmlspecialchars($slotForPeriod->classSubject?->subjectName ?: 'Subject') ?>
                                                            </div>
                                                            <?php if ($slotForPeriod->room): ?>
                                                                <div class="text-[10px] text-purple-700 font-semibold mt-1 flex items-center gap-1">
                                                                    <span>Room: <?= htmlspecialchars($slotForPeriod->room) ?></span>
                                                                </div>
                                                            <?php endif; ?>
                                                        </div>
                                                        <div class="mt-2 pt-1.5 border-t border-emerald-200/60 flex items-center justify-between text-[10px] text-slate-400 font-mono">
                                                            <span><?= substr($slotForPeriod->startTime, 0, 5) ?>-<?= substr($slotForPeriod->endTime, 0, 5) ?></span>
                                                            <span><?= $slotForPeriod->getDurationMinutes() ?>m</span>
                                                        </div>
                                                    </div>
                                                </td>
                                            <?php else: ?>
                                                <!-- Free / Unassigned Period Cell -->
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
            <div id="teacher-cards-view" class="space-y-4 hidden">
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

<script>
function switchTeacherTimetableView(mode) {
    const matrixView = document.getElementById('teacher-matrix-view');
    const cardsView = document.getElementById('teacher-cards-view');
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
    localStorage.setItem('claret_teacher_timetable_view_mode', mode);
}

document.addEventListener('DOMContentLoaded', function() {
    const saved = localStorage.getItem('claret_teacher_timetable_view_mode');
    if (saved === 'cards') {
        switchTeacherTimetableView('cards');
    }
});
</script>
