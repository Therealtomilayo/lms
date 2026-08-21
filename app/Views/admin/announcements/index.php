<?php
/**
 * Admin Announcements Broadcast Hub View (ADMIN-20)
 *
 * @var string $title
 * @var string $headerTitle
 * @var \App\Models\Announcement[] $announcements
 * @var string $csrf_token
 */

$totalCount = count($announcements);
$activeCount = count(array_filter($announcements, fn($a) => $a->isActive()));
$schoolWideCount = count(array_filter($announcements, fn($a) => $a->isSchoolWide()));
$scopedCount = $totalCount - $schoolWideCount;
$csrfToken = $csrf_token ?? ($_SESSION['_csrf_token'] ?? '');
?>

<div class="space-y-6">

    <!-- Page Header & Action Bar -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-xs p-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <!-- Breadcrumbs -->
                <nav class="flex items-center gap-2 text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1" aria-label="Breadcrumb">
                    <a href="/admin/dashboard" class="hover:text-brand-600 transition-colors">Admin</a>
                    <svg class="w-3 h-3 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                    </svg>
                    <span class="text-brand-600">Announcements</span>
                </nav>
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Broadcast Announcements Hub</h1>
                <p class="text-sm text-slate-500 mt-1">
                    Manage institutional bulletins, class-level updates, and subject-specific notices across the school community.
                </p>
            </div>
            <div class="flex items-center gap-3">
                <?php
                echo $this->render('components/button', [
                    'label' => 'Broadcast Announcement',
                    'variant' => 'primary',
                    'href' => '/admin/announcements/create',
                    'icon' => '<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>',
                ]);
                ?>
            </div>
        </div>
    </div>

    <!-- Quick Stats Metric Strip -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <!-- Total -->
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-xs flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-slate-100 text-slate-700 flex items-center justify-center flex-shrink-0">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z" />
                </svg>
            </div>
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Total Notices</p>
                <h3 class="text-2xl font-bold text-slate-900 mt-0.5"><?= number_format($totalCount) ?></h3>
            </div>
        </div>

        <!-- Active -->
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-xs flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center flex-shrink-0">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Active Now</p>
                <h3 class="text-2xl font-bold text-emerald-700 mt-0.5"><?= number_format($activeCount) ?></h3>
            </div>
        </div>

        <!-- School-Wide -->
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-xs flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-brand-50 text-brand-700 flex items-center justify-center flex-shrink-0">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                </svg>
            </div>
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">School-Wide</p>
                <h3 class="text-2xl font-bold text-brand-800 mt-0.5"><?= number_format($schoolWideCount) ?></h3>
            </div>
        </div>

        <!-- Scoped -->
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-xs flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center flex-shrink-0">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
            </div>
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Class / Subject</p>
                <h3 class="text-2xl font-bold text-blue-800 mt-0.5"><?= number_format($scopedCount) ?></h3>
            </div>
        </div>
    </div>

    <!-- Main Announcements Content Card -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">

        <!-- Filter & Search Toolbar -->
        <div class="p-4 bg-slate-50/70 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <!-- Search Bar -->
            <div class="relative flex-1 max-w-md">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                <input
                    type="text"
                    id="announcementSearchInput"
                    placeholder="Search by title, body, author, or target..."
                    class="block w-full pl-9 pr-4 py-2 border border-slate-300 rounded-lg text-sm bg-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 transition-colors"
                >
            </div>

            <!-- Scope Filter Buttons -->
            <div class="flex items-center gap-1.5 flex-wrap" id="scopeFilterButtons">
                <button
                    type="button"
                    data-filter="all"
                    class="filter-btn px-3 py-1.5 rounded-lg text-xs font-semibold transition-all bg-brand-600 text-white shadow-xs"
                >
                    All (<?= $totalCount ?>)
                </button>
                <button
                    type="button"
                    data-filter="active"
                    class="filter-btn px-3 py-1.5 rounded-lg text-xs font-medium text-slate-600 bg-white border border-slate-300 hover:bg-slate-50 transition-all"
                >
                    Active (<?= $activeCount ?>)
                </button>
                <button
                    type="button"
                    data-filter="school"
                    class="filter-btn px-3 py-1.5 rounded-lg text-xs font-medium text-slate-600 bg-white border border-slate-300 hover:bg-slate-50 transition-all"
                >
                    School-wide (<?= $schoolWideCount ?>)
                </button>
                <button
                    type="button"
                    data-filter="scoped"
                    class="filter-btn px-3 py-1.5 rounded-lg text-xs font-medium text-slate-600 bg-white border border-slate-300 hover:bg-slate-50 transition-all"
                >
                    Scoped (<?= $scopedCount ?>)
                </button>
            </div>
        </div>

        <?php if (empty($announcements)): ?>
            <div class="p-12">
                <?php
                echo $this->render('components/empty_state', [
                    'title' => 'No Announcements Broadcasted Yet',
                    'message' => 'Start by publishing your first school-wide bulletin, class notification, or subject update.',
                    'actionLabel' => '+ Broadcast Announcement',
                    'actionUrl' => '/admin/announcements/create',
                    'icon' => '<svg class="mx-auto h-12 w-12 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z" /></svg>',
                ]);
                ?>
            </div>
        <?php else: ?>
            <div class="divide-y divide-slate-200" id="announcementsFeed">
                <?php foreach ($announcements as $a): ?>
                    <?php
                    $isActive = $a->isActive();
                    $isExpired = $a->isExpired();
                    $isScheduled = !$isActive && !$isExpired;
                    $isSchoolWide = $a->isSchoolWide();

                    // Status Badge config
                    $statusVariant = $isActive ? 'success' : ($isExpired ? 'neutral' : 'warning');
                    $statusLabel = $isActive ? 'Active' : ($isExpired ? 'Expired' : 'Scheduled');

                    // Scope Tag styling
                    $scopeBg = $isSchoolWide ? 'bg-brand-50 text-brand-700 border-brand-200' : 'bg-blue-50 text-blue-700 border-blue-200';
                    $targetDisplay = $a->targetName ?? 'School-wide';
                    ?>
                    <article
                        class="announcement-item p-6 flex flex-col lg:flex-row lg:items-start justify-between gap-6 hover:bg-slate-50/70 transition-colors"
                        data-scope="<?= $isSchoolWide ? 'school' : 'scoped' ?>"
                        data-status="<?= $isActive ? 'active' : ($isExpired ? 'expired' : 'scheduled') ?>"
                        data-search="<?= htmlspecialchars(strtolower($a->title . ' ' . $a->body . ' ' . ($a->authorName ?? '') . ' ' . $targetDisplay)) ?>"
                    >
                        <!-- Left Details Column -->
                        <div class="space-y-3 flex-1 min-w-0">

                            <!-- Meta Header -->
                            <div class="flex items-center gap-2.5 flex-wrap">
                                <!-- Scope Badge -->
                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold border <?= $scopeBg ?>">
                                    <?php if ($isSchoolWide): ?>
                                        <svg class="w-3 h-3 text-brand-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                        </svg>
                                    <?php else: ?>
                                        <svg class="w-3 h-3 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                        </svg>
                                    <?php endif; ?>
                                    <?= htmlspecialchars($targetDisplay) ?>
                                </span>

                                <!-- Status Badge -->
                                <?php
                                echo $this->render('components/badge', [
                                    'label' => $statusLabel,
                                    'variant' => $statusVariant,
                                ]);
                                ?>

                                <!-- Published Date -->
                                <span class="inline-flex items-center gap-1 text-xs text-slate-400">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    Published: <strong class="text-slate-600 font-medium"><?= htmlspecialchars(date('M d, Y · h:i A', strtotime($a->publishedAt ?? $a->createdAt))) ?></strong>
                                </span>

                                <?php if ($a->expiresAt): ?>
                                    <span class="inline-flex items-center gap-1 text-xs text-slate-400">
                                        <svg class="w-3.5 h-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        Expires: <span class="text-slate-600 font-medium"><?= htmlspecialchars(date('M d, Y', strtotime($a->expiresAt))) ?></span>
                                    </span>
                                <?php endif; ?>
                            </div>

                            <!-- Title -->
                            <h2 class="text-lg font-bold text-slate-900 tracking-tight leading-snug">
                                <?= htmlspecialchars($a->title) ?>
                            </h2>

                            <!-- Body Text -->
                            <div class="text-sm text-slate-600 leading-relaxed max-w-4xl whitespace-pre-line">
                                <?= htmlspecialchars($a->body) ?>
                            </div>

                            <!-- Meta Footer (Author) -->
                            <div class="pt-2 flex items-center gap-3 text-xs text-slate-500 border-t border-slate-100">
                                <span class="inline-flex items-center gap-1.5">
                                    <span class="w-5 h-5 rounded-full bg-slate-200 text-slate-700 font-bold text-[10px] flex items-center justify-center flex-shrink-0">
                                        <?= htmlspecialchars(strtoupper(substr($a->authorName ?? 'A', 0, 1))) ?>
                                    </span>
                                    <span>Author: <strong class="text-slate-800 font-semibold"><?= htmlspecialchars($a->authorName ?? 'Administrator') ?></strong></span>
                                </span>
                            </div>
                        </div>

                        <!-- Right Action Buttons -->
                        <div class="flex lg:flex-col items-center justify-end gap-2.5 flex-shrink-0 pt-2 lg:pt-0">
                            <!-- Edit Button -->
                            <?php
                            echo $this->render('components/button', [
                                'label' => 'Edit',
                                'variant' => 'secondary',
                                'href' => "/admin/announcements/{$a->id}/edit",
                                'icon' => '<svg class="w-3.5 h-3.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>',
                                'class' => 'w-full text-xs py-1.5',
                            ]);
                            ?>

                            <!-- Delete Action Form -->
                            <form
                                method="POST"
                                action="/admin/announcements/<?= (int)$a->id ?>/delete"
                                onsubmit="return confirm('Are you sure you want to delete this announcement? This action cannot be undone.');"
                                class="w-full"
                            >
                                <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                <?php
                                echo $this->render('components/button', [
                                    'label' => 'Delete',
                                    'variant' => 'danger',
                                    'type' => 'submit',
                                    'icon' => '<svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>',
                                    'class' => 'w-full text-xs py-1.5',
                                ]);
                                ?>
                            </form>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>

            <!-- Dynamic Search No Results State -->
            <div id="noSearchMatches" class="hidden p-12 text-center">
                <svg class="mx-auto h-10 w-10 text-slate-300 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                <h4 class="text-sm font-bold text-slate-900">No matching announcements found</h4>
                <p class="text-xs text-slate-500 mt-1">Try adjusting your search keywords or filter scope.</p>
                <button
                    type="button"
                    id="clearSearchFilterBtn"
                    class="mt-4 inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold text-brand-600 bg-brand-50 hover:bg-brand-100 transition-colors"
                >
                    Clear Filter
                </button>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Client-side Search & Scope Filter Script -->
<script>
document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('announcementSearchInput');
    const filterButtons = document.querySelectorAll('.filter-btn');
    const items = document.querySelectorAll('.announcement-item');
    const noResults = document.getElementById('noSearchMatches');
    const clearBtn = document.getElementById('clearSearchFilterBtn');

    let currentFilter = 'all';
    let searchQuery = '';

    function filterAnnouncements() {
        let visibleCount = 0;
        const query = searchQuery.trim().toLowerCase();

        items.forEach(item => {
            const scope = item.getAttribute('data-scope');
            const status = item.getAttribute('data-status');
            const searchData = item.getAttribute('data-search') || '';

            // Check Scope / Status Filter
            let matchesFilter = true;
            if (currentFilter === 'active') {
                matchesFilter = (status === 'active');
            } else if (currentFilter === 'school') {
                matchesFilter = (scope === 'school');
            } else if (currentFilter === 'scoped') {
                matchesFilter = (scope === 'scoped');
            }

            // Check Search Text
            let matchesSearch = true;
            if (query.length > 0) {
                matchesSearch = searchData.includes(query);
            }

            if (matchesFilter && matchesSearch) {
                item.classList.remove('hidden');
                visibleCount++;
            } else {
                item.classList.add('hidden');
            }
        });

        if (noResults) {
            if (visibleCount === 0 && items.length > 0) {
                noResults.classList.remove('hidden');
            } else {
                noResults.classList.add('hidden');
            }
        }
    }

    if (searchInput) {
        searchInput.addEventListener('input', (e) => {
            searchQuery = e.target.value;
            filterAnnouncements();
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
            filterAnnouncements();
        });
    });

    if (clearBtn) {
        clearBtn.addEventListener('click', () => {
            if (searchInput) searchInput.value = '';
            searchQuery = '';
            const allBtn = document.querySelector('.filter-btn[data-filter="all"]');
            if (allBtn) allBtn.click();
            else filterAnnouncements();
        });
    }
});
</script>
