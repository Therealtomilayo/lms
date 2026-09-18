<?php
/**
 * Shared View Component: Locked Activity Banner & Unmet Prerequisites Display
 *
 * Variables:
 * - $title: Activity title
 * - $unmet: Array of unmet prerequisite details (from PrerequisiteService::getPrerequisiteStatus()['unmet'])
 * - $allPrerequisites: Array of all prerequisites with status (optional)
 * - $backUrl: URL to return to
 */
$allPrerequisites = $allPrerequisites ?? [];
$unmet = $unmet ?? [];
$backUrl = $backUrl ?? '/student/dashboard';
?>
<div class="bg-amber-50/90 border border-amber-200 rounded-2xl p-6 sm:p-8 space-y-5 shadow-xs">
    <div class="flex items-start gap-4">
        <div class="w-12 h-12 rounded-2xl bg-amber-100 text-amber-700 flex items-center justify-center flex-shrink-0">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
            </svg>
        </div>
        <div class="space-y-1">
            <div class="flex items-center gap-2 flex-wrap">
                <h3 class="text-base font-bold text-amber-900">Activity Locked</h3>
                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-amber-200 text-amber-900 uppercase tracking-wider">
                    Prerequisites Required
                </span>
            </div>
            <p class="text-xs text-amber-800 leading-relaxed">
                You must complete all prerequisite learning activities before you can start or view this content.
            </p>
        </div>
    </div>

    <!-- Prerequisites List -->
    <div class="bg-white rounded-xl border border-amber-200 divide-y divide-slate-100 overflow-hidden text-xs">
        <div class="px-4 py-2.5 bg-amber-100/50 text-[11px] font-bold uppercase tracking-wider text-amber-900">
            Required Activities (<?= count(array_filter($allPrerequisites, fn($p) => !empty($p['is_completed']))) ?> of <?= count($allPrerequisites) ?> completed)
        </div>
        <?php foreach ($allPrerequisites as $p): ?>
            <div class="px-4 py-3 flex items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <?php if (!empty($p['is_completed'])): ?>
                        <span class="w-6 h-6 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center text-xs font-bold flex-shrink-0">
                            ✓
                        </span>
                    <?php else: ?>
                        <span class="w-6 h-6 rounded-full bg-amber-100 text-amber-700 flex items-center justify-center text-xs font-bold flex-shrink-0">
                            🔒
                        </span>
                    <?php endif; ?>
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="font-bold <?= !empty($p['is_completed']) ? 'text-slate-700 line-through' : 'text-slate-900' ?>">
                                <?= htmlspecialchars($p['title'] ?? 'Prerequisite Activity') ?>
                            </span>
                            <span class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider">
                                (<?= htmlspecialchars(ucfirst(str_replace('_', ' ', $p['type'] ?? 'activity'))) ?>)
                            </span>
                        </div>
                        <?php if (!empty($p['is_completed'])): ?>
                            <p class="text-[11px] text-emerald-600 font-medium mt-0.5">
                                Completed ✓
                            </p>
                        <?php else: ?>
                            <p class="text-[11px] text-amber-700 font-medium mt-0.5">
                                Incomplete &bull; Must be completed first
                            </p>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="flex-shrink-0">
                    <?php if (!empty($p['is_completed'])): ?>
                        <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                            Satisfied
                        </span>
                    <?php else: ?>
                        <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold bg-amber-50 text-amber-800 border border-amber-200">
                            Required
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="flex items-center justify-between pt-1">
        <a href="<?= htmlspecialchars($backUrl) ?>" class="inline-flex items-center gap-1.5 px-4 py-2 bg-white hover:bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold text-slate-700 transition">
            &larr; Return to Overview
        </a>
    </div>
</div>
