<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
        <div>
            <h1 class="text-xl font-bold text-slate-900">Timetable Setup & Administration</h1>
            <p class="text-sm text-slate-600 mt-1">Configure weekly classroom instructional schedules, teacher periods, and venues with automated conflict checking.</p>
        </div>
        <div class="flex items-center gap-3">
            <form method="GET" action="/admin/timetable" class="flex items-center gap-2">
                <label for="term_id" class="text-xs font-semibold text-slate-600 uppercase tracking-wider">Term:</label>
                <select name="term_id" id="term_id" onchange="this.form.submit()" class="bg-slate-50 border border-slate-300 text-slate-900 text-sm rounded-lg px-3 py-2 focus:ring-2 focus:ring-brand-500 focus:border-brand-500">
                    <?php foreach ($terms as $t): ?>
                        <option value="<?= (int)$t->id ?>" <?= $selectedTerm && (int)$selectedTerm->id === (int)$t->id ? 'selected' : '' ?>>
                            <?= htmlspecialchars($t->name) ?> <?= $t->isCurrent ? '(Current)' : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>
    </div>

    <!-- Class Cohort Cards -->
    <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm space-y-4">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-lg font-semibold text-slate-900">Class Timetables</h2>
                <p class="text-xs text-slate-500">Select a class cohort to build, view, or modify its weekly period schedule for <strong class="text-slate-700"><?= htmlspecialchars($selectedTerm?->name ?? 'Active Term') ?></strong>.</p>
            </div>
            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-brand-50 text-brand-700 border border-brand-200">
                <?= count($classes) ?> Classes Available
            </span>
        </div>

        <?php if (empty($classes)): ?>
            <div class="text-center py-12 border-2 border-dashed border-slate-200 rounded-lg">
                <svg class="mx-auto h-12 w-12 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                <h3 class="mt-2 text-sm font-medium text-slate-900">No classes found</h3>
                <p class="mt-1 text-xs text-slate-500">Create classes in the academic setup before generating timetables.</p>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                <?php foreach ($classes as $cls): ?>
                    <?php 
                        $slotCount = $classSlotsCount[$cls->id] ?? 0;
                        $hasSlots = $slotCount > 0;
                    ?>
                    <div class="p-5 border border-slate-200 rounded-xl hover:border-brand-500 hover:shadow-md transition-all bg-slate-50/50 flex flex-col justify-between group">
                        <div>
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-semibold uppercase tracking-wider text-slate-500"><?= htmlspecialchars($cls->sectionArm ? "Arm {$cls->sectionArm}" : 'Standard') ?></span>
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium <?= $hasSlots ? 'bg-emerald-100 text-emerald-800 border border-emerald-200' : 'bg-slate-100 text-slate-600 border border-slate-200' ?>">
                                    <?= $slotCount ?> <?= $slotCount === 1 ? 'Period' : 'Periods' ?>
                                </span>
                            </div>
                            <h3 class="text-lg font-bold text-slate-900 mt-2 group-hover:text-brand-600 transition-colors">
                                <?= htmlspecialchars($cls->name) ?>
                            </h3>
                            <p class="text-xs text-slate-500 mt-1">
                                <?= $hasSlots ? 'Weekly schedule active and configured.' : 'No timetable slots scheduled yet.' ?>
                            </p>
                        </div>
                        <div class="mt-5 pt-3 border-t border-slate-200 flex items-center justify-between">
                            <a href="/admin/timetable/<?= (int)$cls->id ?>/edit<?= $selectedTerm ? "?term_id={$selectedTerm->id}" : '' ?>" 
                               class="text-sm font-semibold text-brand-600 hover:text-brand-800 flex items-center gap-1 group-hover:gap-2 transition-all">
                                Open Builder &rarr;
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
