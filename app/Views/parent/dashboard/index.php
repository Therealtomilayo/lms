<?php
/**
 * Parent/Guardian Dashboard — Phase UI-0 Modernized
 * Multi-child family overview, attendance, report cards, and notices.
 *
 * @var \App\Models\ParentProfile|null $parent
 * @var \App\Models\Student[] $children
 * @var \App\Models\Student|null $selectedChild
 * @var array<int, array> $childrenSummaries
 * @var array $recentAnnouncements
 * @var \App\Models\AcademicSession|null $currentSession
 * @var \App\Models\Term|null $currentTerm
 * @var \App\Core\UserContext $user
 */

$linkedChildren = $children ?? [];
$summaries = $childrenSummaries ?? [];
$announcements = $recentAnnouncements ?? [];
$parentName = $parent?->user?->name ?? ($parent?->userName ?: ($user->name ?? 'Guardian'));
$activeChildId = $selectedChild?->id ?? (!empty($linkedChildren) ? $linkedChildren[0]->id : null);

// Calculate overall average attendance across all linked children
$totalAttRates = [];
foreach ($summaries as $cSummary) {
    $att = $cSummary['attendanceSummary'] ?? null;
    $totalDays = (int)($att['total_days'] ?? $att['total_records'] ?? 0);
    $presentDays = (int)($att['present_days'] ?? $att['present_count'] ?? 0);
    if ($totalDays > 0) {
        $totalAttRates[] = ($presentDays / $totalDays) * 100;
    }
}
$avgAttRate = !empty($totalAttRates) ? round(array_sum($totalAttRates) / count($totalAttRates), 1) : null;

// Determine if results are released for any ward
$anyResultsPublished = false;
foreach ($summaries as $cSummary) {
    if (!empty($cSummary['isResultPublished'])) {
        $anyResultsPublished = true;
        break;
    }
}
?>

<div class="space-y-6">
    <!-- Header Card -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 rounded-2xl bg-brand-700 text-white font-extrabold text-xl flex items-center justify-center shadow-xs flex-shrink-0">
                    <?= strtoupper(substr($parentName, 0, 1)) ?>
                </div>
                <div>
                    <!-- Breadcrumbs -->
                    <nav class="flex items-center gap-2 text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">
                        <span>Guardian Portal</span>
                        <span class="text-slate-300">/</span>
                        <span class="text-slate-700">Family Overview</span>
                    </nav>
                    <div class="flex flex-wrap items-center gap-2.5">
                        <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight leading-tight">
                            Welcome, <?= htmlspecialchars($parentName) ?>!
                        </h1>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-lg text-xs font-bold bg-pink-50 text-pink-700 border border-pink-200">
                            Guardian Access
                        </span>
                    </div>
                    <div class="flex flex-wrap items-center gap-3 text-xs text-slate-500 mt-1">
                        <span>Academic Session: <strong class="text-slate-700"><?= htmlspecialchars($currentSession?->name ?? '2026/2027') ?></strong></span>
                        <span>&bull;</span>
                        <span class="text-emerald-700 font-semibold"><?= htmlspecialchars($currentTerm?->name ?? 'Active Term') ?></span>
                        <span>&bull;</span>
                        <span class="font-medium text-slate-600"><?= count($linkedChildren) ?> <?= count($linkedChildren) === 1 ? 'Ward Linked' : 'Wards Linked' ?></span>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-2.5 flex-wrap">
                <a href="/parent/announcements" 
                   class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-bold text-slate-700 bg-slate-100 hover:bg-slate-200 transition">
                    <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                    </svg>
                    <span>Notices & Bulletins</span>
                </a>
                <?php if ($activeChildId): ?>
                    <a href="/parent/children/<?= (int)$activeChildId ?>" 
                       class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-bold text-white bg-brand-600 hover:bg-brand-700 shadow-xs transition">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                        <span>Active Child File</span>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- 4-Card KPI Overview Metrics Strip -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Linked Wards -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs">
            <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Linked Wards</p>
            <div class="flex items-baseline gap-2 mt-1">
                <h3 class="text-2xl font-extrabold text-slate-900"><?= number_format(count($linkedChildren)) ?></h3>
                <span class="text-xs font-semibold text-slate-500"><?= count($linkedChildren) === 1 ? 'student' : 'students' ?></span>
            </div>
            <span class="text-[11px] font-medium text-brand-600 mt-1 block">
                Family Portfolio
            </span>
        </div>

        <!-- Academic Session & Term -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs">
            <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Current Term</p>
            <div class="flex items-baseline gap-2 mt-1">
                <h3 class="text-base font-extrabold text-slate-900 truncate">
                    <?= htmlspecialchars($currentTerm?->name ?? '1st Term') ?>
                </h3>
            </div>
            <span class="text-[11px] font-semibold text-emerald-600 mt-1 block flex items-center gap-1">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                <span><?= htmlspecialchars($currentSession?->name ?? '2026/2027') ?> &bull; In Session</span>
            </span>
        </div>

        <!-- Attendance Overview -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs">
            <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Term Attendance</p>
            <div class="flex items-baseline gap-2 mt-1">
                <?php if ($avgAttRate !== null): ?>
                    <h3 class="text-2xl font-extrabold <?= $avgAttRate >= 80 ? 'text-emerald-700' : ($avgAttRate >= 65 ? 'text-amber-700' : 'text-rose-700') ?>">
                        <?= $avgAttRate ?>%
                    </h3>
                    <span class="text-xs font-semibold text-slate-500">family avg</span>
                <?php else: ?>
                    <h3 class="text-lg font-bold text-slate-400">Pending</h3>
                <?php endif; ?>
            </div>
            <span class="text-[11px] font-medium text-slate-500 mt-1 block">
                Punctuality & Presence
            </span>
        </div>

        <!-- Official Results Status -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs">
            <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Terminal Results</p>
            <div class="mt-1">
                <?php if ($anyResultsPublished): ?>
                    <span class="inline-flex items-center gap-1 text-xs font-bold text-emerald-700 bg-emerald-50 px-2.5 py-1 rounded-lg border border-emerald-200">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        <span>Published Live</span>
                    </span>
                <?php else: ?>
                    <span class="inline-flex items-center text-xs font-bold text-amber-800 bg-amber-50 px-2.5 py-1 rounded-lg border border-amber-200">
                        Awaiting Verification
                    </span>
                <?php endif; ?>
            </div>
            <span class="text-[11px] font-medium text-slate-500 mt-1 block">
                Continuous Evaluation
            </span>
        </div>
    </div>

    <?php if (empty($linkedChildren)): ?>
        <!-- Empty State Card -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-12 text-center">
            <div class="w-16 h-16 rounded-2xl bg-pink-50 text-pink-600 mx-auto flex items-center justify-center mb-4">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                </svg>
            </div>
            <h3 class="text-base font-bold text-slate-900">No Linked Students Found</h3>
            <p class="text-xs text-slate-500 mt-1.5 max-w-md mx-auto">
                Your guardian account is not currently linked to any enrolled student profiles. Please contact the school administrative office to link your wards.
            </p>
            <div class="mt-6">
                <a href="mailto:support@claretacademy.edu" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold bg-slate-100 hover:bg-slate-200 text-slate-700 transition">
                    <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                    <span>support@claretacademy.edu</span>
                </a>
            </div>
        </div>
    <?php else: ?>

        <!-- Children Overview Cards Grid -->
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-base font-bold text-slate-900 flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-brand-500"></span>
                        <span>Your Linked Wards</span>
                        <span class="text-xs font-bold px-2 py-0.5 rounded-full bg-slate-100 text-slate-600">
                            <?= count($linkedChildren) ?>
                        </span>
                    </h2>
                    <p class="text-xs text-slate-500 mt-0.5">Select any student card to inspect detailed continuous assessments, attendance, and coursework.</p>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <?php foreach ($linkedChildren as $child): 
                    $summary = $summaries[$child->id] ?? null;
                    $att = $summary['attendanceSummary'] ?? null;
                    $ts = $summary['termSummary'] ?? null;
                    $isPublished = $summary['isResultPublished'] ?? false;
                    $recentAsgns = $summary['recentAssignments'] ?? [];
                    
                    $totalDays = (int)($att['total_days'] ?? $att['total_records'] ?? 0);
                    $presentDays = (int)($att['present_days'] ?? $att['present_count'] ?? 0);
                    $attRate = $totalDays > 0 ? round(($presentDays / $totalDays) * 100, 1) : null;
                    $isActiveChild = ($activeChildId === $child->id);

                    $childClass = $child->currentClass?->name ?? ($child->className ?: 'Class Assigned');
                    $arm = !empty($child->currentClass?->sectionArm) ? ' (' . $child->currentClass->sectionArm . ')' : '';
                    $fullClassName = $childClass . $arm;
                ?>
                    <div class="bg-white rounded-2xl border <?= $isActiveChild ? 'border-brand-300 ring-1 ring-brand-300' : 'border-slate-200' ?> shadow-xs hover:border-slate-300 transition flex flex-col justify-between p-6 space-y-5">
                        <div class="space-y-4">
                            <!-- Card Header -->
                            <div class="flex items-start justify-between gap-4 pb-4 border-b border-slate-100">
                                <div class="flex items-center gap-3 min-w-0">
                                    <div class="w-12 h-12 rounded-2xl <?= $isActiveChild ? 'bg-brand-600 text-white' : 'bg-slate-100 text-slate-700' ?> font-extrabold text-base flex items-center justify-center flex-shrink-0 shadow-xs">
                                        <?= strtoupper(substr($child->name, 0, 1)) ?>
                                    </div>
                                    <div class="min-w-0">
                                        <h3 class="text-base font-extrabold text-slate-900 leading-snug truncate">
                                            <a href="/parent/children/<?= (int)$child->id ?>" class="hover:text-brand-600 transition">
                                                <?= htmlspecialchars($child->name) ?>
                                            </a>
                                        </h3>
                                        <div class="flex flex-wrap items-center gap-2 text-xs text-slate-500 mt-1">
                                            <span class="font-mono font-bold text-slate-700 bg-slate-100 px-2 py-0.5 rounded-md text-[11px]">
                                                Adm: <?= htmlspecialchars($child->admissionNumber) ?>
                                            </span>
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-bold bg-sky-50 text-sky-800 border border-sky-200">
                                                <?= htmlspecialchars($fullClassName) ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <div class="flex-shrink-0">
                                    <?php if ($isActiveChild): ?>
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-bold bg-brand-50 text-brand-700 border border-brand-200">
                                            <span class="w-1.5 h-1.5 rounded-full bg-brand-600"></span>
                                            <span>Active Focus</span>
                                        </span>
                                    <?php else: ?>
                                        <form action="/parent/children/<?= (int)$child->id ?>/select" method="POST">
                                            <?= csrf_field() ?>
                                            <button type="submit" 
                                                    class="inline-flex items-center gap-1 px-3 py-1 rounded-lg text-xs font-bold bg-slate-100 hover:bg-brand-600 hover:text-white text-slate-700 transition shadow-xs">
                                                <span>Switch Focus</span>
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                                                </svg>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Performance Metrics Quick Glance -->
                            <div class="grid grid-cols-2 gap-3">
                                <!-- Attendance Rate -->
                                <div class="bg-slate-50 rounded-xl p-3.5 border border-slate-100">
                                    <div class="flex items-center justify-between text-xs text-slate-500 mb-1">
                                        <span class="font-bold text-[11px] uppercase tracking-wider text-slate-400">Attendance</span>
                                        <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                        </svg>
                                    </div>
                                    <?php if ($attRate !== null): ?>
                                        <div class="flex items-baseline gap-1.5">
                                            <span class="text-xl font-extrabold <?= $attRate >= 80 ? 'text-emerald-700' : ($attRate >= 65 ? 'text-amber-700' : 'text-rose-700') ?>">
                                                <?= $attRate ?>%
                                            </span>
                                            <span class="text-[11px] text-slate-400 font-mono">
                                                (<?= $presentDays ?>/<?= $totalDays ?>d)
                                            </span>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-xs text-slate-400 italic">No attendance yet</span>
                                    <?php endif; ?>
                                </div>

                                <!-- Academic Result Summary -->
                                <div class="bg-slate-50 rounded-xl p-3.5 border border-slate-100">
                                    <div class="flex items-center justify-between text-xs text-slate-500 mb-1">
                                        <span class="font-bold text-[11px] uppercase tracking-wider text-slate-400">Terminal Score</span>
                                        <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                        </svg>
                                    </div>
                                    <?php if ($isPublished && $ts): ?>
                                        <div class="flex items-baseline gap-1.5">
                                            <span class="text-xl font-extrabold text-brand-700">
                                                <?= number_format((float)($ts->averageScore ?? 0), 1) ?>%
                                            </span>
                                            <?php if ($ts->rankInClass): ?>
                                                <span class="text-[11px] font-bold text-slate-600 bg-white px-1.5 py-0.5 rounded border border-slate-200">
                                                    #<?= (int)$ts->rankInClass ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    <?php else: ?>
                                        <span class="inline-flex items-center text-[11px] text-amber-800 font-semibold bg-amber-50 px-2 py-0.5 rounded border border-amber-200">
                                            Pending Release
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Recent Coursework Glance -->
                            <?php if (!empty($recentAsgns)): ?>
                                <div class="space-y-2 pt-1">
                                    <span class="text-[10px] font-extrabold text-slate-400 uppercase tracking-wider block">Recent Coursework Tasks</span>
                                    <div class="space-y-1.5">
                                        <?php foreach ($recentAsgns as $item): 
                                            $asgn = $item['assignment'];
                                            $sub = $item['submission'];
                                        ?>
                                            <div class="flex items-center justify-between text-xs p-2.5 rounded-xl bg-slate-50 border border-slate-100">
                                                <span class="truncate max-w-[220px] font-medium text-slate-800" title="<?= htmlspecialchars($asgn->title) ?>">
                                                    <?= htmlspecialchars($asgn->title) ?>
                                                </span>
                                                <div>
                                                    <?php if ($sub && $sub->score !== null): ?>
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-bold bg-emerald-50 text-emerald-800 border border-emerald-200 font-mono">
                                                            <?= (float)$sub->score ?> / <?= (float)$asgn->maxScore ?> PTS
                                                        </span>
                                                    <?php elseif ($sub): ?>
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-bold bg-sky-50 text-sky-800 border border-sky-200">
                                                            Turned In
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-bold bg-amber-50 text-amber-800 border border-amber-200">
                                                            Due
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Card Action Buttons Strip -->
                        <div class="pt-4 border-t border-slate-100 grid grid-cols-4 gap-2 text-center">
                            <a href="/parent/children/<?= (int)$child->id ?>" 
                               class="py-2 px-1 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition flex flex-col items-center justify-center gap-1">
                                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                                <span>Profile</span>
                            </a>
                            <a href="/parent/children/<?= (int)$child->id ?>/grades" 
                               class="py-2 px-1 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition flex flex-col items-center justify-center gap-1">
                                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                <span>Reports</span>
                            </a>
                            <a href="/parent/children/<?= (int)$child->id ?>/attendance" 
                               class="py-2 px-1 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition flex flex-col items-center justify-center gap-1">
                                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                <span>Attendance</span>
                            </a>
                            <a href="/parent/children/<?= (int)$child->id ?>/assignments" 
                               class="py-2 px-1 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition flex flex-col items-center justify-center gap-1">
                                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                </svg>
                                <span>Tasks</span>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Official Notices & Bulletins Section -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6 space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-3 border-b border-slate-100 gap-2">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-brand-50 text-brand-700 flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-slate-900 leading-snug">Official School Bulletins & Notices</h3>
                        <p class="text-xs text-slate-500">School-wide notices and enrolled classroom communications</p>
                    </div>
                </div>

                <a href="/parent/announcements" class="text-xs font-bold text-brand-600 hover:text-brand-700 transition flex items-center gap-1">
                    <span>View All Notices</span>
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                </a>
            </div>

            <?php if (empty($announcements)): ?>
                <div class="text-center py-8 text-slate-400 text-xs italic">
                    <p>No active announcements posted at this time.</p>
                </div>
            <?php else: ?>
                <div class="divide-y divide-slate-100">
                    <?php foreach ($announcements as $item): 
                        $title = is_object($item) ? $item->title : ($item['title'] ?? '');
                        $body = is_object($item) ? $item->body : ($item['body'] ?? '');
                        $scope = is_object($item) ? $item->scope : ($item['scope'] ?? 'school');
                        $isRead = is_object($item) ? $item->isRead : !empty($item['read_at']);
                        $publishedDate = is_object($item) ? ($item->publishedAt ?? $item->createdAt) : ($item['published_at'] ?? $item['created_at'] ?? null);
                        $targetName = is_object($item) ? ($item->targetName ?? ($scope === 'school' ? 'School-wide' : 'Class Notice')) : ($item['target_name'] ?? ($scope === 'school' ? 'School-wide' : 'Class Notice'));
                    ?>
                        <div class="py-3.5 flex items-start justify-between gap-4">
                            <div class="space-y-1 min-w-0">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold uppercase tracking-wider <?= $scope === 'school' ? 'bg-purple-50 text-purple-700 border border-purple-200' : 'bg-cyan-50 text-cyan-800 border border-cyan-200' ?>">
                                        <?= htmlspecialchars($targetName) ?>
                                    </span>
                                    <h4 class="text-sm font-bold text-slate-900 truncate">
                                        <?= htmlspecialchars($title) ?>
                                    </h4>
                                    <?php if (!$isRead): ?>
                                        <span class="w-2 h-2 rounded-full bg-brand-600 animate-pulse"></span>
                                    <?php endif; ?>
                                </div>
                                <p class="text-xs text-slate-600 line-clamp-2 leading-relaxed">
                                    <?= htmlspecialchars($body) ?>
                                </p>
                                <span class="text-[11px] text-slate-400 block font-mono">
                                    <?= date('M d, Y', strtotime($publishedDate ?: 'now')) ?> &bull; <?= date('g:i A', strtotime($publishedDate ?: 'now')) ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>
