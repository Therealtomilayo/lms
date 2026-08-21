</main>

<footer class="bg-brand-dark text-white">
  <div class="mx-auto max-w-7xl px-5 lg:px-8 py-16 grid gap-12 md:grid-cols-2 lg:grid-cols-[1.3fr_0.9fr_0.9fr_1.1fr]">

    <div>
      <img src="assets/img/logo.png" alt="<?= htmlspecialchars($school['name']) ?>" class="h-20 w-20 object-contain mb-5 bg-white rounded-2xl p-1.5">
      <p class="max-w-xs text-sm leading-6 text-white/70">Raising confident, curious learners who lead with discipline, integrity and ardour, from creche through secondary school.</p>
      <div class="flex items-center gap-3 mt-6">
        <a href="https://www.facebook.com/ClaretInternationalSchool/" target="_blank" rel="noopener noreferrer" aria-label="Facebook" class="rounded-full border border-white/20 p-2.5 hover:bg-white/10 hover:border-white/40 transition-colors">
          <img src="https://cdn.simpleicons.org/facebook/ffffff" class="size-4" alt="" aria-hidden="true" width="16" height="16">
        </a>
        <a href="https://www.instagram.com/claretintlschoolabuja/" target="_blank" rel="noopener noreferrer" aria-label="Instagram" class="rounded-full border border-white/20 p-2.5 hover:bg-white/10 hover:border-white/40 transition-colors">
          <img src="https://cdn.simpleicons.org/instagram/ffffff" class="size-4" alt="" aria-hidden="true" width="16" height="16">
        </a>
        <a href="https://www.youtube.com/channel/UCJN9AQtW-iwW5SnWsyCGz-A" target="_blank" rel="noopener noreferrer" aria-label="YouTube" class="rounded-full border border-white/20 p-2.5 hover:bg-white/10 hover:border-white/40 transition-colors">
          <img src="https://cdn.simpleicons.org/youtube/ffffff" class="size-4" alt="" aria-hidden="true" width="16" height="16">
        </a>
      </div>
    </div>

    <div>
      <h3 class="text-xs font-bold uppercase tracking-[0.2em] text-brand-accent mb-5">Quick links</h3>
      <ul class="flex flex-col gap-3 text-sm text-white/70">
        <?php foreach ($nav as $href => $label): ?>
          <li><a href="<?= $href ?>" class="hover:text-white transition-colors"><?= $label ?></a></li>
        <?php endforeach; ?>
        <li><a href="/portal" class="hover:text-white transition-colors">Parent portal</a></li>
      </ul>
    </div>

    <div>
      <h3 class="text-xs font-bold uppercase tracking-[0.2em] text-brand-accent mb-5">School life</h3>
      <ul class="flex flex-col gap-3 text-sm text-white/70">
        <li>Creche &amp; Nursery</li>
        <li>Primary School</li>
        <li>Secondary School</li>
        <li>Clubs &amp; enrichment</li>
      </ul>
    </div>

    <div>
      <h3 class="text-xs font-bold uppercase tracking-[0.2em] text-brand-accent mb-5">Stay in the loop</h3>
      <p class="text-sm text-white/70 mb-4">Term dates, admissions news and school life, straight to your inbox.</p>
      <form class="flex items-center gap-2" action="#" method="post">
        <label for="newsletter-email" class="sr-only">Email address</label>
        <input id="newsletter-email" name="email" type="email" required placeholder="you@email.com"
               class="min-w-0 flex-1 rounded-full border border-white/20 bg-white/5 px-4 py-2.5 text-sm text-white placeholder:text-white/40 outline-none focus:ring-2 focus:ring-brand-primary focus:border-transparent transition-all">
        <button type="submit" aria-label="Subscribe to newsletter"
                class="shrink-0 inline-flex items-center justify-center rounded-full bg-brand-primary p-2.5 hover:bg-brand-primary/85 transition-colors">
          <i data-lucide="send" class="size-4" aria-hidden="true"></i>
        </button>
      </form>

      <div class="mt-7 space-y-2.5 text-sm text-white/70">
        <a href="tel:<?= htmlspecialchars($school['phone_href']) ?>" class="flex items-start gap-2.5 hover:text-white transition-colors">
          <i data-lucide="phone" class="size-4 mt-0.5 shrink-0" aria-hidden="true"></i> <?= htmlspecialchars($school['phone']) ?>
        </a>
        <p class="flex items-start gap-2.5">
          <i data-lucide="map-pin" class="size-4 mt-0.5 shrink-0" aria-hidden="true"></i>
          <span><?= htmlspecialchars($school['address_line1']) ?>, <?= htmlspecialchars($school['address_line2']) ?></span>
        </p>
      </div>
    </div>
  </div>

  <div class="border-t border-white/10">
    <div class="mx-auto max-w-7xl px-5 lg:px-8 py-6 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-white/50">
      <p>© <?= date('Y') ?> <?= htmlspecialchars($school['name']) ?>. All rights reserved.</p>
      <p><?= htmlspecialchars($school['tagline']) ?></p>
    </div>
  </div>
</footer>

<script>
  document.addEventListener('DOMContentLoaded', () => { if (window.lucide) lucide.createIcons(); });
  window.addEventListener('load', () => { if (window.lucide) lucide.createIcons(); });

  // Scroll-triggered reveal, staggered by DOM order within each parent
  (function () {
    const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const targets = document.querySelectorAll('.reveal, .reveal-scale');
    if (reduce || !('IntersectionObserver' in window)) {
      targets.forEach(el => el.classList.add('is-visible'));
      return;
    }
    const groups = new Map();
    targets.forEach(el => {
      const parent = el.parentElement;
      const idx = groups.get(parent)?.length || 0;
      el.style.setProperty('--reveal-delay', Math.min(idx * 90, 360) + 'ms');
      groups.set(parent, [...(groups.get(parent) || []), el]);
    });
    const io = new IntersectionObserver((entries, obs) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) { entry.target.classList.add('is-visible'); obs.unobserve(entry.target); }
      });
    }, { threshold: 0.15, rootMargin: '0px 0px -60px 0px' });
    targets.forEach(el => io.observe(el));
  })();

  // Animated stat counters — plays once when the card scrolls into view
  (function () {
    const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const counters = document.querySelectorAll('[data-count-to]');
    if (!counters.length) return;
    function animateCounter(el) {
      const to = parseFloat(el.getAttribute('data-count-to'));
      const suffix = el.getAttribute('data-count-suffix') || '';
      if (reduce || isNaN(to)) { el.textContent = to + suffix; return; }
      const duration = 1400;
      const start = performance.now();
      function tick(now) {
        const p = Math.min((now - start) / duration, 1);
        const eased = 1 - Math.pow(1 - p, 3);
        el.textContent = Math.round(to * eased) + suffix;
        if (p < 1) requestAnimationFrame(tick);
      }
      requestAnimationFrame(tick);
    }
    if (!('IntersectionObserver' in window)) { counters.forEach(animateCounter); return; }
    const io = new IntersectionObserver((entries, obs) => {
      entries.forEach(entry => { if (entry.isIntersecting) { animateCounter(entry.target); obs.unobserve(entry.target); } });
    }, { threshold: 0.6 });
    counters.forEach(el => io.observe(el));
  })();
</script>
</body>
</html>
