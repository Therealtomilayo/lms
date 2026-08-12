<div class="space-y-6">
    <!-- Header with Action -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-slate-900">Academic Sessions</h2>
            <p class="text-sm text-slate-500 mt-1">Manage school years, calendars, and overarching lifecycle states.</p>
        </div>
        <button type="button" onclick="document.getElementById('create-modal').classList.remove('hidden')" class="inline-flex items-center gap-2 px-4 py-2.5 bg-brand-600 hover:bg-brand-700 text-white rounded-lg text-sm font-medium shadow-sm transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Create Session
        </button>
    </div>

    <!-- Sessions List -->
    <div class="bg-white border border-slate-200 rounded-xl overflow-hidden shadow-sm">
        <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
            <thead class="bg-slate-50 text-slate-600 font-semibold">
                <tr>
                    <th scope="col" class="px-6 py-3.5">Session Name</th>
                    <th scope="col" class="px-6 py-3.5">Start Date</th>
                    <th scope="col" class="px-6 py-3.5">End Date</th>
                    <th scope="col" class="px-6 py-3.5">Status</th>
                    <th scope="col" class="px-6 py-3.5 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200">
                <?php if (empty($sessions)): ?>
                    <tr>
                        <td colspan="5" class="px-6 py-8 text-center text-slate-500">
                            No academic sessions created yet. Click "Create Session" to get started.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($sessions as $session): ?>
                        <tr class="hover:bg-slate-50 transition">
                            <td class="px-6 py-4 font-semibold text-slate-900">
                                <?= e($session->name) ?>
                            </td>
                            <td class="px-6 py-4 text-slate-600">
                                <?= e($session->startDate) ?>
                            </td>
                            <td class="px-6 py-4 text-slate-600">
                                <?= e($session->endDate) ?>
                            </td>
                            <td class="px-6 py-4">
                                <?php if ($session->isActive()): ?>
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-success-100 text-success-700">
                                        Active / Current
                                    </span>
                                <?php elseif ($session->isPlanning()): ?>
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-warning-100 text-warning-800">
                                        Planning
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-600">
                                        Archived
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 text-right space-x-2">
                                <?php if ($session->isPlanning()): ?>
                                    <form method="POST" action="/admin/sessions/<?= $session->id ?>/make-current" class="inline">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="text-xs font-medium px-2.5 py-1.5 rounded bg-brand-50 text-brand-600 hover:bg-brand-100 transition">
                                            Make Active
                                        </button>
                                    </form>
                                <?php endif; ?>

                                <?php if (!$session->isArchived()): ?>
                                    <button type="button" 
                                            onclick="openEditModal(<?= $session->id ?>, '<?= e(addslashes($session->name)) ?>', '<?= e($session->startDate) ?>', '<?= e($session->endDate) ?>')" 
                                            class="text-xs font-medium px-2.5 py-1.5 rounded bg-slate-100 text-slate-700 hover:bg-slate-200 transition">
                                        Edit
                                    </button>

                                    <form method="POST" action="/admin/sessions/<?= $session->id ?>/archive" class="inline" onsubmit="return confirm('Archiving a session is permanent. Proceed?');">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="text-xs font-medium px-2.5 py-1.5 rounded bg-danger-100 text-danger-700 hover:bg-danger-200 transition">
                                            Archive
                                        </button>
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

<!-- Create Modal -->
<div id="create-modal" class="hidden fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-xl max-w-md w-full p-6 shadow-xl border border-slate-200">
        <div class="flex items-center justify-between pb-4 border-b border-slate-100">
            <h3 class="text-lg font-bold text-slate-900">Create Academic Session</h3>
            <button type="button" onclick="document.getElementById('create-modal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form method="POST" action="/admin/sessions" class="mt-4 space-y-4">
            <?= csrf_field() ?>
            <div>
                <label for="create_name" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Session Name</label>
                <input type="text" id="create_name" name="name" required placeholder="e.g. 2026/2027" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-brand-500 focus:border-brand-500">
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="create_start_date" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Start Date</label>
                    <input type="date" id="create_start_date" name="start_date" required class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-brand-500 focus:border-brand-500">
                </div>
                <div>
                    <label for="create_end_date" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">End Date</label>
                    <input type="date" id="create_end_date" name="end_date" required class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-brand-500 focus:border-brand-500">
                </div>
            </div>
            <div class="pt-4 flex justify-end gap-3 border-t border-slate-100">
                <button type="button" onclick="document.getElementById('create-modal').classList.add('hidden')" class="px-4 py-2 rounded-lg text-sm font-medium text-slate-600 hover:bg-slate-100 transition">Cancel</button>
                <button type="submit" class="px-4 py-2 rounded-lg text-sm font-medium bg-brand-600 hover:bg-brand-700 text-white shadow-sm transition">Create Session</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Modal -->
<div id="edit-modal" class="hidden fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-xl max-w-md w-full p-6 shadow-xl border border-slate-200">
        <div class="flex items-center justify-between pb-4 border-b border-slate-100">
            <h3 class="text-lg font-bold text-slate-900">Edit Academic Session</h3>
            <button type="button" onclick="document.getElementById('edit-modal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form id="edit-form" method="POST" action="" class="mt-4 space-y-4">
            <?= csrf_field() ?>
            <div>
                <label for="edit_name" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Session Name</label>
                <input type="text" id="edit_name" name="name" required class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-brand-500 focus:border-brand-500">
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="edit_start_date" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Start Date</label>
                    <input type="date" id="edit_start_date" name="start_date" required class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-brand-500 focus:border-brand-500">
                </div>
                <div>
                    <label for="edit_end_date" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">End Date</label>
                    <input type="date" id="edit_end_date" name="end_date" required class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-brand-500 focus:border-brand-500">
                </div>
            </div>
            <div class="pt-4 flex justify-end gap-3 border-t border-slate-100">
                <button type="button" onclick="document.getElementById('edit-modal').classList.add('hidden')" class="px-4 py-2 rounded-lg text-sm font-medium text-slate-600 hover:bg-slate-100 transition">Cancel</button>
                <button type="submit" class="px-4 py-2 rounded-lg text-sm font-medium bg-brand-600 hover:bg-brand-700 text-white shadow-sm transition">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openEditModal(id, name, startDate, endDate) {
        document.getElementById('edit-form').action = '/admin/sessions/' + id;
        document.getElementById('edit_name').value = name;
        document.getElementById('edit_start_date').value = startDate;
        document.getElementById('edit_end_date').value = endDate;
        document.getElementById('edit-modal').classList.remove('hidden');
    }
</script>
