<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
        <div>
            <h2 class="text-lg font-bold text-slate-900">Guardian Links</h2>
            <p class="text-sm text-slate-500">Manage parent and guardian relationships linked to enrolled students.</p>
        </div>
        <button type="button" onclick="document.getElementById('link-modal').classList.remove('hidden')" 
                class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold text-white bg-brand-600 rounded-lg hover:bg-brand-700 shadow-sm transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
            Link Guardian to Student
        </button>
    </div>

    <!-- Search Form -->
    <form method="GET" action="/admin/guardians" class="bg-slate-50 p-4 rounded-xl border border-slate-200 flex items-center gap-3">
        <input type="text" name="q" value="<?= e($search ?? '') ?>" placeholder="Search parent name or email..."
               class="flex-1 px-3.5 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-brand-500">
        <button type="submit" class="px-5 py-2 bg-slate-800 text-white rounded-lg text-sm font-medium hover:bg-slate-900 transition">
            Search
        </button>
    </form>

    <!-- Guardians Table -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                <thead class="bg-slate-50 text-slate-600 font-semibold uppercase text-xs tracking-wider">
                    <tr>
                        <th class="px-6 py-3.5">Guardian / Parent</th>
                        <th class="px-6 py-3.5">Contact</th>
                        <th class="px-6 py-3.5">Linked Children / Students</th>
                        <th class="px-6 py-3.5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium text-slate-700">
                    <?php if (empty($parents)): ?>
                        <tr>
                            <td colspan="4" class="px-6 py-12 text-center text-slate-400">
                                No parent accounts found.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($parents as $p): ?>
                            <tr class="hover:bg-slate-50 transition">
                                <td class="px-6 py-4">
                                    <div class="font-bold text-slate-900"><?= e($p->user?->name ?? '—') ?></div>
                                    <div class="text-xs text-slate-500"><?= e($p->user?->email ?? '—') ?></div>
                                </td>
                                <td class="px-6 py-4 text-xs text-slate-500">
                                    <?= e($p->user?->phone ?? '—') ?>
                                </td>
                                <td class="px-6 py-4">
                                    <?php if (empty($p->linkedStudents)): ?>
                                        <span class="text-xs text-slate-400 italic">No students linked yet</span>
                                    <?php else: ?>
                                        <div class="space-y-1.5">
                                            <?php foreach ($p->linkedStudents as $st): ?>
                                                <div class="flex items-center justify-between text-xs bg-slate-50 p-2 rounded border border-slate-200 max-w-sm">
                                                    <div>
                                                        <span class="font-semibold text-slate-900"><?= e($st->user?->name ?? '') ?></span>
                                                        <span class="text-slate-500 font-mono">(<?= e($st->admissionNumber) ?>)</span>
                                                        <?php if (!empty($st->relationshipType)): ?>
                                                            <span class="ml-1 text-xs px-1.5 py-0.5 bg-brand-100 text-brand-700 rounded font-semibold">
                                                                <?= e($st->relationshipType) ?>
                                                            </span>
                                                        <?php endif; ?>
                                                    </div>
                                                    <form method="POST" action="/admin/guardians/unlink" class="inline" onsubmit="return confirm('Unlink this child?')">
                                                        <?= csrf_field() ?>
                                                        <input type="hidden" name="parent_id" value="<?= e($p->id) ?>">
                                                        <input type="hidden" name="student_id" value="<?= e($st->id) ?>">
                                                        <button type="submit" class="text-danger-700 hover:text-danger-900 font-bold px-1" title="Unlink Student">
                                                            &times;
                                                        </button>
                                                    </form>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <button type="button" 
                                            onclick="openLinkModal(<?= e($p->id) ?>, '<?= e(addslashes($p->user?->name ?? '')) ?>')"
                                            class="text-xs font-semibold text-brand-600 hover:text-brand-800 transition">
                                        + Link Child
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if (($totalPages ?? 1) > 1): ?>
            <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex items-center justify-between">
                <span class="text-xs text-slate-500">Showing page <?= e($currentPage) ?> of <?= e($totalPages) ?></span>
                <div class="flex items-center gap-1">
                    <?php for ($pg = 1; $pg <= $totalPages; $pg++): ?>
                        <a href="/admin/guardians?page=<?= $pg ?><?= !empty($search) ? '&q=' . urlencode($search) : '' ?>"
                           class="px-3 py-1 rounded text-xs font-medium <?= $pg === $currentPage ? 'bg-brand-600 text-white' : 'bg-white border border-slate-300 text-slate-700 hover:bg-slate-50' ?>">
                            <?= $pg ?>
                        </a>
                    <?php endfor; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Link Guardian Modal -->
<div id="link-modal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm flex items-center justify-center p-4 z-50 hidden">
    <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-xl space-y-5">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <h3 class="text-lg font-bold text-slate-900">Link Guardian to Student</h3>
            <button type="button" onclick="document.getElementById('link-modal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form method="POST" action="/admin/guardians/link" class="space-y-4">
            <?= csrf_field() ?>

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Parent / Guardian *</label>
                <select name="parent_id" id="modal-parent-id" required class="w-full px-3.5 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-brand-500">
                    <option value="">-- Choose Guardian --</option>
                    <?php foreach ($parents as $p): ?>
                        <option value="<?= e($p->id) ?>"><?= e($p->user?->name ?? 'Parent #' . $p->id) ?> (<?= e($p->user?->email ?? '') ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Student / Child *</label>
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
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Relationship Type</label>
                <select name="relationship_type" class="w-full px-3.5 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-brand-500">
                    <option value="Father">Father</option>
                    <option value="Mother">Mother</option>
                    <option value="Guardian">Guardian</option>
                    <option value="Sponsor">Sponsor</option>
                </select>
            </div>

            <div class="flex items-center justify-end gap-3 pt-3">
                <button type="button" onclick="document.getElementById('link-modal').classList.add('hidden')" class="px-4 py-2 text-sm font-medium text-slate-600 hover:text-slate-900">
                    Cancel
                </button>
                <button type="submit" class="px-5 py-2 bg-brand-600 text-white font-semibold rounded-lg hover:bg-brand-700 shadow-sm transition">
                    Establish Link
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openLinkModal(parentId, parentName) {
    var select = document.getElementById('modal-parent-id');
    if (select) {
        select.value = parentId;
    }
    document.getElementById('link-modal').classList.remove('hidden');
}
</script>
