<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <a href="/teacher/assignments" class="text-xs font-bold text-brand-600 hover:underline">← All Assignments</a>
                <span class="text-xs text-slate-400">/</span>
                <span class="text-xs font-semibold text-slate-500"><?= e($assignment->classSubject?->subjectName ?? 'Subject') ?></span>
            </div>
            <h2 class="text-2xl font-bold text-slate-900 tracking-tight"><?= e($assignment->title) ?> — Submissions</h2>
            <p class="text-sm text-slate-500 mt-1">
                Due: <span class="font-medium text-slate-700"><?= date('M d, Y · g:i A', strtotime($assignment->dueAt)) ?></span> · 
                Max Score: <span class="font-medium text-slate-700"><?= number_format($assignment->maxScore, 0) ?> pts</span>
            </p>
        </div>
        <div>
            <a href="/teacher/assignments/<?= $assignment->id ?>/edit" 
               class="inline-flex items-center gap-2 px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-semibold rounded-xl transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                Edit Assignment
            </a>
        </div>
    </div>

    <!-- Submissions Table / Cards -->
    <?php if (empty($submissions)): ?>
        <div class="bg-white rounded-2xl border border-slate-200 p-12 text-center shadow-sm">
            <div class="w-16 h-16 rounded-full bg-slate-100 text-slate-400 mx-auto flex items-center justify-center mb-4">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/></svg>
            </div>
            <h3 class="text-base font-semibold text-slate-800">No Submissions Yet</h3>
            <p class="text-sm text-slate-500 mt-1 max-w-sm mx-auto">Enrolled students have not submitted their coursework responses yet.</p>
        </div>
    <?php else: ?>
        <div class="space-y-4">
            <?php foreach ($submissions as $sub): ?>
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 space-y-4">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-4 border-b border-slate-100">
                        <div>
                            <div class="flex items-center gap-2">
                                <h4 class="text-base font-bold text-slate-900"><?= e($sub->student?->name ?? 'Student') ?></h4>
                                <span class="text-xs font-mono text-slate-400 font-semibold bg-slate-100 px-2 py-0.5 rounded-md">
                                    <?= e($sub->student?->admissionNumber ?? '') ?>
                                </span>
                            </div>
                            <p class="text-xs text-slate-500 mt-0.5">
                                Submitted on <?= date('M d, Y · g:i A', strtotime($sub->submittedAt)) ?>
                                <?php if ($sub->isLate()): ?>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold bg-rose-50 text-rose-700 border border-rose-200 ml-2">
                                        Late Submission
                                    </span>
                                <?php endif; ?>
                            </p>
                        </div>

                        <div>
                            <?php if ($sub->isGraded()): ?>
                                <div class="text-right">
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        Graded: <?= number_format($sub->score, 1) ?> / <?= number_format($assignment->maxScore, 0) ?>
                                    </span>
                                </div>
                            <?php else: ?>
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                    Needs Grading
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Submission Content -->
                    <div class="space-y-3">
                        <?php if ($sub->hasTextResponse()): ?>
                            <div class="p-4 bg-slate-50 rounded-xl border border-slate-200">
                                <span class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">Student's Written Response:</span>
                                <p class="text-sm text-slate-800 whitespace-pre-wrap leading-relaxed"><?= e($sub->textResponse) ?></p>
                            </div>
                        <?php endif; ?>

                        <?php if ($sub->file): ?>
                            <div class="p-3 bg-slate-50 rounded-xl border border-slate-200 flex items-center justify-between gap-3">
                                <div class="flex items-center gap-2 min-w-0">
                                    <svg class="w-5 h-5 text-brand-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                                    <div class="min-w-0">
                                        <p class="text-xs font-semibold text-slate-800 truncate"><?= e($sub->file->originalName) ?></p>
                                        <p class="text-[10px] text-slate-400 font-mono"><?= e($sub->file->getFormattedSize()) ?></p>
                                    </div>
                                </div>
                                <a href="/files/<?= $sub->file->id ?>/download" 
                                   class="px-3 py-1.5 bg-brand-600 hover:bg-brand-700 text-white text-xs font-semibold rounded-lg shadow-sm transition inline-flex items-center gap-1">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                    Download Attachment
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Grading Form -->
                    <div class="pt-4 border-t border-slate-100">
                        <form method="POST" action="/teacher/submissions/<?= $sub->id ?>/grade" class="grid grid-cols-1 md:grid-cols-12 gap-3 items-end">
                            <?= csrf_field() ?>

                            <div class="md:col-span-3">
                                <label for="score_<?= $sub->id ?>" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                                    Score (Max <?= number_format($assignment->maxScore, 0) ?>) <span class="text-rose-500">*</span>
                                </label>
                                <input type="number" step="0.5" min="0" max="<?= $assignment->maxScore ?>" 
                                       name="score" id="score_<?= $sub->id ?>" 
                                       value="<?= $sub->score !== null ? e((string)$sub->score) : '' ?>" required
                                       class="w-full rounded-xl border-slate-300 text-sm focus:border-brand-500 focus:ring-brand-500 bg-slate-50 py-2 px-3 border font-medium">
                            </div>

                            <div class="md:col-span-7">
                                <label for="comment_<?= $sub->id ?>" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                                    Feedback / Comment for Student
                                </label>
                                <input type="text" name="teacher_comment" id="comment_<?= $sub->id ?>" 
                                       value="<?= e($sub->teacherComment ?? '') ?>" placeholder="Constructive feedback, notes on corrections..."
                                       class="w-full rounded-xl border-slate-300 text-sm focus:border-brand-500 focus:ring-brand-500 bg-slate-50 py-2 px-3 border font-medium">
                            </div>

                            <div class="md:col-span-2">
                                <button type="submit" 
                                        class="w-full py-2 px-4 bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold rounded-xl shadow-sm transition">
                                    <?= $sub->isGraded() ? 'Update Grade' : 'Save Grade' ?>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
