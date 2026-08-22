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
                    <span class="text-slate-700">Create Quiz</span>
                </nav>
                <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight leading-tight">
                    Create CBT Assessment
                </h1>
                <p class="text-xs text-slate-500 mt-1">
                    Configure CBT test parameters, time limits, attempt limits, passing criteria, and target classroom cohort.
                </p>
            </div>

            <div class="flex items-center gap-2.5">
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
        <form action="/teacher/quizzes/create" method="POST" class="space-y-6">
            <?= csrf_field() ?>

            <!-- Title -->
            <div>
                <label for="title" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                    Quiz Title / Assessment Name <span class="text-rose-500">*</span>
                </label>
                <input type="text" name="title" id="title" required maxlength="200"
                       value="<?= htmlspecialchars((string)old('title', '')) ?>"
                       placeholder="e.g. Mid-Term Continuous Assessment CBT Test"
                       class="w-full rounded-xl border border-slate-300 text-xs focus:border-emerald-500 focus:ring-emerald-500 bg-slate-50 py-2.5 px-3 font-medium text-slate-900 transition">
            </div>

            <!-- Allocation Grid (Class-Subject & Term) -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Class Subject -->
                <div>
                    <label for="class_subject_id" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                        Target Class & Subject <span class="text-rose-500">*</span>
                    </label>
                    <select name="class_subject_id" id="class_subject_id" required
                            class="w-full rounded-xl border border-slate-300 text-xs focus:border-emerald-500 focus:ring-emerald-500 bg-slate-50 py-2.5 px-3 font-semibold text-slate-900 transition">
                        <option value="">-- Select Allocated Class & Subject --</option>
                        <?php foreach ($classSubjects as $cs): ?>
                            <?php 
                                $sName = $cs->subject?->name ?? 'Subject';
                                $cName = $cs->schoolClass?->name ?? 'Class';
                                $arm = $cs->schoolClass?->sectionArm ?? '';
                            ?>
                            <option value="<?= (int)$cs->id ?>" <?= (int)old('class_subject_id') === (int)$cs->id ? 'selected' : '' ?>>
                                <?= htmlspecialchars($sName) ?> — <?= htmlspecialchars($cName) ?><?= !empty($arm) ? ' (' . htmlspecialchars($arm) . ')' : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Academic Term -->
                <div>
                    <label for="term_id" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                        Academic Term <span class="text-rose-500">*</span>
                    </label>
                    <select name="term_id" id="term_id" required
                            class="w-full rounded-xl border border-slate-300 text-xs focus:border-emerald-500 focus:ring-emerald-500 bg-slate-50 py-2.5 px-3 font-semibold text-slate-900 transition">
                        <?php foreach ($terms as $t): ?>
                            <option value="<?= (int)$t->id ?>" <?= (int)old('term_id', $currentTerm?->id ?? 0) === (int)$t->id ? 'selected' : '' ?>>
                                <?= htmlspecialchars($t->name) ?> <?= $t->isCurrent ? '(Current Term)' : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- CBT Exam Parameters Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <!-- Time Limit -->
                <div>
                    <label for="time_limit_minutes" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                        Time Limit (Minutes)
                    </label>
                    <div class="relative">
                        <input type="number" min="0" max="300" name="time_limit_minutes" id="time_limit_minutes"
                               value="<?= htmlspecialchars((string)old('time_limit_minutes', '30')) ?>"
                               placeholder="30"
                               class="w-full rounded-xl border border-slate-300 text-xs focus:border-emerald-500 focus:ring-emerald-500 bg-slate-50 py-2.5 pl-3 pr-14 font-bold text-slate-900 transition">
                        <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs font-bold text-slate-400 pointer-events-none">MINS</span>
                    </div>
                    <p class="text-[11px] text-slate-400 mt-1">Set to 0 for an untimed assessment without a countdown clock.</p>
                </div>

                <!-- Max Attempts Allowed -->
                <div>
                    <label for="max_attempts" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                        Maximum Attempts Allowed <span class="text-rose-500">*</span>
                    </label>
                    <input type="number" min="1" max="10" name="max_attempts" id="max_attempts" required
                           value="<?= htmlspecialchars((string)old('max_attempts', '1')) ?>"
                           class="w-full rounded-xl border border-slate-300 text-xs focus:border-emerald-500 focus:ring-emerald-500 bg-slate-50 py-2.5 px-3 font-bold text-slate-900 transition">
                    <p class="text-[11px] text-slate-400 mt-1">Number of test sittings each enrolled student is permitted.</p>
                </div>
            </div>

            <!-- Instructions Textarea -->
            <div>
                <label for="instructions" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                    Instructions & Assessment Guidelines
                </label>
                <textarea name="instructions" id="instructions" rows="4"
                          placeholder="Provide guidelines, calculator policies, honesty declarations, or instructions displayed to students before starting the exam..."
                          class="w-full rounded-xl border border-slate-300 text-xs focus:border-emerald-500 focus:ring-emerald-500 bg-slate-50 py-2.5 px-3 font-medium text-slate-900 leading-relaxed transition"><?= htmlspecialchars((string)old('instructions', '')) ?></textarea>
            </div>

            <!-- Form Actions Footer -->
            <div class="pt-6 border-t border-slate-100 flex items-center justify-end gap-3">
                <?php $this->include('components/button', [
                    'label' => 'Cancel',
                    'variant' => 'secondary',
                    'href' => '/teacher/quizzes'
                ]); ?>

                <?php $this->include('components/button', [
                    'label' => 'Next: Add Questions',
                    'variant' => 'primary',
                    'type' => 'submit',
                    'icon' => '<svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>'
                ]); ?>
            </div>
        </form>
    </div>
</div>
