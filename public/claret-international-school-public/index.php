<?php
require_once __DIR__ . '/config.php';
$page_title = 'Home';
$meta_description = 'Claret International School, Mabushi Abuja — a citadel of learning raising 21st century leaders through the Nigerian National, British and Montessori curricula.';
require __DIR__ . '/partials/header.php';
?>

<!-- HERO -->
<section class="relative overflow-hidden bg-brand-dark" aria-label="Introduction">
  <div id="hero-slides" class="relative min-h-[640px] lg:min-h-[720px]">

    <div class="hero-slide absolute inset-0 transition-opacity duration-700 ease-in-out opacity-100" data-slide="0">
      <img src="assets/img/hero-carousel.jpg" alt="Claret International School graduates celebrating on stage" class="absolute inset-0 h-full w-full object-cover object-top opacity-40">
      <div class="absolute inset-0 bg-gradient-to-r from-brand-dark via-brand-dark/90 to-brand-dark/30"></div>
    </div>
    <div class="hero-slide absolute inset-0 transition-opacity duration-700 ease-in-out opacity-0" data-slide="1">
      <img src="assets/img/hero-carousel.jpg" alt="Claret International School graduates raising their caps" class="absolute inset-0 h-full w-full object-cover object-center opacity-40">
      <div class="absolute inset-0 bg-gradient-to-r from-brand-dark via-brand-dark/90 to-brand-dark/30"></div>
    </div>

    <div class="relative mx-auto max-w-7xl px-5 lg:px-8 h-full">
      <div class="grid lg:grid-cols-[1.15fr_0.85fr] gap-10 items-center min-h-[640px] lg:min-h-[720px] py-28">

        <!-- Copy -->
        <div class="text-white">
          <p class="load-in mb-5 inline-flex items-center gap-2 rounded-full border border-white/25 px-4 py-1.5 text-xs font-bold uppercase tracking-[0.2em] text-brand-accent" style="--load-delay:0ms">
            <i data-lucide="sparkles" class="size-3.5" aria-hidden="true"></i> Claret International School
          </p>
          <h1 id="hero-heading" class="load-in text-balance font-serif text-5xl md:text-6xl lg:text-7xl font-bold leading-[1.04]" style="--load-delay:120ms">
            Where every child discovers their brilliance.
          </h1>
          <p id="hero-copy" class="load-in mt-7 max-w-xl text-lg leading-8 text-white/85" style="--load-delay:260ms">
            A citadel of learning raising 21st century leaders — through the Nigerian National, British and Montessori curricula, taught by qualified tutors in a safe, well-equipped environment.
          </p>
          <div class="load-in mt-9 flex flex-wrap gap-4" style="--load-delay:400ms">
            <a href="admissions.php" class="inline-flex items-center gap-2 rounded-full bg-brand-primary px-7 py-4 text-sm font-bold text-white transition-all duration-300 ease-in-out hover:-translate-y-0.5 hover:scale-[1.03] hover:shadow-xl hover:shadow-brand-primary/30">
              Begin your journey <i data-lucide="arrow-right" class="size-4" aria-hidden="true"></i>
            </a>
            <a href="about.php" class="inline-flex items-center gap-2 rounded-full border border-white/40 px-7 py-4 text-sm font-bold text-white transition-colors duration-300 ease-in-out hover:bg-white/10">
              Discover Claret
            </a>
          </div>
        </div>

        <!-- Overlapping image collage -->
        <div class="relative hidden lg:block h-[520px] load-in" style="--load-delay:200ms">
          <div class="drift absolute top-0 right-0 w-[78%] h-[62%] rounded-3xl overflow-hidden shadow-floaty ring-4 ring-white/10" style="--drift-rot:0deg">
            <img src="assets/img/hero-carousel.jpg" alt="" class="h-full w-full object-cover object-top" aria-hidden="true">
          </div>
          <div class="drift absolute bottom-0 left-0 w-[58%] h-[46%] rounded-3xl overflow-hidden shadow-floaty ring-4 ring-brand-dark/40 rotate-[-3deg]" style="--drift-rot:-3deg; animation-delay:-3s;">
            <img src="assets/img/hero-carousel.jpg" alt="" class="h-full w-full object-cover object-bottom" aria-hidden="true">
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

<script>
  (function () {
    const slidesData = [
      { heading: 'Where every child discovers their brilliance.', copy: 'A citadel of learning raising 21st century leaders — through the Nigerian National, British and Montessori curricula, taught by qualified tutors in a safe, well-equipped environment.' },
      { heading: 'Learning with purpose. Living with ardour.', copy: 'From creche to graduation, our clubs, labs and dedicated teachers help every learner find their voice and their calling.' },
    ];
    const slides = document.querySelectorAll('.hero-slide');
    const heading = document.getElementById('hero-heading');
    const copy = document.getElementById('hero-copy');
    const count = document.getElementById('hero-count');
    let active = 0;

    function show(i) {
      slides.forEach((s, idx) => s.classList.toggle('opacity-100', idx === i) || s.classList.toggle('opacity-0', idx !== i));
      heading.textContent = slidesData[i].heading;
      copy.textContent = slidesData[i].copy;
      count.textContent = String(i + 1).padStart(2, '0');
      active = i;
    }
    document.getElementById('hero-next')?.addEventListener('click', () => show((active + 1) % slides.length));
    document.getElementById('hero-prev')?.addEventListener('click', () => show((active - 1 + slides.length) % slides.length));

    const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (!reduce) setInterval(() => show((active + 1) % slides.length), 7000);
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
    <p class="mt-5 text-base leading-7 text-slate-600">Education at Claret is more than a timetable — it's the confidence to ask, the discipline to grow, and the joy of becoming, backed by facilities and clubs built for curious minds.</p>
  </div>

  <div class="grid gap-5 md:grid-cols-4 md:auto-rows-[220px]">
    <div class="reveal lift-hover md:col-span-2 md:row-span-2 rounded-3xl bg-brand-dark text-white p-8 flex flex-col justify-between overflow-hidden relative group">
      <i data-lucide="monitor" class="size-8 text-brand-accent" aria-hidden="true"></i>
      <div>
        <h3 class="font-serif text-2xl font-bold">Tech & science labs</h3>
        <p class="mt-2 text-sm leading-6 text-white/70">Purpose-built labs where coding, robotics and the sciences come alive — preparing learners for the work of tomorrow.</p>
      </div>
      <div class="absolute -right-8 -bottom-8 size-40 rounded-full bg-brand-accent/20 group-hover:scale-110 transition-transform duration-300 ease-in-out"></div>
    </div>

    <div class="reveal lift-hover md:col-span-2 rounded-3xl bg-brand-primary/10 p-7 flex items-center gap-5">
      <i data-lucide="book-open" class="size-8 text-brand-primary shrink-0" aria-hidden="true"></i>
      <div>
        <h3 class="font-serif text-xl font-bold text-brand-dark">Three curricula, one standard</h3>
        <p class="mt-1.5 text-sm leading-6 text-slate-600">Nigerian National, British and Montessori — matched to how your child learns best.</p>
      </div>
    </div>

    <div class="reveal lift-hover rounded-3xl bg-white ring-1 ring-brand-dark/10 p-7 flex flex-col justify-between">
      <i data-lucide="palette" class="size-7 text-brand-accent" aria-hidden="true"></i>
      <div>
        <h3 class="font-serif text-lg font-bold text-brand-dark">12+ clubs</h3>
        <p class="mt-1 text-sm leading-6 text-slate-600">Academic, sports, arts & STEM.</p>
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
        <i data-lucide="smile" class="size-3.5" aria-hidden="true"></i> Creche, Nursery & Primary
      </span>
      <h3 class="font-serif text-3xl font-bold text-brand-dark mb-4">Bright beginnings</h3>
      <p class="text-sm leading-7 text-slate-600 mb-6 max-w-md">Warm classrooms, staggered play-and-learn spaces and teachers who make every first step feel safe — from Creche through Primary.</p>
      <ul class="space-y-3 text-sm text-slate-700 mb-8">
        <li class="flex items-center gap-2.5"><i data-lucide="check-circle-2" class="size-4 text-brand-primary shrink-0" aria-hidden="true"></i> Montessori-informed early years</li>
        <li class="flex items-center gap-2.5"><i data-lucide="check-circle-2" class="size-4 text-brand-primary shrink-0" aria-hidden="true"></i> Art & Craft, Dance and Fitness clubs</li>
        <li class="flex items-center gap-2.5"><i data-lucide="check-circle-2" class="size-4 text-brand-primary shrink-0" aria-hidden="true"></i> Small class sizes, close attention</li>
      </ul>
      <div class="grid grid-cols-2 gap-3">
        <img src="assets/img/hero-carousel.jpg" alt="Claret primary learners at their graduation ceremony" class="rounded-2xl h-32 w-full object-cover object-left-top">
        <img src="assets/img/hero-carousel.jpg" alt="Claret primary learners celebrating with medals" class="rounded-2xl h-32 w-full object-cover object-right-top mt-6">
      </div>
    </div>

    <!-- Secondary: sleek, dark, sharp -->
    <div class="reveal relative bg-brand-dark text-white p-10 pt-16 lg:pl-14 rounded-3xl lg:rounded-l-none -mt-6 lg:mt-0 clip-seam-reverse" style="--reveal-delay:150ms">
      <span class="inline-flex items-center gap-2 rounded-md bg-brand-accent/20 text-brand-accent text-xs font-bold uppercase tracking-widest px-4 py-1.5 mb-6">
        <i data-lucide="cpu" class="size-3.5" aria-hidden="true"></i> Secondary School
      </span>
      <h3 class="font-serif text-3xl font-bold mb-4">Future-ready leaders</h3>
      <p class="text-sm leading-7 text-white/70 mb-6 max-w-md">A sharper, academic edge — preparing learners for AI, the sciences and leadership through the British and Nigerian National curricula.</p>
      <ul class="space-y-3 text-sm text-white/85 mb-8">
        <li class="flex items-center gap-2.5"><i data-lucide="check-circle-2" class="size-4 text-brand-accent shrink-0" aria-hidden="true"></i> Coding Club & STEM labs</li>
        <li class="flex items-center gap-2.5"><i data-lucide="check-circle-2" class="size-4 text-brand-accent shrink-0" aria-hidden="true"></i> Debate, Science and Math Clubs</li>
        <li class="flex items-center gap-2.5"><i data-lucide="check-circle-2" class="size-4 text-brand-accent shrink-0" aria-hidden="true"></i> British & Nigerian National curricula</li>
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
    <p class="mt-6 text-sm font-bold uppercase tracking-widest text-slate-500">— <?= htmlspecialchars($school['director']['name']) ?>, <?= htmlspecialchars($school['director']['role']) ?></p>
  </div>
</section>

<!-- CTA BAND -->
<section class="bg-brand-primary/10 px-5 lg:px-8 py-16">
  <div class="reveal mx-auto max-w-7xl flex flex-col md:flex-row items-start md:items-center justify-between gap-8">
    <div>
      <p class="mb-2 text-sm font-bold uppercase tracking-[0.2em] text-brand-primary">Ready when you are</p>
      <h2 class="font-serif text-3xl md:text-4xl font-bold text-brand-dark">Come and see what is possible.</h2>
    </div>
    <a href="contact.php" class="inline-flex items-center gap-2 rounded-full bg-brand-dark px-7 py-4 text-sm font-bold text-white transition-all duration-300 ease-in-out hover:-translate-y-0.5 hover:scale-[1.03] hover:shadow-xl hover:shadow-brand-dark/30">
      Book a school visit <i data-lucide="calendar-days" class="size-4" aria-hidden="true"></i>
    </a>
  </div>
</section>

<?php require __DIR__ . '/partials/footer.php'; ?>
