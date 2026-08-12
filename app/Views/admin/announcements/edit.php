<div class="max-w-3xl mx-auto space-y-6">
    <div class="flex items-center gap-2">
        <a href="/admin/announcements" class="text-sm font-medium text-slate-500 hover:text-brand-600">&larr; Back to Announcements</a>
    </div>

    <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm space-y-6">
        <div>
            <h1 class="text-xl font-bold text-slate-900">Edit Announcement</h1>
            <p class="text-sm text-slate-600 mt-1">Update announcement content or modify schedule.</p>
        </div>

        <form method="POST" action="/admin/announcements/<?= (int)$announcement->id ?>/edit" class="space-y-5">
            <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

            <!-- Target Scope Selection -->
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1">Target Audience <span class="text-rose-500">*</span></label>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-slate-500 mb-1">Audience Scope</label>
                        <select name="scope" id="scopeSelect" onchange="toggleScopeInputs()" required class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-brand-500 outline-none">
                            <option value="school" <?= $announcement->scope === 'school' ? 'selected' : '' ?>>School-wide</option>
                            <option value="class" <?= $announcement->scope === 'class' ? 'selected' : '' ?>>Target Class</option>
                            <option value="class_subject" <?= $announcement->scope === 'class_subject' ? 'selected' : '' ?>>Target Subject Group</option>
                        </select>
                    </div>

                    <div id="classSelectWrap" class="<?= $announcement->scope === 'class' ? '' : 'hidden' ?>">
                        <label class="block text-xs font-medium text-slate-500 mb-1">Select Target Class</label>
                        <select id="classScopeId" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-brand-500 outline-none">
                            <?php foreach ($classes as $cls): ?>
                                <option value="<?= (int)$cls->id ?>" <?= ($announcement->scope === 'class' && $announcement->scopeId === (int)$cls->id) ? 'selected' : '' ?>><?= htmlspecialchars($cls->name) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div id="subjectSelectWrap" class="<?= $announcement->scope === 'class_subject' ? '' : 'hidden' ?>">
                        <label class="block text-xs font-medium text-slate-500 mb-1">Select Target Subject</label>
                        <select id="subjectScopeId" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-brand-500 outline-none">
                            <?php foreach ($classSubjects as $cs): ?>
                                <option value="<?= (int)$cs->id ?>" <?= ($announcement->scope === 'class_subject' && $announcement->scopeId === (int)$cs->id) ? 'selected' : '' ?>><?= htmlspecialchars($cs->className ?? 'Class') ?> &mdash; <?= htmlspecialchars($cs->subjectName ?? 'Subject') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Title -->
            <div>
                <label for="title" class="block text-sm font-semibold text-slate-700 mb-1">Announcement Title <span class="text-rose-500">*</span></label>
                <input type="text" name="title" id="title" required value="<?= htmlspecialchars($announcement->title) ?>"
                       class="w-full px-4 py-2.5 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-brand-500 outline-none">
            </div>

            <!-- Body -->
            <div>
                <label for="body" class="block text-sm font-semibold text-slate-700 mb-1">Announcement Body <span class="text-rose-500">*</span></label>
                <textarea name="body" id="body" rows="6" required
                          class="w-full px-4 py-2.5 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-brand-500 outline-none"><?= htmlspecialchars($announcement->body) ?></textarea>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <!-- Published Date -->
                <div>
                    <label for="published_at" class="block text-sm font-semibold text-slate-700 mb-1">Publication Date / Time</label>
                    <input type="datetime-local" name="published_at" id="published_at" 
                           value="<?= $announcement->publishedAt ? date('Y-m-d\TH:i', strtotime($announcement->publishedAt)) : '' ?>"
                           class="w-full px-4 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-brand-500 outline-none">
                </div>

                <!-- Expiry Date -->
                <div>
                    <label for="expires_at" class="block text-sm font-semibold text-slate-700 mb-1">Expiration Date (Optional)</label>
                    <input type="datetime-local" name="expires_at" id="expires_at"
                           value="<?= $announcement->expiresAt ? date('Y-m-d\TH:i', strtotime($announcement->expiresAt)) : '' ?>"
                           class="w-full px-4 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-brand-500 outline-none">
                </div>
            </div>

            <div class="pt-4 border-t border-slate-200 flex justify-end gap-3">
                <a href="/admin/announcements" class="px-5 py-2.5 text-slate-600 hover:text-slate-900 text-sm font-medium">Cancel</a>
                <button type="submit" class="px-6 py-2.5 bg-brand-600 text-white font-semibold rounded-lg hover:bg-brand-700 transition">
                    Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function toggleScopeInputs() {
    const scope = document.getElementById('scopeSelect').value;
    const classWrap = document.getElementById('classSelectWrap');
    const subjectWrap = document.getElementById('subjectSelectWrap');
    const classSelect = document.getElementById('classScopeId');
    const subjectSelect = document.getElementById('subjectScopeId');

    if (scope === 'school') {
        classWrap.classList.add('hidden');
        subjectWrap.classList.add('hidden');
        classSelect.name = '';
        subjectSelect.name = '';
    } else if (scope === 'class') {
        classWrap.classList.remove('hidden');
        subjectWrap.classList.add('hidden');
        classSelect.name = 'scope_id';
        subjectSelect.name = '';
    } else if (scope === 'class_subject') {
        classWrap.classList.add('hidden');
        subjectWrap.classList.remove('hidden');
        classSelect.name = '';
        subjectSelect.name = 'scope_id';
    }
}
toggleScopeInputs();
</script>
