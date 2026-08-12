<?php
$att = $attendanceSummary ?? null;
$ts = $termSummary ?? null;
$isPublished = $isResultPublished ?? false;
$subjects = $subjectEnrollments ?? [];
$asgns = $assignments ?? [];
$subs = $submissions ?? [];
$attHistory = $recentAttendanceHistory ?? [];
$attRate = $att && ($att['total_days'] ?? $att['total_records'] ?? 0) > 0 
    ? round((($att['present_days'] ?? $att['present_count'] ?? 0) / ($att['total_days'] ?? $att['total_records'] ?? 1)) * 100, 1) 
    : null;
?>

<div class="space-y-8">
    <!-- Student Profile Header Card -->
    <div class="bg-white rounded-2xl border border-slate-200 p-6 md:p-8 shadow-sm">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 pb-6 border-b border-slate-100">
            <div class="flex items-center gap-4">
                <div class="w-16 h-16 rounded-2xl bg-brand-700 text-white flex items-center justify-center font-bold text-2xl shadow-md">
                    <?= e(substr($student->name, 0, 1)) ?>
                </div>
                <div>
                    <div class="flex items-center gap-2.5">
                        <h2 class="text-xl md:text-2xl font-bold text-slate-900 leading-tight"><?= e($student->name) ?></h2>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-brand-100 text-brand-700">
                            <?= e($student->className ?: 'Class Enrolled') ?>
                        </span>
                    </div>
                    <div class="flex flex-wrap items-center gap-y-1 gap-x-4 text-xs text-slate-500 mt-1.5">
                        <span>Admission No: <strong class="text-slate-800 font-mono"><?= e($student->admissionNumber) ?></strong></span>
                        <span>•</span>
                        <span>Gender: <strong class="text-slate-800 capitalize"><?= e($student->gender ?: 'Not specified') ?></strong></span>
                        <?php if ($student->dateOfBirth): ?>
                            <span>•</span>
                            <span>DOB: <strong class="text-slate-800"><?= e(date('M d, Y', strtotime($student->dateOfBirth))) ?></strong></span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Quick Action Links -->
            <div class="flex flex-wrap items-center gap-2.5">
                <a href="/parent/children/<?= (int)$student->id ?>/grades" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold bg-brand-600 hover:bg-brand-700 text-white shadow-sm transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    Report Card
                </a>
                <a href="/parent/children/<?= (int)$student->id ?>/attendance" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold bg-slate-100 hover:bg-slate-200 text-slate-800 transition">
                    <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    Attendance
                </a>
                <a href="/parent/children/<?= (int)$student->id ?>/assignments" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold bg-slate-100 hover:bg-slate-200 text-slate-800 transition">
                    <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
                    Coursework
                </a>
            </div>
        </div>

        <!-- Academic Key Stats Row -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 pt-6">
            <!-- Attendance Metric Card -->
            <div class="bg-slate-50 rounded-xl p-4 border border-slate-100">
                <div class="flex items-center justify-between text-xs text-slate-500 mb-1">
                    <span class="font-semibold uppercase tracking-wider">Attendance Rate</span>
                    <span class="text-[11px] text-slate-400"><?= e($activeTerm ? $activeTerm->name : 'Current Term') ?></span>
                </div>
                <?php if ($attRate !== null): ?>
                    <div class="flex items-baseline gap-2 mt-1">
                        <span class="text-2xl font-black <?= $attRate >= 80 ? 'text-emerald-700' : ($attRate >= 65 ? 'text-amber-700' : 'text-rose-700') ?>">
                            <?= $attRate ?>%
                        </span>
                        <span class="text-xs text-slate-500 font-medium">
                            (<?= (int)($att['present_days'] ?? $att['present_count'] ?? 0) ?> of <?= (int)($att['total_days'] ?? $att['total_records'] ?? 0) ?> recorded days)
                        </span>
                    </div>
                    <div class="w-full bg-slate-200 rounded-full h-1.5 mt-3">
                        <div class="h-1.5 rounded-full <?= $attRate >= 80 ? 'bg-emerald-500' : ($attRate >= 65 ? 'bg-amber-500' : 'bg-rose-500') ?>" style="width: <?= min(100, $attRate) ?>%"></div>
                    </div>
                <?php else: ?>
                    <p class="text-xs text-slate-400 italic mt-2">No roll-call records entered yet.</p>
                <?php endif; ?>
            </div>

            <!-- Published Grade Average Card -->
            <div class="bg-slate-50 rounded-xl p-4 border border-slate-100">
                <div class="flex items-center justify-between text-xs text-slate-500 mb-1">
                    <span class="font-semibold uppercase tracking-wider">Term Average</span>
                    <span class="text-[11px] text-slate-400">Published Status</span>
                </div>
                <?php if ($isPublished && $ts): ?>
                    <div class="flex items-baseline gap-2 mt-1">
                        <span class="text-2xl font-black text-brand-700">
                            <?= number_format((float)($ts->averageScore ?? 0), 1) ?>%
                        </span>
                        <?php if ($ts->gpa !== null): ?>
                            <span class="text-xs text-slate-600 font-semibold">GPA: <?= number_format((float)$ts->gpa, 2) ?></span>
                        <?php endif; ?>
                    </div>
                    <p class="text-[11px] text-emerald-700 font-semibold mt-2 flex items-center gap-1">
                        <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                        Official Term Results Published
                    </p>
                <?php else: ?>
                    <div class="mt-2">
                        <span class="inline-flex items-center px-2 py-1 rounded text-xs font-semibold bg-amber-100 text-amber-800">
                            Awaiting Release
                        </span>
                        <p class="text-[11px] text-slate-400 mt-1.5">Draft grades are sealed until official publishing.</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Class Standing / Academic Level Card -->
            <div class="bg-slate-50 rounded-xl p-4 border border-slate-100">
                <div class="flex items-center justify-between text-xs text-slate-500 mb-1">
                    <span class="font-semibold uppercase tracking-wider">Class Standing</span>
                    <span class="text-[11px] text-slate-400">Position</span>
                </div>
                <?php if ($isPublished && $ts && $ts->rankInClass): ?>
                    <div class="flex items-baseline gap-2 mt-1">
                        <span class="text-2xl font-black text-slate-900">
                            #<?= (int)$ts->rankInClass ?>
                        </span>
                        <span class="text-xs text-slate-500">Class Rank</span>
                    </div>
                    <p class="text-[11px] text-slate-600 mt-2 font-medium">Ranked within <?= e($student->className) ?></p>
                <?php else: ?>
                    <div class="mt-2">
                        <span class="text-sm font-semibold text-slate-700"><?= e($student->className ?: 'Active Class') ?></span>
                        <p class="text-[11px] text-slate-400 mt-1">Rank released alongside report cards.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Enrolled Subjects & Coursework Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Enrolled Subjects (2 Cols) -->
        <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
            <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-100">
                <h3 class="text-base font-bold text-slate-900">Enrolled Subjects (<?= count($subjects) ?>)</h3>
                <span class="text-xs text-slate-500">Session <?= e($activeSession ? $activeSession->name : '') ?></span>
            </div>

            <?php if (empty($subjects)): ?>
                <div class="text-center py-8 text-slate-400 text-sm">
                    <p>No enrolled subjects registered for this academic session.</p>
                </div>
            <?php else: ?>
                <div class="divide-y divide-slate-100">
                    <?php foreach ($subjects as $enrollment): 
                        $cs = $enrollment->classSubject;
                    ?>
                        <div class="py-3.5 flex items-center justify-between gap-4">
                            <div class="space-y-0.5">
                                <div class="flex items-center gap-2">
                                    <span class="font-mono text-xs font-bold text-brand-700 bg-brand-100 px-1.5 py-0.5 rounded">
                                        <?= e($cs?->subjectCode ?? 'SUB') ?>
                                    </span>
                                    <h4 class="text-sm font-bold text-slate-900">
                                        <?= e($cs?->subjectName ?? 'Subject') ?>
                                    </h4>
                                    <?php if ($enrollment->isElective): ?>
                                        <span class="text-[10px] font-semibold text-slate-500 bg-slate-100 px-1.5 py-0.5 rounded">Elective</span>
                                    <?php endif; ?>
                                </div>
                                <p class="text-xs text-slate-500">
                                    Teacher: <span class="text-slate-700 font-medium"><?= e($cs?->teacher?->user?->name ?? 'Class Instructor') ?></span>
                                </p>
                            </div>
                            <a href="/parent/children/<?= (int)$student->id ?>/assignments" class="text-xs font-semibold text-brand-600 hover:text-brand-700 hover:underline flex-shrink-0">
                                Tasks &rarr;
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Recent Attendance Activity (1 Col) -->
        <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
            <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-100">
                <h3 class="text-base font-bold text-slate-900">Recent Attendance</h3>
                <a href="/parent/children/<?= (int)$student->id ?>/attendance" class="text-xs font-semibold text-brand-600 hover:underline">
                    View Full &rarr;
                </a>
            </div>

            <?php if (empty($attHistory)): ?>
                <div class="text-center py-8 text-slate-400 text-sm">
                    <p>No recent attendance entries recorded.</p>
                </div>
            <?php else: ?>
                <div class="space-y-2.5">
                    <?php foreach ($attHistory as $record): 
                        $statusStr = is_object($record) ? $record->status : ($record['status'] ?? '');
                        $dateStr = is_object($record) ? $record->date : ($record['date'] ?? '');
                        $st = strtolower($statusStr);
                        $badgeClass = match($st) {
                            'present' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
                            'absent' => 'bg-rose-100 text-rose-800 border-rose-200',
                            'late' => 'bg-amber-100 text-amber-800 border-amber-200',
                            'excused' => 'bg-blue-100 text-blue-800 border-blue-200',
                            default => 'bg-slate-100 text-slate-800 border-slate-200',
                        };
                    ?>
                        <div class="flex items-center justify-between p-2.5 rounded-xl bg-slate-50 border border-slate-100 text-xs">
                            <div>
                                <span class="font-semibold text-slate-800 block"><?= e($dateStr ? date('D, M d, Y', strtotime($dateStr)) : '') ?></span>
                                <span class="text-[11px] text-slate-400">Daily Roll-Call</span>
                            </div>
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider border <?= $badgeClass ?>">
                                <?= e($statusStr) ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
