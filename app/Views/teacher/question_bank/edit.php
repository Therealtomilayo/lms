<div class="max-w-4xl mx-auto space-y-6">
    <!-- Header Card -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <!-- Breadcrumbs -->
                <nav class="flex items-center gap-2 text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">
                    <a href="/teacher/dashboard" class="text-slate-400 hover:text-emerald-600 transition">Faculty Portal</a>
                    <span class="text-slate-300">/</span>
                    <a href="/teacher/question-bank?subject_id=<?= (int)$question->subjectId ?>" class="text-slate-400 hover:text-emerald-600 transition">Question Bank</a>
                    <span class="text-slate-300">/</span>
                    <span class="text-slate-700">Edit Question #<?= (int)$question->id ?></span>
                </nav>
                <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight leading-tight">
                    Edit Assessment Question
                </h1>
                <p class="text-xs text-slate-500 mt-1">
                    Update question statement, answer choices, correct key, syllabus topic, or point weights.
                </p>
            </div>

            <div class="flex items-center gap-2.5">
                <?php $this->include('components/button', [
                    'label' => 'Back to Bank',
                    'variant' => 'secondary',
                    'href' => '/teacher/question-bank?subject_id=' . (int)$question->subjectId,
                    'icon' => '<svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>'
                ]); ?>
            </div>
        </div>
    </div>

    <!-- Form Container -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6 sm:p-8">
        <form action="/teacher/question-bank/<?= (int)$question->id ?>/edit" method="POST" class="space-y-6">
            <?= csrf_field() ?>

            <!-- Subject & Topic Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Subject (Readonly) -->
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                        Target Subject
                    </label>
                    <div class="flex items-center gap-2 p-2.5 bg-slate-100/80 rounded-xl border border-slate-200 text-slate-700 text-xs font-bold">
                        <svg class="w-4 h-4 text-slate-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                        </svg>
                        <span class="truncate"><?= htmlspecialchars($question->subject?->name ?? ('Subject #' . $question->subjectId)) ?><?= !empty($question->subject?->code) ? ' (' . htmlspecialchars($question->subject?->code) . ')' : '' ?></span>
                    </div>
                </div>

                <!-- Topic -->
                <div>
                    <label for="topic" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                        Curriculum Topic (Optional)
                    </label>
                    <input type="text" name="topic" id="topic" maxlength="100"
                           value="<?= htmlspecialchars((string)old('topic', $question->topic ?? '')) ?>"
                           placeholder="e.g. Thermodynamics, Linear Algebra, Cell Biology"
                           class="w-full rounded-xl border border-slate-300 text-xs focus:border-emerald-500 focus:ring-emerald-500 bg-slate-50 py-2.5 px-3 font-medium text-slate-900 transition">
                </div>
            </div>

            <!-- Question Type & Points Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Question Type -->
                <div>
                    <label for="type" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                        Question Format <span class="text-rose-500">*</span>
                    </label>
                    <?php $currType = old('type', $question->type); ?>
                    <select name="type" id="type" required onchange="toggleQuestionType(this.value)"
                            class="w-full rounded-xl border border-slate-300 text-xs focus:border-emerald-500 focus:ring-emerald-500 bg-slate-50 py-2.5 px-3 font-semibold text-slate-900 transition">
                        <option value="mcq" <?= $currType === 'mcq' ? 'selected' : '' ?>>Multiple Choice Question (MCQ - Auto Graded)</option>
                        <option value="short_answer" <?= $currType === 'short_answer' ? 'selected' : '' ?>>Short Answer (Teacher Evaluated)</option>
                    </select>
                </div>

                <!-- Default Points -->
                <div>
                    <label for="default_points" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                        Default Points Weight <span class="text-rose-500">*</span>
                    </label>
                    <div class="relative">
                        <input type="number" step="0.25" min="0.25" max="100" name="default_points" id="default_points" required
                               value="<?= htmlspecialchars((string)old('default_points', (string)$question->defaultPoints)) ?>"
                               class="w-full rounded-xl border border-slate-300 text-xs focus:border-emerald-500 focus:ring-emerald-500 bg-slate-50 py-2.5 pl-3 pr-12 font-bold text-slate-900 transition">
                        <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs font-bold text-slate-400 pointer-events-none">PTS</span>
                    </div>
                </div>
            </div>

            <!-- Question Text -->
            <div>
                <label for="question_text" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                    Question Statement / Prompt <span class="text-rose-500">*</span>
                </label>
                <textarea name="question_text" id="question_text" rows="4" required
                          class="w-full rounded-xl border border-slate-300 text-xs focus:border-emerald-500 focus:ring-emerald-500 bg-slate-50 py-2.5 px-3 font-medium text-slate-900 leading-relaxed transition"><?= htmlspecialchars((string)old('question_text', $question->questionText)) ?></textarea>
            </div>

            <!-- Options Container for MCQ -->
            <div id="mcq-options-container" class="space-y-4 pt-4 border-t border-slate-100 <?= $currType === 'short_answer' ? 'hidden' : '' ?>">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-900">MCQ Options & Correct Answer Key</h3>
                        <p class="text-[11px] text-slate-500 mt-0.5">Select the single radio button corresponding to the correct answer. Only one option can be correct.</p>
                    </div>
                    <button type="button" onclick="addOptionRow()" class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-bold text-emerald-700 bg-emerald-50 hover:bg-emerald-100 rounded-lg border border-emerald-200 transition">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                        <span>Add Option</span>
                    </button>
                </div>

                <div id="options-list" class="space-y-3">
                    <?php 
                    $optionsToRender = [];
                    $oldOpts = old('options', []);
                    $oldCorrect = old('correct_option', null);
                    
                    if (!empty($oldOpts)) {
                        $optionsToRender = $oldOpts;
                    } elseif (!empty($question->options)) {
                        foreach ($question->options as $optIdx => $opt) {
                            $optionsToRender[] = [
                                'option_text' => $opt->optionText,
                                'is_correct' => $opt->isCorrect ? 1 : 0,
                            ];
                            if ($opt->isCorrect && $oldCorrect === null) {
                                $oldCorrect = $optIdx;
                            }
                        }
                    } else {
                        $optionsToRender = [
                            ['option_text' => '', 'is_correct' => 1],
                            ['option_text' => '', 'is_correct' => 0],
                            ['option_text' => '', 'is_correct' => 0],
                            ['option_text' => '', 'is_correct' => 0],
                        ];
                    }

                    if ($oldCorrect === null) {
                        $oldCorrect = 0;
                    }

                    foreach ($optionsToRender as $idx => $opt): 
                        $isSelected = ((int)$oldCorrect === $idx);
                    ?>
                        <div class="option-row flex items-center gap-3 p-3 rounded-xl border <?= $isSelected ? 'border-emerald-400 bg-emerald-50/40' : 'border-slate-200 bg-slate-50/60' ?> transition" id="option_row_<?= $idx ?>">
                            <label class="flex items-center gap-2 cursor-pointer flex-shrink-0" title="Select as single correct answer">
                                <input type="radio" name="correct_option" value="<?= $idx ?>" <?= $isSelected ? 'checked' : '' ?>
                                       onchange="handleCorrectRadioChange()"
                                       class="w-4 h-4 text-emerald-600 focus:ring-emerald-500 border-slate-300 cursor-pointer">
                                <span class="option-letter w-6 h-6 rounded-lg font-bold text-xs flex items-center justify-center <?= $isSelected ? 'bg-emerald-600 text-white' : 'bg-slate-200 text-slate-700' ?>">
                                    <?= chr(65 + $idx) ?>
                                </span>
                            </label>
                            <input type="text" name="options[<?= $idx ?>][option_text]" value="<?= htmlspecialchars((string)($opt['option_text'] ?? '')) ?>" 
                                   placeholder="Option <?= chr(65 + $idx) ?> text statement..." 
                                   class="flex-1 rounded-lg border border-slate-300 text-xs py-2 px-3 focus:border-emerald-500 focus:ring-emerald-500 bg-white font-medium text-slate-900 transition">
                            
                            <?php if ($idx >= 2): ?>
                                <button type="button" onclick="removeOptionRow(this)" class="p-1 text-slate-400 hover:text-rose-600 rounded-lg hover:bg-rose-50 transition" title="Remove this option">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Form Actions Footer -->
            <div class="pt-6 border-t border-slate-100 flex items-center justify-end gap-3">
                <?php $this->include('components/button', [
                    'label' => 'Cancel',
                    'variant' => 'secondary',
                    'href' => '/teacher/question-bank?subject_id=' . (int)$question->subjectId
                ]); ?>

                <?php $this->include('components/button', [
                    'label' => 'Save Changes',
                    'variant' => 'primary',
                    'type' => 'submit',
                    'icon' => '<svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>'
                ]); ?>
            </div>
        </form>
    </div>
</div>

<script>
function toggleQuestionType(type) {
    const mcqContainer = document.getElementById('mcq-options-container');
    if (type === 'short_answer') {
        mcqContainer.classList.add('hidden');
    } else {
        mcqContainer.classList.remove('hidden');
    }
}

function handleCorrectRadioChange() {
    const rows = document.querySelectorAll('.option-row');
    rows.forEach((row, idx) => {
        const radio = row.querySelector('input[type="radio"]');
        const letter = row.querySelector('.option-letter');
        if (radio && radio.checked) {
            row.classList.add('border-emerald-400', 'bg-emerald-50/40');
            row.classList.remove('border-slate-200', 'bg-slate-50/60');
            if (letter) {
                letter.classList.add('bg-emerald-600', 'text-white');
                letter.classList.remove('bg-slate-200', 'text-slate-700');
            }
        } else {
            row.classList.remove('border-emerald-400', 'bg-emerald-50/40');
            row.classList.add('border-slate-200', 'bg-slate-50/60');
            if (letter) {
                letter.classList.remove('bg-emerald-600', 'text-white');
                letter.classList.add('bg-slate-200', 'text-slate-700');
            }
        }
    });
}

function addOptionRow() {
    const list = document.getElementById('options-list');
    const count = list.children.length;
    if (count >= 10) {
        alert('Maximum of 10 options reached.');
        return;
    }
    const letter = String.fromCharCode(65 + count);
    
    const row = document.createElement('div');
    row.className = 'option-row flex items-center gap-3 p-3 rounded-xl border border-slate-200 bg-slate-50/60 transition';
    row.id = 'option_row_' + count;
    row.innerHTML = `
        <label class="flex items-center gap-2 cursor-pointer flex-shrink-0" title="Select as single correct answer">
            <input type="radio" name="correct_option" value="${count}" onchange="handleCorrectRadioChange()"
                   class="w-4 h-4 text-emerald-600 focus:ring-emerald-500 border-slate-300 cursor-pointer">
            <span class="option-letter w-6 h-6 rounded-lg font-bold text-xs flex items-center justify-center bg-slate-200 text-slate-700">
                ${letter}
            </span>
        </label>
        <input type="text" name="options[${count}][option_text]" placeholder="Option ${letter} statement..." 
               class="flex-1 rounded-lg border border-slate-300 text-xs py-2 px-3 focus:border-emerald-500 focus:ring-emerald-500 bg-white font-medium text-slate-900 transition">
        <button type="button" onclick="removeOptionRow(this)" class="p-1 text-slate-400 hover:text-rose-600 rounded-lg hover:bg-rose-50 transition" title="Remove this option">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    `;
    list.appendChild(row);
}

function removeOptionRow(btn) {
    const list = document.getElementById('options-list');
    if (list.children.length <= 2) {
        alert('A minimum of 2 options is required for Multiple Choice Questions.');
        return;
    }
    const row = btn.closest('.option-row');
    const wasChecked = row.querySelector('input[type="radio"]').checked;
    row.remove();
    
    // Re-index remaining rows
    const rows = list.querySelectorAll('.option-row');
    rows.forEach((r, idx) => {
        const letter = String.fromCharCode(65 + idx);
        r.id = 'option_row_' + idx;
        const radio = r.querySelector('input[type="radio"]');
        radio.value = idx;
        const letterSpan = r.querySelector('.option-letter');
        letterSpan.textContent = letter;
        const input = r.querySelector('input[type="text"]');
        input.name = `options[${idx}][option_text]`;
        input.placeholder = `Option ${letter} statement...`;
    });
    
    if (wasChecked && rows.length > 0) {
        rows[0].querySelector('input[type="radio"]').checked = true;
    }
    handleCorrectRadioChange();
}
</script>
