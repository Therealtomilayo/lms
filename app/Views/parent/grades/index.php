<?php
/**
 * Child Grades & Performance Overview (PARENT-04) — Phase UI-0 Modernized
 * Continuous assessment, terminal scores, and official report card access for parents.
 *
 * @var \App\Models\Student $student
 * @var \App\Models\Student|null $selectedChild
 * @var \App\Models\Student[] $children
 * @var \App\Models\Term[] $terms
 * @var int $selectedTermId
 * @var bool $isPublished
 * @var \App\Models\TermResult[] $subjectResults
 * @var \App\Models\StudentTermSummary|null $summary
 * @var \App\Core\UserContext $user
 */

$results = $subjectResults ?? [];
$studentName = $student->name ?? 'Child';
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
                    <?= strtoupper(substr($studentName, 0, 1)) ?>
                </div>
                <div>
                    <!-- Breadcrumbs -->
                    <div class="flex items-center gap-2 text-xs font-semibold text-slate-400 mb-1">
                        <a href="/parent/dashboard" class="hover:text-brand-600 transition">Dashboard</a>
                        <span>&rsaquo;</span>
                        <a href="/parent/children/<?= (int)$student->id ?>" class="hover:text-brand-600 transition">Child Profile</a>
                        <span>&rsaquo;</span>
                        <span class="text-slate-700 font-bold">Academic Grades</span>
                    </div>

                    <div class="flex flex-wrap items-center gap-2.5">
                        <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight leading-tight">
                            <?= htmlspecialchars($studentName) ?>'s Academic Grades
                        </h1>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-lg text-xs font-bold bg-sky-50 text-sky-800 border border-sky-200">
                            <?= htmlspecialchars($fullClassName) ?>
                        </span>
                    </div>

                    <div class="flex flex-wrap items-center gap-3 text-xs text-slate-500 mt-1.5">
                        <span class="font-mono font-bold text-slate-700 bg-slate-100 px-2 py-0.5 rounded-md text-[11px]">
                            Adm: <?= htmlspecialchars($student->admissionNumber) ?>
                        </span>
                        <span>&bull;</span>
                        <span>Terminal Assessment & Examination Records</span>
                    </div>
                </div>
            </div>

            <!-- Quick Action Toolbar -->
            <div class="flex flex-wrap items-center gap-2">
                <?php if ($isPublished && !empty($results)): ?>
                    <a href="/parent/children/<?= (int)$student->id ?>/grades/report-card?term_id=<?= (int)$selectedTermId ?>" target="_blank" 
                       class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-bold bg-brand-600 hover:bg-brand-700 text-white shadow-xs transition">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <span>Official Report Card</span>
                    </a>
                <?php endif; ?>
                <a href="/parent/children/<?= (int)$student->id ?>" 
                   class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-bold bg-slate-100 hover:bg-slate-200 text-slate-700 transition">
                    <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    <span>Child Profile</span>
                </a>
                <a href="/parent/children/<?= (int)$student->id ?>/assignments" 
                   class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-bold bg-slate-100 hover:bg-slate-200 text-slate-700 transition">
                    <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                    <span>Coursework</span>
                </a>
                <a href="/parent/dashboard" 
                   class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-bold bg-slate-100 hover:bg-slate-200 text-slate-700 transition">
                    <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                    </svg>
                    <span>Dashboard</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Academic Term Selector Toolbar -->
    <!-- Academic Term Selector Toolbar -->
    <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <form method="GET" action="/parent/children/<?= (int)$student->id ?>/grades" class="flex items-center gap-3 w-full sm:w-auto">
            <label for="term_id" class="text-xs font-bold text-slate-700 uppercase tracking-wider whitespace-nowrap">Academic Term:</label>
            <select name="term_id" id="term_id" onchange="this.form.submit()" 
                    class="px-3.5 py-2 text-xs font-bold rounded-xl border border-slate-300 bg-slate-50 text-slate-800 focus:bg-white focus:border-brand-500 focus:ring-brand-500 transition">
                <?php 
                $activeStartDate = !empty($activeTerm) ? $activeTerm->startDate : date('Y-m-d');
                foreach ($terms as $t): 
                    $isFuture = ($t->startDate > $activeStartDate) && !in_array($t->status, ['active', 'grading_open', 'completed', 'archived'], true);
                ?>
                    <option value="<?= (int)$t->id ?>" <?= (int)$t->id === (int)$selectedTermId ? 'selected' : '' ?> <?= $isFuture ? 'disabled' : '' ?>>
                        <?= htmlspecialchars($t->name) ?><?= $isFuture ? ' (Upcoming Term)' : '' ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>

        <div class="text-xs font-semibold text-slate-500 flex items-center gap-2">
            <span>Result Status:</span>
            <?php if ($isPublished): ?>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-lg text-xs font-bold bg-emerald-50 text-emerald-800 border border-emerald-200">
                    Published Live
                </span>
            <?php else: ?>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-lg text-xs font-bold bg-amber-50 text-amber-800 border border-amber-200">
                    Results Processing
                </span>
            <?php endif; ?>
        </div>
    </div>

    <!-- Multi-Term Result Access & Clearance Hub -->
    <?php if (!empty($termsData)): ?>
        <div class="space-y-3">
            <div class="flex items-center justify-between">
                <h2 class="text-xs font-bold uppercase tracking-wider text-slate-700 flex items-center gap-2">
                    <svg class="w-4 h-4 text-brand-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    <span>Term-by-Term Official Report Card Access</span>
                </h2>
                <span class="text-xs text-slate-500 font-medium hidden sm:inline">Per-term bursary clearance &amp; scratch PIN security</span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <?php foreach ($termsData as $td): 
                    $tObj = $td['term'];
                    $isCur = (int)$tObj->id === (int)$selectedTermId;
                    $isFuture = !empty($td['isFuture']);
                ?>
                    <div class="bg-white rounded-2xl border <?= $isCur ? 'border-brand-500 ring-2 ring-brand-500/10' : 'border-slate-200' ?> p-5 shadow-xs flex flex-col justify-between transition hover:shadow-sm <?= $isFuture ? 'opacity-70 bg-slate-50/50' : '' ?>">
                        <div class="space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-extrabold uppercase tracking-wider text-slate-800"><?= htmlspecialchars($tObj->name) ?></span>
                                <?php if ($isFuture): ?>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-600 border border-slate-200">Upcoming Term</span>
                                <?php elseif ($td['isPublished']): ?>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">Published</span>
                                <?php else: ?>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200">In Review</span>
                                <?php endif; ?>
                            </div>

                            <div class="space-y-2 pt-1 border-t border-slate-100 text-xs">
                                <?php if ($isFuture): ?>
                                    <div class="flex items-center justify-between">
                                        <span class="text-slate-500 flex items-center gap-1.5">
                                            <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                            Status:
                                        </span>
                                        <span class="font-bold text-slate-500">Not Commenced</span>
                                    </div>
                                <?php else: ?>
                                    <div class="flex items-center justify-between">
                                        <span class="text-slate-500 flex items-center gap-1.5">
                                            <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                                            School Fees:
                                        </span>
                                        <?php if ($td['isCleared']): ?>
                                            <span class="font-bold text-emerald-600">Cleared &check;</span>
                                        <?php else: ?>
                                            <span class="font-bold text-red-600">Pending Bursary</span>
                                        <?php endif; ?>
                                    </div>

                                    <div class="flex items-center justify-between">
                                        <span class="text-slate-500 flex items-center gap-1.5">
                                            <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
                                            Result PIN:
                                        </span>
                                        <?php if ($td['isPinUnlocked']): ?>
                                            <span class="font-bold text-emerald-600">Unlocked &check;</span>
                                        <?php else: ?>
                                            <span class="font-semibold text-slate-500">PIN Required</span>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="mt-4 pt-3 border-t border-slate-100 flex items-center gap-2">
                            <?php if ($isFuture): ?>
                                <span class="flex-1 text-center py-2 px-3 rounded-xl text-xs font-bold bg-slate-100 text-slate-400 cursor-not-allowed">
                                    Term Inactive
                                </span>
                            <?php else: ?>
                                <a href="<?= $td['reportUrl'] ?>" target="_blank"
                                   class="flex-1 text-center py-2 px-3 rounded-xl text-xs font-bold transition <?= $td['isCleared'] && $td['isPublished'] ? 'bg-brand-600 hover:bg-brand-700 text-white shadow-xs' : 'bg-slate-100 hover:bg-slate-200 text-slate-700' ?>">
                                    <?= !$td['isCleared'] ? 'Clear Fees &amp; View' : ($td['isPinUnlocked'] ? 'View Report Card' : 'Unlock &amp; View Report') ?>
                                </a>
                                <?php if (!$isCur): ?>
                                    <a href="/parent/children/<?= (int)$student->id ?>/grades?term_id=<?= (int)$tObj->id ?>" 
                                       class="px-2.5 py-2 rounded-xl text-xs font-bold text-slate-600 bg-slate-100 hover:bg-slate-200" title="Switch overview to this term">
                                        &rarr;
                                    </a>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <?php if (!$isPublished): ?>
        <!-- Unpublished Alert Banner -->
        <div class="bg-amber-50 border border-amber-200 rounded-2xl p-10 text-center shadow-xs">
            <div class="w-14 h-14 rounded-2xl bg-amber-100 text-amber-600 mx-auto flex items-center justify-center mb-3">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <h3 class="text-base font-extrabold text-amber-900">Term Results Are Processing</h3>
            <p class="text-xs mt-1.5 text-amber-700 max-w-md mx-auto leading-relaxed">
                Official scores and terminal report cards for this term have not yet been approved and published by school administration. Please check back after official release.
            </p>
        </div>
    <?php else: ?>
        <!-- 4-Card Overview Stats Strip -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Class Position -->
            <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Class Position</span>
                    <span class="p-2 rounded-xl bg-brand-50 text-brand-700">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
                        </svg>
                    </span>
                </div>
                <div class="mt-3">
                    <div class="flex items-baseline gap-1.5">
                        <h3 class="text-2xl font-extrabold text-brand-700">
                            <?= $summary && $summary->rankInClass ? "#{$summary->rankInClass}" : '—' ?>
                        </h3>
                        <?php if ($summary && $summary->rankInClass): ?>
                            <span class="text-xs font-semibold text-slate-500">in cohort</span>
                        <?php endif; ?>
                    </div>
                    <p class="text-xs text-slate-500 mt-1.5">
                        Cohort Standing
                    </p>
                </div>
            </div>

            <!-- Average Score -->
            <?php
            $displayAvg = 0.0;
            if ($summary && (float)$summary->averageScore > 0) {
                $displayAvg = (float)$summary->averageScore;
            } elseif (!empty($results)) {
                $scores = array_map(fn($r) => (float)$r->computedScore, $results);
                $displayAvg = round(array_sum($scores) / count($scores), 2);
            }
            ?>
            <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Average Score</span>
                    <span class="p-2 rounded-xl bg-indigo-50 text-indigo-700">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                        </svg>
                    </span>
                </div>
                <div class="mt-3">
                    <div class="flex items-baseline gap-1.5">
                        <h3 class="text-2xl font-extrabold text-slate-900">
                            <?= number_format($displayAvg, 2) ?>%
                        </h3>
                    </div>
                    <p class="text-xs text-slate-500 mt-1.5">
                        Weighted Term Average
                    </p>
                </div>
            </div>

            <!-- GPA -->
            <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Grade Point Average</span>
                    <span class="p-2 rounded-xl bg-emerald-50 text-emerald-700">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 14l9-5-9-5-9 5 9 5z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/>
                        </svg>
                    </span>
                </div>
                <div class="mt-3">
                    <div class="flex items-baseline gap-1.5">
                        <h3 class="text-2xl font-extrabold text-slate-900">
                            <?= ($summary && $summary->gpa !== null) ? number_format((float)$summary->gpa, 2) : 'N/A' ?>
                        </h3>
                        <span class="text-xs font-semibold text-slate-500">/ 5.0</span>
                    </div>
                    <p class="text-xs text-slate-500 mt-1.5">
                        GPA Rating
                    </p>
                </div>
            </div>

            <!-- Total Courses Evaluated -->
            <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Evaluated Courses</span>
                    <span class="p-2 rounded-xl bg-sky-50 text-sky-700">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </span>
                </div>
                <div class="mt-3">
                    <div class="flex items-baseline gap-1.5">
                        <h3 class="text-2xl font-extrabold text-slate-900"><?= count($results) ?></h3>
                        <span class="text-xs font-semibold text-slate-500">subjects</span>
                    </div>
                    <p class="text-xs text-slate-500 mt-1.5">
                        Total Scored
                    </p>
                </div>
            </div>
        </div>

        <!-- Subject Grades Table Card -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
            <div class="p-5 border-b border-slate-100 flex items-center justify-between">
                <div>
                    <h2 class="text-base font-bold text-slate-900">Coursework & Subject Grade Breakdown</h2>
                    <p class="text-xs text-slate-500 mt-0.5">Computed continuous assessment scores, examination results, and assigned grade scales</p>
                </div>
                <span class="text-xs font-bold text-slate-500 font-mono bg-slate-100 px-2.5 py-1 rounded-lg"><?= count($results) ?> Subjects</span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200 text-slate-600 font-bold uppercase tracking-wider">
                            <th class="py-3.5 px-5">Subject Name</th>
                            <th class="py-3.5 px-4">Subject Code</th>
                            <th class="py-3.5 px-4 text-center">Computed Score</th>
                            <th class="py-3.5 px-4 text-center">Grade Letter</th>
                            <th class="py-3.5 px-5">Teacher Remark</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-medium text-slate-800">
                        <?php if (empty($results)): ?>
                            <tr>
                                <td colspan="5" class="py-8 text-center text-slate-400 text-xs italic">
                                    No subject grades recorded for this academic term.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($results as $res): 
                                $sName = $res->classSubject?->subject?->name ?? ($res->classSubject?->subjectName ?? 'Subject');
                                $sCode = $res->classSubject?->subject?->code ?? ($res->classSubject?->subjectCode ?? 'SUB');
                                $grade = strtoupper((string)$res->gradeLetter);
                                $badgeStyle = match($grade) {
                                    'A', 'A+', 'A*' => 'bg-emerald-50 text-emerald-800 border-emerald-200',
                                    'B', 'B+', 'B-' => 'bg-sky-50 text-sky-800 border-sky-200',
                                    'C', 'C+' => 'bg-amber-50 text-amber-800 border-amber-200',
                                    'D', 'E', 'F' => 'bg-rose-50 text-rose-800 border-rose-200',
                                    default => 'bg-slate-100 text-slate-700 border-slate-200'
                                };
                            ?>
                                <tr class="hover:bg-slate-50/50 transition">
                                    <td class="py-3.5 px-5 font-bold text-slate-900 text-sm">
                                        <?= htmlspecialchars($sName) ?>
                                    </td>
                                    <td class="py-3.5 px-4 font-mono font-bold text-slate-500">
                                        <?= htmlspecialchars($sCode) ?>
                                    </td>
                                    <td class="py-3.5 px-4 text-center font-extrabold text-slate-900 font-mono text-sm">
                                        <?= number_format((float)$res->computedScore, 1) ?>%
                                    </td>
                                    <td class="py-3.5 px-4 text-center">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-black border font-mono <?= $badgeStyle ?>">
                                            <?= htmlspecialchars($grade ?: '—') ?>
                                        </span>
                                    </td>
                                    <td class="py-3.5 px-5 text-slate-600 font-semibold">
                                        <?= htmlspecialchars($res->remark ?: 'Satisfactory') ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Standard Nigerian Grading Scale Reference Legend -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-5">
            <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2.5">Official Grading Scale Reference</h3>
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-2 text-center text-xs">
                <div class="p-2.5 rounded-xl bg-emerald-50 border border-emerald-200">
                    <span class="font-extrabold text-emerald-800 block text-sm">A</span>
                    <span class="text-[11px] text-emerald-700 font-semibold">70% – 100%</span>
                    <span class="text-[10px] text-emerald-600 block">Distinction (5.0)</span>
                </div>
                <div class="p-2.5 rounded-xl bg-sky-50 border border-sky-200">
                    <span class="font-extrabold text-sky-800 block text-sm">B</span>
                    <span class="text-[11px] text-sky-700 font-semibold">60% – 69%</span>
                    <span class="text-[10px] text-sky-600 block">Very Good (4.0)</span>
                </div>
                <div class="p-2.5 rounded-xl bg-amber-50 border border-amber-200">
                    <span class="font-extrabold text-amber-800 block text-sm">C</span>
                    <span class="text-[11px] text-amber-700 font-semibold">50% – 59%</span>
                    <span class="text-[10px] text-amber-600 block">Credit (3.0)</span>
                </div>
                <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-200">
                    <span class="font-extrabold text-slate-800 block text-sm">D</span>
                    <span class="text-[11px] text-slate-700 font-semibold">45% – 49%</span>
                    <span class="text-[10px] text-slate-600 block">Pass (2.0)</span>
                </div>
                <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-200">
                    <span class="font-extrabold text-slate-800 block text-sm">E</span>
                    <span class="text-[11px] text-slate-700 font-semibold">40% – 44%</span>
                    <span class="text-[10px] text-slate-600 block">Fair (1.0)</span>
                </div>
                <div class="p-2.5 rounded-xl bg-rose-50 border border-rose-200">
                    <span class="font-extrabold text-rose-800 block text-sm">F</span>
                    <span class="text-[11px] text-rose-700 font-semibold">0% – 39%</span>
                    <span class="text-[10px] text-rose-600 block">Fail (0.0)</span>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>
