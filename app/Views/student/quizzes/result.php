<div class="max-w-3xl mx-auto space-y-6">
    <!-- Header Controls -->
    <div class="flex items-center justify-between">
        <a href="/student/quizzes" class="text-sm font-semibold text-slate-600 hover:text-slate-900 inline-block">
            &larr; Back to Assessments
        </a>
    </div>

    <!-- Score Card Summary -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 sm:p-8 space-y-6 text-center">
        <div>
            <span class="text-xs font-bold text-brand-600 uppercase tracking-wider block mb-1">
                Assessment Results
            </span>
            <h2 class="text-2xl font-bold text-slate-900"><?= e($quiz->title) ?></h2>
            <p class="text-xs text-slate-500 mt-1">
                Attempt #<?= $attempt->attemptNumber ?> · Submitted on <?= e($attempt->submittedAt ?? '') ?>
            </p>
        </div>

        <div class="inline-flex flex-col items-center justify-center p-6 rounded-2xl bg-slate-50 border border-slate-200 min-w-[200px]">
            <span class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Final Score</span>
            <div class="text-4xl font-extrabold text-brand-600">
                <?= $attempt->score !== null ? number_format($attempt->score, 2) : '—' ?>
                <span class="text-lg font-medium text-slate-400">/ <?= number_format($attempt->maxScore, 2) ?></span>
            </div>
            <div class="mt-2">
                <?php if ($attempt->status === 'graded'): ?>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                        Grading Complete
                    </span>
                <?php else: ?>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-50 text-amber-700 border border-amber-200">
                        Under Teacher Review (Short Answers)
                    </span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Question-by-Question Breakdown -->
    <div class="space-y-4">
        <h3 class="text-base font-bold text-slate-900">Question Performance & Breakdown</h3>

        <?php foreach ($answers as $idx => $ans): 
            $q = $ans->question;
        ?>
            <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm space-y-3">
                <div class="flex items-start justify-between gap-4">
                    <div class="flex-1">
                        <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block mb-1">
                            Question <?= $idx + 1 ?>
                        </span>
                        <p class="text-sm font-semibold text-slate-900">
                            <?= nl2br(e($q?->questionText ?? 'Question')) ?>
                        </p>
                    </div>
                    <div class="text-right">
                        <span class="text-xs font-bold text-slate-900">
                            <?= $ans->pointsAwarded !== null ? number_format($ans->pointsAwarded, 2) : '—' ?> pt(s)
                        </span>
                    </div>
                </div>

                <div class="p-3.5 rounded-xl border <?= $q?->isMcq() ? ($ans->selectedOption?->isCorrect ? 'bg-emerald-50/60 border-emerald-200' : 'bg-rose-50/60 border-rose-200') : 'bg-slate-50 border-slate-200' ?>">
                    <span class="text-[11px] font-bold uppercase tracking-wider block mb-1 <?= $q?->isMcq() ? ($ans->selectedOption?->isCorrect ? 'text-emerald-700' : 'text-rose-700') : 'text-slate-500' ?>">
                        Your Answer:
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

                <?php if ($ans->teacherComment): ?>
                    <div class="p-3 rounded-xl bg-brand-50/50 border border-brand-100 text-xs text-brand-900">
                        <strong>Instructor Feedback:</strong> <?= e($ans->teacherComment) ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
</div>
