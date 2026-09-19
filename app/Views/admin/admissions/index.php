<?php
/**
 * Admin Admissions Applications Listing View
 *
 * @var array $applications
 * @var array $sessions
 * @var int|null $selectedSessionId
 * @var string $selectedStatus
 * @var string|null $searchQuery
 * @var array $counts
 * @var string|null $success
 * @var string|null $error
 */
$this->layout('layouts/admin', [
    'title' => 'Admissions Applications — Claret LMS',
    'headerTitle' => 'Admissions'
]);
?>

<div class="space-y-6">
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <span class="p-2 rounded-xl bg-brand-500/10 text-brand-600">
                    <i data-lucide="user-check" class="w-5 h-5"></i>
                </span>
                <h1 class="text-2xl font-black text-slate-900 tracking-tight">Admissions Portal</h1>
            </div>
            <p class="text-sm text-slate-500 mt-1">Review, assess, and matriculate prospective student applications.</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="/admin/admissions/sessions" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-700 font-semibold text-xs hover:bg-slate-50 hover:border-slate-300 transition shadow-sm">
                <i data-lucide="calendar-cog" class="w-4 h-4 text-slate-500"></i>
                Admission Sessions
            </a>
            <a href="/apply" target="_blank" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-brand-600 text-white font-semibold text-xs hover:bg-brand-700 transition shadow-sm shadow-brand-500/20 group">
                <i data-lucide="external-link" class="w-4 h-4"></i>
                <span>Public Portal</span>
            </a>
        </div>
    </div>

    <!-- Feedback Alerts -->
    <?php if (!empty($success)): ?>
        <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 flex items-start gap-3">
            <i data-lucide="check-circle-2" class="w-5 h-5 text-emerald-600 flex-shrink-0 mt-0.5"></i>
            <p class="text-sm font-medium text-emerald-800"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></p>
        </div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 flex items-start gap-3">
            <i data-lucide="alert-circle" class="w-5 h-5 text-rose-600 flex-shrink-0 mt-0.5"></i>
            <p class="text-sm font-medium text-rose-800"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
        </div>
    <?php endif; ?>

    <!-- Filter & Search Controls -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5 space-y-4">
        <!-- Status Tabs with Badges -->
        <div class="flex items-center gap-2 overflow-x-auto pb-2 border-b border-slate-100">
            <?php
            $tabs = [
                'all' => ['label' => 'All Applications', 'icon' => 'layers'],
                'submitted' => ['label' => 'Submitted', 'icon' => 'inbox'],
                'under_review' => ['label' => 'Under Review', 'icon' => 'search'],
                'approved' => ['label' => 'Approved', 'icon' => 'check-circle-2'],
                'rejected' => ['label' => 'Rejected', 'icon' => 'x-circle'],
                'draft' => ['label' => 'Drafts', 'icon' => 'file-edit'],
            ];
            foreach ($tabs as $key => $tab):
                $isActive = ($selectedStatus === $key);
                $query = http_build_query([
                    'status' => $key,
                    'session_id' => $selectedSessionId,
                    'q' => $searchQuery
                ]);
            ?>
                <a href="/admin/admissions/applications?<?= $query ?>" 
                   class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs font-bold transition whitespace-nowrap <?= $isActive ? 'bg-slate-900 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' ?>">
                    <i data-lucide="<?= $tab['icon'] ?>" class="w-3.5 h-3.5 <?= $isActive ? 'text-white' : 'text-slate-400' ?>"></i>
                    <span><?= $tab['label'] ?></span>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold <?= $isActive ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-600' ?>">
                        <?= $counts[$key] ?? 0 ?>
                    </span>
                </a>
            <?php endforeach; ?>
        </div>

        <!-- Search & Session Select Form -->
        <form method="GET" action="/admin/admissions/applications" class="grid grid-cols-1 sm:grid-cols-12 gap-3">
            <input type="hidden" name="status" value="<?= htmlspecialchars($selectedStatus, ENT_QUOTES, 'UTF-8') ?>">

            <!-- Search input -->
            <div class="sm:col-span-7 relative">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                    <i data-lucide="search" class="w-4 h-4"></i>
                </div>
                <input type="text" 
                       name="q" 
                       value="<?= htmlspecialchars($searchQuery ?? '', ENT_QUOTES, 'UTF-8') ?>" 
                       placeholder="Search by Application No (APP-...), Applicant name, email, or ward name..." 
                       class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-200 text-xs text-slate-800 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition">
            </div>

            <!-- Session Filter -->
            <div class="sm:col-span-3">
                <select name="session_id" 
                        class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs font-medium text-slate-700 focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition bg-white">
                    <option value="">All Academic Sessions</option>
                    <?php foreach ($sessions as $s): ?>
                        <option value="<?= $s->id ?>" <?= $selectedSessionId === $s->id ? 'selected' : '' ?>>
                            <?= htmlspecialchars($s->title, ENT_QUOTES, 'UTF-8') ?> <?= $s->isActive ? '(Active)' : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Action buttons -->
            <div class="sm:col-span-2 flex items-center gap-2">
                <button type="submit" class="w-full py-2.5 px-3 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-semibold text-xs transition flex items-center justify-center gap-1.5 shadow-sm">
                    <i data-lucide="filter" class="w-3.5 h-3.5"></i>
                    Filter
                </button>
                <?php if ($searchQuery || $selectedSessionId): ?>
                    <a href="/admin/admissions/applications?status=<?= urlencode($selectedStatus) ?>" 
                       title="Reset Filters" 
                       class="p-2.5 rounded-xl border border-slate-200 hover:bg-slate-100 text-slate-500 transition flex items-center justify-center">
                        <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Applications Table -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        <?php if (empty($applications)): ?>
            <div class="p-12 text-center">
                <div class="w-16 h-16 rounded-2xl bg-slate-100 text-slate-400 mx-auto flex items-center justify-center mb-4">
                    <i data-lucide="file-x-2" class="w-8 h-8"></i>
                </div>
                <h3 class="text-base font-bold text-slate-800">No applications found</h3>
                <p class="text-xs text-slate-500 mt-1 max-w-sm mx-auto">There are no admission applications matching the selected criteria.</p>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-slate-100 bg-slate-50/75 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                            <th class="py-3.5 px-5">Application No</th>
                            <th class="py-3.5 px-5">Applicant Guardian</th>
                            <th class="py-3.5 px-5">Registered Wards</th>
                            <th class="py-3.5 px-5">Fee Status</th>
                            <th class="py-3.5 px-5">Docket Status</th>
                            <th class="py-3.5 px-5">Timeline</th>
                            <th class="py-3.5 px-5 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-xs">
                        <?php foreach ($applications as $app): 
                            $applicant = $app->applicant;
                            $wards = $app->wards;
                            $totalWards = count($wards);
                            $paidWards = count(array_filter($wards, fn($w) => $w->paymentStatus === 'paid'));
                        ?>
                            <tr class="hover:bg-slate-50/80 transition-colors group">
                                <!-- Application No -->
                                <td class="py-4 px-5">
                                    <div class="font-mono font-black text-slate-900 group-hover:text-brand-600 transition-colors">
                                        <?= htmlspecialchars($app->applicationNumber, ENT_QUOTES, 'UTF-8') ?>
                                    </div>
                                    <div class="text-[11px] text-slate-400 mt-0.5">
                                        <?= htmlspecialchars($app->session?->title ?? 'General Session', ENT_QUOTES, 'UTF-8') ?>
                                    </div>
                                </td>

                                <!-- Guardian -->
                                <td class="py-4 px-5">
                                    <div class="font-bold text-slate-800">
                                        <?= htmlspecialchars($applicant?->name ?? 'Applicant User', ENT_QUOTES, 'UTF-8') ?>
                                    </div>
                                    <div class="text-[11px] text-slate-500 flex items-center gap-1 mt-0.5">
                                        <i data-lucide="mail" class="w-3 h-3 text-slate-400"></i>
                                        <?= htmlspecialchars($applicant?->email ?? '—', ENT_QUOTES, 'UTF-8') ?>
                                    </div>
                                    <?php if (!empty($applicant?->phone)): ?>
                                        <div class="text-[11px] text-slate-400 flex items-center gap-1 mt-0.5">
                                            <i data-lucide="phone" class="w-3 h-3 text-slate-400"></i>
                                            <?= htmlspecialchars($applicant->phone, ENT_QUOTES, 'UTF-8') ?>
                                        </div>
                                    <?php endif; ?>
                                </td>

                                <!-- Wards -->
                                <td class="py-4 px-5">
                                    <?php if ($totalWards === 0): ?>
                                        <span class="text-slate-400 italic">No wards registered</span>
                                    <?php else: ?>
                                        <div class="space-y-1">
                                            <?php foreach ($wards as $ward): ?>
                                                <div class="flex items-center gap-1.5">
                                                    <span class="w-1.5 h-1.5 rounded-full <?= $ward->paymentStatus === 'paid' ? 'bg-emerald-500' : 'bg-amber-400' ?>"></span>
                                                    <span class="font-semibold text-slate-800"><?= htmlspecialchars($ward->getFullName(), ENT_QUOTES, 'UTF-8') ?></span>
                                                    <span class="text-[10px] px-1.5 py-0.5 rounded bg-slate-100 text-slate-600 font-medium">
                                                        <?= htmlspecialchars($ward->classGrade ?? ($ward->academicLevelName ?? 'N/A'), ENT_QUOTES, 'UTF-8') ?>
                                                    </span>
                                                    <?php if (!empty($ward->studentAdmissionNumber)): ?>
                                                        <span class="font-mono text-[10px] font-bold text-brand-600 bg-brand-50 px-1.5 py-0.5 rounded border border-brand-200">
                                                            <?= htmlspecialchars($ward->studentAdmissionNumber, ENT_QUOTES, 'UTF-8') ?>
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </td>

                                <!-- Fee Status -->
                                <td class="py-4 px-5">
                                    <?php if ($totalWards > 0 && $paidWards === $totalWards): ?>
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200/60">
                                            <i data-lucide="check" class="w-3 h-3"></i>
                                            All Paid (<?= $paidWards ?>/<?= $totalWards ?>)
                                        </span>
                                    <?php elseif ($paidWards > 0): ?>
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-amber-50 text-amber-700 border border-amber-200/60">
                                            <i data-lucide="alert-circle" class="w-3 h-3"></i>
                                            Partial (<?= $paidWards ?>/<?= $totalWards ?>)
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-slate-100 text-slate-600">
                                            <i data-lucide="clock" class="w-3 h-3"></i>
                                            Unpaid
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <!-- Docket Status -->
                                <td class="py-4 px-5">
                                    <?php
                                    $statusClasses = [
                                        'draft' => 'bg-slate-100 text-slate-700 border-slate-200',
                                        'submitted' => 'bg-sky-50 text-sky-700 border-sky-200',
                                        'under_review' => 'bg-purple-50 text-purple-700 border-purple-200',
                                        'approved' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                        'rejected' => 'bg-rose-50 text-rose-700 border-rose-200',
                                    ];
                                    $badgeStyle = $statusClasses[$app->status] ?? 'bg-slate-100 text-slate-700 border-slate-200';
                                    ?>
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-extrabold uppercase tracking-wider border <?= $badgeStyle ?>">
                                        <?= htmlspecialchars(str_replace('_', ' ', $app->status), ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                </td>

                                <!-- Timeline -->
                                <td class="py-4 px-5 text-slate-500 text-[11px]">
                                    <div>Created: <?= date('M d, Y', strtotime($app->createdAt)) ?></div>
                                    <?php if ($app->submittedAt): ?>
                                        <div class="text-sky-600 font-medium">Submitted: <?= date('M d, Y', strtotime($app->submittedAt)) ?></div>
                                    <?php endif; ?>
                                </td>

                                <!-- Actions -->
                                <td class="py-4 px-5 text-right">
                                    <a href="/admin/admissions/applications/<?= $app->id ?>" 
                                       class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-slate-200 hover:border-brand-500 hover:bg-brand-50 hover:text-brand-600 text-slate-700 font-bold text-xs transition group/btn shadow-sm">
                                        <span>Review</span>
                                        <i data-lucide="arrow-right" class="w-3.5 h-3.5 group-hover/btn:translate-x-0.5 transition-transform"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    });
</script>
