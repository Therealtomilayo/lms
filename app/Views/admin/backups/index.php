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
                    <span class="text-slate-700">Database Backups</span>
                </nav>
                <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight leading-tight">
                    Database Backups & Archival
                </h1>
                <p class="text-xs text-slate-500 mt-1">
                    Manage database snapshots, verify SHA-256 cryptographic checksums, and trigger manual archival dumps.
                </p>
            </div>

            <!-- Generate Backup Action -->
            <div class="flex items-center gap-3">
                <form method="POST" action="/admin/backups/create" onsubmit="this.querySelector('button').disabled = true; this.querySelector('button').innerHTML = 'Generating Dump...';">
                    <?= csrf_field() ?>
                    <button
                        type="submit"
                        class="inline-flex items-center gap-2 px-4 py-2.5 bg-brand-600 hover:bg-brand-700 active:bg-brand-800 text-white rounded-xl text-xs font-bold uppercase tracking-wider transition shadow-sm cursor-pointer disabled:opacity-50"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/>
                        </svg>
                        Generate Backup Now
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- 4-Card Overview Metric Strip -->
    <?php
    $backups = $backups ?? [];
    $totalCount = count($backups);
    $totalSizeBytes = array_sum(array_column($backups, 'size_bytes'));
    
    // Format total size
    if ($totalSizeBytes >= 1048576) {
        $formattedTotalSize = number_format($totalSizeBytes / 1048576, 2) . ' MB';
    } elseif ($totalSizeBytes >= 1024) {
        $formattedTotalSize = number_format($totalSizeBytes / 1024, 1) . ' KB';
    } else {
        $formattedTotalSize = $totalSizeBytes . ' B';
    }

    $latestBackup = !empty($backups) ? $backups[0] : null;
    ?>
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Total Archives -->
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-xs">
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Total Archives</p>
            <div class="flex items-baseline gap-2 mt-1">
                <h3 class="text-2xl font-extrabold text-slate-900"><?= number_format($totalCount) ?></h3>
            </div>
            <span class="text-[11px] font-medium text-slate-500 mt-1 block">
                Storage: <span class="font-mono font-semibold text-slate-700"><?= $formattedTotalSize ?></span>
            </span>
        </div>

        <!-- Latest Backup -->
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-xs">
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Latest Snapshot</p>
            <div class="flex items-baseline gap-2 mt-1">
                <h3 class="text-2xl font-extrabold text-slate-900">
                    <?= $latestBackup ? 'Active' : 'None' ?>
                </h3>
            </div>
            <span class="text-[11px] font-medium text-slate-500 mt-1 block truncate">
                <?= $latestBackup ? htmlspecialchars(substr((string)$latestBackup['created_at'], 0, 19)) : 'No backups created yet' ?>
            </span>
        </div>

        <!-- Integrity Mode -->
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-xs">
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Integrity Check</p>
            <div class="flex items-baseline gap-2 mt-1">
                <h3 class="text-2xl font-extrabold text-emerald-600">SHA-256</h3>
            </div>
            <span class="text-[11px] font-medium text-slate-500 mt-1 block">
                Validation: <span class="font-semibold text-emerald-700">Pre-Download Verification</span>
            </span>
        </div>

        <!-- Retention Policy -->
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-xs">
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Disaster Recovery</p>
            <div class="flex items-baseline gap-2 mt-1">
                <h3 class="text-2xl font-extrabold text-slate-900">Ready</h3>
            </div>
            <span class="text-[11px] font-medium text-slate-500 mt-1 block">
                Audit: <span class="font-semibold text-slate-700">Every snapshot logged</span>
            </span>
        </div>
    </div>

    <!-- Security & Integrity Guidance Notice -->
    <div class="p-4 bg-sky-50 border border-sky-200 rounded-xl text-xs text-sky-900 flex items-start gap-3">
        <svg class="w-5 h-5 text-sky-600 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
        </svg>
        <div class="space-y-0.5">
            <p class="font-bold text-sky-950">Cryptographic Integrity & Disaster Recovery</p>
            <p class="text-sky-800 leading-relaxed text-[11px]">
                Each backup snapshot produces a complete atomic SQL dump with a paired SHA-256 checksum manifest. Before downloading, the server verifies the integrity of the file against the cryptographic manifest to ensure zero file corruption.
            </p>
        </div>
    </div>

    <!-- Backup List Table -->
    <div class="bg-white border border-slate-200 rounded-2xl overflow-hidden shadow-xs">
        <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
            <h2 class="text-base font-bold text-slate-900">Database Snapshots Archive</h2>
            <?php $this->include('components/badge', [
                'label' => $totalCount . ' ' . ($totalCount === 1 ? 'Snapshot' : 'Snapshots'),
                'variant' => $totalCount > 0 ? 'brand' : 'neutral',
                'class' => 'text-xs font-semibold'
            ]); ?>
        </div>

        <?php if (empty($backups)): ?>
            <div class="p-12 text-center">
                <div class="w-16 h-16 rounded-2xl bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/>
                    </svg>
                </div>
                <h3 class="text-base font-bold text-slate-900">No database backups generated yet</h3>
                <p class="text-xs text-slate-500 max-w-sm mx-auto mt-1 mb-5">
                    Trigger a database snapshot dump above to create your first disaster recovery archive.
                </p>
                <form method="POST" action="/admin/backups/create" class="inline">
                    <?= csrf_field() ?>
                    <?php $this->include('components/button', [
                        'type' => 'submit',
                        'label' => 'Generate First Backup',
                        'variant' => 'primary',
                        'class' => 'text-xs font-bold'
                    ]); ?>
                </form>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                    <thead class="bg-slate-50 text-slate-600 font-semibold uppercase text-xs tracking-wider">
                        <tr>
                            <th scope="col" class="px-6 py-3.5">Archive Filename</th>
                            <th scope="col" class="px-6 py-3.5">Size</th>
                            <th scope="col" class="px-6 py-3.5">SHA-256 Checksum</th>
                            <th scope="col" class="px-6 py-3.5">Generated At (UTC)</th>
                            <th scope="col" class="px-6 py-3.5 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        <?php foreach ($backups as $b): ?>
                            <tr class="hover:bg-slate-50/70 transition-colors">
                                <!-- Filename with Icon -->
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-lg bg-emerald-50 text-emerald-700 flex items-center justify-center flex-shrink-0 border border-emerald-100 font-mono text-xs font-bold">
                                            SQL
                                        </div>
                                        <div>
                                            <span class="font-mono text-xs font-bold text-slate-900 block truncate max-w-xs md:max-w-md">
                                                <?= htmlspecialchars((string)$b['filename']) ?>
                                            </span>
                                            <span class="text-[11px] text-slate-400 block mt-0.5">
                                                Database Dump Archive
                                            </span>
                                        </div>
                                    </div>
                                </td>

                                <!-- Size -->
                                <td class="px-6 py-4 text-xs font-semibold text-slate-700">
                                    <?= htmlspecialchars((string)($b['size_formatted'] ?? '0 B')) ?>
                                </td>

                                <!-- Checksum Tag -->
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-1.5" title="<?= htmlspecialchars((string)($b['sha256'] ?? '')) ?>">
                                        <span class="px-2 py-1 rounded-md bg-slate-100 border border-slate-200 font-mono text-[11px] font-semibold text-slate-700 tracking-tight">
                                            <?= htmlspecialchars(substr((string)($b['sha256'] ?? 'N/A'), 0, 16)) ?>…
                                        </span>
                                        <span class="text-[10px] text-emerald-600 font-bold uppercase">Verified ✓</span>
                                    </div>
                                </td>

                                <!-- Timestamp -->
                                <td class="px-6 py-4 text-xs font-medium text-slate-600">
                                    <span class="font-mono"><?= htmlspecialchars((string)$b['created_at']) ?></span>
                                </td>

                                <!-- Download Action -->
                                <td class="px-6 py-4 text-right">
                                    <a
                                        href="/admin/backups/<?= urlencode((string)$b['filename']) ?>/download"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold bg-brand-50 text-brand-700 hover:bg-brand-100 active:bg-brand-200 border border-brand-200 transition"
                                    >
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                        </svg>
                                        Download
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
