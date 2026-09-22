<div class="space-y-6">
    <!-- Header Card -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <!-- Breadcrumbs -->
                <nav class="flex items-center gap-2 text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">
                    <a href="/student/dashboard" class="hover:text-sky-600 transition">Student Portal</a>
                    <span class="text-slate-300">/</span>
                    <span class="text-slate-700">Enrolled Subjects</span>
                </nav>
                <div class="flex flex-wrap items-center gap-3">
                    <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight leading-tight">
                        My Academic Courses
                    </h1>
                    <?php if ($student && $student->schoolClass): ?>
                        <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold bg-sky-50 text-sky-800 border border-sky-200">
                            <?= htmlspecialchars($student->schoolClass->name) ?><?= !empty($student->schoolClass->sectionArm) ? ' (' . htmlspecialchars($student->schoolClass->sectionArm) . ')' : '' ?>
                        </span>
                    <?php endif; ?>
                </div>
                <p class="text-xs text-slate-500 mt-1">
                    Access learning materials, syllabi, coursework tasks, and CBT exams for your enrolled subjects.
                </p>
            </div>

            <div class="flex items-center gap-2.5 flex-wrap">
                <a href="/student/content" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-bold text-slate-700 bg-slate-100 hover:bg-slate-200 transition">
                    <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                    </svg>
                    <span>All Learning Materials</span>
                </a>
            </div>
        </div>
    </div>

    <!-- 4-Card KPI Summary Metrics Strip -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Enrolled Subjects -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs">
            <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Enrolled Courses</p>
            <div class="flex items-baseline gap-2 mt-1">
                <h3 class="text-2xl font-extrabold text-slate-900"><?= number_format(count($subjectEnrollments)) ?></h3>
                <span class="text-xs font-semibold text-slate-500">subjects</span>
            </div>
            <span class="text-[11px] font-medium text-sky-600 mt-1 block">
                Active term courses
            </span>
        </div>

        <!-- Class Cohort -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs">
            <p class="text-xs font-bold uppercase tracking-wider text-sky-600">Class Cohort</p>
            <div class="flex items-baseline gap-2 mt-1">
                <h3 class="text-lg font-extrabold text-slate-900 truncate"><?= htmlspecialchars($student->schoolClass?->name ?? 'Enrolled Class') ?></h3>
            </div>
            <span class="text-[11px] font-medium text-sky-600 mt-1 block">
                <?= !empty($student->schoolClass?->sectionArm) ? 'Arm ' . htmlspecialchars($student->schoolClass->sectionArm) : 'Standard Cohort' ?>
            </span>
        </div>

        <!-- Academic Session -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs">
            <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Academic Session</p>
            <div class="flex items-baseline gap-2 mt-1">
                <h3 class="text-lg font-extrabold text-slate-900 truncate"><?= htmlspecialchars($activeSession?->name ?? '2026/2027') ?></h3>
            </div>
            <span class="text-[11px] font-medium text-slate-500 mt-1 block">
                Official calendar year
            </span>
        </div>

        <!-- Current Term -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs">
            <p class="text-xs font-bold uppercase tracking-wider text-emerald-600">Current Term</p>
            <div class="flex items-baseline gap-2 mt-1">
                <h3 class="text-lg font-extrabold text-emerald-600 truncate"><?= htmlspecialchars($activeTerm?->name ?? 'Active Term') ?></h3>
            </div>
            <span class="text-[11px] font-medium text-emerald-600 mt-1 block">
                Instructional period
            </span>
        </div>
    </div>

    <!-- Search/Filter Bar -->
    <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-xs">
        <div class="relative">
            <input type="text" id="subject-search" placeholder="Search enrolled subject name or code..." 
                   oninput="filterSubjects(this.value)"
                   class="w-full text-xs font-semibold rounded-xl border border-slate-300 bg-slate-50 focus:bg-white focus:border-sky-500 focus:ring-sky-500 py-2.5 pl-10 pr-4 transition">
            <svg class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
        </div>
    </div>

    <!-- Subjects Grid -->
    <?php if (empty($subjectEnrollments)): ?>
        <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-12 text-center">
            <div class="w-16 h-16 rounded-2xl bg-sky-50 text-sky-600 mx-auto flex items-center justify-center mb-4">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                </svg>
            </div>
            <h3 class="text-base font-bold text-slate-900">No Enrolled Subjects</h3>
            <p class="text-xs text-slate-500 mt-1.5 max-w-sm mx-auto">
                You are not currently registered for any academic subjects in this term. Please contact the school administration or class teacher.
            </p>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" id="subjects-grid">
            <?php foreach ($subjectEnrollments as $se): ?>
                <?php 
                    $cs = $se->classSubject;
                    $sName = $cs?->subject?->name ?? 'Subject';
                    $sCode = $cs?->subject?->code ?? '';
                    $cName = $cs?->schoolClass?->name ?? 'Class';
                    $tName = $cs?->teacher?->user?->name ?? ($cs?->teacher?->name ?? 'Subject Teacher');
                ?>
                <div class="subject-card bg-white rounded-2xl border border-slate-200 shadow-xs flex flex-col justify-between hover:border-slate-300 transition overflow-hidden p-6 space-y-4"
                     data-search="<?= strtolower(htmlspecialchars($sName . ' ' . $sCode . ' ' . $cName . ' ' . $tName)) ?>">
                    <div>
                        <div class="flex items-center justify-between gap-2 mb-3">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-[11px] font-bold bg-sky-50 text-sky-800 border border-sky-200">
                                <?= htmlspecialchars($sCode ?: 'SUBJ') ?>
                            </span>
                            <span class="text-[11px] font-semibold text-slate-400">
                                <?= htmlspecialchars($cName) ?>
                            </span>
                        </div>

                        <h3 class="text-base font-bold text-slate-900 leading-snug">
                            <a href="/student/subjects/<?= (int)($cs?->id ?? 0) ?>" class="hover:text-sky-600 transition">
                                <?= htmlspecialchars($sName) ?>
                            </a>
                        </h3>

                        <div class="flex items-center gap-2 mt-3 pt-3 border-t border-slate-100">
                            <div class="w-6 h-6 rounded-full bg-slate-100 text-slate-700 font-bold text-[10px] flex items-center justify-center flex-shrink-0">
                                <?= strtoupper(substr($tName, 0, 1)) ?>
                            </div>
                            <span class="text-xs text-slate-600 truncate font-medium">
                                <?= htmlspecialchars($tName) ?>
                            </span>
                        </div>

                        <?php 
                            $pInfo = $subjectProgressMap[(int)($cs?->id ?? 0)] ?? null;
                        ?>
                        <?php if ($pInfo && !empty($pInfo['has_modules'])): ?>
                            <div class="mt-3 pt-3 border-t border-slate-100 space-y-1">
                                <div class="flex items-center justify-between text-[11px] font-bold">
                                    <span class="text-slate-500">Course Progress</span>
                                    <span class="<?= !empty($pInfo['is_completed']) ? 'text-emerald-600' : 'text-slate-700' ?>">
                                        <?= number_format((float)$pInfo['progress_percent'], 1) ?>%
                                    </span>
                                </div>
                                <div class="w-full h-1.5 bg-slate-100 rounded-full overflow-hidden border border-slate-200">
                                    <div class="h-full <?= !empty($pInfo['is_completed']) ? 'bg-emerald-500' : 'bg-sky-500' ?> rounded-full" style="width: <?= min(100.0, max(0.0, (float)$pInfo['progress_percent'])) ?>%;"></div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="pt-3 border-t border-slate-100 flex items-center justify-between gap-2">
                        <a href="/student/content?class_subject_id=<?= (int)($cs?->id ?? 0) ?>" 
                           class="text-xs font-bold text-slate-500 hover:text-slate-800 transition">
                            Notes & Files
                        </a>

                        <a href="/student/subjects/<?= (int)($cs?->id ?? 0) ?>" 
                           class="inline-flex items-center gap-1.5 px-3.5 py-1.5 bg-sky-600 hover:bg-sky-700 text-white text-xs font-bold rounded-xl shadow-xs transition">
                            <span>Open Hub</span>
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                            </svg>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script>
function filterSubjects(query) {
    const q = query.trim().toLowerCase();
    const cards = document.querySelectorAll('.subject-card');
    cards.forEach(card => {
        const text = card.getAttribute('data-search') || '';
        if (!q || text.includes(q)) {
            card.style.display = 'flex';
        } else {
            card.style.display = 'none';
        }
    });
}
</script>
