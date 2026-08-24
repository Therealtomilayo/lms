<div class="max-w-3xl mx-auto space-y-6">
    <!-- Header Card -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <!-- Breadcrumbs -->
                <nav class="flex items-center gap-2 text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">
                    <a href="/student/dashboard" class="hover:text-sky-600 transition">Student Portal</a>
                    <span class="text-slate-300">/</span>
                    <a href="/student/quizzes" class="hover:text-sky-600 transition">CBT Exams</a>
                    <span class="text-slate-300">/</span>
                    <span class="text-slate-700">Exam Instructions</span>
                </nav>
                <div class="flex flex-wrap items-center gap-3">
                    <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight leading-tight">
                        <?= htmlspecialchars($quiz->title) ?>
                    </h1>
                    <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold bg-sky-50 text-sky-800 border border-sky-200">
                        <?= htmlspecialchars($quiz->classSubject?->subjectName ?? $quiz->term?->name ?? 'Assessment') ?>
                    </span>
                </div>
                <p class="text-xs text-slate-500 mt-1 flex items-center gap-2 flex-wrap">
                    <span>Teacher: <strong class="text-slate-800 font-semibold"><?= htmlspecialchars($quiz->teacher?->name ?? 'Faculty Staff') ?></strong></span>
                    <span>&bull;</span>
                    <span>Term: <strong><?= htmlspecialchars($activeTerm?->name ?? 'Current Term') ?></strong></span>
                </p>
            </div>

            <div class="flex items-center gap-2.5 flex-wrap">
                <?php $this->include('components/button', [
                    'label' => 'Back to CBT Catalog',
                    'variant' => 'secondary',
                    'href' => '/student/quizzes',
                    'icon' => '<svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>'
                ]); ?>
            </div>
        </div>
    </div>

    <!-- 4-Card KPI Summary Metrics Strip -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <!-- Duration -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs">
            <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Time Limit</p>
            <div class="flex items-baseline gap-1 mt-1">
                <h3 class="text-2xl font-extrabold text-slate-900">
                    <?= $quiz->hasTimeLimit() ? (int)$quiz->timeLimitMinutes : 'Untimed' ?>
                </h3>
                <?php if ($quiz->hasTimeLimit()): ?>
                    <span class="text-xs font-semibold text-slate-500">mins</span>
                <?php endif; ?>
            </div>
            <span class="text-[11px] font-medium text-sky-600 mt-1 block">
                Countdown Timer
            </span>
        </div>

        <!-- Total Questions -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs">
            <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Total Items</p>
            <div class="flex items-baseline gap-1 mt-1">
                <h3 class="text-2xl font-extrabold text-slate-900"><?= count($quiz->quizQuestions) ?></h3>
                <span class="text-xs font-semibold text-slate-500">questions</span>
            </div>
            <span class="text-[11px] font-medium text-slate-500 mt-1 block">
                Exam Palette
            </span>
        </div>

        <!-- Attempts Allowed -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs">
            <p class="text-xs font-bold uppercase tracking-wider text-amber-500">Attempts</p>
            <div class="flex items-baseline gap-1 mt-1">
                <h3 class="text-2xl font-extrabold text-amber-600"><?= count($attempts) ?> / <?= (int)$quiz->maxAttempts ?></h3>
            </div>
            <span class="text-[11px] font-medium text-slate-500 mt-1 block">
                Used vs Max Limit
            </span>
        </div>

        <!-- Max Points -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs">
            <p class="text-xs font-bold uppercase tracking-wider text-emerald-600">Total Marks</p>
            <div class="flex items-baseline gap-1 mt-1">
                <h3 class="text-2xl font-extrabold text-emerald-600"><?= number_format($quiz->getTotalMaxScore(), 1) ?></h3>
                <span class="text-xs font-semibold text-slate-500">PTS</span>
            </div>
            <span class="text-[11px] font-medium text-emerald-600 mt-1 block">
                Assessment Weight
            </span>
        </div>
    </div>

    <!-- Instructions & Rules Card -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6 sm:p-8 space-y-6">
        <div>
            <h2 class="text-base font-bold text-slate-900 mb-3">Pre-Exam Instructions & Guidelines</h2>
            <div class="p-5 bg-slate-50 rounded-2xl border border-slate-200 text-sm text-slate-700 space-y-3 leading-relaxed">
                <?php if (!empty($quiz->instructions)): ?>
                    <p class="font-medium text-slate-900"><?= nl2br(htmlspecialchars($quiz->instructions)) ?></p>
                <?php else: ?>
                    <p>Read all questions carefully. Choose the single best answer for multiple-choice questions or write your answer clearly for short-answer questions.</p>
                <?php endif; ?>

                <div class="pt-3 border-t border-slate-200 space-y-2 text-xs text-slate-600">
                    <p class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-emerald-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        <span>Once you click <strong>Start Assessment Now</strong>, the server-authoritative timer begins immediately.</span>
                    </p>
                    <p class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-emerald-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        <span>Your answers are automatically saved in the background as you select or type them.</span>
                    </p>
                    <p class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-amber-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        <span>Do not close or reload the test window during the exam. Submit when completed.</span>
                    </p>
                </div>
            </div>
        </div>

        <!-- Action Submission / Start Button -->
        <div class="pt-4 border-t border-slate-100 flex items-center justify-between flex-wrap gap-4">
            <a href="/student/quizzes" class="text-xs font-bold text-slate-500 hover:text-slate-800 transition">
                &larr; Return to Catalog
            </a>

            <?php if ($activeAttempt): ?>
                <a href="/student/quiz-attempts/<?= (int)$activeAttempt->id ?>" 
                   class="px-6 py-3 bg-amber-600 hover:bg-amber-700 text-white text-xs font-bold rounded-xl shadow-xs transition inline-flex items-center gap-2">
                    <span>Resume Active Attempt &rarr;</span>
                </a>
            <?php elseif ($canStart): ?>
                <form method="POST" action="/student/quizzes/<?= (int)$quiz->id ?>/attempts">
                    <?= csrf_field() ?>
                    <button type="submit" class="px-6 py-3 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl shadow-xs transition inline-flex items-center gap-2">
                        <span>Start Assessment Now</span>
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                        </svg>
                    </button>
                </form>
            <?php else: ?>
                <button disabled class="px-6 py-3 bg-slate-200 text-slate-400 text-xs font-bold rounded-xl cursor-not-allowed">
                    Maximum Attempt Limit Reached
                </button>
            <?php endif; ?>
        </div>
    </div>
</div>
