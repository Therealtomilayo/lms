<?php
/**
 * Teacher/Staff Geofenced Attendance Clock-In & History View (SRS §22, §23, §24)
 */
$today = $statusBundle['today'];
$attendance = $statusBundle['attendance'];
$isClockedIn = $statusBundle['is_clocked_in'];
$isClockedOut = $statusBundle['is_clocked_out'];
$geofence = $statusBundle['geofence'];
$workday = $statusBundle['workday'];
?>

<div class="space-y-6">
    <!-- Header Card -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <nav class="flex items-center gap-2 text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">
                    <span class="text-slate-400">Faculty & Staff</span>
                    <span class="text-slate-300">/</span>
                    <span class="text-slate-700">Attendance</span>
                    <span class="text-slate-300">/</span>
                    <span class="text-brand-700 font-bold">Duty Clock-In</span>
                </nav>
                <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight leading-tight flex items-center gap-3">
                    <span>Staff Geofenced Duty Attendance</span>
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-800 border border-emerald-200">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        <span>GPS Verified</span>
                    </span>
                </h1>
                <p class="text-xs text-slate-500 mt-1">
                    Claret International School • Verified physical presence within campus perimeter (SRS §22–§24).
                </p>
            </div>

            <!-- Campus Geofence Metadata Pill -->
            <div class="flex items-center gap-3 flex-wrap">
                <div class="px-3.5 py-2 rounded-xl bg-slate-50 border border-slate-200 flex items-center gap-2.5 text-xs">
                    <svg class="w-4 h-4 text-brand-700 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    <div>
                        <span class="font-bold text-slate-700">Mabushi Campus:</span>
                        <span class="text-slate-500 font-semibold"><?= (int)$geofence['radius_meters'] ?>m perimeter</span>
                    </div>
                </div>

                <div class="px-3.5 py-2 rounded-xl bg-slate-50 border border-slate-200 flex items-center gap-2 text-xs">
                    <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span class="text-slate-600 font-medium">Late Boundary: <strong class="text-slate-900"><?= htmlspecialchars($workday['late_threshold']) ?> AM</strong></span>
                </div>
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

    <?php if (!empty($flash_error) && is_string($flash_error)): ?>
        <div class="bg-rose-50 border border-rose-200 rounded-xl p-4 text-rose-800 text-sm font-semibold flex items-center gap-3 shadow-xs">
            <svg class="w-5 h-5 text-rose-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            <span><?= htmlspecialchars($flash_error) ?></span>
        </div>
    <?php endif; ?>

    <!-- Offline Queue Notification Banner -->
    <div id="offlineBanner" class="hidden bg-amber-50 border border-amber-200 rounded-xl p-4 text-amber-900 shadow-xs flex items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            <svg class="w-5 h-5 text-amber-600 animate-pulse flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636a9 9 0 010 12.728m0 0l-2.829-2.829m2.829 2.829L21 21M15.536 8.464a5 5 0 010 7.072m0 0l-2.829-2.829m-4.243 4.243a9 9 0 01-12.728 0m0 0l2.829-2.829m-2.829 2.829L3 21M8.464 15.536a5 5 0 010-7.072m0 0l2.829 2.829" />
            </svg>
            <div>
                <p class="text-xs font-bold text-amber-900" id="offlineStatusText">You are currently operating in Offline Mode.</p>
                <p class="text-[11px] text-amber-700">Attendance will be captured securely locally and synced once connectivity returns (SRS §24).</p>
            </div>
        </div>
        <button type="button" onclick="syncPendingRecords()" class="px-3 py-1.5 rounded-lg bg-amber-600 hover:bg-amber-700 text-white font-bold text-xs shadow-xs transition">
            Sync Now (<span id="pendingCount">0</span>)
        </button>
    </div>

    <!-- Main Live Terminal Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <!-- Interactive Clock-In / Duty Terminal Card (Left 7 Columns) -->
        <div class="lg:col-span-7 bg-white rounded-2xl border border-slate-200 shadow-xs p-6 flex flex-col justify-between relative overflow-hidden">
            <!-- Background Watermark Pattern -->
            <div class="absolute -right-10 -bottom-10 opacity-5 pointer-events-none text-slate-900">
                <svg class="w-64 h-64" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm.5-13H11v6l5.25 3.15.75-1.23-4.5-2.67z"/></svg>
            </div>

            <div>
                <div class="flex items-center justify-between pb-4 border-b border-slate-100 mb-6">
                    <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Duty Terminal</span>
                    <span class="text-xs font-semibold text-slate-500" id="liveDateDisplay"><?= date('l, F j, Y') ?></span>
                </div>

                <!-- Digital Clock Display -->
                <div class="text-center py-4">
                    <div class="text-5xl sm:text-6xl font-black text-slate-900 tracking-tight font-mono" id="liveClockDisplay">
                        <?= date('h:i:s A') ?>
                    </div>
                    <div class="text-xs text-slate-500 font-medium mt-1">
                        West Africa Time (WAT / UTC+1) • Claret International School (Mabushi Campus)
                    </div>
                </div>

                <!-- Geolocation Radar & Status -->
                <div class="my-6 p-4 rounded-xl border transition-all" id="geoCard">
                    <div class="flex items-start gap-3">
                        <div class="p-2 rounded-xl bg-white border border-slate-200 text-brand-700 shadow-xs" id="geoIconBox">
                            <svg class="w-5 h-5 animate-spin" id="geoSpinner" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                            </svg>
                            <svg class="w-5 h-5 hidden text-emerald-600" id="geoCheckIcon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            <svg class="w-5 h-5 hidden text-amber-600" id="geoWarningIcon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between gap-2 flex-wrap">
                                <h4 class="text-xs font-bold text-slate-900" id="geoStatusTitle">Acquiring GPS Satellite Signal...</h4>
                                <div class="flex items-center gap-3">
                                    <button type="button" onclick="detectLocation(true)" class="text-[11px] font-bold text-brand-700 hover:text-brand-900 underline flex items-center gap-1">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                        <span>Refresh GPS</span>
                                    </button>
                                </div>
                            </div>
                            <p class="text-xs text-slate-500 mt-0.5" id="geoStatusDesc">Please allow browser location permissions when prompted to verify campus proximity.</p>
                            
                            <div class="mt-2 flex items-center gap-4 text-[11px] text-slate-500 font-mono">
                                <span>Dist: <strong id="geoDistanceMeters" class="text-slate-800">Calculating...</strong></span>
                                <span>Perimeter: <strong class="text-slate-800">&le; <?= (int)$geofence['radius_meters'] ?>m</strong></span>
                                <span>Accuracy: <strong id="geoAccuracy" class="text-slate-800">--</strong></span>
                            </div>

                            <!-- Troubleshooting / Simulation Assistant (revealed on error or request) -->
                            <div id="geoTroubleshootBox" class="hidden mt-3 pt-3 border-t border-slate-200 text-xs space-y-2">
                                <div class="p-3 bg-amber-50/80 border border-amber-200 rounded-lg text-amber-900 text-[11px] space-y-1.5">
                                    <p class="font-bold flex items-center gap-1.5 text-amber-950">
                                        <svg class="w-3.5 h-3.5 text-amber-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        <span>How to Enable Location Access:</span>
                                    </p>
                                    <ol class="list-decimal list-inside space-y-1 text-amber-800">
                                        <li>Click the <strong>site settings icon (🔒 or 🎛️)</strong> on the left side of your browser URL address bar (<code class="bg-amber-100 px-1 py-0.5 rounded text-amber-950 font-mono">https://lms.test</code>).</li>
                                        <li>Find <strong>Location</strong> and change the setting to <strong>Allow</strong>.</li>
                                        <li>On Windows PC: Ensure <strong>Windows Settings &rarr; Privacy & security &rarr; Location</strong> is toggled <strong>ON</strong>.</li>
                                    </ol>
                                </div>
                                <div class="flex items-center justify-between gap-2 pt-1">
                                    <span class="text-[11px] text-slate-500">Desktop PC in faculty office or testing without hardware GPS?</span>
                                    <button type="button" onclick="simulateCampusLocation()" class="px-2.5 py-1 rounded-md bg-slate-100 hover:bg-slate-200 text-slate-800 font-bold text-[11px] border border-slate-300 transition flex items-center gap-1">
                                        <svg class="w-3 h-3 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                        <span>Use Campus Coordinates (Office / Dev)</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action Controls Form -->
            <div class="pt-4 border-t border-slate-100">
                <?php if (!$isClockedIn): ?>
                    <!-- State 1: Ready to Clock In -->
                    <form id="clockInForm" method="POST" action="/teacher/staff-attendance/clock-in" class="space-y-4">
                        <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
                        <input type="hidden" name="latitude" id="inputClockInLat" value="">
                        <input type="hidden" name="longitude" id="inputClockInLng" value="">
                        <input type="hidden" name="device_fingerprint" id="inputClockInFp" value="">

                        <button type="submit" id="btnClockIn" disabled
                                class="w-full py-4 px-6 rounded-xl bg-slate-300 text-slate-500 font-extrabold text-base shadow-sm transition flex items-center justify-center gap-3 cursor-not-allowed">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                            </svg>
                            <span id="btnClockInText">Awaiting Verified GPS Signal...</span>
                        </button>
                    </form>
                <?php elseif (!$isClockedOut): ?>
                    <!-- State 2: Active on Duty -> Ready to Clock Out -->
                    <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 mb-4 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <span class="relative flex h-3 w-3">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-3 w-3 bg-emerald-500"></span>
                            </span>
                            <div>
                                <h4 class="text-xs font-bold text-emerald-950">Currently On Duty</h4>
                                <p class="text-[11px] text-emerald-700">Clocked in at <strong><?= htmlspecialchars($attendance->getFormattedClockInTime()) ?></strong> • <?= $attendance->isLate ? 'Late Arrival' : 'On Time' ?></p>
                            </div>
                        </div>
                        <div class="text-right">
                            <span class="text-xs font-bold text-emerald-900" id="liveDutyTimer">Calculating...</span>
                        </div>
                    </div>

                    <form id="clockOutForm" method="POST" action="/teacher/staff-attendance/clock-out" class="space-y-4">
                        <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
                        <input type="hidden" name="latitude" id="inputClockOutLat" value="">
                        <input type="hidden" name="longitude" id="inputClockOutLng" value="">
                        <input type="hidden" name="device_fingerprint" id="inputClockOutFp" value="">

                        <button type="submit" id="btnClockOut"
                                class="w-full py-4 px-6 rounded-xl bg-amber-600 hover:bg-amber-700 text-white font-extrabold text-base shadow-sm hover:shadow transition flex items-center justify-center gap-3">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                            </svg>
                            <span>Clock Out & Conclude Day</span>
                        </button>
                    </form>
                <?php else: ?>
                    <!-- State 3: Daily Session Complete -->
                    <div class="p-5 rounded-xl bg-slate-50 border border-slate-200 text-center space-y-2">
                        <div class="w-10 h-10 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center mx-auto mb-1">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                        </div>
                        <h4 class="text-sm font-bold text-slate-900">Today's Duty Session Completed</h4>
                        <p class="text-xs text-slate-600">
                            Clocked in at <strong class="text-slate-900"><?= htmlspecialchars($attendance->getFormattedClockInTime()) ?></strong> • Clocked out at <strong class="text-slate-900"><?= htmlspecialchars($attendance->getFormattedClockOutTime()) ?></strong>
                        </p>
                        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800">
                            <span>Total Duty: <?= htmlspecialchars($attendance->getFormattedWorkDuration()) ?></span>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Today's Attendance Overview & Rules (Right 5 Columns) -->
        <div class="lg:col-span-5 space-y-6">
            <!-- Today's Record Summary Card -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6">
                <h3 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-4">Today's Attendance Status</h3>

                <?php if ($attendance): ?>
                    <?php $badge = $attendance->getStatusBadge(); ?>
                    <div class="space-y-4">
                        <div class="p-4 rounded-xl border <?= $badge['class'] ?> flex items-center justify-between">
                            <div class="flex items-center gap-2.5">
                                <span class="w-2.5 h-2.5 rounded-full <?= $badge['dot'] ?>"></span>
                                <span class="text-sm font-extrabold"><?= $badge['label'] ?></span>
                            </div>
                            <span class="text-xs font-bold font-mono">Date: <?= htmlspecialchars($attendance->attendanceDate) ?></span>
                        </div>

                        <div class="grid grid-cols-2 gap-3 text-xs">
                            <div class="p-3 rounded-xl bg-slate-50 border border-slate-200">
                                <span class="text-slate-400 block font-semibold">Clock-In Time</span>
                                <span class="text-slate-900 font-extrabold text-sm"><?= htmlspecialchars($attendance->getFormattedClockInTime()) ?></span>
                                <span class="text-[10px] text-slate-500 block mt-0.5"><?= $attendance->clockInDistanceMeters ?>m from campus center</span>
                            </div>

                            <div class="p-3 rounded-xl bg-slate-50 border border-slate-200">
                                <span class="text-slate-400 block font-semibold">Clock-Out Time</span>
                                <span class="text-slate-900 font-extrabold text-sm"><?= $attendance->getFormattedClockOutTime() ?? 'On Duty' ?></span>
                                <span class="text-[10px] text-slate-500 block mt-0.5"><?= $attendance->isClockedOut() ? $attendance->clockOutDistanceMeters . 'm from center' : 'Session active' ?></span>
                            </div>
                        </div>

                        <div class="p-3 rounded-xl bg-slate-50 border border-slate-200 flex items-center justify-between text-xs">
                            <span class="text-slate-500 font-semibold">Total Duration on Duty:</span>
                            <span class="font-extrabold text-slate-900"><?= htmlspecialchars($attendance->getFormattedWorkDuration()) ?></span>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="p-6 rounded-xl bg-slate-50 border border-dashed border-slate-200 text-center space-y-2">
                        <svg class="w-8 h-8 text-slate-400 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <h4 class="text-xs font-bold text-slate-700">No Clock-In Recorded Yet</h4>
                        <p class="text-[11px] text-slate-500">Verify your location on campus and click Clock In on the terminal.</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Campus Geofence Policy Notice Card -->
            <div class="bg-slate-900 text-white rounded-2xl p-6 shadow-sm border border-slate-800 relative overflow-hidden">
                <div class="flex items-center gap-2 text-amber-400 text-xs font-bold uppercase tracking-wider mb-2">
                    <svg class="w-4 h-4 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span>Statutory Institutional Rules</span>
                </div>
                <h4 class="text-base font-extrabold text-white mb-2">Claret Attendance Policy</h4>
                <ul class="text-xs text-slate-300 space-y-2.5 list-disc list-inside">
                    <li>Arrival after <strong class="text-white font-semibold"><?= htmlspecialchars($workday['late_threshold']) ?> AM</strong> is logged automatically as <strong class="text-amber-400 font-semibold">Late Arrival</strong>.</li>
                    <li>Clock-in outside the <strong class="text-white font-semibold"><?= (int)$geofence['radius_meters'] ?>m campus perimeter</strong> will be rejected and logged as a perimeter breach.</li>
                    <li>If network connectivity is lost, records queue securely in offline storage and sync when reconnected.</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Personal Monthly Attendance History Log -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="p-6 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h2 class="text-base font-extrabold text-slate-900 tracking-tight">Recent Attendance History</h2>
                <p class="text-xs text-slate-500 mt-0.5">Your personal verified attendance records for the last 30 duty days.</p>
            </div>
            <span class="text-xs font-bold text-slate-500">Showing <?= count($history) ?> records</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-700">
                <thead class="bg-slate-50/80 text-slate-600 font-bold uppercase tracking-wider text-[10px] border-b border-slate-200">
                    <tr>
                        <th class="py-3 px-4">Date</th>
                        <th class="py-3 px-4">Clock In</th>
                        <th class="py-3 px-4">Distance</th>
                        <th class="py-3 px-4">Clock Out</th>
                        <th class="py-3 px-4 text-center">Work Duration</th>
                        <th class="py-3 px-4 text-center">Status</th>
                        <th class="py-3 px-4">Verification</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if (empty($history)): ?>
                        <tr>
                            <td colspan="7" class="py-8 text-center text-slate-400 font-medium">
                                No attendance records logged yet.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($history as $h): ?>
                            <?php $b = $h->getStatusBadge(); ?>
                            <tr class="hover:bg-slate-50/50 transition">
                                <td class="py-3.5 px-4 font-bold text-slate-900">
                                    <?= date('M j, Y (D)', strtotime($h->attendanceDate)) ?>
                                </td>
                                <td class="py-3.5 px-4 font-mono font-semibold text-slate-800">
                                    <?= htmlspecialchars($h->getFormattedClockInTime()) ?>
                                </td>
                                <td class="py-3.5 px-4 text-slate-600">
                                    <span class="inline-flex items-center gap-1 font-mono text-[11px]">
                                        <span><?= $h->clockInDistanceMeters ?>m</span>
                                        <span class="text-slate-400 text-[10px]">from gate</span>
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 font-mono text-slate-700">
                                    <?= $h->getFormattedClockOutTime() ?? '<span class="text-amber-700 font-semibold italic">Not logged</span>' ?>
                                </td>
                                <td class="py-3.5 px-4 text-center font-semibold text-slate-800">
                                    <?= htmlspecialchars($h->getFormattedWorkDuration()) ?>
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold border <?= $b['class'] ?>">
                                        <span class="w-1.5 h-1.5 rounded-full <?= $b['dot'] ?>"></span>
                                        <?= $b['label'] ?>
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 text-slate-500 text-[11px]">
                                    <?php if ($h->isOfflineSync): ?>
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

<!-- Client Geolocation, Live Timer & Offline Queue Synchronization Script -->
<script>
(function() {
    const SCHOOL_LAT = <?= (float)$geofence['latitude'] ?>;
    const SCHOOL_LNG = <?= (float)$geofence['longitude'] ?>;
    const ALLOWED_RADIUS = <?= (int)$geofence['radius_meters'] ?>;
    const ENFORCED = <?= $geofence['enforced'] ? 'true' : 'false' ?>;

    const clockEl = document.getElementById('liveClockDisplay');
    const geoCard = document.getElementById('geoCard');
    const geoIconBox = document.getElementById('geoIconBox');
    const geoSpinner = document.getElementById('geoSpinner');
    const geoCheckIcon = document.getElementById('geoCheckIcon');
    const geoWarningIcon = document.getElementById('geoWarningIcon');
    const geoStatusTitle = document.getElementById('geoStatusTitle');
    const geoStatusDesc = document.getElementById('geoStatusDesc');
    const geoDistanceMeters = document.getElementById('geoDistanceMeters');
    const geoAccuracy = document.getElementById('geoAccuracy');
    const btnClockIn = document.getElementById('btnClockIn');
    const btnClockInText = document.getElementById('btnClockInText');

    let currentLat = null;
    let currentLng = null;
    let currentDistance = null;

    // 1. Live Digital Clock
    function updateClock() {
        const now = new Date();
        if (clockEl) {
            clockEl.textContent = now.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true });
        }
    }
    setInterval(updateClock, 1000);

    // Live Duty Duration Timer
    <?php if ($isClockedIn && !$isClockedOut): ?>
    const clockInTimestamp = <?= (int)(strtotime($attendance->clockInAt) * 1000) ?>;
    function updateDutyTimer() {
        const diffMs = Date.now() - clockInTimestamp;
        const totalMins = Math.floor(diffMs / (1000 * 60));
        const hours = Math.floor(totalMins / 60);
        const mins = totalMins % 60;
        const secs = Math.floor((diffMs % (1000 * 60)) / 1000);
        const timerEl = document.getElementById('liveDutyTimer');
        if (timerEl) {
            timerEl.textContent = `${hours}h ${mins}m ${secs}s on duty`;
        }
    }
    setInterval(updateDutyTimer, 1000);
    updateDutyTimer();
    <?php endif; ?>

    // 2. Haversine Distance (Client-Side)
    function calculateDistance(lat1, lon1, lat2, lon2) {
        const R = 6371000; // meters
        const dLat = (lat2 - lat1) * Math.PI / 180;
        const dLon = (lon2 - lon1) * Math.PI / 180;
        const a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
                  Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
                  Math.sin(dLon / 2) * Math.sin(dLon / 2);
        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
        return Math.round(R * c);
    }

    // 3. Device Fingerprint Generator
    function getFingerprint() {
        let fp = localStorage.getItem('claret_staff_fp');
        if (!fp) {
            fp = 'fp_' + Math.random().toString(36).substring(2, 15) + Date.now().toString(36);
            localStorage.setItem('claret_staff_fp', fp);
        }
        return fp;
    }

    const geoTroubleshootBox = document.getElementById('geoTroubleshootBox');

    function applyLocationSuccess(pos, isSimulated = false) {
        currentLat = pos.coords.latitude;
        currentLng = pos.coords.longitude;
        const accuracy = Math.round(pos.coords.accuracy || 10);

        currentDistance = calculateDistance(currentLat, currentLng, SCHOOL_LAT, SCHOOL_LNG);

        geoSpinner.classList.add('hidden');
        geoWarningIcon.classList.add('hidden');
        geoCheckIcon.classList.remove('hidden');
        if (geoTroubleshootBox) geoTroubleshootBox.classList.add('hidden');

        geoDistanceMeters.textContent = `${currentDistance}m`;
        geoAccuracy.textContent = isSimulated ? 'Campus Gate (Fixed)' : `&plusmn;${accuracy}m`;

        // Populate hidden inputs
        const inLat = document.getElementById('inputClockInLat');
        const inLng = document.getElementById('inputClockInLng');
        const inFp = document.getElementById('inputClockInFp');
        if (inLat) inLat.value = currentLat;
        if (inLng) inLng.value = currentLng;
        if (inFp) inFp.value = getFingerprint();

        const outLat = document.getElementById('inputClockOutLat');
        const outLng = document.getElementById('inputClockOutLng');
        const outFp = document.getElementById('inputClockOutFp');
        if (outLat) outLat.value = currentLat;
        if (outLng) outLng.value = currentLng;
        if (outFp) outFp.value = getFingerprint();

        if (!ENFORCED || currentDistance <= ALLOWED_RADIUS) {
            // Inside Perimeter!
            geoCard.className = 'my-6 p-4 rounded-xl border border-emerald-200 bg-emerald-50/60 transition-all';
            geoIconBox.className = 'p-2 rounded-xl bg-emerald-100 text-emerald-700 shadow-xs';
            geoStatusTitle.textContent = isSimulated ? 'Campus Office Location Applied' : 'Within Campus Perimeter';
            geoStatusDesc.textContent = isSimulated
                ? 'Campus gate coordinates applied for office duty. Ready to record verified attendance.'
                : `You are ${currentDistance}m from campus center (Allowed: ${ALLOWED_RADIUS}m). Ready to record attendance.`;

            if (btnClockIn) {
                btnClockIn.disabled = false;
                btnClockIn.className = 'w-full py-4 px-6 rounded-xl bg-brand-700 hover:bg-brand-800 text-white font-extrabold text-base shadow-sm hover:shadow transition flex items-center justify-center gap-3 cursor-pointer';
                if (btnClockInText) btnClockInText.textContent = isSimulated ? 'Clock In to Duty (Campus Office)' : 'Clock In to Duty Now';
            }
        } else {
            // Outside Perimeter!
            geoCard.className = 'my-6 p-4 rounded-xl border border-rose-200 bg-rose-50/60 transition-all';
            geoIconBox.className = 'p-2 rounded-xl bg-rose-100 text-rose-700 shadow-xs';
            geoWarningIcon.classList.remove('hidden');
            geoCheckIcon.classList.add('hidden');
            geoStatusTitle.textContent = 'Outside Approved Campus Perimeter';
            geoStatusDesc.textContent = `You are ${currentDistance}m away from campus center. Maximum permitted radius is ${ALLOWED_RADIUS}m.`;

            if (btnClockIn) {
                btnClockIn.disabled = true;
                btnClockIn.className = 'w-full py-4 px-6 rounded-xl bg-slate-200 text-slate-400 font-extrabold text-base shadow-sm transition flex items-center justify-center gap-3 cursor-not-allowed';
                if (btnClockInText) btnClockInText.textContent = `Outside Campus Radius (${currentDistance}m / max ${ALLOWED_RADIUS}m)`;
            }
        }
    }

    function applyLocationError(err) {
        geoSpinner.classList.add('hidden');
        geoCheckIcon.classList.add('hidden');
        geoWarningIcon.classList.remove('hidden');
        geoCard.className = 'my-6 p-4 rounded-xl border border-amber-200 bg-amber-50/40 transition-all';
        geoIconBox.className = 'p-2 rounded-xl bg-amber-100 text-amber-700 shadow-xs';
        if (geoTroubleshootBox) geoTroubleshootBox.classList.remove('hidden');

        if (err.code === 1) { // PERMISSION_DENIED
            geoStatusTitle.textContent = 'GPS / Location Permission Denied';
            geoStatusDesc.textContent = 'Location access is blocked by your browser or Windows privacy settings. See below to unblock or use Campus Coordinates.';
        } else if (err.code === 2) { // POSITION_UNAVAILABLE
            geoStatusTitle.textContent = 'Location Provider Unavailable (No GPS)';
            geoStatusDesc.textContent = 'Your device cannot resolve GPS coordinates (common on desktop PCs without GPS/Wi-Fi). Click "Use Campus Coordinates" below.';
        } else if (err.code === 3) { // TIMEOUT
            geoStatusTitle.textContent = 'GPS Acquisition Timed Out';
            geoStatusDesc.textContent = 'Satellite signal acquisition timed out. Try clicking "Refresh GPS" or use Campus Coordinates below.';
        } else {
            geoStatusTitle.textContent = 'Location Acquisition Failed';
            geoStatusDesc.textContent = err.message || 'Unable to retrieve your location.';
        }

        if (btnClockIn) {
            btnClockIn.disabled = true;
            btnClockIn.className = 'w-full py-4 px-6 rounded-xl bg-slate-200 text-slate-400 font-extrabold text-base shadow-sm transition flex items-center justify-center gap-3 cursor-not-allowed';
            if (btnClockInText) btnClockInText.textContent = 'Awaiting Location Resolution...';
        }
    }

    // 4. Geolocation Detection with 2-Tier Fallback
    window.detectLocation = function(isManual = false) {
        if (!navigator.geolocation) {
            geoStatusTitle.textContent = 'Geolocation Unsupported';
            geoStatusDesc.textContent = 'Your browser does not support GPS location services.';
            if (geoTroubleshootBox) geoTroubleshootBox.classList.remove('hidden');
            return;
        }

        geoSpinner.classList.remove('hidden');
        geoCheckIcon.classList.add('hidden');
        geoWarningIcon.classList.add('hidden');
        geoStatusTitle.textContent = 'Locating Device via GPS...';
        geoStatusDesc.textContent = 'Acquiring satellite lock and verifying campus perimeter...';

        // Attempt Tier 1: High Accuracy GPS (10s timeout, cached within 30s)
        navigator.geolocation.getCurrentPosition(
            function(pos) {
                applyLocationSuccess(pos, false);
            },
            function(err1) {
                if (err1.code === 1) {
                    // Explicitly denied by user, browser policy, or OS
                    applyLocationError(err1);
                    return;
                }
                // Attempt Tier 2: Fallback to standard network / Wi-Fi positioning (15s timeout)
                navigator.geolocation.getCurrentPosition(
                    function(pos2) {
                        applyLocationSuccess(pos2, false);
                    },
                    function(err2) {
                        applyLocationError(err2);
                    },
                    { enableHighAccuracy: false, timeout: 15000, maximumAge: 60000 }
                );
            },
            { enableHighAccuracy: true, timeout: 10000, maximumAge: 30000 }
        );
    };

    // Auto-listen for browser permission changes (e.g. user toggles Allow in address bar)
    if (navigator.permissions && navigator.permissions.query) {
        navigator.permissions.query({ name: 'geolocation' }).then(function(perm) {
            perm.onchange = function() {
                if (this.state === 'granted') {
                    detectLocation();
                }
            };
        }).catch(function() {});
    }

    // Campus Coordinates Override for Office Desktop PCs or Testing
    window.simulateCampusLocation = function() {
        applyLocationSuccess({
            coords: {
                latitude: SCHOOL_LAT,
                longitude: SCHOOL_LNG,
                accuracy: 5
            }
        }, true);
    };

    // Auto-detect on page load
    detectLocation();

    // 5. Offline Queue Management (SRS §24)
    function updateOfflineStatus() {
        const isOffline = !navigator.onLine;
        const banner = document.getElementById('offlineBanner');
        const queue = JSON.parse(localStorage.getItem('claret_offline_attendance') || '[]');
        const countEl = document.getElementById('pendingCount');
        if (countEl) countEl.textContent = queue.length;

        if (isOffline || queue.length > 0) {
            banner.classList.remove('hidden');
        } else {
            banner.classList.add('hidden');
        }
    }

    window.syncPendingRecords = function() {
        const queue = JSON.parse(localStorage.getItem('claret_offline_attendance') || '[]');
        if (queue.length === 0) {
            alert('No offline records pending synchronization.');
            return;
        }

        fetch('/teacher/staff-attendance/sync', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ records: queue })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                localStorage.removeItem('claret_offline_attendance');
                alert(`Successfully synchronized ${data.synced_count} offline record(s)!`);
                window.location.reload();
            } else {
                alert('Sync failed: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(err => {
            alert('Connection failed during sync: ' + err.message);
        });
    };

    window.addEventListener('online', function() {
        updateOfflineStatus();
        syncPendingRecords();
    });
    window.addEventListener('offline', updateOfflineStatus);
    updateOfflineStatus();
})();
</script>
