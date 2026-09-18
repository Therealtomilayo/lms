<div class="max-w-4xl mx-auto space-y-6">
    <!-- Header Card -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <!-- Breadcrumbs -->
                <nav class="flex items-center gap-2 text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">
                    <a href="/student/dashboard" class="hover:text-sky-600 transition">Student Portal</a>
                    <span class="text-slate-300">/</span>
                    <a href="/student/subjects" class="hover:text-sky-600 transition">Subjects</a>
                    <span class="text-slate-300">/</span>
                    <a href="/student/content?class_subject_id=<?= (int)$item->classSubjectId ?>" class="hover:text-sky-600 transition">
                        <?= htmlspecialchars($item->classSubject?->subject?->name ?? 'Course Materials') ?>
                    </a>
                </nav>
                <div class="flex flex-wrap items-center gap-3">
                    <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight leading-tight">
                        <?= htmlspecialchars($item->title) ?>
                    </h1>
                    <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold bg-sky-50 text-sky-800 border border-sky-200">
                        <?= htmlspecialchars($item->classSubject?->subject?->code ?: 'SUBJ') ?>
                    </span>
                    <?php if (!empty($item->topic)): ?>
                        <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold bg-slate-100 text-slate-700 border border-slate-200">
                            Topic: <?= htmlspecialchars($item->topic) ?>
                        </span>
                    <?php endif; ?>
                </div>
                <p class="text-xs text-slate-500 mt-1.5 flex items-center gap-2 flex-wrap">
                    <span>Teacher: <strong class="text-slate-800 font-semibold"><?= htmlspecialchars($item->teacher?->user?->name ?? ($item->teacher?->name ?? 'Subject Teacher')) ?></strong></span>
                    <span>&bull;</span>
                    <span>Published: <strong><?= date('F j, Y', strtotime($item->publishedAt ?? $item->createdAt)) ?></strong></span>
                </p>
            </div>

            <div class="flex items-center gap-2.5 flex-wrap">
                <a href="/student/content?class_subject_id=<?= (int)$item->classSubjectId ?>" 
                   class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-bold text-slate-700 bg-slate-100 hover:bg-slate-200 transition">
                    <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    <span>All Lessons</span>
                </a>
                <?php 
                $isPdf = $item->file && ($item->file->mimeType === 'application/pdf' || str_ends_with(strtolower($item->file->originalName), '.pdf'));
                $isDocx = $item->file && (
                    $item->file->mimeType === 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
                    || $item->file->mimeType === 'application/zip'
                    || $item->file->mimeType === 'application/msword'
                    || str_ends_with(strtolower($item->file->originalName), '.docx')
                );
                $isReadable = $isPdf || $isDocx;
                ?>
                <?php if ($item->file): ?>
                    <?php if ($isReadable): ?>
                        <a href="/student/content/<?= (int)$item->id ?>/read" 
                           class="inline-flex items-center gap-1.5 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl shadow-xs transition">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                            </svg>
                            <span>Read Online</span>
                        </a>
                    <?php endif; ?>
                    <a href="/files/<?= (int)$item->file->id ?>/download" 
                       class="inline-flex items-center gap-1.5 px-4 py-2 bg-sky-600 hover:bg-sky-700 text-white text-xs font-bold rounded-xl shadow-xs transition">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                        <span>Download Attachment</span>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- External Link / Video Banner if Present -->
    <?php if (!empty($item->externalUrl)): ?>
        <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500">External Educational Resource</h3>
                    <p class="text-xs font-mono font-semibold text-slate-700 truncate max-w-md"><?= htmlspecialchars($item->externalUrl) ?></p>
                </div>
            </div>
            <a href="<?= htmlspecialchars($item->externalUrl) ?>" target="_blank" rel="noopener noreferrer" 
               class="inline-flex items-center gap-1.5 px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-xl text-xs font-bold transition shadow-xs flex-shrink-0">
                <span>Open in New Tab</span>
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                </svg>
            </a>
        </div>
    <?php endif; ?>

    <!-- Main Lesson Body Text Card -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6 sm:p-8 space-y-6">
        <h2 class="text-sm font-bold uppercase tracking-wider text-slate-400">Lesson Material & Notes</h2>
        <div class="prose prose-slate max-w-none text-slate-800 leading-relaxed whitespace-pre-line text-sm sm:text-base font-normal">
            <?= htmlspecialchars($item->description ?? 'No textual notes provided for this lesson.') ?>
        </div>

        <!-- Attached File Card -->
        <?php if ($item->file): ?>
            <?php if (!empty($isDocUnlocked) === false && !empty($docPrereqStatus)): ?>
                <!-- Document-Level Locked Activity Warning Banner -->
                <div class="pt-4 border-t border-slate-100">
                    <?php $this->include('components/locked_activity', [
                        'title' => $item->title,
                        'allPrerequisites' => $docPrereqStatus['prerequisites'] ?? [],
                        'unmet' => $docPrereqStatus['unmet'] ?? [],
                        'backUrl' => '/student/content',
                    ]); ?>
                </div>
            <?php else: ?>
                <div class="pt-6 border-t border-slate-100">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-3">Attached Document</h3>
                    <div class="p-4 bg-slate-50 rounded-2xl border border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-sky-100 text-sky-700 flex items-center justify-center flex-shrink-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-xs font-bold text-slate-900"><?= htmlspecialchars($item->file->originalName) ?></p>
                                <p class="text-[11px] text-slate-500 font-semibold"><?= htmlspecialchars($item->file->getFormattedSize()) ?> &bull; <?= htmlspecialchars($item->file->mimeType) ?></p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2 flex-wrap flex-shrink-0">
                            <?php if ($isReadable): ?>
                                <a href="/student/content/<?= (int)$item->id ?>/read" 
                                   class="inline-flex items-center justify-center gap-1.5 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold shadow-xs transition">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                                    </svg>
                                    <span><?= ($progress && $progress->lastPage > 1) ? ($isDocx ? 'Resume (Section ' . (int)$progress->lastPage . ')' : 'Resume (Page ' . (int)$progress->lastPage . ')') : 'Read Online' ?></span>
                                </a>
                            <?php endif; ?>
                            <a href="/files/<?= (int)$item->file->id ?>/download" 
                               class="inline-flex items-center justify-center gap-1.5 px-4 py-2 bg-sky-600 hover:bg-sky-700 text-white rounded-xl text-xs font-bold shadow-xs transition">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                </svg>
                                <span>Download Attachment</span>
                            </a>
                        </div>
                    </div>

                    <?php if ($isReadable && $progress): ?>
                        <div class="mt-3.5 p-3.5 bg-white rounded-xl border border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                            <div class="flex-1 max-w-sm">
                                <div class="flex items-center justify-between text-xs font-bold text-slate-600 mb-1.5">
                                    <span class="flex items-center gap-1.5">
                                        <span>Reading Progress</span>
                                        <span class="text-slate-400 font-normal">(<?= $progress->getUniquePagesCount() ?> of <?= $progress->totalPages ?> <?= $isDocx ? 'sections' : 'pages' ?>)</span>
                                    </span>
                                    <span class="<?= $progress->isCompleted() ? 'text-emerald-600 font-extrabold' : 'text-sky-600' ?>"><?= (int)$progress->progressPercent ?>%</span>
                                </div>
                                <div class="w-full bg-slate-100 rounded-full h-2 overflow-hidden">
                                    <div class="h-2 rounded-full transition-all duration-300 <?= $progress->isCompleted() ? 'bg-emerald-500' : 'bg-sky-500' ?>" style="width: <?= min(100, (int)$progress->progressPercent) ?>%"></div>
                                </div>
                            </div>
                            <?php if ($progress->isCompleted()): ?>
                                <span class="inline-flex items-center gap-1 px-3 py-1 rounded-lg text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                    <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                    <span>Completed <?= $progress->completedAt ? date('M d, Y', strtotime($progress->completedAt)) : '' ?></span>
                                </span>
                            <?php else: ?>
                                <span class="text-xs text-slate-500 font-medium">
                                    Last read: <?= $isDocx ? 'Section ' : 'Page ' ?><?= (int)$progress->lastPage ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <?php if (!empty($sections)): ?>
            <!-- Logical Learning Sections Breakdown (Phase 3 & 4) -->
            <div class="pt-6 border-t border-slate-100">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400">Document Learning Sections</h3>
                        <p class="text-xs text-slate-500">Read through sections sequentially or jump to a specific chapter.</p>
                    </div>
                    <span class="text-xs font-bold text-slate-600 bg-slate-100 px-2.5 py-1 rounded-lg">
                        <?= count(array_filter($sections, fn($s) => $s->isCompleted ?? false)) ?> of <?= count($sections) ?> Completed
                    </span>
                </div>

                <div class="space-y-3">
                    <?php foreach ($sections as $index => $sec): 
                        $isSecUnlocked = $sec->isUnlocked ?? true;
                        $secPrereqStatus = $sec->prerequisiteStatus ?? null;
                    ?>
                        <div class="p-4 bg-slate-50 rounded-2xl border <?= $isSecUnlocked ? 'border-slate-200 hover:border-emerald-300' : 'border-amber-200 bg-amber-50/40' ?> flex flex-col sm:flex-row sm:items-center justify-between gap-4 transition">
                            <div class="flex items-start gap-3">
                                <span class="inline-flex items-center justify-center w-7 h-7 rounded-xl text-xs font-bold flex-shrink-0 <?= ($sec->isCompleted ?? false) ? 'bg-emerald-100 text-emerald-700' : ($isSecUnlocked ? 'bg-slate-200 text-slate-700' : 'bg-amber-100 text-amber-700') ?>">
                                    <?php if ($sec->isCompleted ?? false): ?>
                                        ✓
                                    <?php elseif ($isSecUnlocked): ?>
                                        <?= (int)$sec->sequenceOrder ?>
                                    <?php else: ?>
                                        🔒
                                    <?php endif; ?>
                                </span>
                                <div>
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <h4 class="text-sm font-bold text-slate-900"><?= htmlspecialchars($sec->title) ?></h4>
                                        <?php if ($sec->isCompleted ?? false): ?>
                                            <span class="inline-flex items-center gap-0.5 px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                                Completed ✓
                                            </span>
                                        <?php elseif (!$isSecUnlocked): ?>
                                            <span class="inline-flex items-center gap-0.5 px-2 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-200">
                                                🔒 Locked
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <p class="text-xs text-slate-500 mt-0.5">
                                        <span class="font-mono">Pages <?= (int)$sec->startPage ?> &ndash; <?= (int)$sec->endPage ?></span>
                                        <span>&bull;</span>
                                        <span><?= (int)$sec->getTotalPages() ?> <?= $sec->getTotalPages() === 1 ? 'page' : 'pages' ?></span>
                                    </p>
                                    <?php if (!$isSecUnlocked && !empty($secPrereqStatus['unmet'])): ?>
                                        <p class="text-[11px] text-amber-800 font-semibold mt-1 flex items-center gap-1">
                                            <span>Requires:</span>
                                            <span class="font-bold"><?= implode(', ', array_map(fn($u) => htmlspecialchars($u['title']), $secPrereqStatus['unmet'])) ?></span>
                                        </p>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="flex items-center gap-4 sm:flex-shrink-0">
                                <div class="w-28 sm:w-32">
                                    <div class="flex items-center justify-between text-[11px] font-bold text-slate-600 mb-1">
                                        <span>Progress</span>
                                        <span class="<?= ($sec->isCompleted ?? false) ? 'text-emerald-600 font-extrabold' : 'text-sky-600' ?>">
                                            <?= number_format((float)($sec->progressPercent ?? 0), 0) ?>%
                                        </span>
                                    </div>
                                    <div class="w-full bg-slate-200 rounded-full h-1.5 overflow-hidden">
                                        <div class="h-1.5 rounded-full transition-all duration-300 <?= ($sec->isCompleted ?? false) ? 'bg-emerald-500' : 'bg-sky-500' ?>" 
                                             style="width: <?= min(100, (float)($sec->progressPercent ?? 0)) ?>%"></div>
                                    </div>
                                </div>

                                <?php if ($isSecUnlocked): ?>
                                    <a href="/student/content/<?= (int)$item->id ?>/read?page=<?= (int)$sec->startPage ?>" 
                                       class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold transition shadow-2xs <?= ($sec->isCompleted ?? false) ? 'bg-slate-100 hover:bg-slate-200 text-slate-700' : 'bg-emerald-600 hover:bg-emerald-700 text-white' ?>">
                                        <span><?= ($sec->isCompleted ?? false) ? 'Review' : 'Read' ?></span>
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                                        </svg>
                                    </a>
                                <?php else: ?>
                                    <button disabled 
                                       class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold bg-amber-100 text-amber-800 border border-amber-200 cursor-not-allowed">
                                        <span>🔒 Locked</span>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
