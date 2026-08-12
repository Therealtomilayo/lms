<div class="max-w-3xl mx-auto space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <a href="/teacher/quizzes/<?= $quiz->id ?>/attempts" class="text-xs font-semibold text-slate-500 hover:text-slate-700">&larr; Back to Attempts</a>
            </div>
            <h2 class="text-2xl font-bold text-slate-900 tracking-tight">Grade Student Assessment</h2>
            <p class="text-sm text-slate-500 mt-1">
                Student: <strong><?= e($attempt->student?->user?->name ?? 'Student') ?></strong> (<?= e($attempt->student?->admissionNumber ?? '') ?>) · Attempt #<?= $attempt->attemptNumber ?>
            </p>
        </div>
        <div class="text-right">
            <span class="text-xs text-slate-400 block">Total Score</span>
            <span class="text-xl font-bold text-slate-900">
                <?= $attempt->score !== null ? number_format($attempt->score, 2) : '0.00' ?> / <?= number_format($attempt->maxScore, 2) ?>
            </span>
        </div>
    </div>

    <form method="POST" action="/teacher/quizzes/<?= $quiz->id ?>/attempts/<?= $attempt->id ?>/grade" class="space-y-6">
        <input type="hidden" name="csrf_token" value="<?= e(\App\Middleware\CsrfMiddleware::getToken()) ?>">

        <div class="space-y-4">
            <?php foreach ($answers as $idx => $ans): 
                $q = $ans->question;
            ?>
                <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm space-y-3">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex-1">
                            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block mb-1">
                                Question <?= $idx + 1 ?> (<?= $q?->isMcq() ? 'MCQ — Auto Graded' : 'Short Answer' ?>)
                            </span>
                            <p class="text-sm font-semibold text-slate-900">
                                <?= nl2br(e($q?->questionText ?? 'Question')) ?>
                            </p>
                        </div>
                    </div>

                    <!-- Student Response -->
                    <div class="p-3.5 rounded-xl border <?= $q?->isMcq() ? ($ans->selectedOption?->isCorrect ? 'bg-emerald-50/60 border-emerald-200' : 'bg-rose-50/60 border-rose-200') : 'bg-slate-50 border-slate-200' ?>">
                        <span class="text-[11px] font-bold uppercase tracking-wider block mb-1 <?= $q?->isMcq() ? ($ans->selectedOption?->isCorrect ? 'text-emerald-700' : 'text-rose-700') : 'text-slate-500' ?>">
                            Student Response:
                        </span>
                        <?php if ($q?->isMcq()): ?>
                            <p class="text-sm font-medium text-slate-900">
                                <?= $ans->selectedOption ? e($ans->selectedOption->optionText) : '<span class="text-slate-400 italic">No option selected</span>' ?>
                            </p>
                        <?php else: ?>
                            <p class="text-sm text-slate-900 whitespace-pre-wrap">
                                <?= !empty($ans->textAnswer) ? e($ans->textAnswer) : '<span class="text-slate-400 italic">No answer provided</span>' ?>
                            </p>
                        <?php endif; ?>
                    </div>

                    <!-- Grading Controls -->
                    <?php if ($q?->isShortAnswer()): ?>
                        <div class="pt-2 border-t border-slate-100 grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-1">Points Awarded (Max: <?= $q->defaultPoints ?>)</label>
                                <input type="number" step="0.25" min="0" max="<?= $q->defaultPoints ?>" name="grades[<?= $ans->id ?>][points_awarded]" value="<?= e((string)($ans->pointsAwarded ?? '0.00')) ?>" class="w-full rounded-xl border-slate-300 shadow-sm text-sm focus:border-brand-500 focus:ring-brand-500" required>
                                <input type="hidden" name="grades[<?= $ans->id ?>][answer_id]" value="<?= $ans->id ?>">
                            </div>
                            <div class="sm:col-span-2">
                                <label class="block text-xs font-semibold text-slate-600 mb-1">Teacher Feedback / Notes</label>
                                <input type="text" name="grades[<?= $ans->id ?>][teacher_comment]" value="<?= e($ans->teacherComment ?? '') ?>" placeholder="Optional constructive feedback..." class="w-full rounded-xl border-slate-300 shadow-sm text-sm focus:border-brand-500 focus:ring-brand-500">
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="flex items-center justify-between text-xs text-slate-500 pt-1">
                            <span>Auto-graded points: <strong><?= number_format($ans->pointsAwarded ?? 0, 2) ?> pt(s)</strong></span>
                            <span>Result: <strong><?= e($ans->teacherComment ?? '') ?></strong></span>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="flex items-center justify-end gap-3 pt-4">
            <a href="/teacher/quizzes/<?= $quiz->id ?>/attempts" class="px-4 py-2.5 rounded-xl border border-slate-300 text-slate-700 text-sm font-semibold hover:bg-slate-50 transition">
                Cancel
            </a>
            <button type="submit" class="px-5 py-2.5 bg-brand-600 hover:bg-brand-700 text-white text-sm font-semibold rounded-xl shadow-sm transition">
                Finalize & Save Grades
            </button>
        </div>
    </form>
</div>
