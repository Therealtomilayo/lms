<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-slate-900 tracking-tight">My Enrolled Subjects</h2>
            <p class="text-sm text-slate-500 mt-1">Subjects enrolled for the active academic term (<?= e($activeSession?->name ?? 'Current Session') ?>).</p>
        </div>
    </div>

    <?php if (empty($subjectEnrollments)): ?>
        <div class="bg-white rounded-2xl border border-slate-200 p-12 text-center shadow-sm">
            <div class="w-16 h-16 rounded-full bg-slate-100 text-slate-400 mx-auto flex items-center justify-center mb-4">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
            </div>
            <h3 class="text-base font-semibold text-slate-800">No Enrolled Subjects</h3>
            <p class="text-sm text-slate-500 mt-1">You are not currently enrolled in any academic subjects. Please contact the administrator or registrar.</p>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($subjectEnrollments as $se): ?>
                <?php $cs = $se->classSubject; ?>
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm flex flex-col hover:border-slate-300 transition overflow-hidden">
                    <div class="p-5 pb-3 border-b border-slate-100 flex items-center justify-between">
                        <span class="px-2.5 py-0.5 rounded-full text-[11px] font-semibold tracking-wider uppercase bg-cyan-50 text-cyan-700 border border-cyan-200">
                            <?= e($cs?->subject?->code ?? 'SUBJ') ?>
                        </span>
                        <span class="text-xs font-semibold text-slate-500"><?= e($cs?->schoolClass?->name ?? 'Class') ?></span>
                    </div>

                    <div class="p-5 flex-1 space-y-3">
                        <h3 class="text-lg font-bold text-slate-900 leading-snug">
                            <a href="/student/subjects/<?= $cs?->id ?>" class="hover:text-cyan-600 transition">
                                <?= e($cs?->subject?->name ?? 'Subject') ?>
                            </a>
                        </h3>
                        <p class="text-xs text-slate-500 flex items-center gap-1.5">
                            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                            Teacher: <?= e($cs?->teacher?->name ?? 'Faculty Staff') ?>
                        </p>
                    </div>

                    <div class="p-4 bg-slate-50 border-t border-slate-100 flex items-center justify-between gap-2">
                        <a href="/student/subjects/<?= $cs?->id ?>" class="text-xs font-semibold text-cyan-600 hover:text-cyan-700">
                            Subject Overview &rarr;
                        </a>
                        <a href="/student/content?class_subject_id=<?= $cs?->id ?>" 
                           class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-cyan-600 hover:bg-cyan-700 text-white rounded-xl text-xs font-semibold shadow-sm transition">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                            Materials
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
