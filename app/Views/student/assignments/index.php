<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-slate-900 tracking-tight">Coursework & Assignments</h2>
            <p class="text-sm text-slate-500 mt-1">Review assignments, submission deadlines, and feedback from your teachers.</p>
        </div>
    </div>

    <!-- Active Assignments Section -->
    <div class="space-y-4">
        <h3 class="text-lg font-bold text-slate-800 flex items-center gap-2">
            <span class="w-2.5 h-2.5 rounded-full bg-brand-600"></span>
            Active Coursework
            <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-brand-50 text-brand-700">
                <?= count($activeAssignments) ?>
            </span>
        </h3>

        <?php if (empty($activeAssignments)): ?>
            <div class="bg-white rounded-2xl border border-slate-200 p-8 text-center shadow-sm">
                <p class="text-sm text-slate-500">No active assignments due at this time.</p>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($activeAssignments as $assignment): ?>
                    <?php $sub = $submissions[$assignment->id] ?? null; ?>
                    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm flex flex-col hover:border-slate-300 transition overflow-hidden">
                        <div class="p-5 pb-3 border-b border-slate-100 flex items-start justify-between gap-2">
                            <div>
                                <span class="text-xs font-bold text-brand-600 uppercase tracking-wider">
                                    <?= e($assignment->classSubject?->subjectName ?? 'Subject') ?>
                                </span>
                            </div>
                            <?php if ($sub && $sub->isGraded()): ?>
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                    Graded: <?= number_format($sub->score, 1) ?>/<?= number_format($assignment->maxScore, 0) ?>
                                </span>
                            <?php elseif ($sub): ?>
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-blue-50 text-blue-700 border border-blue-200">
                                    Submitted
                                </span>
                            <?php else: ?>
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                    Pending
                                </span>
                            <?php endif; ?>
                        </div>

                        <div class="p-5 flex-1 space-y-2">
                            <?php if (!empty($assignment->topic)): ?>
                                <span class="inline-block text-[11px] font-semibold text-slate-500 uppercase tracking-wider">
                                    <?= e($assignment->topic) ?>
                                </span>
                            <?php endif; ?>

                            <h4 class="text-base font-bold text-slate-900 leading-snug">
                                <?= e($assignment->title) ?>
                            </h4>

                            <p class="text-xs text-slate-600 line-clamp-2 leading-relaxed">
                                <?= e($assignment->instructions) ?>
                            </p>

                            <div class="pt-3 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
                                <span>Due: <strong class="text-slate-800"><?= date('M d, g:i A', strtotime($assignment->dueAt)) ?></strong></span>
                                <span>Max: <strong class="text-slate-800"><?= number_format($assignment->maxScore, 0) ?> pts</strong></span>
                            </div>
                        </div>

                        <div class="p-4 bg-slate-50 border-t border-slate-100">
                            <a href="/student/assignments/<?= $assignment->id ?>" 
                               class="w-full inline-flex items-center justify-center gap-2 py-2 px-4 bg-brand-600 hover:bg-brand-700 text-white text-xs font-semibold rounded-xl shadow-sm transition">
                                <?= $sub ? 'View Submission & Grade' : 'Open & Submit Work' ?>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Past Due Assignments Section -->
    <?php if (!empty($pastDueAssignments)): ?>
        <div class="space-y-4 pt-6 border-t border-slate-200">
            <h3 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-slate-400"></span>
                Past Deadlines
                <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-slate-100 text-slate-600">
                    <?= count($pastDueAssignments) ?>
                </span>
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($pastDueAssignments as $assignment): ?>
                    <?php $sub = $submissions[$assignment->id] ?? null; ?>
                    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm flex flex-col hover:border-slate-300 transition overflow-hidden">
                        <div class="p-5 pb-3 border-b border-slate-100 flex items-start justify-between gap-2">
                            <div>
                                <span class="text-xs font-bold text-slate-600 uppercase tracking-wider">
                                    <?= e($assignment->classSubject?->subjectName ?? 'Subject') ?>
                                </span>
                            </div>
                            <?php if ($sub && $sub->isGraded()): ?>
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                    Graded: <?= number_format($sub->score, 1) ?>/<?= number_format($assignment->maxScore, 0) ?>
                                </span>
                            <?php elseif ($sub): ?>
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-blue-50 text-blue-700 border border-blue-200">
                                    Submitted <?= $sub->isLate() ? '(Late)' : '' ?>
                                </span>
                            <?php else: ?>
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-rose-50 text-rose-700 border border-rose-200">
                                    Missing
                                </span>
                            <?php endif; ?>
                        </div>

                        <div class="p-5 flex-1 space-y-2">
                            <h4 class="text-base font-bold text-slate-900 leading-snug">
                                <?= e($assignment->title) ?>
                            </h4>

                            <div class="pt-3 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
                                <span>Deadline was: <?= date('M d, Y', strtotime($assignment->dueAt)) ?></span>
                                <span><?= number_format($assignment->maxScore, 0) ?> pts</span>
                            </div>
                        </div>

                        <div class="p-4 bg-slate-50 border-t border-slate-100">
                            <a href="/student/assignments/<?= $assignment->id ?>" 
                               class="w-full inline-flex items-center justify-center gap-2 py-2 px-4 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded-xl transition">
                                View Details
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>
