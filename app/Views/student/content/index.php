<div class="space-y-6">
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
                    <span class="text-slate-700">Study Materials</span>
                </nav>
                <div class="flex flex-wrap items-center gap-3">
                    <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight leading-tight">
                        Learning Materials & Notes
                    </h1>
                    <?php if ($selectedClassSubject): ?>
                        <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold bg-sky-50 text-sky-800 border border-sky-200">
                            <?= htmlspecialchars($selectedClassSubject->subject?->name ?? 'Course') ?>
                        </span>
                    <?php endif; ?>
                </div>
                <p class="text-xs text-slate-500 mt-1">
                    Access lecture notes, downloadable PDF documents, slides, and educational resources.
                </p>
            </div>

            <div class="flex items-center gap-2.5 flex-wrap">
                <?php if ($selectedClassSubject): ?>
                    <a href="/student/content" 
                       class="inline-flex items-center gap-1.5 px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl transition">
                        <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                        <span>All Subjects</span>
                    </a>
                <?php else: ?>
                    <a href="/student/subjects" 
                       class="inline-flex items-center gap-1.5 px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl transition">
                        <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                        <span>Subject Overview</span>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php if (!empty($selectedClassSubject)): ?>
    <!-- 4-Card KPI Summary Metrics Strip -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Total Available Materials -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs">
            <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Total Lessons</p>
            <div class="flex items-baseline gap-2 mt-1">
                <h3 class="text-2xl font-extrabold text-slate-900"><?= number_format(count($items)) ?></h3>
                <span class="text-xs font-semibold text-slate-500">materials</span>
            </div>
            <span class="text-[11px] font-medium text-sky-600 mt-1 block">
                Available in this course
            </span>
        </div>

        <!-- Selected Subject Code -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs">
            <p class="text-xs font-bold uppercase tracking-wider text-sky-600">Subject Code</p>
            <div class="flex items-baseline gap-2 mt-1">
                <h3 class="text-xl font-extrabold text-slate-900 truncate">
                    <?= htmlspecialchars($selectedClassSubject?->subject?->code ?: 'ALL') ?>
                </h3>
            </div>
            <span class="text-[11px] font-medium text-slate-500 mt-1 block truncate">
                <?= htmlspecialchars($selectedClassSubject?->schoolClass?->name ?? 'All Subjects') ?>
            </span>
        </div>

        <!-- Course Instructor -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs">
            <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Teacher</p>
            <div class="flex items-baseline gap-2 mt-1">
                <h3 class="text-base font-extrabold text-slate-900 truncate">
                    <?= htmlspecialchars($selectedClassSubject?->teacher?->user?->name ?? ($selectedClassSubject?->teacher?->name ?? 'Subject Teacher')) ?>
                </h3>
            </div>
            <span class="text-[11px] font-medium text-slate-500 mt-1 block">
                Assigned Instructor
            </span>
        </div>

        <!-- Active Term -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs">
            <p class="text-xs font-bold uppercase tracking-wider text-emerald-600">Current Term</p>
            <div class="flex items-baseline gap-2 mt-1">
                <h3 class="text-base font-extrabold text-emerald-600 truncate">
                    <?= htmlspecialchars($activeTerm?->name ?? 'Current Term') ?>
                </h3>
            </div>
            <span class="text-[11px] font-medium text-emerald-600 mt-1 block">
                <?= htmlspecialchars($activeSession?->name ?? 'Active Session') ?>
            </span>
        </div>
    </div>

    <!-- Course Filter & Search Bar -->
    <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs">
        <form method="GET" action="/student/content" class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-4">
            <div class="flex items-center gap-3 flex-1">
                <label for="class_subject_id" class="text-xs font-bold uppercase tracking-wider text-slate-500 flex-shrink-0">
                    Switch Subject:
                </label>
                <select name="class_subject_id" id="class_subject_id" onchange="this.form.submit()"
                        class="w-full sm:max-w-md rounded-xl border border-slate-300 bg-slate-50 text-xs font-bold text-slate-900 focus:bg-white focus:border-sky-500 focus:ring-sky-500 py-2.5 px-3 transition">
                    <?php if (empty($subjectEnrollments)): ?>
                        <option value="">No enrolled subjects</option>
                    <?php else: ?>
                        <?php foreach ($subjectEnrollments as $se): ?>
                            <?php $cs = $se->classSubject; ?>
                            <option value="<?= (int)$se->classSubjectId ?>" <?= $se->classSubjectId === $selectedClassSubjectId ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cs?->subject?->name ?? 'Subject') ?> (<?= htmlspecialchars($cs?->subject?->code ?? '') ?>) &bull; <?= htmlspecialchars($cs?->schoolClass?->name ?? '') ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>

            <div class="relative sm:w-72">
                <input type="text" id="content-search" placeholder="Search lesson topics..." 
                       oninput="filterContentCards(this.value)"
                       class="w-full text-xs font-semibold rounded-xl border border-slate-300 bg-slate-50 focus:bg-white focus:border-sky-500 focus:ring-sky-500 py-2 pl-9 pr-3 transition">
                <svg class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>
        </form>
    </div>
    <?php endif; ?>

    <?php if (empty($selectedClassSubject)): ?>
        <!-- Subjects Cards Overview Grid -->
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-base font-bold text-slate-900">Your Enrolled Subjects</h2>
                    <p class="text-xs text-slate-500">Click any subject card to access notes, documents, and video lessons.</p>
                </div>
                <span class="text-xs font-bold font-mono text-slate-600 bg-slate-100 border border-slate-200 px-3 py-1 rounded-xl">
                    <?= count($subjectEnrollments) ?> Subjects
                </span>
            </div>

            <?php if (empty($subjectEnrollments)): ?>
                <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-12 text-center">
                    <p class="text-sm font-semibold text-slate-600">No enrolled subjects found for the current academic session.</p>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
                    <?php foreach ($subjectEnrollments as $se): 
                        $cs = $se->classSubject;
                        if (!$cs) continue;
                        $matCount = (int)($subjectMaterialCounts[$cs->id] ?? 0);
                        $teacherName = $cs->teacher?->user?->name ?? ($cs->teacher?->name ?? 'Subject Teacher');
                    ?>
                        <a href="/student/content?class_subject_id=<?= (int)$cs->id ?>" 
                           class="group bg-white rounded-2xl border border-slate-200 hover:border-sky-500 hover:shadow-md p-6 flex flex-col justify-between transition duration-200 space-y-5">
                            <div>
                                <div class="flex items-start justify-between gap-3 mb-3">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-mono font-bold bg-sky-50 text-sky-700 border border-sky-200">
                                        <?= htmlspecialchars($cs->subject?->code ?? 'SUBJ') ?>
                                    </span>
                                    <span class="inline-flex items-center gap-1 text-[11px] font-bold text-slate-500 bg-slate-100 px-2.5 py-1 rounded-full">
                                        <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                                        <?= $matCount ?> <?= $matCount === 1 ? 'Material' : 'Materials' ?>
                                    </span>
                                </div>
                                <h3 class="text-base font-extrabold text-slate-900 group-hover:text-sky-600 transition leading-snug">
                                    <?= htmlspecialchars($cs->subject?->name ?? 'Subject') ?>
                                </h3>
                                <p class="text-xs text-slate-500 mt-1 flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                    <span><?= htmlspecialchars($cs->schoolClass?->name ?? 'Class') ?></span>
                                </p>
                            </div>

                            <div class="pt-4 border-t border-slate-100 flex items-center justify-between text-xs">
                                <div class="flex items-center gap-2 text-slate-600">
                                    <div class="w-6 h-6 rounded-full bg-slate-100 text-slate-600 flex items-center justify-center font-bold text-[10px]">
                                        <?= strtoupper(substr($teacherName, 0, 1)) ?>
                                    </div>
                                    <span class="truncate max-w-[140px] font-medium text-[11px]"><?= htmlspecialchars($teacherName) ?></span>
                                </div>
                                <span class="font-bold text-sky-600 group-hover:translate-x-0.5 transition inline-flex items-center gap-1">
                                    View Materials &rarr;
                                </span>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    <?php elseif (empty($items)): ?>
        <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-12 text-center">
            <div class="w-16 h-16 rounded-2xl bg-sky-50 text-sky-600 mx-auto flex items-center justify-center mb-4">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
            </div>
            <h3 class="text-base font-bold text-slate-800">No Study Materials Published Yet</h3>
            <p class="text-xs text-slate-500 mt-1.5 max-w-sm mx-auto">
                Your instructor has not uploaded any study notes or files for <?= htmlspecialchars($selectedClassSubject->subject?->name ?? 'this subject') ?> yet.
            </p>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" id="content-grid">
            <?php foreach ($items as $item): ?>
                <div class="content-card bg-white rounded-2xl border border-slate-200 shadow-xs flex flex-col justify-between hover:border-slate-300 transition overflow-hidden p-6 space-y-4"
                     data-search="<?= strtolower(htmlspecialchars($item->title . ' ' . ($item->topic ?? '') . ' ' . ($item->description ?? ''))) ?>">
                    <div>
                        <div class="flex items-center justify-between gap-2 mb-3">
                            <div class="flex items-center gap-2">
                                <?php if ($item->type === 'note'): ?>
                                    <span class="px-2 py-0.5 rounded-lg text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-200">
                                        Lecture Note
                                    </span>
                                <?php elseif ($item->type === 'document'): ?>
                                    <span class="px-2 py-0.5 rounded-lg text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        Document
                                    </span>
                                <?php elseif ($item->type === 'video'): ?>
                                    <span class="px-2 py-0.5 rounded-lg text-[10px] font-bold bg-purple-50 text-purple-700 border border-purple-200">
                                        Video Tutorial
                                    </span>
                                <?php else: ?>
                                    <span class="px-2 py-0.5 rounded-lg text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                        Web Link
                                    </span>
                                <?php endif; ?>
                            </div>
                            <span class="text-[10px] font-semibold text-slate-400">
                                <?= date('M d, Y', strtotime($item->publishedAt ?? $item->createdAt)) ?>
                            </span>
                        </div>

                        <?php if (!empty($item->topic)): ?>
                            <span class="text-[10px] font-bold text-sky-600 uppercase tracking-wider block mb-1">
                                <?= htmlspecialchars($item->topic) ?>
                            </span>
                        <?php endif; ?>

                        <h3 class="text-base font-bold text-slate-900 leading-snug">
                            <a href="/student/content/<?= (int)$item->id ?>" class="hover:text-sky-600 transition">
                                <?= htmlspecialchars($item->title) ?>
                            </a>
                        </h3>

                        <?php if (!empty($item->description)): ?>
                            <p class="text-xs text-slate-500 line-clamp-2 leading-relaxed mt-2">
                                <?= htmlspecialchars($item->description) ?>
                            </p>
                        <?php endif; ?>

                        <?php $prog = ($progressMap ?? [])[(int)$item->id] ?? null; ?>
                        <?php if ($prog): ?>
                            <div class="mt-3 pt-3 border-t border-slate-100">
                                <div class="flex items-center justify-between text-[11px] font-bold text-slate-500 mb-1">
                                    <span>Reading Progress</span>
                                    <span class="<?= $prog->isCompleted() ? 'text-emerald-700 font-extrabold' : 'text-sky-700' ?>">
                                        <?= $prog->isCompleted() ? 'Completed ✓' : ((int)$prog->progressPercent . '% read') ?>
                                    </span>
                                </div>
                                <div class="w-full bg-slate-100 rounded-full h-1.5 overflow-hidden">
                                    <div class="h-1.5 rounded-full transition-all duration-300 <?= $prog->isCompleted() ? 'bg-emerald-500' : 'bg-sky-500' ?>" style="width: <?= min(100, (int)$prog->progressPercent) ?>%"></div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="pt-3 border-t border-slate-100 flex items-center justify-between gap-2">
                        <a href="/student/content/<?= (int)$item->id ?>" class="text-xs font-bold text-sky-600 hover:text-sky-700 transition">
                            Read Lesson &rarr;
                        </a>

                        <?php if ($item->file): ?>
                            <div class="flex items-center gap-1.5 flex-wrap">
                                <?php 
                                $isCardPdf = $item->file->mimeType === 'application/pdf' || str_ends_with(strtolower($item->file->originalName), '.pdf');
                                $isCardDocx = $item->file->mimeType === 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
                                    || $item->file->mimeType === 'application/zip'
                                    || $item->file->mimeType === 'application/msword'
                                    || str_ends_with(strtolower($item->file->originalName), '.docx');
                                ?>
                                <?php if ($isCardPdf || $isCardDocx): ?>
                                    <a href="/student/content/<?= (int)$item->id ?>/read" 
                                       class="inline-flex items-center gap-1 px-2.5 py-1 <?= ($prog && $prog->isCompleted()) ? 'bg-emerald-100 text-emerald-800 border-emerald-300' : 'bg-emerald-50 text-emerald-700 border-emerald-200' ?> hover:bg-emerald-100 border rounded-xl text-xs font-bold transition">
                                        <svg class="w-3 h-3 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                                        </svg>
                                        <span><?= ($prog && $prog->lastPage > 1 && !$prog->isCompleted()) ? ($isCardDocx ? 'Resume (s.' . (int)$prog->lastPage . ')' : 'Resume (p.' . (int)$prog->lastPage . ')') : ($prog && $prog->isCompleted() ? 'Read ✓' : 'Read') ?></span>
                                    </a>
                                <?php endif; ?>
                                <a href="/files/<?= (int)$item->file->id ?>/download" 
                                   class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition shadow-2xs">
                                    <svg class="w-3.5 h-3.5 text-sky-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                    </svg>
                                    <span><?= htmlspecialchars($item->file->getFormattedSize()) ?></span>
                                </a>
                            </div>
                        <?php elseif (!empty($item->externalUrl)): ?>
                            <a href="<?= htmlspecialchars($item->externalUrl) ?>" target="_blank" rel="noopener noreferrer"
                               class="inline-flex items-center gap-1 text-xs font-bold text-purple-600 hover:underline">
                                <span>Open Link</span>
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                </svg>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script>
function filterContentCards(query) {
    const q = query.trim().toLowerCase();
    const cards = document.querySelectorAll('.content-card');
    cards.forEach(card => {
        const text = card.getAttribute('data-search') || '';
        if (!q || text.includes(q)) {
            card.style.display = 'flex';
        } else {
            card.style.display = 'none';
        }
    });
}
</script>
