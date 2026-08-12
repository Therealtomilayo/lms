<?php $this->layout('layouts/auth', ['title' => 'Forgot Password - Claret LMS']); ?>

<h2 class="text-xl font-semibold text-slate-900 mb-2">Reset your password</h2>
<p class="text-sm text-slate-600 mb-6">
    Enter your registered email address and we will generate secure password reset instructions.
</p>

<?php if (!empty($errors['general'])): ?>
    <div role="alert" class="mb-4 rounded-lg bg-danger-100 p-3 border border-red-200 text-danger-700 text-sm">
        <?= e($errors['general'][0]) ?>
    </div>
<?php endif; ?>

<form action="/forgot-password" method="POST" class="space-y-4" novalidate>
    <?= csrf_field() ?>

    <div>
        <label for="email" class="block text-sm font-medium text-slate-700 mb-1">Email Address</label>
        <input type="email" 
               id="email" 
               name="email" 
               value="<?= e(old('email')) ?>" 
               required 
               autocomplete="email"
               aria-describedby="<?= !empty($errors['email']) ? 'email-error' : '' ?>"
               class="w-full px-3 py-2 border <?= !empty($errors['email']) ? 'border-danger-700' : 'border-slate-300' ?> rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-brand-600 focus:border-brand-600 sm:text-sm text-slate-900"
               placeholder="name@claret.edu">
        <?php if (!empty($errors['email'])): ?>
            <p id="email-error" class="mt-1 text-xs text-danger-700"><?= e($errors['email'][0]) ?></p>
        <?php endif; ?>
    </div>

    <div class="pt-2">
        <button type="submit" 
                class="w-full flex justify-center py-2.5 px-4 border border-transparent rounded-lg shadow-sm text-sm font-semibold text-white bg-brand-600 hover:bg-brand-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-600 transition-colors">
            Send Reset Instructions
        </button>
    </div>

    <div class="text-center pt-2">
        <a href="/login" class="text-sm font-medium text-slate-600 hover:text-slate-900 hover:underline">
            &larr; Back to sign in
        </a>
    </div>
</form>
