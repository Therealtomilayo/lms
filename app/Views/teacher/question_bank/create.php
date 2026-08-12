<div class="max-w-3xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-slate-900 tracking-tight">Add New Question</h2>
            <p class="text-sm text-slate-500 mt-1">Create a reusable question for the Question Bank.</p>
        </div>
        <a href="/teacher/question-bank?subject_id=<?= $selectedSubjectId ?>" class="text-sm font-semibold text-slate-600 hover:text-slate-900">
            &larr; Back to Question Bank
        </a>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="bg-rose-50 border border-rose-200 text-rose-700 p-4 rounded-xl text-sm">
            <p class="font-semibold mb-1">Please correct the following errors:</p>
            <ul class="list-disc list-inside space-y-1">
                <?php foreach ($errors as $error): ?>
                    <li><?= e($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="POST" action="/teacher/question-bank/create" class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 space-y-6">
        <input type="hidden" name="csrf_token" value="<?= e(\App\Middleware\CsrfMiddleware::getToken()) ?>">

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
            <div>
                <label for="subject_id" class="block text-sm font-semibold text-slate-700 mb-1">Subject <span class="text-rose-500">*</span></label>
                <select name="subject_id" id="subject_id" class="w-full rounded-xl border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 text-sm" required>
                    <?php foreach ($subjects as $s): ?>
                        <option value="<?= $s->id ?>" <?= $selectedSubjectId === $s->id ? 'selected' : '' ?>>
                            <?= e($s->name) ?> (<?= e($s->code) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label for="topic" class="block text-sm font-semibold text-slate-700 mb-1">Topic (Optional)</label>
                <input type="text" name="topic" id="topic" value="<?= e($old['topic'] ?? '') ?>" placeholder="e.g. Thermodynamics, Algebra, Cell Biology" class="w-full rounded-xl border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 text-sm">
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
            <div>
                <label for="type" class="block text-sm font-semibold text-slate-700 mb-1">Question Type <span class="text-rose-500">*</span></label>
                <select name="type" id="type" class="w-full rounded-xl border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 text-sm" onchange="toggleQuestionType(this.value)" required>
                    <option value="mcq" <?= ($old['type'] ?? 'mcq') === 'mcq' ? 'selected' : '' ?>>Multiple Choice Question (MCQ)</option>
                    <option value="short_answer" <?= ($old['type'] ?? '') === 'short_answer' ? 'selected' : '' ?>>Short Answer (Manual Grading)</option>
                </select>
            </div>

            <div>
                <label for="default_points" class="block text-sm font-semibold text-slate-700 mb-1">Default Points <span class="text-rose-500">*</span></label>
                <input type="number" step="0.25" min="0.25" name="default_points" id="default_points" value="<?= e($old['default_points'] ?? '1.00') ?>" class="w-full rounded-xl border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 text-sm" required>
            </div>
        </div>

        <div>
            <label for="question_text" class="block text-sm font-semibold text-slate-700 mb-1">Question Text <span class="text-rose-500">*</span></label>
            <textarea name="question_text" id="question_text" rows="4" placeholder="Enter the full question statement or prompt here..." class="w-full rounded-xl border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 text-sm" required><?= e($old['question_text'] ?? '') ?></textarea>
        </div>

        <!-- Options Container for MCQ -->
        <div id="mcq-options-container" class="space-y-4 pt-4 border-t border-slate-100 <?= ($old['type'] ?? 'mcq') === 'short_answer' ? 'hidden' : '' ?>">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-bold text-slate-900">Answer Options</h3>
                    <p class="text-xs text-slate-500">Provide possible answer choices and select the correct one.</p>
                </div>
            </div>

            <div id="options-list" class="space-y-3">
                <?php 
                $defaultOpts = [
                    ['option_text' => '', 'is_correct' => 1],
                    ['option_text' => '', 'is_correct' => 0],
                    ['option_text' => '', 'is_correct' => 0],
                    ['option_text' => '', 'is_correct' => 0],
                ];
                $optsToRender = !empty($old['options']) ? $old['options'] : $defaultOpts;
                foreach ($optsToRender as $idx => $opt): 
                ?>
                    <div class="flex items-center gap-3 p-3 rounded-xl border border-slate-200 bg-slate-50/50">
                        <input type="radio" name="options[<?= $idx ?>][is_correct]" value="1" <?= !empty($opt['is_correct']) ? 'checked' : '' ?> class="w-4 h-4 text-brand-600 focus:ring-brand-500 border-slate-300" title="Mark as correct answer">
                        <input type="text" name="options[<?= $idx ?>][option_text]" value="<?= e($opt['option_text'] ?? '') ?>" placeholder="Option text (e.g. <?= chr(65 + $idx) ?>)" class="flex-1 rounded-lg border-slate-300 shadow-sm text-sm focus:border-brand-500 focus:ring-brand-500">
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
            <a href="/teacher/question-bank?subject_id=<?= $selectedSubjectId ?>" class="px-4 py-2.5 rounded-xl border border-slate-300 text-slate-700 text-sm font-semibold hover:bg-slate-50 transition">
                Cancel
            </a>
            <button type="submit" class="px-5 py-2.5 rounded-xl bg-brand-600 hover:bg-brand-700 text-white text-sm font-semibold shadow-sm transition">
                Save Question
            </button>
        </div>
    </form>
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
</script>
