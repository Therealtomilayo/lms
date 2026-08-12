<div class="space-y-6">
    <!-- Header -->
    <div>
        <h2 class="text-2xl font-bold text-slate-900 tracking-tight">Computer-Based Tests & Quizzes</h2>
        <p class="text-sm text-slate-500 mt-1">Take timed online tests and view assessment results for your enrolled subjects.</p>
    </div>

    <!-- Active / Available Quizzes -->
    <div class="space-y-4">
        <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
            <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
            Available Assessments
        </h3>

        <?php if (empty($activeQuizzes)): ?>
            <div class="bg-white rounded-2xl border border-slate-200 p-8 text-center shadow-sm">
                <p class="text-sm text-slate-500">No active CBT quizzes available at the moment.</p>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($activeQuizzes as $item): 
                    $quiz = $item['quiz'];
                ?>
                    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm flex flex-col justify-between hover:border-slate-300 transition overflow-hidden">
                        <div class="p-5">
                            <div class="flex items-center justify-between gap-2 mb-2">
                                <span class="text-xs font-bold text-brand-600 uppercase tracking-wider">
                                    <?= e($quiz->term?->name ?? 'Quiz') ?>
                                </span>
                                <?php if ($item['has_active_attempt']): ?>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-200">
                                        In Progress
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        <?= $item['attempts_taken'] ?> / <?= $item['max_attempts'] ?> Taken
                                    </span>
                                <?php endif; ?>
                            </div>

                            <h4 class="text-base font-bold text-slate-900 leading-snug">
                                <?= e($quiz->title) ?>
                            </h4>

                            <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500 font-medium">
                                <span>⏱ <?= $quiz->hasTimeLimit() ? "{$quiz->timeLimitMinutes} Mins" : 'Untimed' ?></span>
                                <span><?= $item['max_attempts'] ?> Attempt<?= $item['max_attempts'] > 1 ? 's' : '' ?></span>
                            </div>
                        </div>

                        <div class="p-4 bg-slate-50 border-t border-slate-100">
                            <?php if ($item['has_active_attempt']): ?>
                                <a href="/student/quiz-attempts/<?= $item['active_attempt_id'] ?>" 
                                   class="w-full inline-flex items-center justify-center gap-2 py-2 px-4 bg-amber-600 hover:bg-amber-700 text-white text-xs font-bold rounded-xl shadow-sm transition">
                                    Resume Exam &rarr;
                                </a>
                            <?php else: ?>
                                <a href="/student/quizzes/<?= $quiz->id ?>" 
                                   class="w-full inline-flex items-center justify-center gap-2 py-2 px-4 bg-brand-600 hover:bg-brand-700 text-white text-xs font-bold rounded-xl shadow-sm transition">
                                    View Test Instructions &rarr;
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Completed Quizzes -->
    <?php if (!empty($completedQuizzes)): ?>
        <div class="space-y-4 pt-6 border-t border-slate-200">
            <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-slate-400"></span>
                Past / Completed Assessments
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($completedQuizzes as $item): 
                    $quiz = $item['quiz'];
                    $latest = $item['latest_attempt'];
                ?>
                    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 space-y-3">
                        <div class="flex items-center justify-between">
                            <h4 class="text-base font-bold text-slate-900"><?= e($quiz->title) ?></h4>
                            <span class="text-xs font-semibold text-slate-500">Attempt Limit Reached</span>
                        </div>

                        <?php if ($latest && $latest->isSubmitted()): ?>
                            <div class="p-3 bg-slate-50 rounded-xl border border-slate-200 flex items-center justify-between text-xs">
                                <span>Final Score:</span>
                                <strong class="text-slate-900 text-sm">
                                    <?= $latest->score !== null ? number_format($latest->score, 2) . ' / ' . number_format($latest->maxScore, 2) : 'Under Review' ?>
                                </strong>
                            </div>

                            <a href="/student/quiz-attempts/<?= $latest->id ?>/result" 
                               class="w-full inline-flex items-center justify-center py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl transition">
                                View Results & Feedback
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>
