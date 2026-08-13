<?php
/**
 * Forgot Password Screen View
 * AUTH-02 — Guest
 * 
 * @var array|null $errors Form validation errors
 */
$this->layout('layouts/auth', ['title' => 'Forgot Password — Claret LMS']);
?>

<h2 class="text-xl font-bold text-slate-900 mb-2">Reset your password</h2>
<p class="text-sm text-slate-500 mb-6">
    Enter your registered email address and we will generate secure password reset instructions.
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

<!-- Forgot Password Form -->
<form action="/forgot-password" method="POST" class="space-y-4" novalidate>
    <?= csrf_field() ?>

    <!-- Email Address Input Component -->
    <?php $this->include('components/input', [
        'name' => 'email',
        'label' => 'Email Address',
        'type' => 'email',
        'value' => old('email'),
        'placeholder' => 'name@claret.edu',
        'required' => true,
        'error' => !empty($errors['email']) ? $errors['email'][0] : '',
        'attributes' => 'autocomplete="email"'
    ]); ?>

    <!-- Submit Primary Button Component -->
    <div class="pt-2">
        <?php $this->include('components/button', [
            'type' => 'submit',
            'variant' => 'primary',
            'label' => 'Send Reset Instructions',
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
