<div class="space-y-6">
    <!-- Header Card -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <!-- Breadcrumbs -->
                <nav class="flex items-center gap-2 text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">
                    <span class="text-slate-400">Admin</span>
                    <span class="text-slate-300">/</span>
                    <span class="text-slate-400">System & Security</span>
                    <span class="text-slate-300">/</span>
                    <span class="text-slate-700">Audit Trail</span>
                </nav>
                <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight leading-tight">
                    System Audit Trail
                </h1>
                <p class="text-xs text-slate-500 mt-1">
                    Immutable ledger of administrative mutations, grading adjustments, authentication events, and entity lifecycle changes.
                </p>
            </div>

            <!-- Total Event Counter Badge -->
            <div class="flex items-center gap-3">
                <?php $this->include('components/badge', [
                    'label' => number_format((int)($pagination['total'] ?? 0)) . ' Recorded Events',
                    'variant' => 'brand',
                    'class' => 'font-mono uppercase font-bold text-xs px-3 py-1.5'
                ]); ?>
            </div>
        </div>
    </div>

    <!-- 4-Card Overview Metric Strip -->
    <?php
    $totalRecords = (int)($pagination['total'] ?? 0);
    $currentPage = (int)($pagination['page'] ?? 1);
    $totalPages = (int)($pagination['total_pages'] ?? 1);
    $perPage = (int)($pagination['per_page'] ?? 25);
    $actionCount = count($actions);
    $entityCount = count($entityTypes);
    ?>
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Total Events -->
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-xs">
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Total Log Entries</p>
            <div class="flex items-baseline gap-2 mt-1">
                <h3 class="text-2xl font-extrabold text-slate-900"><?= number_format($totalRecords) ?></h3>
            </div>
            <span class="text-[11px] font-medium text-slate-500 mt-1 block">
                Immutable record ledger
            </span>
        </div>

        <!-- Pagination Index -->
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-xs">
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Page Navigation</p>
            <div class="flex items-baseline gap-2 mt-1">
                <h3 class="text-2xl font-extrabold text-slate-900"><?= $currentPage ?> <span class="text-sm font-semibold text-slate-400">/ <?= $totalPages ?></span></h3>
            </div>
            <span class="text-[11px] font-medium text-slate-500 mt-1 block">
                <?= $perPage ?> entries per page
            </span>
        </div>

        <!-- Monitored Actions -->
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-xs">
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Action Types</p>
            <div class="flex items-baseline gap-2 mt-1">
                <h3 class="text-2xl font-extrabold text-slate-900"><?= number_format($actionCount) ?></h3>
            </div>
            <span class="text-[11px] font-medium text-slate-500 mt-1 block">
                Active mutation categories
            </span>
        </div>

        <!-- Monitored Entities -->
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-xs">
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Entity Domains</p>
            <div class="flex items-baseline gap-2 mt-1">
                <h3 class="text-2xl font-extrabold text-slate-900"><?= number_format($entityCount) ?></h3>
            </div>
            <span class="text-[11px] font-medium text-slate-500 mt-1 block">
                Tracked domain models
            </span>
        </div>
    </div>

    <!-- Filter Bar Card -->
    <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-xs">
        <form method="GET" action="/admin/audit-logs" class="flex flex-col sm:flex-row items-stretch sm:items-end gap-4">
            <!-- Action Filter -->
            <div class="flex-1 min-w-[200px]">
                <label for="filter_action" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                    Filter by Action
                </label>
                <div class="relative">
                    <select
                        id="filter_action"
                        name="action"
                        class="w-full bg-slate-50 border border-slate-300 text-slate-900 text-xs font-semibold rounded-lg px-3 py-2.5 pr-8 focus:ring-2 focus:ring-brand-500 focus:border-brand-500 focus:bg-white transition-colors cursor-pointer appearance-none"
                    >
                        <option value="">All Actions (<?= $actionCount ?> total)</option>
                        <?php foreach ($actions as $act): ?>
                            <option value="<?= htmlspecialchars($act) ?>" <?= ($filters['action'] ?? '') === $act ? 'selected' : '' ?>>
                                <?= htmlspecialchars($act) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-slate-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </div>
                </div>
            </div>

            <!-- Entity Type Filter -->
            <div class="flex-1 min-w-[200px]">
                <label for="filter_entity" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                    Filter by Target Entity
                </label>
                <div class="relative">
                    <select
                        id="filter_entity"
                        name="entity_type"
                        class="w-full bg-slate-50 border border-slate-300 text-slate-900 text-xs font-semibold rounded-lg px-3 py-2.5 pr-8 focus:ring-2 focus:ring-brand-500 focus:border-brand-500 focus:bg-white transition-colors cursor-pointer appearance-none"
                    >
                        <option value="">All Entities (<?= $entityCount ?> total)</option>
                        <?php foreach ($entityTypes as $ent): ?>
                            <option value="<?= htmlspecialchars($ent) ?>" <?= ($filters['entity_type'] ?? '') === $ent ? 'selected' : '' ?>>
                                <?= htmlspecialchars($ent) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-slate-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex items-center gap-2">
                <button
                    type="submit"
                    class="px-4 py-2.5 bg-brand-600 hover:bg-brand-700 active:bg-brand-800 text-white rounded-lg text-xs font-bold uppercase tracking-wider transition shadow-sm cursor-pointer flex items-center gap-1.5"
                >
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    Apply Filters
                </button>
                <a
                    href="/admin/audit-logs"
                    class="px-3.5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold transition"
                >
                    Reset
                </a>
            </div>
        </form>
    </div>

    <!-- Audit Logs Table Card -->
    <div class="bg-white border border-slate-200 rounded-2xl overflow-hidden shadow-xs">
        <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
            <h2 class="text-base font-bold text-slate-900">Event Stream</h2>
            <span class="text-xs text-slate-500 font-medium">
                Page <?= $currentPage ?> of <?= $totalPages ?> (<?= number_format($totalRecords) ?> records)
            </span>
        </div>

        <?php if (empty($logs)): ?>
            <div class="p-12 text-center">
                <div class="w-16 h-16 rounded-2xl bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                <h3 class="text-base font-bold text-slate-900">No audit records found</h3>
                <p class="text-xs text-slate-500 max-w-sm mx-auto mt-1 mb-4">
                    No logged mutations match your filter criteria. Try selecting "All Actions" or resetting the search filters.
                </p>
                <a href="/admin/audit-logs" class="inline-flex items-center gap-1 px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold transition">
                    Clear Filters
                </a>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-left text-xs">
                    <thead class="bg-slate-50 text-slate-600 font-semibold uppercase tracking-wider">
                        <tr>
                            <th scope="col" class="px-5 py-3.5">Timestamp (UTC)</th>
                            <th scope="col" class="px-5 py-3.5">Actor</th>
                            <th scope="col" class="px-5 py-3.5">Action</th>
                            <th scope="col" class="px-5 py-3.5">Target Entity</th>
                            <th scope="col" class="px-5 py-3.5">Changes / Payload</th>
                            <th scope="col" class="px-5 py-3.5 text-right">Correlation ID</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        <?php foreach ($logs as $log): ?>
                            <tr class="hover:bg-slate-50/70 transition-colors">
                                <!-- Timestamp -->
                                <td class="px-5 py-3.5 whitespace-nowrap text-slate-700 font-mono text-[11px]">
                                    <?= htmlspecialchars(substr($log->createdAt, 0, 19)) ?>
                                </td>

                                <!-- Actor -->
                                <td class="px-5 py-3.5">
                                    <?php if ($log->actorUserId): ?>
                                        <div class="font-bold text-slate-900">
                                            <?= htmlspecialchars($log->actorName ?: 'User #' . $log->actorUserId) ?>
                                        </div>
                                        <div class="text-[11px] text-slate-400 mt-0.5">
                                            <?= htmlspecialchars($log->actorEmail ?: 'ID: ' . $log->actorUserId) ?>
                                        </div>
                                    <?php else: ?>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded bg-slate-100 text-slate-600 font-medium text-[11px]">
                                            System / Automated
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <!-- Action Badge -->
                                <td class="px-5 py-3.5 whitespace-nowrap">
                                    <?php
                                    $actionStr = (string)$log->action;
                                    $variant = 'brand';
                                    if (str_contains($actionStr, 'delete') || str_contains($actionStr, 'failed')) {
                                        $variant = 'danger';
                                    } elseif (str_contains($actionStr, 'create') || str_contains($actionStr, 'publish')) {
                                        $variant = 'success';
                                    } elseif (str_contains($actionStr, 'update') || str_contains($actionStr, 'edit')) {
                                        $variant = 'warning';
                                    }
                                    ?>
                                    <span class="px-2.5 py-1 rounded-md font-mono text-[11px] font-bold tracking-tight inline-block <?= $variant === 'danger' ? 'bg-rose-50 text-rose-700 border border-rose-200' : ($variant === 'success' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : ($variant === 'warning' ? 'bg-amber-50 text-amber-800 border border-amber-200' : 'bg-brand-50 text-brand-700 border border-brand-200')) ?>">
                                        <?= htmlspecialchars($actionStr) ?>
                                    </span>
                                </td>

                                <!-- Target Entity -->
                                <td class="px-5 py-3.5 whitespace-nowrap">
                                    <span class="font-semibold text-slate-800 uppercase font-mono text-[11px]"><?= htmlspecialchars($log->entityType) ?></span>
                                    <span class="text-slate-400 font-mono text-[11px]">#<?= (int)$log->entityId ?></span>
                                </td>

                                <!-- Changes / Payload -->
                                <td class="px-5 py-3.5 max-w-xs md:max-w-sm truncate text-slate-600 font-mono text-[11px]">
                                    <?php
                                    $payloadStr = '';
                                    if (!empty($log->afterJson)) {
                                        $payloadStr = json_encode($log->afterJson, JSON_UNESCAPED_SLASHES);
                                    } elseif (!empty($log->metadataJson)) {
                                        $payloadStr = json_encode($log->metadataJson, JSON_UNESCAPED_SLASHES);
                                    } elseif (!empty($log->beforeJson)) {
                                        $payloadStr = json_encode($log->beforeJson, JSON_UNESCAPED_SLASHES);
                                    }
                                    ?>
                                    <?php if ($payloadStr !== ''): ?>
                                        <span class="cursor-help bg-slate-50 px-2 py-1 rounded border border-slate-200 block truncate" title="<?= htmlspecialchars($payloadStr) ?>">
                                            <?= htmlspecialchars(substr($payloadStr, 0, 70)) ?><?= strlen($payloadStr) > 70 ? '…' : '' ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-slate-400 italic">—</span>
                                    <?php endif; ?>
                                </td>

                                <!-- Request / Trace ID -->
                                <td class="px-5 py-3.5 text-right font-mono text-[11px] text-slate-400 whitespace-nowrap">
                                    <?php if (!empty($log->requestId)): ?>
                                        <span class="px-1.5 py-0.5 rounded bg-slate-100 font-mono text-[10px] text-slate-600" title="<?= htmlspecialchars($log->requestId) ?>">
                                            <?= htmlspecialchars(substr($log->requestId, 0, 10)) ?>…
                                        </span>
                                    <?php else: ?>
                                        <span class="text-slate-300">—</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination Bar -->
            <?php if ($totalPages > 1): ?>
                <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex flex-col sm:flex-row items-center justify-between gap-3">
                    <span class="text-xs text-slate-600 font-medium">
                        Showing page <strong><?= $currentPage ?></strong> of <strong><?= $totalPages ?></strong> (<?= number_format($totalRecords) ?> total logs)
                    </span>
                    <div class="flex items-center gap-2">
                        <?php if ($currentPage > 1): ?>
                            <a
                                href="?page=<?= $currentPage - 1 ?>&action=<?= urlencode($filters['action'] ?? '') ?>&entity_type=<?= urlencode($filters['entity_type'] ?? '') ?>"
                                class="inline-flex items-center gap-1 px-3 py-1.5 bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 rounded-lg text-xs font-semibold transition shadow-2xs"
                            >
                                &larr; Previous
                            </a>
                        <?php else: ?>
                            <span class="px-3 py-1.5 bg-slate-100 text-slate-400 rounded-lg text-xs font-semibold cursor-not-allowed">
                                &larr; Previous
                            </span>
                        <?php endif; ?>

                        <?php if ($currentPage < $totalPages): ?>
                            <a
                                href="?page=<?= $currentPage + 1 ?>&action=<?= urlencode($filters['action'] ?? '') ?>&entity_type=<?= urlencode($filters['entity_type'] ?? '') ?>"
                                class="inline-flex items-center gap-1 px-3 py-1.5 bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 rounded-lg text-xs font-semibold transition shadow-2xs"
                            >
                                Next &rarr;
                            </a>
                        <?php else: ?>
                            <span class="px-3 py-1.5 bg-slate-100 text-slate-400 rounded-lg text-xs font-semibold cursor-not-allowed">
                                Next &rarr;
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
