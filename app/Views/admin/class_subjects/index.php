<?php
$this->layout('layouts/admin', [
    'title' => $title ?? 'Class Subjects & Teachers — Claret LMS',
    'headerTitle' => $headerTitle ?? 'Class Subjects & Teacher Mappings'
]);

// Prepare options for selects
$sessionOptions = [];
foreach ($sessions as $session) {
    $sessionOptions[$session->id] = e($session->name) . ' (' . ucfirst($session->status) . ')';
}

$classOptions = ['' => 'All Classes'];
foreach ($classes as $cls) {
    $classOptions[$cls->id] = e($cls->name);
}

$createClassOptions = ['' => 'Select Class...'];
foreach ($classes as $cls) {
    $createClassOptions[$cls->id] = e($cls->name);
}

$subjectOptions = ['' => 'Select Subject...'];
foreach ($subjects as $sub) {
    $subjectOptions[$sub->id] = e($sub->name) . ' (' . e($sub->code) . ')';
}

$teacherOptions = ['' => 'Select Teacher...'];
foreach ($teachers as $t) {
    $teacherOptions[$t->id] = e($t->user?->name ?? 'Teacher #' . $t->id) . ' (' . e($t->staffId) . ')';
}
?>
<div class="space-y-6">
    <!-- Header with Actions -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-slate-900 tracking-tight">Class Subjects & Teacher Mappings</h2>
            <p class="text-sm text-slate-500 mt-1">Manage session-scoped teaching allocations, subject deliveries, and teacher assignments.</p>
        </div>
        <div>
            <?php $this->include('components/button', [
                'type' => 'button',
                'variant' => 'primary',
                'label' => 'Assign Subject & Teacher',
                'icon' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>',
                'attributes' => 'onclick="window.LMS.showModal(\'create-modal\')"'
            ]); ?>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm">
        <form method="GET" action="/admin/class-subjects" class="flex flex-col sm:flex-row items-stretch sm:items-end gap-4">
            <div class="flex-1 min-w-[200px]">
                <?php $this->include('components/select', [
                    'name' => 'session_id',
                    'id' => 'filter_session',
                    'label' => 'Academic Session',
                    'options' => $sessionOptions,
                    'selected' => $selectedSessionId,
                    'placeholder' => '',
                    'attributes' => 'onchange="this.form.submit()"'
                ]); ?>
            </div>

            <div class="flex-1 min-w-[200px]">
                <?php $this->include('components/select', [
                    'name' => 'class_id',
                    'id' => 'filter_class',
                    'label' => 'Filter by Class',
                    'options' => $classOptions,
                    'selected' => $selectedClassId,
                    'placeholder' => '',
                    'attributes' => 'onchange="this.form.submit()"'
                ]); ?>
            </div>

            <div class="flex items-center">
                <?php $this->include('components/button', [
                    'href' => '/admin/class-subjects',
                    'variant' => 'quiet',
                    'label' => 'Reset Filter'
                ]); ?>
            </div>
        </form>
    </div>

    <!-- Class Subjects List -->
    <?php if (empty($classSubjects)): ?>
        <?php $this->include('components/empty_state', [
            'title' => 'No Class-Subject Allocations',
            'message' => 'No class-subject allocations found for this session. Click "Assign Subject & Teacher" to create assignments.'
        ]); ?>
    <?php else: ?>
        <div class="bg-white border border-slate-200 rounded-xl overflow-hidden shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                    <thead class="bg-slate-50 text-slate-600 font-semibold uppercase text-xs tracking-wider">
                        <tr>
                            <th scope="col" class="px-6 py-3.5">Class / Cohort</th>
                            <th scope="col" class="px-6 py-3.5">Subject</th>
                            <th scope="col" class="px-6 py-3.5">Assigned Teacher</th>
                            <th scope="col" class="px-6 py-3.5">Status</th>
                            <th scope="col" class="px-6 py-3.5 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        <?php foreach ($classSubjects as $cs): ?>
                            <tr class="hover:bg-slate-50/60 transition-colors">
                                <td class="px-6 py-4 font-semibold text-slate-900">
                                    <div class="flex items-center gap-2">
                                        <span><?= e($cs->schoolClass?->name ?? 'Class #' . $cs->classId) ?></span>
                                        <?php if ($cs->schoolClass?->sectionArm): ?>
                                            <span class="inline-flex items-center px-2 py-0.5 rounded bg-brand-50 text-brand-700 font-mono text-xs font-semibold border border-brand-100">
                                                Arm <?= e($cs->schoolClass->sectionArm) ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-slate-800">
                                    <div class="font-medium text-slate-900"><?= e($cs->subject?->name ?? 'Subject #' . $cs->subjectId) ?></div>
                                    <div class="text-xs text-slate-500 font-mono font-bold mt-0.5"><?= e($cs->subject?->code ?? '') ?></div>
                                </td>
                                <td class="px-6 py-4 text-slate-700">
                                    <div class="font-medium text-slate-900"><?= e($cs->teacher?->user?->name ?? 'Teacher #' . $cs->teacherId) ?></div>
                                    <div class="text-xs text-slate-500 mt-0.5">Staff ID: <span class="font-mono font-semibold"><?= e($cs->teacher?->staffId ?? 'N/A') ?></span></div>
                                </td>
                                <td class="px-6 py-4">
                                    <?php if ($cs->isActive()): ?>
                                        <?php $this->include('components/badge', ['label' => 'Active', 'variant' => 'success']); ?>
                                    <?php else: ?>
                                        <?php $this->include('components/badge', ['label' => 'Inactive', 'variant' => 'neutral']); ?>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="inline-flex items-center gap-2 justify-end">
                                        <?php $this->include('components/button', [
                                            'type' => 'button',
                                            'variant' => 'secondary',
                                            'label' => 'Reassign Teacher',
                                            'class' => 'px-3 py-1.5 text-xs font-semibold',
                                            'attributes' => 'onclick="openReassignModal(' . $cs->id . ', ' . $cs->sessionId . ', ' . $cs->teacherId . ', \'' . e(addslashes($cs->subject?->name ?? '')) . '\', \'' . e(addslashes($cs->schoolClass?->name ?? '')) . '\')"'
                                        ]); ?>

                                        <form method="POST" action="/admin/class-subjects/<?= $cs->id ?>/status" class="inline">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="session_id" value="<?= $selectedSessionId ?>">
                                            <input type="hidden" name="status" value="<?= $cs->isActive() ? 'inactive' : 'active' ?>">
                                            <?php $this->include('components/button', [
                                                'type' => 'submit',
                                                'variant' => 'secondary',
                                                'label' => $cs->isActive() ? 'Deactivate' : 'Activate',
                                                'class' => 'px-3 py-1.5 text-xs font-semibold ' . ($cs->isActive()
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
        </div>
    <?php endif; ?>
</div>

<!-- Create Modal -->
<?php ob_start(); ?>
<form method="POST" action="/admin/class-subjects" class="space-y-4" novalidate>
    <?= csrf_field() ?>

    <?php $this->include('components/select', [
        'name' => 'session_id',
        'id' => 'create_session',
        'label' => 'Academic Session',
        'options' => $sessionOptions,
        'selected' => $selectedSessionId,
        'required' => true,
        'placeholder' => ''
    ]); ?>

    <?php $this->include('components/select', [
        'name' => 'class_id',
        'id' => 'create_class',
        'label' => 'Class / Arm',
        'options' => $createClassOptions,
        'selected' => $selectedClassId > 0 ? $selectedClassId : '',
        'required' => true,
        'placeholder' => ''
    ]); ?>

    <?php $this->include('components/select', [
        'name' => 'subject_id',
        'id' => 'create_subject',
        'label' => 'Subject',
        'options' => $subjectOptions,
        'selected' => '',
        'required' => true,
        'placeholder' => ''
    ]); ?>

    <?php $this->include('components/select', [
        'name' => 'teacher_id',
        'id' => 'create_teacher',
        'label' => 'Teacher',
        'options' => $teacherOptions,
        'selected' => '',
        'required' => true,
        'placeholder' => ''
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
            'label' => 'Assign'
        ]); ?>
    </div>
</form>
<?php $createModalBody = ob_get_clean(); ?>

<?php $this->include('components/modal', [
    'id' => 'create-modal',
    'title' => 'Assign Subject & Teacher',
    'body' => $createModalBody,
    'size' => 'md'
]); ?>

<!-- Reassign Modal -->
<?php ob_start(); ?>
<form id="reassign-form" method="POST" action="" class="space-y-4" novalidate>
    <?= csrf_field() ?>
    <input type="hidden" id="reassign_session_id" name="session_id" value="">

    <div>
        <p id="reassign_context" class="text-sm text-slate-600 bg-slate-50 p-3 rounded-lg border border-slate-200 font-medium"></p>
    </div>

    <?php $this->include('components/select', [
        'name' => 'teacher_id',
        'id' => 'reassign_teacher_id',
        'label' => 'New Teacher',
        'options' => $teacherOptions,
        'selected' => '',
        'required' => true,
        'placeholder' => ''
    ]); ?>

    <div class="pt-4 border-t border-slate-200 flex justify-end gap-3">
        <?php $this->include('components/button', [
            'type' => 'button',
            'variant' => 'secondary',
            'label' => 'Cancel',
            'attributes' => 'onclick="window.LMS.hideModal(\'reassign-modal\')"'
        ]); ?>
        <?php $this->include('components/button', [
            'type' => 'submit',
            'variant' => 'primary',
            'label' => 'Update Teacher'
        ]); ?>
    </div>
</form>
<?php $reassignModalBody = ob_get_clean(); ?>

<?php $this->include('components/modal', [
    'id' => 'reassign-modal',
    'title' => 'Reassign Teacher',
    'body' => $reassignModalBody,
    'size' => 'md'
]); ?>

<script>
    function openReassignModal(id, sessionId, currentTeacherId, subjectName, className) {
        document.getElementById('reassign-form').action = '/admin/class-subjects/' + id;
        document.getElementById('reassign_session_id').value = sessionId;
        document.getElementById('reassign_context').textContent = subjectName + ' — ' + className;
        document.getElementById('reassign_teacher_id').value = currentTeacherId;
        window.LMS.showModal('reassign-modal');
    }
</script>
