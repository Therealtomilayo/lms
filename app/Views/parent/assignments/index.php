<?php
/**
 * Child Coursework & Grading Overview (PARENT-03) — Phase UI-0 Modernized
 * Read-only coursework tracking, submitted answers, and teacher feedback for guardians.
 *
 * @var \App\Models\Student $student
 * @var \App\Models\Student|null $selectedChild
 * @var \App\Models\Student[] $children
 * @var \App\Models\Assignment[] $assignments
 * @var array<int, \App\Models\AssignmentSubmission> $submissions
 * @var \App\Models\AcademicSession|null $activeSession
 * @var \App\Core\UserContext $user
 */

$assignedList = $assignments ?? [];
$submissionMap = $submissions ?? [];

// Calculate KPI summary metrics
$totalCount = count($assignedList);
$gradedCount = 0;
$submittedCount = 0;
$pendingCount = 0;
$overdueCount = 0;

foreach ($assignedList as $asgn) {
    $sub = $submissionMap[$asgn->id] ?? null;
    if ($sub && $sub->isGraded()) {
        $gradedCount++;
    } elseif ($sub) {
        $submittedCount++;
    } elseif ($asgn->isPastDue()) {
        $overdueCount++;
    } else {
        $pendingCount++;
    }
}

$childClass = $student->currentClass?->name ?? ($student->className ?: 'Class Assigned');
$arm = !empty($student->currentClass?->sectionArm) ? ' (' . $student->currentClass->sectionArm . ')' : '';
$fullClassName = $childClass . $arm;
?>

<div class="space-y-6">
    <!-- Header Card -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6">
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 rounded-2xl bg-brand-700 text-white font-extrabold text-2xl flex items-center justify-center shadow-xs flex-shrink-0">
                    <?= strtoupper(substr($student->name, 0, 1)) ?>
                </div>
                <div>
                    <!-- Breadcrumbs -->
                    <div class="flex items-center gap-2 text-xs font-semibold text-slate-400 mb-1">
                        <a href="/parent/dashboard" class="hover:text-brand-600 transition">Dashboard</a>
                        <span>&rsaquo;</span>
                        <a href="/parent/children/<?= (int)$student->id ?>" class="hover:text-brand-600 transition">Child Profile</a>
                        <span>&rsaquo;</span>
                        <span class="text-slate-700 font-bold">Coursework & Tasks</span>
                    </div>

                    <div class="flex flex-wrap items-center gap-2.5">
                        <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight leading-tight">
                            <?= htmlspecialchars($student->name) ?>'s Coursework
                        </h1>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-lg text-xs font-bold bg-amber-50 text-amber-800 border border-amber-200">
                            <?= htmlspecialchars($fullClassName) ?>
                        </span>
                    </div>

                    <div class="flex flex-wrap items-center gap-3 text-xs text-slate-500 mt-1.5">
                        <span class="font-mono font-bold text-slate-700 bg-slate-100 px-2 py-0.5 rounded-md text-[11px]">
                            Adm: <?= htmlspecialchars($student->admissionNumber) ?>
                        </span>
                        <span>&bull;</span>
                        <span>Session: <strong class="text-slate-700"><?= htmlspecialchars($activeSession?->name ?? '2026/2027') ?></strong></span>
                        <span>&bull;</span>
                        <span class="text-slate-600 font-medium"><?= $totalCount ?> <?= $totalCount === 1 ? 'Task Assigned' : 'Tasks Assigned' ?></span>
                    </div>
                </div>
            </div>

            <!-- Quick Action Toolbar -->
            <div class="flex flex-wrap items-center gap-2">
                <a href="/parent/children/<?= (int)$student->id ?>" 
                   class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-bold bg-slate-100 hover:bg-slate-200 text-slate-700 transition">
                    <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    <span>Child Profile</span>
                </a>
                <a href="/parent/children/<?= (int)$student->id ?>/grades" 
                   class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-bold bg-slate-100 hover:bg-slate-200 text-slate-700 transition">
                    <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <span>Report Cards</span>
                </a>
                <a href="/parent/children/<?= (int)$student->id ?>/attendance" 
                   class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-bold bg-slate-100 hover:bg-slate-200 text-slate-700 transition">
                    <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    <span>Attendance</span>
                </a>
                <a href="/parent/dashboard" 
                   class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-bold bg-brand-600 hover:bg-brand-700 text-white shadow-xs transition">
                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                    </svg>
                    <span>Dashboard</span>
                </a>
            </div>
        </div>
    </div>

    <!-- 4-Card Overview Stats Strip -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Total Assigned -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Total Tasks</span>
                <span class="p-2 rounded-xl bg-brand-50 text-brand-700">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                    </svg>
                </span>
            </div>
            <div class="mt-3">
                <div class="flex items-baseline gap-1.5">
                    <h3 class="text-2xl font-extrabold text-slate-900"><?= number_format($totalCount) ?></h3>
                    <span class="text-xs font-semibold text-slate-500">coursework</span>
                </div>
                <p class="text-xs text-slate-500 mt-1.5">
                    Assigned Curriculum
                </p>
            </div>
        </div>

        <!-- Evaluated & Graded -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Evaluated</span>
                <span class="p-2 rounded-xl bg-emerald-50 text-emerald-700">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </span>
            </div>
            <div class="mt-3">
                <div class="flex items-baseline gap-1.5">
                    <h3 class="text-2xl font-extrabold text-emerald-700"><?= number_format($gradedCount) ?></h3>
                    <span class="text-xs font-semibold text-slate-500">graded</span>
                </div>
                <p class="text-xs text-emerald-600 font-medium mt-1.5">
                    Teacher Feedback Ready
                </p>
            </div>
        </div>

        <!-- Submitted / Turned In -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Turned In</span>
                <span class="p-2 rounded-xl bg-sky-50 text-sky-700">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </span>
            </div>
            <div class="mt-3">
                <div class="flex items-baseline gap-1.5">
                    <h3 class="text-2xl font-extrabold text-slate-900"><?= number_format($submittedCount) ?></h3>
                    <span class="text-xs font-semibold text-slate-500">pending score</span>
                </div>
                <p class="text-xs text-sky-600 font-medium mt-1.5">
                    Awaiting Evaluation
                </p>
            </div>
        </div>

        <!-- Pending / Due -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500">To Do / Due</span>
                <span class="p-2 rounded-xl <?= $overdueCount > 0 ? 'bg-rose-50 text-rose-700' : 'bg-amber-50 text-amber-700' ?>">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </span>
            </div>
            <div class="mt-3">
                <div class="flex items-baseline gap-1.5">
                    <h3 class="text-2xl font-extrabold <?= $overdueCount > 0 ? 'text-rose-700' : 'text-slate-900' ?>">
                        <?= number_format($pendingCount + $overdueCount) ?>
                    </h3>
                    <span class="text-xs font-semibold text-slate-500">
                        (<?= $overdueCount ?> overdue)
                    </span>
                </div>
                <p class="text-xs <?= $overdueCount > 0 ? 'text-rose-600' : 'text-amber-600' ?> font-medium mt-1.5">
                    <?= $overdueCount > 0 ? 'Urgent: Missing Tasks' : 'Upcoming Deadlines' ?>
                </p>
            </div>
        </div>
    </div>

    <!-- Coursework Listing -->
    <?php if (empty($assignedList)): ?>
        <!-- Empty State Card -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-12 text-center">
            <div class="w-16 h-16 rounded-2xl bg-amber-50 text-amber-600 mx-auto flex items-center justify-center mb-4">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
            </div>
            <h3 class="text-base font-bold text-slate-900">No Coursework Assigned</h3>
            <p class="text-xs text-slate-500 mt-1.5 max-w-sm mx-auto">
                There are currently no published assignments or homework tasks for <?= htmlspecialchars($student->name) ?>'s enrolled subjects.
            </p>
        </div>
    <?php else: ?>
        <div class="space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <h2 class="text-base font-bold text-slate-900 flex items-center gap-2">
                    <span>Assigned Tasks & Projects</span>
                    <span class="text-xs font-bold px-2 py-0.5 rounded-full bg-slate-100 text-slate-600">
                        <?= $totalCount ?>
                    </span>
                </h2>
                <div class="text-xs text-slate-500">
                    Showing all homework set across enrolled subjects
                </div>
            </div>

            <div class="space-y-4">
                <?php foreach ($assignedList as $assignment): 
                    $sub = $submissionMap[$assignment->id] ?? null;
                    $subjectCode = $assignment->classSubject?->subjectCode ?? 'SUB';
                    $subjectTitle = $assignment->classSubject?->subjectName ?? 'Subject';
                    $teacherName = $assignment->teacherName ?? ($assignment->teacher?->userName ?? 'Subject Teacher');
                    $isPastDue = $assignment->isPastDue();
                ?>
                    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs hover:border-slate-300 transition p-6 space-y-4">
                        <!-- Top Header Row -->
                        <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4 pb-4 border-b border-slate-100">
                            <div class="space-y-1.5 min-w-0">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="font-mono text-xs font-bold text-sky-800 bg-sky-50 border border-sky-200 px-2 py-0.5 rounded-md">
                                        <?= htmlspecialchars($subjectCode) ?>
                                    </span>
                                    <span class="text-xs font-semibold text-slate-600">
                                        <?= htmlspecialchars($subjectTitle) ?>
                                    </span>
                                    <span>&bull;</span>
                                    <span class="text-xs text-slate-500">
                                        Teacher: <strong class="text-slate-700"><?= htmlspecialchars($teacherName) ?></strong>
                                    </span>
                                </div>

                                <h3 class="text-base font-bold text-slate-900 leading-snug">
                                    <?= htmlspecialchars($assignment->title) ?>
                                </h3>

                                <div class="flex flex-wrap items-center gap-3 text-xs text-slate-500">
                                    <span class="flex items-center gap-1 font-mono">
                                        <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                        </svg>
                                        <span>Due: <?= date('M d, Y', strtotime($assignment->dueAt)) ?> &bull; <?= date('g:i A', strtotime($assignment->dueAt)) ?></span>
                                    </span>
                                    <span>&bull;</span>
                                    <span class="font-bold text-slate-700">Max Score: <?= number_format($assignment->maxScore, 0) ?> PTS</span>
                                    <?php if ($assignment->hasFile()): ?>
                                        <span>&bull;</span>
                                        <span class="inline-flex items-center gap-1 text-[11px] font-bold text-slate-600 bg-slate-100 px-2 py-0.5 rounded">
                                            <svg class="w-3 h-3 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                                            <span>Attached Materials</span>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Status Pill Badge -->
                            <div class="flex-shrink-0">
                                <?php if ($sub && $sub->isGraded()): ?>
                                    <div class="text-right">
                                        <span class="inline-flex items-center gap-1 px-3 py-1 rounded-xl text-xs font-bold bg-emerald-50 text-emerald-800 border border-emerald-200 font-mono shadow-xs">
                                            <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                            <span>Score: <?= number_format((float)$sub->score, 1) ?> / <?= number_format((float)$assignment->maxScore, 0) ?></span>
                                        </span>
                                        <span class="block text-[10px] text-slate-400 mt-1">Graded by teacher</span>
                                    </div>
                                <?php elseif ($sub): ?>
                                    <span class="inline-flex items-center gap-1 px-3 py-1 rounded-xl text-xs font-bold bg-sky-50 text-sky-800 border border-sky-200 shadow-xs">
                                        <svg class="w-3.5 h-3.5 text-sky-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        <span>Turned In <?= $sub->isLate($assignment->dueAt) ? '(Late)' : '' ?></span>
                                    </span>
                                <?php elseif ($isPastDue): ?>
                                    <span class="inline-flex items-center gap-1 px-3 py-1 rounded-xl text-xs font-bold bg-rose-50 text-rose-800 border border-rose-200 shadow-xs">
                                        <svg class="w-3.5 h-3.5 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        <span>Past Due / Missing</span>
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center gap-1 px-3 py-1 rounded-xl text-xs font-bold bg-amber-50 text-amber-800 border border-amber-200 shadow-xs">
                                        <svg class="w-3.5 h-3.5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        <span>Pending Submission</span>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Instructions Excerpt (if available) -->
                        <?php if (!empty($assignment->instructions)): ?>
                            <div class="p-3.5 bg-slate-50 rounded-xl border border-slate-100 text-xs text-slate-700 leading-relaxed">
                                <span class="font-bold text-slate-900 block mb-1">Instructions:</span>
                                <?= nl2br(htmlspecialchars($assignment->instructions)) ?>
                            </div>
                        <?php endif; ?>

                        <!-- Submission Details & Teacher Evaluation Feedback -->
                        <?php if ($sub): ?>
                            <div class="space-y-3 pt-2">
                                <div class="flex items-center justify-between text-xs text-slate-500 font-mono">
                                    <span>Submitted on: <?= date('M d, Y', strtotime($sub->submittedAt)) ?> &bull; <?= date('g:i A', strtotime($sub->submittedAt)) ?></span>
                                    <?php if ($sub->hasAttachment()): ?>
                                        <span class="text-brand-600 font-bold flex items-center gap-1">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                            <span>File Response Attached</span>
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <?php if ($sub->isGraded()): ?>
                                    <div class="p-4 bg-emerald-50/70 rounded-xl border border-emerald-200 space-y-1.5">
                                        <div class="flex items-center justify-between gap-2">
                                            <span class="text-xs font-extrabold text-emerald-900 uppercase tracking-wider flex items-center gap-1.5">
                                                <svg class="w-4 h-4 text-emerald-700" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                                <span>Teacher Evaluation Feedback</span>
                                            </span>
                                            <?php if ($sub->gradedAt): ?>
                                                <span class="text-[11px] text-emerald-700 font-mono">
                                                    Graded <?= date('M d, Y', strtotime($sub->gradedAt)) ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>

                                        <?php if (!empty($sub->teacherComment)): ?>
                                            <p class="text-xs text-emerald-950 font-medium leading-relaxed mt-1">
                                                <?= nl2br(htmlspecialchars($sub->teacherComment)) ?>
                                            </p>
                                        <?php else: ?>
                                            <p class="text-xs text-emerald-700 italic mt-1">Numerical score recorded without textual remarks.</p>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>
