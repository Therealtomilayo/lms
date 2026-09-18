<div class="space-y-6">
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
                    <span class="text-slate-700">Course Workspace</span>
                </nav>
                <?php 
                    $sName = $classSubject->subject?->name ?? 'Subject';
                    $sCode = $classSubject->subject?->code ?? '';
                    $cName = $classSubject->schoolClass?->name ?? 'Class';
                    $tName = $classSubject->teacher?->user?->name ?? ($classSubject->teacher?->name ?? 'Subject Teacher');
                ?>
                <div class="flex flex-wrap items-center gap-3">
                    <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight leading-tight">
                        <?= htmlspecialchars($sName) ?>
                    </h1>
                    <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold bg-sky-50 text-sky-800 border border-sky-200">
                        <?= htmlspecialchars($sCode ?: 'SUBJ') ?>
                    </span>
                    <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold bg-slate-100 text-slate-700 border border-slate-200">
                        <?= htmlspecialchars($cName) ?>
                    </span>
                </div>
                <p class="text-xs text-slate-500 mt-1 flex items-center gap-2">
                    <span>Teacher: <strong class="text-slate-800 font-semibold"><?= htmlspecialchars($tName) ?></strong></span>
                    <span>&bull;</span>
                    <span>Term: <strong class="text-emerald-700 font-semibold"><?= htmlspecialchars($activeTerm?->name ?? 'Current Term') ?></strong></span>
                </p>
            </div>

            <div class="flex items-center gap-2.5">
                <a href="/student/subjects/<?= (int)$classSubject->id ?>/discussions"
                   class="px-3.5 py-2 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-bold transition shadow-xs inline-flex items-center gap-1.5">
                    <svg class="w-4 h-4 text-brand-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                    Class Discussions
                </a>
                <?php $this->include('components/button', [
                    'label' => 'Back to Subjects',
                    'variant' => 'secondary',
                    'href' => '/student/subjects',
                    'icon' => '<svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>'
                ]); ?>
            </div>
        </div>
    </div>

    <!-- Course Learning Progression Card (PHASE-6) -->
    <?php 
        $hasModules = !empty($learningPath['has_modules']);
        $coursePct = (float)($learningPath['course_progress_percent'] ?? 0.0);
        $isCourseCompleted = !empty($learningPath['is_course_completed']);
        $totalReq = (int)($learningPath['total_required_items'] ?? 0);
        $totalComp = (int)($learningPath['total_completed_items'] ?? 0);
    ?>
    <div class="bg-white rounded-xl border border-slate-200 shadow-xs p-6">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div class="space-y-2 max-w-xl">
                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-semibold uppercase tracking-wider bg-sky-50 text-sky-700 border border-sky-200">
                        Learning Progression Path
                    </span>
                    <?php if ($isCourseCompleted): ?>
                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-md text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            Course Completed
                        </span>
                    <?php elseif ($coursePct > 0): ?>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-semibold bg-amber-50 text-amber-800 border border-amber-200">
                            In Progress (<?= number_format($coursePct, 1) ?>%)
                        </span>
                    <?php else: ?>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-semibold bg-slate-100 text-slate-600 border border-slate-200">
                            Not Started
                        </span>
                    <?php endif; ?>
                </div>
                <h2 class="text-xl font-bold tracking-tight text-slate-900">
                    Academic Course Journey & Completion
                </h2>
                <p class="text-xs text-slate-600 leading-relaxed">
                    <?php if ($hasModules): ?>
                        Complete all required learning materials, quizzes, and assignments across all modules to fulfill course requirements.
                    <?php else: ?>
                        Study published notes, submit assignments, and take scheduled CBT quizzes to build continuous assessment progress.
                    <?php endif; ?>
                </p>
            </div>

            <!-- Progress Meter Widget -->
            <div class="bg-slate-50 rounded-xl p-4 border border-slate-200 min-w-[260px]">
                <div class="flex items-baseline justify-between gap-2 mb-2">
                    <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Overall Progress</span>
                    <span class="text-2xl font-extrabold text-slate-900"><?= number_format($coursePct, 1) ?>%</span>
                </div>
                <!-- Progress Bar -->
                <div class="w-full h-2.5 bg-slate-200 rounded-full overflow-hidden">
                    <div class="h-full <?= $isCourseCompleted ? 'bg-emerald-600' : 'bg-sky-600' ?> rounded-full transition-all duration-500" style="width: <?= min(100.0, max(0.0, $coursePct)) ?>%;"></div>
                </div>
                <div class="flex items-center justify-between text-xs text-slate-500 mt-2 font-medium">
                    <span><?= $totalComp ?> of <?= $totalReq ?> activities completed</span>
                    <span><?= $isCourseCompleted ? '100% Done' : ($totalReq - $totalComp) . ' remaining' ?></span>
                </div>
            </div>
        </div>
    </div>

    <?php if (!empty($resumeTarget)): ?>
        <!-- Continue Learning / Resume Quick-Action Card (PHASE-7) -->
        <div class="bg-white rounded-2xl border border-sky-200 bg-sky-50/20 shadow-xs p-5 transition hover:border-sky-300">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-start gap-3.5">
                    <div class="w-10 h-10 rounded-xl bg-sky-100 border border-sky-200 flex items-center justify-center shrink-0 text-sky-700 mt-0.5">
                        <?php if ($resumeTarget['subtype'] === 'pdf'): ?>
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                            </svg>
                        <?php elseif ($resumeTarget['subtype'] === 'quiz'): ?>
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                            </svg>
                        <?php elseif ($resumeTarget['subtype'] === 'assignment'): ?>
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        <?php else: ?>
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                            </svg>
                        <?php endif; ?>
                    </div>
                    <div class="space-y-0.5">
                        <div class="flex items-center gap-2">
                            <span class="text-2xs font-bold uppercase tracking-wider text-sky-700">
                                <?= $resumeTarget['type'] === 'resume' ? 'Continue Learning' : 'Next Up in Course' ?>
                            </span>
                            <?php if ($resumeTarget['progress_percent'] > 0): ?>
                                <span class="px-2 py-0.5 rounded-full text-2xs font-bold bg-amber-100 text-amber-800">
                                    <?= number_format((float)$resumeTarget['progress_percent'], 0) ?>% read
                                </span>
                            <?php endif; ?>
                        </div>
                        <h3 class="text-sm font-bold text-slate-900">
                            <?= htmlspecialchars($resumeTarget['title'], ENT_QUOTES, 'UTF-8') ?>
                        </h3>
                        <p class="text-xs text-slate-500 flex items-center gap-1.5 flex-wrap">
                            <span>Module: <strong class="text-slate-700 font-semibold"><?= htmlspecialchars($resumeTarget['module_title'], ENT_QUOTES, 'UTF-8') ?></strong></span>
                            <?php if (!empty($resumeTarget['last_page'])): ?>
                                <span>&bull;</span>
                                <span>Page <strong><?= (int)$resumeTarget['last_page'] ?></strong><?= !empty($resumeTarget['total_pages']) ? ' of ' . (int)$resumeTarget['total_pages'] : '' ?></span>
                            <?php endif; ?>
                        </p>
                    </div>
                </div>

                <div class="shrink-0">
                    <a href="<?= htmlspecialchars($resumeTarget['url'], ENT_QUOTES, 'UTF-8') ?>"
                       class="px-5 py-2.5 rounded-xl bg-sky-600 hover:bg-sky-700 text-white text-xs font-bold shadow-xs transition inline-flex items-center gap-2 w-full sm:w-auto justify-center">
                        <span><?= htmlspecialchars($resumeTarget['label'], ENT_QUOTES, 'UTF-8') ?></span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                        </svg>
                    </a>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- 4-Card KPI Summary Metrics Strip -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Lessons / Notes -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs">
            <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Course Materials</p>
            <div class="flex items-baseline gap-2 mt-1">
                <h3 class="text-2xl font-extrabold text-slate-900"><?= number_format(count($items)) ?></h3>
                <span class="text-xs font-semibold text-slate-500">lessons</span>
            </div>
            <span class="text-[11px] font-medium text-sky-600 mt-1 block">
                Published study topics
            </span>
        </div>

        <!-- Assignments -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs">
            <p class="text-xs font-bold uppercase tracking-wider text-amber-500">Assignments</p>
            <div class="flex items-baseline gap-2 mt-1">
                <h3 class="text-2xl font-extrabold text-slate-900"><?= number_format(count($assignments)) ?></h3>
                <span class="text-xs font-semibold text-slate-500">tasks</span>
            </div>
            <span class="text-[11px] font-medium text-amber-600/90 mt-1 block">
                Coursework items
            </span>
        </div>

        <!-- CBT Quizzes -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs">
            <p class="text-xs font-bold uppercase tracking-wider text-emerald-600">Online CBTs</p>
            <div class="flex items-baseline gap-2 mt-1">
                <h3 class="text-2xl font-extrabold text-emerald-600"><?= number_format(count($quizzes)) ?></h3>
                <span class="text-xs font-semibold text-slate-500">exams</span>
            </div>
            <span class="text-[11px] font-medium text-emerald-600 mt-1 block">
                Scheduled assessments
            </span>
        </div>

        <!-- Continuous Assessment Total -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs">
            <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Term Result</p>
            <div class="flex items-baseline gap-2 mt-1">
                <?php if ($termResult): ?>
                    <h3 class="text-2xl font-extrabold text-slate-900"><?= number_format((float)$termResult->computedScore, 1) ?>%</h3>
                    <span class="px-2 py-0.5 rounded text-xs font-bold bg-emerald-100 text-emerald-800 ml-1">
                        <?= htmlspecialchars($termResult->gradeLetter) ?>
                    </span>
                <?php else: ?>
                    <h3 class="text-lg font-extrabold text-slate-900">In Progress</h3>
                <?php endif; ?>
            </div>
            <span class="text-[11px] font-medium text-slate-500 mt-1 block">
                Official CA & Exam score
            </span>
        </div>
    </div>

    <!-- Workspace Tabs Container -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
        <!-- Tab Navigation Bar -->
        <div class="flex items-center gap-2 p-2 bg-slate-50 border-b border-slate-200 overflow-x-auto">
            <?php if ($hasModules): ?>
                <button type="button" onclick="switchTab('modules')" id="tab-btn-modules"
                        class="tab-btn px-4 py-2 rounded-xl text-xs font-bold transition bg-white text-slate-900 shadow-xs border border-slate-200">
                    1. Course Learning Path (<?= count($learningPath['modules']) ?>)
                </button>
                <button type="button" onclick="switchTab('materials')" id="tab-btn-materials"
                        class="tab-btn px-4 py-2 rounded-xl text-xs font-bold text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition">
                    2. Study Materials (<?= count($items) ?>)
                </button>
                <button type="button" onclick="switchTab('assignments')" id="tab-btn-assignments"
                        class="tab-btn px-4 py-2 rounded-xl text-xs font-bold text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition">
                    3. Assignments & Tasks (<?= count($assignments) ?>)
                </button>
                <button type="button" onclick="switchTab('quizzes')" id="tab-btn-quizzes"
                        class="tab-btn px-4 py-2 rounded-xl text-xs font-bold text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition">
                    4. CBT Exams & Quizzes (<?= count($quizzes) ?>)
                </button>
            <?php else: ?>
                <button type="button" onclick="switchTab('materials')" id="tab-btn-materials"
                        class="tab-btn px-4 py-2 rounded-xl text-xs font-bold transition bg-white text-slate-900 shadow-xs border border-slate-200">
                    1. Study Materials (<?= count($items) ?>)
                </button>
                <button type="button" onclick="switchTab('assignments')" id="tab-btn-assignments"
                        class="tab-btn px-4 py-2 rounded-xl text-xs font-bold text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition">
                    2. Assignments & Tasks (<?= count($assignments) ?>)
                </button>
                <button type="button" onclick="switchTab('quizzes')" id="tab-btn-quizzes"
                        class="tab-btn px-4 py-2 rounded-xl text-xs font-bold text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition">
                    3. CBT Exams & Quizzes (<?= count($quizzes) ?>)
                </button>
            <?php endif; ?>
        </div>

        <!-- Tab Content Panes -->
        <div class="p-6">
            <?php if ($hasModules): ?>
                <!-- Pane 0: Modular Learning Path -->
                <div id="tab-pane-modules" class="tab-pane space-y-6">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-100 pb-4">
                        <div>
                            <h2 class="text-base font-bold text-slate-900">Sequential Learning Modules</h2>
                            <p class="text-xs text-slate-500 mt-0.5">Follow the structured instructional path to master this subject step-by-step.</p>
                        </div>
                        <span class="text-xs font-semibold text-slate-400"><?= count($learningPath['modules']) ?> modules in course</span>
                    </div>

                    <div class="space-y-6">
                        <?php foreach ($learningPath['modules'] as $mIdx => $mod): ?>
                            <div class="rounded-2xl border border-slate-200 overflow-hidden bg-slate-50/40">
                                <!-- Module Header -->
                                <div class="p-4 sm:p-5 bg-white border-b border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                    <div class="flex items-start sm:items-center gap-3">
                                        <span class="flex-shrink-0 w-8 h-8 rounded-lg bg-sky-50 text-sky-700 border border-sky-200 font-bold text-xs flex items-center justify-center">
                                            <?= $mIdx + 1 ?>
                                        </span>
                                        <div>
                                            <div class="flex items-center gap-2 flex-wrap">
                                                <h3 class="text-sm sm:text-base font-bold text-slate-900">
                                                    <?= htmlspecialchars($mod->title) ?>
                                                </h3>
                                                <!-- Status Badge -->
                                                <?php if ($mod->computedStatus === 'Completed'): ?>
                                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-lg text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                                        Completed
                                                    </span>
                                                <?php elseif ($mod->computedStatus === 'In Progress'): ?>
                                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-lg text-[10px] font-bold bg-sky-100 text-sky-800 border border-sky-200">
                                                        <span>◐</span> In Progress
                                                    </span>
                                                <?php elseif ($mod->computedStatus === 'Locked'): ?>
                                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-lg text-[10px] font-bold bg-slate-200 text-slate-700">
                                                        <span>🔒</span> Locked
                                                    </span>
                                                <?php else: ?>
                                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-lg text-[10px] font-bold bg-slate-100 text-slate-600">
                                                        <span>○</span> Not Started
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                            <?php if (!empty($mod->description)): ?>
                                                <p class="text-xs text-slate-500 mt-1 leading-relaxed">
                                                    <?= htmlspecialchars($mod->description) ?>
                                                </p>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <!-- Module Progress Mini Bar -->
                                    <div class="flex items-center gap-3 sm:text-right flex-shrink-0">
                                        <div class="w-28 sm:w-36">
                                            <div class="flex items-center justify-between text-[11px] font-bold text-slate-700 mb-1">
                                                <span>Progress</span>
                                                <span><?= number_format((float)$mod->progressPercent, 1) ?>%</span>
                                            </div>
                                            <div class="w-full h-2 bg-slate-100 rounded-full overflow-hidden border border-slate-200">
                                                <div class="h-full bg-emerald-500 rounded-full transition-all duration-300" style="width: <?= min(100.0, max(0.0, (float)$mod->progressPercent)) ?>%;"></div>
                                            </div>
                                            <span class="text-[10px] text-slate-400 mt-0.5 block">
                                                <?= $mod->completedItemsCount ?> of <?= $mod->totalItemsCount ?> activities
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Module Activities List -->
                                <div class="p-4 sm:p-5">
                                    <?php if (empty($mod->items)): ?>
                                        <p class="text-xs text-slate-400 italic">No activities currently assigned to this module.</p>
                                    <?php else: ?>
                                        <div class="space-y-3">
                                            <?php foreach ($mod->items as $aIdx => $act): ?>
                                                <div class="p-3.5 rounded-xl border transition flex flex-col sm:flex-row sm:items-center justify-between gap-3 <?= $act->statusState === 'completed' ? 'bg-emerald-50/40 border-emerald-200' : ($act->statusState === 'locked' ? 'bg-slate-100/60 border-slate-200 opacity-75' : 'bg-white border-slate-200') ?>">
                                                    <div class="flex items-start sm:items-center gap-3">
                                                        <!-- Item Index -->
                                                        <span class="text-xs font-bold text-slate-400 w-4 text-right flex-shrink-0">
                                                            <?= $aIdx + 1 ?>.
                                                        </span>

                                                        <!-- Type Icon -->
                                                        <?php if ($act->activityType === 'document'): ?>
                                                            <span class="p-2 rounded-xl bg-sky-50 text-sky-700 border border-sky-200 flex-shrink-0">
                                                                <?php if ($act->itemSubtype === 'docx'): ?>
                                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                                    </svg>
                                                                <?php else: ?>
                                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                                                    </svg>
                                                                <?php endif; ?>
                                                            </span>
                                                        <?php elseif ($act->activityType === 'quiz'): ?>
                                                            <span class="p-2 rounded-xl bg-emerald-50 text-emerald-700 border border-emerald-200 flex-shrink-0">
                                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                                                                </svg>
                                                            </span>
                                                        <?php else: ?>
                                                            <span class="p-2 rounded-xl bg-amber-50 text-amber-700 border border-amber-200 flex-shrink-0">
                                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                                </svg>
                                                            </span>
                                                        <?php endif; ?>

                                                        <div>
                                                            <div class="flex items-center gap-2 flex-wrap">
                                                                <span class="text-xs font-bold text-slate-900">
                                                                    <?= htmlspecialchars($act->title) ?>
                                                                </span>
                                                                <span class="inline-flex items-center px-2 py-0.2 rounded text-[10px] font-bold uppercase tracking-wider bg-slate-100 text-slate-600">
                                                                    <?= htmlspecialchars($act->itemSubtype ?: $act->activityType) ?>
                                                                </span>
                                                                <?php if ($act->isRequired): ?>
                                                                    <span class="inline-flex items-center px-1.5 py-0.2 rounded text-[9px] font-bold uppercase tracking-wider bg-indigo-50 text-indigo-700 border border-indigo-200">
                                                                        Required
                                                                    </span>
                                                                <?php endif; ?>

                                                                <!-- Status Tag -->
                                                                <?php if ($act->statusState === 'completed'): ?>
                                                                    <span class="inline-flex items-center gap-1 text-[11px] font-bold text-emerald-700">
                                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                                                        Completed
                                                                    </span>
                                                                <?php elseif ($act->statusState === 'in_progress'): ?>
                                                                    <span class="inline-flex items-center gap-1 text-[11px] font-bold text-sky-700">
                                                                        <span>◐</span> <?= number_format((float)$act->progressPercent, 0) ?>% Read
                                                                    </span>
                                                                <?php elseif ($act->statusState === 'locked'): ?>
                                                                    <span class="inline-flex items-center gap-1 text-[11px] font-bold text-slate-500">
                                                                        <span>🔒</span> Locked
                                                                    </span>
                                                                <?php else: ?>
                                                                    <span class="inline-flex items-center gap-1 text-[11px] font-medium text-slate-400">
                                                                        <span>○</span> Not Started
                                                                    </span>
                                                                <?php endif; ?>
                                                            </div>

                                                            <!-- Unmet Prerequisites Alert -->
                                                            <?php if ($act->statusState === 'locked' && !empty($act->unmetPrerequisites)): ?>
                                                                <div class="mt-1 flex items-center gap-1.5 text-[11px] text-rose-600 font-medium">
                                                                    <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                                                    <span>Prerequisite required: <?= htmlspecialchars($act->unmetPrerequisites[0]['title'] ?? 'Complete preceding activity') ?></span>
                                                                </div>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>

                                                    <!-- Action / Resume Button -->
                                                    <div class="flex items-center gap-2 flex-shrink-0 self-end sm:self-auto">
                                                        <?php if ($act->statusState === 'locked'): ?>
                                                            <button type="button" disabled
                                                                    class="px-3.5 py-1.5 bg-slate-200 text-slate-400 text-xs font-bold rounded-xl cursor-not-allowed inline-flex items-center gap-1.5">
                                                                <span>🔒 Locked</span>
                                                            </button>
                                                        <?php elseif ($act->statusState === 'completed'): ?>
                                                            <a href="<?= htmlspecialchars($act->resumeUrl) ?>"
                                                               class="px-3.5 py-1.5 bg-white hover:bg-slate-50 text-slate-700 border border-slate-300 text-xs font-bold rounded-xl transition inline-flex items-center gap-1.5">
                                                                <span>Review Activity</span>
                                                                <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                                                            </a>
                                                        <?php elseif ($act->statusState === 'in_progress'): ?>
                                                            <a href="<?= htmlspecialchars($act->resumeUrl) ?>"
                                                               class="px-3.5 py-1.5 bg-sky-600 hover:bg-sky-700 text-white text-xs font-bold rounded-xl shadow-xs transition inline-flex items-center gap-1.5">
                                                                <span>Resume</span>
                                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                                                            </a>
                                                        <?php else: ?>
                                                            <a href="<?= htmlspecialchars($act->resumeUrl) ?>"
                                                               class="px-3.5 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl shadow-xs transition inline-flex items-center gap-1.5">
                                                                <span>Start Activity</span>
                                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                                                            </a>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Pane 1: Study Materials -->
            <div id="tab-pane-materials" class="tab-pane space-y-4 <?= $hasModules ? 'hidden' : '' ?>">
                <div class="flex items-center justify-between">
                    <h2 class="text-base font-bold text-slate-900">Course Notes & Learning Resources</h2>
                    <span class="text-xs font-semibold text-slate-400"><?= count($items) ?> items available</span>
                </div>

                <?php if (empty($items)): ?>
                    <div class="p-12 text-center text-slate-400 text-xs">
                        No study materials have been published by the instructor yet.
                    </div>
                <?php else: ?>
                    <div class="divide-y divide-slate-100">
                        <?php foreach ($items as $item): ?>
                            <div class="py-4 first:pt-0 last:pb-0 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                <div class="space-y-1">
                                    <?php if (!empty($item->topic)): ?>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-sky-50 text-sky-800 border border-sky-200">
                                            <?= htmlspecialchars($item->topic) ?>
                                        </span>
                                    <?php endif; ?>
                                    <h3 class="text-sm font-bold text-slate-900">
                                        <a href="/student/content/<?= (int)$item->id ?>" class="hover:text-sky-600 transition">
                                            <?= htmlspecialchars($item->title) ?>
                                        </a>
                                    </h3>
                                    <p class="text-[11px] text-slate-500">
                                        Published <?= date('M d, Y', strtotime($item->publishedAt ?? $item->createdAt)) ?>
                                    </p>
                                </div>

                                <div class="flex items-center gap-2 flex-shrink-0">
                                    <a href="/student/content/<?= (int)$item->id ?>" 
                                       class="px-3.5 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl transition">
                                        Read Note
                                    </a>
                                    <?php if ($item->file): ?>
                                        <?php if ($item->file->mimeType === 'application/pdf' || str_ends_with(strtolower($item->file->originalName), '.pdf')): ?>
                                            <a href="/student/content/<?= (int)$item->id ?>/read" 
                                               class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl shadow-xs transition flex items-center gap-1.5">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                                                </svg>
                                                <span>Read Online</span>
                                            </a>
                                        <?php endif; ?>
                                        <a href="/files/<?= (int)$item->file->id ?>/download" 
                                           class="px-3 py-1.5 bg-sky-600 hover:bg-sky-700 text-white text-xs font-bold rounded-xl shadow-xs transition flex items-center gap-1.5">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                            </svg>
                                            <span>Download</span>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Pane 2: Coursework Tasks -->
            <div id="tab-pane-assignments" class="tab-pane hidden space-y-4">
                <div class="flex items-center justify-between">
                    <h2 class="text-base font-bold text-slate-900">Subject Coursework & Assignments</h2>
                    <span class="text-xs font-semibold text-slate-400"><?= count($assignments) ?> total assignments</span>
                </div>

                <?php if (empty($assignments)): ?>
                    <div class="p-12 text-center text-slate-400 text-xs">
                        No coursework assignments posted for this term.
                    </div>
                <?php else: ?>
                    <div class="divide-y divide-slate-100">
                        <?php foreach ($assignments as $assignment): ?>
                            <div class="py-4 first:pt-0 last:pb-0 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                <div class="space-y-1">
                                    <div class="flex items-center gap-2">
                                        <?php if (!empty($assignment->topic)): ?>
                                            <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">
                                                <?= htmlspecialchars($assignment->topic) ?>
                                            </span>
                                        <?php endif; ?>
                                        <span class="text-[11px] font-bold text-amber-600">
                                            Due: <?= date('M d, Y · g:i A', strtotime($assignment->dueAt)) ?>
                                        </span>
                                    </div>
                                    <h3 class="text-sm font-bold text-slate-900">
                                        <?= htmlspecialchars($assignment->title) ?>
                                    </h3>
                                    <p class="text-[11px] text-slate-500">
                                        Weight: <strong><?= number_format((float)$assignment->maxScore, 1) ?> PTS</strong>
                                    </p>
                                </div>

                                <a href="/student/assignments/<?= (int)$assignment->id ?>" 
                                   class="px-4 py-2 bg-sky-600 hover:bg-sky-700 text-white text-xs font-bold rounded-xl shadow-xs transition inline-flex items-center gap-1.5 flex-shrink-0">
                                    <span>Submit Homework</span>
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                                    </svg>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Pane 3: CBT Quizzes -->
            <div id="tab-pane-quizzes" class="tab-pane hidden space-y-4">
                <div class="flex items-center justify-between">
                    <h2 class="text-base font-bold text-slate-900">Online Computer-Based Tests</h2>
                    <span class="text-xs font-semibold text-slate-400"><?= count($quizzes) ?> active</span>
                </div>

                <?php if (empty($quizzes)): ?>
                    <div class="p-12 text-center text-slate-400 text-xs">
                        No online CBT exams currently scheduled for this subject.
                    </div>
                <?php else: ?>
                    <div class="divide-y divide-slate-100">
                        <?php foreach ($quizzes as $quiz): ?>
                            <?php
                                $attempts = $studentAttempts[$quiz->id] ?? [];
                                $attemptsCount = count($attempts);
                                $maxAttempts = (int)$quiz->maxAttempts;
                                $remainingAttempts = max(0, $maxAttempts - $attemptsCount);
                                $latestAttempt = !empty($attempts) ? $attempts[0] : null;
                                $inProgressAttempt = null;
                                foreach ($attempts as $att) {
                                    if ($att->isInProgress()) {
                                        $inProgressAttempt = $att;
                                        break;
                                    }
                                }
                            ?>
                            <div class="p-5 bg-white rounded-xl border border-slate-200 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-4 hover:border-slate-300 transition">
                                <div class="space-y-1.5">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <?php if ($inProgressAttempt): ?>
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-800 border border-amber-200 animate-pulse">
                                                In Progress
                                            </span>
                                        <?php elseif ($remainingAttempts <= 0 && $latestAttempt): ?>
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-800 border border-emerald-200">
                                                Completed &bull; Best Score: <?= $latestAttempt->score !== null ? number_format((float)$latestAttempt->score, 1) : '0.0' ?> PTS
                                            </span>
                                        <?php elseif ($attemptsCount > 0): ?>
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-sky-50 text-sky-800 border border-sky-200">
                                                Attempted (<?= $attemptsCount ?>/<?= $maxAttempts ?>) &bull; <?= $remainingAttempts ?> Left
                                            </span>
                                        <?php else: ?>
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-700 border border-slate-200">
                                                <?= $maxAttempts ?> <?= $maxAttempts === 1 ? 'Attempt Allowed' : 'Attempts Allowed' ?>
                                            </span>
                                        <?php endif; ?>

                                        <span class="text-[11px] font-semibold text-slate-500">
                                            Duration: <?= $quiz->hasTimeLimit() ? "{$quiz->timeLimitMinutes} Mins" : 'Untimed' ?>
                                        </span>
                                    </div>

                                    <h3 class="text-sm font-extrabold text-slate-900 leading-snug">
                                        <?= htmlspecialchars($quiz->title) ?>
                                    </h3>

                                    <p class="text-[11px] text-slate-500 flex items-center gap-2">
                                        <span>Teacher: <strong class="text-slate-700"><?= htmlspecialchars($quiz->teacherName) ?></strong></span>
                                        <span>&bull;</span>
                                        <span>Total Points: <strong><?= number_format($quiz->getTotalMaxScore(), 0) ?> PTS</strong></span>
                                    </p>
                                </div>

                                <div class="flex items-center gap-2 flex-shrink-0">
                                    <?php if ($inProgressAttempt): ?>
                                        <a href="/student/quiz-attempts/<?= (int)$inProgressAttempt->id ?>/take" 
                                           class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white text-xs font-bold rounded-xl shadow-xs transition inline-flex items-center gap-1.5">
                                            <span>Resume CBT</span>
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                                        </a>
                                    <?php elseif ($remainingAttempts <= 0 && $latestAttempt): ?>
                                        <a href="/student/quiz-attempts/<?= (int)$latestAttempt->id ?>/result" 
                                           class="px-4 py-2 bg-sky-600 hover:bg-sky-700 text-white text-xs font-bold rounded-xl shadow-xs transition inline-flex items-center gap-1.5">
                                            <span>View CBT Result</span>
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                                        </a>
                                    <?php elseif ($attemptsCount > 0): ?>
                                        <a href="/student/quizzes/<?= (int)$quiz->id ?>" 
                                           class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl shadow-xs transition inline-flex items-center gap-1.5">
                                            <span>Retake CBT (<?= $remainingAttempts ?> left)</span>
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                                        </a>
                                    <?php else: ?>
                                        <a href="/student/quizzes/<?= (int)$quiz->id ?>" 
                                           class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl shadow-xs transition inline-flex items-center gap-1.5">
                                            <span>Take CBT Quiz</span>
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
function switchTab(tabId) {
    // Hide all panes
    document.querySelectorAll('.tab-pane').forEach(p => p.classList.add('hidden'));
    // Remove active styles from buttons
    document.querySelectorAll('.tab-btn').forEach(b => {
        b.classList.remove('bg-white', 'text-slate-900', 'shadow-xs', 'border', 'border-slate-200');
        b.classList.add('text-slate-600', 'hover:text-slate-900', 'hover:bg-slate-100');
    });

    // Show active pane
    const targetPane = document.getElementById('tab-pane-' + tabId);
    if (targetPane) targetPane.classList.remove('hidden');

    // Highlight active button
    const targetBtn = document.getElementById('tab-btn-' + tabId);
    if (targetBtn) {
        targetBtn.classList.remove('text-slate-600', 'hover:text-slate-900', 'hover:bg-slate-100');
        targetBtn.classList.add('bg-white', 'text-slate-900', 'shadow-xs', 'border', 'border-slate-200');
    }
}
</script>
