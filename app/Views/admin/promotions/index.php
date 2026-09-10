<?php

/**
 * Admin Student Promotions & Cohort Advancement Overview (SRS §17, §18, §58.3)
 *
 * @var array<int, \App\Models\AcademicSession> $sessions
 * @var \App\Models\AcademicSession|null $currentSession
 * @var int $selectedSessionId
 * @var array $overview
 */

$this->layout('layouts/admin', [
    'title' => 'Student Promotions & Cohort Advancement — Claret LMS',
    'headerTitle' => 'Student Promotions & Advancement',
    'headerSubtitle' => 'Session-end cumulative academic performance evaluation, graduation, and cohort advancement (SRS §17, §18)',
]);

$session = $overview['session'] ?? null;
$finalTerm = $overview['final_term'] ?? null;
$isPublished = (bool)($overview['is_final_term_published'] ?? false);
$totalClasses = (int)($overview['total_classes'] ?? 0);
$totalStudents = (int)($overview['total_students'] ?? 0);
$totalPromoted = (int)($overview['total_promoted'] ?? 0);
$totalGraduated = (int)($overview['total_graduated'] ?? 0);
$totalRepeating = (int)($overview['total_repeating'] ?? 0);
$totalBorderline = (int)($overview['total_borderline'] ?? 0);
$cohortClasses = $overview['classes'] ?? [];
?>

<div class="space-y-6 pb-12">

    <!-- Header Card with Session Selector & 3rd Term Release Prerequisite Notice -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6">
            <div class="space-y-2 max-w-3xl">
                <div class="flex items-center gap-3">
                    <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-brand-50 text-brand-700 font-bold">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                        </svg>
                    </span>
                    <div>
                        <h1 class="text-xl font-black tracking-tight text-slate-900">Student Promotion &amp; Graduation Engine</h1>
                        <p class="text-xs text-slate-500 font-medium">Claret International School &bull; Academic Cohort Management</p>
                    </div>
                </div>
                <p class="text-xs text-slate-600 leading-relaxed">
                    Promotion decisions are computed across all three academic terms (cumulative annual average). 
                    Promotion from terminal classes (<strong>Primary 5</strong> and <strong>SS 3</strong>) confers <strong>Graduated Alumni</strong> standing, 
                    while intermediate cohorts advance sequentially. Final cohort advancement is committed exclusively upon <strong>3rd Term results release</strong>.
                </p>
            </div>

            <!-- Session Switcher & 3rd Term Gate Status -->
            <div class="flex flex-col sm:flex-row items-start sm:items-center gap-3 flex-shrink-0">
                <form method="GET" action="/admin/promotions" class="flex items-center gap-2">
                    <label for="session_id" class="text-xs font-bold text-slate-600">Session:</label>
                    <select id="session_id" name="session_id" onchange="this.form.submit()" 
                            class="text-xs font-semibold bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 text-slate-800 focus:ring-2 focus:ring-brand-500 focus:outline-none">
                        <?php foreach ($sessions as $s): ?>
                            <option value="<?= (int)$s->id ?>" <?= $s->id === $selectedSessionId ? 'selected' : '' ?>>
                                <?= htmlspecialchars($s->name) ?> <?= $s->isActive() ? '(Active)' : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>

                <div class="px-3.5 py-2 rounded-xl flex items-center gap-2.5 text-xs font-bold <?= $isPublished ? 'bg-emerald-50 border border-emerald-200 text-emerald-800' : 'bg-amber-50 border border-amber-200 text-amber-900' ?>">
                    <span class="w-2.5 h-2.5 rounded-full <?= $isPublished ? 'bg-emerald-500' : 'bg-amber-500 animate-pulse' ?>"></span>
                    <span>
                        <?= $isPublished ? '3rd Term Results Published' : '3rd Term Results Locked (Draft / Unpublished)' ?>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Alert / Prerequisite Callout -->
    <?php if (!$isPublished): ?>
        <div class="bg-amber-50 border-l-4 border-amber-500 rounded-xl p-4 text-amber-900 shadow-xs flex items-start gap-3">
            <svg class="w-5 h-5 text-amber-600 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
            <div class="text-xs space-y-1">
                <h4 class="font-bold">Preliminary Cumulative Standings Mode</h4>
                <p>
                    The 3rd Term results for session <strong><?= htmlspecialchars($session?->name ?? 'Current Session') ?></strong> have not yet been published (or <?= $finalTerm ? htmlspecialchars($finalTerm->name) : '3rd Term' ?> is still in session).
                    You can view preliminary cumulative rankings and identify borderline candidates, but <strong>final cohort advancement execution remains locked</strong> until official publication to maintain academic audit integrity.
                </p>
            </div>
        </div>
    <?php else: ?>
        <div class="bg-emerald-50 border-l-4 border-emerald-500 rounded-xl p-4 text-emerald-900 shadow-xs flex items-start gap-3">
            <svg class="w-5 h-5 text-emerald-600 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <div class="text-xs space-y-1">
                <h4 class="font-bold">Promotion Execution Cleared</h4>
                <p>
                    3rd Term results are officially published for <?= htmlspecialchars($session?->name ?? '') ?>. You may now evaluate each class cohort, review repetition recommendations, and execute atomic advancement into the next academic session.
                </p>
            </div>
        </div>
    <?php endif; ?>

    <!-- 5-Card Analytics Summary Strip -->
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
        <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-xs">
            <div class="text-[11px] font-bold uppercase text-slate-500 tracking-wider">Total Cohorts</div>
            <div class="text-2xl font-black text-slate-900 mt-1"><?= $totalClasses ?></div>
            <div class="text-[11px] text-slate-500 mt-0.5"><?= $totalStudents ?> active students</div>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-xs">
            <div class="text-[11px] font-bold uppercase text-emerald-600 tracking-wider">Promoting (&ge; 50%)</div>
            <div class="text-2xl font-black text-emerald-700 mt-1"><?= $totalPromoted ?></div>
            <div class="text-[11px] text-slate-500 mt-0.5">Satisfied pass threshold</div>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-xs">
            <div class="text-[11px] font-bold uppercase text-blue-600 tracking-wider">Graduating (Alumni)</div>
            <div class="text-2xl font-black text-blue-700 mt-1"><?= $totalGraduated ?></div>
            <div class="text-[11px] text-slate-500 mt-0.5">Pri 5 &amp; SS 3 terminal</div>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-xs">
            <div class="text-[11px] font-bold uppercase text-amber-600 tracking-wider">Borderline (40-49%)</div>
            <div class="text-2xl font-black text-amber-700 mt-1"><?= $totalBorderline ?></div>
            <div class="text-[11px] text-slate-500 mt-0.5">Two-tier review eligible</div>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-xs">
            <div class="text-[11px] font-bold uppercase text-rose-600 tracking-wider">Repeating (&lt; 40%)</div>
            <div class="text-2xl font-black text-rose-700 mt-1"><?= $totalRepeating ?></div>
            <div class="text-[11px] text-slate-500 mt-0.5">Retain in current level</div>
        </div>
    </div>

    <!-- Cohort Classes Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="p-5 border-b border-slate-100 flex items-center justify-between gap-4">
            <div>
                <h3 class="font-bold text-slate-900 text-sm">Class Cohorts &bull; Academic Year Standings</h3>
                <p class="text-xs text-slate-500">Select a class to review the student cumulative matrix, manage borderline repeats, and advance students</p>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="bg-slate-50/80 border-b border-slate-200 text-slate-700 font-bold uppercase tracking-wider text-[11px]">
                        <th class="py-3 px-4">Class &amp; Level</th>
                        <th class="py-3 px-3">Type</th>
                        <th class="py-3 px-3 text-center">Enrolled</th>
                        <th class="py-3 px-3 text-center">Promoting</th>
                        <th class="py-3 px-3 text-center">Graduating</th>
                        <th class="py-3 px-3 text-center">Borderline</th>
                        <th class="py-3 px-3 text-center">Repeating</th>
                        <th class="py-3 px-3 text-center">Advancement Status</th>
                        <th class="py-3 px-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium text-slate-800">
                    <?php if (empty($cohortClasses)): ?>
                        <tr>
                            <td colspan="9" class="py-8 text-center text-slate-400 italic">
                                No class cohorts found for this academic session.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($cohortClasses as $cRow): ?>
                            <?php
                            $c = $cRow['class'];
                            $isTerminal = (bool)($cRow['is_terminal'] ?? false);
                            $isFinalized = (bool)($cRow['is_finalized'] ?? false);
                            ?>
                            <tr class="hover:bg-slate-50/60 transition-colors">
                                <td class="py-3.5 px-4 font-bold text-slate-900">
                                    <div class="flex items-center gap-2">
                                        <span class="text-sm"><?= htmlspecialchars($c->name) ?></span>
                                        <?php if ($isTerminal): ?>
                                            <span class="bg-purple-100 text-purple-800 text-[10px] font-black uppercase px-2 py-0.5 rounded-md">
                                                Terminal Class
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="text-[11px] text-slate-500 font-normal">
                                        <?= htmlspecialchars($cRow['level_name'] ?? 'Academic Level') ?>
                                    </div>
                                </td>
                                <td class="py-3.5 px-3">
                                    <?php if ($isTerminal): ?>
                                        <span class="text-blue-700 font-bold">Graduation Track</span>
                                    <?php else: ?>
                                        <span class="text-slate-600">Standard Sequential</span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3.5 px-3 text-center font-bold text-slate-900">
                                    <?= (int)($cRow['students_count'] ?? $cRow['student_count'] ?? 0) ?>
                                </td>
                                <td class="py-3.5 px-3 text-center font-bold text-emerald-700">
                                    <?= (int)($cRow['promoted_count'] ?? $cRow['promoted'] ?? 0) ?>
                                </td>
                                <td class="py-3.5 px-3 text-center font-bold text-blue-700">
                                    <?= (int)($cRow['graduated_count'] ?? $cRow['graduated'] ?? 0) ?>
                                </td>
                                <td class="py-3.5 px-3 text-center font-bold text-amber-700">
                                    <?= (int)($cRow['borderline_count'] ?? 0) ?>
                                </td>
                                <td class="py-3.5 px-3 text-center font-bold text-rose-700">
                                    <?= (int)($cRow['repeating_count'] ?? $cRow['repeating'] ?? 0) ?>
                                </td>
                                <td class="py-3.5 px-3 text-center">
                                    <?php if ($isFinalized): ?>
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-100 text-emerald-800">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                            Committed
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold bg-slate-100 text-slate-600">
                                            <?= $isPublished ? 'Ready to Commit' : 'Standing Preview' ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3.5 px-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="/admin/promotions/class/<?= (int)$c->id ?>?session_id=<?= $selectedSessionId ?>" 
                                           class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-brand-700 hover:bg-brand-800 text-white font-bold text-xs shadow-xs transition">
                                            <span>Evaluate Cohort</span>
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
