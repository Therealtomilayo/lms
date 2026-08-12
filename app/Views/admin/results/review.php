<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-slate-900 tracking-tight">Result Review & Publication</h2>
            <p class="text-sm text-slate-500 mt-1">Review computed subject grades, calculate class rankings, lock terms, and publish report cards.</p>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4">
        <form method="GET" action="/admin/results/review" class="flex flex-wrap items-center gap-4">
            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase">Academic Term</label>
                <select name="term_id" class="mt-1 px-3 py-1.5 text-sm rounded-xl border border-slate-300">
                    <?php foreach ($terms as $t): ?>
                        <option value="<?= $t->id ?>" <?= $t->id == $selectedTermId ? 'selected' : '' ?>>
                            <?= e($t->name) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase">Class</label>
                <select name="class_id" class="mt-1 px-3 py-1.5 text-sm rounded-xl border border-slate-300">
                    <option value="">-- Select Class --</option>
                    <?php foreach ($classes as $c): ?>
                        <option value="<?= $c->id ?>" <?= $c->id == $selectedClassId ? 'selected' : '' ?>>
                            <?= e($c->name) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="pt-5">
                <button type="submit" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded-xl transition">
                    View Class Results
                </button>
            </div>
        </form>
    </div>

    <?php if ($selectedTermId > 0 && $selectedClassId > 0): ?>
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 space-y-6">
            <!-- Header & Publication Controls -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-slate-100">
                <div class="flex items-center gap-3">
                    <span class="text-sm font-bold text-slate-700">Publication Status:</span>
                    <?php if ($isPublished): ?>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                            Published to Students & Guardians
                        </span>
                    <?php else: ?>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-amber-50 text-amber-700 border border-amber-200">
                            Unpublished (Draft Review)
                        </span>
                    <?php endif; ?>
                </div>

                <div class="flex items-center gap-3">
                    <form method="POST" action="/admin/results/compute">
                        <input type="hidden" name="csrf_token" value="<?= e(\App\Middleware\CsrfMiddleware::getToken()) ?>">
                        <input type="hidden" name="term_id" value="<?= e((string)$selectedTermId) ?>">
                        <input type="hidden" name="class_id" value="<?= e((string)$selectedClassId) ?>">
                        <button type="submit" class="px-4 py-2 bg-slate-800 hover:bg-slate-900 text-white text-xs font-semibold rounded-xl shadow-sm transition">
                            Recompute & Rank Class
                        </button>
                    </form>

                    <?php if (!$isPublished): ?>
                        <form method="POST" action="/admin/results/publish">
                            <input type="hidden" name="csrf_token" value="<?= e(\App\Middleware\CsrfMiddleware::getToken()) ?>">
                            <input type="hidden" name="term_id" value="<?= e((string)$selectedTermId) ?>">
                            <input type="hidden" name="class_id" value="<?= e((string)$selectedClassId) ?>">
                            <button type="submit" class="px-4 py-2 bg-brand-600 hover:bg-brand-700 text-white text-xs font-semibold rounded-xl shadow-sm transition">
                                Publish Results
                            </button>
                        </form>
                    <?php else: ?>
                        <form method="POST" action="/admin/results/unpublish">
                            <input type="hidden" name="csrf_token" value="<?= e(\App\Middleware\CsrfMiddleware::getToken()) ?>">
                            <input type="hidden" name="term_id" value="<?= e((string)$selectedTermId) ?>">
                            <input type="hidden" name="class_id" value="<?= e((string)$selectedClassId) ?>">
                            <input type="hidden" name="reason" value="Administrative Review">
                            <button type="submit" class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white text-xs font-semibold rounded-xl shadow-sm transition">
                                Unpublish Results
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Student Summary Ranking Table -->
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm border-collapse">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200 text-xs font-bold text-slate-700 uppercase">
                            <th class="py-3 px-3 text-center">Rank</th>
                            <th class="py-3 px-4">Student</th>
                            <th class="py-3 px-3">Admission No</th>
                            <th class="py-3 px-3 text-center">Total Score</th>
                            <th class="py-3 px-3 text-center">Average (%)</th>
                            <th class="py-3 px-3 text-center">GPA</th>
                            <th class="py-3 px-3 text-right">Report Card</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-medium">
                        <?php if (empty($summaries)): ?>
                            <tr>
                                <td colspan="7" class="py-8 text-center text-slate-400 text-xs">
                                    No summaries computed yet. Click "Recompute & Rank Class" to generate rankings.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($summaries as $s): ?>
                                <tr>
                                    <td class="py-3 px-3 text-center font-bold text-brand-600">
                                        #<?= e((string)$s->rankInClass) ?>
                                    </td>
                                    <td class="py-3 px-4 font-bold text-slate-900">
                                        <?= e($s->student?->user?->name ?? 'Student') ?>
                                    </td>
                                    <td class="py-3 px-3 text-xs text-slate-500">
                                        <?= e($s->student?->admissionNumber ?? '') ?>
                                    </td>
                                    <td class="py-3 px-3 text-center font-semibold">
                                        <?= number_format((float)$s->totalScore, 2) ?>
                                    </td>
                                    <td class="py-3 px-3 text-center font-bold text-slate-900">
                                        <?= number_format((float)$s->averageScore, 2) ?>%
                                    </td>
                                    <td class="py-3 px-3 text-center font-semibold">
                                        <?= $s->gpa !== null ? number_format((float)$s->gpa, 2) : '&mdash;' ?>
                                    </td>
                                    <td class="py-3 px-3 text-right">
                                        <a href="/admin/reports/student/<?= $s->studentId ?>/<?= $selectedTermId ?>.pdf" target="_blank" class="inline-flex items-center gap-1 text-xs text-brand-600 hover:text-brand-800 font-semibold">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                            PDF Report
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>
