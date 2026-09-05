<?php
/**
 * Parent Child Timetable View
 * Phase UI-0 Modernization following .ai/08-ui-design-system.md
 * 
 * @var array $children
 * @var object|array|null $selectedChild
 * @var array|null $scheduleData
 * @var array $terms
 * @var object|null $selectedTerm
 * @var \App\Core\UserContext $user
 * @var string|null $error
 */

$childId = is_object($selectedChild) ? (int)$selectedChild->id : (int)($selectedChild['id'] ?? 0);
$childName = is_object($selectedChild) ? $selectedChild->name : ($selectedChild['name'] ?? 'Student');
$admissionNumber = is_object($selectedChild) ? ($selectedChild->admissionNumber ?? '') : ($selectedChild['admission_number'] ?? '');
$className = $scheduleData['class']->name ?? (
    is_object($selectedChild) 
        ? ($selectedChild->className ?: ($selectedChild->currentClass?->name ?: 'JSS 1A'))
        : ($selectedChild['class_name'] ?? 'JSS 1A')
);

$slots = $scheduleData['slots'] ?? [];
$totalSlots = count($slots);
$grid = $scheduleData['grid'] ?? [];

// Calculate distinct subjects
$distinctSubjects = [];
foreach ($slots as $slot) {
    $code = $slot->classSubject?->subjectCode ?: ($slot->classSubject?->subjectName ?: null);
    if ($code) {
        $distinctSubjects[$code] = true;
    }
}
$distinctSubjectCount = count($distinctSubjects);

$days = [
    'mon' => 'Monday',
    'tue' => 'Tuesday',
    'wed' => 'Wednesday',
    'thu' => 'Thursday',
    'fri' => 'Friday',
    'sat' => 'Saturday',
    'sun' => 'Sunday',
];
?>

<style>
/* Screen defaults */
.print-only {
    display: none;
}

@media print {
    /* Hide layout chrome & screen controls */
    #sidebar-navigation,
    #sidebar-backdrop,
    aside, 
    header, 
    #sidebar, 
    .sidebar, 
    nav, 
    [role="banner"],
    .screen-only,
    .no-print,
    .header-card,
    .stats-strip,
    .day-filter-bar,
    .bell-times-card,
    footer {
        display: none !important;
        visibility: hidden !important;
        position: absolute !important;
        left: -99999px !important;
        top: -99999px !important;
        width: 0 !important;
        height: 0 !important;
        overflow: hidden !important;
        opacity: 0 !important;
    }

    /* Reset body and container constraints for flawless print rendering */
    html, body {
        background: #ffffff !important;
        color: #0f172a !important;
        margin: 0 !important;
        padding: 0 !important;
        overflow: visible !important;
        height: auto !important;
        min-height: auto !important;
        width: 100% !important;
        font-size: 11px !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }

    body > div.flex-1,
    div.overflow-hidden,
    .min-h-screen, 
    .h-full, 
    #main-content, 
    main {
        overflow: visible !important;
        padding: 0 !important;
        margin: 0 !important;
        min-height: auto !important;
        max-height: none !important;
        display: block !important;
        width: 100% !important;
        background: #ffffff !important;
    }

    @page {
        size: A4 landscape;
        margin: 8mm 10mm;
    }

    /* Reveal printable elements */
    .print-only {
        display: block !important;
    }

    /* Force all day sections to display in print even if filtered on screen */
    .day-section {
        display: block !important;
        page-break-inside: avoid !important;
        break-inside: avoid !important;
        margin-bottom: 12px !important;
        border: 1px solid #cbd5e1 !important;
        box-shadow: none !important;
        border-radius: 8px !important;
        background: #ffffff !important;
    }

    .day-section .p-4 {
        padding: 6px 12px !important;
        background-color: #f8fafc !important;
        border-bottom: 1px solid #cbd5e1 !important;
    }

    .day-section .p-5 {
        padding: 8px 12px !important;
    }

    .period-card {
        page-break-inside: avoid !important;
        break-inside: avoid !important;
        border: 1px solid #cbd5e1 !important;
        background: #ffffff !important;
        box-shadow: none !important;
        padding: 8px 10px !important;
        border-radius: 6px !important;
    }
}
</style>

<div class="space-y-6">

    <!-- 1. HEADER CARD WITH BREADCRUMBS, STUDENT INFO & QUICK TOOLBAR (SCREEN-ONLY) -->
    <div class="screen-only header-card bg-white rounded-2xl border border-slate-200 shadow-xs p-6">
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
                        <?php if ($childId > 0): ?>
                            <a href="/parent/children/<?= $childId ?>" class="hover:text-brand-600 transition">Child Profile</a>
                            <span>&rsaquo;</span>
                        <?php endif; ?>
                        <span class="text-slate-700 font-bold">Class Timetable</span>
                    </div>

                    <div class="flex flex-wrap items-center gap-2.5">
                        <h1 class="text-xl font-bold text-slate-900 tracking-tight">
                            <?= htmlspecialchars($childName) ?>'s Weekly Schedule
                        </h1>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-brand-50 text-brand-700 border border-brand-200">
                            Class: <?= htmlspecialchars($className) ?>
                        </span>
                        <?php if (!empty($admissionNumber)): ?>
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-slate-100 text-slate-700 font-mono">
                                Adm: <?= htmlspecialchars($admissionNumber) ?>
                            </span>
                        <?php endif; ?>
                    </div>
                    <p class="text-xs text-slate-500 mt-1">
                        Weekly classroom timetable, lesson period times, and assigned subject teachers.
                    </p>
                </div>
            </div>

            <!-- Right: Quick Navigation Toolbar -->
            <?php if ($childId > 0): ?>
                <div class="flex flex-wrap items-center gap-2">
                    <a href="/parent/children/<?= $childId ?>" 
                       class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-white hover:bg-slate-50 text-slate-700 text-xs font-bold rounded-xl border border-slate-300 shadow-xs transition">
                        <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                        <span>Profile</span>
                    </a>

                    <a href="/parent/children/<?= $childId ?>/grades" 
                       class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-white hover:bg-slate-50 text-slate-700 text-xs font-bold rounded-xl border border-slate-300 shadow-xs transition">
                        <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <span>Grades</span>
                    </a>

                    <a href="/parent/children/<?= $childId ?>/assignments" 
                       class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-white hover:bg-slate-50 text-slate-700 text-xs font-bold rounded-xl border border-slate-300 shadow-xs transition">
                        <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                        <span>Coursework</span>
                    </a>

                    <a href="/parent/children/<?= $childId ?>/attendance" 
                       class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-white hover:bg-slate-50 text-slate-700 text-xs font-bold rounded-xl border border-slate-300 shadow-xs transition">
                        <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        <span>Attendance</span>
                    </a>

                    <button type="button" onclick="printSchedule()" 
                            class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-brand-50 hover:bg-brand-100 text-brand-700 text-xs font-bold rounded-xl border border-brand-200 transition">
                        <svg class="w-4 h-4 text-brand-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                        </svg>
                        <span>Print Schedule</span>
                    </button>
                </div>
            <?php endif; ?>

        </div>
    </div>

    <!-- Error Alert if present -->
    <?php if (isset($error) && $error): ?>
        <div class="p-4 bg-rose-50 border border-rose-200 text-rose-800 rounded-2xl text-xs font-semibold">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <!-- 2. MULTI-CHILD SELECTOR (IF MULTIPLE WARDS LINKED) (SCREEN-ONLY) -->
    <?php if (count($children) > 1): ?>
        <div class="screen-only flex items-center gap-2 border-b border-slate-200 pb-3 overflow-x-auto">
            <span class="text-xs font-bold uppercase tracking-wider text-slate-400 mr-1 flex-shrink-0">Viewing Ward:</span>
            <?php foreach ($children as $c): 
                $cId = is_object($c) ? (int)$c->id : (int)($c['id'] ?? 0);
                $cName = is_object($c) ? $c->name : ($c['name'] ?? '');
                $isActiveChild = ($childId === $cId);
            ?>
                <a href="/parent/children/<?= $cId ?>/timetable<?= $selectedTerm ? "?term_id={$selectedTerm->id}" : '' ?>"
                   class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-xl text-xs font-bold transition flex-shrink-0 <?= $isActiveChild ? 'bg-brand-700 text-white shadow-xs' : 'bg-white text-slate-700 hover:bg-slate-100 border border-slate-200' ?>">
                    <span class="w-2 h-2 rounded-full <?= $isActiveChild ? 'bg-emerald-400' : 'bg-slate-300' ?>"></span>
                    <span><?= htmlspecialchars($cName) ?>'s Schedule</span>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- 3. TERM SELECTOR & SCHEDULE CONTROLS (SCREEN-ONLY) -->
    <div class="screen-only bg-white rounded-2xl border border-slate-200 shadow-xs p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        
        <!-- Term Dropdown -->
        <div class="flex items-center gap-3">
            <label for="term_id" class="text-xs font-bold uppercase tracking-wider text-slate-500 flex-shrink-0">
                Academic Term:
            </label>
            <?php if (!empty($terms)): ?>
                <form method="GET" action="/parent/children/<?= $childId ?>/timetable" class="inline-block">
                    <select id="term_id" name="term_id" onchange="this.form.submit()" 
                            class="bg-slate-50 border border-slate-300 text-slate-800 text-xs font-bold rounded-xl px-3.5 py-2.5 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 cursor-pointer shadow-xs">
                        <?php foreach ($terms as $t): ?>
                            <option value="<?= (int)$t->id ?>" <?= $selectedTerm && (int)$selectedTerm->id === (int)$t->id ? 'selected' : '' ?>>
                                <?= htmlspecialchars($t->name) ?> <?= !empty($t->isCurrent) ? '(Active Session)' : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>
            <?php else: ?>
                <span class="text-xs font-bold text-slate-700">Current Academic Term</span>
            <?php endif; ?>
        </div>

        <!-- Schedule Status Badge -->
        <div>
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold bg-brand-50 text-brand-700 border border-brand-200">
                <span class="w-2 h-2 rounded-full bg-brand-600"></span>
                <span>Active Schedule &bull; <?= htmlspecialchars($selectedTerm?->name ?? 'Current Term') ?></span>
            </span>
        </div>

    </div>

    <!-- 4. 4-CARD OVERVIEW STATS STRIP (SCREEN-ONLY) -->
    <div class="screen-only stats-strip grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        
        <!-- Scheduled Periods -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Scheduled Periods</span>
                <span class="p-2 rounded-xl bg-brand-50 text-brand-700">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </span>
            </div>
            <div class="mt-3">
                <div class="text-2xl font-extrabold text-slate-900">
                    <?= $totalSlots ?>
                </div>
                <p class="text-xs text-slate-500 mt-1">
                    Classroom lesson slots per week
                </p>
            </div>
        </div>

        <!-- Distinct Subjects -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Curriculum Subjects</span>
                <span class="p-2 rounded-xl bg-indigo-50 text-indigo-700">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                    </svg>
                </span>
            </div>
            <div class="mt-3">
                <div class="text-2xl font-extrabold text-indigo-700">
                    <?= $distinctSubjectCount ?>
                </div>
                <p class="text-xs text-slate-500 mt-1">
                    Distinct academic disciplines
                </p>
            </div>
        </div>

        <!-- Instruction Days -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Instruction Days</span>
                <span class="p-2 rounded-xl bg-emerald-50 text-emerald-700">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </span>
            </div>
            <div class="mt-3">
                <div class="text-2xl font-extrabold text-emerald-700">
                    5 Days
                </div>
                <p class="text-xs text-slate-500 mt-1">
                    Monday through Friday
                </p>
            </div>
        </div>

        <!-- Class Cohort -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Cohort Room</span>
                <span class="p-2 rounded-xl bg-sky-50 text-sky-700">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                </span>
            </div>
            <div class="mt-3">
                <div class="text-2xl font-extrabold text-slate-900 truncate">
                    <?= htmlspecialchars($className) ?>
                </div>
                <p class="text-xs text-slate-500 mt-1">
                    Homeroom / Form Cohort
                </p>
            </div>
        </div>

    </div>

    <!-- 5. WEEKDAY NAVIGATION FILTER BUTTONS (SCREEN-ONLY) -->
    <div class="screen-only day-filter-bar flex items-center justify-between gap-4 flex-wrap">
        <div>
            <h3 class="text-base font-bold text-slate-900">
                Weekly Timetable Matrix
            </h3>
            <p class="text-xs text-slate-500 mt-0.5">
                Daily period allocations, subjects, and instructors.
            </p>
        </div>

        <div class="flex items-center gap-1.5 bg-slate-100 p-1 rounded-xl" role="tablist">
            <button type="button" onclick="filterDay('all')" id="btn-day-all"
                    class="day-filter-btn px-3 py-1 rounded-lg text-xs font-bold bg-white text-slate-800 shadow-xs transition">
                All Days
            </button>
            <button type="button" onclick="filterDay('mon')" id="btn-day-mon"
                    class="day-filter-btn px-3 py-1 rounded-lg text-xs font-bold text-slate-600 hover:text-slate-900 transition">
                Mon
            </button>
            <button type="button" onclick="filterDay('tue')" id="btn-day-tue"
                    class="day-filter-btn px-3 py-1 rounded-lg text-xs font-bold text-slate-600 hover:text-slate-900 transition">
                Tue
            </button>
            <button type="button" onclick="filterDay('wed')" id="btn-day-wed"
                    class="day-filter-btn px-3 py-1 rounded-lg text-xs font-bold text-slate-600 hover:text-slate-900 transition">
                Wed
            </button>
            <button type="button" onclick="filterDay('thu')" id="btn-day-thu"
                    class="day-filter-btn px-3 py-1 rounded-lg text-xs font-bold text-slate-600 hover:text-slate-900 transition">
                Thu
            </button>
            <button type="button" onclick="filterDay('fri')" id="btn-day-fri"
                    class="day-filter-btn px-3 py-1 rounded-lg text-xs font-bold text-slate-600 hover:text-slate-900 transition">
                Fri
            </button>
        </div>
    </div>

    <!-- PRINT-ONLY OFFICIAL TIMETABLE DOSSIER HEADER -->
    <div class="print-only mb-4 border border-slate-300 rounded-xl p-4 bg-white">
        <div class="flex items-center justify-between border-b border-slate-200 pb-3 mb-3">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-xl bg-brand-700 text-white font-extrabold text-xl flex items-center justify-center flex-shrink-0">
                    CL
                </div>
                <div>
                    <h1 class="text-base font-extrabold uppercase tracking-wide text-slate-900 leading-tight">
                        Claret Academy Secondary School
                    </h1>
                    <p class="text-xs font-semibold text-brand-700">
                        Official Classroom Timetable &amp; Weekly Lesson Schedule
                    </p>
                </div>
            </div>
            <div class="text-right text-xs">
                <span class="inline-block px-2 py-0.5 rounded bg-slate-100 font-bold text-slate-700 border border-slate-200">
                    Academic Session: 2026/2027
                </span>
                <div class="text-[10px] text-slate-500 mt-1 font-mono">
                    Printed: <?= date('M d, Y') ?>
                </div>
            </div>
        </div>

        <!-- Student Bio Particulars Grid -->
        <div class="grid grid-cols-4 gap-2 text-xs bg-slate-50 p-2.5 rounded-lg border border-slate-200">
            <div>
                <span class="text-slate-400 font-bold uppercase tracking-wider text-[10px] block">Student Name:</span>
                <span class="font-extrabold text-slate-900"><?= htmlspecialchars($childName) ?></span>
            </div>
            <div>
                <span class="text-slate-400 font-bold uppercase tracking-wider text-[10px] block">Admission No:</span>
                <span class="font-mono font-bold text-slate-900"><?= htmlspecialchars($admissionNumber ?: 'N/A') ?></span>
            </div>
            <div>
                <span class="text-slate-400 font-bold uppercase tracking-wider text-[10px] block">Class Cohort:</span>
                <span class="font-bold text-brand-700"><?= htmlspecialchars($className) ?></span>
            </div>
            <div>
                <span class="text-slate-400 font-bold uppercase tracking-wider text-[10px] block">Academic Term:</span>
                <span class="font-bold text-slate-800"><?= htmlspecialchars($selectedTerm?->name ?? 'Current Term') ?></span>
            </div>
        </div>
    </div>

    <!-- 6. TIMETABLE SCHEDULE BY DAY -->
    <?php if ($totalSlots === 0): ?>
        <!-- Empty State -->
        <div class="bg-white p-12 text-center rounded-2xl border border-slate-200 shadow-xs">
            <div class="w-12 h-12 rounded-2xl bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-3">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
            </div>
            <h4 class="text-sm font-bold text-slate-900">No Scheduled Periods Found</h4>
            <p class="text-xs text-slate-500 max-w-sm mx-auto mt-1">
                There are no scheduled lessons published for <?= htmlspecialchars($className) ?> in <?= htmlspecialchars($selectedTerm?->name ?? 'this academic term') ?>.
            </p>
        </div>
    <?php else: ?>
        <div class="space-y-4" id="timetable-days-container">
            <?php foreach ($days as $dayKey => $dayLabel): 
                $daySlots = $grid[$dayKey] ?? [];
                if (empty($daySlots) && ($dayKey === 'sat' || $dayKey === 'sun')) {
                    continue;
                }
            ?>
                <div class="day-section bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden transition-all duration-200"
                     data-day="<?= $dayKey ?>">
                    
                    <!-- Day Bar Header -->
                    <div class="p-4 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
                        <div class="flex items-center gap-2.5">
                            <span class="w-2.5 h-2.5 rounded-full bg-brand-700"></span>
                            <h4 class="text-sm font-bold text-slate-900 uppercase tracking-wide">
                                <?= $dayLabel ?>
                            </h4>
                        </div>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold <?= !empty($daySlots) ? 'bg-brand-50 text-brand-700 border border-brand-200' : 'bg-slate-100 text-slate-500' ?>">
                            <?= count($daySlots) ?> <?= count($daySlots) === 1 ? 'Period' : 'Periods' ?>
                        </span>
                    </div>

                    <div class="p-5">
                        <?php if (empty($daySlots)): ?>
                            <div class="py-4 text-center text-xs text-slate-400 italic">
                                No scheduled lessons for <?= $dayLabel ?>.
                            </div>
                        <?php else: ?>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                <?php foreach ($daySlots as $idx => $slot): 
                                    $subCode = $slot->classSubject?->subjectCode ?: 'SUB';
                                    $subName = $slot->classSubject?->subjectName ?: 'Subject';
                                    $teacher = $slot->classSubject?->teacherName ?: 'Subject Teacher';
                                    $timeRange = $slot->getFormattedTimeRange();
                                    $duration = $slot->getDurationMinutes();
                                    $room = $slot->room;
                                ?>
                                    <div class="period-card p-4 rounded-xl border border-slate-200 bg-slate-50/50 hover:bg-white hover:border-brand-300 hover:shadow-xs transition-all duration-150 flex flex-col justify-between">
                                        <div>
                                            <!-- Card Badges -->
                                            <div class="flex items-center justify-between gap-2">
                                                <div class="flex items-center gap-1.5">
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-lg text-xs font-bold bg-brand-50 text-brand-700 border border-brand-200">
                                                        <?= htmlspecialchars($subCode) ?>
                                                    </span>
                                                    <span class="text-[11px] font-bold text-slate-400 font-mono">
                                                        #<?= $idx + 1 ?>
                                                    </span>
                                                </div>
                                                <?php if ($room): ?>
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-lg text-[11px] font-semibold bg-purple-50 text-purple-700 border border-purple-200">
                                                        Room: <?= htmlspecialchars($room) ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>

                                            <!-- Subject Title -->
                                            <h5 class="text-sm font-bold text-slate-900 mt-2.5 leading-snug">
                                                <?= htmlspecialchars($subName) ?>
                                            </h5>

                                            <!-- Teacher (Normalized secondary school terminology) -->
                                            <div class="flex items-center gap-1.5 mt-2 text-xs text-slate-500">
                                                <svg class="w-3.5 h-3.5 text-slate-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                                </svg>
                                                <span class="truncate">
                                                    Teacher: <strong class="text-slate-700 font-semibold"><?= htmlspecialchars($teacher) ?></strong>
                                                </span>
                                            </div>
                                        </div>

                                        <!-- Period Time Footer -->
                                        <div class="mt-4 pt-2.5 border-t border-slate-200/80 flex items-center justify-between text-xs">
                                            <span class="font-bold text-brand-700 flex items-center gap-1">
                                                <svg class="w-3.5 h-3.5 text-brand-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                </svg>
                                                <span><?= htmlspecialchars($timeRange) ?></span>
                                            </span>
                                            <span class="text-[11px] font-medium text-slate-400">
                                                <?= $duration ?> mins
                                            </span>
                                        </div>

                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                </div>
            <?php endforeach; ?>
        </div>

        <!-- PRINT-ONLY SIGNATURE & ENDORSEMENT FOOTER -->
        <div class="print-only mt-6 pt-4 border-t border-slate-300">
            <div class="grid grid-cols-3 gap-6 text-center text-xs">
                <div>
                    <div class="border-b border-slate-400 pb-1 mb-1 font-semibold text-slate-800">
                        <?= htmlspecialchars($scheduleData['class']->classTeacherName ?? 'Class Teacher') ?>
                    </div>
                    <span class="text-[10px] text-slate-500 uppercase tracking-wider">Form / Class Teacher Signature</span>
                </div>
                <div>
                    <div class="border-b border-slate-400 pb-1 mb-1 font-semibold text-slate-800">
                        Vice Principal (Academics)
                    </div>
                    <span class="text-[10px] text-slate-500 uppercase tracking-wider">Academic Director Signature &amp; Stamp</span>
                </div>
                <div>
                    <div class="border-b border-slate-400 pb-1 mb-1 font-mono text-slate-800">
                        <?= date('d/m/Y') ?>
                    </div>
                    <span class="text-[10px] text-slate-500 uppercase tracking-wider">Date of Issue</span>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- 7. SECONDARY SCHOOL SCHEDULE & BELL TIMES ADVISORY CARD (SCREEN-ONLY) -->
    <div class="screen-only bell-times-card bg-brand-50 border border-brand-100 rounded-2xl p-5 flex flex-col sm:flex-row sm:items-start gap-4 shadow-xs">
        <div class="w-10 h-10 rounded-xl bg-brand-700 text-white flex items-center justify-center flex-shrink-0 shadow-xs">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>
        <div>
            <h4 class="text-sm font-bold text-brand-900">
                Official School Day Schedule & Bell Times
            </h4>
            <p class="text-xs text-slate-600 mt-1 leading-relaxed">
                Claret Academy Secondary School operates on a disciplined bell schedule. Guardians are advised to ensure wards arrive before 07:45 AM for morning assembly and devotion roll-call.
            </p>
            <div class="mt-2.5 text-xs text-brand-700 font-semibold flex flex-wrap gap-x-6 gap-y-1">
                <span>&bull; <strong>Morning Assembly & Devotion:</strong> 07:45 AM &ndash; 08:00 AM</span>
                <span>&bull; <strong>Mid-Day Break / Lunch:</strong> 11:15 AM &ndash; 11:45 AM</span>
                <span>&bull; <strong>School Closing (Mon &ndash; Thu):</strong> 02:30 PM</span>
                <span>&bull; <strong>Friday Early Dismissal:</strong> 01:00 PM</span>
            </div>
        </div>
    </div>

</div>

<!-- Interactive Client-side Day Filter Script -->
<script>
    function filterDay(dayKey) {
        const sections = document.querySelectorAll('.day-section');
        const buttons = document.querySelectorAll('.day-filter-btn');

        // Toggle active button styling
        buttons.forEach(btn => {
            btn.classList.remove('bg-white', 'text-slate-800', 'shadow-xs');
            btn.classList.add('text-slate-600');
        });

        const activeBtn = document.getElementById('btn-day-' + dayKey);
        if (activeBtn) {
            activeBtn.classList.remove('text-slate-600');
            activeBtn.classList.add('bg-white', 'text-slate-800', 'shadow-xs');
        }

        // Show/hide day sections
        sections.forEach(sec => {
            const secDay = sec.getAttribute('data-day');
            if (dayKey === 'all') {
                sec.style.display = '';
            } else {
                sec.style.display = (secDay === dayKey) ? '' : 'none';
            }
        });
    }

    function printSchedule() {
        // Restore all day sections so full weekly schedule is printed cleanly
        document.querySelectorAll('.day-section').forEach(sec => sec.style.display = '');
        window.print();
    }

    window.addEventListener('beforeprint', function() {
        document.querySelectorAll('.day-section').forEach(sec => sec.style.display = '');
    });
</script>
