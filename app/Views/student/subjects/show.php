<div class="space-y-6">
    <div class="flex items-center justify-between">
        <a href="/student/subjects" class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-600 hover:text-slate-900 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Back to Enrolled Subjects
        </a>

        <a href="/student/content?class_subject_id=<?= $classSubject->id ?>" 
           class="inline-flex items-center gap-2 px-4 py-2 bg-cyan-600 hover:bg-cyan-700 text-white text-xs font-semibold rounded-xl shadow-sm transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
            All Course Materials
        </a>
    </div>

    <!-- Course Banner -->
    <div class="bg-gradient-to-r from-slate-900 via-slate-800 to-cyan-950 rounded-2xl p-6 sm:p-8 text-white shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-6">
        <div>
            <div class="flex items-center gap-2 mb-2">
                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-white/10 text-cyan-300 backdrop-blur-sm">
                    <?= e($classSubject->subject?->code ?? 'SUBJ') ?>
                </span>
                <span class="text-xs text-slate-300"><?= e($classSubject->schoolClass?->name ?? 'Class') ?></span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-bold tracking-tight text-white"><?= e($classSubject->subject?->name ?? 'Subject') ?></h1>
            <p class="text-xs text-slate-300 mt-2 flex items-center gap-2">
                <span>Instructor: <strong><?= e($classSubject->teacher?->name ?? 'Staff') ?></strong></span>
            </p>
        </div>
    </div>

    <!-- Published Topics & Lessons List -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
        <h2 class="text-lg font-bold text-slate-900 mb-4">Lessons & Syllabus Topics</h2>

        <?php if (empty($items)): ?>
            <p class="text-sm text-slate-500 py-6 text-center">No learning materials published for this subject yet.</p>
        <?php else: ?>
            <div class="space-y-4">
                <?php foreach ($items as $item): ?>
                    <div class="p-4 rounded-xl border border-slate-200 hover:border-slate-300 bg-slate-50/50 flex flex-col sm:flex-row sm:items-center justify-between gap-4 transition">
                        <div class="space-y-1">
                            <?php if (!empty($item->topic)): ?>
                                <span class="text-[10px] font-bold text-cyan-600 uppercase tracking-wider"><?= e($item->topic) ?></span>
                            <?php endif; ?>
                            <h3 class="text-sm font-bold text-slate-900">
                                <a href="/student/content/<?= $item->id ?>" class="hover:text-cyan-600 transition">
                                    <?= e($item->title) ?>
                                </a>
                            </h3>
                            <p class="text-xs text-slate-500"><?= e($item->type) ?> &bull; Published <?= e(date('M j, Y', strtotime($item->publishedAt ?? $item->createdAt))) ?></p>
                        </div>
                        <div class="flex items-center gap-2 flex-shrink-0">
                            <a href="/student/content/<?= $item->id ?>" class="px-3 py-1.5 bg-white border border-slate-300 text-slate-700 text-xs font-semibold rounded-lg hover:bg-slate-50 transition shadow-sm">
                                View
                            </a>
                            <?php if ($item->file): ?>
                                <a href="/files/<?= $item->file->id ?>/download" class="px-3 py-1.5 bg-brand-600 text-white text-xs font-semibold rounded-lg hover:bg-brand-700 transition shadow-sm flex items-center gap-1">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                    Download
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
