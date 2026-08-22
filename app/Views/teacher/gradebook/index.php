<div class="space-y-6">
    <!-- Header Card -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <!-- Breadcrumbs -->
                <nav class="flex items-center gap-2 text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">
                    <a href="/teacher/dashboard" class="text-slate-400 hover:text-emerald-600 transition">Faculty Portal</a>
                    <span class="text-slate-300">/</span>
                    <span class="text-slate-700">Academic Gradebooks</span>
                </nav>
                <div class="flex flex-wrap items-center gap-3">
                    <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight leading-tight">
                        Continuous Assessment & Gradebooks
                    </h1>
                    <?php if ($activeTerm): ?>
                        <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold bg-emerald-50 text-emerald-800 border border-emerald-200">
                            <?= htmlspecialchars($activeTerm->name) ?>
                        </span>
                    <?php endif; ?>
                </div>
                <p class="text-xs text-slate-500 mt-1">
                    Select an allocated class cohort to record Continuous Assessment (CA) tests, practical tasks, midterm marks, and final exam scores.
                </p>
            </div>
        </div>
    </div>

    <!-- 4-Card KPI Summary Metrics Strip -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Assigned Class-Subjects -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs">
            <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Assigned Cohorts</p>
            <div class="flex items-baseline gap-2 mt-1">
                <h3 class="text-2xl font-extrabold text-slate-900"><?= number_format(count($classSubjects)) ?></h3>
                <span class="text-xs font-semibold text-slate-500">classes</span>
            </div>
            <span class="text-[11px] font-medium text-slate-500 mt-1 block">
                Teaching allocations
            </span>
        </div>

        <!-- Academic Session -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs">
            <p class="text-xs font-bold uppercase tracking-wider text-emerald-600">Active Session</p>
            <div class="flex items-baseline gap-2 mt-1">
                <h3 class="text-lg font-extrabold text-slate-900 truncate"><?= htmlspecialchars($activeSession?->name ?? 'Current Session') ?></h3>
            </div>
            <span class="text-[11px] font-medium text-emerald-600 mt-1 block">
                Official calendar year
            </span>
        </div>

        <!-- Academic Term -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs">
            <p class="text-xs font-bold uppercase tracking-wider text-amber-500">Current Term</p>
            <div class="flex items-baseline gap-2 mt-1">
                <h3 class="text-lg font-extrabold text-slate-900 truncate"><?= htmlspecialchars($activeTerm?->name ?? 'Current Term') ?></h3>
            </div>
            <span class="text-[11px] font-medium text-amber-600/90 mt-1 block">
                Active grading period
            </span>
        </div>

        <!-- System Policy -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs">
            <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Grading Policy</p>
            <div class="flex items-baseline gap-2 mt-1">
                <h3 class="text-lg font-extrabold text-slate-900">Weighted CA</h3>
            </div>
            <span class="text-[11px] font-medium text-slate-500 mt-1 block">
                Auto-computed totals & scale
            </span>
        </div>
    </div>

    <!-- Search/Filter Bar -->
    <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-xs">
        <div class="relative">
            <input type="text" id="gradebook-search" placeholder="Search assigned subject or class cohort..." 
                   oninput="filterGradebooks(this.value)"
                   class="w-full text-xs font-semibold rounded-xl border border-slate-300 bg-slate-50 focus:bg-white focus:border-emerald-500 focus:ring-emerald-500 py-2.5 pl-10 pr-4 transition">
            <svg class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
        </div>
    </div>

    <!-- Gradebook Cards Grid -->
    <?php if (empty($classSubjects)): ?>
        <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-12 text-center">
            <div class="w-16 h-16 rounded-2xl bg-emerald-50 text-emerald-600 mx-auto flex items-center justify-center mb-4">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                </svg>
            </div>
            <h3 class="text-base font-bold text-slate-900">No Assigned Classes Found</h3>
            <p class="text-xs text-slate-500 mt-1.5 max-w-sm mx-auto">
                You have not been assigned to any class cohorts for the current academic session. Contact administration if this is unexpected.
            </p>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" id="gradebooks-grid">
            <?php foreach ($classSubjects as $cs): ?>
                <?php 
                    $sName = $cs->subject?->name ?? 'Subject';
                    $sCode = $cs->subject?->code ?? '';
                    $cName = $cs->schoolClass?->name ?? ($cs->class?->name ?? 'Class');
                    $arm = $cs->schoolClass?->sectionArm ?? '';
                ?>
                <div class="gradebook-card bg-white rounded-2xl border border-slate-200 shadow-xs flex flex-col justify-between hover:border-slate-300 transition overflow-hidden p-6"
                     data-search="<?= strtolower(htmlspecialchars($sName . ' ' . $sCode . ' ' . $cName . ' ' . $arm)) ?>">
                    <div>
                        <div class="flex items-center justify-between gap-2 mb-3">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold bg-slate-100 text-slate-700">
                                <?= htmlspecialchars($cName) ?><?= !empty($arm) ? ' (' . htmlspecialchars($arm) . ')' : '' ?>
                            </span>
                            <span class="text-[11px] font-semibold text-slate-400">
                                <?= htmlspecialchars($activeTerm?->name ?? 'Current Term') ?>
                            </span>
                        </div>

                        <h3 class="text-base font-bold text-slate-900 leading-snug">
                            <?= htmlspecialchars($sName) ?>
                        </h3>
                        <?php if (!empty($sCode)): ?>
                            <p class="text-xs text-slate-400 font-mono font-semibold mt-1">
                                Code: <?= htmlspecialchars($sCode) ?>
                            </p>
                        <?php endif; ?>
                    </div>

                    <div class="mt-6 pt-4 border-t border-slate-100 flex items-center justify-between gap-2">
                        <span class="text-xs font-medium text-slate-500 flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span>Open for scoring</span>
                        </span>

                        <a href="/teacher/gradebook/<?= (int)$cs->id ?>" 
                           class="inline-flex items-center gap-1.5 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl shadow-xs transition">
                            <span>Open Gradebook</span>
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                            </svg>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script>
function filterGradebooks(query) {
    const q = query.trim().toLowerCase();
    const cards = document.querySelectorAll('.gradebook-card');
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
