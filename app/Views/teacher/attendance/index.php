<div class="max-w-6xl mx-auto space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Attendance Register</h1>
            <p class="text-sm text-slate-600 mt-1">Take roll call for your assigned classes or specific subject periods.</p>
        </div>
        <div class="flex items-center gap-3">
            <span class="inline-flex items-center px-3 py-1.5 rounded-lg text-sm font-medium bg-brand-50 text-brand-700 border border-brand-200">
                Today: <?= htmlspecialchars($today) ?>
            </span>
        </div>
    </div>

    <!-- Quick Roll-Call Selection -->
    <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
        <h2 class="text-lg font-semibold text-slate-900 mb-4">Daily Class Roll Call</h2>
        <?php if (empty($classes)): ?>
            <div class="p-8 text-center bg-slate-50 rounded-lg border border-dashed border-slate-200">
                <p class="text-sm text-slate-600">You do not have any teaching allocations in the current academic session.</p>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <?php foreach ($classes as $cls): ?>
                    <div class="p-5 border border-slate-200 rounded-lg hover:border-brand-500 hover:shadow-sm transition bg-slate-50 flex flex-col justify-between">
                        <div>
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-semibold uppercase tracking-wider text-slate-500"><?= htmlspecialchars($cls['level_name'] ?? 'Class') ?></span>
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-emerald-100 text-emerald-800">Active</span>
                            </div>
                            <h3 class="text-lg font-bold text-slate-900 mt-1"><?= htmlspecialchars($cls['name']) ?></h3>
                        </div>
                        <div class="mt-4 pt-3 border-t border-slate-200 flex items-center justify-between">
                            <a href="/teacher/attendance/<?= (int)$cls['id'] ?>/<?= htmlspecialchars($today) ?>" 
                               class="w-full inline-flex justify-center items-center px-4 py-2 bg-brand-600 text-white rounded-lg text-sm font-medium hover:bg-brand-700 transition">
                                Take Today's Roll Call &rarr;
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Subject / Period Attendance Selection -->
    <?php if (!empty($allocations)): ?>
    <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
        <h2 class="text-lg font-semibold text-slate-900 mb-4">Subject & Period Attendance</h2>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-700">
                <thead class="bg-slate-100 text-xs font-semibold uppercase text-slate-600">
                    <tr>
                        <th class="p-3">Class</th>
                        <th class="p-3">Subject</th>
                        <th class="p-3 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    <?php foreach ($allocations as $alloc): ?>
                        <tr class="hover:bg-slate-50">
                            <td class="p-3 font-medium text-slate-900"><?= htmlspecialchars($alloc['class_name']) ?></td>
                            <td class="p-3"><?= htmlspecialchars($alloc['subject_name']) ?> (<?= htmlspecialchars($alloc['subject_code']) ?>)</td>
                            <td class="p-3 text-right">
                                <a href="/teacher/attendance/<?= (int)$alloc['class_id'] ?>/<?= htmlspecialchars($today) ?>?class_subject_id=<?= (int)$alloc['class_subject_id'] ?>" 
                                   class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-brand-700 bg-brand-50 hover:bg-brand-100 rounded-lg transition">
                                    Take Subject Attendance
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>
