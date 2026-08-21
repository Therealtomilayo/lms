<?php
require_once __DIR__ . '/config.php';
$page_title = 'Parent Portal';
$meta_description = 'Sign in to the Claret International School parent portal.';
require __DIR__ . '/partials/header.php';
?>

<section class="min-h-[75vh] flex items-center justify-center px-5 lg:px-8 py-16 lg:py-24 bg-brand-bg">
  <div class="reveal-scale w-full max-w-4xl rounded-3xl bg-white shadow-floaty ring-1 ring-brand-dark/10 overflow-hidden grid md:grid-cols-2">

    <!-- Branding side -->
    <div class="relative hidden md:block bg-brand-dark">
      <img src="assets/img/Claret-International-School-12-1024x576.jpg" alt="Claret International School campus" class="absolute inset-0 h-full w-full object-cover opacity-40">
      <div class="absolute inset-0 bg-gradient-to-t from-brand-dark via-brand-dark/70 to-brand-dark/20"></div>
      <div class="relative flex h-full flex-col justify-between p-10 text-white">
        <img src="assets/img/logo.png" alt="<?= htmlspecialchars($school['name']) ?>" class="h-16 w-16 object-contain">
        <div>
          <p class="text-xs font-bold uppercase tracking-[0.2em] text-brand-accent mb-3">Parent portal</p>
          <h2 class="font-serif text-2xl font-bold leading-snug">Stay close to your child's day, wherever you are.</h2>
          <p class="mt-3 text-sm text-white/70">Attendance, term reports and school announcements, all in one place.</p>
        </div>
      </div>
    </div>

    <!-- Login side -->
    <div class="p-8 sm:p-10 lg:p-12 flex flex-col justify-center">
      <div class="flex items-center gap-3 mb-8 md:hidden">
        <img src="assets/img/logo.png" alt="<?= htmlspecialchars($school['name']) ?>" class="h-12 w-12 object-contain">
      </div>

      <div class="mb-8">
        <span class="flex size-11 items-center justify-center rounded-2xl bg-brand-primary/10 text-brand-primary mb-5">
          <i data-lucide="school" class="size-5" aria-hidden="true"></i>
        </span>
        <h1 class="font-serif text-2xl font-bold text-brand-dark">Sign in to your account</h1>
        <p class="text-sm text-slate-500 mt-1">Access your family's Claret portal.</p>
      </div>

      <form id="portal-form" class="flex flex-col gap-5" novalidate>
        <div class="relative">
          <input id="portal-email" type="email" placeholder=" " required autocomplete="username"
                 class="peer w-full rounded-xl border border-slate-200 bg-slate-50/60 px-4 pt-6 pb-2.5 text-sm outline-none transition-all focus:ring-2 focus:ring-brand-primary focus:border-transparent">
          <label for="portal-email" class="absolute left-4 top-2 text-[11px] font-semibold text-slate-400 transition-all peer-placeholder-shown:top-4 peer-placeholder-shown:text-sm peer-focus:top-2 peer-focus:text-[11px] peer-focus:text-brand-primary">Email address</label>
        </div>

        <div class="relative">
          <input id="portal-password" type="password" placeholder=" " required autocomplete="current-password"
                 class="peer w-full rounded-xl border border-slate-200 bg-slate-50/60 px-4 pt-6 pb-2.5 pr-11 text-sm outline-none transition-all focus:ring-2 focus:ring-brand-primary focus:border-transparent">
          <label for="portal-password" class="absolute left-4 top-2 text-[11px] font-semibold text-slate-400 transition-all peer-placeholder-shown:top-4 peer-placeholder-shown:text-sm peer-focus:top-2 peer-focus:text-[11px] peer-focus:text-brand-primary">Password</label>
          <button type="button" id="toggle-password" aria-label="Show password" class="absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-brand-primary transition-colors">
            <i data-lucide="eye" class="size-4.5" aria-hidden="true"></i>
          </button>
        </div>

        <div class="flex items-center justify-between text-sm">
          <label class="flex items-center gap-2 text-slate-600">
            <input type="checkbox" class="size-4 rounded border-slate-300 text-brand-primary focus:ring-brand-primary"> Remember me
          </label>
          <a href="#" class="font-semibold text-brand-primary hover:underline">Forgot password?</a>
        </div>

        <button type="submit" class="mt-2 inline-flex items-center justify-center gap-2 rounded-full bg-brand-primary px-6 py-3.5 text-sm font-bold text-white transition-all duration-300 ease-in-out hover:-translate-y-0.5 hover:shadow-lg hover:shadow-brand-primary/30">
          Sign in <i data-lucide="arrow-right" class="size-4" aria-hidden="true"></i>
        </button>
      </form>

      <p class="mt-8 text-center text-sm text-slate-500">Not a Claret parent yet? <a href="/admissions" class="font-semibold text-brand-primary hover:underline">Apply for admission</a></p>
    </div>
  </div>
</section>

<script>
  const toggleBtn = document.getElementById('toggle-password');
  const pwd = document.getElementById('portal-password');
  toggleBtn?.addEventListener('click', () => {
    const show = pwd.type === 'password';
    pwd.type = show ? 'text' : 'password';
    toggleBtn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
    toggleBtn.innerHTML = `<i data-lucide="${show ? 'eye-off' : 'eye'}" class="size-4.5"></i>`;
    if (window.lucide) lucide.createIcons();
  });
  document.getElementById('portal-form')?.addEventListener('submit', (e) => {
    e.preventDefault();
    alert('Portal sign-in will connect to the school\'s student information system.');
  });
</script>

<?php require __DIR__ . '/partials/footer.php'; ?>
