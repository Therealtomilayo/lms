<?php
/**
 * Reusable Card Panel Component
 * 
 * @var string|null $title Card title (optional)
 * @var string|null $subtitle Card subtitle (optional)
 * @var string|null $headerActions HTML content for right-aligned header actions (optional)
 * @var string $body HTML content for the card body
 * @var string|null $class Custom CSS classes for the outer wrapper (optional)
 */
?>

<div class="bg-white border border-slate-200 rounded-xl shadow-xs overflow-hidden <?= e($class ?? '') ?>">
    <?php if (!empty($title) || !empty($headerActions) || !empty($subtitle)): ?>
        <div class="px-5 py-4 border-b border-slate-200 flex items-center justify-between gap-4 bg-slate-50/50">
            <div>
                <?php if (!empty($title)): ?>
                    <h3 class="text-base font-bold text-slate-900 leading-tight"><?= e($title) ?></h3>
                <?php endif; ?>
                <?php if (!empty($subtitle)): ?>
                    <p class="text-xs text-slate-500 mt-1"><?= e($subtitle) ?></p>
                <?php endif; ?>
            </div>
            <?php if (!empty($headerActions)): ?>
                <div class="flex items-center gap-2 flex-shrink-0">
                    <?= $headerActions ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    <div class="p-5">
        <?= $body ?>
    </div>
</div>
