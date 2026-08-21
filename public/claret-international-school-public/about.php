<?php
require_once __DIR__ . '/config.php';
$page_title = 'About us';
$meta_description = 'The story, curriculum and community behind Claret International School, Mabushi Abuja.';
require __DIR__ . '/partials/header.php';
?>

<!-- PAGE INTRO -->
<section class="relative bg-brand-dark text-white px-5 lg:px-8 py-16 lg:py-24 overflow-hidden">
  <img src="assets/img/Claret-International-School-10-1024x576.jpg" alt="" class="absolute inset-0 h-full w-full object-cover object-center opacity-20" aria-hidden="true">
  <div class="absolute inset-0 bg-gradient-to-r from-brand-dark via-brand-dark/90 to-brand-dark/50"></div>
  <div class="relative mx-auto max-w-7xl">
    <p class="mb-4 text-sm font-bold uppercase tracking-[0.22em] text-brand-accent">About Claret</p>
    <h1 class="max-w-4xl text-balance font-serif text-4xl md:text-6xl font-bold leading-tight">A citadel of learning, raising 21st century leaders.</h1>
    <p class="mt-6 max-w-2xl text-base md:text-lg leading-7 text-white/75"><?= htmlspecialchars($school['about_long']) ?></p>
  </div>
</section>

<!-- CLASSIFICATION BENTO -->
<section class="mx-auto max-w-7xl px-5 lg:px-8 py-16 lg:py-20">
  <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-5">
    <?php
    $icons = [
      'School Curriculum'       => 'book-marked',
      'School Classification'   => 'landmark',
      'Gender Composition'      => 'users',
      'Student Residence'       => 'home',
      'School Bus Availability' => 'bus',
    ];
    foreach ($school['classification'] as $label => $value): ?>
      <div class="reveal lift-hover rounded-3xl bg-white ring-1 ring-brand-dark/10 p-6 flex flex-col gap-4">
        <span class="flex size-11 items-center justify-center rounded-2xl bg-brand-primary/10 text-brand-primary">
          <i data-lucide="<?= $icons[$label] ?>" class="size-5" aria-hidden="true"></i>
        </span>
        <div>
          <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-1"><?= htmlspecialchars($label) ?></h3>
          <p class="font-serif text-lg font-bold text-brand-dark leading-snug"><?= htmlspecialchars($value) ?></p>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</section>

<!-- TIMELINE -->
<section class="bg-brand-primary/5 px-5 lg:px-8 py-16 lg:py-24">
  <div class="mx-auto max-w-4xl">
    <p class="mb-3 text-sm font-bold uppercase tracking-[0.22em] text-brand-primary">Our story</p>
    <h2 class="font-serif text-3xl md:text-4xl font-bold text-brand-dark mb-14">Growing, one graduating class at a time.</h2>

    <ol class="relative border-l-2 border-brand-primary/25 pl-8 space-y-12">
      <li class="reveal relative">
        <span class="absolute -left-[41px] top-0 flex size-6 items-center justify-center rounded-full bg-brand-primary text-white"><i data-lucide="flag" class="size-3.5" aria-hidden="true"></i></span>
        <h3 class="font-serif text-xl font-bold text-brand-dark">The early years</h3>
        <p class="mt-2 text-sm leading-6 text-slate-600">Claret International School opens its doors in Mabushi, Abuja, to provide private Nursery and Primary education built on discipline, integrity and ardour.</p>
      </li>
      <li class="reveal relative">
        <span class="absolute -left-[41px] top-0 flex size-6 items-center justify-center rounded-full bg-brand-primary text-white"><i data-lucide="book-open" class="size-3.5" aria-hidden="true"></i></span>
        <h3 class="font-serif text-xl font-bold text-brand-dark">Expanding the curriculum</h3>
        <p class="mt-2 text-sm leading-6 text-slate-600">Alongside the Nigerian National Curriculum, Claret introduces British and Montessori pathways, giving families a choice matched to how their child learns.</p>
      </li>
      <li class="reveal relative">
        <span class="absolute -left-[41px] top-0 flex size-6 items-center justify-center rounded-full bg-brand-primary text-white"><i data-lucide="graduation-cap" class="size-3.5" aria-hidden="true"></i></span>
        <h3 class="font-serif text-xl font-bold text-brand-dark">Secondary School launches</h3>
        <p class="mt-2 text-sm leading-6 text-slate-600">Claret extends its promise into secondary education, sharpening its academic focus on the sciences, technology and leadership.</p>
      </li>
      <li class="reveal relative">
        <span class="absolute -left-[41px] top-0 flex size-6 items-center justify-center rounded-full bg-brand-accent text-white"><i data-lucide="star" class="size-3.5" aria-hidden="true"></i></span>
        <h3 class="font-serif text-xl font-bold text-brand-dark">Claret today</h3>
        <p class="mt-2 text-sm leading-6 text-slate-600">Over 10 years on, Claret is a co-educational day school known for its clubs, its tech-ready classrooms, and learners who are known, valued and inspired to make a difference.</p>
      </li>
    </ol>
  </div>
</section>

<!-- MEET THE PRINCIPAL -->
<section class="mx-auto max-w-7xl px-5 lg:px-8 py-16 lg:py-24">
  <div class="grid lg:grid-cols-[0.85fr_1.15fr] gap-14 items-center">
    <div class="reveal-scale relative">
      <div class="rounded-3xl overflow-hidden shadow-floaty aspect-[4/5]">
        <img src="assets/img/director.jpg" alt="Theresa Titilayo, School Director, pictured at a Claret graduation ceremony" class="h-full w-full object-cover object-top">
      </div>
      <div class="drift absolute -bottom-8 -right-6 lg:-right-10 max-w-xs rounded-2xl bg-white shadow-floaty ring-1 ring-brand-dark/10 p-6" style="--drift-rot:0deg; animation-duration:7s;">
        <i data-lucide="quote" class="size-6 text-brand-accent mb-3" aria-hidden="true"></i>
        <p class="font-serif text-base font-semibold text-brand-dark leading-snug">"<?= htmlspecialchars($school['director']['quote']) ?>"</p>
      </div>
    </div>
    <div class="reveal">
      <p class="mb-3 text-sm font-bold uppercase tracking-[0.22em] text-brand-primary">Meet the director</p>
      <h2 class="font-serif text-3xl md:text-4xl font-bold text-brand-dark mb-2"><?= htmlspecialchars($school['director']['name']) ?></h2>
      <p class="text-sm font-semibold text-brand-accent uppercase tracking-wider mb-6"><?= htmlspecialchars($school['director']['role']) ?></p>
      <p class="text-sm leading-7 text-slate-600 max-w-lg"><?= htmlspecialchars($school['about_long']) ?></p>
    </div>
  </div>
</section>

<!-- CLUBS -->
<section class="bg-brand-dark text-white px-5 lg:px-8 py-16 lg:py-24">
  <div class="mx-auto max-w-7xl">
    <p class="mb-3 text-sm font-bold uppercase tracking-[0.22em] text-brand-accent">Beyond the classroom</p>
    <h2 class="font-serif text-3xl md:text-4xl font-bold mb-12 max-w-2xl">Clubs &amp; extra-curricular activities.</h2>

    <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
      <?php
      $club_icons = [
        'Academic Clubs' => 'brain',
        'Technology & STEM Clubs' => 'cpu',
        'Arts & Crafts Clubs' => 'palette',
        'Sports & Fitness Clubs' => 'dumbbell',
        'Enrichment' => 'music',
      ];
      foreach ($school['clubs'] as $category => $items): ?>
        <div class="reveal lift-hover rounded-2xl bg-white/5 border border-white/10 p-6">
          <span class="flex size-10 items-center justify-center rounded-xl bg-brand-accent/20 text-brand-accent mb-4">
            <i data-lucide="<?= $club_icons[$category] ?? 'star' ?>" class="size-5" aria-hidden="true"></i>
          </span>
          <h3 class="font-serif text-lg font-bold mb-3"><?= htmlspecialchars($category) ?></h3>
          <ul class="space-y-2 text-sm text-white/70">
            <?php foreach ($items as $club): ?>
              <li class="flex items-center gap-2"><i data-lucide="check" class="size-3.5 text-brand-accent shrink-0" aria-hidden="true"></i> <?= htmlspecialchars($club) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- GALLERY -->
<section class="mx-auto max-w-7xl px-5 lg:px-8 py-16 lg:py-24">
  <p class="mb-3 text-sm font-bold uppercase tracking-[0.22em] text-brand-primary">Gallery</p>
  <h2 class="font-serif text-3xl md:text-4xl font-bold text-brand-dark mb-12 max-w-2xl">Moments from campus life.</h2>

  <div class="grid grid-cols-2 md:grid-cols-4 gap-4 md:auto-rows-[180px]">
    <div class="reveal-scale lift-hover md:col-span-2 md:row-span-2 rounded-3xl overflow-hidden"><img src="assets/img/hero-carousel.jpg" alt="Claret graduates celebrating on stage" class="h-full w-full object-cover"></div>
    <div class="reveal-scale lift-hover rounded-3xl overflow-hidden"><img src="assets/img/Claret-International-School-10-1024x576.jpg" alt="Claret campus life" class="h-full w-full object-cover object-top"></div>
    <div class="reveal-scale lift-hover rounded-3xl overflow-hidden"><img src="assets/img/Claret-International-School-12-1024x576.jpg" alt="Claret learners in their graduation gowns" class="h-full w-full object-cover object-left"></div>
    <div class="reveal-scale lift-hover rounded-3xl overflow-hidden"><img src="assets/img/Claret-International-School-9-1024x576.jpg" alt="Claret learners holding certificates" class="h-full w-full object-cover object-right"></div>
    <div class="reveal-scale lift-hover rounded-3xl overflow-hidden"><img src="assets/img/hero-carousel-2.jpg" alt="Claret school activities" class="h-full w-full object-cover object-bottom"></div>
  </div>
</section>

<?php require __DIR__ . '/partials/footer.php'; ?>
