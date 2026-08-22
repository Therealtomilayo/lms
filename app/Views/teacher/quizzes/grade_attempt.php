<div class="max-w-4xl mx-auto space-y-6">
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
                    <a href="/teacher/quizzes/<?= (int)$quiz->id ?>/attempts" class="text-slate-400 hover:text-emerald-600 transition">Attempts</a>
                    <span class="text-slate-300">/</span>
                    <span class="text-slate-700">Grade Sitting</span>
                </nav>
                <div class="flex flex-wrap items-center gap-3">
                    <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight leading-tight">
                        Evaluate Student Attempt
                    </h1>
                    <?php 
                        $candName = $attempt->student?->user?->name ?? ($attempt->student?->name ?? 'Student Candidate');
                        $admNo = $attempt->student?->admissionNumber ?? '';
                    ?>
                    <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold bg-slate-100 text-slate-700 border border-slate-200">
                        <?= htmlspecialchars($candName) ?><?= !empty($admNo) ? ' (' . htmlspecialchars($admNo) . ')' : '' ?>
                    </span>
                    <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold bg-emerald-50 text-emerald-800 border border-emerald-200">
                        Attempt #<?= (int)$attempt->attemptNumber ?>
                    </span>
                </div>
                <p class="text-xs text-slate-500 mt-1">
                    Review candidate answers, manually award points and feedback for open-ended prompts, and finalize evaluation scores.
                </p>
            </div>

            <div class="flex items-center gap-3">
                <div class="text-right p-3 bg-slate-50 rounded-xl border border-slate-200">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Current Tally</span>
                    <span class="text-lg font-extrabold text-slate-900">
                        <?= $attempt->score !== null ? number_format((float)$attempt->score, 2) : '0.00' ?> 
                        <span class="text-xs font-semibold text-slate-400">/ <?= number_format((float)$attempt->maxScore, 2) ?> PTS</span>
                    </span>
                </div>

                <?php $this->include('components/button', [
                    'label' => 'Back to Attempts',
                    'variant' => 'secondary',
                    'href' => "/teacher/quizzes/{$quiz->id}/attempts",
                    'icon' => '<svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>'
                ]); ?>
            </div>
        </div>
    </div>

    <!-- Evaluation Form -->
    <form method="POST" action="/teacher/quizzes/<?= (int)$quiz->id ?>/attempts/<?= (int)$attempt->id ?>/grade" class="space-y-6">
        <?= csrf_field() ?>

        <div class="space-y-4">
            <?php foreach ($answers as $idx => $ans): 
                $q = $ans->question;
            ?>
                <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6 space-y-4 hover:border-slate-300 transition">
                    <!-- Question Header -->
                    <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-3 pb-3 border-b border-slate-100">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="w-6 h-6 rounded-md bg-slate-100 text-slate-700 font-mono font-bold text-xs flex items-center justify-center flex-shrink-0">
                                #<?= $idx + 1 ?>
                            </span>

                            <?php if ($q?->isMcq()): ?>
                                <?php $this->include('components/badge', [
                                    'label' => 'MCQ — Auto Graded',
                                    'variant' => 'info',
                                    'size' => 'sm'
                                ]); ?>
                            <?php else: ?>
                                <?php $this->include('components/badge', [
                                    'label' => 'Short Answer — Manual Evaluation',
                                    'variant' => 'warning',
                                    'size' => 'sm'
                                ]); ?>
                            <?php endif; ?>

                            <?php if ($q?->topic): ?>
                                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-semibold bg-slate-100 text-slate-600">
                                    <?= htmlspecialchars($q->topic) ?>
                                </span>
                            <?php endif; ?>
                        </div>

                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-bold bg-amber-50 text-amber-800 border border-amber-200">
                            Max: <?= number_format((float)($q?->defaultPoints ?? 1.00), 2) ?> PTS
                        </span>
                    </div>

                    <!-- Question Statement -->
                    <div>
                        <p class="text-sm font-semibold text-slate-900 leading-relaxed">
                            <?= nl2br(htmlspecialchars($q?->questionText ?? 'Question Statement')) ?>
                        </p>
                    </div>

                    <!-- Student Response Box -->
                    <div class="p-4 rounded-xl border <?= $q?->isMcq() ? ($ans->selectedOption?->isCorrect ? 'bg-emerald-50/70 border-emerald-200' : 'bg-rose-50/70 border-rose-200') : 'bg-slate-50/80 border-slate-200' ?>">
                        <div class="flex items-center justify-between mb-1.5">
                            <span class="text-[11px] font-bold uppercase tracking-wider <?= $q?->isMcq() ? ($ans->selectedOption?->isCorrect ? 'text-emerald-700' : 'text-rose-700') : 'text-slate-500' ?>">
                                Student Candidate Response:
                            </span>
                            <?php if ($q?->isMcq()): ?>
                                <span class="text-[11px] font-bold px-2 py-0.5 rounded-full <?= $ans->selectedOption?->isCorrect ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800' ?>">
                                    <?= $ans->selectedOption?->isCorrect ? '✓ Correct Option' : '✗ Incorrect' ?>
                                </span>
                            <?php endif; ?>
                        </div>

                        <?php if ($q?->isMcq()): ?>
                            <p class="text-xs font-bold text-slate-900">
                                <?= $ans->selectedOption ? htmlspecialchars($ans->selectedOption->optionText) : '<span class="text-slate-400 italic">No option selected</span>' ?>
                            </p>
                        <?php else: ?>
                            <p class="text-xs font-medium text-slate-900 whitespace-pre-wrap leading-relaxed">
                                <?= !empty($ans->textAnswer) ? htmlspecialchars($ans->textAnswer) : '<span class="text-slate-400 italic font-normal">No written answer provided</span>' ?>
                            </p>
                        <?php endif; ?>
                    </div>

                    <!-- Grading & Feedback Controls -->
                    <?php if ($q?->isShortAnswer()): ?>
                        <div class="pt-3 border-t border-slate-100 grid grid-cols-1 sm:grid-cols-12 gap-4 items-end">
                            <div class="sm:col-span-4">
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                                    Points Awarded (Max: <?= number_format((float)$q->defaultPoints, 2) ?> PTS) <span class="text-rose-500">*</span>
                                </label>
                                <div class="relative">
                                    <input type="number" step="0.25" min="0" max="<?= (float)$q->defaultPoints ?>" 
                                           name="grades[<?= (int)$ans->id ?>][points_awarded]" 
                                           value="<?= htmlspecialchars((string)($ans->pointsAwarded ?? '0.00')) ?>" 
                                           class="w-full rounded-xl border border-slate-300 text-xs font-bold text-slate-900 py-2.5 pl-3 pr-12 bg-slate-50 focus:bg-white focus:border-emerald-500 focus:ring-emerald-500 transition" required>
                                    <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs font-bold text-slate-400 pointer-events-none">PTS</span>
                                </div>
                                <input type="hidden" name="grades[<?= (int)$ans->id ?>][answer_id]" value="<?= (int)$ans->id ?>">
                            </div>

                            <div class="sm:col-span-8">
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                                    Teacher Remarks / Feedback
                                </label>
                                <input type="text" name="grades[<?= (int)$ans->id ?>][teacher_comment]" 
                                       value="<?= htmlspecialchars((string)($ans->teacherComment ?? '')) ?>" 
                                       placeholder="Constructive feedback, notes on student answers..." 
                                       class="w-full rounded-xl border border-slate-300 text-xs font-medium text-slate-900 py-2.5 px-3 bg-slate-50 focus:bg-white focus:border-emerald-500 focus:ring-emerald-500 transition">
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="flex items-center justify-between text-xs text-slate-500 pt-1">
                            <span class="font-medium">Auto-scored: <strong class="text-slate-900 font-bold"><?= number_format((float)($ans->pointsAwarded ?? 0), 2) ?> PTS</strong></span>
                            <span>Outcome: <strong class="text-slate-700"><?= htmlspecialchars((string)($ans->teacherComment ?? 'System evaluated')) ?></strong></span>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Sticky Footer -->
        <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
            <?php $this->include('components/button', [
                'label' => 'Cancel',
                'variant' => 'secondary',
                'href' => "/teacher/quizzes/{$quiz->id}/attempts"
            ]); ?>

            <?php $this->include('components/button', [
                'label' => 'Finalize & Save Grades',
                'variant' => 'primary',
                'type' => 'submit',
                'icon' => '<svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>'
            ]); ?>
        </div>
    </form>
</div>
