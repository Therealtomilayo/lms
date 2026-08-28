<div class="space-y-6">
    <!-- Header Card -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <!-- Breadcrumbs -->
                <nav class="flex items-center gap-2 text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">
                    <a href="/student/dashboard" class="hover:text-sky-600 transition">Student Portal</a>
                    <span class="text-slate-300">/</span>
                    <a href="/student/subjects" class="hover:text-sky-600 transition">Subjects</a>
                    <span class="text-slate-300">/</span>
                    <span class="text-slate-700">Course Workspace</span>
                </nav>
                <?php 
                    $sName = $classSubject->subject?->name ?? 'Subject';
                    $sCode = $classSubject->subject?->code ?? '';
                    $cName = $classSubject->schoolClass?->name ?? 'Class';
                    $tName = $classSubject->teacher?->user?->name ?? 'Faculty Staff';
                ?>
                <div class="flex flex-wrap items-center gap-3">
                    <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight leading-tight">
                        <?= htmlspecialchars($sName) ?>
                    </h1>
                    <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold bg-sky-50 text-sky-800 border border-sky-200">
                        <?= htmlspecialchars($sCode ?: 'SUBJ') ?>
                    </span>
                    <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold bg-slate-100 text-slate-700 border border-slate-200">
                        <?= htmlspecialchars($cName) ?>
                    </span>
                </div>
                <p class="text-xs text-slate-500 mt-1 flex items-center gap-2">
                    <span>Instructor: <strong class="text-slate-800 font-semibold"><?= htmlspecialchars($tName) ?></strong></span>
                    <span>&bull;</span>
                    <span>Term: <strong class="text-emerald-700 font-semibold"><?= htmlspecialchars($activeTerm?->name ?? 'Current Term') ?></strong></span>
                </p>
            </div>

            <div class="flex items-center gap-2.5">
                <?php $this->include('components/button', [
                    'label' => 'Back to Subjects',
                    'variant' => 'secondary',
                    'href' => '/student/subjects',
                    'icon' => '<svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>'
                ]); ?>
            </div>
        </div>
    </div>

    <!-- 4-Card KPI Summary Metrics Strip -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Lessons / Notes -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs">
            <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Course Materials</p>
            <div class="flex items-baseline gap-2 mt-1">
                <h3 class="text-2xl font-extrabold text-slate-900"><?= number_format(count($items)) ?></h3>
                <span class="text-xs font-semibold text-slate-500">lessons</span>
            </div>
            <span class="text-[11px] font-medium text-sky-600 mt-1 block">
                Published study topics
            </span>
        </div>

        <!-- Assignments -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs">
            <p class="text-xs font-bold uppercase tracking-wider text-amber-500">Assignments</p>
            <div class="flex items-baseline gap-2 mt-1">
                <h3 class="text-2xl font-extrabold text-slate-900"><?= number_format(count($assignments)) ?></h3>
                <span class="text-xs font-semibold text-slate-500">tasks</span>
            </div>
            <span class="text-[11px] font-medium text-amber-600/90 mt-1 block">
                Coursework items
            </span>
        </div>

        <!-- CBT Quizzes -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs">
            <p class="text-xs font-bold uppercase tracking-wider text-emerald-600">Online CBTs</p>
            <div class="flex items-baseline gap-2 mt-1">
                <h3 class="text-2xl font-extrabold text-emerald-600"><?= number_format(count($quizzes)) ?></h3>
                <span class="text-xs font-semibold text-slate-500">exams</span>
            </div>
            <span class="text-[11px] font-medium text-emerald-600 mt-1 block">
                Scheduled assessments
            </span>
        </div>

        <!-- Continuous Assessment Total -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs">
            <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Term Result</p>
            <div class="flex items-baseline gap-2 mt-1">
                <?php if ($termResult): ?>
                    <h3 class="text-2xl font-extrabold text-slate-900"><?= number_format((float)$termResult->computedScore, 1) ?>%</h3>
                    <span class="px-2 py-0.5 rounded text-xs font-bold bg-emerald-100 text-emerald-800 ml-1">
                        <?= htmlspecialchars($termResult->gradeLetter) ?>
                    </span>
                <?php else: ?>
                    <h3 class="text-lg font-extrabold text-slate-900">In Progress</h3>
                <?php endif; ?>
            </div>
            <span class="text-[11px] font-medium text-slate-500 mt-1 block">
                Official CA & Exam score
            </span>
        </div>
    </div>

    <!-- Workspace Tabs Container -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
        <!-- Tab Navigation Bar -->
        <div class="flex items-center gap-2 p-2 bg-slate-50 border-b border-slate-200 overflow-x-auto">
            <button type="button" onclick="switchTab('materials')" id="tab-btn-materials"
                    class="tab-btn px-4 py-2 rounded-xl text-xs font-bold transition bg-white text-slate-900 shadow-xs border border-slate-200">
                1. Study Materials (<?= count($items) ?>)
            </button>
            <button type="button" onclick="switchTab('assignments')" id="tab-btn-assignments"
                    class="tab-btn px-4 py-2 rounded-xl text-xs font-bold text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition">
                2. Assignments & Tasks (<?= count($assignments) ?>)
            </button>
            <button type="button" onclick="switchTab('quizzes')" id="tab-btn-quizzes"
                    class="tab-btn px-4 py-2 rounded-xl text-xs font-bold text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition">
                3. CBT Exams & Quizzes (<?= count($quizzes) ?>)
            </button>
        </div>

        <!-- Tab Content Panes -->
        <div class="p-6">
            <!-- Pane 1: Study Materials -->
            <div id="tab-pane-materials" class="tab-pane space-y-4">
                <div class="flex items-center justify-between">
                    <h2 class="text-base font-bold text-slate-900">Course Notes & Learning Resources</h2>
                    <span class="text-xs font-semibold text-slate-400"><?= count($items) ?> items available</span>
                </div>

                <?php if (empty($items)): ?>
                    <div class="p-12 text-center text-slate-400 text-xs">
                        No study materials have been published by the instructor yet.
                    </div>
                <?php else: ?>
                    <div class="divide-y divide-slate-100">
                        <?php foreach ($items as $item): ?>
                            <div class="py-4 first:pt-0 last:pb-0 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                <div class="space-y-1">
                                    <?php if (!empty($item->topic)): ?>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-sky-50 text-sky-800 border border-sky-200">
                                            <?= htmlspecialchars($item->topic) ?>
                                        </span>
                                    <?php endif; ?>
                                    <h3 class="text-sm font-bold text-slate-900">
                                        <a href="/student/content/<?= (int)$item->id ?>" class="hover:text-sky-600 transition">
                                            <?= htmlspecialchars($item->title) ?>
                                        </a>
                                    </h3>
                                    <p class="text-[11px] text-slate-500">
                                        Published <?= date('M d, Y', strtotime($item->publishedAt ?? $item->createdAt)) ?>
                                    </p>
                                </div>

                                <div class="flex items-center gap-2 flex-shrink-0">
                                    <a href="/student/content/<?= (int)$item->id ?>" 
                                       class="px-3.5 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl transition">
                                        Read Note
                                    </a>
                                    <?php if ($item->file): ?>
                                        <a href="/files/<?= (int)$item->file->id ?>/download" 
                                           class="px-3.5 py-1.5 bg-sky-600 hover:bg-sky-700 text-white text-xs font-bold rounded-xl shadow-xs transition flex items-center gap-1.5">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                            </svg>
                                            <span>Download Attachment</span>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Pane 2: Coursework Tasks -->
            <div id="tab-pane-assignments" class="tab-pane hidden space-y-4">
                <div class="flex items-center justify-between">
                    <h2 class="text-base font-bold text-slate-900">Subject Coursework & Assignments</h2>
                    <span class="text-xs font-semibold text-slate-400"><?= count($assignments) ?> total assignments</span>
                </div>

                <?php if (empty($assignments)): ?>
                    <div class="p-12 text-center text-slate-400 text-xs">
                        No coursework assignments posted for this term.
                    </div>
                <?php else: ?>
                    <div class="divide-y divide-slate-100">
                        <?php foreach ($assignments as $assignment): ?>
                            <div class="py-4 first:pt-0 last:pb-0 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                <div class="space-y-1">
                                    <div class="flex items-center gap-2">
                                        <?php if (!empty($assignment->topic)): ?>
                                            <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">
                                                <?= htmlspecialchars($assignment->topic) ?>
                                            </span>
                                        <?php endif; ?>
                                        <span class="text-[11px] font-bold text-amber-600">
                                            Due: <?= date('M d, Y · g:i A', strtotime($assignment->dueAt)) ?>
                                        </span>
                                    </div>
                                    <h3 class="text-sm font-bold text-slate-900">
                                        <?= htmlspecialchars($assignment->title) ?>
                                    </h3>
                                    <p class="text-[11px] text-slate-500">
                                        Weight: <strong><?= number_format((float)$assignment->maxScore, 1) ?> PTS</strong>
                                    </p>
                                </div>

                                <a href="/student/assignments/<?= (int)$assignment->id ?>" 
                                   class="px-4 py-2 bg-sky-600 hover:bg-sky-700 text-white text-xs font-bold rounded-xl shadow-xs transition inline-flex items-center gap-1.5 flex-shrink-0">
                                    <span>Submit Homework</span>
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                                    </svg>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Pane 3: CBT Quizzes -->
            <div id="tab-pane-quizzes" class="tab-pane hidden space-y-4">
                <div class="flex items-center justify-between">
                    <h2 class="text-base font-bold text-slate-900">Online Computer-Based Tests</h2>
                    <span class="text-xs font-semibold text-slate-400"><?= count($quizzes) ?> active</span>
                </div>

                <?php if (empty($quizzes)): ?>
                    <div class="p-12 text-center text-slate-400 text-xs">
                        No online CBT exams currently scheduled for this subject.
                    </div>
                <?php else: ?>
                    <div class="divide-y divide-slate-100">
                        <?php foreach ($quizzes as $quiz): ?>
                            <?php
                                $attempts = $studentAttempts[$quiz->id] ?? [];
                                $attemptsCount = count($attempts);
                                $maxAttempts = (int)$quiz->maxAttempts;
                                $remainingAttempts = max(0, $maxAttempts - $attemptsCount);
                                $latestAttempt = !empty($attempts) ? $attempts[0] : null;
                                $inProgressAttempt = null;
                                foreach ($attempts as $att) {
                                    if ($att->isInProgress()) {
                                        $inProgressAttempt = $att;
                                        break;
                                    }
                                }
                            ?>
                            <div class="p-5 bg-white rounded-xl border border-slate-200 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-4 hover:border-slate-300 transition">
                                <div class="space-y-1.5">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <?php if ($inProgressAttempt): ?>
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-800 border border-amber-200 animate-pulse">
                                                In Progress
                                            </span>
                                        <?php elseif ($remainingAttempts <= 0 && $latestAttempt): ?>
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-800 border border-emerald-200">
                                                Completed &bull; Best Score: <?= $latestAttempt->score !== null ? number_format((float)$latestAttempt->score, 1) : '0.0' ?> PTS
                                            </span>
                                        <?php elseif ($attemptsCount > 0): ?>
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-sky-50 text-sky-800 border border-sky-200">
                                                Attempted (<?= $attemptsCount ?>/<?= $maxAttempts ?>) &bull; <?= $remainingAttempts ?> Left
                                            </span>
                                        <?php else: ?>
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-700 border border-slate-200">
                                                <?= $maxAttempts ?> <?= $maxAttempts === 1 ? 'Attempt Allowed' : 'Attempts Allowed' ?>
                                            </span>
                                        <?php endif; ?>

                                        <span class="text-[11px] font-semibold text-slate-500">
                                            Duration: <?= $quiz->hasTimeLimit() ? "{$quiz->timeLimitMinutes} Mins" : 'Untimed' ?>
                                        </span>
                                    </div>

                                    <h3 class="text-sm font-extrabold text-slate-900 leading-snug">
                                        <?= htmlspecialchars($quiz->title) ?>
                                    </h3>

                                    <p class="text-[11px] text-slate-500 flex items-center gap-2">
                                        <span>Teacher: <strong class="text-slate-700"><?= htmlspecialchars($quiz->teacherName) ?></strong></span>
                                        <span>&bull;</span>
                                        <span>Total Points: <strong><?= number_format($quiz->getTotalMaxScore(), 0) ?> PTS</strong></span>
                                    </p>
                                </div>

                                <div class="flex items-center gap-2 flex-shrink-0">
                                    <?php if ($inProgressAttempt): ?>
                                        <a href="/student/quiz-attempts/<?= (int)$inProgressAttempt->id ?>/take" 
                                           class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white text-xs font-bold rounded-xl shadow-xs transition inline-flex items-center gap-1.5">
                                            <span>Resume CBT</span>
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                                        </a>
                                    <?php elseif ($remainingAttempts <= 0 && $latestAttempt): ?>
                                        <a href="/student/quiz-attempts/<?= (int)$latestAttempt->id ?>/result" 
                                           class="px-4 py-2 bg-sky-600 hover:bg-sky-700 text-white text-xs font-bold rounded-xl shadow-xs transition inline-flex items-center gap-1.5">
                                            <span>View CBT Result</span>
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                                        </a>
                                    <?php elseif ($attemptsCount > 0): ?>
                                        <a href="/student/quizzes/<?= (int)$quiz->id ?>" 
                                           class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl shadow-xs transition inline-flex items-center gap-1.5">
                                            <span>Retake CBT (<?= $remainingAttempts ?> left)</span>
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                                        </a>
                                    <?php else: ?>
                                        <a href="/student/quizzes/<?= (int)$quiz->id ?>" 
                                           class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl shadow-xs transition inline-flex items-center gap-1.5">
                                            <span>Take CBT Quiz</span>
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
function switchTab(tabId) {
    // Hide all panes
    document.querySelectorAll('.tab-pane').forEach(p => p.classList.add('hidden'));
    // Remove active styles from buttons
    document.querySelectorAll('.tab-btn').forEach(b => {
        b.classList.remove('bg-white', 'text-slate-900', 'shadow-xs', 'border', 'border-slate-200');
        b.classList.add('text-slate-600', 'hover:text-slate-900', 'hover:bg-slate-100');
    });

    // Show active pane
    const targetPane = document.getElementById('tab-pane-' + tabId);
    if (targetPane) targetPane.classList.remove('hidden');

    // Highlight active button
    const targetBtn = document.getElementById('tab-btn-' + tabId);
    if (targetBtn) {
        targetBtn.classList.remove('text-slate-600', 'hover:text-slate-900', 'hover:bg-slate-100');
        targetBtn.classList.add('bg-white', 'text-slate-900', 'shadow-xs', 'border', 'border-slate-200');
    }
}
</script>
