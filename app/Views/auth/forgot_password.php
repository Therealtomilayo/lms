<?php
/**
 * Modern Forgot Password Screen View for Claret LMS
 * AUTH-02 — Guest
 * 
 * @var array|null $errors Form validation errors
 */
$this->layout('layouts/auth', [
    'title' => 'Forgot Password — Claret International School',
    'wideLayout' => false,
]);
?>

<div class="mb-6 text-center sm:text-left">
    <div class="inline-flex items-center justify-center size-12 rounded-2xl bg-rose-50 text-[#7B3046] border border-rose-100 mb-4">
        <svg class="size-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
    </div>
    <h2 class="font-serif text-2xl font-bold text-slate-900 tracking-tight">Reset your password</h2>
    <p class="text-xs text-slate-500 mt-1.5 leading-relaxed">
        Enter your registered email address and we will generate secure password reset instructions for your account.
    </p>
</div>

<!-- General Error Banner -->
<?php if (!empty($errors['general'])): ?>
    <div class="mb-4 rounded-xl bg-red-50/80 border border-red-200 p-3.5 text-xs text-red-700 flex items-start gap-2.5">
        <svg class="size-4 text-red-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <span><?= e($errors['general'][0]) ?></span>
    </div>
<?php endif; ?>

<!-- Forgot Password Form -->
<form action="/forgot-password" method="POST" class="space-y-4" novalidate>
    <?= csrf_field() ?>

    <!-- Email Address Input Component -->
    <div>
        <label for="email" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
            Email Address
        </label>
        <div class="relative group">
            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400 group-focus-within:text-[#7B3046] transition-colors">
                <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
            </div>
            <input id="email" 
                   name="email" 
                   type="email" 
                   value="<?= e(old('email')) ?>" 
                   required 
                   autocomplete="email" 
                   placeholder="name@claret.edu"
                   class="w-full pl-10 pr-4 py-2.5 text-sm rounded-xl border <?= !empty($errors['email']) ? 'border-red-400 bg-red-50/30' : 'border-slate-200 bg-slate-50/50' ?> text-slate-900 placeholder:text-slate-400 focus:bg-white focus:border-[#7B3046] focus:ring-4 focus:ring-[#7B3046]/10 outline-none transition-all duration-200">
        </div>
        <?php if (!empty($errors['email'])): ?>
            <p class="mt-1.5 text-xs text-red-600 font-medium flex items-center gap-1">
                <svg class="size-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span><?= e($errors['email'][0]) ?></span>
            </p>
        <?php endif; ?>
    </div>

    <!-- Submit Primary Button -->
    <div class="pt-2">
        <button type="submit" 
                class="group w-full inline-flex items-center justify-center gap-2 rounded-full bg-gradient-to-r from-[#7B3046] to-[#9B3B58] px-6 py-3 text-sm font-bold text-white shadow-md shadow-[#7B3046]/20 transition-all duration-300 ease-out hover:-translate-y-0.5 hover:shadow-lg hover:shadow-[#7B3046]/30 active:translate-y-0 cursor-pointer">
            <span>Send Reset Instructions</span>
            <svg class="size-4 transition-transform duration-200 group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
        </button>
    </div>

    <!-- Back to login link -->
    <div class="text-center pt-3 border-t border-slate-100 mt-5">
        <a href="/login" class="inline-flex items-center text-xs font-bold text-slate-600 hover:text-[#7B3046] transition-colors gap-1.5 group">
            <svg class="size-3.5 transition-transform duration-200 group-hover:-translate-x-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            <span>Back to sign in</span>
        </a>
    </div>
</form>
