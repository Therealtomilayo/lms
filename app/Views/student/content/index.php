<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-slate-900 tracking-tight">Learning Materials & Notes</h2>
            <p class="text-sm text-slate-500 mt-1">Access lecture documents, teacher notes, video tutorials, and study resources.</p>
        </div>
    </div>

    <!-- Enrolled Subject Selector Tabs -->
    <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-sm">
        <form method="GET" action="/student/content" class="flex flex-wrap items-center gap-3">
            <label for="class_subject_id" class="text-xs font-semibold text-slate-600 uppercase tracking-wider">Course / Subject:</label>
            <select name="class_subject_id" id="class_subject_id" onchange="this.form.submit()"
                    class="flex-1 min-w-[280px] rounded-xl border-slate-300 text-sm focus:border-cyan-500 focus:ring-cyan-500 bg-slate-50 py-2 px-3 border font-medium">
                <?php if (empty($subjectEnrollments)): ?>
                    <option value="">No active subject enrollments</option>
                <?php else: ?>
                    <?php foreach ($subjectEnrollments as $se): ?>
                        <option value="<?= $se->classSubjectId ?>" <?= $se->classSubjectId === $selectedClassSubjectId ? 'selected' : '' ?>>
                            <?= e($se->classSubject?->subject?->name ?? 'Subject') ?> (<?= e($se->classSubject?->subject?->code ?? '') ?>)
                        </option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
            <noscript>
                <button type="submit" class="px-3 py-1.5 bg-slate-800 text-white rounded-lg text-xs font-medium">View</button>
            </noscript>
        </form>
    </div>

    <!-- Content Items -->
    <?php if (empty($selectedClassSubject)): ?>
        <div class="bg-white rounded-2xl border border-slate-200 p-12 text-center shadow-sm">
            <div class="w-16 h-16 rounded-full bg-slate-100 text-slate-400 mx-auto flex items-center justify-center mb-4">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
            </div>
            <h3 class="text-base font-semibold text-slate-800">No Enrolled Subject Selected</h3>
            <p class="text-sm text-slate-500 mt-1">Please select an enrolled subject from the dropdown above.</p>
        </div>
    <?php elseif (empty($items)): ?>
        <div class="bg-white rounded-2xl border border-slate-200 p-12 text-center shadow-sm">
            <div class="w-16 h-16 rounded-full bg-cyan-50 text-cyan-500 mx-auto flex items-center justify-center mb-4">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            </div>
            <h3 class="text-base font-semibold text-slate-800">No Study Materials Published Yet</h3>
            <p class="text-sm text-slate-500 mt-1">Your teacher has not uploaded any materials for <?= e($selectedClassSubject->subject?->name ?? 'this subject') ?> yet. Please check back soon!</p>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($items as $item): ?>
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm flex flex-col hover:border-slate-300 transition overflow-hidden">
                    <div class="p-5 pb-3 border-b border-slate-100 flex items-center justify-between gap-2">
                        <div class="flex items-center gap-2">
                            <?php if ($item->type === 'note'): ?>
                                <span class="p-2 rounded-lg bg-blue-50 text-blue-600">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                </span>
                            <?php elseif ($item->type === 'document'): ?>
                                <span class="p-2 rounded-lg bg-emerald-50 text-emerald-600">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                </span>
                            <?php elseif ($item->type === 'video'): ?>
                                <span class="p-2 rounded-lg bg-purple-50 text-purple-600">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                </span>
                            <?php else: ?>
                                <span class="p-2 rounded-lg bg-amber-50 text-amber-600">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                                </span>
                            <?php endif; ?>
                            <span class="text-xs font-semibold uppercase tracking-wider text-slate-500"><?= e($item->type) ?></span>
                        </div>
                        <span class="text-[11px] text-slate-400"><?= e(date('M j, Y', strtotime($item->publishedAt ?? $item->createdAt))) ?></span>
                    </div>

                    <div class="p-5 flex-1 space-y-2.5">
                        <?php if (!empty($item->topic)): ?>
                            <span class="inline-block text-[11px] font-semibold text-cyan-600 uppercase tracking-wider">
                                <?= e($item->topic) ?>
                            </span>
                        <?php endif; ?>

                        <h3 class="text-base font-bold text-slate-900 leading-snug">
                            <a href="/student/content/<?= $item->id ?>" class="hover:text-cyan-600 transition">
                                <?= e($item->title) ?>
                            </a>
                        </h3>

                        <?php if (!empty($item->description)): ?>
                            <p class="text-xs text-slate-600 line-clamp-3 leading-relaxed">
                                <?= e($item->description) ?>
                            </p>
                        <?php endif; ?>
                    </div>

                    <div class="p-4 bg-slate-50 border-t border-slate-100 flex items-center justify-between gap-2">
                        <a href="/student/content/<?= $item->id ?>" class="text-xs font-semibold text-cyan-600 hover:text-cyan-700">
                            View Lesson &rarr;
                        </a>

                        <?php if ($item->file): ?>
                            <a href="/files/<?= $item->file->id ?>/download" 
                               class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 rounded-lg text-xs font-medium shadow-sm transition">
                                <svg class="w-3.5 h-3.5 text-brand-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                <span>Download (<?= e($item->file->getFormattedSize()) ?>)</span>
                            </a>
                        <?php elseif (!empty($item->externalUrl)): ?>
                            <a href="<?= e($item->externalUrl) ?>" target="_blank" rel="noopener noreferrer"
                               class="inline-flex items-center gap-1 text-xs font-medium text-purple-600 hover:underline">
                                <span>Open Link</span>
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
