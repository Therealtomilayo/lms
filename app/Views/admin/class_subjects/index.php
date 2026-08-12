<div class="space-y-6">
    <!-- Header with Actions -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-slate-900">Class Subjects & Teacher Mappings</h2>
            <p class="text-sm text-slate-500 mt-1">Manage session-scoped teaching allocations, subject deliveries, and teacher assignments.</p>
        </div>
        <button type="button" onclick="document.getElementById('create-modal').classList.remove('hidden')" class="inline-flex items-center gap-2 px-4 py-2.5 bg-brand-600 hover:bg-brand-700 text-white rounded-lg text-sm font-medium shadow-sm transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Assign Subject & Teacher
        </button>
    </div>

    <!-- Filter Bar -->
    <div class="bg-white border border-slate-200 rounded-xl p-4 shadow-sm">
        <form method="GET" action="/admin/class-subjects" class="flex flex-wrap items-center gap-4">
            <div class="flex-1 min-w-[200px]">
                <label for="filter_session" class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1">Academic Session</label>
                <select id="filter_session" name="session_id" onchange="this.form.submit()" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-brand-500 focus:border-brand-500">
                    <?php foreach ($sessions as $session): ?>
                        <option value="<?= $session->id ?>" <?= $session->id === $selectedSessionId ? 'selected' : '' ?>>
                            <?= e($session->name) ?> (<?= ucfirst($session->status) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="flex-1 min-w-[200px]">
                <label for="filter_class" class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1">Filter by Class</label>
                <select id="filter_class" name="class_id" onchange="this.form.submit()" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-brand-500 focus:border-brand-500">
                    <option value="">All Classes</option>
                    <?php foreach ($classes as $cls): ?>
                        <option value="<?= $cls->id ?>" <?= $cls->id === $selectedClassId ? 'selected' : '' ?>>
                            <?= e($cls->name) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="flex items-end pt-5">
                <a href="/admin/class-subjects" class="px-3 py-2 rounded-lg text-sm font-medium text-slate-600 hover:bg-slate-100 transition">
                    Reset Filter
                </a>
            </div>
        </form>
    </div>

    <!-- Class Subjects List -->
    <div class="bg-white border border-slate-200 rounded-xl overflow-hidden shadow-sm">
        <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
            <thead class="bg-slate-50 text-slate-600 font-semibold">
                <tr>
                    <th scope="col" class="px-6 py-3.5">Class / Cohort</th>
                    <th scope="col" class="px-6 py-3.5">Subject</th>
                    <th scope="col" class="px-6 py-3.5">Assigned Teacher</th>
                    <th scope="col" class="px-6 py-3.5">Status</th>
                    <th scope="col" class="px-6 py-3.5 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200">
                <?php if (empty($classSubjects)): ?>
                    <tr>
                        <td colspan="5" class="px-6 py-8 text-center text-slate-500">
                            No class-subject allocations found for this session. Click "Assign Subject & Teacher" to create assignments.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($classSubjects as $cs): ?>
                        <tr class="hover:bg-slate-50 transition">
                            <td class="px-6 py-4 font-semibold text-slate-900">
                                <?= e($cs->schoolClass?->name ?? 'Class #' . $cs->classId) ?>
                                <?php if ($cs->schoolClass?->sectionArm): ?>
                                    <span class="ml-1 font-mono text-xs font-semibold px-2 py-0.5 rounded bg-blue-50 text-brand-700">
                                        Arm <?= e($cs->schoolClass->sectionArm) ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 text-slate-800">
                                <div class="font-medium"><?= e($cs->subject?->name ?? 'Subject #' . $cs->subjectId) ?></div>
                                <div class="text-xs text-slate-500 font-mono"><?= e($cs->subject?->code ?? '') ?></div>
                            </td>
                            <td class="px-6 py-4 text-slate-700">
                                <div class="font-medium text-slate-900"><?= e($cs->teacher?->user?->name ?? 'Teacher #' . $cs->teacherId) ?></div>
                                <div class="text-xs text-slate-500">Staff ID: <span class="font-mono"><?= e($cs->teacher?->staffId ?? 'N/A') ?></span></div>
                            </td>
                            <td class="px-6 py-4">
                                <?php if ($cs->isActive()): ?>
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-success-100 text-success-700">
                                        Active
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-600">
                                        Inactive
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 text-right space-x-2">
                                <button type="button" 
                                        onclick="openReassignModal(<?= $cs->id ?>, <?= $cs->sessionId ?>, <?= $cs->teacherId ?>, '<?= e(addslashes($cs->subject?->name ?? '')) ?>', '<?= e(addslashes($cs->schoolClass?->name ?? '')) ?>')" 
                                        class="text-xs font-medium px-2.5 py-1.5 rounded bg-slate-100 text-slate-700 hover:bg-slate-200 transition">
                                    Reassign Teacher
                                </button>
                                <form method="POST" action="/admin/class-subjects/<?= $cs->id ?>/status" class="inline">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="session_id" value="<?= $selectedSessionId ?>">
                                    <input type="hidden" name="status" value="<?= $cs->isActive() ? 'inactive' : 'active' ?>">
                                    <button type="submit" class="text-xs font-medium px-2.5 py-1.5 rounded <?= $cs->isActive() ? 'bg-warning-100 text-warning-800 hover:bg-warning-200' : 'bg-success-100 text-success-700 hover:bg-success-200' ?> transition">
                                        <?= $cs->isActive() ? 'Deactivate' : 'Activate' ?>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Create Modal -->
<div id="create-modal" class="hidden fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-xl max-w-md w-full p-6 shadow-xl border border-slate-200">
        <div class="flex items-center justify-between pb-4 border-b border-slate-100">
            <h3 class="text-lg font-bold text-slate-900">Assign Subject & Teacher</h3>
            <button type="button" onclick="document.getElementById('create-modal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form method="POST" action="/admin/class-subjects" class="mt-4 space-y-4">
            <?= csrf_field() ?>
            <div>
                <label for="create_session" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Academic Session</label>
                <select id="create_session" name="session_id" required class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-brand-500 focus:border-brand-500">
                    <?php foreach ($sessions as $session): ?>
                        <option value="<?= $session->id ?>" <?= $session->id === $selectedSessionId ? 'selected' : '' ?>>
                            <?= e($session->name) ?> (<?= ucfirst($session->status) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="create_class" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Class / Arm</label>
                <select id="create_class" name="class_id" required class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-brand-500 focus:border-brand-500">
                    <option value="">Select Class...</option>
                    <?php foreach ($classes as $cls): ?>
                        <option value="<?= $cls->id ?>" <?= $cls->id === $selectedClassId ? 'selected' : '' ?>>
                            <?= e($cls->name) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="create_subject" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Subject</label>
                <select id="create_subject" name="subject_id" required class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-brand-500 focus:border-brand-500">
                    <option value="">Select Subject...</option>
                    <?php foreach ($subjects as $sub): ?>
                        <option value="<?= $sub->id ?>">
                            <?= e($sub->name) ?> (<?= e($sub->code) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="create_teacher" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Teacher</label>
                <select id="create_teacher" name="teacher_id" required class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-brand-500 focus:border-brand-500">
                    <option value="">Select Teacher...</option>
                    <?php foreach ($teachers as $t): ?>
                        <option value="<?= $t->id ?>">
                            <?= e($t->user?->name ?? 'Teacher #' . $t->id) ?> (<?= e($t->staffId) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="pt-4 flex justify-end gap-3 border-t border-slate-100">
                <button type="button" onclick="document.getElementById('create-modal').classList.add('hidden')" class="px-4 py-2 rounded-lg text-sm font-medium text-slate-600 hover:bg-slate-100 transition">Cancel</button>
                <button type="submit" class="px-4 py-2 rounded-lg text-sm font-medium bg-brand-600 hover:bg-brand-700 text-white shadow-sm transition">Assign</button>
            </div>
        </form>
    </div>
</div>

<!-- Reassign Modal -->
<div id="reassign-modal" class="hidden fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-xl max-w-md w-full p-6 shadow-xl border border-slate-200">
        <div class="flex items-center justify-between pb-4 border-b border-slate-100">
            <h3 class="text-lg font-bold text-slate-900">Reassign Teacher</h3>
            <button type="button" onclick="document.getElementById('reassign-modal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form id="reassign-form" method="POST" action="" class="mt-4 space-y-4">
            <?= csrf_field() ?>
            <input type="hidden" id="reassign_session_id" name="session_id" value="">
            <div>
                <p id="reassign_context" class="text-sm text-slate-600 bg-slate-50 p-3 rounded-lg border border-slate-200 font-medium"></p>
            </div>
            <div>
                <label for="reassign_teacher_id" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">New Teacher</label>
                <select id="reassign_teacher_id" name="teacher_id" required class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-brand-500 focus:border-brand-500">
                    <option value="">Select Teacher...</option>
                    <?php foreach ($teachers as $t): ?>
                        <option value="<?= $t->id ?>">
                            <?= e($t->user?->name ?? 'Teacher #' . $t->id) ?> (<?= e($t->staffId) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="pt-4 flex justify-end gap-3 border-t border-slate-100">
                <button type="button" onclick="document.getElementById('reassign-modal').classList.add('hidden')" class="px-4 py-2 rounded-lg text-sm font-medium text-slate-600 hover:bg-slate-100 transition">Cancel</button>
                <button type="submit" class="px-4 py-2 rounded-lg text-sm font-medium bg-brand-600 hover:bg-brand-700 text-white shadow-sm transition">Update Teacher</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openReassignModal(id, sessionId, currentTeacherId, subjectName, className) {
        document.getElementById('reassign-form').action = '/admin/class-subjects/' + id;
        document.getElementById('reassign_session_id').value = sessionId;
        document.getElementById('reassign_context').textContent = subjectName + ' — ' + className;
        document.getElementById('reassign_teacher_id').value = currentTeacherId;
        document.getElementById('reassign-modal').classList.remove('hidden');
    }
</script>
