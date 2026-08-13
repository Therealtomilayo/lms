<?php
$this->layout('layouts/admin', [
    'title' => $title ?? 'Academic Sessions — Claret LMS',
    'headerTitle' => $headerTitle ?? 'Academic Sessions'
]);
?>
<div class="space-y-6">
    <!-- Header with Action -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-slate-900">Academic Sessions</h2>
            <p class="text-sm text-slate-500 mt-1">Manage school years, calendars, and overarching lifecycle states.</p>
        </div>
        <div>
            <?php $this->include('components/button', [
                'type' => 'button',
                'variant' => 'primary',
                'label' => 'Create Session',
                'icon' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>',
                'attributes' => 'onclick="window.LMS.showModal(\'create-modal\')"'
            ]); ?>
        </div>
    </div>

    <!-- Sessions List -->
    <?php if (empty($sessions)): ?>
        <div class="space-y-6">
            <?php $this->include('components/empty_state', [
                'title' => 'No Academic Sessions',
                'message' => 'No academic sessions created yet. Click "Create Session" to get started.'
            ]); ?>
        </div>
    <?php else: ?>
        <div class="bg-white border border-slate-200 rounded-xl shadow-xs overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                    <thead class="bg-slate-50 text-slate-600 font-semibold">
                        <tr>
                            <th scope="col" class="px-6 py-3.5">Session Name</th>
                            <th scope="col" class="px-6 py-3.5">Start Date</th>
                            <th scope="col" class="px-6 py-3.5">End Date</th>
                            <th scope="col" class="px-6 py-3.5">Status</th>
                            <th scope="col" class="px-6 py-3.5 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        <?php foreach ($sessions as $session): ?>
                            <tr class="hover:bg-slate-50/50 transition">
                                <td class="px-6 py-4 font-semibold text-slate-900">
                                    <?= e($session->name) ?>
                                </td>
                                <td class="px-6 py-4 text-slate-600">
                                    <?= e($session->startDate) ?>
                                </td>
                                <td class="px-6 py-4 text-slate-600">
                                    <?= e($session->endDate) ?>
                                </td>
                                <td class="px-6 py-4">
                                    <?php if ($session->isActive()): ?>
                                        <?php $this->include('components/badge', ['label' => 'Active / Current', 'variant' => 'success']); ?>
                                    <?php elseif ($session->isPlanning()): ?>
                                        <?php $this->include('components/badge', ['label' => 'Planning', 'variant' => 'warning']); ?>
                                    <?php else: ?>
                                        <?php $this->include('components/badge', ['label' => 'Archived', 'variant' => 'neutral']); ?>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="inline-flex items-center gap-2 justify-end">
                                        <?php if ($session->isPlanning()): ?>
                                            <form method="POST" action="/admin/sessions/<?= $session->id ?>/make-current" class="inline">
                                                <?= csrf_field() ?>
                                                <?php $this->include('components/button', [
                                                    'type' => 'submit',
                                                    'variant' => 'secondary',
                                                    'label' => 'Make Active',
                                                    'class' => 'px-2.5 py-1 min-h-0 text-xs font-semibold'
                                                ]); ?>
                                            </form>
                                        <?php endif; ?>

                                        <?php if (!$session->isArchived()): ?>
                                            <?php $this->include('components/button', [
                                                'type' => 'button',
                                                'variant' => 'secondary',
                                                'label' => 'Edit',
                                                'class' => 'px-2.5 py-1 min-h-0 text-xs font-semibold',
                                                'attributes' => 'onclick="openEditModal(' . $session->id . ', \'' . e(addslashes($session->name)) . '\', \'' . e($session->startDate) . '\', \'' . e($session->endDate) . '\')"'
                                            ]); ?>

                                            <form method="POST" action="/admin/sessions/<?= $session->id ?>/archive" class="inline" onsubmit="return confirm('Archiving a session is permanent. Proceed?');">
                                                <?= csrf_field() ?>
                                                <?php $this->include('components/button', [
                                                    'type' => 'submit',
                                                    'variant' => 'danger',
                                                    'label' => 'Archive',
                                                    'class' => 'px-2.5 py-1 min-h-0 text-xs font-semibold'
                                                ]); ?>
                                            </form>
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
<form method="POST" action="/admin/sessions" class="space-y-4" novalidate>
    <?= csrf_field() ?>
    
    <?php $this->include('components/input', [
        'name' => 'name',
        'label' => 'Session Name',
        'placeholder' => 'e.g. 2026/2027',
        'required' => true
    ]); ?>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <?php $this->include('components/input', [
            'name' => 'start_date',
            'label' => 'Start Date',
            'type' => 'date',
            'required' => true
        ]); ?>

        <?php $this->include('components/input', [
            'name' => 'end_date',
            'label' => 'End Date',
            'type' => 'date',
            'required' => true
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
            'label' => 'Create Session'
        ]); ?>
    </div>
</form>
<?php $createModalBody = ob_get_clean(); ?>

<?php $this->include('components/modal', [
    'id' => 'create-modal',
    'title' => 'Create Academic Session',
    'body' => $createModalBody,
    'size' => 'md'
]); ?>

<!-- Edit Modal -->
<?php ob_start(); ?>
<form id="edit-form" method="POST" action="" class="space-y-4" novalidate>
    <?= csrf_field() ?>
    
    <?php $this->include('components/input', [
        'name' => 'name',
        'id' => 'edit_name',
        'label' => 'Session Name',
        'placeholder' => 'e.g. 2026/2027',
        'required' => true
    ]); ?>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <?php $this->include('components/input', [
            'name' => 'start_date',
            'id' => 'edit_start_date',
            'label' => 'Start Date',
            'type' => 'date',
            'required' => true
        ]); ?>

        <?php $this->include('components/input', [
            'name' => 'end_date',
            'id' => 'edit_end_date',
            'label' => 'End Date',
            'type' => 'date',
            'required' => true
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
    'title' => 'Edit Academic Session',
    'body' => $editModalBody,
    'size' => 'md'
]); ?>

<script>
    function openEditModal(id, name, startDate, endDate) {
        document.getElementById('edit-form').action = '/admin/sessions/' + id;
        document.getElementById('edit_name').value = name;
        document.getElementById('edit_start_date').value = startDate;
        document.getElementById('edit_end_date').value = endDate;
        window.LMS.showModal('edit-modal');
    }
</script>
