<?php
$this->layout('layouts/admin', [
    'title'          => 'Result Review & Publication — Claret LMS',
    'headerTitle'    => 'Result Review & Publication',
    'headerSubtitle' => 'Review computed subject grades, calculate class rankings, lock terms, and publish report cards.',
]);

$termOptions  = [];
foreach ($terms as $t) {
    $termOptions[$t->id] = $t->name;
}

$classOptions = [];
foreach ($classes as $c) {
    $classOptions[$c->id] = method_exists($c, 'getFullName') ? $c->getFullName() : ($c->name . (!empty($c->sectionArm) ? ' (' . $c->sectionArm . ')' : ''));
}
?>

<div class="space-y-6 pb-12">

    <!-- Unified Review Suite Tab Navigation -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-2 sm:p-3">
        <nav class="flex flex-wrap items-center gap-1.5" aria-label="Review Navigation Tabs">
            <a href="/admin/results/review<?= ($selectedTermId > 0 && $selectedClassId > 0) ? "?term_id={$selectedTermId}&class_id={$selectedClassId}" : '' ?>"
               class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-bold text-brand-700 bg-brand-50 border border-brand-200/60 shadow-xs transition" aria-current="page">
                <svg class="w-4 h-4 text-brand-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                <span>Results Review &amp; Publication</span>
            </a>

            <a href="/admin/results/broadsheet<?= ($selectedTermId > 0 && $selectedClassId > 0) ? "?term_id={$selectedTermId}&class_id={$selectedClassId}" : '' ?>"
               class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition">
                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                <span>Class Broadsheet Matrix</span>
            </a>

            <a href="/admin/results/comments<?= ($selectedTermId > 0 && $selectedClassId > 0) ? "?term_id={$selectedTermId}&class_id={$selectedClassId}" : '' ?>"
               class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition">
                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/></svg>
                <span>Batch Remarks &amp; Ratings</span>
            </a>
        </nav>
    </div>

    <!-- Filter Bar -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
        <form method="GET" action="/admin/results/review"
              class="flex flex-col md:flex-row md:items-end gap-4">

            <div class="w-full md:w-64">
                <?php $this->include('components/select', [
                    'name'    => 'term_id',
                    'id'      => 'filter_term_id',
                    'label'   => 'Academic Term',
                    'options' => $termOptions,
                    'selected'=> $selectedTermId,
                ]); ?>
            </div>

            <div class="w-full md:w-64">
                <?php $this->include('components/select', [
                    'name'       => 'class_id',
                    'id'         => 'filter_class_id',
                    'label'      => 'Class',
                    'options'    => $classOptions,
                    'selected'   => $selectedClassId ?: '',
                    'placeholder'=> '— Select Class —',
                ]); ?>
            </div>

            <div class="flex-shrink-0">
                <?php $this->include('components/button', [
                    'type'    => 'submit',
                    'variant' => 'primary',
                    'label'   => 'View Class Results',
                    'icon'    => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>',
                    'class'   => 'min-h-[44px] w-full md:w-auto',
                ]); ?>
            </div>
        </form>
    </div>

    <?php if ($selectedTermId > 0 && $selectedClassId > 0): ?>

        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-6">

            <!-- Publication Status Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4
                        pb-4 border-b border-slate-200">

                <div class="flex flex-wrap items-center gap-4">
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Form Teacher Submission:</span>
                        <?php if (isset($submission) && $submission && $submission->isApproved()): ?>
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                Approved
                            </span>
                        <?php elseif (isset($submission) && $submission && $submission->isSubmitted()): ?>
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                Submitted <?= !empty($submission->teacherName) ? 'by ' . e($submission->teacherName) : '' ?>
                            </span>
                        <?php else: ?>
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-600 border border-slate-200">
                                Pending Submission
                            </span>
                        <?php endif; ?>
                    </div>

                    <div class="flex items-center gap-2">
                        <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Publication:</span>
                        <?php if ($isPublished): ?>
                            <?php $this->include('components/badge', [
                                'label'   => 'Published',
                                'variant' => 'success',
                            ]); ?>
                        <?php else: ?>
                            <?php $this->include('components/badge', [
                                'label'   => 'Unpublished (Draft)',
                                'variant' => 'warning',
                            ]); ?>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <?php if (isset($submission) && $submission && $submission->isSubmitted()): ?>
                        <form method="POST" action="/admin/results/approve-submission">
                            <?= csrf_field() ?>
                            <input type="hidden" name="term_id"  value="<?= e((string)$selectedTermId) ?>">
                            <input type="hidden" name="class_id" value="<?= e((string)$selectedClassId) ?>">
                            <?php $this->include('components/button', [
                                'type'    => 'submit',
                                'variant' => 'primary',
                                'label'   => 'Approve Submission',
                                'icon'    => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>',
                            ]); ?>
                        </form>
                    <?php endif; ?>

                    <!-- Recompute & Rank -->
                    <form method="POST" action="/admin/results/compute">
                        <?= csrf_field() ?>
                        <input type="hidden" name="term_id"  value="<?= e((string)$selectedTermId) ?>">
                        <input type="hidden" name="class_id" value="<?= e((string)$selectedClassId) ?>">
                        <?php $this->include('components/button', [
                            'type'    => 'submit',
                            'variant' => 'secondary',
                            'label'   => 'Recompute & Rank Class',
                            'icon'    => '<svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>',
                        ]); ?>
                    </form>

                    <?php if (!$isPublished): ?>
                        <!-- Publish -->
                        <form method="POST" action="/admin/results/publish">
                            <?= csrf_field() ?>
                            <input type="hidden" name="term_id"  value="<?= e((string)$selectedTermId) ?>">
                            <input type="hidden" name="class_id" value="<?= e((string)$selectedClassId) ?>">
                            <?php $this->include('components/button', [
                                'type'    => 'submit',
                                'variant' => 'primary',
                                'label'   => 'Publish Results',
                                'icon'    => '<svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>',
                            ]); ?>
                        </form>
                    <?php else: ?>
                        <!-- Unpublish -->
                        <form method="POST" action="/admin/results/unpublish">
                            <?= csrf_field() ?>
                            <input type="hidden" name="term_id"  value="<?= e((string)$selectedTermId) ?>">
                            <input type="hidden" name="class_id" value="<?= e((string)$selectedClassId) ?>">
                            <input type="hidden" name="reason"   value="Administrative Review">
                            <?php $this->include('components/button', [
                                'type'    => 'submit',
                                'variant' => 'danger',
                                'label'   => 'Unpublish Results',
                                'icon'    => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>',
                            ]); ?>
                        </form>
                    <?php endif; ?>

                </div>
            </div>

            <!-- Student Summary Ranking Table -->
            <?php if (empty($summaries)): ?>
                <?php $this->include('components/empty_state', [
                    'title'   => 'No Results Computed',
                    'message' => 'No summaries have been computed yet. Click "Recompute & Rank Class" to generate rankings for this class and term.',
                ]); ?>
            <?php else: ?>
                <div class="overflow-x-auto rounded-lg border border-slate-200">
                    <table class="w-full text-left text-sm border-collapse">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-200
                                       text-xs font-semibold text-slate-600 uppercase tracking-wider">
                                <th class="py-3 px-3 text-center">Rank</th>
                                <th class="py-3 px-4">Student</th>
                                <th class="py-3 px-3">Admission No.</th>
                                <th class="py-3 px-3 text-center">Total Score</th>
                                <th class="py-3 px-3 text-center">Average (%)</th>
                                <th class="py-3 px-3 text-center">GPA</th>
                                <th class="py-3 px-3 text-right">Report Card</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 font-medium text-slate-700">
                            <?php foreach ($summaries as $s): ?>
                                <tr class="hover:bg-slate-50/50 transition">
                                    <td class="py-3 px-3 text-center">
                                        <span class="inline-flex items-center justify-center w-8 h-8
                                                     rounded-full bg-brand-50 text-brand-700
                                                     font-bold text-xs border border-brand-200">
                                            <?= e((string)$s->rankInClass) ?>
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 font-bold text-slate-900">
                                        <?= e($s->student?->user?->name ?? 'Student') ?>
                                    </td>
                                    <td class="py-3 px-3 text-xs text-slate-500 font-mono">
                                        <?= e($s->student?->admissionNumber ?? '—') ?>
                                    </td>
                                    <td class="py-3 px-3 text-center font-semibold font-mono">
                                        <?= number_format((float)$s->totalScore, 2) ?>
                                    </td>
                                    <td class="py-3 px-3 text-center font-bold text-slate-900 font-mono">
                                        <?= number_format((float)$s->averageScore, 2) ?>%
                                    </td>
                                    <td class="py-3 px-3 text-center font-semibold font-mono">
                                        <?= $s->gpa !== null ? number_format((float)$s->gpa, 2) : '&mdash;' ?>
                                    </td>
                                    <td class="py-3 px-3 text-right">
                                        <a href="/admin/reports/student/<?= $s->studentId ?>/<?= $selectedTermId ?>.pdf"
                                           target="_blank"
                                           class="inline-flex items-center gap-1.5 text-xs
                                                  text-brand-600 hover:text-brand-800
                                                  font-semibold transition">
                                            <svg class="w-4 h-4 flex-shrink-0" fill="none"
                                                 stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                      stroke-width="2"
                                                      d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                            </svg>
                                            PDF Report
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

        </div>

    <?php else: ?>

        <!-- No context selected yet -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-10">
            <?php $this->include('components/empty_state', [
                'title'   => 'Select a Term and Class',
                'message' => 'Use the filters above to choose an academic term and class, then click "View Class Results".',
            ]); ?>
        </div>

    <?php endif; ?>

</div>
