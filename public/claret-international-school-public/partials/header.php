<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= isset($page_title) ? htmlspecialchars($page_title) . ' — ' . $school['name'] : $school['name'] ?></title>
<meta name="description" content="<?= htmlspecialchars($meta_description ?? $school['about_long']) ?>">
<link rel="icon" type="image/png" href="assets/img/logo.png">

<!-- Fonts: Fraunces (display serif, prestige) + Inter (body) -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,500;9..144,600;9..144,700;9..144,800&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<!-- Tailwind (CDN build for static/PHP delivery) -->
<script src="https://cdn.tailwindcss.com"></script>
<script>
  tailwind.config = {
    theme: {
      extend: {
        colors: {
          brand: {
            primary: '#0C9DD5',
            dark: '#7B3046',
            accent: '#C3456B',
            bg: '#F8FAFC',
            surface: '#FFFFFF',
          },
        },
        fontFamily: {
          serif: ['"Fraunces"', 'ui-serif', 'Georgia', 'serif'],
          sans: ['"Inter"', 'ui-sans-serif', 'system-ui', 'sans-serif'],
        },
        boxShadow: {
          floaty: '0 30px 60px -25px rgba(123,48,70,0.35)',
        },
      },
    },
  };
</script>

<!-- Lucide icons -->
<script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js" defer></script>

<style>
  html { scroll-behavior: smooth; }
  body { background-color: #F8FAFC; }
  .glass { background-color: rgba(248,250,252,0.72); backdrop-filter: blur(14px); -webkit-backdrop-filter: blur(14px); }
  .clip-seam { clip-path: polygon(0 0, 100% 0, 100% 92%, 0% 100%); }
  .clip-seam-reverse { clip-path: polygon(0 8%, 100% 0, 100% 100%, 0 100%); }

  /* Scroll-triggered reveal: elements fade + rise into place once, on entry */
  .reveal { opacity: 0; transform: translateY(28px); transition: opacity .7s cubic-bezier(.22,.61,.36,1), transform .7s cubic-bezier(.22,.61,.36,1); transition-delay: var(--reveal-delay, 0s); }
  .reveal.is-visible { opacity: 1; transform: translateY(0); }
  .reveal-scale { opacity: 0; transform: scale(.94); transition: opacity .6s ease, transform .6s ease; transition-delay: var(--reveal-delay, 0s); }
  .reveal-scale.is-visible { opacity: 1; transform: scale(1); }

  /* Hero load-in: plays once on page load, not scroll-linked */
  @keyframes fade-up { from { opacity: 0; transform: translateY(22px); } to { opacity: 1; transform: translateY(0); } }
  .load-in { opacity: 0; animation: fade-up .8s cubic-bezier(.22,.61,.36,1) forwards; animation-delay: var(--load-delay, 0s); }

  /* Gentle ambient drift on hero collage frames */
  @keyframes drift { 0%, 100% { transform: translateY(0) rotate(var(--drift-rot, 0deg)); } 50% { transform: translateY(-10px) rotate(var(--drift-rot, 0deg)); } }
  .drift { animation: drift 6s ease-in-out infinite; }

  /* Card hover lift used across bento/stat/club tiles */
  .lift-hover { transition: transform .35s cubic-bezier(.22,.61,.36,1), box-shadow .35s ease; }
  .lift-hover:hover { transform: translateY(-6px); }

  @media (prefers-reduced-motion: reduce) {
    html { scroll-behavior: auto; }
    .reveal, .reveal-scale, .load-in { opacity: 1 !important; transform: none !important; animation: none !important; }
    .drift { animation: none !important; }
    * { animation-duration: 0.001ms !important; animation-iteration-count: 1 !important; transition-duration: 0.001ms !important; scroll-behavior: auto !important; }
  }
</style>
</head>
<body class="font-sans text-slate-800 antialiased">

<a href="#main" class="sr-only focus:not-sr-only focus:absolute focus:z-[100] focus:top-3 focus:left-3 focus:bg-brand-primary focus:text-white focus:px-4 focus:py-2 focus:rounded-full">Skip to content</a>

<header class="sticky top-0 z-50 glass border-b border-brand-dark/10">
  <div class="mx-auto max-w-7xl px-5 lg:px-8">
    <div class="flex items-center justify-between h-20">

      <a href="index.php" class="flex items-center gap-3 shrink-0">
        <img src="assets/img/logo.png" alt="<?= htmlspecialchars($school['name']) ?> crest" class="h-14 w-14 object-contain">
        <span class="hidden sm:flex flex-col leading-tight">
          <span class="font-serif font-bold text-brand-dark text-base tracking-tight">Claret International</span>
          <span class="text-[11px] font-semibold uppercase tracking-[0.2em] text-brand-primary">School</span>
        </span>
      </a>

      <nav class="hidden lg:flex items-center gap-9" aria-label="Main navigation">
        <?php foreach ($nav as $href => $label): ?>
          <a href="<?= $href ?>"
             class="text-sm font-semibold transition-colors hover:text-brand-primary <?= $current_page === $href ? 'text-brand-primary' : 'text-slate-600' ?>">
            <?= $label ?>
          </a>
        <?php endforeach; ?>

        <a href="portal.php" class="flex items-center gap-2 text-sm font-semibold text-slate-600 hover:text-brand-primary transition-colors">
          <i data-lucide="lock" class="size-4" aria-hidden="true"></i> Parent portal
        </a>

        <a href="admissions.php"
           class="inline-flex items-center gap-2 rounded-full bg-brand-primary px-6 py-3 text-sm font-bold text-white shadow-sm transition-all duration-300 ease-in-out hover:-translate-y-0.5 hover:shadow-lg hover:shadow-brand-primary/30">
          Apply Now <i data-lucide="arrow-up-right" class="size-4" aria-hidden="true"></i>
        </a>
      </nav>

      <button type="button" id="menu-toggle" aria-label="Open menu" aria-expanded="false" aria-controls="mobile-menu"
              class="lg:hidden inline-flex items-center justify-center rounded-full p-2.5 text-brand-dark hover:bg-brand-dark/5 transition-colors">
        <i data-lucide="menu" class="size-6" id="menu-icon-open" aria-hidden="true"></i>
        <i data-lucide="x" class="size-6 hidden" id="menu-icon-close" aria-hidden="true"></i>
      </button>
    </div>
  </div>

  <nav id="mobile-menu" class="hidden lg:hidden border-t border-brand-dark/10 bg-brand-surface px-5 py-6" aria-label="Mobile navigation">
    <div class="flex flex-col gap-1">
      <?php foreach ($nav as $href => $label): ?>
        <a href="<?= $href ?>" class="rounded-xl px-4 py-3 text-sm font-semibold <?= $current_page === $href ? 'bg-brand-primary/10 text-brand-primary' : 'text-slate-700 hover:bg-slate-50' ?>"><?= $label ?></a>
      <?php endforeach; ?>
      <a href="portal.php" class="rounded-xl px-4 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50 flex items-center gap-2">
        <i data-lucide="lock" class="size-4" aria-hidden="true"></i> Parent portal
      </a>
      <a href="admissions.php" class="mt-2 rounded-full bg-brand-primary px-4 py-3.5 text-center text-sm font-bold text-white">Apply Now</a>
    </div>
  </nav>
</header>

<script>
  const menuToggle = document.getElementById('menu-toggle');
  const mobileMenu = document.getElementById('mobile-menu');
  const iconOpen = document.getElementById('menu-icon-open');
  const iconClose = document.getElementById('menu-icon-close');
  menuToggle?.addEventListener('click', () => {
    const isHidden = mobileMenu.classList.contains('hidden');
    mobileMenu.classList.toggle('hidden');
    iconOpen.classList.toggle('hidden');
    iconClose.classList.toggle('hidden');
    menuToggle.setAttribute('aria-expanded', String(isHidden));
    menuToggle.setAttribute('aria-label', isHidden ? 'Close menu' : 'Open menu');
  });
</script>

<main id="main">
