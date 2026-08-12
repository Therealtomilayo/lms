<div class="space-y-6">
    <!-- Header & Navigation -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
        <div>
            <div class="flex items-center gap-2 text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">
                <a href="/admin/timetable" class="text-brand-600 hover:underline">&larr; Timetable Overview</a>
                <span>/</span>
                <span><?= htmlspecialchars($class->name) ?></span>
            </div>
            <h1 class="text-2xl font-bold text-slate-900 leading-tight">Weekly Timetable Builder: <?= htmlspecialchars($class->name) ?></h1>
            <p class="text-xs text-slate-500 mt-1">Configure instructional periods, subject allocations, assigned teachers, and rooms for <strong><?= htmlspecialchars($term->name) ?></strong>.</p>
        </div>

        <div class="flex items-center gap-3 flex-wrap">
            <form method="GET" action="/admin/timetable/<?= (int)$class->id ?>/edit" class="flex items-center gap-2">
                <label for="term_id" class="text-xs font-semibold text-slate-600 uppercase tracking-wider">Term:</label>
                <select name="term_id" id="term_id" onchange="this.form.submit()" class="bg-slate-50 border border-slate-300 text-slate-900 text-xs rounded-lg px-3 py-2 focus:ring-2 focus:ring-brand-500 focus:border-brand-500 font-medium">
                    <?php foreach ($terms as $t): ?>
                        <option value="<?= (int)$t->id ?>" <?= (int)$term->id === (int)$t->id ? 'selected' : '' ?>>
                            <?= htmlspecialchars($t->name) ?> <?= $t->isCurrent ? '(Current)' : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
            <a href="#add-slot-card" class="inline-flex items-center px-4 py-2 bg-brand-600 text-white rounded-lg text-sm font-semibold hover:bg-brand-700 transition shadow-sm">
                + Add Schedule Slot
            </a>
        </div>
    </div>

    <!-- Main Grid and Slot Creator -->
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6 items-start">
        
        <!-- Weekly Timetable Matrix (2 cols on XL) -->
        <div class="xl:col-span-2 space-y-6">
            <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
                <div class="flex items-center justify-between pb-4 border-b border-slate-200">
                    <div>
                        <h2 class="text-lg font-bold text-slate-900">Weekly Schedule Matrix</h2>
                        <p class="text-xs text-slate-500">All periods scheduled for this class cohort.</p>
                    </div>
                    <span class="px-2.5 py-1 bg-slate-100 text-slate-700 rounded-full text-xs font-semibold">
                        <?= count($scheduleData['slots'] ?? []) ?> Total Periods
                    </span>
                </div>

                <!-- Days of Week Tabs / Schedule Column View -->
                <div class="mt-6 space-y-6">
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
                            <div class="px-4 py-2.5 bg-slate-100 border-b border-slate-200 flex items-center justify-between">
                                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-800 flex items-center gap-2">
                                    <span class="w-2 h-2 rounded-full bg-brand-500"></span>
                                    <?= $dayLabel ?>
                                </h3>
                                <span class="text-[11px] font-medium text-slate-500">
                                    <?= count($daySlots) ?> <?= count($daySlots) === 1 ? 'Period' : 'Periods' ?>
                                </span>
                            </div>

                            <div class="p-4">
                                <?php if (empty($daySlots)): ?>
                                    <p class="text-xs text-slate-400 italic py-2">No periods scheduled for <?= $dayLabel ?>.</p>
                                <?php else: ?>
                                    <div class="space-y-3">
                                        <?php foreach ($daySlots as $slot): ?>
                                            <div class="bg-white p-4 rounded-lg border border-slate-200 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-3 hover:border-slate-300 transition">
                                                <div class="flex items-start gap-3">
                                                    <div class="w-10 h-10 rounded-lg bg-brand-50 text-brand-700 flex items-center justify-center font-bold text-xs flex-shrink-0 border border-brand-100">
                                                        <?= htmlspecialchars(substr($slot->classSubject?->subjectCode ?: 'SUB', 0, 4)) ?>
                                                    </div>
                                                    <div>
                                                        <div class="flex items-center gap-2">
                                                            <h4 class="text-sm font-bold text-slate-900"><?= htmlspecialchars($slot->classSubject?->subjectName ?: 'Subject') ?></h4>
                                                            <?php if ($slot->room): ?>
                                                                <span class="px-2 py-0.5 bg-purple-50 text-purple-700 border border-purple-200 rounded text-[11px] font-medium">
                                                                    Room: <?= htmlspecialchars($slot->room) ?>
                                                                </span>
                                                            <?php endif; ?>
                                                        </div>
                                                        <div class="text-xs text-slate-500 mt-1 flex flex-wrap items-center gap-x-3 gap-y-1">
                                                            <span class="font-semibold text-brand-600 flex items-center gap-1">
                                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                                                <?= htmlspecialchars($slot->getFormattedTimeRange()) ?> (<?= $slot->getDurationMinutes() ?>m)
                                                            </span>
                                                            <span>•</span>
                                                            <span>Teacher: <strong class="text-slate-700"><?= htmlspecialchars($slot->classSubject?->teacherName ?? 'Unassigned') ?></strong></span>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Action Buttons (Delete Slot) -->
                                                <div class="flex items-center gap-2 self-end sm:self-center">
                                                    <form method="POST" action="/admin/timetable/<?= (int)$class->id ?>/slots/<?= (int)$slot->id ?>/delete" onsubmit="return confirm('Are you sure you want to remove this timetable slot?');">
                                                        <?= csrf_field() ?>
                                                        <input type="hidden" name="term_id" value="<?= (int)$term->id ?>">
                                                        <button type="submit" class="text-xs text-rose-600 hover:text-rose-800 hover:bg-rose-50 px-2.5 py-1.5 rounded-md border border-rose-200 transition font-medium">
                                                            Remove Slot
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

        <!-- Add Slot Form Card (1 col on XL) -->
        <div id="add-slot-card" class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm space-y-4 sticky top-6">
            <div class="border-b border-slate-200 pb-3">
                <h2 class="text-base font-bold text-slate-900">Add Schedule Slot</h2>
                <p class="text-xs text-slate-500">Allocate a teaching period with real-time conflict checking.</p>
            </div>

            <form method="POST" action="/admin/timetable/<?= (int)$class->id ?>/slots" class="space-y-4">
                <?= csrf_field() ?>
                <input type="hidden" name="term_id" value="<?= (int)$term->id ?>">

                <!-- Subject & Teacher Selection -->
                <div>
                    <label for="class_subject_id" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                        Class Subject & Teacher <span class="text-rose-500">*</span>
                    </label>
                    <select name="class_subject_id" id="class_subject_id" required class="w-full bg-slate-50 border border-slate-300 text-slate-900 text-xs rounded-lg px-3 py-2.5 focus:ring-2 focus:ring-brand-500 focus:border-brand-500">
                        <option value="">-- Select Subject --</option>
                        <?php foreach ($classSubjects as $cs): ?>
                            <option value="<?= (int)$cs->id ?>">
                                <?= htmlspecialchars($cs->subjectName) ?> (<?= htmlspecialchars($cs->subjectCode) ?>) — <?= htmlspecialchars($cs->teacherName ?: 'No Teacher Assigned') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Day of Week -->
                <div>
                    <label for="day_of_week" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                        Day of Week <span class="text-rose-500">*</span>
                    </label>
                    <select name="day_of_week" id="day_of_week" required class="w-full bg-slate-50 border border-slate-300 text-slate-900 text-xs rounded-lg px-3 py-2.5 focus:ring-2 focus:ring-brand-500 focus:border-brand-500">
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
                        <label for="start_time" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                            Start Time <span class="text-rose-500">*</span>
                        </label>
                        <input type="time" name="start_time" id="start_time" value="08:00" required
                               class="w-full bg-slate-50 border border-slate-300 text-slate-900 text-xs rounded-lg px-3 py-2 focus:ring-2 focus:ring-brand-500 focus:border-brand-500">
                    </div>
                    <div>
                        <label for="end_time" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                            End Time <span class="text-rose-500">*</span>
                        </label>
                        <input type="time" name="end_time" id="end_time" value="09:00" required
                               class="w-full bg-slate-50 border border-slate-300 text-slate-900 text-xs rounded-lg px-3 py-2 focus:ring-2 focus:ring-brand-500 focus:border-brand-500">
                    </div>
                </div>

                <!-- Room / Venue -->
                <div>
                    <label for="room" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                        Room / Venue <span class="text-slate-400 font-normal">(Optional)</span>
                    </label>
                    <input type="text" name="room" id="room" placeholder="e.g. Lab 2, Hall A, Room 101" maxlength="50"
                           class="w-full bg-slate-50 border border-slate-300 text-slate-900 text-xs rounded-lg px-3 py-2 focus:ring-2 focus:ring-brand-500 focus:border-brand-500">
                </div>

                <!-- Guidance Box -->
                <div class="p-3 bg-blue-50 border border-blue-200 rounded-lg text-[11px] text-blue-800 space-y-1">
                    <p class="font-semibold flex items-center gap-1">
                        <svg class="w-3.5 h-3.5 text-blue-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg>
                        Automated Conflict Detection
                    </p>
                    <p class="text-blue-700">The system automatically prevents teacher double-booking, class overlapping, and venue collisions using exact interval mathematics.</p>
                </div>

                <button type="submit" class="w-full py-2.5 bg-brand-600 hover:bg-brand-700 text-white rounded-lg text-xs font-bold uppercase tracking-wider transition shadow-sm flex items-center justify-center gap-1.5">
                    Save Period to Timetable
                </button>
            </form>
        </div>
    </div>
</div>
