<?php
$this->layout('layouts/admin', [
    'title'          => 'Institutional Gradebook — Classes & Arms Overview',
    'headerTitle'    => 'Institutional Gradebook',
    'headerSubtitle' => 'School-wide continuous assessment oversight, class arm broadsheets, and score locking.',
]);

// Prepare dropdown options
$sessionOptions = [];
foreach ($sessions as $s) {
    $sessionOptions[$s->id] = $s->name . ($s->status === 'active' ? ' (Active)' : '');
}

$termOptions = [];
foreach ($terms as $t) {
    $termOptions[$t->id] = $t->name . ($t->status === 'active' ? ' (Current)' : '');
}

$levelOptions = ['' => 'All Academic Levels'];
foreach ($levels as $l) {
    $levelOptions[$l->id] = $l->name . ' (' . ucfirst($l->stage) . ')';
}
?>

<div class="space-y-6 pb-12">

    <!-- Header Card with Breadcrumb & Quick Actions -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div class="space-y-1">
                <nav class="flex items-center gap-2 text-xs font-medium text-slate-400 mb-1" aria-label="Breadcrumb">
                    <a href="/admin/dashboard" class="hover:text-brand-700 transition">Dashboard</a>
                    <span>&rsaquo;</span>
                    <span class="text-slate-700 font-semibold">Institutional Gradebook</span>
                </nav>
                <div class="flex flex-wrap items-center gap-3">
                    <h2 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">Classes &amp; Arms Gradebook Directory</h2>
                    <?php if ($selectedSession): ?>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-brand-50 text-brand-800 border border-brand-200">
                            <?= e($selectedSession->name) ?>
                        </span>
                    <?php endif; ?>
                    <?php if ($selectedTerm): ?>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-sky-50 text-sky-700 border border-sky-200">
                            <?= e($selectedTerm->name) ?>
                        </span>
                    <?php endif; ?>
                </div>
                <p class="text-sm text-slate-500">
                    Oversight of student performance across class cohorts. Select an arm to inspect its broadsheet and continuous assessment sheets.
                </p>
            </div>

            <!-- Quick Management Actions -->
            <div class="flex flex-wrap items-center gap-2.5 flex-shrink-0">
                <a href="/admin/results/review<?= $selectedTermId ? '?term_id=' . $selectedTermId : '' ?>"
                   class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl text-sm font-semibold text-slate-700 bg-slate-50 hover:bg-slate-100 border border-slate-200 transition shadow-xs">
                    <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    <span>Results Review</span>
                </a>
                <a href="/admin/assessment-categories"
                   class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl text-sm font-semibold text-slate-700 bg-slate-50 hover:bg-slate-100 border border-slate-200 transition shadow-xs">
                    <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
                    <span>Assessment Config</span>
                </a>
                <a href="/admin/grading-scales"
                   class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl text-sm font-semibold text-slate-700 bg-slate-50 hover:bg-slate-100 border border-slate-200 transition shadow-xs">
                    <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3"/></svg>
                    <span>Grading Scales</span>
                </a>
            </div>
        </div>
    </div>

    <!-- 4-Card Overview Stats Strip -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- 1. Total Classes & Arms -->
        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-brand-50 text-brand-700 flex items-center justify-center flex-shrink-0">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
            </div>
            <div class="min-w-0">
                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Classes &amp; Arms</p>
                <h3 class="text-2xl font-extrabold text-slate-900 leading-tight"><?= (int)$totalClasses ?></h3>
                <p class="text-xs text-slate-500 truncate">Academic class cohorts</p>
            </div>
        </div>

        <!-- 2. Enrolled Student Population -->
        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center flex-shrink-0">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
            </div>
            <div class="min-w-0">
                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Total Enrolled</p>
                <h3 class="text-2xl font-extrabold text-slate-900 leading-tight"><?= (int)$totalStudentsAllClasses ?></h3>
                <p class="text-xs text-slate-500 truncate">Active student population</p>
            </div>
        </div>

        <!-- 3. Curriculum Subjects Allocated -->
        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center flex-shrink-0">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
            </div>
            <div class="min-w-0">
                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Subject Offerings</p>
                <h3 class="text-2xl font-extrabold text-slate-900 leading-tight"><?= (int)$totalSubjectsAllClasses ?></h3>
                <p class="text-xs text-slate-500 truncate">Total class-subject allocations</p>
            </div>
        </div>

        <!-- 4. Fully Locked Cohorts / Institutional Mean -->
        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center flex-shrink-0">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
            </div>
            <div class="min-w-0">
                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Locked Classes</p>
                <div class="flex items-baseline gap-2">
                    <h3 class="text-2xl font-extrabold text-slate-900 leading-tight"><?= (int)$totalLockedClassesCount ?></h3>
                    <?php if ($institutionalMean !== null): ?>
                        <span class="text-xs font-semibold text-emerald-600">Mean: <?= $institutionalMean ?>%</span>
                    <?php endif; ?>
                </div>
                <p class="text-xs text-slate-500 truncate">Finalized and frozen</p>
            </div>
        </div>
    </div>

    <!-- Filter Toolbar Form -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-5">
        <form method="GET" action="/admin/gradebook" class="space-y-4" id="gradebook-filter-form">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5">
                <!-- Academic Session -->
                <div>
                    <label for="filter-session" class="block text-xs font-semibold text-slate-600 mb-1">Academic Session</label>
                    <select id="filter-session" name="session_id" onchange="document.getElementById('gradebook-filter-form').submit()"
                            class="w-full min-h-[42px] px-3 py-2 bg-white border border-slate-300 rounded-xl text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-brand-500">
                        <?php foreach ($sessionOptions as $val => $lbl): ?>
                            <option value="<?= e($val) ?>" <?= (int)$selectedSessionId === (int)$val ? 'selected' : '' ?>><?= e($lbl) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Term -->
                <div>
                    <label for="filter-term" class="block text-xs font-semibold text-slate-600 mb-1">Academic Term</label>
                    <select id="filter-term" name="term_id" onchange="document.getElementById('gradebook-filter-form').submit()"
                            class="w-full min-h-[42px] px-3 py-2 bg-white border border-slate-300 rounded-xl text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-brand-500">
                        <?php foreach ($termOptions as $val => $lbl): ?>
                            <option value="<?= e($val) ?>" <?= (int)$selectedTermId === (int)$val ? 'selected' : '' ?>><?= e($lbl) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Academic Level -->
                <div>
                    <label for="filter-level" class="block text-xs font-semibold text-slate-600 mb-1">Academic Level</label>
                    <select id="filter-level" name="level_id" onchange="document.getElementById('gradebook-filter-form').submit()"
                            class="w-full min-h-[42px] px-3 py-2 bg-white border border-slate-300 rounded-xl text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-brand-500">
                        <?php foreach ($levelOptions as $val => $lbl): ?>
                            <option value="<?= e($val) ?>" <?= (int)$selectedLevelId === (int)$val ? 'selected' : '' ?>><?= e($lbl) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Instant Search Input -->
                <div>
                    <label for="class-search" class="block text-xs font-semibold text-slate-600 mb-1">Search Class / Arm</label>
                    <div class="relative">
                        <svg class="w-4 h-4 text-slate-400 absolute left-3.5 top-3 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        <input type="text" id="class-search" name="q" value="<?= e($searchQuery) ?>"
                               placeholder="e.g. JSS 1 Emerald..."
                               oninput="filterClasses(this.value)"
                               class="w-full pl-10 pr-4 py-2 bg-slate-50 hover:bg-white focus:bg-white border border-slate-200 focus:border-brand-500 rounded-xl text-sm text-slate-800 transition shadow-2xs focus:outline-none focus:ring-2 focus:ring-brand-500">
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-between pt-2 border-t border-slate-100">
                <span class="text-xs font-semibold text-slate-500">
                    Showing <strong id="class-count-visible" class="text-slate-900"><?= count($classRows) ?></strong> class arms
                </span>
                <div class="flex items-center gap-2">
                    <button type="submit" class="px-4 py-2 bg-brand-700 hover:bg-brand-800 text-white rounded-xl text-sm font-semibold transition shadow-xs">
                        Filter
                    </button>
                    <?php if ($selectedLevelId || $searchQuery): ?>
                        <a href="/admin/gradebook?session_id=<?= (int)$selectedSessionId ?>&term_id=<?= (int)$selectedTermId ?>"
                           class="px-3 py-2 text-slate-500 hover:text-slate-800 text-sm font-semibold transition">
                            Clear
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    </div>

    <!-- MAIN CLASSES & ARMS DIRECTORY TABLE -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
            <div>
                <h3 class="text-base font-bold text-slate-900">School Classes &amp; Arms</h3>
                <p class="text-xs text-slate-500">Click any class arm to inspect its student broadsheet and individual subject continuous assessments.</p>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-700 divide-y divide-slate-200" id="classes-table">
                <thead class="bg-slate-50 text-xs font-bold text-slate-600 uppercase tracking-wider">
                    <tr>
                        <th scope="col" class="py-3.5 px-4">Class &amp; Arm</th>
                        <th scope="col" class="py-3.5 px-4">Academic Level</th>
                        <th scope="col" class="py-3.5 px-4 text-center">Enrolled Roster</th>
                        <th scope="col" class="py-3.5 px-4 text-center">Curriculum Subjects</th>
                        <th scope="col" class="py-3.5 px-4 text-center">Scoring Progress</th>
                        <th scope="col" class="py-3.5 px-4 text-center">Class Mean</th>
                        <th scope="col" class="py-3.5 px-4 text-center">Status</th>
                        <th scope="col" class="py-3.5 px-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 bg-white" id="classes-tbody">
                    <?php if (empty($classRows)): ?>
                        <tr>
                            <td colspan="8" class="py-12">
                                <?php $this->include('components/empty_state', [
                                    'title'       => 'No classes found',
                                    'description' => 'No active class cohorts match your filter criteria.',
                                    'icon'        => 'academic',
                                ]); ?>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($classRows as $row): ?>
                            <?php
                            $c = $row['class'];
                            $enrolled = $row['enrolledCount'];
                            $subjects = $row['subjectsCount'];
                            $evaluated = $row['evaluatedSubjects'];
                            $locked = $row['lockedSubjects'];
                            $avg = $row['classAverage'];
                            $isFullyLocked = $row['isFullyLocked'];

                            $evalPct = $subjects > 0 ? min(100, round(($evaluated / $subjects) * 100)) : 0;
                            ?>
                            <tr class="class-row hover:bg-slate-50/80 transition"
                                data-class-name="<?= e(strtolower($c->name . ' ' . ($c->sectionArm ?? ''))) ?>">
                                
                                <!-- Class & Arm -->
                                <td class="py-4 px-4 font-bold text-slate-900">
                                    <div class="flex items-center gap-2">
                                        <span class="text-base text-slate-900"><?= e($c->name) ?></span>
                                        <?php if (!empty($c->sectionArm)): ?>
                                            <span class="px-2.5 py-0.5 rounded-md text-xs font-mono font-bold bg-brand-50 text-brand-700 border border-brand-200">
                                                <?= e($c->sectionArm) ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </td>

                                <!-- Academic Level -->
                                <td class="py-4 px-4 text-xs font-medium text-slate-600">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded bg-slate-100 border border-slate-200 text-slate-700">
                                        <?= e($c->academicLevel?->name ?? 'Level ' . $c->academicLevelId) ?>
                                    </span>
                                </td>

                                <!-- Enrolled Roster -->
                                <td class="py-4 px-4 text-center font-mono text-sm font-semibold text-slate-900">
                                    <span class="inline-flex items-center gap-1.5">
                                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                        <?= (int)$enrolled ?>
                                    </span>
                                </td>

                                <!-- Curriculum Subjects -->
                                <td class="py-4 px-4 text-center font-mono text-xs font-medium text-slate-700">
                                    <?= (int)$subjects ?> subjects
                                </td>

                                <!-- Scoring Progress -->
                                <td class="py-4 px-4 text-center">
                                    <div class="inline-flex flex-col items-center gap-1 w-28">
                                        <div class="flex items-center justify-between w-full text-xs font-semibold">
                                            <span class="text-slate-700"><?= $evaluated ?>/<?= $subjects ?></span>
                                            <span class="text-[11px] text-slate-400"><?= $evalPct ?>%</span>
                                        </div>
                                        <div class="w-full bg-slate-100 rounded-full h-2 overflow-hidden">
                                            <div class="h-2 rounded-full <?= $evalPct === 100 ? 'bg-emerald-500' : ($evalPct > 0 ? 'bg-brand-600' : 'bg-slate-200') ?>" style="width: <?= $evalPct ?>%"></div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Class Mean -->
                                <td class="py-4 px-4 text-center font-mono">
                                    <?php if ($avg !== null): ?>
                                        <span class="text-sm font-bold text-slate-900"><?= $avg ?>%</span>
                                    <?php else: ?>
                                        <span class="text-xs text-slate-400 font-normal">Pending</span>
                                    <?php endif; ?>
                                </td>

                                <!-- Status -->
                                <td class="py-4 px-4 text-center">
                                    <?php if ($isFullyLocked): ?>
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-200">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                            Fully Locked
                                        </span>
                                    <?php elseif ($evaluated > 0): ?>
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            <?= $locked > 0 ? "{$locked} Locked &bull; Scoring" : 'In Progress' ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-600 border border-slate-200">
                                            Not Started
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <!-- Actions -->
                                <td class="py-4 px-4 text-right whitespace-nowrap">
                                    <div class="inline-flex items-center gap-2">
                                        <a href="/admin/gradebook/class/<?= (int)$c->id ?>?session_id=<?= (int)$selectedSessionId ?>&term_id=<?= (int)$selectedTermId ?>"
                                           class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold bg-brand-700 hover:bg-brand-800 text-white transition shadow-2xs">
                                            <span>Inspect Gradebook</span>
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                        </a>
                                        <a href="/admin/results/review?term_id=<?= (int)$selectedTermId ?>&class_id=<?= (int)$c->id ?>"
                                           title="Review Results for Class"
                                           class="p-1.5 text-slate-400 hover:text-slate-700 rounded-lg hover:bg-slate-100 transition">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- Client-side Fast Filter JS -->
<script>
function filterClasses(query) {
    const q = (query || '').toLowerCase().trim();
    const rows = document.querySelectorAll('.class-row');
    let visibleCount = 0;

    rows.forEach(function(row) {
        const name = row.getAttribute('data-class-name') || '';
        if (!q || name.indexOf(q) !== -1) {
            row.style.display = '';
            visibleCount++;
        } else {
            row.style.display = 'none';
        }
    });

    const countElem = document.getElementById('class-count-visible');
    if (countElem) {
        countElem.textContent = visibleCount;
    }
}
</script>
