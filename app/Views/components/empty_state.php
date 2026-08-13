<?php
/**
 * Reusable Empty State Component
 * 
 * @var string $title Header message for empty state
 * @var string $message Detailed description explaining why content is empty
 * @var string|null $icon SVG icon markup (optional; a default dashboard/empty state folder icon is provided)
 * @var string|null $actionUrl Action button link (optional)
 * @var string|null $actionLabel Action button text (optional)
 * @var string|null $class Custom CSS classes (optional)
 */

$icon = $icon ?? '<svg class="mx-auto h-12 w-12 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 13.5h3.86a2.25 2.25 0 012.008 1.24l.885 1.77a2.25 2.25 0 002.007 1.24h1.98a2.25 2.25 0 002.007-1.24l.885-1.77a2.25 2.25 0 012.007-1.24h3.86m-18 0h18M2.25 13.5l1.625-7.311A2.248 2.248 0 016.077 4.5h11.846a2.248 2.248 0 012.202 1.689l1.625 7.312M12 9v6m-3-3h6" /></svg>';
?>

<div class="text-center py-12 px-6 border-2 border-dashed border-slate-300 rounded-xl bg-slate-50/50 max-w-lg mx-auto <?= e($class ?? '') ?>">
    <div class="mb-4">
        <?= $icon ?>
    </div>
    <h3 class="text-base font-bold text-slate-900 leading-tight"><?= e($title) ?></h3>
    <p class="mt-2 text-sm text-slate-500 leading-relaxed max-w-sm mx-auto"><?= e($message) ?></p>
    
    <?php if (!empty($actionUrl) && !empty($actionLabel)): ?>
        <div class="mt-6">
            <a href="<?= e($actionUrl) ?>" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg text-sm font-semibold bg-brand-600 hover:bg-brand-700 text-white focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-500 shadow-sm transition min-h-[44px]">
                <span><?= e($actionLabel) ?></span>
            </a>
        </div>
    <?php endif; ?>
</div>
