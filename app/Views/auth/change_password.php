<?php $this->layout('layouts/auth', ['title' => 'Change Password - Claret LMS']); ?>

<h2 class="text-xl font-semibold text-slate-900 mb-2">
    <?= !empty($isForced) ? 'Update Your Password' : 'Change Password' ?>
</h2>
<p class="text-sm text-slate-600 mb-6">
    <?= !empty($isForced) 
        ? 'For your security, you are required to change your temporary or initial password before continuing.' 
        : 'Update your account password. Choose a strong password with at least 8 characters.' ?>
</p>

<?php if (!empty($errors['general'])): ?>
    <div role="alert" class="mb-4 rounded-lg bg-danger-100 p-3 border border-red-200 text-danger-700 text-sm">
        <?= e($errors['general'][0]) ?>
    </div>
<?php endif; ?>

<form action="/profile/password" method="POST" class="space-y-4" novalidate>
    <?= csrf_field() ?>

    <div>
        <label for="current_password" class="block text-sm font-medium text-slate-700 mb-1">Current Password</label>
        <input type="password" 
               id="current_password" 
               name="current_password" 
               required 
               autocomplete="current-password"
               aria-describedby="<?= !empty($errors['current_password']) ? 'current-password-error' : '' ?>"
               class="w-full px-3 py-2 border <?= !empty($errors['current_password']) ? 'border-danger-700' : 'border-slate-300' ?> rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-brand-600 focus:border-brand-600 sm:text-sm text-slate-900"
               placeholder="••••••••">
        <?php if (!empty($errors['current_password'])): ?>
            <p id="current-password-error" class="mt-1 text-xs text-danger-700"><?= e($errors['current_password'][0]) ?></p>
        <?php endif; ?>
    </div>

    <div>
        <label for="password" class="block text-sm font-medium text-slate-700 mb-1">New Password</label>
        <input type="password" 
               id="password" 
               name="password" 
               required 
               autocomplete="new-password"
               aria-describedby="<?= !empty($errors['password']) ? 'password-error' : '' ?>"
               class="w-full px-3 py-2 border <?= !empty($errors['password']) ? 'border-danger-700' : 'border-slate-300' ?> rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-brand-600 focus:border-brand-600 sm:text-sm text-slate-900"
               placeholder="Minimum 8 characters">
        <?php if (!empty($errors['password'])): ?>
            <p id="password-error" class="mt-1 text-xs text-danger-700"><?= e($errors['password'][0]) ?></p>
        <?php endif; ?>
    </div>

    <div>
        <label for="password_confirmation" class="block text-sm font-medium text-slate-700 mb-1">Confirm New Password</label>
        <input type="password" 
               id="password_confirmation" 
               name="password_confirmation" 
               required 
               autocomplete="new-password"
               aria-describedby="<?= !empty($errors['password_confirmation']) ? 'confirmation-error' : '' ?>"
               class="w-full px-3 py-2 border <?= !empty($errors['password_confirmation']) ? 'border-danger-700' : 'border-slate-300' ?> rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-brand-600 focus:border-brand-600 sm:text-sm text-slate-900"
               placeholder="Repeat new password">
        <?php if (!empty($errors['password_confirmation'])): ?>
            <p id="confirmation-error" class="mt-1 text-xs text-danger-700"><?= e($errors['password_confirmation'][0]) ?></p>
        <?php endif; ?>
    </div>

    <div class="pt-2">
        <button type="submit" 
                class="w-full flex justify-center py-2.5 px-4 border border-transparent rounded-lg shadow-sm text-sm font-semibold text-white bg-brand-600 hover:bg-brand-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-600 transition-colors">
            Update Password
        </button>
    </div>

    <?php if (empty($isForced)): ?>
        <div class="text-center pt-2">
            <a href="/dashboard" class="text-sm font-medium text-slate-600 hover:text-slate-900 hover:underline">
                Cancel and return
            </a>
        </div>
    <?php endif; ?>
</form>
