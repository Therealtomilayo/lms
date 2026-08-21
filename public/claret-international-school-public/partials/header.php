<!DOCTYPE html>
<html lang="en-NG">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<?php
  // ── Per-page SEO variables (pages can override before including header) ──────
  $seo_title       = isset($page_title) ? htmlspecialchars($page_title) . ' | ' . $school['name'] : $school['name'];
  $seo_description = htmlspecialchars($meta_description ?? $school['about_long']);
  $seo_canonical   = $site_base_url . ($current_page === '/' ? '/' : $current_page);
  $seo_og_image    = $og_image ?? $og_image_default;
  $seo_og_type     = $og_type ?? 'website';
?>

<!-- Primary meta -->
<title><?= $seo_title ?></title>
<meta name="description" content="<?= $seo_description ?>">
<link rel="canonical" href="<?= htmlspecialchars($seo_canonical) ?>">
<meta name="robots" content="index, follow">
<meta name="theme-color" content="#7B3046">

<!-- Open Graph -->
<meta property="og:type"        content="<?= $seo_og_type ?>">
<meta property="og:site_name"   content="<?= htmlspecialchars($school['name']) ?>">
<meta property="og:title"       content="<?= $seo_title ?>">
<meta property="og:description" content="<?= $seo_description ?>">
<meta property="og:url"         content="<?= htmlspecialchars($seo_canonical) ?>">
<meta property="og:image"       content="<?= htmlspecialchars($seo_og_image) ?>">
<meta property="og:image:width"  content="1024">
<meta property="og:image:height" content="576">
<meta property="og:locale"      content="en_NG">

<!-- Twitter / X Card -->
<meta name="twitter:card"        content="summary_large_image">
<meta name="twitter:title"       content="<?= $seo_title ?>">
<meta name="twitter:description" content="<?= $seo_description ?>">
<meta name="twitter:image"       content="<?= htmlspecialchars($seo_og_image) ?>">

<!-- Schema.org: School (LocalBusiness) -->
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "School",
  "name": "<?= addslashes($school['name']) ?>",
  "url": "<?= $site_base_url ?>",
  "logo": "<?= $site_base_url ?>/assets/img/logo.png",
  "image": "<?= addslashes($seo_og_image) ?>",
  "description": "<?= addslashes($school['about_long']) ?>",
  "telephone": "<?= addslashes($school['phone_href']) ?>",
  "foundingDate": "2015",
  "address": {
    "@type": "PostalAddress",
    "streetAddress": "<?= addslashes($school['address_line1']) ?>",
    "addressLocality": "Abuja",
    "addressRegion": "FCT",
    "addressCountry": "NG"
  },
  "geo": {
    "@type": "GeoCoordinates",
    "latitude": "<?= $school['lat'] ?>",
    "longitude": "<?= $school['lng'] ?>"
  },
  "sameAs": [
    "https://www.facebook.com/ClaretInternationalSchool/",
    "https://www.instagram.com/claretintlschoolabuja/",
    "https://www.youtube.com/channel/UCJN9AQtW-iwW5SnWsyCGz-A"
  ]
}
</script>

<!-- Favicons -->
<link rel="icon" type="image/png" href="/assets/img/logo.png">
<link rel="apple-touch-icon" href="/assets/img/logo.png">

<!-- Fonts: Fraunces (display serif, prestige) + Inter (body) -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,500;9..144,600;9..144,700;9..144,800&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<?php
  $css_ver = file_exists(__DIR__ . '/../assets/css/output.css') ? filemtime(__DIR__ . '/../assets/css/output.css') : time();
  $js_ver  = file_exists(__DIR__ . '/../assets/js/icons.min.js') ? filemtime(__DIR__ . '/../assets/js/icons.min.js') : time();
?>
<!-- Compiled Tailwind CSS + Custom Design System -->
<link rel="stylesheet" href="/assets/css/output.css?v=<?= $css_ver ?>">

<!-- Compiled Lucide icons -->
<script src="/assets/js/icons.min.js?v=<?= $js_ver ?>" defer></script>
</head>
<body class="font-sans text-slate-800 antialiased">

<a href="#main" class="sr-only focus:not-sr-only focus:absolute focus:z-[100] focus:top-3 focus:left-3 focus:bg-brand-primary focus:text-white focus:px-4 focus:py-2 focus:rounded-full">Skip to content</a>

<header class="sticky top-0 z-50 glass border-b border-brand-dark/10">
  <div class="mx-auto max-w-7xl px-5 lg:px-8">
    <div class="flex items-center justify-between h-20">

      <a href="/" class="flex items-center gap-3 shrink-0">
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

        <a href="/portal" class="flex items-center gap-2 text-sm font-semibold text-slate-600 hover:text-brand-primary transition-colors">
          <i data-lucide="lock" class="size-4" aria-hidden="true"></i> Parent portal
        </a>

        <a href="/admissions"
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
      <a href="/portal" class="rounded-xl px-4 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50 flex items-center gap-2">
        <i data-lucide="lock" class="size-4" aria-hidden="true"></i> Parent portal
      </a>
      <a href="/admissions" class="mt-2 rounded-full bg-brand-primary px-4 py-3.5 text-center text-sm font-bold text-white">Apply Now</a>
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
