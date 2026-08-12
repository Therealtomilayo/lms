<div class="max-w-5xl mx-auto space-y-6">
    <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">My Attendance Record</h1>
            <p class="text-sm text-slate-600 mt-1">Review your attendance statistics and roll-call history.</p>
        </div>

        <!-- Term Selector -->
        <div>
            <form method="GET" action="/student/attendance">
                <select name="term_id" onchange="this.form.submit()" class="px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-brand-500 outline-none">
                    <?php foreach ($terms as $t): ?>
                        <option value="<?= (int)$t->id ?>" <?= $selectedTermId === (int)$t->id ? 'selected' : '' ?>><?= htmlspecialchars($t->name) ?></option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>
    </div>

    <!-- Summary Metrics -->
    <div class="grid grid-cols-2 sm:grid-cols-5 gap-4">
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm text-center">
            <span class="text-xs font-semibold uppercase text-slate-500">Attendance Rate</span>
            <div class="text-2xl font-black <?= ($summary['attendance_rate'] ?? 100) >= 75 ? 'text-emerald-600' : 'text-rose-600' ?> mt-1">
                <?= $summary['attendance_rate'] ?? 100 ?>%
            </div>
        </div>

        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm text-center">
            <span class="text-xs font-semibold uppercase text-slate-500">Total Recorded</span>
            <div class="text-2xl font-black text-slate-900 mt-1">
                <?= $summary['total_days'] ?? 0 ?>
            </div>
        </div>

        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm text-center">
            <span class="text-xs font-semibold uppercase text-emerald-600">Present</span>
            <div class="text-2xl font-black text-emerald-600 mt-1">
                <?= $summary['present_days'] ?? 0 ?>
            </div>
        </div>

        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm text-center">
            <span class="text-xs font-semibold uppercase text-amber-600">Late</span>
            <div class="text-2xl font-black text-amber-600 mt-1">
                <?= $summary['late_days'] ?? 0 ?>
            </div>
        </div>

        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm text-center col-span-2 sm:col-span-1">
            <span class="text-xs font-semibold uppercase text-rose-600">Absent</span>
            <div class="text-2xl font-black text-rose-600 mt-1">
                <?= $summary['absent_days'] ?? 0 ?>
            </div>
        </div>
    </div>

    <!-- History Log Table -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-4 bg-slate-50 border-b border-slate-200 font-semibold text-sm text-slate-800">
            Attendance History Log
        </div>

        <?php if (empty($history)): ?>
            <div class="p-8 text-center text-slate-500 text-sm">
                No attendance logs found for this academic term.
            </div>
        <?php else: ?>
            <div class="divide-y divide-slate-200">
                <?php foreach ($history as $rec): ?>
                    <div class="p-4 flex items-center justify-between hover:bg-slate-50">
                        <div class="space-y-0.5">
                            <div class="font-semibold text-slate-900 text-sm">
                                <?= htmlspecialchars(date('l, F j, Y', strtotime($rec->date))) ?>
                            </div>
                            <div class="text-xs text-slate-500">
                                <?php if ($rec->isDaily()): ?>
                                    Daily Roll Call
                                <?php else: ?>
                                    Subject Session (Period <?= $rec->periodNumber ?? 'N/A' ?>)
                                <?php endif; ?>
                            </div>
                        </div>

                        <div>
                            <?php if ($rec->status === 'present'): ?>
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800">Present</span>
                            <?php elseif ($rec->status === 'late'): ?>
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-800">Late</span>
                            <?php elseif ($rec->status === 'absent'): ?>
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-rose-100 text-rose-800">Absent</span>
                            <?php else: ?>
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-sky-100 text-sky-800">Excused</span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
