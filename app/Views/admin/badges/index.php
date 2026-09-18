<?php
/**
 * ADMIN-34 — Badges & Rewards Central Management (SRS §33, §57 Phase 3)
 *
 * @var \App\Models\Badge[] $badges
 * @var \App\Models\SchoolClass[] $classes
 * @var \App\Models\StudentBadge[] $awardedBadges
 * @var \App\Models\Student[] $students
 * @var int|null $selectedBadgeId
 * @var int|null $selectedClassId
 * @var string $csrf_token
 */

$categoryStyles = [
    'academic' => 'bg-brand-50 text-brand-700 border-brand-200',
    'attendance' => 'bg-sky-50 text-sky-700 border-sky-200',
    'progression' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
    'citizenship' => 'bg-purple-50 text-purple-700 border-purple-200',
];
?>
<div class="space-y-6">

    <!-- Page Header Card -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-6 rounded-2xl border border-slate-200 shadow-xs">
        <div>
            <div class="flex items-center gap-2 text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">
                <a href="/admin/dashboard" class="hover:text-brand-600 transition">Admin Portal</a>
                <span>/</span>
                <span class="text-slate-700 font-bold">Badges &amp; Rewards</span>
            </div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight">Institutional Honors &amp; Gamification</h1>
            <p class="text-xs text-slate-500 mt-1">
                Manage school achievement badges, review awarded honors across classes, or award new digital commendations.
            </p>
        </div>
        <div class="flex items-center gap-3">
            <button type="button" onclick="document.getElementById('awardBadgeModal').classList.remove('hidden')" 
                    class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold bg-brand-600 text-white hover:bg-brand-700 shadow-xs transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                </svg>
                Award Student Badge
            </button>
        </div>
    </div>

    <!-- Section 1: Badges Catalog Grid -->
    <div class="space-y-3">
        <div class="flex items-center justify-between">
            <h2 class="text-sm font-extrabold uppercase tracking-wider text-slate-700">Official Badge Definitions Catalog</h2>
            <span class="text-xs text-slate-500 font-medium"><?= count($badges) ?> badges registered</span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <?php foreach ($badges as $badge): ?>
                <?php 
                    $catClass = $categoryStyles[$badge->category] ?? 'bg-slate-50 text-slate-700 border-slate-200';
                ?>
                <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs flex flex-col justify-between hover:border-brand-300 transition">
                    <div>
                        <div class="flex items-start justify-between gap-3 mb-3">
                            <div class="w-11 h-11 rounded-xl bg-amber-50 border border-amber-200 text-amber-600 flex items-center justify-center flex-shrink-0">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" />
                                </svg>
                            </div>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold uppercase tracking-wider border <?= $catClass ?>">
                                <?= htmlspecialchars(ucfirst($badge->category)) ?>
                            </span>
                        </div>
                        <h3 class="text-base font-extrabold text-slate-900 leading-snug">
                            <?= htmlspecialchars($badge->name) ?>
                        </h3>
                        <p class="text-xs text-slate-500 mt-1 line-clamp-2 leading-relaxed">
                            <?= htmlspecialchars($badge->description) ?>
                        </p>
                    </div>

                    <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-[11px] text-slate-400">
                        <span>Slug: <code><?= htmlspecialchars($badge->slug) ?></code></span>
                        <span><?= $badge->isSystem ? 'System Auto/Manual' : 'Custom' ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Section 2: School-wide Awarded Badges History Ledger -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6 space-y-5">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h2 class="text-base font-bold text-slate-900">Awarded Badges Register</h2>
                <p class="text-xs text-slate-500 mt-0.5">School-wide audit trail of student commendations and earned digital achievements.</p>
            </div>

            <!-- Filter Controls -->
            <form method="GET" action="/admin/badges" class="flex flex-wrap items-center gap-2">
                <select name="class_id" onchange="this.form.submit()" class="px-3 py-1.5 text-xs font-bold rounded-xl border border-slate-300 bg-slate-50 text-slate-700">
                    <option value="">All Classes</option>
                    <?php foreach ($classes as $c): ?>
                        <option value="<?= (int)$c->id ?>" <?= $selectedClassId === (int)$c->id ? 'selected' : '' ?>>
                            <?= htmlspecialchars($c->name) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <select name="badge_id" onchange="this.form.submit()" class="px-3 py-1.5 text-xs font-bold rounded-xl border border-slate-300 bg-slate-50 text-slate-700">
                    <option value="">All Badges</option>
                    <?php foreach ($badges as $b): ?>
                        <option value="<?= (int)$b->id ?>" <?= $selectedBadgeId === (int)$b->id ? 'selected' : '' ?>>
                            <?= htmlspecialchars($b->name) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <?php if ($selectedClassId || $selectedBadgeId): ?>
                    <a href="/admin/badges" class="text-xs font-bold text-brand-600 hover:text-brand-700 ml-1">Reset</a>
                <?php endif; ?>
            </form>
        </div>

        <?php if (empty($awardedBadges)): ?>
            <div class="text-center py-12 border-2 border-dashed border-slate-200 rounded-xl">
                <div class="w-12 h-12 rounded-2xl bg-amber-50 text-amber-500 mx-auto flex items-center justify-center mb-3">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <h3 class="text-sm font-bold text-slate-900">No Badges Awarded Matching Filter</h3>
                <p class="text-xs text-slate-500 mt-1 max-w-sm mx-auto">
                    No student achievement records match your current filter parameters. Use the button above to award a badge.
                </p>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-slate-200 text-[11px] font-bold uppercase tracking-wider text-slate-400 bg-slate-50/50">
                            <th class="py-3 px-4">Student</th>
                            <th class="py-3 px-4">Class</th>
                            <th class="py-3 px-4">Awarded Badge</th>
                            <th class="py-3 px-4">Commendation Reason</th>
                            <th class="py-3 px-4">Awarded By</th>
                            <th class="py-3 px-4">Date</th>
                            <th class="py-3 px-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-xs">
                        <?php foreach ($awardedBadges as $sb): ?>
                            <?php 
                                $bCat = $sb->badge?->category ?? 'academic';
                                $catClass = $categoryStyles[$bCat] ?? 'bg-slate-50 text-slate-700 border-slate-200';
                            ?>
                            <tr class="hover:bg-slate-50/60 transition">
                                <td class="py-3 px-4 font-bold text-slate-900">
                                    <div><?= htmlspecialchars($sb->studentName ?? 'Student') ?></div>
                                    <?php if (!empty($sb->admissionNumber)): ?>
                                        <div class="text-[10px] text-slate-400 font-mono"><?= htmlspecialchars($sb->admissionNumber) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3 px-4 font-medium text-slate-600">
                                    <?= htmlspecialchars($sb->className ?? '—') ?>
                                </td>
                                <td class="py-3 px-4">
                                    <div class="flex items-center gap-2">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold border <?= $catClass ?>">
                                            <?= htmlspecialchars($sb->badge?->name ?? 'Badge') ?>
                                        </span>
                                    </div>
                                </td>
                                <td class="py-3 px-4 text-slate-600 max-w-xs truncate" title="<?= htmlspecialchars($sb->reason) ?>">
                                    <?= htmlspecialchars($sb->reason) ?>
                                </td>
                                <td class="py-3 px-4 text-slate-500">
                                    <?= htmlspecialchars($sb->awarderName ?? 'Staff') ?>
                                </td>
                                <td class="py-3 px-4 font-mono text-[11px] text-slate-500 whitespace-nowrap">
                                    <?= date('M j, Y', strtotime($sb->awardedAt)) ?>
                                </td>
                                <td class="py-3 px-4 text-right">
                                    <form method="POST" action="/admin/badges/<?= (int)$sb->id ?>/revoke" class="inline" onsubmit="return confirm('Are you sure you want to revoke this badge award?');">
                                        <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                                        <button type="submit" class="text-xs font-bold text-rose-600 hover:text-rose-800 transition">
                                            Revoke
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

</div>

<!-- Award Badge Modal -->
<div id="awardBadgeModal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl border border-slate-200 max-w-lg w-full p-6 space-y-5">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <div>
                <h3 class="text-base font-extrabold text-slate-900">Award Student Achievement Badge</h3>
                <p class="text-xs text-slate-500 mt-0.5">Issue an official commendation to a student.</p>
            </div>
            <button type="button" onclick="document.getElementById('awardBadgeModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <form method="POST" action="/admin/badges/award" class="space-y-4">
            <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

            <!-- Select Student -->
            <div>
                <label for="modal_student_id" class="text-xs font-bold text-slate-700 uppercase tracking-wider block mb-1.5">Recipient Student *</label>
                <select id="modal_student_id" name="student_id" required class="w-full px-3.5 py-2.5 text-xs font-semibold rounded-xl border border-slate-300 bg-slate-50 focus:bg-white focus:border-brand-500 focus:ring-brand-500">
                    <option value="">-- Choose Student --</option>
                    <?php foreach ($students as $s): ?>
                        <option value="<?= (int)$s->id ?>">
                            <?= htmlspecialchars($s->userName ?? 'Student') ?> 
                            (<?= htmlspecialchars($s->admissionNumber) ?><?= !empty($s->className) ? ' - ' . htmlspecialchars($s->className) : '' ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Select Badge -->
            <div>
                <label for="modal_badge_id" class="text-xs font-bold text-slate-700 uppercase tracking-wider block mb-1.5">Achievement Badge *</label>
                <select id="modal_badge_id" name="badge_id" required class="w-full px-3.5 py-2.5 text-xs font-semibold rounded-xl border border-slate-300 bg-slate-50 focus:bg-white focus:border-brand-500 focus:ring-brand-500">
                    <option value="">-- Choose Badge --</option>
                    <?php foreach ($badges as $b): ?>
                        <option value="<?= (int)$b->id ?>">
                            <?= htmlspecialchars($b->name) ?> (<?= htmlspecialchars(ucfirst($b->category)) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Commendation Reason -->
            <div>
                <label for="modal_reason" class="text-xs font-bold text-slate-700 uppercase tracking-wider block mb-1.5">Commendation Reason *</label>
                <textarea id="modal_reason" name="reason" rows="3" required placeholder="e.g. Commended for outstanding leadership, peer mentorship, and consistent excellence in term assignments." class="w-full px-3.5 py-2.5 text-xs font-medium rounded-xl border border-slate-300 focus:border-brand-500 focus:ring-brand-500"></textarea>
            </div>

            <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
                <button type="button" onclick="document.getElementById('awardBadgeModal').classList.add('hidden')" class="px-4 py-2 text-xs font-bold text-slate-600 hover:text-slate-800">
                    Cancel
                </button>
                <button type="submit" class="px-5 py-2 text-xs font-bold bg-brand-600 text-white rounded-xl hover:bg-brand-700 shadow-sm transition">
                    Award Badge
                </button>
            </div>
        </form>
    </div>
</div>
