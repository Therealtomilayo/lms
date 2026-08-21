<?php
$this->layout('layouts/admin', [
    'title' => $title ?? 'Subjects — Claret LMS',
    'headerTitle' => $headerTitle ?? 'Subjects'
]);
?>
<div class="space-y-6">
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-slate-900">Subjects Directory</h2>
            <p class="text-sm text-slate-500 mt-1">Manage global curriculum subjects, subject codes, and catalog availability.</p>
        </div>
        <div>
            <?php $this->include('components/button', [
                'type' => 'button',
                'variant' => 'primary',
                'label' => 'Create Subject',
                'icon' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>',
                'attributes' => 'onclick="window.LMS.showModal(\'create-modal\')"'
            ]); ?>
        </div>
    </div>

    <!-- Subjects Table -->
    <?php if (empty($subjects)): ?>
        <?php $this->include('components/empty_state', [
            'title' => 'No Subjects Registered',
            'message' => 'No subjects registered yet. Click "Create Subject" to begin configuring the curriculum.'
        ]); ?>
    <?php else: ?>
        <div class="bg-white border border-slate-200 rounded-xl overflow-hidden shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                    <thead class="bg-slate-50 text-slate-600 font-semibold">
                        <tr>
                            <th scope="col" class="px-6 py-3.5">Code</th>
                            <th scope="col" class="px-6 py-3.5">Subject Name</th>
                            <th scope="col" class="px-6 py-3.5">Status</th>
                            <th scope="col" class="px-6 py-3.5 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        <?php foreach ($subjects as $sub): ?>
                            <tr class="hover:bg-slate-50/50 transition">
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-md bg-slate-100 text-slate-800 text-xs font-mono font-bold uppercase tracking-wider">
                                        <?= e($sub->code) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 font-semibold text-slate-900">
                                    <?= e($sub->name) ?>
                                </td>
                                <td class="px-6 py-4">
                                    <?php if ($sub->isActive()): ?>
                                        <?php $this->include('components/badge', ['label' => 'Active', 'variant' => 'success']); ?>
                                    <?php else: ?>
                                        <?php $this->include('components/badge', ['label' => 'Inactive', 'variant' => 'neutral']); ?>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="inline-flex items-center gap-2 justify-end">
                                        <?php $this->include('components/button', [
                                            'type' => 'button',
                                            'variant' => 'secondary',
                                            'label' => 'Edit',
                                            'class' => 'px-2.5 py-1 min-h-0 text-xs font-semibold',
                                            'attributes' => 'onclick="openEditModal(' . $sub->id . ', \'' . e(addslashes($sub->name)) . '\', \'' . e(addslashes($sub->code)) . '\')"'
                                        ]); ?>

                                        <form method="POST" action="/admin/subjects/<?= $sub->id ?>/status" class="inline">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="status" value="<?= $sub->isActive() ? 'inactive' : 'active' ?>">
                                            <?php $this->include('components/button', [
                                                'type' => 'submit',
                                                'variant' => 'secondary',
                                                'label' => $sub->isActive() ? 'Deactivate' : 'Activate',
                                                'class' => 'px-2.5 py-1 min-h-0 text-xs font-semibold ' . ($sub->isActive()
                                                    ? 'bg-warning-100 hover:bg-warning-200 text-warning-800 border-transparent'
                                                    : 'bg-success-100 hover:bg-success-200 text-success-700 border-transparent')
                                            ]); ?>
                                        </form>
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
<form method="POST" action="/admin/subjects" class="space-y-4" novalidate>
    <?= csrf_field() ?>

    <?php $this->include('components/input', [
        'name' => 'name',
        'id' => 'create_sub_name',
        'label' => 'Subject Name',
        'placeholder' => 'e.g. Mathematics, English Language',
        'required' => true
    ]); ?>

    <?php $this->include('components/input', [
        'name' => 'code',
        'id' => 'create_sub_code',
        'label' => 'Subject Code',
        'placeholder' => 'e.g. jss1_mth, jss1_eng',
        'required' => true,
        'class' => 'uppercase'
    ]); ?>

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
            'label' => 'Create Subject'
        ]); ?>
    </div>
</form>
<?php $createModalBody = ob_get_clean(); ?>

<?php $this->include('components/modal', [
    'id' => 'create-modal',
    'title' => 'Create Subject',
    'body' => $createModalBody,
    'size' => 'md'
]); ?>

<!-- Edit Modal -->
<?php ob_start(); ?>
<form id="edit-form" method="POST" action="" class="space-y-4" novalidate>
    <?= csrf_field() ?>

    <?php $this->include('components/input', [
        'name' => 'name',
        'id' => 'edit_sub_name',
        'label' => 'Subject Name',
        'required' => true
    ]); ?>

    <?php $this->include('components/input', [
        'name' => 'code',
        'id' => 'edit_sub_code',
        'label' => 'Subject Code',
        'required' => true,
        'class' => 'uppercase'
    ]); ?>

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
    'title' => 'Edit Subject',
    'body' => $editModalBody,
    'size' => 'md'
]); ?>

<script>
    function openEditModal(id, name, code) {
        document.getElementById('edit-form').action = '/admin/subjects/' + id;
        document.getElementById('edit_sub_name').value = name;
        document.getElementById('edit_sub_code').value = code;
        window.LMS.showModal('edit-modal');
    }
</script>
