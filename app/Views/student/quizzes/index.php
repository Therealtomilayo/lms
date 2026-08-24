<div class="space-y-6">
    <!-- Header Card -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <!-- Breadcrumbs -->
                <nav class="flex items-center gap-2 text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">
                    <a href="/student/dashboard" class="hover:text-sky-600 transition">Student Portal</a>
                    <span class="text-slate-300">/</span>
                    <span class="text-slate-700">Online CBT Exams</span>
                </nav>
                <div class="flex flex-wrap items-center gap-3">
                    <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight leading-tight">
                        Computer-Based Assessments
                    </h1>
                    <?php if ($student && $student->schoolClass): ?>
                        <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold bg-sky-50 text-sky-800 border border-sky-200">
                            <?= htmlspecialchars($student->schoolClass->name) ?>
                        </span>
                    <?php endif; ?>
                </div>
                <p class="text-xs text-slate-500 mt-1">
                    Take timed online assessments, monitor ongoing tests, and review performance reports.
                </p>
            </div>

            <div class="flex items-center gap-2.5 flex-wrap">
                <a href="/student/subjects" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-bold text-slate-700 bg-slate-100 hover:bg-slate-200 transition">
                    <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                    </svg>
                    <span>My Subjects</span>
                </a>
            </div>
        </div>
    </div>

    <!-- 4-Card KPI Summary Metrics Strip -->
    <?php
        $activeCount = count($activeQuizzes);
        $completedCount = count($completedQuizzes);
        $inProgressCount = 0;
        foreach ($activeQuizzes as $item) {
            if ($item['has_active_attempt'] ?? false) {
                $inProgressCount++;
            }
        }
    ?>
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Available CBTs -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs">
            <p class="text-xs font-bold uppercase tracking-wider text-emerald-600">Available CBTs</p>
            <div class="flex items-baseline gap-2 mt-1">
                <h3 class="text-2xl font-extrabold text-slate-900"><?= number_format($activeCount) ?></h3>
                <span class="text-xs font-semibold text-slate-500">tests</span>
            </div>
            <span class="text-[11px] font-medium text-emerald-600 mt-1 block">
                Ready to take
            </span>
        </div>

        <!-- In Progress -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs">
            <p class="text-xs font-bold uppercase tracking-wider text-amber-500">In Progress</p>
            <div class="flex items-baseline gap-2 mt-1">
                <h3 class="text-2xl font-extrabold text-amber-600"><?= number_format($inProgressCount) ?></h3>
                <span class="text-xs font-semibold text-slate-500">attempts</span>
            </div>
            <span class="text-[11px] font-medium text-amber-600 mt-1 block">
                <?= $inProgressCount > 0 ? 'Resume active test &rarr;' : 'No paused attempts' ?>
            </span>
        </div>

        <!-- Completed Assessments -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs">
            <p class="text-xs font-bold uppercase tracking-wider text-sky-600">Completed</p>
            <div class="flex items-baseline gap-2 mt-1">
                <h3 class="text-2xl font-extrabold text-slate-900"><?= number_format($completedCount) ?></h3>
                <span class="text-xs font-semibold text-slate-500">exams</span>
            </div>
            <span class="text-[11px] font-medium text-slate-500 mt-1 block">
                Archived submissions
            </span>
        </div>

        <!-- Academic Term -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs">
            <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Current Term</p>
            <div class="flex items-baseline gap-2 mt-1">
                <h3 class="text-base font-extrabold text-slate-900 truncate"><?= htmlspecialchars($activeTerm?->name ?? 'Current Term') ?></h3>
            </div>
            <span class="text-[11px] font-medium text-slate-500 mt-1 block">
                <?= htmlspecialchars($activeSession?->name ?? 'Active Session') ?>
            </span>
        </div>
    </div>

    <!-- Search Bar -->
    <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-xs">
        <div class="relative">
            <input type="text" id="quiz-search" placeholder="Search CBT assessment title or subject..." 
                   oninput="filterQuizCards(this.value)"
                   class="w-full text-xs font-semibold rounded-xl border border-slate-300 bg-slate-50 focus:bg-white focus:border-sky-500 focus:ring-sky-500 py-2.5 pl-10 pr-4 transition">
            <svg class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
        </div>
    </div>

    <!-- Available Assessments Section -->
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <h2 class="text-base font-bold text-slate-900 flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                <span>Active & Available Assessments</span>
                <span class="text-xs font-bold px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-800 border border-emerald-200">
                    <?= count($activeQuizzes) ?>
                </span>
            </h2>
        </div>

        <?php if (empty($activeQuizzes)): ?>
            <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-12 text-center">
                <div class="w-14 h-14 rounded-2xl bg-slate-100 text-slate-400 mx-auto flex items-center justify-center mb-3">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                </div>
                <h3 class="text-base font-bold text-slate-900">No Active CBT Assessments</h3>
                <p class="text-xs text-slate-500 mt-1">There are no online quizzes or exams currently available for your cohort.</p>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" id="active-quizzes-grid">
                <?php foreach ($activeQuizzes as $item): 
                    $quiz = $item['quiz'];
                    $hasActive = $item['has_active_attempt'] ?? false;
                ?>
                    <div class="quiz-card bg-white rounded-2xl border border-slate-200 shadow-xs flex flex-col justify-between hover:border-slate-300 transition overflow-hidden p-6 space-y-4"
                         data-search="<?= strtolower(htmlspecialchars($quiz->title . ' ' . ($quiz->term?->name ?? '') . ' ' . ($quiz->classSubject?->subjectName ?? ''))) ?>">
                        <div>
                            <div class="flex items-center justify-between gap-2 mb-2">
                                <span class="px-2.5 py-1 rounded-lg text-[10px] font-bold uppercase tracking-wider bg-sky-50 text-sky-800 border border-sky-200">
                                    <?= htmlspecialchars($quiz->classSubject?->subjectName ?? $quiz->term?->name ?? 'Quiz') ?>
                                </span>
                                <?php if ($hasActive): ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-lg text-xs font-bold bg-amber-50 text-amber-800 border border-amber-200 animate-pulse">
                                        In Progress
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-lg text-xs font-bold bg-emerald-50 text-emerald-800 border border-emerald-200">
                                        <?= (int)$item['attempts_taken'] ?> / <?= (int)$item['max_attempts'] ?> Attempt<?= $item['max_attempts'] > 1 ? 's' : '' ?>
                                    </span>
                                <?php endif; ?>
                            </div>

                            <h3 class="text-base font-bold text-slate-900 leading-snug">
                                <a href="<?= $hasActive ? "/student/quiz-attempts/{$item['active_attempt_id']}" : "/student/quizzes/{$quiz->id}" ?>" class="hover:text-sky-600 transition">
                                    <?= htmlspecialchars($quiz->title) ?>
                                </a>
                            </h3>

                            <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500 font-medium">
                                <span class="flex items-center gap-1">
                                    <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    <?= $quiz->hasTimeLimit() ? "{$quiz->timeLimitMinutes} Mins" : 'Untimed' ?>
                                </span>
                                <span><?= count($quiz->quizQuestions) ?> Questions</span>
                            </div>
                        </div>

                        <div class="pt-3 border-t border-slate-100">
                            <?php if ($hasActive): ?>
                                <a href="/student/quiz-attempts/<?= (int)$item['active_attempt_id'] ?>" 
                                   class="w-full inline-flex items-center justify-center gap-1.5 py-2.5 px-4 bg-amber-600 hover:bg-amber-700 text-white text-xs font-bold rounded-xl shadow-xs transition">
                                    <span>Resume Active Attempt</span>
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                                    </svg>
                                </a>
                            <?php else: ?>
                                <a href="/student/quizzes/<?= (int)$quiz->id ?>" 
                                   class="w-full inline-flex items-center justify-center gap-1.5 py-2.5 px-4 bg-sky-600 hover:bg-sky-700 text-white text-xs font-bold rounded-xl shadow-xs transition">
                                    <span>View Instructions & Take</span>
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                                    </svg>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Completed Assessments Section -->
    <?php if (!empty($completedQuizzes)): ?>
        <div class="space-y-4 pt-6 border-t border-slate-200">
            <div class="flex items-center justify-between">
                <h2 class="text-base font-bold text-slate-800 flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-slate-400"></span>
                    <span>Completed Assessments & Results</span>
                    <span class="text-xs font-bold px-2 py-0.5 rounded-full bg-slate-100 text-slate-600">
                        <?= count($completedQuizzes) ?>
                    </span>
                </h2>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($completedQuizzes as $item): 
                    $quiz = $item['quiz'];
                    $latest = $item['latest_attempt'];
                ?>
                    <div class="quiz-card bg-white rounded-2xl border border-slate-200 shadow-xs p-6 space-y-4 flex flex-col justify-between"
                         data-search="<?= strtolower(htmlspecialchars($quiz->title)) ?>">
                        <div class="space-y-2">
                            <div class="flex items-center justify-between gap-2">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-slate-100 text-slate-700">
                                    <?= htmlspecialchars($quiz->classSubject?->subjectName ?? 'Completed') ?>
                                </span>
                                <span class="text-[11px] font-bold text-slate-400">Attempts Limit Reached</span>
                            </div>

                            <h3 class="text-base font-bold text-slate-900 leading-snug">
                                <?= htmlspecialchars($quiz->title) ?>
                            </h3>

                            <?php if ($latest && $latest->isSubmitted()): ?>
                                <div class="p-3 bg-slate-50 rounded-xl border border-slate-100 flex items-center justify-between text-xs mt-3">
                                    <span class="text-slate-500 font-medium">Final Recorded Score:</span>
                                    <strong class="text-slate-900 text-sm font-extrabold">
                                        <?= $latest->score !== null ? number_format((float)$latest->score, 2) . ' / ' . number_format((float)$latest->maxScore, 2) : 'Under Review' ?>
                                    </strong>
                                </div>
                            <?php endif; ?>
                        </div>

                        <?php if ($latest && $latest->isSubmitted()): ?>
                            <div class="pt-3 border-t border-slate-100">
                                <a href="/student/quiz-attempts/<?= (int)$latest->id ?>/result" 
                                   class="w-full inline-flex items-center justify-center gap-1.5 py-2 px-4 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl transition">
                                    <span>View Result Breakdown</span>
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                                    </svg>
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
function filterQuizCards(query) {
    const q = query.trim().toLowerCase();
    const cards = document.querySelectorAll('.quiz-card');
    cards.forEach(card => {
        const text = card.getAttribute('data-search') || '';
        if (!q || text.includes(q)) {
            card.style.display = 'flex';
        } else {
            card.style.display = 'none';
        }
    });
}
</script>
