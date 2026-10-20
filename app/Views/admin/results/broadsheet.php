<?php
$className = $selectedClass ? ($selectedClass->name . ($selectedClass->sectionArm ? ' (Arm ' . $selectedClass->sectionArm . ')' : '')) : '';
$titleText = $className ? "{$className} — Class Broadsheet Matrix" : "Class Broadsheet Matrix — Claret LMS";

$this->layout('layouts/admin', [
    'title'          => $titleText,
    'headerTitle'    => 'Class Broadsheet Matrix',
    'headerSubtitle' => 'Multi-subject horizontal tabular gradebook with class analytics, CSV export, and print formatting.',
]);

$termOptions = [];
foreach ($terms as $t) {
    $termOptions[$t->id] = $t->name;
}

$classOptions = [];
foreach ($classes as $c) {
    $classOptions[$c->id] = $c->name . ($c->sectionArm ? ' (' . $c->sectionArm . ')' : '');
}

if (!isset($settings)) {
    try {
        $stmt = \App\Core\Database::getInstance()->query("SELECT setting_key, setting_value FROM system_settings");
        $raw = $stmt ? $stmt->fetchAll(PDO::FETCH_KEY_PAIR) : [];
    } catch (\Throwable) {
        $raw = [];
    }
    $settings = [
        'school_name' => $raw['school_name'] ?? 'Claret International School',
        'school_motto' => $raw['school_motto'] ?? 'Discipline, Integrity & Ardour',
        'school_logo_url' => $raw['school_logo_url'] ?? '/assets/img/logo.png',
    ];
}
?>

<div class="space-y-6 pb-12">

    <!-- Unified Review Suite Tab Navigation -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-2 sm:p-3">
        <nav class="flex flex-wrap items-center gap-1.5" aria-label="Review Navigation Tabs">
            <a href="/admin/results/review<?= ($selectedTermId > 0 && $selectedClassId > 0) ? "?term_id={$selectedTermId}&class_id={$selectedClassId}" : '' ?>"
               class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition">
                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                <span>Results Review &amp; Publication</span>
            </a>

            <a href="/admin/results/broadsheet<?= ($selectedTermId > 0 && $selectedClassId > 0) ? "?term_id={$selectedTermId}&class_id={$selectedClassId}" : '' ?>"
               class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-bold text-brand-700 bg-brand-50 border border-brand-200/60 shadow-xs transition" aria-current="page">
                <svg class="w-4 h-4 text-brand-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                <span>Class Broadsheet Matrix</span>
            </a>

            <a href="/admin/results/comments<?= ($selectedTermId > 0 && $selectedClassId > 0) ? "?term_id={$selectedTermId}&class_id={$selectedClassId}" : '' ?>"
               class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition">
                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/></svg>
                <span>Batch Remarks &amp; Ratings</span>
            </a>
        </nav>
    </div>

    <!-- Filter Bar (Screen Only) -->
    <div class="screen-only bg-white rounded-2xl border border-slate-200 shadow-xs p-5">
        <form method="GET" action="/admin/results/broadsheet" class="flex flex-col md:flex-row md:items-end gap-4">
            <div class="w-full md:w-64">
                <?php $this->include('components/select', [
                    'name'     => 'term_id',
                    'id'       => 'filter_term_id',
                    'label'    => 'Academic Term',
                    'options'  => $termOptions,
                    'selected' => $selectedTermId,
                ]); ?>
            </div>

            <div class="w-full md:w-64">
                <?php $this->include('components/select', [
                    'name'        => 'class_id',
                    'id'          => 'filter_class_id',
                    'label'       => 'Class Cohort',
                    'options'     => $classOptions,
                    'selected'    => $selectedClassId ?: '',
                    'placeholder' => '— Select Class Arm —',
                ]); ?>
            </div>

            <div class="flex-shrink-0">
                <?php $this->include('components/button', [
                    'type'    => 'submit',
                    'variant' => 'primary',
                    'label'   => 'Load Broadsheet',
                    'icon'    => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>',
                    'class'   => 'min-h-[44px] w-full md:w-auto',
                ]); ?>
            </div>
        </form>
    </div>

    <?php if ($selectedTerm && $selectedClass): ?>

        <!-- Official Print Header (Print Only) -->
        <div class="print-only hidden pb-4 mb-4 border-b-2 border-slate-800">
            <div class="flex items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <img src="<?= htmlspecialchars($settings['school_logo_url']) ?>" alt="School Crest" class="w-16 h-16 object-contain" onerror="this.style.display='none'">
                    <div>
                        <h1 class="text-2xl font-black tracking-tight text-slate-900 uppercase"><?= htmlspecialchars($settings['school_name']) ?></h1>
                        <p class="text-xs font-semibold text-slate-600 tracking-wider uppercase"><?= htmlspecialchars($settings['school_motto']) ?></p>
                        <p class="text-xs text-slate-500">Official Terminal Academic Broadsheet</p>
                    </div>
                </div>
                <div class="text-right text-xs text-slate-700 space-y-0.5">
                    <p><span class="font-bold">Cohort:</span> <?= e($className) ?></p>
                    <p><span class="font-bold">Term:</span> <?= e($selectedTerm->name) ?></p>
                    <p><span class="font-bold">Form Teacher:</span> <?= e($selectedClass->formTeacherName ?? 'Unassigned') ?></p>
                    <p><span class="font-bold">Generated:</span> <?= date('d M Y, H:i') ?></p>
                </div>
            </div>
        </div>

        <!-- Cohort Intelligence & Actions Card (Screen Only) -->
        <div class="screen-only bg-white rounded-2xl border border-slate-200 shadow-xs p-6">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div class="space-y-1.5">
                    <div class="flex flex-wrap items-center gap-3">
                        <h2 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">
                            <?= e($selectedClass->name) ?>
                            <?php if ($selectedClass->sectionArm): ?>
                                <span class="px-2.5 py-0.5 rounded-md text-xs font-mono font-bold bg-brand-50 text-brand-700 border border-brand-200">
                                    Arm <?= e($selectedClass->sectionArm) ?>
                                </span>
                            <?php endif; ?>
                        </h2>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-sky-50 text-sky-700 border border-sky-200">
                            <?= e($selectedTerm->name) ?>
                        </span>
                        <?php if ($isPublished): ?>
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                Published
                            </span>
                        <?php else: ?>
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-200">
                                <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                Draft Review
                            </span>
                        <?php endif; ?>

                        <?php if (isset($submission) && $submission && $submission->isApproved()): ?>
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                Submission Approved
                            </span>
                        <?php elseif (isset($submission) && $submission && $submission->isSubmitted()): ?>
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                                Form Teacher Submitted
                            </span>
                        <?php endif; ?>
                    </div>

                    <div class="flex flex-wrap items-center gap-4 text-xs sm:text-sm text-slate-500">
                        <div class="flex items-center gap-1.5">
                            <span class="font-medium text-slate-700">Form Teacher:</span>
                            <?php if (!empty($selectedClass->formTeacherName)): ?>
                                <span class="inline-flex items-center gap-1 font-semibold text-slate-900 bg-slate-100 px-2 py-0.5 rounded border border-slate-200">
                                    <?= e($selectedClass->formTeacherName) ?>
                                    <?php if (!empty($selectedClass->formTeacherStaffId)): ?>
                                        <span class="text-[10px] font-mono text-slate-400">(<?= e($selectedClass->formTeacherStaffId) ?>)</span>
                                    <?php endif; ?>
                                </span>
                            <?php else: ?>
                                <span class="italic text-slate-400">Unassigned</span>
                            <?php endif; ?>
                        </div>
                        <span>&bull;</span>
                        <div><strong class="text-slate-700"><?= count($students) ?></strong> Enrolled Candidates</div>
                        <span>&bull;</span>
                        <div><strong class="text-slate-700"><?= count($classSubjects) ?></strong> Offered Subjects</div>
                        <?php if ($classMean !== null): ?>
                            <span>&bull;</span>
                            <div>Class Mean: <strong class="text-brand-700 font-bold"><?= $classMean ?>%</strong></div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Action Toolbar -->
                <div class="flex flex-wrap items-center gap-2.5 flex-shrink-0">
                    <a href="/admin/results/broadsheet/export?term_id=<?= (int)$selectedTermId ?>&class_id=<?= (int)$selectedClassId ?>"
                       class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold text-white bg-brand-700 hover:bg-brand-800 transition shadow-xs">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        <span>Export CSV</span>
                    </a>

                    <button type="button" onclick="window.print()"
                            class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold text-slate-700 bg-slate-50 hover:bg-slate-100 border border-slate-200 transition shadow-xs cursor-pointer">
                        <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                        <span>Print Broadsheet</span>
                    </button>

                    <a href="/admin/results/comments?term_id=<?= (int)$selectedTermId ?>&class_id=<?= (int)$selectedClassId ?>"
                       class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold text-slate-700 bg-slate-50 hover:bg-slate-100 border border-slate-200 transition shadow-xs">
                        <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/></svg>
                        <span>Batch Remarks</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- Broadsheet Matrix Table Container (Visible on screen and print) -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden print:border-none print:shadow-none print:rounded-none">
            <?php if (empty($students)): ?>
                <div class="p-12 text-center">
                    <p class="text-sm text-slate-500">No students are currently enrolled in this class arm for this academic session.</p>
                </div>
            <?php elseif (empty($classSubjects)): ?>
                <div class="p-12 text-center">
                    <p class="text-sm text-slate-500">No curriculum subjects have been assigned to this class cohort yet.</p>
                </div>
            <?php else: ?>
                <!-- Authentic Broadsheet Institutional Banner (Screen & Print) -->
                <div class="p-4 sm:p-5 border-b border-slate-200 bg-slate-50/50">
                    <div class="text-center font-bold text-xs uppercase tracking-wider text-slate-700 pb-2 border-b border-slate-200">
                        | <?= htmlspecialchars($settings['school_name'] ?? 'CLARET INTERNATIONAL SCHOOL, ABUJA') ?>
                    </div>
                    <div class="flex flex-wrap items-center justify-between gap-4 pt-3 text-xs">
                        <span class="text-sm sm:text-base font-black tracking-wide text-slate-900 uppercase font-sans">
                            BROAD SHEET
                        </span>
                        <div class="flex flex-wrap items-center gap-4 sm:gap-8 font-semibold text-slate-700 uppercase text-[11px]">
                            <span><strong>SESSION:</strong> <?= htmlspecialchars($selectedTerm->academicSessionName ?? $selectedTerm->sessionName ?? '2025/2026') ?></span>
                            <span><strong>TERM:</strong> <?= htmlspecialchars($selectedTerm->name ?? 'SUMMER') ?></span>
                            <span><strong>CLASS:</strong> <?= htmlspecialchars($className) ?></span>
                        </div>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full text-left text-xs border-collapse">
                        <!-- 3-Tier Authentic Header matching sample image -->
                        <thead class="bg-slate-100/90 font-bold text-slate-800 uppercase tracking-wider select-none">
                            <!-- Header Row 1: Subject Titles spanning 2 sub-columns -->
                            <tr class="border-b border-slate-300">
                                <th scope="col" rowspan="3" class="sticky left-0 z-20 bg-slate-100 px-2 py-2 text-center border-r border-slate-300 w-10 text-[11px]">POS</th>
                                <th scope="col" rowspan="3" class="sticky left-10 z-20 bg-slate-100 px-2.5 py-2 border-r border-slate-300 w-24 text-[10px] font-mono">ADM NO</th>
                                <th scope="col" rowspan="3" class="sticky left-34 z-20 bg-slate-100 px-3 py-2 border-r border-slate-300 min-w-[160px] text-[11px]">NAMES</th>
                                <th scope="col" rowspan="3" class="px-2 py-2 text-center border-r border-slate-300 w-10 text-[10px]">SEX</th>

                                <?php foreach ($classSubjects as $cs): ?>
                                    <th scope="col" colspan="2" class="px-2 py-1.5 text-center border-r border-slate-300 min-w-[80px] bg-slate-100 text-[11px] font-extrabold text-slate-900" title="<?= e($cs->subject?->name ?? '') ?>">
                                        <?= e(strtoupper(($cs->subject?->code ? $cs->subject->code . ' ' : '') . ($cs->subject?->name ?: 'SUBJECT'))) ?>
                                    </th>
                                <?php endforeach; ?>

                                <th scope="col" rowspan="3" class="px-3 py-2 text-center border-r border-slate-300 w-16 bg-slate-200/70 font-black text-slate-900 text-[11px]">TOTAL</th>
                                <th scope="col" rowspan="3" class="px-3 py-2 text-center border-r border-slate-300 w-16 bg-slate-200/70 font-black text-slate-900 text-[11px]">AVERAGE</th>
                                <th scope="col" rowspan="3" class="px-3 py-2 text-center border-r border-slate-300 w-16 bg-slate-200/70 font-black text-slate-900 text-[11px]">POSITION</th>
                                <th scope="col" rowspan="3" class="px-3 py-2 border-slate-300 min-w-[180px] bg-slate-100 text-[11px] font-semibold text-slate-700">REMARK</th>
                            </tr>

                            <!-- Header Row 2: CA | EXAM Subheaders -->
                            <tr class="bg-slate-100 border-b border-slate-200 text-[9.5px]">
                                <?php foreach ($classSubjects as $cs): ?>
                                    <th scope="col" class="px-1.5 py-1 text-center border-r border-slate-300 font-extrabold text-slate-700 w-10">CA</th>
                                    <th scope="col" class="px-1.5 py-1 text-center border-r border-slate-300 font-extrabold text-slate-700 w-10">EXAM</th>
                                <?php endforeach; ?>
                            </tr>

                            <!-- Header Row 3: TOTAL | POSITION Subheaders -->
                            <tr class="bg-slate-200/60 border-b-2 border-slate-400 text-[9px]">
                                <?php foreach ($classSubjects as $cs): ?>
                                    <th scope="col" class="px-1.5 py-1 text-center border-r border-slate-300 font-black text-slate-800 w-10">TOTAL</th>
                                    <th scope="col" class="px-1.5 py-1 text-center border-r border-slate-300 font-black text-slate-800 w-10">POSITION</th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-200 bg-white font-sans text-slate-700">
                            <?php foreach ($students as $idx => $st): ?>
                                <?php
                                $sm = $summaryMap[$st->id] ?? null;
                                $rankNum = $sm?->classRank ?? ($idx + 1);

                                // Compute student subject scores and totals
                                $studentScores = [];
                                $subDataList = [];
                                foreach ($classSubjects as $cs) {
                                    $subRes = $resultsMatrix[$st->id][$cs->subjectId] ?? $resultsMatrix[$st->id][$cs->id] ?? null;
                                    $scoreVal = $subRes ? (float)($subRes['computed_score'] ?? $subRes['total_score'] ?? 0) : null;
                                    $caVal = null;
                                    $examVal = null;

                                    if ($scoreVal !== null) {
                                        $studentScores[] = $scoreVal;
                                        if (!empty($subRes['breakdown_json'])) {
                                            $bd = json_decode($subRes['breakdown_json'], true);
                                            if (!empty($bd['categories'])) {
                                                $cScore = 0.0;
                                                $eScore = 0.0;
                                                foreach ($bd['categories'] as $cat) {
                                                    $cName = strtolower($cat['name'] ?? '');
                                                    $w = (float)($cat['weighted_contribution'] ?? $cat['raw_score'] ?? 0);
                                                    if (str_contains($cName, 'exam') || str_contains($cName, 'term test') || str_contains($cName, 'eta') || str_contains($cName, 'esa')) {
                                                        $eScore += $w;
                                                    } else {
                                                        $cScore += $w;
                                                    }
                                                }
                                                $caVal = $cScore;
                                                $examVal = $eScore;
                                            }
                                        }
                                        if ($caVal === null && $examVal === null) {
                                            $caVal = round($scoreVal * 0.4, 1);
                                            $examVal = round($scoreVal - $caVal, 1);
                                        }
                                    }

                                    $subPos = $subjectRanks[$cs->id][$st->id] ?? null;

                                    $subDataList[$cs->id] = [
                                        'ca' => $caVal,
                                        'exam' => $examVal,
                                        'total' => $scoreVal,
                                        'position' => $subPos,
                                    ];
                                }

                                $rowTotal = !empty($studentScores) ? array_sum($studentScores) : ($sm?->totalScore !== null ? (float)$sm->totalScore : null);
                                $rowAvg = !empty($studentScores) ? ($rowTotal / count($studentScores)) : ($sm?->averageScore !== null ? (float)$sm->averageScore : null);
                                ?>

                                <!-- Student Row 1 (CA | EXAM on line 1) -->
                                <?php
                                $rankNum = $sm?->rankInClass ?? $sm?->classRank ?? ($idx + 1);
                                $rankVal = $sm?->rankInClass ?? $sm?->classRank ?? null;
                                ?>
                                <tr class="hover:bg-slate-50/70 transition border-t border-slate-300">
                                    <td rowspan="2" class="sticky left-0 z-10 bg-white px-2 py-1.5 text-center font-bold text-slate-800 border-r border-b border-slate-300 text-xs">
                                        <?php if ($rankNum === 1): ?>
                                             <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-amber-100 text-amber-800 text-[11px] font-black">1</span>
                                        <?php elseif ($rankNum === 2): ?>
                                             <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-slate-200 text-slate-800 text-[11px] font-black">2</span>
                                        <?php elseif ($rankNum === 3): ?>
                                             <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-orange-100 text-orange-800 text-[11px] font-black">3</span>
                                        <?php else: ?>
                                             <span class="text-slate-500 font-mono text-[11px]"><?= $rankNum ?></span>
                                        <?php endif; ?>
                                    </td>

                                    <td rowspan="2" class="sticky left-10 z-10 bg-white px-2.5 py-1.5 font-mono text-[10.5px] text-slate-600 border-r border-b border-slate-300 whitespace-nowrap">
                                        <?= e($st->admissionNumber) ?>
                                    </td>

                                    <td rowspan="2" class="sticky left-34 z-10 bg-white px-3 py-1.5 font-bold text-slate-900 border-r border-b border-slate-300 whitespace-nowrap text-xs">
                                        <?= e(strtoupper($st->name)) ?>
                                    </td>

                                    <td rowspan="2" class="px-2 py-1.5 text-center text-[10.5px] text-slate-500 border-r border-b border-slate-300 uppercase font-bold">
                                        <?= $st->gender ? substr($st->gender, 0, 1) : '—' ?>
                                    </td>

                                    <?php foreach ($classSubjects as $cs):
                                        $d = $subDataList[$cs->id];
                                    ?>
                                        <td class="px-1.5 py-1 text-center border-r border-slate-200 font-mono text-[11px] text-slate-800 bg-white">
                                            <?= $d['ca'] !== null ? number_format($d['ca'], 0) : '—' ?>
                                        </td>
                                        <td class="px-1.5 py-1 text-center border-r border-slate-300 font-mono text-[11px] text-slate-800 bg-white">
                                            <?= $d['exam'] !== null ? number_format($d['exam'], 0) : '—' ?>
                                        </td>
                                    <?php endforeach; ?>

                                    <td rowspan="2" class="px-2 py-1.5 text-center font-mono font-extrabold text-slate-900 bg-slate-100/70 border-r border-b border-slate-300 text-xs">
                                        <?= $rowTotal !== null ? number_format($rowTotal, 0) : '—' ?>
                                    </td>

                                    <td rowspan="2" class="px-2 py-1.5 text-center font-mono font-black text-brand-700 bg-slate-100/70 border-r border-b border-slate-300 text-xs">
                                        <?= $rowAvg !== null ? number_format($rowAvg, 1) : '—' ?>
                                    </td>

                                    <td rowspan="2" class="px-2 py-1.5 text-center font-black text-slate-900 bg-slate-100/70 border-r border-b border-slate-300 text-xs font-mono">
                                        <?= $rankVal ? (is_numeric($rankVal) ? ((function($n){ $e=['TH','ST','ND','RD','TH','TH','TH','TH','TH','TH']; return (($n%100)>=11&&($n%100)<=13)?($n.'TH'):($n.($e[$n%10]??'TH')); })((int)$rankVal)) : $rankVal) : '—' ?>
                                    </td>

                                    <td rowspan="2" class="px-3 py-1.5 text-slate-600 text-xs italic truncate max-w-xs border-b border-slate-300">
                                        <?= !empty($sm?->classTeacherRemark) ? e($sm->classTeacherRemark) : '<span class="text-slate-300 not-italic">—</span>' ?>
                                    </td>
                                </tr>

                                <!-- Student Row 2 (TOTAL | POSITION on line 2) -->
                                <tr class="hover:bg-slate-50/70 transition border-b border-slate-300 bg-slate-50/40">
                                    <?php foreach ($classSubjects as $cs):
                                        $d = $subDataList[$cs->id];
                                    ?>
                                        <td class="px-1.5 py-1 text-center border-r border-slate-200 font-mono font-bold text-[11px] <?= ($d['total'] !== null && $d['total'] < 40) ? 'text-rose-600' : 'text-slate-900' ?>">
                                            <?= $d['total'] !== null ? number_format($d['total'], 0) : '—' ?>
                                        </td>
                                        <td class="px-1.5 py-1 text-center border-r border-slate-300 font-bold text-[10px] text-slate-800 font-mono">
                                            <?= $d['position'] !== null ? $d['position'] : '—' ?>
                                        </td>
                                    <?php endforeach; ?>
                                </tr>

                            <?php endforeach; ?>
                        </tbody>

                        <tfoot class="bg-slate-100 font-bold text-slate-800 text-xs border-t-2 border-slate-400">
                            <!-- Subject Averages Row -->
                            <tr>
                                <td colspan="4" class="sticky left-0 z-10 bg-slate-100 px-3 py-2 font-bold text-slate-900 uppercase tracking-wider text-right border-r border-slate-300 text-[11px]">
                                    Subject Class Mean:
                                </td>
                                <?php foreach ($classSubjects as $cs): ?>
                                    <?php
                                    $stats = $subjectAverages[$cs->subjectId] ?? null;
                                    $avg = $stats['avg'] ?? null;
                                    ?>
                                    <td colspan="2" class="px-2 py-2 text-center font-mono font-black text-slate-900 border-r border-slate-300 text-[11px]">
                                        <?= $avg !== null ? number_format((float)$avg, 1) : '—' ?>
                                    </td>
                                <?php endforeach; ?>
                                <td colspan="4" class="px-3 py-2 text-slate-700 text-xs font-semibold">
                                    <?= $classMean !== null ? "Overall Class Mean: <strong class=\"text-brand-700\">{$classMean}%</strong>" : '' ?>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- Official Sign-off & Endorsements (Print Only) -->
        <div class="print-only hidden pt-8 mt-6 border-t border-slate-300">
            <div class="grid grid-cols-2 gap-12 text-xs text-slate-800">
                <div class="space-y-12">
                    <p class="font-bold text-slate-700">Form Teacher (Class Master):</p>
                    <div class="border-t border-slate-500 pt-1.5 flex justify-between">
                        <span><?= e($selectedClass->formTeacherName ?? 'Name: ______________________') ?></span>
                        <span class="italic text-slate-400">Signature / Date</span>
                    </div>
                </div>
                <div class="space-y-12">
                    <p class="font-bold text-slate-700">Principal / Vice-Principal (Academics):</p>
                    <div class="border-t border-slate-500 pt-1.5 flex justify-between">
                        <span>Name: _______________________________</span>
                        <span class="italic text-slate-400">Signature / Stamp / Date</span>
                    </div>
                </div>
            </div>
        </div>

    <?php else: ?>
        <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-12 text-center space-y-3">
            <div class="w-12 h-12 rounded-full bg-brand-50 text-brand-700 flex items-center justify-center mx-auto">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            </div>
            <h3 class="text-base font-bold text-slate-900">Select Term &amp; Class Cohort</h3>
            <p class="text-sm text-slate-500 max-w-md mx-auto">Please choose an academic term and class arm from the filter above to generate the full multi-subject broadsheet matrix.</p>
        </div>
    <?php endif; ?>

</div>

<style>
@media print {
    @page {
        size: A4 landscape;
        margin: 6mm;
    }
    html, body {
        background: white !important;
        color: #0f172a !important;
        font-size: 7.5pt !important;
        margin: 0 !important;
        padding: 0 !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }
    .screen-only, aside, header, nav, #sidebar-navigation, [role="navigation"] {
        display: none !important;
    }
    .print-only {
        display: block !important;
    }
    main {
        padding: 0 !important;
        margin: 0 !important;
        overflow: visible !important;
        width: 100% !important;
    }
    .overflow-x-auto {
        overflow: visible !important;
    }
    table {
        width: 100% !important;
        font-size: 6.5pt !important;
        border-collapse: collapse !important;
        table-layout: auto !important;
    }
    thead {
        display: table-header-group !important;
    }
    tbody tr {
        page-break-inside: avoid !important;
        break-inside: avoid !important;
    }
    th, td {
        padding: 2px 2.5px !important;
        border: 1px solid #94a3b8 !important;
        word-break: normal !important;
        white-space: nowrap !important;
    }
    .sticky {
        position: static !important;
    }
}
</style>
