<?php
$this->layout('layouts/admin', [
    'title' => $title ?? 'Classes & Arms — Claret LMS',
    'headerTitle' => $headerTitle ?? 'Classes & Arms'
]);

// Build level options for select components
$levelOptions = ['' => 'Select Level...'];
foreach ($levels as $lvl) {
    $levelOptions[$lvl->id] = e($lvl->name) . ' (' . e($lvl->stage) . ')';
}

// Build teacher options for select components
$teacherOptions = ['' => '-- None (Unassigned) --'];
if (!empty($teachers)) {
    foreach ($teachers as $t) {
        $teacherOptions[$t->id] = e($t->name) . ' (' . e($t->staffId) . ')';
    }
}
?>
<div class="space-y-6">
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-slate-900">Classes &amp; Arms</h2>
            <p class="text-sm text-slate-500 mt-1">Manage class groups, section arms (A, B, Gold, Diamond), and assigned Form Teachers (Class Masters).</p>
        </div>
        <div>
            <?php $this->include('components/button', [
                'type' => 'button',
                'variant' => 'primary',
                'label' => 'Create Class',
                'icon' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>',
                'attributes' => 'onclick="window.LMS.showModal(\'create-modal\')"'
            ]); ?>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm">
        <form method="GET" action="/admin/classes" data-lms-filter="true" class="flex flex-col md:flex-row items-stretch md:items-end gap-4">
            <div class="flex-1 min-w-[200px]">
                <label for="class_search" class="block text-sm font-semibold text-slate-700 mb-1.5">Search Classes</label>
                <div class="relative">
                    <input type="text" 
                           id="class_search" 
                           name="search" 
                           value="<?= e($search ?? '') ?>" 
                           placeholder="Search class name, section arm..." 
                           autocomplete="off"
                           class="block w-full min-h-[44px] pl-10 pr-3.5 py-2.5 rounded-lg text-sm text-slate-800 bg-white border border-slate-300 shadow-xs focus:ring-2 focus:ring-[#7B3046]/20 focus:border-[#7B3046] transition outline-none">
                    <svg class="w-4 h-4 text-slate-400 absolute left-3.5 top-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
            </div>

            <div class="w-full md:w-64">
                <label for="filter_level" class="block text-sm font-semibold text-slate-700 mb-1.5">Filter by Level</label>
                <select id="filter_level" name="level_id" class="block w-full min-h-[44px] px-3.5 py-2.5 rounded-lg text-sm text-slate-800 bg-white border border-slate-300 shadow-xs focus:ring-2 focus:ring-[#7B3046]/20 focus:border-[#7B3046] transition outline-none cursor-pointer">
                    <option value="">All Levels</option>
                    <?php foreach ($levels as $lvl): ?>
                        <option value="<?= $lvl->id ?>" <?= ($selectedLevelId ?? 0) === $lvl->id ? 'selected' : '' ?>>
                            <?= e($lvl->name) ?> (<?= e($lvl->stage) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="flex items-center gap-2">
                <button type="submit" class="inline-flex items-center justify-center min-h-[44px] px-4 py-2.5 rounded-lg text-sm font-semibold text-white bg-[#7B3046] hover:bg-[#652739] transition shadow-xs cursor-pointer">
                    Filter
                </button>
                <?php if (!empty($search) || !empty($selectedLevelId)): ?>
                    <a href="/admin/classes" class="inline-flex items-center justify-center min-h-[44px] px-3 py-2.5 rounded-lg text-xs font-semibold text-slate-600 hover:bg-slate-100 transition">
                        Reset
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Classes Table Container -->
    <div data-lms-table-container="true" class="space-y-4">
        <?php if (empty($classes)): ?>
            <?php $this->include('components/empty_state', [
                'title' => 'No Classes Configured',
                'message' => 'No classes or arms match your criteria. Click "Create Class" to get started.'
            ]); ?>
        <?php else: ?>
            <div class="bg-white border border-slate-200 rounded-xl overflow-hidden shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                    <thead class="bg-slate-50 text-slate-600 font-semibold">
                        <tr>
                            <th scope="col" class="px-6 py-3.5">Level</th>
                            <th scope="col" class="px-6 py-3.5">Class Name</th>
                            <th scope="col" class="px-6 py-3.5">Section / Arm</th>
                            <th scope="col" class="px-6 py-3.5">Form Teacher (Class Master)</th>
                            <th scope="col" class="px-6 py-3.5">Status</th>
                            <th scope="col" class="px-6 py-3.5 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        <?php foreach ($classes as $cls): ?>
                            <tr class="hover:bg-slate-50/50 transition">
                                <td class="px-6 py-4">
                                    <?php $this->include('components/badge', [
                                        'label' => $cls->academicLevel?->name ?? 'Level #' . $cls->academicLevelId,
                                        'variant' => 'neutral'
                                    ]); ?>
                                </td>
                                <td class="px-6 py-4 font-semibold text-slate-900">
                                    <a href="/admin/classes/<?= $cls->id ?>" class="text-[#7B3046] hover:underline font-bold">
                                        <?= e($cls->name) ?>
                                    </a>
                                </td>
                                <td class="px-6 py-4">
                                    <?php if ($cls->sectionArm): ?>
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-md bg-brand-50 text-brand-700 text-xs font-bold font-mono border border-brand-100">
                                            Arm <?= e($cls->sectionArm) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-xs text-slate-400 italic">—</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4">
                                    <?php if (!empty($cls->formTeacherName)): ?>
                                        <div class="flex items-center gap-2.5">
                                            <span class="w-7 h-7 rounded-full bg-brand-100 text-brand-800 text-xs font-bold flex items-center justify-center shrink-0 border border-brand-200">
                                                <?= strtoupper(substr($cls->formTeacherName, 0, 1)) ?>
                                            </span>
                                            <div class="min-w-0">
                                                <p class="text-xs font-semibold text-slate-900 truncate"><?= e($cls->formTeacherName) ?></p>
                                                <?php if (!empty($cls->formTeacherStaffId)): ?>
                                                    <p class="text-[10px] font-mono text-slate-500"><?= e($cls->formTeacherStaffId) ?></p>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-slate-100 text-slate-500 border border-slate-200">
                                            Unassigned
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4">
                                    <?php if ($cls->isActive()): ?>
                                        <?php $this->include('components/badge', ['label' => 'Active', 'variant' => 'success']); ?>
                                    <?php else: ?>
                                        <?php $this->include('components/badge', ['label' => 'Inactive', 'variant' => 'neutral']); ?>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="inline-flex items-center gap-2 justify-end">
                                        <a href="/admin/classes/<?= $cls->id ?>" class="inline-flex items-center gap-1 px-2.5 py-1 rounded text-xs font-semibold bg-slate-100 hover:bg-[#7B3046] hover:text-white text-slate-700 transition">
                                            <span>Details</span>
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                        </a>

                                        <?php $this->include('components/button', [
                                            'type' => 'button',
                                            'variant' => 'secondary',
                                            'label' => 'Edit',
                                            'class' => 'px-2.5 py-1 min-h-0 text-xs font-semibold',
                                            'attributes' => 'onclick="openEditModal(' . $cls->id . ', ' . $cls->academicLevelId . ', \'' . e(addslashes($cls->name)) . '\', \'' . e(addslashes($cls->sectionArm ?? '')) . '\', ' . ($cls->formTeacherId ? $cls->formTeacherId : 'null') . ')"'
                                        ]); ?>

                                        <form method="POST" action="/admin/classes/<?= $cls->id ?>/status" class="inline">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="status" value="<?= $cls->isActive() ? 'inactive' : 'active' ?>">
                                            <?php $this->include('components/button', [
                                                'type' => 'submit',
                                                'variant' => 'secondary',
                                                'label' => $cls->isActive() ? 'Deactivate' : 'Activate',
                                                'class' => 'px-2.5 py-1 min-h-0 text-xs font-semibold ' . ($cls->isActive()
                                                    ? 'bg-warning-100 hover:bg-warning-200 text-warning-800 border-transparent'
                                                    : 'bg-success-100 hover:bg-success-200 text-success-700 border-transparent')
                                            ]); ?>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination Component -->
            <?php
            $paginationBaseUrl = '/admin/classes?' . http_build_query(array_filter([
                'level_id' => $selectedLevelId ?: null,
                'search' => !empty($search) ? $search : null,
            ]));
            $this->include('components/pagination', [
                'currentPage' => $currentPage ?? 1,
                'totalPages' => $totalPages ?? 1,
                'totalResults' => $totalResults ?? count($classes),
                'perPage' => $perPage ?? 25,
                'baseUrl' => $paginationBaseUrl,
            ]);
            ?>
        </div>
    <?php endif; ?>
</div>

<!-- Create Modal -->
<?php ob_start(); ?>
<form method="POST" action="/admin/classes" class="space-y-4" novalidate>
    <?= csrf_field() ?>

    <?php $this->include('components/select', [
        'name' => 'academic_level_id',
        'id' => 'create_level',
        'label' => 'Academic Level',
        'options' => $levelOptions,
        'selected' => '',
        'required' => true,
        'placeholder' => ''
    ]); ?>

    <?php $this->include('components/input', [
        'name' => 'name',
        'id' => 'create_class_name',
        'label' => 'Class Name',
        'placeholder' => 'e.g. JSS 1A, Grade 7 Gold',
        'required' => true
    ]); ?>

    <?php $this->include('components/input', [
        'name' => 'section_arm',
        'id' => 'create_arm',
        'label' => 'Section / Arm',
        'placeholder' => 'e.g. A, B, Gold, Diamond',
        'helpText' => 'Optional. Leave blank if the class has no arm designation.'
    ]); ?>

    <?php $this->include('components/select', [
        'name' => 'form_teacher_id',
        'id' => 'create_form_teacher',
        'label' => 'Form Teacher (Class Master)',
        'options' => $teacherOptions,
        'selected' => '',
        'helpText' => 'Optional. Select the teacher responsible for this class cohort and report card remarks.'
    ]); ?>

    <div class="pt-4 border-t border-slate-200 flex justify-end gap-3">
        <?php $this->include('components/button', [
            'type' => 'button',
            'variant' => 'secondary',
            'label' => 'Cancel',
            'attributes' => 'onclick="window.LMS.hideModal(\'create-modal\')"'
        ]); ?>
        <?php $this->include('components/button', [
            'type' => 'submit',
            'variant' => 'primary',
            'label' => 'Create Class'
        ]); ?>
    </div>
</form>
<?php $createModalBody = ob_get_clean(); ?>

<?php $this->include('components/modal', [
    'id' => 'create-modal',
    'title' => 'Create Class',
    'body' => $createModalBody,
    'size' => 'md'
]); ?>

<!-- Edit Modal -->
<?php ob_start(); ?>
<form id="edit-form" method="POST" action="" class="space-y-4" novalidate>
    <?= csrf_field() ?>

    <?php $this->include('components/select', [
        'name' => 'academic_level_id',
        'id' => 'edit_level',
        'label' => 'Academic Level',
        'options' => $levelOptions,
        'selected' => '',
        'required' => true,
        'placeholder' => ''
    ]); ?>

    <?php $this->include('components/input', [
        'name' => 'name',
        'id' => 'edit_class_name',
        'label' => 'Class Name',
        'required' => true
    ]); ?>

    <?php $this->include('components/input', [
        'name' => 'section_arm',
        'id' => 'edit_arm',
        'label' => 'Section / Arm',
        'helpText' => 'Optional. Leave blank if the class has no arm designation.'
    ]); ?>

    <?php $this->include('components/select', [
        'name' => 'form_teacher_id',
        'id' => 'edit_form_teacher',
        'label' => 'Form Teacher (Class Master)',
        'options' => $teacherOptions,
        'selected' => '',
        'helpText' => 'Optional. Select or change the Form Teacher for this class cohort.'
    ]); ?>

    <div class="pt-4 border-t border-slate-200 flex justify-end gap-3">
        <?php $this->include('components/button', [
            'type' => 'button',
            'variant' => 'secondary',
            'label' => 'Cancel',
            'attributes' => 'onclick="window.LMS.hideModal(\'edit-modal\')"'
        ]); ?>
        <?php $this->include('components/button', [
            'type' => 'submit',
            'variant' => 'primary',
            'label' => 'Save Changes'
        ]); ?>
    </div>
</form>
<?php $editModalBody = ob_get_clean(); ?>

<?php $this->include('components/modal', [
    'id' => 'edit-modal',
    'title' => 'Edit Class',
    'body' => $editModalBody,
    'size' => 'md'
]); ?>

<script>
    function openEditModal(id, levelId, name, arm, formTeacherId) {
        document.getElementById('edit-form').action = '/admin/classes/' + id;
        document.getElementById('edit_level').value = levelId;
        document.getElementById('edit_class_name').value = name;
        document.getElementById('edit_arm').value = arm || '';
        document.getElementById('edit_form_teacher').value = formTeacherId || '';
        window.LMS.showModal('edit-modal');
    }
</script>
