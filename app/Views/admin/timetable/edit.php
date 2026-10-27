<div class="space-y-6">
    <!-- Header & Navigation Card -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div>
                <!-- Breadcrumbs -->
                <nav class="flex items-center gap-2 text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">
                    <a href="/admin/timetable" class="text-brand-600 hover:text-brand-700 transition flex items-center gap-1">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        Timetable Overview
                    </a>
                    <span class="text-slate-300">/</span>
                    <span class="text-slate-700"><?= htmlspecialchars($class->name) ?></span>
                </nav>
                <div class="flex items-center gap-3 flex-wrap">
                    <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight leading-tight">
                        Weekly Timetable: <?= htmlspecialchars($class->name) ?>
                    </h1>
                    <?php if (!empty($class->sectionArm)): ?>
                        <?php $this->include('components/badge', [
                            'label' => 'Arm ' . $class->sectionArm,
                            'variant' => 'brand',
                            'class' => 'font-mono uppercase font-bold text-xs'
                        ]); ?>
                    <?php endif; ?>
                </div>
                <p class="text-xs text-slate-500 mt-1">
                    Manage instructional periods, subject allocations, assigned teachers, and rooms for <strong><?= htmlspecialchars($term->name) ?></strong>.
                </p>
            </div>

            <!-- Action Toolbar (Term Switcher + Quick Actions) -->
            <div class="flex items-center gap-2.5 flex-wrap">
                <!-- Term Switcher Form -->
                <form method="GET" action="/admin/timetable/<?= (int)$class->id ?>/edit" class="flex items-center gap-1.5">
                    <label for="term_id" class="text-xs font-semibold text-slate-600 uppercase tracking-wider whitespace-nowrap">
                        Term:
                    </label>
                    <div class="relative">
                        <select
                            name="term_id"
                            id="term_id"
                            onchange="this.form.submit()"
                            class="bg-slate-50 border border-slate-300 text-slate-900 text-xs font-semibold rounded-lg px-3 py-2 pr-8 focus:ring-2 focus:ring-brand-500 focus:border-brand-500 focus:bg-white transition-colors cursor-pointer appearance-none shadow-2xs"
                        >
                            <?php foreach ($terms as $t): ?>
                                <option value="<?= (int)$t->id ?>" <?= (int)$term->id === (int)$t->id ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($t->name) ?> <?= $t->isActive() ? '★ Active' : '' ?>
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

                <!-- Download CSV Template -->
                <a
                    href="/admin/timetable/<?= (int)$class->id ?>/template/download?term_id=<?= (int)$term->id ?>"
                    title="Download pre-populated CSV template with subjects for this class"
                    class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold transition shadow-2xs"
                >
                    <svg class="w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                    Template
                </a>

                <!-- Import CSV Button (Opens Preview & Mapping Wizard) -->
                <button
                    type="button"
                    onclick="openImportModal()"
                    class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold transition shadow-2xs cursor-pointer"
                >
                    <svg class="w-4 h-4 text-brand-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                    </svg>
                    Import CSV
                </button>

                <!-- Clone Schedule Button -->
                <button
                    type="button"
                    onclick="openCloneModal()"
                    class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold transition shadow-2xs cursor-pointer"
                >
                    <svg class="w-4 h-4 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2" />
                    </svg>
                    Clone Term
                </button>

                <!-- Add Slot Action (Opens Focused Add Slot Modal) -->
                <button
                    type="button"
                    onclick="openAddSlotModal()"
                    class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg bg-brand-600 hover:bg-brand-700 active:bg-brand-800 text-white text-xs font-bold transition shadow-xs cursor-pointer"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                    Add Slot
                </button>
            </div>
        </div>
    </div>

    <!-- Main Grid: Timetable Views (Matrix / Cards) + Add Slot Sticky Sidebar -->
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6 items-start">
        
        <!-- Schedule Display Area (2 cols on XL) -->
        <div class="xl:col-span-2 space-y-6">
            <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-xs">
                <!-- Card Header with View Switcher -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-4 border-b border-slate-200 gap-3">
                    <div>
                        <h2 class="text-base font-bold text-slate-900">Weekly Schedule</h2>
                        <p class="text-xs text-slate-500">All instructional periods scheduled for this cohort.</p>
                    </div>

                    <div class="flex items-center gap-3">
                        <?php 
                        $totalSlots = count($scheduleData['slots'] ?? []); 
                        $this->include('components/badge', [
                            'label' => $totalSlots . ' ' . ($totalSlots === 1 ? 'Period' : 'Periods'),
                            'variant' => $totalSlots > 0 ? 'success' : 'neutral',
                            'class' => 'font-semibold text-xs'
                        ]);
                        ?>

                        <!-- Segmented View Toggle Switch -->
                        <div class="inline-flex rounded-xl bg-slate-100 p-1 border border-slate-200 shadow-2xs">
                            <button
                                type="button"
                                id="btn-view-matrix"
                                onclick="switchTimetableView('matrix')"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold transition cursor-pointer bg-white text-slate-900 shadow-xs"
                            >
                                <svg class="w-3.5 h-3.5 text-brand-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                                </svg>
                                Table Matrix
                            </button>
                            <button
                                type="button"
                                id="btn-view-cards"
                                onclick="switchTimetableView('cards')"
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

                <?php
                $grid = $scheduleData['grid'] ?? [];
                $allSlots = $scheduleData['slots'] ?? [];
                $matchedSlotIds = [];

                // Helper to match a slot against a standard period
                $slotMatchesPeriod = function($slot, $period) {
                    $slotStart = substr($slot->startTime, 0, 5);
                    $slotEnd = substr($slot->endTime, 0, 5);
                    if ($slotStart === $period['start_time']) {
                        return true;
                    }
                    return ($slotStart < $period['end_time'] && $slotEnd > $period['start_time']);
                };
                ?>

                <!-- VIEW 1: TABLE MATRIX VIEW -->
                <div id="timetable-matrix-view" class="mt-5 space-y-4">
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
                                                        $matchedSlotIds[$slot->id] = true;
                                                        break;
                                                    }
                                                }
                                                ?>
                                                <?php if ($slotForPeriod): ?>
                                                    <td class="p-2 border-r border-slate-200 align-top bg-white">
                                                        <div class="p-2.5 rounded-xl border border-brand-200 bg-brand-50/60 hover:bg-brand-50 transition shadow-2xs flex flex-col justify-between h-full min-h-[96px]">
                                                            <div>
                                                                <div class="flex items-center justify-between gap-1 mb-1">
                                                                    <span class="px-1.5 py-0.5 rounded text-[10px] font-mono font-bold bg-brand-600 text-white uppercase">
                                                                        <?= htmlspecialchars(substr($slotForPeriod->classSubject?->subjectCode ?: 'SUB', 0, 8)) ?>
                                                                    </span>
                                                                    <?php if ($slotForPeriod->room): ?>
                                                                        <span class="px-1 py-0.5 rounded text-[9px] font-semibold bg-purple-100 text-purple-700 truncate max-w-[55px]" title="Room: <?= htmlspecialchars($slotForPeriod->room) ?>">
                                                                            <?= htmlspecialchars($slotForPeriod->room) ?>
                                                                        </span>
                                                                    <?php endif; ?>
                                                                </div>
                                                                <div class="font-bold text-slate-900 text-xs leading-tight line-clamp-1" title="<?= htmlspecialchars($slotForPeriod->classSubject?->subjectName ?: 'Subject') ?>">
                                                                    <?= htmlspecialchars($slotForPeriod->classSubject?->subjectName ?: 'Subject') ?>
                                                                </div>
                                                                <div class="text-[10px] text-slate-600 mt-1 line-clamp-1 flex items-center gap-1" title="Teacher: <?= htmlspecialchars($slotForPeriod->classSubject?->teacherName ?: 'No Teacher') ?>">
                                                                    <svg class="w-3 h-3 text-slate-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                                                    </svg>
                                                                    <?= htmlspecialchars($slotForPeriod->classSubject?->teacherName ?: 'No Teacher') ?>
                                                                </div>
                                                            </div>
                                                            <div class="mt-2 pt-1.5 border-t border-brand-200/60 flex items-center justify-between text-[10px]">
                                                                <span class="text-slate-400 font-mono text-[9px]">
                                                                    <?= substr($slotForPeriod->startTime, 0, 5) ?>-<?= substr($slotForPeriod->endTime, 0, 5) ?>
                                                                </span>
                                                                <form method="POST" action="/admin/timetable/<?= (int)$class->id ?>/slots/<?= (int)$slotForPeriod->id ?>/delete" onsubmit="return confirm('Remove <?= htmlspecialchars(addslashes($slotForPeriod->classSubject?->subjectName ?? 'this slot')) ?> from <?= $dayName ?>?');" class="inline">
                                                                    <?= csrf_field() ?>
                                                                    <input type="hidden" name="term_id" value="<?= (int)$term->id ?>">
                                                                    <button type="submit" class="text-rose-500 hover:text-rose-700 p-0.5 rounded hover:bg-rose-100/60 transition cursor-pointer" title="Remove slot">
                                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                                        </svg>
                                                                    </button>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </td>
                                                <?php else: ?>
                                                    <!-- Vacant Period Slot with Quick-Add Action -->
                                                    <td class="p-1.5 border-r border-slate-200 align-middle text-center bg-slate-50/20 hover:bg-brand-50/30 transition group">
                                                        <button
                                                            type="button"
                                                            onclick="quickAddSlot('<?= $dayKey ?>', '<?= $period['start_time'] ?>', '<?= $period['end_time'] ?>', <?= (int)$period['period_number'] ?>)"
                                                            class="w-full h-full min-h-[96px] rounded-xl border border-dashed border-slate-200 group-hover:border-brand-400 group-hover:bg-brand-50/50 flex flex-col items-center justify-center text-slate-300 group-hover:text-brand-600 transition p-2 cursor-pointer"
                                                            title="Allocate <?= $dayName ?> <?= htmlspecialchars($period['name']) ?> (<?= htmlspecialchars($period['start_time']) ?>-<?= htmlspecialchars($period['end_time']) ?>)"
                                                        >
                                                            <svg class="w-4 h-4 mb-1 opacity-60 group-hover:opacity-100 group-hover:scale-110 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                                                            </svg>
                                                            <span class="text-[10px] font-semibold opacity-0 group-hover:opacity-100 transition-opacity">Add</span>
                                                        </button>
                                                    </td>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Off-Schedule Slots Warning -->
                    <?php
                    $unalignedSlots = array_filter($allSlots, fn($s) => !isset($matchedSlotIds[$s->id]));
                    ?>
                    <?php if (!empty($unalignedSlots)): ?>
                        <div class="p-4 rounded-xl border border-amber-200 bg-amber-50/80 space-y-2">
                            <h4 class="text-xs font-bold text-amber-900 flex items-center gap-1.5">
                                <svg class="w-4 h-4 text-amber-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                </svg>
                                Non-Standard Time Slots Detected (<?= count($unalignedSlots) ?>)
                            </h4>
                            <p class="text-[11px] text-amber-800">
                                The following slot(s) were scheduled outside canonical bell hours and are shown below:
                            </p>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-2 mt-2">
                                <?php foreach ($unalignedSlots as $uSlot): ?>
                                    <div class="bg-white p-2.5 rounded-lg border border-amber-200 flex items-center justify-between text-xs">
                                        <div>
                                            <span class="font-bold text-slate-800"><?= htmlspecialchars($uSlot->classSubject?->subjectName) ?></span>
                                            <span class="text-slate-500 font-mono text-[11px] ml-1">(<?= strtoupper($uSlot->dayOfWeek) ?> <?= $uSlot->getFormattedTimeRange() ?>)</span>
                                        </div>
                                        <form method="POST" action="/admin/timetable/<?= (int)$class->id ?>/slots/<?= (int)$uSlot->id ?>/delete" onsubmit="return confirm('Remove slot?');">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="term_id" value="<?= (int)$term->id ?>">
                                            <button type="submit" class="text-rose-600 text-xs font-semibold hover:underline">Remove</button>
                                        </form>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- VIEW 2: DAY CARDS VIEW (Toggleable) -->
                <div id="timetable-cards-view" class="mt-5 space-y-5 hidden">
                    <?php
                    $allDays = [
                        'mon' => 'Monday',
                        'tue' => 'Tuesday',
                        'wed' => 'Wednesday',
                        'thu' => 'Thursday',
                        'fri' => 'Friday',
                        'sat' => 'Saturday',
                        'sun' => 'Sunday',
                    ];
                    ?>

                    <?php foreach ($allDays as $dayKey => $dayLabel): ?>
                        <?php 
                        $daySlots = $grid[$dayKey] ?? []; 
                        if (empty($daySlots) && ($dayKey === 'sat' || $dayKey === 'sun')) {
                            continue;
                        }
                        ?>
                        <div class="border border-slate-200 rounded-xl overflow-hidden bg-slate-50/50">
                            <!-- Day Header -->
                            <div class="px-4 py-2.5 bg-slate-100/80 border-b border-slate-200 flex items-center justify-between">
                                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-800 flex items-center gap-2">
                                    <span class="w-2.5 h-2.5 rounded-full <?= !empty($daySlots) ? 'bg-brand-500' : 'bg-slate-300' ?>"></span>
                                    <?= $dayLabel ?>
                                </h3>
                                <span class="text-[11px] font-semibold text-slate-500">
                                    <?= count($daySlots) ?> <?= count($daySlots) === 1 ? 'Period' : 'Periods' ?>
                                </span>
                            </div>

                            <!-- Day Slots -->
                            <div class="p-4">
                                <?php if (empty($daySlots)): ?>
                                    <p class="text-xs text-slate-400 italic py-1.5 flex items-center gap-1.5">
                                        <svg class="w-4 h-4 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        No instructional periods scheduled for <?= $dayLabel ?>.
                                    </p>
                                <?php else: ?>
                                    <div class="space-y-3">
                                        <?php foreach ($daySlots as $slot): ?>
                                            <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-3 hover:border-slate-300 hover:shadow-sm transition">
                                                <div class="flex items-start gap-3">
                                                    <!-- Subject Code Badge Box -->
                                                    <div class="w-11 h-11 rounded-xl bg-brand-50 text-brand-700 flex flex-col items-center justify-center font-mono font-bold text-xs flex-shrink-0 border border-brand-100">
                                                        <span class="text-[10px] text-brand-500 uppercase leading-none">SUB</span>
                                                        <span class="text-xs mt-0.5 leading-none"><?= htmlspecialchars(substr($slot->classSubject?->subjectCode ?: 'SUB', 0, 8)) ?></span>
                                                    </div>
                                                    <div>
                                                        <div class="flex items-center gap-2 flex-wrap">
                                                            <h4 class="text-sm font-bold text-slate-900"><?= htmlspecialchars($slot->classSubject?->subjectName ?: 'Subject') ?></h4>
                                                            <?php if ($slot->room): ?>
                                                                <span class="px-2 py-0.5 bg-purple-50 text-purple-700 border border-purple-200 rounded text-[11px] font-medium">
                                                                    Room: <?= htmlspecialchars($slot->room) ?>
                                                                </span>
                                                            <?php endif; ?>
                                                        </div>
                                                        <div class="text-xs text-slate-500 mt-1.5 flex flex-wrap items-center gap-x-3 gap-y-1">
                                                            <span class="font-semibold text-brand-600 flex items-center gap-1">
                                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                                </svg>
                                                                <?= htmlspecialchars($slot->getFormattedTimeRange()) ?> (<?= $slot->getDurationMinutes() ?>m)
                                                            </span>
                                                            <span class="text-slate-300">•</span>
                                                            <span>Teacher: <strong class="text-slate-800 font-semibold"><?= htmlspecialchars($slot->classSubject?->teacherName ?: 'No Teacher Assigned') ?></strong></span>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Action Form (Delete Slot) -->
                                                <div class="flex items-center gap-2 self-end sm:self-center">
                                                    <form method="POST" action="/admin/timetable/<?= (int)$class->id ?>/slots/<?= (int)$slot->id ?>/delete" onsubmit="return confirm('Are you sure you want to remove this timetable slot?');">
                                                        <?= csrf_field() ?>
                                                        <input type="hidden" name="term_id" value="<?= (int)$term->id ?>">
                                                        <button
                                                            type="submit"
                                                            class="inline-flex items-center gap-1 text-xs text-rose-600 hover:text-rose-800 hover:bg-rose-50 px-3 py-1.5 rounded-lg border border-rose-200 transition font-semibold cursor-pointer"
                                                        >
                                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                            </svg>
                                                            Remove
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Add Slot Form Card (Sticky on XL) -->
        <div id="add-slot-card" class="bg-white p-6 rounded-2xl border border-slate-200 shadow-xs space-y-4 sticky top-6">
            <div class="border-b border-slate-200 pb-3">
                <h2 class="text-base font-bold text-slate-900">Add Schedule Slot</h2>
                <p class="text-xs text-slate-500">Allocate an instructional period with conflict detection.</p>
            </div>

            <!-- Quick Period Preset Buttons -->
            <div>
                <div class="flex items-center justify-between mb-1.5">
                    <label class="text-[11px] font-bold text-slate-700 uppercase tracking-wider">
                        Period Presets
                    </label>
                    <span class="text-[10px] text-slate-400">Click to fill times</span>
                </div>
                <div class="grid grid-cols-4 gap-1.5">
                    <?php foreach ($instructionalPeriods as $p): ?>
                        <button
                            type="button"
                            id="preset-p<?= (int)$p['period_number'] ?>"
                            onclick="applyPeriodPreset('<?= $p['start_time'] ?>', '<?= $p['end_time'] ?>', <?= (int)$p['period_number'] ?>)"
                            class="preset-btn px-2 py-1.5 rounded-lg border border-slate-200 bg-slate-50 hover:bg-brand-50 hover:border-brand-300 text-slate-700 hover:text-brand-700 text-xs font-bold transition text-center cursor-pointer"
                            title="<?= htmlspecialchars($p['name']) ?> (<?= htmlspecialchars($p['start_time']) ?>-<?= htmlspecialchars($p['end_time']) ?>)"
                        >
                            <?= htmlspecialchars($p['short_name']) ?>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>

            <form method="POST" action="/admin/timetable/<?= (int)$class->id ?>/slots" class="space-y-4">
                <?= csrf_field() ?>
                <input type="hidden" name="term_id" value="<?= (int)$term->id ?>">

                <!-- Subject & Teacher Selection -->
                <div>
                    <label for="class_subject_id" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                        Class Subject & Teacher <span class="text-rose-500">*</span>
                    </label>
                    <select
                        name="class_subject_id"
                        id="class_subject_id"
                        required
                        class="w-full bg-slate-50 border border-slate-300 text-slate-900 text-xs font-semibold rounded-lg px-3 py-2.5 focus:ring-2 focus:ring-brand-500 focus:border-brand-500 focus:bg-white transition-colors"
                    >
                        <option value="">-- Select Subject & Teacher --</option>
                        <?php foreach ($classSubjects as $cs): ?>
                            <option value="<?= (int)$cs->id ?>">
                                <?= htmlspecialchars($cs->subjectName) ?> (<?= htmlspecialchars($cs->subjectCode) ?>) — <?= htmlspecialchars($cs->teacherName ?: 'No Teacher Assigned') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Day of Week -->
                <div>
                    <label for="day_of_week" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                        Day of Week <span class="text-rose-500">*</span>
                    </label>
                    <select
                        name="day_of_week"
                        id="day_of_week"
                        required
                        class="w-full bg-slate-50 border border-slate-300 text-slate-900 text-xs font-semibold rounded-lg px-3 py-2.5 focus:ring-2 focus:ring-brand-500 focus:border-brand-500 focus:bg-white transition-colors"
                    >
                        <option value="mon">Monday</option>
                        <option value="tue">Tuesday</option>
                        <option value="wed">Wednesday</option>
                        <option value="thu">Thursday</option>
                        <option value="fri">Friday</option>
                        <option value="sat">Saturday</option>
                        <option value="sun">Sunday</option>
                    </select>
                </div>

                <!-- Time Interval -->
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label for="start_time" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                            Start Time <span class="text-rose-500">*</span>
                        </label>
                        <input
                            type="time"
                            name="start_time"
                            id="start_time"
                            value="08:00"
                            required
                            class="w-full bg-slate-50 border border-slate-300 text-slate-900 text-xs font-semibold rounded-lg px-3 py-2 focus:ring-2 focus:ring-brand-500 focus:border-brand-500 focus:bg-white transition-colors"
                        >
                    </div>
                    <div>
                        <label for="end_time" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                            End Time <span class="text-rose-500">*</span>
                        </label>
                        <input
                            type="time"
                            name="end_time"
                            id="end_time"
                            value="08:45"
                            required
                            class="w-full bg-slate-50 border border-slate-300 text-slate-900 text-xs font-semibold rounded-lg px-3 py-2 focus:ring-2 focus:ring-brand-500 focus:border-brand-500 focus:bg-white transition-colors"
                        >
                    </div>
                </div>

                <!-- Room / Venue -->
                <div>
                    <label for="room" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                        Room / Venue <span class="text-slate-400 font-normal lowercase">(optional)</span>
                    </label>
                    <input
                        type="text"
                        name="room"
                        id="room"
                        placeholder="e.g. Science Lab, Room 101"
                        maxlength="50"
                        class="w-full bg-slate-50 border border-slate-300 text-slate-900 text-xs font-medium rounded-lg px-3 py-2 focus:ring-2 focus:ring-brand-500 focus:border-brand-500 focus:bg-white transition-colors"
                    >
                </div>

                <!-- Conflict Detection Guidance Box -->
                <div class="p-3.5 bg-sky-50 border border-sky-200 rounded-xl text-xs text-sky-900 space-y-1">
                    <p class="font-bold flex items-center gap-1.5 text-sky-800">
                        <svg class="w-4 h-4 text-sky-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                        </svg>
                        Automated Conflict Detection
                    </p>
                    <p class="text-[11px] text-sky-700 leading-relaxed">
                        Prevents teacher double-booking, class overlapping, and venue collisions across all cohorts.
                    </p>
                </div>

                <button
                    type="submit"
                    class="w-full py-2.5 bg-brand-600 hover:bg-brand-700 active:bg-brand-800 text-white rounded-lg text-xs font-bold uppercase tracking-wider transition shadow-sm flex items-center justify-center gap-1.5 cursor-pointer"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                    Save Period to Timetable
                </button>
            </form>
        </div>
    </div>
</div>

<!-- ========================================== -->
<!-- MODAL: ADD TIMETABLE SLOT (POPUP DIALOG)   -->
<!-- ========================================== -->
<div id="addSlotModal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-slate-900/50 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-xl border border-slate-200 space-y-4">
        <div class="flex items-center justify-between border-b border-slate-200 pb-3">
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-lg bg-brand-100 text-brand-700 flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-base font-bold text-slate-900">Add Timetable Slot</h3>
                    <p class="text-[11px] text-slate-500"><?= htmlspecialchars($class->name) ?> • <?= htmlspecialchars($term->name) ?></p>
                </div>
            </div>
            <button type="button" onclick="closeAddSlotModal()" class="text-slate-400 hover:text-slate-600 p-1 rounded-lg cursor-pointer">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <form method="POST" action="/admin/timetable/<?= (int)$class->id ?>/slots" class="space-y-4">
            <?= csrf_field() ?>
            <input type="hidden" name="term_id" value="<?= (int)$term->id ?>">

            <!-- Quick Period Presets in Modal -->
            <div>
                <div class="flex items-center justify-between mb-1.5">
                    <label class="text-[11px] font-bold text-slate-700 uppercase tracking-wider">
                        Period Presets
                    </label>
                    <span class="text-[10px] text-slate-400">Click to fill times</span>
                </div>
                <div class="grid grid-cols-4 gap-1.5">
                    <?php foreach ($instructionalPeriods as $p): ?>
                        <button
                            type="button"
                            id="modal-preset-p<?= (int)$p['period_number'] ?>"
                            onclick="applyModalPeriodPreset('<?= $p['start_time'] ?>', '<?= $p['end_time'] ?>', <?= (int)$p['period_number'] ?>)"
                            class="modal-preset-btn px-2 py-1.5 rounded-lg border border-slate-200 bg-slate-50 hover:bg-brand-50 hover:border-brand-300 text-slate-700 hover:text-brand-700 text-xs font-bold transition text-center cursor-pointer"
                        >
                            <?= htmlspecialchars($p['short_name']) ?>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Subject & Teacher Selection -->
            <div>
                <label for="modal_class_subject_id" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                    Class Subject & Teacher <span class="text-rose-500">*</span>
                </label>
                <select
                    name="class_subject_id"
                    id="modal_class_subject_id"
                    required
                    class="w-full bg-slate-50 border border-slate-300 text-slate-900 text-xs font-semibold rounded-lg px-3 py-2.5 focus:ring-2 focus:ring-brand-500 focus:border-brand-500 focus:bg-white transition-colors"
                >
                    <option value="">-- Select Subject & Teacher --</option>
                    <?php foreach ($classSubjects as $cs): ?>
                        <option value="<?= (int)$cs->id ?>">
                            <?= htmlspecialchars($cs->subjectName) ?> (<?= htmlspecialchars($cs->subjectCode) ?>) — <?= htmlspecialchars($cs->teacherName ?: 'No Teacher Assigned') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Day of Week -->
            <div>
                <label for="modal_day_of_week" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                    Day of Week <span class="text-rose-500">*</span>
                </label>
                <select
                    name="day_of_week"
                    id="modal_day_of_week"
                    required
                    class="w-full bg-slate-50 border border-slate-300 text-slate-900 text-xs font-semibold rounded-lg px-3 py-2.5 focus:ring-2 focus:ring-brand-500 focus:border-brand-500 focus:bg-white transition-colors"
                >
                    <option value="mon">Monday</option>
                    <option value="tue">Tuesday</option>
                    <option value="wed">Wednesday</option>
                    <option value="thu">Thursday</option>
                    <option value="fri">Friday</option>
                    <option value="sat">Saturday</option>
                    <option value="sun">Sunday</option>
                </select>
            </div>

            <!-- Time Interval -->
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label for="modal_start_time" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                        Start Time <span class="text-rose-500">*</span>
                    </label>
                    <input
                        type="time"
                        name="start_time"
                        id="modal_start_time"
                        value="08:00"
                        required
                        class="w-full bg-slate-50 border border-slate-300 text-slate-900 text-xs font-semibold rounded-lg px-3 py-2 focus:ring-2 focus:ring-brand-500 focus:border-brand-500 focus:bg-white transition-colors"
                    >
                </div>
                <div>
                    <label for="modal_end_time" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                        End Time <span class="text-rose-500">*</span>
                    </label>
                    <input
                        type="time"
                        name="end_time"
                        id="modal_end_time"
                        value="08:45"
                        required
                        class="w-full bg-slate-50 border border-slate-300 text-slate-900 text-xs font-semibold rounded-lg px-3 py-2 focus:ring-2 focus:ring-brand-500 focus:border-brand-500 focus:bg-white transition-colors"
                    >
                </div>
            </div>

            <!-- Room / Venue -->
            <div>
                <label for="modal_room" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                    Room / Venue <span class="text-slate-400 font-normal lowercase">(optional)</span>
                </label>
                <input
                    type="text"
                    name="room"
                    id="modal_room"
                    placeholder="e.g. Science Lab, Room 101"
                    maxlength="50"
                    class="w-full bg-slate-50 border border-slate-300 text-slate-900 text-xs font-medium rounded-lg px-3 py-2 focus:ring-2 focus:ring-brand-500 focus:border-brand-500 focus:bg-white transition-colors"
                >
            </div>

            <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-slate-200">
                <button
                    type="button"
                    onclick="closeAddSlotModal()"
                    class="px-4 py-2 border border-slate-200 hover:bg-slate-50 text-slate-700 text-xs font-bold rounded-lg transition cursor-pointer"
                >
                    Cancel
                </button>
                <button
                    type="submit"
                    class="px-4 py-2.5 bg-brand-600 hover:bg-brand-700 active:bg-brand-800 text-white rounded-lg text-xs font-bold uppercase tracking-wider transition shadow-sm flex items-center justify-center gap-1.5 cursor-pointer"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                    Save Period to Timetable
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ========================================== -->
<!-- MODAL: CLONE TIMETABLE FROM ANOTHER TERM   -->
<!-- ========================================== -->
<div id="cloneScheduleModal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-slate-900/50 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-xl border border-slate-200 space-y-4">
        <div class="flex items-center justify-between border-b border-slate-200 pb-3">
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-lg bg-purple-100 text-purple-700 flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2" />
                    </svg>
                </div>
                <h3 class="text-base font-bold text-slate-900">Clone Schedule from Another Term</h3>
            </div>
            <button type="button" onclick="closeCloneModal()" class="text-slate-400 hover:text-slate-600 p-1 rounded-lg cursor-pointer">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <?php
        $sameSessionTerms = array_filter($terms, fn($t) => (int)$t->sessionId === (int)$term->sessionId && (int)$t->id !== (int)$term->id);
        ?>

        <?php if (empty($sameSessionTerms)): ?>
            <div class="p-4 rounded-xl bg-amber-50 border border-amber-200 text-xs text-amber-900 space-y-2">
                <p class="font-bold">No Alternate Term Found in Current Session</p>
                <p class="text-[11px] text-amber-700 leading-relaxed">
                    Cloning timetable schedules is restricted to terms within the same academic session to guarantee class subject invariants. Please create or configure another term in this session first.
                </p>
            </div>
            <div class="flex justify-end pt-2">
                <button type="button" onclick="closeCloneModal()" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-lg cursor-pointer">
                    Close
                </button>
            </div>
        <?php else: ?>
            <form method="POST" action="/admin/timetable/<?= (int)$class->id ?>/clone" class="space-y-4">
                <?= csrf_field() ?>
                <input type="hidden" name="target_term_id" value="<?= (int)$term->id ?>">

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                        Target Cohort & Term
                    </label>
                    <div class="p-3 bg-slate-50 border border-slate-200 rounded-lg text-xs font-semibold text-slate-800">
                        <?= htmlspecialchars($class->name) ?> — <span class="text-brand-600"><?= htmlspecialchars($term->name) ?></span>
                    </div>
                </div>

                <div>
                    <label for="source_term_id" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                        Source Term (to copy from) <span class="text-rose-500">*</span>
                    </label>
                    <select
                        name="source_term_id"
                        id="source_term_id"
                        required
                        class="w-full bg-slate-50 border border-slate-300 text-slate-900 text-xs font-semibold rounded-lg px-3 py-2.5 focus:ring-2 focus:ring-brand-500 focus:border-brand-500 focus:bg-white transition-colors"
                    >
                        <option value="">-- Select Source Term --</option>
                        <?php foreach ($sameSessionTerms as $st): ?>
                            <option value="<?= (int)$st->id ?>">
                                <?= htmlspecialchars($st->name) ?> <?= $st->isActive() ? '★ Active' : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="p-3.5 bg-slate-50 border border-slate-200 rounded-xl space-y-2">
                    <label class="flex items-start gap-2.5 cursor-pointer">
                        <input
                            type="checkbox"
                            name="overwrite_existing"
                            value="1"
                            class="mt-0.5 rounded border-slate-300 text-brand-600 focus:ring-brand-500"
                        >
                        <span class="text-xs text-slate-700 font-medium">
                            <strong>Overwrite existing slots</strong> in <?= htmlspecialchars($term->name) ?> for this class cohort before copying.
                        </span>
                    </label>
                    <p class="text-[11px] text-slate-500 pl-6 leading-relaxed">
                        If unchecked, existing slots will be preserved, and only non-conflicting periods from the source term will be imported.
                    </p>
                </div>

                <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-slate-200">
                    <button
                        type="button"
                        onclick="closeCloneModal()"
                        class="px-4 py-2 border border-slate-200 hover:bg-slate-50 text-slate-700 text-xs font-bold rounded-lg transition cursor-pointer"
                    >
                        Cancel
                    </button>
                    <button
                        type="submit"
                        class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white text-xs font-bold rounded-lg transition shadow-xs cursor-pointer flex items-center gap-1.5"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2" />
                        </svg>
                        Clone Schedule Now
                    </button>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>

<!-- ====================================================================== -->
<!-- MODAL: BULK CSV IMPORT WITH LIVE PREVIEW & COLUMN MAPPING WIZARD       -->
<!-- ====================================================================== -->
<div id="importCsvModal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-slate-900/50 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-3xl w-full p-6 shadow-xl border border-slate-200 space-y-4 max-h-[92vh] flex flex-col">
        <!-- Header -->
        <div class="flex items-center justify-between border-b border-slate-200 pb-3">
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-lg bg-brand-100 text-brand-700 flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-base font-bold text-slate-900">Import Timetable Spreadsheet</h3>
                    <p class="text-[11px] text-slate-500">Preview, map columns, and verify before importing into <?= htmlspecialchars($class->name) ?></p>
                </div>
            </div>
            <button type="button" onclick="closeImportModal()" class="text-slate-400 hover:text-slate-600 p-1 rounded-lg cursor-pointer">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <!-- Form with 2 steps -->
        <form id="importForm" method="POST" action="/admin/timetable/<?= (int)$class->id ?>/import" enctype="multipart/form-data" class="flex-1 overflow-y-auto space-y-4">
            <?= csrf_field() ?>
            <input type="hidden" name="term_id" value="<?= (int)$term->id ?>">
            <input type="hidden" name="csv_content" id="mapped_csv_content">
            <input type="hidden" name="map_class" id="input_map_class">
            <input type="hidden" name="map_day" id="input_map_day">
            <input type="hidden" name="map_period" id="input_map_period">
            <input type="hidden" name="map_subject" id="input_map_subject">
            <input type="hidden" name="map_room" id="input_map_room">

            <!-- STEP 1: CHOOSE CSV FILE -->
            <div id="import-step-1" class="space-y-4">
                <div class="p-3.5 bg-slate-50 border border-slate-200 rounded-xl space-y-2 text-xs">
                    <div class="flex items-center justify-between">
                        <span class="font-bold text-slate-800 uppercase tracking-wider text-[11px]">CSV Structure Specification</span>
                        <a
                            href="/admin/timetable/<?= (int)$class->id ?>/template/download?term_id=<?= (int)$term->id ?>"
                            class="text-brand-600 hover:text-brand-800 font-bold underline flex items-center gap-1"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                            </svg>
                            Download Sample Template
                        </a>
                    </div>
                    <p class="text-slate-600 text-[11px] leading-relaxed">
                        Upload your timetable CSV spreadsheet. In the next step, you can match your spreadsheet columns and preview the exact slots before saving.
                    </p>
                </div>

                <div class="border-2 border-dashed border-slate-300 rounded-2xl p-8 text-center hover:border-brand-400 hover:bg-brand-50/20 transition cursor-pointer">
                    <input
                        type="file"
                        name="csv_file"
                        id="csv_file_input"
                        accept=".csv,text/csv"
                        onchange="handleImportFileSelect(this)"
                        class="hidden"
                    >
                    <label for="csv_file_input" class="cursor-pointer flex flex-col items-center">
                        <div class="w-14 h-14 rounded-full bg-brand-50 text-brand-600 flex items-center justify-center mb-3">
                            <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                            </svg>
                        </div>
                        <span class="text-sm font-bold text-slate-800">Click to choose CSV file</span>
                        <span class="text-xs text-slate-500 mt-1">Supports standard CSV spreadsheets exported from Excel or Sheets</span>
                    </label>
                </div>
            </div>

            <!-- STEP 2: PREVIEW & COLUMN MAPPING -->
            <div id="import-step-2" class="space-y-4 hidden">
                <!-- File Info Badge & Reset -->
                <div class="flex items-center justify-between p-3 bg-slate-50 border border-slate-200 rounded-xl text-xs">
                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                        <span class="font-bold text-slate-800" id="preview_filename">timetable.csv</span>
                        <span class="text-slate-400 font-mono text-[11px]" id="preview_total_rows">(0 rows)</span>
                    </div>
                    <button type="button" onclick="resetImportStep()" class="text-brand-600 hover:underline font-bold text-xs cursor-pointer flex items-center gap-1">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        Choose Different File
                    </button>
                </div>

                <!-- Column Mapping Selector Grid -->
                <div class="p-3.5 bg-brand-50/40 border border-brand-200 rounded-xl space-y-2">
                    <div class="flex items-center justify-between">
                        <h4 class="text-xs font-bold text-brand-900 uppercase tracking-wider flex items-center gap-1.5">
                            <svg class="w-4 h-4 text-brand-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                            </svg>
                            Match Spreadsheet Columns
                        </h4>
                        <span class="text-[10px] text-brand-700">Change any dropdown to re-map</span>
                    </div>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-2.5 pt-1">
                        <div>
                            <label class="block text-[10px] font-bold text-slate-600 uppercase mb-1">Day Column <span class="text-rose-500">*</span></label>
                            <select id="map_select_day" onchange="renderImportPreview()" class="w-full bg-white border border-slate-300 text-xs rounded-lg p-2 font-medium">
                            </select>
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-600 uppercase mb-1">Period Column <span class="text-rose-500">*</span></label>
                            <select id="map_select_period" onchange="renderImportPreview()" class="w-full bg-white border border-slate-300 text-xs rounded-lg p-2 font-medium">
                            </select>
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-600 uppercase mb-1">Subject Column <span class="text-rose-500">*</span></label>
                            <select id="map_select_subject" onchange="renderImportPreview()" class="w-full bg-white border border-slate-300 text-xs rounded-lg p-2 font-medium">
                            </select>
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-600 uppercase mb-1">Room Column</label>
                            <select id="map_select_room" onchange="renderImportPreview()" class="w-full bg-white border border-slate-300 text-xs rounded-lg p-2 font-medium">
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Preview Table Container -->
                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <span class="text-xs font-bold text-slate-800 uppercase tracking-wider text-[11px]">Preview & Verification</span>
                        <div id="preview_stats" class="text-xs font-bold"></div>
                    </div>
                    <div class="border border-slate-200 rounded-xl overflow-x-auto max-h-56">
                        <table class="w-full text-left text-xs border-collapse">
                            <thead class="bg-slate-50 text-slate-700 font-bold border-b border-slate-200 sticky top-0">
                                <tr>
                                    <th class="p-2 text-center w-10">#</th>
                                    <th class="p-2">Day</th>
                                    <th class="p-2">Period / Time</th>
                                    <th class="p-2">Subject & Assigned Teacher</th>
                                    <th class="p-2">Room</th>
                                    <th class="p-2 text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody id="preview_table_body" class="divide-y divide-slate-200 font-medium">
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Overwrite Checkbox -->
                <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl">
                    <label class="flex items-center gap-2 cursor-pointer text-xs text-slate-700 font-medium">
                        <input type="checkbox" name="overwrite_existing" value="1" class="rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                        <span><strong>Overwrite existing timetable slots</strong> for <?= htmlspecialchars($class->name) ?> in this term before importing.</span>
                    </label>
                </div>
            </div>

            <!-- Footer Buttons -->
            <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-slate-200">
                <button
                    type="button"
                    onclick="closeImportModal()"
                    class="px-4 py-2 border border-slate-200 hover:bg-slate-50 text-slate-700 text-xs font-bold rounded-lg transition cursor-pointer"
                >
                    Cancel
                </button>
                <button
                    type="submit"
                    id="btn_confirm_import"
                    disabled
                    class="px-4 py-2 bg-brand-600 hover:bg-brand-700 disabled:opacity-50 disabled:cursor-not-allowed text-white text-xs font-bold rounded-lg transition shadow-xs cursor-pointer flex items-center gap-1.5"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                    </svg>
                    Confirm & Import Slots
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Available curriculum subjects for this class (for client-side preview matching)
window.CLASS_SUBJECTS = <?= json_encode(array_map(fn($cs) => [
    'id' => $cs->id,
    'code' => $cs->subjectCode,
    'name' => $cs->subjectName,
    'teacher' => $cs->teacherName ?: 'No Teacher Assigned',
], $classSubjects)) ?>;

// Canonical period times mapping
window.PERIOD_SCHEDULE = {
    1: { name: 'Period 1', time: '08:00 – 08:45' },
    2: { name: 'Period 2', time: '08:45 – 09:30' },
    3: { name: 'Period 3', time: '09:50 – 10:35' },
    4: { name: 'Period 4', time: '10:35 – 11:20' },
    5: { name: 'Period 5', time: '12:05 – 12:50' },
    6: { name: 'Period 6', time: '12:50 – 13:35' },
    7: { name: 'Period 7', time: '13:35 – 14:20' },
    8: { name: 'Period 8', time: '14:20 – 15:05' }
};

// ==========================================
// VIEW SWITCHER (Table Matrix vs Day Cards)
// ==========================================
function switchTimetableView(mode) {
    const matrixView = document.getElementById('timetable-matrix-view');
    const cardsView = document.getElementById('timetable-cards-view');
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
    localStorage.setItem('claret_timetable_view_mode', mode);
}

// Restore saved preference
document.addEventListener('DOMContentLoaded', function() {
    const saved = localStorage.getItem('claret_timetable_view_mode');
    if (saved === 'cards') {
        switchTimetableView('cards');
    }
});

// ==========================================
// QUICK-ADD & PERIOD PRESETS
// ==========================================
function applyPeriodPreset(startTime, endTime, periodNumber) {
    const startInput = document.getElementById('start_time');
    const endInput = document.getElementById('end_time');

    if (startInput) startInput.value = startTime;
    if (endInput) endInput.value = endTime;

    // Reset button states
    document.querySelectorAll('.preset-btn').forEach(function(btn) {
        btn.classList.remove('bg-brand-600', 'text-white', 'border-brand-600');
        btn.classList.add('bg-slate-50', 'text-slate-700', 'border-slate-200');
    });

    const activeBtn = document.getElementById('preset-p' + periodNumber);
    if (activeBtn) {
        activeBtn.classList.remove('bg-slate-50', 'text-slate-700', 'border-slate-200');
        activeBtn.classList.add('bg-brand-600', 'text-white', 'border-brand-600');
    }
}

function applyModalPeriodPreset(startTime, endTime, periodNumber) {
    const startInput = document.getElementById('modal_start_time');
    const endInput = document.getElementById('modal_end_time');

    if (startInput) startInput.value = startTime;
    if (endInput) endInput.value = endTime;

    document.querySelectorAll('.modal-preset-btn').forEach(function(btn) {
        btn.classList.remove('bg-brand-600', 'text-white', 'border-brand-600');
        btn.classList.add('bg-slate-50', 'text-slate-700', 'border-slate-200');
    });

    const activeBtn = document.getElementById('modal-preset-p' + periodNumber);
    if (activeBtn) {
        activeBtn.classList.remove('bg-slate-50', 'text-slate-700', 'border-slate-200');
        activeBtn.classList.add('bg-brand-600', 'text-white', 'border-brand-600');
    }
}

function quickAddSlot(dayKey, startTime, endTime, periodNumber) {
    // Open dedicated Add Slot modal with day and period pre-filled
    openAddSlotModal(dayKey, startTime, endTime, periodNumber);

    // Also update sticky sidebar card for continuity
    const daySelect = document.getElementById('day_of_week');
    if (daySelect) daySelect.value = dayKey;
    if (periodNumber) {
        applyPeriodPreset(startTime, endTime, periodNumber);
    }
}

// ==========================================
// MODAL CONTROLS
// ==========================================
function openAddSlotModal(dayKey, startTime, endTime, periodNumber) {
    if (dayKey) {
        const modalDay = document.getElementById('modal_day_of_week');
        if (modalDay) modalDay.value = dayKey;
    }
    if (periodNumber) {
        applyModalPeriodPreset(startTime, endTime, periodNumber);
    } else if (startTime && endTime) {
        const s = document.getElementById('modal_start_time');
        const e = document.getElementById('modal_end_time');
        if (s) s.value = startTime;
        if (e) e.value = endTime;
    }
    document.getElementById('addSlotModal')?.classList.remove('hidden');
    document.getElementById('modal_class_subject_id')?.focus();
}

function closeAddSlotModal() {
    document.getElementById('addSlotModal')?.classList.add('hidden');
}

function openCloneModal() {
    document.getElementById('cloneScheduleModal')?.classList.remove('hidden');
}

function closeCloneModal() {
    document.getElementById('cloneScheduleModal')?.classList.add('hidden');
}

function openImportModal() {
    document.getElementById('importCsvModal')?.classList.remove('hidden');
}

function closeImportModal() {
    document.getElementById('importCsvModal')?.classList.add('hidden');
    resetImportStep();
}

function resetImportStep() {
    document.getElementById('import-step-1')?.classList.remove('hidden');
    document.getElementById('import-step-2')?.classList.add('hidden');
    const input = document.getElementById('csv_file_input');
    if (input) input.value = '';
    const confirmBtn = document.getElementById('btn_confirm_import');
    if (confirmBtn) confirmBtn.disabled = true;
    window.PARSED_CSV = null;
}

// Close modals on clicking backdrop
document.getElementById('addSlotModal')?.addEventListener('click', function(e) {
    if (e.target === this) closeAddSlotModal();
});
document.getElementById('cloneScheduleModal')?.addEventListener('click', function(e) {
    if (e.target === this) closeCloneModal();
});
document.getElementById('importCsvModal')?.addEventListener('click', function(e) {
    if (e.target === this) closeImportModal();
});

// ==========================================
// CLIENT-SIDE CSV PARSING & PREVIEW WIZARD
// ==========================================
window.PARSED_CSV = null;

function handleImportFileSelect(input) {
    if (!input.files || input.files.length === 0) return;
    const file = input.files[0];

    const reader = new FileReader();
    reader.onload = function(e) {
        const text = e.target.result;
        document.getElementById('mapped_csv_content').value = text;
        parseAndSetupImportPreview(file.name, text);
    };
    reader.readAsText(file);
}

function parseCsvLines(text) {
    const lines = text.split(/\r\n|\n|\r/);
    const result = [];
    for (let i = 0; i < lines.length; i++) {
        const line = lines[i].trim();
        if (!line) continue;

        // Simple CSV splitter handling quoted fields
        const row = [];
        let inQuotes = false;
        let token = '';
        for (let c = 0; c < line.length; c++) {
            const char = line[c];
            if (char === '"') {
                inQuotes = !inQuotes;
            } else if (char === ',' && !inQuotes) {
                row.push(token.trim().replace(/^"|"$/g, ''));
                token = '';
            } else {
                token += char;
            }
        }
        row.push(token.trim().replace(/^"|"$/g, ''));
        result.push(row);
    }
    return result;
}

function parseAndSetupImportPreview(filename, text) {
    const rows = parseCsvLines(text);
    if (rows.length < 2) {
        alert('The uploaded file must have at least a header row and one data row.');
        resetImportStep();
        return;
    }

    const headers = rows[0];
    const dataRows = rows.slice(1);

    window.PARSED_CSV = {
        filename: filename,
        headers: headers,
        rows: dataRows
    };

    document.getElementById('preview_filename').textContent = filename;
    document.getElementById('preview_total_rows').textContent = `(${dataRows.length} data rows detected)`;

    // Populate Column Mapping Selects
    const selects = ['map_select_day', 'map_select_period', 'map_select_subject', 'map_select_room'];
    selects.forEach(function(selId) {
        const sel = document.getElementById(selId);
        sel.innerHTML = '<option value="-1">-- Not Specified --</option>';
        headers.forEach(function(h, idx) {
            const opt = document.createElement('option');
            opt.value = idx;
            opt.textContent = `${h} (Column ${idx + 1})`;
            sel.appendChild(opt);
        });
    });

    // Auto-match headers by regex
    headers.forEach(function(h, idx) {
        const clean = h.toLowerCase().replace(/[\s\-_]/g, '');
        if (/day|weekday/.test(clean)) {
            document.getElementById('map_select_day').value = idx;
        } else if (/period|slot|bell/.test(clean)) {
            document.getElementById('map_select_period').value = idx;
        } else if (/sub|course|subject/.test(clean)) {
            document.getElementById('map_select_subject').value = idx;
        } else if (/room|venue|lab|hall/.test(clean)) {
            document.getElementById('map_select_room').value = idx;
        }
    });

    // Transition from Step 1 to Step 2
    document.getElementById('import-step-1').classList.add('hidden');
    document.getElementById('import-step-2').classList.remove('hidden');

    renderImportPreview();
}

function renderImportPreview() {
    if (!window.PARSED_CSV) return;

    const dayIdx = parseInt(document.getElementById('map_select_day').value, 10);
    const periodIdx = parseInt(document.getElementById('map_select_period').value, 10);
    const subjectIdx = parseInt(document.getElementById('map_select_subject').value, 10);
    const roomIdx = parseInt(document.getElementById('map_select_room').value, 10);

    // Sync hidden inputs
    document.getElementById('input_map_day').value = dayIdx >= 0 ? dayIdx : '';
    document.getElementById('input_map_period').value = periodIdx >= 0 ? periodIdx : '';
    document.getElementById('input_map_subject').value = subjectIdx >= 0 ? subjectIdx : '';
    document.getElementById('input_map_room').value = roomIdx >= 0 ? roomIdx : '';

    const tbody = document.getElementById('preview_table_body');
    tbody.innerHTML = '';

    let validCount = 0;
    let warningCount = 0;

    const maxPreview = Math.min(window.PARSED_CSV.rows.length, 30);

    for (let i = 0; i < maxPreview; i++) {
        const row = window.PARSED_CSV.rows[i];
        const dayVal = dayIdx >= 0 ? (row[dayIdx] || '').trim() : '';
        const periodVal = periodIdx >= 0 ? (row[periodIdx] || '').trim() : '';
        const subjectVal = subjectIdx >= 0 ? (row[subjectIdx] || '').trim() : '';
        const roomVal = roomIdx >= 0 ? (row[roomIdx] || '').trim() : '';

        // Match subject against class subjects
        let matchedSubject = null;
        if (subjectVal) {
            matchedSubject = window.CLASS_SUBJECTS.find(function(s) {
                return s.code.toLowerCase() === subjectVal.toLowerCase() || s.name.toLowerCase() === subjectVal.toLowerCase();
            });
        }

        // Format period
        let periodDisplay = periodVal ? `Period ${periodVal}` : '<span class="text-slate-400 italic">None</span>';
        const pNum = parseInt(periodVal, 10);
        if (window.PERIOD_SCHEDULE[pNum]) {
            periodDisplay = `<span class="font-bold text-slate-800">${window.PERIOD_SCHEDULE[pNum].name}</span> <span class="text-[10px] text-slate-500 font-mono">(${window.PERIOD_SCHEDULE[pNum].time})</span>`;
        }

        const isValid = dayVal && periodVal && matchedSubject;
        if (isValid) {
            validCount++;
        } else {
            warningCount++;
        }

        const tr = document.createElement('tr');
        tr.className = isValid ? 'hover:bg-slate-50/60' : 'bg-rose-50/30 hover:bg-rose-50/50';

        tr.innerHTML = `
            <td class="p-2 text-center text-slate-400 font-mono text-[10px]">${i + 1}</td>
            <td class="p-2 font-bold text-slate-800">${dayVal || '<span class="text-rose-500 italic">Missing</span>'}</td>
            <td class="p-2">${periodDisplay}</td>
            <td class="p-2">
                ${matchedSubject 
                    ? `<div><span class="font-bold text-brand-700">${matchedSubject.name}</span> <span class="text-[10px] font-mono px-1 py-0.5 rounded bg-brand-50 text-brand-700 font-bold">${matchedSubject.code}</span></div><div class="text-[10px] text-slate-500">Teacher: ${matchedSubject.teacher}</div>`
                    : `<span class="text-rose-600 font-semibold">${subjectVal || 'No Subject'}</span> <span class="text-[10px] text-rose-500 block">Not in class curriculum</span>`
                }
            </td>
            <td class="p-2 text-slate-600 font-medium">${roomVal || '<span class="text-slate-400 italic">—</span>'}</td>
            <td class="p-2 text-center">
                ${isValid
                    ? '<span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">✓ Valid</span>'
                    : '<span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-700">⚠️ Needs Fix</span>'
                }
            </td>
        `;

        tbody.appendChild(tr);
    }

    const statsEl = document.getElementById('preview_stats');
    statsEl.innerHTML = `
        <span class="text-emerald-700 font-bold">${validCount} Valid</span>
        ${warningCount > 0 ? ` • <span class="text-rose-600 font-bold">${warningCount} with issues</span>` : ''}
        ${window.PARSED_CSV.rows.length > maxPreview ? ` <span class="text-slate-400 text-[10px]">(showing first ${maxPreview})</span>` : ''}
    `;

    const confirmBtn = document.getElementById('btn_confirm_import');
    if (confirmBtn) {
        confirmBtn.disabled = validCount === 0;
        confirmBtn.innerHTML = `
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
            </svg>
            Confirm & Import ${validCount} Slot(s)
        `;
    }
}
</script>
