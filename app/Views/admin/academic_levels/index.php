<?php
$this->layout('layouts/admin', [
    'title' => $title ?? 'Academic Levels — Claret LMS',
    'headerTitle' => $headerTitle ?? 'Academic Levels'
]);

// Build grading scale options for the select components
$scaleOptions = ['' => 'Default Scale'];
foreach ($gradingScales as $scale) {
    $scaleOptions[$scale->id] = e($scale->name);
}
?>
<div class="space-y-6">
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-slate-900">Academic Levels</h2>
            <p class="text-sm text-slate-500 mt-1">Configure institutional stages (e.g. Primary, Junior Secondary) and grading scales.</p>
        </div>
        <div>
            <?php $this->include('components/button', [
                'type' => 'button',
                'variant' => 'primary',
                'label' => 'Create Level',
                'icon' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>',
                'attributes' => 'onclick="window.LMS.showModal(\'create-modal\')"'
            ]); ?>
        </div>
    </div>

    <!-- Levels Table -->
    <?php if (empty($levels)): ?>
        <?php $this->include('components/empty_state', [
            'title' => 'No Academic Levels',
            'message' => 'No academic levels configured yet. Click "Create Level" to get started.'
        ]); ?>
    <?php else: ?>
        <div class="bg-white border border-slate-200 rounded-xl overflow-hidden shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                    <thead class="bg-slate-50 text-slate-600 font-semibold">
                        <tr>
                            <th scope="col" class="px-6 py-3.5">Rank</th>
                            <th scope="col" class="px-6 py-3.5">Level Name</th>
                            <th scope="col" class="px-6 py-3.5">Stage</th>
                            <th scope="col" class="px-6 py-3.5">Grading Scale</th>
                            <th scope="col" class="px-6 py-3.5 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        <?php foreach ($levels as $lvl): ?>
                            <tr class="hover:bg-slate-50/50 transition">
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-slate-100 text-slate-600 text-xs font-bold font-mono">
                                        <?= e((string)$lvl->rankOrder) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 font-semibold text-slate-900">
                                    <?= e($lvl->name) ?>
                                </td>
                                <td class="px-6 py-4">
                                    <?php $this->include('components/badge', ['label' => $lvl->stage, 'variant' => 'info']); ?>
                                </td>
                                <td class="px-6 py-4 text-slate-600">
                                    <?php if ($lvl->gradingScaleId): ?>
                                        <?php
                                        $scaleName = 'Scale #' . $lvl->gradingScaleId;
                                        foreach ($gradingScales as $s) {
                                            if ($s->id === $lvl->gradingScaleId) {
                                                $scaleName = e($s->name);
                                                break;
                                            }
                                        }
                                        ?>
                                        <span class="text-sm font-medium text-slate-700"><?= $scaleName ?></span>
                                    <?php else: ?>
                                        <span class="text-xs text-slate-400 italic">Default Scale</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <?php $this->include('components/button', [
                                        'type' => 'button',
                                        'variant' => 'secondary',
                                        'label' => 'Edit',
                                        'class' => 'px-2.5 py-1 min-h-0 text-xs font-semibold',
                                        'attributes' => 'onclick="openEditModal(' . $lvl->id . ', \'' . e(addslashes($lvl->name)) . '\', \'' . e(addslashes($lvl->stage)) . '\', ' . $lvl->rankOrder . ', \'' . ($lvl->gradingScaleId ?? '') . '\')"'
                                    ]); ?>
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
<form method="POST" action="/admin/academic-levels" class="space-y-4" novalidate>
    <?= csrf_field() ?>

    <?php $this->include('components/input', [
        'name' => 'name',
        'id' => 'create_level_name',
        'label' => 'Level Name',
        'placeholder' => 'e.g. JSS 1, Grade 7',
        'required' => true
    ]); ?>

    <?php $this->include('components/input', [
        'name' => 'stage',
        'id' => 'create_level_stage',
        'label' => 'Stage',
        'placeholder' => 'e.g. Junior Secondary, Primary',
        'required' => true
    ]); ?>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <?php $this->include('components/input', [
            'name' => 'rank_order',
            'id' => 'create_rank_order',
            'label' => 'Rank Order',
            'type' => 'number',
            'value' => '1',
            'required' => true,
            'helpText' => 'Determines the sort position in lists.'
        ]); ?>

        <?php $this->include('components/select', [
            'name' => 'grading_scale_id',
            'id' => 'create_scale',
            'label' => 'Grading Scale',
            'options' => $scaleOptions,
            'selected' => '',
            'placeholder' => ''
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
            'label' => 'Create Level'
        ]); ?>
    </div>
</form>
<?php $createModalBody = ob_get_clean(); ?>

<?php $this->include('components/modal', [
    'id' => 'create-modal',
    'title' => 'Create Academic Level',
    'body' => $createModalBody,
    'size' => 'md'
]); ?>

<!-- Edit Modal -->
<?php ob_start(); ?>
<form id="edit-form" method="POST" action="" class="space-y-4" novalidate>
    <?= csrf_field() ?>

    <?php $this->include('components/input', [
        'name' => 'name',
        'id' => 'edit_level_name',
        'label' => 'Level Name',
        'required' => true
    ]); ?>

    <?php $this->include('components/input', [
        'name' => 'stage',
        'id' => 'edit_level_stage',
        'label' => 'Stage',
        'required' => true
    ]); ?>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <?php $this->include('components/input', [
            'name' => 'rank_order',
            'id' => 'edit_rank_order',
            'label' => 'Rank Order',
            'type' => 'number',
            'required' => true,
            'helpText' => 'Determines the sort position in lists.'
        ]); ?>

        <?php $this->include('components/select', [
            'name' => 'grading_scale_id',
            'id' => 'edit_scale',
            'label' => 'Grading Scale',
            'options' => $scaleOptions,
            'selected' => '',
            'placeholder' => ''
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
    'title' => 'Edit Academic Level',
    'body' => $editModalBody,
    'size' => 'md'
]); ?>

<script>
    function openEditModal(id, name, stage, rankOrder, scaleId) {
        document.getElementById('edit-form').action = '/admin/academic-levels/' + id;
        document.getElementById('edit_level_name').value = name;
        document.getElementById('edit_level_stage').value = stage;
        document.getElementById('edit_rank_order').value = rankOrder;
        document.getElementById('edit_scale').value = scaleId || '';
        window.LMS.showModal('edit-modal');
    }
</script>
