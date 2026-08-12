<div class="space-y-6">
    <!-- Header with Action -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-slate-900">Academic Terms</h2>
            <p class="text-sm text-slate-500 mt-1">Configure terms, date spans, grading windows, and operational states.</p>
        </div>
        <?php if ($selectedSessionId > 0): ?>
            <button type="button" onclick="document.getElementById('create-modal').classList.remove('hidden')" class="inline-flex items-center gap-2 px-4 py-2.5 bg-brand-600 hover:bg-brand-700 text-white rounded-lg text-sm font-medium shadow-sm transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Create Term
            </button>
        <?php endif; ?>
    </div>

    <!-- Session Filter Bar -->
    <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex items-center gap-4">
        <label for="session-select" class="text-sm font-semibold text-slate-700">Filter by Session:</label>
        <select id="session-select" onchange="window.location.href='/admin/terms?session_id=' + this.value" class="px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-brand-500 focus:border-brand-500">
            <?php foreach ($sessions as $session): ?>
                <option value="<?= $session->id ?>" <?= $session->id === $selectedSessionId ? 'selected' : '' ?>>
                    <?= e($session->name) ?> <?= $session->isActive() ? '(Active)' : '' ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <!-- Terms List -->
    <div class="bg-white border border-slate-200 rounded-xl overflow-hidden shadow-sm">
        <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
            <thead class="bg-slate-50 text-slate-600 font-semibold">
                <tr>
                    <th scope="col" class="px-6 py-3.5">Term Name</th>
                    <th scope="col" class="px-6 py-3.5">Duration</th>
                    <th scope="col" class="px-6 py-3.5">Grading Window</th>
                    <th scope="col" class="px-6 py-3.5">Status</th>
                    <th scope="col" class="px-6 py-3.5 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200">
                <?php if (empty($terms)): ?>
                    <tr>
                        <td colspan="5" class="px-6 py-8 text-center text-slate-500">
                            No terms configured for this academic session.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($terms as $term): ?>
                        <tr class="hover:bg-slate-50 transition">
                            <td class="px-6 py-4 font-semibold text-slate-900">
                                <?= e($term->name) ?>
                            </td>
                            <td class="px-6 py-4 text-slate-600">
                                <?= e($term->startDate) ?> &rarr; <?= e($term->endDate) ?>
                            </td>
                            <td class="px-6 py-4 text-slate-600">
                                <?php if ($term->gradingStartsAt && $term->gradingEndsAt): ?>
                                    <span class="text-xs"><?= e($term->gradingStartsAt) ?> &rarr; <?= e($term->gradingEndsAt) ?></span>
                                <?php else: ?>
                                    <span class="text-xs text-slate-400">Unspecified</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4">
                                <?php if ($term->isActive()): ?>
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-success-100 text-success-700">
                                        Active
                                    </span>
                                <?php elseif ($term->isGradingOpen()): ?>
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-info-100 text-info-700">
                                        Grading Open
                                    </span>
                                <?php elseif ($term->isGradingLocked()): ?>
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-warning-100 text-warning-800">
                                        Grading Locked
                                    </span>
                                <?php elseif ($term->isPlanning()): ?>
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-700">
                                        Planning
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-400">
                                        Archived
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 text-right space-x-2">
                                <?php if ($term->isPlanning()): ?>
                                    <form method="POST" action="/admin/terms/<?= $term->id ?>/make-current" class="inline">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="text-xs font-medium px-2.5 py-1.5 rounded bg-brand-50 text-brand-600 hover:bg-brand-100 transition">
                                            Activate
                                        </button>
                                    </form>
                                <?php elseif ($term->isActive()): ?>
                                    <form method="POST" action="/admin/terms/<?= $term->id ?>/status" class="inline">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="status" value="grading_open">
                                        <button type="submit" class="text-xs font-medium px-2.5 py-1.5 rounded bg-info-100 text-info-700 hover:bg-info-200 transition">
                                            Open Grading
                                        </button>
                                    </form>
                                <?php elseif ($term->isGradingOpen()): ?>
                                    <form method="POST" action="/admin/terms/<?= $term->id ?>/status" class="inline">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="status" value="grading_locked">
                                        <button type="submit" class="text-xs font-medium px-2.5 py-1.5 rounded bg-warning-100 text-warning-800 hover:bg-warning-200 transition">
                                            Lock Grading
                                        </button>
                                    </form>
                                <?php elseif ($term->isGradingLocked()): ?>
                                    <form method="POST" action="/admin/terms/<?= $term->id ?>/status" class="inline">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="status" value="grading_open">
                                        <button type="submit" class="text-xs font-medium px-2.5 py-1.5 rounded bg-info-100 text-info-700 hover:bg-info-200 transition">
                                            Re-open Grading
                                        </button>
                                    </form>
                                    <form method="POST" action="/admin/terms/<?= $term->id ?>/status" class="inline" onsubmit="return confirm('Archive this term? Result records will be permanently locked.');">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="status" value="archived">
                                        <button type="submit" class="text-xs font-medium px-2.5 py-1.5 rounded bg-slate-200 text-slate-700 hover:bg-slate-300 transition">
                                            Archive
                                        </button>
                                    </form>
                                <?php endif; ?>

                                <?php if (!$term->isArchived()): ?>
                                    <button type="button" 
                                            onclick="openEditModal(<?= $term->id ?>, '<?= e(addslashes($term->name)) ?>', '<?= e($term->startDate) ?>', '<?= e($term->endDate) ?>', '<?= e($term->gradingStartsAt ?? '') ?>', '<?= e($term->gradingEndsAt ?? '') ?>')" 
                                            class="text-xs font-medium px-2.5 py-1.5 rounded bg-slate-100 text-slate-700 hover:bg-slate-200 transition">
                                        Edit
                                    </button>
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
            <h3 class="text-lg font-bold text-slate-900">Create Academic Term</h3>
            <button type="button" onclick="document.getElementById('create-modal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form method="POST" action="/admin/terms" class="mt-4 space-y-4">
            <?= csrf_field() ?>
            <input type="hidden" name="session_id" value="<?= $selectedSessionId ?>">
            <div>
                <label for="create_term_name" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Term Name</label>
                <input type="text" id="create_term_name" name="name" required placeholder="e.g. First Term" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-brand-500 focus:border-brand-500">
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="create_term_start" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Start Date</label>
                    <input type="date" id="create_term_start" name="start_date" required class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-brand-500 focus:border-brand-500">
                </div>
                <div>
                    <label for="create_term_end" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">End Date</label>
                    <input type="date" id="create_term_end" name="end_date" required class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-brand-500 focus:border-brand-500">
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="create_grading_start" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Grading Opens</label>
                    <input type="datetime-local" id="create_grading_start" name="grading_starts_at" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-brand-500 focus:border-brand-500">
                </div>
                <div>
                    <label for="create_grading_end" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Grading Closes</label>
                    <input type="datetime-local" id="create_grading_end" name="grading_ends_at" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-brand-500 focus:border-brand-500">
                </div>
            </div>
            <div class="pt-4 flex justify-end gap-3 border-t border-slate-100">
                <button type="button" onclick="document.getElementById('create-modal').classList.add('hidden')" class="px-4 py-2 rounded-lg text-sm font-medium text-slate-600 hover:bg-slate-100 transition">Cancel</button>
                <button type="submit" class="px-4 py-2 rounded-lg text-sm font-medium bg-brand-600 hover:bg-brand-700 text-white shadow-sm transition">Create Term</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Modal -->
<div id="edit-modal" class="hidden fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-xl max-w-md w-full p-6 shadow-xl border border-slate-200">
        <div class="flex items-center justify-between pb-4 border-b border-slate-100">
            <h3 class="text-lg font-bold text-slate-900">Edit Academic Term</h3>
            <button type="button" onclick="document.getElementById('edit-modal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form id="edit-form" method="POST" action="" class="mt-4 space-y-4">
            <?= csrf_field() ?>
            <input type="hidden" name="session_id" value="<?= $selectedSessionId ?>">
            <div>
                <label for="edit_term_name" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Term Name</label>
                <input type="text" id="edit_term_name" name="name" required class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-brand-500 focus:border-brand-500">
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="edit_term_start" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Start Date</label>
                    <input type="date" id="edit_term_start" name="start_date" required class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-brand-500 focus:border-brand-500">
                </div>
                <div>
                    <label for="edit_term_end" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">End Date</label>
                    <input type="date" id="edit_term_end" name="end_date" required class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-brand-500 focus:border-brand-500">
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="edit_grading_start" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Grading Opens</label>
                    <input type="datetime-local" id="edit_grading_start" name="grading_starts_at" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-brand-500 focus:border-brand-500">
                </div>
                <div>
                    <label for="edit_grading_end" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Grading Closes</label>
                    <input type="datetime-local" id="edit_grading_end" name="grading_ends_at" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-brand-500 focus:border-brand-500">
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
    function openEditModal(id, name, startDate, endDate, gradingStart, gradingEnd) {
        document.getElementById('edit-form').action = '/admin/terms/' + id;
        document.getElementById('edit_term_name').value = name;
        document.getElementById('edit_term_start').value = startDate;
        document.getElementById('edit_term_end').value = endDate;
        document.getElementById('edit_grading_start').value = gradingStart ? gradingStart.replace(' ', 'T').slice(0, 16) : '';
        document.getElementById('edit_grading_end').value = gradingEnd ? gradingEnd.replace(' ', 'T').slice(0, 16) : '';
        document.getElementById('edit-modal').classList.remove('hidden');
    }
</script>
