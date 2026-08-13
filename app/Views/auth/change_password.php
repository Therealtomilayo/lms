<?php
/**
 * Change Password Screen View
 * AUTH-04 — Authenticated / Guest (Forced)
 * 
 * @var App\Models\User $user User model instance
 * @var bool $isForced Is the password change mandatory?
 * @var array|null $errors Form validation errors
 */

// Resolve dynamic layout based on whether user is forced to change password
if (empty($isForced)) {
    $roles = $user->roles ?? [];
    if (in_array('super_admin', $roles, true) || in_array('admin', $roles, true)) {
        $layoutName = 'layouts/admin';
    } elseif (in_array('teacher', $roles, true)) {
        $layoutName = 'layouts/teacher';
    } elseif (in_array('student', $roles, true)) {
        $layoutName = 'layouts/student';
    } elseif (in_array('parent', $roles, true)) {
        $layoutName = 'layouts/parent';
    } else {
        $layoutName = 'layouts/app';
    }
} else {
    $layoutName = 'layouts/auth';
}

$this->layout($layoutName, ['title' => 'Change Password — Claret LMS']);
?>

<?php ob_start(); ?>
<!-- General Error Banner -->
<?php if (!empty($errors['general'])): ?>
    <div class="mb-4">
        <?php $this->include('components/alert', [
            'type' => 'error',
            'message' => e($errors['general'][0]),
            'dismissible' => false
        ]); ?>
    </div>
<?php endif; ?>

<!-- Change Password Form -->
<form action="/profile/password" method="POST" class="space-y-4" novalidate>
    <?= csrf_field() ?>

    <!-- Current Password Input Component -->
    <?php $this->include('components/input', [
        'name' => 'current_password',
        'label' => 'Current Password',
        'type' => 'password',
        'placeholder' => '••••••••',
        'required' => true,
        'error' => !empty($errors['current_password']) ? $errors['current_password'][0] : '',
        'attributes' => 'autocomplete="current-password"'
    ]); ?>

    <!-- New Password Input Component -->
    <?php $this->include('components/input', [
        'name' => 'password',
        'label' => 'New Password',
        'type' => 'password',
        'placeholder' => 'Minimum 8 characters',
        'required' => true,
        'error' => !empty($errors['password']) ? $errors['password'][0] : '',
        'attributes' => 'autocomplete="new-password"'
    ]); ?>

    <!-- Confirm Password Input Component -->
    <?php $this->include('components/input', [
        'name' => 'password_confirmation',
        'label' => 'Confirm New Password',
        'type' => 'password',
        'placeholder' => 'Repeat new password',
        'required' => true,
        'error' => !empty($errors['password_confirmation']) ? $errors['password_confirmation'][0] : '',
        'attributes' => 'autocomplete="new-password"'
    ]); ?>

    <!-- Action Buttons Row -->
    <div class="flex items-center justify-between gap-4 pt-2">
        <?php if (empty($isForced)): ?>
            <a href="/dashboard" class="inline-flex items-center text-sm font-semibold text-slate-500 hover:text-slate-900 focus:underline transition">
                Cancel
            </a>
        <?php else: ?>
            <div></div> <!-- Spacer -->
        <?php endif; ?>

        <?php $this->include('components/button', [
            'type' => 'submit',
            'variant' => 'primary',
            'label' => 'Update Password'
        ]); ?>
    </div>
</form>
<?php $formContent = ob_get_clean(); ?>

<!-- Wrapper Shell Layout Scaffolding -->
<?php if (!empty($isForced)): ?>
    <h2 class="text-xl font-bold text-slate-900 mb-2">Update Your Password</h2>
    <p class="text-sm text-slate-500 mb-6">
        For your security, you are required to change your temporary or initial password before continuing.
    </p>
    <?= $formContent ?>
<?php else: ?>
    <div class="max-w-xl">
        <div class="mb-6">
            <h2 class="text-2xl font-bold tracking-tight text-slate-900">Account Settings</h2>
            <p class="text-sm text-slate-500 mt-1">Manage and update your security credentials.</p>
        </div>
        <?php $this->include('components/card', [
            'title' => 'Change Password',
            'subtitle' => 'Update your account password. Choose a strong password with at least 8 characters.',
            'body' => $formContent
        ]); ?>
    </div>
<?php endif; ?>
