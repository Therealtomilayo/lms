<?php
$this->layout('layouts/admin', [
    'title' => $title ?? "Edit {$user->name} — Claret LMS",
    'headerTitle' => $headerTitle ?? "Edit User: {$user->name}"
]);

$errors = $errors ?? [];

$canChangeStatus = \App\Policies\UserPolicy::canChangeUserStatus($actor, $user);
$statusAttributes = $canChangeStatus ? '' : 'disabled';
$statusHelp = $canChangeStatus ? '' : 'You cannot change the status of your own account or a super administrator.';

$selectedRoles = old('roles', $user->roles);
if (!is_array($selectedRoles)) {
    $selectedRoles = [$selectedRoles];
}
$isStudentSelected = in_array('student', $selectedRoles, true);

$studentAdm = $student ? $student->admissionNumber : '';
$teacherStaff = $teacher ? $teacher->staffId : '';
$studentGender = $student ? $student->gender : '';
$studentClassId = $student ? $student->currentClassId : null;
?>

<div class="max-w-4xl mx-auto space-y-6">
    <!-- Page Header & Back Link -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-slate-900">Edit User: <?= e($user->name) ?></h2>
            <p class="text-sm text-slate-500 mt-1">Update contact info, role allocations, profile metadata, or manage security credentials.</p>
        </div>
        <div>
            <?php $this->include('components/button', [
                'variant' => 'secondary',
                'label' => 'Back to Directory',
                'href' => '/admin/users',
                'icon' => '<svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>'
            ]); ?>
        </div>
    </div>

    <!-- General Error Banner -->
    <?php if (!empty($errors['general'])): ?>
        <div class="rounded-2xl bg-red-50/80 border border-red-200 p-4 text-sm text-red-700 flex items-start gap-3">
            <svg class="w-5 h-5 text-red-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <div>
                <p class="font-semibold text-red-900">Please review the form errors</p>
                <p class="text-xs text-red-700 mt-0.5"><?= e($errors['general'][0]) ?></p>
            </div>
        </div>
    <?php endif; ?>

    <!-- Main Update Form -->
    <form method="POST" action="/admin/users/<?= e($user->id) ?>" class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 sm:p-8 space-y-8" novalidate id="edit-user-form"
          data-suggested-adm="<?= e($suggestedAdmissionNumber ?? '') ?>"
          data-suggested-staff="<?= e($suggestedStaffId ?? '') ?>">
        <?= csrf_field() ?>

        <!-- 1. Role Allocations (First for UX) -->
        <div class="space-y-5 border-b border-slate-200 pb-6">
            <div>
                <h3 class="text-base font-bold text-slate-900">1. Role Allocations <span class="text-brand-600">*</span></h3>
                <p class="text-xs text-slate-500 mt-0.5">Select user permission roles. <span class="text-amber-700 font-medium">Note: Student accounts are mutually exclusive with staff and parent roles.</span></p>
                <?php if (!empty($errors['roles'])): ?>
                    <p class="mt-1.5 text-xs text-red-600 font-medium flex items-center gap-1">
                        <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span><?= e($errors['roles'][0]) ?></span>
                    </p>
                <?php endif; ?>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <?php if ($actor->hasRole('super_admin')): ?>
                    <label class="flex items-center gap-3 p-3.5 border <?= in_array('super_admin', $selectedRoles, true) ? 'border-brand-500 bg-brand-50/20' : 'border-slate-200' ?> rounded-xl cursor-pointer hover:bg-slate-50/80 transition">
                        <input type="checkbox" name="roles[]" value="super_admin" <?= in_array('super_admin', $selectedRoles, true) ? 'checked' : '' ?> class="w-4 h-4 rounded text-brand-600 focus:ring-brand-500 border-slate-300">
                        <div>
                            <span class="text-sm font-semibold text-slate-900 block">Super Admin</span>
                            <span class="text-xs text-slate-500 block">Full system access</span>
                        </div>
                    </label>
                <?php endif; ?>

                <label class="flex items-center gap-3 p-3.5 border <?= in_array('admin', $selectedRoles, true) ? 'border-brand-500 bg-brand-50/20' : 'border-slate-200' ?> rounded-xl cursor-pointer hover:bg-slate-50/80 transition">
                    <input type="checkbox" name="roles[]" value="admin" <?= in_array('admin', $selectedRoles, true) ? 'checked' : '' ?> class="w-4 h-4 rounded text-brand-600 focus:ring-brand-500 border-slate-300">
                    <div>
                        <span class="text-sm font-semibold text-slate-900 block">Admin</span>
                        <span class="text-xs text-slate-500 block">Academic management</span>
                    </div>
                </label>

                <label class="flex items-center gap-3 p-3.5 border <?= in_array('teacher', $selectedRoles, true) ? 'border-brand-500 bg-brand-50/20' : 'border-slate-200' ?> rounded-xl cursor-pointer hover:bg-slate-50/80 transition">
                    <input type="checkbox" name="roles[]" value="teacher" <?= in_array('teacher', $selectedRoles, true) ? 'checked' : '' ?> class="w-4 h-4 rounded text-brand-600 focus:ring-brand-500 border-slate-300">
                    <div>
                        <span class="text-sm font-semibold text-slate-900 block">Teacher</span>
                        <span class="text-xs text-slate-500 block">Grading & attendance</span>
                    </div>
                </label>

                <label class="flex items-center gap-3 p-3.5 border <?= in_array('student', $selectedRoles, true) ? 'border-brand-500 bg-brand-50/20' : 'border-slate-200' ?> rounded-xl cursor-pointer hover:bg-slate-50/80 transition">
                    <input type="checkbox" name="roles[]" value="student" <?= in_array('student', $selectedRoles, true) ? 'checked' : '' ?> class="w-4 h-4 rounded text-brand-600 focus:ring-brand-500 border-slate-300">
                    <div>
                        <span class="text-sm font-semibold text-slate-900 block">Student</span>
                        <span class="text-xs text-slate-500 block">Coursework & results</span>
                    </div>
                </label>

                <label class="flex items-center gap-3 p-3.5 border <?= in_array('parent', $selectedRoles, true) ? 'border-brand-500 bg-brand-50/20' : 'border-slate-200' ?> rounded-xl cursor-pointer hover:bg-slate-50/80 transition">
                    <input type="checkbox" name="roles[]" value="parent" <?= in_array('parent', $selectedRoles, true) ? 'checked' : '' ?> class="w-4 h-4 rounded text-brand-600 focus:ring-brand-500 border-slate-300">
                    <div>
                        <span class="text-sm font-semibold text-slate-900 block">Parent / Guardian</span>
                        <span class="text-xs text-slate-500 block">Child progress tracking</span>
                    </div>
                </label>
            </div>

            <!-- Current Class (Students) - Moved inside Role Allocation, displayed/required ONLY if student role is selected -->
            <div id="current_class_container" class="<?= $isStudentSelected ? '' : 'hidden' ?> p-4 bg-slate-50 border border-slate-200 rounded-xl space-y-2">
                <label for="user_current_class_id" class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                    Current Class Placement <span class="text-red-600">*</span>
                </label>
                <p class="text-xs text-slate-500">Assigning or changing a class automatically enrolls the student into its active subjects.</p>
                <select name="current_class_id" id="user_current_class_id" <?= $isStudentSelected ? 'required' : '' ?>
                        class="w-full px-3.5 py-2.5 text-sm rounded-lg border <?= !empty($errors['current_class_id']) ? 'border-red-400 bg-red-50/30' : 'border-slate-300 bg-white' ?> text-slate-800 shadow-xs focus:ring-2 focus:ring-brand-500 focus:outline-hidden">
                    <option value="">-- Select Class Arm / Track --</option>
                    <?php foreach ($classes as $c): ?>
                        <option value="<?= (int)$c->id ?>" <?= (string)old('current_class_id', $studentClassId ?? '') === (string)$c->id ? 'selected' : '' ?>>
                            <?= e($c->name) ?><?= !empty($c->sectionArm) ? ' — ' . e($c->sectionArm) : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (!empty($errors['current_class_id'])): ?>
                    <p class="mt-1 text-xs text-red-600 font-medium flex items-center gap-1">
                        <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span><?= e($errors['current_class_id'][0]) ?></span>
                    </p>
                <?php endif; ?>
            </div>
        </div>

        <!-- 2. Account Information -->
        <div class="space-y-4 border-b border-slate-200 pb-6">
            <div>
                <h3 class="text-base font-bold text-slate-900">2. Account Information</h3>
                <p class="text-xs text-slate-500 mt-0.5">Contact details and active account status.</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <!-- Full Name -->
                <div>
                    <label for="user_name" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                        Full Name <span class="text-red-600">*</span>
                    </label>
                    <input type="text" 
                           id="user_name" 
                           name="name" 
                           value="<?= e(old('name', $user->name)) ?>" 
                           required 
                           placeholder="e.g. John Doe"
                           class="w-full px-3.5 py-2.5 text-sm rounded-lg border <?= !empty($errors['name']) ? 'border-red-400 bg-red-50/30' : 'border-slate-300 bg-white' ?> text-slate-800 shadow-xs focus:ring-2 focus:ring-brand-500 focus:outline-hidden transition">
                    <?php if (!empty($errors['name'])): ?>
                        <p class="mt-1 text-xs text-red-600 font-medium flex items-center gap-1">
                            <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <span><?= e($errors['name'][0]) ?></span>
                        </p>
                    <?php endif; ?>
                </div>

                <!-- Gender Select -->
                <div>
                    <label for="user_gender" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                        Gender <span class="text-red-600">*</span>
                    </label>
                    <select name="gender" id="user_gender" required
                            class="w-full px-3.5 py-2.5 text-sm rounded-lg border <?= !empty($errors['gender']) ? 'border-red-400 bg-red-50/30' : 'border-slate-300 bg-white' ?> text-slate-800 shadow-xs focus:ring-2 focus:ring-brand-500 focus:outline-hidden transition">
                        <option value="">-- Select Gender --</option>
                        <option value="male" <?= old('gender', $studentGender) === 'male' ? 'selected' : '' ?>>Male</option>
                        <option value="female" <?= old('gender', $studentGender) === 'female' ? 'selected' : '' ?>>Female</option>
                    </select>
                    <?php if (!empty($errors['gender'])): ?>
                        <p class="mt-1 text-xs text-red-600 font-medium flex items-center gap-1">
                            <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <span><?= e($errors['gender'][0]) ?></span>
                        </p>
                    <?php endif; ?>
                </div>

                <!-- Email Address -->
                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label for="user_email" class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                            Email Address 
                            <span id="email_required_marker" class="text-red-600 <?= $isStudentSelected ? 'hidden' : '' ?>">*</span>
                            <span id="email_optional_marker" class="text-slate-400 font-normal text-xs lowercase <?= $isStudentSelected ? '' : 'hidden' ?>">(optional for students)</span>
                        </label>
                    </div>
                    <input type="email" 
                           id="user_email" 
                           name="email" 
                           value="<?= e(old('email', $user->email)) ?>" 
                           <?= $isStudentSelected ? '' : 'required' ?>
                           placeholder="e.g. user@claret.edu"
                           class="w-full px-3.5 py-2.5 text-sm rounded-lg border <?= !empty($errors['email']) ? 'border-red-400 bg-red-50/30' : 'border-slate-300 bg-white' ?> text-slate-800 shadow-xs focus:ring-2 focus:ring-brand-500 focus:outline-hidden transition">
                    <?php if (!empty($errors['email'])): ?>
                        <p class="mt-1 text-xs text-red-600 font-medium flex items-center gap-1">
                            <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <span><?= e($errors['email'][0]) ?></span>
                        </p>
                    <?php endif; ?>
                </div>

                <!-- Phone Number -->
                <div>
                    <label for="user_phone" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                        Phone Number
                    </label>
                    <input type="tel" 
                           id="user_phone" 
                           name="phone" 
                           value="<?= e(old('phone', $user->phone ?? '')) ?>" 
                           placeholder="e.g. +234 801 234 5678"
                           class="w-full px-3.5 py-2.5 text-sm rounded-lg border <?= !empty($errors['phone']) ? 'border-red-400 bg-red-50/30' : 'border-slate-300 bg-white' ?> text-slate-800 shadow-xs focus:ring-2 focus:ring-brand-500 focus:outline-hidden transition">
                    <?php if (!empty($errors['phone'])): ?>
                        <p class="mt-1 text-xs text-red-600 font-medium flex items-center gap-1">
                            <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <span><?= e($errors['phone'][0]) ?></span>
                        </p>
                    <?php endif; ?>
                </div>

                <!-- Account Status -->
                <div class="sm:col-span-2">
                    <label for="user_status" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                        Account Status
                    </label>
                    <select name="status" id="user_status" <?= $statusAttributes ?>
                            class="w-full px-3.5 py-2.5 text-sm rounded-lg border <?= !empty($errors['status']) ? 'border-red-400 bg-red-50/30' : 'border-slate-300 bg-white' ?> text-slate-800 shadow-xs focus:ring-2 focus:ring-brand-500 focus:outline-hidden transition">
                        <option value="active" <?= old('status', $user->status) === 'active' ? 'selected' : '' ?>>Active</option>
                        <option value="inactive" <?= old('status', $user->status) === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                        <option value="suspended" <?= old('status', $user->status) === 'suspended' ? 'selected' : '' ?>>Suspended</option>
                    </select>
                    <?php if (!empty($statusHelp)): ?>
                        <p class="text-[11px] text-slate-500 mt-1"><?= e($statusHelp) ?></p>
                    <?php endif; ?>
                    <?php if (!empty($errors['status'])): ?>
                        <p class="mt-1 text-xs text-red-600 font-medium flex items-center gap-1">
                            <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <span><?= e($errors['status'][0]) ?></span>
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- 3. Specialized Profile Metadata -->
        <div class="space-y-4 border-b border-slate-200 pb-6">
            <div>
                <h3 class="text-base font-bold text-slate-900">3. Specialized Profile Metadata</h3>
                <p class="text-xs text-slate-500 mt-0.5">Role-specific identifiers associated with this user account.</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <!-- Student Admission Number -->
                <div id="student_adm_field" class="<?= $isStudentSelected ? '' : 'opacity-60' ?>">
                    <div class="flex items-center justify-between mb-1.5">
                        <label for="user_admission_number" class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                            Student Admission No.
                        </label>
                        <span class="text-[11px] font-semibold text-brand-600 bg-brand-50 px-2 py-0.5 rounded">Admission Identifier</span>
                    </div>
                    <input type="text" 
                           name="admission_number" 
                           id="user_admission_number" 
                           value="<?= e(old('admission_number', $studentAdm ?: ($isStudentSelected ? $suggestedAdmissionNumber : ''))) ?>" 
                           placeholder="e.g. STD-00001"
                           class="w-full px-3.5 py-2.5 text-sm rounded-lg border <?= !empty($errors['admission_number']) ? 'border-red-400 bg-red-50/30' : 'border-slate-300 bg-white' ?> text-slate-800 shadow-xs focus:ring-2 focus:ring-brand-500 focus:outline-hidden transition">
                    <p class="text-[11px] text-slate-500 mt-1">Students can use this unique identifier to log in directly.</p>
                    <?php if (!empty($errors['admission_number'])): ?>
                        <p class="mt-1 text-xs text-red-600 font-medium flex items-center gap-1">
                            <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <span><?= e($errors['admission_number'][0]) ?></span>
                        </p>
                    <?php endif; ?>
                </div>

                <!-- Teacher Staff ID -->
                <div id="teacher_staff_field" class="<?= in_array('teacher', $selectedRoles, true) ? '' : 'opacity-60' ?>">
                    <div class="flex items-center justify-between mb-1.5">
                        <label for="user_staff_id" class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                            Teacher Staff ID
                        </label>
                        <span class="text-[11px] font-semibold text-brand-600 bg-brand-50 px-2 py-0.5 rounded">Staff Identifier</span>
                    </div>
                    <input type="text" 
                           name="staff_id" 
                           id="user_staff_id" 
                           value="<?= e(old('staff_id', $teacherStaff ?: (in_array('teacher', $selectedRoles, true) ? $suggestedStaffId : ''))) ?>" 
                           placeholder="e.g. TCH-0001"
                           class="w-full px-3.5 py-2.5 text-sm rounded-lg border <?= !empty($errors['staff_id']) ? 'border-red-400 bg-red-50/30' : 'border-slate-300 bg-white' ?> text-slate-800 shadow-xs focus:ring-2 focus:ring-brand-500 focus:outline-hidden transition">
                    <p class="text-[11px] text-slate-500 mt-1">Institutional staff number for academic assignments.</p>
                    <?php if (!empty($errors['staff_id'])): ?>
                        <p class="mt-1 text-xs text-red-600 font-medium flex items-center gap-1">
                            <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <span><?= e($errors['staff_id'][0]) ?></span>
                        </p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Student Demographics & Institutional Records -->
            <div id="student_demographics_container" class="<?= $isStudentSelected ? '' : 'hidden' ?> p-4 bg-slate-50 border border-slate-200 rounded-xl space-y-4">
                <div>
                    <h4 class="text-xs font-bold uppercase tracking-wider text-slate-700">Student Demographics & Origin</h4>
                    <p class="text-[11px] text-slate-500">Official demographic data for statutory records, transcripts, and terminal report cards.</p>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label for="user_date_of_birth" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">Date of Birth</label>
                        <input type="date" name="date_of_birth" id="user_date_of_birth"
                               value="<?= e(old('date_of_birth', $student?->dateOfBirth ?? '')) ?>"
                               class="w-full px-3.5 py-2 text-sm rounded-lg border border-slate-300 bg-white text-slate-800 shadow-xs focus:ring-2 focus:ring-brand-500 focus:outline-hidden">
                    </div>
                    <div>
                        <label for="user_admission_date" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">Admission Date</label>
                        <input type="date" name="admission_date" id="user_admission_date"
                               value="<?= e(old('admission_date', $student?->admissionDate ?? '')) ?>"
                               class="w-full px-3.5 py-2 text-sm rounded-lg border border-slate-300 bg-white text-slate-800 shadow-xs focus:ring-2 focus:ring-brand-500 focus:outline-hidden">
                    </div>
                    <div>
                        <label for="user_nationality" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">Nationality</label>
                        <input type="text" name="nationality" id="user_nationality"
                               value="<?= e(old('nationality', $student?->nationality ?? 'Nigerian')) ?>"
                               placeholder="e.g. Nigerian"
                               class="w-full px-3.5 py-2 text-sm rounded-lg border border-slate-300 bg-white text-slate-800 shadow-xs focus:ring-2 focus:ring-brand-500 focus:outline-hidden">
                    </div>
                    <div>
                        <label for="user_state_of_origin" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">State of Origin</label>
                        <input type="text" name="state_of_origin" id="user_state_of_origin"
                               value="<?= e(old('state_of_origin', $student?->stateOfOrigin ?? '')) ?>"
                               placeholder="e.g. Imo, Lagos, Abuja FCT"
                               class="w-full px-3.5 py-2 text-sm rounded-lg border border-slate-300 bg-white text-slate-800 shadow-xs focus:ring-2 focus:ring-brand-500 focus:outline-hidden">
                    </div>
                    <div>
                        <label for="user_lga" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">L.G.A.</label>
                        <input type="text" name="lga" id="user_lga"
                               value="<?= e(old('lga', $student?->lga ?? '')) ?>"
                               placeholder="e.g. Owerri Municipal, Ikeja"
                               class="w-full px-3.5 py-2 text-sm rounded-lg border border-slate-300 bg-white text-slate-800 shadow-xs focus:ring-2 focus:ring-brand-500 focus:outline-hidden">
                    </div>
                    <div>
                        <label for="user_religion" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">Religion</label>
                        <select name="religion" id="user_religion"
                                class="w-full px-3.5 py-2 text-sm rounded-lg border border-slate-300 bg-white text-slate-800 shadow-xs focus:ring-2 focus:ring-brand-500 focus:outline-hidden">
                            <?php $currRel = old('religion', $student?->religion ?? ''); ?>
                            <option value="">-- Select Religion --</option>
                            <option value="Christianity" <?= $currRel === 'Christianity' ? 'selected' : '' ?>>Christianity</option>
                            <option value="Islam" <?= $currRel === 'Islam' ? 'selected' : '' ?>>Islam</option>
                            <option value="Other" <?= $currRel === 'Other' ? 'selected' : '' ?>>Other</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- Form Actions -->
        <div class="flex items-center justify-end gap-3 pt-2">
            <?php $this->include('components/button', [
                'variant' => 'secondary',
                'label' => 'Cancel',
                'href' => '/admin/users'
            ]); ?>

            <?php $this->include('components/button', [
                'type' => 'submit',
                'variant' => 'primary',
                'label' => 'Save Changes'
            ]); ?>
        </div>
    </form>

    <!-- Administrative Password Reset -->
    <?php if (\App\Policies\UserPolicy::canResetUserPassword($actor, $user)): ?>
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 sm:p-8 space-y-6">
            <div>
                <h3 class="text-base font-bold text-slate-900">Administrative Password Reset</h3>
                <p class="text-xs text-slate-500 mt-0.5">Assign a new password and immediately revoke all active browser sessions for this user.</p>
            </div>

            <form method="POST" action="/admin/users/<?= e($user->id) ?>/reset-password" class="flex flex-col sm:flex-row items-end gap-4" novalidate>
                <?= csrf_field() ?>
                <div class="w-full sm:w-80">
                    <label for="reset_password" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                        New Temporary Password <span class="text-red-600">*</span>
                    </label>
                    <div class="relative w-full">
                        <input type="password" 
                               name="password" 
                               id="reset_password" 
                               required 
                               placeholder="Minimum 8 characters"
                               class="w-full pl-3.5 pr-11 py-2.5 text-sm rounded-lg border <?= !empty($errors['password']) ? 'border-red-400 bg-red-50/30' : 'border-slate-300 bg-white' ?> text-slate-800 shadow-xs focus:ring-2 focus:ring-brand-500 focus:outline-hidden transition">
                        <button type="button" 
                                class="lms-password-toggle-btn absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-[#7B3046] focus:outline-none cursor-pointer" 
                                aria-label="Toggle password visibility">
                            <svg class="eye-open w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            <svg class="eye-closed w-4 h-4 hidden text-[#7B3046]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"/></svg>
                        </button>
                    </div>
                    <?php if (!empty($errors['password'])): ?>
                        <p class="mt-1 text-xs text-red-600 font-medium flex items-center gap-1">
                            <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <span><?= e($errors['password'][0]) ?></span>
                        </p>
                    <?php endif; ?>
                </div>

                <?php $this->include('components/button', [
                    'type' => 'submit',
                    'variant' => 'primary',
                    'label' => 'Reset Password & Revoke Sessions',
                    'class' => 'h-[44px]'
                ]); ?>
            </form>
        </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('edit-user-form');
    if (!form) return;

    const suggestedAdm = form.dataset.suggestedAdm || '';
    const suggestedStaff = form.dataset.suggestedStaff || '';

    const roleInputs = Array.from(document.querySelectorAll('input[name="roles[]"]'));
    const studentInput = roleInputs.find(i => i.value === 'student');
    const teacherInput = roleInputs.find(i => i.value === 'teacher');
    const otherInputs = roleInputs.filter(i => i.value !== 'student');

    const classContainer = document.getElementById('current_class_container');
    const classSelect = document.getElementById('user_current_class_id');
    const emailInput = document.getElementById('user_email');
    const emailReqMarker = document.getElementById('email_required_marker');
    const emailOptMarker = document.getElementById('email_optional_marker');

    const admFieldWrapper = document.getElementById('student_adm_field');
    const admInput = document.getElementById('user_admission_number');
    const demoContainer = document.getElementById('student_demographics_container');
    const staffFieldWrapper = document.getElementById('teacher_staff_field');
    const staffInput = document.getElementById('user_staff_id');

    function syncRolesAndFields() {
        const isStudent = studentInput && studentInput.checked;
        const isTeacher = teacherInput && teacherInput.checked;

        if (demoContainer) {
            if (isStudent) {
                demoContainer.classList.remove('hidden');
            } else {
                demoContainer.classList.add('hidden');
            }
        }

        // Current Class visibility & requirement
        if (classContainer && classSelect) {
            if (isStudent) {
                classContainer.classList.remove('hidden');
                classSelect.setAttribute('required', 'required');
            } else {
                classContainer.classList.add('hidden');
                classSelect.removeAttribute('required');
            }
        }

        // Email optional for student, required for others
        if (emailInput && emailReqMarker && emailOptMarker) {
            if (isStudent) {
                emailInput.removeAttribute('required');
                emailReqMarker.classList.add('hidden');
                emailOptMarker.classList.remove('hidden');
            } else {
                emailInput.setAttribute('required', 'required');
                emailReqMarker.classList.remove('hidden');
                emailOptMarker.classList.add('hidden');
            }
        }

        // Student Admission Number prefill if empty
        if (admFieldWrapper && admInput) {
            if (isStudent) {
                admFieldWrapper.classList.remove('opacity-60');
                if (!admInput.value.trim() && suggestedAdm) {
                    admInput.value = suggestedAdm;
                }
            } else {
                admFieldWrapper.classList.add('opacity-60');
            }
        }

        // Teacher Staff ID prefill if empty
        if (staffFieldWrapper && staffInput) {
            if (isTeacher) {
                staffFieldWrapper.classList.remove('opacity-60');
                if (!staffInput.value.trim() && suggestedStaff) {
                    staffInput.value = suggestedStaff;
                }
            } else {
                staffFieldWrapper.classList.add('opacity-60');
            }
        }
    }

    if (studentInput) {
        studentInput.addEventListener('change', function () {
            if (this.checked) {
                otherInputs.forEach(input => {
                    input.checked = false;
                });
            }
            syncRolesAndFields();
        });
    }

    otherInputs.forEach(input => {
        input.addEventListener('change', function () {
            if (this.checked && studentInput && studentInput.checked) {
                studentInput.checked = false;
            }
            syncRolesAndFields();
        });
    });

    syncRolesAndFields();
});
</script>
