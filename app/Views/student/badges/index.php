<?php
/**
 * Student Badges & Honors Showcase Screen (SRS §33, §57 Phase 3)
 * Displays all gamified credentials, commendations, and achievements earned by the student.
 *
 * @var \App\Models\Student|null $student
 * @var \App\Models\StudentBadge[] $earnedBadges
 * @var \App\Models\Badge[] $allBadges
 * @var int[] $earnedBadgeIds
 * @var \App\Models\AcademicSession|null $activeSession
 * @var \App\Core\UserContext $user
 */

$totalEarned = count($earnedBadges);
?>

<div class="space-y-6">
    <!-- Header Banner -->
    <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-xs">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <div class="flex items-center gap-2 text-xs font-semibold text-slate-400 mb-1">
                    <a href="/student/dashboard" class="hover:text-brand-600 transition">Dashboard</a>
                    <span>&rsaquo;</span>
                    <span class="text-slate-700 font-bold">My Achievements</span>
                </div>
                <h1 class="text-2xl font-black text-slate-900 tracking-tight flex items-center gap-2.5">
                    <span>🏆</span>
                    Honors & Achievement Badges
                </h1>
                <p class="text-sm text-slate-500 mt-1">Digital credentials, verified course milestones, and faculty commendations.</p>
            </div>

            <div class="flex items-center gap-3">
                <div class="px-4 py-2.5 rounded-xl bg-amber-50 border border-amber-200 text-amber-900 flex items-center gap-3 shadow-xs">
                    <span class="text-2xl font-black text-amber-600 leading-none"><?= $totalEarned ?></span>
                    <div class="text-left">
                        <div class="text-xs font-bold uppercase tracking-wider text-amber-800">Badges Earned</div>
                        <div class="text-[11px] text-amber-700 font-medium">Keep learning to unlock more</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Section 1: Earned Badges Showcase -->
    <div class="space-y-4">
        <h2 class="text-base font-bold text-slate-900 flex items-center gap-2 px-1">
            <span>Earned Achievements</span>
            <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-800">
                <?= $totalEarned ?>
            </span>
        </h2>

        <?php if (empty($earnedBadges)): ?>
            <div class="bg-white rounded-xl border border-slate-200 p-12 text-center shadow-xs">
                <div class="w-14 h-14 rounded-2xl bg-amber-50 text-amber-500 flex items-center justify-center mx-auto mb-3">
                    <span class="text-2xl">🎖️</span>
                </div>
                <h3 class="text-base font-bold text-slate-900">No badges earned yet</h3>
                <p class="text-xs text-slate-500 mt-1 max-w-sm mx-auto">
                    Complete course modules, score high on evaluations, and maintain exemplary attendance to receive digital badges from your instructors!
                </p>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                <?php foreach ($earnedBadges as $sb): ?>
                    <div class="bg-white rounded-xl border border-amber-200 p-5 shadow-xs relative overflow-hidden flex flex-col justify-between hover:shadow-md transition">
                        <div class="absolute top-0 right-0 w-16 h-16 bg-gradient-to-bl from-amber-100 to-transparent pointer-events-none"></div>
                        
                        <div>
                            <div class="flex items-center justify-between gap-2 mb-3">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-bold bg-amber-50 text-amber-800 border border-amber-200">
                                    <?= ucfirst(htmlspecialchars($sb->badge->category ?? 'General')) ?>
                                </span>
                                <span class="text-[11px] font-semibold text-slate-400">
                                    <?= date('M j, Y', strtotime($sb->awardedAt)) ?>
                                </span>
                            </div>

                            <div class="flex items-center gap-3 mb-3">
                                <div class="w-12 h-12 rounded-xl bg-amber-500 text-white flex items-center justify-center font-bold text-2xl shadow-xs flex-shrink-0">
                                    🏆
                                </div>
                                <div>
                                    <h3 class="text-base font-black text-slate-900 leading-tight">
                                        <?= htmlspecialchars($sb->badge->name ?? 'Achievement') ?>
                                    </h3>
                                    <?php if ($sb->classSubject && $sb->classSubject->subject): ?>
                                        <p class="text-xs font-semibold text-brand-600 mt-0.5">
                                            <?= htmlspecialchars($sb->classSubject->subject->name) ?>
                                        </p>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <p class="text-xs text-slate-600 leading-relaxed mb-3">
                                <?= htmlspecialchars($sb->badge->description ?? '') ?>
                            </p>
                        </div>

                        <?php if (!empty($sb->reason)): ?>
                            <div class="mt-2 pt-3 border-t border-slate-100 bg-slate-50/60 -mx-5 -mb-5 p-3 px-5 rounded-b-xl">
                                <div class="text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">Citation</div>
                                <p class="text-xs italic text-slate-700">
                                    &ldquo;<?= htmlspecialchars($sb->reason) ?>&rdquo;
                                </p>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Section 2: Catalog of Available Badges -->
    <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-xs space-y-4">
        <div>
            <h2 class="text-base font-bold text-slate-900">Institution Badge Catalog</h2>
            <p class="text-xs text-slate-500 mt-0.5">Explore honors you can unlock throughout this academic term.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <?php foreach ($allBadges as $badge): ?>
                <?php $isUnlocked = in_array((int)$badge->id, $earnedBadgeIds, true); ?>
                <div class="p-4 rounded-xl border <?= $isUnlocked ? 'border-emerald-200 bg-emerald-50/20' : 'border-slate-200 bg-slate-50/40 opacity-75' ?> transition">
                    <div class="flex items-center justify-between gap-2 mb-2">
                        <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                            <?= ucfirst(htmlspecialchars($badge->category)) ?>
                        </span>
                        <?php if ($isUnlocked): ?>
                            <span class="inline-flex items-center gap-1 text-[11px] font-bold text-emerald-700">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                Unlocked
                            </span>
                        <?php else: ?>
                            <span class="inline-flex items-center gap-1 text-[11px] font-semibold text-slate-400">
                                🔒 Locked
                            </span>
                        <?php endif; ?>
                    </div>

                    <h4 class="text-sm font-bold text-slate-900 mb-1">
                        <?= htmlspecialchars($badge->name) ?>
                    </h4>
                    <p class="text-xs text-slate-600 leading-relaxed">
                        <?= htmlspecialchars($badge->description) ?>
                    </p>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
