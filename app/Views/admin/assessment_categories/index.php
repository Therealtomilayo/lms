<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-slate-900 tracking-tight">Assessment Categories & Weights</h2>
            <p class="text-sm text-slate-500 mt-1">Define term assessment components (e.g., CA1, CA2, Exam) and assign weights that sum to 100%.</p>
        </div>
    </div>

    <!-- Context Filter Bar -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4">
        <form method="GET" action="/admin/assessment-categories" class="flex flex-wrap items-center gap-4">
            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase">Academic Session</label>
                <select name="session_id" class="mt-1 px-3 py-1.5 text-sm rounded-xl border border-slate-300">
                    <?php foreach ($sessions as $s): ?>
                        <option value="<?= $s->id ?>" <?= $s->id == $selectedSessionId ? 'selected' : '' ?>>
                            <?= e($s->name) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase">Term</label>
                <select name="term_id" class="mt-1 px-3 py-1.5 text-sm rounded-xl border border-slate-300">
                    <?php foreach ($terms as $t): ?>
                        <option value="<?= $t->id ?>" <?= $t->id == $selectedTermId ? 'selected' : '' ?>>
                            <?= e($t->name) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="pt-5">
                <button type="submit" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded-xl transition">
                    Filter
                </button>
            </div>
        </form>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Categories List -->
        <div class="lg:col-span-2 space-y-4">
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 space-y-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-base font-bold text-slate-900">Configured Categories</h3>
                    <?php 
                        $totalWeight = array_sum(array_map(fn($c) => $c->weightPercentage, $categories));
                        $isComplete = abs($totalWeight - 100.0) < 0.01;
                    ?>
                    <span class="px-3 py-1 rounded-full text-xs font-bold <?= $isComplete ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-rose-50 text-rose-700 border border-rose-200' ?>">
                        Total Weight: <?= number_format($totalWeight, 1) ?>% / 100%
                    </span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm border-collapse">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-200 text-xs font-bold text-slate-600 uppercase">
                                <th class="py-3 px-4">Name</th>
                                <th class="py-3 px-3">Weight (%)</th>
                                <th class="py-3 px-3">Max Points</th>
                                <th class="py-3 px-3 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 font-medium">
                            <?php if (empty($categories)): ?>
                                <tr>
                                    <td colspan="4" class="py-6 text-center text-slate-400 text-xs">
                                        No categories defined for this session and term.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($categories as $cat): ?>
                                    <tr>
                                        <td class="py-3 px-4 font-bold text-slate-900"><?= e($cat->name) ?></td>
                                        <td class="py-3 px-3"><?= number_format($cat->weightPercentage, 1) ?>%</td>
                                        <td class="py-3 px-3"><?= number_format($cat->maxPoints, 1) ?></td>
                                        <td class="py-3 px-3 text-right">
                                            <form method="POST" action="/admin/assessment-categories/<?= $cat->id ?>/delete" onsubmit="return confirm('Delete this category?');" class="inline">
                                                <input type="hidden" name="csrf_token" value="<?= e(\App\Middleware\CsrfMiddleware::getToken()) ?>">
                                                <button type="submit" class="text-xs text-rose-600 hover:text-rose-800 font-semibold">Delete</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Add Category Form -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 space-y-4">
            <h3 class="text-base font-bold text-slate-900">Add Category</h3>
            <form method="POST" action="/admin/assessment-categories" class="space-y-4">
                <input type="hidden" name="csrf_token" value="<?= e(\App\Middleware\CsrfMiddleware::getToken()) ?>">
                <input type="hidden" name="session_id" value="<?= e((string)$selectedSessionId) ?>">
                <input type="hidden" name="term_id" value="<?= e((string)$selectedTermId) ?>">

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase">Category Name</label>
                    <input type="text" name="name" required placeholder="e.g. Continuous Assessment 1" class="mt-1 w-full px-3 py-2 text-sm rounded-xl border border-slate-300 focus:ring-2 focus:ring-brand-500">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase">Weight Percentage (%)</label>
                    <input type="number" step="0.01" min="0.01" max="100" name="weight_percentage" required placeholder="e.g. 20.00" class="mt-1 w-full px-3 py-2 text-sm rounded-xl border border-slate-300 focus:ring-2 focus:ring-brand-500">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase">Max Points</label>
                    <input type="number" step="0.01" min="1" name="max_points" value="100" required class="mt-1 w-full px-3 py-2 text-sm rounded-xl border border-slate-300 focus:ring-2 focus:ring-brand-500">
                </div>

                <button type="submit" class="w-full py-2.5 bg-brand-600 hover:bg-brand-700 text-white font-semibold text-xs rounded-xl shadow-sm transition">
                    Add Category
                </button>
            </form>
        </div>
    </div>
</div>
