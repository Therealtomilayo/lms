<?php
/**
 * Reset Password Screen View
 * AUTH-03 — Guest
 * 
 * @var string $token Password reset token
 * @var string $email Email address associated with the reset request
 * @var array|null $errors Form validation errors
 */
$this->layout('layouts/auth', ['title' => 'Set New Password — Claret LMS']);
?>

<h2 class="text-xl font-bold text-slate-900 mb-2">Create new password</h2>
<p class="text-sm text-slate-500 mb-6">
    Setting new password for <span class="font-medium text-slate-800"><?= e($email ?? '') ?></span>
</p>

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

<!-- Reset Password Form -->
<form action="/reset-password" method="POST" class="space-y-4" novalidate>
    <?= csrf_field() ?>
    <input type="hidden" name="token" value="<?= e($token ?? '') ?>">

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

    <!-- Submit Primary Button Component -->
    <div class="pt-2">
        <?php $this->include('components/button', [
            'type' => 'submit',
            'variant' => 'primary',
            'label' => 'Reset Password',
            'class' => 'w-full justify-center'
        ]); ?>
    </div>

    <!-- Back to login link -->
    <div class="text-center pt-2">
        <a href="/login" class="inline-flex items-center text-sm font-semibold text-slate-500 hover:text-slate-900 focus:underline transition gap-1">
            &larr; Back to sign in
        </a>
    </div>
</form>
