<div class="space-y-6">
    <!-- Header Card -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <!-- Breadcrumbs -->
                <nav class="flex items-center gap-2 text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">
                    <a href="/teacher/dashboard" class="text-slate-400 hover:text-emerald-600 transition">Faculty Portal</a>
                    <span class="text-slate-300">/</span>
                    <span class="text-slate-700">Class Workspace</span>
                </nav>
                <div class="flex flex-wrap items-center gap-3">
                    <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight leading-tight">
                        My Classes & Student Rosters
                    </h1>
                    <?php if ($activeTerm): ?>
                        <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold bg-emerald-50 text-emerald-800 border border-emerald-200">
                            <?= htmlspecialchars($activeTerm->name) ?>
                        </span>
                    <?php endif; ?>
                </div>
                <p class="text-xs text-slate-500 mt-1">
                    Manage your assigned teaching cohorts, explore student rosters, verify guardian linkages, and access class workspaces.
                </p>
            </div>
            <div class="flex items-center gap-3">
                <a href="/teacher/gradebook" class="inline-flex items-center gap-2 px-4 py-2.5 text-xs font-bold text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-xl transition">
                    <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Institutional Gradebook
                </a>
            </div>
        </div>
    </div>

    <!-- 4-Card KPI Summary Metrics Strip -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Assigned Cohorts -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs">
            <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Assigned Cohorts</p>
            <div class="flex items-baseline gap-2 mt-1">
                <h3 class="text-2xl font-extrabold text-slate-900"><?= number_format($totalCohorts) ?></h3>
                <span class="text-xs font-semibold text-slate-500">subject classes</span>
            </div>
            <span class="text-[11px] font-medium text-slate-500 mt-1 block">
                Active teaching allocations
            </span>
        </div>

        <!-- Total Enrolled Candidates -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs">
            <p class="text-xs font-bold uppercase tracking-wider text-emerald-600">Total Students</p>
            <div class="flex items-baseline gap-2 mt-1">
                <h3 class="text-2xl font-extrabold text-slate-900"><?= number_format($totalStudents) ?></h3>
                <span class="text-xs font-semibold text-slate-500">candidates</span>
            </div>
            <span class="text-[11px] font-medium text-emerald-600 mt-1 block">
                Across all assigned classes
            </span>
        </div>

        <!-- Distinct Class Arms -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs">
            <p class="text-xs font-bold uppercase tracking-wider text-sky-600">Distinct Arms</p>
            <div class="flex items-baseline gap-2 mt-1">
                <h3 class="text-2xl font-extrabold text-slate-900"><?= number_format($totalClasses) ?></h3>
                <span class="text-xs font-semibold text-slate-500">class arms</span>
            </div>
            <span class="text-[11px] font-medium text-sky-600 mt-1 block">
                Grade level cohorts
            </span>
        </div>

        <!-- Academic Session & Term -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs">
            <p class="text-xs font-bold uppercase tracking-wider text-amber-500">Session & Term</p>
            <div class="flex items-baseline gap-2 mt-1">
                <h3 class="text-base font-extrabold text-slate-900 truncate"><?= htmlspecialchars($activeSession?->name ?? 'Active Session') ?></h3>
            </div>
            <span class="text-[11px] font-medium text-amber-600 mt-1 block truncate">
                <?= htmlspecialchars($activeTerm?->name ?? 'Current Academic Term') ?>
            </span>
        </div>
    </div>

    <!-- Search / Filter -->
    <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-xs">
        <div class="relative">
            <input type="text" id="cohort-search" placeholder="Filter by class arm (e.g. JSS 1 Gold) or subject..." 
                   class="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-700 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition">
            <svg class="w-4 h-4 text-slate-400 absolute left-3.5 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
        </div>
    </div>

    <!-- Cohorts Grid -->
    <?php if (empty($cohortData)): ?>
        <div class="bg-white rounded-2xl border border-slate-200 p-12 text-center shadow-xs">
            <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-slate-100 flex items-center justify-center text-slate-400">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
            </div>
            <h3 class="text-base font-bold text-slate-800">No Teaching Cohorts Assigned</h3>
            <p class="text-xs text-slate-500 max-w-sm mx-auto mt-1">
                You currently have no class-subject allocations for this academic term. Contact the administrator if this is an error.
            </p>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5" id="cohorts-container">
            <?php foreach ($cohortData as $row): 
                $cs = $row['classSubject'];
                $class = $row['class'];
                $subject = $row['subject'];
                $enrolled = $row['enrolledCount'];
            ?>
                <div class="cohort-card bg-white rounded-2xl border border-slate-200 hover:border-emerald-300 hover:shadow-md transition-all duration-200 flex flex-col justify-between overflow-hidden group"
                     data-search="<?= htmlspecialchars(strtolower(($class?->name ?? '') . ' ' . ($subject?->name ?? '') . ' ' . ($subject?->code ?? ''))) ?>">
                    
                    <div class="p-5">
                        <!-- Top Badges -->
                        <div class="flex items-center justify-between gap-2 mb-3">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-[11px] font-bold bg-slate-100 text-slate-700 border border-slate-200">
                                <?= htmlspecialchars($class?->name ?? 'Class Arm') ?>
                            </span>
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200/80">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                                <?= number_format($enrolled) ?> <?= $enrolled === 1 ? 'Student' : 'Students' ?>
                            </span>
                        </div>

                        <!-- Subject Title & Code -->
                        <h4 class="text-base font-extrabold text-slate-900 group-hover:text-emerald-600 transition-colors line-clamp-1">
                            <?= htmlspecialchars($subject?->name ?? 'Subject') ?>
                        </h4>
                        <p class="text-xs font-semibold text-slate-400 mt-0.5">
                            Code: <span class="text-slate-600"><?= htmlspecialchars($subject?->code ?? 'N/A') ?></span>
                        </p>

                        <!-- Cohort Meta -->
                        <div class="mt-4 pt-4 border-t border-slate-100 grid grid-cols-2 gap-2 text-xs">
                            <div>
                                <span class="text-slate-400 block text-[10px] uppercase font-bold tracking-wider">Role</span>
                                <span class="font-semibold text-slate-700">Subject Teacher</span>
                            </div>
                            <div>
                                <span class="text-slate-400 block text-[10px] uppercase font-bold tracking-wider">Session</span>
                                <span class="font-semibold text-slate-700 truncate block"><?= htmlspecialchars($activeSession?->name ?? 'Current') ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Action Footer -->
                    <div class="px-5 py-3.5 bg-slate-50 border-t border-slate-100 flex items-center justify-between gap-2">
                        <a href="/teacher/classes/<?= (int)$cs->id ?>" 
                           class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold bg-emerald-600 hover:bg-emerald-700 text-white shadow-xs transition">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                            View Roster
                        </a>

                        <div class="flex items-center gap-1">
                            <a href="/teacher/gradebook/<?= (int)$cs->id ?>" 
                               title="Open Gradebook" 
                               class="p-1.5 text-slate-400 hover:text-emerald-600 hover:bg-white rounded-lg transition">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                            </a>
                            <a href="/teacher/attendance/<?= (int)$cs->classId ?>" 
                               title="Daily Attendance" 
                               class="p-1.5 text-slate-400 hover:text-sky-600 hover:bg-white rounded-lg transition">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                            </a>
                            <a href="/teacher/content" 
                               title="Course Materials" 
                               class="p-1.5 text-slate-400 hover:text-amber-600 hover:bg-white rounded-lg transition">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                                </svg>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('cohort-search');
    const cards = document.querySelectorAll('.cohort-card');

    if (searchInput) {
        searchInput.addEventListener('input', (e) => {
            const query = e.target.value.toLowerCase().trim();
            cards.forEach(card => {
                const searchData = card.getAttribute('data-search') || '';
                if (!query || searchData.includes(query)) {
                    card.style.display = 'flex';
                } else {
                    card.style.display = 'none';
                }
            });
        });
    }
});
</script>
