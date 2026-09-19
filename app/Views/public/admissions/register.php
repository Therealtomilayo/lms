<?php
/**
 * Applicant Registration Form: /apply/register
 * 
 * @var \App\Models\AdmissionSession|null $session Active admission session
 * @var array|null $errors Validation errors
 */
$this->layout('layouts/auth', [
    'title' => 'Register Applicant Account — Claret Admissions',
    'wideLayout' => true,
]);
?>

<div class="mx-auto w-full max-w-5xl rounded-3xl bg-white shadow-2xl ring-1 ring-slate-900/5 overflow-hidden grid lg:grid-cols-12 min-h-[600px]">

    <!-- Left Column: Branding -->
    <div class="relative hidden lg:flex lg:col-span-5 flex-col justify-between p-10 text-white overflow-hidden bg-slate-950 select-none">
        <img src="/assets/img/Claret-International-School-12-1024x576.jpg" 
             alt="Claret International School Campus" 
             class="absolute inset-0 h-full w-full object-cover opacity-35 transform scale-105 transition-transform duration-1000 ease-out hover:scale-100">
        
        <div class="absolute inset-0 bg-gradient-to-t from-[#2A0B14] via-[#5F2234]/85 to-[#1C2A39]/80"></div>

        <!-- Top Brand Crest -->
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

        <!-- Center Inspiration -->
        <div class="relative z-10 my-auto py-8">
            <h2 class="font-serif text-3xl font-bold leading-snug tracking-tight text-white">
                Guardian Account Registration
            </h2>
            <p class="mt-3 text-sm text-slate-200/90 leading-relaxed">
                As the parent or legal guardian, you will manage all applications, ward details, entrance requirements, and admission decisions from this single account.
            </p>

            <div class="mt-6 rounded-2xl bg-white/10 backdrop-blur-md p-4 border border-white/10 text-xs text-slate-200 space-y-1.5">
                <p class="font-bold text-white flex items-center gap-1.5">
                    <svg class="size-4 text-pink-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span>Multi-Ward Application Support</span>
                </p>
                <p class="text-white/80">
                    Applying for more than one child? You only need to register once. Additional wards can be added inside your dashboard.
                </p>
            </div>
        </div>

        <!-- Bottom Contact -->
        <div class="relative z-10 pt-4 border-t border-white/10 flex items-center justify-between text-xs text-white/70">
            <span>Official Admissions Portal</span>
            <span>Plot 700, Mabushi</span>
        </div>
    </div>

    <!-- Right Column: Registration Form -->
    <div class="lg:col-span-7 p-8 sm:p-12 lg:p-14 flex flex-col justify-center bg-white relative">
        
        <!-- Mobile Crest (Shown only on small screens) -->
        <div class="flex items-center gap-3 mb-8 lg:hidden">
            <a href="/" class="size-12 rounded-xl bg-slate-50 p-2 border border-slate-200 shadow-xs flex items-center justify-center">
                <img src="/assets/img/logo.png" alt="Claret Crest" class="h-full w-full object-contain">
            </a>
            <div>
                <span class="block font-serif font-bold text-slate-900 text-base leading-tight">Claret International School</span>
                <span class="block text-[11px] font-semibold tracking-wider text-[#7B3046] uppercase">Guardian Registration</span>
            </div>
        </div>

        <div class="mb-6">
            <h1 class="font-serif text-2xl sm:text-3xl font-bold text-slate-900 tracking-tight">
                Create guardian account
            </h1>
            <p class="text-sm text-slate-500 mt-1">
                Enter your contact details to begin prospective student applications.
            </p>
        </div>

        <!-- General Error Banner -->
        <?php if (!empty($errors['general'])): ?>
            <div class="mb-6 rounded-2xl bg-red-50/80 border border-red-200 p-4 text-sm text-red-700 flex items-start gap-3">
                <svg class="size-5 text-red-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <div class="text-xs"><?= e($errors['general'][0]) ?></div>
            </div>
        <?php endif; ?>

        <form action="/apply/register" method="POST" class="space-y-4" novalidate id="register-form">
            <?= csrf_field() ?>

            <!-- Guardian Full Name -->
            <div>
                <label for="name" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                    Parent / Guardian Full Name
                </label>
                <div class="relative group">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400 group-focus-within:text-[#7B3046] transition-colors">
                        <i data-lucide="user" class="w-4 h-4 text-slate-400 shrink-0" style="width:16px;height:16px;"></i>
                    </div>
                    <input id="name" 
                           name="name" 
                           type="text" 
                           value="<?= e(old('name')) ?>" 
                           required 
                           placeholder="e.g. Dr. Theresa Titilayo"
                           class="w-full pl-11 pr-4 py-2.5 text-sm rounded-xl border <?= !empty($errors['name']) ? 'border-red-400 bg-red-50/30' : 'border-slate-200 bg-slate-50/50' ?> text-slate-900 placeholder:text-slate-400 focus:bg-white focus:border-[#7B3046] focus:ring-4 focus:ring-[#7B3046]/10 outline-none transition-all duration-200">
                </div>
                <?php if (!empty($errors['name'])): ?>
                    <p class="mt-1 text-xs text-red-600 font-medium flex items-center gap-1">
                        <i data-lucide="alert-circle" class="w-3.5 h-3.5 shrink-0" style="width:14px;height:14px;"></i>
                        <span><?= e($errors['name'][0]) ?></span>
                    </p>
                <?php endif; ?>
            </div>

            <!-- Email Address -->
            <div>
                <label for="email" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                    Email Address
                </label>
                <div class="relative group">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400 group-focus-within:text-[#7B3046] transition-colors">
                        <i data-lucide="mail" class="w-4 h-4 text-slate-400 shrink-0" style="width:16px;height:16px;"></i>
                    </div>
                    <input id="email" 
                           name="email" 
                           type="email" 
                           value="<?= e(old('email')) ?>" 
                           required 
                           placeholder="guardian@example.com"
                           class="w-full pl-11 pr-4 py-2.5 text-sm rounded-xl border <?= !empty($errors['email']) ? 'border-red-400 bg-red-50/30' : 'border-slate-200 bg-slate-50/50' ?> text-slate-900 placeholder:text-slate-400 focus:bg-white focus:border-[#7B3046] focus:ring-4 focus:ring-[#7B3046]/10 outline-none transition-all duration-200">
                </div>
                <?php if (!empty($errors['email'])): ?>
                    <p class="mt-1 text-xs text-red-600 font-medium flex items-center gap-1">
                        <i data-lucide="alert-circle" class="w-3.5 h-3.5 shrink-0" style="width:14px;height:14px;"></i>
                        <span><?= e($errors['email'][0]) ?></span>
                    </p>
                <?php endif; ?>
            </div>

            <!-- Phone Number -->
            <div>
                <label for="phone" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                    Phone Number
                </label>
                <div class="relative group">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400 group-focus-within:text-[#7B3046] transition-colors">
                        <i data-lucide="phone" class="w-4 h-4 text-slate-400 shrink-0" style="width:16px;height:16px;"></i>
                    </div>
                    <input id="phone" 
                           name="phone" 
                           type="tel" 
                           value="<?= e(old('phone')) ?>" 
                           placeholder="0803 000 0000"
                           class="w-full pl-11 pr-4 py-2.5 text-sm rounded-xl border <?= !empty($errors['phone']) ? 'border-red-400 bg-red-50/30' : 'border-slate-200 bg-slate-50/50' ?> text-slate-900 placeholder:text-slate-400 focus:bg-white focus:border-[#7B3046] focus:ring-4 focus:ring-[#7B3046]/10 outline-none transition-all duration-200">
                </div>
                <?php if (!empty($errors['phone'])): ?>
                    <p class="mt-1 text-xs text-red-600 font-medium flex items-center gap-1">
                        <i data-lucide="alert-circle" class="w-3.5 h-3.5 shrink-0" style="width:14px;height:14px;"></i>
                        <span><?= e($errors['phone'][0]) ?></span>
                    </p>
                <?php endif; ?>
            </div>

            <!-- Password -->
            <div>
                <label for="password" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                    Password (Min. 8 characters)
                </label>
                <div class="relative group">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400 group-focus-within:text-[#7B3046] transition-colors">
                        <i data-lucide="lock" class="w-4 h-4 text-slate-400 shrink-0" style="width:16px;height:16px;"></i>
                    </div>
                    <input id="password" 
                           name="password" 
                           type="password" 
                           required 
                           placeholder="Create a strong password"
                           class="w-full pl-11 pr-12 py-2.5 text-sm rounded-xl border <?= !empty($errors['password']) ? 'border-red-400 bg-red-50/30' : 'border-slate-200 bg-slate-50/50' ?> text-slate-900 placeholder:text-slate-400 focus:bg-white focus:border-[#7B3046] focus:ring-4 focus:ring-[#7B3046]/10 outline-none transition-all duration-200">
                    
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
                    <p class="mt-1 text-xs text-red-600 font-medium flex items-center gap-1">
                        <i data-lucide="alert-circle" class="w-3.5 h-3.5 shrink-0" style="width:14px;height:14px;"></i>
                        <span><?= e($errors['password'][0]) ?></span>
                    </p>
                <?php endif; ?>
            </div>

            <!-- Submit Button (Arrow only appears and animates on hover) -->
            <div class="pt-2">
                <button type="submit" 
                        class="group relative w-full inline-flex items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-[#7B3046] via-[#8B3650] to-[#A33D5E] h-11 px-5 text-sm font-semibold text-white shadow-md shadow-[#7B3046]/20 transition-all duration-200 ease-out hover:shadow-lg hover:shadow-[#7B3046]/30 active:scale-[0.99] cursor-pointer">
                    <span>Create Account &amp; Continue</span>
                    <span class="inline-flex items-center justify-center max-w-0 opacity-0 -translate-x-2 group-hover:max-w-[20px] group-hover:opacity-100 group-hover:translate-x-0 transition-all duration-200 ease-out overflow-hidden">
                        <i data-lucide="arrow-right" class="w-4 h-4 shrink-0" style="width:16px;height:16px;"></i>
                    </span>
                </button>
            </div>
        </form>

        <!-- Cross Link: Already registered? Login here (Requirement 5) -->
        <div class="mt-6 pt-5 border-t border-slate-100 text-center">
            <p class="text-xs text-slate-600">
                Already registered? 
                <a href="/login" class="font-bold text-[#7B3046] hover:underline ml-1">Login here</a>
            </p>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        if (window.lucide) {
            lucide.createIcons();
        }

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
