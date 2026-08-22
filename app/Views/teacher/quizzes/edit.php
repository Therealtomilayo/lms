<div class="max-w-4xl mx-auto space-y-6">
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
                    <span class="text-slate-700">Edit Settings</span>
                </nav>
                <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight leading-tight">
                    Edit CBT Quiz Settings
                </h1>
                <p class="text-xs text-slate-500 mt-1">
                    Update assessment title, duration countdowns, attempt rules, and guidelines.
                </p>
            </div>

            <div class="flex items-center gap-2.5 flex-wrap">
                <a href="/teacher/quizzes/<?= (int)$quiz->id ?>/questions" 
                   class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg text-xs font-bold text-emerald-700 bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 transition">
                    <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                    </svg>
                    <span>Manage Questions (<?= count($quiz->quizQuestions) ?>)</span>
                </a>

                <?php $this->include('components/button', [
                    'label' => 'Back to Quizzes',
                    'variant' => 'secondary',
                    'href' => '/teacher/quizzes',
                    'icon' => '<svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>'
                ]); ?>
            </div>
        </div>
    </div>

    <!-- Form Container -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6 sm:p-8">
        <form action="/teacher/quizzes/<?= (int)$quiz->id ?>/edit" method="POST" class="space-y-6">
            <?= csrf_field() ?>

            <!-- Title -->
            <div>
                <label for="title" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                    Quiz Title / Assessment Name <span class="text-rose-500">*</span>
                </label>
                <input type="text" name="title" id="title" required maxlength="200"
                       value="<?= htmlspecialchars((string)old('title', $quiz->title)) ?>"
                       class="w-full rounded-xl border border-slate-300 text-xs focus:border-emerald-500 focus:ring-emerald-500 bg-slate-50 py-2.5 px-3 font-medium text-slate-900 transition">
            </div>

            <!-- Allocation Display (Read-Only) -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                        Assigned Class & Subject
                    </label>
                    <?php 
                        $sName = $quiz->classSubject?->subject?->name ?? 'Subject';
                        $cName = $quiz->classSubject?->schoolClass?->name ?? 'Class';
                        $arm = $quiz->classSubject?->schoolClass?->sectionArm ?? '';
                    ?>
                    <div class="flex items-center gap-2 p-2.5 bg-slate-100/80 rounded-xl border border-slate-200 text-slate-700 text-xs font-bold">
                        <svg class="w-4 h-4 text-slate-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                        </svg>
                        <span class="truncate"><?= htmlspecialchars($sName) ?> — <?= htmlspecialchars($cName) ?><?= !empty($arm) ? ' (' . htmlspecialchars($arm) . ')' : '' ?></span>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                        Academic Term
                    </label>
                    <div class="p-2.5 bg-slate-100/80 rounded-xl border border-slate-200 text-slate-700 text-xs font-bold">
                        <?= htmlspecialchars($quiz->term?->name ?? 'Academic Term') ?>
                    </div>
                </div>
            </div>

            <!-- Parameters Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <!-- Time Limit -->
                <div>
                    <label for="time_limit_minutes" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                        Time Limit (Minutes)
                    </label>
                    <div class="relative">
                        <input type="number" min="0" max="300" name="time_limit_minutes" id="time_limit_minutes"
                               value="<?= htmlspecialchars((string)old('time_limit_minutes', (string)$quiz->timeLimitMinutes)) ?>"
                               class="w-full rounded-xl border border-slate-300 text-xs focus:border-emerald-500 focus:ring-emerald-500 bg-slate-50 py-2.5 pl-3 pr-14 font-bold text-slate-900 transition">
                        <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs font-bold text-slate-400 pointer-events-none">MINS</span>
                    </div>
                    <p class="text-[11px] text-slate-400 mt-1">Set to 0 if the assessment is untimed.</p>
                </div>

                <!-- Max Attempts -->
                <div>
                    <label for="max_attempts" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                        Max Attempts Allowed <span class="text-rose-500">*</span>
                    </label>
                    <input type="number" min="1" max="10" name="max_attempts" id="max_attempts" required
                           value="<?= htmlspecialchars((string)old('max_attempts', (string)$quiz->maxAttempts)) ?>"
                           class="w-full rounded-xl border border-slate-300 text-xs focus:border-emerald-500 focus:ring-emerald-500 bg-slate-50 py-2.5 px-3 font-bold text-slate-900 transition">
                </div>
            </div>

            <!-- Instructions -->
            <div>
                <label for="instructions" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                    Instructions & Guidelines
                </label>
                <textarea name="instructions" id="instructions" rows="4"
                          class="w-full rounded-xl border border-slate-300 text-xs focus:border-emerald-500 focus:ring-emerald-500 bg-slate-50 py-2.5 px-3 font-medium text-slate-900 leading-relaxed transition"><?= htmlspecialchars((string)old('instructions', $quiz->instructions ?? '')) ?></textarea>
            </div>

            <!-- Form Actions Footer -->
            <div class="pt-6 border-t border-slate-100 flex items-center justify-end gap-3">
                <?php $this->include('components/button', [
                    'label' => 'Cancel',
                    'variant' => 'secondary',
                    'href' => '/teacher/quizzes'
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
