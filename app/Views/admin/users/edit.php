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
$hasRole = function(string $role) use ($selectedRoles, $user): bool {
    if (is_array($selectedRoles)) {
        return in_array($role, $selectedRoles, true);
    }
    return $user->hasRole($role);
};
?>

<div class="max-w-4xl mx-auto space-y-6">
    <!-- Page Header & Back Link -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-slate-900">Edit User: <?= e($user->name) ?></h2>
            <p class="text-sm text-slate-500 mt-1">Update contact info, role allocations, or manage security credentials.</p>
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

    <!-- Main Update Form -->
    <form method="POST" action="/admin/users/<?= e($user->id) ?>" class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 sm:p-8 space-y-8" novalidate>
        <?= csrf_field() ?>

        <!-- 1. Account Information -->
        <div class="space-y-4 border-b border-slate-200 pb-6">
            <div>
                <h3 class="text-base font-bold text-slate-900">Account Information</h3>
                <p class="text-xs text-slate-500 mt-0.5">Contact details and active account status.</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <?php $this->include('components/input', [
                    'name' => 'name',
                    'id' => 'user_name',
                    'label' => 'Full Name',
                    'placeholder' => 'e.g. John Doe',
                    'value' => old('name', $user->name),
                    'required' => true,
                    'error' => !empty($errors['name']) ? $errors['name'][0] : ''
                ]); ?>

                <?php $this->include('components/input', [
                    'name' => 'email',
                    'id' => 'user_email',
                    'type' => 'email',
                    'label' => 'Email Address',
                    'placeholder' => 'e.g. jdoe@claret.edu.ng',
                    'value' => old('email', $user->email),
                    'required' => true,
                    'error' => !empty($errors['email']) ? $errors['email'][0] : ''
                ]); ?>

                <?php $this->include('components/input', [
                    'name' => 'phone',
                    'id' => 'user_phone',
                    'type' => 'tel',
                    'label' => 'Phone Number',
                    'placeholder' => 'e.g. +234 801 234 5678',
                    'value' => old('phone', $user->phone ?? ''),
                    'error' => !empty($errors['phone']) ? $errors['phone'][0] : ''
                ]); ?>

                <?php $this->include('components/select', [
                    'name' => 'status',
                    'id' => 'user_status',
                    'label' => 'Account Status',
                    'options' => [
                        'active' => 'Active',
                        'inactive' => 'Inactive',
                        'suspended' => 'Suspended'
                    ],
                    'selected' => old('status', $user->status),
                    'error' => !empty($errors['status']) ? $errors['status'][0] : '',
                    'helpText' => $statusHelp,
                    'attributes' => $statusAttributes
                ]); ?>
            </div>
        </div>

        <!-- 2. Role Allocations -->
        <div class="space-y-4 border-b border-slate-200 pb-6">
            <div>
                <h3 class="text-base font-bold text-slate-900">Role Allocations <span class="text-brand-600">*</span></h3>
                <p class="text-xs text-slate-500 mt-0.5">Select one or more permission roles for this account.</p>
                <?php if (!empty($errors['roles'])): ?>
                    <p class="text-xs font-semibold text-danger-700 mt-1 flex items-center gap-1.5">
                        <svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                        <?= e($errors['roles'][0]) ?>
                    </p>
                <?php endif; ?>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <?php if ($actor->hasRole('super_admin')): ?>
                    <label class="flex items-center gap-3 p-3.5 border border-slate-200 rounded-xl cursor-pointer hover:bg-slate-50/80 transition">
                        <input type="checkbox" name="roles[]" value="super_admin" <?= $hasRole('super_admin') ? 'checked' : '' ?> class="w-4 h-4 rounded text-brand-600 focus:ring-brand-500 border-slate-300">
                        <div>
                            <span class="text-sm font-semibold text-slate-900 block">Super Admin</span>
                            <span class="text-xs text-slate-500 block">Full system access</span>
                        </div>
                    </label>
                <?php endif; ?>

                <label class="flex items-center gap-3 p-3.5 border border-slate-200 rounded-xl cursor-pointer hover:bg-slate-50/80 transition">
                    <input type="checkbox" name="roles[]" value="admin" <?= $hasRole('admin') ? 'checked' : '' ?> class="w-4 h-4 rounded text-brand-600 focus:ring-brand-500 border-slate-300">
                    <div>
                        <span class="text-sm font-semibold text-slate-900 block">Admin</span>
                        <span class="text-xs text-slate-500 block">Academic management</span>
                    </div>
                </label>

                <label class="flex items-center gap-3 p-3.5 border border-slate-200 rounded-xl cursor-pointer hover:bg-slate-50/80 transition">
                    <input type="checkbox" name="roles[]" value="teacher" <?= $hasRole('teacher') ? 'checked' : '' ?> class="w-4 h-4 rounded text-brand-600 focus:ring-brand-500 border-slate-300">
                    <div>
                        <span class="text-sm font-semibold text-slate-900 block">Teacher</span>
                        <span class="text-xs text-slate-500 block">Grading & attendance</span>
                    </div>
                </label>

                <label class="flex items-center gap-3 p-3.5 border border-slate-200 rounded-xl cursor-pointer hover:bg-slate-50/80 transition">
                    <input type="checkbox" name="roles[]" value="student" <?= $hasRole('student') ? 'checked' : '' ?> class="w-4 h-4 rounded text-brand-600 focus:ring-brand-500 border-slate-300">
                    <div>
                        <span class="text-sm font-semibold text-slate-900 block">Student</span>
                        <span class="text-xs text-slate-500 block">Coursework & results</span>
                    </div>
                </label>

                <label class="flex items-center gap-3 p-3.5 border border-slate-200 rounded-xl cursor-pointer hover:bg-slate-50/80 transition">
                    <input type="checkbox" name="roles[]" value="parent" <?= $hasRole('parent') ? 'checked' : '' ?> class="w-4 h-4 rounded text-brand-600 focus:ring-brand-500 border-slate-300">
                    <div>
                        <span class="text-sm font-semibold text-slate-900 block">Parent / Guardian</span>
                        <span class="text-xs text-slate-500 block">Child progress tracking</span>
                    </div>
                </label>
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
                    <?php $this->include('components/input', [
                        'name' => 'password',
                        'id' => 'reset_password',
                        'type' => 'password',
                        'label' => 'New Temporary Password',
                        'placeholder' => 'Minimum 8 characters',
                        'required' => true,
                        'error' => !empty($errors['password']) ? $errors['password'][0] : ''
                    ]); ?>
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
