<div class="max-w-3xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-slate-900 tracking-tight">Create Learning Material</h2>
            <p class="text-sm text-slate-500 mt-1">Upload lecture documents, add detailed lesson notes, or attach video links.</p>
        </div>
        <a href="/teacher/content<?= $presetClassSubjectId ? '?class_subject_id=' . $presetClassSubjectId : '' ?>" 
           class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50 shadow-sm transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Back to List
        </a>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 sm:p-8">
        <form action="/teacher/content/create" method="POST" enctype="multipart/form-data" class="space-y-6">
            <?= csrf_field() ?>

            <!-- Class-Subject Selector -->
            <div>
                <label for="class_subject_id" class="block text-sm font-semibold text-slate-700 mb-1.5">
                    Target Class & Subject <span class="text-rose-500">*</span>
                </label>
                <select name="class_subject_id" id="class_subject_id" required
                        class="w-full rounded-xl border-slate-300 text-sm focus:border-brand-500 focus:ring-brand-500 bg-slate-50 py-2.5 px-3 border font-medium">
                    <option value="">-- Select Class & Subject --</option>
                    <?php foreach ($classSubjects as $cs): ?>
                        <option value="<?= $cs->id ?>" <?= $cs->id === $presetClassSubjectId || (int)old('class_subject_id') === $cs->id ? 'selected' : '' ?>>
                            <?= e($cs->subject?->name ?? 'Subject') ?> — <?= e($cs->schoolClass?->name ?? 'Class') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Title & Topic Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="sm:col-span-2">
                    <label for="title" class="block text-sm font-semibold text-slate-700 mb-1.5">
                        Lesson / Material Title <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" name="title" id="title" required maxlength="200"
                           value="<?= e(old('title')) ?>"
                           placeholder="e.g. Week 3: Cell Division and Mitosis"
                           class="w-full rounded-xl border-slate-300 text-sm focus:border-brand-500 focus:ring-brand-500 bg-slate-50 py-2.5 px-3 border">
                </div>

                <div>
                    <label for="topic" class="block text-sm font-semibold text-slate-700 mb-1.5">
                        Topic / Module Name
                    </label>
                    <input type="text" name="topic" id="topic" maxlength="100"
                           value="<?= e(old('topic')) ?>"
                           placeholder="e.g. Cell Biology"
                           class="w-full rounded-xl border-slate-300 text-sm focus:border-brand-500 focus:ring-brand-500 bg-slate-50 py-2.5 px-3 border">
                </div>
            </div>

            <!-- Content Type -->
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">
                    Material Type <span class="text-rose-500">*</span>
                </label>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                    <label class="relative flex flex-col items-center p-3 rounded-xl border border-slate-200 cursor-pointer hover:bg-slate-50 transition has-[:checked]:border-brand-500 has-[:checked]:bg-brand-50/40">
                        <input type="radio" name="type" value="note" class="sr-only" <?= old('type', 'note') === 'note' ? 'checked' : '' ?> onchange="updateTypeFields('note')">
                        <svg class="w-5 h-5 text-blue-500 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        <span class="text-xs font-semibold text-slate-800">Lesson Note</span>
                    </label>

                    <label class="relative flex flex-col items-center p-3 rounded-xl border border-slate-200 cursor-pointer hover:bg-slate-50 transition has-[:checked]:border-brand-500 has-[:checked]:bg-brand-50/40">
                        <input type="radio" name="type" value="document" class="sr-only" <?= old('type') === 'document' ? 'checked' : '' ?> onchange="updateTypeFields('document')">
                        <svg class="w-5 h-5 text-emerald-500 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                        <span class="text-xs font-semibold text-slate-800">Document / PDF</span>
                    </label>

                    <label class="relative flex flex-col items-center p-3 rounded-xl border border-slate-200 cursor-pointer hover:bg-slate-50 transition has-[:checked]:border-brand-500 has-[:checked]:bg-brand-50/40">
                        <input type="radio" name="type" value="video" class="sr-only" <?= old('type') === 'video' ? 'checked' : '' ?> onchange="updateTypeFields('video')">
                        <svg class="w-5 h-5 text-purple-500 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                        <span class="text-xs font-semibold text-slate-800">Video Lesson</span>
                    </label>

                    <label class="relative flex flex-col items-center p-3 rounded-xl border border-slate-200 cursor-pointer hover:bg-slate-50 transition has-[:checked]:border-brand-500 has-[:checked]:bg-brand-50/40">
                        <input type="radio" name="type" value="link" class="sr-only" <?= old('type') === 'link' ? 'checked' : '' ?> onchange="updateTypeFields('link')">
                        <svg class="w-5 h-5 text-amber-500 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                        <span class="text-xs font-semibold text-slate-800">Web Resource</span>
                    </label>
                </div>
            </div>

            <!-- Description / Body Text -->
            <div>
                <label for="description" class="block text-sm font-semibold text-slate-700 mb-1.5">
                    Lesson Description / Summary / Notes
                </label>
                <textarea name="description" id="description" rows="5"
                          placeholder="Provide detailed lecture notes, study instructions, or context for the students..."
                          class="w-full rounded-xl border-slate-300 text-sm focus:border-brand-500 focus:ring-brand-500 bg-slate-50 py-2.5 px-3 border leading-relaxed"><?= e(old('description')) ?></textarea>
            </div>

            <!-- External URL (for link / video) -->
            <div id="url-field-container">
                <label for="external_url" class="block text-sm font-semibold text-slate-700 mb-1.5">
                    External Resource URL <span class="text-slate-400 font-normal">(YouTube, Vimeo, Google Drive, or Web Link)</span>
                </label>
                <input type="url" name="external_url" id="external_url"
                       value="<?= e(old('external_url')) ?>"
                       placeholder="https://www.youtube.com/watch?v=..."
                       class="w-full rounded-xl border-slate-300 text-sm focus:border-brand-500 focus:ring-brand-500 bg-slate-50 py-2.5 px-3 border">
            </div>

            <!-- File Upload -->
            <div>
                <label for="attachment" class="block text-sm font-semibold text-slate-700 mb-1.5">
                    File Attachment <span class="text-slate-400 font-normal">(PDF, Office docs, slides, max 25MB)</span>
                </label>
                <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-slate-300 border-dashed rounded-xl bg-slate-50 hover:bg-slate-100/60 transition cursor-pointer" onclick="document.getElementById('attachment').click()">
                    <div class="space-y-1 text-center">
                        <svg class="mx-auto h-10 w-10 text-slate-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                            <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                        <div class="flex text-xs text-slate-600 justify-center">
                            <label for="attachment" class="relative cursor-pointer font-semibold text-brand-600 hover:text-brand-500">
                                <span>Select a file</span>
                            </label>
                            <span class="pl-1 text-slate-500">or drag and drop</span>
                        </div>
                        <p class="text-[11px] text-slate-400">PDF, DOCX, PPTX, XLSX, MP4, MP3 up to 25MB</p>
                        <p id="file-chosen-name" class="text-xs font-semibold text-brand-600 pt-1"></p>
                    </div>
                </div>
                <input id="attachment" name="attachment" type="file" class="sr-only" onchange="document.getElementById('file-chosen-name').textContent = this.files[0] ? this.files[0].name + ' (' + (this.files[0].size/1024/1024).toFixed(2) + ' MB)' : ''">
            </div>

            <!-- Publish Now Checkbox -->
            <div class="flex items-center gap-3 p-4 bg-slate-50 rounded-xl border border-slate-200">
                <input type="checkbox" name="publish_now" id="publish_now" value="1" <?= old('publish_now', '1') ? 'checked' : '' ?>
                       class="h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                <div>
                    <label for="publish_now" class="text-sm font-semibold text-slate-800 cursor-pointer">
                        Publish immediately to enrolled students
                    </label>
                    <p class="text-xs text-slate-500">When unchecked, this material will be saved as a draft visible only to you and administrators.</p>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-200">
                <a href="/teacher/content" class="px-4 py-2.5 text-sm font-semibold text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50 transition shadow-sm">
                    Cancel
                </a>
                <button type="submit" class="px-6 py-2.5 bg-brand-600 hover:bg-brand-700 text-white text-sm font-semibold rounded-xl shadow-sm transition">
                    Save Material
                </button>
            </div>
        </form>
    </div>
</div>
