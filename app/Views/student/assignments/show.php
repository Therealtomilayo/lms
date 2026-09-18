<div class="max-w-4xl mx-auto space-y-6">
    <!-- Header Card -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <!-- Breadcrumbs -->
                <nav class="flex items-center gap-2 text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">
                    <a href="/student/dashboard" class="hover:text-sky-600 transition">Student Portal</a>
                    <span class="text-slate-300">/</span>
                    <a href="/student/assignments" class="hover:text-sky-600 transition">Coursework</a>
                    <span class="text-slate-300">/</span>
                    <span class="text-slate-700">Assignment Task</span>
                </nav>
                <div class="flex flex-wrap items-center gap-3">
                    <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight leading-tight">
                        <?= htmlspecialchars($assignment->title) ?>
                    </h1>
                    <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold bg-sky-50 text-sky-800 border border-sky-200">
                        <?= htmlspecialchars($assignment->classSubject?->subjectName ?? 'Subject') ?>
                    </span>
                    <?php if (!empty($assignment->topic)): ?>
                        <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold bg-slate-100 text-slate-700 border border-slate-200">
                            Topic: <?= htmlspecialchars($assignment->topic) ?>
                        </span>
                    <?php endif; ?>
                </div>
                <p class="text-xs text-slate-500 mt-1.5 flex items-center gap-2 flex-wrap">
                    <span>Teacher: <strong class="text-slate-800 font-semibold"><?= htmlspecialchars($assignment->teacher?->userName ?? 'Instructor') ?></strong></span>
                    <span>&bull;</span>
                    <span>Due: <strong class="<?= $assignment->isPastDue() ? 'text-rose-600' : 'text-slate-800' ?>"><?= date('F d, Y · g:i A', strtotime($assignment->dueAt)) ?></strong></span>
                </p>
            </div>

            <div class="flex items-center gap-2.5 flex-wrap">
                <?php $this->include('components/button', [
                    'label' => 'Back to Tasks',
                    'variant' => 'secondary',
                    'href' => '/student/assignments',
                    'icon' => '<svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>'
                ]); ?>
            </div>
        </div>
    </div>

    <!-- 4-Card KPI Summary Metrics Strip -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Max Score -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs">
            <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Total Points</p>
            <div class="flex items-baseline gap-1 mt-1">
                <h3 class="text-2xl font-extrabold text-slate-900"><?= number_format((float)$assignment->maxScore, 0) ?></h3>
                <span class="text-xs font-semibold text-slate-500">PTS</span>
            </div>
            <span class="text-[11px] font-medium text-sky-600 mt-1 block">
                Assignment Weight
            </span>
        </div>

        <!-- Deadline Status -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs">
            <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Deadline</p>
            <div class="flex items-baseline gap-1 mt-1">
                <h3 class="text-base font-extrabold <?= $assignment->isPastDue() ? 'text-rose-600' : 'text-slate-900' ?>">
                    <?= date('M d, Y', strtotime($assignment->dueAt)) ?>
                </h3>
            </div>
            <span class="text-[11px] font-medium <?= $assignment->isPastDue() ? 'text-rose-600' : 'text-emerald-600' ?> mt-1 block">
                <?= $assignment->isPastDue() ? 'Deadline Passed' : 'Active Submission Window' ?>
            </span>
        </div>

        <!-- Submission Status -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs">
            <p class="text-xs font-bold uppercase tracking-wider text-sky-600">Your Status</p>
            <div class="flex items-baseline gap-1 mt-1">
                <?php if ($submission && $submission->isGraded()): ?>
                    <h3 class="text-lg font-extrabold text-emerald-600">Graded</h3>
                <?php elseif ($submission): ?>
                    <h3 class="text-lg font-extrabold text-sky-600">Submitted</h3>
                <?php else: ?>
                    <h3 class="text-lg font-extrabold text-amber-500">Pending</h3>
                <?php endif; ?>
            </div>
            <span class="text-[11px] font-medium text-slate-500 mt-1 block">
                <?= $submission ? date('M d, g:i A', strtotime($submission->submittedAt)) : 'Not submitted yet' ?>
            </span>
        </div>

        <!-- Your Score -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs">
            <p class="text-xs font-bold uppercase tracking-wider text-emerald-600">Your Score</p>
            <div class="flex items-baseline gap-1 mt-1">
                <?php if ($submission && $submission->isGraded()): ?>
                    <h3 class="text-2xl font-extrabold text-emerald-600"><?= number_format((float)$submission->score, 1) ?></h3>
                    <span class="text-xs font-semibold text-slate-500">/ <?= number_format((float)$assignment->maxScore, 0) ?></span>
                <?php else: ?>
                    <h3 class="text-base font-extrabold text-slate-400">&mdash;</h3>
                <?php endif; ?>
            </div>
            <span class="text-[11px] font-medium text-slate-500 mt-1 block">
                Official Grade
            </span>
        </div>
    </div>

    <!-- Assignment Instructions Card -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6 sm:p-8 space-y-6">
        <div>
            <h2 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Instructions & Guidelines</h2>
            <div class="p-5 bg-slate-50 rounded-2xl border border-slate-200 text-sm text-slate-800 whitespace-pre-wrap leading-relaxed">
                <?= htmlspecialchars($assignment->instructions) ?>
            </div>
        </div>

        <!-- Teacher Reference Attachment -->
        <?php if ($assignment->file): ?>
            <div class="pt-6 border-t border-slate-100">
                <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-3">Resource / Attached Worksheet</h3>
                <div class="p-4 bg-sky-50/50 rounded-2xl border border-sky-200 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="w-10 h-10 rounded-xl bg-sky-100 text-sky-700 flex items-center justify-center flex-shrink-0">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs font-bold text-slate-900 truncate"><?= htmlspecialchars($assignment->file->originalName) ?></p>
                            <p class="text-[11px] text-slate-500 font-semibold"><?= htmlspecialchars($assignment->file->getFormattedSize()) ?></p>
                        </div>
                    </div>
                    <a href="/files/<?= (int)$assignment->file->id ?>/download" 
                       class="inline-flex items-center justify-center gap-1.5 px-4 py-2 bg-sky-600 hover:bg-sky-700 text-white rounded-xl text-xs font-bold shadow-xs transition flex-shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                        <span>Download Worksheet</span>
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Student Submission Details & Teacher Feedback -->
    <?php if ($submission): ?>
        <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6 sm:p-8 space-y-6">
            <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                <div>
                    <h3 class="text-base font-bold text-slate-900">Your Submission Record</h3>
                    <p class="text-xs text-slate-500 mt-0.5">
                        Delivered on <?= date('F d, Y · g:i A', strtotime($submission->submittedAt)) ?>
                        <?php if ($submission->isLate()): ?>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-lg text-[10px] font-bold bg-rose-50 text-rose-800 border border-rose-200 ml-1">
                                Submitted Late
                            </span>
                        <?php endif; ?>
                    </p>
                </div>
                <div>
                    <?php if ($submission->isGraded()): ?>
                        <span class="inline-flex items-center px-3 py-1 rounded-xl text-xs font-bold bg-emerald-50 text-emerald-800 border border-emerald-200">
                            Score: <?= number_format((float)$submission->score, 1) ?> / <?= number_format((float)$assignment->maxScore, 0) ?>
                        </span>
                    <?php else: ?>
                        <span class="inline-flex items-center px-3 py-1 rounded-xl text-xs font-bold bg-sky-50 text-sky-800 border border-sky-200">
                            Awaiting Evaluation
                        </span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Teacher Evaluation & Feedback -->
            <?php if ($submission->isGraded()): ?>
                <div class="p-5 bg-emerald-50/50 rounded-2xl border border-emerald-200 space-y-2">
                    <div class="flex items-center justify-between">
                        <h4 class="text-xs font-bold text-emerald-800 uppercase tracking-wider">Teacher Feedback & Remarks</h4>
                        <span class="text-xs text-emerald-700 font-semibold">Graded on <?= date('M d, Y', strtotime($submission->gradedAt)) ?></span>
                    </div>
                    <?php if (!empty($submission->teacherComment)): ?>
                        <p class="text-xs text-emerald-950 leading-relaxed font-medium">
                            <?= htmlspecialchars($submission->teacherComment) ?>
                        </p>
                    <?php else: ?>
                        <p class="text-xs text-emerald-700 italic">No written comments provided.</p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <!-- Submitted Response Content -->
            <div class="space-y-4">
                <?php if ($submission->hasTextResponse()): ?>
                    <div>
                        <span class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Your Written Response:</span>
                        <div class="p-4 bg-slate-50 rounded-xl border border-slate-200 text-xs text-slate-800 whitespace-pre-wrap leading-relaxed">
                            <?= htmlspecialchars($submission->textResponse) ?>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($submission->file): ?>
                    <div>
                        <span class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Your Submitted Attachment:</span>
                        <div class="p-4 bg-slate-50 rounded-xl border border-slate-200 flex items-center justify-between gap-3">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="w-8 h-8 rounded-lg bg-sky-100 text-sky-700 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
                                    </svg>
                                </div>
                                <span class="text-xs font-bold text-slate-900 truncate"><?= htmlspecialchars($submission->file->originalName) ?></span>
                            </div>
                            <a href="/files/<?= (int)$submission->file->id ?>/download" 
                               class="text-xs font-bold text-sky-600 hover:text-sky-700">
                                Download File
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Submit / Update Submission Form -->
    <?php if (!empty($isUnlocked) === false && !empty($prerequisiteStatus)): ?>
        <div class="pt-2">
            <?php $this->include('components/locked_activity', [
                'title' => $assignment->title,
                'allPrerequisites' => $prerequisiteStatus['prerequisites'] ?? [],
                'unmet' => $prerequisiteStatus['unmet'] ?? [],
                'backUrl' => '/student/assignments',
            ]); ?>
        </div>
    <?php elseif (!$submission || !$submission->isGraded()): ?>
        <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6 sm:p-8 space-y-6">
            <h2 class="text-base font-bold text-slate-900">
                <?= $submission ? 'Update Your Homework Submission' : 'Submit Assignment Response' ?>
            </h2>

            <form method="POST" action="/student/assignments/<?= (int)$assignment->id ?>/submit" enctype="multipart/form-data" class="space-y-4">
                <?= csrf_field() ?>

                <div>
                    <label for="text_response" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                        Written Text Answer (Optional if attaching file)
                    </label>
                    <textarea name="text_response" id="text_response" rows="5" placeholder="Write your homework answers, explanations, or remarks here..."
                              class="w-full rounded-xl border border-slate-300 bg-slate-50 text-xs font-medium text-slate-900 focus:bg-white focus:border-sky-500 focus:ring-sky-500 py-3 px-3.5 transition"><?= htmlspecialchars($submission?->textResponse ?? '') ?></textarea>
                </div>

                <div>
                    <label for="attachment" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                        Upload Document / Solution File (PDF, DOCX, ZIP, PNG, JPG)
                    </label>
                    <input type="file" name="attachment" id="attachment"
                           class="w-full text-xs text-slate-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-sky-50 file:text-sky-700 hover:file:bg-sky-100 cursor-pointer">
                </div>

                <div class="pt-3">
                    <?php $this->include('components/button', [
                        'label' => $submission ? 'Update Submission' : 'Submit Assignment Response',
                        'variant' => 'primary',
                        'type' => 'submit',
                        'icon' => '<svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>'
                    ]); ?>
                </div>
            </form>
        </div>
    <?php endif; ?>
</div>
