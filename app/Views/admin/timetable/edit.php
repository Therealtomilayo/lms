<div class="space-y-6">
    <!-- Header & Navigation Card -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
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

            <!-- Term Switcher & Add Slot Anchor -->
            <div class="flex items-center gap-3 flex-wrap">
                <form method="GET" action="/admin/timetable/<?= (int)$class->id ?>/edit" class="flex items-center gap-2">
                    <label for="term_id" class="text-xs font-semibold text-slate-600 uppercase tracking-wider whitespace-nowrap">
                        Term:
                    </label>
                    <div class="relative">
                        <select
                            name="term_id"
                            id="term_id"
                            onchange="this.form.submit()"
                            class="bg-slate-50 border border-slate-300 text-slate-900 text-xs font-semibold rounded-lg px-3.5 py-2 pr-8 focus:ring-2 focus:ring-brand-500 focus:border-brand-500 focus:bg-white transition-colors cursor-pointer appearance-none"
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

                <?php $this->include('components/button', [
                    'label' => '+ Add Schedule Slot',
                    'variant' => 'primary',
                    'href' => '#add-slot-card',
                    'class' => 'text-xs font-bold'
                ]); ?>
            </div>
        </div>
    </div>

    <!-- Main Grid: Weekly Timetable Matrix + Add Slot Sticky Sidebar -->
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6 items-start">
        
        <!-- Weekly Timetable Matrix (2 cols on XL) -->
        <div class="xl:col-span-2 space-y-6">
            <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-xs">
                <div class="flex items-center justify-between pb-4 border-b border-slate-200">
                    <div>
                        <h2 class="text-base font-bold text-slate-900">Weekly Schedule Matrix</h2>
                        <p class="text-xs text-slate-500">All instructional periods scheduled for this class cohort.</p>
                    </div>
                    <?php 
                    $totalSlots = count($scheduleData['slots'] ?? []); 
                    $this->include('components/badge', [
                        'label' => $totalSlots . ' Total ' . ($totalSlots === 1 ? 'Period' : 'Periods'),
                        'variant' => $totalSlots > 0 ? 'success' : 'neutral',
                        'class' => 'font-semibold text-xs'
                    ]);
                    ?>
                </div>

                <!-- Days of Week Tabs / Schedule Column View -->
                <div class="mt-6 space-y-5">
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
                    ?>

                    <?php foreach ($days as $dayKey => $dayLabel): ?>
                        <?php 
                        $daySlots = $grid[$dayKey] ?? []; 
                        // Only show Sat/Sun if they have slots or on weekday
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
                                                            class="inline-flex items-center gap-1 text-xs text-rose-600 hover:text-rose-800 hover:bg-rose-50 px-3 py-1.5 rounded-lg border border-rose-200 transition font-semibold"
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
                <p class="text-xs text-slate-500">Allocate a teaching period with automatic conflict checking.</p>
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
                            value="09:00"
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

                <!-- Automated Conflict Detection Guidance Box -->
                <div class="p-3.5 bg-sky-50 border border-sky-200 rounded-xl text-xs text-sky-900 space-y-1">
                    <p class="font-bold flex items-center gap-1.5 text-sky-800">
                        <svg class="w-4 h-4 text-sky-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                        </svg>
                        Automated Conflict Detection
                    </p>
                    <p class="text-[11px] text-sky-700 leading-relaxed">
                        The system automatically prevents teacher double-booking, class overlapping, and venue collisions across all cohorts.
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
