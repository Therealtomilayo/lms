<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-slate-900 tracking-tight">Teacher Gradebook</h2>
            <p class="text-sm text-slate-500 mt-1">Select an assigned class and subject to enter continuous assessments and exam scores.</p>
        </div>
    </div>

    <?php if (empty($classSubjects)): ?>
        <div class="bg-white rounded-2xl border border-slate-200 p-12 text-center shadow-sm">
            <div class="w-16 h-16 rounded-full bg-brand-50 text-brand-500 mx-auto flex items-center justify-center mb-4">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
            </div>
            <h3 class="text-base font-semibold text-slate-800">No Assigned Classes Found</h3>
            <p class="text-sm text-slate-500 mt-1 max-w-sm mx-auto">You have not been assigned to any class subjects for the current academic session.</p>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($classSubjects as $cs): ?>
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 flex flex-col justify-between hover:border-slate-300 transition">
                    <div>
                        <span class="text-xs font-bold text-brand-600 uppercase tracking-wider">
                            <?= e($cs->class?->name ?? 'Class') ?>
                        </span>
                        <h3 class="text-lg font-bold text-slate-900 mt-1">
                            <?= e($cs->subject?->name ?? 'Subject') ?>
                        </h3>
                        <p class="text-xs text-slate-500 mt-0.5">Code: <?= e($cs->subject?->code ?? 'N/A') ?></p>
                    </div>

                    <div class="mt-6 pt-4 border-t border-slate-100 flex items-center justify-between">
                        <span class="text-xs text-slate-500 font-medium">
                            <?= e($activeTerm?->name ?? 'Current Term') ?>
                        </span>
                        <a href="/teacher/gradebook/<?= $cs->id ?>" class="inline-flex items-center gap-1.5 px-4 py-2 bg-brand-600 hover:bg-brand-700 text-white text-xs font-semibold rounded-xl transition shadow-sm">
                            Open Gradebook
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
