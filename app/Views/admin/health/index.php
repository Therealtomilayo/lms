<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-slate-900">System Health & Diagnostics</h2>
            <p class="text-sm text-slate-500 mt-1">Real-time telemetry, database responsiveness, storage writability, and backup status.</p>
        </div>
        <div class="flex items-center gap-3">
            <span class="text-xs text-slate-500">Last Checked: <?= e($health->timestamp) ?></span>
            <a href="/admin/health" class="inline-flex items-center gap-2 px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                Refresh
            </a>
        </div>
    </div>

    <!-- Overall System Status Banner -->
    <div class="p-5 rounded-xl border <?= $health->status === 'healthy' ? 'bg-emerald-50 border-emerald-200 text-emerald-900' : ($health->status === 'warning' ? 'bg-amber-50 border-amber-200 text-amber-900' : 'bg-rose-50 border-rose-200 text-rose-900') ?> flex items-center justify-between">
        <div class="flex items-center gap-3">
            <?php if ($health->status === 'healthy'): ?>
                <div class="w-3 h-3 rounded-full bg-emerald-500 animate-pulse"></div>
                <span class="font-bold text-base">All Systems Fully Operational</span>
            <?php elseif ($health->status === 'warning'): ?>
                <div class="w-3 h-3 rounded-full bg-amber-500"></div>
                <span class="font-bold text-base">System Operational with Warnings</span>
            <?php else: ?>
                <div class="w-3 h-3 rounded-full bg-rose-500"></div>
                <span class="font-bold text-base">System Degraded or Critical Failure Detected</span>
            <?php endif; ?>
        </div>
        <span class="text-xs font-mono uppercase tracking-wider px-2.5 py-1 rounded bg-white/60 font-semibold">
            Status: <?= e($health->status) ?>
        </span>
    </div>

    <!-- Diagnostics Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <!-- Database Card -->
        <?php $db = $health->checks['database'] ?? []; ?>
        <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm space-y-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <div class="p-2 rounded-lg bg-brand-50 text-brand-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"/></svg>
                    </div>
                    <h3 class="font-bold text-slate-900">Database Engine</h3>
                </div>
                <span class="px-2.5 py-0.5 text-xs font-semibold rounded-full <?= ($db['status'] ?? '') === 'healthy' ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800' ?>">
                    <?= e($db['status'] ?? 'unknown') ?>
                </span>
            </div>
            <div class="space-y-2 text-xs text-slate-600">
                <div class="flex justify-between py-1 border-b border-slate-100">
                    <span class="text-slate-500">Driver</span>
                    <span class="font-mono font-medium text-slate-900"><?= e($db['driver'] ?? 'N/A') ?></span>
                </div>
                <div class="flex justify-between py-1 border-b border-slate-100">
                    <span class="text-slate-500">Response Latency</span>
                    <span class="font-mono font-medium text-slate-900"><?= isset($db['latency_ms']) ? e($db['latency_ms']) . ' ms' : 'N/A' ?></span>
                </div>
                <p class="text-slate-500 pt-1"><?= e($db['message'] ?? '') ?></p>
            </div>
        </div>

        <!-- Storage Card -->
        <?php $storage = $health->checks['storage'] ?? []; ?>
        <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm space-y-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <div class="p-2 rounded-lg bg-indigo-50 text-indigo-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/></svg>
                    </div>
                    <h3 class="font-bold text-slate-900">Storage Subsystems</h3>
                </div>
                <span class="px-2.5 py-0.5 text-xs font-semibold rounded-full <?= ($storage['status'] ?? '') === 'healthy' ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800' ?>">
                    <?= e($storage['status'] ?? 'unknown') ?>
                </span>
            </div>
            <div class="space-y-2 text-xs text-slate-600">
                <div class="flex justify-between py-1 border-b border-slate-100">
                    <span class="text-slate-500">Uploads Directory</span>
                    <span class="font-medium <?= ($storage['uploads_writable'] ?? false) ? 'text-emerald-600' : 'text-rose-600' ?>">
                        <?= ($storage['uploads_writable'] ?? false) ? 'Writable' : 'Unwritable' ?>
                    </span>
                </div>
                <div class="flex justify-between py-1 border-b border-slate-100">
                    <span class="text-slate-500">Logs Directory</span>
                    <span class="font-medium <?= ($storage['logs_writable'] ?? false) ? 'text-emerald-600' : 'text-rose-600' ?>">
                        <?= ($storage['logs_writable'] ?? false) ? 'Writable' : 'Unwritable' ?>
                    </span>
                </div>
                <div class="flex justify-between py-1 border-b border-slate-100">
                    <span class="text-slate-500">Free Disk Space</span>
                    <span class="font-mono font-medium text-slate-900"><?= isset($storage['free_space_mb']) ? e($storage['free_space_mb']) . ' MB' : 'Available' ?></span>
                </div>
            </div>
        </div>

        <!-- Backups Card -->
        <?php $backups = $health->checks['backups'] ?? []; ?>
        <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm space-y-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <div class="p-2 rounded-lg bg-teal-50 text-teal-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
                    </div>
                    <h3 class="font-bold text-slate-900">Backup Freshness</h3>
                </div>
                <span class="px-2.5 py-0.5 text-xs font-semibold rounded-full <?= ($backups['status'] ?? '') === 'healthy' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' ?>">
                    <?= e($backups['status'] ?? 'unknown') ?>
                </span>
            </div>
            <div class="space-y-2 text-xs text-slate-600">
                <div class="flex justify-between py-1 border-b border-slate-100">
                    <span class="text-slate-500">Total Archives</span>
                    <span class="font-mono font-medium text-slate-900"><?= e((string)($backups['total_backups'] ?? 0)) ?></span>
                </div>
                <div class="flex justify-between py-1 border-b border-slate-100">
                    <span class="text-slate-500">Latest Backup</span>
                    <span class="font-mono text-slate-900"><?= e($backups['last_backup_at'] ? substr($backups['last_backup_at'], 0, 19) : 'None') ?></span>
                </div>
                <p class="text-slate-500 pt-1"><?= e($backups['message'] ?? '') ?></p>
            </div>
        </div>

        <!-- Session Card -->
        <?php $session = $health->checks['session'] ?? []; ?>
        <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm space-y-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <div class="p-2 rounded-lg bg-amber-50 text-amber-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                    </div>
                    <h3 class="font-bold text-slate-900">Session & Auth State</h3>
                </div>
                <span class="px-2.5 py-0.5 text-xs font-semibold rounded-full bg-emerald-100 text-emerald-800">
                    Healthy
                </span>
            </div>
            <div class="space-y-2 text-xs text-slate-600">
                <div class="flex justify-between py-1 border-b border-slate-100">
                    <span class="text-slate-500">Cookie Identifier</span>
                    <span class="font-mono text-slate-900"><?= e($session['cookie_name'] ?? 'lms_session') ?></span>
                </div>
                <div class="flex justify-between py-1 border-b border-slate-100">
                    <span class="text-slate-500">Session TTL</span>
                    <span class="font-mono text-slate-900"><?= e((string)($session['lifetime_seconds'] ?? 7200)) ?>s</span>
                </div>
            </div>
        </div>

        <!-- Environment Card -->
        <?php $env = $health->checks['environment'] ?? []; ?>
        <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm space-y-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <div class="p-2 rounded-lg bg-purple-50 text-purple-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    </div>
                    <h3 class="font-bold text-slate-900">Environment Config</h3>
                </div>
                <span class="px-2.5 py-0.5 text-xs font-semibold rounded-full <?= ($env['status'] ?? '') === 'healthy' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' ?>">
                    <?= e($env['status'] ?? 'unknown') ?>
                </span>
            </div>
            <div class="space-y-2 text-xs text-slate-600">
                <div class="flex justify-between py-1 border-b border-slate-100">
                    <span class="text-slate-500">Mode</span>
                    <span class="font-mono font-medium text-slate-900 uppercase"><?= e($env['environment'] ?? 'production') ?></span>
                </div>
                <div class="flex justify-between py-1 border-b border-slate-100">
                    <span class="text-slate-500">Debug Enabled</span>
                    <span class="font-mono text-slate-900"><?= ($env['debug'] ?? false) ? 'True' : 'False' ?></span>
                </div>
            </div>
        </div>
    </div>
</div>
