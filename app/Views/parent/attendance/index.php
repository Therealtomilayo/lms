<?php
/**
 * Parent Child Attendance Monitoring View
 * Phase UI-0 Modernization following .ai/08-ui-design-system.md
 * 
 * @var array $children
 * @var object|array $selectedChild
 * @var int $selectedTermId
 * @var array|null $summary
 * @var array $history
 * @var array $terms
 * @var \App\Core\UserContext $user
 */

$childId = is_object($selectedChild) ? (int)$selectedChild->id : (int)($selectedChild['id'] ?? 0);
$childName = is_object($selectedChild) ? $selectedChild->name : ($selectedChild['name'] ?? 'Student');
$admissionNumber = is_object($selectedChild) ? ($selectedChild->admissionNumber ?? '') : ($selectedChild['admission_number'] ?? '');
$className = is_object($selectedChild) 
    ? ($selectedChild->className ?: ($selectedChild->currentClass?->name ?: 'JSS 1A'))
    : ($selectedChild['class_name'] ?? 'JSS 1A');

$rate = isset($summary['attendance_rate']) ? (float)$summary['attendance_rate'] : 100.0;
$totalDays = isset($summary['total_days']) ? (int)$summary['total_days'] : 0;
$presentDays = isset($summary['present_days']) ? (int)$summary['present_days'] : 0;
$lateDays = isset($summary['late_days']) ? (int)$summary['late_days'] : 0;
$absentDays = isset($summary['absent_days']) ? (int)$summary['absent_days'] : 0;
$excusedDays = isset($summary['excused_days']) ? (int)$summary['excused_days'] : 0;
?>

<div class="space-y-6">

    <!-- 1. HEADER CARD WITH BREADCRUMBS, STUDENT INFO & QUICK TOOLBAR -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6">
            
            <!-- Left: Student Particulars -->
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 rounded-2xl bg-brand-700 text-white font-extrabold text-2xl flex items-center justify-center shadow-xs flex-shrink-0">
                    <?= strtoupper(substr($childName, 0, 1)) ?>
                </div>
                <div>
                    <!-- Breadcrumbs -->
                    <div class="flex items-center gap-2 text-xs font-semibold text-slate-400 mb-1">
                        <a href="/parent/dashboard" class="hover:text-brand-600 transition">Dashboard</a>
                        <span>&rsaquo;</span>
                        <a href="/parent/children/<?= $childId ?>" class="hover:text-brand-600 transition">Child Profile</a>
                        <span>&rsaquo;</span>
                        <span class="text-slate-700 font-bold">Daily Attendance</span>
                    </div>

                    <div class="flex flex-wrap items-center gap-2.5">
                        <h1 class="text-xl font-bold text-slate-900 tracking-tight">
                            <?= htmlspecialchars($childName) ?>
                        </h1>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-brand-50 text-brand-700 border border-brand-200">
                            <?= htmlspecialchars($className) ?>
                        </span>
                        <?php if (!empty($admissionNumber)): ?>
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-slate-100 text-slate-700 font-mono">
                                Adm: <?= htmlspecialchars($admissionNumber) ?>
                            </span>
                        <?php endif; ?>
                    </div>
                    <p class="text-xs text-slate-500 mt-1">
                        Daily roll-call records, punctuality tracking, and terminal attendance reliability.
                    </p>
                </div>
            </div>

            <!-- Right: Quick Navigation Toolbar -->
            <div class="flex flex-wrap items-center gap-2">
                <a href="/parent/children/<?= $childId ?>" 
                   class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-white hover:bg-slate-50 text-slate-700 text-xs font-bold rounded-xl border border-slate-300 shadow-xs transition">
                    <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    <span>Child Profile</span>
                </a>

                <a href="/parent/children/<?= $childId ?>/grades" 
                   class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-white hover:bg-slate-50 text-slate-700 text-xs font-bold rounded-xl border border-slate-300 shadow-xs transition">
                    <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <span>Grades & Reports</span>
                </a>

                <a href="/parent/children/<?= $childId ?>/assignments" 
                   class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-white hover:bg-slate-50 text-slate-700 text-xs font-bold rounded-xl border border-slate-300 shadow-xs transition">
                    <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                    <span>Coursework</span>
                </a>

                <a href="/parent/children/<?= $childId ?>/timetable" 
                   class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-white hover:bg-slate-50 text-slate-700 text-xs font-bold rounded-xl border border-slate-300 shadow-xs transition">
                    <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    <span>Timetable</span>
                </a>
            </div>

        </div>
    </div>

    <!-- 2. MULTI-CHILD SELECTOR (IF MULTIPLE WARDS LINKED) -->
    <?php if (count($children) > 1): ?>
        <div class="flex items-center gap-2 border-b border-slate-200 pb-3 overflow-x-auto">
            <span class="text-xs font-bold uppercase tracking-wider text-slate-400 mr-1 flex-shrink-0">Viewing Ward:</span>
            <?php foreach ($children as $c): 
                $cId = is_object($c) ? (int)$c->id : (int)($c['id'] ?? 0);
                $cName = is_object($c) ? $c->name : ($c['name'] ?? '');
                $isActiveChild = ($childId === $cId);
            ?>
                <a href="/parent/children/<?= $cId ?>/attendance<?= $selectedTermId > 0 ? "?term_id={$selectedTermId}" : '' ?>"
                   class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-xl text-xs font-bold transition flex-shrink-0 <?= $isActiveChild ? 'bg-brand-700 text-white shadow-xs' : 'bg-white text-slate-700 hover:bg-slate-100 border border-slate-200' ?>">
                    <span class="w-2 h-2 rounded-full <?= $isActiveChild ? 'bg-emerald-400' : 'bg-slate-300' ?>"></span>
                    <span><?= htmlspecialchars($cName) ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- 3. TERM SELECTOR & ATTENDANCE STATUS BAR -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        
        <!-- Term Dropdown -->
        <div class="flex items-center gap-3">
            <label for="term_id" class="text-xs font-bold uppercase tracking-wider text-slate-500 flex-shrink-0">
                Academic Term:
            </label>
            <?php if (!empty($terms)): ?>
                <form method="GET" action="/parent/children/<?= $childId ?>/attendance" class="inline-block">
                    <input type="hidden" name="student_id" value="<?= $childId ?>">
                    <select id="term_id" name="term_id" onchange="this.form.submit()" 
                            class="bg-slate-50 border border-slate-300 text-slate-800 text-xs font-bold rounded-xl px-3.5 py-2.5 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 cursor-pointer shadow-xs">
                        <?php foreach ($terms as $t): ?>
                            <option value="<?= (int)$t->id ?>" <?= $selectedTermId === (int)$t->id ? 'selected' : '' ?>>
                                <?= htmlspecialchars($t->name) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>
            <?php else: ?>
                <span class="text-xs font-bold text-slate-700">Current Academic Term</span>
            <?php endif; ?>
        </div>

        <!-- Standing / Compliance Badge -->
        <div>
            <?php if ($rate >= 90): ?>
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span>Excellent Standing (<?= number_format($rate, 1) ?>%)</span>
                </span>
            <?php elseif ($rate >= 75): ?>
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold bg-sky-50 text-sky-700 border border-sky-200">
                    <span class="w-2 h-2 rounded-full bg-sky-500"></span>
                    <span>Good Standing (<?= number_format($rate, 1) ?>%)</span>
                </span>
            <?php else: ?>
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold bg-rose-50 text-rose-700 border border-rose-200">
                    <span class="w-2 h-2 rounded-full bg-rose-500 animate-ping"></span>
                    <span>Below Minimum (<?= number_format($rate, 1) ?>% &lt; 75%)</span>
                </span>
            <?php endif; ?>
        </div>

    </div>

    <!-- 4. 4-CARD OVERVIEW STATS STRIP -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        
        <!-- Attendance Rate % Card -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Attendance Rate</span>
                <span class="p-2 rounded-xl bg-brand-50 text-brand-700">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                </span>
            </div>
            <div class="mt-3">
                <div class="text-2xl font-extrabold <?= $rate >= 75 ? 'text-slate-900' : 'text-rose-600' ?>">
                    <?= number_format($rate, 1) ?>%
                </div>
                <!-- Progress Bar -->
                <div class="w-full bg-slate-100 rounded-full h-1.5 mt-2 overflow-hidden">
                    <div class="h-full rounded-full transition-all duration-500 <?= $rate >= 90 ? 'bg-emerald-500' : ($rate >= 75 ? 'bg-sky-500' : 'bg-rose-500') ?>" 
                         style="width: <?= min(100, max(0, $rate)) ?>%;"></div>
                </div>
                <div class="flex items-center justify-between text-[11px] text-slate-500 mt-1.5">
                    <span><?= $presentDays + $lateDays ?> of <?= $totalDays ?> sessions</span>
                    <span class="font-medium">Min: 75%</span>
                </div>
            </div>
        </div>

        <!-- Present Days Card -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Present Sessions</span>
                <span class="p-2 rounded-xl bg-emerald-50 text-emerald-700">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </span>
            </div>
            <div class="mt-3">
                <div class="text-2xl font-extrabold text-emerald-700">
                    <?= $presentDays ?>
                </div>
                <p class="text-xs text-slate-500 mt-1">
                    On-time attendance records
                </p>
            </div>
        </div>

        <!-- Late Arrivals Card -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Late Arrivals</span>
                <span class="p-2 rounded-xl bg-amber-50 text-amber-700">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </span>
            </div>
            <div class="mt-3">
                <div class="text-2xl font-extrabold text-amber-700">
                    <?= $lateDays ?>
                </div>
                <p class="text-xs text-slate-500 mt-1">
                    Punctuality flags recorded
                </p>
            </div>
        </div>

        <!-- Absences Card -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Absences</span>
                <span class="p-2 rounded-xl bg-rose-50 text-rose-700">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </span>
            </div>
            <div class="mt-3">
                <div class="text-2xl font-extrabold text-rose-700">
                    <?= $absentDays ?>
                </div>
                <p class="text-xs text-slate-500 mt-1">
                    <?= $excusedDays > 0 ? "{$excusedDays} excused by note" : 'Unexcused roll-call marks' ?>
                </p>
            </div>
        </div>

    </div>

    <!-- 5. ATTENDANCE LOG / ROLL-CALL HISTORY -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
        
        <!-- Table Header & Filter Tabs -->
        <div class="p-5 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-slate-50/50">
            <div>
                <h3 class="text-base font-bold text-slate-900">
                    Roll-Call Register & Session History
                </h3>
                <p class="text-xs text-slate-500 mt-0.5">
                    Official chronologically ordered attendance logs marked by teachers.
                </p>
            </div>

            <!-- In-Table View Filters -->
            <div class="flex items-center gap-1.5 bg-slate-100 p-1 rounded-xl" role="tablist">
                <button type="button" onclick="filterLogs('all')" id="btn-filter-all"
                        class="log-filter-btn px-3 py-1 rounded-lg text-xs font-bold bg-white text-slate-800 shadow-xs transition">
                    All Logs (<?= count($history) ?>)
                </button>
                <button type="button" onclick="filterLogs('daily')" id="btn-filter-daily"
                        class="log-filter-btn px-3 py-1 rounded-lg text-xs font-bold text-slate-600 hover:text-slate-900 transition">
                    Daily Roll-Call
                </button>
                <button type="button" onclick="filterLogs('subject')" id="btn-filter-subject"
                        class="log-filter-btn px-3 py-1 rounded-lg text-xs font-bold text-slate-600 hover:text-slate-900 transition">
                    Subject Period
                </button>
            </div>
        </div>

        <?php if (empty($history)): ?>
            <!-- Empty State -->
            <div class="p-12 text-center">
                <div class="w-12 h-12 rounded-2xl bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-3">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>
                <h4 class="text-sm font-bold text-slate-900">No Attendance Records Yet</h4>
                <p class="text-xs text-slate-500 max-w-sm mx-auto mt-1">
                    There are no attendance roll-call logs entered for <?= htmlspecialchars($childName) ?> in this academic term.
                </p>
            </div>
        <?php else: ?>
            <!-- Table View -->
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm" id="attendance-log-table">
                    <thead>
                        <tr class="border-b border-slate-200 bg-slate-50/80 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                            <th class="py-3.5 px-5">Date & Day</th>
                            <th class="py-3.5 px-5">Session / Roll-Call Type</th>
                            <th class="py-3.5 px-5">Status</th>
                            <th class="py-3.5 px-5">Recorded By</th>
                            <th class="py-3.5 px-5">Teacher's Note</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php foreach ($history as $rec): 
                            $recDate = is_object($rec) ? $rec->date : ($rec['date'] ?? '');
                            $recStatus = strtolower(is_object($rec) ? $rec->status : ($rec['status'] ?? 'present'));
                            $isDaily = is_object($rec) ? $rec->isDaily() : (empty($rec['class_subject_id']));
                            $periodNo = is_object($rec) ? $rec->periodNumber : ($rec['period_number'] ?? null);
                            $subName = is_object($rec) ? ($rec->subjectName ?? null) : ($rec['subject_name'] ?? null);
                            $markerName = is_object($rec) ? ($rec->markerName ?? 'Subject Teacher') : ($rec['marker_name'] ?? 'Subject Teacher');
                            $remarks = is_object($rec) ? ($rec->remarks ?? null) : ($rec['remarks'] ?? null);

                            $typeClass = $isDaily ? 'log-type-daily' : 'log-type-subject';
                        ?>
                            <tr class="hover:bg-slate-50/80 transition log-row <?= $typeClass ?>">
                                
                                <!-- Date -->
                                <td class="py-4 px-5">
                                    <div class="font-bold text-slate-900 text-xs">
                                        <?= htmlspecialchars(date('l, F j, Y', strtotime($recDate))) ?>
                                    </div>
                                    <span class="text-[11px] text-slate-400 font-mono">
                                        <?= date('d/m/Y', strtotime($recDate)) ?>
                                    </span>
                                </td>

                                <!-- Session Type -->
                                <td class="py-4 px-5">
                                    <?php if ($isDaily): ?>
                                        <div class="flex items-center gap-1.5">
                                            <span class="w-2 h-2 rounded-full bg-brand-600"></span>
                                            <span class="font-bold text-slate-800 text-xs">Daily Roll Call</span>
                                        </div>
                                        <span class="text-[11px] text-slate-500">Morning Assembly / Homeroom</span>
                                    <?php else: ?>
                                        <div class="flex items-center gap-1.5">
                                            <span class="w-2 h-2 rounded-full bg-indigo-600"></span>
                                            <span class="font-bold text-slate-800 text-xs">
                                                <?= htmlspecialchars($subName ?: 'Subject Lecture') ?>
                                            </span>
                                        </div>
                                        <span class="text-[11px] text-slate-500">
                                            Period #<?= $periodNo ?: '1' ?> Classroom Session
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <!-- Status Badge -->
                                <td class="py-4 px-5">
                                    <?php if ($recStatus === 'present'): ?>
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                            </svg>
                                            <span>Present</span>
                                        </span>
                                    <?php elseif ($recStatus === 'late'): ?>
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                            <span>Late</span>
                                        </span>
                                    <?php elseif ($recStatus === 'absent'): ?>
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-rose-50 text-rose-700 border border-rose-200">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                                            </svg>
                                            <span>Absent</span>
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-sky-50 text-sky-700 border border-sky-200">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                            </svg>
                                            <span>Excused</span>
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <!-- Marker -->
                                <td class="py-4 px-5">
                                    <div class="text-xs font-semibold text-slate-800">
                                        <?= htmlspecialchars($markerName) ?>
                                    </div>
                                    <span class="text-[11px] text-slate-400">Class Teacher</span>
                                </td>

                                <!-- Remark -->
                                <td class="py-4 px-5">
                                    <?php if (!empty($remarks)): ?>
                                        <span class="text-xs text-slate-700 italic">"<?= htmlspecialchars($remarks) ?>"</span>
                                    <?php else: ?>
                                        <span class="text-xs text-slate-400">&mdash;</span>
                                    <?php endif; ?>
                                </td>

                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

    </div>

    <!-- 6. STATUTORY SECONDARY SCHOOL ATTENDANCE POLICY ADVISORY CARD -->
    <div class="bg-brand-50 border border-brand-100 rounded-2xl p-5 flex flex-col sm:flex-row sm:items-start gap-4 shadow-xs">
        <div class="w-10 h-10 rounded-xl bg-brand-700 text-white flex items-center justify-center flex-shrink-0 shadow-xs">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>
        <div>
            <h4 class="text-sm font-bold text-brand-900">
                School Statutory Attendance Requirement & Examination Policy
            </h4>
            <p class="text-xs text-slate-600 mt-1 leading-relaxed">
                In compliance with the Ministry of Education standards and Claret Academy academic regulations, all enrolled students must maintain a minimum of <strong>75% cumulative attendance</strong> across each subject during the term to qualify to sit for terminal examinations and promote to the next grade cohort.
            </p>
            <div class="mt-2 text-xs text-brand-700 font-semibold flex flex-wrap gap-4">
                <span>&bull; To report an illness or planned absence, submit a doctor's certificate or guardian letter.</span>
                <span>&bull; Unexcused late arrivals exceeding 3 instances per term trigger counseling review.</span>
            </div>
        </div>
    </div>

</div>

<!-- Interactive Client-side Filter Script -->
<script>
    function filterLogs(type) {
        const rows = document.querySelectorAll('.log-row');
        const buttons = document.querySelectorAll('.log-filter-btn');

        // Toggle active button styling
        buttons.forEach(btn => {
            btn.classList.remove('bg-white', 'text-slate-800', 'shadow-xs');
            btn.classList.add('text-slate-600');
        });

        const activeBtn = document.getElementById('btn-filter-' + type);
        if (activeBtn) {
            activeBtn.classList.remove('text-slate-600');
            activeBtn.classList.add('bg-white', 'text-slate-800', 'shadow-xs');
        }

        // Show/hide rows
        rows.forEach(row => {
            if (type === 'all') {
                row.style.display = '';
            } else if (type === 'daily') {
                row.style.display = row.classList.contains('log-type-daily') ? '' : 'none';
            } else if (type === 'subject') {
                row.style.display = row.classList.contains('log-type-subject') ? '' : 'none';
            }
        });
    }
</script>
