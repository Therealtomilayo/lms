<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <a href="/teacher/quizzes" class="text-xs font-semibold text-slate-500 hover:text-slate-700">&larr; Quizzes</a>
                <span class="text-slate-300">/</span>
                <span class="text-xs font-semibold text-brand-600"><?= e($quiz->title) ?></span>
            </div>
            <h2 class="text-2xl font-bold text-slate-900 tracking-tight">Quiz Question Composition</h2>
            <p class="text-sm text-slate-500 mt-1">Select questions from the Question Bank and assign customized point allocations.</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="/teacher/question-bank/create<?= $classSubject ? '?subject_id=' . $classSubject->subjectId : '' ?>" target="_blank" class="inline-flex items-center gap-2 px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded-xl transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                New Question in Bank
            </a>
            <a href="/teacher/quizzes/<?= $quiz->id ?>/edit" class="px-3 py-2 border border-slate-300 text-slate-700 text-xs font-semibold rounded-xl hover:bg-slate-50 transition">
                Quiz Settings
            </a>
        </div>
    </div>

    <!-- Question Picker Form -->
    <form method="POST" action="/teacher/quizzes/<?= $quiz->id ?>/questions" class="space-y-6">
        <input type="hidden" name="csrf_token" value="<?= e(\App\Middleware\CsrfMiddleware::getToken()) ?>">

        <?php if (empty($availableQuestions)): ?>
            <div class="bg-white rounded-2xl border border-slate-200 p-12 text-center shadow-sm">
                <p class="text-slate-500 text-sm">There are no questions in the Question Bank for this subject yet.</p>
                <div class="mt-4">
                    <a href="/teacher/question-bank/create<?= $classSubject ? '?subject_id=' . $classSubject->subjectId : '' ?>" 
                       class="inline-flex items-center gap-2 px-4 py-2 bg-brand-600 hover:bg-brand-700 text-white text-sm font-semibold rounded-xl shadow-sm transition">
                        Add Questions to Bank
                    </a>
                </div>
            </div>
        <?php else: ?>
            <?php 
                $selectedMap = [];
                foreach ($selectedQuestions as $sq) {
                    $selectedMap[$sq->questionId] = $sq;
                }
            ?>

            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="p-4 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
                    <span class="text-xs font-bold text-slate-700 uppercase tracking-wider">
                        Available Bank Questions (<?= count($availableQuestions) ?>)
                    </span>
                    <span class="text-xs text-slate-500">
                        Check questions to include in this quiz assessment
                    </span>
                </div>

                <div class="divide-y divide-slate-100">
                    <?php foreach ($availableQuestions as $idx => $q): 
                        $isSelected = isset($selectedMap[$q->id]);
                        $assignedPoints = $isSelected ? $selectedMap[$q->id]->points : $q->defaultPoints;
                        $sortOrder = $isSelected ? $selectedMap[$q->id]->sortOrder : ($idx + 1);
                    ?>
                        <div class="p-5 flex items-start gap-4 hover:bg-slate-50/50 transition">
                            <div class="pt-1">
                                <input type="checkbox" id="q_check_<?= $q->id ?>" name="questions[<?= $q->id ?>][question_id]" value="<?= $q->id ?>" <?= $isSelected ? 'checked' : '' ?> class="w-5 h-5 text-brand-600 rounded border-slate-300 focus:ring-brand-500" onchange="toggleQuestionRow(<?= $q->id ?>, this.checked)">
                            </div>

                            <div class="flex-1 space-y-2">
                                <div class="flex items-center gap-2">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold <?= $q->isMcq() ? 'bg-indigo-50 text-indigo-700 border border-indigo-200' : 'bg-emerald-50 text-emerald-700 border border-emerald-200' ?>">
                                        <?= $q->isMcq() ? 'MCQ' : 'Short Answer' ?>
                                    </span>
                                    <?php if ($q->topic): ?>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-600">
                                            <?= e($q->topic) ?>
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <label for="q_check_<?= $q->id ?>" class="block text-sm font-medium text-slate-900 cursor-pointer">
                                    <?= nl2br(e($q->questionText)) ?>
                                </label>

                                <?php if ($q->isMcq() && !empty($q->options)): ?>
                                    <div class="text-xs text-slate-500 flex flex-wrap gap-2 pt-1">
                                        <?php foreach ($q->options as $opt): ?>
                                            <span class="px-2 py-1 rounded-md border <?= $opt->isCorrect ? 'bg-emerald-50 border-emerald-200 text-emerald-800 font-semibold' : 'bg-slate-50 border-slate-200 text-slate-600' ?>">
                                                <?= $opt->isCorrect ? '✓ ' : '' ?><?= e($opt->optionText) ?>
                                            </span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Points & Order Inputs -->
                            <div class="w-40 flex items-center gap-2 pt-1">
                                <div>
                                    <label class="block text-[10px] font-semibold text-slate-400 uppercase tracking-wider mb-0.5">Points</label>
                                    <input type="number" step="0.25" min="0.25" name="questions[<?= $q->id ?>][points]" value="<?= e((string)$assignedPoints) ?>" class="w-20 rounded-lg border-slate-300 shadow-sm text-xs focus:border-brand-500 focus:ring-brand-500" <?= $isSelected ? '' : 'disabled' ?> id="pts_<?= $q->id ?>">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-semibold text-slate-400 uppercase tracking-wider mb-0.5">Order</label>
                                    <input type="number" min="1" name="questions[<?= $q->id ?>][sort_order]" value="<?= e((string)$sortOrder) ?>" class="w-16 rounded-lg border-slate-300 shadow-sm text-xs focus:border-brand-500 focus:ring-brand-500" <?= $isSelected ? '' : 'disabled' ?> id="ord_<?= $q->id ?>">
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Sticky Bottom Bar -->
            <div class="flex items-center justify-between p-4 bg-white rounded-2xl border border-slate-200 shadow-sm">
                <span class="text-sm font-medium text-slate-600">
                    Total Maximum Score: <strong class="text-slate-900"><?= number_format($quiz->getTotalMaxScore(), 2) ?> pts</strong>
                </span>

                <div class="flex items-center gap-3">
                    <a href="/teacher/quizzes" class="px-4 py-2 text-sm font-semibold text-slate-600 hover:text-slate-900">
                        Cancel
                    </a>
                    <button type="submit" class="px-5 py-2.5 bg-brand-600 hover:bg-brand-700 text-white text-sm font-semibold rounded-xl shadow-sm transition">
                        Save Quiz Questions
                    </button>
                </div>
            </div>
        <?php endif; ?>
    </form>
</div>

<script>
function toggleQuestionRow(qId, checked) {
    const ptsInput = document.getElementById('pts_' + qId);
    const ordInput = document.getElementById('ord_' + qId);
    if (ptsInput && ordInput) {
        ptsInput.disabled = !checked;
        ordInput.disabled = !checked;
    }
}
</script>
