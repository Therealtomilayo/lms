<div class="max-w-4xl mx-auto space-y-6">
    <!-- Header Card -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <!-- Breadcrumbs -->
                <nav class="flex items-center gap-2 text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">
                    <a href="/teacher/dashboard" class="text-slate-400 hover:text-emerald-600 transition">Faculty Portal</a>
                    <span class="text-slate-300">/</span>
                    <a href="/teacher/content" class="text-slate-400 hover:text-emerald-600 transition">Learning Materials</a>
                    <span class="text-slate-300">/</span>
                    <span class="text-slate-700">Upload</span>
                </nav>
                <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight leading-tight">
                    Upload Learning Material
                </h1>
                <p class="text-xs text-slate-500 mt-1">
                    Provide lesson notes, attach documents or lecture slides, and share educational resources with your students.
                </p>
            </div>

            <div>
                <?php $this->include('components/button', [
                    'label' => 'Back to Materials',
                    'variant' => 'secondary',
                    'href' => '/teacher/content' . ($presetClassSubjectId ? '?class_subject_id=' . (int)$presetClassSubjectId : ''),
                    'icon' => '<svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>'
                ]); ?>
            </div>
        </div>
    </div>

    <!-- Form Container -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6 sm:p-8">
        <form action="/teacher/content/create" method="POST" enctype="multipart/form-data" class="space-y-6">
            <?= csrf_field() ?>

            <!-- Target Class & Subject Selector -->
            <div>
                <label for="class_subject_id" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                    Target Class & Subject <span class="text-rose-500">*</span>
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

            <!-- Title & Topic Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="sm:col-span-2">
                    <label for="title" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                        Material Title <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" name="title" id="title" required maxlength="200"
                           value="<?= htmlspecialchars((string)old('title', '')) ?>"
                           placeholder="e.g. Week 3: Cell Division and Mitosis Notes"
                           class="w-full rounded-xl border border-slate-300 text-xs focus:border-emerald-500 focus:ring-emerald-500 bg-slate-50 py-2.5 px-3 font-medium text-slate-900 transition">
                </div>

                <div>
                    <label for="topic" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                        Topic / Module Name
                    </label>
                    <input type="text" name="topic" id="topic" maxlength="100"
                           value="<?= htmlspecialchars((string)old('topic', '')) ?>"
                           placeholder="e.g. Cell Biology"
                           class="w-full rounded-xl border border-slate-300 text-xs focus:border-emerald-500 focus:ring-emerald-500 bg-slate-50 py-2.5 px-3 font-medium text-slate-900 transition">
                </div>
            </div>

            <!-- Material Type Selector -->
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-2">
                    Material Type <span class="text-rose-500">*</span>
                </label>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                    <label class="relative flex flex-col items-center p-3.5 rounded-xl border border-slate-200 cursor-pointer hover:bg-slate-50 transition has-[:checked]:border-emerald-500 has-[:checked]:bg-emerald-50/50">
                        <input type="radio" name="type" value="note" class="sr-only" <?= old('type', 'note') === 'note' ? 'checked' : '' ?> onchange="updateTypeFields('note')">
                        <svg class="w-5 h-5 text-blue-600 mb-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        <span class="text-xs font-bold text-slate-900">Lesson Note</span>
                    </label>

                    <label class="relative flex flex-col items-center p-3.5 rounded-xl border border-slate-200 cursor-pointer hover:bg-slate-50 transition has-[:checked]:border-emerald-500 has-[:checked]:bg-emerald-50/50">
                        <input type="radio" name="type" value="document" class="sr-only" <?= old('type') === 'document' ? 'checked' : '' ?> onchange="updateTypeFields('document')">
                        <svg class="w-5 h-5 text-emerald-600 mb-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                        <span class="text-xs font-bold text-slate-900">Document / PDF</span>
                    </label>

                    <label class="relative flex flex-col items-center p-3.5 rounded-xl border border-slate-200 cursor-pointer hover:bg-slate-50 transition has-[:checked]:border-emerald-500 has-[:checked]:bg-emerald-50/50">
                        <input type="radio" name="type" value="video" class="sr-only" <?= old('type') === 'video' ? 'checked' : '' ?> onchange="updateTypeFields('video')">
                        <svg class="w-5 h-5 text-purple-600 mb-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                        <span class="text-xs font-bold text-slate-900">Video Lesson</span>
                    </label>

                    <label class="relative flex flex-col items-center p-3.5 rounded-xl border border-slate-200 cursor-pointer hover:bg-slate-50 transition has-[:checked]:border-emerald-500 has-[:checked]:bg-emerald-50/50">
                        <input type="radio" name="type" value="link" class="sr-only" <?= old('type') === 'link' ? 'checked' : '' ?> onchange="updateTypeFields('link')">
                        <svg class="w-5 h-5 text-amber-600 mb-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                        <span class="text-xs font-bold text-slate-900">Web Resource</span>
                    </label>
                </div>
            </div>

            <!-- Description / Body Text -->
            <div>
                <label for="description" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                    Lesson Description / Notes
                </label>
                <textarea name="description" id="description" rows="5"
                          placeholder="Provide detailed lecture notes, study instructions, or context for students..."
                          class="w-full rounded-xl border border-slate-300 text-xs focus:border-emerald-500 focus:ring-emerald-500 bg-slate-50 py-2.5 px-3 font-medium text-slate-900 leading-relaxed transition"><?= htmlspecialchars((string)old('description', '')) ?></textarea>
            </div>

            <!-- External URL (for link / video) -->
            <div id="url-field-container">
                <label for="external_url" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                    External Resource URL <span class="text-slate-400 font-normal lowercase">(YouTube, Google Drive, or Web Link)</span>
                </label>
                <input type="url" name="external_url" id="external_url"
                       value="<?= htmlspecialchars((string)old('external_url', '')) ?>"
                       placeholder="https://www.youtube.com/watch?v=..."
                       class="w-full rounded-xl border border-slate-300 text-xs focus:border-emerald-500 focus:ring-emerald-500 bg-slate-50 py-2.5 px-3 font-medium text-slate-900 transition">
            </div>

            <!-- File Upload Dropzone -->
            <div>
                <label for="attachment" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                    File Attachment <span class="text-slate-400 font-normal lowercase">(PDF, Office docs, slides, max 25MB)</span>
                </label>
                <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-slate-300 border-dashed rounded-xl bg-slate-50/70 hover:bg-slate-100/70 transition cursor-pointer" onclick="document.getElementById('attachment').click()">
                    <div class="space-y-1 text-center">
                        <svg class="mx-auto h-10 w-10 text-slate-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                            <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                        <div class="flex text-xs text-slate-600 justify-center">
                            <span class="relative cursor-pointer font-bold text-emerald-600 hover:text-emerald-700">
                                Select a file
                            </span>
                            <span class="pl-1 text-slate-500">or drag and drop</span>
                        </div>
                        <p class="text-[11px] text-slate-400">PDF, DOCX, PPTX, XLSX, MP4, MP3 up to 25MB</p>
                        <p id="file-chosen-name" class="text-xs font-bold text-emerald-700 pt-1"></p>
                    </div>
                </div>
                <input id="attachment" name="attachment" type="file" class="sr-only" onchange="document.getElementById('file-chosen-name').textContent = this.files[0] ? this.files[0].name + ' (' + (this.files[0].size/1024/1024).toFixed(2) + ' MB)' : ''">
            </div>

            <!-- Publish Now Checkbox -->
            <div class="flex items-center gap-3 p-4 bg-slate-50 rounded-xl border border-slate-200">
                <input type="checkbox" name="publish_now" id="publish_now" value="1" <?= old('publish_now', '1') ? 'checked' : '' ?>
                       class="h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500 cursor-pointer">
                <div>
                    <label for="publish_now" class="text-xs font-bold text-slate-900 cursor-pointer">
                        Publish immediately to enrolled students
                    </label>
                    <p class="text-[11px] text-slate-500">When unchecked, this material will be saved as a draft visible only to you.</p>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-200">
                <?php $this->include('components/button', [
                    'label' => 'Cancel',
                    'variant' => 'secondary',
                    'href' => '/teacher/content' . ($presetClassSubjectId ? '?class_subject_id=' . (int)$presetClassSubjectId : '')
                ]); ?>

                <?php $this->include('components/button', [
                    'type' => 'submit',
                    'label' => 'Publish Learning Material',
                    'variant' => 'primary'
                ]); ?>
            </div>
        </form>
    </div>
</div>

<script>
function updateTypeFields(type) {
    var urlContainer = document.getElementById('url-field-container');
    if (urlContainer) {
        if (type === 'video' || type === 'link') {
            urlContainer.style.display = 'block';
        }
    }
}
</script>
