<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-slate-900">System Audit Trail</h2>
            <p class="text-sm text-slate-500 mt-1">Immutable record of administrative mutations, grading actions, and entity lifecycle changes.</p>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white border border-slate-200 rounded-xl p-4 shadow-sm">
        <form method="GET" action="/admin/audit-logs" class="flex flex-wrap items-center gap-4">
            <div class="flex-1 min-w-[200px]">
                <label for="filter_action" class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1">Action</label>
                <select id="filter_action" name="action" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-xs focus:ring-2 focus:ring-brand-500">
                    <option value="">All Actions</option>
                    <?php foreach ($actions as $act): ?>
                        <option value="<?= e($act) ?>" <?= ($filters['action'] ?? '') === $act ? 'selected' : '' ?>>
                            <?= e($act) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="flex-1 min-w-[200px]">
                <label for="filter_entity" class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1">Entity Type</label>
                <select id="filter_entity" name="entity_type" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-xs focus:ring-2 focus:ring-brand-500">
                    <option value="">All Entities</option>
                    <?php foreach ($entityTypes as $ent): ?>
                        <option value="<?= e($ent) ?>" <?= ($filters['entity_type'] ?? '') === $ent ? 'selected' : '' ?>>
                            <?= e($ent) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="flex items-end gap-2 pt-5">
                <button type="submit" class="px-4 py-2 bg-brand-600 hover:bg-brand-700 text-white rounded-lg text-xs font-semibold transition">
                    Filter
                </button>
                <a href="/admin/audit-logs" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold transition">
                    Reset
                </a>
            </div>
        </form>
    </div>

    <!-- Logs Table -->
    <div class="bg-white border border-slate-200 rounded-xl overflow-hidden shadow-sm">
        <table class="min-w-full divide-y divide-slate-200 text-left text-xs">
            <thead class="bg-slate-50 text-slate-600 font-semibold uppercase tracking-wider">
                <tr>
                    <th scope="col" class="px-5 py-3">Timestamp (UTC)</th>
                    <th scope="col" class="px-5 py-3">Actor</th>
                    <th scope="col" class="px-5 py-3">Action</th>
                    <th scope="col" class="px-5 py-3">Target Entity</th>
                    <th scope="col" class="px-5 py-3">Changes / Metadata</th>
                    <th scope="col" class="px-5 py-3">Req ID</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200">
                <?php if (empty($logs)): ?>
                    <tr>
                        <td colspan="6" class="px-5 py-10 text-center text-slate-500 text-sm">
                            No audit log records found matching query criteria.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($logs as $log): ?>
                        <tr class="hover:bg-slate-50 transition">
                            <td class="px-5 py-3.5 whitespace-nowrap text-slate-600 font-mono">
                                <?= e(substr($log->createdAt, 0, 19)) ?>
                            </td>
                            <td class="px-5 py-3.5">
                                <?php if ($log->actorUserId): ?>
                                    <div class="font-semibold text-slate-900"><?= e($log->actorName ?? 'User #' . $log->actorUserId) ?></div>
                                    <div class="text-[11px] text-slate-500"><?= e($log->actorEmail ?? '') ?></div>
                                <?php else: ?>
                                    <span class="text-slate-400 italic">System / Anonymous</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-5 py-3.5">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-mono font-medium bg-brand-50 text-brand-700">
                                    <?= e($log->action) ?>
                                </span>
                            </td>
                            <td class="px-5 py-3.5 whitespace-nowrap">
                                <span class="font-medium text-slate-800"><?= e($log->entityType) ?></span>
                                <span class="text-slate-400">#<?= e((string)$log->entityId) ?></span>
                            </td>
                            <td class="px-5 py-3.5 max-w-xs truncate text-slate-600 font-mono text-[11px]">
                                <?php if (!empty($log->afterJson)): ?>
                                    <span title="<?= e(json_encode($log->afterJson)) ?>">
                                        <?= e(substr(json_encode($log->afterJson), 0, 60)) ?><?= strlen(json_encode($log->afterJson)) > 60 ? '...' : '' ?>
                                    </span>
                                <?php elseif (!empty($log->metadataJson)): ?>
                                    <span title="<?= e(json_encode($log->metadataJson)) ?>">
                                        <?= e(substr(json_encode($log->metadataJson), 0, 60)) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-slate-400">—</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-5 py-3.5 font-mono text-[11px] text-slate-400">
                                <?= e($log->requestId ? substr($log->requestId, 0, 8) . '...' : '—') ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Pagination Bar -->
        <?php if (($pagination['total_pages'] ?? 1) > 1): ?>
            <div class="px-5 py-3 bg-slate-50 border-t border-slate-200 flex items-center justify-between">
                <span class="text-xs text-slate-500">
                    Showing Page <?= e((string)$pagination['page']) ?> of <?= e((string)$pagination['total_pages']) ?> (<?= e((string)$pagination['total']) ?> records)
                </span>
                <div class="flex gap-2">
                    <?php if ($pagination['page'] > 1): ?>
                        <a href="?page=<?= $pagination['page'] - 1 ?>&action=<?= urlencode($filters['action'] ?? '') ?>&entity_type=<?= urlencode($filters['entity_type'] ?? '') ?>" class="px-3 py-1 bg-white border border-slate-200 rounded text-xs text-slate-700 hover:bg-slate-50">Previous</a>
                    <?php endif; ?>
                    <?php if ($pagination['page'] < $pagination['total_pages']): ?>
                        <a href="?page=<?= $pagination['page'] + 1 ?>&action=<?= urlencode($filters['action'] ?? '') ?>&entity_type=<?= urlencode($filters['entity_type'] ?? '') ?>" class="px-3 py-1 bg-white border border-slate-200 rounded text-xs text-slate-700 hover:bg-slate-50">Next</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
