<?php
/**
 * Admin Geofence Security Breaches & Out-of-Perimeter Violation Audit Log (SRS §23)
 */
?>

<div class="space-y-6">
    <!-- Header Card -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <nav class="flex items-center gap-2 text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">
                    <span class="text-slate-400">Institutional Oversight</span>
                    <span class="text-slate-300">/</span>
                    <a href="/admin/staff-attendance" class="text-slate-600 hover:text-slate-800">Staff Attendance</a>
                    <span class="text-slate-300">/</span>
                    <span class="text-rose-700 font-bold">Security Breaches</span>
                </nav>
                <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight leading-tight flex items-center gap-3">
                    <span>Geofence Perimeter Breach Audit Log</span>
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-rose-50 text-rose-800 border border-rose-200">
                        Fraud Prevention Trail
                    </span>
                </h1>
                <p class="text-xs text-slate-500 mt-1">
                    Claret International School • Audit trail of blocked out-of-bounds clock-in attempts (SRS §23).
                </p>
            </div>

            <a href="/admin/staff-attendance"
               class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs shadow-xs transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                <span>Back to Register</span>
            </a>
        </div>
    </div>

    <!-- Breaches Table Card -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="p-6 border-b border-slate-200 flex items-center justify-between">
            <h2 class="text-base font-extrabold text-slate-900 tracking-tight">Recorded Out-of-Bounds Clock-In Attempts</h2>
            <span class="text-xs font-bold text-slate-500"><?= count($breaches) ?> logged events</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-700">
                <thead class="bg-slate-50/80 text-slate-600 font-bold uppercase tracking-wider text-[10px] border-b border-slate-200">
                    <tr>
                        <th class="py-3 px-4">Attempted At</th>
                        <th class="py-3 px-4">Staff Candidate</th>
                        <th class="py-3 px-4">Action Type</th>
                        <th class="py-3 px-4">Recorded Coordinates</th>
                        <th class="py-3 px-4">Distance from Gate</th>
                        <th class="py-3 px-4">Allowed Radius</th>
                        <th class="py-3 px-4">IP Address & Device</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if (empty($breaches)): ?>
                        <tr>
                            <td colspan="7" class="py-12 text-center text-slate-400 font-medium">
                                <svg class="w-8 h-8 text-emerald-500 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                No perimeter breaches recorded. All staff clock-ins conform to approved boundaries.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($breaches as $b): ?>
                            <tr class="hover:bg-rose-50/30 transition">
                                <td class="py-3.5 px-4 font-mono font-bold text-slate-900">
                                    <?= date('M j, Y • h:i:s A', strtotime($b->attemptedAt)) ?>
                                </td>
                                <td class="py-3.5 px-4">
                                    <span class="font-bold text-slate-900 block"><?= htmlspecialchars($b->staffName ?? 'User #' . $b->userId) ?></span>
                                    <span class="text-[11px] text-slate-500 block"><?= htmlspecialchars($b->staffEmail ?? '') ?></span>
                                </td>
                                <td class="py-3.5 px-4 font-semibold text-slate-700">
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-700">
                                        <?= strtoupper($b->attemptType) ?>
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 font-mono text-[11px] text-slate-600">
                                    <?= round($b->latitude, 6) ?>, <?= round($b->longitude, 6) ?>
                                </td>
                                <td class="py-3.5 px-4 font-mono font-bold text-rose-700 text-sm">
                                    <?= number_format($b->distanceMeters) ?>m
                                </td>
                                <td class="py-3.5 px-4 font-mono text-slate-600">
                                    &le; <?= $b->allowedRadiusMeters ?>m
                                </td>
                                <td class="py-3.5 px-4 text-slate-500 text-[11px]">
                                    <span class="font-mono block text-slate-700 font-semibold"><?= htmlspecialchars($b->ipAddress ?? 'N/A') ?></span>
                                    <span class="text-[10px] truncate max-w-xs block text-slate-400" title="<?= htmlspecialchars($b->deviceFingerprint ?? '') ?>">
                                        <?= htmlspecialchars($b->deviceFingerprint ? substr($b->deviceFingerprint, 0, 16) . '...' : 'No Fingerprint') ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
