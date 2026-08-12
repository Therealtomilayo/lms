<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-slate-900 tracking-tight">Grading Scales & Boundaries</h2>
            <p class="text-sm text-slate-500 mt-1">Configure GPA point scales, letter boundaries, and academic remarks.</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Scale List -->
        <div class="lg:col-span-2 space-y-4">
            <?php if (empty($scales)): ?>
                <div class="bg-white rounded-2xl border border-slate-200 p-8 text-center shadow-sm">
                    <p class="text-sm text-slate-500">No grading scales defined yet.</p>
                </div>
            <?php else: ?>
                <?php foreach ($scales as $scale): ?>
                    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 space-y-4">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <h3 class="text-base font-bold text-slate-900"><?= e($scale->name) ?></h3>
                                <?php if ($scale->isDefault): ?>
                                    <span class="px-2 py-0.5 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        Default Scale
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php if ($scale->description): ?>
                            <p class="text-xs text-slate-500"><?= e($scale->description) ?></p>
                        <?php endif; ?>

                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-xs border-collapse">
                                <thead>
                                    <tr class="bg-slate-50 border-b border-slate-200 text-slate-600 font-bold uppercase">
                                        <th class="py-2 px-3">Grade</th>
                                        <th class="py-2 px-3">Score Range</th>
                                        <th class="py-2 px-3">Grade Point</th>
                                        <th class="py-2 px-3">Remark</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <?php foreach ($scale->boundaries as $b): ?>
                                        <tr>
                                            <td class="py-2 px-3 font-bold text-slate-900"><?= e($b->letter) ?></td>
                                            <td class="py-2 px-3 text-slate-600"><?= number_format($b->minScore, 1) ?>% &ndash; <?= number_format($b->maxScore, 1) ?>%</td>
                                            <td class="py-2 px-3 text-slate-600"><?= $b->gradePoint !== null ? number_format($b->gradePoint, 2) : '&mdash;' ?></td>
                                            <td class="py-2 px-3 text-slate-600"><?= e($b->remark ?? '&mdash;') ?></td>
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
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 space-y-4">
            <h3 class="text-base font-bold text-slate-900">Create Grading Scale</h3>
            <form method="POST" action="/admin/grading-scales" class="space-y-4">
                <input type="hidden" name="csrf_token" value="<?= e(\App\Middleware\CsrfMiddleware::getToken()) ?>">

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase">Scale Name</label>
                    <input type="text" name="name" required placeholder="e.g. Standard Secondary Scale" class="mt-1 w-full px-3 py-2 text-sm rounded-xl border border-slate-300 focus:ring-2 focus:ring-brand-500">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase">Description</label>
                    <textarea name="description" rows="2" placeholder="Optional notes" class="mt-1 w-full px-3 py-2 text-sm rounded-xl border border-slate-300 focus:ring-2 focus:ring-brand-500"></textarea>
                </div>

                <div class="flex items-center gap-2">
                    <input type="checkbox" name="is_default" value="1" id="is_default" class="rounded border-slate-300 text-brand-600">
                    <label for="is_default" class="text-xs font-semibold text-slate-700">Set as System Default</label>
                </div>

                <div class="pt-2 border-t border-slate-100">
                    <h4 class="text-xs font-bold text-slate-800 uppercase mb-2">Grade Boundaries</h4>
                    <div class="space-y-2 text-xs">
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
                            <div class="grid grid-cols-5 gap-1.5 items-center">
                                <input type="text" name="boundaries[<?= $i ?>][letter]" value="<?= $d['letter'] ?>" class="px-2 py-1 border rounded text-center font-bold">
                                <input type="number" step="0.01" name="boundaries[<?= $i ?>][min_score]" value="<?= $d['min'] ?>" placeholder="Min" class="px-2 py-1 border rounded text-center">
                                <input type="number" step="0.01" name="boundaries[<?= $i ?>][max_score]" value="<?= $d['max'] ?>" placeholder="Max" class="px-2 py-1 border rounded text-center">
                                <input type="number" step="0.1" name="boundaries[<?= $i ?>][grade_point]" value="<?= $d['gp'] ?>" placeholder="GP" class="px-2 py-1 border rounded text-center">
                                <input type="text" name="boundaries[<?= $i ?>][remark]" value="<?= $d['remark'] ?>" placeholder="Remark" class="px-2 py-1 border rounded">
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <button type="submit" class="w-full py-2.5 bg-brand-600 hover:bg-brand-700 text-white font-semibold text-xs rounded-xl shadow-sm transition">
                    Save Grading Scale
                </button>
            </form>
        </div>
    </div>
</div>
