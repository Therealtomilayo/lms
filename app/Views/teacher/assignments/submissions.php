<div class="space-y-6">
    <!-- Header Card -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <!-- Breadcrumbs -->
                <nav class="flex items-center gap-2 text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">
                    <a href="/teacher/dashboard" class="text-slate-400 hover:text-emerald-600 transition">Faculty Portal</a>
                    <span class="text-slate-300">/</span>
                    <a href="/teacher/assignments" class="text-slate-400 hover:text-emerald-600 transition">Assignments & Homework</a>
                    <span class="text-slate-300">/</span>
                    <span class="text-slate-700">Submissions & Grading</span>
                </nav>
                <div class="flex flex-wrap items-center gap-3">
                    <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight leading-tight">
                        <?= htmlspecialchars($assignment->title) ?>
                    </h1>
                    <?php 
                        $subjectName = $assignment->classSubject?->subjectName ?? ($assignment->classSubject?->subject?->name ?? 'Subject');
                        $className = $assignment->classSubject?->className ?? ($assignment->classSubject?->schoolClass?->name ?? 'Class');
                        $sectionArm = $assignment->classSubject?->sectionArm ?? ($assignment->classSubject?->schoolClass?->sectionArm ?? '');
                    ?>
                    <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold bg-slate-100 text-slate-700 border border-slate-200">
                        <?= htmlspecialchars($subjectName) ?> — <?= htmlspecialchars($className) ?><?= !empty($sectionArm) ? ' (' . htmlspecialchars($sectionArm) . ')' : '' ?>
                    </span>
                </div>
                <p class="text-xs text-slate-500 mt-1.5 flex flex-wrap items-center gap-x-4 gap-y-1">
                    <span>Deadline: <strong class="text-slate-700 font-semibold"><?= date('M d, Y · g:i A', strtotime($assignment->dueAt)) ?></strong></span>
                    <span>Max Score: <strong class="text-emerald-700 font-semibold"><?= number_format((float)$assignment->maxScore, 0) ?> PTS</strong></span>
                    <?php if ($assignment->topic): ?>
                        <span>Topic: <strong class="text-slate-700 font-semibold"><?= htmlspecialchars($assignment->topic) ?></strong></span>
                    <?php endif; ?>
                </p>
            </div>

            <div class="flex items-center gap-2.5 flex-wrap">
                <?php $this->include('components/button', [
                    'label' => 'Edit Assignment',
                    'variant' => 'secondary',
                    'href' => "/teacher/assignments/{$assignment->id}/edit",
                    'icon' => '<svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>'
                ]); ?>

                <?php $this->include('components/button', [
                    'label' => 'Back to Assignments',
                    'variant' => 'secondary',
                    'href' => '/teacher/assignments',
                    'icon' => '<svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>'
                ]); ?>
            </div>
        </div>
    </div>

    <!-- Summary KPI Metrics Cards -->
    <?php
        $totalSubmissions = count($submissions);
        $gradedCount = 0;
        $lateCount = 0;
        foreach ($submissions as $s) {
            if ($s->isGraded()) {
                $gradedCount++;
            }
            if ($s->isLate()) {
                $lateCount++;
            }
        }
        $pendingGrading = $totalSubmissions - $gradedCount;
    ?>
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Total Submissions -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs">
            <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Total Submissions</p>
            <div class="flex items-baseline gap-2 mt-1">
                <h3 class="text-2xl font-extrabold text-slate-900"><?= number_format($totalSubmissions) ?></h3>
                <span class="text-xs font-semibold text-slate-500">turned in</span>
            </div>
            <span class="text-[11px] font-medium text-slate-500 mt-1 block">
                Classroom intake roster
            </span>
        </div>

        <!-- Pending Grading -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs">
            <p class="text-xs font-bold uppercase tracking-wider text-amber-500">Needs Grading</p>
            <div class="flex items-baseline gap-2 mt-1">
                <h3 class="text-2xl font-extrabold text-amber-600"><?= number_format($pendingGrading) ?></h3>
                <span class="text-xs font-semibold text-slate-500">pending</span>
            </div>
            <span class="text-[11px] font-medium text-amber-600/90 mt-1 block">
                <?= $pendingGrading === 0 ? 'All submissions evaluated' : 'Awaiting teacher feedback' ?>
            </span>
        </div>

        <!-- Graded Submissions -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs">
            <p class="text-xs font-bold uppercase tracking-wider text-emerald-600">Graded Submissions</p>
            <div class="flex items-baseline gap-2 mt-1">
                <h3 class="text-2xl font-extrabold text-emerald-600"><?= number_format($gradedCount) ?></h3>
                <span class="text-xs font-semibold text-slate-500">/ <?= number_format($totalSubmissions) ?></span>
            </div>
            <span class="text-[11px] font-medium text-slate-500 mt-1 block">
                Recorded in gradebook
            </span>
        </div>

        <!-- Late Submissions -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs">
            <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Late Turn-Ins</p>
            <div class="flex items-baseline gap-2 mt-1">
                <h3 class="text-2xl font-extrabold <?= $lateCount > 0 ? 'text-rose-600' : 'text-slate-900' ?>"><?= number_format($lateCount) ?></h3>
                <span class="text-xs font-semibold text-slate-500">after deadline</span>
            </div>
            <span class="text-[11px] font-medium text-slate-500 mt-1 block">
                <?= $lateCount > 0 ? 'Submitted past deadline' : 'All submissions on time' ?>
            </span>
        </div>
    </div>

    <!-- Filter Toolbar -->
    <?php if (!empty($submissions)): ?>
        <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div class="flex items-center gap-2">
                <button type="button" onclick="filterSubmissions('all')" id="tab-all"
                        class="px-3.5 py-1.5 rounded-xl text-xs font-bold bg-slate-900 text-white transition filter-tab">
                    All (<?= $totalSubmissions ?>)
                </button>
                <button type="button" onclick="filterSubmissions('pending')" id="tab-pending"
                        class="px-3.5 py-1.5 rounded-xl text-xs font-bold bg-slate-100 hover:bg-slate-200 text-slate-700 transition filter-tab">
                    Needs Grading (<?= $pendingGrading ?>)
                </button>
                <button type="button" onclick="filterSubmissions('graded')" id="tab-graded"
                        class="px-3.5 py-1.5 rounded-xl text-xs font-bold bg-slate-100 hover:bg-slate-200 text-slate-700 transition filter-tab">
                    Graded (<?= $gradedCount ?>)
                </button>
            </div>

            <div class="relative max-w-xs w-full">
                <input type="text" id="submission-search" oninput="searchSubmissions(this.value)" placeholder="Search student name or adm..."
                       class="w-full rounded-xl border border-slate-300 text-xs pl-8 pr-3 py-2 bg-slate-50 focus:bg-white focus:border-emerald-500 focus:ring-emerald-500 transition">
                <svg class="w-4 h-4 text-slate-400 absolute left-2.5 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>
        </div>
    <?php endif; ?>

    <!-- Submissions List -->
    <?php if (empty($submissions)): ?>
        <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-12 text-center">
            <div class="w-16 h-16 rounded-2xl bg-slate-100 text-slate-400 mx-auto flex items-center justify-center mb-4">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                </svg>
            </div>
            <h3 class="text-base font-bold text-slate-900">No Submissions Received Yet</h3>
            <p class="text-xs text-slate-500 mt-1.5 max-w-sm mx-auto">
                Enrolled students in this class have not turned in any coursework responses or uploaded files for this assignment.
            </p>
            <div class="mt-6">
                <?php $this->include('components/button', [
                    'label' => 'Back to Assignments',
                    'variant' => 'secondary',
                    'href' => '/teacher/assignments'
                ]); ?>
            </div>
        </div>
    <?php else: ?>
        <div class="space-y-4" id="submissions-container">
            <?php foreach ($submissions as $sub): ?>
                <?php 
                    $studentName = $sub->student?->name ?? 'Student';
                    $admissionNumber = $sub->student?->admissionNumber ?? '';
                    $isGraded = $sub->isGraded();
                    $isLate = $sub->isLate();
                ?>
                <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6 space-y-5 submission-card"
                     data-status="<?= $isGraded ? 'graded' : 'pending' ?>"
                     data-student="<?= strtolower(htmlspecialchars($studentName . ' ' . $admissionNumber)) ?>">
                    
                    <!-- Card Top Header -->
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-4 border-b border-slate-100">
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="w-10 h-10 rounded-xl bg-emerald-100 text-emerald-800 font-bold text-sm flex items-center justify-center flex-shrink-0">
                                <?= strtoupper(substr($studentName, 0, 1)) ?>
                            </div>
                            <div class="min-w-0 truncate">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <h3 class="text-sm font-bold text-slate-900 truncate"><?= htmlspecialchars($studentName) ?></h3>
                                    <?php if (!empty($admissionNumber)): ?>
                                        <span class="font-mono text-[11px] font-semibold text-slate-500 bg-slate-100 px-2 py-0.5 rounded-md">
                                            <?= htmlspecialchars($admissionNumber) ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <p class="text-[11px] text-slate-400 mt-0.5">
                                    Submitted on <?= date('M d, Y · g:i A', strtotime($sub->submittedAt)) ?>
                                </p>
                            </div>
                        </div>

                        <!-- Status Indicators -->
                        <div class="flex items-center gap-2 flex-wrap self-start sm:self-center">
                            <?php if ($isLate): ?>
                                <?php $this->include('components/badge', [
                                    'label' => 'Late Submission',
                                    'variant' => 'danger',
                                    'size' => 'sm'
                                ]); ?>
                            <?php endif; ?>

                            <?php if ($isGraded): ?>
                                <?php $this->include('components/badge', [
                                    'label' => 'Graded: ' . number_format((float)$sub->score, 1) . ' / ' . number_format((float)$assignment->maxScore, 0) . ' PTS',
                                    'variant' => 'success',
                                    'size' => 'sm'
                                ]); ?>
                            <?php else: ?>
                                <?php $this->include('components/badge', [
                                    'label' => 'Needs Grading',
                                    'variant' => 'warning',
                                    'size' => 'sm'
                                ]); ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Submission Response / Content Body -->
                    <div class="space-y-3">
                        <?php if ($sub->hasTextResponse()): ?>
                            <div class="p-4 bg-slate-50/80 rounded-xl border border-slate-200">
                                <span class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1.5">
                                    Student's Written Response
                                </span>
                                <p class="text-xs text-slate-800 whitespace-pre-wrap leading-relaxed"><?= htmlspecialchars((string)$sub->textResponse) ?></p>
                            </div>
                        <?php endif; ?>

                        <?php if ($sub->file): ?>
                            <div class="p-3.5 bg-slate-50 rounded-xl border border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                <div class="flex items-center gap-3 min-w-0">
                                    <div class="w-9 h-9 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center flex-shrink-0">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
                                        </svg>
                                    </div>
                                    <div class="min-w-0 truncate">
                                        <p class="text-xs font-bold text-slate-900 truncate"><?= htmlspecialchars($sub->file->originalName) ?></p>
                                        <p class="text-[10px] text-slate-400 font-mono"><?= htmlspecialchars($sub->file->getFormattedSize()) ?></p>
                                    </div>
                                </div>

                                <a href="/files/<?= (int)$sub->file->id ?>/download" 
                                   class="inline-flex items-center justify-center gap-1.5 px-3.5 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-lg shadow-xs transition flex-shrink-0">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                    </svg>
                                    <span>Download Attachment</span>
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Grading & Feedback Form -->
                    <div class="pt-4 border-t border-slate-100">
                        <form action="/teacher/submissions/<?= (int)$sub->id ?>/grade" method="POST" class="grid grid-cols-1 md:grid-cols-12 gap-3 items-end">
                            <?= csrf_field() ?>

                            <!-- Score Input -->
                            <div class="md:col-span-3">
                                <label for="score_<?= (int)$sub->id ?>" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                                    Score (Max <?= number_format((float)$assignment->maxScore, 0) ?> PTS) <span class="text-rose-500">*</span>
                                </label>
                                <div class="relative">
                                    <input type="number" step="0.5" min="0" max="<?= (float)$assignment->maxScore ?>"
                                           name="score" id="score_<?= (int)$sub->id ?>"
                                           value="<?= $sub->score !== null ? htmlspecialchars((string)$sub->score) : '' ?>" required
                                           class="w-full rounded-xl border border-slate-300 text-xs font-bold text-slate-900 bg-slate-50 focus:bg-white focus:border-emerald-500 focus:ring-emerald-500 py-2.5 pl-3 pr-10 transition">
                                    <span class="absolute right-3 top-1/2 -translate-y-1/2 text-[11px] font-bold text-slate-400 pointer-events-none">PTS</span>
                                </div>
                            </div>

                            <!-- Comment Input -->
                            <div class="md:col-span-6">
                                <label for="comment_<?= (int)$sub->id ?>" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                                    Teacher Feedback / Corrections
                                </label>
                                <input type="text" name="teacher_comment" id="comment_<?= (int)$sub->id ?>"
                                       value="<?= htmlspecialchars((string)($sub->teacherComment ?? '')) ?>"
                                       placeholder="Constructive feedback, remarks, or notes on errors..."
                                       class="w-full rounded-xl border border-slate-300 text-xs font-medium text-slate-900 bg-slate-50 focus:bg-white focus:border-emerald-500 focus:ring-emerald-500 py-2.5 px-3 transition">
                            </div>

                            <!-- Submit Button -->
                            <div class="md:col-span-3">
                                <button type="submit" 
                                        class="w-full inline-flex items-center justify-center gap-1.5 py-2.5 px-4 bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold rounded-xl shadow-xs transition">
                                    <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    <span><?= $isGraded ? 'Update Evaluation' : 'Save Grade' ?></span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script>
function filterSubmissions(status) {
    const cards = document.querySelectorAll('.submission-card');
    const tabs = document.querySelectorAll('.filter-tab');
    
    tabs.forEach(tab => {
        tab.classList.remove('bg-slate-900', 'text-white');
        tab.classList.add('bg-slate-100', 'text-slate-700');
    });

    const activeTab = document.getElementById('tab-' + status);
    if (activeTab) {
        activeTab.classList.remove('bg-slate-100', 'text-slate-700');
        activeTab.classList.add('bg-slate-900', 'text-white');
    }

    cards.forEach(card => {
        const cardStatus = card.getAttribute('data-status');
        if (status === 'all' || cardStatus === status) {
            card.classList.remove('hidden');
        } else {
            card.classList.add('hidden');
        }
    });
}

function searchSubmissions(query) {
    const q = query.toLowerCase().trim();
    const cards = document.querySelectorAll('.submission-card');

    cards.forEach(card => {
        const studentInfo = card.getAttribute('data-student') || '';
        if (q === '' || studentInfo.includes(q)) {
            card.classList.remove('hidden');
        } else {
            card.classList.add('hidden');
        }
    });
}
</script>

