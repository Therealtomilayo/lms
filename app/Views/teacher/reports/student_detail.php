<?php
/**
 * Teacher Individual Student Learning Progression Detail View (Phase 7)
 * Strictly follows .ai/08-ui-design-system.md: flat, accessible, clean white surfaces.
 */
$st = $student;
$stName = htmlspecialchars($st->user?->name ?? "Student #{$st->id}", ENT_QUOTES, 'UTF-8');
$admNo = htmlspecialchars($st->admissionNumber ?? 'N/A', ENT_QUOTES, 'UTF-8');
$email = htmlspecialchars($st->user?->email ?? '', ENT_QUOTES, 'UTF-8');

$subName = htmlspecialchars($classSubject->subject?->name ?? ($classSubject->subjectName ?? 'Subject'), ENT_QUOTES, 'UTF-8');
$clsName = htmlspecialchars($classSubject->schoolClass?->name ?? ($classSubject->className ?? 'Class'), ENT_QUOTES, 'UTF-8');
$secArm = !empty($classSubject->schoolClass?->sectionArm) ? ' (' . htmlspecialchars($classSubject->schoolClass->sectionArm, ENT_QUOTES, 'UTF-8') . ')' : '';

$lp = $learningPath ?? [];
$modules = $lp['modules'] ?? [];
$courseProgress = (float)($lp['course_progress_percent'] ?? 0.0);
$totalReq = (int)($lp['total_required_items'] ?? 0);
$compReq = (int)($lp['total_completed_items'] ?? 0);
$isComp = (bool)($lp['is_course_completed'] ?? false);
?>

<div class="space-y-6">
    <!-- Breadcrumb & Student Header Card -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <nav class="flex items-center gap-2 text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">
                    <a href="/teacher/dashboard" class="text-slate-400 hover:text-sky-600 transition">Faculty Portal</a>
                    <span class="text-slate-300">/</span>
                    <a href="/teacher/subjects/<?= (int)$classSubject->id ?>/progress" class="text-slate-400 hover:text-sky-600 transition">Cohort Report</a>
                    <span class="text-slate-300">/</span>
                    <span class="text-slate-700">Student Progress Inspection</span>
                </nav>
                <div class="flex items-center gap-3 flex-wrap">
                    <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight leading-tight">
                        <?= $stName ?>
                    </h1>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-mono font-bold bg-slate-100 text-slate-700 border border-slate-200">
                        <?= $admNo ?>
                    </span>
                    <?php if ($email): ?>
                        <span class="text-xs text-slate-500 font-medium"><?= $email ?></span>
                    <?php endif; ?>
                </div>
                <p class="text-xs text-slate-500 mt-1">
                    Course: <span class="font-semibold text-slate-700"><?= $subName ?> &mdash; <?= $clsName ?><?= $secArm ?></span>
                </p>
            </div>

            <!-- Back to Cohort CTA -->
            <div class="flex items-center gap-3 shrink-0">
                <a href="/teacher/subjects/<?= (int)$classSubject->id ?>/progress"
                   class="px-4 py-2.5 rounded-xl border border-slate-300 text-xs font-bold text-slate-700 hover:bg-slate-50 transition inline-flex items-center gap-2 shadow-2xs">
                    <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Back to Cohort Report
                </a>
            </div>
        </div>
    </div>

    <!-- Overview Stats & Resume Target Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <!-- Overall Progress Card -->
        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs flex flex-col justify-between">
            <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Subject Completion</span>
            <div class="mt-3">
                <div class="flex items-baseline justify-between">
                    <span class="text-3xl font-black text-slate-900"><?= number_format($courseProgress, 1) ?>%</span>
                    <span class="text-xs font-bold px-2.5 py-0.5 rounded-full border <?= $isComp ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : ($courseProgress > 0 ? 'bg-amber-50 text-amber-700 border-amber-200' : 'bg-slate-100 text-slate-600 border-slate-200') ?>">
                        <?= $isComp ? 'Completed' : ($courseProgress > 0 ? 'In Progress' : 'Not Started') ?>
                    </span>
                </div>
                <div class="w-full bg-slate-100 rounded-full h-2 mt-3 overflow-hidden border border-slate-200/60">
                    <div class="<?= $isComp ? 'bg-emerald-600' : ($courseProgress > 0 ? 'bg-amber-500' : 'bg-slate-300') ?> h-2 rounded-full transition-all duration-300"
                         style="width: <?= min(100, max(0, $courseProgress)) ?>%"></div>
                </div>
                <p class="text-2xs text-slate-400 mt-2">
                    Completed <strong><?= $compReq ?></strong> of <strong><?= $totalReq ?></strong> required learning activities
                </p>
            </div>
        </div>

        <!-- Current Learning Position / Resume Card -->
        <div class="md:col-span-2 bg-white rounded-2xl border border-slate-200 p-5 shadow-xs flex flex-col justify-between">
            <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Active Learning Position</span>
            <div class="mt-3">
                <?php if ($resumeTarget): ?>
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 p-3.5 rounded-xl bg-sky-50/50 border border-sky-200">
                        <div>
                            <span class="inline-flex items-center gap-1.5 text-2xs font-bold uppercase tracking-wider text-sky-700">
                                <span class="w-1.5 h-1.5 rounded-full bg-sky-600"></span>
                                <?= $resumeTarget['type'] === 'resume' ? 'Currently In Progress' : 'Next Sequential Activity' ?>
                            </span>
                            <h4 class="font-bold text-slate-900 text-sm mt-0.5">
                                <?= htmlspecialchars($resumeTarget['title'], ENT_QUOTES, 'UTF-8') ?>
                            </h4>
                            <p class="text-2xs text-slate-500 mt-0.5">
                                Module: <span class="font-semibold text-slate-700"><?= htmlspecialchars($resumeTarget['module_title'], ENT_QUOTES, 'UTF-8') ?></span>
                                <?php if (!empty($resumeTarget['last_page'])): ?>
                                    &bull; Current Page: <strong class="text-slate-800"><?= (int)$resumeTarget['last_page'] ?></strong><?= !empty($resumeTarget['total_pages']) ? ' of ' . (int)$resumeTarget['total_pages'] : '' ?>
                                <?php endif; ?>
                            </p>
                        </div>
                        <span class="inline-flex items-center px-3 py-1 rounded-lg text-xs font-bold bg-sky-600 text-white shadow-xs shrink-0 self-start sm:self-auto">
                            <?= htmlspecialchars($resumeTarget['label'], ENT_QUOTES, 'UTF-8') ?>
                        </span>
                    </div>
                <?php elseif ($isComp): ?>
                    <div class="p-3.5 rounded-xl bg-emerald-50/60 border border-emerald-200 flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-emerald-100 flex items-center justify-center shrink-0 text-emerald-700">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                        <div>
                            <h4 class="font-bold text-emerald-900 text-xs">Course Completed</h4>
                            <p class="text-2xs text-emerald-700">Student has fulfilled all required activities across published course modules.</p>
                        </div>
                    </div>
                <?php else: ?>
                    <p class="text-xs text-slate-500 italic">No activity currently started or unlocked.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Module-by-Module Progression Breakdown -->
    <div class="space-y-4">
        <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider">
            Module Breakdown
        </h2>

        <?php if (empty($modules)): ?>
            <div class="bg-white rounded-2xl border border-slate-200 p-8 text-center text-xs text-slate-500">
                No modules published for this subject offering.
            </div>
        <?php else: ?>
            <?php foreach ($modules as $module): ?>
                <?php
                    $mTitle = htmlspecialchars($module->title, ENT_QUOTES, 'UTF-8');
                    $mDesc = !empty($module->description) ? htmlspecialchars($module->description, ENT_QUOTES, 'UTF-8') : '';
                    $mPct = (float)$module->progressPercent;
                    $mStatus = $module->computedStatus ?? 'Not Started';

                    $mBadgeClass = match ($mStatus) {
                        'Completed' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                        'In Progress' => 'bg-amber-50 text-amber-700 border-amber-200',
                        'Locked' => 'bg-rose-50 text-rose-700 border-rose-200',
                        default => 'bg-slate-100 text-slate-600 border-slate-200',
                    };
                ?>
                <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
                    <!-- Module Header -->
                    <div class="p-4 sm:p-5 border-b border-slate-100 bg-slate-50/50 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div class="space-y-1 flex-1">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="px-2 py-0.5 rounded text-2xs font-mono font-bold bg-slate-200 text-slate-700">
                                    Unit <?= (int)$module->sequenceOrder ?>
                                </span>
                                <h3 class="font-bold text-slate-900 text-sm">
                                    <?= $mTitle ?>
                                </h3>
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-2xs font-bold border <?= $mBadgeClass ?>">
                                    <?= $mStatus ?>
                                </span>
                            </div>
                            <?php if ($mDesc): ?>
                                <p class="text-xs text-slate-500 line-clamp-1"><?= $mDesc ?></p>
                            <?php endif; ?>
                        </div>

                        <!-- Module Progress Bar -->
                        <div class="w-full sm:w-48 space-y-1 shrink-0">
                            <div class="flex items-center justify-between text-2xs text-slate-500 font-medium">
                                <span><?= (int)$module->completedItemsCount ?> of <?= (int)$module->totalItemsCount ?> items</span>
                                <span class="font-bold text-slate-800"><?= number_format($mPct, 1) ?>%</span>
                            </div>
                            <div class="w-full bg-slate-200 rounded-full h-1.5 overflow-hidden">
                                <div class="<?= $mStatus === 'Completed' ? 'bg-emerald-600' : 'bg-sky-600' ?> h-1.5 rounded-full"
                                     style="width: <?= min(100, max(0, $mPct)) ?>%"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Module Items Table -->
                    <?php if (empty($module->items)): ?>
                        <div class="p-6 text-center text-xs text-slate-400 italic">
                            No activities currently assigned to this module.
                        </div>
                    <?php else: ?>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-xs text-slate-700">
                                <thead class="bg-slate-50/70 text-slate-400 font-bold uppercase tracking-wider border-b border-slate-100 text-2xs">
                                    <tr>
                                        <th class="py-2.5 px-5">Activity</th>
                                        <th class="py-2.5 px-3">Type</th>
                                        <th class="py-2.5 px-3">Rule</th>
                                        <th class="py-2.5 px-3">Status</th>
                                        <th class="py-2.5 px-4 w-36">Progress</th>
                                        <th class="py-2.5 px-5 text-right">Completed At</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <?php foreach ($module->items as $item): ?>
                                        <?php
                                            $iTitle = htmlspecialchars($item->title ?? "Activity #{$item->activityId}", ENT_QUOTES, 'UTF-8');
                                            $iType = htmlspecialchars(strtoupper($item->itemSubtype ?? $item->activityType), ENT_QUOTES, 'UTF-8');
                                            $iReq = $item->isRequired ? 'Required' : 'Optional';
                                            $iStatus = $item->statusState ?? 'not_started';
                                            $iPct = (float)$item->progressPercent;
                                            $completedAt = !empty($item->completedAt) ? date('M j, Y g:i A', strtotime($item->completedAt)) : '&mdash;';

                                            $statusBadge = match ($iStatus) {
                                                'completed' => '<span class="inline-flex items-center px-2 py-0.5 rounded text-2xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">Completed</span>',
                                                'in_progress' => '<span class="inline-flex items-center px-2 py-0.5 rounded text-2xs font-bold bg-amber-50 text-amber-700 border border-amber-200">In Progress</span>',
                                                'locked' => '<span class="inline-flex items-center px-2 py-0.5 rounded text-2xs font-bold bg-rose-50 text-rose-700 border border-rose-200">Locked</span>',
                                                default => '<span class="inline-flex items-center px-2 py-0.5 rounded text-2xs font-bold bg-slate-100 text-slate-600 border border-slate-200">Not Started</span>',
                                            };
                                        ?>
                                        <tr class="hover:bg-slate-50/50 transition">
                                            <td class="py-3 px-5 font-semibold text-slate-900 break-words">
                                                <?= $iTitle ?>
                                                <?php if (!$item->isUnlocked && !empty($item->unmetPrerequisites)): ?>
                                                    <div class="text-2xs text-rose-600 font-normal mt-0.5">
                                                        Requires completion of: <?= htmlspecialchars(implode(', ', array_map(fn($p) => $p['title'] ?? 'Prerequisite', $item->unmetPrerequisites)), ENT_QUOTES, 'UTF-8') ?>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td class="py-3 px-3 whitespace-nowrap">
                                                <span class="px-2 py-0.5 rounded text-2xs font-mono font-bold bg-slate-100 text-slate-600">
                                                    <?= $iType ?>
                                                </span>
                                            </td>
                                            <td class="py-3 px-3 whitespace-nowrap text-2xs">
                                                <span class="<?= $item->isRequired ? 'text-slate-800 font-bold' : 'text-slate-400 font-medium' ?>">
                                                    <?= $iReq ?>
                                                </span>
                                            </td>
                                            <td class="py-3 px-3 whitespace-nowrap">
                                                <?= $statusBadge ?>
                                            </td>
                                            <td class="py-3 px-4">
                                                <div class="flex items-center gap-2">
                                                    <div class="flex-1 bg-slate-100 rounded-full h-1.5 overflow-hidden">
                                                        <div class="<?= $iStatus === 'completed' ? 'bg-emerald-600' : 'bg-sky-500' ?> h-1.5 rounded-full"
                                                             style="width: <?= min(100, max(0, $iPct)) ?>%"></div>
                                                    </div>
                                                    <span class="text-2xs font-bold text-slate-700 whitespace-nowrap w-9 text-right">
                                                        <?= number_format($iPct, 0) ?>%
                                                    </span>
                                                </div>
                                            </td>
                                            <td class="py-3 px-5 text-right whitespace-nowrap text-2xs text-slate-500">
                                                <?= $completedAt ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
