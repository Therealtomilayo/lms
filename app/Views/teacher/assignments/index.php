<div class="space-y-6">
    <!-- Header Card -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <!-- Breadcrumbs -->
                <nav class="flex items-center gap-2 text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">
                    <a href="/teacher/dashboard" class="text-slate-400 hover:text-emerald-600 transition">Faculty Portal</a>
                    <span class="text-slate-300">/</span>
                    <span class="text-slate-700">Assignments & Homework</span>
                </nav>
                <div class="flex items-center gap-3">
                    <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight leading-tight">
                        Coursework Assignments & Grading
                    </h1>
                    <?php if (!empty($assignments)): ?>
                        <?php $this->include('components/badge', [
                            'label' => count($assignments) . ' Active ' . (count($assignments) === 1 ? 'Task' : 'Tasks'),
                            'variant' => 'neutral',
                            'size' => 'sm'
                        ]); ?>
                    <?php endif; ?>
                </div>
                <p class="text-xs text-slate-500 mt-1">
                    Publish homework tasks, manage submission deadlines, and evaluate student submissions.
                </p>
            </div>

            <div>
                <?php $this->include('components/button', [
                    'label' => 'Create Assignment',
                    'variant' => 'primary',
                    'href' => '/teacher/assignments/create',
                    'icon' => '<svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>'
                ]); ?>
            </div>
        </div>
    </div>

    <!-- Assignment List / Grid -->
    <?php if (empty($assignments)): ?>
        <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-12 text-center">
            <div class="w-16 h-16 rounded-2xl bg-emerald-50 text-emerald-600 mx-auto flex items-center justify-center mb-4">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                </svg>
            </div>
            <h3 class="text-base font-bold text-slate-900">No Assignments Created Yet</h3>
            <p class="text-xs text-slate-500 mt-1.5 max-w-sm mx-auto">
                Get started by creating your first homework task, project assignment, or essay for your assigned classes.
            </p>
            <div class="mt-6 flex justify-center">
                <?php $this->include('components/button', [
                    'label' => 'Create First Assignment',
                    'variant' => 'primary',
                    'href' => '/teacher/assignments/create',
                    'icon' => '<svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>'
                ]); ?>
            </div>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($assignments as $assignment): ?>
                <?php 
                    $subsCount = $submissionCounts[$assignment->id] ?? 0;
                    $gradCount = $gradedCounts[$assignment->id] ?? 0;
                    $subjName = $assignment->classSubject?->subject?->name ?? ($assignment->classSubject?->subjectName ?? ($assignment->subjectName ?? 'Subject'));
                    $className = $assignment->classSubject?->schoolClass?->name ?? ($assignment->classSubject?->className ?? ($assignment->className ?? 'Class'));
                    $armName = $assignment->classSubject?->schoolClass?->sectionArm ?? ($assignment->classSubject?->sectionArm ?? '');
                ?>
                <div class="bg-white rounded-2xl border border-slate-200 shadow-xs flex flex-col hover:border-slate-300 hover:shadow-md transition duration-200 overflow-hidden">
                    <!-- Top Allocation Ribbon -->
                    <div class="p-5 pb-3 border-b border-slate-100 flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <div class="text-xs font-bold text-emerald-700 uppercase tracking-wider truncate">
                                <?= htmlspecialchars($subjName) ?>
                            </div>
                            <div class="text-[11px] text-slate-500 font-medium truncate mt-0.5">
                                <?= htmlspecialchars($className) ?><?= !empty($armName) ? ' (' . htmlspecialchars($armName) . ')' : '' ?>
                            </div>
                        </div>
                        <div>
                            <?php if ($assignment->isPublished()): ?>
                                <?php $this->include('components/badge', ['label' => 'Published', 'variant' => 'success', 'size' => 'xs']); ?>
                            <?php elseif ($assignment->isArchived()): ?>
                                <?php $this->include('components/badge', ['label' => 'Archived', 'variant' => 'neutral', 'size' => 'xs']); ?>
                            <?php else: ?>
                                <?php $this->include('components/badge', ['label' => 'Draft', 'variant' => 'warning', 'size' => 'xs']); ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Card Body -->
                    <div class="p-5 flex-1 space-y-3">
                        <?php if (!empty($assignment->topic)): ?>
                            <span class="inline-block text-[10px] font-bold text-slate-500 uppercase tracking-wider bg-slate-100 px-2 py-0.5 rounded">
                                <?= htmlspecialchars($assignment->topic) ?>
                            </span>
                        <?php endif; ?>

                        <h3 class="text-sm font-bold text-slate-900 leading-snug line-clamp-2">
                            <?= htmlspecialchars($assignment->title) ?>
                        </h3>

                        <?php if (!empty($assignment->instructions)): ?>
                            <p class="text-xs text-slate-600 line-clamp-3 leading-relaxed">
                                <?= htmlspecialchars($assignment->instructions) ?>
                            </p>
                        <?php endif; ?>

                        <!-- Details meta -->
                        <div class="pt-2 border-t border-slate-100 flex flex-wrap items-center justify-between text-xs text-slate-500 gap-2">
                            <div>
                                <span class="font-bold text-slate-700">Due:</span> 
                                <span class="<?= $assignment->isPastDue() ? 'text-rose-600 font-bold' : 'text-slate-800 font-medium' ?>">
                                    <?= date('M d, Y · g:i A', strtotime((string)$assignment->dueAt)) ?>
                                </span>
                            </div>
                            <div>
                                <span class="font-bold text-slate-700">Max Score:</span> 
                                <span class="font-mono font-bold text-slate-900"><?= number_format((float)$assignment->maxScore, 0) ?> pts</span>
                            </div>
                        </div>

                        <!-- Submissions Count Metric Box -->
                        <div class="p-3 bg-slate-50 rounded-xl border border-slate-200 flex items-center justify-between">
                            <span class="text-xs font-bold text-slate-700">Submissions</span>
                            <span class="text-xs font-semibold text-slate-600">
                                <strong class="text-emerald-700 font-bold"><?= (int)$subsCount ?></strong> turned in · 
                                <strong class="text-slate-800 font-bold"><?= (int)$gradCount ?></strong> graded
                            </span>
                        </div>
                    </div>

                    <!-- Footer Actions -->
                    <div class="p-4 bg-slate-50/80 border-t border-slate-100 flex items-center justify-between gap-2">
                        <?php $this->include('components/button', [
                            'label' => 'Submissions & Grading',
                            'variant' => 'secondary',
                            'size' => 'sm',
                            'href' => '/teacher/assignments/' . (int)$assignment->id . '/submissions',
                            'icon' => '<svg class="w-3.5 h-3.5 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>'
                        ]); ?>

                        <div class="flex items-center gap-1">
                            <a href="/teacher/assignments/<?= (int)$assignment->id ?>/edit" 
                               class="p-2 text-slate-500 hover:text-slate-900 hover:bg-slate-200 rounded-lg transition" title="Edit Assignment">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            </a>

                            <form action="/teacher/assignments/<?= (int)$assignment->id ?>/delete" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete/archive this assignment?');">
                                <?= csrf_field() ?>
                                <button type="submit" class="p-2 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition cursor-pointer" title="Delete / Archive">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
