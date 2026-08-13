<?php
/**
 * Reusable Badge/Tag Component
 * 
 * @var string $label Tag text
 * @var string|null $variant success|danger|warning|info|neutral (default: neutral)
 * @var string|null $class Custom CSS classes (default: empty)
 */

$variant = $variant ?? 'neutral';

$styles = [
    'success' => 'bg-success-100 text-success-700 border-green-200',
    'danger' => 'bg-danger-100 text-danger-700 border-red-200',
    'warning' => 'bg-warning-100 text-warning-800 border-amber-200',
    'info' => 'bg-info-100 text-info-700 border-blue-200',
    'neutral' => 'bg-slate-100 text-slate-700 border-slate-200'
];

$classes = 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold border ' . ($styles[$variant] ?? $styles['neutral']) . ' ' . ($class ?? '');
?>

<span class="<?= e($classes) ?>">
    <?= e($label) ?>
</span>
