<div class="space-y-6">
    <!-- Top info & actions -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
        <div>
            <h2 class="text-lg font-bold text-slate-900">Class Roster & Enrollments</h2>
            <p class="text-sm text-slate-500">Manage student enrollment rosters and subject allocations per academic session.</p>
        </div>
        <?php if ($canManage ?? false): ?>
            <div class="flex items-center gap-3">
                <button type="button" onclick="document.getElementById('enroll-modal').classList.remove('hidden')" 
                        class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold text-white bg-brand-600 rounded-lg hover:bg-brand-700 shadow-sm transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Enroll Student
                </button>
            </div>
        <?php endif; ?>
    </div>

    <!-- Filter Bar: Session & Class Selector -->
    <form method="GET" action="/admin/enrollments" class="grid grid-cols-1 sm:grid-cols-3 gap-4 bg-slate-50 p-4 rounded-xl border border-slate-200">
        <div>
            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Academic Session</label>
            <select name="session_id" onchange="this.form.submit()" class="w-full px-3.5 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-brand-500">
                <?php foreach ($sessions as $ses): ?>
                    <option value="<?= e($ses->id) ?>" <?= $ses->id === $selectedSessionId ? 'selected' : '' ?>>
                        <?= e($ses->name) ?> (<?= e(ucfirst($ses->status)) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Class / Arm</label>
            <select name="class_id" onchange="this.form.submit()" class="w-full px-3.5 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-brand-500">
                <?php foreach ($classes as $c): ?>
                    <option value="<?= e($c->id) ?>" <?= $c->id === $selectedClassId ? 'selected' : '' ?>>
                        <?= e($c->name) ?><?= $c->sectionArm ? ' — ' . e($c->sectionArm) : '' ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Status Filter</label>
            <select name="status" onchange="this.form.submit()" class="w-full px-3.5 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-brand-500">
                <option value="">All Statuses</option>
                <option value="active" <?= ($selectedStatus ?? '') === 'active' ? 'selected' : '' ?>>Active</option>
                <option value="promoted" <?= ($selectedStatus ?? '') === 'promoted' ? 'selected' : '' ?>>Promoted</option>
                <option value="repeating" <?= ($selectedStatus ?? '') === 'repeating' ? 'selected' : '' ?>>Repeating</option>
                <option value="transferred" <?= ($selectedStatus ?? '') === 'transferred' ? 'selected' : '' ?>>Transferred</option>
                <option value="withdrawn" <?= ($selectedStatus ?? '') === 'withdrawn' ? 'selected' : '' ?>>Withdrawn</option>
            </select>
        </div>
    </form>

    <!-- Roster Table -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
            <h3 class="font-bold text-slate-900">Enrolled Students (<?= count($roster) ?>)</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                <thead class="bg-slate-50 text-slate-600 font-semibold uppercase text-xs tracking-wider">
                    <tr>
                        <th class="px-6 py-3.5">Student</th>
                        <th class="px-6 py-3.5">Admission No.</th>
                        <th class="px-6 py-3.5">Enrolled Date</th>
                        <th class="px-6 py-3.5">Status</th>
                        <th class="px-6 py-3.5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium text-slate-700">
                    <?php if (empty($roster)): ?>
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-slate-400">
                                No students currently enrolled in this class for the selected session.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($roster as $enr): ?>
                            <tr class="hover:bg-slate-50 transition">
                                <td class="px-6 py-4">
                                    <div class="font-bold text-slate-900"><?= e($enr->student?->user?->name ?? '—') ?></div>
                                    <div class="text-xs text-slate-500"><?= e($enr->student?->user?->email ?? '—') ?></div>
                                </td>
                                <td class="px-6 py-4 font-mono text-xs font-semibold text-slate-800">
                                    <?= e($enr->student?->admissionNumber ?? '—') ?>
                                </td>
                                <td class="px-6 py-4 text-xs text-slate-500">
                                    <?= e($enr->enrolledAt ? date('M j, Y', strtotime($enr->enrolledAt)) : '—') ?>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold
                                        <?= $enr->status === 'active' ? 'bg-success-100 text-success-700' : ($enr->status === 'withdrawn' ? 'bg-danger-100 text-danger-700' : 'bg-slate-100 text-slate-700') ?>">
                                        <?= e(ucfirst($enr->status)) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <?php if ($canManage ?? false): ?>
                                        <form method="POST" action="/admin/enrollments/<?= e($enr->id) ?>/status" class="inline-flex items-center gap-1">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="session_id" value="<?= e($selectedSessionId) ?>">
                                            <input type="hidden" name="class_id" value="<?= e($selectedClassId) ?>">
                                            <select name="status" onchange="this.form.submit()" class="text-xs border border-slate-300 rounded px-2 py-1 bg-white focus:ring-1 focus:ring-brand-500">
                                                <option value="active" <?= $enr->status === 'active' ? 'selected' : '' ?>>Active</option>
                                                <option value="promoted" <?= $enr->status === 'promoted' ? 'selected' : '' ?>>Promoted</option>
                                                <option value="repeating" <?= $enr->status === 'repeating' ? 'selected' : '' ?>>Repeating</option>
                                                <option value="transferred" <?= $enr->status === 'transferred' ? 'selected' : '' ?>>Transferred</option>
                                                <option value="withdrawn" <?= $enr->status === 'withdrawn' ? 'selected' : '' ?>>Withdrawn</option>
                                            </select>
                                        </form>
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

<!-- Enroll Student Modal -->
<div id="enroll-modal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm flex items-center justify-center p-4 z-50 hidden">
    <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-xl space-y-5">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <h3 class="text-lg font-bold text-slate-900">Enroll Student</h3>
            <button type="button" onclick="document.getElementById('enroll-modal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form method="POST" action="/admin/enrollments" class="space-y-4">
            <?= csrf_field() ?>
            <input type="hidden" name="session_id" value="<?= e($selectedSessionId) ?>">
            <input type="hidden" name="class_id" value="<?= e($selectedClassId) ?>">

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Select Student *</label>
                <select name="student_id" required class="w-full px-3.5 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-brand-500">
                    <option value="">-- Choose Student --</option>
                    <?php foreach ($allStudents as $st): ?>
                        <option value="<?= e($st->id) ?>">
                            <?= e($st->user?->name ?? 'Student #' . $st->id) ?> (<?= e($st->admissionNumber) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Enrollment Status</label>
                <select name="status" class="w-full px-3.5 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-brand-500">
                    <option value="active">Active</option>
                    <option value="promoted">Promoted</option>
                    <option value="repeating">Repeating</option>
                </select>
            </div>

            <div class="flex items-center justify-end gap-3 pt-3">
                <button type="button" onclick="document.getElementById('enroll-modal').classList.add('hidden')" class="px-4 py-2 text-sm font-medium text-slate-600 hover:text-slate-900">
                    Cancel
                </button>
                <button type="submit" class="px-5 py-2 bg-brand-600 text-white font-semibold rounded-lg hover:bg-brand-700 shadow-sm transition">
                    Enroll Student
                </button>
            </div>
        </form>
    </div>
</div>
