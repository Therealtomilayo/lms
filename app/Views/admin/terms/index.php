<?php
$this->layout('layouts/admin', [
    'title' => $title ?? 'Academic Terms & Session Lifecycle — Claret LMS',
    'headerTitle' => $headerTitle ?? 'Academic Terms'
]);

$today = $today ?? date('Y-m-d');
$isActiveTermElapsed = $activeTerm ? (strtotime($today) > strtotime($activeTerm->endDate)) : false;
$daysLeft = $activeTerm ? max(0, (int)ceil((strtotime($activeTerm->endDate) - strtotime($today)) / 86400)) : 0;
$daysElapsed = $activeTerm ? max(0, (int)floor((strtotime($today) - strtotime($activeTerm->endDate)) / 86400)) : 0;
?>

<div class="space-y-6">
    <!-- Header with Action -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-slate-900">Academic Terms &amp; Lifecycles</h2>
            <p class="text-sm text-slate-500 mt-1">Configure terms, track calendar spans against operational states, and switch active institutional terms.</p>
        </div>
        <?php if ($selectedSessionId > 0): ?>
            <div>
                <?php $this->include('components/button', [
                    'type' => 'button',
                    'variant' => 'primary',
                    'label' => 'Create Term',
                    'icon' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>',
                    'attributes' => 'onclick="window.LMS.showModal(\'create-modal\')"'
                ]); ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Active Term & Operational Switcher Hero Card -->
    <div class="bg-white rounded-2xl border <?= $isActiveTermElapsed ? 'border-amber-300 ring-4 ring-amber-500/10' : 'border-slate-200' ?> shadow-sm overflow-hidden">
        <div class="p-6 sm:p-7 <?= $isActiveTermElapsed ? 'bg-gradient-to-r from-amber-500/10 via-amber-50/40 to-transparent' : 'bg-gradient-to-r from-brand-50/50 via-slate-50/50 to-transparent' ?> border-b border-slate-200">
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6">
                <!-- Left: Active Term Status -->
                <div class="space-y-2">
                    <div class="flex flex-wrap items-center gap-2.5">
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider <?= $isActiveTermElapsed ? 'bg-amber-100 text-amber-900 border border-amber-300' : 'bg-emerald-100 text-emerald-800 border border-emerald-300' ?>">
                            <?= $isActiveTermElapsed ? '⚠️ Term Duration Concluded' : '🟢 Current Active Term' ?>
                        </span>
                        <span class="text-xs text-slate-500 font-semibold">Today: <?= date('D, d M Y') ?></span>
                    </div>

                    <?php if ($activeTerm): ?>
                        <div class="flex flex-wrap items-baseline gap-3">
                            <h3 class="text-2xl sm:text-3xl font-extrabold text-slate-900"><?= e($activeTerm->name) ?></h3>
                            <span class="text-sm font-semibold text-slate-600 bg-white px-3 py-1 rounded-lg border border-slate-200 shadow-2xs">
                                Session: <?= e($activeSession?->name ?? '2026/2027') ?>
                            </span>
                        </div>

                        <div class="flex flex-wrap items-center gap-3 text-xs sm:text-sm text-slate-600 pt-1">
                            <span><strong>Calendar Span:</strong> <?= e($activeTerm->startDate) ?> &rarr; <?= e($activeTerm->endDate) ?></span>
                            <span>•</span>
                            <?php if ($isActiveTermElapsed): ?>
                                <span class="font-bold text-amber-800">
                                    Set end date elapsed <?= $daysElapsed ?> <?= $daysElapsed === 1 ? 'day' : 'days' ?> ago
                                </span>
                            <?php else: ?>
                                <span class="font-semibold text-emerald-700">
                                    <?= $daysLeft ?> <?= $daysLeft === 1 ? 'day' : 'days' ?> remaining in this term
                                </span>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <h3 class="text-xl font-bold text-slate-900">No Term Currently Active</h3>
                        <p class="text-xs text-slate-500">Please activate a term from the list below to enable coursework, CBT, and gradebook operations.</p>
                    <?php endif; ?>
                </div>

                <!-- Right: Frictionless Switch Term Interface -->
                <div class="bg-white p-4 sm:p-5 rounded-xl border border-slate-200 shadow-xs max-w-md w-full">
                    <h4 class="text-xs font-bold uppercase tracking-wider text-slate-700 mb-2 flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-[#7B3046]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                        <span>Switch Institutional Term</span>
                    </h4>
                    <p class="text-[11px] text-slate-500 mb-3 leading-relaxed">
                        Select a term below and click switch to update the active institutional term across student report cards, gradebooks, fees, and attendance.
                    </p>

                    <form method="POST" action="/admin/terms/switch" class="flex flex-col sm:flex-row items-center gap-2">
                        <?= csrf_field() ?>
                        <div class="relative w-full">
                            <select name="term_id" required class="w-full px-3 py-2 text-sm font-semibold rounded-lg border border-slate-300 focus:border-[#7B3046] focus:ring-2 focus:ring-[#7B3046]/10 outline-none transition cursor-pointer">
                                <?php foreach ($terms as $t): ?>
                                    <?php if (!$t->isArchived()): ?>
                                        <option value="<?= $t->id ?>" <?= $t->id === $activeTerm?->id ? 'selected' : '' ?>>
                                            <?= e($t->name) ?> <?= $t->id === $activeTerm?->id ? '(Active)' : '' ?>
                                        </option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" class="w-full sm:w-auto px-4 py-2 text-sm font-bold bg-[#7B3046] hover:bg-[#632738] text-white rounded-lg shadow-xs transition shrink-0 cursor-pointer">
                            Switch Term
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <?php if ($isActiveTermElapsed): ?>
            <!-- Clarification Notice for Elapsed Date -->
            <div class="p-4 bg-amber-50/70 border-t border-amber-200/80 flex items-start gap-3">
                <svg class="w-5 h-5 text-amber-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <div class="text-xs text-amber-900 leading-relaxed">
                    <strong>Why terms do not switch automatically when the date has elapsed:</strong>
                    Academic terms in secondary and primary schools require administrative leeway for end-of-term score recording, broadsheet moderation, and board approvals before transitioning. The system keeps the term active until you deliberately switch it above.
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Session Filter Bar -->
    <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <form method="GET" action="/admin/terms" data-lms-filter="true" class="flex items-center gap-3 w-full sm:w-auto">
            <label for="session-select" class="text-sm font-semibold text-slate-700 whitespace-nowrap">Filter Session:</label>
            <div class="relative w-full sm:w-72">
                <select id="session-select" name="session_id"
                        class="block w-full min-h-[44px] px-3.5 py-2.5 rounded-lg text-base text-slate-800 bg-white border border-slate-300 shadow-xs transition duration-200 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 cursor-pointer pr-10 appearance-none bg-[url('data:image/svg+xml;charset=utf-8,%3Csvg%20xmlns%3D%22http%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20viewBox%3D%220%200%2020%2020%22%20fill%3D%22none%22%3E%3Cpath%20d%3D%22M7%209l3%203%203-3%22%20stroke%3D%22%2364748B%22%20stroke-width%3D%221.5%22%20stroke-linecap%3D%22round%22%20stroke-linejoin%3D%22round%22%2F%3E%3C%2Fsvg%3E')] bg-[position:right_0.5rem_center] bg-[size:1.5em_1.5em] bg-no-repeat">
                    <?php foreach ($sessions as $session): ?>
                        <option value="<?= $session->id ?>" <?= $session->id === $selectedSessionId ? 'selected' : '' ?>>
                            <?= e($session->name) ?> <?= $session->isActive() ? '(Active Session)' : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </form>

        <span class="text-xs text-slate-500">
            Total terms in session: <strong><?= count($terms) ?></strong>
        </span>
    </div>

    <!-- Terms List Container -->
    <div data-lms-table-container="true" class="space-y-4">
        <?php if (empty($terms)): ?>
            <?php $this->include('components/empty_state', [
                'title' => 'No Academic Terms',
            'message' => 'No terms configured for this academic session.'
        ]); ?>
    <?php else: ?>
        <div class="bg-white border border-slate-200 rounded-xl overflow-hidden shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                    <thead class="bg-slate-50 text-slate-600 font-semibold">
                        <tr>
                            <th scope="col" class="px-6 py-3.5">Term Name</th>
                            <th scope="col" class="px-6 py-3.5">Duration &amp; Date Span</th>
                            <th scope="col" class="px-6 py-3.5">Date Status</th>
                            <th scope="col" class="px-6 py-3.5">Grading Window</th>
                            <th scope="col" class="px-6 py-3.5">Operational State</th>
                            <th scope="col" class="px-6 py-3.5 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        <?php foreach ($terms as $term): ?>
                            <?php 
                            $termElapsed = strtotime($today) > strtotime($term->endDate);
                            $termUpcoming = strtotime($today) < strtotime($term->startDate);
                            $termRunning = !$termElapsed && !$termUpcoming;
                            ?>
                            <tr class="hover:bg-slate-50/50 transition <?= $term->isActive() ? 'bg-[#7B3046]/5' : '' ?>">
                                <td class="px-6 py-4 font-bold text-slate-900">
                                    <?= e($term->name) ?>
                                    <?php if ($term->isActive()): ?>
                                        <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-[#7B3046] text-white">
                                            Current Term
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 text-slate-600 font-medium">
                                    <?= e($term->startDate) ?> &rarr; <?= e($term->endDate) ?>
                                </td>
                                <td class="px-6 py-4">
                                    <?php if ($termElapsed): ?>
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-800 border border-amber-200">
                                            Elapsed
                                        </span>
                                    <?php elseif ($termUpcoming): ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-600">
                                            Upcoming
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                            In Session
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 text-slate-600">
                                    <?php if ($term->gradingStartsAt && $term->gradingEndsAt): ?>
                                        <span class="text-xs font-semibold"><?= e($term->gradingStartsAt) ?> &rarr; <?= e($term->gradingEndsAt) ?></span>
                                    <?php else: ?>
                                        <span class="text-xs text-slate-400">Unspecified</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4">
                                    <?php if ($term->isActive()): ?>
                                        <?php $this->include('components/badge', ['label' => 'Active', 'variant' => 'success']); ?>
                                    <?php elseif ($term->isGradingOpen()): ?>
                                        <?php $this->include('components/badge', ['label' => 'Grading Open', 'variant' => 'info']); ?>
                                    <?php elseif ($term->isGradingLocked()): ?>
                                        <?php $this->include('components/badge', ['label' => 'Grading Locked', 'variant' => 'warning']); ?>
                                    <?php elseif ($term->isPlanning()): ?>
                                        <?php $this->include('components/badge', ['label' => 'Planning', 'variant' => 'neutral']); ?>
                                    <?php else: ?>
                                        <?php $this->include('components/badge', ['label' => 'Archived', 'variant' => 'neutral', 'class' => 'opacity-60']); ?>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="inline-flex items-center gap-2 justify-end">
                                        <?php if (!$term->isActive() && !$term->isArchived()): ?>
                                            <form method="POST" action="/admin/terms/switch" class="inline">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="term_id" value="<?= $term->id ?>">
                                                <button type="submit" class="px-2.5 py-1 text-xs font-semibold rounded bg-[#7B3046]/10 text-[#7B3046] hover:bg-[#7B3046] hover:text-white transition cursor-pointer">
                                                    Activate
                                                </button>
                                            </form>
                                        <?php endif; ?>

                                        <?php if ($term->isActive()): ?>
                                            <form method="POST" action="/admin/terms/<?= $term->id ?>/status" class="inline">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="status" value="grading_open">
                                                <?php $this->include('components/button', [
                                                    'type' => 'submit',
                                                    'variant' => 'secondary',
                                                    'label' => 'Open Grading',
                                                    'class' => 'px-2.5 py-1 min-h-0 text-xs font-semibold bg-info-100 hover:bg-info-200 text-info-700 border-transparent'
                                                ]); ?>
                                            </form>
                                        <?php elseif ($term->isGradingOpen()): ?>
                                            <form method="POST" action="/admin/terms/<?= $term->id ?>/status" class="inline">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="status" value="grading_locked">
                                                <?php $this->include('components/button', [
                                                    'type' => 'submit',
                                                    'variant' => 'secondary',
                                                    'label' => 'Lock Grading',
                                                    'class' => 'px-2.5 py-1 min-h-0 text-xs font-semibold bg-warning-100 hover:bg-warning-200 text-warning-800 border-transparent'
                                                ]); ?>
                                            </form>
                                        <?php elseif ($term->isGradingLocked()): ?>
                                            <form method="POST" action="/admin/terms/<?= $term->id ?>/status" class="inline">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="status" value="grading_open">
                                                <?php $this->include('components/button', [
                                                    'type' => 'submit',
                                                    'variant' => 'secondary',
                                                    'label' => 'Re-open Grading',
                                                    'class' => 'px-2.5 py-1 min-h-0 text-xs font-semibold bg-info-100 hover:bg-info-200 text-info-700 border-transparent'
                                                ]); ?>
                                            </form>
                                        <?php endif; ?>

                                        <?php if (!$term->isArchived()): ?>
                                            <?php $this->include('components/button', [
                                                'type' => 'button',
                                                'variant' => 'secondary',
                                                'label' => 'Edit',
                                                'class' => 'px-2.5 py-1 min-h-0 text-xs font-semibold',
                                                'attributes' => 'onclick="openEditModal(' . $term->id . ', \'' . e(addslashes($term->name)) . '\', \'' . e($term->startDate) . '\', \'' . e($term->endDate) . '\', \'' . e($term->gradingStartsAt ?? '') . '\', \'' . e($term->gradingEndsAt ?? '') . '\')"'
                                            ]); ?>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
    </div>
</div>

<!-- Create Modal -->
<?php ob_start(); ?>
<form method="POST" action="/admin/terms" class="space-y-4" novalidate>
    <?= csrf_field() ?>
    <input type="hidden" name="session_id" value="<?= $selectedSessionId ?>">

    <?php $this->include('components/input', [
        'name' => 'name',
        'id' => 'create_term_name',
        'label' => 'Term Name',
        'placeholder' => 'e.g. First Term, Second Term',
        'required' => true
    ]); ?>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <?php $this->include('components/input', [
            'type' => 'date',
            'name' => 'start_date',
            'id' => 'create_start_date',
            'label' => 'Start Date',
            'required' => true
        ]); ?>

        <?php $this->include('components/input', [
            'type' => 'date',
            'name' => 'end_date',
            'id' => 'create_end_date',
            'label' => 'End Date',
            'required' => true
        ]); ?>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <?php $this->include('components/input', [
            'type' => 'datetime-local',
            'name' => 'grading_starts_at',
            'id' => 'create_grading_starts',
            'label' => 'Grading Opens At (Optional)'
        ]); ?>

        <?php $this->include('components/input', [
            'type' => 'datetime-local',
            'name' => 'grading_ends_at',
            'id' => 'create_grading_ends',
            'label' => 'Grading Closes At (Optional)'
        ]); ?>
    </div>

    <div class="flex justify-end gap-3 pt-4 border-t border-slate-100">
        <?php $this->include('components/button', [
            'type' => 'button',
            'variant' => 'secondary',
            'label' => 'Cancel',
            'attributes' => 'onclick="window.LMS.hideModal(\'create-modal\')"'
        ]); ?>
        <?php $this->include('components/button', [
            'type' => 'submit',
            'variant' => 'primary',
            'label' => 'Create Term'
        ]); ?>
    </div>
</form>
<?php $createBody = ob_get_clean(); ?>

<?php $this->include('components/modal', [
    'id' => 'create-modal',
    'title' => 'Create New Academic Term',
    'body' => $createBody,
    'size' => 'md'
]); ?>

<!-- Edit Modal -->
<?php ob_start(); ?>
<form id="edit-form" method="POST" action="" class="space-y-4" novalidate>
    <?= csrf_field() ?>
    <input type="hidden" name="session_id" value="<?= $selectedSessionId ?>">

    <?php $this->include('components/input', [
        'name' => 'name',
        'id' => 'edit_term_name',
        'label' => 'Term Name',
        'required' => true
    ]); ?>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <?php $this->include('components/input', [
            'type' => 'date',
            'name' => 'start_date',
            'id' => 'edit_start_date',
            'label' => 'Start Date',
            'required' => true
        ]); ?>

        <?php $this->include('components/input', [
            'type' => 'date',
            'name' => 'end_date',
            'id' => 'edit_end_date',
            'label' => 'End Date',
            'required' => true
        ]); ?>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <?php $this->include('components/input', [
            'type' => 'datetime-local',
            'name' => 'grading_starts_at',
            'id' => 'edit_grading_starts',
            'label' => 'Grading Opens At (Optional)'
        ]); ?>

        <?php $this->include('components/input', [
            'type' => 'datetime-local',
            'name' => 'grading_ends_at',
            'id' => 'edit_grading_ends',
            'label' => 'Grading Closes At (Optional)'
        ]); ?>
    </div>

    <div class="flex justify-end gap-3 pt-4 border-t border-slate-100">
        <?php $this->include('components/button', [
            'type' => 'button',
            'variant' => 'secondary',
            'label' => 'Cancel',
            'attributes' => 'onclick="window.LMS.hideModal(\'edit-modal\')"'
        ]); ?>
        <?php $this->include('components/button', [
            'type' => 'submit',
            'variant' => 'primary',
            'label' => 'Update Term'
        ]); ?>
    </div>
</form>
<?php $editBody = ob_get_clean(); ?>

<?php $this->include('components/modal', [
    'id' => 'edit-modal',
    'title' => 'Edit Academic Term',
    'body' => $editBody,
    'size' => 'md'
]); ?>

<script>
function openEditModal(id, name, startDate, endDate, gradingStarts, gradingEnds) {
    document.getElementById('edit-form').action = '/admin/terms/' + id;
    document.getElementById('edit_term_name').value = name;
    document.getElementById('edit_start_date').value = startDate;
    document.getElementById('edit_end_date').value = endDate;
    document.getElementById('edit_grading_starts').value = gradingStarts ? gradingStarts.replace(' ', 'T').substring(0, 16) : '';
    document.getElementById('edit_grading_ends').value = gradingEnds ? gradingEnds.replace(' ', 'T').substring(0, 16) : '';
    window.LMS.showModal('edit-modal');
}
</script>
