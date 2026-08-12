<div class="space-y-6">
    <!-- Header Controls -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-slate-900 tracking-tight">Coursework Assignments & Grading</h2>
            <p class="text-sm text-slate-500 mt-1">Create homework, assignments, projects, and grade student submissions.</p>
        </div>
        <div>
            <a href="/teacher/assignments/create" 
               class="inline-flex items-center gap-2 px-4 py-2.5 bg-brand-600 hover:bg-brand-700 text-white text-sm font-semibold rounded-xl shadow-sm transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Create Assignment
            </a>
        </div>
    </div>

    <!-- Assignment List -->
    <?php if (empty($assignments)): ?>
        <div class="bg-white rounded-2xl border border-slate-200 p-12 text-center shadow-sm">
            <div class="w-16 h-16 rounded-full bg-brand-50 text-brand-500 mx-auto flex items-center justify-center mb-4">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
            </div>
            <h3 class="text-base font-semibold text-slate-800">No Assignments Created Yet</h3>
            <p class="text-sm text-slate-500 mt-1 max-w-sm mx-auto">Get started by creating your first homework task, essay topic, or class assignment.</p>
            <div class="mt-6">
                <a href="/teacher/assignments/create" 
                   class="inline-flex items-center gap-2 px-4 py-2 bg-brand-600 hover:bg-brand-700 text-white text-sm font-semibold rounded-xl shadow-sm transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Create Assignment
                </a>
            </div>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($assignments as $assignment): ?>
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm flex flex-col hover:border-slate-300 transition overflow-hidden">
                    <!-- Top Ribbon -->
                    <div class="p-5 pb-3 border-b border-slate-100 flex items-start justify-between gap-2">
                        <div>
                            <span class="text-xs font-bold text-brand-600 uppercase tracking-wider">
                                <?= e($assignment->classSubject?->subjectName ?? 'Subject') ?>
                            </span>
                            <span class="text-xs text-slate-400 font-medium ml-1">
                                · <?= e($assignment->classSubject?->className ?? 'Class') ?>
                            </span>
                        </div>
                        <?php if ($assignment->isPublished()): ?>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">
                                Published
                            </span>
                        <?php elseif ($assignment->isArchived()): ?>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-600 border border-slate-200">
                                Archived
                            </span>
                        <?php else: ?>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-amber-50 text-amber-700 border border-amber-200">
                                Draft
                            </span>
                        <?php endif; ?>
                    </div>

                    <!-- Card Body -->
                    <div class="p-5 flex-1 space-y-3">
                        <?php if (!empty($assignment->topic)): ?>
                            <span class="inline-block text-[11px] font-semibold text-slate-500 uppercase tracking-wider">
                                <?= e($assignment->topic) ?>
                            </span>
                        <?php endif; ?>

                        <h3 class="text-base font-bold text-slate-900 leading-snug"><?= e($assignment->title) ?></h3>

                        <p class="text-xs text-slate-600 line-clamp-3 leading-relaxed">
                            <?= e($assignment->instructions) ?>
                        </p>

                        <!-- Details meta -->
                        <div class="pt-2 border-t border-slate-100 flex flex-wrap items-center justify-between text-xs text-slate-500 gap-2">
                            <div>
                                <span class="font-medium text-slate-700">Due:</span> 
                                <span class="<?= $assignment->isPastDue() ? 'text-rose-600 font-medium' : '' ?>">
                                    <?= date('M d, Y · g:i A', strtotime($assignment->dueAt)) ?>
                                </span>
                            </div>
                            <div>
                                <span class="font-medium text-slate-700">Max Score:</span> <?= number_format($assignment->maxScore, 0) ?> pts
                            </div>
                        </div>

                        <!-- Submissions Count Badge -->
                        <?php 
                            $subsCount = $submissionCounts[$assignment->id] ?? 0;
                            $gradCount = $gradedCounts[$assignment->id] ?? 0;
                        ?>
                        <div class="p-3 bg-slate-50 rounded-xl border border-slate-200 flex items-center justify-between">
                            <span class="text-xs font-semibold text-slate-700">Submissions:</span>
                            <span class="text-xs font-medium text-slate-600">
                                <strong class="text-brand-600"><?= $subsCount ?></strong> submitted · 
                                <strong class="text-emerald-600"><?= $gradCount ?></strong> graded
                            </span>
                        </div>
                    </div>

                    <!-- Footer Actions -->
                    <div class="p-4 bg-slate-50 border-t border-slate-100 flex items-center justify-between gap-2">
                        <a href="/teacher/assignments/<?= $assignment->id ?>/submissions" 
                           class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-brand-50 hover:bg-brand-100 text-brand-700 text-xs font-semibold rounded-lg transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            Submissions & Grading
                        </a>

                        <div class="flex items-center gap-1">
                            <a href="/teacher/assignments/<?= $assignment->id ?>/edit" 
                               class="p-1.5 text-slate-500 hover:text-slate-800 rounded-lg hover:bg-slate-200 transition" title="Edit">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            </a>

                            <form action="/teacher/assignments/<?= $assignment->id ?>/delete" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete/archive this assignment?');">
                                <?= csrf_field() ?>
                                <button type="submit" class="p-1.5 text-slate-500 hover:text-rose-600 rounded-lg hover:bg-slate-200 transition" title="Delete / Archive">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
