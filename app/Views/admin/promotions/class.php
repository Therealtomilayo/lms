<?php

/**
 * Admin Class Cohort Promotion Evaluation & Advancement Matrix (SRS §17, §18, §58.3)
 *
 * @var int $classId
 * @var array<int, \App\Models\AcademicSession> $sessions
 * @var int $selectedSessionId
 * @var array $cohort
 * @var array<int, \App\Models\SchoolClass> $allClasses
 * @var bool $isSuperAdmin
 */

$this->layout('layouts/admin', [
    'title' => 'Cohort Promotion Matrix &bull; ' . ($cohort['class']?->name ?? 'Class') . ' — Claret LMS',
    'headerTitle' => 'Class Cohort Promotion Matrix',
    'headerSubtitle' => 'Cumulative academic evaluation, graduation standing, and session advancement',
]);

$schoolClass = $cohort['class'] ?? null;
$session = $cohort['session'] ?? null;
$finalTerm = $cohort['final_term'] ?? null;
$isPublished = (bool)($cohort['is_final_term_published'] ?? false);
$isTerminal = (bool)($cohort['is_terminal'] ?? false);
$isFinalized = (bool)($cohort['is_finalized'] ?? false);
$nextLevel = $cohort['next_level'] ?? null;
$suggestedNextClasses = $cohort['suggested_next_classes'] ?? [];
$students = $cohort['students'] ?? [];
$totalStudents = (int)($cohort['total_students'] ?? 0);
$promotedCount = (int)($cohort['promoted_count'] ?? 0);
$graduatedCount = (int)($cohort['graduated_count'] ?? 0);
$borderlineCount = (int)($cohort['borderline_count'] ?? 0);
$repeatingCount = (int)($cohort['repeating_count'] ?? 0);

$passCount = $isTerminal ? $graduatedCount : $promotedCount;
$passRate = $totalStudents > 0 ? round(($passCount / $totalStudents) * 100, 1) : 0;
?>

<div class="space-y-6 pb-16">

    <!-- Top Action Ribbon -->
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <a href="/admin/promotions?session_id=<?= $selectedSessionId ?>" 
           class="inline-flex items-center gap-2 text-xs font-bold text-slate-600 hover:text-brand-700 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            <span>&larr; Back to Promotions Cohorts Overview</span>
        </a>

        <div class="flex items-center gap-3">
            <a href="/admin/promotions/class/<?= $classId ?>/export?session_id=<?= $selectedSessionId ?>" 
               class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-white border border-slate-300 hover:bg-slate-50 text-slate-800 font-bold text-xs shadow-xs transition">
                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                </svg>
                <span>Export Promotion Broadsheet (CSV)</span>
            </a>
        </div>
    </div>

    <!-- Class Cohort Hero Card -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6">
            <div class="space-y-2 max-w-3xl">
                <div class="flex items-center gap-3">
                    <span class="inline-flex items-center justify-center w-11 h-11 rounded-xl bg-brand-50 text-brand-700 font-black text-base shadow-xs">
                        <?= htmlspecialchars(substr($schoolClass?->name ?? 'C', 0, 2)) ?>
                    </span>
                    <div>
                        <div class="flex items-center gap-2.5">
                            <h1 class="text-xl font-black tracking-tight text-slate-900"><?= htmlspecialchars($schoolClass?->name ?? 'Class') ?></h1>
                            <?php if ($isTerminal): ?>
                                <span class="bg-blue-100 text-blue-800 text-[10px] font-black uppercase px-2.5 py-1 rounded-md tracking-wider">
                                    Terminal Level &bull; Graduation Track
                                </span>
                            <?php else: ?>
                                <span class="bg-slate-100 text-slate-700 text-[10px] font-black uppercase px-2.5 py-1 rounded-md tracking-wider">
                                    Sequential Advancement &bull; <?= htmlspecialchars($nextLevel?->name ?? 'Next Level') ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        <p class="text-xs text-slate-500 font-medium mt-0.5">
                            <?= htmlspecialchars($session?->name ?? '') ?> Session &bull; <?= $totalStudents ?> Students Registered
                        </p>
                    </div>
                </div>
                <p class="text-xs text-slate-600 leading-relaxed">
                    <?php if ($isTerminal): ?>
                        Students achieving &ge; 50% cumulative annual average graduate and transition to <strong>Alumni Status</strong>. 
                        Active class enrollment will be closed upon batch execution.
                    <?php else: ?>
                        Advancement progresses students to the sequential academic tier (<strong><?= htmlspecialchars($nextLevel?->name ?? 'Next Level') ?></strong>). 
                        Repeating students remain enrolled at the current tier.
                    <?php endif; ?>
                </p>
            </div>

            <div class="flex items-center gap-3 flex-shrink-0">
                <div class="px-4 py-3 rounded-xl flex items-center gap-3 <?= $isPublished ? 'bg-emerald-50 border border-emerald-200' : 'bg-amber-50 border border-amber-200' ?>">
                    <span class="w-3 h-3 rounded-full <?= $isPublished ? 'bg-emerald-500' : 'bg-amber-500 animate-pulse' ?>"></span>
                    <div>
                        <div class="text-xs font-bold <?= $isPublished ? 'text-emerald-900' : 'text-amber-900' ?>">
                            <?= $isPublished ? '3rd Term Released' : '3rd Term Unreleased' ?>
                        </div>
                        <div class="text-[11px] <?= $isPublished ? 'text-emerald-700' : 'text-amber-700' ?>">
                            <?= $isPublished ? 'Batch execution authorized' : 'Batch commitment locked' ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Alert / Gating Notification -->
    <?php if (!$isPublished): ?>
        <div class="bg-amber-50 border-l-4 border-amber-500 rounded-xl p-4 text-amber-900 shadow-xs flex items-start gap-3">
            <svg class="w-5 h-5 text-amber-600 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
            <div class="text-xs space-y-1">
                <h4 class="font-bold">Cohort Advancement Locked: 3rd Term Results Prerequisite</h4>
                <p>
                    In accordance with institutional policy, promotion decisions can only be committed after 3rd Term results are officially published.
                    You can preview the cumulative matrix, adjust preliminary placement decisions, or stage borderline candidates for Super Admin repetition review.
                </p>
            </div>
        </div>
    <?php endif; ?>

    <!-- 5 Analytics Cards -->
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
        <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-xs">
            <div class="text-[11px] font-bold uppercase text-slate-500 tracking-wider">Cohort Enrolled</div>
            <div class="text-2xl font-black text-slate-900 mt-1"><?= $totalStudents ?></div>
            <div class="text-[11px] text-slate-500 mt-0.5"><?= $passRate ?>% pass rate</div>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-xs">
            <div class="text-[11px] font-bold uppercase text-emerald-600 tracking-wider"><?= $isTerminal ? 'Graduating' : 'Promoted' ?></div>
            <div class="text-2xl font-black text-emerald-700 mt-1"><?= $passCount ?></div>
            <div class="text-[11px] text-slate-500 mt-0.5">&ge; 50% annual average</div>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-xs">
            <div class="text-[11px] font-bold uppercase text-amber-600 tracking-wider">Borderline Review</div>
            <div class="text-2xl font-black text-amber-700 mt-1"><?= $borderlineCount ?></div>
            <div class="text-[11px] text-slate-500 mt-0.5">40.0% &ndash; 49.9%</div>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-xs">
            <div class="text-[11px] font-bold uppercase text-rose-600 tracking-wider">Repeating Class</div>
            <div class="text-2xl font-black text-rose-700 mt-1"><?= $repeatingCount ?></div>
            <div class="text-[11px] text-slate-500 mt-0.5">&lt; 40% annual average</div>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-xs">
            <div class="text-[11px] font-bold uppercase text-purple-600 tracking-wider">Advancement State</div>
            <div class="text-lg font-black <?= $isFinalized ? 'text-emerald-700' : 'text-slate-700' ?> mt-1">
                <?= $isFinalized ? 'Committed' : 'Draft Standings' ?>
            </div>
            <div class="text-[11px] text-slate-500 mt-0.5"><?= $isFinalized ? 'Active in new level' : 'Pending batch run' ?></div>
        </div>
    </div>

    <!-- Main Batch Advancement Form -->
    <form method="POST" action="/admin/promotions/execute" id="promotionForm" class="space-y-6">
        <input type="hidden" name="class_id" value="<?= $classId ?>">
        <input type="hidden" name="from_session_id" value="<?= $selectedSessionId ?>">

        <!-- Target Session & Placement Toolbar -->
        <div class="bg-slate-50 border border-slate-200 rounded-2xl p-5 shadow-xs">
            <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
                <div class="space-y-1">
                    <h3 class="text-sm font-black text-slate-900">Target Session &amp; Cohort Placement</h3>
                    <p class="text-xs text-slate-500">Configure destination parameters for promoted and advancing students</p>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <div class="flex items-center gap-2">
                        <label for="to_session_id" class="text-xs font-bold text-slate-700">Advance Into Session:</label>
                        <select name="to_session_id" id="to_session_id" 
                                class="text-xs font-semibold bg-white border border-slate-300 rounded-xl px-3 py-2 text-slate-800 focus:ring-2 focus:ring-brand-500 focus:outline-none">
                            <?php foreach ($sessions as $s): ?>
                                <option value="<?= (int)$s->id ?>" <?= $s->id === $selectedSessionId ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($s->name) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <?php if (!$isTerminal): ?>
                        <div class="flex items-center gap-2">
                            <label for="to_class_id" class="text-xs font-bold text-slate-700">Default Target Class:</label>
                            <select name="to_class_id" id="to_class_id" 
                                    class="text-xs font-semibold bg-white border border-slate-300 rounded-xl px-3 py-2 text-slate-800 focus:ring-2 focus:ring-brand-500 focus:outline-none">
                                <?php if (!empty($suggestedNextClasses)): ?>
                                    <?php foreach ($suggestedNextClasses as $nc): ?>
                                        <option value="<?= (int)$nc->id ?>">
                                            <?= htmlspecialchars($nc->getFullName()) ?> (Sequential Next Level)
                                        </option>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <option value="">&mdash; Select Target Class &mdash;</option>
                                    <?php foreach ($allClasses as $ac): ?>
                                        <?php if ($ac->id !== $classId): ?>
                                            <option value="<?= (int)$ac->id ?>">
                                                <?= htmlspecialchars($ac->getFullName()) ?>
                                            </option>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                    <?php else: ?>
                        <div class="px-3.5 py-2 rounded-xl bg-blue-50 border border-blue-200 text-blue-800 text-xs font-bold">
                            Alumni Transition &bull; Target Class Disabled
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Student Cumulative Standings Matrix -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
            <div class="p-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h3 class="font-bold text-slate-900 text-sm">Student Cumulative Matrix &bull; Annual Performance</h3>
                    <p class="text-xs text-slate-500">Term 1 + Term 2 + Term 3 breakdown with automated benchmark recommendation</p>
                </div>
                <div class="flex items-center gap-2 text-xs">
                    <span class="inline-flex items-center gap-1 text-emerald-700 font-bold"><span class="w-2 h-2 rounded-full bg-emerald-500"></span> &ge; 50% Pass</span>
                    <span class="inline-flex items-center gap-1 text-amber-700 font-bold ml-2"><span class="w-2 h-2 rounded-full bg-amber-500"></span> 40-49% Review</span>
                    <span class="inline-flex items-center gap-1 text-rose-700 font-bold ml-2"><span class="w-2 h-2 rounded-full bg-rose-500"></span> &lt; 40% Repeat</span>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="bg-slate-50/90 border-b border-slate-200 text-slate-700 font-bold uppercase tracking-wider text-[11px]">
                            <th class="py-3 px-3 w-10 text-center">Rank</th>
                            <th class="py-3 px-4">Student Details</th>
                            <th class="py-3 px-3 text-center">1st Term</th>
                            <th class="py-3 px-3 text-center">2nd Term</th>
                            <th class="py-3 px-3 text-center">3rd Term</th>
                            <th class="py-3 px-3 text-center bg-slate-100/70">Annual Average</th>
                            <th class="py-3 px-3 text-center">Recommended</th>
                            <th class="py-3 px-4">Advancement Decision</th>
                            <th class="py-3 px-3 text-center">Actions / Governance</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-medium text-slate-800">
                        <?php if (empty($students)): ?>
                            <tr>
                                <td colspan="9" class="py-8 text-center text-slate-400 italic">
                                    No students enrolled in this class cohort.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($students as $row): ?>
                                <?php
                                $s = $row['student'];
                                $sId = (int)$s->id;
                                $cumStats = $row['cumulative_stats'];
                                $annualAvg = (float)($cumStats['annual_average'] ?? 0);
                                $termScores = array_values($cumStats['terms'] ?? []);
                                $t1 = $termScores[0]['average'] ?? null;
                                $t2 = $termScores[1]['average'] ?? null;
                                $t3 = $termScores[2]['average'] ?? null;
                                
                                $sugDecision = $row['suggested_decision'];
                                $curDecision = $row['decision'] ?? $sugDecision;
                                $isBorderline = (bool)($row['is_borderline'] ?? false);
                                $isFinalizedRow = ($row['status'] ?? '') === 'finalized';
                                
                                $avgColor = $annualAvg >= 50.0 ? 'text-emerald-700' : ($annualAvg >= 40.0 ? 'text-amber-700' : 'text-rose-700');
                                ?>
                                <tr class="hover:bg-slate-50/70 transition-colors">
                                    <td class="py-3.5 px-3 text-center font-bold text-slate-600">
                                        <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-slate-100 text-slate-700 font-black text-[10px]">
                                            <?= $row['rank'] ?? '-' ?>
                                        </span>
                                    </td>
                                    <td class="py-3.5 px-4 font-bold text-slate-900">
                                        <div class="text-sm font-extrabold text-slate-900"><?= htmlspecialchars($s->name) ?></div>
                                        <div class="text-[11px] font-mono text-slate-500 font-normal"><?= htmlspecialchars($s->admissionNumber) ?></div>
                                    </td>
                                    <td class="py-3.5 px-3 text-center">
                                        <?= $t1 !== null ? number_format((float)$t1, 1) . '%' : '<span class="text-slate-400">&mdash;</span>' ?>
                                    </td>
                                    <td class="py-3.5 px-3 text-center">
                                        <?= $t2 !== null ? number_format((float)$t2, 1) . '%' : '<span class="text-slate-400">&mdash;</span>' ?>
                                    </td>
                                    <td class="py-3.5 px-3 text-center">
                                        <?= $t3 !== null ? number_format((float)$t3, 1) . '%' : '<span class="text-slate-400">&mdash;</span>' ?>
                                    </td>
                                    <td class="py-3.5 px-3 text-center font-black text-sm <?= $avgColor ?> bg-slate-50/50">
                                        <?= number_format($annualAvg, 2) ?>%
                                    </td>
                                    <td class="py-3.5 px-3 text-center">
                                        <?php if ($sugDecision === 'graduated'): ?>
                                            <span class="px-2 py-0.5 rounded-md text-[10px] font-black uppercase bg-blue-100 text-blue-800">
                                                Graduated
                                            </span>
                                        <?php elseif ($sugDecision === 'promoted'): ?>
                                            <span class="px-2 py-0.5 rounded-md text-[10px] font-black uppercase bg-emerald-100 text-emerald-800">
                                                Promoted
                                            </span>
                                        <?php elseif ($sugDecision === 'borderline'): ?>
                                            <span class="px-2 py-0.5 rounded-md text-[10px] font-black uppercase bg-amber-100 text-amber-800">
                                                Borderline
                                            </span>
                                        <?php else: ?>
                                            <span class="px-2 py-0.5 rounded-md text-[10px] font-black uppercase bg-rose-100 text-rose-800">
                                                Repeating
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-3.5 px-4">
                                        <select name="overrides[<?= $sId ?>][decision]" 
                                                class="text-xs font-bold bg-white border border-slate-300 rounded-lg px-2.5 py-1.5 text-slate-800 focus:ring-2 focus:ring-brand-500 focus:outline-none">
                                            <?php if ($isTerminal): ?>
                                                <option value="graduated" <?= $curDecision === 'graduated' ? 'selected' : '' ?>>Graduated (Alumni)</option>
                                                <option value="repeating" <?= $curDecision === 'repeating' ? 'selected' : '' ?>>Repeat Terminal Year</option>
                                                <option value="withdrawn" <?= $curDecision === 'withdrawn' ? 'selected' : '' ?>>Withdrawn</option>
                                            <?php else: ?>
                                                <option value="promoted" <?= $curDecision === 'promoted' ? 'selected' : '' ?>>Promoted (Advance)</option>
                                                <option value="repeating" <?= $curDecision === 'repeating' ? 'selected' : '' ?>>Repeat Current Class</option>
                                                <option value="withdrawn" <?= $curDecision === 'withdrawn' ? 'selected' : '' ?>>Withdrawn</option>
                                            <?php endif; ?>
                                        </select>
                                    </td>
                                    <td class="py-3.5 px-3 text-center">
                                        <?php if ($isBorderline): ?>
                                            <button type="button" 
                                                    onclick="openRepetitionModal(<?= $sId ?>, '<?= addslashes($s->name) ?>', '<?= addslashes($s->admissionNumber) ?>', '<?= $annualAvg ?>')"
                                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md bg-amber-100 hover:bg-amber-200 text-amber-900 font-bold text-[10px] transition cursor-pointer">
                                                <svg class="w-3.5 h-3.5 text-amber-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                                <span>Stage Repetition</span>
                                            </button>
                                        <?php elseif ($isFinalizedRow): ?>
                                            <span class="inline-flex items-center gap-1 text-[11px] font-bold text-emerald-700">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                                Advancement Active
                                            </span>
                                        <?php else: ?>
                                            <span class="text-[11px] text-slate-400 font-medium">Standard Rule</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Bottom Execution Ribbon -->
            <div class="p-5 bg-slate-50 border-t border-slate-200 flex flex-col sm:flex-row items-center justify-between gap-4">
                <div class="text-xs text-slate-600">
                    <?php if (!$isPublished): ?>
                        <span class="text-amber-800 font-bold">Promotion commitments are locked until 3rd Term results are officially released.</span>
                    <?php else: ?>
                        <span>Executing will update student class enrollments, assign promotion records, and register graduates as alumni.</span>
                    <?php endif; ?>
                </div>

                <div class="flex items-center gap-3">
                    <button type="submit" 
                            <?= !$isPublished ? 'disabled' : '' ?>
                            class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-brand-700 hover:bg-brand-800 disabled:opacity-50 disabled:cursor-not-allowed text-white font-black text-xs shadow-md transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <span><?= $isFinalized ? 'Re-Commit Cohort Advancement' : 'Commit Batch Promotion &amp; Advancement' ?></span>
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- Borderline Repetition Review Modal (Two-Tier Governance §58.3) -->
<div id="repetitionModal" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center hidden p-4">
    <div class="bg-white rounded-2xl border border-slate-200 shadow-2xl max-w-lg w-full overflow-hidden">
        <form method="POST" action="/admin/promotions/stage-repetition">
            <input type="hidden" name="student_id" id="modalStudentId" value="">
            <input type="hidden" name="class_id" value="<?= $classId ?>">
            <input type="hidden" name="session_id" value="<?= $selectedSessionId ?>">

            <div class="p-6 border-b border-slate-100 flex items-start justify-between gap-4">
                <div class="flex items-center gap-3">
                    <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-amber-100 text-amber-800">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    </span>
                    <div>
                        <h3 class="font-black text-slate-900 text-base">Stage Repetition Request</h3>
                        <p class="text-xs text-slate-500">Super Admin Two-Tier Governance Queue (SRS §58.3)</p>
                    </div>
                </div>
                <button type="button" onclick="closeRepetitionModal()" class="text-slate-400 hover:text-slate-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <div class="p-6 space-y-4 text-xs">
                <div class="bg-slate-50 border border-slate-200 rounded-xl p-3.5 space-y-1">
                    <div><span class="font-bold text-slate-600">Student:</span> <strong id="modalStudentName" class="text-slate-900 font-black"></strong></div>
                    <div><span class="font-bold text-slate-600">Admission No:</span> <span id="modalStudentAdm" class="font-mono text-slate-700"></span></div>
                    <div><span class="font-bold text-slate-600">Annual Cumulative Average:</span> <span id="modalStudentAvg" class="font-black text-amber-700"></span>%</div>
                </div>

                <div class="space-y-1.5">
                    <label for="modalReason" class="font-bold text-slate-800 block">
                        Academic Justification / Repetition Recommendation Note <span class="text-rose-600">*</span>
                    </label>
                    <textarea name="reason" id="modalReason" rows="4" required
                              placeholder="Detail the subject deficiencies, attendance gaps, or pedagogical factors supporting cohort retention..."
                              class="w-full text-xs rounded-xl border border-slate-300 p-3 text-slate-800 focus:ring-2 focus:ring-brand-500 focus:outline-none"></textarea>
                    <p class="text-[11px] text-slate-500">
                        This request will appear on the Super Admin Two-Tier Approval Queue (<code>/admin/approvals</code>) for final authorization.
                    </p>
                </div>
            </div>

            <div class="p-4 bg-slate-50 border-t border-slate-100 flex items-center justify-end gap-3">
                <button type="button" onclick="closeRepetitionModal()" class="px-4 py-2 rounded-xl text-xs font-bold text-slate-600 hover:text-slate-800">
                    Cancel
                </button>
                <button type="submit" class="px-4 py-2 rounded-xl text-xs font-black bg-amber-600 hover:bg-amber-700 text-white shadow-xs">
                    Dispatch to Super Admin Queue
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openRepetitionModal(studentId, studentName, studentAdm, studentAvg) {
    document.getElementById('modalStudentId').value = studentId;
    document.getElementById('modalStudentName').textContent = studentName;
    document.getElementById('modalStudentAdm').textContent = studentAdm;
    document.getElementById('modalStudentAvg').textContent = parseFloat(studentAvg).toFixed(2);
    document.getElementById('repetitionModal').classList.remove('hidden');
}

function closeRepetitionModal() {
    document.getElementById('repetitionModal').classList.add('hidden');
    document.getElementById('modalReason').value = '';
}
</script>
