<div class="space-y-6">
    <!-- Header Card -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <!-- Breadcrumbs -->
                <nav class="flex items-center gap-2 text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">
                    <a href="/teacher/dashboard" class="text-slate-400 hover:text-emerald-600 transition">Faculty Portal</a>
                    <span class="text-slate-300">/</span>
                    <span class="text-slate-700">Question Bank</span>
                </nav>
                <div class="flex flex-wrap items-center gap-3">
                    <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight leading-tight">
                        Assessment Question Bank
                    </h1>
                    <?php if (!empty($subjects) && isset($subjects[$selectedSubjectId])): ?>
                        <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold bg-emerald-50 text-emerald-800 border border-emerald-200">
                            <?= htmlspecialchars($subjects[$selectedSubjectId]->name) ?> (<?= htmlspecialchars($subjects[$selectedSubjectId]->code) ?>)
                        </span>
                    <?php endif; ?>
                </div>
                <p class="text-xs text-slate-500 mt-1">
                    Manage reusable assessment items, multiple-choice options with randomized keys, and short-answer prompts.
                </p>
            </div>

            <div class="flex items-center gap-2.5 flex-wrap">
                <a href="/teacher/question-bank/bulk<?= $selectedSubjectId ? '?subject_id=' . (int)$selectedSubjectId : '' ?>" 
                   class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-bold text-slate-700 bg-slate-100 hover:bg-slate-200 transition">
                    <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                    </svg>
                    <span>Bulk Import</span>
                </a>

                <?php $this->include('components/button', [
                    'label' => 'Add Question',
                    'variant' => 'primary',
                    'href' => '/teacher/question-bank/create' . ($selectedSubjectId ? '?subject_id=' . (int)$selectedSubjectId : ''),
                    'icon' => '<svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>'
                ]); ?>
            </div>
        </div>
    </div>

    <!-- 4-Card KPI Summary Metrics Strip -->
    <?php
        $totalCount = count($questions);
        $mcqCount = 0;
        $shortCount = 0;
        foreach ($questions as $q) {
            if ($q->isMcq()) {
                $mcqCount++;
            } else {
                $shortCount++;
            }
        }
        $topicCount = count($topics);
    ?>
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Total Questions in Subject -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs">
            <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Total Items</p>
            <div class="flex items-baseline gap-2 mt-1">
                <h3 class="text-2xl font-extrabold text-slate-900"><?= number_format($totalCount) ?></h3>
                <span class="text-xs font-semibold text-slate-500">questions</span>
            </div>
            <span class="text-[11px] font-medium text-slate-500 mt-1 block">
                Repository questions
            </span>
        </div>

        <!-- MCQ Questions -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs">
            <p class="text-xs font-bold uppercase tracking-wider text-indigo-600">Multiple Choice</p>
            <div class="flex items-baseline gap-2 mt-1">
                <h3 class="text-2xl font-extrabold text-indigo-600"><?= number_format($mcqCount) ?></h3>
                <span class="text-xs font-semibold text-slate-500">MCQ items</span>
            </div>
            <span class="text-[11px] font-medium text-indigo-600/90 mt-1 block">
                Auto-graded during CBT
            </span>
        </div>

        <!-- Short Answer Prompts -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs">
            <p class="text-xs font-bold uppercase tracking-wider text-emerald-600">Short Answer</p>
            <div class="flex items-baseline gap-2 mt-1">
                <h3 class="text-2xl font-extrabold text-emerald-600"><?= number_format($shortCount) ?></h3>
                <span class="text-xs font-semibold text-slate-500">prompts</span>
            </div>
            <span class="text-[11px] font-medium text-emerald-600 mt-1 block">
                Teacher manual evaluation
            </span>
        </div>

        <!-- Topics Covered -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs">
            <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Topics Tagged</p>
            <div class="flex items-baseline gap-2 mt-1">
                <h3 class="text-2xl font-extrabold text-slate-900"><?= number_format($topicCount) ?></h3>
                <span class="text-xs font-semibold text-slate-500">categories</span>
            </div>
            <span class="text-[11px] font-medium text-slate-500 mt-1 block">
                Curriculum syllabus tags
            </span>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs">
        <form method="GET" action="/teacher/question-bank" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 items-end">
            <!-- Subject Selector -->
            <div>
                <label for="subject_id" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                    Target Subject
                </label>
                <select name="subject_id" id="subject_id" class="w-full text-xs font-semibold rounded-xl border border-slate-300 bg-slate-50 focus:bg-white focus:border-emerald-500 focus:ring-emerald-500 py-2.5 px-3 transition" onchange="this.form.submit()">
                    <?php foreach ($subjects as $s): ?>
                        <option value="<?= (int)$s->id ?>" <?= $selectedSubjectId === (int)$s->id ? 'selected' : '' ?>>
                            <?= htmlspecialchars($s->name) ?> (<?= htmlspecialchars($s->code) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Topic Filter -->
            <div>
                <label for="topic" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                    Curriculum Topic
                </label>
                <select name="topic" id="topic" class="w-full text-xs font-semibold rounded-xl border border-slate-300 bg-slate-50 focus:bg-white focus:border-emerald-500 focus:ring-emerald-500 py-2.5 px-3 transition" onchange="this.form.submit()">
                    <option value="">All Syllabus Topics</option>
                    <?php foreach ($topics as $t): ?>
                        <option value="<?= htmlspecialchars((string)$t) ?>" <?= $selectedTopic === $t ? 'selected' : '' ?>><?= htmlspecialchars((string)$t) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Type Filter -->
            <div>
                <label for="type" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                    Question Type
                </label>
                <select name="type" id="type" class="w-full text-xs font-semibold rounded-xl border border-slate-300 bg-slate-50 focus:bg-white focus:border-emerald-500 focus:ring-emerald-500 py-2.5 px-3 transition" onchange="this.form.submit()">
                    <option value="">All Question Types</option>
                    <option value="mcq" <?= $selectedType === 'mcq' ? 'selected' : '' ?>>Multiple Choice (MCQ)</option>
                    <option value="short_answer" <?= $selectedType === 'short_answer' ? 'selected' : '' ?>>Short Answer</option>
                </select>
            </div>

            <!-- Search Input -->
            <div>
                <label for="search" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                    Search Question
                </label>
                <div class="flex gap-2">
                    <input type="text" name="search" id="search" value="<?= htmlspecialchars((string)($search ?? '')) ?>" placeholder="Search keywords..." 
                           class="w-full text-xs font-medium rounded-xl border border-slate-300 bg-slate-50 focus:bg-white focus:border-emerald-500 focus:ring-emerald-500 py-2.5 px-3 transition">
                    <button type="submit" class="px-4 py-2.5 bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold rounded-xl shadow-xs transition">
                        Filter
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Questions List -->
    <?php if (empty($questions)): ?>
        <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-12 text-center">
            <div class="w-16 h-16 rounded-2xl bg-emerald-50 text-emerald-600 mx-auto flex items-center justify-center mb-4">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <h3 class="text-base font-bold text-slate-900">No Assessment Questions Found</h3>
            <p class="text-xs text-slate-500 mt-1.5 max-w-sm mx-auto">
                There are no questions matching your filter criteria. Add your first item to build reusable CBT assessments.
            </p>
            <div class="mt-6">
                <?php $this->include('components/button', [
                    'label' => 'Create Question',
                    'variant' => 'primary',
                    'href' => '/teacher/question-bank/create' . ($selectedSubjectId ? '?subject_id=' . (int)$selectedSubjectId : ''),
                    'icon' => '<svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>'
                ]); ?>
            </div>
        </div>
    <?php else: ?>
        <div class="space-y-4">
            <?php foreach ($questions as $index => $q): ?>
                <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6 hover:border-slate-300 transition space-y-4">
                    <!-- Item Header & Metadata -->
                    <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4 pb-3 border-b border-slate-100">
                        <div class="flex items-center gap-2.5 flex-wrap">
                            <span class="w-7 h-7 rounded-lg bg-slate-100 text-slate-700 font-mono font-bold text-xs flex items-center justify-center flex-shrink-0">
                                #<?= $index + 1 ?>
                            </span>

                            <?php if ($q->isMcq()): ?>
                                <?php $this->include('components/badge', [
                                    'label' => 'Multiple Choice (MCQ)',
                                    'variant' => 'info',
                                    'size' => 'sm'
                                ]); ?>
                            <?php else: ?>
                                <?php $this->include('components/badge', [
                                    'label' => 'Short Answer',
                                    'variant' => 'success',
                                    'size' => 'sm'
                                ]); ?>
                            <?php endif; ?>

                            <?php if ($q->topic): ?>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-lg text-xs font-semibold bg-slate-100 text-slate-700 border border-slate-200">
                                    Topic: <?= htmlspecialchars($q->topic) ?>
                                </span>
                            <?php endif; ?>

                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-lg text-xs font-bold bg-amber-50 text-amber-800 border border-amber-200">
                                <?= number_format((float)$q->defaultPoints, 2) ?> PTS
                            </span>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex items-center gap-2 self-start">
                            <a href="/teacher/question-bank/<?= (int)$q->id ?>/edit" 
                               class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-lg transition">
                                <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                                <span>Edit</span>
                            </a>

                            <form method="POST" action="/teacher/question-bank/<?= (int)$q->id ?>/delete" onsubmit="return confirm('Are you sure you want to permanently delete this question from the Question Bank?');">
                                <?= csrf_field() ?>
                                <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-rose-50 hover:bg-rose-100 text-rose-700 text-xs font-bold rounded-lg border border-rose-200 transition">
                                    <svg class="w-3.5 h-3.5 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                    <span>Delete</span>
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Question Prompt Body -->
                    <div>
                        <p class="text-sm font-semibold text-slate-900 leading-relaxed">
                            <?= nl2br(htmlspecialchars($q->questionText)) ?>
                        </p>
                    </div>

                    <!-- MCQ Options Grid -->
                    <?php if ($q->isMcq() && !empty($q->options)): ?>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 pt-2">
                            <?php foreach ($q->options as $optIdx => $opt): ?>
                                <?php $isCorrect = (bool)$opt->isCorrect; ?>
                                <div class="flex items-center gap-2.5 p-3 rounded-xl text-xs border <?= $isCorrect ? 'bg-emerald-50/80 border-emerald-300 text-emerald-950 font-bold' : 'bg-slate-50/80 border-slate-200 text-slate-700 font-medium' ?>">
                                    <span class="w-5 h-5 rounded-lg flex items-center justify-center text-[10px] font-extrabold flex-shrink-0 <?= $isCorrect ? 'bg-emerald-600 text-white' : 'bg-white border border-slate-300 text-slate-600' ?>">
                                        <?= $isCorrect ? '✓' : chr(65 + $optIdx) ?>
                                    </span>
                                    <span class="flex-1 truncate"><?= htmlspecialchars($opt->optionText) ?></span>
                                    <?php if ($isCorrect): ?>
                                        <span class="text-[10px] font-bold uppercase tracking-wider text-emerald-700 bg-emerald-100 px-1.5 py-0.5 rounded">Correct</span>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
