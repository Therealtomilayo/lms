<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <a href="/teacher/gradebook" class="text-sm font-semibold text-slate-500 hover:text-slate-800 transition">&larr; Back to Gradebooks</a>
            </div>
            <h2 class="text-2xl font-bold text-slate-900 tracking-tight mt-1">
                <?= e($classSubject->subject?->name ?? 'Subject') ?> &mdash; <?= e($classSubject->class?->name ?? 'Class') ?>
            </h2>
            <p class="text-sm text-slate-500 mt-0.5">
                Session: <?= e($session?->name ?? 'N/A') ?> | Term: <?= e($term?->name ?? 'N/A') ?>
            </p>
        </div>
        <div>
            <?php if ($isLocked): ?>
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold bg-rose-50 text-rose-700 border border-rose-200">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                    Locked by Administration
                </span>
            <?php else: ?>
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"/></svg>
                    Open for Scoring
                </span>
            <?php endif; ?>
        </div>
    </div>

    <?php if (empty($categories)): ?>
        <div class="bg-amber-50 border border-amber-200 rounded-2xl p-6 text-amber-800">
            <h4 class="font-semibold text-sm">No Assessment Categories Configured</h4>
            <p class="text-xs mt-1">Assessment categories (such as CA 1, CA 2, and Exam) must be configured by an administrator for this term before scores can be entered.</p>
        </div>
    <?php else: ?>
        <form method="POST" action="/teacher/gradebook/<?= $classSubject->id ?>/save">
            <input type="hidden" name="csrf_token" value="<?= e(\App\Middleware\CsrfMiddleware::getToken()) ?>">
            <input type="hidden" name="term_id" value="<?= e($term->id) ?>">

            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-sm">
                        <thead>
                            <tr class="bg-slate-50/75 border-b border-slate-200 text-xs font-bold text-slate-700 uppercase tracking-wider">
                                <th class="py-3.5 px-4">Student</th>
                                <th class="py-3.5 px-3">Admission No</th>
                                <?php foreach ($categories as $cat): ?>
                                    <th class="py-3.5 px-3 text-center">
                                        <?= e($cat->name) ?>
                                        <div class="text-[10px] text-slate-400 font-normal normal-case">
                                            (<?= $cat->weightPercentage ?>% / Max <?= $cat->maxPoints ?>)
                                        </div>
                                    </th>
                                <?php endforeach; ?>
                                <th class="py-3.5 px-3 text-center">Total (100%)</th>
                                <th class="py-3.5 px-3 text-center">Grade</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 font-medium text-slate-800">
                            <?php if (empty($students)): ?>
                                <tr>
                                    <td colspan="<?= count($categories) + 4 ?>" class="py-8 text-center text-slate-400 text-sm">
                                        No students enrolled in this subject.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($students as $student): ?>
                                    <?php 
                                        $result = $resultMap[$student->id] ?? null;
                                    ?>
                                    <tr class="hover:bg-slate-50/50 transition">
                                        <td class="py-3 px-4 font-semibold text-slate-900">
                                            <?= e($student->user?->name ?? 'Student') ?>
                                        </td>
                                        <td class="py-3 px-3 text-xs text-slate-500">
                                            <?= e($student->admissionNumber) ?>
                                        </td>
                                        <?php foreach ($categories as $cat): ?>
                                            <?php 
                                                $scoreVal = $scoreMatrix[$student->id][$cat->id] ?? '';
                                            ?>
                                            <td class="py-2 px-3 text-center">
                                                <input type="number" 
                                                       step="0.01" 
                                                       min="0" 
                                                       max="<?= $cat->maxPoints ?>" 
                                                       name="scores[<?= $student->id ?>][<?= $cat->id ?>]" 
                                                       value="<?= $scoreVal !== '' ? e((string)$scoreVal) : '' ?>"
                                                       <?= $isLocked ? 'disabled' : '' ?>
                                                       class="w-20 px-2.5 py-1.5 text-center text-sm rounded-lg border border-slate-300 focus:ring-2 focus:ring-brand-500 focus:border-brand-500 disabled:bg-slate-100 disabled:text-slate-500 transition">
                                            </td>
                                        <?php endforeach; ?>
                                        <td class="py-3 px-3 text-center font-bold text-slate-900">
                                            <?= $result ? number_format($result->computedScore, 2) : '&mdash;' ?>
                                        </td>
                                        <td class="py-3 px-3 text-center">
                                            <?php if ($result): ?>
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-brand-50 text-brand-700">
                                                    <?= e($result->gradeLetter) ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-slate-400">&mdash;</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <?php if (!$isLocked && !empty($students)): ?>
                    <div class="p-4 bg-slate-50 border-t border-slate-200 flex flex-col sm:flex-row items-center justify-between gap-3">
                        <label class="flex items-center gap-2 text-xs font-semibold text-slate-700 cursor-pointer">
                            <input type="checkbox" name="compute_results" value="1" checked class="rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                            Automatically compute weighted totals & grades on save
                        </label>
                        <div class="flex items-center gap-2">
                            <button type="submit" class="px-5 py-2.5 bg-brand-600 hover:bg-brand-700 text-white text-sm font-semibold rounded-xl transition shadow-sm">
                                Save Gradebook
                            </button>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </form>
    <?php endif; ?>
</div>
