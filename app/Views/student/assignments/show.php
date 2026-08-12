<div class="max-w-4xl mx-auto space-y-6">
    <!-- Breadcrumb & Back -->
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-2">
            <a href="/student/assignments" class="text-xs font-bold text-brand-600 hover:underline">← Coursework</a>
            <span class="text-xs text-slate-400">/</span>
            <span class="text-xs font-semibold text-slate-500"><?= e($assignment->classSubject?->subjectName ?? 'Subject') ?></span>
        </div>
        <span class="text-xs text-slate-400 font-mono">Max Score: <?= number_format($assignment->maxScore, 0) ?> pts</span>
    </div>

    <!-- Assignment Card -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 sm:p-8 space-y-6">
        <div class="border-b border-slate-100 pb-5">
            <?php if (!empty($assignment->topic)): ?>
                <span class="inline-block text-xs font-bold text-brand-600 uppercase tracking-wider mb-2">
                    <?= e($assignment->topic) ?>
                </span>
            <?php endif; ?>
            <h2 class="text-2xl font-bold text-slate-900 tracking-tight"><?= e($assignment->title) ?></h2>
            <div class="flex flex-wrap items-center gap-4 mt-2 text-xs text-slate-500">
                <span>Teacher: <strong class="text-slate-700"><?= e($assignment->teacher?->userName ?? 'Instructor') ?></strong></span>
                <span>·</span>
                <span>Due Date: <strong class="<?= $assignment->isPastDue() ? 'text-rose-600' : 'text-slate-700' ?>"><?= date('F d, Y · g:i A', strtotime($assignment->dueAt)) ?></strong></span>
            </div>
        </div>

        <!-- Task Instructions -->
        <div class="space-y-2">
            <h3 class="text-xs font-bold text-slate-700 uppercase tracking-wider">Instructions & Guidelines</h3>
            <div class="p-4 bg-slate-50 rounded-xl border border-slate-200 text-sm text-slate-800 whitespace-pre-wrap leading-relaxed">
                <?= e($assignment->instructions) ?>
            </div>
        </div>

        <!-- Reference Attachment Download -->
        <?php if ($assignment->file): ?>
            <div class="space-y-2">
                <h3 class="text-xs font-bold text-slate-700 uppercase tracking-wider">Resource / Attachment</h3>
                <div class="p-4 bg-brand-50/50 rounded-xl border border-brand-100 flex items-center justify-between gap-3">
                    <div class="flex items-center gap-3 min-w-0">
                        <svg class="w-6 h-6 text-brand-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                        <div class="min-w-0">
                            <p class="text-sm font-bold text-slate-900 truncate"><?= e($assignment->file->originalName) ?></p>
                            <p class="text-xs text-slate-500 font-mono"><?= e($assignment->file->getFormattedSize()) ?></p>
                        </div>
                    </div>
                    <a href="/files/<?= $assignment->file->id ?>/download" 
                       class="px-4 py-2 bg-brand-600 hover:bg-brand-700 text-white text-xs font-semibold rounded-xl shadow-sm transition inline-flex items-center gap-1.5 flex-shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                        Download Reference File
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Student Submission & Feedback Section -->
    <?php if ($submission): ?>
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 sm:p-8 space-y-6">
            <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                <div>
                    <h3 class="text-lg font-bold text-slate-900">Your Submission</h3>
                    <p class="text-xs text-slate-500 mt-0.5">
                        Submitted on <?= date('M d, Y · g:i A', strtotime($submission->submittedAt)) ?>
                        <?php if ($submission->isLate()): ?>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold bg-rose-50 text-rose-700 border border-rose-200 ml-1">
                                Late
                            </span>
                        <?php endif; ?>
                    </p>
                </div>
                <div>
                    <?php if ($submission->isGraded()): ?>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                            Score: <?= number_format($submission->score, 1) ?> / <?= number_format($assignment->maxScore, 0) ?>
                        </span>
                    <?php else: ?>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-blue-50 text-blue-700 border border-blue-200">
                            Awaiting Grade
                        </span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Grade & Teacher Feedback -->
            <?php if ($submission->isGraded()): ?>
                <div class="p-5 bg-emerald-50/50 rounded-xl border border-emerald-200 space-y-2">
                    <div class="flex items-center justify-between">
                        <h4 class="text-xs font-bold text-emerald-800 uppercase tracking-wider">Teacher Feedback & Evaluation</h4>
                        <span class="text-xs text-emerald-600">Graded on <?= date('M d, Y', strtotime($submission->gradedAt)) ?></span>
                    </div>
                    <?php if (!empty($submission->teacherComment)): ?>
                        <p class="text-sm text-emerald-950 leading-relaxed font-medium">
                            <?= e($submission->teacherComment) ?>
                        </p>
                    <?php else: ?>
                        <p class="text-xs text-emerald-700 italic">No textual feedback provided.</p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <!-- Submitted Response Content -->
            <div class="space-y-3">
                <?php if ($submission->hasTextResponse()): ?>
                    <div>
                        <span class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Your Written Answer:</span>
                        <div class="p-4 bg-slate-50 rounded-xl border border-slate-200 text-sm text-slate-800 whitespace-pre-wrap leading-relaxed">
                            <?= e($submission->textResponse) ?>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($submission->file): ?>
                    <div>
                        <span class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Your Submitted Attachment:</span>
                        <div class="p-3 bg-slate-50 rounded-xl border border-slate-200 flex items-center justify-between gap-3">
                            <div class="flex items-center gap-2 min-w-0">
                                <svg class="w-5 h-5 text-brand-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                                <span class="text-xs font-medium text-slate-700 truncate"><?= e($submission->file->originalName) ?></span>
                            </div>
                            <a href="/files/<?= $submission->file->id ?>/download" 
                               class="text-xs font-semibold text-brand-600 hover:text-brand-700">
                                Download File
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Submit / Resubmit Form (allowed if not graded yet) -->
    <?php if (!$submission || !$submission->isGraded()): ?>
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 sm:p-8 space-y-6">
            <h3 class="text-lg font-bold text-slate-900">
                <?= $submission ? 'Update Your Submission' : 'Submit Your Work' ?>
            </h3>

            <form method="POST" action="/student/assignments/<?= $assignment->id ?>/submit" enctype="multipart/form-data" class="space-y-4">
                <?= csrf_field() ?>

                <div>
                    <label for="text_response" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                        Text Response (Optional if uploading file)
                    </label>
                    <textarea name="text_response" id="text_response" rows="5" placeholder="Type your response, essay, or comments here..."
                              class="w-full rounded-xl border-slate-300 text-sm focus:border-brand-500 focus:ring-brand-500 bg-slate-50 py-2.5 px-3 border font-medium"><?= e($submission?->textResponse ?? '') ?></textarea>
                </div>

                <div>
                    <label for="attachment" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                        File Attachment (PDF, DOCX, ZIP, Image, etc.)
                    </label>
                    <input type="file" name="attachment" id="attachment"
                           class="w-full text-xs text-slate-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-brand-50 file:text-brand-700 hover:file:bg-brand-100">
                </div>

                <div class="pt-3">
                    <button type="submit" class="w-full sm:w-auto px-6 py-2.5 bg-brand-600 hover:bg-brand-700 text-white text-sm font-semibold rounded-xl shadow-sm transition">
                        <?= $submission ? 'Update Submission' : 'Submit Assignment' ?>
                    </button>
                </div>
            </form>
        </div>
    <?php endif; ?>
</div>
