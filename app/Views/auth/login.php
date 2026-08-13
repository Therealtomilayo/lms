<?php
/**
 * Login Form Screen View
 * AUTH-01 — Guest
 * 
 * @var array|null $errors Form validation errors
 */
$this->layout('layouts/auth', ['title' => 'Sign In — Claret LMS']);
?>

<h2 class="text-xl font-bold text-slate-900 mb-6">Sign in to your account</h2>

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

<!-- Login Form Submission -->
<form action="/login" method="POST" class="space-y-4" novalidate>
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

    <!-- Password Input Component -->
    <div>
        <?php $this->include('components/input', [
            'name' => 'password',
            'label' => 'Password',
            'type' => 'password',
            'placeholder' => '••••••••',
            'required' => true,
            'error' => !empty($errors['password']) ? $errors['password'][0] : '',
            'attributes' => 'autocomplete="current-password"'
        ]); ?>
        
        <!-- Forgot Password Link Helper -->
        <div class="flex justify-end mt-1.5">
            <a href="/forgot-password" class="text-xs font-semibold text-brand-600 hover:text-brand-700 focus:underline transition">
                Forgot password?
            </a>
        </div>
    </div>

    <!-- Submit Primary Button Component -->
    <div class="pt-2">
        <?php $this->include('components/button', [
            'type' => 'submit',
            'variant' => 'primary',
            'label' => 'Sign In',
            'class' => 'w-full justify-center'
        ]); ?>
    </div>
</form>
