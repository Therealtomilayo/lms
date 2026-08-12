<div class="max-w-4xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-slate-900 tracking-tight">Edit Assignment</h2>
            <p class="text-sm text-slate-500 mt-1">Update coursework specifications, instructions, or deadline.</p>
        </div>
        <a href="/teacher/assignments" class="inline-flex items-center gap-2 text-sm font-semibold text-slate-600 hover:text-slate-900">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Back to Assignments
        </a>
    </div>

    <form method="POST" action="/teacher/assignments/<?= $assignment->id ?>/edit" enctype="multipart/form-data" class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 sm:p-8 space-y-6">
        <?= csrf_field() ?>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Subject (Read-only) -->
            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Subject & Class</label>
                <input type="text" disabled value="<?= e($assignment->classSubject?->subjectName ?? 'Subject') ?> — <?= e($assignment->classSubject?->className ?? 'Class') ?>"
                       class="w-full rounded-xl border-slate-200 text-sm bg-slate-100 text-slate-600 py-2.5 px-3 border font-medium cursor-not-allowed">
            </div>

            <!-- Term -->
            <div>
                <label for="term_id" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Academic Term <span class="text-rose-500">*</span></label>
                <select name="term_id" id="term_id" required
                        class="w-full rounded-xl border-slate-300 text-sm focus:border-brand-500 focus:ring-brand-500 bg-slate-50 py-2.5 px-3 border font-medium">
                    <?php foreach ($terms as $term): ?>
                        <option value="<?= $term->id ?>" <?= $term->id === $assignment->termId ? 'selected' : '' ?>>
                            <?= e($term->name) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Topic -->
            <div>
                <label for="topic" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Topic / Module (Optional)</label>
                <input type="text" name="topic" id="topic" value="<?= e($assignment->topic ?? '') ?>" placeholder="e.g. Unit 3: Linear Equations"
                       class="w-full rounded-xl border-slate-300 text-sm focus:border-brand-500 focus:ring-brand-500 bg-slate-50 py-2.5 px-3 border font-medium">
            </div>

            <!-- Due Date/Time -->
            <div>
                <label for="due_at" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Due Date & Time <span class="text-rose-500">*</span></label>
                <input type="datetime-local" name="due_at" id="due_at" required value="<?= date('Y-m-d\TH:i', strtotime($assignment->dueAt)) ?>"
                       class="w-full rounded-xl border-slate-300 text-sm focus:border-brand-500 focus:ring-brand-500 bg-slate-50 py-2.5 px-3 border font-medium">
            </div>

            <!-- Max Score -->
            <div>
                <label for="max_score" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Maximum Score <span class="text-rose-500">*</span></label>
                <input type="number" step="0.5" min="1" max="1000" name="max_score" id="max_score" value="<?= e((string)$assignment->maxScore) ?>" required
                       class="w-full rounded-xl border-slate-300 text-sm focus:border-brand-500 focus:ring-brand-500 bg-slate-50 py-2.5 px-3 border font-medium">
            </div>
        </div>

        <!-- Title -->
        <div>
            <label for="title" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Assignment Title <span class="text-rose-500">*</span></label>
            <input type="text" name="title" id="title" required value="<?= e($assignment->title) ?>"
                   class="w-full rounded-xl border-slate-300 text-sm focus:border-brand-500 focus:ring-brand-500 bg-slate-50 py-2.5 px-3 border font-medium">
        </div>

        <!-- Instructions -->
        <div>
            <label for="instructions" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Detailed Instructions <span class="text-rose-500">*</span></label>
            <textarea name="instructions" id="instructions" rows="6" required
                      class="w-full rounded-xl border-slate-300 text-sm focus:border-brand-500 focus:ring-brand-500 bg-slate-50 py-2.5 px-3 border font-medium"><?= e($assignment->instructions) ?></textarea>
        </div>

        <!-- File Attachment -->
        <div>
            <label for="attachment" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Replace Attachment (Optional)</label>
            <?php if ($assignment->file): ?>
                <div class="mb-2 p-3 bg-slate-50 rounded-xl border border-slate-200 flex items-center justify-between gap-2">
                    <div class="flex items-center gap-2 min-w-0">
                        <svg class="w-4 h-4 text-brand-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                        <span class="text-xs font-medium text-slate-700 truncate"><?= e($assignment->file->originalName) ?></span>
                    </div>
                    <a href="/files/<?= $assignment->file->id ?>/download" class="text-xs font-medium text-brand-600 hover:underline">Download</a>
                </div>
            <?php endif; ?>
            <input type="file" name="attachment" id="attachment"
                   class="w-full text-xs text-slate-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-brand-50 file:text-brand-700 hover:file:bg-brand-100">
        </div>

        <!-- Publication Status -->
        <div>
            <label for="status" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Status</label>
            <select name="status" id="status"
                    class="w-full max-w-xs rounded-xl border-slate-300 text-sm focus:border-brand-500 focus:ring-brand-500 bg-slate-50 py-2.5 px-3 border font-medium">
                <option value="published" <?= $assignment->status === 'published' ? 'selected' : '' ?>>Published (Live)</option>
                <option value="draft" <?= $assignment->status === 'draft' ? 'selected' : '' ?>>Draft (Hidden)</option>
                <option value="archived" <?= $assignment->status === 'archived' ? 'selected' : '' ?>>Archived</option>
            </select>
        </div>

        <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
            <a href="/teacher/assignments" class="px-5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-semibold rounded-xl transition">
                Cancel
            </a>
            <button type="submit" class="px-6 py-2.5 bg-brand-600 hover:bg-brand-700 text-white text-sm font-semibold rounded-xl shadow-sm transition">
                Save Changes
            </button>
        </div>
    </form>
</div>
