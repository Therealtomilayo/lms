<?php
$this->layout('layouts/admin', [
    'title' => 'Grading Scales & Boundaries — Claret LMS',
    'headerTitle' => 'Grading Scales & Boundaries',
    'headerSubtitle' => 'Configure stage-specific grade boundaries, score ranges, and academic remarks.'
]);

// Build stage lookup map
$stageLookup = [];
$stageOptions = ['' => 'Universal / All Stages'];
if (!empty($stages)) {
    foreach ($stages as $stg) {
        $stageLookup[$stg->key] = $stg->name;
        $stageOptions[$stg->key] = $stg->name;
    }
}
?>
<div class="space-y-6">
    <!-- Top Action / Info Banner -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-slate-900">Grading Scales Management</h2>
            <p class="text-sm text-slate-500 mt-1">Configure distinct grading standards for institutional stages (Junior Secondary, Senior Secondary, Primary, etc.).</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="/admin/academic-levels" class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold rounded-lg border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 shadow-xs transition">
                <span>View Academic Levels &rarr;</span>
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Scale List -->
        <div class="lg:col-span-2 space-y-6">
            <?php if (empty($scales)): ?>
                <div class="bg-white rounded-xl border border-slate-200 p-8 text-center shadow-sm">
                    <?php $this->include('components/empty_state', [
                        'title' => 'No Grading Scales',
                        'message' => 'No grading scales or boundaries have been configured yet.'
                    ]); ?>
                </div>
            <?php else: ?>
                <?php foreach ($scales as $scale): ?>
                    <?php
                    $stageName = !empty($scale->stage) ? ($stageLookup[$scale->stage] ?? ucwords(str_replace('_', ' ', $scale->stage))) : 'Universal / All Stages';
                    $scaleJson = htmlspecialchars(json_encode([
                        'id' => $scale->id,
                        'name' => $scale->name,
                        'stage' => $scale->stage ?? '',
                        'description' => $scale->description ?? '',
                        'is_default' => $scale->isDefault ? 1 : 0,
                        'boundaries' => array_map(fn($b) => [
                            'letter' => $b->letter,
                            'min_score' => $b->minScore,
                            'max_score' => $b->maxScore,
                            'remark' => $b->remark ?? '',
                        ], $scale->boundaries)
                    ]), ENT_QUOTES, 'UTF-8');
                    ?>
                    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-4 transition hover:border-slate-300">
                        <div class="flex items-center justify-between flex-wrap gap-2">
                            <div class="flex items-center gap-2 flex-wrap">
                                <h3 class="text-lg font-bold text-slate-900"><?= e($scale->name) ?></h3>
                                <?php if (!empty($scale->stage)): ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-indigo-50 text-indigo-700 border border-indigo-200">
                                        <?= e($stageName) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-600">
                                        Universal Scale
                                    </span>
                                <?php endif; ?>
                                <?php if ($scale->isDefault): ?>
                                    <?php $this->include('components/badge', ['label' => 'System Default', 'variant' => 'success']); ?>
                                <?php endif; ?>
                            </div>
                            <div class="flex items-center gap-2">
                                <button type="button" 
                                        onclick="openEditScaleModal(JSON.parse(this.getAttribute('data-scale')))"
                                        data-scale="<?= $scaleJson ?>"
                                        class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-semibold rounded-lg border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 transition shadow-2xs">
                                    <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                    Edit Scale
                                </button>
                                <?php if (!$scale->isDefault): ?>
                                    <form method="POST" action="/admin/grading-scales/<?= $scale->id ?>/delete" onsubmit="return confirm('Are you sure you want to remove this grading scale?');" class="inline">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="inline-flex items-center p-1.5 text-xs font-medium text-slate-400 hover:text-rose-600 rounded-lg transition" title="Delete Scale">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>

                        <?php if ($scale->description): ?>
                            <p class="text-sm text-slate-500 font-normal"><?= e($scale->description) ?></p>
                        <?php endif; ?>

                        <div class="overflow-x-auto rounded-lg border border-slate-200">
                            <table class="w-full text-left text-sm border-collapse">
                                <thead>
                                    <tr class="bg-slate-50 border-b border-slate-200 text-slate-600 font-semibold uppercase text-xs tracking-wider">
                                        <th class="py-2.5 px-4">Grade</th>
                                        <th class="py-2.5 px-4">Score Range</th>
                                        <th class="py-2.5 px-4">Remark</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 font-medium text-slate-700">
                                    <?php foreach ($scale->boundaries as $b): ?>
                                        <tr class="hover:bg-slate-50/50 transition">
                                            <td class="py-2.5 px-4 font-bold text-slate-900"><?= e($b->letter) ?></td>
                                            <td class="py-2.5 px-4 text-slate-600 font-mono text-xs"><?= number_format($b->minScore, 1) ?>% &ndash; <?= number_format($b->maxScore, 1) ?>%</td>
                                            <td class="py-2.5 px-4 text-slate-600 font-normal"><?= e($b->remark ?? '&mdash;') ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Create Scale Form -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-5 h-fit">
            <div>
                <h3 class="text-lg font-bold text-slate-900">Create Grading Scale</h3>
                <p class="text-xs text-slate-500 mt-0.5">Define a new scale for an institutional stage or universal usage.</p>
            </div>
            <form method="POST" action="/admin/grading-scales" class="space-y-4" novalidate>
                <?= csrf_field() ?>

                <?php $this->include('components/input', [
                    'name' => 'name',
                    'id' => 'scale_name',
                    'label' => 'Scale Name',
                    'required' => true,
                    'placeholder' => 'e.g. Primary School Standard Scale'
                ]); ?>

                <?php $this->include('components/select', [
                    'name' => 'stage',
                    'id' => 'scale_stage',
                    'label' => 'Educational Stage Association',
                    'options' => $stageOptions,
                    'selected' => '',
                    'placeholder' => '',
                    'helpText' => 'Automatically applies to all academic levels in this stage.'
                ]); ?>

                <div class="form-group flex flex-col gap-1.5 w-full">
                    <label for="scale_description" class="text-sm font-semibold text-slate-700">Description</label>
                    <textarea name="description" id="scale_description" rows="2" placeholder="Optional notes regarding this scale" 
                              class="w-full text-sm px-3.5 py-2.5 rounded-lg border border-slate-300 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 shadow-xs transition duration-200"></textarea>
                </div>

                <div class="flex items-center gap-2 py-1">
                    <input type="checkbox" name="is_default" value="1" id="is_default" class="rounded border-slate-300 text-brand-600 focus:ring-brand-500 h-4 w-4">
                    <label for="is_default" class="text-sm font-semibold text-slate-700 cursor-pointer">Set as System Default</label>
                </div>

                <div class="pt-4 border-t border-slate-200">
                    <div class="flex items-center justify-between mb-3">
                        <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider">Grade Boundaries</h4>
                        <button type="button" onclick="addBoundaryRow('create-boundaries-container')" class="text-xs font-semibold text-brand-600 hover:text-brand-700 flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            Add Row
                        </button>
                    </div>
                    
                    <!-- Column Titles -->
                    <div class="grid grid-cols-12 gap-1.5 mb-2 text-[10px] font-bold text-slate-500 uppercase text-center">
                        <div class="col-span-2">Grade</div>
                        <div class="col-span-3">Min %</div>
                        <div class="col-span-3">Max %</div>
                        <div class="col-span-3">Remark</div>
                        <div class="col-span-1"></div>
                    </div>

                    <div id="create-boundaries-container" class="space-y-2">
                        <?php 
                        $defaults = [
                            ['letter' => 'A', 'min' => 70, 'max' => 100, 'remark' => 'Excellent'],
                            ['letter' => 'B', 'min' => 60, 'max' => 69.99, 'remark' => 'Very Good'],
                            ['letter' => 'C', 'min' => 50, 'max' => 59.99, 'remark' => 'Credit'],
                            ['letter' => 'D', 'min' => 45, 'max' => 49.99, 'remark' => 'Pass'],
                            ['letter' => 'E', 'min' => 40, 'max' => 44.99, 'remark' => 'Fair'],
                            ['letter' => 'F', 'min' => 0, 'max' => 39.99, 'remark' => 'Fail'],
                        ];
                        foreach ($defaults as $i => $d): ?>
                            <div class="grid grid-cols-12 gap-1.5 items-center text-xs boundary-row">
                                <div class="col-span-2">
                                    <input type="text" name="boundaries[<?= $i ?>][letter]" value="<?= $d['letter'] ?>" class="w-full px-1.5 py-1.5 bg-white border border-slate-300 rounded text-center font-bold focus:outline-none focus:ring-2 focus:ring-brand-500">
                                </div>
                                <div class="col-span-3">
                                    <input type="number" step="0.01" name="boundaries[<?= $i ?>][min_score]" value="<?= $d['min'] ?>" placeholder="Min" class="w-full px-1.5 py-1.5 bg-white border border-slate-300 rounded text-center focus:outline-none focus:ring-2 focus:ring-brand-500">
                                </div>
                                <div class="col-span-3">
                                    <input type="number" step="0.01" name="boundaries[<?= $i ?>][max_score]" value="<?= $d['max'] ?>" placeholder="Max" class="w-full px-1.5 py-1.5 bg-white border border-slate-300 rounded text-center focus:outline-none focus:ring-2 focus:ring-brand-500">
                                </div>
                                <div class="col-span-3">
                                    <input type="text" name="boundaries[<?= $i ?>][remark]" value="<?= $d['remark'] ?>" placeholder="Remark" class="w-full px-1.5 py-1.5 bg-white border border-slate-300 rounded focus:outline-none focus:ring-2 focus:ring-brand-500">
                                </div>
                                <div class="col-span-1 text-center">
                                    <button type="button" onclick="this.closest('.boundary-row').remove()" class="text-slate-400 hover:text-rose-600 transition" title="Remove row">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="pt-2">
                    <?php $this->include('components/button', [
                        'type' => 'submit',
                        'variant' => 'primary',
                        'label' => 'Save Grading Scale',
                        'class' => 'w-full justify-center'
                    ]); ?>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Scale Modal -->
<?php ob_start(); ?>
<form id="edit-scale-form" method="POST" action="" class="space-y-4" novalidate>
    <?= csrf_field() ?>

    <?php $this->include('components/input', [
        'name' => 'name',
        'id' => 'edit_scale_name',
        'label' => 'Scale Name',
        'required' => true,
        'placeholder' => 'e.g. Junior Secondary Scale'
    ]); ?>

    <?php $this->include('components/select', [
        'name' => 'stage',
        'id' => 'edit_scale_stage',
        'label' => 'Educational Stage Association',
        'options' => $stageOptions,
        'placeholder' => '',
        'helpText' => 'Automatically applies to all academic levels in this stage.'
    ]); ?>

    <div class="form-group flex flex-col gap-1.5 w-full">
        <label for="edit_scale_description" class="text-sm font-semibold text-slate-700">Description</label>
        <textarea name="description" id="edit_scale_description" rows="2" placeholder="Optional notes" 
                  class="w-full text-sm px-3.5 py-2.5 rounded-lg border border-slate-300 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 shadow-xs transition duration-200"></textarea>
    </div>

    <div class="flex items-center gap-2 py-1">
        <input type="checkbox" name="is_default" value="1" id="edit_is_default" class="rounded border-slate-300 text-brand-600 focus:ring-brand-500 h-4 w-4">
        <label for="edit_is_default" class="text-sm font-semibold text-slate-700 cursor-pointer">Set as System Default</label>
    </div>

    <div class="pt-4 border-t border-slate-200">
        <div class="flex items-center justify-between mb-3">
            <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider">Grade Boundaries</h4>
            <button type="button" onclick="addBoundaryRow('edit-boundaries-container')" class="text-xs font-semibold text-brand-600 hover:text-brand-700 flex items-center gap-1">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Add Row
            </button>
        </div>
        
        <!-- Column Titles -->
        <div class="grid grid-cols-12 gap-1.5 mb-2 text-[10px] font-bold text-slate-500 uppercase text-center">
            <div class="col-span-2">Grade</div>
            <div class="col-span-3">Min %</div>
            <div class="col-span-3">Max %</div>
            <div class="col-span-3">Remark</div>
            <div class="col-span-1"></div>
        </div>

        <div id="edit-boundaries-container" class="space-y-2 max-h-72 overflow-y-auto pr-1">
            <!-- Dynamic rows will be rendered here -->
        </div>
    </div>

    <div class="pt-4 border-t border-slate-200 flex justify-end gap-3">
        <?php $this->include('components/button', [
            'type' => 'button',
            'variant' => 'secondary',
            'label' => 'Cancel',
            'attributes' => 'onclick="window.LMS.hideModal(\'edit-scale-modal\')"'
        ]); ?>
        <?php $this->include('components/button', [
            'type' => 'submit',
            'variant' => 'primary',
            'label' => 'Save Changes'
        ]); ?>
    </div>
</form>
<?php $editScaleModalBody = ob_get_clean(); ?>

<?php $this->include('components/modal', [
    'id' => 'edit-scale-modal',
    'title' => 'Edit Grading Scale & Boundaries',
    'body' => $editScaleModalBody,
    'size' => 'lg'
]); ?>

<script>
    let boundaryIndex = 100;

    function addBoundaryRow(containerId, letter = '', min = '', max = '', remark = '') {
        const container = document.getElementById(containerId);
        boundaryIndex++;
        const row = document.createElement('div');
        row.className = 'grid grid-cols-12 gap-1.5 items-center text-xs boundary-row';
        row.innerHTML = `
            <div class="col-span-2">
                <input type="text" name="boundaries[${boundaryIndex}][letter]" value="${escapeHtml(letter)}" placeholder="A" class="w-full px-1.5 py-1.5 bg-white border border-slate-300 rounded text-center font-bold focus:outline-none focus:ring-2 focus:ring-brand-500">
            </div>
            <div class="col-span-3">
                <input type="number" step="0.01" name="boundaries[${boundaryIndex}][min_score]" value="${min}" placeholder="Min" class="w-full px-1.5 py-1.5 bg-white border border-slate-300 rounded text-center focus:outline-none focus:ring-2 focus:ring-brand-500">
            </div>
            <div class="col-span-3">
                <input type="number" step="0.01" name="boundaries[${boundaryIndex}][max_score]" value="${max}" placeholder="Max" class="w-full px-1.5 py-1.5 bg-white border border-slate-300 rounded text-center focus:outline-none focus:ring-2 focus:ring-brand-500">
            </div>
            <div class="col-span-3">
                <input type="text" name="boundaries[${boundaryIndex}][remark]" value="${escapeHtml(remark)}" placeholder="Remark" class="w-full px-1.5 py-1.5 bg-white border border-slate-300 rounded focus:outline-none focus:ring-2 focus:ring-brand-500">
            </div>
            <div class="col-span-1 text-center">
                <button type="button" onclick="this.closest('.boundary-row').remove()" class="text-slate-400 hover:text-rose-600 transition" title="Remove row">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
        `;
        container.appendChild(row);
    }

    function openEditScaleModal(scale) {
        document.getElementById('edit-scale-form').action = '/admin/grading-scales/' + scale.id;
        document.getElementById('edit_scale_name').value = scale.name || '';
        document.getElementById('edit_scale_stage').value = scale.stage || '';
        document.getElementById('edit_scale_description').value = scale.description || '';
        document.getElementById('edit_is_default').checked = Boolean(scale.is_default);

        const container = document.getElementById('edit-boundaries-container');
        container.innerHTML = '';

        if (scale.boundaries && scale.boundaries.length > 0) {
            scale.boundaries.forEach(b => {
                addBoundaryRow('edit-boundaries-container', b.letter, b.min_score, b.max_score, b.remark);
            });
        } else {
            addBoundaryRow('edit-boundaries-container', 'A', 70, 100, 'Excellent');
            addBoundaryRow('edit-boundaries-container', 'F', 0, 39.99, 'Fail');
        }

        window.LMS.showModal('edit-scale-modal');
    }

    function escapeHtml(text) {
        if (!text) return '';
        const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
        return text.toString().replace(/[&<>"']/g, m => map[m]);
    }
</script>
