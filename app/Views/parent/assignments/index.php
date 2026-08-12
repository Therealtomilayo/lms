<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Parent Portal</span>
                <span class="text-xs text-slate-400">/</span>
                <span class="text-xs font-semibold text-brand-600"><?= e($student->name) ?></span>
            </div>
            <h2 class="text-2xl font-bold text-slate-900 tracking-tight">Coursework & Grading Overview</h2>
            <p class="text-sm text-slate-500 mt-1">
                Academic progress, submitted assignments, and teacher feedback for <strong><?= e($student->name) ?></strong> (<?= e($student->admissionNumber) ?>).
            </p>
        </div>
    </div>

    <!-- Coursework List -->
    <?php if (empty($assignments)): ?>
        <div class="bg-white rounded-2xl border border-slate-200 p-12 text-center shadow-sm">
            <div class="w-16 h-16 rounded-full bg-slate-100 text-slate-400 mx-auto flex items-center justify-center mb-4">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            </div>
            <h3 class="text-base font-semibold text-slate-800">No Coursework Assigned</h3>
            <p class="text-sm text-slate-500 mt-1 max-w-sm mx-auto">There are no published assignments for your child's enrolled subjects at this time.</p>
        </div>
    <?php else: ?>
        <div class="space-y-4">
            <?php foreach ($assignments as $assignment): ?>
                <?php $sub = $submissions[$assignment->id] ?? null; ?>
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 space-y-4">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-slate-100">
                        <div>
                            <span class="text-xs font-bold text-brand-600 uppercase tracking-wider">
                                <?= e($assignment->classSubject?->subjectName ?? 'Subject') ?>
                            </span>
                            <h3 class="text-base font-bold text-slate-900 mt-0.5"><?= e($assignment->title) ?></h3>
                            <p class="text-xs text-slate-500 mt-0.5">
                                Due: <?= date('M d, Y · g:i A', strtotime($assignment->dueAt)) ?> · Max Score: <?= number_format($assignment->maxScore, 0) ?> pts
                            </p>
                        </div>

                        <div>
                            <?php if ($sub && $sub->isGraded()): ?>
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                    Score: <?= number_format($sub->score, 1) ?> / <?= number_format($assignment->maxScore, 0) ?>
                                </span>
                            <?php elseif ($sub): ?>
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-blue-50 text-blue-700 border border-blue-200">
                                    Submitted <?= $sub->isLate() ? '(Late)' : '' ?>
                                </span>
                            <?php elseif ($assignment->isPastDue()): ?>
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-rose-50 text-rose-700 border border-rose-200">
                                    Missing / Past Due
                                </span>
                            <?php else: ?>
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                    Pending Submission
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Teacher Feedback if available -->
                    <?php if ($sub && $sub->isGraded()): ?>
                        <div class="p-4 bg-emerald-50/50 rounded-xl border border-emerald-200 space-y-1">
                            <span class="block text-[11px] font-bold text-emerald-800 uppercase tracking-wider">Teacher Feedback:</span>
                            <?php if (!empty($sub->teacherComment)): ?>
                                <p class="text-sm text-emerald-950 font-medium leading-relaxed"><?= e($sub->teacherComment) ?></p>
                            <?php else: ?>
                                <p class="text-xs text-emerald-700 italic">No textual feedback provided.</p>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
