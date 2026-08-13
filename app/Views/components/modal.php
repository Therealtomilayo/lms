<?php
/**
 * Reusable Accessible Overlay Modal Component
 * 
 * @var string $id HTML id for identifying and controling the modal (required)
 * @var string $title Accessible header text
 * @var string $body HTML content of the modal body
 * @var string|null $footer HTML content for footer action buttons (optional)
 * @var string|null $size sm|md|lg|xl (default: md)
 */

$size = $size ?? 'md';

$sizes = [
    'sm' => 'sm:max-w-sm',
    'md' => 'sm:max-w-md',
    'lg' => 'sm:max-w-lg',
    'xl' => 'sm:max-w-xl'
];

$sizeClass = $sizes[$size] ?? $sizes['md'];
?>

<div id="<?= e($id) ?>" 
     class="lms-modal hidden fixed inset-0 z-50 items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs transition-opacity duration-300"
     role="dialog" 
     aria-modal="true" 
     aria-labelledby="<?= e($id) ?>-title">
    
    <!-- Backdrop Click Trap to close -->
    <div class="fixed inset-0 cursor-default" onclick="window.LMS.hideModal('<?= e($id) ?>')"></div>
    
    <!-- Modal Panel -->
    <div class="relative bg-white rounded-xl shadow-xl overflow-hidden w-full <?= e($sizeClass) ?> transform transition-all duration-300 border border-slate-200 z-10 flex flex-col max-h-[90vh]">
        
        <!-- Header -->
        <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between bg-slate-50/50 flex-shrink-0">
            <h3 id="<?= e($id) ?>-title" class="text-base font-bold text-slate-900 leading-tight">
                <?= e($title) ?>
            </h3>
            <button type="button" 
                    onclick="window.LMS.hideModal('<?= e($id) ?>')" 
                    class="p-1 rounded-lg text-slate-400 hover:bg-slate-100 hover:text-slate-600 transition cursor-pointer"
                    aria-label="Close modal">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        
        <!-- Body -->
        <div class="p-6 overflow-y-auto flex-1 text-sm text-slate-700 leading-relaxed">
            <?= $body ?>
        </div>
        
        <!-- Footer -->
        <?php if (!empty($footer)): ?>
            <div class="px-6 py-4 border-t border-slate-200 bg-slate-50 flex items-center justify-end gap-3 flex-shrink-0">
                <?= $footer ?>
            </div>
        <?php endif; ?>
    </div>
</div>
