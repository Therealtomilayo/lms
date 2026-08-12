<div class="space-y-6">
    <!-- Header with Action -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-slate-900">Academic Levels</h2>
            <p class="text-sm text-slate-500 mt-1">Configure institutional stages (e.g. Primary, Junior Secondary) and grading scales.</p>
        </div>
        <button type="button" onclick="document.getElementById('create-modal').classList.remove('hidden')" class="inline-flex items-center gap-2 px-4 py-2.5 bg-brand-600 hover:bg-brand-700 text-white rounded-lg text-sm font-medium shadow-sm transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Create Level
        </button>
    </div>

    <!-- Levels List -->
    <div class="bg-white border border-slate-200 rounded-xl overflow-hidden shadow-sm">
        <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
            <thead class="bg-slate-50 text-slate-600 font-semibold">
                <tr>
                    <th scope="col" class="px-6 py-3.5">Rank Order</th>
                    <th scope="col" class="px-6 py-3.5">Level Name</th>
                    <th scope="col" class="px-6 py-3.5">Stage</th>
                    <th scope="col" class="px-6 py-3.5">Grading Scale</th>
                    <th scope="col" class="px-6 py-3.5 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200">
                <?php if (empty($levels)): ?>
                    <tr>
                        <td colspan="5" class="px-6 py-8 text-center text-slate-500">
                            No academic levels configured yet.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($levels as $lvl): ?>
                        <tr class="hover:bg-slate-50 transition">
                            <td class="px-6 py-4 font-mono text-xs text-slate-500">
                                <?= e((string)$lvl->rankOrder) ?>
                            </td>
                            <td class="px-6 py-4 font-semibold text-slate-900">
                                <?= e($lvl->name) ?>
                            </td>
                            <td class="px-6 py-4 text-slate-600">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-medium bg-slate-100 text-slate-800">
                                    <?= e($lvl->stage) ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-slate-600">
                                <?= $lvl->gradingScaleId ? 'Scale #' . e((string)$lvl->gradingScaleId) : '<span class="text-slate-400 text-xs italic">Default Scale</span>' ?>
                            </td>
                            <td class="px-6 py-4 text-right space-x-2">
                                <button type="button" 
                                        onclick="openEditModal(<?= $lvl->id ?>, '<?= e(addslashes($lvl->name)) ?>', '<?= e(addslashes($lvl->stage)) ?>', <?= $lvl->rankOrder ?>, '<?= $lvl->gradingScaleId ?? '' ?>')" 
                                        class="text-xs font-medium px-2.5 py-1.5 rounded bg-slate-100 text-slate-700 hover:bg-slate-200 transition">
                                    Edit
                                </button>
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
            <h3 class="text-lg font-bold text-slate-900">Create Academic Level</h3>
            <button type="button" onclick="document.getElementById('create-modal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form method="POST" action="/admin/academic-levels" class="mt-4 space-y-4">
            <?= csrf_field() ?>
            <div>
                <label for="create_level_name" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Level Name</label>
                <input type="text" id="create_level_name" name="name" required placeholder="e.g. JSS 1, Grade 7" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-brand-500 focus:border-brand-500">
            </div>
            <div>
                <label for="create_level_stage" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Stage</label>
                <input type="text" id="create_level_stage" name="stage" required placeholder="e.g. Junior Secondary, Primary" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-brand-500 focus:border-brand-500">
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="create_rank_order" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Rank Order</label>
                    <input type="number" id="create_rank_order" name="rank_order" value="1" required class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-brand-500 focus:border-brand-500">
                </div>
                <div>
                    <label for="create_scale" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Grading Scale</label>
                    <select id="create_scale" name="grading_scale_id" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-brand-500 focus:border-brand-500">
                        <option value="">Default Scale</option>
                        <?php foreach ($gradingScales as $scale): ?>
                            <option value="<?= $scale->id ?>"><?= e($scale->name) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="pt-4 flex justify-end gap-3 border-t border-slate-100">
                <button type="button" onclick="document.getElementById('create-modal').classList.add('hidden')" class="px-4 py-2 rounded-lg text-sm font-medium text-slate-600 hover:bg-slate-100 transition">Cancel</button>
                <button type="submit" class="px-4 py-2 rounded-lg text-sm font-medium bg-brand-600 hover:bg-brand-700 text-white shadow-sm transition">Create Level</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Modal -->
<div id="edit-modal" class="hidden fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-xl max-w-md w-full p-6 shadow-xl border border-slate-200">
        <div class="flex items-center justify-between pb-4 border-b border-slate-100">
            <h3 class="text-lg font-bold text-slate-900">Edit Academic Level</h3>
            <button type="button" onclick="document.getElementById('edit-modal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form id="edit-form" method="POST" action="" class="mt-4 space-y-4">
            <?= csrf_field() ?>
            <div>
                <label for="edit_level_name" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Level Name</label>
                <input type="text" id="edit_level_name" name="name" required class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-brand-500 focus:border-brand-500">
            </div>
            <div>
                <label for="edit_level_stage" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Stage</label>
                <input type="text" id="edit_level_stage" name="stage" required class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-brand-500 focus:border-brand-500">
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="edit_rank_order" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Rank Order</label>
                    <input type="number" id="edit_rank_order" name="rank_order" required class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-brand-500 focus:border-brand-500">
                </div>
                <div>
                    <label for="edit_scale" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Grading Scale</label>
                    <select id="edit_scale" name="grading_scale_id" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-brand-500 focus:border-brand-500">
                        <option value="">Default Scale</option>
                        <?php foreach ($gradingScales as $scale): ?>
                            <option value="<?= $scale->id ?>"><?= e($scale->name) ?></option>
                        <?php endforeach; ?>
                    </select>
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
    function openEditModal(id, name, stage, rankOrder, scaleId) {
        document.getElementById('edit-form').action = '/admin/academic-levels/' + id;
        document.getElementById('edit_level_name').value = name;
        document.getElementById('edit_level_stage').value = stage;
        document.getElementById('edit_rank_order').value = rankOrder;
        document.getElementById('edit_scale').value = scaleId || '';
        document.getElementById('edit-modal').classList.remove('hidden');
    }
</script>
