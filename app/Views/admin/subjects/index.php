<div class="space-y-6">
    <!-- Header with Action -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-slate-900">Subjects</h2>
            <p class="text-sm text-slate-500 mt-1">Manage global curriculum subjects, subject codes, and catalog availability.</p>
        </div>
        <button type="button" onclick="document.getElementById('create-modal').classList.remove('hidden')" class="inline-flex items-center gap-2 px-4 py-2.5 bg-brand-600 hover:bg-brand-700 text-white rounded-lg text-sm font-medium shadow-sm transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Create Subject
        </button>
    </div>

    <!-- Subjects List -->
    <div class="bg-white border border-slate-200 rounded-xl overflow-hidden shadow-sm">
        <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
            <thead class="bg-slate-50 text-slate-600 font-semibold">
                <tr>
                    <th scope="col" class="px-6 py-3.5">Code</th>
                    <th scope="col" class="px-6 py-3.5">Subject Name</th>
                    <th scope="col" class="px-6 py-3.5">Status</th>
                    <th scope="col" class="px-6 py-3.5 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200">
                <?php if (empty($subjects)): ?>
                    <tr>
                        <td colspan="4" class="px-6 py-8 text-center text-slate-500">
                            No subjects registered yet. Click "Create Subject" to begin.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($subjects as $sub): ?>
                        <tr class="hover:bg-slate-50 transition">
                            <td class="px-6 py-4 font-mono font-bold text-xs text-brand-700">
                                <?= e($sub->code) ?>
                            </td>
                            <td class="px-6 py-4 font-semibold text-slate-900">
                                <?= e($sub->name) ?>
                            </td>
                            <td class="px-6 py-4">
                                <?php if ($sub->isActive()): ?>
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
                                        onclick="openEditModal(<?= $sub->id ?>, '<?= e(addslashes($sub->name)) ?>', '<?= e(addslashes($sub->code)) ?>')" 
                                        class="text-xs font-medium px-2.5 py-1.5 rounded bg-slate-100 text-slate-700 hover:bg-slate-200 transition">
                                    Edit
                                </button>
                                <form method="POST" action="/admin/subjects/<?= $sub->id ?>/status" class="inline">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="status" value="<?= $sub->isActive() ? 'inactive' : 'active' ?>">
                                    <button type="submit" class="text-xs font-medium px-2.5 py-1.5 rounded <?= $sub->isActive() ? 'bg-warning-100 text-warning-800 hover:bg-warning-200' : 'bg-success-100 text-success-700 hover:bg-success-200' ?> transition">
                                        <?= $sub->isActive() ? 'Deactivate' : 'Activate' ?>
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
            <h3 class="text-lg font-bold text-slate-900">Create Subject</h3>
            <button type="button" onclick="document.getElementById('create-modal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form method="POST" action="/admin/subjects" class="mt-4 space-y-4">
            <?= csrf_field() ?>
            <div>
                <label for="create_sub_name" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Subject Name</label>
                <input type="text" id="create_sub_name" name="name" required placeholder="e.g. Mathematics, English Language" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-brand-500 focus:border-brand-500">
            </div>
            <div>
                <label for="create_sub_code" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Subject Code</label>
                <input type="text" id="create_sub_code" name="code" required placeholder="e.g. MTH101, ENG" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm uppercase focus:ring-2 focus:ring-brand-500 focus:border-brand-500">
            </div>
            <div class="pt-4 flex justify-end gap-3 border-t border-slate-100">
                <button type="button" onclick="document.getElementById('create-modal').classList.add('hidden')" class="px-4 py-2 rounded-lg text-sm font-medium text-slate-600 hover:bg-slate-100 transition">Cancel</button>
                <button type="submit" class="px-4 py-2 rounded-lg text-sm font-medium bg-brand-600 hover:bg-brand-700 text-white shadow-sm transition">Create Subject</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Modal -->
<div id="edit-modal" class="hidden fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-xl max-w-md w-full p-6 shadow-xl border border-slate-200">
        <div class="flex items-center justify-between pb-4 border-b border-slate-100">
            <h3 class="text-lg font-bold text-slate-900">Edit Subject</h3>
            <button type="button" onclick="document.getElementById('edit-modal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form id="edit-form" method="POST" action="" class="mt-4 space-y-4">
            <?= csrf_field() ?>
            <div>
                <label for="edit_sub_name" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Subject Name</label>
                <input type="text" id="edit_sub_name" name="name" required class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-brand-500 focus:border-brand-500">
            </div>
            <div>
                <label for="edit_sub_code" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Subject Code</label>
                <input type="text" id="edit_sub_code" name="code" required class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm uppercase focus:ring-2 focus:ring-brand-500 focus:border-brand-500">
            </div>
            <div class="pt-4 flex justify-end gap-3 border-t border-slate-100">
                <button type="button" onclick="document.getElementById('edit-modal').classList.add('hidden')" class="px-4 py-2 rounded-lg text-sm font-medium text-slate-600 hover:bg-slate-100 transition">Cancel</button>
                <button type="submit" class="px-4 py-2 rounded-lg text-sm font-medium bg-brand-600 hover:bg-brand-700 text-white shadow-sm transition">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openEditModal(id, name, code) {
        document.getElementById('edit-form').action = '/admin/subjects/' + id;
        document.getElementById('edit_sub_name').value = name;
        document.getElementById('edit_sub_code').value = code;
        document.getElementById('edit-modal').classList.remove('hidden');
    }
</script>
