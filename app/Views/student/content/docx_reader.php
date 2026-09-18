<div class="max-w-6xl mx-auto space-y-4" id="docx-reader-root">
    <!-- Header & Breadcrumbs Card -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-5 transition">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
            <div>
                <nav class="flex items-center gap-2 text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">
                    <a href="/student/dashboard" class="hover:text-sky-600 transition">Student Portal</a>
                    <span class="text-slate-300">/</span>
                    <a href="/student/subjects" class="hover:text-sky-600 transition">Subjects</a>
                    <span class="text-slate-300">/</span>
                    <a href="/student/content?class_subject_id=<?= (int)$item->classSubjectId ?>" class="hover:text-sky-600 transition">
                        <?= htmlspecialchars($item->classSubject?->subject?->name ?? 'Course Materials') ?>
                    </a>
                    <span class="text-slate-300">/</span>
                    <a href="/student/content/<?= (int)$item->id ?>" class="hover:text-sky-600 transition">
                        <?= htmlspecialchars($item->title) ?>
                    </a>
                </nav>

                <div class="flex flex-wrap items-center gap-3">
                    <h1 class="text-xl sm:text-2xl font-extrabold text-slate-900 tracking-tight leading-tight">
                        <?= htmlspecialchars($item->title) ?>
                    </h1>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-lg text-xs font-bold bg-sky-50 text-sky-800 border border-sky-200">
                        <?= htmlspecialchars($item->classSubject?->subject?->code ?: 'SUBJ') ?>
                    </span>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-lg text-xs font-bold bg-blue-50 text-blue-800 border border-blue-200">
                        Word Document Reader
                    </span>
                </div>

                <p class="text-xs text-slate-500 mt-1.5 flex items-center gap-2 flex-wrap">
                    <span>File: <strong class="text-slate-700"><?= htmlspecialchars($file->originalName) ?></strong></span>
                    <span>&bull;</span>
                    <span>Size: <strong><?= htmlspecialchars($file->getFormattedSize()) ?></strong></span>
                    <span>&bull;</span>
                    <span>Teacher: <strong><?= htmlspecialchars($item->teacher?->user?->name ?? ($item->teacher?->name ?? 'Subject Teacher')) ?></strong></span>
                </p>
            </div>

            <!-- Action buttons -->
            <div class="flex items-center gap-2.5 flex-wrap">
                <a href="/student/content/<?= (int)$item->id ?>" 
                   class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-bold text-slate-700 bg-slate-100 hover:bg-slate-200 transition">
                    <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    <span>Back to Lesson</span>
                </a>
                <a href="<?= htmlspecialchars($downloadUrl) ?>" 
                   class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl transition border border-slate-200 shadow-2xs">
                    <svg class="w-4 h-4 text-sky-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                    <span>Download</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Sticky DOCX Toolbar -->
    <div id="docx-toolbar-container" class="sticky top-2 z-30 bg-white/95 backdrop-blur-md rounded-2xl border border-slate-200 shadow-sm px-4 py-2.5 transition">
        <div class="flex flex-wrap items-center justify-between gap-3 text-xs font-semibold text-slate-700">
            <!-- Navigation / Location indicator -->
            <div class="flex items-center gap-2">
                <button type="button" id="btn-scroll-top" class="p-1.5 rounded-lg border border-slate-200 bg-slate-50 hover:bg-slate-100 text-slate-700 transition" title="Scroll to Top">
                    <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 10l7-7m0 0l7 7m-7-7v18"/>
                    </svg>
                </button>

                <div class="flex items-center gap-1.5 text-xs text-slate-600 bg-slate-50 border border-slate-200 px-2.5 py-1 rounded-lg">
                    <span class="text-slate-400 font-bold uppercase text-[10px]">Section</span>
                    <span id="current-block-label" class="font-extrabold text-slate-900">1</span>
                    <span class="text-slate-400">/</span>
                    <span id="total-blocks-label" class="font-semibold text-slate-700">-</span>
                </div>

                <button type="button" id="btn-prev-block" class="p-1.5 rounded-lg border border-slate-200 bg-slate-50 hover:bg-slate-100 disabled:opacity-40 disabled:cursor-not-allowed transition" title="Previous Section">
                    <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                    </svg>
                </button>
                <button type="button" id="btn-next-block" class="p-1.5 rounded-lg border border-slate-200 bg-slate-50 hover:bg-slate-100 disabled:opacity-40 disabled:cursor-not-allowed transition" title="Next Section">
                    <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                    </svg>
                </button>
            </div>

            <!-- Zoom Controls -->
            <div class="flex items-center gap-2">
                <button type="button" id="zoom-out" class="p-1.5 rounded-lg border border-slate-200 bg-slate-50 hover:bg-slate-100 disabled:opacity-40 transition" title="Zoom Out (-)">
                    <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20 12H4"/>
                    </svg>
                </button>

                <button type="button" id="zoom-reset" class="px-2 py-1 rounded-lg border border-slate-200 bg-slate-50 hover:bg-slate-100 text-[11px] font-bold text-slate-800 transition" title="Reset Zoom">
                    <span id="zoom-level">100%</span>
                </button>

                <button type="button" id="zoom-in" class="p-1.5 rounded-lg border border-slate-200 bg-slate-50 hover:bg-slate-100 disabled:opacity-40 transition" title="Zoom In (+)">
                    <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                    </svg>
                </button>
            </div>

            <!-- Progress & Fullscreen -->
            <div class="flex items-center gap-2">
                <!-- Reading Progress Bar -->
                <div class="flex items-center gap-2.5 px-2.5 py-1 rounded-xl bg-slate-50 border border-slate-200">
                    <div class="flex flex-col gap-0.5">
                        <div class="flex items-center justify-between gap-3 text-[10px] uppercase font-bold text-slate-500">
                            <span>Reading Progress</span>
                            <span id="progress-percent-label"><?= $progress ? (int)$progress->progressPercent : 0 ?>%</span>
                        </div>
                        <div class="w-24 sm:w-28 bg-slate-200 rounded-full h-1.5 overflow-hidden">
                            <div id="progress-bar-fill" class="h-1.5 rounded-full transition-all duration-300 <?= ($progress && $progress->isCompleted()) ? 'bg-emerald-500' : 'bg-sky-500' ?>" style="width: <?= $progress ? min(100, (int)$progress->progressPercent) : 0 ?>%"></div>
                        </div>
                    </div>
                    <span id="progress-status-badge" class="<?= ($progress && $progress->isCompleted()) ? '' : 'hidden' ?> inline-flex items-center gap-0.5 px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-300">
                        <span>Completed</span>
                        <svg class="w-3 h-3 text-emerald-600 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    </span>
                </div>

                <button type="button" id="toggle-fullscreen" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg border border-slate-200 bg-slate-50 hover:bg-slate-100 text-slate-700 transition" title="Fullscreen">
                    <svg id="fullscreen-enter-icon" class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/>
                    </svg>
                    <svg id="fullscreen-exit-icon" class="w-3.5 h-3.5 text-slate-500 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 9L4 4m0 0v4m0-4h4m6 5l5-5m0 0v4m0-4h-4m-7 11l-5 5m0 0v-4m0 4h4m11-5l5 5m0 0v-4m0 4h-4"/>
                    </svg>
                    <span id="fullscreen-text" class="hidden sm:inline">Fullscreen</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Viewer Viewport & Container -->
    <div id="docx-viewport" class="bg-slate-100/90 rounded-2xl border border-slate-200 shadow-inner p-4 sm:p-6 min-h-[650px] overflow-auto flex flex-col items-center">
        <!-- Loading State Indicator -->
        <div id="docx-loading" class="flex flex-col items-center justify-center py-24 text-slate-500 space-y-4">
            <div class="relative w-12 h-12">
                <div class="w-12 h-12 rounded-full border-4 border-slate-200 border-t-sky-600 animate-spin"></div>
            </div>
            <p class="text-sm font-semibold text-slate-600" id="docx-loading-text">Loading Word document...</p>
            <p class="text-xs text-slate-400">Parsing document structure and styles</p>
        </div>

        <!-- Render Target Canvas -->
        <div id="docx-container" class="w-full max-w-4xl mx-auto transition-transform origin-top"></div>

        <!-- Error State Indicator -->
        <div id="docx-error" class="hidden flex-col items-center justify-center py-20 text-center space-y-4 max-w-md mx-auto">
            <div class="w-14 h-14 rounded-2xl bg-rose-50 text-rose-500 flex items-center justify-center shadow-xs">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
            </div>
            <h3 class="text-base font-bold text-slate-800" id="docx-error-title">Unable to render document</h3>
            <p class="text-xs text-slate-500" id="docx-error-desc">
                An unexpected error occurred while parsing this document. You can still download the original file to view it on your device.
            </p>
            <a href="<?= htmlspecialchars($downloadUrl) ?>" 
               class="inline-flex items-center gap-2 px-4 py-2 bg-sky-600 hover:bg-sky-700 text-white rounded-xl text-xs font-bold transition shadow-xs">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                </svg>
                <span>Download <?= htmlspecialchars($file->originalName) ?></span>
            </a>
        </div>
    </div>
</div>

<style>
/* Clean, professional styling for rendered DOCX content */
#docx-container .docx-wrapper {
    background: transparent !important;
    padding: 0 !important;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 1.5rem;
}
#docx-container section.docx,
#docx-container .docx {
    background: #ffffff !important;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -2px rgba(0, 0, 0, 0.05) !important;
    border: 1px solid #e2e8f0 !important;
    border-radius: 0.75rem !important;
    margin-bottom: 1.25rem !important;
    padding: 2.5rem 3rem !important;
    max-width: 100% !important;
    box-sizing: border-box !important;
    overflow-x: auto !important;
}
@media (max-width: 640px) {
    #docx-container section.docx,
    #docx-container .docx {
        padding: 1.25rem 1rem !important;
        border-radius: 0.5rem !important;
    }
}
#docx-container table {
    max-width: 100% !important;
    border-collapse: collapse !important;
}
#docx-container img {
    max-width: 100% !important;
    height: auto !important;
}
/* Reading block observation highlight (subtle active indicator) */
.docx-reading-block {
    position: relative;
    transition: outline 0.2s ease;
}
</style>

<!-- Vendor Dependencies -->
<script src="/assets/js/vendor/docx/jszip.min.js"></script>
<script src="/assets/js/vendor/docx/docx-preview.min.js"></script>

<script>
(function() {
    'use strict';

    const CONTENT_ID = <?= (int)$item->id ?>;
    const STREAM_URL = <?= json_encode($streamUrl) ?>;
    const INITIAL_LAST_PAGE = <?= (int)$initialLastPage ?>;
    const SAVED_PROGRESS_PERCENT = <?= (float)($progress ? $progress->progressPercent : 0.0) ?>;
    const SAVED_IS_COMPLETED = <?= ($progress && $progress->isCompleted()) ? 'true' : 'false' ?>;
    const SAVED_PAGES_READ = <?= json_encode($progress ? $progress->pagesRead : []) ?>;

    // DOM Elements
    const container = document.getElementById('docx-container');
    const loadingEl = document.getElementById('docx-loading');
    const loadingText = document.getElementById('docx-loading-text');
    const errorEl = document.getElementById('docx-error');
    const errorTitle = document.getElementById('docx-error-title');
    const errorDesc = document.getElementById('docx-error-desc');
    const viewportEl = document.getElementById('docx-viewport');

    const currentBlockLabel = document.getElementById('current-block-label');
    const totalBlocksLabel = document.getElementById('total-blocks-label');
    const progressPercentLabel = document.getElementById('progress-percent-label');
    const progressBarFill = document.getElementById('progress-bar-fill');
    const progressStatusBadge = document.getElementById('progress-status-badge');

    const btnPrevBlock = document.getElementById('btn-prev-block');
    const btnNextBlock = document.getElementById('btn-next-block');
    const btnScrollTop = document.getElementById('btn-scroll-top');
    const btnZoomIn = document.getElementById('zoom-in');
    const btnZoomOut = document.getElementById('zoom-out');
    const btnZoomReset = document.getElementById('zoom-reset');
    const zoomLevelLabel = document.getElementById('zoom-level');
    const toggleFullscreenBtn = document.getElementById('toggle-fullscreen');
    const fullscreenEnterIcon = document.getElementById('fullscreen-enter-icon');
    const fullscreenExitIcon = document.getElementById('fullscreen-exit-icon');
    const fullscreenText = document.getElementById('fullscreen-text');

    // State Variables
    let readBlocks = new Set(Array.isArray(SAVED_PAGES_READ) ? SAVED_PAGES_READ.map(Number) : []);
    let readingBlocks = [];
    let totalBlocks = 1;
    let currentVisibleBlock = Math.max(1, INITIAL_LAST_PAGE);
    let zoomLevel = 1.0;
    let isCompleted = SAVED_IS_COMPLETED;
    let progressPercent = SAVED_PROGRESS_PERCENT;
    let autosaveTimer = null;
    let isDirty = false;

    // Fetch and Render Document
    async function loadDocxDocument() {
        try {
            loadingText.textContent = 'Downloading protected document...';
            const response = await fetch(STREAM_URL, {
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/vnd.openxmlformats-officedocument.wordprocessingml.document, */*'
                }
            });

            if (!response.ok) {
                throw new Error('Failed to retrieve document: HTTP ' + response.status);
            }

            loadingText.textContent = 'Rendering Word document...';
            const arrayBuffer = await response.arrayBuffer();

            if (typeof docx === 'undefined' || !docx.renderAsync) {
                throw new Error('Word rendering engine is unavailable.');
            }

            await docx.renderAsync(arrayBuffer, container, null, {
                className: 'docx',
                inWrapper: true,
                ignoreWidth: false,
                ignoreHeight: false,
                breakPages: true,
                experimental: true,
                trimXmlDeclaration: true,
                useBase64URL: false
            });

            // Rendering complete: Hide loader
            loadingEl.classList.add('hidden');

            // Partition into stable reading units
            setupReadingBlocks();

        } catch (err) {
            console.error('DOCX Load Error:', err);
            loadingEl.classList.add('hidden');
            errorTitle.textContent = 'Failed to load document';
            errorDesc.textContent = err.message || 'The Word document could not be rendered online.';
            errorEl.classList.remove('hidden');
        }
    }

    // Identify or partition rendered DOM into stable reading blocks
    function setupReadingBlocks() {
        // Look for docx-preview generated sections first
        let sections = container.querySelectorAll('section.docx');

        if (sections.length > 0) {
            readingBlocks = Array.from(sections);
        } else {
            // If rendered as single continuous block, chunk child elements (headings, tables, paragraphs)
            const children = Array.from(container.children[0]?.children || container.children);
            if (children.length > 8) {
                // Group children into ~4-element chunks
                const chunkSize = Math.max(3, Math.ceil(children.length / 6));
                readingBlocks = [];
                for (let i = 0; i < children.length; i += chunkSize) {
                    const chunkWrapper = document.createElement('div');
                    chunkWrapper.className = 'docx-reading-block-wrapper';
                    const slice = children.slice(i, i + chunkSize);
                    slice.forEach(el => chunkWrapper.appendChild(el));
                    (container.children[0] || container).appendChild(chunkWrapper);
                    readingBlocks.push(chunkWrapper);
                }
            } else {
                readingBlocks = children.length > 0 ? children : [container];
            }
        }

        totalBlocks = Math.max(1, readingBlocks.length);
        totalBlocksLabel.textContent = totalBlocks;

        readingBlocks.forEach((block, idx) => {
            block.setAttribute('data-docx-block', (idx + 1).toString());
            block.classList.add('docx-reading-block');
        });

        // Set up IntersectionObserver to track reading
        initIntersectionObserver();

        // Auto-resume: Scroll to last read block if > 1
        if (INITIAL_LAST_PAGE > 1 && INITIAL_LAST_PAGE <= totalBlocks) {
            const targetBlock = readingBlocks[INITIAL_LAST_PAGE - 1];
            if (targetBlock) {
                setTimeout(() => {
                    targetBlock.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }, 300);
            }
        }

        updateBlockNavigationUI();
    }

    // IntersectionObserver for tracking read units
    function initIntersectionObserver() {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const blockIdx = parseInt(entry.target.getAttribute('data-docx-block'), 10);
                    if (blockIdx >= 1 && blockIdx <= totalBlocks) {
                        currentVisibleBlock = blockIdx;
                        currentBlockLabel.textContent = currentVisibleBlock;
                        updateBlockNavigationUI();

                        if (!readBlocks.has(blockIdx)) {
                            readBlocks.add(blockIdx);
                            isDirty = true;
                            calculateAndRenderProgress();
                            scheduleAutosave();
                        }
                    }
                }
            });
        }, {
            root: null,
            rootMargin: '0px',
            threshold: 0.25 // 25% visible considered read
        });

        readingBlocks.forEach(b => observer.observe(b));
    }

    // Calculate progress percentage and update UI
    function calculateAndRenderProgress() {
        const uniqueCount = readBlocks.size;
        const calcPercent = Math.min(100.0, Math.round((uniqueCount / totalBlocks) * 100 * 100) / 100);

        // Retain highest progress reached
        progressPercent = Math.max(progressPercent, calcPercent);

        if (progressPercent >= 90.0) {
            isCompleted = true;
        }

        progressPercentLabel.textContent = Math.round(progressPercent) + '%';
        progressBarFill.style.width = Math.min(100, Math.round(progressPercent)) + '%';

        if (isCompleted) {
            progressBarFill.classList.remove('bg-sky-500');
            progressBarFill.classList.add('bg-emerald-500');
            progressStatusBadge.classList.remove('hidden');
        }
    }

    // Schedule debounced autosave (2.5s)
    function scheduleAutosave() {
        if (autosaveTimer) {
            clearTimeout(autosaveTimer);
        }
        autosaveTimer = setTimeout(() => {
            persistProgress();
        }, 2500);
    }

    // Persist progress to server via AJAX
    async function persistProgress() {
        if (!isDirty) return;
        isDirty = false;

        const payload = {
            last_page: currentVisibleBlock,
            total_pages: totalBlocks,
            viewed_pages: Array.from(readBlocks)
        };

        try {
            const res = await fetch('/student/content/' + CONTENT_ID + '/progress', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify(payload)
            });

            if (res.ok) {
                const json = await res.json();
                if (json.success && json.data) {
                    if (json.data.is_completed) {
                        isCompleted = true;
                        progressStatusBadge.classList.remove('hidden');
                        progressBarFill.classList.remove('bg-sky-500');
                        progressBarFill.classList.add('bg-emerald-500');
                    }
                    if (typeof json.data.progress_percent === 'number') {
                        progressPercent = Math.max(progressPercent, json.data.progress_percent);
                        progressPercentLabel.textContent = Math.round(progressPercent) + '%';
                        progressBarFill.style.width = Math.min(100, Math.round(progressPercent)) + '%';
                    }
                }
            }
        } catch (err) {
            console.warn('Progress save failed:', err);
            isDirty = true; // Retry on next cycle
        }
    }

    // Beacon save on exit (beforeunload / visibilitychange)
    function sendExitBeacon() {
        if (!isDirty && readBlocks.size === 0) return;

        const payload = JSON.stringify({
            last_page: currentVisibleBlock,
            total_pages: totalBlocks,
            viewed_pages: Array.from(readBlocks)
        });

        const url = '/student/content/' + CONTENT_ID + '/progress';

        if (navigator.sendBeacon) {
            const blob = new Blob([payload], { type: 'application/json' });
            navigator.sendBeacon(url, blob);
        } else {
            fetch(url, {
                method: 'POST',
                credentials: 'same-origin',
                keepalive: true,
                headers: { 'Content-Type': 'application/json' },
                body: payload
            }).catch(() => {});
        }
        isDirty = false;
    }

    document.addEventListener('visibilitychange', function() {
        if (document.visibilityState === 'hidden') {
            sendExitBeacon();
        }
    });
    window.addEventListener('beforeunload', sendExitBeacon);

    // Navigation Controls
    function updateBlockNavigationUI() {
        btnPrevBlock.disabled = currentVisibleBlock <= 1;
        btnNextBlock.disabled = currentVisibleBlock >= totalBlocks;
    }

    btnPrevBlock.addEventListener('click', () => {
        if (currentVisibleBlock > 1) {
            const target = readingBlocks[currentVisibleBlock - 2];
            target?.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    });

    btnNextBlock.addEventListener('click', () => {
        if (currentVisibleBlock < totalBlocks) {
            const target = readingBlocks[currentVisibleBlock];
            target?.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    });

    btnScrollTop.addEventListener('click', () => {
        viewportEl.scrollTo({ top: 0, behavior: 'smooth' });
    });

    // Zoom Controls
    function applyZoom() {
        container.style.transform = `scale(${zoomLevel})`;
        container.style.width = zoomLevel > 1.0 ? `${100 / zoomLevel}%` : '100%';
        zoomLevelLabel.textContent = `${Math.round(zoomLevel * 100)}%`;
    }

    btnZoomIn.addEventListener('click', () => {
        if (zoomLevel < 1.6) {
            zoomLevel += 0.15;
            applyZoom();
        }
    });

    btnZoomOut.addEventListener('click', () => {
        if (zoomLevel > 0.6) {
            zoomLevel -= 0.15;
            applyZoom();
        }
    });

    btnZoomReset.addEventListener('click', () => {
        zoomLevel = 1.0;
        applyZoom();
    });

    // Fullscreen Controls
    toggleFullscreenBtn.addEventListener('click', () => {
        const root = document.getElementById('docx-reader-root');
        if (!document.fullscreenElement) {
            root.requestFullscreen().catch(() => {});
        } else {
            document.exitFullscreen().catch(() => {});
        }
    });

    document.addEventListener('fullscreenchange', () => {
        const isFs = !!document.fullscreenElement;
        fullscreenEnterIcon.classList.toggle('hidden', isFs);
        fullscreenExitIcon.classList.toggle('hidden', !isFs);
        fullscreenText.textContent = isFs ? 'Exit Fullscreen' : 'Fullscreen';
    });

    // Initialize
    loadDocxDocument();

})();
</script>
