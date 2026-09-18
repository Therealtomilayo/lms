<?php
/**
 * Parent Child Badges & Honors View (SRS §33, §57 Phase 3)
 * Parents monitor digital badges and teacher commendations earned by their child.
 *
 * @var \App\Models\Student $student
 * @var \App\Models\Student $selectedChild
 * @var \App\Models\Student[] $children
 * @var \App\Models\StudentBadge[] $earnedBadges
 * @var \App\Models\AcademicSession|null $activeSession
 * @var \App\Core\UserContext $user
 */

$childId = (int)$student->id;
$childName = $student->name;
$totalBadges = count($earnedBadges);
$csrfToken = $_SESSION['_csrf_token'] ?? '';
?>

<div class="space-y-6">
    <!-- Header Card -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6">
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 rounded-2xl bg-brand-700 text-white font-extrabold text-2xl flex items-center justify-center shadow-xs flex-shrink-0">
                    <?= strtoupper(substr($childName, 0, 1)) ?>
                </div>
                <div>
                    <div class="flex items-center gap-2 text-xs font-semibold text-slate-400 mb-1">
                        <a href="/parent/dashboard" class="hover:text-brand-600 transition">Dashboard</a>
                        <span>&rsaquo;</span>
                        <a href="/parent/children/<?= $childId ?>" class="hover:text-brand-600 transition">Child Profile</a>
                        <span>&rsaquo;</span>
                        <span class="text-slate-700 font-bold">Achievements</span>
                    </div>

                    <div class="flex flex-wrap items-center gap-2.5">
                        <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight leading-tight">
                            <?= htmlspecialchars($childName) ?>'s Badges & Honors
                        </h1>
                    </div>
                    <p class="text-xs text-slate-500 mt-1">Verified academic milestones and faculty commendations.</p>
                </div>
            </div>

            <!-- Child Selector Dropdown (if multiple) -->
            <?php if (count($children) > 1): ?>
                <form method="POST" action="/parent/children/<?= $childId ?>/select" class="flex items-center gap-2">
                    <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                    <input type="hidden" name="redirect_to" value="/parent/children/<?= $childId ?>/badges">
                    <label for="child_switcher" class="text-xs font-bold text-slate-500 whitespace-nowrap">Switch Child:</label>
                    <select name="student_id" id="child_switcher" onchange="this.form.action='/parent/children/' + this.value + '/select'; this.form.submit();"
                            class="px-3 py-2 text-sm font-semibold rounded-lg border border-slate-300 bg-white text-slate-800 shadow-xs focus:ring-2 focus:ring-brand-500 focus:outline-hidden">
                        <?php foreach ($children as $c): ?>
                            <option value="<?= (int)$c->id ?>" <?= (int)$c->id === $childId ? 'selected' : '' ?>>
                                <?= htmlspecialchars($c->name) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <!-- Badges Grid -->
    <div class="space-y-4">
        <div class="flex items-center justify-between px-1">
            <h2 class="text-base font-bold text-slate-900 flex items-center gap-2">
                <span>Earned Achievements</span>
                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-800">
                    <?= $totalBadges ?>
                </span>
            </h2>
        </div>

        <?php if (empty($earnedBadges)): ?>
            <div class="bg-white rounded-2xl border border-slate-200 p-12 text-center shadow-xs">
                <div class="w-14 h-14 rounded-2xl bg-amber-50 text-amber-500 flex items-center justify-center mx-auto mb-3 text-2xl">
                    🎖️
                </div>
                <h3 class="text-base font-bold text-slate-900">No achievement badges yet</h3>
                <p class="text-xs text-slate-500 mt-1 max-w-sm mx-auto">
                    When <?= htmlspecialchars($childName) ?> achieves notable course progression or receives commendations from teachers, they will be listed here.
                </p>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                <?php foreach ($earnedBadges as $sb): ?>
                    <div class="bg-white rounded-2xl border border-amber-200 p-5 shadow-xs flex flex-col justify-between hover:shadow-md transition">
                        <div>
                            <div class="flex items-center justify-between gap-2 mb-3">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-[11px] font-bold bg-amber-50 text-amber-800 border border-amber-200">
                                    <?= ucfirst(htmlspecialchars($sb->badge->category ?? 'General')) ?>
                                </span>
                                <span class="text-xs font-semibold text-slate-400">
                                    <?= date('M j, Y', strtotime($sb->awardedAt)) ?>
                                </span>
                            </div>

                            <div class="flex items-center gap-3 mb-3">
                                <div class="w-12 h-12 rounded-xl bg-amber-500 text-white flex items-center justify-center font-bold text-2xl shadow-xs flex-shrink-0">
                                    🏆
                                </div>
                                <div>
                                    <h3 class="text-base font-extrabold text-slate-900 leading-tight">
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
                            <div class="mt-2 pt-3 border-t border-slate-100 bg-slate-50/60 -mx-5 -mb-5 p-3 px-5 rounded-b-2xl">
                                <div class="text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">Teacher Citation</div>
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
</div>
