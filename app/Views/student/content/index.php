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
                <?php $this->include('components/button', [
                    'label' => 'Back to Subjects',
                    'variant' => 'secondary',
                    'href' => '/student/subjects',
                    'icon' => '<svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>'
                ]); ?>
            </div>
        </div>
    </div>

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
                    Filter by Subject:
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

    <!-- Content Items Grid -->
    <?php if (empty($selectedClassSubject)): ?>
        <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-12 text-center">
            <div class="w-16 h-16 rounded-2xl bg-slate-100 text-slate-400 mx-auto flex items-center justify-center mb-4">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                </svg>
            </div>
            <h3 class="text-base font-bold text-slate-800">No Enrolled Subject Selected</h3>
            <p class="text-xs text-slate-500 mt-1">Please select an enrolled subject from the dropdown above.</p>
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
                            <p class="text-xs text-slate-500 line-clamp-3 leading-relaxed mt-2">
                                <?= htmlspecialchars($item->description) ?>
                            </p>
                        <?php endif; ?>
                    </div>

                    <div class="pt-3 border-t border-slate-100 flex items-center justify-between gap-2">
                        <a href="/student/content/<?= (int)$item->id ?>" class="text-xs font-bold text-sky-600 hover:text-sky-700 transition">
                            Read Lesson &rarr;
                        </a>

                        <?php if ($item->file): ?>
                            <a href="/files/<?= (int)$item->file->id ?>/download" 
                               class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition shadow-2xs">
                                <svg class="w-3.5 h-3.5 text-sky-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                </svg>
                                <span><?= htmlspecialchars($item->file->getFormattedSize()) ?></span>
                            </a>
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
