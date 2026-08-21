<?php
require_once __DIR__ . '/config.php';
$page_title = 'Home';
$meta_description = 'Claret International School, Mabushi Abuja — a citadel of learning raising 21st century leaders through the Nigerian National, British and Montessori curricula.';
require __DIR__ . '/partials/header.php';
?>

<!-- HERO -->
<section class="relative overflow-hidden bg-brand-dark" aria-label="Introduction">
  <div id="hero-slides" class="relative min-h-[640px] lg:min-h-[720px]">

    <div class="hero-slide absolute inset-0" data-slide="0">
      <img src="assets/img/hero-carousel.jpg" alt="Claret International School graduates celebrating on stage" class="absolute inset-0 h-full w-full object-cover object-top opacity-40">
      <div class="absolute inset-0 bg-gradient-to-r from-brand-dark via-brand-dark/90 to-brand-dark/30"></div>
    </div>
    <div class="hero-slide absolute inset-0" data-slide="1">
      <img src="assets/img/hero-carousel-2.jpg" alt="Claret International School campus and learners" class="absolute inset-0 h-full w-full object-cover object-center opacity-40">
      <div class="absolute inset-0 bg-gradient-to-r from-brand-dark via-brand-dark/90 to-brand-dark/30"></div>
    </div>

    <div class="relative mx-auto max-w-7xl px-5 lg:px-8 h-full">
      <div class="grid lg:grid-cols-[1.15fr_0.85fr] gap-10 items-center min-h-[640px] lg:min-h-[720px] py-28">

        <!-- Copy -->
        <div class="text-white">
          <p class="load-in mb-5 inline-flex items-center gap-2 rounded-full border border-white/25 px-4 py-1.5 text-xs font-bold uppercase tracking-[0.2em] text-brand-accent" style="--load-delay:0ms">
            Est. 2015 &middot; Mabushi, Abuja
          </p>
          <h1 id="hero-heading" class="load-in text-balance font-serif text-5xl md:text-6xl lg:text-7xl font-bold leading-[1.04]" style="--load-delay:120ms">
            Where every child discovers their brilliance.
          </h1>
          <p id="hero-copy" class="load-in mt-7 max-w-xl text-lg leading-8 text-white/85" style="--load-delay:260ms">
            A citadel of learning raising 21st century leaders, through the Nigerian National, British and Montessori curricula, taught by qualified tutors in a safe, well-equipped environment.
          </p>
          <div class="load-in mt-9 flex flex-wrap gap-4" style="--load-delay:400ms">
            <a href="/admissions" class="inline-flex items-center gap-2 rounded-full bg-brand-primary px-7 py-4 text-sm font-bold text-white transition-all duration-300 ease-in-out hover:-translate-y-0.5 hover:scale-[1.03] hover:shadow-xl hover:shadow-brand-primary/30">
              Begin your journey <i data-lucide="arrow-right" class="size-4" aria-hidden="true"></i>
            </a>
            <a href="/about" class="inline-flex items-center gap-2 rounded-full border border-white/40 px-7 py-4 text-sm font-bold text-white transition-colors duration-300 ease-in-out hover:bg-white/10">
              Discover Claret
            </a>
          </div>
        </div>

        <!-- Overlapping image collage -->
        <div class="relative hidden lg:block h-[520px] load-in" style="--load-delay:200ms">
          <div class="drift absolute top-0 right-0 w-[78%] h-[62%] rounded-3xl overflow-hidden shadow-floaty ring-4 ring-white/10" style="--drift-rot:0deg">
            <img src="assets/img/hero-carousel-2.jpg" alt="" class="h-full w-full object-cover object-top" aria-hidden="true">
          </div>
          <div class="drift absolute bottom-0 left-0 w-[58%] h-[46%] rounded-3xl overflow-hidden shadow-floaty ring-4 ring-brand-dark/40 rotate-[-3deg]" style="--drift-rot:-3deg; animation-delay:-3s;">
            <img src="assets/img/Claret-International-School-4-1024x576.jpg" alt="" class="h-full w-full object-cover object-bottom" aria-hidden="true">
          </div>
          <div class="absolute -bottom-2 -right-2 size-24 rounded-full bg-brand-accent/90 flex items-center justify-center text-white text-center text-xs font-bold leading-tight p-3 shadow-floaty animate-pulse motion-reduce:animate-none">
            CIS since day one
          </div>
        </div>
      </div>
    </div>

    <!-- Carousel controls -->
    <div class="absolute bottom-7 right-5 lg:right-8 z-10 flex items-center gap-3">
      <button type="button" id="hero-prev" aria-label="Previous slide" class="rounded-full border border-white/40 p-2 text-white hover:bg-white/10 transition-colors"><i data-lucide="chevron-left" class="size-4" aria-hidden="true"></i></button>
      <span class="text-xs font-bold text-white tracking-widest"><span id="hero-count">01</span> / 02</span>
      <button type="button" id="hero-next" aria-label="Next slide" class="rounded-full border border-white/40 p-2 text-white hover:bg-white/10 transition-colors"><i data-lucide="chevron-right" class="size-4" aria-hidden="true"></i></button>
    </div>

    <!-- Scroll indicator -->
    <div class="absolute bottom-7 left-5 lg:left-8 z-10 hidden sm:flex items-center gap-2 text-white/70 text-xs font-semibold uppercase tracking-[0.2em] animate-bounce motion-reduce:animate-none">
      <i data-lucide="mouse" class="size-4" aria-hidden="true"></i> Scroll
    </div>
  </div>
</section>

<style>
  /* Hero slide crossfade */
  .hero-slide { opacity: 0; transition: opacity 0.85s cubic-bezier(.22,.61,.36,1); }
  .hero-slide.is-active { opacity: 1; }

  /* Hero text swap — driven via inline style transitions, no class flash */
  #hero-heading, #hero-copy {
    transition: opacity 0.3s ease, transform 0.3s ease;
    will-change: opacity, transform;
  }

  /* Science card */
  .bento-science-card { position: relative; overflow: hidden; }
  .bento-science-card img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; object-position: center; }
  .bento-science-card .overlay { position: absolute; inset: 0; background: linear-gradient(135deg, rgba(123,48,70,0.88) 0%, rgba(12,157,213,0.55) 100%); }
  .bento-science-card .content { position: relative; z-index: 1; }

  /* Clubs mini-carousel */
  .clubs-carousel-track { display: flex; width: 200%; transition: transform 0.7s cubic-bezier(.22,.61,.36,1); }
  .clubs-carousel-track .club-slide { width: 50%; height: 100%; flex-shrink: 0; position: relative; }
  .clubs-carousel-track .club-slide img { width: 100%; height: 100%; object-fit: cover; }
  .clubs-carousel-track .club-slide .overlay { position: absolute; inset: 0; background: linear-gradient(to top, rgba(123,48,70,0.75) 0%, transparent 60%); }
</style>

<script>
  (function () {
    const slidesData = [
      { heading: 'Where every child discovers their brilliance.', copy: 'A citadel of learning raising 21st century leaders, through the Nigerian National, British and Montessori curricula, taught by qualified tutors in a safe, well-equipped environment.' },
      { heading: 'Learning with purpose. Living with ardour.', copy: 'From creche to graduation, our clubs, labs and dedicated teachers help every learner find their voice and their calling.' },
    ];
    const slides = document.querySelectorAll('.hero-slide');
    const heading = document.getElementById('hero-heading');
    const copy = document.getElementById('hero-copy');
    const count = document.getElementById('hero-count');
    let active = 0;
    let animating = false;

    function swapText(el, newText) {
      // Phase 1: fade + slide out (instant transition override)
      el.style.transition = 'opacity 0.25s ease, transform 0.25s ease';
      el.style.opacity = '0';
      el.style.transform = 'translateY(-10px)';

      setTimeout(() => {
        // Swap content while invisible — no flash possible
        el.textContent = newText;
        // Reset to entry position with NO transition so it snaps below
        el.style.transition = 'none';
        el.style.opacity = '0';
        el.style.transform = 'translateY(14px)';

        // Force a layout flush so the browser acknowledges the snap before we re-enable the transition
        void el.offsetHeight;

        // Phase 2: fade + slide in
        el.style.transition = 'opacity 0.45s cubic-bezier(.22,.61,.36,1), transform 0.45s cubic-bezier(.22,.61,.36,1)';
        el.style.opacity = '1';
        el.style.transform = 'translateY(0)';
      }, 260);
    }

    function show(i) {
      if (animating) return;
      animating = true;
      slides.forEach((s, idx) => {
        s.classList.toggle('is-active', idx === i);
      });
      swapText(heading, slidesData[i].heading);
      swapText(copy, slidesData[i].copy);
      count.textContent = String(i + 1).padStart(2, '0');
      active = i;
      setTimeout(() => { animating = false; }, 900);
    }

    // init first slide
    slides[0].classList.add('is-active');

    document.getElementById('hero-next')?.addEventListener('click', () => show((active + 1) % slides.length));
    document.getElementById('hero-prev')?.addEventListener('click', () => show((active - 1 + slides.length) % slides.length));

    const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (!reduce) setInterval(() => show((active + 1) % slides.length), 7000);
  })();

  // Clubs mini-carousel auto-play
  (function () {
    const track = document.getElementById('clubs-track');
    if (!track) return;
    let idx = 0;
    const count = track.querySelectorAll('.club-slide').length;
    const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (reduce) return;
    setInterval(() => {
      idx = (idx + 1) % count;
      track.style.transform = `translateX(-${idx * 50}%)`;
    }, 3200);
  })();
</script>

<!-- STATS: floating overlap card -->
<section class="relative z-10 -mt-12 mb-4">
  <div class="mx-auto max-w-6xl px-5 lg:px-8">
    <div class="reveal-scale rounded-3xl bg-white shadow-floaty ring-1 ring-brand-dark/5 px-8 py-8 grid gap-8 sm:grid-cols-3">
      <?php
      $icons = ['graduation-cap', 'trophy', 'users'];
      $i = 0;
      foreach ($school['stats'] as $stat):
        preg_match('/([\d.]+)(.*)/', $stat['value'], $m);
        $has_number = isset($m[1]) && $m[1] !== '';
      ?>
        <div class="flex items-center gap-4 lift-hover">
          <span class="flex size-12 items-center justify-center rounded-2xl bg-brand-primary/10 text-brand-primary shrink-0">
            <i data-lucide="<?= $icons[$i % count($icons)] ?>" class="size-6" aria-hidden="true"></i>
          </span>
          <div>
            <?php if ($has_number): ?>
              <strong class="block font-serif text-3xl text-brand-dark" data-count-to="<?= htmlspecialchars($m[1]) ?>" data-count-suffix="<?= htmlspecialchars($m[2]) ?>">0<?= htmlspecialchars($m[2]) ?></strong>
            <?php else: ?>
              <strong class="block font-serif text-3xl text-brand-dark"><?= htmlspecialchars($stat['value']) ?></strong>
            <?php endif; ?>
            <span class="text-sm text-slate-500"><?= htmlspecialchars($stat['label']) ?></span>
          </div>
        </div>
      <?php $i++; endforeach; ?>
    </div>
  </div>
</section>

<!-- BENTO GRID: Why choose Claret -->
<section class="mx-auto max-w-7xl px-5 lg:px-8 py-20 lg:py-28">
  <div class="max-w-2xl mb-12">
    <p class="mb-3 text-sm font-bold uppercase tracking-[0.22em] text-brand-primary">The Claret difference</p>
    <h2 class="text-balance font-serif text-4xl md:text-5xl font-bold leading-tight text-brand-dark">A strong start for a remarkable life.</h2>
    <p class="mt-5 text-base leading-7 text-slate-600">Education at Claret is more than a timetable. It's the confidence to ask, the discipline to grow, and the joy of becoming, backed by facilities and clubs built for curious minds.</p>
  </div>

  <div class="grid gap-5 md:grid-cols-4 md:auto-rows-[220px]">
    <!-- Tech & Science Labs — image card with science.jpg + brand tint -->
    <div class="reveal lift-hover bento-science-card md:col-span-2 md:row-span-2 rounded-3xl text-white p-8 flex flex-col justify-between group">
      <img src="assets/img/science.jpg" alt="Claret science laboratory" aria-hidden="true">
      <div class="overlay"></div>
      <div class="content">
        <i data-lucide="monitor" class="size-8 text-brand-accent" aria-hidden="true"></i>
      </div>
      <div class="content">
        <h3 class="font-serif text-2xl font-bold">Tech &amp; science labs</h3>
        <p class="mt-2 text-sm leading-6 text-white/80">Purpose-built labs where coding, robotics and the sciences come alive, preparing learners for the work of tomorrow.</p>
      </div>
    </div>

    <div class="reveal lift-hover md:col-span-2 rounded-3xl bg-brand-primary/10 p-7 flex items-center gap-5">
      <i data-lucide="book-open" class="size-8 text-brand-primary shrink-0" aria-hidden="true"></i>
      <div>
        <h3 class="font-serif text-xl font-bold text-brand-dark">Three curricula, one standard</h3>
        <p class="mt-1.5 text-sm leading-6 text-slate-600">Nigerian National, British and Montessori, matched to how your child learns best.</p>
      </div>
    </div>

    <!-- 12+ Clubs — mini swipe carousel showing art.jpg + sports.jpg -->
    <div class="reveal lift-hover rounded-3xl overflow-hidden ring-1 ring-brand-dark/10 flex flex-col justify-between relative" id="clubs-bento">
      <div class="overflow-hidden h-full relative">
        <div id="clubs-track" class="clubs-carousel-track h-full">
          <div class="club-slide">
            <img src="assets/img/art.jpg" alt="Art club at Claret International School">
            <div class="overlay"></div>
          </div>
          <div class="club-slide">
            <img src="assets/img/sports.jpg" alt="Sports activities at Claret International School">
            <div class="overlay"></div>
          </div>
        </div>
        <div class="absolute bottom-0 left-0 right-0 p-5 z-10">
          <i data-lucide="palette" class="size-5 text-white/80 mb-1" aria-hidden="true"></i>
          <h3 class="font-serif text-lg font-bold text-white">12+ clubs</h3>
          <p class="mt-0.5 text-xs leading-5 text-white/75">Academic, sports, arts &amp; STEM.</p>
        </div>
      </div>
    </div>

    <div class="reveal lift-hover rounded-3xl bg-white ring-1 ring-brand-dark/10 p-7 flex flex-col justify-between">
      <i data-lucide="bus" class="size-7 text-brand-primary" aria-hidden="true"></i>
      <div>
        <h3 class="font-serif text-lg font-bold text-brand-dark">School bus</h3>
        <p class="mt-1 text-sm leading-6 text-slate-600">Safe daily transport available.</p>
      </div>
    </div>

    <div class="reveal lift-hover md:col-span-2 rounded-3xl bg-brand-accent/10 p-7 flex items-center gap-5">
      <i data-lucide="shield-check" class="size-8 text-brand-accent shrink-0" aria-hidden="true"></i>
      <div>
        <h3 class="font-serif text-xl font-bold text-brand-dark">Safe, well-equipped campus</h3>
        <p class="mt-1.5 text-sm leading-6 text-slate-600">A day school in Mabushi where every learner is known, valued and looked after.</p>
      </div>
    </div>
  </div>
</section>

<!-- PRIMARY vs SECONDARY — signature asymmetrical seam -->
<section class="relative bg-brand-bg py-4">
  <div class="mx-auto max-w-7xl px-5 lg:px-8">
    <p class="mb-3 text-sm font-bold uppercase tracking-[0.22em] text-brand-primary">Two stages, one journey</p>
    <h2 class="max-w-2xl text-balance font-serif text-4xl md:text-5xl font-bold leading-tight text-brand-dark mb-12">Built around the child in front of us.</h2>
  </div>

  <div class="mx-auto max-w-7xl px-5 lg:px-8 grid lg:grid-cols-2 gap-0 lg:gap-0">

    <!-- Primary: bright, rounded -->
    <div class="reveal relative rounded-3xl lg:rounded-r-none bg-white ring-1 ring-brand-dark/10 p-10 pb-16 lg:pr-14 clip-seam">
      <span class="inline-flex items-center gap-2 rounded-full bg-brand-primary/10 text-brand-primary text-xs font-bold uppercase tracking-widest px-4 py-1.5 mb-6">
        <i data-lucide="smile" class="size-3.5" aria-hidden="true"></i> Creche, Nursery &amp; Primary
      </span>
      <h3 class="font-serif text-3xl font-bold text-brand-dark mb-4">Bright beginnings</h3>
      <p class="text-sm leading-7 text-slate-600 mb-6 max-w-md">Warm classrooms, staggered play-and-learn spaces and teachers who make every first step feel safe, from Creche through Primary.</p>
      <ul class="space-y-3 text-sm text-slate-700 mb-8">
        <li class="flex items-center gap-2.5"><i data-lucide="check-circle-2" class="size-4 text-brand-primary shrink-0" aria-hidden="true"></i> Montessori-informed early years</li>
        <li class="flex items-center gap-2.5"><i data-lucide="check-circle-2" class="size-4 text-brand-primary shrink-0" aria-hidden="true"></i> Art &amp; Craft, Dance and Fitness clubs</li>
        <li class="flex items-center gap-2.5"><i data-lucide="check-circle-2" class="size-4 text-brand-primary shrink-0" aria-hidden="true"></i> Small class sizes, close attention</li>
      </ul>
      <div class="grid grid-cols-2 gap-3">
        <img src="assets/img/Claret-International-School-12-1024x576.jpg" alt="Claret primary learners at their graduation ceremony" class="rounded-2xl h-32 w-full object-cover object-left-top">
        <img src="assets/img/Claret-International-School-9-1024x576.jpg" alt="Claret primary learners celebrating with medals" class="rounded-2xl h-32 w-full object-cover object-right-top mt-6">
      </div>
    </div>

    <!-- Secondary: sleek, dark, sharp -->
    <div class="reveal relative bg-brand-dark text-white p-10 pt-16 lg:pl-14 rounded-3xl lg:rounded-l-none -mt-6 lg:mt-0 clip-seam-reverse" style="--reveal-delay:150ms">
      <span class="inline-flex items-center gap-2 rounded-md bg-brand-accent/20 text-brand-accent text-xs font-bold uppercase tracking-widest px-4 py-1.5 mb-6">
        <i data-lucide="cpu" class="size-3.5" aria-hidden="true"></i> Secondary School
      </span>
      <h3 class="font-serif text-3xl font-bold mb-4">Future-ready leaders</h3>
      <p class="text-sm leading-7 text-white/70 mb-6 max-w-md">A sharper, academic edge, preparing learners for AI, the sciences and leadership through the British and Nigerian National curricula.</p>
      <ul class="space-y-3 text-sm text-white/85 mb-8">
        <li class="flex items-center gap-2.5"><i data-lucide="check-circle-2" class="size-4 text-brand-accent shrink-0" aria-hidden="true"></i> Coding Club &amp; STEM labs</li>
        <li class="flex items-center gap-2.5"><i data-lucide="check-circle-2" class="size-4 text-brand-accent shrink-0" aria-hidden="true"></i> Debate, Science and Math Clubs</li>
        <li class="flex items-center gap-2.5"><i data-lucide="check-circle-2" class="size-4 text-brand-accent shrink-0" aria-hidden="true"></i> British &amp; Nigerian National curricula</li>
      </ul>
      <div class="grid grid-cols-3 gap-3">
        <div class="rounded-xl bg-white/5 border border-white/10 p-4 text-center">
          <i data-lucide="brain-circuit" class="size-5 mx-auto text-brand-accent mb-2" aria-hidden="true"></i>
          <span class="text-[11px] font-semibold text-white/70">AI &amp; Tech</span>
        </div>
        <div class="rounded-xl bg-white/5 border border-white/10 p-4 text-center">
          <i data-lucide="flask-conical" class="size-5 mx-auto text-brand-accent mb-2" aria-hidden="true"></i>
          <span class="text-[11px] font-semibold text-white/70">Sciences</span>
        </div>
        <div class="rounded-xl bg-white/5 border border-white/10 p-4 text-center">
          <i data-lucide="megaphone" class="size-5 mx-auto text-brand-accent mb-2" aria-hidden="true"></i>
          <span class="text-[11px] font-semibold text-white/70">Leadership</span>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- QUOTE -->
<section class="px-5 lg:px-8 py-20 lg:py-28">
  <div class="reveal-scale mx-auto max-w-4xl text-center">
    <i data-lucide="quote" class="mx-auto mb-6 size-10 text-brand-accent" aria-hidden="true"></i>
    <blockquote class="font-serif text-3xl md:text-5xl font-bold leading-tight text-brand-dark">
      "<?= htmlspecialchars($school['director']['quote']) ?>"
    </blockquote>
    <p class="mt-6 text-sm font-bold uppercase tracking-widest text-slate-500"><?= htmlspecialchars($school['director']['name']) ?>, <?= htmlspecialchars($school['director']['role']) ?></p>
  </div>
</section>

<!-- CTA BAND -->
<section class="bg-brand-primary/10 px-5 lg:px-8 py-16">
  <div class="reveal mx-auto max-w-7xl flex flex-col md:flex-row items-start md:items-center justify-between gap-8">
    <div>
      <p class="mb-2 text-sm font-bold uppercase tracking-[0.2em] text-brand-primary">Ready when you are</p>
      <h2 class="font-serif text-3xl md:text-4xl font-bold text-brand-dark">Come and see what is possible.</h2>
    </div>
    <a href="/contact" class="inline-flex items-center gap-2 rounded-full bg-brand-dark px-7 py-4 text-sm font-bold text-white transition-all duration-300 ease-in-out hover:-translate-y-0.5 hover:scale-[1.03] hover:shadow-xl hover:shadow-brand-dark/30">
      Book a school visit <i data-lucide="calendar-days" class="size-4" aria-hidden="true"></i>
    </a>
  </div>
</section>

<?php require __DIR__ . '/partials/footer.php'; ?>
