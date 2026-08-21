<?php
$this->layout('layouts/admin', [
    'title' => $title ?? 'Bulk User Imports — Claret LMS',
    'headerTitle' => $headerTitle ?? 'Bulk User CSV Imports'
]);

$typeOptions = [
    'students' => 'Students (name, email, admission_number, [gender, date_of_birth, class_name])',
    'teachers' => 'Teachers (name, email, staff_id, [phone])',
    'parents' => 'Parents / Guardians (name, email, [phone, student_admission_number, relationship])'
];
?>
<div class="space-y-6">
    <!-- Import Form Card -->
    <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
        <h2 class="text-xl font-bold text-slate-900 mb-1">Bulk User Onboarding via CSV</h2>
        <p class="text-sm text-slate-500 mb-6">Upload CSV spreadsheets to rapidly provision student rosters, teaching faculty, or parent profiles in batches.</p>

        <form method="POST" action="/admin/imports/users/validate" enctype="multipart/form-data" class="space-y-6" novalidate>
            <?= csrf_field() ?>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <div>
                    <?php $this->include('components/select', [
                        'name' => 'type',
                        'id' => 'import_type',
                        'label' => 'Import Target Type',
                        'options' => $typeOptions,
                        'selected' => 'students',
                        'required' => true,
                        'placeholder' => ''
                    ]); ?>
                </div>

                <div class="form-group flex flex-col gap-1.5 w-full">
                    <label class="text-sm font-semibold text-slate-700">Upload CSV File</label>
                    <div class="relative flex items-center justify-center w-full min-h-[44px] px-3.5 py-2.5 bg-white border border-slate-300 rounded-lg shadow-xs hover:border-slate-400 transition cursor-pointer">
                        <input type="file" name="csv_file" accept=".csv,text/csv" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer">
                        <div class="flex items-center gap-2 text-slate-500">
                            <svg class="w-5 h-5 text-slate-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                            <span class="text-sm text-slate-600 font-medium">Select a CSV file...</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-group flex flex-col gap-1.5 w-full">
                <label for="csv_content" class="text-sm font-semibold text-slate-700">Or Paste Raw CSV Data</label>
                <textarea name="csv_content" id="csv_content" rows="6" placeholder="name,email,admission_number,gender,class_name&#10;Alice Smith,alice@claret.edu.ng,STD-2026-001,female,JSS 1 Gold" 
                          class="w-full font-mono text-sm p-3.5 bg-white border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 shadow-xs transition duration-200"></textarea>
            </div>

            <div class="flex items-center justify-end">
                <?php $this->include('components/button', [
                    'type' => 'submit',
                    'variant' => 'primary',
                    'label' => 'Validate & Preview CSV',
                    'icon' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>'
                ]); ?>
            </div>
        </form>
    </div>

    <!-- Recent Import Batches -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100">
            <h3 class="font-bold text-slate-900">Recent Import History</h3>
        </div>
        <?php if (empty($recentImports)): ?>
            <div class="p-6">
                <?php $this->include('components/empty_state', [
                    'title' => 'No Import History',
                    'message' => 'No CSV import batches have been uploaded yet.'
                ]); ?>
            </div>
        <?php else: ?>
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
                    <tbody class="divide-y divide-slate-200 font-medium text-slate-700">
                        <?php foreach ($recentImports as $imp): ?>
                            <tr class="hover:bg-slate-50/50 transition">
                                <td class="px-6 py-4 font-mono text-xs font-semibold text-slate-900">
                                    #<?= e($imp->id) ?>
                                </td>
                                <td class="px-6 py-4">
                                    <?php $this->include('components/badge', [
                                        'label' => ucfirst($imp->type),
                                        'variant' => 'neutral'
                                    ]); ?>
                                </td>
                                <td class="px-6 py-4 text-xs text-slate-600 truncate max-w-xs font-normal">
                                    <?= e($imp->originalName) ?>
                                </td>
                                <td class="px-6 py-4 text-xs font-normal">
                                    <span class="font-bold text-slate-900"><?= e($imp->totalRows) ?></span> total
                                    (<span class="text-success-700 font-bold"><?= e($imp->validRows) ?> valid</span>, 
                                     <span class="text-danger-700 font-bold"><?= e($imp->invalidRows) ?> invalid</span>)
                                </td>
                                <td class="px-6 py-4">
                                    <?php
                                    $statusVariant = 'neutral';
                                    if ($imp->status === 'committed') {
                                        $statusVariant = 'success';
                                    } elseif ($imp->status === 'failed') {
                                        $statusVariant = 'danger';
                                    } elseif (in_array($imp->status, ['pending', 'processing'])) {
                                        $statusVariant = 'warning';
                                    }
                                    $this->include('components/badge', [
                                        'label' => ucfirst($imp->status),
                                        'variant' => $statusVariant
                                    ]);
                                    ?>
                                </td>
                                <td class="px-6 py-4 text-xs text-slate-500 font-normal">
                                    <?= e($imp->createdAt ? date('M j, Y H:i', strtotime($imp->createdAt)) : '—') ?>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <?php $this->include('components/button', [
                                        'type' => 'link',
                                        'variant' => 'secondary',
                                        'label' => 'Review',
                                        'class' => 'px-2.5 py-1 min-h-0 text-xs font-semibold',
                                        'attributes' => 'href="/admin/imports/' . $imp->id . '/review"'
                                    ]); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const fileInput = document.querySelector('input[type="file"]');
    if (fileInput) {
        fileInput.addEventListener('change', (e) => {
            const fileName = e.target.files[0]?.name || 'Select a CSV file...';
            const labelSpan = fileInput.nextElementSibling.querySelector('span');
            if (labelSpan) {
                labelSpan.textContent = fileName;
            }
        });
    }
});
</script>
