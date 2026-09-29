<?php
$this->layout('layouts/teacher', [
    'title' => 'Affective & Psychomotor Evaluation Matrix — Claret Faculty Portal',
    'headerTitle' => 'Affective & Psychomotor Evaluation Matrix',
    'headerSubtitle' => 'Streamlined rating grid for homeroom teachers to assess students on affective and psychomotor domain traits.'
]);

$affectiveSkills = array_values(array_filter($skills, static fn($s) => $s->category === 'affective'));
$psychomotorSkills = array_values(array_filter($skills, static fn($s) => $s->category === 'psychomotor'));
// Fallback if no categorized skills
if (empty($affectiveSkills) && empty($psychomotorSkills)) {
    $affectiveSkills = $skills;
}
?>

<div class="space-y-6 pb-16">
    <!-- Top Flash Messages -->
    <?php if (!empty($flashSuccess)): ?>
        <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm font-medium flex items-center justify-between shadow-xs">
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5 text-emerald-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                <span><?= htmlspecialchars($flashSuccess) ?></span>
            </div>
        </div>
    <?php endif; ?>

    <?php if (!empty($flashError)): ?>
        <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-sm font-medium flex items-center justify-between shadow-xs">
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5 text-rose-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                <span><?= htmlspecialchars($flashError) ?></span>
            </div>
        </div>
    <?php endif; ?>

    <!-- Navigation Tabs across Form Teacher Result Suite -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-2 sm:p-3">
        <nav class="flex flex-wrap items-center gap-1.5" aria-label="Results Navigation Tabs">
            <a href="/teacher/results/overview<?= ($selectedTermId > 0 && $selectedClassId > 0) ? "?term_id={$selectedTermId}&class_id={$selectedClassId}" : '' ?>"
               class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition">
                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                <span>Form Class Broadsheet Overview</span>
            </a>

            <a href="/teacher/results/skills<?= ($selectedTermId > 0 && $selectedClassId > 0) ? "?session_id={$selectedSessionId}&term_id={$selectedTermId}&class_id={$selectedClassId}" : '' ?>"
               class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-bold text-brand-700 bg-brand-50 border border-brand-200/60 shadow-xs transition" aria-current="page">
                <svg class="w-4 h-4 text-brand-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/></svg>
                <span>Affective &amp; Psychomotor Matrix</span>
            </a>

            <a href="/teacher/results/comments<?= ($selectedTermId > 0 && $selectedClassId > 0) ? "?session_id={$selectedSessionId}&term_id={$selectedTermId}&class_id={$selectedClassId}" : '' ?>"
               class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition">
                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/></svg>
                <span>Batch Remarks &amp; Comments</span>
            </a>
        </nav>
    </div>

    <!-- Cohort Filter Card -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-5">
        <form method="GET" action="/teacher/results/skills" id="skills-cohort-form" class="flex flex-col md:flex-row md:items-end gap-4">
            <div class="w-full md:w-64">
                <label for="filter_class_id" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Assigned Form Class</label>
                <select name="class_id" id="filter_class_id" onchange="document.getElementById('skills-cohort-form').submit()" 
                        class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-slate-300 focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none transition bg-white">
                    <?php foreach ($classes as $c): 
                        $className = method_exists($c, 'getFullName') ? $c->getFullName() : ($c->name . (!empty($c->sectionArm) ? ' (' . $c->sectionArm . ')' : ''));
                    ?>
                        <option value="<?= (int)$c->id ?>" <?= $selectedClassId === (int)$c->id ? 'selected' : '' ?>>
                            <?= htmlspecialchars($className) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="w-full md:w-60">
                <label for="filter_session_id" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Academic Session</label>
                <select name="session_id" id="filter_session_id" onchange="document.getElementById('skills-cohort-form').submit()" 
                        class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-slate-300 focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none transition bg-white">
                    <?php foreach ($sessions as $s): ?>
                        <option value="<?= (int)$s->id ?>" <?= $selectedSessionId === (int)$s->id ? 'selected' : '' ?>>
                            <?= htmlspecialchars($s->name) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="w-full md:w-56">
                <label for="filter_term_id" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Academic Term</label>
                <select name="term_id" id="filter_term_id" onchange="document.getElementById('skills-cohort-form').submit()" 
                        class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-slate-300 focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none transition bg-white">
                    <?php foreach ($terms as $t): ?>
                        <option value="<?= (int)$t->id ?>" <?= $selectedTermId === (int)$t->id ? 'selected' : '' ?>>
                            <?= htmlspecialchars($t->name) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="flex-shrink-0">
                <button type="submit" class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl text-sm font-bold text-white bg-brand-700 hover:bg-brand-800 transition shadow-xs w-full md:w-auto min-h-[44px]">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <span>Load Matrix</span>
                </button>
            </div>
        </form>
    </div>

    <?php if (empty($classes)): ?>
        <div class="bg-white rounded-2xl border border-slate-200 p-12 text-center shadow-xs space-y-4">
            <div class="w-16 h-16 rounded-full bg-slate-100 text-slate-400 mx-auto flex items-center justify-center">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            </div>
            <div class="max-w-md mx-auto space-y-2">
                <h3 class="text-lg font-bold text-slate-900">Form Teacher Class Assignment Required</h3>
                <p class="text-sm text-slate-500 leading-relaxed">
                    You are not currently assigned as a Homeroom / Class Teacher for any cohort. Behavioral ratings and domain assessments are evaluated exclusively by assigned Class Teachers.
                </p>
            </div>
            <div>
                <a href="/teacher/gradebook" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 transition">
                    &larr; Go to Subject Gradebooks
                </a>
            </div>
        </div>
    <?php elseif (empty($students)): ?>
        <div class="bg-white rounded-2xl border border-slate-200 p-12 text-center shadow-xs space-y-3">
            <div class="w-12 h-12 rounded-full bg-slate-100 text-slate-400 mx-auto flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
            </div>
            <h3 class="text-base font-bold text-slate-800">No Students Enrolled in this Cohort</h3>
            <p class="text-sm text-slate-500 max-w-md mx-auto">There are currently no active students found in this class arm for the selected academic period.</p>
        </div>
    <?php else: ?>

        <!-- Matrix Workspace Form -->
        <form method="POST" action="/teacher/results/skills" id="skills-matrix-form" class="space-y-6">
            <?= csrf_field() ?>
            <input type="hidden" name="session_id" value="<?= (int)$selectedSessionId ?>">
            <input type="hidden" name="term_id" value="<?= (int)$selectedTermId ?>">
            <input type="hidden" name="class_id" value="<?= (int)$selectedClassId ?>">

            <!-- Control Bar & Scale Guide -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-5 space-y-4">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-bold text-slate-900 tracking-tight flex items-center gap-2">
                            <span><?= htmlspecialchars($selectedClass?->name ?? 'Class') ?> Evaluation Matrix</span>
                            <span class="text-xs px-2.5 py-0.5 rounded-full bg-brand-50 text-brand-700 border border-brand-200 font-mono font-semibold">
                                <?= count($students) ?> Students
                            </span>
                            <span class="text-xs px-2.5 py-0.5 rounded-full bg-slate-100 text-slate-700 border border-slate-200 font-semibold">
                                <?= count($skills) ?> Evaluation Traits
                            </span>
                        </h2>
                        <p class="text-xs text-slate-500 mt-1">
                            Click or tap ratings (1–5 scale) for each student. Changes are saved when you click "Save Ratings Matrix".
                        </p>
                    </div>

                    <!-- Bulk Fast Actions -->
                    <div class="flex flex-wrap items-center gap-2.5">
                        <div class="flex items-center gap-1.5 bg-slate-50 p-1 rounded-xl border border-slate-200 text-xs">
                            <span class="px-2 text-slate-500 font-medium">Quick Fill All:</span>
                            <button type="button" onclick="bulkSetAll(5)" class="px-2.5 py-1 rounded-lg font-bold bg-white text-emerald-700 border border-slate-200 hover:bg-emerald-50 transition cursor-pointer" title="Set all unset/all traits to 5">
                                All 5
                            </button>
                            <button type="button" onclick="bulkSetAll(4)" class="px-2.5 py-1 rounded-lg font-bold bg-white text-sky-700 border border-slate-200 hover:bg-sky-50 transition cursor-pointer" title="Set all unset/all traits to 4">
                                All 4
                            </button>
                            <button type="button" onclick="bulkSetAll(3)" class="px-2.5 py-1 rounded-lg font-bold bg-white text-slate-700 border border-slate-200 hover:bg-slate-100 transition cursor-pointer" title="Set all unset/all traits to 3">
                                All 3
                            </button>
                        </div>

                        <button type="submit" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-bold text-white bg-emerald-600 hover:bg-emerald-700 shadow-sm transition cursor-pointer">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
                            <span>Save Ratings Matrix</span>
                        </button>
                    </div>
                </div>

                <!-- 1 to 5 Key Indicator Legend -->
                <div class="grid grid-cols-2 sm:grid-cols-5 gap-2 pt-3 border-t border-slate-100 text-xs">
                    <div class="flex items-center gap-2 p-2 rounded-lg bg-emerald-50/60 border border-emerald-100 text-emerald-900">
                        <span class="w-6 h-6 rounded-full bg-emerald-600 text-white font-bold flex items-center justify-center text-xs flex-shrink-0">5</span>
                        <div class="truncate"><strong class="font-bold">5 - Excellent</strong><div class="text-[10px] text-emerald-700 truncate">Exemplary mastery</div></div>
                    </div>
                    <div class="flex items-center gap-2 p-2 rounded-lg bg-sky-50/60 border border-sky-100 text-sky-900">
                        <span class="w-6 h-6 rounded-full bg-sky-600 text-white font-bold flex items-center justify-center text-xs flex-shrink-0">4</span>
                        <div class="truncate"><strong class="font-bold">4 - Very Good</strong><div class="text-[10px] text-sky-700 truncate">High consistency</div></div>
                    </div>
                    <div class="flex items-center gap-2 p-2 rounded-lg bg-slate-50 border border-slate-200 text-slate-800">
                        <span class="w-6 h-6 rounded-full bg-slate-600 text-white font-bold flex items-center justify-center text-xs flex-shrink-0">3</span>
                        <div class="truncate"><strong class="font-bold">3 - Good</strong><div class="text-[10px] text-slate-600 truncate">Satisfactory standard</div></div>
                    </div>
                    <div class="flex items-center gap-2 p-2 rounded-lg bg-amber-50/60 border border-amber-100 text-amber-900">
                        <span class="w-6 h-6 rounded-full bg-amber-600 text-white font-bold flex items-center justify-center text-xs flex-shrink-0">2</span>
                        <div class="truncate"><strong class="font-bold">2 - Fair</strong><div class="text-[10px] text-amber-700 truncate">Needs encouragement</div></div>
                    </div>
                    <div class="flex items-center gap-2 p-2 rounded-lg bg-rose-50/60 border border-rose-100 text-rose-900">
                        <span class="w-6 h-6 rounded-full bg-rose-600 text-white font-bold flex items-center justify-center text-xs flex-shrink-0">1</span>
                        <div class="truncate"><strong class="font-bold">1 - Poor</strong><div class="text-[10px] text-rose-700 truncate">Urgent intervention</div></div>
                    </div>
                </div>
            </div>

            <!-- Sticky Horizontal Evaluation Matrix Table -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
                <div class="overflow-x-auto max-w-full">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead>
                            <!-- Category Super-Header Row -->
                            <tr class="bg-slate-100 border-b border-slate-200 text-slate-700 font-extrabold tracking-wider uppercase text-[11px]">
                                <th colspan="3" class="py-2.5 px-4 sticky left-0 z-20 bg-slate-100 border-r border-slate-300">
                                    Student Identification
                                </th>
                                <?php if (!empty($affectiveSkills)): ?>
                                    <th colspan="<?= count($affectiveSkills) ?>" class="py-2.5 px-3 text-center bg-blue-50/80 text-blue-900 border-r border-slate-300">
                                        Affective Domain (Social, Emotional &amp; Conduct)
                                    </th>
                                <?php endif; ?>
                                <?php if (!empty($psychomotorSkills)): ?>
                                    <th colspan="<?= count($psychomotorSkills) ?>" class="py-2.5 px-3 text-center bg-emerald-50/80 text-emerald-900">
                                        Psychomotor Domain (Physical, Motor Skills &amp; Dexterity)
                                    </th>
                                <?php endif; ?>
                            </tr>

                            <!-- Individual Trait Header Row with Column Quick Actions -->
                            <tr class="bg-slate-50 border-b border-slate-200 text-slate-600 font-bold">
                                <th class="py-3 px-3 w-10 text-center sticky left-0 z-20 bg-slate-50 border-r border-slate-200">#</th>
                                <th class="py-3 px-3 w-28 sticky left-10 z-20 bg-slate-50 border-r border-slate-200">Adm. No.</th>
                                <th class="py-3 px-4 min-w-[190px] sticky left-38 z-20 bg-slate-50 border-r border-slate-300 shadow-[2px_0_4px_-1px_rgba(0,0,0,0.06)]">Student Name</th>

                                <!-- Affective Skills Columns -->
                                <?php foreach ($affectiveSkills as $sk): ?>
                                    <th class="py-3 px-2 text-center min-w-[125px] border-r border-slate-200 bg-blue-50/30">
                                        <div class="font-bold text-slate-900 leading-tight"><?= htmlspecialchars($sk->name) ?></div>
                                        <div class="mt-1 flex items-center justify-center gap-1">
                                            <span class="text-[9px] text-slate-400 font-normal uppercase">Col:</span>
                                            <button type="button" onclick="bulkSetColumn(<?= (int)$sk->id ?>, 5)" class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-white text-emerald-700 border border-slate-200 hover:bg-emerald-50" title="Set entire column to 5">5</button>
                                            <button type="button" onclick="bulkSetColumn(<?= (int)$sk->id ?>, 4)" class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-white text-sky-700 border border-slate-200 hover:bg-sky-50" title="Set entire column to 4">4</button>
                                            <button type="button" onclick="bulkSetColumn(<?= (int)$sk->id ?>, 3)" class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-white text-slate-700 border border-slate-200 hover:bg-slate-100" title="Set entire column to 3">3</button>
                                        </div>
                                    </th>
                                <?php endforeach; ?>

                                <!-- Psychomotor Skills Columns -->
                                <?php foreach ($psychomotorSkills as $sk): ?>
                                    <th class="py-3 px-2 text-center min-w-[125px] border-r border-slate-200 bg-emerald-50/30">
                                        <div class="font-bold text-slate-900 leading-tight"><?= htmlspecialchars($sk->name) ?></div>
                                        <div class="mt-1 flex items-center justify-center gap-1">
                                            <span class="text-[9px] text-slate-400 font-normal uppercase">Col:</span>
                                            <button type="button" onclick="bulkSetColumn(<?= (int)$sk->id ?>, 5)" class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-white text-emerald-700 border border-slate-200 hover:bg-emerald-50" title="Set entire column to 5">5</button>
                                            <button type="button" onclick="bulkSetColumn(<?= (int)$sk->id ?>, 4)" class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-white text-sky-700 border border-slate-200 hover:bg-sky-50" title="Set entire column to 4">4</button>
                                            <button type="button" onclick="bulkSetColumn(<?= (int)$sk->id ?>, 3)" class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-white text-slate-700 border border-slate-200 hover:bg-slate-100" title="Set entire column to 3">3</button>
                                        </div>
                                    </th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php foreach ($students as $idx => $st): 
                                $studentId = (int)$st->id;
                                $studentName = $st->name ?? ($st->user?->name ?? 'Student');
                                $admNo = $st->admissionNumber ?? "ADM-{$studentId}";
                                $studentRatings = $ratingsMatrix[$studentId] ?? [];
                            ?>
                                <tr class="hover:bg-slate-50/80 transition group" data-student-row="<?= $studentId ?>">
                                    <td class="py-2.5 px-3 text-center font-mono text-slate-400 sticky left-0 z-10 bg-white group-hover:bg-slate-50 border-r border-slate-200">
                                        <?= $idx + 1 ?>
                                    </td>
                                    <td class="py-2.5 px-3 font-mono text-[11px] text-slate-600 sticky left-10 z-10 bg-white group-hover:bg-slate-50 border-r border-slate-200 whitespace-nowrap">
                                        <?= htmlspecialchars($admNo) ?>
                                    </td>
                                    <td class="py-2.5 px-4 font-bold text-slate-900 sticky left-38 z-10 bg-white group-hover:bg-slate-50 border-r border-slate-300 shadow-[2px_0_4px_-1px_rgba(0,0,0,0.06)] whitespace-nowrap">
                                        <div class="flex items-center justify-between gap-3">
                                            <span class="truncate max-w-[150px] sm:max-w-none" title="<?= htmlspecialchars($studentName) ?>"><?= htmlspecialchars($studentName) ?></span>
                                            <!-- Row quick set -->
                                            <div class="opacity-0 group-hover:opacity-100 transition-opacity flex items-center gap-1 text-[10px]">
                                                <button type="button" onclick="bulkSetRow(<?= $studentId ?>, 5)" class="px-1 py-0.2 rounded bg-emerald-50 text-emerald-800 border border-emerald-200 font-bold hover:bg-emerald-100" title="Set student to all 5">5</button>
                                                <button type="button" onclick="bulkSetRow(<?= $studentId ?>, 4)" class="px-1 py-0.2 rounded bg-sky-50 text-sky-800 border border-sky-200 font-bold hover:bg-sky-100" title="Set student to all 4">4</button>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Affective Skills Cells -->
                                    <?php foreach ($affectiveSkills as $sk): 
                                        $currentVal = (int)($studentRatings[$sk->id] ?? 0);
                                    ?>
                                        <td class="py-2 px-2 text-center border-r border-slate-100 whitespace-nowrap">
                                            <div class="inline-flex items-center gap-1 bg-slate-50/80 p-0.5 rounded-lg border border-slate-200/80">
                                                <?php for ($val = 1; $val <= 5; $val++): 
                                                    $radioId = "rate_{$studentId}_{$sk->id}_{$val}";
                                                    $colorClass = match($val) {
                                                        5 => 'peer-checked:bg-emerald-600 peer-checked:text-white peer-checked:border-emerald-600',
                                                        4 => 'peer-checked:bg-sky-600 peer-checked:text-white peer-checked:border-sky-600',
                                                        3 => 'peer-checked:bg-slate-700 peer-checked:text-white peer-checked:border-slate-700',
                                                        2 => 'peer-checked:bg-amber-600 peer-checked:text-white peer-checked:border-amber-600',
                                                        1 => 'peer-checked:bg-rose-600 peer-checked:text-white peer-checked:border-rose-600',
                                                    };
                                                ?>
                                                    <label for="<?= $radioId ?>" class="cursor-pointer">
                                                        <input type="radio" 
                                                               id="<?= $radioId ?>"
                                                               name="ratings[<?= $studentId ?>][<?= (int)$sk->id ?>]" 
                                                               value="<?= $val ?>" 
                                                               data-skill-col="<?= (int)$sk->id ?>"
                                                               data-student-row="<?= $studentId ?>"
                                                               class="peer sr-only"
                                                               <?= $currentVal === $val ? 'checked' : '' ?>>
                                                        <span class="inline-flex items-center justify-center w-5 h-5 rounded text-[10px] font-bold text-slate-600 hover:bg-white hover:shadow-2xs transition <?= $colorClass ?>">
                                                            <?= $val ?>
                                                        </span>
                                                    </label>
                                                <?php endfor; ?>
                                            </div>
                                        </td>
                                    <?php endforeach; ?>

                                    <!-- Psychomotor Skills Cells -->
                                    <?php foreach ($psychomotorSkills as $sk): 
                                        $currentVal = (int)($studentRatings[$sk->id] ?? 0);
                                    ?>
                                        <td class="py-2 px-2 text-center border-r border-slate-100 whitespace-nowrap">
                                            <div class="inline-flex items-center gap-1 bg-slate-50/80 p-0.5 rounded-lg border border-slate-200/80">
                                                <?php for ($val = 1; $val <= 5; $val++): 
                                                    $radioId = "rate_{$studentId}_{$sk->id}_{$val}";
                                                    $colorClass = match($val) {
                                                        5 => 'peer-checked:bg-emerald-600 peer-checked:text-white peer-checked:border-emerald-600',
                                                        4 => 'peer-checked:bg-sky-600 peer-checked:text-white peer-checked:border-sky-600',
                                                        3 => 'peer-checked:bg-slate-700 peer-checked:text-white peer-checked:border-slate-700',
                                                        2 => 'peer-checked:bg-amber-600 peer-checked:text-white peer-checked:border-amber-600',
                                                        1 => 'peer-checked:bg-rose-600 peer-checked:text-white peer-checked:border-rose-600',
                                                    };
                                                ?>
                                                    <label for="<?= $radioId ?>" class="cursor-pointer">
                                                        <input type="radio" 
                                                               id="<?= $radioId ?>"
                                                               name="ratings[<?= $studentId ?>][<?= (int)$sk->id ?>]" 
                                                               value="<?= $val ?>" 
                                                               data-skill-col="<?= (int)$sk->id ?>"
                                                               data-student-row="<?= $studentId ?>"
                                                               class="peer sr-only"
                                                               <?= $currentVal === $val ? 'checked' : '' ?>>
                                                        <span class="inline-flex items-center justify-center w-5 h-5 rounded text-[10px] font-bold text-slate-600 hover:bg-white hover:shadow-2xs transition <?= $colorClass ?>">
                                                            <?= $val ?>
                                                        </span>
                                                    </label>
                                                <?php endfor; ?>
                                            </div>
                                        </td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Sticky Bottom Action Footer -->
            <div class="flex items-center justify-between flex-wrap gap-4 bg-white p-4 rounded-2xl border border-slate-200 shadow-sm sticky bottom-4 z-30">
                <div class="text-xs text-slate-500">
                    Showing <strong class="text-slate-800"><?= count($students) ?></strong> students across <strong class="text-slate-800"><?= count($skills) ?></strong> behavioral dimensions.
                </div>
                <div class="flex items-center gap-3">
                    <a href="/teacher/results/overview?class_id=<?= (int)$selectedClassId ?>&term_id=<?= (int)$selectedTermId ?>" 
                       class="px-4 py-2.5 rounded-xl text-sm font-semibold text-slate-700 bg-slate-100 hover:bg-slate-200 transition">
                        Cancel / Back to Broadsheet
                    </a>
                    <button type="submit" class="inline-flex items-center gap-2 px-6 py-2.5 rounded-xl text-sm font-bold text-white bg-emerald-600 hover:bg-emerald-700 shadow-sm transition cursor-pointer">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        <span>Save All Ratings Matrix</span>
                    </button>
                </div>
            </div>
        </form>

        <script>
        function bulkSetAll(value) {
            const inputs = document.querySelectorAll(`input[type="radio"][value="${value}"]`);
            inputs.forEach(input => {
                input.checked = true;
            });
        }

        function bulkSetColumn(skillId, value) {
            const inputs = document.querySelectorAll(`input[type="radio"][data-skill-col="${skillId}"][value="${value}"]`);
            inputs.forEach(input => {
                input.checked = true;
            });
        }

        function bulkSetRow(studentId, value) {
            const inputs = document.querySelectorAll(`input[type="radio"][data-student-row="${studentId}"][value="${value}"]`);
            inputs.forEach(input => {
                input.checked = true;
            });
        }
        </script>
    <?php endif; ?>
</div>
