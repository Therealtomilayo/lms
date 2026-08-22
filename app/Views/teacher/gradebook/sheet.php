<div class="space-y-6">
    <!-- Header Card -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <!-- Breadcrumbs -->
                <nav class="flex items-center gap-2 text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">
                    <a href="/teacher/dashboard" class="text-slate-400 hover:text-emerald-600 transition">Faculty Portal</a>
                    <span class="text-slate-300">/</span>
                    <a href="/teacher/gradebook" class="text-slate-400 hover:text-emerald-600 transition">Gradebooks</a>
                    <span class="text-slate-300">/</span>
                    <span class="text-slate-700">Score Sheet</span>
                </nav>
                <?php 
                    $sName = $classSubject->subject?->name ?? 'Subject';
                    $cName = $classSubject->schoolClass?->name ?? ($classSubject->class?->name ?? 'Class');
                    $arm = $classSubject->schoolClass?->sectionArm ?? '';
                ?>
                <div class="flex flex-wrap items-center gap-3">
                    <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight leading-tight">
                        <?= htmlspecialchars($sName) ?> &mdash; <?= htmlspecialchars($cName) ?><?= !empty($arm) ? ' (' . htmlspecialchars($arm) . ')' : '' ?>
                    </h1>
                </div>
                <p class="text-xs text-slate-500 mt-1">
                    Academic Session: <strong><?= htmlspecialchars($session?->name ?? 'Current Session') ?></strong> &middot; Term: <strong><?= htmlspecialchars($term?->name ?? 'Current Term') ?></strong>
                </p>
            </div>

            <div class="flex items-center gap-2.5 flex-wrap">
                <!-- Status Badge -->
                <?php if ($isLocked): ?>
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold bg-rose-50 text-rose-700 border border-rose-200">
                        <svg class="w-4 h-4 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                        <span>Locked by Administration</span>
                    </span>
                <?php else: ?>
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                        <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"/>
                        </svg>
                        <span>Open for Scoring</span>
                    </span>
                <?php endif; ?>

                <?php $this->include('components/button', [
                    'label' => 'Back to Gradebooks',
                    'variant' => 'secondary',
                    'href' => '/teacher/gradebook',
                    'icon' => '<svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>'
                ]); ?>
            </div>
        </div>
    </div>

    <!-- Term Switcher Bar -->
    <?php if (!empty($allTerms)): ?>
        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-xs flex items-center justify-between flex-wrap gap-3">
            <div class="flex items-center gap-2">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Grading Term:</span>
                <div class="flex items-center gap-1.5">
                    <?php foreach ($allTerms as $t): ?>
                        <a href="/teacher/gradebook/<?= (int)$classSubject->id ?>?term_id=<?= (int)$t->id ?>" 
                           class="px-3 py-1.5 rounded-xl text-xs font-bold transition <?= (int)$term->id === (int)$t->id ? 'bg-slate-900 text-white shadow-xs' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' ?>">
                            <?= htmlspecialchars($t->name) ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <span class="text-xs font-medium text-slate-500">
                <?= count($students) ?> enrolled student<?= count($students) === 1 ? '' : 's' ?> in cohort
            </span>
        </div>
    <?php endif; ?>

    <!-- Gradebook Sheet Container -->
    <?php if (empty($categories)): ?>
        <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-12 text-center">
            <div class="w-16 h-16 rounded-2xl bg-amber-50 text-amber-600 mx-auto flex items-center justify-center mb-4">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
            </div>
            <h3 class="text-base font-bold text-slate-900">No Assessment Categories Configured</h3>
            <p class="text-xs text-slate-500 mt-1.5 max-w-md mx-auto">
                Assessment categories (such as Continuous Assessment CA 1, CA 2, and Final Exam) must be configured by an administrator for this academic term before scores can be submitted.
            </p>
        </div>
    <?php else: ?>
        <form method="POST" action="/teacher/gradebook/<?= (int)$classSubject->id ?>/save" class="space-y-6" id="gradebook-form">
            <?= csrf_field() ?>
            <input type="hidden" name="term_id" value="<?= (int)$term->id ?>">

            <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-200 text-[11px] font-bold text-slate-600 uppercase tracking-wider">
                                <th class="py-3.5 px-5">Student Candidate</th>
                                <th class="py-3.5 px-3">Admission No</th>
                                <?php foreach ($categories as $cat): ?>
                                    <th class="py-3.5 px-3 text-center">
                                        <?= htmlspecialchars($cat->name) ?>
                                        <div class="text-[10px] text-slate-400 font-semibold normal-case">
                                            <?= $cat->weightPercentage ?>% (Max <?= $cat->maxPoints ?> PTS)
                                        </div>
                                    </th>
                                <?php endforeach; ?>
                                <th class="py-3.5 px-4 text-center">Total (100%)</th>
                                <th class="py-3.5 px-4 text-center">Grade</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php if (empty($students)): ?>
                                <tr>
                                    <td colspan="<?= count($categories) + 4 ?>" class="py-12 text-center text-slate-400">
                                        No students enrolled in this class-subject.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($students as $student): ?>
                                    <?php 
                                        $result = $resultMap[$student->id] ?? null;
                                        $sName = $student->user?->name ?? ($student->name ?? 'Student');
                                        $admNo = $student->admissionNumber ?? '';
                                    ?>
                                    <tr class="hover:bg-slate-50/60 transition" id="row_student_<?= (int)$student->id ?>">
                                        <td class="py-3.5 px-5">
                                            <div class="flex items-center gap-3">
                                                <div class="w-7 h-7 rounded-lg bg-emerald-100 text-emerald-800 font-bold text-xs flex items-center justify-center flex-shrink-0">
                                                    <?= strtoupper(substr($sName, 0, 1)) ?>
                                                </div>
                                                <span class="font-bold text-slate-900 truncate"><?= htmlspecialchars($sName) ?></span>
                                            </div>
                                        </td>

                                        <td class="py-3.5 px-3">
                                            <span class="font-mono text-[11px] font-semibold text-slate-500">
                                                <?= htmlspecialchars($admNo) ?>
                                            </span>
                                        </td>

                                        <?php foreach ($categories as $cat): ?>
                                            <?php 
                                                $scoreVal = $scoreMatrix[$student->id][$cat->id] ?? '';
                                            ?>
                                            <td class="py-2.5 px-3 text-center">
                                                <input type="number" 
                                                       step="0.01" 
                                                       min="0" 
                                                       max="<?= (float)$cat->maxPoints ?>" 
                                                       name="scores[<?= (int)$student->id ?>][<?= (int)$cat->id ?>]" 
                                                       value="<?= $scoreVal !== '' ? htmlspecialchars((string)$scoreVal) : '' ?>"
                                                       <?= $isLocked ? 'disabled' : '' ?>
                                                       data-weight="<?= (float)$cat->weightPercentage ?>"
                                                       data-max="<?= (float)$cat->maxPoints ?>"
                                                       class="w-20 px-2 py-1.5 text-center text-xs font-bold rounded-xl border border-slate-300 bg-slate-50 focus:bg-white focus:ring-emerald-500 focus:border-emerald-500 disabled:bg-slate-100 disabled:text-slate-400 transition">
                                            </td>
                                        <?php endforeach; ?>

                                        <td class="py-3.5 px-4 text-center">
                                            <span class="font-extrabold text-slate-900 text-xs">
                                                <?= $result ? number_format((float)$result->computedScore, 2) : '&mdash;' ?>
                                            </span>
                                        </td>

                                        <td class="py-3.5 px-4 text-center">
                                            <?php if ($result && !empty($result->gradeLetter)): ?>
                                                <?php 
                                                    $gl = strtoupper($result->gradeLetter);
                                                    $v = in_array($gl, ['A', 'B']) ? 'success' : (in_array($gl, ['C', 'D']) ? 'info' : 'danger');
                                                ?>
                                                <?php $this->include('components/badge', [
                                                    'label' => $result->gradeLetter,
                                                    'variant' => $v,
                                                    'size' => 'sm'
                                                ]); ?>
                                            <?php else: ?>
                                                <span class="text-slate-300">&mdash;</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <?php if (!$isLocked && !empty($students)): ?>
                <!-- Action Submission Card -->
                <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-5 flex flex-col sm:flex-row items-center justify-between gap-4">
                    <label class="flex items-center gap-2.5 text-xs font-semibold text-slate-700 cursor-pointer">
                        <input type="checkbox" name="compute_results" value="1" checked class="w-4 h-4 text-emerald-600 rounded border-slate-300 focus:ring-emerald-500 cursor-pointer">
                        <span>Automatically compute weighted totals & assign grade scale letters on save</span>
                    </label>

                    <div class="flex items-center gap-3 flex-shrink-0">
                        <?php $this->include('components/button', [
                            'label' => 'Cancel',
                            'variant' => 'secondary',
                            'href' => '/teacher/gradebook'
                        ]); ?>

                        <?php $this->include('components/button', [
                            'label' => 'Save Gradebook',
                            'variant' => 'primary',
                            'type' => 'submit',
                            'icon' => '<svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>'
                        ]); ?>
                    </div>
                </div>
            <?php endif; ?>
        </form>
    <?php endif; ?>
</div>
