<?php
/**
 * Reusable Button Component
 * 
 * @var string $label Button text
 * @var string|null $variant primary|secondary|quiet|danger (default: primary)
 * @var string|null $type button|submit|reset (default: button)
 * @var string|null $href URL if rendering as link (optional)
 * @var string|null $icon SVG icon markup (optional)
 * @var bool|null $disabled Whether the button is disabled (default: false)
 * @var string|null $class Custom CSS classes (optional)
 * @var string|null $id HTML ID (optional)
 * @var string|null $attributes Custom raw HTML attributes (optional)
 */

$variant = $variant ?? 'primary';
$type = $type ?? 'button';
$disabled = $disabled ?? false;
$idAttr = !empty($id) ? 'id="' . e($id) . '"' : '';
$disabledAttr = $disabled ? 'disabled aria-disabled="true"' : '';
$rawAttributes = $attributes ?? '';
$href = $href ?? null;

// Styling mapping based on design system
$baseStyles = 'inline-flex items-center justify-center gap-2 px-4 py-2 rounded-lg text-sm font-semibold transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 cursor-pointer select-none';

$variantStyles = [
    'primary' => 'bg-brand-600 hover:bg-brand-700 active:bg-brand-800 text-white focus:ring-brand-500 shadow-sm border border-transparent disabled:bg-slate-200 disabled:text-slate-400 disabled:cursor-not-allowed',
    'secondary' => 'bg-white hover:bg-slate-50 active:bg-slate-100 text-slate-700 border border-slate-300 focus:ring-brand-500 shadow-xs disabled:bg-slate-50 disabled:text-slate-400 disabled:border-slate-200 disabled:cursor-not-allowed',
    'quiet' => 'text-brand-600 hover:bg-brand-50 active:bg-brand-100 focus:ring-brand-500 rounded-md px-3 py-1.5 min-h-0 disabled:text-slate-400 disabled:hover:bg-transparent disabled:cursor-not-allowed',
    'danger' => 'bg-danger-600 hover:bg-red-700 active:bg-red-800 text-white focus:ring-danger-500 shadow-sm border border-transparent disabled:bg-slate-200 disabled:text-slate-400 disabled:cursor-not-allowed'
];

$classes = $baseStyles . ' ' . $variantStyles[$variant] . ' ' . ($class ?? '');
?>

<?php if ($href !== null): ?>
    <a href="<?= e($href) ?>" <?= $idAttr ?> class="<?= e($classes) ?>" <?= $rawAttributes ?>>
        <?php if (!empty($icon)): ?>
            <span class="w-4 h-4 flex-shrink-0 flex items-center justify-center" aria-hidden="true">
                <?= $icon ?>
            </span>
        <?php endif; ?>
        <span><?= e($label) ?></span>
    </a>
<?php else: ?>
    <button type="<?= e($type) ?>" <?= $idAttr ?> class="<?= e($classes) ?>" <?= $disabledAttr ?> <?= $rawAttributes ?>>
        <?php if (!empty($icon)): ?>
            <span class="w-4 h-4 flex-shrink-0 flex items-center justify-center" aria-hidden="true">
                <?= $icon ?>
            </span>
        <?php endif; ?>
        <span><?= e($label) ?></span>
    </button>
<?php endif; ?>

