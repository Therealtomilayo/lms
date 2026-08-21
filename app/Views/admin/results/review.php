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
    $classOptions[$c->id] = $c->name;
}
?>

<div class="space-y-6">

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
                    'variant' => 'secondary',
                    'label'   => 'View Class Results',
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

                <div class="flex items-center gap-3">
                    <span class="text-sm font-bold text-slate-700">Publication Status:</span>
                    <?php if ($isPublished): ?>
                        <?php $this->include('components/badge', [
                            'label'   => 'Published to Students & Guardians',
                            'variant' => 'success',
                        ]); ?>
                    <?php else: ?>
                        <?php $this->include('components/badge', [
                            'label'   => 'Unpublished — Draft Review',
                            'variant' => 'warning',
                        ]); ?>
                    <?php endif; ?>
                </div>

                <div class="flex flex-wrap items-center gap-3">

                    <!-- Recompute & Rank -->
                    <form method="POST" action="/admin/results/compute">
                        <?= csrf_field() ?>
                        <input type="hidden" name="term_id"  value="<?= e((string)$selectedTermId) ?>">
                        <input type="hidden" name="class_id" value="<?= e((string)$selectedClassId) ?>">
                        <?php $this->include('components/button', [
                            'type'    => 'submit',
                            'variant' => 'secondary',
                            'label'   => 'Recompute & Rank Class',
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
