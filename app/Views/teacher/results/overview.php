<?php
$titleText = $selectedClass ? ($selectedClass->getFullName() . ' — Class Results Broadsheet') : 'Form Class Results Broadsheet — Claret LMS';

$sessionOptions = [];
foreach ($sessions as $s) {
    $sessionOptions[$s->id] = $s->name;
}

$termOptions = [];
foreach ($terms as $t) {
    $termOptions[$t->id] = $t->name;
}

$classOptions = [];
foreach ($formClasses as $c) {
    $classOptions[$c->id] = method_exists($c, 'getFullName') ? $c->getFullName() : ($c->name . (!empty($c->sectionArm) ? ' (' . $c->sectionArm . ')' : ''));
}
?>

<div class="space-y-6 pb-12">
    <!-- Tab Navigation between Overview and Batch Remarks -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-2 sm:p-3">
        <nav class="flex flex-wrap items-center gap-1.5" aria-label="Results Navigation Tabs">
            <a href="/teacher/results/overview<?= ($selectedTermId > 0 && $selectedClassId > 0) ? "?term_id={$selectedTermId}&class_id={$selectedClassId}" : '' ?>"
               class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-bold text-brand-700 bg-brand-50 border border-brand-200/60 shadow-xs transition" aria-current="page">
                <svg class="w-4 h-4 text-brand-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                <span>Form Class Broadsheet Overview</span>
            </a>

            <a href="/teacher/results/comments<?= ($selectedTermId > 0 && $selectedClassId > 0) ? "?term_id={$selectedTermId}&class_id={$selectedClassId}" : '' ?>"
               class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition">
                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/></svg>
                <span>Batch Remarks &amp; Behavioral Ratings</span>
            </a>
        </nav>
    </div>

    <?php if (empty($formClasses)): ?>
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-8 text-center space-y-4">
            <div class="w-16 h-16 rounded-full bg-slate-100 text-slate-400 mx-auto flex items-center justify-center">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
            </div>
            <div class="max-w-md mx-auto space-y-2">
                <h3 class="text-lg font-bold text-slate-900">Form Teacher Class Assignment Required</h3>
                <p class="text-sm text-slate-500 leading-relaxed">
                    You are currently configured as a Subject Teacher. Comprehensive multi-subject class broadsheets and terminal result submissions are reserved for designated Class / Form Teachers.
                </p>
            </div>
            <div class="pt-2">
                <a href="/teacher/gradebook" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    <span>Go to My Subject Gradebooks</span>
                </a>
            </div>
        </div>
    <?php else: ?>

        <!-- Context Filter Bar -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
            <form method="GET" action="/teacher/results/overview" class="flex flex-col md:flex-row md:items-end gap-4">
                <div class="w-full md:w-64">
                    <?php $this->include('components/select', [
                        'name' => 'class_id',
                        'id' => 'filter_class_id',
                        'label' => 'My Form Class',
                        'options' => $classOptions,
                        'selected' => $selectedClassId,
                    ]); ?>
                </div>

                <div class="w-full md:w-64">
                    <?php $this->include('components/select', [
                        'name' => 'session_id',
                        'id' => 'filter_session_id',
                        'label' => 'Academic Session',
                        'options' => $sessionOptions,
                        'selected' => $selectedSessionId,
                    ]); ?>
                </div>

                <div class="w-full md:w-56">
                    <?php $this->include('components/select', [
                        'name' => 'term_id',
                        'id' => 'filter_term_id',
                        'label' => 'Academic Term',
                        'options' => $termOptions,
                        'selected' => $selectedTermId,
                    ]); ?>
                </div>

                <div class="flex-shrink-0">
                    <?php $this->include('components/button', [
                        'type' => 'submit',
                        'variant' => 'primary',
                        'label' => 'Load Broadsheet',
                        'icon' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>',
                        'class' => 'min-h-[44px] w-full md:w-auto',
                    ]); ?>
                </div>
            </form>
        </div>

        <?php if ($selectedClass && $selectedTerm): ?>

            <!-- Submission Status & Actions Banner -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-5">
                    <div class="space-y-2">
                        <div class="flex flex-wrap items-center gap-3">
                            <h2 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">
                                <?= e($selectedClass->name) ?>
                                <?php if ($selectedClass->sectionArm): ?>
                                    <span class="px-2.5 py-0.5 rounded-md text-xs font-mono font-bold bg-blue-50 text-blue-700 border border-blue-200">
                                        Arm <?= e($selectedClass->sectionArm) ?>
                                    </span>
                                <?php endif; ?>
                            </h2>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-sky-50 text-sky-700 border border-sky-200">
                                <?= e($selectedTerm->name) ?>
                            </span>

                            <!-- Status Pill -->
                            <?php if ($submission && $submission->isApproved()): ?>
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                    Approved by Administration
                                </span>
                            <?php elseif ($submission && $submission->isSubmitted()): ?>
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                    <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
                                    Submitted for Admin Approval
                                </span>
                            <?php elseif ($submission && $submission->isRejected()): ?>
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-rose-50 text-rose-700 border border-rose-200">
                                    <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                                    Returned for Revisions
                                </span>
                            <?php else: ?>
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-slate-100 text-slate-700 border border-slate-200">
                                    <span class="w-2 h-2 rounded-full bg-slate-400"></span>
                                    Draft (Unsubmitted)
                                </span>
                            <?php endif; ?>

                            <?php if ($isPublished): ?>
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-purple-50 text-purple-700 border border-purple-200">
                                    Published to Parents
                                </span>
                            <?php endif; ?>
                        </div>

                        <div class="flex flex-wrap items-center gap-4 text-xs sm:text-sm text-slate-500">
                            <div><strong class="text-slate-700"><?= count($students) ?></strong> Enrolled Students</div>
                            <span>&bull;</span>
                            <div><strong class="text-slate-700"><?= count($classSubjects) ?></strong> Registered Subjects</div>
                            <?php if ($classMean !== null): ?>
                                <span>&bull;</span>
                                <div>Class Overall Average: <strong class="text-blue-700 font-bold"><?= $classMean ?>%</strong></div>
                            <?php endif; ?>
                            <?php if ($submission): ?>
                                <span>&bull;</span>
                                <div>Submitted on: <span class="font-medium text-slate-700"><?= date('d M Y, H:i', strtotime($submission->submittedAt)) ?></span></div>
                            <?php endif; ?>
                        </div>

                        <?php if ($submission && $submission->isRejected() && !empty($submission->notes)): ?>
                            <div class="p-3 bg-rose-50 rounded-xl border border-rose-200 text-xs text-rose-800">
                                <strong>Admin Feedback:</strong> <?= e($submission->notes) ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Submission Action Button -->
                    <div class="flex flex-wrap items-center gap-3">
                        <button type="button" onclick="window.print()"
                                class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold text-slate-700 bg-slate-50 hover:bg-slate-100 border border-slate-200 transition shadow-xs cursor-pointer">
                            <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                            <span>Print Broadsheet</span>
                        </button>

                        <?php if (!$submission || !$submission->isApproved()): ?>
                            <button type="button" onclick="openSubmitResultsModal()"
                                    class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-bold text-white bg-blue-600 hover:bg-blue-700 shadow-sm transition cursor-pointer">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                <span><?= $submission ? 'Resubmit Results for Approval' : 'Submit Class Results to Admin' ?></span>
                            </button>
                        <?php else: ?>
                            <div class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-semibold text-emerald-800 bg-emerald-50 border border-emerald-200">
                                <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                <span>Results Locked &amp; Approved</span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Full Multi-Subject Broadsheet Table -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between flex-wrap gap-2">
                    <div>
                        <h3 class="text-base font-bold text-slate-900">Class Academic Broadsheet Matrix</h3>
                        <p class="text-xs text-slate-500">Student scores across all curriculum subjects for <?= e($selectedClass->getFullName()) ?></p>
                    </div>
                    <div class="text-xs text-slate-400 font-mono">
                        Ranked by Academic Average
                    </div>
                </div>

                <?php if (empty($students)): ?>
                    <div class="p-8 text-center text-sm text-slate-500">
                        No students are currently enrolled in this class arm for the selected academic session.
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto max-w-full">
                        <table class="w-full text-left text-xs border-collapse">
                            <thead>
                                <tr class="bg-slate-50 border-b border-slate-200 text-slate-600 font-bold uppercase tracking-wider">
                                    <th class="py-3 px-3 w-12 text-center">Rank</th>
                                    <th class="py-3 px-4 min-w-[180px] sticky left-0 bg-slate-50 shadow-[1px_0_0_0_#e2e8f0]">Student Details</th>
                                    <?php foreach ($classSubjects as $cs): ?>
                                        <th class="py-3 px-3 text-center min-w-[75px]" title="<?= e($cs->subjectName) ?>">
                                            <div class="font-bold text-slate-900"><?= e($cs->subjectCode ?? substr($cs->subjectName, 0, 4)) ?></div>
                                            <div class="text-[10px] text-slate-400 font-normal truncate max-w-[80px]"><?= e($cs->subjectName) ?></div>
                                        </th>
                                    <?php endforeach; ?>
                                    <th class="py-3 px-3 text-center bg-slate-100 font-bold text-slate-900">Total</th>
                                    <th class="py-3 px-3 text-center bg-slate-100 font-bold text-slate-900">Average</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 font-medium text-slate-700">
                                <?php foreach ($students as $index => $stu): 
                                    $summary = $summaryMap[$stu->id] ?? null;
                                    $rank = $summary->classRank ?? ($index + 1);
                                    $isTop3 = $rank <= 3;
                                ?>
                                    <tr class="hover:bg-slate-50/70 transition <?= $isTop3 ? 'bg-amber-50/30' : '' ?>">
                                        <td class="py-3 px-3 text-center font-bold">
                                            <?php if ($rank === 1): ?>
                                                <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-amber-100 text-amber-800 text-xs font-black ring-2 ring-amber-300">1</span>
                                            <?php elseif ($rank === 2): ?>
                                                <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-slate-200 text-slate-700 text-xs font-black">2</span>
                                            <?php elseif ($rank === 3): ?>
                                                <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-amber-50 text-amber-700 text-xs font-black">3</span>
                                            <?php else: ?>
                                                <span class="text-slate-400"><?= $rank ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="py-3 px-4 sticky left-0 bg-white shadow-[1px_0_0_0_#e2e8f0]">
                                            <div class="font-bold text-slate-900 leading-tight"><?= e($stu->name) ?></div>
                                            <div class="text-[11px] font-mono text-slate-400"><?= e($stu->admissionNumber) ?></div>
                                        </td>
                                        <?php foreach ($classSubjects as $cs): 
                                            $res = $resultsMatrix[$stu->id][$cs->id] ?? null;
                                            $score = $res['total_score'] ?? null;
                                            $grade = $res['grade'] ?? null;
                                        ?>
                                            <td class="py-3 px-3 text-center font-mono">
                                                <?php if ($score !== null): ?>
                                                    <span class="font-bold text-slate-800"><?= number_format((float)$score, 0) ?></span>
                                                    <?php if ($grade): ?>
                                                        <span class="text-[10px] ml-0.5 px-1 py-0.2 rounded bg-slate-100 text-slate-600 font-semibold"><?= e($grade) ?></span>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <span class="text-slate-300">&mdash;</span>
                                                <?php endif; ?>
                                            </td>
                                        <?php endforeach; ?>
                                        <td class="py-3 px-3 text-center bg-slate-50 font-mono font-bold text-slate-900">
                                            <?= $summary && $summary->totalScore !== null ? number_format((float)$summary->totalScore, 1) : '&mdash;' ?>
                                        </td>
                                        <td class="py-3 px-3 text-center bg-slate-50 font-mono font-bold text-blue-700">
                                            <?= $summary && $summary->averageScore !== null ? number_format((float)$summary->averageScore, 1) . '%' : '&mdash;' ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <!-- Footer Means -->
                            <tfoot>
                                <tr class="bg-slate-100 border-t-2 border-slate-300 font-bold text-slate-800 text-center">
                                    <td colspan="2" class="py-3 px-4 text-left font-extrabold uppercase text-xs tracking-wider">
                                        Subject Class Means
                                    </td>
                                    <?php foreach ($classSubjects as $cs): 
                                        $avg = $subjectAverages[$cs->subjectId]['avg'] ?? null;
                                    ?>
                                        <td class="py-3 px-3 font-mono text-slate-700">
                                            <?= $avg !== null ? number_format((float)$avg, 1) : '&mdash;' ?>
                                        </td>
                                    <?php endforeach; ?>
                                    <td class="py-3 px-3 bg-slate-200 text-slate-900">&mdash;</td>
                                    <td class="py-3 px-3 bg-slate-200 font-mono text-blue-900">
                                        <?= $classMean !== null ? number_format($classMean, 1) . '%' : '&mdash;' ?>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Submit Modal Component -->
            <?php ob_start(); ?>
            <form id="submitResultsForm" method="POST" action="/teacher/results/submit" class="space-y-4">
                <?= csrf_field() ?>
                <input type="hidden" name="class_id" value="<?= e((string)$selectedClassId) ?>">
                <input type="hidden" name="term_id" value="<?= e((string)$selectedTermId) ?>">

                <div class="p-4 bg-blue-50 rounded-xl border border-blue-100 text-xs text-blue-900 space-y-1.5 leading-relaxed">
                    <p class="font-bold">Form Teacher Final Submission Protocol:</p>
                    <p>
                        By submitting, you certify that all subject continuous assessment scores, batch teacher remarks, and behavioral trait ratings for <strong class="font-semibold"><?= e($selectedClass->getFullName()) ?></strong> have been reviewed and verified for the term.
                    </p>
                    <p>
                        The administration exam committee will inspect the broadsheet, review rankings, and proceed to official publication.
                    </p>
                </div>

                <div>
                    <label for="submission_notes" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                        Handover / Submittal Notes (Optional)
                    </label>
                    <textarea id="submission_notes" name="notes" rows="3" 
                              class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm text-slate-800 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition"
                              placeholder="e.g. All CA and exam scores tallied; remarkable improvements recorded in science subjects."><?= e($submission?->notes ?? '') ?></textarea>
                </div>
            </form>
            <?php $submitModalBody = ob_get_clean(); ?>

            <?php ob_start(); ?>
            <button type="button" 
                    onclick="window.LMS.hideModal('submit-results-modal')" 
                    class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 transition cursor-pointer">
                Cancel
            </button>
            <button type="submit" 
                    form="submitResultsForm" 
                    class="px-4 py-2 text-sm font-semibold text-white bg-blue-600 rounded-lg hover:bg-blue-700 shadow-sm transition cursor-pointer">
                Confirm &amp; Submit Results
            </button>
            <?php $submitModalFooter = ob_get_clean(); ?>

            <?php $this->include('components/modal', [
                'id' => 'submit-results-modal',
                'title' => 'Submit Class Results for Administrative Approval',
                'body' => $submitModalBody,
                'footer' => $submitModalFooter,
                'size' => 'md'
            ]); ?>

            <script>
            function openSubmitResultsModal() {
                window.LMS.showModal('submit-results-modal');
            }
            </script>

        <?php endif; ?>
    <?php endif; ?>
</div>
