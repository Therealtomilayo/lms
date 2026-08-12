<div class="max-w-3xl mx-auto space-y-6">
    <div class="flex items-center gap-2">
        <a href="/teacher/announcements" class="text-sm font-medium text-slate-500 hover:text-brand-600">&larr; Back to Announcements</a>
    </div>

    <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm space-y-6">
        <div>
            <h1 class="text-xl font-bold text-slate-900">Post Announcement</h1>
            <p class="text-sm text-slate-600 mt-1">Broadcast an announcement to your class or specific subject group.</p>
        </div>

        <form method="POST" action="/teacher/announcements" class="space-y-5">
            <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

            <!-- Target Scope Selection -->
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1">Target Audience <span class="text-rose-500">*</span></label>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-slate-500 mb-1">Target Scope</label>
                        <select name="scope" id="scopeSelect" onchange="toggleScopeInputs()" required class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-brand-500 outline-none">
                            <option value="class">Entire Class</option>
                            <option value="class_subject">Subject Specific</option>
                        </select>
                    </div>

                    <div id="classSelectWrap">
                        <label class="block text-xs font-medium text-slate-500 mb-1">Select Class</label>
                        <select name="scope_id" id="classScopeId" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-brand-500 outline-none">
                            <?php foreach ($classes as $cls): ?>
                                <option value="<?= (int)$cls['id'] ?>"><?= htmlspecialchars($cls['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div id="subjectSelectWrap" class="hidden">
                        <label class="block text-xs font-medium text-slate-500 mb-1">Select Subject</label>
                        <select id="subjectScopeId" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-brand-500 outline-none">
                            <?php foreach ($allocations as $alloc): ?>
                                <option value="<?= (int)$alloc['class_subject_id'] ?>"><?= htmlspecialchars($alloc['class_name']) ?> &mdash; <?= htmlspecialchars($alloc['subject_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Title -->
            <div>
                <label for="title" class="block text-sm font-semibold text-slate-700 mb-1">Announcement Title <span class="text-rose-500">*</span></label>
                <input type="text" name="title" id="title" required placeholder="e.g., Midterm Project Submission Deadline"
                       class="w-full px-4 py-2.5 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-brand-500 outline-none">
            </div>

            <!-- Body -->
            <div>
                <label for="body" class="block text-sm font-semibold text-slate-700 mb-1">Announcement Content <span class="text-rose-500">*</span></label>
                <textarea name="body" id="body" rows="6" required placeholder="Enter the full message details..."
                          class="w-full px-4 py-2.5 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-brand-500 outline-none"></textarea>
            </div>

            <!-- Expiry -->
            <div>
                <label for="expires_at" class="block text-sm font-semibold text-slate-700 mb-1">Expiration Date (Optional)</label>
                <input type="date" name="expires_at" id="expires_at"
                       class="px-4 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-brand-500 outline-none">
                <p class="text-xs text-slate-500 mt-1">If left blank, the announcement will remain active indefinitely.</p>
            </div>

            <div class="pt-4 border-t border-slate-200 flex justify-end gap-3">
                <a href="/teacher/announcements" class="px-5 py-2.5 text-slate-600 hover:text-slate-900 text-sm font-medium">Cancel</a>
                <button type="submit" class="px-6 py-2.5 bg-brand-600 text-white font-semibold rounded-lg hover:bg-brand-700 transition">
                    Publish Announcement
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

    if (scope === 'class') {
        classWrap.classList.remove('hidden');
        subjectWrap.classList.add('hidden');
        classSelect.name = 'scope_id';
        subjectSelect.name = '';
    } else {
        classWrap.classList.add('hidden');
        subjectWrap.classList.remove('hidden');
        classSelect.name = '';
        subjectSelect.name = 'scope_id';
    }
}
</script>
