<div class="space-y-6">
    <!-- Header with Action -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-slate-900">Database Backups & Archival</h2>
            <p class="text-sm text-slate-500 mt-1">Manage database snapshots, verify SHA-256 integrity checksums, and trigger manual archival dumps.</p>
        </div>
        <form method="POST" action="/admin/backups/create">
            <?= csrf_field() ?>
            <button type="submit" class="inline-flex items-center gap-2 px-4 py-2.5 bg-brand-600 hover:bg-brand-700 text-white rounded-lg text-sm font-medium shadow-sm transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
                Generate Backup Now
            </button>
        </form>
    </div>

    <!-- Backup List -->
    <div class="bg-white border border-slate-200 rounded-xl overflow-hidden shadow-sm">
        <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
            <thead class="bg-slate-50 text-slate-600 font-semibold">
                <tr>
                    <th scope="col" class="px-6 py-3.5">Archive Filename</th>
                    <th scope="col" class="px-6 py-3.5">Size</th>
                    <th scope="col" class="px-6 py-3.5">SHA-256 Checksum</th>
                    <th scope="col" class="px-6 py-3.5">Generated At (UTC)</th>
                    <th scope="col" class="px-6 py-3.5 text-right">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200">
                <?php if (empty($backups)): ?>
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center text-slate-500">
                            <div class="flex flex-col items-center justify-center">
                                <svg class="w-10 h-10 text-slate-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
                                <span class="font-medium text-slate-600">No database backups generated yet.</span>
                                <span class="text-xs text-slate-400 mt-1">Click "Generate Backup Now" to create your first database archive.</span>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($backups as $b): ?>
                        <tr class="hover:bg-slate-50 transition">
                            <td class="px-6 py-4 font-mono text-xs font-semibold text-slate-900">
                                <?= e($b['filename']) ?>
                            </td>
                            <td class="px-6 py-4 text-slate-600 text-xs">
                                <?= e($b['size_formatted']) ?>
                            </td>
                            <td class="px-6 py-4 font-mono text-xs text-slate-500" title="<?= e($b['sha256'] ?? '') ?>">
                                <span class="px-2 py-0.5 rounded bg-slate-100 font-mono text-slate-700">
                                    <?= e(substr($b['sha256'] ?? 'N/A', 0, 16)) ?>...
                                </span>
                            </td>
                            <td class="px-6 py-4 text-xs text-slate-600">
                                <?= e($b['created_at']) ?>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <a href="/admin/backups/<?= urlencode($b['filename']) ?>/download" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-brand-50 text-brand-700 hover:bg-brand-100 transition">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                    Download
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
