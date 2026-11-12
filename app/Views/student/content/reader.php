<div class="max-w-6xl mx-auto space-y-3 sm:space-y-4" id="reader-root">
    <!-- Header & Breadcrumbs Card (Compact on mobile/tablets, hidden in fullscreen) -->
    <div id="pdf-header-card" class="bg-white rounded-2xl border border-slate-200 shadow-xs p-3.5 sm:p-5 transition">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div class="min-w-0">
                <nav class="hidden sm:flex items-center gap-1.5 text-[11px] font-semibold text-slate-400 uppercase tracking-wider mb-1.5">
                    <a href="/student/dashboard" class="hover:text-sky-600 transition">Student Portal</a>
                    <span class="text-slate-300">/</span>
                    <a href="/student/subjects" class="hover:text-sky-600 transition">Subjects</a>
                    <span class="text-slate-300">/</span>
                    <a href="/student/content?class_subject_id=<?= (int)$item->classSubjectId ?>" class="hover:text-sky-600 transition">
                        <?= htmlspecialchars($item->classSubject?->subject?->name ?? 'Course Materials') ?>
                    </a>
                </nav>

                <div class="flex items-center gap-2 flex-wrap">
                    <h1 class="text-base sm:text-xl font-extrabold text-slate-900 tracking-tight leading-tight truncate">
                        <?= htmlspecialchars($item->title) ?>
                    </h1>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-lg text-[10px] font-bold bg-sky-50 text-sky-800 border border-sky-200">
                        <?= htmlspecialchars($item->classSubject?->subject?->code ?: 'SUBJ') ?>
                    </span>
                </div>

                <p class="text-[11px] text-slate-500 mt-1 flex items-center gap-2 flex-wrap">
                    <span class="truncate max-w-[200px] sm:max-w-xs">File: <strong class="text-slate-700"><?= htmlspecialchars($file->originalName) ?></strong></span>
                    <span>&bull;</span>
                    <span><?= htmlspecialchars($file->getFormattedSize()) ?></span>
                </p>
            </div>

            <!-- Action buttons -->
            <div class="flex items-center gap-2 flex-shrink-0">
                <a href="/student/content/<?= (int)$item->id ?>" 
                   class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold text-slate-700 bg-slate-100 hover:bg-slate-200 transition">
                    <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    <span>Back</span>
                </a>
                <a href="<?= htmlspecialchars($downloadUrl) ?>" 
                   class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl transition border border-slate-200 shadow-2xs">
                    <svg class="w-3.5 h-3.5 text-sky-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                    <span>Download</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Sticky PDF Toolbar (Responsive: Single Row on Mobile, Full on Desktop) -->
    <div id="pdf-toolbar-container" class="sticky top-2 z-30 bg-white/95 backdrop-blur-md rounded-2xl border border-slate-200 shadow-sm p-2 sm:px-4 sm:py-2.5 transition">
        <div class="flex items-center justify-between gap-2 text-xs font-semibold text-slate-700">
            <!-- Navigation (Prev / Page Num / Next) -->
            <div class="flex items-center gap-1 sm:gap-2">
                <?php if (!empty($sections)): ?>
                <button type="button" id="toggle-outline-btn" 
                        class="inline-flex items-center gap-1 sm:gap-1.5 p-1.5 sm:px-2.5 sm:py-1 rounded-lg border border-slate-200 bg-slate-50 hover:bg-slate-100 text-slate-700 transition" 
                        title="Document Sections Outline">
                    <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h7"/>
                    </svg>
                    <span class="hidden md:inline">Outline</span>
                    <span class="px-1 py-0.2 rounded-full text-[9px] font-bold bg-slate-200 text-slate-700"><?= count($sections) ?></span>
                </button>
                <?php endif; ?>

                <button type="button" id="prev-page" class="p-1.5 rounded-lg border border-slate-200 bg-slate-50 hover:bg-slate-100 disabled:opacity-40 disabled:cursor-not-allowed transition" title="Previous Page">
                    <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                    </svg>
                </button>

                <div class="flex items-center gap-1 text-[11px] sm:text-xs">
                    <input type="number" id="page-num" min="1" value="1" 
                           class="w-9 sm:w-12 px-1 py-1 text-center font-bold text-slate-800 bg-slate-50 border border-slate-300 rounded-md focus:ring-1 focus:ring-sky-500 focus:outline-none">
                    <span class="text-slate-400">/</span>
                    <span id="page-count" class="font-bold text-slate-800">-</span>
                </div>

                <button type="button" id="next-page" class="p-1.5 rounded-lg border border-slate-200 bg-slate-50 hover:bg-slate-100 disabled:opacity-40 disabled:cursor-not-allowed transition" title="Next Page">
                    <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                    </svg>
                </button>
            </div>

            <!-- Zoom Controls (Compact on mobile) -->
            <div class="flex items-center gap-1 sm:gap-2">
                <button type="button" id="zoom-out" class="p-1.5 rounded-lg border border-slate-200 bg-slate-50 hover:bg-slate-100 disabled:opacity-40 transition" title="Zoom Out">
                    <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20 12H4"/>
                    </svg>
                </button>

                <select id="scale-select" class="hidden sm:inline-block px-2 py-1 bg-slate-50 border border-slate-300 rounded-md text-xs font-bold text-slate-700 focus:ring-1 focus:ring-sky-500 focus:outline-none">
                    <option value="auto">Auto Fit</option>
                    <option value="page-width">Page Width</option>
                    <option value="0.75">75%</option>
                    <option value="1.0" selected>100%</option>
                    <option value="1.25">125%</option>
                    <option value="1.5">150%</option>
                    <option value="2.0">200%</option>
                </select>

                <button type="button" id="zoom-in" class="p-1.5 rounded-lg border border-slate-200 bg-slate-50 hover:bg-slate-100 disabled:opacity-40 transition" title="Zoom In">
                    <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                    </svg>
                </button>
            </div>

            <!-- Fullscreen & Progress Controls -->
            <div class="flex items-center gap-1 sm:gap-2">
                <!-- Desktop Reading Progress Bar -->
                <div class="hidden md:flex items-center gap-2.5 px-2.5 py-1 rounded-xl bg-slate-50 border border-slate-200">
                    <div class="flex flex-col gap-0.5">
                        <div class="flex items-center justify-between gap-3 text-[10px] uppercase font-bold text-slate-500">
                            <span>Progress</span>
                            <span id="progress-percent-label"><?= $progress ? (int)$progress->progressPercent : 0 ?>%</span>
                        </div>
                        <div class="w-20 lg:w-28 bg-slate-200 rounded-full h-1.5 overflow-hidden">
                            <div id="progress-bar-fill" class="h-1.5 rounded-full transition-all duration-300 <?= ($progress && $progress->isCompleted()) ? 'bg-emerald-500' : 'bg-sky-500' ?>" style="width: <?= $progress ? min(100, (int)$progress->progressPercent) : 0 ?>%"></div>
                        </div>
                    </div>
                </div>

                <!-- Mobile Progress Pill -->
                <span class="md:hidden text-[11px] font-bold font-mono text-sky-600 bg-sky-50 border border-sky-200 px-2 py-0.5 rounded-md">
                    <span id="mobile-progress-label"><?= $progress ? (int)$progress->progressPercent : 0 ?>%</span>
                </span>

                <button type="button" id="btn-fit-width" class="hidden sm:inline-flex items-center gap-1 px-2.5 py-1 rounded-lg border border-slate-200 bg-slate-50 hover:bg-slate-100 text-slate-700 transition" title="Fit to width">
                    <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/>
                    </svg>
                    <span class="hidden md:inline">Fit Width</span>
                </button>

                <button type="button" id="toggle-fullscreen" class="inline-flex items-center gap-1 p-1.5 sm:px-2.5 sm:py-1 rounded-lg border border-slate-200 bg-slate-50 hover:bg-slate-100 text-slate-700 transition" title="Fullscreen (F)">
                    <svg id="fullscreen-enter-icon" class="w-3.5 h-3.5 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 8V4m0 0h4M4 4l5 5m11-5h-4m4 0v4m0-4l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/>
                    </svg>
                    <svg id="fullscreen-exit-icon" class="w-3.5 h-3.5 text-slate-600 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 9L4 4m0 0v4m0-4h4m6 5l5-5m0 0v4m0-4h-4m-7 11l-5 5m0 0v-4m0 4h4m11-5l5 5m0 0v-4m0 4h-4"/>
                    </svg>
                    <span id="fullscreen-text" class="hidden sm:inline">Fullscreen</span>
                </button>
            </div>
        </div>

        <!-- Thin Mobile Progress Strip (Zero vertical footprint) -->
        <div class="md:hidden w-full bg-slate-100 rounded-full h-1 mt-1.5 overflow-hidden">
            <div id="mobile-progress-bar-fill" class="h-1 rounded-full transition-all duration-300 <?= ($progress && $progress->isCompleted()) ? 'bg-emerald-500' : 'bg-sky-500' ?>" style="width: <?= $progress ? min(100, (int)$progress->progressPercent) : 0 ?>%"></div>
        </div>

        <?php if (!empty($sections)): ?>
        <!-- Current Section Indicator Strip -->
        <div id="current-section-strip" class="hidden items-center justify-between text-xs pt-1.5 mt-1.5 border-t border-slate-100 text-slate-600">
            <div class="flex items-center gap-1.5 min-w-0">
                <span class="text-[9px] font-bold uppercase tracking-wider text-slate-400 flex-shrink-0">Section:</span>
                <span id="current-section-title" class="font-bold text-slate-800 text-xs truncate">-</span>
                <span id="current-section-pages" class="text-[10px] font-mono text-slate-400 flex-shrink-0"></span>
            </div>
            <div id="current-section-progress-wrap" class="flex items-center gap-1.5 text-[10px] flex-shrink-0">
                <span id="current-section-status-badge" class="hidden inline-flex items-center px-1.5 py-0.2 rounded text-[9px] font-bold bg-emerald-100 text-emerald-800">✓ Done</span>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <?php if (!empty($sections)): ?>
    <!-- Slide-over Section Outline Drawer -->
    <div id="outline-drawer" class="hidden fixed inset-0 z-50 overflow-hidden">
        <!-- Backdrop -->
        <div id="outline-backdrop" class="absolute inset-0 bg-slate-900/40 backdrop-blur-2xs transition-opacity"></div>

        <div class="absolute inset-y-0 left-0 max-w-full flex">
            <div class="w-screen max-w-sm bg-white shadow-2xl border-r border-slate-200 flex flex-col">
                <!-- Drawer Header -->
                <div class="p-4 border-b border-slate-200 flex items-center justify-between bg-slate-50/80">
                    <div class="flex items-center gap-2">
                        <span class="p-1.5 rounded-lg bg-emerald-50 text-emerald-600">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h7"/>
                            </svg>
                        </span>
                        <div>
                            <h2 class="text-sm font-bold text-slate-900">Document Outline</h2>
                            <p class="text-[11px] text-slate-400"><?= count($sections) ?> logical learning sections</p>
                        </div>
                    </div>
                    <button type="button" id="close-outline-btn" class="text-slate-400 hover:text-slate-600 p-1.5 rounded-lg">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <!-- Drawer Content: Sections List -->
                <div class="flex-1 overflow-y-auto p-4 space-y-2.5">
                    <?php foreach ($sections as $sec): 
                        $isSecUnlocked = $sec->isUnlocked ?? true;
                        $secPrereqs = $sec->prerequisiteStatus ?? null;
                    ?>
                        <div class="section-outline-item group p-3 rounded-xl border <?= $isSecUnlocked ? 'border-slate-200 hover:border-emerald-300 hover:bg-emerald-50/30 cursor-pointer' : 'border-amber-200 bg-amber-50/40 opacity-75 cursor-not-allowed' ?> transition"
                             data-section-id="<?= (int)$sec->id ?>"
                             data-start-page="<?= (int)$sec->startPage ?>"
                             data-end-page="<?= (int)$sec->endPage ?>"
                             data-is-unlocked="<?= $isSecUnlocked ? '1' : '0' ?>"
                             data-unmet-title="<?= !empty($secPrereqs['unmet']) ? htmlspecialchars(implode(', ', array_map(fn($u) => $u['title'], $secPrereqs['unmet']))) : '' ?>">
                            <div class="flex items-start justify-between gap-2 mb-1.5">
                                <div class="flex items-center gap-2">
                                    <span class="section-status-icon inline-flex items-center justify-center w-5 h-5 rounded-full text-[10px] font-bold <?= ($sec->isCompleted ?? false) ? 'bg-emerald-100 text-emerald-700' : ($isSecUnlocked ? 'bg-slate-100 text-slate-500' : 'bg-amber-100 text-amber-700') ?>">
                                        <?= ($sec->isCompleted ?? false) ? '✓' : ($isSecUnlocked ? '○' : '🔒') ?>
                                    </span>
                                    <span class="text-xs font-bold <?= $isSecUnlocked ? 'text-slate-900 group-hover:text-emerald-700' : 'text-amber-900' ?> transition">
                                        <?= htmlspecialchars($sec->title) ?>
                                    </span>
                                </div>
                                <span class="section-badge-pct text-[11px] font-bold <?= $isSecUnlocked ? 'text-slate-600' : 'text-amber-800' ?>">
                                    <?= $isSecUnlocked ? number_format((float)($sec->progressPercent ?? 0), 0) . '%' : '🔒 Locked' ?>
                                </span>
                            </div>

                            <div class="flex items-center justify-between text-[11px] text-slate-500 mb-1.5">
                                <span class="font-mono">Pages <?= (int)$sec->startPage ?> &ndash; <?= (int)$sec->endPage ?></span>
                                <span><?= (int)$sec->getTotalPages() ?> <?= $sec->getTotalPages() === 1 ? 'page' : 'pages' ?></span>
                            </div>

                            <?php if (!$isSecUnlocked && !empty($secPrereqs['unmet'])): ?>
                                <p class="text-[10px] text-amber-800 font-semibold mb-1.5">
                                    Requires: <?= htmlspecialchars(implode(', ', array_map(fn($u) => $u['title'], $secPrereqs['unmet']))) ?>
                                </p>
                            <?php endif; ?>

                            <!-- Section mini progress bar -->
                            <div class="w-full bg-slate-100 rounded-full h-1 overflow-hidden">
                                <div class="section-progress-bar-fill h-1 rounded-full transition-all duration-300 <?= ($sec->isCompleted ?? false) ? 'bg-emerald-500' : 'bg-sky-500' ?>" 
                                     style="width: <?= min(100, (float)($sec->progressPercent ?? 0)) ?>%"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Drawer Footer: Overall Progress -->
                <div class="p-3 bg-slate-50 border-t border-slate-200 text-xs text-slate-500 flex items-center justify-between">
                    <span>Document Progress: <strong id="outline-doc-progress"><?= $progress ? (int)$progress->progressPercent : 0 ?>%</strong></span>
                    <span class="text-[11px] text-slate-400">90% per section</span>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Viewer Viewport -->
    <div id="pdf-viewer-viewport" class="bg-slate-200/70 rounded-2xl border border-slate-300 p-3 sm:p-6 min-h-[600px] flex flex-col items-center justify-start overflow-auto transition">
        <!-- Loading Indicator -->
        <div id="pdf-loading" class="py-24 text-center space-y-3">
            <div class="inline-block animate-spin rounded-full h-10 w-10 border-4 border-sky-600 border-t-transparent"></div>
            <p class="text-xs font-bold text-slate-600 uppercase tracking-wider">Loading Document...</p>
            <p class="text-[11px] text-slate-400">Rendering streaming pages via PDF.js</p>
        </div>

        <!-- Error Notification Banner -->
        <div id="pdf-error" class="hidden max-w-lg mx-auto my-12 p-6 bg-white rounded-2xl border border-rose-200 shadow-sm text-center space-y-3">
            <div class="w-12 h-12 rounded-full bg-rose-50 text-rose-600 flex items-center justify-center mx-auto">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
            </div>
            <h3 class="text-sm font-bold text-slate-900" id="pdf-error-title">Unable to render document online</h3>
            <p class="text-xs text-slate-500" id="pdf-error-message">The document could not be loaded in the browser. You can still download the original attachment to view it.</p>
            <div>
                <a href="<?= htmlspecialchars($downloadUrl) ?>" 
                   class="inline-flex items-center gap-1.5 px-4 py-2 bg-sky-600 hover:bg-sky-700 text-white rounded-xl text-xs font-bold shadow-xs transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                    <span>Download File Directly</span>
                </a>
            </div>
        </div>

        <!-- Continuous Scroll Pages Container -->
        <div id="pdf-pages-container" class="hidden w-full flex flex-col items-center gap-4 sm:gap-6 py-2"></div>
    </div>
</div>

<style>
/* FULLSCREEN READING MODE: Pure document immersion, zero distractions */
#reader-root:fullscreen,
#reader-root.is-fullscreen {
    position: fixed !important;
    inset: 0 !important;
    width: 100vw !important;
    max-width: 100vw !important;
    height: 100vh !important;
    max-height: 100vh !important;
    margin: 0 !important;
    padding: 0 !important;
    background: #0f172a !important;
    z-index: 99999 !important;
    display: flex !important;
    flex-direction: column !important;
    overflow: hidden !important;
}

#reader-root:fullscreen #pdf-header-card,
#reader-root.is-fullscreen #pdf-header-card {
    display: none !important;
}

#reader-root:fullscreen #pdf-toolbar-container,
#reader-root.is-fullscreen #pdf-toolbar-container {
    position: relative !important;
    top: 0 !important;
    border-radius: 0 !important;
    border-left: none !important;
    border-right: none !important;
    border-top: none !important;
    flex-shrink: 0 !important;
    z-index: 50 !important;
}

#reader-root:fullscreen #pdf-viewer-viewport,
#reader-root.is-fullscreen #pdf-viewer-viewport {
    flex: 1 1 auto !important;
    height: calc(100vh - 55px) !important;
    max-height: calc(100vh - 55px) !important;
    min-height: 0 !important;
    border-radius: 0 !important;
    border: none !important;
    overflow-y: auto !important;
    -webkit-overflow-scrolling: touch !important;
    padding: 1rem !important;
    padding-bottom: 5rem !important;
}
</style>

<!-- Static PDF.js Assets (Mozilla distribution, no npm required) -->
<script src="/assets/js/vendor/pdfjs/pdf.min.js"></script>

<script>
(function() {
    'use strict';

    // Point to local static worker asset
    if (typeof pdfjsLib !== 'undefined') {
        pdfjsLib.GlobalWorkerOptions.workerSrc = '/assets/js/vendor/pdfjs/pdf.worker.min.js';
    } else {
        showError('PDF.js library failed to load.');
        return;
    }

    const streamUrl = <?= json_encode($streamUrl, JSON_UNESCAPED_SLASHES) ?>;
    const contentId = <?= (int)$item->id ?>;
    const initialLastPage = <?= (int)($initialLastPage ?? 1) ?>;
    const initialPagesRead = <?= json_encode($progress ? $progress->pagesReadJson : []) ?>;
    let isCompleted = <?= ($progress && $progress->isCompleted()) ? 'true' : 'false' ?>;
    const visitedPages = new Set(initialPagesRead);
    const sections = <?= json_encode(array_map(fn($s) => [
        'id' => (int)$s->id,
        'title' => $s->title,
        'start_page' => (int)$s->startPage,
        'end_page' => (int)$s->endPage,
        'sequence_order' => (int)$s->sequenceOrder,
        'total_pages' => (int)$s->getTotalPages(),
        'progress_percent' => (float)($s->progressPercent ?? 0),
        'is_completed' => (bool)($s->isCompleted ?? false)
    ], $sections ?? [])) ?>;
    let hasPendingChanges = false;
    let syncTimeout = null;

    let pdfDoc = null;
    let pageNum = 1;
    let currentScale = 1.0;
    let scaleMode = '1.0';
    let firstPageWidth = 595;
    let firstPageHeight = 842;

    const pagesContainer = document.getElementById('pdf-pages-container');
    const pageNumInput = document.getElementById('page-num');
    const pageCountSpan = document.getElementById('page-count');
    const prevBtn = document.getElementById('prev-page');
    const nextBtn = document.getElementById('next-page');
    const zoomInBtn = document.getElementById('zoom-in');
    const zoomOutBtn = document.getElementById('zoom-out');
    const scaleSelect = document.getElementById('scale-select');
    const fitWidthBtn = document.getElementById('btn-fit-width');
    const toggleFullscreenBtn = document.getElementById('toggle-fullscreen');
    const fullscreenEnterIcon = document.getElementById('fullscreen-enter-icon');
    const fullscreenExitIcon = document.getElementById('fullscreen-exit-icon');
    const fullscreenText = document.getElementById('fullscreen-text');
    const loadingElem = document.getElementById('pdf-loading');
    const errorElem = document.getElementById('pdf-error');
    const viewportElem = document.getElementById('pdf-viewer-viewport');
    const readerRoot = document.getElementById('reader-root');
    const progressPercentLabel = document.getElementById('progress-percent-label');
    const progressBarFill = document.getElementById('progress-bar-fill');
    const progressStatusBadge = document.getElementById('progress-status-badge');

    // Caches and state for continuous scroll
    const renderedPages = new Map(); // pageNum => { scale: number }
    const renderingPages = new Set(); // pageNum
    let renderObserver = null;
    let scrollAnimationFrame = null;

    // Outline Drawer Elements
    const outlineDrawer = document.getElementById('outline-drawer');
    const toggleOutlineBtn = document.getElementById('toggle-outline-btn');
    const closeOutlineBtn = document.getElementById('close-outline-btn');
    const outlineBackdrop = document.getElementById('outline-backdrop');

    function openOutline() {
        if (outlineDrawer) outlineDrawer.classList.remove('hidden');
    }

    function closeOutline() {
        if (outlineDrawer) outlineDrawer.classList.add('hidden');
    }

    if (toggleOutlineBtn) toggleOutlineBtn.addEventListener('click', openOutline);
    if (closeOutlineBtn) closeOutlineBtn.addEventListener('click', closeOutline);
    if (outlineBackdrop) outlineBackdrop.addEventListener('click', closeOutline);

    // Section outline jump click
    document.querySelectorAll('.section-outline-item').forEach(function(el) {
        el.addEventListener('click', function() {
            if (this.dataset.isUnlocked === '0') {
                const unmet = this.dataset.unmetTitle || 'prerequisite sections';
                alert('This section is locked. You must first complete: ' + unmet);
                return;
            }
            const targetPage = parseInt(this.dataset.startPage, 10);
            if (targetPage >= 1 && pdfDoc && targetPage <= pdfDoc.numPages) {
                scrollToPage(targetPage);
                closeOutline();
            }
        });
    });

    function updateProgressUI() {
        if (!pdfDoc) return;
        const total = pdfDoc.numPages;
        const count = visitedPages.size;
        const pct = total > 0 ? Math.min(100, Math.round((count / total) * 100)) : 0;

        if (pct >= 90) {
            isCompleted = true;
        }

        if (progressPercentLabel) {
            progressPercentLabel.textContent = pct + '%';
        }
        const outlineDocProgress = document.getElementById('outline-doc-progress');
        if (outlineDocProgress) {
            outlineDocProgress.textContent = pct + '%';
        }
        if (progressBarFill) {
            progressBarFill.style.width = pct + '%';
            if (isCompleted) {
                progressBarFill.classList.remove('bg-sky-500');
                progressBarFill.classList.add('bg-emerald-500');
            }
        }
        const mobileProgLabel = document.getElementById('mobile-progress-label');
        if (mobileProgLabel) {
            mobileProgLabel.textContent = pct + '%';
        }
        const mobileBarFill = document.getElementById('mobile-progress-bar-fill');
        if (mobileBarFill) {
            mobileBarFill.style.width = pct + '%';
            if (isCompleted) {
                mobileBarFill.classList.remove('bg-sky-500');
                mobileBarFill.classList.add('bg-emerald-500');
            }
        }
        if (progressStatusBadge && isCompleted) {
            progressStatusBadge.classList.remove('hidden');
        }

        // Update Section Progress
        sections.forEach(function(sec) {
            let secRead = 0;
            for (let p = sec.start_page; p <= sec.end_page; p++) {
                if (visitedPages.has(p)) secRead++;
            }
            const secTotal = sec.end_page - sec.start_page + 1;
            const secPct = secTotal > 0 ? Math.min(100, Math.round((secRead / secTotal) * 100)) : 0;
            if (secPct >= 90) {
                sec.is_completed = true;
            }
            sec.progress_percent = secPct;

            const itemEl = document.querySelector('.section-outline-item[data-section-id="' + sec.id + '"]');
            if (itemEl) {
                const pctEl = itemEl.querySelector('.section-badge-pct');
                const fillEl = itemEl.querySelector('.section-progress-bar-fill');
                const iconEl = itemEl.querySelector('.section-status-icon');
                if (pctEl) pctEl.textContent = (sec.is_completed ? '100%' : secPct + '%');
                if (fillEl) {
                    fillEl.style.width = secPct + '%';
                    if (sec.is_completed) {
                        fillEl.classList.remove('bg-sky-500');
                        fillEl.classList.add('bg-emerald-500');
                    }
                }
                if (iconEl) {
                    if (sec.is_completed) {
                        iconEl.textContent = '✓';
                        iconEl.className = 'section-status-icon inline-flex items-center justify-center w-5 h-5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-700';
                    } else if (secPct > 0) {
                        iconEl.textContent = '◉';
                        iconEl.className = 'section-status-icon inline-flex items-center justify-center w-5 h-5 rounded-full text-[10px] font-bold bg-sky-100 text-sky-700';
                    } else {
                        iconEl.textContent = '○';
                        iconEl.className = 'section-status-icon inline-flex items-center justify-center w-5 h-5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-500';
                    }
                }
            }
        });

        // Update Current Section Indicator Strip
        const activeSec = sections.find(s => pageNum >= s.start_page && pageNum <= s.end_page);
        const strip = document.getElementById('current-section-strip');
        if (strip) {
            if (activeSec) {
                strip.classList.remove('hidden');
                strip.classList.add('flex');
                const titleEl = document.getElementById('current-section-title');
                const pagesEl = document.getElementById('current-section-pages');
                const progEl = document.getElementById('current-section-progress-text');
                const badgeEl = document.getElementById('current-section-status-badge');
                if (titleEl) titleEl.textContent = activeSec.title;
                if (pagesEl) pagesEl.textContent = '(p. ' + activeSec.start_page + '–' + activeSec.end_page + ')';
                if (progEl) progEl.textContent = (activeSec.is_completed ? '100%' : activeSec.progress_percent + '%');
                if (badgeEl) {
                    if (activeSec.is_completed) badgeEl.classList.remove('hidden');
                    else badgeEl.classList.add('hidden');
                }
            } else {
                strip.classList.add('hidden');
                strip.classList.remove('flex');
            }
        }

        // Highlight active section in outline drawer
        document.querySelectorAll('.section-outline-item').forEach(function(el) {
            const sId = parseInt(el.dataset.sectionId, 10);
            if (activeSec && activeSec.id === sId) {
                el.classList.add('border-emerald-500', 'bg-emerald-50/50');
            } else {
                el.classList.remove('border-emerald-500', 'bg-emerald-50/50');
            }
        });
    }

    function scheduleSync(immediate = false) {
        hasPendingChanges = true;
        if (syncTimeout) {
            clearTimeout(syncTimeout);
            syncTimeout = null;
        }

        if (immediate) {
            sendProgress();
        } else {
            // Debounce after 2.5 seconds of quiet time
            syncTimeout = setTimeout(sendProgress, 2500);
        }
    }

    function sendProgress() {
        if (!pdfDoc || !hasPendingChanges) return;
        hasPendingChanges = false;
        if (syncTimeout) {
            clearTimeout(syncTimeout);
            syncTimeout = null;
        }

        const payload = {
            current_page: pageNum,
            total_pages: pdfDoc.numPages,
            viewed_pages: Array.from(visitedPages)
        };

        fetch('/student/content/' + contentId + '/progress', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            credentials: 'same-origin',
            body: JSON.stringify(payload)
        }).then(function(res) {
            return res.json();
        }).then(function(data) {
            if (data && data.success && data.data) {
                if (data.data.is_completed) {
                    isCompleted = true;
                }
                if (Array.isArray(data.data.sections)) {
                    data.data.sections.forEach(function(sData) {
                        const sec = sections.find(s => s.id === sData.id);
                        if (sec) {
                            if (sData.is_completed) sec.is_completed = true;
                            if (typeof sData.progress_percent !== 'undefined') {
                                sec.progress_percent = sData.progress_percent;
                            }
                        }
                    });
                }
                updateProgressUI();
            }
        }).catch(function(err) {
            console.warn('Progress autosave failed:', err);
            hasPendingChanges = true;
        });
    }

    function flushProgressBeacon() {
        if (!pdfDoc || !hasPendingChanges) return;
        const payload = {
            current_page: pageNum,
            total_pages: pdfDoc.numPages,
            viewed_pages: Array.from(visitedPages)
        };
        const blob = new Blob([JSON.stringify(payload)], { type: 'application/json' });
        if (navigator.sendBeacon) {
            navigator.sendBeacon('/student/content/' + contentId + '/progress', blob);
            hasPendingChanges = false;
        } else {
            fetch('/student/content/' + contentId + '/progress', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'same-origin',
                body: JSON.stringify(payload),
                keepalive: true
            }).catch(() => {});
        }
    }

    window.addEventListener('beforeunload', flushProgressBeacon);
    document.addEventListener('visibilitychange', function() {
        if (document.visibilityState === 'hidden') {
            flushProgressBeacon();
        }
    });

    // Periodic safety check every 25 seconds
    setInterval(function() {
        if (hasPendingChanges) {
            sendProgress();
        }
    }, 25000);

    function showError(msg) {
        if (loadingElem) loadingElem.classList.add('hidden');
        if (pagesContainer) pagesContainer.classList.add('hidden');
        if (errorElem) {
            errorElem.classList.remove('hidden');
            const desc = document.getElementById('pdf-error-message');
            if (desc && msg) desc.textContent = msg;
        }
    }

    /**
     * Build placeholder DOM elements for all document pages.
     */
    function buildPageElements() {
        if (!pdfDoc) return;
        pagesContainer.innerHTML = '';

        const estWidth = Math.floor(firstPageWidth * currentScale);
        const estHeight = Math.floor(firstPageHeight * currentScale);

        for (let p = 1; p <= pdfDoc.numPages; p++) {
            const wrapper = document.createElement('div');
            wrapper.id = 'pdf-page-' + p;
            wrapper.className = 'pdf-page-wrapper relative bg-white rounded-xl shadow-md transition-all duration-150 flex flex-col items-center justify-center overflow-hidden flex-shrink-0';
            wrapper.dataset.pageNumber = p;
            wrapper.style.width = estWidth + 'px';
            wrapper.style.height = estHeight + 'px';
            wrapper.style.minHeight = '180px';

            const canvas = document.createElement('canvas');
            canvas.id = 'pdf-canvas-' + p;
            canvas.className = 'pdf-page-canvas block rounded-xl max-w-full';
            canvas.dataset.pageNumber = p;

            const loading = document.createElement('div');
            loading.className = 'pdf-page-loading absolute inset-0 flex items-center justify-center bg-slate-50 text-slate-400 text-xs font-semibold select-none';
            loading.innerHTML = '<span class="inline-flex items-center gap-2"><svg class="animate-spin h-4 w-4 text-sky-600" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg> Loading page ' + p + '...</span>';

            wrapper.appendChild(canvas);
            wrapper.appendChild(loading);
            pagesContainer.appendChild(wrapper);
        }

        if (loadingElem) loadingElem.classList.add('hidden');
        if (pagesContainer) pagesContainer.classList.remove('hidden');

        setupRenderObserver();
    }

    /**
     * Setup IntersectionObserver for continuous lazy rendering as user scrolls.
     */
    function setupRenderObserver() {
        if (renderObserver) {
            renderObserver.disconnect();
            renderObserver = null;
        }

        renderObserver = new IntersectionObserver(function(entries) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting) {
                    const p = parseInt(entry.target.dataset.pageNumber, 10);
                    if (p) renderPage(p);
                }
            });
        }, {
            root: viewportElem,
            rootMargin: '500px 0px 500px 0px',
            threshold: 0.01
        });

        document.querySelectorAll('.pdf-page-wrapper').forEach(function(wrapper) {
            renderObserver.observe(wrapper);
        });
    }

    /**
     * Render the specified page onto its canvas.
     */
    function renderPage(num) {
        if (!pdfDoc || num < 1 || num > pdfDoc.numPages) return;
        if (renderingPages.has(num)) return;

        const renderedInfo = renderedPages.get(num);
        if (renderedInfo && Math.abs(renderedInfo.scale - currentScale) < 0.01) {
            return; // Already rendered at current scale
        }

        const wrapper = document.getElementById('pdf-page-' + num);
        const canvas = document.getElementById('pdf-canvas-' + num);
        if (!wrapper || !canvas) return;

        const loadingOverlay = wrapper.querySelector('.pdf-page-loading');
        renderingPages.add(num);

        pdfDoc.getPage(num).then(function(page) {
            const outputScale = window.devicePixelRatio || 1;
            const viewport = page.getViewport({ scale: currentScale });

            canvas.width = Math.floor(viewport.width * outputScale);
            canvas.height = Math.floor(viewport.height * outputScale);
            canvas.style.width = Math.floor(viewport.width) + 'px';
            canvas.style.height = Math.floor(viewport.height) + 'px';
            wrapper.style.width = Math.floor(viewport.width) + 'px';
            wrapper.style.height = Math.floor(viewport.height) + 'px';

            const ctx = canvas.getContext('2d');
            const transform = outputScale !== 1
                ? [outputScale, 0, 0, outputScale, 0, 0]
                : null;

            const renderContext = {
                canvasContext: ctx,
                transform: transform,
                viewport: viewport
            };

            const renderTask = page.render(renderContext);
            renderTask.promise.then(function() {
                renderingPages.delete(num);
                renderedPages.set(num, { scale: currentScale });
                if (loadingOverlay) loadingOverlay.style.display = 'none';
            }).catch(function(err) {
                renderingPages.delete(num);
                console.error('Error rendering page ' + num + ':', err);
            });
        }).catch(function(err) {
            renderingPages.delete(num);
            console.error('Error fetching page ' + num + ':', err);
        });
    }

    /**
     * Detect and update current active page from viewport scroll position.
     */
    function onViewportScroll() {
        if (scrollAnimationFrame) return;
        scrollAnimationFrame = requestAnimationFrame(function() {
            scrollAnimationFrame = null;
            updateActivePageFromScroll();
        });
    }

    function updateActivePageFromScroll() {
        if (!pdfDoc) return;
        const viewportRect = viewportElem.getBoundingClientRect();
        // Target vertical reference line at 35% height of viewport
        const targetY = viewportRect.top + Math.min(250, viewportRect.height * 0.35);

        let active = pageNum;
        let minDistance = Infinity;

        for (let p = 1; p <= pdfDoc.numPages; p++) {
            const el = document.getElementById('pdf-page-' + p);
            if (!el) continue;
            const rect = el.getBoundingClientRect();
            if (rect.top <= targetY && rect.bottom >= targetY) {
                active = p;
                break;
            }
            const dist = Math.abs(rect.top - targetY);
            if (dist < minDistance) {
                minDistance = dist;
                active = p;
            }
        }

        if (active !== pageNum) {
            pageNum = active;
            pageNumInput.value = pageNum;
            prevBtn.disabled = (pageNum <= 1);
            nextBtn.disabled = (pageNum >= pdfDoc.numPages);

            if (!visitedPages.has(pageNum)) {
                visitedPages.add(pageNum);
                hasPendingChanges = true;
                updateProgressUI();
                scheduleSync();
            } else {
                updateProgressUI();
            }
        }
    }

    viewportElem.addEventListener('scroll', onViewportScroll, { passive: true });

    /**
     * Scroll viewport to bring a specific page into view.
     */
    function scrollToPage(num, smooth = true) {
        if (!pdfDoc || num < 1 || num > pdfDoc.numPages) return;
        const targetEl = document.getElementById('pdf-page-' + num);
        if (targetEl) {
            targetEl.scrollIntoView({ behavior: smooth ? 'smooth' : 'auto', block: 'start' });
            pageNum = num;
            pageNumInput.value = pageNum;
            prevBtn.disabled = (pageNum <= 1);
            nextBtn.disabled = (pageNum >= pdfDoc.numPages);

            renderPage(num);
            if (num + 1 <= pdfDoc.numPages) renderPage(num + 1);

            if (!visitedPages.has(pageNum)) {
                visitedPages.add(pageNum);
                hasPendingChanges = true;
                scheduleSync();
            }
            updateProgressUI();
        }
    }

    function onPrevPage() {
        if (pageNum <= 1) return;
        scrollToPage(pageNum - 1);
    }

    function onNextPage() {
        if (!pdfDoc || pageNum >= pdfDoc.numPages) return;
        scrollToPage(pageNum + 1);
    }

    /**
     * Recalculate dimensions for all page wrappers and re-render current viewport.
     */
    function reapplyScale() {
        if (!pdfDoc || !firstPageWidth) return;

        if (scaleMode === 'page-width' || scaleMode === 'auto') {
            const availableWidth = Math.max(260, viewportElem.clientWidth - (window.innerWidth < 640 ? 24 : 48));
            currentScale = Math.max(0.4, Math.min(2.5, availableWidth / firstPageWidth));
        }

        const estWidth = Math.floor(firstPageWidth * currentScale);
        const estHeight = Math.floor(firstPageHeight * currentScale);

        renderedPages.clear();

        for (let p = 1; p <= pdfDoc.numPages; p++) {
            const wrapper = document.getElementById('pdf-page-' + p);
            if (wrapper) {
                wrapper.style.width = estWidth + 'px';
                wrapper.style.height = estHeight + 'px';
                const loadingOverlay = wrapper.querySelector('.pdf-page-loading');
                if (loadingOverlay) loadingOverlay.style.display = 'flex';
            }
        }

        // Render current active page and immediate neighbors
        renderPage(pageNum);
        if (pageNum + 1 <= pdfDoc.numPages) renderPage(pageNum + 1);
        if (pageNum - 1 >= 1) renderPage(pageNum - 1);
    }

    // Attach button listeners
    prevBtn.addEventListener('click', onPrevPage);
    nextBtn.addEventListener('click', onNextPage);

    pageNumInput.addEventListener('change', function() {
        const val = parseInt(this.value, 10);
        if (val >= 1 && pdfDoc && val <= pdfDoc.numPages) {
            scrollToPage(val);
        } else {
            this.value = pageNum;
        }
    });

    zoomInBtn.addEventListener('click', function() {
        scaleMode = 'custom';
        currentScale = Math.min(3.0, currentScale + 0.25);
        scaleSelect.value = currentScale.toFixed(2);
        reapplyScale();
    });

    zoomOutBtn.addEventListener('click', function() {
        scaleMode = 'custom';
        currentScale = Math.max(0.5, currentScale - 0.25);
        scaleSelect.value = currentScale.toFixed(2);
        reapplyScale();
    });

    scaleSelect.addEventListener('change', function() {
        const val = this.value;
        if (val === 'auto' || val === 'page-width') {
            scaleMode = val;
            reapplyScale();
        } else {
            const num = parseFloat(val);
            if (!isNaN(num)) {
                scaleMode = 'custom';
                currentScale = num;
                reapplyScale();
            }
        }
    });

    if (fitWidthBtn) {
        fitWidthBtn.addEventListener('click', function() {
            scaleMode = 'page-width';
            scaleSelect.value = 'page-width';
            reapplyScale();
        });
    }

    // Fullscreen Controls with Fallback for Mobile / iOS
    function setFullscreenMode(enable) {
        if (enable) {
            readerRoot.classList.add('is-fullscreen');
            document.body.classList.add('overflow-hidden');
            fullscreenEnterIcon.classList.add('hidden');
            fullscreenExitIcon.classList.remove('hidden');
            fullscreenText.textContent = 'Exit Fullscreen';
        } else {
            readerRoot.classList.remove('is-fullscreen');
            document.body.classList.remove('overflow-hidden');
            fullscreenEnterIcon.classList.remove('hidden');
            fullscreenExitIcon.classList.add('hidden');
            fullscreenText.textContent = 'Fullscreen';
        }
        setTimeout(function() {
            reapplyScale();
        }, 150);
    }

    toggleFullscreenBtn.addEventListener('click', function() {
        const isCurrentlyFull = !!document.fullscreenElement || readerRoot.classList.contains('is-fullscreen');
        if (!isCurrentlyFull) {
            if (readerRoot.requestFullscreen) {
                readerRoot.requestFullscreen().then(() => {
                    setFullscreenMode(true);
                }).catch(() => {
                    setFullscreenMode(true);
                });
            } else {
                setFullscreenMode(true);
            }
        } else {
            if (document.fullscreenElement && document.exitFullscreen) {
                document.exitFullscreen().catch(() => {});
            }
            setFullscreenMode(false);
        }
    });

    document.addEventListener('fullscreenchange', function() {
        const isFull = !!document.fullscreenElement;
        setFullscreenMode(isFull);
    });

    // Keyboard shortcuts
    window.addEventListener('keydown', function(e) {
        if (e.target.tagName === 'INPUT' || e.target.tagName === 'SELECT' || e.target.tagName === 'TEXTAREA') {
            return;
        }
        if (e.key === 'ArrowLeft' || e.key === 'PageUp') {
            e.preventDefault();
            onPrevPage();
        } else if (e.key === 'ArrowRight' || e.key === 'PageDown' || e.key === ' ') {
            e.preventDefault();
            onNextPage();
        } else if (e.key === '+' || e.key === '=') {
            e.preventDefault();
            zoomInBtn.click();
        } else if (e.key === '-') {
            e.preventDefault();
            zoomOutBtn.click();
        } else if (e.key === 'f' || e.key === 'F') {
            e.preventDefault();
            toggleFullscreenBtn.click();
        } else if (e.key === 'Escape') {
            closeOutline();
        }
    });

    // Responsive resize handler (debounced)
    let resizeTimeout = null;
    window.addEventListener('resize', function() {
        if (scaleMode === 'page-width' || scaleMode === 'auto') {
            clearTimeout(resizeTimeout);
            resizeTimeout = setTimeout(function() {
                reapplyScale();
            }, 150);
        }
    });

    // Initialize document stream
    const loadingTask = pdfjsLib.getDocument({
        url: streamUrl,
        disableRange: false,
        disableStream: false,
        disableAutoFetch: false
    });

    loadingTask.promise.then(function(doc) {
        pdfDoc = doc;
        pageCountSpan.textContent = doc.numPages;
        pageNumInput.max = doc.numPages;

        // Fetch Page 1 to establish baseline dimensions
        doc.getPage(1).then(function(firstPage) {
            const unscaledViewport = firstPage.getViewport({ scale: 1 });
            firstPageWidth = unscaledViewport.width || 595;
            firstPageHeight = unscaledViewport.height || 842;

            // Auto-select page-width for mobile screens
            if (window.innerWidth < 768) {
                scaleMode = 'page-width';
                scaleSelect.value = 'page-width';
                const availableWidth = Math.max(260, viewportElem.clientWidth - 24);
                currentScale = Math.max(0.4, Math.min(2.5, availableWidth / firstPageWidth));
            }

            buildPageElements();

            // Auto-resume from initialLastPage if valid
            let targetPage = 1;
            if (initialLastPage >= 1 && initialLastPage <= doc.numPages) {
                targetPage = initialLastPage;
            }

            pageNum = targetPage;
            pageNumInput.value = pageNum;
            prevBtn.disabled = (pageNum <= 1);
            nextBtn.disabled = (pageNum >= doc.numPages);

            // Render active page and adjacent page
            renderPage(targetPage);
            if (targetPage + 1 <= doc.numPages) renderPage(targetPage + 1);

            if (!visitedPages.has(targetPage)) {
                visitedPages.add(targetPage);
                hasPendingChanges = true;
            }
            updateProgressUI();

            if (targetPage > 1) {
                setTimeout(function() {
                    scrollToPage(targetPage, false);
                }, 150);
            }
        });
    }).catch(function(err) {
        console.error('PDF loading error:', err);
        showError('Failed to load PDF document. Server returned: ' + (err.message || 'Stream error'));
    });
})();
</script>
