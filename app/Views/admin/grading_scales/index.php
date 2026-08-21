<?php
$this->layout('layouts/admin', [
    'title' => 'Grading Scales & Boundaries — Claret LMS',
    'headerTitle' => 'Grading Scales & Boundaries',
    'headerSubtitle' => 'Configure GPA point scales, letter boundaries, and academic remarks.'
]);
?>
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
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-4">
                    <div class="flex items-center justify-between flex-wrap gap-2">
                        <div class="flex items-center gap-2 flex-wrap">
                            <h3 class="text-lg font-bold text-slate-900"><?= e($scale->name) ?></h3>
                            <?php if ($scale->isDefault): ?>
                                <?php $this->include('components/badge', ['label' => 'Default Scale', 'variant' => 'success']); ?>
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
                                    <th class="py-3 px-4">Grade</th>
                                    <th class="py-3 px-4">Score Range</th>
                                    <th class="py-3 px-4">Grade Point</th>
                                    <th class="py-3 px-4">Remark</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 font-medium text-slate-700">
                                <?php foreach ($scale->boundaries as $b): ?>
                                    <tr class="hover:bg-slate-50/50 transition">
                                        <td class="py-3 px-4 font-bold text-slate-900"><?= e($b->letter) ?></td>
                                        <td class="py-3 px-4 text-slate-600 font-normal font-mono"><?= number_format($b->minScore, 1) ?>% &ndash; <?= number_format($b->maxScore, 1) ?>%</td>
                                        <td class="py-3 px-4 text-slate-600 font-bold font-mono"><?= $b->gradePoint !== null ? number_format($b->gradePoint, 2) : '&mdash;' ?></td>
                                        <td class="py-3 px-4 text-slate-600 font-normal"><?= e($b->remark ?? '&mdash;') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Add Scale Form -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-5 h-fit">
        <h3 class="text-lg font-bold text-slate-900">Create Grading Scale</h3>
        <form method="POST" action="/admin/grading-scales" class="space-y-5" novalidate>
            <?= csrf_field() ?>

            <?php $this->include('components/input', [
                'name' => 'name',
                'id' => 'scale_name',
                'label' => 'Scale Name',
                'required' => true,
                'placeholder' => 'e.g. Standard Secondary Scale'
            ]); ?>

            <div class="form-group flex flex-col gap-1.5 w-full">
                <label for="scale_description" class="text-sm font-semibold text-slate-700">Description</label>
                <textarea name="description" id="scale_description" rows="2" placeholder="Optional notes" 
                          class="w-full text-sm px-3.5 py-2.5 rounded-lg border border-slate-300 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 shadow-xs transition duration-200"></textarea>
            </div>

            <div class="flex items-center gap-2 py-1">
                <input type="checkbox" name="is_default" value="1" id="is_default" class="rounded border-slate-300 text-brand-600 focus:ring-brand-500 h-4 w-4">
                <label for="is_default" class="text-sm font-semibold text-slate-700 cursor-pointer">Set as System Default</label>
            </div>

            <div class="pt-4 border-t border-slate-200">
                <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-3">Grade Boundaries</h4>
                
                <!-- Column Titles -->
                <div class="grid grid-cols-5 gap-1.5 mb-2 text-[10px] font-bold text-slate-500 uppercase text-center">
                    <div>Grade</div>
                    <div>Min %</div>
                    <div>Max %</div>
                    <div>GP</div>
                    <div>Remark</div>
                </div>

                <div class="space-y-2">
                    <?php 
                    $defaults = [
                        ['letter' => 'A', 'min' => 70, 'max' => 100, 'gp' => 5.0, 'remark' => 'Excellent'],
                        ['letter' => 'B', 'min' => 60, 'max' => 69.99, 'gp' => 4.0, 'remark' => 'Very Good'],
                        ['letter' => 'C', 'min' => 50, 'max' => 59.99, 'gp' => 3.0, 'remark' => 'Credit'],
                        ['letter' => 'D', 'min' => 45, 'max' => 49.99, 'gp' => 2.0, 'remark' => 'Pass'],
                        ['letter' => 'E', 'min' => 40, 'max' => 44.99, 'gp' => 1.0, 'remark' => 'Fair'],
                        ['letter' => 'F', 'min' => 0, 'max' => 39.99, 'gp' => 0.0, 'remark' => 'Fail'],
                    ];
                    foreach ($defaults as $i => $d): ?>
                        <div class="grid grid-cols-5 gap-1.5 items-center text-xs">
                            <input type="text" name="boundaries[<?= $i ?>][letter]" value="<?= $d['letter'] ?>" class="px-1.5 py-1.5 bg-white border border-slate-300 rounded text-center font-bold focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500">
                            <input type="number" step="0.01" name="boundaries[<?= $i ?>][min_score]" value="<?= $d['min'] ?>" placeholder="Min" class="px-1.5 py-1.5 bg-white border border-slate-300 rounded text-center focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500">
                            <input type="number" step="0.01" name="boundaries[<?= $i ?>][max_score]" value="<?= $d['max'] ?>" placeholder="Max" class="px-1.5 py-1.5 bg-white border border-slate-300 rounded text-center focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500">
                            <input type="number" step="0.1" name="boundaries[<?= $i ?>][grade_point]" value="<?= $d['gp'] ?>" placeholder="GP" class="px-1.5 py-1.5 bg-white border border-slate-300 rounded text-center focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500">
                            <input type="text" name="boundaries[<?= $i ?>][remark]" value="<?= $d['remark'] ?>" placeholder="Remark" class="px-1.5 py-1.5 bg-white border border-slate-300 rounded focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500">
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
