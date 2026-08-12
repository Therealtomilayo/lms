<div class="max-w-2xl mx-auto space-y-6">
    <a href="/student/quizzes" class="text-sm font-semibold text-slate-600 hover:text-slate-900 inline-block">
        &larr; Back to Assessments
    </a>

    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 sm:p-8 space-y-6">
        <div>
            <span class="text-xs font-bold text-brand-600 uppercase tracking-wider block mb-1">
                <?= e($quiz->term?->name ?? 'Assessment') ?>
            </span>
            <h2 class="text-2xl font-bold text-slate-900"><?= e($quiz->title) ?></h2>
        </div>

        <!-- Assessment Rules / Specs -->
        <div class="grid grid-cols-2 sm:grid-cols-3 gap-4 p-4 bg-slate-50 rounded-xl border border-slate-200 text-center">
            <div>
                <span class="block text-xs font-semibold text-slate-400 uppercase tracking-wider">Duration</span>
                <span class="text-base font-bold text-slate-900">
                    <?= $quiz->hasTimeLimit() ? "{$quiz->timeLimitMinutes} Minutes" : 'Untimed' ?>
                </span>
            </div>
            <div>
                <span class="block text-xs font-semibold text-slate-400 uppercase tracking-wider">Questions</span>
                <span class="text-base font-bold text-slate-900">
                    <?= count($quiz->quizQuestions) ?> Items
                </span>
            </div>
            <div class="col-span-2 sm:col-span-1">
                <span class="block text-xs font-semibold text-slate-400 uppercase tracking-wider">Attempts</span>
                <span class="text-base font-bold text-slate-900">
                    <?= count($attempts) ?> of <?= $quiz->maxAttempts ?> Used
                </span>
            </div>
        </div>

        <!-- Instructions -->
        <div class="space-y-2">
            <h4 class="text-sm font-bold text-slate-900">Instructions:</h4>
            <div class="text-sm text-slate-600 space-y-2 bg-slate-50/50 p-4 rounded-xl border border-slate-100">
                <?php if ($quiz->instructions): ?>
                    <p><?= nl2br(e($quiz->instructions)) ?></p>
                <?php else: ?>
                    <p>Read all questions carefully. Choose the single best answer for multiple-choice questions or write your answer clearly for short-answer questions.</p>
                <?php endif; ?>
                <ul class="list-disc list-inside space-y-1 text-xs text-slate-500 pt-2 border-t border-slate-200">
                    <li>Once you click <strong>Start Assessment</strong>, the timer begins immediately.</li>
                    <li>Your answers are automatically saved as you navigate through questions.</li>
                    <li>Do not close or reload the test window without submitting.</li>
                </ul>
            </div>
        </div>

        <!-- Action Button -->
        <div class="pt-4 border-t border-slate-100 flex items-center justify-end">
            <?php if ($activeAttempt): ?>
                <a href="/student/quiz-attempts/<?= $activeAttempt->id ?>" 
                   class="px-6 py-3 bg-amber-600 hover:bg-amber-700 text-white text-sm font-bold rounded-xl shadow-sm transition">
                    Resume Active Attempt &rarr;
                </a>
            <?php elseif ($canStart): ?>
                <form method="POST" action="/student/quizzes/<?= $quiz->id ?>/attempts">
                    <input type="hidden" name="csrf_token" value="<?= e(\App\Middleware\CsrfMiddleware::getToken()) ?>">
                    <button type="submit" class="px-6 py-3 bg-brand-600 hover:bg-brand-700 text-white text-sm font-bold rounded-xl shadow-sm transition">
                        Start Assessment Now &rarr;
                    </button>
                </form>
            <?php else: ?>
                <button disabled class="px-6 py-3 bg-slate-200 text-slate-400 text-sm font-bold rounded-xl cursor-not-allowed">
                    Attempt Limit Reached
                </button>
            <?php endif; ?>
        </div>
    </div>
</div>
