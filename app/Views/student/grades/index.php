<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-slate-900 tracking-tight">Academic Grades & Performance</h2>
            <p class="text-sm text-slate-500 mt-1">Review your published continuous assessments, examination results, and official term grade cards.</p>
        </div>
        <?php if ($isPublished && !empty($subjectResults)): ?>
            <div>
                <a href="/student/grades/report-card?term_id=<?= $selectedTermId ?>" target="_blank" class="inline-flex items-center gap-2 px-4 py-2.5 bg-brand-600 hover:bg-brand-700 text-white text-sm font-semibold rounded-xl shadow-sm transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    View Official Report Card
                </a>
            </div>
        <?php endif; ?>
    </div>

    <!-- Term Filter -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4">
        <form method="GET" action="/student/grades" class="flex items-center gap-4">
            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase">Select Academic Term</label>
                <select name="term_id" onchange="this.form.submit()" class="mt-1 px-3 py-1.5 text-sm rounded-xl border border-slate-300">
                    <?php foreach ($terms as $t): ?>
                        <option value="<?= $t->id ?>" <?= $t->id == $selectedTermId ? 'selected' : '' ?>>
                            <?= e($t->name) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </form>
    </div>

    <?php if (!$isPublished): ?>
        <div class="bg-amber-50 border border-amber-200 rounded-2xl p-8 text-center text-amber-800">
            <div class="w-12 h-12 rounded-full bg-amber-100 text-amber-600 mx-auto flex items-center justify-center mb-3">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <h3 class="text-base font-bold">Results Processing</h3>
            <p class="text-xs mt-1 text-amber-700 max-w-md mx-auto">Official grades for this academic term have not yet been published by administration. Please check back later.</p>
        </div>
    <?php else: ?>
        <!-- Term Performance Summary Cards -->
        <?php if ($summary): ?>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
                    <span class="text-xs font-bold text-slate-400 uppercase">Class Position</span>
                    <h3 class="text-2xl font-extrabold text-brand-600 mt-1">
                        #<?= e((string)$summary->rankInClass) ?>
                    </h3>
                </div>
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
                    <span class="text-xs font-bold text-slate-400 uppercase">Average Score</span>
                    <h3 class="text-2xl font-extrabold text-slate-900 mt-1">
                        <?= number_format((float)$summary->averageScore, 2) ?>%
                    </h3>
                </div>
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
                    <span class="text-xs font-bold text-slate-400 uppercase">Grade Point Average (GPA)</span>
                    <h3 class="text-2xl font-extrabold text-slate-900 mt-1">
                        <?= $summary->gpa !== null ? number_format((float)$summary->gpa, 2) : 'N/A' ?>
                    </h3>
                </div>
            </div>
        <?php endif; ?>

        <!-- Subject Grades Table -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="p-5 border-b border-slate-100">
                <h3 class="text-base font-bold text-slate-900">Subject Breakdown</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm border-collapse">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200 text-xs font-bold text-slate-700 uppercase">
                            <th class="py-3 px-4">Subject</th>
                            <th class="py-3 px-3">Code</th>
                            <th class="py-3 px-3 text-center">Score</th>
                            <th class="py-3 px-3 text-center">Grade</th>
                            <th class="py-3 px-3">Remark</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-medium text-slate-800">
                        <?php if (empty($subjectResults)): ?>
                            <tr>
                                <td colspan="5" class="py-6 text-center text-slate-400 text-xs">
                                    No subject grades found for this term.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($subjectResults as $res): ?>
                                <tr>
                                    <td class="py-3 px-4 font-bold text-slate-900">
                                        <?= e($res->classSubject?->subject?->name ?? 'Subject') ?>
                                    </td>
                                    <td class="py-3 px-3 text-xs text-slate-500">
                                        <?= e($res->classSubject?->subject?->code ?? '') ?>
                                    </td>
                                    <td class="py-3 px-3 text-center font-bold text-slate-900">
                                        <?= number_format($res->computedScore, 2) ?>%
                                    </td>
                                    <td class="py-3 px-3 text-center">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-lg text-xs font-bold bg-brand-50 text-brand-700">
                                            <?= e($res->gradeLetter) ?>
                                        </span>
                                    </td>
                                    <td class="py-3 px-3 text-xs text-slate-600">
                                        <?= e($res->remark ?? '&mdash;') ?>
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
