<div class="max-w-4xl mx-auto space-y-6">
    <!-- Breadcrumb & Back -->
    <div class="flex items-center justify-between">
        <a href="/student/content?class_subject_id=<?= $item->classSubjectId ?>" 
           class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-600 hover:text-slate-900 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Back to <?= e($item->classSubject?->subject?->name ?? 'Course Materials') ?>
        </a>

        <?php if ($item->file): ?>
            <a href="/files/<?= $item->file->id ?>/download" 
               class="inline-flex items-center gap-2 px-4 py-2 bg-brand-600 hover:bg-brand-700 text-white text-xs font-semibold rounded-xl shadow-sm transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                Download Attachment (<?= e($item->file->getFormattedSize()) ?>)
            </a>
        <?php endif; ?>
    </div>

    <!-- Main Content Container -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <!-- Lesson Header -->
        <div class="p-6 sm:p-8 border-b border-slate-100 bg-gradient-to-br from-slate-900 to-slate-800 text-white">
            <div class="flex items-center gap-2 mb-3">
                <span class="px-2.5 py-1 rounded-full text-xs font-semibold uppercase tracking-wider bg-white/10 text-cyan-300 backdrop-blur-sm">
                    <?= e($item->classSubject?->subject?->name ?? 'Subject') ?>
                </span>
                <?php if (!empty($item->topic)): ?>
                    <span class="text-xs text-slate-300 font-medium">/ <?= e($item->topic) ?></span>
                <?php endif; ?>
            </div>
            <h1 class="text-2xl sm:text-3xl font-bold tracking-tight text-white leading-tight">
                <?= e($item->title) ?>
            </h1>
            <div class="flex flex-wrap items-center gap-4 mt-4 text-xs text-slate-300">
                <span class="flex items-center gap-1.5">
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    Instructor: <?= e($item->teacher?->name ?? 'Teacher') ?>
                </span>
                <span class="flex items-center gap-1.5">
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    Published: <?= e(date('F j, Y', strtotime($item->publishedAt ?? $item->createdAt))) ?>
                </span>
            </div>
        </div>

        <!-- Video Embed / Link Section if Video Type -->
        <?php if (!empty($item->externalUrl)): ?>
            <div class="p-6 sm:p-8 border-b border-slate-100 bg-slate-50">
                <h3 class="text-sm font-bold text-slate-800 mb-3 flex items-center gap-2">
                    <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                    External Video / Resource Link
                </h3>
                <div class="p-4 bg-white rounded-xl border border-slate-200 flex items-center justify-between gap-4">
                    <div class="truncate text-xs font-mono text-slate-600"><?= e($item->externalUrl) ?></div>
                    <a href="<?= e($item->externalUrl) ?>" target="_blank" rel="noopener noreferrer" 
                       class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-purple-600 hover:bg-purple-700 text-white rounded-lg text-xs font-semibold transition flex-shrink-0">
                        <span>Open Resource</span>
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                    </a>
                </div>
            </div>
        <?php endif; ?>

        <!-- Lesson Body Text -->
        <div class="p-6 sm:p-8 space-y-6">
            <h3 class="text-sm font-bold uppercase tracking-wider text-slate-500">Lesson Material & Notes</h3>
            <div class="prose prose-slate max-w-none text-slate-700 leading-relaxed whitespace-pre-line text-sm sm:text-base">
                <?= e($item->description ?? 'No textual notes provided for this lesson.') ?>
            </div>

            <!-- Attached File Card if present -->
            <?php if ($item->file): ?>
                <div class="mt-8 pt-6 border-t border-slate-200">
                    <h4 class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-3">Attached Document</h4>
                    <div class="p-4 bg-slate-50 rounded-2xl border border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-brand-50 text-brand-600 flex items-center justify-center flex-shrink-0">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-slate-900"><?= e($item->file->originalName) ?></p>
                                <p class="text-xs text-slate-500"><?= e($item->file->getFormattedSize()) ?> &bull; <?= e($item->file->mimeType) ?></p>
                            </div>
                        </div>
                        <a href="/files/<?= $item->file->id ?>/download" 
                           class="inline-flex items-center justify-center gap-2 px-4 py-2 bg-brand-600 hover:bg-brand-700 text-white rounded-xl text-xs font-semibold shadow-sm transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                            Download File
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
