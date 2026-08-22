<div class="space-y-6">
    <!-- Header Card -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <!-- Breadcrumbs -->
                <nav class="flex items-center gap-2 text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">
                    <a href="/teacher/dashboard" class="text-slate-400 hover:text-emerald-600 transition">Faculty Portal</a>
                    <span class="text-slate-300">/</span>
                    <a href="/teacher/quizzes" class="text-slate-400 hover:text-emerald-600 transition">Quizzes & CBT Exams</a>
                    <span class="text-slate-300">/</span>
                    <span class="text-slate-700">Question Builder</span>
                </nav>
                <div class="flex flex-wrap items-center gap-3">
                    <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight leading-tight">
                        <?= htmlspecialchars($quiz->title) ?> — Questions
                    </h1>
                    <?php 
                        $sName = $classSubject?->subject?->name ?? 'Subject';
                        $cName = $classSubject?->schoolClass?->name ?? 'Class';
                    ?>
                    <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold bg-slate-100 text-slate-700 border border-slate-200">
                        <?= htmlspecialchars($sName) ?> (<?= htmlspecialchars($cName) ?>)
                    </span>
                </div>
                <p class="text-xs text-slate-500 mt-1">
                    Select assessment items from the Subject Question Bank, assign customized point weights, and arrange question sequence order.
                </p>
            </div>

            <div class="flex items-center gap-2.5 flex-wrap">
                <a href="/teacher/question-bank/create<?= $classSubject ? '?subject_id=' . (int)$classSubject->subjectId : '' ?>" target="_blank"
                   class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-bold text-slate-700 bg-slate-100 hover:bg-slate-200 transition">
                    <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                    </svg>
                    <span>Add New Bank Question</span>
                </a>

                <?php $this->include('components/button', [
                    'label' => 'Quiz Settings',
                    'variant' => 'secondary',
                    'href' => "/teacher/quizzes/{$quiz->id}/edit",
                    'icon' => '<svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>'
                ]); ?>
            </div>
        </div>
    </div>

    <!-- Question Picker Form -->
    <form method="POST" action="/teacher/quizzes/<?= (int)$quiz->id ?>/questions" id="quiz-questions-form" class="space-y-6">
        <?= csrf_field() ?>

        <?php if (empty($availableQuestions)): ?>
            <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-12 text-center">
                <div class="w-16 h-16 rounded-2xl bg-amber-50 text-amber-600 mx-auto flex items-center justify-center mb-4">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <h3 class="text-base font-bold text-slate-900">No Question Bank Items Available</h3>
                <p class="text-xs text-slate-500 mt-1.5 max-w-sm mx-auto">
                    There are currently no questions authored in the Question Bank for <strong><?= htmlspecialchars($sName) ?></strong>.
                </p>
                <div class="mt-6 flex items-center justify-center gap-3">
                    <a href="/teacher/question-bank/bulk<?= $classSubject ? '?subject_id=' . (int)$classSubject->subjectId : '' ?>" 
                       class="inline-flex items-center gap-2 px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl transition">
                        <span>Bulk Import Questions</span>
                    </a>
                    <a href="/teacher/question-bank/create<?= $classSubject ? '?subject_id=' . (int)$classSubject->subjectId : '' ?>" 
                       class="inline-flex items-center gap-2 px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl shadow-xs transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                        </svg>
                        <span>Add Questions to Bank</span>
                    </a>
                </div>
            </div>
        <?php else: ?>
            <?php 
                $selectedMap = [];
                foreach ($selectedQuestions as $sq) {
                    $selectedMap[$sq->questionId] = $sq;
                }
            ?>

            <!-- Questions Table Container -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
                <div class="p-4.5 bg-slate-50 border-b border-slate-200 flex items-center justify-between flex-wrap gap-3">
                    <div class="flex items-center gap-3 flex-wrap">
                        <span class="text-xs font-bold text-slate-800 uppercase tracking-wider">
                            Available Bank Questions (<span id="total-questions-count"><?= count($availableQuestions) ?></span>)
                        </span>
                        <div class="flex items-center gap-2">
                            <button type="button" onclick="selectAllQuestions(true)" class="text-[11px] font-bold text-emerald-700 hover:text-emerald-800 bg-emerald-50 hover:bg-emerald-100 px-2.5 py-1 rounded-md border border-emerald-200 transition">
                                Select All
                            </button>
                            <button type="button" onclick="selectAllQuestions(false)" class="text-[11px] font-bold text-slate-600 hover:text-slate-700 bg-slate-100 hover:bg-slate-200 px-2.5 py-1 rounded-md border border-slate-200 transition">
                                Deselect All
                            </button>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <span id="selected-count-badge" class="text-xs font-bold text-emerald-700 bg-emerald-100/80 px-2.5 py-1 rounded-lg">
                            <?= count($selectedMap) ?> of <?= count($availableQuestions) ?> Selected
                        </span>
                        <span class="text-xs font-bold text-slate-700 bg-slate-100 px-2.5 py-1 rounded-lg border border-slate-200">
                            Total: <strong id="header-max-score" class="text-emerald-700 font-extrabold"><?= number_format((float)$quiz->getTotalMaxScore(), 2) ?></strong> PTS
                        </span>
                    </div>
                </div>

                <div class="divide-y divide-slate-100" id="questions-container">
                    <?php foreach ($availableQuestions as $idx => $q): 
                        $isSelected = isset($selectedMap[$q->id]);
                        $assignedPoints = $isSelected ? $selectedMap[$q->id]->points : $q->defaultPoints;
                        $sortOrder = $isSelected ? $selectedMap[$q->id]->sortOrder : ($idx + 1);
                    ?>
                        <div class="question-row p-5 flex items-start gap-4 hover:bg-slate-50/60 transition <?= $isSelected ? 'bg-emerald-50/20' : '' ?>" id="row_<?= (int)$q->id ?>">
                            <div class="pt-1">
                                <input type="checkbox" id="q_check_<?= (int)$q->id ?>" 
                                       name="questions[<?= (int)$q->id ?>][question_id]" 
                                       value="<?= (int)$q->id ?>" 
                                       <?= $isSelected ? 'checked' : '' ?> 
                                       class="question-checkbox w-4.5 h-4.5 text-emerald-600 rounded-md border-slate-300 focus:ring-emerald-500 cursor-pointer" 
                                       onchange="toggleQuestionRow(<?= (int)$q->id ?>, this.checked)">
                            </div>

                            <div class="flex-1 space-y-2 min-w-0">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <?php if ($q->isMcq()): ?>
                                        <?php $this->include('components/badge', [
                                            'label' => 'MCQ',
                                            'variant' => 'info',
                                            'size' => 'sm'
                                        ]); ?>
                                    <?php else: ?>
                                        <?php $this->include('components/badge', [
                                            'label' => 'Short Answer',
                                            'variant' => 'success',
                                            'size' => 'sm'
                                        ]); ?>
                                    <?php endif; ?>

                                    <?php if ($q->topic): ?>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-semibold bg-slate-100 text-slate-600 border border-slate-200">
                                            <?= htmlspecialchars($q->topic) ?>
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <label for="q_check_<?= (int)$q->id ?>" class="block text-xs font-semibold text-slate-900 cursor-pointer leading-relaxed">
                                    <?= nl2br(htmlspecialchars($q->questionText)) ?>
                                </label>

                                <?php if ($q->isMcq() && !empty($q->options)): ?>
                                    <div class="text-xs text-slate-500 flex flex-wrap gap-2 pt-1">
                                        <?php foreach ($q->options as $optIdx => $opt): ?>
                                            <span class="px-2 py-0.5 rounded-md border text-[11px] <?= $opt->isCorrect ? 'bg-emerald-50 border-emerald-200 text-emerald-900 font-bold' : 'bg-slate-50 border-slate-200 text-slate-600' ?>">
                                                <?= $opt->isCorrect ? '✓ ' : (chr(65 + $optIdx) . ': ') ?><?= htmlspecialchars($opt->optionText) ?>
                                            </span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Points & Sequence Order Inputs -->
                            <div class="w-48 flex items-center gap-2.5 pt-0.5 flex-shrink-0">
                                <div>
                                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Score PTS</label>
                                    <input type="number" step="0.25" min="0.25" max="100" 
                                           name="questions[<?= (int)$q->id ?>][points]" 
                                           value="<?= htmlspecialchars((string)$assignedPoints) ?>" 
                                           oninput="recalculateTotals()"
                                           class="question-points-input w-20 rounded-xl border border-slate-300 text-xs font-bold text-slate-900 py-1.5 px-2 bg-slate-50 focus:bg-white focus:border-emerald-500 focus:ring-emerald-500 transition disabled:opacity-40 disabled:bg-slate-100" 
                                           <?= $isSelected ? '' : 'disabled' ?> id="pts_<?= (int)$q->id ?>">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Order</label>
                                    <input type="number" min="1" max="999" 
                                           name="questions[<?= (int)$q->id ?>][sort_order]" 
                                           value="<?= htmlspecialchars((string)$sortOrder) ?>" 
                                           class="w-16 rounded-xl border border-slate-300 text-xs font-bold text-slate-900 py-1.5 px-2 bg-slate-50 focus:bg-white focus:border-emerald-500 focus:ring-emerald-500 transition disabled:opacity-40 disabled:bg-slate-100" 
                                           <?= $isSelected ? '' : 'disabled' ?> id="ord_<?= (int)$q->id ?>">
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Sticky Bottom Submission Bar -->
            <div class="flex items-center justify-between p-5 bg-white rounded-2xl border border-slate-200 shadow-xs flex-wrap gap-3">
                <span class="text-xs font-medium text-slate-600">
                    Total Maximum CBT Marks: <strong id="footer-max-score" class="text-emerald-700 font-extrabold text-sm"><?= number_format((float)$quiz->getTotalMaxScore(), 2) ?> PTS</strong>
                </span>

                <div class="flex items-center gap-3">
                    <?php $this->include('components/button', [
                        'label' => 'Cancel',
                        'variant' => 'secondary',
                        'href' => '/teacher/quizzes'
                    ]); ?>

                    <?php $this->include('components/button', [
                        'label' => 'Save Quiz Questions',
                        'variant' => 'primary',
                        'type' => 'submit',
                        'icon' => '<svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>'
                    ]); ?>
                </div>
            </div>
        <?php endif; ?>
    </form>
</div>

<script>
function toggleQuestionRow(qId, checked) {
    const ptsInput = document.getElementById('pts_' + qId);
    const ordInput = document.getElementById('ord_' + qId);
    const row = document.getElementById('row_' + qId);
    if (ptsInput && ordInput) {
        ptsInput.disabled = !checked;
        ordInput.disabled = !checked;
    }
    if (row) {
        if (checked) {
            row.classList.add('bg-emerald-50/20');
        } else {
            row.classList.remove('bg-emerald-50/20');
        }
    }
    recalculateTotals();
}

function recalculateTotals() {
    let totalScore = 0;
    let selectedCount = 0;
    const checkboxes = document.querySelectorAll('.question-checkbox');
    const totalCount = checkboxes.length;

    checkboxes.forEach(cb => {
        if (cb.checked) {
            selectedCount++;
            const qId = cb.value;
            const ptsInput = document.getElementById('pts_' + qId);
            if (ptsInput) {
                const pts = parseFloat(ptsInput.value) || 0;
                totalScore += pts;
            }
        }
    });

    const formattedScore = totalScore.toFixed(2);
    
    const headerScore = document.getElementById('header-max-score');
    if (headerScore) {
        headerScore.textContent = formattedScore;
    }

    const footerScore = document.getElementById('footer-max-score');
    if (footerScore) {
        footerScore.textContent = formattedScore + ' PTS';
    }

    const countBadge = document.getElementById('selected-count-badge');
    if (countBadge) {
        countBadge.textContent = selectedCount + ' of ' + totalCount + ' Selected';
    }
}

function selectAllQuestions(selectAll) {
    const checkboxes = document.querySelectorAll('.question-checkbox');
    checkboxes.forEach((cb, idx) => {
        cb.checked = selectAll;
        const qId = cb.value;
        const ptsInput = document.getElementById('pts_' + qId);
        const ordInput = document.getElementById('ord_' + qId);
        const row = document.getElementById('row_' + qId);
        if (ptsInput && ordInput) {
            ptsInput.disabled = !selectAll;
            ordInput.disabled = !selectAll;
            if (selectAll && !ordInput.value) {
                ordInput.value = idx + 1;
            }
        }
        if (row) {
            if (selectAll) {
                row.classList.add('bg-emerald-50/20');
            } else {
                row.classList.remove('bg-emerald-50/20');
            }
        }
    });
    recalculateTotals();
}

document.addEventListener('DOMContentLoaded', recalculateTotals);
</script>
