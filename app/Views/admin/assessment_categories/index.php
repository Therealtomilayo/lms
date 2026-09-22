<?php
$this->layout('layouts/admin', [
    'title' => 'Assessment Categories & Weights — Claret LMS',
    'headerTitle' => 'Assessment Categories & Weights',
    'headerSubtitle' => 'Define term assessment components (e.g., CA1, CA2, Exam) and assign weights that sum to 100% per academic level.'
]);

$sessionOptions = [];
foreach ($sessions as $s) {
    $sessionOptions[$s->id] = $s->name;
}

$termOptions = [];
foreach ($terms as $t) {
    $termOptions[$t->id] = $t->name;
}

$levelFilterOptions = ['' => 'All Levels (Global & Specific)'];
$levelFormOptions = ['' => 'Global Default (All Levels)'];
foreach ($academicLevels as $lvl) {
    $levelFilterOptions[$lvl->id] = $lvl->name . ' (' . $lvl->stage . ')';
    $levelFormOptions[$lvl->id] = $lvl->name . ' (' . $lvl->stage . ')';
}
?>
<div class="space-y-6">
    <!-- Context Filter Bar -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
        <form method="GET" action="/admin/assessment-categories" class="flex flex-col md:flex-row md:items-end gap-4 w-full">
            <div class="w-full md:w-64">
                <?php $this->include('components/select', [
                    'name' => 'session_id',
                    'id' => 'session_id',
                    'label' => 'Academic Session',
                    'options' => $sessionOptions,
                    'selected' => $selectedSessionId
                ]); ?>
            </div>

            <div class="w-full md:w-52">
                <?php $this->include('components/select', [
                    'name' => 'term_id',
                    'id' => 'term_id',
                    'label' => 'Term',
                    'options' => $termOptions,
                    'selected' => $selectedTermId
                ]); ?>
            </div>

            <div class="w-full md:w-64">
                <?php $this->include('components/select', [
                    'name' => 'academic_level_id',
                    'id' => 'academic_level_id',
                    'label' => 'Academic Level',
                    'options' => $levelFilterOptions,
                    'selected' => (string)($selectedAcademicLevelId ?? '')
                ]); ?>
            </div>

            <div>
                <?php $this->include('components/button', [
                    'type' => 'submit',
                    'variant' => 'secondary',
                    'label' => 'Apply Filter',
                    'class' => 'w-full md:w-auto min-h-[44px]'
                ]); ?>
            </div>
        </form>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Categories List -->
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-4">
                <div class="flex items-center justify-between flex-wrap gap-2">
                    <div>
                        <h3 class="text-lg font-bold text-slate-900">Configured Categories</h3>
                        <p class="text-xs text-slate-500 mt-0.5">
                            <?= $selectedAcademicLevelId ? 'Showing categories for selected academic level and global defaults' : 'Showing all categories across academic levels' ?>
                        </p>
                    </div>
                    <?php 
                        $totalWeight = array_sum(array_map(fn($c) => $c->weightPercentage, $categories));
                        $isComplete = abs($totalWeight - 100.0) < 0.01;
                    ?>
                    <?php $this->include('components/badge', [
                        'label' => 'Total Weight: ' . number_format($totalWeight, 1) . '% / 100%',
                        'variant' => $isComplete ? 'success' : 'danger'
                    ]); ?>
                </div>

                <?php if (empty($categories)): ?>
                    <div class="py-6 text-center shadow-none">
                        <?php $this->include('components/empty_state', [
                            'title' => 'No Assessment Categories',
                            'message' => 'No assessment categories or weights have been configured for the selected filters.'
                        ]); ?>
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto rounded-lg border border-slate-200">
                        <table class="w-full text-left text-sm border-collapse">
                            <thead>
                                <tr class="bg-slate-50 border-b border-slate-200 text-xs font-semibold text-slate-600 uppercase tracking-wider">
                                    <th class="py-3 px-4">Name</th>
                                    <th class="py-3 px-3">Academic Level</th>
                                    <th class="py-3 px-3">Weight (%)</th>
                                    <th class="py-3 px-3">Max Points</th>
                                    <th class="py-3 px-3 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 font-medium text-slate-700">
                                <?php foreach ($categories as $cat): ?>
                                    <tr class="hover:bg-slate-50/50 transition">
                                        <td class="py-3 px-4 font-bold text-slate-900"><?= e($cat->name) ?></td>
                                        <td class="py-3 px-3">
                                            <?php if (!empty($cat->academicLevelName)): ?>
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-blue-50 text-blue-700 border border-blue-200">
                                                    <?= e($cat->academicLevelName) ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-600 border border-slate-200">
                                                    Global Default
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="py-3 px-3 font-mono"><?= number_format($cat->weightPercentage, 1) ?>%</td>
                                        <td class="py-3 px-3 font-mono"><?= number_format($cat->maxPoints, 1) ?></td>
                                        <td class="py-3 px-3 text-right">
                                            <div class="inline-flex items-center gap-2">
                                                <button type="button" 
                                                        onclick="openEditCategoryModal(<?= $cat->id ?>, '<?= e(addslashes($cat->name)) ?>', <?= (float)$cat->weightPercentage ?>, <?= (float)$cat->maxPoints ?>, '<?= e((string)($cat->academicLevelId ?? '')) ?>')"
                                                        class="inline-flex items-center px-2.5 py-1.5 rounded-lg border border-slate-200 text-xs font-semibold text-slate-700 hover:bg-slate-100 hover:text-slate-900 transition">
                                                    Edit
                                                </button>
                                                <form method="POST" action="/admin/assessment-categories/<?= $cat->id ?>/delete" onsubmit="return confirm('Delete this category?');" class="inline">
                                                    <?= csrf_field() ?>
                                                    <?php $this->include('components/button', [
                                                        'type' => 'submit',
                                                        'variant' => 'danger',
                                                        'label' => 'Delete',
                                                        'class' => 'px-2.5 py-1.5 text-xs'
                                                    ]); ?>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Add Category Form -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-5 h-fit">
            <h3 class="text-lg font-bold text-slate-900">Add Category</h3>
            <form method="POST" action="/admin/assessment-categories" class="space-y-5" novalidate>
                <?= csrf_field() ?>
                <input type="hidden" name="session_id" value="<?= e((string)$selectedSessionId) ?>">
                <input type="hidden" name="term_id" value="<?= e((string)$selectedTermId) ?>">

                <?php $this->include('components/input', [
                    'name' => 'name',
                    'id' => 'category_name',
                    'label' => 'Category Name',
                    'required' => true,
                    'placeholder' => 'e.g. Continuous Assessment 1'
                ]); ?>

                <?php $this->include('components/select', [
                    'name' => 'academic_level_id',
                    'id' => 'add_academic_level_id',
                    'label' => 'Academic Level',
                    'options' => $levelFormOptions,
                    'selected' => (string)($selectedAcademicLevelId ?? ''),
                    'helper' => 'Optional. Leave as Global Default if applicable to all academic levels.'
                ]); ?>

                <?php $this->include('components/input', [
                    'name' => 'weight_percentage',
                    'id' => 'weight_percentage',
                    'label' => 'Weight Percentage (%)',
                    'type' => 'number',
                    'required' => true,
                    'placeholder' => 'e.g. 20.00',
                    'attributes' => 'step="0.01" min="0.01" max="100"'
                ]); ?>

                <?php $this->include('components/input', [
                    'name' => 'max_points',
                    'id' => 'max_points',
                    'label' => 'Max Points',
                    'type' => 'number',
                    'required' => true,
                    'value' => '100',
                    'attributes' => 'step="0.01" min="1"'
                ]); ?>

                <div class="pt-2">
                    <?php $this->include('components/button', [
                        'type' => 'submit',
                        'variant' => 'primary',
                        'label' => 'Add Category',
                        'class' => 'w-full justify-center'
                    ]); ?>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Category Modal -->
<?php ob_start(); ?>
<form id="editCategoryForm" method="POST" action="" class="space-y-4">
    <?= csrf_field() ?>

    <?php $this->include('components/input', [
        'name' => 'name',
        'id' => 'edit_category_name',
        'label' => 'Category Name',
        'required' => true,
        'placeholder' => 'e.g. Continuous Assessment 1'
    ]); ?>

    <?php $this->include('components/select', [
        'name' => 'academic_level_id',
        'id' => 'edit_category_academic_level_id',
        'label' => 'Academic Level',
        'options' => $levelFormOptions,
        'helper' => 'Set to Global Default or assign to a specific academic level.'
    ]); ?>

    <?php $this->include('components/input', [
        'name' => 'weight_percentage',
        'id' => 'edit_category_weight_percentage',
        'label' => 'Weight Percentage (%)',
        'type' => 'number',
        'required' => true,
        'attributes' => 'step="0.01" min="0.01" max="100"'
    ]); ?>

    <?php $this->include('components/input', [
        'name' => 'max_points',
        'id' => 'edit_category_max_points',
        'label' => 'Max Points',
        'type' => 'number',
        'required' => true,
        'attributes' => 'step="0.01" min="1"'
    ]); ?>
</form>
<?php $editModalBody = ob_get_clean(); ?>

<?php ob_start(); ?>
<button type="button" 
        onclick="window.LMS.hideModal('edit-category-modal')" 
        class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 transition cursor-pointer">
    Cancel
</button>
<button type="submit" 
        form="editCategoryForm" 
        class="px-4 py-2 text-sm font-semibold text-white bg-blue-600 rounded-lg hover:bg-blue-700 shadow-sm transition cursor-pointer">
    Save Changes
</button>
<?php $editModalFooter = ob_get_clean(); ?>

<?php $this->include('components/modal', [
    'id' => 'edit-category-modal',
    'title' => 'Edit Assessment Category',
    'body' => $editModalBody,
    'footer' => $editModalFooter,
    'size' => 'md'
]); ?>

<script>
function openEditCategoryModal(id, name, weight, maxPoints, levelId) {
    var form = document.getElementById('editCategoryForm');
    form.action = '/admin/assessment-categories/' + id + '/update';

    var nameInput = document.getElementById('edit_category_name');
    if (nameInput) nameInput.value = name;

    var weightInput = document.getElementById('edit_category_weight_percentage');
    if (weightInput) weightInput.value = weight;

    var maxInput = document.getElementById('edit_category_max_points');
    if (maxInput) maxInput.value = maxPoints;

    var levelSelect = document.getElementById('edit_category_academic_level_id');
    if (levelSelect) levelSelect.value = levelId || '';

    window.LMS.showModal('edit-category-modal');
}
</script>
