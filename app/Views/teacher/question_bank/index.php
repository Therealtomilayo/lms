<div class="space-y-6">
    <!-- Header Controls -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-slate-900 tracking-tight">Question Bank</h2>
            <p class="text-sm text-slate-500 mt-1">Manage reusable assessment questions, MCQs with randomized choices, and short-answer prompts.</p>
        </div>
        <div>
            <a href="/teacher/question-bank/create<?= $selectedSubjectId ? '?subject_id=' . $selectedSubjectId : '' ?>" 
               class="inline-flex items-center gap-2 px-4 py-2.5 bg-brand-600 hover:bg-brand-700 text-white text-sm font-semibold rounded-xl shadow-sm transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Add Question
            </a>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm">
        <form method="GET" action="/teacher/question-bank" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
            <div>
                <label for="subject_id" class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1">Subject</label>
                <select name="subject_id" id="subject_id" class="w-full text-sm rounded-xl border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500" onchange="this.form.submit()">
                    <?php foreach ($subjects as $s): ?>
                        <option value="<?= $s->id ?>" <?= $selectedSubjectId === $s->id ? 'selected' : '' ?>>
                            <?= e($s->name) ?> (<?= e($s->code) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label for="topic" class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1">Topic</label>
                <select name="topic" id="topic" class="w-full text-sm rounded-xl border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500" onchange="this.form.submit()">
                    <option value="">All Topics</option>
                    <?php foreach ($topics as $t): ?>
                        <option value="<?= e($t) ?>" <?= $selectedTopic === $t ? 'selected' : '' ?>><?= e($t) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label for="type" class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1">Question Type</label>
                <select name="type" id="type" class="w-full text-sm rounded-xl border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500" onchange="this.form.submit()">
                    <option value="">All Types</option>
                    <option value="mcq" <?= $selectedType === 'mcq' ? 'selected' : '' ?>>Multiple Choice (MCQ)</option>
                    <option value="short_answer" <?= $selectedType === 'short_answer' ? 'selected' : '' ?>>Short Answer</option>
                </select>
            </div>

            <div>
                <label for="search" class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1">Search</label>
                <div class="flex gap-2">
                    <input type="text" name="search" id="search" value="<?= e($search ?? '') ?>" placeholder="Search text..." class="w-full text-sm rounded-xl border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">
                    <button type="submit" class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-semibold rounded-xl transition">
                        Go
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Questions Table / Cards -->
    <?php if (empty($questions)): ?>
        <div class="bg-white rounded-2xl border border-slate-200 p-12 text-center shadow-sm">
            <div class="w-16 h-16 rounded-full bg-brand-50 text-brand-500 mx-auto flex items-center justify-center mb-4">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <h3 class="text-base font-semibold text-slate-800">No Questions Found</h3>
            <p class="text-sm text-slate-500 mt-1 max-w-sm mx-auto">Add your first question to the bank to build dynamic and randomized quizzes.</p>
            <div class="mt-6">
                <a href="/teacher/question-bank/create<?= $selectedSubjectId ? '?subject_id=' . $selectedSubjectId : '' ?>" 
                   class="inline-flex items-center gap-2 px-4 py-2 bg-brand-600 hover:bg-brand-700 text-white text-sm font-semibold rounded-xl shadow-sm transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Create Question
                </a>
            </div>
        </div>
    <?php else: ?>
        <div class="space-y-4">
            <?php foreach ($questions as $index => $q): ?>
                <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm hover:border-slate-300 transition">
                    <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4">
                        <div class="flex-1">
                            <div class="flex items-center gap-2 mb-2">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold <?= $q->isMcq() ? 'bg-indigo-50 text-indigo-700 border border-indigo-200' : 'bg-emerald-50 text-emerald-700 border border-emerald-200' ?>">
                                    <?= $q->isMcq() ? 'Multiple Choice' : 'Short Answer' ?>
                                </span>
                                <?php if ($q->topic): ?>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-600">
                                        <?= e($q->topic) ?>
                                    </span>
                                <?php endif; ?>
                                <span class="text-xs text-slate-400 font-medium">
                                    <?= number_format($q->defaultPoints, 2) ?> pt<?= $q->defaultPoints != 1 ? 's' : '' ?>
                                </span>
                            </div>

                            <p class="text-base font-medium text-slate-900 leading-relaxed">
                                <?= nl2br(e($q->questionText)) ?>
                            </p>

                            <?php if ($q->isMcq() && !empty($q->options)): ?>
                                <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-2">
                                    <?php foreach ($q->options as $opt): ?>
                                        <div class="flex items-center gap-2 p-2 rounded-xl text-sm border <?= $opt->isCorrect ? 'bg-emerald-50 border-emerald-200 text-emerald-900 font-medium' : 'bg-slate-50 border-slate-200 text-slate-700' ?>">
                                            <span class="w-4 h-4 rounded-full flex items-center justify-center text-xs <?= $opt->isCorrect ? 'bg-emerald-500 text-white' : 'border border-slate-300' ?>">
                                                <?= $opt->isCorrect ? '✓' : '' ?>
                                            </span>
                                            <span><?= e($opt->optionText) ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center gap-2 sm:self-start">
                            <a href="/teacher/question-bank/<?= $q->id ?>/edit" 
                               class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded-lg transition">
                                Edit
                            </a>
                            <form method="POST" action="/teacher/question-bank/<?= $q->id ?>/delete" onsubmit="return confirm('Are you sure you want to delete this question?');">
                                <input type="hidden" name="csrf_token" value="<?= e(\App\Middleware\CsrfMiddleware::getToken()) ?>">
                                <button type="submit" class="px-3 py-1.5 bg-rose-50 hover:bg-rose-100 text-rose-600 text-xs font-semibold rounded-lg transition">
                                    Delete
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
