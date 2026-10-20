<?php
/**
 * Child Academic Profile Screen (PARENT-02) — Phase UI-0 Modernized
 * Detailed student overview, attendance history, enrolled courses, and coursework tasks for parents.
 *
 * @var \App\Models\Student $student
 * @var \App\Models\ParentProfile|null $parent
 * @var \App\Models\AcademicSession|null $activeSession
 * @var \App\Models\Term|null $activeTerm
 * @var array|null $attendanceSummary
 * @var array $recentAttendanceHistory
 * @var bool $isResultPublished
 * @var \App\Models\StudentTermSummary|null $termSummary
 * @var array $subjectResults
 * @var \App\Models\StudentSubjectEnrollment[] $subjectEnrollments
 * @var \App\Models\Assignment[] $assignments
 * @var array<int, \App\Models\AssignmentSubmission> $submissions
 * @var \App\Models\Announcement[] $announcements
 * @var \App\Core\UserContext $user
 */

$att = $attendanceSummary ?? null;
$ts = $termSummary ?? null;
$isPublished = $isResultPublished ?? false;
$subjects = $subjectEnrollments ?? [];
$asgns = $assignments ?? [];
$subs = $submissions ?? [];
$attHistory = $recentAttendanceHistory ?? [];
$recentNotices = $announcements ?? [];

$totalDays = (int)($att['total_days'] ?? $att['total_records'] ?? 0);
$presentDays = (int)($att['present_days'] ?? $att['present_count'] ?? 0);
$attRate = $totalDays > 0 ? round(($presentDays / $totalDays) * 100, 1) : null;

$childClass = $student->currentClass?->name ?? ($student->className ?: 'Class Assigned');
$arm = !empty($student->currentClass?->sectionArm) ? ' (' . $student->currentClass->sectionArm . ')' : '';
$fullClassName = $childClass . $arm;
?>

<div class="space-y-6">
    <!-- Header Card -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6">
            <div class="flex items-center gap-4">
                <div class="relative flex-shrink-0 group">
                    <?php if (!empty($student->avatarUrl)): ?>
                        <img src="<?= e($student->avatarUrl) ?>" alt="<?= e($student->name) ?>" class="w-14 h-14 rounded-full object-cover border-2 border-white shadow-xs">
                    <?php else: ?>
                        <div class="w-14 h-14 rounded-full bg-[#7B3046] text-white font-extrabold text-2xl flex items-center justify-center shadow-xs">
                            <?= strtoupper(substr($student->name, 0, 1)) ?>
                        </div>
                    <?php endif; ?>
                    <button type="button" onclick="openPassportModal()" title="Upload Passport Photograph" 
                            class="absolute -bottom-1 -right-1 w-6 h-6 rounded-full bg-white text-slate-700 hover:text-brand-600 border border-slate-200 shadow-sm flex items-center justify-center transition hover:scale-110">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </button>
                </div>
                <div>
                    <!-- Breadcrumbs -->
                    <div class="flex items-center gap-2 text-xs font-semibold text-slate-400 mb-1">
                        <a href="/parent/dashboard" class="hover:text-brand-600 transition">Dashboard</a>
                        <span>&rsaquo;</span>
                        <span class="text-slate-700 font-bold">Child Profile</span>
                    </div>

                    <div class="flex flex-wrap items-center gap-2.5">
                        <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight leading-tight">
                            <?= htmlspecialchars($student->name) ?>
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
                        <span>Gender: <strong class="text-slate-700 capitalize"><?= htmlspecialchars($student->gender ?: 'Not specified') ?></strong></span>
                        <?php if ($student->dateOfBirth): ?>
                            <span>&bull;</span>
                            <span>DOB: <strong class="text-slate-700"><?= date('M d, Y', strtotime($student->dateOfBirth)) ?></strong></span>
                        <?php endif; ?>
                        <span>&bull;</span>
                        <span class="text-emerald-700 font-semibold"><?= htmlspecialchars($activeTerm?->name ?? 'Active Term') ?></span>
                    </div>
                </div>
            </div>

            <!-- Quick Action Toolbar -->
            <div class="flex flex-wrap items-center gap-2">
                <button type="button" onclick="openPassportModal()" 
                   class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-bold bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 shadow-xs transition">
                    <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    <span>Upload Passport</span>
                </button>
                <a href="/parent/children/<?= (int)$student->id ?>/grades" 
                   class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-bold bg-brand-600 hover:bg-brand-700 text-white shadow-xs transition">
                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <span>Terminal Reports</span>
                </a>
                <a href="/parent/children/<?= (int)$student->id ?>/attendance" 
                   class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-bold bg-slate-100 hover:bg-slate-200 text-slate-700 transition">
                    <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    <span>Daily Attendance</span>
                </a>
                <a href="/parent/children/<?= (int)$student->id ?>/assignments" 
                   class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-bold bg-slate-100 hover:bg-slate-200 text-slate-700 transition">
                    <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                    <span>Coursework</span>
                </a>
                <a href="/parent/children/<?= (int)$student->id ?>/timetable" 
                   class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-bold bg-slate-100 hover:bg-slate-200 text-slate-700 transition">
                    <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span>Timetable</span>
                </a>
            </div>
        </div>
    </div>

    <!-- 4-Card Overview Stats Strip -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Attendance Metric Card -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Term Attendance</span>
                <span class="p-2 rounded-xl <?= ($attRate !== null && $attRate >= 75) ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700' ?>">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </span>
            </div>
            <div class="mt-3">
                <div class="flex items-baseline gap-1.5">
                    <?php if ($attRate !== null): ?>
                        <h3 class="text-2xl font-extrabold <?= $attRate >= 80 ? 'text-emerald-700' : ($attRate >= 65 ? 'text-amber-700' : 'text-rose-700') ?>">
                            <?= $attRate ?>%
                        </h3>
                        <span class="text-xs font-semibold text-slate-400 font-mono">
                            (<?= $presentDays ?>/<?= $totalDays ?>d)
                        </span>
                    <?php else: ?>
                        <h3 class="text-xl font-bold text-slate-400">No records</h3>
                    <?php endif; ?>
                </div>
                <?php if ($attRate !== null): ?>
                    <div class="w-full bg-slate-100 rounded-full h-1.5 mt-2 overflow-hidden">
                        <div class="h-1.5 rounded-full <?= $attRate >= 80 ? 'bg-emerald-500' : ($attRate >= 65 ? 'bg-amber-500' : 'bg-rose-500') ?>" style="width: <?= min(100, $attRate) ?>%"></div>
                    </div>
                <?php endif; ?>
                <p class="text-xs text-slate-500 mt-1.5">
                    Roll-call attendance rate
                </p>
            </div>
        </div>

        <!-- Terminal Average Score -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Term Average</span>
                <span class="p-2 rounded-xl bg-brand-50 text-brand-700">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                    </svg>
                </span>
            </div>
            <div class="mt-3">
                <div class="flex items-baseline gap-1.5">
                    <?php if ($isPublished && $ts): ?>
                        <h3 class="text-2xl font-extrabold text-brand-700">
                            <?= number_format((float)($ts->averageScore ?? 0), 1) ?>%
                        </h3>
                        <?php if (!empty($ts->rankInClass)): ?>
                            <span class="text-xs font-bold text-slate-600 bg-slate-100 px-1.5 py-0.5 rounded">
                                Rank #<?= e((string)$ts->rankInClass) ?>
                            </span>
                        <?php endif; ?>
                    <?php else: ?>
                        <span class="inline-flex items-center text-xs font-bold text-amber-800 bg-amber-50 px-2 py-0.5 rounded-lg border border-amber-200">
                            Pending Release
                        </span>
                    <?php endif; ?>
                </div>
                <p class="text-xs text-slate-500 mt-1.5">
                    <?= $isPublished ? 'Official Result Released' : 'Under administrative seal' ?>
                </p>
            </div>
        </div>

        <!-- Class Standing / Position -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Class Standing</span>
                <span class="p-2 rounded-xl bg-indigo-50 text-indigo-700">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
                    </svg>
                </span>
            </div>
            <div class="mt-3">
                <div class="flex items-baseline gap-1.5">
                    <?php if ($isPublished && $ts && $ts->rankInClass): ?>
                        <h3 class="text-2xl font-extrabold text-slate-900">
                            #<?= (int)$ts->rankInClass ?>
                        </h3>
                        <span class="text-xs font-semibold text-slate-500">in cohort</span>
                    <?php else: ?>
                        <h3 class="text-lg font-bold text-slate-700 truncate">
                            <?= htmlspecialchars($childClass) ?>
                        </h3>
                    <?php endif; ?>
                </div>
                <p class="text-xs text-slate-500 mt-1.5">
                    <?= ($isPublished && $ts && $ts->rankInClass) ? 'Cohort Position' : 'Rank sealed until release' ?>
                </p>
            </div>
        </div>

        <!-- Enrolled Subjects Count -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Enrolled Subjects</span>
                <span class="p-2 rounded-xl bg-sky-50 text-sky-700">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                    </svg>
                </span>
            </div>
            <div class="mt-3">
                <div class="flex items-baseline gap-1.5">
                    <h3 class="text-2xl font-extrabold text-slate-900"><?= count($subjects) ?></h3>
                    <span class="text-xs font-semibold text-slate-500">disciplines</span>
                </div>
                <p class="text-xs text-slate-500 mt-1.5">
                    Active Curriculum
                </p>
            </div>
        </div>
    </div>

    <!-- Main Content 2-Column Layout -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left 2 Cols: Enrolled Subjects Directory & Recent Tasks -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Enrolled Subjects Card -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6 space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div>
                        <h2 class="text-base font-bold text-slate-900 flex items-center gap-2">
                            <span>Enrolled Academic Subjects</span>
                            <span class="text-xs font-bold px-2 py-0.5 rounded-full bg-slate-100 text-slate-600">
                                <?= count($subjects) ?>
                            </span>
                        </h2>
                        <p class="text-xs text-slate-500 mt-0.5">Assigned subject lessons and teachers for session <?= htmlspecialchars($activeSession?->name ?? '') ?></p>
                    </div>
                </div>

                <?php if (empty($subjects)): ?>
                    <div class="text-center py-8 text-slate-400 text-xs italic">
                        <p>No enrolled subjects found for the current academic session.</p>
                    </div>
                <?php else: ?>
                    <div class="divide-y divide-slate-100">
                        <?php foreach ($subjects as $enrollment): 
                            $cs = $enrollment->classSubject;
                            $code = $cs?->subjectCode ?? 'SUB';
                            $title = $cs?->subjectName ?? 'Subject';
                            $teacher = $cs?->teacherName ?? ($cs?->teacher?->user?->name ?? ($cs?->teacher?->name ?? 'Subject Teacher'));
                        ?>
                            <div class="py-3.5 flex items-center justify-between gap-4">
                                <div class="space-y-1 min-w-0">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="font-mono text-xs font-bold text-sky-800 bg-sky-50 border border-sky-200 px-2 py-0.5 rounded-md">
                                            <?= htmlspecialchars($code) ?>
                                        </span>
                                        <h3 class="text-sm font-bold text-slate-900 truncate">
                                            <?= htmlspecialchars($title) ?>
                                        </h3>
                                        <?php if ($enrollment->isElective): ?>
                                            <span class="text-[10px] font-bold text-amber-800 bg-amber-50 border border-amber-200 px-1.5 py-0.5 rounded">Elective</span>
                                        <?php endif; ?>
                                    </div>
                                    <p class="text-xs text-slate-500 flex items-center gap-1.5">
                                        <span>Teacher:</span>
                                        <span class="text-slate-800 font-semibold"><?= htmlspecialchars($teacher) ?></span>
                                    </p>
                                </div>

                                <a href="/parent/children/<?= (int)$student->id ?>/assignments" 
                                   class="inline-flex items-center gap-1 text-xs font-bold text-brand-600 hover:text-brand-700 transition flex-shrink-0 bg-brand-50 hover:bg-brand-100 px-3 py-1.5 rounded-lg border border-brand-200">
                                    <span>Coursework</span>
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                                    </svg>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Recent Coursework Glance -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6 space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div>
                        <h2 class="text-base font-bold text-slate-900">Recent Coursework Tasks</h2>
                        <p class="text-xs text-slate-500 mt-0.5">Track assignment submissions and evaluation grades</p>
                    </div>
                    <a href="/parent/children/<?= (int)$student->id ?>/assignments" class="text-xs font-bold text-brand-600 hover:text-brand-700 transition">
                        View All &rarr;
                    </a>
                </div>

                <?php if (empty($asgns)): ?>
                    <div class="text-center py-8 text-slate-400 text-xs italic">
                        <p>No assignments recorded for this academic term.</p>
                    </div>
                <?php else: ?>
                    <div class="space-y-2">
                        <?php foreach (array_slice($asgns, 0, 4) as $asgn): 
                            $sub = $subs[$asgn->id] ?? null;
                        ?>
                            <div class="flex items-center justify-between p-3 rounded-xl bg-slate-50 border border-slate-100 text-xs">
                                <div class="min-w-0 pr-3">
                                    <h4 class="font-bold text-slate-900 truncate"><?= htmlspecialchars($asgn->title) ?></h4>
                                    <span class="text-[11px] text-slate-400 block mt-0.5">
                                        Due: <?= $asgn->dueDate ? date('M d, Y', strtotime($asgn->dueDate)) : 'No deadline' ?>
                                    </span>
                                </div>
                                <div class="flex-shrink-0">
                                    <?php if ($sub && $sub->score !== null): ?>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-bold bg-emerald-50 text-emerald-800 border border-emerald-200 font-mono">
                                            <?= (float)$sub->score ?> / <?= (float)$asgn->maxScore ?> PTS
                                        </span>
                                    <?php elseif ($sub): ?>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-bold bg-sky-50 text-sky-800 border border-sky-200">
                                            Submitted
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-bold bg-amber-50 text-amber-800 border border-amber-200">
                                            Pending
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Right 1 Col: Attendance Roll-Call & Bulletins -->
        <div class="space-y-6">
            <!-- Recent Attendance History -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6 space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <h2 class="text-base font-bold text-slate-900">Recent Attendance</h2>
                    <a href="/parent/children/<?= (int)$student->id ?>/attendance" class="text-xs font-bold text-brand-600 hover:text-brand-700 transition">
                        Full Log &rarr;
                    </a>
                </div>

                <?php if (empty($attHistory)): ?>
                    <div class="text-center py-6 text-slate-400 text-xs italic">
                        <p>No attendance entries entered yet.</p>
                    </div>
                <?php else: ?>
                    <div class="space-y-2">
                        <?php foreach ($attHistory as $record): 
                            $statusStr = is_object($record) ? $record->status : ($record['status'] ?? '');
                            $dateStr = is_object($record) ? $record->date : ($record['date'] ?? '');
                            $st = strtolower($statusStr);
                            $badgeStyle = match($st) {
                                'present' => 'bg-emerald-50 text-emerald-800 border-emerald-200',
                                'absent' => 'bg-rose-50 text-rose-800 border-rose-200',
                                'late' => 'bg-amber-50 text-amber-800 border-amber-200',
                                'excused' => 'bg-sky-50 text-sky-800 border-sky-200',
                                default => 'bg-slate-50 text-slate-700 border-slate-200',
                            };
                        ?>
                            <div class="flex items-center justify-between p-2.5 rounded-xl bg-slate-50 border border-slate-100 text-xs">
                                <div>
                                    <span class="font-bold text-slate-800 block">
                                        <?= $dateStr ? date('D, M d, Y', strtotime($dateStr)) : '' ?>
                                    </span>
                                    <span class="text-[10px] text-slate-400 uppercase tracking-wider">Homeroom Roll-Call</span>
                                </div>
                                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold uppercase tracking-wider border <?= $badgeStyle ?>">
                                    <?= htmlspecialchars($statusStr) ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Bulletins for Child -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6 space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <h2 class="text-base font-bold text-slate-900">Class Notices</h2>
                    <a href="/parent/children/<?= (int)$student->id ?>/announcements" class="text-xs font-bold text-brand-600 hover:text-brand-700 transition">
                        View All &rarr;
                    </a>
                </div>

                <?php if (empty($recentNotices)): ?>
                    <div class="text-center py-6 text-slate-400 text-xs italic">
                        <p>No active notices for this class.</p>
                    </div>
                <?php else: ?>
                    <div class="space-y-3">
                        <?php foreach (array_slice($recentNotices, 0, 3) as $notice): 
                            $nTitle = is_object($notice) ? $notice->title : ($notice['title'] ?? '');
                            $nScope = is_object($notice) ? $notice->scope : ($notice['scope'] ?? 'school');
                            $nDate = is_object($notice) ? ($notice->publishedAt ?? $notice->createdAt) : ($notice['published_at'] ?? $notice['created_at'] ?? null);
                        ?>
                            <div class="p-3 rounded-xl bg-slate-50 border border-slate-100 space-y-1">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="inline-flex items-center px-1.5 py-0.2 text-[10px] font-bold uppercase tracking-wider rounded <?= $nScope === 'school' ? 'bg-purple-50 text-purple-700 border border-purple-200' : 'bg-cyan-50 text-cyan-800 border border-cyan-200' ?>">
                                        <?= htmlspecialchars($nScope) ?>
                                    </span>
                                    <span class="text-[10px] text-slate-400 font-mono">
                                        <?= date('M d', strtotime($nDate ?: 'now')) ?>
                                    </span>
                                </div>
                                <h4 class="text-xs font-bold text-slate-900 line-clamp-1">
                                    <?= htmlspecialchars($nTitle) ?>
                                </h4>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
    </div>
</div>

<!-- Passport Upload Modal (Single & Batch) -->
<div id="passportModal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="passportModalTitle" role="dialog" aria-modal="true">
    <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity" onclick="closePassportModal()"></div>

    <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
        <div class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-2xl border border-slate-200">
            <!-- Modal Header -->
            <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4 bg-slate-50/50">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-[#7B3046]/10 text-[#7B3046] flex items-center justify-center font-bold">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-slate-900" id="passportModalTitle">Student Passport Photograph</h3>
                        <p class="text-xs text-slate-500">Official student identity photo for reports, ID dockets, and profiles</p>
                    </div>
                </div>
                <button type="button" onclick="closePassportModal()" class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-600 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <?php $allChildren = $children ?? [$student]; ?>
            <?php if (count($allChildren) > 1): ?>
                <!-- Modal Tabs -->
                <div class="flex border-b border-slate-200 px-6 bg-slate-50">
                    <button type="button" id="tabBtnSingle" onclick="switchPassportTab('single')" 
                            class="py-3 px-4 text-xs font-bold border-b-2 border-brand-600 text-brand-600 transition">
                        <?= htmlspecialchars($student->name) ?> (Active)
                    </button>
                    <button type="button" id="tabBtnBatch" onclick="switchPassportTab('batch')" 
                            class="py-3 px-4 text-xs font-bold border-b-2 border-transparent text-slate-500 hover:text-slate-700 transition flex items-center gap-1.5">
                        <span>Multi-Ward Batch Upload</span>
                        <span class="px-1.5 py-0.2 rounded-full bg-slate-200 text-slate-700 text-[10px]"><?= count($allChildren) ?></span>
                    </button>
                </div>
            <?php endif; ?>

            <div class="p-6">
                <!-- Single Child Form -->
                <div id="singlePassportView">
                    <form action="/parent/children/<?= (int)$student->id ?>/passport" method="POST" enctype="multipart/form-data" class="space-y-5">
                        <?= csrf_field() ?>
                        <input type="hidden" name="redirect_to" value="/parent/children/<?= (int)$student->id ?>">

                        <div class="flex flex-col sm:flex-row items-center gap-6 p-4 rounded-xl bg-slate-50 border border-slate-100">
                            <!-- Live Preview Box -->
                            <div class="relative flex-shrink-0">
                                <div id="singlePreviewContainer" class="w-28 h-28 rounded-2xl overflow-hidden border-2 border-dashed border-slate-300 bg-white shadow-xs flex items-center justify-center">
                                    <?php if (!empty($student->avatarUrl)): ?>
                                        <img id="singleAvatarPreview" src="<?= e($student->avatarUrl) ?>" alt="<?= e($student->name) ?>" class="w-full h-full object-cover">
                                    <?php else: ?>
                                        <div id="singleAvatarPlaceholder" class="w-full h-full bg-[#7B3046]/10 text-[#7B3046] font-extrabold text-3xl flex items-center justify-center">
                                            <?= strtoupper(substr($student->name, 0, 1)) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <span class="absolute -bottom-2 inset-x-0 mx-auto w-max px-2 py-0.5 rounded text-[10px] font-bold bg-slate-800 text-white shadow-xs">
                                    Preview
                                </span>
                            </div>

                            <div class="flex-1 text-center sm:text-left space-y-2">
                                <h4 class="text-sm font-bold text-slate-900"><?= htmlspecialchars($student->name) ?></h4>
                                <p class="text-xs text-slate-500">
                                    Adm: <strong class="text-slate-700"><?= htmlspecialchars($student->admissionNumber) ?></strong> &bull; 
                                    Class: <strong class="text-slate-700"><?= htmlspecialchars($fullClassName) ?></strong>
                                </p>
                                <label for="singleFileInput" class="inline-flex items-center gap-2 px-3 py-1.5 rounded-xl text-xs font-bold bg-brand-600 hover:bg-brand-700 text-white cursor-pointer shadow-xs transition">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                                    </svg>
                                    <span>Choose New Photograph</span>
                                </label>
                                <input type="file" id="singleFileInput" name="passport" accept="image/png,image/jpeg,image/webp" class="hidden" onchange="previewSingleFile(this)">
                                <p id="singleFileName" class="text-[11px] text-slate-500 italic">No file chosen yet</p>
                            </div>
                        </div>

                        <div class="bg-amber-50 border border-amber-200 rounded-xl p-3 text-xs text-amber-800 flex items-start gap-2.5">
                            <svg class="w-4 h-4 text-amber-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <div>
                                <strong class="font-bold">Passport Guidelines:</strong> Accepted formats are JPG, PNG, and WebP (Max 5MB). Photo must show full front-facing head and shoulders against a light, neutral background.
                            </div>
                        </div>

                        <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
                            <button type="button" onclick="closePassportModal()" class="px-4 py-2 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-100 transition">
                                Cancel
                            </button>
                            <button type="submit" id="singleSubmitBtn" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-xs font-bold bg-brand-600 hover:bg-brand-700 text-white shadow-xs transition disabled:opacity-50" disabled>
                                <span>Upload & Save Passport</span>
                            </button>
                        </div>
                    </form>
                </div>

                <?php if (count($allChildren) > 1): ?>
                    <!-- Batch Upload Form -->
                    <div id="batchPassportView" class="hidden space-y-5">
                        <form action="/parent/passports/batch" method="POST" enctype="multipart/form-data" class="space-y-5" id="batchPassportForm">
                            <?= csrf_field() ?>
                            <input type="hidden" name="redirect_to" value="/parent/children/<?= (int)$student->id ?>">

                            <div class="bg-sky-50 border border-sky-200 rounded-xl p-3 text-xs text-sky-800">
                                <p class="font-semibold">Upload photos for each of your enrolled wards below, or use the multi-file picker to choose photos simultaneously.</p>
                            </div>

                            <!-- Ward List with per-child inputs -->
                            <div class="space-y-3 max-h-72 overflow-y-auto pr-1">
                                <?php foreach ($allChildren as $ward): 
                                    $wClass = $ward->currentClass?->name ?? ($ward->className ?: 'Assigned Class');
                                ?>
                                    <div class="flex items-center justify-between gap-4 p-3 rounded-xl bg-slate-50 border border-slate-200">
                                        <div class="flex items-center gap-3 min-w-0">
                                            <div class="w-10 h-10 rounded-full overflow-hidden bg-slate-200 flex-shrink-0 flex items-center justify-center font-bold text-slate-700" id="batchWardThumbContainer_<?= (int)$ward->id ?>">
                                                <?php if (!empty($ward->avatarUrl)): ?>
                                                    <img src="<?= e($ward->avatarUrl) ?>" alt="<?= e($ward->name) ?>" class="w-full h-full object-cover" id="batchWardThumb_<?= (int)$ward->id ?>">
                                                <?php else: ?>
                                                    <span id="batchWardInitial_<?= (int)$ward->id ?>"><?= strtoupper(substr($ward->name, 0, 1)) ?></span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="min-w-0">
                                                <h5 class="text-xs font-bold text-slate-900 truncate"><?= htmlspecialchars($ward->name) ?></h5>
                                                <p class="text-[11px] text-slate-500 font-mono">Adm: <?= htmlspecialchars($ward->admissionNumber) ?> &bull; <?= htmlspecialchars($wClass) ?></p>
                                            </div>
                                        </div>

                                        <div class="flex items-center gap-2">
                                            <label for="batchFile_<?= (int)$ward->id ?>" class="px-2.5 py-1.5 rounded-lg text-xs font-semibold bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 cursor-pointer shadow-2xs transition">
                                                Select Photo
                                            </label>
                                            <input type="file" id="batchFile_<?= (int)$ward->id ?>" name="passports[<?= (int)$ward->id ?>]" accept="image/png,image/jpeg,image/webp" class="hidden" onchange="previewBatchWardFile(this, <?= (int)$ward->id ?>)">
                                            <span id="batchFileName_<?= (int)$ward->id ?>" class="text-[11px] text-slate-400 italic max-w-[100px] truncate">None</span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
                                <button type="button" onclick="closePassportModal()" class="px-4 py-2 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-100 transition">
                                    Cancel
                                </button>
                                <button type="submit" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-xs font-bold bg-[#7B3046] hover:bg-[#68273a] text-white shadow-xs transition">
                                    <span>Upload Selected Passports</span>
                                </button>
                            </div>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
function openPassportModal() {
    var modal = document.getElementById('passportModal');
    if (modal) {
        modal.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
    }
}

function closePassportModal() {
    var modal = document.getElementById('passportModal');
    if (modal) {
        modal.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
    }
}

function switchPassportTab(tab) {
    var singleView = document.getElementById('singlePassportView');
    var batchView = document.getElementById('batchPassportView');
    var tabBtnSingle = document.getElementById('tabBtnSingle');
    var tabBtnBatch = document.getElementById('tabBtnBatch');

    if (tab === 'single') {
        if (singleView) singleView.classList.remove('hidden');
        if (batchView) batchView.classList.add('hidden');
        if (tabBtnSingle) {
            tabBtnSingle.classList.add('border-brand-600', 'text-brand-600');
            tabBtnSingle.classList.remove('border-transparent', 'text-slate-500');
        }
        if (tabBtnBatch) {
            tabBtnBatch.classList.remove('border-brand-600', 'text-brand-600');
            tabBtnBatch.classList.add('border-transparent', 'text-slate-500');
        }
    } else {
        if (singleView) singleView.classList.add('hidden');
        if (batchView) batchView.classList.remove('hidden');
        if (tabBtnBatch) {
            tabBtnBatch.classList.add('border-brand-600', 'text-brand-600');
            tabBtnBatch.classList.remove('border-transparent', 'text-slate-500');
        }
        if (tabBtnSingle) {
            tabBtnSingle.classList.remove('border-brand-600', 'text-brand-600');
            tabBtnSingle.classList.add('border-transparent', 'text-slate-500');
        }
    }
}

function previewSingleFile(input) {
    if (input.files && input.files[0]) {
        var file = input.files[0];
        var nameSpan = document.getElementById('singleFileName');
        var submitBtn = document.getElementById('singleSubmitBtn');
        if (nameSpan) nameSpan.textContent = file.name;
        if (submitBtn) submitBtn.disabled = false;

        var reader = new FileReader();
        reader.onload = function(e) {
            var container = document.getElementById('singlePreviewContainer');
            if (container) {
                container.innerHTML = '<img src="' + e.target.result + '" class="w-full h-full object-cover">';
            }
        };
        reader.readAsDataURL(file);
    }
}

function previewBatchWardFile(input, wardId) {
    if (input.files && input.files[0]) {
        var file = input.files[0];
        var nameSpan = document.getElementById('batchFileName_' + wardId);
        if (nameSpan) {
            nameSpan.textContent = file.name;
            nameSpan.classList.remove('text-slate-400');
            nameSpan.classList.add('text-emerald-600', 'font-semibold');
        }

        var reader = new FileReader();
        reader.onload = function(e) {
            var container = document.getElementById('batchWardThumbContainer_' + wardId);
            if (container) {
                container.innerHTML = '<img src="' + e.target.result + '" class="w-full h-full object-cover">';
            }
        };
        reader.readAsDataURL(file);
    }
}

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closePassportModal();
    }
});
</script>
