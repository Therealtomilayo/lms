<?php
/**
 * Reusable Alert Banner Component
 * 
 * @var string $message Alert text or markup
 * @var string|null $type success|error|warning|info (default: info)
 * @var bool|null $dismissible Whether the alert can be closed (default: false)
 * @var string|null $id HTML id (default: empty)
 * @var string|null $class Custom CSS classes (default: empty)
 */

$type = $type ?? 'info';
$dismissible = $dismissible ?? false;
$idAttr = !empty($id) ? 'id="' . e($id) . '"' : '';

// Colors and roles mapping
$styles = [
    'success' => [
        'bg' => 'bg-success-100/70 border-green-200 text-success-700',
        'role' => 'status',
        'icon' => '<svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>'
    ],
    'error' => [
        'bg' => 'bg-danger-100/70 border-red-200 text-danger-700',
        'role' => 'alert',
        'icon' => '<svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>'
    ],
    'warning' => [
        'bg' => 'bg-warning-100/70 border-amber-200 text-warning-800',
        'role' => 'alert',
        'icon' => '<svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>'
    ],
    'info' => [
        'bg' => 'bg-info-100/70 border-blue-200 text-info-700',
        'role' => 'status',
        'icon' => '<svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>'
    ]
];

$config = $styles[$type] ?? $styles['info'];
$classes = 'p-4 rounded-xl border flex items-start gap-3 shadow-xs ' . $config['bg'] . ' ' . ($class ?? '');
?>

<div <?= $idAttr ?> class="<?= e($classes) ?>" role="<?= e($config['role']) ?>">
    <?= $config['icon'] ?>
    <div class="flex-1 text-sm font-medium leading-relaxed">
        <?= $message ?>
    </div>

    <?php if ($dismissible): ?>
        <button type="button" onclick="this.parentElement.remove()" class="p-1 -mr-1 rounded-lg hover:bg-black/5 focus:outline-none focus:ring-1 focus:ring-current transition cursor-pointer" aria-label="Dismiss alert">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    <?php endif; ?>
</div>
