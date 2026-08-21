<?php
/**
 * ADMIN-19 — Attendance Analytics & Report
 *
 * Layout is declared by the controller via:
 *   $this->render('admin/attendance/report', $data, 'layouts/admin')
 *
 * Available variables:
 *   $classes         — array of class objects (each has ->id, ->name)
 *   $terms           — array of term objects (each has ->id, ->name)
 *   $selectedClassId — int
 *   $selectedTermId  — int
 *   $startDate       — ?string 'Y-m-d'
 *   $endDate         — ?string 'Y-m-d'
 *   $reportData      — array of associative arrays with keys:
 *                        date, total_students, present_count, late_count, absent_count, excused_count
 *
 * Do NOT call $this->layout() here — it is already injected by the controller.
 */

// Prepare options for select components
$classOptions = [];
foreach ($classes as $cls) {
    $classOptions[$cls->id] = $cls->name;
}

$termOptions = [];
foreach ($terms as $t) {
    $termOptions[$t->id] = $t->name;
}

// Compute aggregate metrics
$totalDays = count($reportData);
$totalStudentsSum = 0;
$presentSum = 0;
$lateSum = 0;
$absentSum = 0;
$excusedSum = 0;

foreach ($reportData as $row) {
    $totalStudentsSum += (int)$row['total_students'];
    $presentSum += (int)$row['present_count'];
    $lateSum += (int)$row['late_count'];
    $absentSum += (int)$row['absent_count'];
    $excusedSum += (int)$row['excused_count'];
}

$overallAttended = $presentSum + $lateSum;
$overallRate = $totalStudentsSum > 0 ? round(($overallAttended / $totalStudentsSum) * 100, 1) : 0;
?>
<div class="space-y-6">

    <!-- Page Header Card -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4
                bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <a href="/admin/attendance" class="text-xs font-semibold text-slate-400 hover:text-brand-600 transition-colors">
                    Attendance Oversight
                </a>
                <span class="text-xs text-slate-300">/</span>
                <span class="text-xs font-semibold text-brand-600">Analytics &amp; Reports</span>
            </div>
            <h1 class="text-xl font-bold text-slate-900">Attendance Analytics &amp; Report</h1>
            <p class="text-sm text-slate-500 mt-1">
                Review class attendance rates, trends, and aggregate metrics across academic terms.
            </p>
        </div>
        <div class="flex items-center gap-3">
            <?php $this->include('components/button', [
                'href'    => '/admin/attendance',
                'variant' => 'secondary',
                'label'   => 'Attendance Register',
                'icon'    => '<svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>',
            ]); ?>
        </div>
    </div>

    <!-- Filter Bar Card -->
    <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm space-y-4">
        <div class="flex items-center justify-between">
            <h2 class="text-sm font-bold uppercase tracking-wider text-slate-700">Filter Parameters</h2>
            <?php if (!empty($startDate) || !empty($endDate)): ?>
                <a href="/admin/attendance/report?class_id=<?= (int)$selectedClassId ?>&term_id=<?= (int)$selectedTermId ?>" 
                   class="text-xs font-semibold text-brand-600 hover:text-brand-700 transition-colors">
                    Clear Date Filters
                </a>
            <?php endif; ?>
        </div>

        <form method="GET" action="/admin/attendance/report" class="space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Class Selector -->
                <div>
                    <?php $this->include('components/select', [
                        'name'     => 'class_id',
                        'label'    => 'Class',
                        'options'  => $classOptions,
                        'selected' => $selectedClassId,
                        'required' => true,
                    ]); ?>
                </div>

                <!-- Academic Term Selector -->
                <div>
                    <?php $this->include('components/select', [
                        'name'     => 'term_id',
                        'label'    => 'Academic Term',
                        'options'  => $termOptions,
                        'selected' => $selectedTermId,
                        'required' => true,
                    ]); ?>
                </div>

                <!-- Start Date -->
                <div>
                    <?php $this->include('components/input', [
                        'name'     => 'start_date',
                        'label'    => 'Start Date',
                        'type'     => 'date',
                        'value'    => $startDate ?? '',
                    ]); ?>
                </div>

                <!-- End Date -->
                <div>
                    <?php $this->include('components/input', [
                        'name'     => 'end_date',
                        'label'    => 'End Date',
                        'type'     => 'date',
                        'value'    => $endDate ?? '',
                    ]); ?>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 pt-2">
                <a href="/admin/attendance/report" class="text-sm font-semibold text-slate-500 hover:text-slate-700 px-4 py-2">
                    Reset
                </a>
                <?php $this->include('components/button', [
                    'type'    => 'submit',
                    'variant' => 'primary',
                    'label'   => 'Generate Report',
                    'icon'    => '<svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>',
                ]); ?>
            </div>
        </form>
    </div>

    <!-- KPI Aggregate Stats -->
    <?php if (!empty($reportData)): ?>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Overall Rate Card -->
            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Attendance Rate</span>
                    <?php $this->include('components/badge', [
                        'label'   => $overallRate >= 80 ? 'Good' : ($overallRate >= 60 ? 'Moderate' : 'Low'),
                        'variant' => $overallRate >= 80 ? 'success' : ($overallRate >= 60 ? 'warning' : 'danger'),
                    ]); ?>
                </div>
                <div class="mt-3">
                    <span class="text-3xl font-bold font-mono text-slate-900"><?= $overallRate ?>%</span>
                    <p class="text-xs text-slate-400 mt-1"><?= number_format($overallAttended) ?> of <?= number_format($totalStudentsSum) ?> student days</p>
                </div>
            </div>

            <!-- Recorded Days Card -->
            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm flex flex-col justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500">School Days Logged</span>
                <div class="mt-3">
                    <span class="text-3xl font-bold font-mono text-slate-900"><?= $totalDays ?></span>
                    <p class="text-xs text-slate-400 mt-1">Recorded register sessions</p>
                </div>
            </div>

            <!-- Present vs Late Card -->
            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm flex flex-col justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Present &amp; Late</span>
                <div class="mt-3 flex items-baseline gap-3">
                    <div>
                        <span class="text-2xl font-bold font-mono text-emerald-600"><?= number_format($presentSum) ?></span>
                        <span class="text-xs text-slate-400 block">Present</span>
                    </div>
                    <span class="text-slate-300">/</span>
                    <div>
                        <span class="text-2xl font-bold font-mono text-amber-600"><?= number_format($lateSum) ?></span>
                        <span class="text-xs text-slate-400 block">Late</span>
                    </div>
                </div>
            </div>

            <!-- Absent vs Excused Card -->
            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm flex flex-col justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Absent &amp; Excused</span>
                <div class="mt-3 flex items-baseline gap-3">
                    <div>
                        <span class="text-2xl font-bold font-mono text-rose-600"><?= number_format($absentSum) ?></span>
                        <span class="text-xs text-slate-400 block">Absent</span>
                    </div>
                    <span class="text-slate-300">/</span>
                    <div>
                        <span class="text-2xl font-bold font-mono text-sky-600"><?= number_format($excusedSum) ?></span>
                        <span class="text-xs text-slate-400 block">Excused</span>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Report Log Matrix Table -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-4 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <h2 class="font-bold text-sm text-slate-800">Daily Attendance Log Matrix</h2>
                <?php if (!empty($reportData)): ?>
                    <?php $this->include('components/badge', [
                        'label'   => count($reportData) . ' Sessions',
                        'variant' => 'neutral',
                    ]); ?>
                <?php endif; ?>
            </div>
        </div>

        <?php if (empty($reportData)): ?>
            <div class="p-8">
                <?php $this->include('components/empty_state', [
                    'title'   => 'No Attendance Records Found',
                    'message' => 'No attendance entries match the selected class, term, or date range parameters. Try broadening your date filter or take attendance for this class.',
                    'actionUrl'   => '/admin/attendance',
                    'actionLabel' => 'Go to Attendance Register',
                ]); ?>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-slate-700">
                    <thead class="bg-slate-50 border-b border-slate-200 text-xs font-semibold uppercase tracking-wider text-slate-500">
                        <tr>
                            <th class="py-3.5 px-4">Date</th>
                            <th class="py-3.5 px-4 text-center">Roster Size</th>
                            <th class="py-3.5 px-4 text-center">Present</th>
                            <th class="py-3.5 px-4 text-center">Late</th>
                            <th class="py-3.5 px-4 text-center">Absent</th>
                            <th class="py-3.5 px-4 text-center">Excused</th>
                            <th class="py-3.5 px-4 text-center">Daily Rate</th>
                            <th class="py-3.5 px-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php foreach ($reportData as $row): 
                            $total = (int)$row['total_students'];
                            $attended = (int)$row['present_count'] + (int)$row['late_count'];
                            $rate = $total > 0 ? round(($attended / $total) * 100, 1) : 0;
                            $dateStr = (string)$row['date'];
                            $timestamp = strtotime($dateStr);
                            $formattedDate = $timestamp ? date('D, M j, Y', $timestamp) : $dateStr;
                        ?>
                            <tr class="hover:bg-slate-50/75 transition-colors">
                                <td class="py-3.5 px-4 font-semibold text-slate-900">
                                    <div class="flex items-center gap-2">
                                        <span class="font-mono"><?= e($dateStr) ?></span>
                                        <span class="text-xs text-slate-400 font-normal hidden sm:inline">(<?= e($formattedDate) ?>)</span>
                                    </div>
                                </td>
                                <td class="py-3.5 px-4 text-center font-mono font-medium text-slate-700">
                                    <?= $total ?>
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-mono font-semibold bg-emerald-50 text-emerald-700">
                                        <?= (int)$row['present_count'] ?>
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-mono font-semibold bg-amber-50 text-amber-700">
                                        <?= (int)$row['late_count'] ?>
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-mono font-semibold bg-rose-50 text-rose-700">
                                        <?= (int)$row['absent_count'] ?>
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-mono font-semibold bg-sky-50 text-sky-700">
                                        <?= (int)$row['excused_count'] ?>
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    <?php $this->include('components/badge', [
                                        'label'   => $rate . '%',
                                        'variant' => $rate >= 80 ? 'success' : ($rate >= 60 ? 'warning' : 'danger'),
                                    ]); ?>
                                </td>
                                <td class="py-3.5 px-4 text-right">
                                    <a href="/admin/attendance/<?= (int)$selectedClassId ?>/<?= e($dateStr) ?>/edit" 
                                       class="inline-flex items-center gap-1 text-xs font-semibold text-brand-600 hover:text-brand-700 transition-colors p-1 rounded hover:bg-brand-50">
                                        <span>Edit Register</span>
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

</div>
