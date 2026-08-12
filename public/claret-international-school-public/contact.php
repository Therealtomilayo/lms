<?php
require_once __DIR__ . '/config.php';
$page_title = 'Contact';
$meta_description = 'Visit or contact Claret International School at Plot 700, Gitto Street, Mabushi, Abuja.';
require __DIR__ . '/partials/header.php';

$maps_directions_url = "https://www.google.com/maps?q={$school['lat']},{$school['lng']}";
$maps_embed_url = "https://www.google.com/maps?q={$school['lat']},{$school['lng']}&z=16&output=embed";
?>

<!-- PAGE INTRO -->
<section class="bg-brand-dark text-white px-5 lg:px-8 py-16 lg:py-24">
  <div class="mx-auto max-w-7xl">
    <p class="mb-4 text-sm font-bold uppercase tracking-[0.22em] text-brand-accent">Contact</p>
    <h1 class="max-w-3xl text-balance font-serif text-4xl md:text-6xl font-bold leading-tight">Come and see Claret for yourself.</h1>
    <p class="mt-6 max-w-2xl text-base md:text-lg leading-7 text-white/75">Reach out with any question — admissions, school life, or a campus visit. We'd love to hear from you.</p>
  </div>
</section>

<section class="mx-auto max-w-7xl px-5 lg:px-8 py-16 lg:py-24">
  <div class="grid lg:grid-cols-[0.9fr_1.1fr] gap-12">

    <!-- Details -->
    <div class="reveal">
      <div class="space-y-6 mb-10">
        <div class="flex items-start gap-4">
          <span class="flex size-11 items-center justify-center rounded-2xl bg-brand-primary/10 text-brand-primary shrink-0"><i data-lucide="map-pin" class="size-5" aria-hidden="true"></i></span>
          <div>
            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">Visit us</h3>
            <p class="font-semibold text-brand-dark leading-relaxed"><?= htmlspecialchars($school['address_line1']) ?><br><?= htmlspecialchars($school['address_line2']) ?></p>
            <a href="<?= htmlspecialchars($maps_directions_url) ?>" target="_blank" rel="noopener" class="mt-2 inline-flex items-center gap-1.5 text-sm font-bold text-brand-primary hover:underline">
              Map directions <i data-lucide="arrow-up-right" class="size-3.5" aria-hidden="true"></i>
            </a>
          </div>
        </div>
        <div class="flex items-start gap-4">
          <span class="flex size-11 items-center justify-center rounded-2xl bg-brand-primary/10 text-brand-primary shrink-0"><i data-lucide="phone" class="size-5" aria-hidden="true"></i></span>
          <div>
            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">Call us</h3>
            <a href="tel:<?= htmlspecialchars($school['phone_href']) ?>" class="font-semibold text-brand-dark hover:text-brand-primary transition-colors"><?= htmlspecialchars($school['phone']) ?></a>
          </div>
        </div>
        <div class="flex items-start gap-4">
          <span class="flex size-11 items-center justify-center rounded-2xl bg-brand-primary/10 text-brand-primary shrink-0"><i data-lucide="clock" class="size-5" aria-hidden="true"></i></span>
          <div>
            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">School hours</h3>
            <p class="font-semibold text-brand-dark">Monday – Friday, 8:00am – 3:00pm</p>
          </div>
        </div>
      </div>

      <div class="rounded-3xl overflow-hidden ring-1 ring-brand-dark/10 shadow-sm aspect-[4/3]">
        <iframe
          title="Map to Claret International School, Mabushi, Abuja"
          src="<?= htmlspecialchars($maps_embed_url) ?>"
          class="h-full w-full border-0"
          loading="lazy"
          referrerpolicy="no-referrer-when-downgrade"></iframe>
      </div>
    </div>

    <!-- Form -->
    <div class="reveal rounded-3xl bg-white ring-1 ring-brand-dark/10 shadow-sm p-8 lg:p-10" style="--reveal-delay:120ms">
      <h2 class="font-serif text-2xl font-bold text-brand-dark mb-1">Send us an enquiry</h2>
      <p class="text-sm text-slate-500 mb-7">We usually respond within one working day.</p>

      <form id="contact-form" class="flex flex-col gap-6">
        <div class="grid gap-6 sm:grid-cols-2">
          <div class="relative">
            <input id="contact-name" type="text" placeholder=" " required
                   class="peer w-full rounded-xl border border-slate-200 bg-slate-50/60 px-4 pt-6 pb-2.5 text-sm outline-none transition-all focus:ring-2 focus:ring-brand-primary focus:border-transparent">
            <label for="contact-name" class="absolute left-4 top-2 text-[11px] font-semibold text-slate-400 transition-all peer-placeholder-shown:top-4 peer-placeholder-shown:text-sm peer-focus:top-2 peer-focus:text-[11px] peer-focus:text-brand-primary">Your name</label>
          </div>
          <div class="relative">
            <input id="contact-email" type="email" placeholder=" " required
                   class="peer w-full rounded-xl border border-slate-200 bg-slate-50/60 px-4 pt-6 pb-2.5 text-sm outline-none transition-all focus:ring-2 focus:ring-brand-primary focus:border-transparent">
            <label for="contact-email" class="absolute left-4 top-2 text-[11px] font-semibold text-slate-400 transition-all peer-placeholder-shown:top-4 peer-placeholder-shown:text-sm peer-focus:top-2 peer-focus:text-[11px] peer-focus:text-brand-primary">Email address</label>
          </div>
        </div>
        <div class="relative">
          <textarea id="contact-message" rows="5" placeholder=" " required
                    class="peer w-full resize-none rounded-xl border border-slate-200 bg-slate-50/60 px-4 pt-6 pb-2.5 text-sm outline-none transition-all focus:ring-2 focus:ring-brand-primary focus:border-transparent"></textarea>
          <label for="contact-message" class="absolute left-4 top-2 text-[11px] font-semibold text-slate-400 transition-all peer-placeholder-shown:top-4 peer-placeholder-shown:text-sm peer-focus:top-2 peer-focus:text-[11px] peer-focus:text-brand-primary">How can we help?</label>
        </div>
        <button type="submit" class="inline-flex w-fit items-center gap-2 rounded-full bg-brand-primary px-7 py-3.5 text-sm font-bold text-white transition-all duration-300 ease-in-out hover:-translate-y-0.5">
          Send enquiry <i data-lucide="send" class="size-4" aria-hidden="true"></i>
        </button>
      </form>

      <div id="contact-success" class="hidden items-start gap-3 rounded-xl bg-brand-primary/10 p-5 text-brand-dark mt-2">
        <i data-lucide="check" class="size-5 mt-0.5 text-brand-primary shrink-0" aria-hidden="true"></i>
        <p class="text-sm leading-6">Thank you — your enquiry has been sent. Our admissions team will be in touch shortly.</p>
      </div>
    </div>
  </div>
</section>

<script>
  const cForm = document.getElementById('contact-form');
  cForm?.addEventListener('submit', (e) => {
    e.preventDefault();
    cForm.classList.add('hidden');
    const success = document.getElementById('contact-success');
    success.classList.remove('hidden');
    success.classList.add('flex');
  });
</script>

<?php require __DIR__ . '/partials/footer.php'; ?>
