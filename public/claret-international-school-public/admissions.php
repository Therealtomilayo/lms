<?php
require_once __DIR__ . '/config.php';
$page_title = 'Admissions';
$meta_description = 'Apply to Claret International School, Mabushi Abuja — Nursery, Primary and Secondary admissions.';
require __DIR__ . '/partials/header.php';
?>

<!-- PAGE INTRO -->
<section class="bg-brand-dark text-white px-5 lg:px-8 py-16 lg:py-24">
  <div class="mx-auto max-w-7xl">
    <p class="mb-4 text-sm font-bold uppercase tracking-[0.22em] text-brand-accent">Admissions</p>
    <h1 class="max-w-3xl text-balance font-serif text-4xl md:text-6xl font-bold leading-tight">Begin your child's journey at Claret.</h1>
    <p class="mt-6 max-w-2xl text-base md:text-lg leading-7 text-white/75">A day school open to Creche through Secondary — co-educational, with a school bus service and a choice of three curricula. Start your application below.</p>
  </div>
</section>

<!-- CURRICULUM QUICK FACTS -->
<section class="mx-auto max-w-7xl px-5 lg:px-8 -mt-10 relative z-10 mb-6">
  <div class="grid gap-5 sm:grid-cols-3">
    <?php foreach ($school['curricula'] as $i => $curriculum): ?>
      <div class="reveal lift-hover rounded-3xl bg-white shadow-floaty ring-1 ring-brand-dark/10 p-6 flex items-center gap-4">
        <span class="flex size-11 items-center justify-center rounded-2xl bg-brand-primary/10 text-brand-primary shrink-0">
          <i data-lucide="book-marked" class="size-5" aria-hidden="true"></i>
        </span>
        <p class="font-serif font-bold text-brand-dark leading-snug"><?= htmlspecialchars($curriculum) ?></p>
      </div>
    <?php endforeach; ?>
  </div>
</section>

<!-- MULTI-STEP FORM -->
<section class="mx-auto max-w-4xl px-5 lg:px-8 py-16 lg:py-20">

  <!-- Progress -->
  <div class="flex items-center gap-3 mb-12" id="step-progress">
    <?php $steps = ['Student', 'Guardian', 'Program', 'Documents']; foreach ($steps as $i => $label): ?>
      <div class="flex-1">
        <div class="step-bar h-1.5 rounded-full <?= $i === 0 ? 'bg-brand-primary' : 'bg-brand-primary/15' ?>" data-bar="<?= $i ?>"></div>
        <span class="mt-2 block text-[11px] font-bold uppercase tracking-wider <?= $i === 0 ? 'text-brand-primary' : 'text-slate-400' ?>" data-label="<?= $i ?>"><?= $i + 1 ?>. <?= $label ?></span>
      </div>
    <?php endforeach; ?>
  </div>

  <form id="admissions-form" class="reveal rounded-3xl bg-white ring-1 ring-brand-dark/10 shadow-sm p-8 lg:p-10" novalidate>

    <!-- STEP 1: Student -->
    <fieldset class="form-step" data-step="0">
      <legend class="font-serif text-2xl font-bold text-brand-dark mb-6">Student information</legend>
      <div class="grid gap-6 sm:grid-cols-2">
        <div class="relative">
          <input id="student-name" name="student_name" type="text" placeholder=" " required
                 class="peer w-full rounded-xl border border-slate-200 bg-slate-50/60 px-4 pt-6 pb-2.5 text-sm outline-none transition-all focus:ring-2 focus:ring-brand-primary focus:border-transparent">
          <label for="student-name" class="absolute left-4 top-2 text-[11px] font-semibold text-slate-400 transition-all peer-placeholder-shown:top-4 peer-placeholder-shown:text-sm peer-placeholder-shown:text-slate-400 peer-focus:top-2 peer-focus:text-[11px] peer-focus:text-brand-primary">Student's full name</label>
        </div>
        <div class="relative">
          <input id="student-dob" name="student_dob" type="date" placeholder=" " required
                 class="peer w-full rounded-xl border border-slate-200 bg-slate-50/60 px-4 pt-6 pb-2.5 text-sm outline-none transition-all focus:ring-2 focus:ring-brand-primary focus:border-transparent">
          <label for="student-dob" class="absolute left-4 top-2 text-[11px] font-semibold text-slate-400">Date of birth</label>
        </div>
        <div class="relative sm:col-span-2">
          <select id="student-level" name="student_level" required
                  class="peer w-full rounded-xl border border-slate-200 bg-slate-50/60 px-4 pt-6 pb-2.5 text-sm outline-none transition-all focus:ring-2 focus:ring-brand-primary focus:border-transparent appearance-none">
            <option value="" disabled selected></option>
            <option>Creche</option>
            <option>Nursery</option>
            <option>Primary</option>
            <option>Secondary</option>
          </select>
          <label for="student-level" class="absolute left-4 top-2 text-[11px] font-semibold text-brand-primary">Applying for level</label>
          <i data-lucide="chevron-down" class="absolute right-4 top-1/2 -translate-y-1/2 size-4 text-slate-400 pointer-events-none" aria-hidden="true"></i>
        </div>
      </div>
    </fieldset>

    <!-- STEP 2: Guardian -->
    <fieldset class="form-step hidden" data-step="1">
      <legend class="font-serif text-2xl font-bold text-brand-dark mb-6">Parent / guardian details</legend>
      <div class="grid gap-6 sm:grid-cols-2">
        <div class="relative">
          <input id="guardian-name" name="guardian_name" type="text" placeholder=" " required
                 class="peer w-full rounded-xl border border-slate-200 bg-slate-50/60 px-4 pt-6 pb-2.5 text-sm outline-none transition-all focus:ring-2 focus:ring-brand-primary focus:border-transparent">
          <label for="guardian-name" class="absolute left-4 top-2 text-[11px] font-semibold text-slate-400 transition-all peer-placeholder-shown:top-4 peer-placeholder-shown:text-sm peer-focus:top-2 peer-focus:text-[11px] peer-focus:text-brand-primary">Full name</label>
        </div>
        <div class="relative">
          <input id="guardian-phone" name="guardian_phone" type="tel" placeholder=" " required
                 class="peer w-full rounded-xl border border-slate-200 bg-slate-50/60 px-4 pt-6 pb-2.5 text-sm outline-none transition-all focus:ring-2 focus:ring-brand-primary focus:border-transparent">
          <label for="guardian-phone" class="absolute left-4 top-2 text-[11px] font-semibold text-slate-400 transition-all peer-placeholder-shown:top-4 peer-placeholder-shown:text-sm peer-focus:top-2 peer-focus:text-[11px] peer-focus:text-brand-primary">Phone number</label>
        </div>
        <div class="relative sm:col-span-2">
          <input id="guardian-email" name="guardian_email" type="email" placeholder=" " required
                 class="peer w-full rounded-xl border border-slate-200 bg-slate-50/60 px-4 pt-6 pb-2.5 text-sm outline-none transition-all focus:ring-2 focus:ring-brand-primary focus:border-transparent">
          <label for="guardian-email" class="absolute left-4 top-2 text-[11px] font-semibold text-slate-400 transition-all peer-placeholder-shown:top-4 peer-placeholder-shown:text-sm peer-focus:top-2 peer-focus:text-[11px] peer-focus:text-brand-primary">Email address</label>
        </div>
        <div class="relative sm:col-span-2">
          <input id="guardian-address" name="guardian_address" type="text" placeholder=" " required
                 class="peer w-full rounded-xl border border-slate-200 bg-slate-50/60 px-4 pt-6 pb-2.5 text-sm outline-none transition-all focus:ring-2 focus:ring-brand-primary focus:border-transparent">
          <label for="guardian-address" class="absolute left-4 top-2 text-[11px] font-semibold text-slate-400 transition-all peer-placeholder-shown:top-4 peer-placeholder-shown:text-sm peer-focus:top-2 peer-focus:text-[11px] peer-focus:text-brand-primary">Home address</label>
        </div>
      </div>
    </fieldset>

    <!-- STEP 3: Program -->
    <fieldset class="form-step hidden" data-step="2">
      <legend class="font-serif text-2xl font-bold text-brand-dark mb-6">Choose a curriculum</legend>
      <div class="grid gap-4 sm:grid-cols-3">
        <?php foreach ($school['curricula'] as $i => $curriculum): ?>
          <label class="relative flex cursor-pointer flex-col gap-2 rounded-2xl border-2 border-slate-200 p-5 has-[:checked]:border-brand-primary has-[:checked]:bg-brand-primary/5 transition-colors">
            <input type="radio" name="curriculum" value="<?= htmlspecialchars($curriculum) ?>" class="sr-only" <?= $i === 0 ? 'required' : '' ?>>
            <i data-lucide="book-marked" class="size-5 text-brand-primary" aria-hidden="true"></i>
            <span class="text-sm font-bold text-brand-dark"><?= htmlspecialchars($curriculum) ?></span>
          </label>
        <?php endforeach; ?>
      </div>
      <div class="mt-6 flex items-center gap-3 rounded-2xl bg-brand-primary/5 p-5">
        <i data-lucide="bus" class="size-5 text-brand-primary shrink-0" aria-hidden="true"></i>
        <label class="flex items-center gap-2.5 text-sm font-semibold text-brand-dark">
          <input type="checkbox" name="school_bus" class="size-4 rounded border-slate-300 text-brand-primary focus:ring-brand-primary">
          We'd like to use the school bus service
        </label>
      </div>
    </fieldset>

    <!-- STEP 4: Documents -->
    <fieldset class="form-step hidden" data-step="3">
      <legend class="font-serif text-2xl font-bold text-brand-dark mb-6">Upload documents</legend>
      <p class="text-sm text-slate-500 mb-5">Birth certificate, passport photograph and previous school report (if applicable). PDF, JPG or PNG — up to 10MB each.</p>

      <label for="file-upload" id="dropzone"
             class="flex flex-col items-center justify-center gap-3 rounded-2xl border-2 border-dashed border-brand-primary/40 bg-brand-primary/5 px-6 py-12 text-center cursor-pointer transition-colors hover:bg-brand-primary/10 focus-within:ring-2 focus-within:ring-brand-primary">
        <i data-lucide="upload-cloud" class="size-8 text-brand-primary" aria-hidden="true"></i>
        <span class="text-sm font-bold text-brand-dark">Drag &amp; drop files here, or click to browse</span>
        <span class="text-xs text-slate-500">PDF, JPG, PNG — up to 10MB</span>
        <input id="file-upload" name="documents[]" type="file" multiple accept=".pdf,.jpg,.jpeg,.png" class="sr-only">
      </label>
      <ul id="file-list" class="mt-4 space-y-2 text-sm text-slate-600"></ul>
    </fieldset>

    <!-- Nav buttons -->
    <div class="mt-10 flex items-center justify-between">
      <button type="button" id="btn-prev" class="inline-flex items-center gap-2 rounded-full border border-slate-200 px-6 py-3 text-sm font-bold text-slate-500 transition-colors hover:bg-slate-50 invisible">
        <i data-lucide="arrow-left" class="size-4" aria-hidden="true"></i> Back
      </button>
      <button type="button" id="btn-next" class="inline-flex items-center gap-2 rounded-full bg-brand-primary px-7 py-3.5 text-sm font-bold text-white transition-all duration-300 ease-in-out hover:-translate-y-0.5">
        Continue <i data-lucide="arrow-right" class="size-4" aria-hidden="true"></i>
      </button>
      <button type="submit" id="btn-submit" class="hidden inline-flex items-center gap-2 rounded-full bg-brand-primary px-7 py-3.5 text-sm font-bold text-white transition-all duration-300 ease-in-out hover:-translate-y-0.5">
        Submit application <i data-lucide="check" class="size-4" aria-hidden="true"></i>
      </button>
    </div>
  </form>

  <div id="success-message" class="hidden rounded-3xl bg-brand-primary/10 p-10 text-center">
    <i data-lucide="check-circle-2" class="mx-auto mb-4 size-12 text-brand-primary" aria-hidden="true"></i>
    <h2 class="font-serif text-2xl font-bold text-brand-dark mb-2">Application received</h2>
    <p class="text-sm text-slate-600">Thank you. Our admissions team will review your application and contact you at the phone number or email provided.</p>
  </div>
</section>

<script>
  (function () {
    const form = document.getElementById('admissions-form');
    const stepsEls = Array.from(document.querySelectorAll('.form-step'));
    const bars = Array.from(document.querySelectorAll('[data-bar]'));
    const labels = Array.from(document.querySelectorAll('[data-label]'));
    const btnPrev = document.getElementById('btn-prev');
    const btnNext = document.getElementById('btn-next');
    const btnSubmit = document.getElementById('btn-submit');
    let current = 0;

    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    stepsEls.forEach(el => { el.style.transition = 'opacity .35s ease, transform .35s ease'; });

    function render() {
      stepsEls.forEach((el, i) => {
        if (i === current) {
          el.classList.remove('hidden');
          el.style.opacity = reduceMotion ? '1' : '0';
          el.style.transform = reduceMotion ? 'none' : 'translateY(10px)';
          requestAnimationFrame(() => requestAnimationFrame(() => { el.style.opacity = '1'; el.style.transform = 'translateY(0)'; }));
        } else {
          el.classList.add('hidden');
        }
      });
      bars.forEach((el, i) => el.classList.toggle('bg-brand-primary', i <= current) || el.classList.toggle('bg-brand-primary/15', i > current));
      labels.forEach((el, i) => el.classList.toggle('text-brand-primary', i <= current) || el.classList.toggle('text-slate-400', i > current));
      btnPrev.classList.toggle('invisible', current === 0);
      btnNext.classList.toggle('hidden', current === stepsEls.length - 1);
      btnSubmit.classList.toggle('hidden', current !== stepsEls.length - 1);
    }

    function currentStepValid() {
      const inputs = stepsEls[current].querySelectorAll('[required]');
      for (const input of inputs) {
        if (input.type === 'radio') {
          const group = stepsEls[current].querySelectorAll(`[name="${input.name}"]`);
          if (![...group].some(r => r.checked)) { input.reportValidity(); return false; }
        } else if (!input.checkValidity()) { input.reportValidity(); return false; }
      }
      return true;
    }

    btnNext.addEventListener('click', () => {
      if (!currentStepValid()) return;
      current = Math.min(current + 1, stepsEls.length - 1);
      render();
    });
    btnPrev.addEventListener('click', () => { current = Math.max(current - 1, 0); render(); });

    form.addEventListener('submit', (e) => {
      e.preventDefault();
      if (!currentStepValid()) return;
      form.classList.add('hidden');
      document.getElementById('step-progress').classList.add('hidden');
      document.getElementById('success-message').classList.remove('hidden');
    });

    // Dropzone
    const dropzone = document.getElementById('dropzone');
    const fileInput = document.getElementById('file-upload');
    const fileList = document.getElementById('file-list');
    function renderFiles(files) {
      fileList.innerHTML = '';
      Array.from(files).forEach(f => {
        const li = document.createElement('li');
        li.className = 'flex items-center gap-2 rounded-lg bg-slate-50 px-3 py-2';
        li.innerHTML = `<i data-lucide="file" class="size-4 text-brand-primary"></i><span>${f.name}</span>`;
        fileList.appendChild(li);
      });
      if (window.lucide) lucide.createIcons();
    }
    fileInput.addEventListener('change', () => renderFiles(fileInput.files));
    ['dragenter', 'dragover'].forEach(evt => dropzone.addEventListener(evt, (e) => { e.preventDefault(); dropzone.classList.add('bg-brand-primary/15'); }));
    ['dragleave', 'drop'].forEach(evt => dropzone.addEventListener(evt, (e) => { e.preventDefault(); dropzone.classList.remove('bg-brand-primary/15'); }));
    dropzone.addEventListener('drop', (e) => { fileInput.files = e.dataTransfer.files; renderFiles(fileInput.files); });

    render();
  })();
</script>

<?php require __DIR__ . '/partials/footer.php'; ?>
