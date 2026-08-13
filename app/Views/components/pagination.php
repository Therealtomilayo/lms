<?php
/**
 * Reusable Pagination Component
 * 
 * @var int $currentPage Current active page (default: 1)
 * @var int $totalPages Total number of pages (default: 1)
 * @var string $baseUrl Base URL for pagination links (default: empty)
 * @var int|null $totalResults Total records count (optional)
 * @var int|null $perPage Records per page (optional)
 * @var string|null $class Custom CSS classes (optional)
 */

$currentPage = (int)($currentPage ?? 1);
$totalPages = (int)($totalPages ?? 1);
$baseUrl = $baseUrl ?? '';
$totalResults = isset($totalResults) ? (int)$totalResults : null;
$perPage = isset($perPage) ? (int)$perPage : null;

$startCount = $totalResults !== null && $perPage !== null ? ($currentPage - 1) * $perPage + 1 : null;
$endCount = $totalResults !== null && $perPage !== null ? min($currentPage * $perPage, $totalResults) : null;

// Helper to attach page parameter
$getUrl = function(int $page) use ($baseUrl) {
    if (str_contains($baseUrl, '?')) {
        // Strip out existing page parameter if present to avoid duplication
        $cleanUrl = preg_replace('/([?&])page=\d+(&?)/', '$1', $baseUrl);
        $cleanUrl = rtrim($cleanUrl, '?&');
        return $cleanUrl . (str_contains($cleanUrl, '?') ? '&' : '?') . 'page=' . $page;
    }
    return $baseUrl . '?page=' . $page;
};
?>

<?php if ($totalPages > 1): ?>
    <nav class="flex items-center justify-between border-t border-slate-200 px-4 py-3 sm:px-6 <?= e($class ?? '') ?>" aria-label="Pagination Navigation">
        <!-- Mobile Simple View -->
        <div class="flex flex-1 justify-between sm:hidden">
            <a href="<?= $currentPage > 1 ? e($getUrl($currentPage - 1)) : '#' ?>" 
               class="relative inline-flex items-center rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 min-h-[44px] <?= $currentPage <= 1 ? 'pointer-events-none opacity-50' : '' ?>"
               <?= $currentPage <= 1 ? 'aria-disabled="true"' : '' ?>>
                Previous
            </a>
            <a href="<?= $currentPage < $totalPages ? e($getUrl($currentPage + 1)) : '#' ?>" 
               class="relative ml-3 inline-flex items-center rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 min-h-[44px] <?= $currentPage >= $totalPages ? 'pointer-events-none opacity-50' : '' ?>"
               <?= $currentPage >= $totalPages ? 'aria-disabled="true"' : '' ?>>
                Next
            </a>
        </div>

        <!-- Desktop Full View -->
        <div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
            <div>
                <?php if ($totalResults !== null): ?>
                    <p class="text-sm text-slate-700 leading-normal">
                        Showing <span class="font-semibold text-slate-900"><?= (int)$startCount ?></span> to 
                        <span class="font-semibold text-slate-900"><?= (int)$endCount ?></span> of 
                        <span class="font-semibold text-slate-900"><?= (int)$totalResults ?></span> results
                    </p>
                <?php endif; ?>
            </div>
            <div>
                <ul class="inline-flex -space-x-px rounded-md shadow-xs bg-white border border-slate-300 divide-x divide-slate-300" aria-label="Pagination links list">
                    <!-- Previous Button -->
                    <li>
                        <a href="<?= $currentPage > 1 ? e($getUrl($currentPage - 1)) : '#' ?>" 
                           class="inline-flex items-center px-3 py-2 text-sm font-semibold text-slate-500 hover:text-slate-700 hover:bg-slate-50 rounded-l-md min-w-[38px] min-h-[38px] justify-center <?= $currentPage <= 1 ? 'pointer-events-none opacity-50' : '' ?>"
                           <?= $currentPage <= 1 ? 'aria-disabled="true"' : '' ?>
                           aria-label="Go to previous page">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                        </a>
                    </li>

                    <!-- Page Numbers -->
                    <?php
                    $startPage = max(1, $currentPage - 2);
                    $endPage = min($totalPages, $currentPage + 2);
                    
                    if ($startPage > 1): ?>
                        <li>
                            <a href="<?= e($getUrl(1)) ?>" class="inline-flex items-center px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 justify-center min-w-[38px] min-h-[38px]">1</a>
                        </li>
                        <?php if ($startPage > 2): ?>
                            <li>
                                <span class="inline-flex items-center px-3 py-2 text-sm font-semibold text-slate-400 justify-center min-w-[38px] min-h-[38px]" aria-hidden="true">...</span>
                            </li>
                        <?php endif; ?>
                    <?php endif; ?>

                    <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                        <li>
                            <a href="<?= e($getUrl($i)) ?>" 
                               class="inline-flex items-center px-3 py-2 text-sm font-semibold justify-center min-w-[38px] min-h-[38px] <?= $i === $currentPage ? 'bg-brand-600 text-white hover:bg-brand-700' : 'text-slate-700 hover:bg-slate-50' ?>"
                               <?= $i === $currentPage ? 'aria-current="page"' : '' ?>>
                                <?= $i ?>
                            </a>
                        </li>
                    <?php endfor; ?>

                    <?php if ($endPage < $totalPages): ?>
                        <?php if ($endPage < $totalPages - 1): ?>
                            <li>
                                <span class="inline-flex items-center px-3 py-2 text-sm font-semibold text-slate-400 justify-center min-w-[38px] min-h-[38px]" aria-hidden="true">...</span>
                            </li>
                        <?php endif; ?>
                        <li>
                            <a href="<?= e($getUrl($totalPages)) ?>" class="inline-flex items-center px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 justify-center min-w-[38px] min-h-[38px]"><?= $totalPages ?></a>
                        </li>
                    <?php endif; ?>

                    <!-- Next Button -->
                    <li>
                        <a href="<?= $currentPage < $totalPages ? e($getUrl($currentPage + 1)) : '#' ?>" 
                           class="inline-flex items-center px-3 py-2 text-sm font-semibold text-slate-500 hover:text-slate-700 hover:bg-slate-50 rounded-r-md min-w-[38px] min-h-[38px] justify-center <?= $currentPage >= $totalPages ? 'pointer-events-none opacity-50' : '' ?>"
                           <?= $currentPage >= $totalPages ? 'aria-disabled="true"' : '' ?>
                           aria-label="Go to next page">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
<?php endif; ?>
