<div class="max-w-2xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-slate-900 tracking-tight">Create Quiz Assessment</h2>
            <p class="text-sm text-slate-500 mt-1">Configure title, duration, attempt limit, and target academic class.</p>
        </div>
        <a href="/teacher/quizzes" class="text-sm font-semibold text-slate-600 hover:text-slate-900">
            &larr; Back to Quizzes
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

    <form method="POST" action="/teacher/quizzes/create" class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 space-y-6">
        <input type="hidden" name="csrf_token" value="<?= e(\App\Middleware\CsrfMiddleware::getToken()) ?>">

        <div>
            <label for="title" class="block text-sm font-semibold text-slate-700 mb-1">Quiz Title <span class="text-rose-500">*</span></label>
            <input type="text" name="title" id="title" value="<?= e($old['title'] ?? '') ?>" placeholder="e.g. Mid-Term Chemistry CBT Test" class="w-full rounded-xl border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 text-sm" required>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
            <div>
                <label for="class_subject_id" class="block text-sm font-semibold text-slate-700 mb-1">Class & Subject <span class="text-rose-500">*</span></label>
                <select name="class_subject_id" id="class_subject_id" class="w-full rounded-xl border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 text-sm" required>
                    <option value="">-- Select Allocated Class --</option>
                    <?php foreach ($classSubjects as $cs): ?>
                        <option value="<?= $cs->id ?>" <?= ($old['class_subject_id'] ?? '') == $cs->id ? 'selected' : '' ?>>
                            <?= e($cs->subject?->name ?? 'Subject') ?> (<?= e($cs->academicClass?->name ?? 'Class') ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label for="term_id" class="block text-sm font-semibold text-slate-700 mb-1">Term <span class="text-rose-500">*</span></label>
                <select name="term_id" id="term_id" class="w-full rounded-xl border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 text-sm" required>
                    <?php foreach ($terms as $t): ?>
                        <option value="<?= $t->id ?>" <?= ($old['term_id'] ?? ($currentTerm?->id ?? 0)) == $t->id ? 'selected' : '' ?>>
                            <?= e($t->name) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
            <div>
                <label for="time_limit_minutes" class="block text-sm font-semibold text-slate-700 mb-1">Time Limit (Minutes)</label>
                <input type="number" min="0" name="time_limit_minutes" id="time_limit_minutes" value="<?= e($old['time_limit_minutes'] ?? '30') ?>" placeholder="0 for untimed test" class="w-full rounded-xl border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 text-sm">
                <p class="text-xs text-slate-400 mt-1">Set to 0 if the assessment has no countdown timer.</p>
            </div>

            <div>
                <label for="max_attempts" class="block text-sm font-semibold text-slate-700 mb-1">Max Attempts Allowed</label>
                <input type="number" min="1" max="10" name="max_attempts" id="max_attempts" value="<?= e($old['max_attempts'] ?? '1') ?>" class="w-full rounded-xl border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 text-sm" required>
                <p class="text-xs text-slate-400 mt-1">Number of times a student can retake this exam.</p>
            </div>
        </div>

        <div>
            <label for="instructions" class="block text-sm font-semibold text-slate-700 mb-1">Instructions / Assessment Guidelines</label>
            <textarea name="instructions" id="instructions" rows="3" placeholder="Enter instructions shown to students before starting the timer..." class="w-full rounded-xl border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 text-sm"><?= e($old['instructions'] ?? '') ?></textarea>
        </div>

        <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
            <a href="/teacher/quizzes" class="px-4 py-2.5 rounded-xl border border-slate-300 text-slate-700 text-sm font-semibold hover:bg-slate-50 transition">
                Cancel
            </a>
            <button type="submit" class="px-5 py-2.5 rounded-xl bg-brand-600 hover:bg-brand-700 text-white text-sm font-semibold shadow-sm transition">
                Next: Add Questions &rarr;
            </button>
        </div>
    </form>
</div>
