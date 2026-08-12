<?php
$selChildId = is_object($selectedChild) ? $selectedChild->id : ($selectedChild['id'] ?? 0);
$selChildName = is_object($selectedChild) ? $selectedChild->name : ($selectedChild['name'] ?? 'Student');
?>
<div class="max-w-5xl mx-auto space-y-6">
    <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Attendance Monitoring</h1>
            <p class="text-sm text-slate-600 mt-1">Track <?= e($selChildName) ?>'s school attendance records and roll-call reliability.</p>
        </div>

        <?php if (!empty($terms)): ?>
        <div>
            <form method="GET" action="/parent/children/<?= (int)$selChildId ?>/attendance">
                <input type="hidden" name="student_id" value="<?= (int)$selChildId ?>">
                <select name="term_id" onchange="this.form.submit()" class="px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-brand-500 outline-none">
                    <?php foreach ($terms as $t): ?>
                        <option value="<?= (int)$t->id ?>" <?= $selectedTermId === (int)$t->id ? 'selected' : '' ?>><?= htmlspecialchars($t->name) ?></option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>
        <?php endif; ?>
    </div>

    <!-- Children Selector Tabs -->
    <?php if (count($children) > 1): ?>
        <div class="flex items-center gap-2 border-b border-slate-200 pb-2">
            <?php foreach ($children as $c): 
                $cId = is_object($c) ? $c->id : ($c['id'] ?? 0);
                $cName = is_object($c) ? $c->name : ($c['name'] ?? '');
            ?>
                <a href="/parent/children/<?= (int)$cId ?>/attendance?term_id=<?= (int)$selectedTermId ?>"
                   class="px-4 py-2 rounded-lg text-sm font-semibold transition <?= ($selChildId === (int)$cId) ? 'bg-brand-600 text-white shadow-sm' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' ?>">
                    <?= htmlspecialchars($cName) ?>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if (!$selectedChild): ?>
        <div class="bg-white p-12 text-center rounded-xl border border-slate-200 text-slate-500">
            No linked student profile found.
        </div>
    <?php else: ?>
        <!-- Summary Cards -->
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

        <!-- History Register -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="p-4 bg-slate-50 border-b border-slate-200 font-semibold text-sm text-slate-800">
                Attendance Log for <?= htmlspecialchars($selChildName) ?>
            </div>

            <?php if (empty($history)): ?>
                <div class="p-8 text-center text-slate-500 text-sm">
                    No attendance logs recorded for this term yet.
                </div>
            <?php else: ?>
                <div class="divide-y divide-slate-200">
                    <?php foreach ($history as $rec): 
                        $recDate = is_object($rec) ? $rec->date : ($rec['date'] ?? '');
                        $recStatus = is_object($rec) ? $rec->status : ($rec['status'] ?? '');
                        $isDaily = is_object($rec) ? $rec->isDaily() : (empty($rec['class_subject_id']));
                        $periodNo = is_object($rec) ? $rec->periodNumber : ($rec['period_number'] ?? null);
                        $subName = is_object($rec) ? ($rec->subjectName ?? null) : ($rec['subject_name'] ?? null);
                    ?>
                        <div class="p-4 flex items-center justify-between hover:bg-slate-50">
                            <div class="space-y-0.5">
                                <div class="font-semibold text-slate-900 text-sm">
                                    <?= htmlspecialchars(date('l, F j, Y', strtotime($recDate))) ?>
                                </div>
                                <div class="text-xs text-slate-500">
                                    <?= $isDaily ? 'Daily Roll Call' : ($subName ? "Subject: {$subName} (Period #{$periodNo})" : "Subject Period #{$periodNo}") ?>
                                </div>
                            </div>

                            <div>
                                <?php if ($recStatus === 'present'): ?>
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800">Present</span>
                                <?php elseif ($recStatus === 'late'): ?>
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-800">Late</span>
                                <?php elseif ($recStatus === 'absent'): ?>
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
    <?php endif; ?>
</div>
