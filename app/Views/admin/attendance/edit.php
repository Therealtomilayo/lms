<div class="max-w-5xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <a href="/admin/attendance" class="text-sm font-medium text-slate-500 hover:text-brand-600">&larr; Back to Overview</a>
        <span class="inline-flex items-center px-3 py-1 bg-amber-50 text-amber-800 border border-amber-200 rounded-full text-xs font-semibold">
            Administrative Audit Mode
        </span>
    </div>

    <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm space-y-4">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h1 class="text-xl font-bold text-slate-900"><?= htmlspecialchars($class->name) ?> &mdash; Attendance Entry</h1>
                <p class="text-sm text-slate-600 mt-1">Inspection & Historical Correction Panel</p>
            </div>
            <div class="flex items-center gap-2">
                <label for="adminDateSelect" class="text-xs font-semibold uppercase text-slate-500">Date:</label>
                <input type="date" id="adminDateSelect" value="<?= htmlspecialchars($date) ?>" 
                       onchange="window.location.href='/admin/attendance/<?= (int)$class->id ?>/' + this.value + '/edit<?= $classSubjectId ? "?class_subject_id={$classSubjectId}" : '' ?>'"
                       class="px-3 py-1.5 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-brand-500 outline-none">
            </div>
        </div>
    </div>

    <form method="POST" action="/admin/attendance/<?= (int)$class->id ?>/<?= htmlspecialchars($date) ?>/edit" class="space-y-6">
        <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
        <?php if ($classSubjectId): ?>
            <input type="hidden" name="class_subject_id" value="<?= (int)$classSubjectId ?>">
        <?php endif; ?>
        <?php if ($periodNumber): ?>
            <input type="hidden" name="period_number" value="<?= (int)$periodNumber ?>">
        <?php endif; ?>

        <!-- Roster Table -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="p-4 bg-slate-50 border-b border-slate-200 font-semibold text-xs text-slate-600 uppercase">
                Student Roster (<?= count($roster) ?> Enrolled)
            </div>

            <div class="divide-y divide-slate-200">
                <?php foreach ($roster as $index => $row): ?>
                    <div class="p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 hover:bg-slate-50">
                        <div class="flex items-center gap-3">
                            <span class="w-7 h-7 rounded-full bg-slate-100 text-slate-600 text-xs font-bold flex items-center justify-center">
                                <?= $index + 1 ?>
                            </span>
                            <div>
                                <div class="font-semibold text-slate-900"><?= htmlspecialchars($row['student_name']) ?></div>
                                <div class="text-xs text-slate-500">Adm: <?= htmlspecialchars($row['admission_number']) ?></div>
                            </div>
                        </div>

                        <!-- Status Selection -->
                        <div class="flex items-center gap-2">
                            <?php foreach (['present' => 'Present', 'absent' => 'Absent', 'late' => 'Late', 'excused' => 'Excused'] as $stKey => $stLabel): ?>
                                <label class="inline-flex items-center gap-1.5 px-3 py-1.5 border rounded-lg text-xs font-medium cursor-pointer transition <?= $row['status'] === $stKey ? 'bg-brand-50 border-brand-500 text-brand-700 font-bold' : 'border-slate-200 text-slate-600 hover:bg-slate-50' ?>">
                                    <input type="radio" name="status[<?= (int)$row['student_id'] ?>]" value="<?= $stKey ?>" <?= $row['status'] === $stKey ? 'checked' : '' ?> class="text-brand-600 focus:ring-0">
                                    <?= $stLabel ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Mandatory Correction Reason Area -->
            <div class="p-6 bg-amber-50/60 border-t border-amber-200 space-y-4">
                <div>
                    <label for="correction_reason" class="block text-sm font-bold text-slate-900 mb-1">
                        Correction / Audit Justification <span class="text-rose-600">*</span>
                    </label>
                    <textarea name="correction_reason" id="correction_reason" rows="3" required placeholder="Mandatory reason for administrative attendance modification or historical override (e.g. Medical certificate verified by registrar)..."
                              class="w-full px-4 py-2.5 border border-amber-300 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-500 outline-none"></textarea>
                    <p class="text-xs text-slate-500 mt-1">This explanation will be permanently recorded in the system audit log and appended to the attendance record.</p>
                </div>

                <div class="flex justify-end">
                    <button type="submit" class="px-6 py-2.5 bg-brand-600 text-white font-semibold rounded-lg hover:bg-brand-700 transition">
                        Save Changes & Log Audit Trail
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>
