<div class="space-y-6">
    <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
        <h2 class="text-lg font-bold text-slate-900 mb-1">Bulk User Onboarding via CSV</h2>
        <p class="text-sm text-slate-500 mb-6">Upload CSV spreadsheets to rapidly provision student rosters, teaching faculty, or parent profiles in batches.</p>

        <form method="POST" action="/admin/imports/users/validate" enctype="multipart/form-data" class="space-y-6">
            <?= csrf_field() ?>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Import Target Type *</label>
                    <select name="type" required class="w-full px-3.5 py-2.5 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-brand-500">
                        <option value="students">Students (name, email, admission_number, [gender, date_of_birth, class_name])</option>
                        <option value="teachers">Teachers (name, email, staff_id, [phone])</option>
                        <option value="parents">Parents / Guardians (name, email, [phone, student_admission_number, relationship])</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Upload CSV File</label>
                    <input type="file" name="csv_file" accept=".csv,text/csv" 
                           class="w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-brand-50 file:text-brand-700 hover:file:bg-brand-100">
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Or Paste Raw CSV Data</label>
                <textarea name="csv_content" rows="6" placeholder="name,email,admission_number,gender,class_name&#10;Alice Smith,alice@claret.edu.ng,STD-2026-001,female,JSS 1 Gold" 
                          class="w-full font-mono text-xs p-3.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-brand-500"></textarea>
            </div>

            <div class="flex items-center justify-end">
                <button type="submit" class="px-6 py-2.5 bg-brand-600 text-white font-semibold rounded-lg hover:bg-brand-700 shadow-sm transition">
                    Validate & Preview CSV
                </button>
            </div>
        </form>
    </div>

    <!-- Recent Import Batches -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100">
            <h3 class="font-bold text-slate-900">Recent Import History</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                <thead class="bg-slate-50 text-slate-600 font-semibold uppercase text-xs tracking-wider">
                    <tr>
                        <th class="px-6 py-3.5">Batch ID</th>
                        <th class="px-6 py-3.5">Type</th>
                        <th class="px-6 py-3.5">File / Source</th>
                        <th class="px-6 py-3.5">Rows (Total / Valid / Invalid)</th>
                        <th class="px-6 py-3.5">Status</th>
                        <th class="px-6 py-3.5">Uploaded</th>
                        <th class="px-6 py-3.5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium text-slate-700">
                    <?php if (empty($recentImports)): ?>
                        <tr>
                            <td colspan="7" class="px-6 py-10 text-center text-slate-400">
                                No previous import history recorded.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($recentImports as $imp): ?>
                            <tr class="hover:bg-slate-50 transition">
                                <td class="px-6 py-4 font-mono text-xs font-semibold text-slate-900">
                                    #<?= e($imp->id) ?>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-slate-100 text-slate-800">
                                        <?= e(ucfirst($imp->type)) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-xs text-slate-600 truncate max-w-xs">
                                    <?= e($imp->originalName) ?>
                                </td>
                                <td class="px-6 py-4 text-xs">
                                    <span class="font-semibold text-slate-900"><?= e($imp->totalRows) ?></span> total
                                    (<span class="text-success-700 font-semibold"><?= e($imp->validRows) ?> valid</span>, 
                                     <span class="text-danger-700 font-semibold"><?= e($imp->invalidRows) ?> invalid</span>)
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold
                                        <?= $imp->status === 'committed' ? 'bg-success-100 text-success-700' : ($imp->status === 'failed' ? 'bg-danger-100 text-danger-700' : 'bg-warning-100 text-warning-800') ?>">
                                        <?= e(ucfirst($imp->status)) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-xs text-slate-500">
                                    <?= e($imp->createdAt ? date('M j, Y H:i', strtotime($imp->createdAt)) : '—') ?>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <a href="/admin/imports/<?= e($imp->id) ?>/review" class="text-xs font-semibold text-brand-600 hover:text-brand-800 transition">
                                        Review
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
