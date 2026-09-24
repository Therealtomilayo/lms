<?php
$this->layout('layouts/admin', [
    'title' => $title ?? 'Academic Levels — Claret LMS',
    'headerTitle' => $headerTitle ?? 'Academic Levels'
]);

// Build stage options & lookup map
$stageOptions = [];
$stageNameLookup = [];
if (!empty($stages)) {
    foreach ($stages as $stg) {
        $stageOptions[$stg->key] = $stg->name;
        $stageNameLookup[$stg->key] = $stg->name;
    }
}

// Build grading scale options for the select components
$scaleOptions = ['' => 'Stage Default Scale'];
foreach ($gradingScales as $scale) {
    $stageTag = !empty($scale->stage) ? ' [' . ($stageNameLookup[$scale->stage] ?? ucwords(str_replace('_', ' ', $scale->stage))) . ']' : '';
    $scaleOptions[$scale->id] = e($scale->name) . $stageTag;
}
?>
<div class="space-y-6">
    <!-- Page Header & Stage Summary -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-slate-900">Academic Levels & Educational Stages</h2>
            <p class="text-sm text-slate-500 mt-1">Configure institutional stages (Primary, Junior Secondary, etc.) and level grading scales.</p>
        </div>
        <div class="flex items-center gap-2.5 flex-wrap">
            <?php $this->include('components/button', [
                'type' => 'button',
                'variant' => 'secondary',
                'label' => '+ Add Stage',
                'icon' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>',
                'attributes' => 'onclick="window.LMS.showModal(\'stage-modal\')"'
            ]); ?>
            <?php $this->include('components/button', [
                'type' => 'button',
                'variant' => 'primary',
                'label' => 'Create Level',
                'icon' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>',
                'attributes' => 'onclick="window.LMS.showModal(\'create-modal\')"'
            ]); ?>
        </div>
    </div>

    <!-- Educational Stages Overview Bar -->
    <div class="bg-white border border-slate-200 rounded-xl p-4 shadow-xs">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-3">
            <div>
                <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Configured Educational Stages:</span>
                <div class="flex flex-wrap items-center gap-2 mt-1.5">
                    <?php if (!empty($stages)): ?>
                        <?php foreach ($stages as $stg): ?>
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-700 border border-slate-200">
                                <span class="w-1.5 h-1.5 rounded-full bg-brand-500"></span>
                                <?= e($stg->name) ?>
                                <span class="text-[10px] text-slate-400 font-mono">(<?= e($stg->key) ?>)</span>
                            </span>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <span class="text-xs text-slate-400 italic">No stages configured.</span>
                    <?php endif; ?>
                </div>
            </div>
            <a href="/admin/grading-scales" class="inline-flex items-center gap-1.5 text-xs font-semibold text-brand-600 hover:text-brand-700 hover:underline shrink-0">
                <span>Manage Stage Grading Scales &rarr;</span>
            </a>
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
                            <th scope="col" class="px-6 py-3.5">Educational Stage</th>
                            <th scope="col" class="px-6 py-3.5">Grading Scale</th>
                            <th scope="col" class="px-6 py-3.5 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        <?php foreach ($levels as $lvl): ?>
                            <?php 
                            $stageLabel = $stageNameLookup[$lvl->stage] ?? ucwords(str_replace('_', ' ', $lvl->stage));
                            ?>
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
                                    <?php $this->include('components/badge', ['label' => $stageLabel, 'variant' => 'info']); ?>
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
                                        <span class="text-sm font-medium text-slate-800"><?= $scaleName ?></span>
                                        <span class="text-[10px] text-brand-600 font-semibold block">Level Override</span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center gap-1 text-xs text-slate-600 font-medium">
                                            <svg class="w-3.5 h-3.5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                            Stage Default
                                        </span>
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

<!-- Add Stage Modal -->
<?php ob_start(); ?>
<form method="POST" action="/admin/academic-levels/stages" class="space-y-4" novalidate>
    <?= csrf_field() ?>

    <?php $this->include('components/input', [
        'name' => 'name',
        'id' => 'stage_name',
        'label' => 'Stage Display Name',
        'placeholder' => 'e.g. Early Years, Primary, Junior Secondary',
        'required' => true,
        'helpText' => 'The human-readable label shown across the system.'
    ]); ?>

    <?php $this->include('components/input', [
        'name' => 'key',
        'id' => 'stage_key',
        'label' => 'Stage Code / Key (Optional)',
        'placeholder' => 'e.g. early_years, primary, junior_secondary',
        'required' => false,
        'helpText' => 'Leave blank to automatically generate from name.'
    ]); ?>

    <?php $this->include('components/input', [
        'name' => 'rank_order',
        'id' => 'stage_rank_order',
        'label' => 'Sort Order',
        'type' => 'number',
        'value' => '10',
        'required' => true,
        'helpText' => 'Ordering index for listing educational stages.'
    ]); ?>

    <div class="pt-4 border-t border-slate-200 flex justify-end gap-3">
        <?php $this->include('components/button', [
            'type' => 'button',
            'variant' => 'secondary',
            'label' => 'Cancel',
            'attributes' => 'onclick="window.LMS.hideModal(\'stage-modal\')"'
        ]); ?>
        <?php $this->include('components/button', [
            'type' => 'submit',
            'variant' => 'primary',
            'label' => 'Save Stage'
        ]); ?>
    </div>
</form>
<?php $stageModalBody = ob_get_clean(); ?>

<?php $this->include('components/modal', [
    'id' => 'stage-modal',
    'title' => 'Configure Educational Stage',
    'body' => $stageModalBody,
    'size' => 'md'
]); ?>

<!-- Create Level Modal -->
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

    <?php $this->include('components/select', [
        'name' => 'stage',
        'id' => 'create_level_stage',
        'label' => 'Educational Stage',
        'options' => $stageOptions,
        'selected' => 'junior_secondary',
        'required' => true,
        'helpText' => 'Select educational stage for stage-wide grading scales and settings.'
    ]); ?>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <?php $this->include('components/input', [
            'name' => 'rank_order',
            'id' => 'create_rank_order',
            'label' => 'Rank Order',
            'type' => 'number',
            'value' => '1',
            'required' => true,
            'helpText' => 'Determines sort position in lists.'
        ]); ?>

        <?php $this->include('components/select', [
            'name' => 'grading_scale_id',
            'id' => 'create_scale',
            'label' => 'Grading Scale',
            'options' => $scaleOptions,
            'selected' => '',
            'placeholder' => '',
            'helpText' => 'Leave as "Stage Default" to inherit stage scale.'
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

<!-- Edit Level Modal -->
<?php ob_start(); ?>
<form id="edit-form" method="POST" action="" class="space-y-4" novalidate>
    <?= csrf_field() ?>

    <?php $this->include('components/input', [
        'name' => 'name',
        'id' => 'edit_level_name',
        'label' => 'Level Name',
        'required' => true
    ]); ?>

    <?php $this->include('components/select', [
        'name' => 'stage',
        'id' => 'edit_level_stage',
        'label' => 'Educational Stage',
        'options' => $stageOptions,
        'required' => true,
        'helpText' => 'Select educational stage for stage-wide grading scales and settings.'
    ]); ?>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <?php $this->include('components/input', [
            'name' => 'rank_order',
            'id' => 'edit_rank_order',
            'label' => 'Rank Order',
            'type' => 'number',
            'required' => true,
            'helpText' => 'Determines sort position in lists.'
        ]); ?>

        <?php $this->include('components/select', [
            'name' => 'grading_scale_id',
            'id' => 'edit_scale',
            'label' => 'Grading Scale',
            'options' => $scaleOptions,
            'selected' => '',
            'placeholder' => '',
            'helpText' => 'Leave as "Stage Default" to inherit stage scale.'
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
