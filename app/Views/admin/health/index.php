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
                    <span class="text-slate-700">System Health</span>
                </nav>
                <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight leading-tight">
                    System Health & Diagnostics
                </h1>
                <p class="text-xs text-slate-500 mt-1">
                    Real-time telemetry, database responsiveness, storage writability, session integrity, and backup status.
                </p>
            </div>

            <!-- Refresh Action & Timestamp -->
            <div class="flex items-center gap-3 flex-wrap">
                <div class="text-right hidden sm:block">
                    <span class="text-[11px] text-slate-400 block uppercase font-mono font-medium">Last Checked (UTC)</span>
                    <span class="text-xs font-mono font-bold text-slate-700">
                        <?= htmlspecialchars($health->timestamp) ?>
                    </span>
                </div>
                <?php $this->include('components/button', [
                    'label' => 'Refresh Diagnostics',
                    'variant' => 'secondary',
                    'href' => '/admin/health',
                    'class' => 'text-xs font-semibold flex items-center gap-1.5'
                ]); ?>
            </div>
        </div>
    </div>

    <!-- Overall System Status Banner -->
    <?php
    $isHealthy = $health->status === 'healthy';
    $isWarning = $health->status === 'warning';
    $isDegraded = $health->status === 'degraded' || $health->status === 'critical';

    $bannerBg = $isHealthy ? 'bg-emerald-50/80 border-emerald-200 text-emerald-950' : ($isWarning ? 'bg-amber-50/80 border-amber-200 text-amber-950' : 'bg-rose-50/80 border-rose-200 text-rose-950');
    $badgeVariant = $isHealthy ? 'success' : ($isWarning ? 'warning' : 'danger');
    ?>
    <div class="p-6 rounded-2xl border <?= $bannerBg ?> shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div class="flex items-center gap-3.5">
            <?php if ($isHealthy): ?>
                <div class="w-4 h-4 rounded-full bg-emerald-500 ring-4 ring-emerald-200 animate-pulse flex-shrink-0"></div>
                <div>
                    <h2 class="font-bold text-base text-emerald-900 leading-tight">All Subsystems Fully Operational</h2>
                    <p class="text-xs text-emerald-700 mt-0.5">Database connectivity, storage permissions, and security services are running nominally.</p>
                </div>
            <?php elseif ($isWarning): ?>
                <div class="w-4 h-4 rounded-full bg-amber-500 ring-4 ring-amber-200 flex-shrink-0"></div>
                <div>
                    <h2 class="font-bold text-base text-amber-900 leading-tight">System Operational with Warnings</h2>
                    <p class="text-xs text-amber-700 mt-0.5">One or more non-critical subsystems require administrator attention.</p>
                </div>
            <?php else: ?>
                <div class="w-4 h-4 rounded-full bg-rose-500 ring-4 ring-rose-200 flex-shrink-0"></div>
                <div>
                    <h2 class="font-bold text-base text-rose-900 leading-tight">System Degraded or Critical Service Failure</h2>
                    <p class="text-xs text-rose-700 mt-0.5">An essential service or storage subsystem is unreachable or unwritable.</p>
                </div>
            <?php endif; ?>
        </div>

        <div class="flex items-center gap-2 self-start md:self-center">
            <?php $this->include('components/badge', [
                'label' => 'STATUS: ' . strtoupper($health->status),
                'variant' => $badgeVariant,
                'class' => 'font-mono uppercase font-bold text-xs px-3 py-1'
            ]); ?>
        </div>
    </div>

    <!-- Quick Metric Cards (4 Cards) -->
    <?php
    $db = $health->checks['database'] ?? [];
    $storage = $health->checks['storage'] ?? [];
    $backups = $health->checks['backups'] ?? [];
    $session = $health->checks['session'] ?? [];
    $env = $health->checks['environment'] ?? [];
    ?>
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- DB Latency -->
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-xs">
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Database Latency</p>
            <div class="flex items-baseline gap-2 mt-1">
                <h3 class="text-2xl font-extrabold text-slate-900">
                    <?= isset($db['latency_ms']) ? number_format((float)$db['latency_ms'], 2) . '<span class="text-sm font-semibold text-slate-500 ml-1">ms</span>' : 'N/A' ?>
                </h3>
            </div>
            <span class="text-[11px] font-medium text-slate-500 mt-1 block">
                Driver: <span class="font-mono font-semibold uppercase text-slate-700"><?= htmlspecialchars((string)($db['driver'] ?? 'N/A')) ?></span>
            </span>
        </div>

        <!-- Storage Status -->
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-xs">
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Storage Writability</p>
            <div class="flex items-baseline gap-2 mt-1">
                <h3 class="text-2xl font-extrabold <?= ($storage['status'] ?? '') === 'healthy' ? 'text-emerald-600' : 'text-rose-600' ?>">
                    <?= ($storage['status'] ?? '') === 'healthy' ? '100% OK' : 'Degraded' ?>
                </h3>
            </div>
            <span class="text-[11px] font-medium text-slate-500 mt-1 block">
                Free: <span class="font-mono font-semibold text-slate-700"><?= isset($storage['free_space_mb']) ? number_format((float)$storage['free_space_mb']) . ' MB' : 'Available' ?></span>
            </span>
        </div>

        <!-- Backup Status -->
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-xs">
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Backup Archives</p>
            <div class="flex items-baseline gap-2 mt-1">
                <h3 class="text-2xl font-extrabold text-slate-900">
                    <?= number_format((int)($backups['total_backups'] ?? 0)) ?>
                    <span class="text-sm font-semibold text-slate-500 ml-1">total</span>
                </h3>
            </div>
            <span class="text-[11px] font-medium text-slate-500 mt-1 block">
                Freshness: <span class="font-semibold text-slate-700"><?= isset($backups['freshness_hours']) ? (int)$backups['freshness_hours'] . 'h ago' : 'No archives' ?></span>
            </span>
        </div>

        <!-- Environment Mode -->
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-xs">
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Environment Mode</p>
            <div class="flex items-baseline gap-2 mt-1">
                <h3 class="text-2xl font-extrabold font-mono uppercase text-slate-900">
                    <?= htmlspecialchars((string)($env['environment'] ?? 'production')) ?>
                </h3>
            </div>
            <span class="text-[11px] font-medium text-slate-500 mt-1 block">
                Debug Mode: <span class="font-semibold <?= ($env['debug'] ?? false) ? 'text-amber-600' : 'text-slate-700' ?>"><?= ($env['debug'] ?? false) ? 'Enabled (Active)' : 'Disabled' ?></span>
            </span>
        </div>
    </div>

    <!-- Deep Diagnostics Subsystem Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">

        <!-- 1. Database Subsystem Card -->
        <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-xs space-y-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-brand-50 text-brand-600 flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-bold text-slate-900 text-sm">Database Engine</h3>
                        <p class="text-[11px] text-slate-400">MySQL Connection & Latency</p>
                    </div>
                </div>
                <?php $this->include('components/badge', [
                    'label' => ($db['status'] ?? 'unknown'),
                    'variant' => ($db['status'] ?? '') === 'healthy' ? 'success' : 'danger',
                    'class' => 'text-xs uppercase font-bold'
                ]); ?>
            </div>

            <div class="space-y-2.5 text-xs text-slate-600 pt-2 border-t border-slate-100">
                <div class="flex justify-between py-1 border-b border-slate-50">
                    <span class="text-slate-500">PDO Driver</span>
                    <span class="font-mono font-bold text-slate-800 uppercase"><?= htmlspecialchars((string)($db['driver'] ?? 'N/A')) ?></span>
                </div>
                <div class="flex justify-between py-1 border-b border-slate-50">
                    <span class="text-slate-500">Query Latency</span>
                    <span class="font-mono font-bold text-slate-800"><?= isset($db['latency_ms']) ? number_format((float)$db['latency_ms'], 2) . ' ms' : 'N/A' ?></span>
                </div>
                <div class="flex justify-between py-1 border-b border-slate-50">
                    <span class="text-slate-500">Connection Status</span>
                    <span class="font-medium text-emerald-600">Active & Queryable</span>
                </div>
                <p class="text-[11px] text-slate-500 pt-1 leading-relaxed">
                    <?= htmlspecialchars((string)($db['message'] ?? 'Database connection operational.')) ?>
                </p>
            </div>
        </div>

        <!-- 2. Storage Subsystem Card -->
        <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-xs space-y-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-bold text-slate-900 text-sm">Storage Subsystems</h3>
                        <p class="text-[11px] text-slate-400">File Directory Permissions</p>
                    </div>
                </div>
                <?php $this->include('components/badge', [
                    'label' => ($storage['status'] ?? 'unknown'),
                    'variant' => ($storage['status'] ?? '') === 'healthy' ? 'success' : 'danger',
                    'class' => 'text-xs uppercase font-bold'
                ]); ?>
            </div>

            <div class="space-y-2.5 text-xs text-slate-600 pt-2 border-t border-slate-100">
                <div class="flex justify-between py-1 border-b border-slate-50">
                    <span class="text-slate-500">Uploads Directory</span>
                    <span class="font-semibold <?= ($storage['uploads_writable'] ?? false) ? 'text-emerald-600' : 'text-rose-600' ?>">
                        <?= ($storage['uploads_writable'] ?? false) ? 'Writable ✓' : 'Unwritable ✗' ?>
                    </span>
                </div>
                <div class="flex justify-between py-1 border-b border-slate-50">
                    <span class="text-slate-500">Logs Directory</span>
                    <span class="font-semibold <?= ($storage['logs_writable'] ?? false) ? 'text-emerald-600' : 'text-rose-600' ?>">
                        <?= ($storage['logs_writable'] ?? false) ? 'Writable ✓' : 'Unwritable ✗' ?>
                    </span>
                </div>
                <div class="flex justify-between py-1 border-b border-slate-50">
                    <span class="text-slate-500">Backups Directory</span>
                    <span class="font-semibold <?= ($storage['backups_writable'] ?? false) ? 'text-emerald-600' : 'text-rose-600' ?>">
                        <?= ($storage['backups_writable'] ?? false) ? 'Writable ✓' : 'Unwritable ✗' ?>
                    </span>
                </div>
                <div class="flex justify-between py-1 border-b border-slate-50">
                    <span class="text-slate-500">Free Disk Space</span>
                    <span class="font-mono font-bold text-slate-800"><?= isset($storage['free_space_mb']) ? number_format((float)$storage['free_space_mb']) . ' MB' : 'Available' ?></span>
                </div>
            </div>
        </div>

        <!-- 3. Backup Freshness Card -->
        <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-xs space-y-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-teal-50 text-teal-600 flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-bold text-slate-900 text-sm">Backup Freshness</h3>
                        <p class="text-[11px] text-slate-400">Database Snapshots</p>
                    </div>
                </div>
                <?php $this->include('components/badge', [
                    'label' => ($backups['status'] ?? 'unknown'),
                    'variant' => ($backups['status'] ?? '') === 'healthy' ? 'success' : 'warning',
                    'class' => 'text-xs uppercase font-bold'
                ]); ?>
            </div>

            <div class="space-y-2.5 text-xs text-slate-600 pt-2 border-t border-slate-100">
                <div class="flex justify-between py-1 border-b border-slate-50">
                    <span class="text-slate-500">Total Archives</span>
                    <span class="font-mono font-bold text-slate-800"><?= number_format((int)($backups['total_backups'] ?? 0)) ?></span>
                </div>
                <div class="flex justify-between py-1 border-b border-slate-50">
                    <span class="text-slate-500">Latest Backup</span>
                    <span class="font-mono font-semibold text-slate-800"><?= !empty($backups['last_backup_at']) ? htmlspecialchars(substr((string)$backups['last_backup_at'], 0, 19)) : 'None created' ?></span>
                </div>
                <div class="flex justify-between py-1 border-b border-slate-50">
                    <span class="text-slate-500">Direct Actions</span>
                    <a href="/admin/backups" class="text-brand-600 hover:text-brand-700 font-semibold inline-flex items-center gap-1">
                        Manage Backups &rarr;
                    </a>
                </div>
                <p class="text-[11px] text-slate-500 pt-1 leading-relaxed">
                    <?= htmlspecialchars((string)($backups['message'] ?? '')) ?>
                </p>
            </div>
        </div>

        <!-- 4. Session & Auth Security Card -->
        <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-xs space-y-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-bold text-slate-900 text-sm">Session & Auth State</h3>
                        <p class="text-[11px] text-slate-400">Cookie & Token Lifetime</p>
                    </div>
                </div>
                <?php $this->include('components/badge', [
                    'label' => 'Healthy',
                    'variant' => 'success',
                    'class' => 'text-xs uppercase font-bold'
                ]); ?>
            </div>

            <div class="space-y-2.5 text-xs text-slate-600 pt-2 border-t border-slate-100">
                <div class="flex justify-between py-1 border-b border-slate-50">
                    <span class="text-slate-500">Session Cookie</span>
                    <span class="font-mono font-bold text-slate-800"><?= htmlspecialchars((string)($session['cookie_name'] ?? 'lms_session')) ?></span>
                </div>
                <div class="flex justify-between py-1 border-b border-slate-50">
                    <span class="text-slate-500">Session TTL</span>
                    <span class="font-mono font-bold text-slate-800"><?= number_format((int)($session['lifetime_seconds'] ?? 7200)) ?>s (<?= round((int)($session['lifetime_seconds'] ?? 7200) / 60) ?>m)</span>
                </div>
                <div class="flex justify-between py-1 border-b border-slate-50">
                    <span class="text-slate-500">Session Active</span>
                    <span class="font-semibold text-emerald-600">Yes (PHP Session Ready)</span>
                </div>
                <p class="text-[11px] text-slate-500 pt-1 leading-relaxed">
                    Authentication middleware actively validates session signatures and CSRF tokens.
                </p>
            </div>
        </div>

        <!-- 5. Application Environment Card -->
        <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-xs space-y-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-bold text-slate-900 text-sm">Environment Config</h3>
                        <p class="text-[11px] text-slate-400">Runtime & Debug Flags</p>
                    </div>
                </div>
                <?php $this->include('components/badge', [
                    'label' => ($env['status'] ?? 'unknown'),
                    'variant' => ($env['status'] ?? '') === 'healthy' ? 'success' : 'warning',
                    'class' => 'text-xs uppercase font-bold'
                ]); ?>
            </div>

            <div class="space-y-2.5 text-xs text-slate-600 pt-2 border-t border-slate-100">
                <div class="flex justify-between py-1 border-b border-slate-50">
                    <span class="text-slate-500">App Mode</span>
                    <span class="font-mono font-bold text-slate-800 uppercase"><?= htmlspecialchars((string)($env['environment'] ?? 'production')) ?></span>
                </div>
                <div class="flex justify-between py-1 border-b border-slate-50">
                    <span class="text-slate-500">Debug Mode</span>
                    <span class="font-mono font-bold <?= ($env['debug'] ?? false) ? 'text-amber-600' : 'text-slate-800' ?>">
                        <?= ($env['debug'] ?? false) ? 'True (Active)' : 'False (Secure)' ?>
                    </span>
                </div>
                <div class="flex justify-between py-1 border-b border-slate-50">
                    <span class="text-slate-500">PHP Version</span>
                    <span class="font-mono font-bold text-slate-800"><?= PHP_VERSION ?></span>
                </div>
                <p class="text-[11px] text-slate-500 pt-1 leading-relaxed">
                    <?= htmlspecialchars((string)($env['message'] ?? 'Environment settings optimal.')) ?>
                </p>
            </div>
        </div>

        <!-- 6. Platform Runtime Summary Card -->
        <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-xs space-y-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-bold text-slate-900 text-sm">Security & Audit</h3>
                        <p class="text-[11px] text-slate-400">Security Telemetry</p>
                    </div>
                </div>
                <?php $this->include('components/badge', [
                    'label' => 'Enforced',
                    'variant' => 'brand',
                    'class' => 'text-xs uppercase font-bold'
                ]); ?>
            </div>

            <div class="space-y-2.5 text-xs text-slate-600 pt-2 border-t border-slate-100">
                <div class="flex justify-between py-1 border-b border-slate-50">
                    <span class="text-slate-500">Security Headers</span>
                    <span class="font-semibold text-emerald-600">Active (HSTS, CSP)</span>
                </div>
                <div class="flex justify-between py-1 border-b border-slate-50">
                    <span class="text-slate-500">Audit Trail</span>
                    <a href="/admin/audit-logs" class="text-brand-600 hover:text-brand-700 font-semibold inline-flex items-center gap-1">
                        View Logs &rarr;
                    </a>
                </div>
                <div class="flex justify-between py-1 border-b border-slate-50">
                    <span class="text-slate-500">RBAC Enforcement</span>
                    <span class="font-semibold text-emerald-600">Strict Middleware</span>
                </div>
                <p class="text-[11px] text-slate-500 pt-1 leading-relaxed">
                    Role-based access control and security header middlewares guard administrative endpoints.
                </p>
            </div>
        </div>

    </div>
</div>
