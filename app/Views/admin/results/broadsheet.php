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
                    <img src="/assets/img/logo.png" alt="School Crest" class="w-16 h-16 object-contain" onerror="this.style.display='none'">
                    <div>
                        <h1 class="text-2xl font-black tracking-tight text-slate-900 uppercase">Claret International School</h1>
                        <p class="text-xs font-semibold text-slate-600 tracking-wider uppercase">Excellence &bull; Integrity &bull; Discipline</p>
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

        <!-- Broadsheet Matrix Table Container -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
            <?php if (empty($students)): ?>
                <div class="p-12 text-center">
                    <p class="text-sm text-slate-500">No students are currently enrolled in this class arm for this academic session.</p>
                </div>
            <?php elseif (empty($classSubjects)): ?>
                <div class="p-12 text-center">
                    <p class="text-sm text-slate-500">No curriculum subjects have been assigned to this class cohort yet.</p>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-left text-xs border-collapse divide-y divide-slate-200">
                        <thead class="bg-slate-50 font-semibold text-slate-700 uppercase tracking-wider">
                            <tr>
                                <th scope="col" class="sticky left-0 z-20 bg-slate-50 px-3 py-3 text-center border-r border-slate-200 w-12">Pos</th>
                                <th scope="col" class="sticky left-12 z-20 bg-slate-50 px-3 py-3 border-r border-slate-200 w-28">Adm No</th>
                                <th scope="col" class="sticky left-40 z-20 bg-slate-50 px-4 py-3 border-r border-slate-200 min-w-[180px]">Student Name</th>
                                <th scope="col" class="px-2.5 py-3 text-center border-r border-slate-200 w-12">Sex</th>

                                <?php foreach ($classSubjects as $cs): ?>
                                    <th scope="col" class="px-3 py-3 text-center border-r border-slate-200 min-w-[70px]" title="<?= e($cs->subject?->name ?? '') ?>">
                                        <span class="block font-bold text-slate-900"><?= e($cs->subject?->code ?: 'SUB') ?></span>
                                        <span class="text-[9px] text-slate-400 font-normal lowercase truncate block max-w-[65px] mx-auto"><?= e($cs->subject?->name ?? '') ?></span>
                                    </th>
                                <?php endforeach; ?>

                                <th scope="col" class="px-3 py-3 text-center border-r border-slate-200 w-20 bg-slate-100/60 font-bold">Total</th>
                                <th scope="col" class="px-3 py-3 text-center border-r border-slate-200 w-20 bg-slate-100/60 font-bold">Avg %</th>
                                <th scope="col" class="px-3 py-3 text-center border-r border-slate-200 w-16 bg-slate-100/60 font-bold">Rank</th>
                                <th scope="col" class="px-4 py-3 border-slate-200 min-w-[220px]">Form Teacher Comment</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 bg-white font-sans text-slate-700">
                            <?php foreach ($students as $idx => $st): ?>
                                <?php
                                $sm = $summaryMap[$st->id] ?? null;
                                $rankNum = $sm?->classRank ?? ($idx + 1);
                                ?>
                                <tr class="hover:bg-slate-50/70 transition">
                                    <td class="sticky left-0 z-10 bg-white px-3 py-3 text-center font-bold text-slate-900 border-r border-slate-200">
                                        <?php if ($rankNum === 1): ?>
                                            <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-amber-100 text-amber-800 text-xs font-black">1</span>
                                        <?php elseif ($rankNum === 2): ?>
                                            <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-slate-200 text-slate-800 text-xs font-black">2</span>
                                        <?php elseif ($rankNum === 3): ?>
                                            <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-orange-100 text-orange-800 text-xs font-black">3</span>
                                        <?php else: ?>
                                            <span class="text-slate-500 font-mono text-xs"><?= $rankNum ?></span>
                                        <?php endif; ?>
                                    </td>

                                    <td class="sticky left-12 z-10 bg-white px-3 py-3 font-mono text-[11px] text-slate-600 border-r border-slate-200 whitespace-nowrap">
                                        <?= e($st->admissionNumber) ?>
                                    </td>

                                    <td class="sticky left-40 z-10 bg-white px-4 py-3 font-semibold text-slate-900 border-r border-slate-200 whitespace-nowrap">
                                        <?= e($st->name) ?>
                                    </td>

                                    <td class="px-2.5 py-3 text-center text-[11px] text-slate-500 border-r border-slate-200 uppercase">
                                        <?= $st->gender ? substr($st->gender, 0, 1) : '—' ?>
                                    </td>

                                    <?php foreach ($classSubjects as $cs): ?>
                                        <?php
                                        $subRes = $resultsMatrix[$st->id][$cs->subjectId] ?? null;
                                        $score = $subRes ? (float)($subRes['computed_score'] ?? $subRes['total_score'] ?? 0) : null;
                                        $grade = $subRes['grade_letter'] ?? null;
                                        ?>
                                        <td class="px-2 py-3 text-center border-r border-slate-200 whitespace-nowrap font-mono text-xs">
                                            <?php if ($score !== null): ?>
                                                <div class="flex items-center justify-center gap-1">
                                                    <span class="font-bold <?= $score < 40 ? 'text-rose-600' : 'text-slate-800' ?>">
                                                        <?= number_format($score, 0) ?>
                                                    </span>
                                                    <?php if ($grade): ?>
                                                        <span class="text-[9px] font-sans font-bold px-1 rounded <?= in_array($grade, ['A', 'A+']) ? 'bg-emerald-100 text-emerald-800' : (in_array($grade, ['B', 'C']) ? 'bg-sky-100 text-sky-800' : ($grade === 'D' ? 'bg-amber-100 text-amber-800' : 'bg-rose-100 text-rose-800')) ?>">
                                                            <?= e($grade) ?>
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                            <?php else: ?>
                                                <span class="text-slate-300 italic">—</span>
                                            <?php endif; ?>
                                        </td>
                                    <?php endforeach; ?>

                                    <td class="px-3 py-3 text-center font-mono font-extrabold text-slate-900 bg-slate-50/50 border-r border-slate-200">
                                        <?= $sm ? number_format((float)$sm->totalScore, 1) : '—' ?>
                                    </td>

                                    <td class="px-3 py-3 text-center font-mono font-black text-brand-700 bg-slate-50/50 border-r border-slate-200 text-xs">
                                        <?= $sm && $sm->averageScore !== null ? number_format((float)$sm->averageScore, 1) . '%' : '—' ?>
                                    </td>

                                    <td class="px-3 py-3 text-center font-bold text-slate-800 bg-slate-50/50 border-r border-slate-200">
                                        <?= $sm?->classRank ?? '—' ?>
                                    </td>

                                    <td class="px-4 py-3 text-slate-600 text-xs italic truncate max-w-xs">
                                        <?= !empty($sm?->classTeacherRemark) ? e($sm->classTeacherRemark) : '<span class="text-slate-300 not-italic">—</span>' ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="bg-slate-100/90 font-semibold text-slate-800 text-xs border-t-2 border-slate-300">
                            <!-- Subject Averages Row -->
                            <tr>
                                <td colspan="4" class="sticky left-0 z-10 bg-slate-100 px-4 py-3 font-bold text-slate-900 uppercase tracking-wider text-right border-r border-slate-200">
                                    Subject Class Mean:
                                </td>
                                <?php foreach ($classSubjects as $cs): ?>
                                    <?php
                                    $stats = $subjectAverages[$cs->subjectId] ?? null;
                                    $avg = $stats['avg'] ?? null;
                                    ?>
                                    <td class="px-2 py-3 text-center font-mono font-bold text-slate-800 border-r border-slate-200">
                                        <?= $avg !== null ? number_format((float)$avg, 1) : '—' ?>
                                    </td>
                                <?php endforeach; ?>
                                <td colspan="4" class="px-4 py-3 text-slate-500 text-xs">
                                    <?= $classMean !== null ? "Overall Class Mean: <strong>{$classMean}%</strong>" : '' ?>
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
        margin: 10mm;
    }
    body {
        background: white !important;
        color: black !important;
        font-size: 9pt !important;
    }
    .screen-only, aside, header, nav {
        display: none !important;
    }
    .print-only {
        display: block !important;
    }
    table {
        width: 100% !important;
        font-size: 8pt !important;
        border-collapse: collapse !important;
    }
    th, td {
        padding: 3px 4px !important;
        border: 1px solid #cbd5e1 !important;
    }
    .sticky {
        position: static !important;
    }
}
</style>
