<div class="space-y-6">
    <!-- Header Card -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <!-- Breadcrumbs -->
                <nav class="flex items-center gap-2 text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">
                    <a href="/teacher/dashboard" class="text-slate-400 hover:text-emerald-600 transition">Faculty Portal</a>
                    <span class="text-slate-300">/</span>
                    <span class="text-slate-700">Quizzes & CBT Exams</span>
                </nav>
                <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight leading-tight">
                    CBT Quizzes & Assessments
                </h1>
                <p class="text-xs text-slate-500 mt-1">
                    Configure timed computer-based tests, randomize question orders, attach Question Bank items, and inspect student score attempts.
                </p>
            </div>

            <div class="flex items-center gap-2.5 flex-wrap">
                <?php $this->include('components/button', [
                    'label' => 'Question Bank',
                    'variant' => 'secondary',
                    'href' => '/teacher/question-bank',
                    'icon' => '<svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>'
                ]); ?>

                <?php $this->include('components/button', [
                    'label' => 'Create Quiz',
                    'variant' => 'primary',
                    'href' => '/teacher/quizzes/create',
                    'icon' => '<svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>'
                ]); ?>
            </div>
        </div>
    </div>

    <!-- 4-Card KPI Summary Metrics Strip -->
    <?php
        $totalQuizzes = count($quizzes);
        $publishedCount = 0;
        $draftCount = 0;
        foreach ($quizzes as $qz) {
            if ($qz->isPublished()) {
                $publishedCount++;
            } else {
                $draftCount++;
            }
        }
    ?>
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Total Quizzes -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs">
            <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Total CBT Exams</p>
            <div class="flex items-baseline gap-2 mt-1">
                <h3 class="text-2xl font-extrabold text-slate-900"><?= number_format($totalQuizzes) ?></h3>
                <span class="text-xs font-semibold text-slate-500">quizzes</span>
            </div>
            <span class="text-[11px] font-medium text-slate-500 mt-1 block">
                Authored assessment exams
            </span>
        </div>

        <!-- Published Quizzes -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs">
            <p class="text-xs font-bold uppercase tracking-wider text-emerald-600">Published (Live)</p>
            <div class="flex items-baseline gap-2 mt-1">
                <h3 class="text-2xl font-extrabold text-emerald-600"><?= number_format($publishedCount) ?></h3>
                <span class="text-xs font-semibold text-slate-500">active</span>
            </div>
            <span class="text-[11px] font-medium text-emerald-600 mt-1 block">
                Open for student testing
            </span>
        </div>

        <!-- Draft Quizzes -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs">
            <p class="text-xs font-bold uppercase tracking-wider text-amber-500">Draft (Staging)</p>
            <div class="flex items-baseline gap-2 mt-1">
                <h3 class="text-2xl font-extrabold text-amber-600"><?= number_format($draftCount) ?></h3>
                <span class="text-xs font-semibold text-slate-500">hidden</span>
            </div>
            <span class="text-[11px] font-medium text-amber-600/90 mt-1 block">
                Under configuration
            </span>
        </div>

        <!-- Class Allocations -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs">
            <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Class Subjects</p>
            <div class="flex items-baseline gap-2 mt-1">
                <h3 class="text-2xl font-extrabold text-slate-900"><?= number_format(count($classSubjects)) ?></h3>
                <span class="text-xs font-semibold text-slate-500">allocated</span>
            </div>
            <span class="text-[11px] font-medium text-slate-500 mt-1 block">
                Faculty teaching classes
            </span>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs">
        <form method="GET" action="/teacher/quizzes" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 items-end">
            <!-- Class Subject Filter -->
            <div>
                <label for="class_subject_id" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                    Filter by Subject & Class
                </label>
                <select name="class_subject_id" id="class_subject_id" class="w-full text-xs font-semibold rounded-xl border border-slate-300 bg-slate-50 focus:bg-white focus:border-emerald-500 focus:ring-emerald-500 py-2.5 px-3 transition" onchange="this.form.submit()">
                    <option value="">All Assigned Class-Subjects</option>
                    <?php foreach ($classSubjects as $cs): ?>
                        <?php 
                            $sName = $cs->subject?->name ?? 'Subject';
                            $cName = $cs->schoolClass?->name ?? 'Class';
                            $arm = $cs->schoolClass?->sectionArm ?? '';
                        ?>
                        <option value="<?= (int)$cs->id ?>" <?= $selectedClassSubjectId === (int)$cs->id ? 'selected' : '' ?>>
                            <?= htmlspecialchars($sName) ?> — <?= htmlspecialchars($cName) ?><?= !empty($arm) ? ' (' . htmlspecialchars($arm) . ')' : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Term Filter -->
            <div>
                <label for="term_id" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                    Filter by Academic Term
                </label>
                <select name="term_id" id="term_id" class="w-full text-xs font-semibold rounded-xl border border-slate-300 bg-slate-50 focus:bg-white focus:border-emerald-500 focus:ring-emerald-500 py-2.5 px-3 transition" onchange="this.form.submit()">
                    <option value="">All Academic Terms</option>
                    <?php foreach ($terms as $t): ?>
                        <option value="<?= (int)$t->id ?>" <?= $selectedTermId === (int)$t->id ? 'selected' : '' ?>>
                            <?= htmlspecialchars($t->name) ?> <?= $t->isCurrent ? '(Current)' : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="flex items-center gap-2">
                <button type="submit" class="px-5 py-2.5 bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold rounded-xl shadow-xs transition">
                    Apply Filter
                </button>
                <?php if ($selectedClassSubjectId || $selectedTermId): ?>
                    <a href="/teacher/quizzes" class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl transition">
                        Reset
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Quiz Cards Grid -->
    <?php if (empty($quizzes)): ?>
        <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-12 text-center">
            <div class="w-16 h-16 rounded-2xl bg-emerald-50 text-emerald-600 mx-auto flex items-center justify-center mb-4">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                </svg>
            </div>
            <h3 class="text-base font-bold text-slate-900">No CBT Quizzes Found</h3>
            <p class="text-xs text-slate-500 mt-1.5 max-w-sm mx-auto">
                No CBT assessments have been created for this selection. Start by composing a quiz and attaching Question Bank items.
            </p>
            <div class="mt-6">
                <?php $this->include('components/button', [
                    'label' => 'Create Quiz',
                    'variant' => 'primary',
                    'href' => '/teacher/quizzes/create',
                    'icon' => '<svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>'
                ]); ?>
            </div>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($quizzes as $quiz): ?>
                <?php 
                    $isPub = $quiz->isPublished();
                    $sName = $quiz->classSubject?->subject?->name ?? 'Subject';
                    $cName = $quiz->classSubject?->schoolClass?->name ?? 'Class';
                ?>
                <div class="bg-white rounded-2xl border border-slate-200 shadow-xs flex flex-col hover:border-slate-300 transition overflow-hidden">
                    <!-- Top Allocation Ribbon -->
                    <div class="p-5 pb-3.5 border-b border-slate-100 flex items-start justify-between gap-2">
                        <div>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-bold bg-slate-100 text-slate-700">
                                <?= htmlspecialchars($sName) ?> — <?= htmlspecialchars($cName) ?>
                            </span>
                            <span class="block text-[11px] font-semibold text-slate-400 mt-1">
                                <?= htmlspecialchars($quiz->term?->name ?? 'Academic Term') ?>
                            </span>
                        </div>

                        <?php if ($isPub): ?>
                            <?php $this->include('components/badge', [
                                'label' => 'Published',
                                'variant' => 'success',
                                'size' => 'sm'
                            ]); ?>
                        <?php else: ?>
                            <?php $this->include('components/badge', [
                                'label' => 'Draft',
                                'variant' => 'warning',
                                'size' => 'sm'
                            ]); ?>
                        <?php endif; ?>
                    </div>

                    <!-- Body Content -->
                    <div class="p-5 flex-1 flex flex-col justify-between space-y-4">
                        <div>
                            <h3 class="text-base font-bold text-slate-900 leading-snug">
                                <?= htmlspecialchars($quiz->title) ?>
                            </h3>
                            <?php if (!empty($quiz->instructions)): ?>
                                <p class="text-xs text-slate-500 line-clamp-2 mt-1.5 leading-relaxed">
                                    <?= htmlspecialchars($quiz->instructions) ?>
                                </p>
                            <?php endif; ?>
                        </div>

                        <!-- Timing & Rules Info -->
                        <div class="p-3 bg-slate-50/80 rounded-xl border border-slate-100 space-y-1.5 text-xs text-slate-600">
                            <div class="flex items-center justify-between">
                                <span class="flex items-center gap-1.5 font-medium">
                                    <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    <span>Duration:</span>
                                </span>
                                <strong class="font-bold text-slate-900"><?= $quiz->hasTimeLimit() ? "{$quiz->timeLimitMinutes} Mins" : 'Untimed' ?></strong>
                            </div>

                            <div class="flex items-center justify-between">
                                <span class="font-medium">Attempt Limit:</span>
                                <strong class="font-bold text-slate-900"><?= (int)$quiz->maxAttempts ?> Attempt<?= $quiz->maxAttempts > 1 ? 's' : '' ?></strong>
                            </div>
                        </div>
                    </div>

                    <!-- Action Footer -->
                    <div class="p-4 bg-slate-50/60 border-t border-slate-100 flex items-center justify-between gap-2 flex-wrap">
                        <div class="flex items-center gap-2">
                            <a href="/teacher/quizzes/<?= (int)$quiz->id ?>/questions" 
                               class="inline-flex items-center gap-1 px-3 py-1.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200 text-xs font-bold rounded-lg transition" 
                               title="Compose and select questions">
                                <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                                </svg>
                                <span>Questions</span>
                            </a>

                            <a href="/teacher/quizzes/<?= (int)$quiz->id ?>/attempts" 
                               class="inline-flex items-center gap-1 px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-lg transition" 
                               title="View student results">
                                <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                                </svg>
                                <span>Attempts</span>
                            </a>
                        </div>

                        <div class="flex items-center gap-1.5">
                            <form method="POST" action="/teacher/quizzes/<?= (int)$quiz->id ?>/publish">
                                <?= csrf_field() ?>
                                <input type="hidden" name="is_published" value="<?= $isPub ? '0' : '1' ?>">
                                <button type="submit" class="px-2.5 py-1.5 text-xs font-bold rounded-lg transition <?= $isPub ? 'bg-amber-50 hover:bg-amber-100 text-amber-700 border border-amber-200' : 'bg-emerald-600 hover:bg-emerald-700 text-white shadow-xs' ?>">
                                    <?= $isPub ? 'Unpublish' : 'Publish' ?>
                                </button>
                            </form>

                            <a href="/teacher/quizzes/<?= (int)$quiz->id ?>/edit" class="p-1.5 text-slate-400 hover:text-slate-700 rounded-lg hover:bg-slate-200 transition" title="Edit Quiz Config">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
