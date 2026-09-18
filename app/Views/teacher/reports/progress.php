<?php
/**
 * Teacher Cohort Learning Progression Report View (Phase 7)
 * Strictly follows .ai/08-ui-design-system.md: flat, accessible, clean white surfaces, no dark gradients.
 */
$metrics = $report['cohort_metrics'] ?? [
    'enrolled_student_count' => 0,
    'not_started_count' => 0,
    'in_progress_count' => 0,
    'completed_count' => 0,
    'average_progress_percentage' => 0.0,
];
$students = $report['students'] ?? [];
$totalRequired = (int)($report['total_required_items'] ?? 0);
$hasModules = (bool)($report['has_modules'] ?? false);

$subName = htmlspecialchars($classSubject->subject?->name ?? ($classSubject->subjectName ?? 'Subject'), ENT_QUOTES, 'UTF-8');
$clsName = htmlspecialchars($classSubject->schoolClass?->name ?? ($classSubject->className ?? 'Class'), ENT_QUOTES, 'UTF-8');
$secArm = !empty($classSubject->schoolClass?->sectionArm) ? ' (' . htmlspecialchars($classSubject->schoolClass->sectionArm, ENT_QUOTES, 'UTF-8') . ')' : '';
?>

<div class="space-y-6">
    <!-- Breadcrumb & Header Card -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <nav class="flex items-center gap-2 text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">
                    <a href="/teacher/dashboard" class="text-slate-400 hover:text-sky-600 transition">Faculty Portal</a>
                    <span class="text-slate-300">/</span>
                    <a href="/teacher/modules?class_subject_id=<?= (int)$selectedClassSubjectId ?>" class="text-slate-400 hover:text-sky-600 transition">Course Modules</a>
                    <span class="text-slate-300">/</span>
                    <span class="text-slate-700">Progression Report</span>
                </nav>
                <div class="flex items-center gap-3 flex-wrap">
                    <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight leading-tight">
                        Learning Progression Report
                    </h1>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-sky-50 text-sky-700 border border-sky-200">
                        <?= $subName ?> &mdash; <?= $clsName ?><?= $secArm ?>
                    </span>
                </div>
                <p class="text-xs text-slate-500 mt-1">
                    Real-time cohort completion statistics and individual student progress tracking across published course modules.
                </p>
            </div>

            <!-- Action CTAs -->
            <div class="flex items-center gap-2.5 flex-wrap">
                <a href="/teacher/modules?class_subject_id=<?= (int)$selectedClassSubjectId ?>"
                   class="px-4 py-2.5 rounded-xl border border-slate-300 text-xs font-bold text-slate-700 hover:bg-slate-50 transition inline-flex items-center gap-2 shadow-2xs">
                    <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                    </svg>
                    Manage Modules
                </a>
                <a href="/teacher/content?class_subject_id=<?= (int)$selectedClassSubjectId ?>"
                   class="px-4 py-2.5 rounded-xl border border-slate-300 text-xs font-bold text-slate-700 hover:bg-slate-50 transition inline-flex items-center gap-2 shadow-2xs">
                    <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                    </svg>
                    Study Materials
                </a>
            </div>
        </div>
    </div>

    <!-- Offering Selector Toolbar -->
    <?php if (!empty($classSubjects) && count($classSubjects) > 1): ?>
        <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-xs">
            <form method="GET" action="" onsubmit="window.location.href='/teacher/subjects/' + document.getElementById('subject_switcher').value + '/progress'; return false;" class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3">
                <div class="flex items-center gap-3 flex-1">
                    <label for="subject_switcher" class="text-xs font-bold text-slate-600 uppercase tracking-wider whitespace-nowrap flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-sky-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                        </svg>
                        Switch Subject:
                    </label>
                    <select id="subject_switcher" onchange="window.location.href='/teacher/subjects/' + this.value + '/progress'"
                            class="flex-1 max-w-lg rounded-xl border border-slate-300 text-xs focus:border-sky-500 focus:ring-sky-500 bg-slate-50 py-2 px-3 font-semibold text-slate-900 transition">
                        <?php foreach ($classSubjects as $cs): ?>
                            <?php 
                                $sN = $cs->subject?->name ?? ($cs->subjectName ?? 'Subject');
                                $cN = $cs->schoolClass?->name ?? ($cs->className ?? 'Class');
                                $arm = !empty($cs->schoolClass?->sectionArm) ? ' (' . $cs->schoolClass->sectionArm . ')' : '';
                                $label = "{$sN} - {$cN}{$arm}";
                            ?>
                            <option value="<?= (int)$cs->id ?>" <?= $cs->id === (int)$selectedClassSubjectId ? 'selected' : '' ?>>
                                <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </form>
        </div>
    <?php endif; ?>

    <!-- Cohort Metric Cards -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3 sm:gap-4">
        <!-- Card 1: Enrolled -->
        <div class="bg-white rounded-2xl border border-slate-200 p-4 sm:p-5 shadow-xs flex flex-col justify-between">
            <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Enrolled Students</span>
            <div class="mt-2 flex items-baseline justify-between">
                <span class="text-2xl sm:text-3xl font-black text-slate-900" id="metric-enrolled">
                    <?= (int)$metrics['enrolled_student_count'] ?>
                </span>
                <span class="text-xs font-medium text-slate-400">Total</span>
            </div>
        </div>

        <!-- Card 2: Not Started -->
        <div class="bg-white rounded-2xl border border-slate-200 p-4 sm:p-5 shadow-xs flex flex-col justify-between">
            <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Not Started</span>
            <div class="mt-2 flex items-baseline justify-between">
                <span class="text-2xl sm:text-3xl font-black text-slate-700" id="metric-not-started">
                    <?= (int)$metrics['not_started_count'] ?>
                </span>
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-2xs font-bold bg-slate-100 text-slate-600">
                    <?= $metrics['enrolled_student_count'] > 0 ? round(($metrics['not_started_count'] / $metrics['enrolled_student_count']) * 100) : 0 ?>%
                </span>
            </div>
        </div>

        <!-- Card 3: In Progress -->
        <div class="bg-white rounded-2xl border border-amber-200/80 bg-amber-50/20 p-4 sm:p-5 shadow-xs flex flex-col justify-between">
            <span class="text-xs font-bold uppercase tracking-wider text-amber-800">In Progress</span>
            <div class="mt-2 flex items-baseline justify-between">
                <span class="text-2xl sm:text-3xl font-black text-amber-700" id="metric-in-progress">
                    <?= (int)$metrics['in_progress_count'] ?>
                </span>
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-2xs font-bold bg-amber-100 text-amber-800">
                    <?= $metrics['enrolled_student_count'] > 0 ? round(($metrics['in_progress_count'] / $metrics['enrolled_student_count']) * 100) : 0 ?>%
                </span>
            </div>
        </div>

        <!-- Card 4: Completed -->
        <div class="bg-white rounded-2xl border border-emerald-200/80 bg-emerald-50/20 p-4 sm:p-5 shadow-xs flex flex-col justify-between">
            <span class="text-xs font-bold uppercase tracking-wider text-emerald-800">Completed</span>
            <div class="mt-2 flex items-baseline justify-between">
                <span class="text-2xl sm:text-3xl font-black text-emerald-700" id="metric-completed">
                    <?= (int)$metrics['completed_count'] ?>
                </span>
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-2xs font-bold bg-emerald-100 text-emerald-800">
                    <?= $metrics['enrolled_student_count'] > 0 ? round(($metrics['completed_count'] / $metrics['enrolled_student_count']) * 100) : 0 ?>%
                </span>
            </div>
        </div>

        <!-- Card 5: Cohort Average -->
        <div class="col-span-2 sm:col-span-1 bg-white rounded-2xl border border-sky-200/80 bg-sky-50/20 p-4 sm:p-5 shadow-xs flex flex-col justify-between">
            <span class="text-xs font-bold uppercase tracking-wider text-sky-800">Average Progress</span>
            <div class="mt-2">
                <div class="flex items-baseline justify-between">
                    <span class="text-2xl sm:text-3xl font-black text-sky-700" id="metric-avg">
                        <?= number_format((float)$metrics['average_progress_percentage'], 1) ?>%
                    </span>
                    <span class="text-2xs font-medium text-slate-500">Cohort Avg</span>
                </div>
                <div class="w-full bg-slate-200 rounded-full h-1.5 mt-2 overflow-hidden">
                    <div class="bg-sky-600 h-1.5 rounded-full transition-all duration-300" style="width: <?= min(100, max(0, (float)$metrics['average_progress_percentage'])) ?>%"></div>
                </div>
            </div>
        </div>
    </div>

    <?php if (!$hasModules || $totalRequired === 0): ?>
        <!-- Informational Notice: No modules or activities published -->
        <div class="bg-white rounded-2xl border border-amber-200 p-6 shadow-xs text-center sm:text-left flex flex-col sm:flex-row items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-amber-50 border border-amber-200 flex items-center justify-center shrink-0 text-amber-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            </div>
            <div class="flex-1">
                <h3 class="text-sm font-bold text-slate-900">No Published Required Activities</h3>
                <p class="text-xs text-slate-500 mt-0.5">
                    This class subject does not have any required activities inside published modules yet. Create instructional modules and attach learning documents, quizzes, or assignments to track student progression.
                </p>
            </div>
            <a href="/teacher/modules?class_subject_id=<?= (int)$selectedClassSubjectId ?>"
               class="px-4 py-2 rounded-xl bg-sky-600 hover:bg-sky-700 text-white text-xs font-bold shadow-xs transition shrink-0">
                Configure Modules &rarr;
            </a>
        </div>
    <?php endif; ?>

    <!-- Student Roster & Progression Table Card -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
        <!-- Table Toolbar: Search & Status Filters -->
        <div class="p-4 sm:p-5 border-b border-slate-200 bg-slate-50/50 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <h2 class="text-sm font-bold text-slate-900 tracking-tight">
                    Enrolled Student Progression
                </h2>
                <p class="text-xs text-slate-500 mt-0.5">
                    <?= count($students) ?> student<?= count($students) === 1 ? '' : 's' ?> enrolled &bull; <?= $totalRequired ?> required activit<?= $totalRequired === 1 ? 'y' : 'ies' ?>
                </p>
            </div>

            <!-- Filter Controls -->
            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2.5">
                <!-- Search Box -->
                <div class="relative">
                    <input type="text" id="rosterSearch" placeholder="Search student name or admission..."
                           onkeyup="filterStudentTable()"
                           class="w-full sm:w-64 pl-8 pr-3 py-1.5 rounded-xl border border-slate-300 text-xs focus:border-sky-500 focus:ring-sky-500 bg-white placeholder-slate-400 transition" />
                    <svg class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-2.5 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>

                <!-- Status Filter Pills -->
                <div class="inline-flex rounded-xl border border-slate-300 bg-white p-0.5 shadow-2xs text-xs font-bold text-slate-600" role="tablist">
                    <button type="button" onclick="setStatusFilter('ALL', this)" class="status-tab px-3 py-1 rounded-lg bg-slate-900 text-white transition text-xs">
                        All
                    </button>
                    <button type="button" onclick="setStatusFilter('Completed', this)" class="status-tab px-3 py-1 rounded-lg hover:text-slate-900 text-slate-600 transition text-xs">
                        Completed
                    </button>
                    <button type="button" onclick="setStatusFilter('In Progress', this)" class="status-tab px-3 py-1 rounded-lg hover:text-slate-900 text-slate-600 transition text-xs">
                        In Progress
                    </button>
                    <button type="button" onclick="setStatusFilter('Not Started', this)" class="status-tab px-3 py-1 rounded-lg hover:text-slate-900 text-slate-600 transition text-xs">
                        Not Started
                    </button>
                </div>
            </div>
        </div>

        <?php if (empty($students)): ?>
            <!-- Empty State: No Enrolled Students -->
            <div class="p-12 text-center">
                <div class="w-12 h-12 rounded-2xl bg-slate-100 flex items-center justify-center mx-auto mb-3 text-slate-400">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                </div>
                <h3 class="text-sm font-bold text-slate-800">No Students Enrolled</h3>
                <p class="text-xs text-slate-500 mt-1 max-w-sm mx-auto">
                    No students are actively enrolled in this subject offering. Enroll students through Academic Management to track their progress.
                </p>
            </div>
        <?php else: ?>
            <!-- Desktop Table View (hidden < 640px) -->
            <div class="hidden sm:block overflow-x-auto">
                <table class="w-full text-left text-xs text-slate-700">
                    <thead class="bg-slate-50 text-slate-500 font-bold uppercase tracking-wider border-b border-slate-200">
                        <tr>
                            <th class="py-3 px-5">Student</th>
                            <th class="py-3 px-4">Status</th>
                            <th class="py-3 px-4">Completed / Total</th>
                            <th class="py-3 px-4 w-48">Progress</th>
                            <th class="py-3 px-4">Last Active</th>
                            <th class="py-3 px-5 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200" id="studentRosterBody">
                        <?php foreach ($students as $row): ?>
                            <?php
                                $sName = htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8');
                                $admNo = htmlspecialchars($row['admission_number'] ?? 'N/A', ENT_QUOTES, 'UTF-8');
                                $status = $row['status'];
                                $pct = (float)$row['progress_percent'];
                                $completedCount = (int)$row['completed_required_count'];
                                $totalCount = (int)$row['total_required_count'];
                                $lastActive = !empty($row['last_active_at']) ? date('M j, Y g:i A', strtotime($row['last_active_at'])) : 'Never';

                                $badgeClass = match ($status) {
                                    'Completed' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                    'In Progress' => 'bg-amber-50 text-amber-700 border-amber-200',
                                    default => 'bg-slate-100 text-slate-600 border-slate-200',
                                };

                                $barColor = match ($status) {
                                    'Completed' => 'bg-emerald-600',
                                    'In Progress' => 'bg-amber-500',
                                    default => 'bg-slate-300',
                                };
                            ?>
                            <tr class="student-row hover:bg-slate-50/80 transition"
                                data-name="<?= strtolower($sName) ?>"
                                data-adm="<?= strtolower($admNo) ?>"
                                data-status="<?= $status ?>">
                                <td class="py-3.5 px-5">
                                    <div class="font-bold text-slate-900 leading-snug break-words">
                                        <?= $sName ?>
                                    </div>
                                    <div class="text-2xs text-slate-400 mt-0.5 font-mono">
                                        <?= $admNo ?>
                                    </div>
                                </td>
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-2xs font-bold border <?= $badgeClass ?>">
                                        <?= $status ?>
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 whitespace-nowrap font-medium text-slate-600">
                                    <span class="font-bold text-slate-900"><?= $completedCount ?></span>
                                    <span class="text-slate-400">/</span>
                                    <span><?= $totalCount ?></span>
                                </td>
                                <td class="py-3.5 px-4">
                                    <div class="flex items-center gap-2">
                                        <div class="flex-1 bg-slate-100 rounded-full h-2 overflow-hidden border border-slate-200/60">
                                            <div class="<?= $barColor ?> h-2 rounded-full transition-all duration-300" style="width: <?= min(100, max(0, $pct)) ?>%"></div>
                                        </div>
                                        <span class="font-bold text-slate-700 text-2xs whitespace-nowrap w-11 text-right">
                                            <?= number_format($pct, 1) ?>%
                                        </span>
                                    </div>
                                </td>
                                <td class="py-3.5 px-4 whitespace-nowrap text-slate-500 text-2xs">
                                    <?= $lastActive ?>
                                </td>
                                <td class="py-3.5 px-5 text-right whitespace-nowrap">
                                    <a href="/teacher/subjects/<?= (int)$selectedClassSubjectId ?>/students/<?= (int)$row['student_id'] ?>/progress"
                                       class="px-3 py-1.5 rounded-lg border border-slate-300 hover:border-sky-500 text-sky-700 hover:bg-sky-50 font-bold text-xs transition inline-flex items-center gap-1.5 shadow-2xs">
                                        <span>Inspect</span>
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                        </svg>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Mobile Stacked Card View (displayed on screens < 640px) -->
            <div class="sm:hidden divide-y divide-slate-200" id="studentRosterMobile">
                <?php foreach ($students as $row): ?>
                    <?php
                        $sName = htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8');
                        $admNo = htmlspecialchars($row['admission_number'] ?? 'N/A', ENT_QUOTES, 'UTF-8');
                        $status = $row['status'];
                        $pct = (float)$row['progress_percent'];
                        $completedCount = (int)$row['completed_required_count'];
                        $totalCount = (int)$row['total_required_count'];
                        $lastActive = !empty($row['last_active_at']) ? date('M j, Y', strtotime($row['last_active_at'])) : 'Never';

                        $badgeClass = match ($status) {
                            'Completed' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                            'In Progress' => 'bg-amber-50 text-amber-700 border-amber-200',
                            default => 'bg-slate-100 text-slate-600 border-slate-200',
                        };

                        $barColor = match ($status) {
                            'Completed' => 'bg-emerald-600',
                            'In Progress' => 'bg-amber-500',
                            default => 'bg-slate-300',
                        };
                    ?>
                    <div class="p-4 space-y-3 student-card"
                         data-name="<?= strtolower($sName) ?>"
                         data-adm="<?= strtolower($admNo) ?>"
                         data-status="<?= $status ?>">
                        <div class="flex items-start justify-between gap-2">
                            <div class="min-w-0 flex-1">
                                <h4 class="font-bold text-slate-900 text-sm break-words leading-tight">
                                    <?= $sName ?>
                                </h4>
                                <p class="text-2xs text-slate-400 font-mono mt-0.5"><?= $admNo ?></p>
                            </div>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-2xs font-bold border shrink-0 <?= $badgeClass ?>">
                                <?= $status ?>
                            </span>
                        </div>

                        <!-- Progress Bar & Stats -->
                        <div class="space-y-1">
                            <div class="flex items-center justify-between text-2xs text-slate-500">
                                <span>Completed: <strong class="text-slate-800"><?= $completedCount ?>/<?= $totalCount ?></strong></span>
                                <span class="font-bold text-slate-800"><?= number_format($pct, 1) ?>%</span>
                            </div>
                            <div class="w-full bg-slate-100 rounded-full h-2 overflow-hidden border border-slate-200/60">
                                <div class="<?= $barColor ?> h-2 rounded-full" style="width: <?= min(100, max(0, $pct)) ?>%"></div>
                            </div>
                        </div>

                        <!-- Mobile Footer with Action Button -->
                        <div class="flex items-center justify-between pt-1 text-2xs text-slate-400">
                            <span>Active: <?= $lastActive ?></span>
                            <a href="/teacher/subjects/<?= (int)$selectedClassSubjectId ?>/students/<?= (int)$row['student_id'] ?>/progress"
                               class="px-3 py-1.5 rounded-lg bg-sky-50 text-sky-700 font-bold border border-sky-200 hover:bg-sky-100 transition inline-flex items-center gap-1">
                                <span>Inspect</span>
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                </svg>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Filter Zero Matches Message -->
            <div id="noMatchMessage" class="hidden p-8 text-center text-slate-500 text-xs">
                No enrolled students match the search criteria.
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
let currentStatusFilter = 'ALL';

function setStatusFilter(status, btn) {
    currentStatusFilter = status;
    document.querySelectorAll('.status-tab').forEach(el => {
        el.classList.remove('bg-slate-900', 'text-white');
        el.classList.add('text-slate-600');
    });
    btn.classList.remove('text-slate-600');
    btn.classList.add('bg-slate-900', 'text-white');
    filterStudentTable();
}

function filterStudentTable() {
    const query = (document.getElementById('rosterSearch')?.value || '').toLowerCase().trim();
    const rows = document.querySelectorAll('.student-row');
    const cards = document.querySelectorAll('.student-card');
    let visibleCount = 0;

    rows.forEach(row => {
        const name = row.getAttribute('data-name') || '';
        const adm = row.getAttribute('data-adm') || '';
        const status = row.getAttribute('data-status') || '';

        const matchesQuery = !query || name.includes(query) || adm.includes(query);
        const matchesStatus = currentStatusFilter === 'ALL' || status === currentStatusFilter;

        if (matchesQuery && matchesStatus) {
            row.style.display = '';
            visibleCount++;
        } else {
            row.style.display = 'none';
        }
    });

    cards.forEach(card => {
        const name = card.getAttribute('data-name') || '';
        const adm = card.getAttribute('data-adm') || '';
        const status = card.getAttribute('data-status') || '';

        const matchesQuery = !query || name.includes(query) || adm.includes(query);
        const matchesStatus = currentStatusFilter === 'ALL' || status === currentStatusFilter;

        if (matchesQuery && matchesStatus) {
            card.style.display = '';
        } else {
            card.style.display = 'none';
        }
    });

    const noMatch = document.getElementById('noMatchMessage');
    if (noMatch) {
        noMatch.style.display = visibleCount === 0 && (rows.length > 0) ? 'block' : 'none';
    }
}
</script>
