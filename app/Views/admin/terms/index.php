<?php
$this->layout('layouts/admin', [
    'title' => $title ?? 'Academic Terms — Claret LMS',
    'headerTitle' => $headerTitle ?? 'Academic Terms'
]);
?>
<div class="space-y-6">
    <!-- Header with Action -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-slate-900">Academic Terms</h2>
            <p class="text-sm text-slate-500 mt-1">Configure terms, date spans, grading windows, and operational states.</p>
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

    <!-- Session Filter Bar -->
    <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm flex flex-col sm:flex-row sm:items-center gap-4">
        <label for="session-select" class="text-sm font-semibold text-slate-700">Filter by Session:</label>
        <div class="relative w-full sm:w-72">
            <select id="session-select" onchange="window.location.href='/admin/terms?session_id=' + this.value" 
                    class="block w-full min-h-[44px] px-3.5 py-2.5 rounded-lg text-base text-slate-800 bg-white border border-slate-300 shadow-xs transition duration-200 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 cursor-pointer pr-10 appearance-none bg-[url('data:image/svg+xml;charset=utf-8,%3Csvg%20xmlns%3D%22http%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20viewBox%3D%220%200%2020%2020%22%20fill%3D%22none%22%3E%3Cpath%20d%3D%22M7%209l3%203%203-3%22%20stroke%3D%22%2364748B%22%20stroke-width%3D%221.5%22%20stroke-linecap%3D%22round%22%20stroke-linejoin%3D%22round%22%2F%3E%3C%2Fsvg%3E')] bg-[position:right_0.5rem_center] bg-[size:1.5em_1.5em] bg-no-repeat">
                <?php foreach ($sessions as $session): ?>
                    <option value="<?= $session->id ?>" <?= $session->id === $selectedSessionId ? 'selected' : '' ?>>
                        <?= e($session->name) ?> <?= $session->isActive() ? '(Active)' : '' ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <!-- Terms List -->
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
                            <th scope="col" class="px-6 py-3.5">Duration</th>
                            <th scope="col" class="px-6 py-3.5">Grading Window</th>
                            <th scope="col" class="px-6 py-3.5">Status</th>
                            <th scope="col" class="px-6 py-3.5 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        <?php foreach ($terms as $term): ?>
                            <tr class="hover:bg-slate-50/50 transition">
                                <td class="px-6 py-4 font-semibold text-slate-900">
                                    <?= e($term->name) ?>
                                </td>
                                <td class="px-6 py-4 text-slate-600">
                                    <?= e($term->startDate) ?> &rarr; <?= e($term->endDate) ?>
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
                                        <?php if ($term->isPlanning()): ?>
                                            <form method="POST" action="/admin/terms/<?= $term->id ?>/make-current" class="inline">
                                                <?= csrf_field() ?>
                                                <?php $this->include('components/button', [
                                                    'type' => 'submit',
                                                    'variant' => 'secondary',
                                                    'label' => 'Activate',
                                                    'class' => 'px-2.5 py-1 min-h-0 text-xs font-semibold border-transparent bg-brand-55 hover:bg-brand-100 text-brand-700'
                                                ]); ?>
                                            </form>
                                        <?php elseif ($term->isActive()): ?>
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
                                            <form method="POST" action="/admin/terms/<?= $term->id ?>/status" class="inline" onsubmit="return confirm('Archive this term? Result records will be permanently locked.');">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="status" value="archived">
                                                <?php $this->include('components/button', [
                                                    'type' => 'submit',
                                                    'variant' => 'secondary',
                                                    'label' => 'Archive',
                                                    'class' => 'px-2.5 py-1 min-h-0 text-xs font-semibold bg-slate-200 hover:bg-slate-300 text-slate-700 border-transparent'
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

<!-- Create Modal -->
<?php ob_start(); ?>
<form method="POST" action="/admin/terms" class="space-y-4" novalidate>
    <?= csrf_field() ?>
    <input type="hidden" name="session_id" value="<?= $selectedSessionId ?>">
    
    <?php $this->include('components/input', [
        'name' => 'name',
        'id' => 'create_term_name',
        'label' => 'Term Name',
        'placeholder' => 'e.g. First Term',
        'required' => true
    ]); ?>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <?php $this->include('components/input', [
            'name' => 'start_date',
            'id' => 'create_term_start',
            'label' => 'Start Date',
            'type' => 'date',
            'required' => true
        ]); ?>

        <?php $this->include('components/input', [
            'name' => 'end_date',
            'id' => 'create_term_end',
            'label' => 'End Date',
            'type' => 'date',
            'required' => true
        ]); ?>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <?php $this->include('components/input', [
            'name' => 'grading_starts_at',
            'id' => 'create_grading_start',
            'label' => 'Grading Opens',
            'type' => 'datetime-local'
        ]); ?>

        <?php $this->include('components/input', [
            'name' => 'grading_ends_at',
            'id' => 'create_grading_end',
            'label' => 'Grading Closes',
            'type' => 'datetime-local'
        ]); ?>
    </div>

    <div class="pt-4 border-t border-slate-200 flex justify-end gap-3">
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
<?php $createModalBody = ob_get_clean(); ?>

<?php $this->include('components/modal', [
    'id' => 'create-modal',
    'title' => 'Create Academic Term',
    'body' => $createModalBody,
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
            'name' => 'start_date',
            'id' => 'edit_term_start',
            'label' => 'Start Date',
            'type' => 'date',
            'required' => true
        ]); ?>

        <?php $this->include('components/input', [
            'name' => 'end_date',
            'id' => 'edit_term_end',
            'label' => 'End Date',
            'type' => 'date',
            'required' => true
        ]); ?>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <?php $this->include('components/input', [
            'name' => 'grading_starts_at',
            'id' => 'edit_grading_start',
            'label' => 'Grading Opens',
            'type' => 'datetime-local'
        ]); ?>

        <?php $this->include('components/input', [
            'name' => 'grading_ends_at',
            'id' => 'edit_grading_end',
            'label' => 'Grading Closes',
            'type' => 'datetime-local'
        ]); ?>
    </div>

    <div class="pt-4 border-t border-slate-200 flex justify-end gap-3">
        <?php $this->include('components/button', [
            'type' => 'button',
            'variant' => 'secondary',
            'label' => 'Cancel',
            'attributes' => 'onclick="window.LMS.hideModal(\'edit-modal\')"'
        ]); ?>
        <?php $this->include('components/button', [
            'type' => 'submit',
            'variant' => 'primary',
            'label' => 'Save Changes'
        ]); ?>
    </div>
</form>
<?php $editModalBody = ob_get_clean(); ?>

<?php $this->include('components/modal', [
    'id' => 'edit-modal',
    'title' => 'Edit Academic Term',
    'body' => $editModalBody,
    'size' => 'md'
]); ?>

<script>
    function openEditModal(id, name, startDate, endDate, gradingStart, gradingEnd) {
        document.getElementById('edit-form').action = '/admin/terms/' + id;
        document.getElementById('edit_term_name').value = name;
        document.getElementById('edit_term_start').value = startDate;
        document.getElementById('edit_term_end').value = endDate;
        document.getElementById('edit_grading_start').value = gradingStart ? gradingStart.replace(' ', 'T').slice(0, 16) : '';
        document.getElementById('edit_grading_end').value = gradingEnd ? gradingEnd.replace(' ', 'T').slice(0, 16) : '';
        window.LMS.showModal('edit-modal');
    }
</script>
