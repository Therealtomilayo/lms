<?php
/**
 * Shared Head Layout Component
 * 
 * @var string|null $title Page title
 */
?>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($title ?? 'Claret LMS') ?></title>
<!-- Google Fonts: Fraunces (Prestige Serif) + Plus Jakarta Sans (Modern UI Sans) -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,600;9..144,700;9..144,800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">

<!-- Lucide Icons (Bundled Local UMD with full icon catalog) -->
<script src="/assets/js/lucide.min.js"></script>
<script>
    // Automatically render Lucide icons on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            if (window.lucide) {
                window.lucide.createIcons();
            }
        });
    } else {
        if (window.lucide) {
            window.lucide.createIcons();
        }
    }
</script>

<!-- Tailwind CSS (compiled tokens adhering to 08-ui-design-system.md) -->
<script src="https://cdn.tailwindcss.com"></script>
<script>
    tailwind.config = {
        theme: {
            extend: {
                colors: {
                    claret: {
                        50:  '#FDF2F4',
                        100: '#FBE8EC',
                        200: '#F5C5CF',
                        500: '#C3456B',
                        600: '#9B3B58',
                        700: '#7B3046',
                        800: '#5F2234',
                        900: '#421422',
                    },
                    brand: {
                        100: '#DBEAFE',
                        500: '#3B82F6',
                        600: '#0C9DD5',
                        700: '#7B3046',
                    },
                    accent: {
                        500: '#C3456B',
                        600: '#C3456B',
                    },
                    success: {
                        100: '#DCFCE7',
                        500: '#22C55E',
                        600: '#16A34A',
                        700: '#15803D',
                    },
                    warning: {
                        100: '#FEF3C7',
                        500: '#F59E0B',
                        600: '#D97706',
                        800: '#92400E',
                    },
                    danger: {
                        50:  '#FEF2F2',
                        100: '#FEE2E2',
                        500: '#EF4444',
                        600: '#DC2626',
                        700: '#B91C1C',
                        800: '#991B1B',
                    },
                    info: {
                        100: '#E0F2FE',
                        500: '#0EA5E9',
                        600: '#0284C7',
                        700: '#0369A1',
                    }
                },
                fontFamily: {
                    sans: ['"Plus Jakarta Sans"', 'Roboto', 'system-ui', '-apple-system', 'sans-serif'],
                    serif: ['Fraunces', 'Georgia', 'serif'],
                }
            }
        }
    }
</script>
<style>
    body { font-family: "Plus Jakarta Sans", Roboto, system-ui, -apple-system, sans-serif; }

    /* Global Print Isolation Stylesheet */
    @media print {
        #sidebar-navigation,
        #sidebar-backdrop,
        aside,
        header,
        nav,
        .no-print,
        .screen-only {
            display: none !important;
            visibility: hidden !important;
            position: absolute !important;
            left: -99999px !important;
            top: -99999px !important;
            width: 0 !important;
            height: 0 !important;
            overflow: hidden !important;
            opacity: 0 !important;
            pointer-events: none !important;
        }

        html, body {
            background: #ffffff !important;
            color: #0f172a !important;
            overflow: visible !important;
            height: auto !important;
            min-height: 0 !important;
            width: 100% !important;
            display: block !important;
            margin: 0 !important;
            padding: 0 !important;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        body > div.flex-1,
        div.overflow-hidden,
        #main-content,
        main {
            display: block !important;
            overflow: visible !important;
            position: static !important;
            height: auto !important;
            min-height: 0 !important;
            max-height: none !important;
            width: 100% !important;
            margin: 0 !important;
            padding: 0 !important;
            background: #ffffff !important;
        }
    }
</style>
