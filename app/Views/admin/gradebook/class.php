<?php
$className = $class->name . ($class->sectionArm ? ' (' . $class->sectionArm . ')' : '');

$this->layout('layouts/admin', [
    'title'          => "{$className} — Gradebook & Broadsheet",
    'headerTitle'    => "{$className} Gradebook",
    'headerSubtitle' => "Curriculum continuous assessment scores and full student broadsheet.",
]);
?>

<div class="space-y-6 pb-12">

    <!-- Header Card with Navigation, Breadcrumbs & Print Actions -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div class="space-y-1">
                <nav class="flex items-center gap-2 text-xs font-medium text-slate-400 mb-1" aria-label="Breadcrumb">
                    <a href="/admin/dashboard" class="hover:text-brand-700 transition">Dashboard</a>
                    <span>&rsaquo;</span>
                    <a href="/admin/gradebook?session_id=<?= (int)$selectedSessionId ?>&term_id=<?= (int)$selectedTermId ?>" class="hover:text-brand-700 transition">Institutional Gradebook</a>
                    <span>&rsaquo;</span>
                    <span class="text-slate-700 font-semibold"><?= e($className) ?></span>
                </nav>
                <div class="flex flex-wrap items-center gap-3">
                    <h2 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">
                        <?= e($class->name) ?>
                        <?php if ($class->sectionArm): ?>
                            <span class="px-2.5 py-0.5 rounded-md text-xs font-mono font-bold bg-brand-50 text-brand-700 border border-brand-200">
                                <?= e($class->sectionArm) ?>
                            </span>
                        <?php endif; ?>
                    </h2>
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
                    <?= count($students) ?> enrolled students &bull; <?= count($classSubjects) ?> curriculum subjects offered in this cohort.
                </p>
            </div>

            <!-- Top Actions -->
            <div class="flex flex-wrap items-center gap-2.5 flex-shrink-0">
                <a href="/admin/gradebook?session_id=<?= (int)$selectedSessionId ?>&term_id=<?= (int)$selectedTermId ?>"
                   class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-sm font-semibold text-slate-700 bg-slate-50 hover:bg-slate-100 border border-slate-200 transition shadow-xs">
                    <span>&larr; Back to Classes</span>
                </a>
                <button type="button" onclick="window.print()"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-sm font-semibold text-slate-700 bg-slate-50 hover:bg-slate-100 border border-slate-200 transition shadow-xs cursor-pointer">
                    <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                    <span>Print Sheet</span>
                </button>
                <a href="/admin/results/review?term_id=<?= (int)$selectedTermId ?>&class_id=<?= (int)$class->id ?>"
                   class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-sm font-semibold text-white bg-brand-700 hover:bg-brand-800 transition shadow-xs">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    <span>Results Review</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Interactive Subject Navigation Switcher (Tabs) -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-4">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-3">
            <div>
                <h3 class="text-sm font-bold text-slate-900">Select Gradebook View</h3>
                <p class="text-xs text-slate-500">Switch between the full Master Broadsheet (all subjects) and specific subject CA sheets.</p>
            </div>
        </div>

        <!-- Subject Tabs Carousel / Flex Pills -->
        <div class="flex items-center gap-2 overflow-x-auto pb-1 text-sm no-scrollbar">
            <!-- Tab 0: Master Broadsheet (All Subjects) -->
            <a href="/admin/gradebook/class/<?= (int)$class->id ?>?session_id=<?= (int)$selectedSessionId ?>&term_id=<?= (int)$selectedTermId ?>"
               class="inline-flex items-center gap-2 px-4 py-2 rounded-xl font-bold whitespace-nowrap transition <?= empty($selectedSubjectId) ? 'bg-brand-700 text-white shadow-xs' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' ?>">
                <svg class="w-4 h-4 <?= empty($selectedSubjectId) ? 'text-white' : 'text-slate-500' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                <span>Master Broadsheet (All Subjects)</span>
            </a>

            <!-- Specific Subject Pills -->
            <?php foreach ($classSubjects as $cs): ?>
                <?php
                $isActive = (int)$selectedSubjectId === (int)$cs->subjectId;
                $sName = $cs->subject?->name ?? 'Subject';
                $sCode = $cs->subject?->code ?? '';
                ?>
                <a href="/admin/gradebook/class/<?= (int)$class->id ?>?session_id=<?= (int)$selectedSessionId ?>&term_id=<?= (int)$selectedTermId ?>&subject_id=<?= (int)$cs->subjectId ?>"
                   class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-bold whitespace-nowrap transition <?= $isActive ? 'bg-brand-700 text-white shadow-xs' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' ?>">
                    <span><?= e($sName) ?></span>
                    <?php if (!empty($sCode)): ?>
                        <span class="font-mono text-[11px] uppercase opacity-80">[<?= e($sCode) ?>]</span>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- VIEW MODE A: MASTER BROADSHEET (ALL SUBJECTS FOR THIS CLASS ARM) -->
    <!-- ========================================================================= -->
    <?php if (empty($selectedSubjectId) && !empty($broadsheetData)): ?>
        <?php
        $resultsMatrix = $broadsheetData['resultsMatrix'];
        $summaryMap = $broadsheetData['summaryMap'];
        $subjectAverages = $broadsheetData['subjectAverages'];
        ?>
        <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 bg-slate-50/50">
                <div>
                    <h3 class="text-base font-bold text-slate-900">Class Master Broadsheet</h3>
                    <p class="text-xs text-slate-500">Student performance across all <?= count($classSubjects) ?> subjects with cumulative totals and ranks.</p>
                </div>
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-brand-50 text-brand-800 border border-brand-200">
                    <?= count($students) ?> candidates enrolled
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-slate-700 divide-y divide-slate-200" id="broadsheet-table">
                    <thead class="bg-slate-100/80 text-xs font-bold text-slate-600 uppercase tracking-wider">
                        <tr>
                            <th scope="col" class="py-3 px-3 w-10 text-center">#</th>
                            <th scope="col" class="py-3 px-4 min-w-[200px] sticky left-0 bg-slate-100 z-10 shadow-xs">Student Candidate</th>
                            <th scope="col" class="py-3 px-3 min-w-[120px]">Admission No</th>
                            <?php foreach ($classSubjects as $cs): ?>
                                <th scope="col" class="py-3 px-3 text-center min-w-[80px]" title="<?= e($cs->subject?->name ?? '') ?>">
                                    <div class="font-mono text-xs"><?= e($cs->subject?->code ?? substr($cs->subject?->name ?? 'SUB', 0, 3)) ?></div>
                                </th>
                            <?php endforeach; ?>
                            <th scope="col" class="py-3 px-4 text-center min-w-[90px] bg-slate-200/50 font-black">Total</th>
                            <th scope="col" class="py-3 px-4 text-center min-w-[80px] bg-slate-200/50 font-black">Average</th>
                            <th scope="col" class="py-3 px-4 text-center min-w-[70px]">Rank</th>
                            <th scope="col" class="py-3 px-4 min-w-[120px]">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 bg-white">
                        <?php if (empty($students)): ?>
                            <tr>
                                <td colspan="<?= 7 + count($classSubjects) ?>" class="py-8 text-center text-slate-400">
                                    No students are enrolled in this class arm for the current academic session.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php $idx = 1; foreach ($students as $student): ?>
                                <?php
                                $sId = $student->id;
                                $summary = $summaryMap[$sId] ?? null;
                                $studentResults = $resultsMatrix[$sId] ?? [];
                                ?>
                                <tr class="hover:bg-slate-50/80 transition">
                                    <td class="py-3 px-3 text-center text-xs text-slate-400 font-mono"><?= $idx++ ?></td>
                                    <td class="py-3 px-4 font-semibold text-slate-900 sticky left-0 bg-white hover:bg-slate-50 z-10 shadow-xs">
                                        <div class="flex items-center gap-2">
                                            <div class="w-7 h-7 rounded-full bg-brand-50 text-brand-700 font-bold text-xs flex items-center justify-center flex-shrink-0">
                                                <?= strtoupper(substr($student->user?->name ?? 'S', 0, 1)) ?>
                                            </div>
                                            <span class="truncate"><?= e($student->user?->name ?? 'Student #' . $student->id) ?></span>
                                        </div>
                                    </td>
                                    <td class="py-3 px-3 font-mono text-xs text-slate-500">
                                        <span class="px-1.5 py-0.5 rounded bg-slate-100 border border-slate-200 text-[11px]">
                                            <?= e($student->admissionNumber ?: 'N/A') ?>
                                        </span>
                                    </td>

                                    <!-- Subject Scores -->
                                    <?php foreach ($classSubjects as $cs): ?>
                                        <?php
                                        $subRes = $studentResults[$cs->subjectId] ?? null;
                                        $score = $subRes ? (float)$subRes['computed_score'] : null;
                                        $grade = $subRes ? $subRes['grade_letter'] : null;

                                        $scoreColor = 'text-slate-800';
                                        if ($grade === 'A') $scoreColor = 'text-emerald-700 font-bold';
                                        elseif (in_array($grade, ['E', 'F'], true)) $scoreColor = 'text-rose-600 font-bold';
                                        ?>
                                        <td class="py-3 px-3 text-center font-mono text-xs">
                                            <?php if ($score !== null): ?>
                                                <div class="<?= $scoreColor ?>"><?= $score ?></div>
                                                <div class="text-[10px] text-slate-400"><?= e($grade) ?></div>
                                            <?php else: ?>
                                                <span class="text-slate-300">&mdash;</span>
                                            <?php endif; ?>
                                        </td>
                                    <?php endforeach; ?>

                                    <!-- Total, Average, Rank -->
                                    <td class="py-3 px-4 text-center font-mono font-black text-sm bg-slate-50 text-slate-900">
                                        <?= $summary && $summary->totalScore !== null ? (float)$summary->totalScore : '&mdash;' ?>
                                    </td>
                                    <td class="py-3 px-4 text-center font-mono font-bold text-xs bg-slate-50 text-slate-800">
                                        <?= $summary && $summary->averageScore !== null ? (float)$summary->averageScore . '%' : '&mdash;' ?>
                                    </td>
                                    <td class="py-3 px-4 text-center font-bold text-xs">
                                        <?php if ($summary && $summary->rankInClass): ?>
                                            <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-brand-50 text-brand-700 font-extrabold text-[11px]">
                                                <?= (int)$summary->rankInClass ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-slate-300">&mdash;</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-3 px-4 text-xs">
                                        <?php if ($summary && $summary->promotionStatus === 'promoted'): ?>
                                            <span class="px-2 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">Promoted</span>
                                        <?php elseif ($summary && $summary->promotionStatus === 'repeat'): ?>
                                            <span class="px-2 py-0.5 rounded-full text-[11px] font-semibold bg-rose-50 text-rose-700 border border-rose-200">Repeat</span>
                                        <?php else: ?>
                                            <span class="text-slate-400">&mdash;</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>

                            <!-- Broadsheet Footer Summary (Class Means) -->
                            <tr class="bg-slate-100/90 font-bold text-xs text-slate-800 border-t-2 border-slate-300">
                                <td colspan="3" class="py-3 px-4 text-right uppercase tracking-wider font-extrabold">
                                    Subject Class Mean:
                                </td>
                                <?php foreach ($classSubjects as $cs): ?>
                                    <?php $sAvg = $subjectAverages[$cs->subjectId] ?? null; ?>
                                    <td class="py-3 px-3 text-center font-mono">
                                        <?= $sAvg !== null ? $sAvg . '%' : '&mdash;' ?>
                                    </td>
                                <?php endforeach; ?>
                                <td colspan="4" class="py-3 px-4"></td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    <!-- ========================================================================= -->
    <!-- VIEW MODE B: SPECIFIC SUBJECT CONTINUOUS ASSESSMENT SHEET -->
    <!-- ========================================================================= -->
    <?php elseif (!empty($subjectSheetData)): ?>
        <?php
        $scs = $subjectSheetData['classSubject'];
        $categories = $subjectSheetData['categories'];
        $subjectStudents = $subjectSheetData['students'];
        $scoreMatrix = $subjectSheetData['scoreMatrix'];
        $resultMap = $subjectSheetData['resultMap'];
        $isLocked = $subjectSheetData['isLocked'];
        $stats = $subjectSheetData['stats'];
        ?>

        <div class="bg-white rounded-2xl border-2 border-brand-200 shadow-md overflow-hidden transition" id="subject-ca-sheet">
            <!-- Inspection Header Ribbon -->
            <div class="bg-gradient-to-r from-brand-900 to-brand-800 text-white px-6 py-5 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div class="space-y-1">
                    <div class="flex items-center gap-2">
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-brand-700/80 text-brand-100 uppercase tracking-wide">
                            Continuous Assessment Sheet
                        </span>
                        <?php if ($isLocked): ?>
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-500 text-white shadow-2xs">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                Gradebook Locked by Administration
                            </span>
                        <?php else: ?>
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-500 text-white shadow-2xs">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"/></svg>
                                Open for Scoring
                            </span>
                        <?php endif; ?>
                    </div>
                    <h3 class="text-xl sm:text-2xl font-black text-white">
                        <?= e($scs->subject?->name ?? 'Subject') ?>
                        <span class="font-mono text-sm text-brand-300 font-semibold">[<?= e($scs->subject?->code ?? '') ?>]</span>
                    </h3>
                    <p class="text-xs text-brand-200">
                        Assigned Subject Teacher: <strong class="text-white"><?= e($scs->teacher?->name ?? 'Unassigned') ?></strong>
                        <?php if ($scs->teacher?->staffId): ?>
                            (Staff ID: <?= e($scs->teacher->staffId) ?>)
                        <?php endif; ?>
                        &bull; <?= count($subjectStudents) ?> candidates enrolled
                    </p>
                </div>

                <!-- Administrative Lock & Unlock Controls -->
                <div class="flex flex-wrap items-center gap-2.5 flex-shrink-0">
                    <?php if ($isLocked): ?>
                        <form method="POST" action="/admin/gradebook/<?= (int)$scs->id ?>/unlock" onsubmit="return confirm('Unlock gradebook? Teachers will be permitted to modify student scores.');" class="inline">
                            <?= csrf_field() ?>
                            <input type="hidden" name="term_id" value="<?= (int)$selectedTermId ?>">
                            <button type="submit" class="px-4 py-2 rounded-xl text-xs font-bold bg-amber-500 hover:bg-amber-600 text-white transition flex items-center gap-1.5 shadow-xs cursor-pointer">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"/></svg>
                                <span>Unlock Gradebook</span>
                            </button>
                        </form>
                    <?php else: ?>
                        <form method="POST" action="/admin/gradebook/<?= (int)$scs->id ?>/lock" onsubmit="return confirm('Lock gradebook results? This will finalize scores and freeze teacher edits.');" class="inline">
                            <?= csrf_field() ?>
                            <input type="hidden" name="term_id" value="<?= (int)$selectedTermId ?>">
                            <button type="submit" class="px-4 py-2 rounded-xl text-xs font-bold bg-emerald-600 hover:bg-emerald-700 text-white transition flex items-center gap-1.5 shadow-xs cursor-pointer">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                <span>Lock Gradebook</span>
                            </button>
                        </form>
                    <?php endif; ?>

                    <a href="/admin/gradebook/class/<?= (int)$class->id ?>?session_id=<?= (int)$selectedSessionId ?>&term_id=<?= (int)$selectedTermId ?>"
                       class="px-3.5 py-2 rounded-xl text-xs font-bold bg-white text-slate-800 hover:bg-slate-100 transition shadow-xs">
                        &larr; View Broadsheet
                    </a>
                </div>
            </div>

            <!-- Categories Weights Banner -->
            <div class="bg-slate-50 border-b border-slate-200 px-6 py-3 flex flex-wrap items-center gap-4 text-xs text-slate-600">
                <span class="font-bold text-slate-800 uppercase tracking-wide">Grading Formula:</span>
                <?php if (!empty($categories)): ?>
                    <?php foreach ($categories as $cat): ?>
                        <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-white border border-slate-200 shadow-2xs font-medium">
                            <span class="text-slate-900 font-bold"><?= e($cat->name) ?>:</span>
                            <span class="text-brand-700 font-semibold"><?= (float)$cat->weightPercentage ?>%</span>
                            <span class="text-slate-400 text-[11px]">(Max <?= (float)$cat->maxPoints ?>pts)</span>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <span class="text-amber-700 font-medium">No assessment categories configured for this term. Default weighting applies.</span>
                <?php endif; ?>
            </div>

            <!-- Detailed Students Score Table -->
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-slate-700 divide-y divide-slate-200">
                    <thead class="bg-slate-100/75 text-xs font-bold text-slate-600 uppercase tracking-wider">
                        <tr>
                            <th scope="col" class="py-3 px-4 w-12 text-center">#</th>
                            <th scope="col" class="py-3 px-4 min-w-[200px]">Candidate Student</th>
                            <th scope="col" class="py-3 px-4 min-w-[130px]">Admission No</th>
                            <?php foreach ($categories as $cat): ?>
                                <th scope="col" class="py-3 px-4 text-center min-w-[100px]">
                                    <div><?= e($cat->name) ?></div>
                                    <div class="text-[10px] font-mono text-slate-400 font-normal"><?= (float)$cat->weightPercentage ?>%</div>
                                </th>
                            <?php endforeach; ?>
                            <th scope="col" class="py-3 px-4 text-center min-w-[90px]">Total (100)</th>
                            <th scope="col" class="py-3 px-4 text-center min-w-[70px]">Grade</th>
                            <th scope="col" class="py-3 px-4 min-w-[130px]">Remark</th>
                            <th scope="col" class="py-3 px-4 text-center min-w-[80px]">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 bg-white">
                        <?php if (empty($subjectStudents)): ?>
                            <tr>
                                <td colspan="<?= 6 + count($categories) ?>" class="py-8 text-center text-slate-400">
                                    No students are taking this subject for this academic session.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php $idx = 1; foreach ($subjectStudents as $student): ?>
                                <?php
                                $sId = $student->id;
                                $termRes = $resultMap[$sId] ?? null;
                                $computedScore = $termRes?->computedScore;
                                $gradeLetter = $termRes?->gradeLetter;
                                $remark = $termRes?->remark;

                                // Grade badge styling
                                $badgeColor = 'bg-slate-100 text-slate-700 border-slate-200';
                                if ($gradeLetter === 'A') {
                                    $badgeColor = 'bg-emerald-50 text-emerald-700 border-emerald-200 font-bold';
                                } elseif ($gradeLetter === 'B') {
                                    $badgeColor = 'bg-blue-50 text-blue-700 border-blue-200 font-bold';
                                } elseif ($gradeLetter === 'C') {
                                    $badgeColor = 'bg-sky-50 text-sky-700 border-sky-200';
                                } elseif ($gradeLetter === 'D') {
                                    $badgeColor = 'bg-amber-50 text-amber-700 border-amber-200';
                                } elseif (in_array($gradeLetter, ['E', 'F'], true)) {
                                    $badgeColor = 'bg-rose-50 text-rose-700 border-rose-200 font-bold';
                                }
                                ?>
                                <tr class="hover:bg-slate-50/80 transition">
                                    <td class="py-3 px-4 text-center text-xs text-slate-400 font-mono"><?= $idx++ ?></td>
                                    <td class="py-3 px-4 font-semibold text-slate-900">
                                        <div class="flex items-center gap-2.5">
                                            <div class="w-8 h-8 rounded-full bg-brand-50 text-brand-700 font-bold text-xs flex items-center justify-center flex-shrink-0">
                                                <?= strtoupper(substr($student->user?->name ?? 'S', 0, 1)) ?>
                                            </div>
                                            <span class="truncate"><?= e($student->user?->name ?? 'Student #' . $student->id) ?></span>
                                        </div>
                                    </td>
                                    <td class="py-3 px-4 font-mono text-xs text-slate-600">
                                        <span class="px-2 py-0.5 rounded bg-slate-100 border border-slate-200">
                                            <?= e($student->admissionNumber ?: 'N/A') ?>
                                        </span>
                                    </td>
                                    <?php foreach ($categories as $cat): ?>
                                        <?php $rawScore = $scoreMatrix[$sId][$cat->id] ?? null; ?>
                                        <td class="py-3 px-4 text-center font-mono text-xs font-semibold">
                                            <?php if ($rawScore !== null): ?>
                                                <span class="text-slate-900"><?= (float)$rawScore ?></span>
                                            <?php else: ?>
                                                <span class="text-slate-300">&mdash;</span>
                                            <?php endif; ?>
                                        </td>
                                    <?php endforeach; ?>
                                    <td class="py-3 px-4 text-center font-mono text-sm font-extrabold text-slate-900">
                                        <?= $computedScore !== null ? (float)$computedScore : '<span class="text-slate-300 font-normal">&mdash;</span>' ?>
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        <?php if ($gradeLetter): ?>
                                            <span class="inline-flex items-center justify-center w-7 h-7 rounded-lg text-xs border <?= $badgeColor ?>">
                                                <?= e($gradeLetter) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-slate-300 text-xs">&mdash;</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-3 px-4 text-xs text-slate-500 truncate max-w-[160px]">
                                        <?= e($remark ?: '—') ?>
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        <?php if ($termRes && $termRes->isLocked): ?>
                                            <span class="inline-flex items-center text-amber-600" title="Locked">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                            </span>
                                        <?php else: ?>
                                            <span class="inline-flex items-center text-slate-400" title="Open for Scoring">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"/></svg>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

</div>
