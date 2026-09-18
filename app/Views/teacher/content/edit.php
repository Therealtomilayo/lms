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
                        <span class="text-xs font-bold text-slate-900">Document (PDF / DOCX)</span>
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

    <?php if ($item->type === 'document'): ?>
    <?php 
    $isDocxDoc = $item->file && (
        $item->file->mimeType === 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
        || $item->file->mimeType === 'application/zip'
        || $item->file->mimeType === 'application/msword'
        || str_ends_with(strtolower($item->file->originalName), '.docx')
    );
    ?>
    <?php if ($isDocxDoc): ?>
    <!-- Word Document Informational Notice -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6 space-y-3">
        <div class="flex items-center gap-3">
            <span class="p-2.5 rounded-xl bg-blue-50 text-blue-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
            </span>
            <div>
                <h3 class="text-sm font-bold text-slate-900">Word Document (.docx) Reading</h3>
                <p class="text-xs text-slate-500">This document is read online via structured continuous section rendering and reading progress tracking. Explicit page ranges apply to paginated PDF documents.</p>
            </div>
        </div>
    </div>
    <?php else: ?>
    <!-- Document Sections Management (Phase 3) -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6 sm:p-8 space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-slate-100">
            <div>
                <div class="flex items-center gap-2">
                    <span class="p-2 rounded-lg bg-emerald-50 text-emerald-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                        </svg>
                    </span>
                    <div>
                        <h2 class="text-lg font-bold text-slate-900 tracking-tight">Logical Document Sections</h2>
                        <p class="text-xs text-slate-500">Divide this PDF into structured learning sections without altering the original file.</p>
                    </div>
                </div>
            </div>

            <button type="button" onclick="openAddSectionModal()" 
                    class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl shadow-xs transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                </svg>
                Add Section
            </button>
        </div>

        <?php if (empty($sections)): ?>
            <div class="text-center py-10 px-4 bg-slate-50/70 rounded-xl border border-dashed border-slate-200">
                <div class="w-12 h-12 mx-auto rounded-full bg-slate-100 flex items-center justify-center text-slate-400 mb-3">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                <h3 class="text-sm font-bold text-slate-800 mb-1">No Logical Sections Defined</h3>
                <p class="text-xs text-slate-500 max-w-md mx-auto mb-4">
                    Students will view this document as a single unit. Define sections to create a sequential reading outline with targeted progress tracking.
                </p>
                <button type="button" onclick="openAddSectionModal()" 
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-slate-800 hover:bg-slate-900 text-white text-xs font-semibold rounded-xl shadow-xs transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                    </svg>
                    Create First Section
                </button>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto rounded-xl border border-slate-200">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 font-bold uppercase tracking-wider text-[11px]">
                        <tr>
                            <th class="px-4 py-3 w-16 text-center">Order</th>
                            <th class="px-4 py-3">Section Title</th>
                            <th class="px-4 py-3 w-36">Page Range</th>
                            <th class="px-4 py-3">Prerequisites</th>
                            <th class="px-4 py-3 w-36 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-medium text-slate-700">
                        <?php foreach ($sections as $index => $sec): 
                            $prereqs = $sec->configuredPrerequisites ?? [];
                        ?>
                            <tr class="hover:bg-slate-50/60 transition">
                                <td class="px-4 py-3.5 text-center font-mono font-bold text-slate-500">
                                    <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-slate-100 text-slate-700 text-xs">
                                        <?= (int)$sec->sequenceOrder ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3.5 font-bold text-slate-900">
                                    <?= htmlspecialchars($sec->title) ?>
                                </td>
                                <td class="px-4 py-3.5 text-slate-600">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-slate-100 text-slate-800 font-mono text-xs">
                                        p. <?= (int)$sec->startPage ?> &ndash; <?= (int)$sec->endPage ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3.5">
                                    <?php if (empty($prereqs)): ?>
                                        <span class="text-slate-400 italic text-[11px]">None (Always Unlocked)</span>
                                    <?php else: ?>
                                        <div class="flex flex-wrap gap-1.5 items-center">
                                            <?php foreach ($prereqs as $p): ?>
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-amber-50 text-amber-900 border border-amber-200 text-[11px]">
                                                    <span>🔒 <?= htmlspecialchars($p['title']) ?></span>
                                                    <form action="/teacher/content/<?= (int)$item->id ?>/prerequisites/<?= (int)$p['id'] ?>/delete" method="POST" class="inline" onsubmit="return confirm('Remove this prerequisite?');">
                                                        <?= csrf_field() ?>
                                                        <button type="submit" class="text-amber-700 hover:text-rose-600 font-bold ml-0.5">&times;</button>
                                                    </form>
                                                </span>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-3.5 text-right">
                                    <div class="inline-flex items-center gap-2">
                                        <button type="button" 
                                                onclick="openAddPrereqModal('document_section', <?= (int)$sec->id ?>, '<?= htmlspecialchars(addslashes($sec->title), ENT_QUOTES) ?>')"
                                                class="text-xs font-semibold text-sky-600 hover:text-sky-700 transition"
                                                title="Add prerequisite activity">
                                            + Prereq
                                        </button>
                                        <span class="text-slate-300">|</span>
                                        <button type="button" 
                                                onclick="openEditSectionModal(<?= (int)$sec->id ?>, '<?= htmlspecialchars(addslashes($sec->title), ENT_QUOTES) ?>', <?= (int)$sec->startPage ?>, <?= (int)$sec->endPage ?>, <?= (int)$sec->sequenceOrder ?>)"
                                                class="text-xs font-semibold text-emerald-600 hover:text-emerald-700 transition"
                                                title="Edit section">
                                            Edit
                                        </button>
                                        <span class="text-slate-300">|</span>
                                        <form action="/teacher/content/<?= (int)$item->id ?>/sections/<?= (int)$sec->id ?>/delete" 
                                              method="POST" 
                                              onsubmit="return confirm('Are you sure you want to delete the section &quot;<?= htmlspecialchars(addslashes($sec->title), ENT_QUOTES) ?>&quot;? Student document progress will be kept, but this section outline will be removed.');" 
                                              class="inline">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="text-xs font-semibold text-rose-600 hover:text-rose-700 transition" title="Delete section">
                                                Delete
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Section Add/Edit Modal -->
    <div id="sectionModal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-slate-900/50 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-xl border border-slate-200 w-full max-w-md overflow-hidden">
            <div class="flex items-center justify-between p-5 border-b border-slate-100 bg-slate-50/50">
                <h3 id="sectionModalTitle" class="text-base font-bold text-slate-900">Add Document Section</h3>
                <button type="button" onclick="closeSectionModal()" class="text-slate-400 hover:text-slate-600 p-1 rounded-lg">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <form id="sectionForm" action="/teacher/content/<?= (int)$item->id ?>/sections" method="POST" class="p-6 space-y-4">
                <?= csrf_field() ?>
                <input type="hidden" name="section_id" id="modal_section_id" value="">

                <div>
                    <label for="modal_title" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                        Section Title <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" name="title" id="modal_title" required maxlength="200"
                           placeholder="e.g., Chapter 1: Introduction"
                           class="w-full rounded-xl border border-slate-300 text-xs focus:border-emerald-500 focus:ring-emerald-500 bg-slate-50 py-2.5 px-3 font-medium text-slate-900 transition">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label for="modal_start_page" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                            Start Page <span class="text-rose-500">*</span>
                        </label>
                        <input type="number" name="start_page" id="modal_start_page" required min="1"
                               class="w-full rounded-xl border border-slate-300 text-xs focus:border-emerald-500 focus:ring-emerald-500 bg-slate-50 py-2.5 px-3 font-medium text-slate-900 transition">
                    </div>
                    <div>
                        <label for="modal_end_page" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                            End Page <span class="text-rose-500">*</span>
                        </label>
                        <input type="number" name="end_page" id="modal_end_page" required min="1"
                               class="w-full rounded-xl border border-slate-300 text-xs focus:border-emerald-500 focus:ring-emerald-500 bg-slate-50 py-2.5 px-3 font-medium text-slate-900 transition">
                    </div>
                </div>

                <div>
                    <label for="modal_sequence_order" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                        Sequence Order
                    </label>
                    <input type="number" name="sequence_order" id="modal_sequence_order" min="1"
                           class="w-full rounded-xl border border-slate-300 text-xs focus:border-emerald-500 focus:ring-emerald-500 bg-slate-50 py-2.5 px-3 font-medium text-slate-900 transition">
                    <p class="text-[11px] text-slate-400 mt-1">Order in which this section appears in the student reader outline.</p>
                </div>

                <div class="p-3 bg-amber-50 border border-amber-200 rounded-xl text-xs text-amber-800">
                    <p class="font-semibold">Sequential Page Ranges:</p>
                    <p class="mt-0.5">Sections cannot overlap pages. Page ranges must be sequential (e.g., 1&ndash;5, then 6&ndash;10).</p>
                </div>

                <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                    <button type="button" onclick="closeSectionModal()" 
                            class="px-4 py-2 text-xs font-bold text-slate-600 hover:text-slate-800 rounded-xl transition">
                        Cancel
                    </button>
                    <button type="submit" 
                            class="px-4 py-2 text-xs font-bold bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl shadow-xs transition">
                        Save Section
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Prerequisite Modal (Phase 4) -->
    <div id="prereqModal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-slate-900/50 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-xl border border-slate-200 w-full max-w-md overflow-hidden">
            <div class="flex items-center justify-between p-5 border-b border-slate-100 bg-slate-50/50">
                <div>
                    <h3 class="text-sm font-bold text-slate-900" id="prereqModalTitle">Add Prerequisite Requirement</h3>
                    <p class="text-[11px] text-slate-500 mt-0.5" id="prereqModalSubtitle">Target: Section</p>
                </div>
                <button type="button" onclick="closePrereqModal()" class="text-slate-400 hover:text-slate-600 p-1 rounded-lg">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <form action="/teacher/content/<?= (int)$item->id ?>/prerequisites" method="POST" class="p-6 space-y-4">
                <?= csrf_field() ?>
                <input type="hidden" name="target_type" id="prereq_target_type" value="">
                <input type="hidden" name="target_id" id="prereq_target_id" value="">
                <input type="hidden" name="requirement_type" value="completion">

                <div>
                    <label for="prerequisite_type" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                        Prerequisite Activity Type <span class="text-rose-500">*</span>
                    </label>
                    <select name="prerequisite_type" id="prerequisite_type" required onchange="updatePrereqOptions()"
                            class="w-full rounded-xl border border-slate-300 text-xs focus:border-emerald-500 focus:ring-emerald-500 bg-slate-50 py-2.5 px-3 font-medium text-slate-900 transition">
                        <option value="document_section">Document Section (Within this Document)</option>
                    </select>
                </div>

                <div>
                    <label for="prerequisite_id" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                        Required Activity (Must be completed first) <span class="text-rose-500">*</span>
                    </label>
                    <select name="prerequisite_id" id="prerequisite_id" required
                            class="w-full rounded-xl border border-slate-300 text-xs focus:border-emerald-500 focus:ring-emerald-500 bg-slate-50 py-2.5 px-3 font-medium text-slate-900 transition">
                        <!-- Populated dynamically via JS -->
                    </select>
                </div>

                <div class="p-3 bg-amber-50 rounded-xl border border-amber-200 text-[11px] text-amber-800 space-y-1 leading-relaxed">
                    <p class="font-bold">Rule Note:</p>
                    <p>The student must complete at least 90% of the required section before this target section unlocks. Circular dependencies and self-dependencies are strictly prevented.</p>
                </div>

                <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                    <button type="button" onclick="closePrereqModal()" 
                            class="px-4 py-2 text-xs font-bold text-slate-600 hover:text-slate-800 rounded-xl transition">
                        Cancel
                    </button>
                    <button type="submit" 
                            class="px-4 py-2 text-xs font-bold bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl shadow-xs transition">
                        Save Prerequisite
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
    const availableSections = <?= json_encode(array_map(fn($s) => [
        'id' => $s->id,
        'title' => $s->title,
        'order' => $s->sequenceOrder
    ], $sections ?? [])) ?>;

    function openAddPrereqModal(targetType, targetId, targetTitle) {
        document.getElementById('prereq_target_type').value = targetType;
        document.getElementById('prereq_target_id').value = targetId;
        document.getElementById('prereqModalSubtitle').textContent = 'Target: ' + targetTitle;

        const select = document.getElementById('prerequisite_id');
        select.innerHTML = '';

        // Filter out self-dependency
        const filtered = availableSections.filter(s => s.id !== targetId);
        if (filtered.length === 0) {
            select.innerHTML = '<option value="">No other sections available to depend on</option>';
        } else {
            filtered.forEach(s => {
                const opt = document.createElement('option');
                opt.value = s.id;
                opt.textContent = '#' + s.order + ' ' + s.title;
                select.appendChild(opt);
            });
        }

        document.getElementById('prereqModal').classList.remove('hidden');
    }

    function closePrereqModal() {
        document.getElementById('prereqModal').classList.add('hidden');
    }

    document.getElementById('prereqModal')?.addEventListener('click', function(e) {
        if (e.target === this) {
            closePrereqModal();
        }
    });

    function openAddSectionModal() {
        document.getElementById('sectionModalTitle').textContent = 'Add Document Section';
        document.getElementById('modal_section_id').value = '';
        document.getElementById('modal_title').value = '';
        document.getElementById('modal_start_page').value = '';
        document.getElementById('modal_end_page').value = '';
        document.getElementById('modal_sequence_order').value = <?= count($sections ?? []) + 1 ?>;
        document.getElementById('sectionModal').classList.remove('hidden');
        document.getElementById('modal_title').focus();
    }

    function openEditSectionModal(id, title, start, end, order) {
        document.getElementById('sectionModalTitle').textContent = 'Edit Document Section';
        document.getElementById('modal_section_id').value = id;
        document.getElementById('modal_title').value = title;
        document.getElementById('modal_start_page').value = start;
        document.getElementById('modal_end_page').value = end;
        document.getElementById('modal_sequence_order').value = order;
        document.getElementById('sectionModal').classList.remove('hidden');
        document.getElementById('modal_title').focus();
    }

    function closeSectionModal() {
        document.getElementById('sectionModal').classList.add('hidden');
    }

    document.getElementById('sectionModal')?.addEventListener('click', function(e) {
        if (e.target === this) {
            closeSectionModal();
        }
    });
    </script>
    <?php endif; ?>
    <?php endif; ?>
</div>
