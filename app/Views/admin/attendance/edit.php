<?php
/**
 * ADMIN-18 — Attendance Editor
 *
 * Layout is injected by the controller:
 *   Response::html($this->render('admin/attendance/edit', $data, 'layouts/admin'))
 * Do NOT call $this->layout() here.
 *
 * Variables from controller:
 *   $class          — class object (->id, ->name)
 *   $date           — string 'Y-m-d'
 *   $classSubjectId — int|null
 *   $periodNumber   — int|null
 *   $roster         — array of rows: student_id, student_name, admission_number,
 *                     attendance_id, status, is_recorded, correction_reason
 *   $csrf_token     — string, raw CSRF token value from session
 *
 * Attendance statuses: present | absent | late | excused
 */

$statusConfig = [
    'present' => ['label' => 'Present', 'variant' => 'success'],
    'absent'  => ['label' => 'Absent',  'variant' => 'danger'],
    'late'    => ['label' => 'Late',    'variant' => 'warning'],
    'excused' => ['label' => 'Excused', 'variant' => 'info'],
];

// Build the date-picker navigation URL base (preserves query params)
$dateNavBase = '/admin/attendance/' . (int)$class->id . '/';
$dateNavSuffix = '/edit' . ($classSubjectId ? '?class_subject_id=' . (int)$classSubjectId : '');
?>

<div class="max-w-5xl mx-auto space-y-6">

    <!-- Top Navigation Bar -->
    <div class="flex items-center justify-between gap-3">
        <?php $this->include('components/button', [
            'href'    => '/admin/attendance',
            'variant' => 'quiet',
            'label'   => 'Back to Overview',
            'icon'    => '<svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>',
        ]); ?>

        <?php $this->include('components/badge', [
            'label'   => 'Administrative Audit Mode',
            'variant' => 'warning',
        ]); ?>
    </div>

    <!-- Context Header Card -->
    <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h1 class="text-xl font-bold text-slate-900">
                    <?= e($class->name) ?> &mdash; Attendance Entry
                </h1>
                <p class="text-sm text-slate-500 mt-1">
                    Inspection &amp; Historical Correction Panel
                </p>
            </div>

            <!-- Date Picker — preserves existing JS navigation hook -->
            <div class="flex items-center gap-2.5 flex-shrink-0">
                <label for="adminDateSelect"
                       class="text-xs font-semibold uppercase tracking-wider text-slate-500">
                    Date:
                </label>
                <input type="date"
                       id="adminDateSelect"
                       value="<?= e($date) ?>"
                       onchange="window.location.href='<?= $dateNavBase ?>' + this.value + '<?= e($dateNavSuffix) ?>'"
                       class="px-3 py-2 border border-slate-300 rounded-lg text-sm
                              focus:ring-2 focus:ring-brand-500 focus:border-brand-500
                              outline-none transition">
            </div>
        </div>
    </div>

    <!-- Attendance Form -->
    <form method="POST"
          action="/admin/attendance/<?= (int)$class->id ?>/<?= e($date) ?>/edit"
          class="space-y-0">

        <!-- CSRF: controller passes raw token as $csrf_token; field name _csrf_token is what CsrfMiddleware validates -->
        <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

        <?php if ($classSubjectId): ?>
            <input type="hidden" name="class_subject_id" value="<?= (int)$classSubjectId ?>">
        <?php endif; ?>
        <?php if ($periodNumber): ?>
            <input type="hidden" name="period_number" value="<?= (int)$periodNumber ?>">
        <?php endif; ?>

        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">

            <!-- Roster Header -->
            <div class="px-5 py-3.5 bg-slate-50 border-b border-slate-200
                        flex items-center justify-between gap-3">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-600">
                    Student Roster
                </span>
                <?php $this->include('components/badge', [
                    'label'   => count($roster) . ' Enrolled',
                    'variant' => 'neutral',
                ]); ?>
            </div>

            <!-- Roster Rows -->
            <?php if (empty($roster)): ?>
                <div class="p-8">
                    <?php $this->include('components/empty_state', [
                        'title'   => 'No Students Enrolled',
                        'message' => 'No students are currently enrolled in this class. Enrol students via Class Enrollments before taking attendance.',
                        'actionUrl'   => '/admin/enrollments',
                        'actionLabel' => 'Manage Enrollments',
                    ]); ?>
                </div>
            <?php else: ?>
                <div class="divide-y divide-slate-100">
                    <?php foreach ($roster as $index => $row): ?>
                        <?php
                            $currentStatus = $row['status'];
                            $isRecorded    = $row['is_recorded'];
                        ?>
                        <div class="px-5 py-4 flex flex-col sm:flex-row sm:items-center
                                    justify-between gap-4 hover:bg-slate-50/60 transition">

                            <!-- Student identity -->
                            <div class="flex items-center gap-3 min-w-0">
                                <span class="flex-shrink-0 w-8 h-8 rounded-full
                                             bg-slate-100 text-slate-600
                                             text-xs font-bold flex items-center justify-center
                                             border border-slate-200">
                                    <?= $index + 1 ?>
                                </span>
                                <div class="min-w-0">
                                    <div class="font-semibold text-slate-900 truncate">
                                        <?= e($row['student_name']) ?>
                                    </div>
                                    <div class="text-xs text-slate-400 font-mono mt-0.5">
                                        Adm: <?= e($row['admission_number']) ?>
                                        <?php if ($isRecorded): ?>
                                            &nbsp;&middot;&nbsp;
                                            <?php $this->include('components/badge', [
                                                'label'   => 'Recorded',
                                                'variant' => 'success',
                                                'class'   => 'text-[10px]',
                                            ]); ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <!-- Status radio pill group — 4 statuses: present|absent|late|excused -->
                            <div class="flex flex-wrap items-center gap-2">
                                <?php foreach ($statusConfig as $stKey => $stConf):
                                    $isChecked = ($currentStatus === $stKey);
                                    $checkedBorder = match($stKey) {
                                        'present' => 'border-success-500 bg-success-50 text-success-700',
                                        'absent'  => 'border-danger-500 bg-danger-50 text-danger-700',
                                        'late'    => 'border-amber-500 bg-amber-50 text-amber-700',
                                        'excused' => 'border-info-500 bg-info-50 text-info-700',
                                        default   => 'border-brand-500 bg-brand-50 text-brand-700',
                                    };
                                    $uncheckedBorder = 'border-slate-200 text-slate-600 hover:bg-slate-50 hover:border-slate-300';
                                ?>
                                    <label class="inline-flex items-center gap-1.5 px-3 py-1.5
                                                  border rounded-lg text-xs font-medium cursor-pointer transition
                                                  <?= $isChecked ? $checkedBorder : $uncheckedBorder ?>">
                                        <input type="radio"
                                               name="status[<?= (int)$row['student_id'] ?>]"
                                               value="<?= e($stKey) ?>"
                                               <?= $isChecked ? 'checked' : '' ?>
                                               class="sr-only">
                                        <?= e($stConf['label']) ?>
                                    </label>
                                <?php endforeach; ?>
                            </div>

                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Audit Justification & Submit -->
            <div class="p-6 bg-amber-50/60 border-t border-amber-200 space-y-4">
                <div>
                    <label for="correction_reason"
                           class="block text-sm font-bold text-slate-900 mb-1">
                        Correction / Audit Justification
                        <span class="text-danger-600 ml-0.5">*</span>
                    </label>
                    <textarea name="correction_reason"
                              id="correction_reason"
                              rows="3"
                              required
                              placeholder="Mandatory reason for administrative attendance modification or historical override (e.g. Medical certificate verified by registrar)..."
                              class="w-full px-4 py-2.5 border border-amber-300 rounded-lg
                                     text-sm bg-white resize-y
                                     focus:ring-2 focus:ring-brand-500 focus:border-brand-500
                                     outline-none transition placeholder-slate-400"></textarea>
                    <p class="text-xs text-slate-500 mt-1.5 leading-relaxed">
                        This explanation will be permanently recorded in the system audit log
                        and appended to the attendance record.
                    </p>
                </div>

                <div class="flex flex-col sm:flex-row sm:items-center
                            justify-between gap-3 pt-1">
                    <p class="text-xs text-amber-700 font-semibold">
                        ⚠ Changes are irreversible once saved and will trigger an audit event.
                    </p>
                    <?php $this->include('components/button', [
                        'type'    => 'submit',
                        'variant' => 'primary',
                        'label'   => 'Save Changes & Log Audit Trail',
                        'icon'    => '<svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
                    ]); ?>
                </div>
            </div>

        </div>
    </form>

</div>
