<?php
$this->layout('layouts/admin', [
    'title' => $title ?? "Review Import #{$batch->id} — Claret LMS",
    'headerTitle' => $headerTitle ?? "Review Import: {$batch->originalName}"
]);
?>
<div class="space-y-6">
    <!-- Header Meta Box -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
        <div>
            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Batch ID</span>
            <h2 class="text-lg font-bold text-slate-900 mt-0.5">Import Batch Preview: #<?= e($batch->id) ?></h2>
            <div class="flex items-center gap-2 mt-1.5 flex-wrap">
                <span class="text-xs text-slate-500">File: <span class="font-semibold text-slate-700"><?= e($batch->originalName) ?></span></span>
                <span class="text-slate-300 text-xs">&bull;</span>
                <span class="text-xs text-slate-500">Type:</span>
                <?php $this->include('components/badge', ['label' => ucfirst($batch->type), 'variant' => 'neutral']); ?>
            </div>
        </div>
        <div class="flex-shrink-0">
            <?php $this->include('components/button', [
                'type' => 'link',
                'variant' => 'secondary',
                'label' => 'Back to Imports',
                'attributes' => 'href="/admin/imports/users"',
                'icon' => '<svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>'
            ]); ?>
        </div>
    </div>

    <!-- Summary Stats -->
    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm flex flex-col justify-between">
            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Total Rows</span>
            <div class="text-3xl font-bold text-slate-900 mt-2"><?= e($batch->totalRows) ?></div>
        </div>
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm flex flex-col justify-between">
            <span class="text-xs font-semibold text-success-700 uppercase tracking-wider">Valid Rows</span>
            <div class="text-3xl font-bold text-success-700 mt-2"><?= e($batch->validRows) ?></div>
        </div>
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm flex flex-col justify-between">
            <span class="text-xs font-semibold text-danger-700 uppercase tracking-wider">Invalid Rows</span>
            <div class="text-3xl font-bold text-danger-700 mt-2"><?= e($batch->invalidRows) ?></div>
        </div>
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm flex flex-col justify-between">
            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Status</span>
            <div class="mt-2">
                <?php
                $statusVariant = 'neutral';
                if ($batch->status === 'committed') {
                    $statusVariant = 'success';
                } elseif ($batch->status === 'failed') {
                    $statusVariant = 'danger';
                } elseif (in_array($batch->status, ['pending', 'processing'])) {
                    $statusVariant = 'warning';
                }
                $this->include('components/badge', [
                    'label' => ucfirst($batch->status),
                    'variant' => $statusVariant
                ]);
                ?>
            </div>
        </div>
    </div>

    <!-- Validation Errors Table -->
    <?php if (!empty($errors)): ?>
        <div class="bg-white rounded-xl border border-danger-200 shadow-sm overflow-hidden">
            <div class="px-6 py-4 bg-danger-50/50 border-b border-danger-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="flex items-center gap-2 text-danger-700 font-bold">
                    <svg class="w-5 h-5 flex-shrink-0 text-danger-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                    <span>Row-Level Validation Errors (<?= count($errors) ?>)</span>
                </div>
                <div class="flex-shrink-0">
                    <?php $this->include('components/button', [
                        'type' => 'link',
                        'variant' => 'danger',
                        'label' => 'Download Errors CSV',
                        'attributes' => 'href="/admin/imports/' . e($batch->id) . '/errors.csv"',
                        'class' => 'px-3 py-1.5 min-h-0 text-xs font-semibold'
                    ]); ?>
                </div>
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
                            <tr class="hover:bg-slate-50/50 transition">
                                <td class="px-6 py-4 font-mono text-xs font-bold text-danger-700">
                                    Row <?= e($err['row_number']) ?>
                                </td>
                                <td class="px-6 py-4 text-xs text-danger-600 font-normal">
                                    <ul class="list-disc list-inside space-y-1">
                                        <?php foreach ($err['errors'] as $msg): ?>
                                            <li><?= e($msg) ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </td>
                                <td class="px-6 py-4 text-xs font-mono text-slate-500 max-w-md truncate font-normal">
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
            <div class="px-6 py-4 border-b border-slate-200 bg-slate-50/50">
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
                    <tbody class="divide-y divide-slate-200 font-medium text-slate-700">
                        <?php foreach (array_slice($validRows, 0, 50) as $vr): ?>
                            <?php $data = $vr['data'] ?? $vr; ?>
                            <tr class="hover:bg-slate-50/50 transition">
                                <td class="px-6 py-4 font-mono text-xs text-slate-500 font-normal">
                                    Row <?= e($vr['row_number'] ?? '—') ?>
                                </td>
                                <td class="px-6 py-4 font-bold text-slate-900">
                                    <?= e($data['name'] ?? '—') ?>
                                </td>
                                <td class="px-6 py-4 text-xs text-slate-600 font-normal font-mono">
                                    <?= e($data['email'] ?? '—') ?>
                                </td>
                                <td class="px-6 py-4 font-mono text-xs font-semibold text-slate-800">
                                    <?= e($data['admission_number'] ?? ($data['staff_id'] ?? ($data['student_admission_number'] ?? '—'))) ?>
                                </td>
                                <td class="px-6 py-4 text-xs text-slate-500 font-normal">
                                    <?= e($data['class_name'] ?? ($data['phone'] ?? ($data['relationship'] ?? '—'))) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php if (count($validRows) > 50): ?>
                <div class="px-6 py-3.5 bg-slate-50 text-xs text-slate-500 text-center border-t border-slate-200 font-normal">
                    Showing first 50 of <?= count($validRows) ?> valid records.
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- Commit Action Card -->
    <?php if (!$batch->isCommitted() && !empty($validRows)): ?>
        <div class="bg-slate-950 text-white p-6 rounded-xl border border-slate-800 shadow-lg flex flex-col sm:flex-row items-center justify-between gap-6">
            <div class="space-y-1">
                <h3 class="text-lg font-bold">Ready to Provision Accounts?</h3>
                <p class="text-sm text-slate-400 font-normal max-w-xl">Committing will create user credentials, assign security roles, and generate profile records in a single transaction.</p>
            </div>
            <form method="POST" action="/admin/imports/<?= e($batch->id) ?>/commit" class="flex-shrink-0">
                <?= csrf_field() ?>
                <?php $this->include('components/button', [
                    'type' => 'submit',
                    'variant' => 'primary',
                    'label' => 'Commit Import (' . count($validRows) . ' Records)',
                    'icon' => '<svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>'
                ]); ?>
            </form>
        </div>
    <?php endif; ?>
</div>
