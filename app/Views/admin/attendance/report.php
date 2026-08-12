<div class="space-y-6">
    <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
        <h1 class="text-xl font-bold text-slate-900">Attendance Analytics & Report</h1>
        <p class="text-sm text-slate-600 mt-1">Review class attendance rates, trends, and aggregate metrics across terms.</p>
    </div>

    <!-- Filter Bar -->
    <form method="GET" action="/admin/attendance/report" class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4 items-end">
        <div>
            <label class="block text-xs font-semibold uppercase text-slate-600 mb-1">Class</label>
            <select name="class_id" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-brand-500 outline-none">
                <?php foreach ($classes as $cls): ?>
                    <option value="<?= (int)$cls->id ?>" <?= $selectedClassId === (int)$cls->id ? 'selected' : '' ?>><?= htmlspecialchars($cls->name) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label class="block text-xs font-semibold uppercase text-slate-600 mb-1">Academic Term</label>
            <select name="term_id" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-brand-500 outline-none">
                <?php foreach ($terms as $t): ?>
                    <option value="<?= (int)$t->id ?>" <?= $selectedTermId === (int)$t->id ? 'selected' : '' ?>><?= htmlspecialchars($t->name) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label class="block text-xs font-semibold uppercase text-slate-600 mb-1">Start Date</label>
            <input type="date" name="start_date" value="<?= htmlspecialchars($startDate ?? '') ?>" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-brand-500 outline-none">
        </div>

        <div>
            <button type="submit" class="w-full px-4 py-2 bg-brand-600 text-white rounded-lg text-sm font-semibold hover:bg-brand-700 transition">
                Generate Report
            </button>
        </div>
    </form>

    <!-- Report Table -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-4 bg-slate-50 border-b border-slate-200 font-semibold text-sm text-slate-800">
            Attendance Log Matrix
        </div>

        <?php if (empty($reportData)): ?>
            <div class="p-8 text-center text-slate-500 text-sm">
                No attendance records found for the selected parameters.
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-slate-700">
                    <thead class="bg-slate-100 text-xs font-semibold uppercase text-slate-600">
                        <tr>
                            <th class="p-3">Date</th>
                            <th class="p-3 text-center">Total Students</th>
                            <th class="p-3 text-center text-emerald-600">Present</th>
                            <th class="p-3 text-center text-amber-600">Late</th>
                            <th class="p-3 text-center text-rose-600">Absent</th>
                            <th class="p-3 text-center text-sky-600">Excused</th>
                            <th class="p-3 text-right">Attendance Rate</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        <?php foreach ($reportData as $row): 
                            $total = (int)$row['total_students'];
                            $attended = (int)$row['present_count'] + (int)$row['late_count'];
                            $rate = $total > 0 ? round(($attended / $total) * 100, 1) : 0;
                        ?>
                            <tr class="hover:bg-slate-50">
                                <td class="p-3 font-semibold text-slate-900"><?= htmlspecialchars($row['date']) ?></td>
                                <td class="p-3 text-center font-medium"><?= $total ?></td>
                                <td class="p-3 text-center text-emerald-700 font-semibold"><?= (int)$row['present_count'] ?></td>
                                <td class="p-3 text-center text-amber-700 font-semibold"><?= (int)$row['late_count'] ?></td>
                                <td class="p-3 text-center text-rose-700 font-semibold"><?= (int)$row['absent_count'] ?></td>
                                <td class="p-3 text-center text-sky-700 font-semibold"><?= (int)$row['excused_count'] ?></td>
                                <td class="p-3 text-right">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold <?= $rate >= 75 ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800' ?>">
                                        <?= $rate ?>%
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
