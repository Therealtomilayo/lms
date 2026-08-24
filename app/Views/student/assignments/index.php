<div class="space-y-6">
    <!-- Header Card -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <!-- Breadcrumbs -->
                <nav class="flex items-center gap-2 text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">
                    <a href="/student/dashboard" class="hover:text-sky-600 transition">Student Portal</a>
                    <span class="text-slate-300">/</span>
                    <span class="text-slate-700">Coursework & Tasks</span>
                </nav>
                <div class="flex flex-wrap items-center gap-3">
                    <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight leading-tight">
                        Coursework & Homework
                    </h1>
                    <?php if ($student && $student->schoolClass): ?>
                        <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold bg-sky-50 text-sky-800 border border-sky-200">
                            <?= htmlspecialchars($student->schoolClass->name) ?>
                        </span>
                    <?php endif; ?>
                </div>
                <p class="text-xs text-slate-500 mt-1">
                    Track ongoing homework assignments, submit your responses, and review teacher evaluations.
                </p>
            </div>

            <div class="flex items-center gap-2.5 flex-wrap">
                <a href="/student/subjects" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-bold text-slate-700 bg-slate-100 hover:bg-slate-200 transition">
                    <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                    </svg>
                    <span>Enrolled Subjects</span>
                </a>
            </div>
        </div>
    </div>

    <!-- 4-Card KPI Summary Metrics Strip -->
    <?php
        $totalActive = count($activeAssignments);
        $totalPastDue = count($pastDueAssignments);
        $submittedCount = 0;
        $gradedCount = 0;
        foreach ($submissions as $sub) {
            if ($sub && $sub->isGraded()) {
                $gradedCount++;
            } elseif ($sub) {
                $submittedCount++;
            }
        }
    ?>
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Active Tasks -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs">
            <p class="text-xs font-bold uppercase tracking-wider text-amber-500">Active Tasks</p>
            <div class="flex items-baseline gap-2 mt-1">
                <h3 class="text-2xl font-extrabold text-slate-900"><?= number_format($totalActive) ?></h3>
                <span class="text-xs font-semibold text-slate-500">open</span>
            </div>
            <span class="text-[11px] font-medium text-amber-600 mt-1 block">
                Upcoming deadlines
            </span>
        </div>

        <!-- Submitted Awaiting Grade -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs">
            <p class="text-xs font-bold uppercase tracking-wider text-sky-600">Submitted</p>
            <div class="flex items-baseline gap-2 mt-1">
                <h3 class="text-2xl font-extrabold text-slate-900"><?= number_format($submittedCount) ?></h3>
                <span class="text-xs font-semibold text-slate-500">tasks</span>
            </div>
            <span class="text-[11px] font-medium text-sky-600 mt-1 block">
                Awaiting evaluation
            </span>
        </div>

        <!-- Graded -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs">
            <p class="text-xs font-bold uppercase tracking-wider text-emerald-600">Evaluated</p>
            <div class="flex items-baseline gap-2 mt-1">
                <h3 class="text-2xl font-extrabold text-emerald-600"><?= number_format($gradedCount) ?></h3>
                <span class="text-xs font-semibold text-slate-500">scored</span>
            </div>
            <span class="text-[11px] font-medium text-emerald-600 mt-1 block">
                Feedback available
            </span>
        </div>

        <!-- Past Due / Overdue -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs">
            <p class="text-xs font-bold uppercase tracking-wider text-rose-500">Past Deadlines</p>
            <div class="flex items-baseline gap-2 mt-1">
                <h3 class="text-2xl font-extrabold text-rose-600"><?= number_format($totalPastDue) ?></h3>
                <span class="text-xs font-semibold text-slate-500">past due</span>
            </div>
            <span class="text-[11px] font-medium text-slate-500 mt-1 block">
                Archived tasks
            </span>
        </div>
    </div>

    <!-- Search / Filter Bar -->
    <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-xs">
        <div class="relative">
            <input type="text" id="assignment-search" placeholder="Search coursework title, topic, or subject..." 
                   oninput="filterAssignmentCards(this.value)"
                   class="w-full text-xs font-semibold rounded-xl border border-slate-300 bg-slate-50 focus:bg-white focus:border-sky-500 focus:ring-sky-500 py-2.5 pl-10 pr-4 transition">
            <svg class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
        </div>
    </div>

    <!-- Active Assignments Section -->
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <h2 class="text-base font-bold text-slate-900 flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span>
                <span>Active Coursework Tasks</span>
                <span class="text-xs font-bold px-2 py-0.5 rounded-full bg-amber-50 text-amber-800 border border-amber-200">
                    <?= count($activeAssignments) ?>
                </span>
            </h2>
        </div>

        <?php if (empty($activeAssignments)): ?>
            <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-12 text-center">
                <div class="w-14 h-14 rounded-2xl bg-emerald-50 text-emerald-600 mx-auto flex items-center justify-center mb-3">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <h3 class="text-base font-bold text-slate-900">All Caught Up!</h3>
                <p class="text-xs text-slate-500 mt-1">You have no pending coursework assignments due at this time.</p>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" id="active-assignments-grid">
                <?php foreach ($activeAssignments as $assignment): ?>
                    <?php 
                        $sub = $submissions[$assignment->id] ?? null; 
                        $sName = $assignment->classSubject?->subject?->name ?? 'Subject';
                        $sCode = $assignment->classSubject?->subject?->code ?? '';
                        $tName = $assignment->teacher?->user?->name ?? 'Faculty Staff';
                    ?>
                    <div class="assignment-card bg-white rounded-2xl border border-slate-200 shadow-xs flex flex-col justify-between hover:border-slate-300 transition overflow-hidden p-6 space-y-4"
                         data-search="<?= strtolower(htmlspecialchars($assignment->title . ' ' . $sName . ' ' . $sCode . ' ' . ($assignment->topic ?? ''))) ?>">
                        <div>
                            <div class="flex items-start justify-between gap-2 mb-2">
                                <span class="px-2.5 py-1 rounded-lg text-[10px] font-bold uppercase tracking-wider bg-sky-50 text-sky-800 border border-sky-200">
                                    <?= htmlspecialchars($sName) ?>
                                </span>
                                <?php if ($sub && $sub->isGraded()): ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-lg text-xs font-bold bg-emerald-50 text-emerald-800 border border-emerald-200">
                                        Graded: <?= number_format((float)$sub->score, 1) ?>/<?= number_format((float)$assignment->maxScore, 0) ?>
                                    </span>
                                <?php elseif ($sub): ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-lg text-xs font-bold bg-sky-50 text-sky-800 border border-sky-200">
                                        Submitted
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-lg text-xs font-bold bg-amber-50 text-amber-800 border border-amber-200">
                                        Action Required
                                    </span>
                                <?php endif; ?>
                            </div>

                            <?php if (!empty($assignment->topic)): ?>
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-1">
                                    <?= htmlspecialchars($assignment->topic) ?>
                                </span>
                            <?php endif; ?>

                            <h3 class="text-base font-bold text-slate-900 leading-snug">
                                <a href="/student/assignments/<?= (int)$assignment->id ?>" class="hover:text-sky-600 transition">
                                    <?= htmlspecialchars($assignment->title) ?>
                                </a>
                            </h3>

                            <p class="text-xs text-slate-500 line-clamp-2 leading-relaxed mt-2">
                                <?= htmlspecialchars($assignment->instructions) ?>
                            </p>

                            <div class="pt-3 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500 mt-3">
                                <span>Due: <strong class="text-slate-800 font-bold"><?= date('M d · g:i A', strtotime($assignment->dueAt)) ?></strong></span>
                                <span class="font-mono font-bold text-slate-700 bg-slate-100 px-2 py-0.5 rounded">
                                    <?= number_format((float)$assignment->maxScore, 0) ?> pts
                                </span>
                            </div>
                        </div>

                        <div class="pt-3 border-t border-slate-100">
                            <a href="/student/assignments/<?= (int)$assignment->id ?>" 
                               class="w-full inline-flex items-center justify-center gap-1.5 py-2.5 px-4 bg-sky-600 hover:bg-sky-700 text-white text-xs font-bold rounded-xl shadow-xs transition">
                                <span><?= $sub ? 'View Submission & Grade' : 'Open & Submit Work' ?></span>
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

    <!-- Past Due Assignments Section -->
    <?php if (!empty($pastDueAssignments)): ?>
        <div class="space-y-4 pt-6 border-t border-slate-200">
            <div class="flex items-center justify-between">
                <h2 class="text-base font-bold text-slate-800 flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-slate-400"></span>
                    <span>Archived / Past Due Tasks</span>
                    <span class="text-xs font-bold px-2 py-0.5 rounded-full bg-slate-100 text-slate-600">
                        <?= count($pastDueAssignments) ?>
                    </span>
                </h2>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($pastDueAssignments as $assignment): ?>
                    <?php 
                        $sub = $submissions[$assignment->id] ?? null; 
                        $sName = $assignment->classSubject?->subject?->name ?? 'Subject';
                    ?>
                    <div class="assignment-card bg-white rounded-2xl border border-slate-200 shadow-xs flex flex-col justify-between hover:border-slate-300 transition overflow-hidden p-6 space-y-4"
                         data-search="<?= strtolower(htmlspecialchars($assignment->title . ' ' . $sName)) ?>">
                        <div>
                            <div class="flex items-start justify-between gap-2 mb-2">
                                <span class="px-2.5 py-1 rounded-lg text-[10px] font-bold uppercase tracking-wider bg-slate-100 text-slate-700">
                                    <?= htmlspecialchars($sName) ?>
                                </span>
                                <?php if ($sub && $sub->isGraded()): ?>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-lg text-xs font-bold bg-emerald-50 text-emerald-800 border border-emerald-200">
                                        Score: <?= number_format((float)$sub->score, 1) ?>/<?= number_format((float)$assignment->maxScore, 0) ?>
                                    </span>
                                <?php elseif ($sub): ?>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-lg text-xs font-bold bg-sky-50 text-sky-800 border border-sky-200">
                                        Submitted <?= $sub->isLate() ? '(Late)' : '' ?>
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-lg text-xs font-bold bg-rose-50 text-rose-800 border border-rose-200">
                                        Missed Deadline
                                    </span>
                                <?php endif; ?>
                            </div>

                            <h3 class="text-base font-bold text-slate-900 leading-snug">
                                <a href="/student/assignments/<?= (int)$assignment->id ?>" class="hover:text-sky-600 transition">
                                    <?= htmlspecialchars($assignment->title) ?>
                                </a>
                            </h3>

                            <div class="pt-3 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500 mt-3">
                                <span>Deadline was: <?= date('M d, Y', strtotime($assignment->dueAt)) ?></span>
                                <span class="font-mono font-bold text-slate-700"><?= number_format((float)$assignment->maxScore, 0) ?> pts</span>
                            </div>
                        </div>

                        <div class="pt-3 border-t border-slate-100">
                            <a href="/student/assignments/<?= (int)$assignment->id ?>" 
                               class="w-full inline-flex items-center justify-center gap-1.5 py-2 px-4 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl transition">
                                <span>View Assignment Details</span>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
function filterAssignmentCards(query) {
    const q = query.trim().toLowerCase();
    const cards = document.querySelectorAll('.assignment-card');
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
