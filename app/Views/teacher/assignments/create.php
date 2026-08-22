<div class="max-w-4xl mx-auto space-y-6">
    <!-- Header Card -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <!-- Breadcrumbs -->
                <nav class="flex items-center gap-2 text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">
                    <a href="/teacher/dashboard" class="text-slate-400 hover:text-emerald-600 transition">Faculty Portal</a>
                    <span class="text-slate-300">/</span>
                    <a href="/teacher/assignments" class="text-slate-400 hover:text-emerald-600 transition">Assignments & Homework</a>
                    <span class="text-slate-300">/</span>
                    <span class="text-slate-700">Create Assignment</span>
                </nav>
                <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight leading-tight">
                    Create Coursework Assignment
                </h1>
                <p class="text-xs text-slate-500 mt-1">
                    Author homework tasks, essay prompts, or projects with deadlines, scoring rubrics, and reference attachments.
                </p>
            </div>

            <div>
                <?php $this->include('components/button', [
                    'label' => 'Back to Assignments',
                    'variant' => 'secondary',
                    'href' => '/teacher/assignments' . ($presetClassSubjectId ? '?class_subject_id=' . (int)$presetClassSubjectId : ''),
                    'icon' => '<svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>'
                ]); ?>
            </div>
        </div>
    </div>

    <!-- Form Container -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6 sm:p-8">
        <form action="/teacher/assignments/create" method="POST" enctype="multipart/form-data" class="space-y-6">
            <?= csrf_field() ?>

            <!-- Allocation Grid (Subject & Term) -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Class Subject -->
                <div>
                    <label for="class_subject_id" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                        Target Subject & Class <span class="text-rose-500">*</span>
                    </label>
                    <select name="class_subject_id" id="class_subject_id" required
                            class="w-full rounded-xl border border-slate-300 text-xs focus:border-emerald-500 focus:ring-emerald-500 bg-slate-50 py-2.5 px-3 font-semibold text-slate-900 transition">
                        <option value="">-- Select Class & Subject Allocation --</option>
                        <?php foreach ($classSubjects as $cs): ?>
                            <?php 
                                $sName = $cs->subject?->name ?? ($cs->subjectName ?? 'Subject');
                                $cName = $cs->schoolClass?->name ?? ($cs->className ?? 'Class');
                                $sArm = $cs->schoolClass?->sectionArm ?? ($cs->sectionArm ?? '');
                            ?>
                            <option value="<?= (int)$cs->id ?>" <?= (int)$cs->id === (int)$presetClassSubjectId || (int)old('class_subject_id') === (int)$cs->id ? 'selected' : '' ?>>
                                <?= htmlspecialchars($sName) ?> — <?= htmlspecialchars($cName) ?><?= !empty($sArm) ? ' (' . htmlspecialchars($sArm) . ')' : '' ?>
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
                        <option value="">-- Select Academic Term --</option>
                        <?php foreach ($terms as $term): ?>
                            <option value="<?= (int)$term->id ?>" <?= (int)$term->id === (int)old('term_id') || (empty(old('term_id')) && $term->isCurrent) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($term->name) ?> <?= $term->isCurrent ? '(Current Term)' : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- Title & Topic Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="sm:col-span-2">
                    <label for="title" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                        Assignment Title <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" name="title" id="title" required maxlength="200"
                           value="<?= htmlspecialchars((string)old('title', '')) ?>"
                           placeholder="e.g. Midterm Essay: Photosynthesis & Cellular Respiration"
                           class="w-full rounded-xl border border-slate-300 text-xs focus:border-emerald-500 focus:ring-emerald-500 bg-slate-50 py-2.5 px-3 font-medium text-slate-900 transition">
                </div>

                <div>
                    <label for="topic" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                        Topic / Module (Optional)
                    </label>
                    <input type="text" name="topic" id="topic" maxlength="100"
                           value="<?= htmlspecialchars((string)old('topic', '')) ?>"
                           placeholder="e.g. Unit 3: Cell Biology"
                           class="w-full rounded-xl border border-slate-300 text-xs focus:border-emerald-500 focus:ring-emerald-500 bg-slate-50 py-2.5 px-3 font-medium text-slate-900 transition">
                </div>
            </div>

            <!-- Parameters Grid (Due Date & Max Score) -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Due Date/Time -->
                <div>
                    <label for="due_at" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                        Submission Deadline (Due Date & Time) <span class="text-rose-500">*</span>
                    </label>
                    <input type="datetime-local" name="due_at" id="due_at" required
                           value="<?= htmlspecialchars((string)old('due_at', '')) ?>"
                           class="w-full rounded-xl border border-slate-300 text-xs focus:border-emerald-500 focus:ring-emerald-500 bg-slate-50 py-2.5 px-3 font-semibold text-slate-900 transition">
                    <p class="text-[11px] text-slate-400 mt-1">Submissions received after this timestamp will automatically be marked as Late.</p>
                </div>

                <!-- Max Score -->
                <div>
                    <label for="max_score" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                        Maximum Score (Points) <span class="text-rose-500">*</span>
                    </label>
                    <div class="relative">
                        <input type="number" step="0.5" min="1" max="1000" name="max_score" id="max_score" required
                               value="<?= htmlspecialchars((string)old('max_score', '100')) ?>"
                               class="w-full rounded-xl border border-slate-300 text-xs focus:border-emerald-500 focus:ring-emerald-500 bg-slate-50 py-2.5 pl-3 pr-12 font-bold text-slate-900 transition">
                        <span class="absolute right-3.5 top-1/2 -translate-y-1/2 text-xs font-bold text-slate-400 pointer-events-none">PTS</span>
                    </div>
                    <p class="text-[11px] text-slate-400 mt-1">Total score limit for student evaluation and continuous assessment records.</p>
                </div>
            </div>

            <!-- Instructions Textarea -->
            <div>
                <label for="instructions" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                    Detailed Instructions & Guidelines <span class="text-rose-500">*</span>
                </label>
                <textarea name="instructions" id="instructions" rows="6" required
                          placeholder="Provide detailed problem statements, formatting criteria, research instructions, or grading rubrics..."
                          class="w-full rounded-xl border border-slate-300 text-xs focus:border-emerald-500 focus:ring-emerald-500 bg-slate-50 py-2.5 px-3 font-medium text-slate-900 leading-relaxed transition"><?= htmlspecialchars((string)old('instructions', '')) ?></textarea>
            </div>

            <!-- Reference Attachment Dropzone -->
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                    Reference File Attachment (Optional)
                </label>
                <div id="dropzone-container" 
                     class="border-2 border-dashed border-slate-300 hover:border-emerald-500 bg-slate-50/70 hover:bg-emerald-50/20 rounded-2xl p-6 text-center transition cursor-pointer relative"
                     onclick="document.getElementById('attachment').click()">
                    
                    <input type="file" name="attachment" id="attachment" class="sr-only" onchange="handleFileSelect(this)">
                    
                    <div id="dropzone-empty">
                        <div class="w-12 h-12 rounded-xl bg-white border border-slate-200 shadow-xs flex items-center justify-center mx-auto mb-3 text-slate-400">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                            </svg>
                        </div>
                        <p class="text-xs font-bold text-slate-800">
                            Click to upload <span class="font-normal text-slate-500">or drag and drop reference document</span>
                        </p>
                        <p class="text-[11px] text-slate-400 mt-1">PDF, DOCX, XLSX, PPTX, JPG, PNG, or ZIP (Up to 25 MB)</p>
                    </div>

                    <div id="dropzone-selected" class="hidden">
                        <div class="inline-flex items-center gap-3 bg-white p-3 rounded-xl border border-emerald-200 shadow-xs max-w-full">
                            <div class="w-8 h-8 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center flex-shrink-0">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                            </div>
                            <div class="text-left min-w-0 pr-2">
                                <p id="file-name-preview" class="text-xs font-bold text-slate-900 truncate">document.pdf</p>
                                <p id="file-size-preview" class="text-[10px] font-mono text-slate-500">0 KB</p>
                            </div>
                            <button type="button" onclick="event.stopPropagation(); clearSelectedFile();" 
                                    class="p-1 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition"
                                    title="Remove file">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Publication Status Option Cards -->
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-2">
                    Initial Publishing Status <span class="text-rose-500">*</span>
                </label>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <label class="relative flex items-start gap-3 p-4 rounded-xl border border-slate-200 cursor-pointer hover:bg-slate-50 transition has-[:checked]:border-emerald-500 has-[:checked]:bg-emerald-50/40">
                        <input type="radio" name="status" value="published" class="mt-0.5 text-emerald-600 focus:ring-emerald-500" 
                               <?= old('status', 'published') === 'published' ? 'checked' : '' ?>>
                        <div>
                            <span class="block text-xs font-bold text-slate-900">Publish Immediately</span>
                            <span class="block text-[11px] text-slate-500 mt-0.5">Visible right away to all students enrolled in the class with deadline timers active.</span>
                        </div>
                    </label>

                    <label class="relative flex items-start gap-3 p-4 rounded-xl border border-slate-200 cursor-pointer hover:bg-slate-50 transition has-[:checked]:border-emerald-500 has-[:checked]:bg-emerald-50/40">
                        <input type="radio" name="status" value="draft" class="mt-0.5 text-emerald-600 focus:ring-emerald-500"
                               <?= old('status') === 'draft' ? 'checked' : '' ?>>
                        <div>
                            <span class="block text-xs font-bold text-slate-900">Save as Draft</span>
                            <span class="block text-[11px] text-slate-500 mt-0.5">Hidden from student view. You can review, edit, and publish when ready.</span>
                        </div>
                    </label>
                </div>
            </div>

            <!-- Form Action Footer -->
            <div class="pt-6 border-t border-slate-100 flex items-center justify-end gap-3">
                <?php $this->include('components/button', [
                    'label' => 'Cancel',
                    'variant' => 'secondary',
                    'href' => '/teacher/assignments' . ($presetClassSubjectId ? '?class_subject_id=' . (int)$presetClassSubjectId : '')
                ]); ?>

                <?php $this->include('components/button', [
                    'label' => 'Publish Assignment',
                    'variant' => 'primary',
                    'type' => 'submit',
                    'icon' => '<svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>'
                ]); ?>
            </div>
        </form>
    </div>
</div>

<script>
function handleFileSelect(input) {
    if (input.files && input.files[0]) {
        const file = input.files[0];
        document.getElementById('file-name-preview').textContent = file.name;
        
        let sizeFormatted = (file.size / 1024).toFixed(1) + ' KB';
        if (file.size >= 1048576) {
            sizeFormatted = (file.size / 1048576).toFixed(2) + ' MB';
        }
        document.getElementById('file-size-preview').textContent = sizeFormatted;
        
        document.getElementById('dropzone-empty').classList.add('hidden');
        document.getElementById('dropzone-selected').classList.remove('hidden');
    }
}

function clearSelectedFile() {
    const input = document.getElementById('attachment');
    input.value = '';
    document.getElementById('dropzone-selected').classList.add('hidden');
    document.getElementById('dropzone-empty').classList.remove('hidden');
}
</script>

