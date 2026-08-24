<div class="max-w-4xl mx-auto space-y-6">
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
                    <span class="text-slate-700">Assessment Results</span>
                </nav>
                <div class="flex flex-wrap items-center gap-3">
                    <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight leading-tight">
                        <?= htmlspecialchars($quiz->title) ?>
                    </h1>
                    <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold bg-sky-50 text-sky-800 border border-sky-200">
                        <?= htmlspecialchars($quiz->classSubject?->subjectName ?? 'Assessment') ?>
                    </span>
                </div>
                <p class="text-xs text-slate-500 mt-1 flex items-center gap-2 flex-wrap">
                    <span>Attempt: <strong class="text-slate-800 font-semibold">#<?= (int)$attempt->attemptNumber ?></strong></span>
                    <span>&bull;</span>
                    <span>Submitted: <strong><?= date('F d, Y · g:i A', strtotime($attempt->submittedAt ?? 'now')) ?></strong></span>
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
    <?php
        $maxScore = (float)$attempt->maxScore;
        $score = $attempt->score !== null ? (float)$attempt->score : null;
        $percentage = ($score !== null && $maxScore > 0) ? ($score / $maxScore) * 100 : null;
    ?>
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <!-- Final Score -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs">
            <p class="text-xs font-bold uppercase tracking-wider text-emerald-600">Final Score</p>
            <div class="flex items-baseline gap-1 mt-1">
                <h3 class="text-2xl font-extrabold text-emerald-600">
                    <?= $score !== null ? number_format($score, 2) : '—' ?>
                </h3>
                <span class="text-xs font-semibold text-slate-500">/ <?= number_format($maxScore, 2) ?></span>
            </div>
            <span class="text-[11px] font-medium text-emerald-600 mt-1 block">
                Total Marks Earned
            </span>
        </div>

        <!-- Accuracy -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs">
            <p class="text-xs font-bold uppercase tracking-wider text-sky-600">Accuracy</p>
            <div class="flex items-baseline gap-1 mt-1">
                <h3 class="text-2xl font-extrabold text-slate-900">
                    <?= $percentage !== null ? number_format($percentage, 1) . '%' : 'Under Review' ?>
                </h3>
            </div>
            <span class="text-[11px] font-medium text-sky-600 mt-1 block">
                Performance Percentage
            </span>
        </div>

        <!-- Grading Status -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs">
            <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Evaluation</p>
            <div class="flex items-baseline gap-1 mt-1">
                <?php if ($attempt->status === 'graded'): ?>
                    <h3 class="text-base font-extrabold text-emerald-600">Graded</h3>
                <?php else: ?>
                    <h3 class="text-base font-extrabold text-amber-600">Pending Review</h3>
                <?php endif; ?>
            </div>
            <span class="text-[11px] font-medium text-slate-500 mt-1 block">
                <?= $attempt->status === 'graded' ? 'Scoring Finalized' : 'Short Answer Evaluation' ?>
            </span>
        </div>

        <!-- Questions Breakdown -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs">
            <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Total Items</p>
            <div class="flex items-baseline gap-1 mt-1">
                <h3 class="text-2xl font-extrabold text-slate-900"><?= count($answers) ?></h3>
                <span class="text-xs font-semibold text-slate-500">answers</span>
            </div>
            <span class="text-[11px] font-medium text-slate-500 mt-1 block">
                Evaluated Questions
            </span>
        </div>
    </div>

    <!-- Question-by-Question Breakdown -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6 sm:p-8 space-y-6">
        <h2 class="text-base font-bold text-slate-900 border-b border-slate-100 pb-3">
            Question Performance & Performance Breakdown
        </h2>

        <div class="space-y-4">
            <?php foreach ($answers as $idx => $ans): 
                $q = $ans->question;
            ?>
                <div class="p-5 bg-slate-50/70 rounded-2xl border border-slate-200 space-y-3">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex-1">
                            <span class="text-[11px] font-bold text-sky-700 uppercase tracking-wider block mb-1">
                                Question <?= $idx + 1 ?>
                            </span>
                            <p class="text-sm font-semibold text-slate-900">
                                <?= nl2br(htmlspecialchars($q?->questionText ?? 'Question')) ?>
                            </p>
                        </div>
                        <div class="text-right flex-shrink-0">
                            <span class="font-mono text-xs font-bold px-2 py-1 bg-white rounded-lg border border-slate-200 text-slate-800">
                                <?= $ans->pointsAwarded !== null ? number_format((float)$ans->pointsAwarded, 2) : '—' ?> pt(s)
                            </span>
                        </div>
                    </div>

                    <div class="p-3.5 rounded-xl border <?= $q?->isMcq() ? ($ans->selectedOption?->isCorrect ? 'bg-emerald-50/70 border-emerald-200' : 'bg-rose-50/70 border-rose-200') : 'bg-white border-slate-200' ?>">
                        <span class="text-[10px] font-bold uppercase tracking-wider block mb-1 <?= $q?->isMcq() ? ($ans->selectedOption?->isCorrect ? 'text-emerald-800' : 'text-rose-800') : 'text-slate-500' ?>">
                            Your Submitted Answer:
                        </span>
                        <?php if ($q?->isMcq()): ?>
                            <p class="text-xs font-bold text-slate-900">
                                <?= $ans->selectedOption ? htmlspecialchars($ans->selectedOption->optionText) : '<span class="text-slate-400 italic">No option selected</span>' ?>
                            </p>
                        <?php else: ?>
                            <p class="text-xs text-slate-900 whitespace-pre-wrap font-medium">
                                <?= !empty($ans->textAnswer) ? htmlspecialchars($ans->textAnswer) : '<span class="text-slate-400 italic">No written answer provided</span>' ?>
                            </p>
                        <?php endif; ?>
                    </div>

                    <?php if (!empty($ans->teacherComment)): ?>
                        <div class="p-3 rounded-xl bg-sky-50 border border-sky-200 text-xs text-sky-950">
                            <strong>Instructor Feedback:</strong> <?= htmlspecialchars($ans->teacherComment) ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
