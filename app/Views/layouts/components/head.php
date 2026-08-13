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
<!-- Tailwind CSS (compiled tokens adhering to 08-ui-design-system.md) -->
<script src="https://cdn.tailwindcss.com"></script>
<script>
    tailwind.config = {
        theme: {
            extend: {
                colors: {
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
                        700: '#15803D',
                    },
                    warning: {
                        100: '#FEF3C7',
                        800: '#92400E',
                    },
                    danger: {
                        100: '#FEE2E2',
                        700: '#B91C1C',
                    },
                    info: {
                        100: '#E0F2FE',
                        700: '#0369A1',
                    }
                },
                fontFamily: {
                    sans: ['Roboto', 'system-ui', '-apple-system', 'BlinkMacSystemFont', 'Segoe UI', 'sans-serif'],
                }
            }
        }
    }
</script>
<style>
    body { font-family: Roboto, system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; }
</style>
