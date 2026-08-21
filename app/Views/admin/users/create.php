<?php
$this->layout('layouts/admin', [
    'title' => $title ?? 'Create User — Claret LMS',
    'headerTitle' => $headerTitle ?? 'Create New User Account'
]);

$classOptions = ['' => '-- No Class Selected --'];
foreach ($classes as $c) {
    $classOptions[$c->id] = e($c->name);
}
?>

<div class="max-w-4xl mx-auto space-y-6">
    <!-- Page Header & Back Link -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-slate-900">Create New User Account</h2>
            <p class="text-sm text-slate-500 mt-1">Provide account credentials, profile details, and role assignments.</p>
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

    <!-- Form Container -->
    <form method="POST" action="/admin/users" class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 sm:p-8 space-y-8" novalidate>
        <?= csrf_field() ?>

        <!-- 1. Primary Account Details -->
        <div class="space-y-4 border-b border-slate-200 pb-6">
            <div>
                <h3 class="text-base font-bold text-slate-900">1. Primary Account Details</h3>
                <p class="text-xs text-slate-500 mt-0.5">Core login credentials and personal identity details.</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <?php $this->include('components/input', [
                    'name' => 'name',
                    'id' => 'user_name',
                    'label' => 'Full Name',
                    'placeholder' => 'e.g. John Doe',
                    'required' => true
                ]); ?>

                <?php $this->include('components/input', [
                    'name' => 'email',
                    'id' => 'user_email',
                    'type' => 'email',
                    'label' => 'Email Address',
                    'placeholder' => 'e.g. jdoe@claret.edu.ng',
                    'required' => true
                ]); ?>

                <?php $this->include('components/input', [
                    'name' => 'phone',
                    'id' => 'user_phone',
                    'type' => 'tel',
                    'label' => 'Phone Number',
                    'placeholder' => 'e.g. +234 801 234 5678'
                ]); ?>

                <?php $this->include('components/input', [
                    'name' => 'password',
                    'id' => 'user_password',
                    'type' => 'password',
                    'label' => 'Initial Password',
                    'value' => 'Password123!',
                    'required' => true,
                    'helpText' => 'Minimum 8 characters. Defaults to Password123!'
                ]); ?>
            </div>
        </div>

        <!-- 2. Role Allocations -->
        <div class="space-y-4 border-b border-slate-200 pb-6">
            <div>
                <h3 class="text-base font-bold text-slate-900">2. Role Allocations <span class="text-brand-600">*</span></h3>
                <p class="text-xs text-slate-500 mt-0.5">Select one or more permission roles for this account.</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <?php if ($actor->hasRole('super_admin')): ?>
                    <label class="flex items-center gap-3 p-3.5 border border-slate-200 rounded-xl cursor-pointer hover:bg-slate-50/80 transition">
                        <input type="checkbox" name="roles[]" value="super_admin" class="w-4 h-4 rounded text-brand-600 focus:ring-brand-500 border-slate-300">
                        <div>
                            <span class="text-sm font-semibold text-slate-900 block">Super Admin</span>
                            <span class="text-xs text-slate-500 block">Full system access</span>
                        </div>
                    </label>
                <?php endif; ?>

                <label class="flex items-center gap-3 p-3.5 border border-slate-200 rounded-xl cursor-pointer hover:bg-slate-50/80 transition">
                    <input type="checkbox" name="roles[]" value="admin" class="w-4 h-4 rounded text-brand-600 focus:ring-brand-500 border-slate-300">
                    <div>
                        <span class="text-sm font-semibold text-slate-900 block">Admin</span>
                        <span class="text-xs text-slate-500 block">Academic management</span>
                    </div>
                </label>

                <label class="flex items-center gap-3 p-3.5 border border-slate-200 rounded-xl cursor-pointer hover:bg-slate-50/80 transition">
                    <input type="checkbox" name="roles[]" value="teacher" class="w-4 h-4 rounded text-brand-600 focus:ring-brand-500 border-slate-300">
                    <div>
                        <span class="text-sm font-semibold text-slate-900 block">Teacher</span>
                        <span class="text-xs text-slate-500 block">Grading & attendance</span>
                    </div>
                </label>

                <label class="flex items-center gap-3 p-3.5 border border-slate-200 rounded-xl cursor-pointer hover:bg-slate-50/80 transition">
                    <input type="checkbox" name="roles[]" value="student" checked class="w-4 h-4 rounded text-brand-600 focus:ring-brand-500 border-slate-300">
                    <div>
                        <span class="text-sm font-semibold text-slate-900 block">Student</span>
                        <span class="text-xs text-slate-500 block">Coursework & results</span>
                    </div>
                </label>

                <label class="flex items-center gap-3 p-3.5 border border-slate-200 rounded-xl cursor-pointer hover:bg-slate-50/80 transition">
                    <input type="checkbox" name="roles[]" value="parent" class="w-4 h-4 rounded text-brand-600 focus:ring-brand-500 border-slate-300">
                    <div>
                        <span class="text-sm font-semibold text-slate-900 block">Parent / Guardian</span>
                        <span class="text-xs text-slate-500 block">Child progress tracking</span>
                    </div>
                </label>
            </div>
        </div>

        <!-- 3. Specialized Profile Metadata -->
        <div class="space-y-4 border-b border-slate-200 pb-6">
            <div>
                <h3 class="text-base font-bold text-slate-900">3. Specialized Profile Metadata (Optional)</h3>
                <p class="text-xs text-slate-500 mt-0.5">Role-specific identifiers and class placements.</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <?php $this->include('components/input', [
                    'name' => 'admission_number',
                    'id' => 'user_admission_number',
                    'label' => 'Student Admission No.',
                    'placeholder' => 'e.g. STD-2026-001'
                ]); ?>

                <?php $this->include('components/input', [
                    'name' => 'staff_id',
                    'id' => 'user_staff_id',
                    'label' => 'Teacher Staff ID',
                    'placeholder' => 'e.g. TCH-0042'
                ]); ?>

                <?php $this->include('components/select', [
                    'name' => 'current_class_id',
                    'id' => 'user_current_class_id',
                    'label' => 'Current Class (Students)',
                    'options' => $classOptions,
                    'selected' => '',
                    'placeholder' => ''
                ]); ?>
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
                'label' => 'Create User Account'
            ]); ?>
        </div>
    </form>
</div>
