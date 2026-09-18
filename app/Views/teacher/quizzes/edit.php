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

    <!-- Learning Activity Prerequisites (Phase 4) -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6 sm:p-8 space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-slate-100">
            <div class="flex items-center gap-2">
                <span class="p-2 rounded-lg bg-amber-50 text-amber-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                </span>
                <div>
                    <h2 class="text-lg font-bold text-slate-900 tracking-tight">Required Prerequisites</h2>
                    <p class="text-xs text-slate-500">Students must complete these activities before this quiz unlocks.</p>
                </div>
            </div>

            <button type="button" onclick="openAddQuizPrereqModal()" 
                    class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-amber-600 hover:bg-amber-700 text-white text-xs font-bold rounded-xl shadow-xs transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                </svg>
                <span>Add Prerequisite</span>
            </button>
        </div>

        <?php if (empty($configuredPrerequisites)): ?>
            <div class="text-center py-8 px-4 bg-slate-50/70 rounded-xl border border-dashed border-slate-200">
                <p class="text-xs font-semibold text-slate-600">No prerequisites configured.</p>
                <p class="text-[11px] text-slate-400 mt-0.5">This quiz is unlocked for all enrolled students according to standard quiz availability rules.</p>
            </div>
        <?php else: ?>
            <div class="divide-y divide-slate-100 rounded-xl border border-slate-200 overflow-hidden text-xs">
                <?php foreach ($configuredPrerequisites as $p): ?>
                    <div class="p-4 bg-white flex items-center justify-between gap-4 hover:bg-slate-50/50 transition">
                        <div class="flex items-center gap-3">
                            <span class="p-1.5 rounded-lg bg-amber-50 text-amber-700 font-bold">🔒</span>
                            <div>
                                <div class="flex items-center gap-2">
                                    <h4 class="font-bold text-slate-900"><?= htmlspecialchars($p['title']) ?></h4>
                                    <span class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider">
                                        (<?= htmlspecialchars(ucfirst(str_replace('_', ' ', $p['type']))) ?>)
                                    </span>
                                </div>
                                <p class="text-[11px] text-slate-500 mt-0.5">Condition: Must reach 100% / full completion</p>
                            </div>
                        </div>

                        <form action="/teacher/quizzes/<?= (int)$quiz->id ?>/prerequisites/<?= (int)$p['id'] ?>/delete" 
                              method="POST" 
                              onsubmit="return confirm('Remove prerequisite &quot;<?= htmlspecialchars(addslashes($p['title']), ENT_QUOTES) ?>&quot;?');">
                            <?= csrf_field() ?>
                            <button type="submit" class="text-xs font-semibold text-rose-600 hover:text-rose-700 transition">
                                Remove
                            </button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Add Prerequisite Modal -->
    <div id="quizPrereqModal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-slate-900/50 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-xl border border-slate-200 w-full max-w-md overflow-hidden">
            <div class="flex items-center justify-between p-5 border-b border-slate-100 bg-slate-50/50">
                <div>
                    <h3 class="text-sm font-bold text-slate-900">Require Prerequisite Activity</h3>
                    <p class="text-[11px] text-slate-500 mt-0.5">Select a prior activity from this class-subject</p>
                </div>
                <button type="button" onclick="closeAddQuizPrereqModal()" class="text-slate-400 hover:text-slate-600 p-1 rounded-lg">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <form action="/teacher/quizzes/<?= (int)$quiz->id ?>/prerequisites" method="POST" class="p-6 space-y-4">
                <?= csrf_field() ?>
                <input type="hidden" name="requirement_type" value="completion">

                <div>
                    <label for="quiz_prereq_type" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                        Prerequisite Type <span class="text-rose-500">*</span>
                    </label>
                    <select name="prerequisite_type" id="quiz_prereq_type" required onchange="onQuizPrereqTypeChange()"
                            class="w-full rounded-xl border border-slate-300 text-xs focus:border-emerald-500 focus:ring-emerald-500 bg-slate-50 py-2.5 px-3 font-medium text-slate-900 transition">
                        <option value="document_section">Document Section</option>
                        <option value="quiz">Another Quiz</option>
                    </select>
                </div>

                <div>
                    <label for="quiz_prereq_id" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                        Select Activity <span class="text-rose-500">*</span>
                    </label>
                    <select name="prerequisite_id" id="quiz_prereq_id" required
                            class="w-full rounded-xl border border-slate-300 text-xs focus:border-emerald-500 focus:ring-emerald-500 bg-slate-50 py-2.5 px-3 font-medium text-slate-900 transition">
                        <!-- Populated dynamically via JS -->
                    </select>
                </div>

                <div class="p-3 bg-amber-50 rounded-xl border border-amber-200 text-[11px] text-amber-800 space-y-1 leading-relaxed">
                    <p class="font-bold">Rule Note:</p>
                    <p>Students will be blocked from starting this quiz until all required activities are fully completed. Circular dependency loops are strictly prevented.</p>
                </div>

                <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                    <button type="button" onclick="closeAddQuizPrereqModal()" 
                            class="px-4 py-2 text-xs font-bold text-slate-600 hover:text-slate-800 rounded-xl transition">
                        Cancel
                    </button>
                    <button type="submit" 
                            class="px-4 py-2 text-xs font-bold bg-amber-600 hover:bg-amber-700 text-white rounded-xl shadow-xs transition">
                        Save Prerequisite
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
    const availableSections = <?= json_encode($availableSections ?? []) ?>;
    const availableQuizzes = <?= json_encode($availableQuizzes ?? []) ?>;

    function onQuizPrereqTypeChange() {
        const type = document.getElementById('quiz_prereq_type').value;
        const select = document.getElementById('quiz_prereq_id');
        select.innerHTML = '';

        const list = (type === 'document_section') ? availableSections : availableQuizzes;
        if (list.length === 0) {
            select.innerHTML = '<option value="">No available ' + (type === 'document_section' ? 'sections' : 'quizzes') + ' found</option>';
        } else {
            list.forEach(item => {
                const opt = document.createElement('option');
                opt.value = item.id;
                opt.innerHTML = item.title;
                select.appendChild(opt);
            });
        }
    }

    function openAddQuizPrereqModal() {
        onQuizPrereqTypeChange();
        document.getElementById('quizPrereqModal').classList.remove('hidden');
    }

    function closeAddQuizPrereqModal() {
        document.getElementById('quizPrereqModal').classList.add('hidden');
    }

    document.getElementById('quizPrereqModal')?.addEventListener('click', function(e) {
        if (e.target === this) {
            closeAddQuizPrereqModal();
        }
    });
    </script>
</div>
