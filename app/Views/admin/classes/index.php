<?php
$this->layout('layouts/admin', [
    'title' => $title ?? 'Classes & Arms — Claret LMS',
    'headerTitle' => $headerTitle ?? 'Classes & Arms'
]);

// Build level options for select components
$levelOptions = ['' => 'Select Level...'];
foreach ($levels as $lvl) {
    $levelOptions[$lvl->id] = e($lvl->name) . ' (' . e($lvl->stage) . ')';
}
?>
<div class="space-y-6">
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-slate-900">Classes &amp; Arms</h2>
            <p class="text-sm text-slate-500 mt-1">Manage class groups and optional section arms (A, B, Gold, Diamond) across academic levels.</p>
        </div>
        <div>
            <?php $this->include('components/button', [
                'type' => 'button',
                'variant' => 'primary',
                'label' => 'Create Class',
                'icon' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>',
                'attributes' => 'onclick="window.LMS.showModal(\'create-modal\')"'
            ]); ?>
        </div>
    </div>

    <!-- Classes Table -->
    <?php if (empty($classes)): ?>
        <?php $this->include('components/empty_state', [
            'title' => 'No Classes Configured',
            'message' => 'No classes or arms have been created yet. Click "Create Class" to get started.'
        ]); ?>
    <?php else: ?>
        <div class="bg-white border border-slate-200 rounded-xl overflow-hidden shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                    <thead class="bg-slate-50 text-slate-600 font-semibold">
                        <tr>
                            <th scope="col" class="px-6 py-3.5">Level</th>
                            <th scope="col" class="px-6 py-3.5">Class Name</th>
                            <th scope="col" class="px-6 py-3.5">Section / Arm</th>
                            <th scope="col" class="px-6 py-3.5">Status</th>
                            <th scope="col" class="px-6 py-3.5 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        <?php foreach ($classes as $cls): ?>
                            <tr class="hover:bg-slate-50/50 transition">
                                <td class="px-6 py-4">
                                    <?php $this->include('components/badge', [
                                        'label' => $cls->academicLevel?->name ?? 'Level #' . $cls->academicLevelId,
                                        'variant' => 'neutral'
                                    ]); ?>
                                </td>
                                <td class="px-6 py-4 font-semibold text-slate-900">
                                    <?= e($cls->name) ?>
                                </td>
                                <td class="px-6 py-4">
                                    <?php if ($cls->sectionArm): ?>
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-md bg-brand-50 text-brand-700 text-xs font-bold font-mono border border-brand-100">
                                            Arm <?= e($cls->sectionArm) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-xs text-slate-400 italic">—</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4">
                                    <?php if ($cls->isActive()): ?>
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
                                            'attributes' => 'onclick="openEditModal(' . $cls->id . ', ' . $cls->academicLevelId . ', \'' . e(addslashes($cls->name)) . '\', \'' . e(addslashes($cls->sectionArm ?? '')) . '\')"'
                                        ]); ?>

                                        <form method="POST" action="/admin/classes/<?= $cls->id ?>/status" class="inline">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="status" value="<?= $cls->isActive() ? 'inactive' : 'active' ?>">
                                            <?php $this->include('components/button', [
                                                'type' => 'submit',
                                                'variant' => 'secondary',
                                                'label' => $cls->isActive() ? 'Deactivate' : 'Activate',
                                                'class' => 'px-2.5 py-1 min-h-0 text-xs font-semibold ' . ($cls->isActive()
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
<form method="POST" action="/admin/classes" class="space-y-4" novalidate>
    <?= csrf_field() ?>

    <?php $this->include('components/select', [
        'name' => 'academic_level_id',
        'id' => 'create_level',
        'label' => 'Academic Level',
        'options' => $levelOptions,
        'selected' => '',
        'required' => true,
        'placeholder' => ''
    ]); ?>

    <?php $this->include('components/input', [
        'name' => 'name',
        'id' => 'create_class_name',
        'label' => 'Class Name',
        'placeholder' => 'e.g. JSS 1A, Grade 7 Gold',
        'required' => true
    ]); ?>

    <?php $this->include('components/input', [
        'name' => 'section_arm',
        'id' => 'create_arm',
        'label' => 'Section / Arm',
        'placeholder' => 'e.g. A, B, Gold, Diamond',
        'helpText' => 'Optional. Leave blank if the class has no arm designation.'
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
            'label' => 'Create Class'
        ]); ?>
    </div>
</form>
<?php $createModalBody = ob_get_clean(); ?>

<?php $this->include('components/modal', [
    'id' => 'create-modal',
    'title' => 'Create Class',
    'body' => $createModalBody,
    'size' => 'md'
]); ?>

<!-- Edit Modal -->
<?php ob_start(); ?>
<form id="edit-form" method="POST" action="" class="space-y-4" novalidate>
    <?= csrf_field() ?>

    <?php $this->include('components/select', [
        'name' => 'academic_level_id',
        'id' => 'edit_level',
        'label' => 'Academic Level',
        'options' => $levelOptions,
        'selected' => '',
        'required' => true,
        'placeholder' => ''
    ]); ?>

    <?php $this->include('components/input', [
        'name' => 'name',
        'id' => 'edit_class_name',
        'label' => 'Class Name',
        'required' => true
    ]); ?>

    <?php $this->include('components/input', [
        'name' => 'section_arm',
        'id' => 'edit_arm',
        'label' => 'Section / Arm',
        'helpText' => 'Optional. Leave blank if the class has no arm designation.'
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
    'title' => 'Edit Class',
    'body' => $editModalBody,
    'size' => 'md'
]); ?>

<script>
    function openEditModal(id, levelId, name, arm) {
        document.getElementById('edit-form').action = '/admin/classes/' + id;
        document.getElementById('edit_level').value = levelId;
        document.getElementById('edit_class_name').value = name;
        document.getElementById('edit_arm').value = arm || '';
        window.LMS.showModal('edit-modal');
    }
</script>
