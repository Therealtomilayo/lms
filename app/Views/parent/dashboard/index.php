<?php
$linkedChildren = $children ?? [];
$summaries = $childrenSummaries ?? [];
$announcements = $recentAnnouncements ?? [];
?>

<div class="space-y-8">
    <!-- Welcome Banner -->
    <div class="bg-gradient-to-r from-slate-900 to-brand-700 rounded-2xl p-6 md:p-8 text-white shadow-lg relative overflow-hidden">
        <div class="relative z-10 max-w-2xl">
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-white/20 text-white mb-3 backdrop-blur-sm">
                Academic Year <?= e($currentSession ? $currentSession->name : '2025/2026') ?> • <?= e($currentTerm ? $currentTerm->name : 'Active Term') ?>
            </span>
            <h2 class="text-2xl md:text-3xl font-bold tracking-tight text-white mb-2">
                Welcome, <?= e($parent ? ($parent->userName ?: 'Guardian') : 'Guardian') ?>
            </h2>
            <p class="text-slate-200 text-sm leading-relaxed">
                Stay closely engaged with your child's academic journey. Monitor published report cards, track daily roll-call attendance, and keep up with classroom coursework and notices.
            </p>
        </div>
        <div class="absolute -right-10 -bottom-10 w-48 h-48 rounded-full bg-brand-600/20 blur-2xl pointer-events-none"></div>
    </div>

    <?php if (empty($linkedChildren)): ?>
        <!-- Empty State -->
        <div class="bg-white rounded-2xl border border-slate-200 p-12 text-center shadow-sm">
            <div class="w-16 h-16 bg-brand-100 text-brand-600 rounded-2xl flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
            </div>
            <h3 class="text-lg font-bold text-slate-900 mb-1">No Linked Students Found</h3>
            <p class="text-sm text-slate-500 max-w-md mx-auto mb-6">
                Your parent portal account is not yet associated with any enrolled students. Please reach out to the school administrative office to link your guardian profile.
            </p>
            <div class="inline-flex items-center gap-2 px-4 py-2 bg-slate-100 rounded-lg text-xs font-semibold text-slate-700">
                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                support@claretacademy.edu
            </div>
        </div>
    <?php else: ?>

        <!-- Children Overview Cards Grid -->
        <div>
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-bold text-slate-900">Your Linked Students (<?= count($linkedChildren) ?>)</h3>
                <span class="text-xs text-slate-500">Select any card to view detailed records</span>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <?php foreach ($linkedChildren as $child): 
                    $summary = $summaries[$child->id] ?? null;
                    $att = $summary['attendanceSummary'] ?? null;
                    $ts = $summary['termSummary'] ?? null;
                    $isPublished = $summary['isResultPublished'] ?? false;
                    $recentAsgns = $summary['recentAssignments'] ?? [];
                    $attRate = $att && ($att['total_days'] ?? $att['total_records'] ?? 0) > 0 
                        ? round((($att['present_days'] ?? $att['present_count'] ?? 0) / ($att['total_days'] ?? $att['total_records'] ?? 1)) * 100, 1) 
                        : null;
                ?>
                    <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm hover:shadow-md transition-all duration-200 flex flex-col justify-between">
                        <div>
                            <!-- Card Header -->
                            <div class="flex items-start justify-between gap-4 pb-4 border-b border-slate-100">
                                <div class="flex items-center gap-3">
                                    <div class="w-12 h-12 rounded-xl bg-brand-100 text-brand-700 flex items-center justify-center font-bold text-lg flex-shrink-0">
                                        <?= e(substr($child->name, 0, 1)) ?>
                                    </div>
                                    <div>
                                        <h4 class="font-bold text-slate-900 text-base leading-tight">
                                            <a href="/parent/children/<?= (int)$child->id ?>" class="hover:text-brand-600 transition">
                                                <?= e($child->name) ?>
                                            </a>
                                        </h4>
                                        <div class="flex items-center gap-2 text-xs text-slate-500 mt-1">
                                            <span class="font-mono">Adm: <?= e($child->admissionNumber) ?></span>
                                            <span>•</span>
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-slate-100 text-slate-700">
                                                <?= e($child->className ?: 'Class Assigned') ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <form action="/parent/children/<?= (int)$child->id ?>/select" method="POST">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="px-3 py-1.5 text-xs font-semibold rounded-lg bg-slate-100 hover:bg-brand-600 hover:text-white text-slate-700 transition">
                                        Switch Focus &rarr;
                                    </button>
                                </form>
                            </div>

                            <!-- Performance Quick Glance Metrics -->
                            <div class="grid grid-cols-2 gap-4 my-5">
                                <!-- Attendance Rate -->
                                <div class="bg-slate-50 rounded-xl p-3.5 border border-slate-100">
                                    <div class="flex items-center justify-between text-xs text-slate-500 mb-1">
                                        <span class="font-medium">Term Attendance</span>
                                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    </div>
                                    <?php if ($attRate !== null): ?>
                                        <div class="flex items-baseline gap-2">
                                            <span class="text-xl font-extrabold <?= $attRate >= 80 ? 'text-emerald-700' : ($attRate >= 65 ? 'text-amber-700' : 'text-rose-700') ?>">
                                                <?= $attRate ?>%
                                            </span>
                                            <span class="text-[11px] text-slate-500">
                                                (<?= (int)($att['present_days'] ?? $att['present_count'] ?? 0) ?>/<?= (int)($att['total_days'] ?? $att['total_records'] ?? 0) ?> days)
                                            </span>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-xs text-slate-400 italic">No attendance records yet</span>
                                    <?php endif; ?>
                                </div>

                                <!-- Academic Result Summary -->
                                <div class="bg-slate-50 rounded-xl p-3.5 border border-slate-100">
                                    <div class="flex items-center justify-between text-xs text-slate-500 mb-1">
                                        <span class="font-medium">Term Performance</span>
                                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                    </div>
                                    <?php if ($isPublished && $ts): ?>
                                        <div class="flex items-baseline gap-2">
                                            <span class="text-xl font-extrabold text-brand-700">
                                                <?= number_format((float)($ts->averageScore ?? 0), 1) ?>%
                                            </span>
                                            <?php if ($ts->rankInClass): ?>
                                                <span class="text-[11px] font-semibold text-slate-600">
                                                    Rank: #<?= (int)$ts->rankInClass ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    <?php else: ?>
                                        <span class="inline-flex items-center text-xs text-amber-700 font-medium bg-amber-50 px-2 py-0.5 rounded">
                                            Awaiting Official Release
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Recent Coursework Glance -->
                            <?php if (!empty($recentAsgns)): ?>
                                <div class="mb-4">
                                    <span class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider block mb-2">Recent Tasks</span>
                                    <div class="space-y-1.5">
                                        <?php foreach ($recentAsgns as $item): 
                                            $asgn = $item['assignment'];
                                            $sub = $item['submission'];
                                        ?>
                                            <div class="flex items-center justify-between text-xs p-2 rounded-lg bg-slate-50 border border-slate-100">
                                                <span class="truncate max-w-[200px] font-medium text-slate-800"><?= e($asgn->title) ?></span>
                                                <?php if ($sub && $sub->score !== null): ?>
                                                    <span class="text-[11px] font-bold text-emerald-700 bg-emerald-50 px-1.5 py-0.5 rounded">
                                                        <?= (float)$sub->score ?> / <?= (float)$asgn->maxScore ?>
                                                    </span>
                                                <?php elseif ($sub): ?>
                                                    <span class="text-[11px] text-blue-700 bg-blue-50 px-1.5 py-0.5 rounded font-medium">Submitted</span>
                                                <?php else: ?>
                                                    <span class="text-[11px] text-amber-700 bg-amber-50 px-1.5 py-0.5 rounded font-medium">Pending</span>
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Card Action Buttons -->
                        <div class="pt-4 border-t border-slate-100 grid grid-cols-4 gap-2 text-center text-xs font-semibold">
                            <a href="/parent/children/<?= (int)$child->id ?>" class="p-2 rounded-lg bg-slate-100 hover:bg-brand-100 hover:text-brand-700 transition">
                                Profile
                            </a>
                            <a href="/parent/children/<?= (int)$child->id ?>/grades" class="p-2 rounded-lg bg-slate-100 hover:bg-brand-100 hover:text-brand-700 transition">
                                Report Card
                            </a>
                            <a href="/parent/children/<?= (int)$child->id ?>/attendance" class="p-2 rounded-lg bg-slate-100 hover:bg-brand-100 hover:text-brand-700 transition">
                                Attendance
                            </a>
                            <a href="/parent/children/<?= (int)$child->id ?>/assignments" class="p-2 rounded-lg bg-slate-100 hover:bg-brand-100 hover:text-brand-700 transition">
                                Coursework
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Recent School & Class Announcements Section -->
        <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
            <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-100">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg bg-accent-500/10 text-accent-600 flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-slate-900">Official Notices & Announcements</h3>
                        <p class="text-xs text-slate-500">School-wide broadcasts and enrolled classroom updates</p>
                    </div>
                </div>
                <a href="/parent/announcements" class="text-xs font-semibold text-brand-600 hover:underline">
                    View All &rarr;
                </a>
            </div>

            <?php if (empty($announcements)): ?>
                <div class="text-center py-8 text-slate-400 text-sm">
                    <p>No announcements published at this time.</p>
                </div>
            <?php else: ?>
                <div class="divide-y divide-slate-100">
                    <?php foreach ($announcements as $item): 
                        $isRead = !empty($item['read_at']);
                    ?>
                        <div class="py-3.5 flex items-start justify-between gap-4">
                            <div class="space-y-1 min-w-0">
                                <div class="flex items-center gap-2">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider <?= $item['scope'] === 'school' ? 'bg-purple-100 text-purple-800' : 'bg-cyan-100 text-cyan-800' ?>">
                                        <?= e($item['scope']) ?>
                                    </span>
                                    <h4 class="text-sm font-semibold text-slate-900 truncate <?= !$isRead ? 'font-bold' : '' ?>">
                                        <?= e($item['title']) ?>
                                    </h4>
                                    <?php if (!$isRead): ?>
                                        <span class="w-2 h-2 rounded-full bg-brand-600"></span>
                                    <?php endif; ?>
                                </div>
                                <p class="text-xs text-slate-600 line-clamp-2"><?= nl2br(e($item['body'])) ?></p>
                                <span class="text-[11px] text-slate-400 block"><?= e(date('M d, Y • h:i A', strtotime($item['published_at'] ?? $item['created_at']))) ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>
