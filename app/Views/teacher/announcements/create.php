<div class="max-w-4xl mx-auto space-y-6">
    <!-- Header Card -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <!-- Breadcrumbs -->
                <nav class="flex items-center gap-2 text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">
                    <a href="/teacher/dashboard" class="text-slate-400 hover:text-emerald-600 transition">Faculty Portal</a>
                    <span class="text-slate-300">/</span>
                    <a href="/teacher/announcements" class="text-slate-400 hover:text-emerald-600 transition">Announcements</a>
                    <span class="text-slate-300">/</span>
                    <span class="text-slate-700">Post Announcement</span>
                </nav>
                <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight leading-tight">
                    Compose Class Announcement
                </h1>
                <p class="text-xs text-slate-500 mt-1">
                    Broadcast a news bulletin, project deadline reminder, or academic update to your enrolled students.
                </p>
            </div>

            <div class="flex items-center gap-2.5">
                <?php $this->include('components/button', [
                    'label' => 'Back to Announcements',
                    'variant' => 'secondary',
                    'href' => '/teacher/announcements',
                    'icon' => '<svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>'
                ]); ?>
            </div>
        </div>
    </div>

    <!-- Form Container -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6 sm:p-8">
        <form action="/teacher/announcements/create" method="POST" class="space-y-6">
            <?= csrf_field() ?>

            <!-- Target Scope Selection Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                        Target Audience Scope <span class="text-rose-500">*</span>
                    </label>
                    <select name="scope" id="scopeSelect" onchange="toggleScopeInputs()" required
                            class="w-full rounded-xl border border-slate-300 text-xs focus:border-emerald-500 focus:ring-emerald-500 bg-slate-50 py-2.5 px-3 font-semibold text-slate-900 transition">
                        <option value="class" <?= old('scope', 'class') === 'class' ? 'selected' : '' ?>>Entire Homeroom Class</option>
                        <option value="class_subject" <?= old('scope') === 'class_subject' ? 'selected' : '' ?>>Subject Specific Cohort</option>
                    </select>
                </div>

                <div id="classSelectWrap">
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                        Select Class Cohort <span class="text-rose-500">*</span>
                    </label>
                    <select name="scope_id" id="classScopeId"
                            class="w-full rounded-xl border border-slate-300 text-xs focus:border-emerald-500 focus:ring-emerald-500 bg-slate-50 py-2.5 px-3 font-semibold text-slate-900 transition">
                        <?php foreach ($classes as $cls): ?>
                            <option value="<?= (int)$cls['id'] ?>" <?= (int)old('scope_id') === (int)$cls['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cls['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div id="subjectSelectWrap" class="hidden">
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                        Select Subject Allocation <span class="text-rose-500">*</span>
                    </label>
                    <select id="subjectScopeId"
                            class="w-full rounded-xl border border-slate-300 text-xs focus:border-emerald-500 focus:ring-emerald-500 bg-slate-50 py-2.5 px-3 font-semibold text-slate-900 transition">
                        <?php foreach ($allocations as $alloc): ?>
                            <option value="<?= (int)$alloc['class_subject_id'] ?>" <?= (int)old('scope_id') === (int)$alloc['class_subject_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($alloc['class_name']) ?> &mdash; <?= htmlspecialchars($alloc['subject_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- Title -->
            <div>
                <label for="title" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                    Announcement Title <span class="text-rose-500">*</span>
                </label>
                <input type="text" name="title" id="title" required maxlength="200"
                       value="<?= htmlspecialchars((string)old('title', '')) ?>"
                       placeholder="e.g. Science Project Submission Deadline & Guidelines"
                       class="w-full rounded-xl border border-slate-300 text-xs focus:border-emerald-500 focus:ring-emerald-500 bg-slate-50 py-2.5 px-3 font-medium text-slate-900 transition">
            </div>

            <!-- Body -->
            <div>
                <label for="body" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                    Announcement Content <span class="text-rose-500">*</span>
                </label>
                <textarea name="body" id="body" rows="6" required
                          placeholder="Type the full announcement message, instructions, or notification details..."
                          class="w-full rounded-xl border border-slate-300 text-xs focus:border-emerald-500 focus:ring-emerald-500 bg-slate-50 py-2.5 px-3 font-medium text-slate-900 leading-relaxed transition"><?= htmlspecialchars((string)old('body', '')) ?></textarea>
            </div>

            <!-- Expiration Date -->
            <div>
                <label for="expires_at" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                    Expiration Date (Optional)
                </label>
                <input type="date" name="expires_at" id="expires_at"
                       value="<?= htmlspecialchars((string)old('expires_at', '')) ?>"
                       class="rounded-xl border border-slate-300 text-xs focus:border-emerald-500 focus:ring-emerald-500 bg-slate-50 py-2 px-3 font-semibold text-slate-900 transition">
                <p class="text-[11px] text-slate-400 mt-1">If left blank, the announcement remains pinned indefinitely.</p>
            </div>

            <!-- Form Actions Footer -->
            <div class="pt-6 border-t border-slate-100 flex items-center justify-end gap-3">
                <?php $this->include('components/button', [
                    'label' => 'Cancel',
                    'variant' => 'secondary',
                    'href' => '/teacher/announcements'
                ]); ?>

                <?php $this->include('components/button', [
                    'label' => 'Publish Announcement',
                    'variant' => 'primary',
                    'type' => 'submit',
                    'icon' => '<svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>'
                ]); ?>
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

document.addEventListener('DOMContentLoaded', toggleScopeInputs);
</script>
