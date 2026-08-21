<div class="max-w-4xl mx-auto space-y-6">
    <!-- Header Card -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <!-- Breadcrumbs -->
                <nav class="flex items-center gap-2 text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">
                    <a href="/teacher/dashboard" class="text-slate-400 hover:text-emerald-600 transition">Faculty Portal</a>
                    <span class="text-slate-300">/</span>
                    <a href="/teacher/content?class_subject_id=<?= (int)$item->classSubjectId ?>" class="text-slate-400 hover:text-emerald-600 transition">Learning Materials</a>
                    <span class="text-slate-300">/</span>
                    <span class="text-slate-700">Edit</span>
                </nav>
                <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight leading-tight">
                    Edit Learning Material
                </h1>
                <p class="text-xs text-slate-500 mt-1">
                    Update lecture notes, replace file attachments, or modify published status.
                </p>
            </div>

            <div>
                <?php $this->include('components/button', [
                    'label' => 'Back to Materials',
                    'variant' => 'secondary',
                    'href' => '/teacher/content?class_subject_id=' . (int)$item->classSubjectId,
                    'icon' => '<svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>'
                ]); ?>
            </div>
        </div>
    </div>

    <!-- Form Container -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6 sm:p-8">
        <form action="/teacher/content/<?= (int)$item->id ?>/edit" method="POST" enctype="multipart/form-data" class="space-y-6">
            <?= csrf_field() ?>

            <!-- Target Class-Subject -->
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                    Class & Subject Allocation
                </label>
                <div class="p-3.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-800 flex items-center justify-between">
                    <span>
                        <?= htmlspecialchars($item->classSubject?->subject?->name ?? ($item->classSubject?->subjectName ?? 'Subject')) ?> — 
                        <?= htmlspecialchars($item->classSubject?->schoolClass?->name ?? ($item->classSubject?->className ?? 'Class')) ?>
                        <?= !empty($item->classSubject?->sectionArm) ? ' (' . htmlspecialchars($item->classSubject->sectionArm) . ')' : '' ?>
                    </span>
                    <span class="text-[10px] font-mono uppercase bg-slate-200/70 text-slate-700 px-2 py-0.5 rounded">
                        ID: #<?= (int)$item->classSubjectId ?>
                    </span>
                </div>
            </div>

            <!-- Title & Topic Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="sm:col-span-2">
                    <label for="title" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                        Material Title <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" name="title" id="title" required maxlength="200"
                           value="<?= htmlspecialchars((string)old('title', $item->title)) ?>"
                           class="w-full rounded-xl border border-slate-300 text-xs focus:border-emerald-500 focus:ring-emerald-500 bg-slate-50 py-2.5 px-3 font-medium text-slate-900 transition">
                </div>

                <div>
                    <label for="topic" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                        Topic / Module Name
                    </label>
                    <input type="text" name="topic" id="topic" maxlength="100"
                           value="<?= htmlspecialchars((string)old('topic', $item->topic ?? '')) ?>"
                           class="w-full rounded-xl border border-slate-300 text-xs focus:border-emerald-500 focus:ring-emerald-500 bg-slate-50 py-2.5 px-3 font-medium text-slate-900 transition">
                </div>
            </div>

            <!-- Material Type Selector -->
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-2">
                    Material Type <span class="text-rose-500">*</span>
                </label>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                    <?php $currType = old('type', $item->type); ?>
                    <label class="relative flex flex-col items-center p-3.5 rounded-xl border border-slate-200 cursor-pointer hover:bg-slate-50 transition has-[:checked]:border-emerald-500 has-[:checked]:bg-emerald-50/50">
                        <input type="radio" name="type" value="note" class="sr-only" <?= $currType === 'note' ? 'checked' : '' ?>>
                        <svg class="w-5 h-5 text-blue-600 mb-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        <span class="text-xs font-bold text-slate-900">Lesson Note</span>
                    </label>

                    <label class="relative flex flex-col items-center p-3.5 rounded-xl border border-slate-200 cursor-pointer hover:bg-slate-50 transition has-[:checked]:border-emerald-500 has-[:checked]:bg-emerald-50/50">
                        <input type="radio" name="type" value="document" class="sr-only" <?= $currType === 'document' ? 'checked' : '' ?>>
                        <svg class="w-5 h-5 text-emerald-600 mb-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                        <span class="text-xs font-bold text-slate-900">Document / PDF</span>
                    </label>

                    <label class="relative flex flex-col items-center p-3.5 rounded-xl border border-slate-200 cursor-pointer hover:bg-slate-50 transition has-[:checked]:border-emerald-500 has-[:checked]:bg-emerald-50/50">
                        <input type="radio" name="type" value="video" class="sr-only" <?= $currType === 'video' ? 'checked' : '' ?>>
                        <svg class="w-5 h-5 text-purple-600 mb-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                        <span class="text-xs font-bold text-slate-900">Video Lesson</span>
                    </label>

                    <label class="relative flex flex-col items-center p-3.5 rounded-xl border border-slate-200 cursor-pointer hover:bg-slate-50 transition has-[:checked]:border-emerald-500 has-[:checked]:bg-emerald-50/50">
                        <input type="radio" name="type" value="link" class="sr-only" <?= $currType === 'link' ? 'checked' : '' ?>>
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
                          class="w-full rounded-xl border border-slate-300 text-xs focus:border-emerald-500 focus:ring-emerald-500 bg-slate-50 py-2.5 px-3 font-medium text-slate-900 leading-relaxed transition"><?= htmlspecialchars((string)old('description', $item->description ?? '')) ?></textarea>
            </div>

            <!-- External URL -->
            <div>
                <label for="external_url" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                    External Resource URL
                </label>
                <input type="url" name="external_url" id="external_url"
                       value="<?= htmlspecialchars((string)old('external_url', $item->externalUrl ?? '')) ?>"
                       class="w-full rounded-xl border border-slate-300 text-xs focus:border-emerald-500 focus:ring-emerald-500 bg-slate-50 py-2.5 px-3 font-medium text-slate-900 transition">
            </div>

            <!-- Existing & Replacement File -->
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                    File Attachment
                </label>
                <?php if ($item->file): ?>
                    <div class="mb-3 p-3.5 bg-slate-50 rounded-xl border border-slate-200 flex items-center justify-between">
                        <div class="flex items-center gap-2 min-w-0">
                            <svg class="w-4 h-4 text-emerald-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                            <span class="text-xs font-semibold text-slate-800 truncate"><?= htmlspecialchars($item->file->originalName) ?></span>
                            <span class="text-[10px] text-slate-400 font-mono">(<?= htmlspecialchars($item->file->getFormattedSize()) ?>)</span>
                        </div>
                        <a href="/files/<?= (int)$item->file->id ?>/download" class="text-xs font-bold text-emerald-600 hover:text-emerald-700 transition">
                            Download Current
                        </a>
                    </div>
                <?php endif; ?>

                <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-slate-300 border-dashed rounded-xl bg-slate-50/70 hover:bg-slate-100/70 transition cursor-pointer" onclick="document.getElementById('attachment').click()">
                    <div class="space-y-1 text-center">
                        <svg class="mx-auto h-10 w-10 text-slate-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                            <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                        <div class="flex text-xs text-slate-600 justify-center">
                            <span class="relative cursor-pointer font-bold text-emerald-600 hover:text-emerald-700">
                                Upload new replacement file
                            </span>
                        </div>
                        <p class="text-[11px] text-slate-400">Leave blank to retain current file</p>
                        <p id="file-chosen-name" class="text-xs font-bold text-emerald-700 pt-1"></p>
                    </div>
                </div>
                <input id="attachment" name="attachment" type="file" class="sr-only" onchange="document.getElementById('file-chosen-name').textContent = this.files[0] ? this.files[0].name + ' (' + (this.files[0].size/1024/1024).toFixed(2) + ' MB)' : ''">
            </div>

            <!-- Published Status -->
            <div class="flex items-center gap-3 p-4 bg-slate-50 rounded-xl border border-slate-200">
                <input type="checkbox" name="is_published" id="is_published" value="1" <?= old('is_published', $item->isPublished() ? '1' : '0') === '1' ? 'checked' : '' ?>
                       class="h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500 cursor-pointer">
                <div>
                    <label for="is_published" class="text-xs font-bold text-slate-900 cursor-pointer">
                        Published to enrolled students
                    </label>
                    <p class="text-[11px] text-slate-500">When checked, enrolled learners can view and download this material.</p>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-200">
                <?php $this->include('components/button', [
                    'label' => 'Cancel',
                    'variant' => 'secondary',
                    'href' => '/teacher/content?class_subject_id=' . (int)$item->classSubjectId
                ]); ?>

                <?php $this->include('components/button', [
                    'type' => 'submit',
                    'label' => 'Save Changes',
                    'variant' => 'primary'
                ]); ?>
            </div>
        </form>
    </div>
</div>
