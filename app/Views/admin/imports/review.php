<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-xl font-bold text-slate-900">Import Batch Preview: #<?= e($batch->id) ?></h2>
            <p class="text-sm text-slate-500">File: <?= e($batch->originalName) ?> &bull; Type: <span class="font-semibold text-slate-800 uppercase"><?= e($batch->type) ?></span></p>
        </div>
        <a href="/admin/imports/users" class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 transition">
            Back to Imports
        </a>
    </div>

    <!-- Summary Stats -->
    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
            <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Rows</span>
            <div class="text-2xl font-bold text-slate-900 mt-1"><?= e($batch->totalRows) ?></div>
        </div>
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
            <span class="text-xs font-semibold text-success-700 uppercase tracking-wider">Valid Rows</span>
            <div class="text-2xl font-bold text-success-700 mt-1"><?= e($batch->validRows) ?></div>
        </div>
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
            <span class="text-xs font-semibold text-danger-700 uppercase tracking-wider">Invalid Rows</span>
            <div class="text-2xl font-bold text-danger-700 mt-1"><?= e($batch->invalidRows) ?></div>
        </div>
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
            <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Status</span>
            <div class="mt-1">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold
                    <?= $batch->status === 'committed' ? 'bg-success-100 text-success-700' : ($batch->status === 'failed' ? 'bg-danger-100 text-danger-700' : 'bg-warning-100 text-warning-800') ?>">
                    <?= e(ucfirst($batch->status)) ?>
                </span>
            </div>
        </div>
    </div>

    <!-- Validation Errors Table -->
    <?php if (!empty($errors)): ?>
        <div class="bg-white rounded-xl border border-danger-200 shadow-sm overflow-hidden">
            <div class="px-6 py-4 bg-danger-50 border-b border-danger-200 flex items-center justify-between">
                <div class="flex items-center gap-2 text-danger-700 font-bold">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                    Row-Level Validation Errors (<?= count($errors) ?>)
                </div>
                <a href="/admin/imports/<?= e($batch->id) ?>/errors.csv" class="text-xs font-semibold text-danger-700 hover:text-danger-900 underline">
                    Download Errors CSV
                </a>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                    <thead class="bg-slate-50 text-slate-600 font-semibold uppercase text-xs tracking-wider">
                        <tr>
                            <th class="px-6 py-3.5">Row #</th>
                            <th class="px-6 py-3.5">Identified Errors</th>
                            <th class="px-6 py-3.5">Raw Submitted Data</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-medium text-slate-700">
                        <?php foreach ($errors as $err): ?>
                            <tr class="hover:bg-slate-50 transition">
                                <td class="px-6 py-4 font-mono text-xs font-bold text-danger-700">
                                    Row <?= e($err['row_number']) ?>
                                </td>
                                <td class="px-6 py-4 text-xs text-danger-700">
                                    <ul class="list-disc list-inside space-y-1">
                                        <?php foreach ($err['errors'] as $msg): ?>
                                            <li><?= e($msg) ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </td>
                                <td class="px-6 py-4 text-xs font-mono text-slate-500 max-w-md truncate">
                                    <?= e(json_encode($err['raw_data'])) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

    <!-- Valid Records Preview -->
    <?php if (!empty($validRows)): ?>
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                <h3 class="font-bold text-slate-900">Valid Rows Ready for Commit (<?= count($validRows) ?>)</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                    <thead class="bg-slate-50 text-slate-600 font-semibold uppercase text-xs tracking-wider">
                        <tr>
                            <th class="px-6 py-3.5">Row #</th>
                            <th class="px-6 py-3.5">Name</th>
                            <th class="px-6 py-3.5">Email</th>
                            <th class="px-6 py-3.5">Identifier</th>
                            <th class="px-6 py-3.5">Additional Details</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-medium text-slate-700">
                        <?php foreach (array_slice($validRows, 0, 50) as $vr): ?>
                            <?php $data = $vr['data'] ?? $vr; ?>
                            <tr class="hover:bg-slate-50 transition">
                                <td class="px-6 py-4 font-mono text-xs text-slate-500">
                                    Row <?= e($vr['row_number'] ?? '—') ?>
                                </td>
                                <td class="px-6 py-4 font-bold text-slate-900">
                                    <?= e($data['name'] ?? '—') ?>
                                </td>
                                <td class="px-6 py-4 text-xs text-slate-600">
                                    <?= e($data['email'] ?? '—') ?>
                                </td>
                                <td class="px-6 py-4 font-mono text-xs font-semibold text-slate-800">
                                    <?= e($data['admission_number'] ?? ($data['staff_id'] ?? ($data['student_admission_number'] ?? '—'))) ?>
                                </td>
                                <td class="px-6 py-4 text-xs text-slate-500">
                                    <?= e($data['class_name'] ?? ($data['phone'] ?? ($data['relationship'] ?? '—'))) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php if (count($validRows) > 50): ?>
                <div class="px-6 py-3 bg-slate-50 text-xs text-slate-500 text-center border-t border-slate-200">
                    Showing first 50 of <?= count($validRows) ?> valid records.
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- Commit Action Card -->
    <?php if (!$batch->isCommitted() && !empty($validRows)): ?>
        <div class="bg-slate-900 text-white p-6 rounded-xl shadow-md flex flex-col sm:flex-row items-center justify-between gap-4">
            <div>
                <h3 class="text-base font-bold">Ready to Provision Accounts?</h3>
                <p class="text-xs text-slate-300">Committing will create user credentials, assign security roles, and generate profile records in a single transactional batch.</p>
            </div>
            <form method="POST" action="/admin/imports/<?= e($batch->id) ?>/commit">
                <?= csrf_field() ?>
                <button type="submit" class="px-6 py-3 bg-brand-600 text-white font-bold rounded-lg hover:bg-brand-700 shadow-sm transition">
                    Commit Import (<?= count($validRows) ?> Records)
                </button>
            </form>
        </div>
    <?php endif; ?>
</div>
