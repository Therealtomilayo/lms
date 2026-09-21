<?php
/**
 * Modern High-End Login View for Claret International School LMS & Portal
 * AUTH-01 — Guest
 * 
 * @var array|null $errors Form validation errors
 */
$this->layout('layouts/auth', [
    'title' => 'Sign In — Claret International School Portal',
    'wideLayout' => true,
]);
?>

<div class="mx-auto w-full max-w-5xl rounded-3xl bg-white shadow-2xl ring-1 ring-slate-900/5 overflow-hidden grid lg:grid-cols-12 min-h-[600px]">

    <!-- Left Column: School Campus Imagery & Identity -->
    <div class="relative hidden lg:flex lg:col-span-5 flex-col justify-between p-10 text-white overflow-hidden bg-slate-950 select-none">
        <!-- Campus Background Photo with Subtle Scale Effect -->
        <img src="/assets/img/Claret-International-School-12-1024x576.jpg" 
             alt="Claret International School Campus" 
             class="absolute inset-0 h-full w-full object-cover opacity-35 transform scale-105 transition-transform duration-1000 ease-out hover:scale-100">
        
        <!-- Multi-Layer Vignette & Brand Gradient Overlay -->
        <div class="absolute inset-0 bg-gradient-to-t from-[#2A0B14] via-[#5F2234]/85 to-[#1C2A39]/80"></div>
        <div class="absolute -top-24 -left-24 w-72 h-72 rounded-full bg-rose-500/20 blur-3xl pointer-events-none"></div>
        <div class="absolute -bottom-24 -right-24 w-72 h-72 rounded-full bg-amber-500/10 blur-3xl pointer-events-none"></div>

        <!-- Top Brand Crest & Header -->
        <div class="relative z-10">
            <a href="/" class="inline-flex items-center gap-3.5 group">
                <div class="size-14 rounded-2xl bg-white/80 backdrop-blur-md p-2 ring-1 ring-white/20 shadow-lg flex items-center justify-center transition-transform group-hover:scale-105">
                    <img src="/assets/img/logo.png" alt="Claret Crest" class="h-full w-full object-contain">
                </div>
                <div>
                    <span class="block font-serif font-bold text-white text-lg tracking-tight leading-tight">Claret International</span>
                    <span class="block text-[11px] font-semibold tracking-[0.2em] text-pink-200 uppercase">School • Abuja</span>
                </div>
            </a>
        </div>

        <!-- Center Inspiration & Institutional Pillars -->
        <div class="relative z-10 my-auto py-8">
            <h2 class="font-serif text-3xl font-bold leading-snug tracking-tight text-white">
                Discipline, Integrity &amp; Ardour.
            </h2>
            
            <p class="mt-3 text-sm text-slate-200/90 leading-relaxed">
                A 21st-century citadel of learning raising visionary leaders for tomorrow's world.
            </p>

            <!-- Role Tags -->
            <div class="mt-6 flex flex-wrap gap-2">
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg bg-white/10 text-xs font-medium text-slate-200 backdrop-blur-xs border border-white/10">
                    <svg class="size-3.5 text-pink-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                    <span>Guardians</span>
                </span>
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg bg-white/10 text-xs font-medium text-slate-200 backdrop-blur-xs border border-white/10">
                    <svg class="size-3.5 text-pink-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                    <span>Students</span>
                </span>
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg bg-white/10 text-xs font-medium text-slate-200 backdrop-blur-xs border border-white/10">
                    <svg class="size-3.5 text-pink-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/></svg>
                    <span>Staff</span>
                </span>
            </div>
        </div>

        <!-- Bottom Security / Verification Badge -->
        <div class="relative z-10 pt-4 border-t border-white/10 flex items-center justify-between text-xs text-white/70">
            <div class="flex items-center gap-2">
                <svg class="size-3.5 text-pink-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                <span>Institutional Portal</span>
            </div>
            <span class="text-[11px] text-white/50">Mabushi, Abuja</span>
        </div>
    </div>

    <!-- Right Column: Interactive Login Form -->
    <div class="lg:col-span-7 p-8 sm:p-12 lg:p-14 flex flex-col justify-center bg-white relative">
        
        <!-- Mobile Crest (Shown only on small screens) -->
        <div class="flex items-center gap-3 mb-8 lg:hidden">
            <a href="/" class="size-12 rounded-xl bg-slate-50 p-2 border border-slate-200 shadow-xs flex items-center justify-center">
                <img src="/assets/img/logo.png" alt="Claret Crest" class="h-full w-full object-contain">
            </a>
            <div>
                <span class="block font-serif font-bold text-slate-900 text-base leading-tight">Claret International School</span>
                <span class="block text-[11px] font-semibold tracking-wider text-[#7B3046] uppercase">Portal Sign In</span>
            </div>
        </div>

        <!-- Clean Form Heading -->
        <div class="mb-8">
            <h1 class="font-serif text-2xl sm:text-3xl font-bold text-slate-900 tracking-tight">
                Sign in to your account
            </h1>
            <p class="text-sm text-slate-500 mt-1">
                Access your Claret student, parent, or staff dashboard.
            </p>
        </div>

        <!-- General Error Banner -->
        <?php if (!empty($errors['general'])): ?>
            <div class="mb-6 rounded-2xl bg-red-50/80 border border-red-200 p-4 text-sm text-red-700 flex items-start gap-3">
                <svg class="size-5 text-red-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <div>
                    <p class="font-semibold text-red-900">Authentication Failed</p>
                    <p class="text-xs text-red-700 mt-0.5"><?= e($errors['general'][0]) ?></p>
                </div>
            </div>
        <?php endif; ?>

        <!-- Login Form Submission -->
        <form action="/login" method="POST" class="space-y-5" novalidate id="login-form">
            <?= csrf_field() ?>

            <!-- Email Address or Admission Number Field -->
            <div>
                <label for="email" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                    Email Address or Student Admission Number
                </label>
                <div class="relative group">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400 group-focus-within:text-[#7B3046] transition-colors">
                        <i data-lucide="user-check" class="w-4 h-4 text-slate-400 shrink-0" style="width:16px;height:16px;"></i>
                    </div>
                    <input id="email" 
                           name="email" 
                           type="text" 
                           value="<?= e(old('email')) ?>" 
                           required 
                           autocomplete="username" 
                           placeholder="e.g. name@claret.edu or STD-00001"
                           class="w-full pl-11 pr-4 py-2.5 text-sm rounded-xl border <?= !empty($errors['email']) ? 'border-red-400 bg-red-50/30' : 'border-slate-200 bg-slate-50/50' ?> text-slate-900 placeholder:text-slate-400 focus:bg-white focus:border-[#7B3046] focus:ring-4 focus:ring-[#7B3046]/10 outline-none transition-all duration-200">
                </div>
                <?php if (!empty($errors['email'])): ?>
                    <p class="mt-1.5 text-xs text-red-600 font-medium flex items-center gap-1">
                        <i data-lucide="alert-circle" class="w-3.5 h-3.5 shrink-0" style="width:14px;height:14px;"></i>
                        <span><?= e($errors['email'][0]) ?></span>
                    </p>
                <?php endif; ?>
            </div>

            <!-- Password Field with Show/Hide Eye Toggle -->
            <div>
                <div class="flex items-center justify-between mb-1.5">
                    <label for="password" class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                        Password
                    </label>
                    <a href="/forgot-password" class="text-xs font-semibold text-[#7B3046] hover:text-[#9B3B58] transition-colors focus:underline">
                        Forgot password?
                    </a>
                </div>
                <div class="relative group">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400 group-focus-within:text-[#7B3046] transition-colors">
                        <i data-lucide="lock" class="w-4 h-4 text-slate-400 shrink-0" style="width:16px;height:16px;"></i>
                    </div>
                    <input id="password" 
                           name="password" 
                           type="password" 
                           required 
                           autocomplete="current-password" 
                           placeholder="••••••••••••"
                           class="w-full pl-11 pr-12 py-2.5 text-sm rounded-xl border <?= !empty($errors['password']) ? 'border-red-400 bg-red-50/30' : 'border-slate-200 bg-slate-50/50' ?> text-slate-900 placeholder:text-slate-400 focus:bg-white focus:border-[#7B3046] focus:ring-4 focus:ring-[#7B3046]/10 outline-none transition-all duration-200">
                    
                    <!-- Lucide Eye Toggle Button -->
                    <button type="button" 
                            id="toggle-password-btn" 
                            aria-label="Show password" 
                            class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-[#7B3046] transition-colors focus:outline-none cursor-pointer">
                        <span id="eye-icon-open" class="flex items-center">
                            <i data-lucide="eye" class="w-4 h-4 shrink-0" style="width:16px;height:16px;"></i>
                        </span>
                        <span id="eye-icon-closed" class="hidden items-center">
                            <i data-lucide="eye-off" class="w-4 h-4 text-[#7B3046] shrink-0" style="width:16px;height:16px;"></i>
                        </span>
                    </button>
                </div>
                <?php if (!empty($errors['password'])): ?>
                    <p class="mt-1.5 text-xs text-red-600 font-medium flex items-center gap-1">
                        <i data-lucide="alert-circle" class="w-3.5 h-3.5 shrink-0" style="width:14px;height:14px;"></i>
                        <span><?= e($errors['password'][0]) ?></span>
                    </p>
                <?php endif; ?>
            </div>

            <!-- Remember Me Row -->
            <div class="flex items-center justify-between pt-1">
                <label class="flex items-center gap-2.5 text-xs text-slate-600 select-none cursor-pointer">
                    <input type="checkbox" name="remember" class="w-4 h-4 rounded border-slate-300 text-[#7B3046] focus:ring-[#7B3046] transition cursor-pointer">
                    <span>Remember this device</span>
                </label>
            </div>

            <!-- Submit Button (Arrow only appears and animates on hover) -->
            <div class="pt-2">
                <button type="submit" 
                        class="group relative w-full inline-flex items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-[#7B3046] via-[#8B3650] to-[#A33D5E] h-11 px-5 text-sm font-semibold text-white shadow-md shadow-[#7B3046]/20 transition-all duration-200 ease-out hover:shadow-lg hover:shadow-[#7B3046]/30 active:scale-[0.99] cursor-pointer">
                    <span>Sign in to your account</span>
                    <span class="inline-flex items-center justify-center max-w-0 opacity-0 -translate-x-2 group-hover:max-w-[20px] group-hover:opacity-100 group-hover:translate-x-0 transition-all duration-200 ease-out overflow-hidden">
                        <i data-lucide="arrow-right" class="w-4 h-4 shrink-0" style="width:16px;height:16px;"></i>
                    </span>
                </button>
            </div>
        </form>

        <!-- Admission Application Cross-Link Banner -->
        <div class="mt-8 pt-6 border-t border-slate-100">
            <div class="rounded-2xl bg-gradient-to-br from-rose-50/80 via-white to-amber-50/40 p-4 border border-rose-100/90 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 shadow-xs">
                <div class="flex items-center gap-3">
                    <div class="size-10 rounded-xl bg-white shadow-xs border border-rose-100 flex items-center justify-center text-[#7B3046] shrink-0">
                        <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/></svg>
                    </div>
                    <div>
                        <div class="text-xs font-bold text-slate-900">
                            Not registered? Apply here
                        </div>
                        <p class="text-[11px] text-slate-500 mt-0.5">
                            Online prospective student applications are now open.
                        </p>
                    </div>
                </div>
                <a href="/apply" 
                   class="group shrink-0 inline-flex items-center gap-1.5 rounded-full bg-[#7B3046] px-4 py-2 text-xs font-bold text-white shadow-xs transition-all duration-200 hover:bg-[#5F2234] hover:-translate-y-0.5">
                    <span>Apply Now</span>
                    <svg class="size-3.5 transition-transform duration-200 group-hover:translate-x-0.5 group-hover:-translate-y-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                    </svg>
                </a>
            </div>
        </div>

        <!-- Secondary Bottom Links -->
        <div class="mt-6 flex items-center justify-between text-xs text-slate-400">
            <a href="/" class="hover:text-slate-600 transition-colors inline-flex items-center gap-1">
                <svg class="size-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                <span>Return to main site</span>
            </a>
            <span class="text-[11px]">&copy; <?= date('Y') ?> Claret International School</span>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        if (window.lucide) {
            lucide.createIcons();
        }

        // Pure Vanilla JavaScript Password Toggle
        const toggleBtn = document.getElementById('toggle-password-btn');
        const pwdInput = document.getElementById('password');
        const iconOpen = document.getElementById('eye-icon-open');
        const iconClosed = document.getElementById('eye-icon-closed');

        if (toggleBtn && pwdInput) {
            toggleBtn.addEventListener('click', (e) => {
                e.preventDefault();
                const isPassword = pwdInput.type === 'password';
                pwdInput.type = isPassword ? 'text' : 'password';
                toggleBtn.setAttribute('aria-label', isPassword ? 'Hide password' : 'Show password');
                
                if (iconOpen && iconClosed) {
                    if (isPassword) {
                        iconOpen.classList.add('hidden');
                        iconClosed.classList.remove('hidden');
                    } else {
                        iconOpen.classList.remove('hidden');
                        iconClosed.classList.add('hidden');
                    }
                }

                if (window.lucide) {
                    lucide.createIcons();
                }
            });
        }
    });
</script>
