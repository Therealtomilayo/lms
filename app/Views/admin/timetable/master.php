<?php
/**
 * Master Institutional Timetable Grid View (ADMIN-24)
 * Multi-cohort master schedule matching high school / grade school institutional layouts.
 *
 * @var string $title
 * @var string $headerTitle
 * @var array $terms
 * @var object|null $selectedTerm
 * @var array $structures
 * @var string $stageKey
 * @var int|null $selectedStructureId
 * @var array $masterData
 * @var string $csrf_token
 */

$structure = $masterData['structure'] ?? null;
$classes = $masterData['classes'] ?? [];
$days = $masterData['days'] ?? ['mon', 'tue', 'wed', 'thu', 'fri'];
$periods = $masterData['periods'] ?? [];
$matrix = $masterData['matrix'] ?? [];

$dayLabels = [
    'mon' => 'Monday',
    'tue' => 'Tuesday',
    'wed' => 'Wednesday',
    'thu' => 'Thursday',
    'fri' => 'Friday',
    'sat' => 'Saturday',
    'sun' => 'Sunday',
];
?>

<div class="space-y-6">

    <!-- Top Filter & Action Bar -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-xs p-5 print:hidden">
        <div class="flex flex-col xl:flex-row xl:items-center xl:justify-between gap-4">
            <div>
                <!-- Breadcrumbs -->
                <nav class="flex items-center gap-2 text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1" aria-label="Breadcrumb">
                    <a href="/admin/dashboard" class="hover:text-brand-600 transition-colors">Admin</a>
                    <svg class="w-3 h-3 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                    </svg>
                    <a href="/admin/timetable" class="hover:text-brand-600 transition-colors">Timetable</a>
                    <svg class="w-3 h-3 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                    </svg>
                    <span class="text-brand-600">Master Institutional Grid</span>
                </nav>
                <div class="flex items-center gap-3">
                    <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Master Institutional Timetable</h1>
                    <?php if ($structure): ?>
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold uppercase tracking-wider bg-brand-50 text-brand-700 border border-brand-200">
                            <?= htmlspecialchars($structure->name) ?> (<?= count($structure->periods) ?> blocks)
                        </span>
                    <?php endif; ?>
                </div>
                <p class="text-sm text-slate-500 mt-1">
                    Multi-class synchronized matrix showing periods, institutional breaks, departmental elective splits, and multi-cohort activities.
                </p>
            </div>

            <!-- Filters and Actions -->
            <div class="flex flex-wrap items-center gap-3">
                <form method="GET" action="/admin/timetable/master" id="masterFilterForm" class="flex flex-wrap items-center gap-2.5">
                    <!-- Stage Switcher -->
                    <div class="inline-flex rounded-lg border border-slate-300 p-0.5 bg-slate-100">
                        <a
                            href="/admin/timetable/master?stage=high_school<?= $selectedTerm ? '&term_id=' . (int)$selectedTerm->id : '' ?>"
                            class="px-3 py-1.5 rounded-md text-xs font-bold transition-colors <?= $stageKey === 'high_school' ? 'bg-white text-brand-700 shadow-xs' : 'text-slate-600 hover:text-slate-900' ?>"
                        >
                            High School (9 Pds)
                        </a>
                        <a
                            href="/admin/timetable/master?stage=primary<?= $selectedTerm ? '&term_id=' . (int)$selectedTerm->id : '' ?>"
                            class="px-3 py-1.5 rounded-md text-xs font-bold transition-colors <?= $stageKey === 'primary' ? 'bg-white text-brand-700 shadow-xs' : 'text-slate-600 hover:text-slate-900' ?>"
                        >
                            Primary / Grade School
                        </a>
                    </div>

                    <!-- Term Select -->
                    <div class="relative">
                        <select
                            name="term_id"
                            id="term_id"
                            onchange="this.form.submit()"
                            class="bg-slate-50 border border-slate-300 text-slate-900 text-xs font-semibold rounded-lg px-3 py-2 pr-8 focus:ring-2 focus:ring-brand-500 focus:border-brand-500 transition-colors cursor-pointer appearance-none"
                        >
                            <?php foreach ($terms as $t): ?>
                                <option value="<?= (int)$t->id ?>" <?= $selectedTerm && (int)$selectedTerm->id === (int)$t->id ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($t->name) ?> <?= $t->isActive() ? '★' : '' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-slate-400">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </div>
                    </div>

                    <input type="hidden" name="stage" value="<?= htmlspecialchars($stageKey) ?>">
                </form>

                <!-- Download CSV -->
                <a
                    href="/admin/timetable/template/download<?= $selectedTerm ? '?term_id=' . (int)$selectedTerm->id : '' ?>"
                    class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold transition-colors"
                    title="Download Excel/CSV Template with Elective Split Samples"
                >
                    <svg class="w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                    Download CSV
                </a>

                <!-- Print Button -->
                <button
                    type="button"
                    onclick="window.print()"
                    class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg bg-brand-600 hover:bg-brand-700 text-white text-xs font-bold shadow-xs transition-colors cursor-pointer"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                    </svg>
                    Print Matrix (Landscape)
                </button>
            </div>
        </div>
    </div>

    <!-- Print Header (Visible only when printing) -->
    <div class="hidden print:block text-center border-b-2 border-slate-800 pb-3 mb-4">
        <h2 class="text-xl font-black uppercase tracking-wider text-slate-900">CLARET INTERNATIONAL SCHOOL &bull; MASTER TIMETABLE</h2>
        <div class="flex items-center justify-center gap-6 mt-1 text-xs font-bold text-slate-700">
            <span>Stage: <?= htmlspecialchars(ucwords(str_replace('_', ' ', $stageKey))) ?></span>
            <span>&bull;</span>
            <span>Academic Term: <?= $selectedTerm ? htmlspecialchars($selectedTerm->name) : 'Current' ?></span>
            <span>&bull;</span>
            <span>Structure: <?= $structure ? htmlspecialchars($structure->name) : 'Standard' ?></span>
        </div>
    </div>

    <!-- Master Timetable Grid Card -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full border-collapse text-left text-xs">
                <thead>
                    <tr class="bg-slate-800 text-white divide-x divide-slate-700 text-[11px] font-bold">
                        <th class="py-3 px-3 w-28 text-center sticky left-0 z-20 bg-slate-900 uppercase tracking-wider">Day</th>
                        <th class="py-3 px-3 w-32 sticky left-28 z-20 bg-slate-900 uppercase tracking-wider">Class</th>

                        <?php if (!empty($periods)): ?>
                            <?php foreach ($periods as $p): ?>
                                <?php if ($p->isBreak()): ?>
                                    <th class="py-2.5 px-2 text-center bg-amber-900/40 text-amber-200 min-w-[70px] uppercase font-bold tracking-tight">
                                        <div class="text-[10px] leading-tight"><?= htmlspecialchars($p->periodName) ?></div>
                                        <div class="text-[9px] font-mono text-amber-300 font-normal"><?= substr($p->startTime, 0, 5) ?>–<?= substr($p->endTime, 0, 5) ?></div>
                                    </th>
                                <?php else: ?>
                                    <th class="py-2.5 px-2 text-center bg-slate-800 min-w-[105px]">
                                        <div class="text-[11px] font-bold text-white"><?= htmlspecialchars($p->periodName) ?></div>
                                        <div class="text-[9.5px] font-mono text-slate-300 font-normal"><?= substr($p->startTime, 0, 5) ?>–<?= substr($p->endTime, 0, 5) ?></div>
                                    </th>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <?php for ($i = 1; $i <= 9; $i++): ?>
                                <th class="py-2.5 px-2 text-center bg-slate-800 min-w-[105px]">Period <?= $i ?></th>
                            <?php endfor; ?>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    <?php if (empty($classes)): ?>
                        <tr>
                            <td colspan="<?= count($periods) + 2 ?>" class="py-12 text-center text-slate-400">
                                No classes configured for this academic stage.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($days as $day): ?>
                            <?php 
                            $classCount = count($classes);
                            $firstClass = true;
                            ?>
                            <?php foreach ($classes as $idx => $class): ?>
                                <tr class="hover:bg-slate-50/70 transition-colors <?= $idx === $classCount - 1 ? 'border-b-2 border-slate-300' : 'border-b border-slate-200' ?>">
                                    <?php if ($firstClass): ?>
                                        <td
                                            rowspan="<?= $classCount ?>"
                                            class="py-3 px-3 text-center font-black text-slate-800 uppercase tracking-wider bg-slate-100 border-r border-slate-300 sticky left-0 z-10 align-middle text-xs"
                                        >
                                            <div class="font-extrabold text-slate-900"><?= $dayLabels[$day] ?? strtoupper($day) ?></div>
                                        </td>
                                        <?php $firstClass = false; ?>
                                    <?php endif; ?>

                                    <!-- Class Name Column -->
                                    <td class="py-2.5 px-3 font-bold text-slate-900 bg-white border-r border-slate-300 sticky left-28 z-10 whitespace-nowrap">
                                        <a href="/admin/timetable/<?= (int)$class->id ?>/edit<?= $selectedTerm ? '?term_id=' . (int)$selectedTerm->id : '' ?>" class="hover:text-brand-600 hover:underline">
                                            <?= htmlspecialchars($class->name) ?>
                                        </a>
                                    </td>

                                    <!-- Periods Columns -->
                                    <?php if (!empty($periods)): ?>
                                        <?php foreach ($periods as $p): ?>
                                            <?php if ($p->isBreak()): ?>
                                                <!-- Institutional Block (Break, Assembly, Activities) -->
                                                <td class="p-1 text-center bg-amber-50/60 border-r border-slate-200 align-middle">
                                                    <span class="text-[9px] font-bold uppercase tracking-wider text-amber-800/80">
                                                        <?= htmlspecialchars($p->periodName) ?>
                                                    </span>
                                                </td>
                                            <?php else: ?>
                                                <?php 
                                                $pNum = $p->periodNumber;
                                                $slots = $matrix[$day][$class->id][$pNum] ?? [];
                                                ?>
                                                <td class="p-1.5 border-r border-slate-200 align-top min-w-[105px] max-w-[140px]">
                                                    <?php if (empty($slots)): ?>
                                                        <div class="h-10 flex items-center justify-center text-slate-300 text-[10px] italic">
                                                            &mdash;
                                                        </div>
                                                    <?php else: ?>
                                                        <div class="space-y-1">
                                                            <?php foreach ($slots as $slot): ?>
                                                                <?php 
                                                                $isSplit = !empty($slot->department);
                                                                $badgeBg = 'bg-blue-50 border-blue-200 text-blue-900';
                                                                if ($slot->slotType === 'sport') {
                                                                    $badgeBg = 'bg-emerald-50 border-emerald-300 text-emerald-900';
                                                                } elseif ($slot->slotType === 'activity') {
                                                                    $badgeBg = 'bg-purple-50 border-purple-200 text-purple-900';
                                                                } elseif ($isSplit) {
                                                                    $deptLower = strtolower($slot->department);
                                                                    if (str_contains($deptLower, 'sci')) {
                                                                        $badgeBg = 'bg-teal-50 border-teal-300 text-teal-950';
                                                                    } elseif (str_contains($deptLower, 'com')) {
                                                                        $badgeBg = 'bg-amber-50 border-amber-300 text-amber-950';
                                                                    } elseif (str_contains($deptLower, 'art')) {
                                                                        $badgeBg = 'bg-rose-50 border-rose-300 text-rose-950';
                                                                    }
                                                                }
                                                                ?>
                                                                <div class="p-1.5 rounded-md border <?= $badgeBg ?> text-[10px] leading-tight shadow-2xs">
                                                                    <div class="font-bold flex items-center justify-between gap-1">
                                                                        <span class="truncate">
                                                                            <?= htmlspecialchars($slot->customTitle ?: ($slot->subjectCode ?: $slot->subjectName)) ?>
                                                                        </span>
                                                                        <?php if (!empty($slot->department)): ?>
                                                                            <span class="text-[8px] font-black uppercase px-1 py-0.2 rounded bg-white/80 border border-slate-200">
                                                                                <?= htmlspecialchars(substr($slot->department, 0, 3)) ?>
                                                                            </span>
                                                                        <?php endif; ?>
                                                                    </div>
                                                                    <?php if (!empty($slot->teacherName) || !empty($slot->room)): ?>
                                                                        <div class="text-[9px] text-slate-600 font-medium truncate mt-0.5">
                                                                            <?= htmlspecialchars($slot->room ?: '') ?>
                                                                            <?php if (!empty($slot->room) && !empty($slot->teacherName)): ?>&bull;<?php endif; ?>
                                                                            <?= htmlspecialchars(substr($slot->teacherName ?? '', 0, 10)) ?>
                                                                        </div>
                                                                    <?php endif; ?>
                                                                </div>
                                                            <?php endforeach; ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </td>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Legend Footer -->
        <div class="p-4 bg-slate-50 border-t border-slate-200 flex flex-wrap items-center justify-between gap-3 text-xs text-slate-600 print:hidden">
            <div class="flex flex-wrap items-center gap-4">
                <span class="font-bold uppercase tracking-wider text-slate-500 text-[10px]">Key Legend:</span>
                <span class="flex items-center gap-1.5">
                    <span class="w-3 h-3 rounded bg-blue-100 border border-blue-300"></span> Core Instructional
                </span>
                <span class="flex items-center gap-1.5">
                    <span class="w-3 h-3 rounded bg-teal-100 border border-teal-300"></span> Science Elective
                </span>
                <span class="flex items-center gap-1.5">
                    <span class="w-3 h-3 rounded bg-amber-100 border border-amber-300"></span> Commercial Elective
                </span>
                <span class="flex items-center gap-1.5">
                    <span class="w-3 h-3 rounded bg-rose-100 border border-rose-300"></span> Arts Elective
                </span>
                <span class="flex items-center gap-1.5">
                    <span class="w-3 h-3 rounded bg-emerald-100 border border-emerald-300"></span> Sport / Activity
                </span>
                <span class="flex items-center gap-1.5">
                    <span class="w-3 h-3 rounded bg-amber-50 border border-amber-200"></span> Institutional Break / Assembly
                </span>
            </div>
            <div class="text-slate-400 text-[11px]">
                Showing <?= count($classes) ?> class cohort(s) &bull; Claret Secure Timetable Engine
            </div>
        </div>
    </div>

</div>

<style>
@media print {
    @page {
        size: landscape;
        margin: 6mm;
    }
    body {
        background: white !important;
        font-size: 8pt !important;
    }
    table {
        font-size: 7.5pt !important;
    }
}
</style>
