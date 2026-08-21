<?php
/**
 * Admin Timetables Overview View (ADMIN-23)
 *
 * @var string $title
 * @var string $headerTitle
 * @var array $terms
 * @var object|null $selectedTerm
 * @var array $classes
 * @var array $classSlotsCount
 */

$totalClasses = count($classes);
$totalPeriodsScheduled = array_sum($classSlotsCount);
$configuredCount = count(array_filter($classSlotsCount, fn($cnt) => $cnt > 0));
$pendingCount = $totalClasses - $configuredCount;
?>

<div class="space-y-6">

    <!-- Page Header & Filter Card -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-xs p-6">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div>
                <!-- Breadcrumbs -->
                <nav class="flex items-center gap-2 text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1" aria-label="Breadcrumb">
                    <a href="/admin/dashboard" class="hover:text-brand-600 transition-colors">Admin</a>
                    <svg class="w-3 h-3 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                    </svg>
                    <span class="text-brand-600">Timetable</span>
                </nav>
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Class Timetables Management</h1>
                <p class="text-sm text-slate-500 mt-1">
                    Manage weekly instructional period schedules, teacher assignments, and classroom venues with collision detection.
                </p>
            </div>

            <!-- Term Filter Form -->
            <div class="flex items-center gap-3">
                <form method="GET" action="/admin/timetable" id="termFilterForm" class="flex items-center gap-2">
                    <label for="term_id" class="text-xs font-semibold text-slate-600 uppercase tracking-wider whitespace-nowrap">
                        Academic Term:
                    </label>
                    <div class="relative">
                        <select
                            name="term_id"
                            id="term_id"
                            onchange="this.form.submit()"
                            class="bg-slate-50 border border-slate-300 text-slate-900 text-sm font-semibold rounded-lg px-3.5 py-2 pr-8 focus:ring-2 focus:ring-brand-500 focus:border-brand-500 focus:bg-white transition-colors cursor-pointer appearance-none"
                        >
                            <?php foreach ($terms as $t): ?>
                                <option value="<?= (int)$t->id ?>" <?= $selectedTerm && (int)$selectedTerm->id === (int)$t->id ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($t->name) ?> <?= $t->isActive() ? '★ Active' : '' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-slate-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- KPI Metric Strip -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <!-- Total Classes -->
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-xs flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-slate-100 text-slate-700 flex items-center justify-center flex-shrink-0">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                </svg>
            </div>
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Total Classes</p>
                <h3 class="text-2xl font-bold text-slate-900 mt-0.5"><?= number_format($totalClasses) ?></h3>
            </div>
        </div>

        <!-- Configured Timetables -->
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-xs flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center flex-shrink-0">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Active Schedules</p>
                <h3 class="text-2xl font-bold text-emerald-700 mt-0.5"><?= number_format($configuredCount) ?></h3>
            </div>
        </div>

        <!-- Pending Configuration -->
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-xs flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center flex-shrink-0">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            </div>
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Unconfigured</p>
                <h3 class="text-2xl font-bold text-amber-700 mt-0.5"><?= number_format($pendingCount) ?></h3>
            </div>
        </div>

        <!-- Total Periods -->
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-xs flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-brand-50 text-brand-700 flex items-center justify-center flex-shrink-0">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
            </div>
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Scheduled Periods</p>
                <h3 class="text-2xl font-bold text-brand-800 mt-0.5"><?= number_format($totalPeriodsScheduled) ?></h3>
            </div>
        </div>
    </div>

    <!-- Main Classes Content Card -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">

        <!-- Toolbar: Search & Status Filters -->
        <div class="p-4 bg-slate-50/70 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <!-- Search Input -->
            <div class="relative flex-1 max-w-md">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                <input
                    type="text"
                    id="classSearchInput"
                    placeholder="Search class cohort, arm, or level..."
                    class="block w-full pl-9 pr-4 py-2 border border-slate-300 rounded-lg text-sm bg-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 transition-colors"
                >
            </div>

            <!-- Status Filter Toggle Buttons -->
            <div class="flex items-center gap-1.5 flex-wrap" id="statusFilterButtons">
                <button
                    type="button"
                    data-filter="all"
                    class="status-btn px-3 py-1.5 rounded-lg text-xs font-semibold transition-all bg-brand-600 text-white shadow-xs"
                >
                    All Classes (<?= $totalClasses ?>)
                </button>
                <button
                    type="button"
                    data-filter="configured"
                    class="status-btn px-3 py-1.5 rounded-lg text-xs font-medium text-slate-600 bg-white border border-slate-300 hover:bg-slate-50 transition-all"
                >
                    Active (<?= $configuredCount ?>)
                </button>
                <button
                    type="button"
                    data-filter="pending"
                    class="status-btn px-3 py-1.5 rounded-lg text-xs font-medium text-slate-600 bg-white border border-slate-300 hover:bg-slate-50 transition-all"
                >
                    Unconfigured (<?= $pendingCount ?>)
                </button>
            </div>
        </div>

        <?php if (empty($classes)): ?>
            <div class="p-12">
                <?php
                echo $this->render('components/empty_state', [
                    'title' => 'No Classes Found',
                    'message' => 'Setup classes and arms in the academic setup before configuring timetables.',
                    'actionLabel' => 'Manage Classes',
                    'actionUrl' => '/admin/classes',
                    'icon' => '<svg class="mx-auto h-12 w-12 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>',
                ]);
                ?>
            </div>
        <?php else: ?>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5" id="classesGrid">
                    <?php foreach ($classes as $cls): ?>
                        <?php
                        $slotCount = $classSlotsCount[$cls->id] ?? 0;
                        $hasSlots = $slotCount > 0;
                        $armLabel = $cls->sectionArm ? "Arm {$cls->sectionArm}" : 'Standard Class';
                        $editUrl = "/admin/timetable/{$cls->id}/edit" . ($selectedTerm ? "?term_id={$selectedTerm->id}" : '');
                        ?>
                        <div
                            class="class-card bg-slate-50/50 hover:bg-white p-6 rounded-xl border border-slate-200 hover:border-brand-500 hover:shadow-md transition-all flex flex-col justify-between group"
                            data-status="<?= $hasSlots ? 'configured' : 'pending' ?>"
                            data-search="<?= htmlspecialchars(strtolower($cls->name . ' ' . $armLabel)) ?>"
                        >
                            <div class="space-y-3">
                                <!-- Top Row: Arm & Period Badge -->
                                <div class="flex items-center justify-between">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-600 border border-slate-200">
                                        <?= htmlspecialchars($armLabel) ?>
                                    </span>

                                    <?php if ($hasSlots): ?>
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                            <?= $slotCount ?> <?= $slotCount === 1 ? 'Period' : 'Periods' ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-500 border border-slate-200">
                                            0 Periods
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <!-- Class Name -->
                                <div>
                                    <h3 class="text-lg font-bold text-slate-900 group-hover:text-brand-700 transition-colors">
                                        <?= htmlspecialchars($cls->name) ?>
                                    </h3>
                                    <p class="text-xs text-slate-500 mt-1">
                                        <?= $hasSlots ? "Active weekly schedule for {$selectedTerm?->name}." : 'No timetable slots scheduled yet for this term.' ?>
                                    </p>
                                </div>
                            </div>

                            <!-- Footer Action Button -->
                            <div class="mt-6 pt-4 border-t border-slate-200/80 flex items-center justify-between">
                                <span class="text-xs text-slate-400">
                                    <?= $hasSlots ? 'Configured' : 'Needs Setup' ?>
                                </span>

                                <?php
                                echo $this->render('components/button', [
                                    'label' => $hasSlots ? 'Open Builder' : 'Build Schedule',
                                    'variant' => $hasSlots ? 'secondary' : 'primary',
                                    'href' => $editUrl,
                                    'icon' => '<svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" /></svg>',
                                    'class' => 'text-xs py-1.5 px-3',
                                ]);
                                ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- No Search Results Dynamic State -->
                <div id="noClassMatches" class="hidden p-12 text-center">
                    <svg class="mx-auto h-10 w-10 text-slate-300 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <h4 class="text-sm font-bold text-slate-900">No matching classes found</h4>
                    <p class="text-xs text-slate-500 mt-1">Try adjusting your keyword or filter status.</p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Client-side Search & Status Filter Script -->
<script>
document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('classSearchInput');
    const filterButtons = document.querySelectorAll('.status-btn');
    const cards = document.querySelectorAll('.class-card');
    const noResults = document.getElementById('noClassMatches');

    let currentFilter = 'all';
    let searchQuery = '';

    function filterClasses() {
        let visibleCount = 0;
        const query = searchQuery.trim().toLowerCase();

        cards.forEach(card => {
            const status = card.getAttribute('data-status');
            const searchData = card.getAttribute('data-search') || '';

            // Check Status Filter
            let matchesFilter = true;
            if (currentFilter === 'configured') {
                matchesFilter = (status === 'configured');
            } else if (currentFilter === 'pending') {
                matchesFilter = (status === 'pending');
            }

            // Check Search Text
            let matchesSearch = true;
            if (query.length > 0) {
                matchesSearch = searchData.includes(query);
            }

            if (matchesFilter && matchesSearch) {
                card.classList.remove('hidden');
                visibleCount++;
            } else {
                card.classList.add('hidden');
            }
        });

        if (noResults) {
            if (visibleCount === 0 && cards.length > 0) {
                noResults.classList.remove('hidden');
            } else {
                noResults.classList.add('hidden');
            }
        }
    }

    if (searchInput) {
        searchInput.addEventListener('input', (e) => {
            searchQuery = e.target.value;
            filterClasses();
        });
    }

    filterButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            filterButtons.forEach(b => {
                b.classList.remove('bg-brand-600', 'text-white', 'shadow-xs');
                b.classList.add('bg-white', 'text-slate-600', 'border', 'border-slate-300');
            });
            btn.classList.remove('bg-white', 'text-slate-600', 'border', 'border-slate-300');
            btn.classList.add('bg-brand-600', 'text-white', 'shadow-xs');

            currentFilter = btn.getAttribute('data-filter');
            filterClasses();
        });
    });
});
</script>
