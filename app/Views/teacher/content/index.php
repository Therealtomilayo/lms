<div class="space-y-6">
    <!-- Header Card -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <!-- Breadcrumbs -->
                <nav class="flex items-center gap-2 text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">
                    <a href="/teacher/dashboard" class="text-slate-400 hover:text-emerald-600 transition">Faculty Portal</a>
                    <span class="text-slate-300">/</span>
                    <span class="text-slate-700">Learning Materials</span>
                </nav>
                <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight leading-tight">
                    Course Content & Study Materials
                </h1>
                <p class="text-xs text-slate-500 mt-1">
                    Publish lesson notes, documents, lecture slides, video tutorials, and curriculum resources for your assigned classes.
                </p>
            </div>

            <!-- Upload CTA Button -->
            <div class="flex-shrink-0">
                <?php $this->include('components/button', [
                    'label' => 'Upload Learning Material',
                    'variant' => 'primary',
                    'href' => '/teacher/content/create' . ($selectedClassSubjectId ? '?class_subject_id=' . (int)$selectedClassSubjectId : ''),
                    'icon' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>'
                ]); ?>
            </div>
        </div>
    </div>

    <!-- Class-Subject Selector Toolbar -->
    <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-xs">
        <form method="GET" action="/teacher/content" class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3">
            <div class="flex items-center gap-3 flex-1">
                <label for="class_subject_id" class="text-xs font-bold text-slate-600 uppercase tracking-wider whitespace-nowrap flex items-center gap-1.5">
                    <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                    </svg>
                    Class & Subject:
                </label>
                <select name="class_subject_id" id="class_subject_id" onchange="this.form.submit()"
                        class="flex-1 max-w-lg rounded-xl border border-slate-300 text-xs focus:border-emerald-500 focus:ring-emerald-500 bg-slate-50 py-2 px-3 font-semibold text-slate-900 transition">
                    <?php if (empty($classSubjects)): ?>
                        <option value="">No active teaching allocations found</option>
                    <?php else: ?>
                        <?php foreach ($classSubjects as $cs): ?>
                            <?php 
                                $sName = $cs->subject?->name ?? ($cs->subjectName ?? 'Subject');
                                $cName = $cs->schoolClass?->name ?? ($cs->className ?? 'Class');
                                $sArm = $cs->schoolClass?->sectionArm ?? ($cs->sectionArm ?? '');
                            ?>
                            <option value="<?= (int)$cs->id ?>" <?= (int)$cs->id === (int)$selectedClassSubjectId ? 'selected' : '' ?>>
                                <?= htmlspecialchars($sName) ?> — <?= htmlspecialchars($cName) ?><?= !empty($sArm) ? ' (' . htmlspecialchars($sArm) . ')' : '' ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
                <noscript>
                    <button type="submit" class="px-3 py-1.5 bg-slate-800 text-white rounded-lg text-xs font-medium">Switch</button>
                </noscript>
            </div>

            <!-- Material Count Badge -->
            <div class="flex items-center gap-2">
                <?php $this->include('components/badge', [
                    'label' => count($items) . ' Resource' . (count($items) === 1 ? '' : 's'),
                    'variant' => 'neutral',
                    'class' => 'text-xs font-semibold'
                ]); ?>
            </div>
        </form>
    </div>

    <!-- Content Items Display -->
    <?php if (empty($selectedClassSubject)): ?>
        <div class="bg-white rounded-2xl border border-slate-200 p-12 text-center shadow-xs">
            <?php $this->include('components/empty_state', [
                'title' => 'No Teaching Allocation Selected',
                'description' => 'Please select an assigned class-subject above to manage instructional materials.'
            ]); ?>
        </div>
    <?php elseif (empty($items)): ?>
        <div class="bg-white rounded-2xl border border-slate-200 p-12 text-center shadow-xs">
            <div class="w-16 h-16 rounded-2xl bg-emerald-50 text-emerald-600 mx-auto flex items-center justify-center mb-4">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                </svg>
            </div>
            <h3 class="text-base font-bold text-slate-800">No Learning Materials Added Yet</h3>
            <p class="text-xs text-slate-500 mt-1 max-w-sm mx-auto">
                Get started by uploading lesson notes, lecture slides, documents, or sharing video resources for students.
            </p>
            <div class="mt-6">
                <?php $this->include('components/button', [
                    'label' => 'Upload First Material',
                    'variant' => 'primary',
                    'href' => '/teacher/content/create?class_subject_id=' . (int)$selectedClassSubject->id,
                    'icon' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>'
                ]); ?>
            </div>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($items as $item): ?>
                <div class="bg-white rounded-2xl border border-slate-200 shadow-xs flex flex-col hover:border-emerald-300 hover:shadow-sm transition overflow-hidden group">
                    <!-- Top Ribbon / Media Type & Status -->
                    <div class="p-4 pb-3 border-b border-slate-100 flex items-center justify-between gap-2">
                        <div class="flex items-center gap-2">
                            <?php if ($item->type === 'note'): ?>
                                <span class="p-1.5 rounded-lg bg-blue-50 text-blue-600">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                </span>
                            <?php elseif ($item->type === 'document'): ?>
                                <span class="p-1.5 rounded-lg bg-emerald-50 text-emerald-600">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                </span>
                            <?php elseif ($item->type === 'video'): ?>
                                <span class="p-1.5 rounded-lg bg-purple-50 text-purple-600">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                </span>
                            <?php else: ?>
                                <span class="p-1.5 rounded-lg bg-amber-50 text-amber-600">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                                </span>
                            <?php endif; ?>
                            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-600"><?= htmlspecialchars($item->type) ?></span>
                        </div>

                        <?php if ($item->isPublished()): ?>
                            <?php $this->include('components/badge', [
                                'label' => 'Published',
                                'variant' => 'success',
                                'class' => 'text-[10px] font-bold'
                            ]); ?>
                        <?php else: ?>
                            <?php $this->include('components/badge', [
                                'label' => 'Draft',
                                'variant' => 'neutral',
                                'class' => 'text-[10px] font-bold'
                            ]); ?>
                        <?php endif; ?>
                    </div>

                    <!-- Card Body -->
                    <div class="p-5 flex-1 space-y-2.5">
                        <?php if (!empty($item->topic)): ?>
                            <span class="inline-block text-[10px] font-extrabold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded uppercase tracking-wider">
                                <?= htmlspecialchars($item->topic) ?>
                            </span>
                        <?php endif; ?>

                        <h3 class="text-sm font-bold text-slate-900 leading-snug group-hover:text-emerald-700 transition">
                            <?= htmlspecialchars($item->title) ?>
                        </h3>

                        <?php if (!empty($item->description)): ?>
                            <p class="text-xs text-slate-500 line-clamp-3 leading-relaxed">
                                <?= htmlspecialchars($item->description) ?>
                            </p>
                        <?php endif; ?>

                        <!-- File / External Link Attachment Info -->
                        <?php if ($item->file): ?>
                            <div class="p-3 bg-slate-50 rounded-xl border border-slate-200 flex items-center justify-between gap-2 mt-3">
                                <div class="flex items-center gap-2 min-w-0">
                                    <svg class="w-4 h-4 text-emerald-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                                    <span class="text-xs font-medium text-slate-700 truncate" title="<?= htmlspecialchars($item->file->originalName) ?>">
                                        <?= htmlspecialchars($item->file->originalName) ?>
                                    </span>
                                </div>
                                <span class="text-[10px] text-slate-400 font-mono flex-shrink-0"><?= htmlspecialchars($item->file->getFormattedSize()) ?></span>
                            </div>
                        <?php elseif (!empty($item->externalUrl)): ?>
                            <div class="p-3 bg-slate-50 rounded-xl border border-slate-200 flex items-center gap-2 mt-3">
                                <svg class="w-4 h-4 text-purple-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                <a href="<?= htmlspecialchars($item->externalUrl) ?>" target="_blank" rel="noopener noreferrer" class="text-xs text-emerald-600 hover:underline truncate">
                                    <?= htmlspecialchars($item->externalUrl) ?>
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Footer Actions -->
                    <div class="p-3.5 bg-slate-50 border-t border-slate-100 flex items-center justify-between gap-2 text-xs">
                        <?php if ($item->file): ?>
                            <a href="/files/<?= (int)$item->file->id ?>/download" 
                               class="inline-flex items-center gap-1 font-semibold text-emerald-600 hover:text-emerald-700 transition">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                Download
                            </a>
                        <?php else: ?>
                            <span></span>
                        <?php endif; ?>

                        <div class="flex items-center gap-1">
                            <!-- Edit Button -->
                            <a href="/teacher/content/<?= (int)$item->id ?>/edit" 
                               class="p-1.5 text-slate-500 hover:text-slate-900 rounded-lg hover:bg-slate-200/70 transition" title="Edit Material">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            </a>

                            <!-- Toggle Status Button -->
                            <form action="/teacher/content/<?= (int)$item->id ?>/publish" method="POST" class="inline">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="<?= $item->isPublished() ? 'unpublish' : 'publish' ?>">
                                <button type="submit" 
                                        class="p-1.5 text-slate-500 hover:text-emerald-600 rounded-lg hover:bg-slate-200/70 transition cursor-pointer" 
                                        title="<?= $item->isPublished() ? 'Unpublish (Move to draft)' : 'Publish to students' ?>">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                </button>
                            </form>

                            <!-- Delete Button -->
                            <form action="/teacher/content/<?= (int)$item->id ?>/delete" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this content item?');">
                                <?= csrf_field() ?>
                                <input type="hidden" name="class_subject_id" value="<?= (int)$item->classSubjectId ?>">
                                <button type="submit" class="p-1.5 text-slate-500 hover:text-rose-600 rounded-lg hover:bg-slate-200/70 transition cursor-pointer" title="Delete Material">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
