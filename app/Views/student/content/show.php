<div class="max-w-4xl mx-auto space-y-6">
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
                    <a href="/student/content?class_subject_id=<?= (int)$item->classSubjectId ?>" class="hover:text-sky-600 transition">
                        <?= htmlspecialchars($item->classSubject?->subject?->name ?? 'Course Materials') ?>
                    </a>
                </nav>
                <div class="flex flex-wrap items-center gap-3">
                    <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight leading-tight">
                        <?= htmlspecialchars($item->title) ?>
                    </h1>
                    <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold bg-sky-50 text-sky-800 border border-sky-200">
                        <?= htmlspecialchars($item->classSubject?->subject?->code ?: 'SUBJ') ?>
                    </span>
                    <?php if (!empty($item->topic)): ?>
                        <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold bg-slate-100 text-slate-700 border border-slate-200">
                            Topic: <?= htmlspecialchars($item->topic) ?>
                        </span>
                    <?php endif; ?>
                </div>
                <p class="text-xs text-slate-500 mt-1.5 flex items-center gap-2 flex-wrap">
                    <span>Teacher: <strong class="text-slate-800 font-semibold"><?= htmlspecialchars($item->teacher?->user?->name ?? $item->teacher?->name ?? 'Faculty Staff') ?></strong></span>
                    <span>&bull;</span>
                    <span>Published: <strong><?= date('F j, Y', strtotime($item->publishedAt ?? $item->createdAt)) ?></strong></span>
                </p>
            </div>

            <div class="flex items-center gap-2.5 flex-wrap">
                <a href="/student/content?class_subject_id=<?= (int)$item->classSubjectId ?>" 
                   class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-bold text-slate-700 bg-slate-100 hover:bg-slate-200 transition">
                    <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    <span>All Lessons</span>
                </a>
                <?php if ($item->file): ?>
                    <a href="/files/<?= (int)$item->file->id ?>/download" 
                       class="inline-flex items-center gap-1.5 px-4 py-2 bg-sky-600 hover:bg-sky-700 text-white text-xs font-bold rounded-xl shadow-xs transition">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                        <span>Download Attachment</span>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- External Link / Video Banner if Present -->
    <?php if (!empty($item->externalUrl)): ?>
        <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500">External Educational Resource</h3>
                    <p class="text-xs font-mono font-semibold text-slate-700 truncate max-w-md"><?= htmlspecialchars($item->externalUrl) ?></p>
                </div>
            </div>
            <a href="<?= htmlspecialchars($item->externalUrl) ?>" target="_blank" rel="noopener noreferrer" 
               class="inline-flex items-center gap-1.5 px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-xl text-xs font-bold transition shadow-xs flex-shrink-0">
                <span>Open in New Tab</span>
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                </svg>
            </a>
        </div>
    <?php endif; ?>

    <!-- Main Lesson Body Text Card -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6 sm:p-8 space-y-6">
        <h2 class="text-sm font-bold uppercase tracking-wider text-slate-400">Lesson Material & Notes</h2>
        <div class="prose prose-slate max-w-none text-slate-800 leading-relaxed whitespace-pre-line text-sm sm:text-base font-normal">
            <?= htmlspecialchars($item->description ?? 'No textual notes provided for this lesson.') ?>
        </div>

        <!-- Attached File Card -->
        <?php if ($item->file): ?>
            <div class="pt-6 border-t border-slate-100">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-3">Attached Document</h3>
                <div class="p-4 bg-slate-50 rounded-2xl border border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-sky-100 text-sky-700 flex items-center justify-center flex-shrink-0">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs font-bold text-slate-900"><?= htmlspecialchars($item->file->originalName) ?></p>
                            <p class="text-[11px] text-slate-500 font-semibold"><?= htmlspecialchars($item->file->getFormattedSize()) ?> &bull; <?= htmlspecialchars($item->file->mimeType) ?></p>
                        </div>
                    </div>
                    <a href="/files/<?= (int)$item->file->id ?>/download" 
                       class="inline-flex items-center justify-center gap-1.5 px-4 py-2 bg-sky-600 hover:bg-sky-700 text-white rounded-xl text-xs font-bold shadow-xs transition flex-shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                        <span>Download Attachment</span>
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
