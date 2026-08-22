<div class="space-y-6">
    <!-- Header Card -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <!-- Breadcrumbs -->
                <nav class="flex items-center gap-2 text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">
                    <a href="/teacher/dashboard" class="text-slate-400 hover:text-emerald-600 transition">Faculty Portal</a>
                    <span class="text-slate-300">/</span>
                    <a href="/teacher/quizzes" class="text-slate-400 hover:text-emerald-600 transition">Quizzes & CBT Exams</a>
                    <span class="text-slate-300">/</span>
                    <span class="text-slate-700">Student Attempts</span>
                </nav>
                <div class="flex flex-wrap items-center gap-3">
                    <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight leading-tight">
                        <?= htmlspecialchars($quiz->title) ?> — Results
                    </h1>
                </div>
                <p class="text-xs text-slate-500 mt-1">
                    Review student score breakdowns, perform manual grading on short-answer questions, and manage attempt resets.
                </p>
            </div>

            <div class="flex items-center gap-2.5">
                <?php $this->include('components/button', [
                    'label' => 'Back to Quizzes',
                    'variant' => 'secondary',
                    'href' => '/teacher/quizzes',
                    'icon' => '<svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>'
                ]); ?>
            </div>
        </div>
    </div>

    <!-- 4-Card KPI Summary Metrics Strip -->
    <?php
        $totalAttempts = count($attempts);
        $gradedCount = 0;
        $pendingCount = 0;
        $inProgressCount = 0;
        foreach ($attempts as $att) {
            if ($att->status === 'graded') {
                $gradedCount++;
            } elseif ($att->status === 'submitted') {
                $pendingCount++;
            } else {
                $inProgressCount++;
            }
        }
    ?>
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Total Attempts -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs">
            <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Total Sittings</p>
            <div class="flex items-baseline gap-2 mt-1">
                <h3 class="text-2xl font-extrabold text-slate-900"><?= number_format($totalAttempts) ?></h3>
                <span class="text-xs font-semibold text-slate-500">attempts</span>
            </div>
            <span class="text-[11px] font-medium text-slate-500 mt-1 block">
                Exam sittings logged
            </span>
        </div>

        <!-- Graded Results -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs">
            <p class="text-xs font-bold uppercase tracking-wider text-emerald-600">Evaluated</p>
            <div class="flex items-baseline gap-2 mt-1">
                <h3 class="text-2xl font-extrabold text-emerald-600"><?= number_format($gradedCount) ?></h3>
                <span class="text-xs font-semibold text-slate-500">completed</span>
            </div>
            <span class="text-[11px] font-medium text-emerald-600 mt-1 block">
                Final marks finalized
            </span>
        </div>

        <!-- Pending Evaluation -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs">
            <p class="text-xs font-bold uppercase tracking-wider text-amber-500">Needs Review</p>
            <div class="flex items-baseline gap-2 mt-1">
                <h3 class="text-2xl font-extrabold text-amber-600"><?= number_format($pendingCount) ?></h3>
                <span class="text-xs font-semibold text-slate-500">pending</span>
            </div>
            <span class="text-[11px] font-medium text-amber-600/90 mt-1 block">
                Short answers to evaluate
            </span>
        </div>

        <!-- In Progress -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs">
            <p class="text-xs font-bold uppercase tracking-wider text-slate-400">In Progress</p>
            <div class="flex items-baseline gap-2 mt-1">
                <h3 class="text-2xl font-extrabold text-slate-900"><?= number_format($inProgressCount) ?></h3>
                <span class="text-xs font-semibold text-slate-500">active</span>
            </div>
            <span class="text-[11px] font-medium text-slate-500 mt-1 block">
                Live test sessions
            </span>
        </div>
    </div>

    <!-- Attempts Table Container -->
    <?php if (empty($attempts)): ?>
        <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-12 text-center">
            <div class="w-16 h-16 rounded-2xl bg-emerald-50 text-emerald-600 mx-auto flex items-center justify-center mb-4">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <h3 class="text-base font-bold text-slate-900">No Student Sittings Recorded Yet</h3>
            <p class="text-xs text-slate-500 mt-1.5 max-w-sm mx-auto">
                When enrolled students take this CBT exam, their score breakdowns, timestamps, and manual grading links will be shown here.
            </p>
            <div class="mt-6">
                <?php $this->include('components/button', [
                    'label' => 'Back to Quizzes',
                    'variant' => 'secondary',
                    'href' => '/teacher/quizzes'
                ]); ?>
            </div>
        </div>
    <?php else: ?>
        <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200 text-[11px] font-bold text-slate-600 uppercase tracking-wider">
                            <th class="py-3.5 px-5">Student Candidate</th>
                            <th class="py-3.5 px-4">Attempt</th>
                            <th class="py-3.5 px-4">Timestamp</th>
                            <th class="py-3.5 px-4">Status</th>
                            <th class="py-3.5 px-4">CBT Score</th>
                            <th class="py-3.5 px-5 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php foreach ($attempts as $att): ?>
                            <?php 
                                $studentName = $att->student?->user?->name ?? ($att->student?->name ?? 'Student Candidate');
                                $admNo = $att->student?->admissionNumber ?? '';
                            ?>
                            <tr class="hover:bg-slate-50/60 transition">
                                <td class="py-4 px-5">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-xl bg-emerald-100 text-emerald-800 font-bold text-xs flex items-center justify-center flex-shrink-0">
                                            <?= strtoupper(substr($studentName, 0, 1)) ?>
                                        </div>
                                        <div>
                                            <span class="block font-bold text-slate-900"><?= htmlspecialchars($studentName) ?></span>
                                            <?php if (!empty($admNo)): ?>
                                                <span class="block font-mono text-[10px] text-slate-400 font-semibold">
                                                    <?= htmlspecialchars($admNo) ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>

                                <td class="py-4 px-4 font-semibold text-slate-700">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md bg-slate-100 text-slate-700 font-bold text-[11px]">
                                        Attempt #<?= (int)$att->attemptNumber ?>
                                    </span>
                                </td>

                                <td class="py-4 px-4 text-slate-500 font-medium">
                                    <?= date('M d, Y · g:i A', strtotime($att->startedAt)) ?>
                                </td>

                                <td class="py-4 px-4">
                                    <?php if ($att->status === 'graded'): ?>
                                        <?php $this->include('components/badge', [
                                            'label' => 'Graded',
                                            'variant' => 'success',
                                            'size' => 'sm'
                                        ]); ?>
                                    <?php elseif ($att->status === 'submitted'): ?>
                                        <?php $this->include('components/badge', [
                                            'label' => 'Pending Review',
                                            'variant' => 'info',
                                            'size' => 'sm'
                                        ]); ?>
                                    <?php else: ?>
                                        <?php $this->include('components/badge', [
                                            'label' => 'In Progress',
                                            'variant' => 'warning',
                                            'size' => 'sm'
                                        ]); ?>
                                    <?php endif; ?>
                                </td>

                                <td class="py-4 px-4">
                                    <?php if ($att->score !== null): ?>
                                        <span class="font-extrabold text-slate-900 text-sm">
                                            <?= number_format((float)$att->score, 1) ?>
                                        </span>
                                        <span class="text-slate-400 font-semibold text-xs">
                                            / <?= number_format((float)$att->maxScore, 0) ?> PTS
                                        </span>
                                    <?php else: ?>
                                        <span class="text-slate-400 font-medium">—</span>
                                    <?php endif; ?>
                                </td>

                                <td class="py-4 px-5 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <?php if ($att->isSubmitted()): ?>
                                            <a href="/teacher/quizzes/<?= (int)$quiz->id ?>/attempts/<?= (int)$att->id ?>/grade" 
                                               class="inline-flex items-center gap-1 px-3 py-1.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200 text-xs font-bold rounded-lg transition">
                                                <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                                                </svg>
                                                <span>Review & Grade</span>
                                            </a>
                                        <?php endif; ?>

                                        <form method="POST" action="/teacher/quizzes/<?= (int)$quiz->id ?>/attempts/<?= (int)$att->id ?>/reset" 
                                              onsubmit="return confirm('Resetting this student attempt will clear recorded answers and permit a re-sit. Proceed?');">
                                            <?= csrf_field() ?>
                                            <button type="submit" 
                                                    class="inline-flex items-center gap-1 px-2.5 py-1.5 bg-slate-100 hover:bg-rose-50 text-slate-600 hover:text-rose-700 text-xs font-bold rounded-lg border border-transparent hover:border-rose-200 transition" 
                                                    title="Allow student to retake">
                                                <span>Reset</span>
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
