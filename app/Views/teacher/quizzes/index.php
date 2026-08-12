<div class="space-y-6">
    <!-- Header Controls -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-slate-900 tracking-tight">Quizzes & CBT Assessments</h2>
            <p class="text-sm text-slate-500 mt-1">Design timed tests, configure CBT exam parameters, and review student attempts.</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="/teacher/question-bank" class="inline-flex items-center gap-2 px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-semibold rounded-xl transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                Question Bank
            </a>
            <a href="/teacher/quizzes/create" class="inline-flex items-center gap-2 px-4 py-2.5 bg-brand-600 hover:bg-brand-700 text-white text-sm font-semibold rounded-xl shadow-sm transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Create Quiz
            </a>
        </div>
    </div>

    <!-- Quiz List -->
    <?php if (empty($quizzes)): ?>
        <div class="bg-white rounded-2xl border border-slate-200 p-12 text-center shadow-sm">
            <div class="w-16 h-16 rounded-full bg-brand-50 text-brand-500 mx-auto flex items-center justify-center mb-4">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
            </div>
            <h3 class="text-base font-semibold text-slate-800">No Quizzes Created Yet</h3>
            <p class="text-sm text-slate-500 mt-1 max-w-sm mx-auto">Build timed computer-based assessments by selecting questions from your Question Bank.</p>
            <div class="mt-6">
                <a href="/teacher/quizzes/create" 
                   class="inline-flex items-center gap-2 px-4 py-2 bg-brand-600 hover:bg-brand-700 text-white text-sm font-semibold rounded-xl shadow-sm transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Create Quiz
                </a>
            </div>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($quizzes as $quiz): ?>
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm flex flex-col hover:border-slate-300 transition overflow-hidden">
                    <div class="p-5 pb-3 border-b border-slate-100 flex items-start justify-between gap-2">
                        <span class="text-xs font-bold text-brand-600 uppercase tracking-wider">
                            <?= e($quiz->term?->name ?? 'Term Assessment') ?>
                        </span>
                        <?php if ($quiz->isPublished()): ?>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">
                                Published
                            </span>
                        <?php else: ?>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-amber-50 text-amber-700 border border-amber-200">
                                Draft
                            </span>
                        <?php endif; ?>
                    </div>

                    <div class="p-5 flex-1 flex flex-col justify-between space-y-4">
                        <div>
                            <h3 class="text-base font-bold text-slate-900 leading-snug">
                                <?= e($quiz->title) ?>
                            </h3>
                            <?php if ($quiz->instructions): ?>
                                <p class="text-xs text-slate-500 line-clamp-2 mt-1">
                                    <?= e($quiz->instructions) ?>
                                </p>
                            <?php endif; ?>
                        </div>

                        <div class="space-y-2 pt-2 border-t border-slate-100 text-xs text-slate-500">
                            <div class="flex items-center justify-between">
                                <span class="flex items-center gap-1.5 font-medium">
                                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    <?= $quiz->hasTimeLimit() ? "{$quiz->timeLimitMinutes} Mins" : 'Untimed' ?>
                                </span>
                                <span class="font-medium">
                                    <?= $quiz->maxAttempts ?> Attempt<?= $quiz->maxAttempts > 1 ? 's' : '' ?> Allowed
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Card Actions -->
                    <div class="p-4 bg-slate-50 border-t border-slate-100 flex items-center justify-between gap-2">
                        <div class="flex items-center gap-2">
                            <a href="/teacher/quizzes/<?= $quiz->id ?>/questions" 
                               class="px-3 py-1.5 bg-brand-50 hover:bg-brand-100 text-brand-700 text-xs font-semibold rounded-lg transition" title="Manage questions in quiz">
                                Questions
                            </a>
                            <a href="/teacher/quizzes/<?= $quiz->id ?>/attempts" 
                               class="px-3 py-1.5 bg-slate-200 hover:bg-slate-300 text-slate-700 text-xs font-semibold rounded-lg transition" title="View student submissions">
                                Attempts
                            </a>
                        </div>

                        <div class="flex items-center gap-2">
                            <form method="POST" action="/teacher/quizzes/<?= $quiz->id ?>/publish">
                                <input type="hidden" name="csrf_token" value="<?= e(\App\Middleware\CsrfMiddleware::getToken()) ?>">
                                <input type="hidden" name="is_published" value="<?= $quiz->isPublished() ? '0' : '1' ?>">
                                <button type="submit" class="px-2.5 py-1.5 text-xs font-semibold rounded-lg transition <?= $quiz->isPublished() ? 'bg-amber-50 hover:bg-amber-100 text-amber-700' : 'bg-emerald-50 hover:bg-emerald-100 text-emerald-700' ?>">
                                    <?= $quiz->isPublished() ? 'Unpublish' : 'Publish' ?>
                                </button>
                            </form>

                            <a href="/teacher/quizzes/<?= $quiz->id ?>/edit" class="p-1.5 text-slate-400 hover:text-slate-600 rounded-lg hover:bg-slate-200/60 transition" title="Edit Quiz Settings">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
