<div class="max-w-4xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-slate-900 tracking-tight">Create Coursework Assignment</h2>
            <p class="text-sm text-slate-500 mt-1">Author homework, essay questions, or projects with deadlines and score limits.</p>
        </div>
        <a href="/teacher/assignments" class="inline-flex items-center gap-2 text-sm font-semibold text-slate-600 hover:text-slate-900">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Back to Assignments
        </a>
    </div>

    <form method="POST" action="/teacher/assignments/create" enctype="multipart/form-data" class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 sm:p-8 space-y-6">
        <?= csrf_field() ?>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Class Subject -->
            <div>
                <label for="class_subject_id" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Subject & Class <span class="text-rose-500">*</span></label>
                <select name="class_subject_id" id="class_subject_id" required
                        class="w-full rounded-xl border-slate-300 text-sm focus:border-brand-500 focus:ring-brand-500 bg-slate-50 py-2.5 px-3 border font-medium">
                    <option value="">-- Select Subject & Class --</option>
                    <?php foreach ($classSubjects as $cs): ?>
                        <option value="<?= $cs->id ?>" <?= $cs->id === $presetClassSubjectId ? 'selected' : '' ?>>
                            <?= e($cs->subject?->name ?? 'Subject') ?> — <?= e($cs->schoolClass?->name ?? 'Class') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Term -->
            <div>
                <label for="term_id" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Academic Term <span class="text-rose-500">*</span></label>
                <select name="term_id" id="term_id" required
                        class="w-full rounded-xl border-slate-300 text-sm focus:border-brand-500 focus:ring-brand-500 bg-slate-50 py-2.5 px-3 border font-medium">
                    <option value="">-- Select Academic Term --</option>
                    <?php foreach ($terms as $term): ?>
                        <option value="<?= $term->id ?>" <?= $term->isCurrent ? 'selected' : '' ?>>
                            <?= e($term->name) ?> <?= $term->isCurrent ? '(Current)' : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Topic -->
            <div>
                <label for="topic" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Topic / Module (Optional)</label>
                <input type="text" name="topic" id="topic" placeholder="e.g. Unit 3: Linear Equations"
                       class="w-full rounded-xl border-slate-300 text-sm focus:border-brand-500 focus:ring-brand-500 bg-slate-50 py-2.5 px-3 border font-medium">
            </div>

            <!-- Due Date/Time -->
            <div>
                <label for="due_at" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Due Date & Time <span class="text-rose-500">*</span></label>
                <input type="datetime-local" name="due_at" id="due_at" required
                       class="w-full rounded-xl border-slate-300 text-sm focus:border-brand-500 focus:ring-brand-500 bg-slate-50 py-2.5 px-3 border font-medium">
            </div>

            <!-- Max Score -->
            <div>
                <label for="max_score" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Maximum Score <span class="text-rose-500">*</span></label>
                <input type="number" step="0.5" min="1" max="1000" name="max_score" id="max_score" value="100" required
                       class="w-full rounded-xl border-slate-300 text-sm focus:border-brand-500 focus:ring-brand-500 bg-slate-50 py-2.5 px-3 border font-medium">
            </div>
        </div>

        <!-- Title -->
        <div>
            <label for="title" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Assignment Title <span class="text-rose-500">*</span></label>
            <input type="text" name="title" id="title" required placeholder="e.g. Midterm Essay: Photosynthesis & Cellular Respiration"
                   class="w-full rounded-xl border-slate-300 text-sm focus:border-brand-500 focus:ring-brand-500 bg-slate-50 py-2.5 px-3 border font-medium">
        </div>

        <!-- Instructions -->
        <div>
            <label for="instructions" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Detailed Instructions <span class="text-rose-500">*</span></label>
            <textarea name="instructions" id="instructions" rows="6" required placeholder="Provide clear task objectives, formatting requirements, and guidelines for students..."
                      class="w-full rounded-xl border-slate-300 text-sm focus:border-brand-500 focus:ring-brand-500 bg-slate-50 py-2.5 px-3 border font-medium"></textarea>
        </div>

        <!-- File Attachment -->
        <div>
            <label for="attachment" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Reference Attachment (Optional)</label>
            <input type="file" name="attachment" id="attachment"
                   class="w-full text-xs text-slate-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-brand-50 file:text-brand-700 hover:file:bg-brand-100">
            <p class="text-[11px] text-slate-400 mt-1">Upload PDF question sheets, rubrics, datasets, or reference docs (up to 25MB).</p>
        </div>

        <!-- Publication Status -->
        <div>
            <label for="status" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Status</label>
            <select name="status" id="status"
                    class="w-full max-w-xs rounded-xl border-slate-300 text-sm focus:border-brand-500 focus:ring-brand-500 bg-slate-50 py-2.5 px-3 border font-medium">
                <option value="published" selected>Published (Visible immediately to enrolled students)</option>
                <option value="draft">Draft (Hidden from students)</option>
            </select>
        </div>

        <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
            <a href="/teacher/assignments" class="px-5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-semibold rounded-xl transition">
                Cancel
            </a>
            <button type="submit" class="px-6 py-2.5 bg-brand-600 hover:bg-brand-700 text-white text-sm font-semibold rounded-xl shadow-sm transition">
                Publish Assignment
            </button>
        </div>
    </form>
</div>
