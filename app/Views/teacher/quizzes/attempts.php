<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <a href="/teacher/quizzes" class="text-xs font-semibold text-slate-500 hover:text-slate-700">&larr; Quizzes</a>
                <span class="text-slate-300">/</span>
                <span class="text-xs font-semibold text-brand-600"><?= e($quiz->title) ?></span>
            </div>
            <h2 class="text-2xl font-bold text-slate-900 tracking-tight">Student Quiz Attempts</h2>
            <p class="text-sm text-slate-500 mt-1">Review student submissions, grade short-answer questions, and manage attempt resets.</p>
        </div>
    </div>

    <!-- Attempts Table -->
    <?php if (empty($attempts)): ?>
        <div class="bg-white rounded-2xl border border-slate-200 p-12 text-center shadow-sm">
            <div class="w-16 h-16 rounded-full bg-brand-50 text-brand-500 mx-auto flex items-center justify-center mb-4">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <h3 class="text-base font-semibold text-slate-800">No Student Attempts Yet</h3>
            <p class="text-sm text-slate-500 mt-1 max-w-sm mx-auto">When students participate in this CBT assessment, their attempts, live timer status, and score results will appear here.</p>
        </div>
    <?php else: ?>
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-sm">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200 text-xs font-bold text-slate-600 uppercase tracking-wider">
                            <th class="py-3.5 px-4">Student</th>
                            <th class="py-3.5 px-4">Attempt #</th>
                            <th class="py-3.5 px-4">Started At</th>
                            <th class="py-3.5 px-4">Status</th>
                            <th class="py-3.5 px-4">Score</th>
                            <th class="py-3.5 px-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php foreach ($attempts as $att): ?>
                            <tr class="hover:bg-slate-50/50 transition">
                                <td class="py-3.5 px-4 font-semibold text-slate-900">
                                    <?= e($att->student?->user?->name ?? 'Student') ?>
                                    <span class="block text-xs font-normal text-slate-400">
                                        <?= e($att->student?->admissionNumber ?? '') ?>
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 text-slate-600">
                                    Attempt <?= $att->attemptNumber ?>
                                </td>
                                <td class="py-3.5 px-4 text-slate-500 text-xs">
                                    <?= e($att->startedAt) ?>
                                </td>
                                <td class="py-3.5 px-4">
                                    <?php if ($att->status === 'graded'): ?>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            Graded
                                        </span>
                                    <?php elseif ($att->status === 'submitted'): ?>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-blue-50 text-blue-700 border border-blue-200">
                                            Pending Grade
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-200">
                                            In Progress
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3.5 px-4 font-semibold text-slate-900">
                                    <?= $att->score !== null ? number_format($att->score, 2) . ' / ' . number_format($att->maxScore, 2) : '—' ?>
                                </td>
                                <td class="py-3.5 px-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <?php if ($att->isSubmitted()): ?>
                                            <a href="/teacher/quizzes/<?= $quiz->id ?>/attempts/<?= $att->id ?>/grade" 
                                               class="px-3 py-1.5 bg-brand-50 hover:bg-brand-100 text-brand-700 text-xs font-semibold rounded-lg transition">
                                                Review & Grade
                                            </a>
                                        <?php endif; ?>

                                        <form method="POST" action="/teacher/quizzes/<?= $quiz->id ?>/attempts/<?= $att->id ?>/reset" onsubmit="return confirm('Resetting this attempt will erase the student answers and allow them to retake the test. Proceed?');">
                                            <input type="hidden" name="csrf_token" value="<?= e(\App\Middleware\CsrfMiddleware::getToken()) ?>">
                                            <button type="submit" class="px-2.5 py-1.5 bg-slate-100 hover:bg-rose-50 hover:text-rose-600 text-slate-600 text-xs font-semibold rounded-lg transition">
                                                Reset
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>
