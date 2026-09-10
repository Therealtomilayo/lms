<?php
/**
 * Admin Institutional Staff Geofenced Attendance Register (SRS §22, §23)
 */
?>

<div class="space-y-6">
    <!-- Header Card -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
            <div>
                <nav class="flex items-center gap-2 text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">
                    <span class="text-slate-400">Institutional Oversight</span>
                    <span class="text-slate-300">/</span>
                    <span class="text-slate-700">Attendance</span>
                    <span class="text-slate-300">/</span>
                    <span class="text-brand-700 font-bold">Staff Geofence Register</span>
                </nav>
                <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight leading-tight flex items-center gap-3">
                    <span>Staff Geofenced Attendance Register</span>
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-slate-100 text-slate-700 border border-slate-200">
                        <?= (int)$config['radius_meters'] ?>m Radius Active
                    </span>
                </h1>
                <p class="text-xs text-slate-500 mt-1">
                    Claret International School • Daily faculty and administrative attendance verification (SRS §22–§24).
                </p>
            </div>

            <!-- Top Action Buttons -->
            <div class="flex items-center gap-2.5 flex-wrap">
                <button type="button" onclick="openSettingsModal()"
                        class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs shadow-xs transition">
                    <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    <span>Geofence Settings</span>
                </button>

                <a href="/admin/staff-attendance/breaches?date=<?= htmlspecialchars($date) ?>"
                   class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-rose-50 hover:bg-rose-100 border border-rose-200 text-rose-800 font-bold text-xs shadow-xs transition">
                    <svg class="w-4 h-4 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    <span>Security Breaches (<?= (int)$summary['breaches'] ?>)</span>
                </a>

                <a href="/admin/staff-attendance/export?date=<?= htmlspecialchars($date) ?>"
                   class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-brand-700 hover:bg-brand-800 text-white font-bold text-xs shadow-xs transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    <span>Export CSV</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Flash Alerts -->
    <?php if (!empty($flash_success) && is_string($flash_success)): ?>
        <div class="bg-emerald-50 border border-emerald-200 rounded-xl p-4 text-emerald-800 text-sm font-semibold flex items-center gap-3 shadow-xs">
            <svg class="w-5 h-5 text-emerald-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            <span><?= htmlspecialchars($flash_success) ?></span>
        </div>
    <?php endif; ?>

    <!-- 4-Card Overview Metric Strip -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Total Staff -->
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-xs">
            <div class="flex items-center justify-between text-slate-400">
                <p class="text-xs font-semibold uppercase tracking-wider">Total Faculty</p>
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
            </div>
            <div class="flex items-baseline gap-2 mt-2">
                <h3 class="text-2xl font-extrabold text-slate-900"><?= (int)$summary['total_staff'] ?></h3>
            </div>
            <span class="text-[11px] font-medium text-slate-500 mt-1 block">Active teaching & admin roster</span>
        </div>

        <!-- Clocked In Today -->
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-xs">
            <div class="flex items-center justify-between text-slate-400">
                <p class="text-xs font-semibold uppercase tracking-wider">Clocked In</p>
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">
                    <?= $summary['rate_percent'] ?>%
                </span>
            </div>
            <div class="flex items-baseline gap-2 mt-2">
                <h3 class="text-2xl font-extrabold text-emerald-700"><?= (int)$summary['clocked_in'] ?></h3>
                <span class="text-xs text-slate-500 font-semibold">/ <?= (int)$summary['total_staff'] ?></span>
            </div>
            <span class="text-[11px] font-medium text-slate-500 mt-1 block"><?= (int)$summary['absent'] ?> absent or pending</span>
        </div>

        <!-- Punctual vs Late -->
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-xs">
            <div class="flex items-center justify-between text-slate-400">
                <p class="text-xs font-semibold uppercase tracking-wider">Punctuality</p>
                <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div class="flex items-baseline gap-2 mt-2">
                <h3 class="text-2xl font-extrabold text-slate-900"><?= (int)$summary['punctual'] ?></h3>
                <span class="text-xs text-amber-700 font-bold">(<?= (int)$summary['late'] ?> late arrivals)</span>
            </div>
            <span class="text-[11px] font-medium text-slate-500 mt-1 block">Threshold: <?= htmlspecialchars($config['workday_late_threshold']) ?> AM</span>
        </div>

        <!-- Security Breaches -->
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-xs">
            <div class="flex items-center justify-between text-slate-400">
                <p class="text-xs font-semibold uppercase tracking-wider">Perimeter Breaches</p>
                <svg class="w-5 h-5 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
            </div>
            <div class="flex items-baseline gap-2 mt-2">
                <h3 class="text-2xl font-extrabold text-rose-700"><?= (int)$summary['breaches'] ?></h3>
            </div>
            <span class="text-[11px] font-medium text-slate-500 mt-1 block">Out-of-perimeter clock-in blocks</span>
        </div>
    </div>

    <!-- Filter Toolbar Form -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-4">
        <form method="GET" action="/admin/staff-attendance" class="flex flex-col md:flex-row items-center gap-3">
            <!-- Date Picker -->
            <div class="w-full md:w-48">
                <label for="date" class="sr-only">Date</label>
                <input type="date" id="date" name="date" value="<?= htmlspecialchars($date) ?>" onchange="this.form.submit()"
                       class="w-full text-xs font-semibold bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 text-slate-800 focus:ring-2 focus:ring-brand-500 focus:outline-none">
            </div>

            <!-- Status Filter -->
            <div class="w-full md:w-44">
                <label for="status" class="sr-only">Status</label>
                <select id="status" name="status" onchange="this.form.submit()"
                        class="w-full text-xs font-semibold bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 text-slate-800 focus:ring-2 focus:ring-brand-500 focus:outline-none">
                    <option value="">All Statuses</option>
                    <option value="present" <?= $status === 'present' ? 'selected' : '' ?>>Present (On Time)</option>
                    <option value="late" <?= $status === 'late' ? 'selected' : '' ?>>Late Arrivals</option>
                    <option value="half_day" <?= $status === 'half_day' ? 'selected' : '' ?>>Half Day</option>
                    <option value="excused" <?= $status === 'excused' ? 'selected' : '' ?>>Excused / Leave</option>
                </select>
            </div>

            <!-- Staff Search Input -->
            <div class="flex-1 w-full relative">
                <input type="text" name="q" value="<?= htmlspecialchars($search ?? '') ?>" placeholder="Search staff member by name or email..."
                       class="w-full text-xs bg-slate-50 border border-slate-300 rounded-xl pl-9 pr-3 py-2 text-slate-800 focus:ring-2 focus:ring-brand-500 focus:outline-none">
                <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </div>

            <button type="submit" class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-900 text-white font-bold text-xs shadow-xs transition">
                Filter
            </button>

            <?php if (!empty($status) || !empty($search) || $date !== date('Y-m-d')): ?>
                <a href="/admin/staff-attendance" class="text-xs text-slate-500 hover:text-slate-700 underline font-semibold">Reset</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Attendance Register Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="p-6 border-b border-slate-200 flex items-center justify-between">
            <h2 class="text-base font-extrabold text-slate-900 tracking-tight">Daily Roll-Call Register: <?= date('F j, Y (l)', strtotime($date)) ?></h2>
            <span class="text-xs font-bold text-slate-500"><?= count($register) ?> verified entries</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-700">
                <thead class="bg-slate-50/80 text-slate-600 font-bold uppercase tracking-wider text-[10px] border-b border-slate-200">
                    <tr>
                        <th class="py-3 px-4">Staff Member</th>
                        <th class="py-3 px-4">Clock In</th>
                        <th class="py-3 px-4">Distance from Center</th>
                        <th class="py-3 px-4">Clock Out</th>
                        <th class="py-3 px-4 text-center">Work Duration</th>
                        <th class="py-3 px-4 text-center">Punctuality Status</th>
                        <th class="py-3 px-4">Verification</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if (empty($register)): ?>
                        <tr>
                            <td colspan="7" class="py-10 text-center text-slate-400 font-medium">
                                No staff attendance records found for <?= htmlspecialchars($date) ?>.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($register as $row): ?>
                            <?php $b = $row->getStatusBadge(); ?>
                            <tr class="hover:bg-slate-50/50 transition">
                                <td class="py-3.5 px-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-full bg-brand-50 border border-brand-100 text-brand-700 font-bold flex items-center justify-center text-xs">
                                            <?= strtoupper(substr($row->staffName ?? 'U', 0, 1)) ?>
                                        </div>
                                        <div>
                                            <span class="font-bold text-slate-900 block"><?= htmlspecialchars($row->staffName ?? 'Staff Member') ?></span>
                                            <span class="text-[11px] text-slate-500 block"><?= htmlspecialchars($row->staffEmail ?? '') ?></span>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3.5 px-4 font-mono font-semibold text-slate-800">
                                    <?= htmlspecialchars($row->getFormattedClockInTime()) ?>
                                </td>
                                <td class="py-3.5 px-4 text-slate-600 font-mono text-[11px]">
                                    <span class="<?= $row->clockInDistanceMeters > (int)$config['radius_meters'] ? 'text-rose-600 font-bold' : 'text-slate-700' ?>">
                                        <?= $row->clockInDistanceMeters ?>m
                                    </span>
                                    <span class="text-slate-400 text-[10px]">(&le; <?= (int)$config['radius_meters'] ?>m)</span>
                                </td>
                                <td class="py-3.5 px-4 font-mono text-slate-700">
                                    <?= $row->getFormattedClockOutTime() ?? '<span class="text-amber-700 font-semibold italic">On Duty</span>' ?>
                                </td>
                                <td class="py-3.5 px-4 text-center font-semibold text-slate-800">
                                    <?= htmlspecialchars($row->getFormattedWorkDuration()) ?>
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold border <?= $b['class'] ?>">
                                        <span class="w-1.5 h-1.5 rounded-full <?= $b['dot'] ?>"></span>
                                        <?= $b['label'] ?>
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 text-slate-500 text-[11px]">
                                    <?php if ($row->isOfflineSync): ?>
                                        <span class="text-blue-700 font-semibold">Offline Synced</span>
                                    <?php else: ?>
                                        <span class="text-emerald-700 font-semibold">Live GPS</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Geofence Calibration Settings Modal -->
<div id="settingsModal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xl max-w-lg w-full overflow-hidden">
        <div class="p-6 border-b border-slate-200 flex items-center justify-between">
            <div class="flex items-center gap-2.5">
                <div class="p-2 rounded-xl bg-brand-50 text-brand-700">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                </div>
                <div>
                    <h3 class="text-base font-extrabold text-slate-900">Geofence Perimeter & Rules</h3>
                    <p class="text-xs text-slate-500">Configure school coordinates and punctuality limits (SRS §23).</p>
                </div>
            </div>
            <button type="button" onclick="closeSettingsModal()" class="text-slate-400 hover:text-slate-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <form method="POST" action="/admin/staff-attendance/settings" class="p-6 space-y-4">
            <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">

            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <label class="block text-xs font-bold text-slate-700">Campus Coordinates</label>
                    <button type="button" id="btnFetchAdminLocation" onclick="fetchAdminLocation()"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-200 text-xs font-bold shadow-2xs transition">
                        <svg class="w-3.5 h-3.5 text-emerald-600" id="fetchLocationIcon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        <svg class="w-3.5 h-3.5 animate-spin hidden text-emerald-600" id="fetchLocationSpinner" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                        </svg>
                        <span id="fetchLocationBtnText">Fetch Current Location (GPS)</span>
                    </button>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-600 mb-1">Latitude</label>
                        <input type="number" step="0.00000001" name="latitude" id="adminLatInput" value="<?= (float)$config['latitude'] ?>" required
                               class="w-full text-xs font-mono bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 text-slate-800 transition">
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-600 mb-1">Longitude</label>
                        <input type="number" step="0.00000001" name="longitude" id="adminLngInput" value="<?= (float)$config['longitude'] ?>" required
                               class="w-full text-xs font-mono bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 text-slate-800 transition">
                    </div>
                </div>

                <div id="adminGeoStatusBox" class="hidden text-[11px] p-2 rounded-lg transition"></div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Allowed Radius (Meters)</label>
                <input type="number" min="50" max="2000" name="radius_meters" value="<?= (int)$config['radius_meters'] ?>" required
                       class="w-full text-xs font-mono bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 text-slate-800">
                <p class="text-[11px] text-slate-500 mt-1">Recommended for Claret Mabushi: 200m – 300m.</p>
            </div>

            <div class="grid grid-cols-3 gap-2">
                <div>
                    <label class="block text-[11px] font-bold text-slate-700 mb-1">Duty Start</label>
                    <input type="time" name="workday_start_time" value="<?= htmlspecialchars($config['workday_start_time']) ?>" required
                           class="w-full text-xs bg-slate-50 border border-slate-300 rounded-xl px-2 py-2 text-slate-800">
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-slate-700 mb-1">Late Threshold</label>
                    <input type="time" name="workday_late_threshold" value="<?= htmlspecialchars($config['workday_late_threshold']) ?>" required
                           class="w-full text-xs bg-slate-50 border border-slate-300 rounded-xl px-2 py-2 text-slate-800">
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-slate-700 mb-1">Duty Close</label>
                    <input type="time" name="workday_end_time" value="<?= htmlspecialchars($config['workday_end_time']) ?>" required
                           class="w-full text-xs bg-slate-50 border border-slate-300 rounded-xl px-2 py-2 text-slate-800">
                </div>
            </div>

            <div class="pt-2">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="enforced" value="1" <?= $config['enforced'] ? 'checked' : '' ?>
                           class="rounded text-brand-700 focus:ring-brand-500">
                    <span class="text-xs font-semibold text-slate-700">Enforce Strict GPS Radius Check (reject if outside)</span>
                </label>
            </div>

            <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-2">
                <button type="button" onclick="closeSettingsModal()" class="px-4 py-2 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-100">
                    Cancel
                </button>
                <button type="submit" class="px-5 py-2 rounded-xl bg-brand-700 hover:bg-brand-800 text-white font-bold text-xs shadow-xs transition">
                    Save Calibration
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openSettingsModal() {
    document.getElementById('settingsModal').classList.remove('hidden');
}
function closeSettingsModal() {
    document.getElementById('settingsModal').classList.add('hidden');
}

function fetchAdminLocation() {
    const btn = document.getElementById('btnFetchAdminLocation');
    const btnText = document.getElementById('fetchLocationBtnText');
    const icon = document.getElementById('fetchLocationIcon');
    const spinner = document.getElementById('fetchLocationSpinner');
    const statusBox = document.getElementById('adminGeoStatusBox');
    const latInput = document.getElementById('adminLatInput');
    const lngInput = document.getElementById('adminLngInput');

    if (!navigator.geolocation) {
        statusBox.className = 'text-[11px] p-2.5 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 block';
        statusBox.textContent = 'Geolocation is not supported by your browser.';
        return;
    }

    // Set loading state
    btn.disabled = true;
    icon.classList.add('hidden');
    spinner.classList.remove('hidden');
    btnText.textContent = 'Acquiring GPS...';
    statusBox.className = 'text-[11px] p-2.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-600 block';
    statusBox.textContent = 'Locating device via GPS satellite / network positioning...';

    function onSuccess(pos) {
        const lat = pos.coords.latitude.toFixed(8);
        const lng = pos.coords.longitude.toFixed(8);
        const accuracy = Math.round(pos.coords.accuracy || 10);

        latInput.value = lat;
        lngInput.value = lng;

        // Visual flash highlight
        [latInput, lngInput].forEach(el => {
            el.classList.add('ring-2', 'ring-emerald-500', 'bg-emerald-50/60');
            setTimeout(() => {
                el.classList.remove('ring-2', 'ring-emerald-500', 'bg-emerald-50/60');
            }, 2500);
        });

        statusBox.className = 'text-[11px] p-2.5 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 font-semibold block flex items-center gap-1.5';
        statusBox.innerHTML = `
            <svg class="w-4 h-4 text-emerald-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            <span>Coordinates updated successfully (&plusmn;${accuracy}m accuracy). Click <strong>Save Calibration</strong> to apply.</span>
        `;

        btn.disabled = false;
        icon.classList.remove('hidden');
        spinner.classList.add('hidden');
        btnText.textContent = 'Re-fetch Location';
    }

    function onError(err) {
        // If high accuracy failed, try fallback
        if (err.code === 2 || err.code === 3) {
            statusBox.textContent = 'High-accuracy satellite GPS timed out. Retrying with network location...';
            navigator.geolocation.getCurrentPosition(
                onSuccess,
                onFinalError,
                { enableHighAccuracy: false, timeout: 15000, maximumAge: 60000 }
            );
            return;
        }
        onFinalError(err);
    }

    function onFinalError(err) {
        btn.disabled = false;
        icon.classList.remove('hidden');
        spinner.classList.add('hidden');
        btnText.textContent = 'Fetch Current Location (GPS)';

        let msg = 'Failed to acquire location.';
        if (err.code === 1) {
            msg = 'Location permission was denied. Please allow location access in your browser address bar.';
        } else if (err.code === 2) {
            msg = 'Location provider unavailable. Ensure location services are enabled on your device.';
        } else if (err.code === 3) {
            msg = 'Location request timed out. Please try again.';
        }

        statusBox.className = 'text-[11px] p-2.5 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 block';
        statusBox.textContent = msg;
    }

    // High accuracy attempt (10s timeout, cached within 30s)
    navigator.geolocation.getCurrentPosition(
        onSuccess,
        onError,
        { enableHighAccuracy: true, timeout: 10000, maximumAge: 30000 }
    );
}
</script>
