<?php
$this->layout('layouts/admin', [
    'title' => $title ?? 'Class Enrollments — Claret LMS',
    'headerTitle' => $headerTitle ?? 'Class Enrollments'
]);

// Build session options
$sessionOptions = [];
foreach ($sessions as $ses) {
    $sessionOptions[$ses->id] = e($ses->name) . ' (' . e(ucfirst($ses->status)) . ')';
}

// Build class options
$classOptions = [];
foreach ($classes as $c) {
    $classOptions[$c->id] = e($c->name) . ($c->sectionArm ? ' — ' . e($c->sectionArm) : '');
}

// Build status filter options
$statusOptions = [
    '' => 'All Statuses',
    'active' => 'Active',
    'promoted' => 'Promoted',
    'repeating' => 'Repeating',
    'transferred' => 'Transferred',
    'withdrawn' => 'Withdrawn',
];

// Build student options for modal
$studentOptions = ['' => 'Select Student...'];
foreach ($allStudents as $st) {
    $studentOptions[$st->id] = e($st->user?->name ?? 'Student #' . $st->id) . ' (' . e($st->admissionNumber) . ')';
}

// Enrollment status options for modal
$enrollStatusOptions = [
    'active' => 'Active',
    'promoted' => 'Promoted',
    'repeating' => 'Repeating',
];
?>
<div class="space-y-6">
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-slate-900">Class Roster &amp; Enrollments</h2>
            <p class="text-sm text-slate-500 mt-1">Manage student enrollment rosters and subject allocations per academic session.</p>
        </div>
        <?php if ($canManage ?? false): ?>
            <div>
                <?php $this->include('components/button', [
                    'type' => 'button',
                    'variant' => 'primary',
                    'label' => 'Enroll Student',
                    'icon' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>',
                    'attributes' => 'onclick="window.LMS.showModal(\'enroll-modal\')"'
                ]); ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Filter Bar: Session & Class Selector -->
    <form method="GET" action="/admin/enrollments" class="grid grid-cols-1 sm:grid-cols-3 gap-4 bg-slate-50 p-4 rounded-xl border border-slate-200">
        <?php $this->include('components/select', [
            'name' => 'session_id',
            'id' => 'filter_session_id',
            'label' => 'Academic Session',
            'options' => $sessionOptions,
            'selected' => $selectedSessionId,
            'attributes' => 'onchange="this.form.submit()"'
        ]); ?>

        <?php $this->include('components/select', [
            'name' => 'class_id',
            'id' => 'filter_class_id',
            'label' => 'Class / Arm',
            'options' => $classOptions,
            'selected' => $selectedClassId,
            'attributes' => 'onchange="this.form.submit()"'
        ]); ?>

        <?php $this->include('components/select', [
            'name' => 'status',
            'id' => 'filter_status',
            'label' => 'Status Filter',
            'options' => $statusOptions,
            'selected' => $selectedStatus ?? '',
            'attributes' => 'onchange="this.form.submit()"'
        ]); ?>
    </form>

    <!-- Roster Table -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/30">
            <h3 class="font-bold text-slate-900">Enrolled Students (<?= count($roster) ?>)</h3>
        </div>
        <?php if (empty($roster)): ?>
            <div class="p-6">
                <?php $this->include('components/empty_state', [
                    'title' => 'No Students Enrolled',
                    'message' => 'No students currently enrolled in this class for the selected session.'
                ]); ?>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                    <thead class="bg-slate-50 text-slate-600 font-semibold uppercase text-xs tracking-wider">
                        <tr>
                            <th class="px-6 py-3.5">Student</th>
                            <th class="px-6 py-3.5">Admission No.</th>
                            <th class="px-6 py-3.5">Enrolled Date</th>
                            <th class="px-6 py-3.5">Status</th>
                            <?php if ($canManage ?? false): ?>
                                <th class="px-6 py-3.5 text-right">Actions</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 font-medium text-slate-700">
                        <?php foreach ($roster as $enr): ?>
                            <tr class="hover:bg-slate-50/50 transition">
                                <td class="px-6 py-4">
                                    <div class="font-bold text-slate-900"><?= e($enr->student?->user?->name ?? '—') ?></div>
                                    <div class="text-xs text-slate-500 font-normal"><?= e($enr->student?->user?->email ?? '—') ?></div>
                                </td>
                                <td class="px-6 py-4 font-mono text-xs font-semibold text-slate-800">
                                    <?= e($enr->student?->admissionNumber ?? '—') ?>
                                </td>
                                <td class="px-6 py-4 text-xs text-slate-500 font-normal">
                                    <?= e($enr->enrolledAt ? date('M j, Y', strtotime($enr->enrolledAt)) : '—') ?>
                                </td>
                                <td class="px-6 py-4">
                                    <?php
                                    $badgeVariant = 'neutral';
                                    if ($enr->status === 'active') {
                                        $badgeVariant = 'success';
                                    } elseif ($enr->status === 'withdrawn') {
                                        $badgeVariant = 'danger';
                                    } elseif ($enr->status === 'repeating') {
                                        $badgeVariant = 'warning';
                                    }
                                    $this->include('components/badge', [
                                        'label' => ucfirst($enr->status),
                                        'variant' => $badgeVariant
                                    ]);
                                    ?>
                                </td>
                                <?php if ($canManage ?? false): ?>
                                    <td class="px-6 py-4 text-right">
                                        <form method="POST" action="/admin/enrollments/<?= e($enr->id) ?>/status" class="inline-flex items-center gap-1">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="session_id" value="<?= e($selectedSessionId) ?>">
                                            <input type="hidden" name="class_id" value="<?= e($selectedClassId) ?>">
                                            <select name="status" onchange="this.form.submit()" class="text-xs border border-slate-200 rounded-lg px-2 py-1 bg-slate-50 hover:bg-slate-100 font-semibold text-slate-700 focus:outline-none focus:ring-2 focus:ring-brand-500 transition cursor-pointer">
                                                <option value="active" <?= $enr->status === 'active' ? 'selected' : '' ?>>Active</option>
                                                <option value="promoted" <?= $enr->status === 'promoted' ? 'selected' : '' ?>>Promoted</option>
                                                <option value="repeating" <?= $enr->status === 'repeating' ? 'selected' : '' ?>>Repeating</option>
                                                <option value="transferred" <?= $enr->status === 'transferred' ? 'selected' : '' ?>>Transferred</option>
                                                <option value="withdrawn" <?= $enr->status === 'withdrawn' ? 'selected' : '' ?>>Withdrawn</option>
                                            </select>
                                        </form>
                                    </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Enroll Student Modal Body -->
<?php ob_start(); ?>
<form method="POST" action="/admin/enrollments" class="space-y-4" novalidate>
    <?= csrf_field() ?>
    <input type="hidden" name="session_id" value="<?= e($selectedSessionId) ?>">
    <input type="hidden" name="class_id" value="<?= e($selectedClassId) ?>">

    <?php $this->include('components/select', [
        'name' => 'student_id',
        'id' => 'modal_student_id',
        'label' => 'Select Student',
        'options' => $studentOptions,
        'selected' => '',
        'required' => true,
        'placeholder' => ''
    ]); ?>

    <?php $this->include('components/select', [
        'name' => 'status',
        'id' => 'modal_status',
        'label' => 'Enrollment Status',
        'options' => $enrollStatusOptions,
        'selected' => 'active',
        'required' => true,
        'placeholder' => ''
    ]); ?>

    <div class="pt-4 border-t border-slate-200 flex justify-end gap-3">
        <?php $this->include('components/button', [
            'type' => 'button',
            'variant' => 'secondary',
            'label' => 'Cancel',
            'attributes' => 'onclick="window.LMS.hideModal(\'enroll-modal\')"'
        ]); ?>
        <?php $this->include('components/button', [
            'type' => 'submit',
            'variant' => 'primary',
            'label' => 'Enroll Student'
        ]); ?>
    </div>
</form>
<?php $modalBody = ob_get_clean(); ?>

<?php $this->include('components/modal', [
    'id' => 'enroll-modal',
    'title' => 'Enroll Student',
    'body' => $modalBody,
    'size' => 'md'
]); ?>
