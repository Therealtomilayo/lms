<?php
$this->layout('layouts/admin', [
    'title' => 'Assessment Categories & Weights — Claret LMS',
    'headerTitle' => 'Assessment Categories & Weights',
    'headerSubtitle' => 'Define term assessment components (e.g., CA1, CA2, Exam) and assign weights that sum to 100%.'
]);

$sessionOptions = [];
foreach ($sessions as $s) {
    $sessionOptions[$s->id] = $s->name;
}

$termOptions = [];
foreach ($terms as $t) {
    $termOptions[$t->id] = $t->name;
}
?>
<div class="space-y-6">
    <!-- Context Filter Bar -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
        <form method="GET" action="/admin/assessment-categories" class="flex flex-col md:flex-row md:items-end gap-4 w-full">
            <div class="w-full md:w-72">
                <?php $this->include('components/select', [
                    'name' => 'session_id',
                    'id' => 'session_id',
                    'label' => 'Academic Session',
                    'options' => $sessionOptions,
                    'selected' => $selectedSessionId
                ]); ?>
            </div>

            <div class="w-full md:w-60">
                <?php $this->include('components/select', [
                    'name' => 'term_id',
                    'id' => 'term_id',
                    'label' => 'Term',
                    'options' => $termOptions,
                    'selected' => $selectedTermId
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
                    <h3 class="text-lg font-bold text-slate-900">Configured Categories</h3>
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
                            'message' => 'No assessment categories or weights have been configured for the selected session and term.'
                        ]); ?>
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto rounded-lg border border-slate-200">
                        <table class="w-full text-left text-sm border-collapse">
                            <thead>
                                <tr class="bg-slate-50 border-b border-slate-200 text-xs font-semibold text-slate-600 uppercase tracking-wider">
                                    <th class="py-3 px-4">Name</th>
                                    <th class="py-3 px-3">Weight (%)</th>
                                    <th class="py-3 px-3">Max Points</th>
                                    <th class="py-3 px-3 text-right">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 font-medium text-slate-700">
                                <?php foreach ($categories as $cat): ?>
                                    <tr class="hover:bg-slate-50/50 transition">
                                        <td class="py-3 px-4 font-bold text-slate-900"><?= e($cat->name) ?></td>
                                        <td class="py-3 px-3 font-mono"><?= number_format($cat->weightPercentage, 1) ?>%</td>
                                        <td class="py-3 px-3 font-mono"><?= number_format($cat->maxPoints, 1) ?></td>
                                        <td class="py-3 px-3 text-right">
                                            <form method="POST" action="/admin/assessment-categories/<?= $cat->id ?>/delete" onsubmit="return confirm('Delete this category?');" class="inline">
                                                <?= csrf_field() ?>
                                                <?php $this->include('components/button', [
                                                    'type' => 'submit',
                                                    'variant' => 'danger',
                                                    'label' => 'Delete',
                                                    'class' => 'px-3 py-1.5 text-xs'
                                                ]); ?>
                                            </form>
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
