<?php
$this->layout('layouts/admin', [
    'title' => 'Assessment Categories & Weights — Claret LMS',
    'headerTitle' => 'Assessment Categories & Weights',
    'headerSubtitle' => 'Configure continuous assessment components (CA, Unit Test, SPAT, Exam) grouped by educational stage with 100% weight validation and automatic term carryover.'
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
$levelFormOptions = ['' => 'All Levels in Stage (Stage Default)'];
foreach ($academicLevels as $lvl) {
    $levelFilterOptions[$lvl->id] = $lvl->name . ' (' . strtoupper($lvl->stage) . ')';
    $levelFormOptions[$lvl->id] = $lvl->name . ' (' . strtoupper($lvl->stage) . ')';
}

$stageLabels = [
    'eyfs' => 'Early Years Foundation (EYFS)',
    'primary' => 'Primary School',
    'junior_secondary' => 'Junior Secondary (JSS)',
    'senior_secondary' => 'Senior Secondary (SSS)',
    'common' => 'Global / Common Defaults',
];

$stageDescriptions = [
    'eyfs' => 'Crèche, Nursery 1, Nursery 2 — Early childhood developmental assessments',
    'primary' => 'Primary 1 to Primary 6 — Class Activity, Unit Test, SPAT, Home Fun, Term Test',
    'junior_secondary' => 'JSS 1 to JSS 3 — EUA (20%), TEST (5%), SPAT (5%), NOTE (5%), Assignment (5%), Exam (60%)',
    'senior_secondary' => 'SSS 1 to SSS 3 — EUA (20%), TEST (5%), SPAT (5%), NOTE (5%), Assignment (5%), Exam (60%)',
    'common' => 'Default assessment breakdown applied when no stage-specific override exists',
];

$activeTab = $selectedStage ?: 'all';
?>
<div class="space-y-6">

    <!-- Institutional Notice: Term Auto-Repeat & Stage Separation -->
    <div class="bg-white rounded-2xl p-5 sm:p-6 shadow-xs border border-slate-200 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div class="space-y-1.5">
            <div class="inline-flex items-center gap-2 px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-800 border border-emerald-200 uppercase tracking-wider">
                <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                <span>Automatic Term Inheritance Active</span>
            </div>
            <h2 class="text-lg sm:text-xl font-black text-slate-900 tracking-tight">Stage-Grouped Assessment Weighting</h2>
            <p class="text-xs sm:text-sm text-slate-600 leading-relaxed max-w-2xl">
                Assessment categories are cleanly separated per educational stage (EYFS, Primary, JSS, SSS) and automatically repeat every term. Any custom updates in a term will override the defaults without affecting other terms.
            </p>
        </div>
        <div class="flex items-center gap-2 flex-shrink-0">
            <a href="#add-category-card" class="px-4 py-2.5 rounded-xl text-xs font-bold bg-brand-600 text-white hover:bg-brand-700 transition shadow-xs flex items-center gap-1.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                <span>Add Category</span>
            </a>
        </div>
    </div>

    <!-- Context Filter Bar -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-5">
        <form method="GET" action="/admin/assessment-categories" class="flex flex-col md:flex-row md:items-end gap-4 w-full">
            <div class="w-full md:w-56">
                <?php $this->include('components/select', [
                    'name' => 'session_id',
                    'id' => 'session_id',
                    'label' => 'Academic Session',
                    'options' => $sessionOptions,
                    'selected' => $selectedSessionId
                ]); ?>
            </div>

            <div class="w-full md:w-48">
                <?php $this->include('components/select', [
                    'name' => 'term_id',
                    'id' => 'term_id',
                    'label' => 'Term',
                    'options' => $termOptions,
                    'selected' => $selectedTermId
                ]); ?>
            </div>

            <div class="w-full md:w-56">
                <label for="filter_stage" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Educational Stage</label>
                <select name="stage" id="filter_stage" class="w-full px-3 py-2 text-sm rounded-xl border border-slate-300 bg-white focus:ring-brand-500 focus:border-brand-500 font-medium">
                    <option value="" <?= empty($selectedStage) ? 'selected' : '' ?>>All Educational Stages</option>
                    <option value="eyfs" <?= $selectedStage === 'eyfs' ? 'selected' : '' ?>>Early Years (EYFS)</option>
                    <option value="primary" <?= $selectedStage === 'primary' ? 'selected' : '' ?>>Primary School</option>
                    <option value="junior_secondary" <?= $selectedStage === 'junior_secondary' ? 'selected' : '' ?>>Junior Secondary (JSS)</option>
                    <option value="senior_secondary" <?= $selectedStage === 'senior_secondary' ? 'selected' : '' ?>>Senior Secondary (SSS)</option>
                </select>
            </div>

            <div class="flex-shrink-0">
                <?php $this->include('components/button', [
                    'type' => 'submit',
                    'variant' => 'primary',
                    'label' => 'Filter Categories',
                    'class' => 'w-full md:w-auto min-h-[44px]'
                ]); ?>
            </div>
        </form>
    </div>

    <!-- Educational Stage Weight Status Dashboard Gauges -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <?php 
        $stageCardConfigs = [
            'eyfs' => [
                'title' => 'EYFS Stage', 
                'svg' => '<svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
                'color' => 'amber'
            ],
            'primary' => [
                'title' => 'Primary School', 
                'svg' => '<svg class="w-5 h-5 text-sky-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>',
                'color' => 'sky'
            ],
            'junior_secondary' => [
                'title' => 'Junior Secondary (JSS)', 
                'svg' => '<svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>',
                'color' => 'indigo'
            ],
            'senior_secondary' => [
                'title' => 'Senior Secondary (SSS)', 
                'svg' => '<svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14zm-4 6v-7.5l4-2.222"/></svg>',
                'color' => 'purple'
            ],
        ];

        foreach ($stageCardConfigs as $stKey => $conf):
            $stWeight = $stageWeights[$stKey] ?? 0.0;
            $isComplete = abs($stWeight - 100.0) < 0.01;
            $catCount = count($categoriesByStage[$stKey] ?? []);
        ?>
            <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-xs flex flex-col justify-between transition hover:border-slate-300">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-black uppercase tracking-wider text-slate-700"><?= $conf['title'] ?></span>
                    <div class="w-8 h-8 rounded-lg bg-slate-50 flex items-center justify-center border border-slate-100">
                        <?= $conf['svg'] ?>
                    </div>
                </div>
                <div class="mt-3">
                    <div class="flex items-baseline justify-between">
                        <span class="text-2xl font-black font-mono <?= $isComplete ? 'text-emerald-600' : ($stWeight > 100 ? 'text-rose-600' : 'text-slate-900') ?>">
                            <?= number_format($stWeight, 1) ?>%
                        </span>
                        <span class="text-xs font-semibold text-slate-400">/ 100% Target</span>
                    </div>
                    <!-- Progress Bar -->
                    <div class="w-full bg-slate-100 rounded-full h-2 mt-2 overflow-hidden">
                        <div class="h-2 rounded-full transition-all duration-500 <?= $isComplete ? 'bg-emerald-500' : ($stWeight > 100 ? 'bg-rose-500' : 'bg-brand-600') ?>" 
                             style="width: <?= min(100, $stWeight) ?>%"></div>
                    </div>
                </div>
                <div class="mt-3 pt-2 border-t border-slate-100 flex items-center justify-between text-[11px] text-slate-500">
                    <span><?= $catCount ?> components</span>
                    <?php if ($isComplete): ?>
                        <span class="font-bold text-emerald-600 inline-flex items-center gap-1">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            Complete
                        </span>
                    <?php else: ?>
                        <span class="font-bold text-amber-600">Pending 100%</span>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Main Content Layout -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Stage Grouped Categories Sections (2 cols) -->
        <div class="lg:col-span-2 space-y-6">

            <?php
            $displayStages = !empty($selectedStage) ? [$selectedStage] : ['eyfs', 'primary', 'junior_secondary', 'senior_secondary', 'common'];
            
            foreach ($displayStages as $stKey):
                $stCats = $categoriesByStage[$stKey] ?? [];
                if (empty($stCats) && !empty($selectedStage) && $selectedStage !== $stKey) continue;
                $stWeight = $stageWeights[$stKey] ?? 0.0;
                $isComplete = abs($stWeight - 100.0) < 0.01;
            ?>
                <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden" id="stage-section-<?= $stKey ?>">
                    
                    <!-- Section Header -->
                    <div class="px-6 py-4 bg-slate-50/80 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="text-base font-bold text-slate-900"><?= $stageLabels[$stKey] ?? ucfirst($stKey) ?></h3>
                                <span class="px-2 py-0.5 rounded-full text-[11px] font-black uppercase <?= $isComplete ? 'bg-emerald-100 text-emerald-800 border border-emerald-200' : 'bg-amber-100 text-amber-800 border border-amber-200' ?>">
                                    <?= number_format($stWeight, 1) ?>% / 100%
                                </span>
                            </div>
                            <p class="text-xs text-slate-500 mt-0.5"><?= $stageDescriptions[$stKey] ?? '' ?></p>
                        </div>
                        <span class="text-xs font-mono text-slate-400 font-semibold">
                            <?= count($stCats) ?> category components
                        </span>
                    </div>

                    <!-- Categories Table -->
                    <?php if (empty($stCats)): ?>
                        <div class="p-8 text-center text-slate-400">
                            <p class="text-sm">No assessment categories configured specifically for this stage yet.</p>
                            <p class="text-xs text-slate-400 mt-1">Default term weights or global categories will be inherited automatically.</p>
                        </div>
                    <?php else: ?>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-sm border-collapse divide-y divide-slate-100">
                                <thead>
                                    <tr class="bg-slate-50/40 text-[11px] font-bold text-slate-600 uppercase tracking-wider">
                                        <th class="py-3 px-5">Component Name</th>
                                        <th class="py-3 px-3">Scope / Level</th>
                                        <th class="py-3 px-3 text-center">Weight (%)</th>
                                        <th class="py-3 px-3 text-center">Max Points</th>
                                        <th class="py-3 px-5 text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 font-medium text-slate-700">
                                    <?php foreach ($stCats as $cat): ?>
                                        <tr class="hover:bg-slate-50/60 transition">
                                            <td class="py-3.5 px-5 font-bold text-slate-900">
                                                <div class="flex items-center gap-2">
                                                    <span><?= e($cat->name) ?></span>
                                                    <?php if (stripos($cat->name, 'exam') !== false || stripos($cat->name, 'eta') !== false || stripos($cat->name, 'esa') !== false || stripos($cat->name, 'term test') !== false): ?>
                                                        <span class="px-1.5 py-0.5 rounded text-[9px] font-black uppercase bg-purple-100 text-purple-800">Terminal Exam</span>
                                                    <?php else: ?>
                                                        <span class="px-1.5 py-0.5 rounded text-[9px] font-black uppercase bg-blue-100 text-blue-800">Continuous Assessment</span>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td class="py-3.5 px-3">
                                                <?php if (!empty($cat->academicLevelName)): ?>
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-indigo-50 text-indigo-700 border border-indigo-200">
                                                        <?= e($cat->academicLevelName) ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-700 border border-slate-200">
                                                        Stage Default (All <?= strtoupper($stKey) ?>)
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="py-3.5 px-3 font-mono font-bold text-center text-slate-900">
                                                <?= number_format($cat->weightPercentage, 1) ?>%
                                            </td>
                                            <td class="py-3.5 px-3 font-mono text-center text-slate-600">
                                                <?= number_format($cat->maxPoints, 1) ?>
                                            </td>
                                            <td class="py-3.5 px-5 text-right">
                                                <div class="inline-flex items-center gap-2">
                                                    <button type="button" 
                                                            onclick="openEditCategoryModal(<?= $cat->id ?>, '<?= e(addslashes($cat->name)) ?>', <?= (float)$cat->weightPercentage ?>, <?= (float)$cat->maxPoints ?>, '<?= e((string)($cat->academicLevelId ?? '')) ?>', '<?= e((string)($cat->stage ?? '')) ?>')"
                                                            class="inline-flex items-center px-2.5 py-1.5 rounded-lg border border-slate-200 text-xs font-semibold text-slate-700 hover:bg-slate-100 transition cursor-pointer">
                                                        Edit
                                                    </button>
                                                    <form method="POST" action="/admin/assessment-categories/<?= $cat->id ?>/delete" onsubmit="return confirm('Delete this category?');" class="inline">
                                                        <?= csrf_field() ?>
                                                        <button type="submit" class="inline-flex items-center px-2.5 py-1.5 rounded-lg border border-rose-200 text-xs font-semibold text-rose-600 hover:bg-rose-50 transition cursor-pointer">
                                                            Delete
                                                        </button>
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
            <?php endforeach; ?>

        </div>

        <!-- Add Category Form Card (1 col) -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6 space-y-5 h-fit sticky top-6" id="add-category-card">
            <div class="border-b border-slate-100 pb-3">
                <h3 class="text-base font-bold text-slate-900">Add Assessment Category</h3>
                <p class="text-xs text-slate-500 mt-0.5">Assign category to an educational stage with percentage weight.</p>
            </div>

            <form method="POST" action="/admin/assessment-categories" class="space-y-4" novalidate>
                <?= csrf_field() ?>
                <input type="hidden" name="session_id" value="<?= e((string)$selectedSessionId) ?>">
                <input type="hidden" name="term_id" value="<?= e((string)$selectedTermId) ?>">

                <div>
                    <label for="category_stage" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Educational Stage <span class="text-rose-500">*</span></label>
                    <select name="stage" id="category_stage" class="w-full px-3 py-2 text-sm rounded-xl border border-slate-300 bg-white focus:ring-brand-500 focus:border-brand-500 font-semibold text-slate-800">
                        <option value="">— Select Target Stage —</option>
                        <option value="eyfs" <?= $selectedStage === 'eyfs' ? 'selected' : '' ?>>Early Years Foundation (EYFS)</option>
                        <option value="primary" <?= $selectedStage === 'primary' ? 'selected' : '' ?>>Primary School</option>
                        <option value="junior_secondary" <?= ($selectedStage === 'junior_secondary' || empty($selectedStage)) ? 'selected' : '' ?>>Junior Secondary (JSS)</option>
                        <option value="senior_secondary" <?= $selectedStage === 'senior_secondary' ? 'selected' : '' ?>>Senior Secondary (SSS)</option>
                    </select>
                    <span class="text-[11px] text-slate-400 mt-1 block">Separates config so EYFS, Primary & High School don't conflict.</span>
                </div>

                <?php $this->include('components/input', [
                    'name' => 'name',
                    'id' => 'category_name',
                    'label' => 'Component Name',
                    'required' => true,
                    'placeholder' => 'e.g. EUA, TEST, SPAT, Exam'
                ]); ?>

                <?php $this->include('components/select', [
                    'name' => 'academic_level_id',
                    'id' => 'add_academic_level_id',
                    'label' => 'Academic Level (Optional Override)',
                    'options' => $levelFormOptions,
                    'selected' => (string)($selectedAcademicLevelId ?? ''),
                    'helper' => 'Leave as Stage Default to apply to all levels in the chosen stage.'
                ]); ?>

                <div class="grid grid-cols-2 gap-3">
                    <?php $this->include('components/input', [
                        'name' => 'weight_percentage',
                        'id' => 'weight_percentage',
                        'label' => 'Weight (%)',
                        'type' => 'number',
                        'required' => true,
                        'placeholder' => '20.00',
                        'attributes' => 'step="0.01" min="0.01" max="100"'
                    ]); ?>

                    <?php $this->include('components/input', [
                        'name' => 'max_points',
                        'id' => 'max_points',
                        'label' => 'Max Points',
                        'type' => 'number',
                        'required' => true,
                        'value' => '20',
                        'attributes' => 'step="0.01" min="1"'
                    ]); ?>
                </div>

                <div class="pt-2">
                    <button type="submit" class="w-full py-2.5 px-4 rounded-xl text-xs font-bold bg-brand-700 hover:bg-brand-800 text-white transition flex items-center justify-center gap-2 shadow-xs cursor-pointer">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        <span>Add Stage Category</span>
                    </button>
                </div>
            </form>
        </div>

    </div>

</div>

<!-- Edit Category Modal -->
<?php ob_start(); ?>
<form id="editCategoryForm" method="POST" action="" class="space-y-4">
    <?= csrf_field() ?>

    <div>
        <label for="edit_category_stage" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Educational Stage</label>
        <select name="stage" id="edit_category_stage" class="w-full px-3 py-2 text-sm rounded-xl border border-slate-300 bg-white focus:ring-brand-500 focus:border-brand-500 font-semibold text-slate-800">
            <option value="">Global / Common</option>
            <option value="eyfs">Early Years Foundation (EYFS)</option>
            <option value="primary">Primary School</option>
            <option value="junior_secondary">Junior Secondary (JSS)</option>
            <option value="senior_secondary">Senior Secondary (SSS)</option>
        </select>
    </div>

    <?php $this->include('components/input', [
        'name' => 'name',
        'id' => 'edit_category_name',
        'label' => 'Category Name',
        'required' => true,
        'placeholder' => 'e.g. EUA, TEST, SPAT, Exam'
    ]); ?>

    <?php $this->include('components/select', [
        'name' => 'academic_level_id',
        'id' => 'edit_category_academic_level_id',
        'label' => 'Academic Level Override',
        'options' => $levelFormOptions,
        'helper' => 'Optional override for a specific class level.'
    ]); ?>

    <div class="grid grid-cols-2 gap-3">
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
    </div>
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
        class="px-4 py-2 text-sm font-semibold text-white bg-brand-700 hover:bg-brand-800 rounded-lg shadow-sm transition cursor-pointer">
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
function openEditCategoryModal(id, name, weight, maxPoints, levelId, stage) {
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

    var stageSelect = document.getElementById('edit_category_stage');
    if (stageSelect) stageSelect.value = stage || '';

    window.LMS.showModal('edit-category-modal');
}
</script>
