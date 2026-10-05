/**
 * Universal AJAX Table, Filter & Pagination Driver — Claret LMS
 * 
 * Provides:
 * - Seamless AJAX pagination with per-page limit (default 25)
 * - Real-time debounced search on keyup/input (250ms)
 * - Automatic focus & caret position preservation so user typing is never interrupted
 * - Dropdown filter updates via AJAX with no full page reloads
 * - Native form.submit() monkey-patch to intercept inline onchange="this.form.submit()"
 * - Non-destructive targeted container swaps ([data-lms-table-container])
 * - Subtle table-level loading spinner overlay
 * - Browser history pushState synchronization
 */
(function() {
    'use strict';

    // Inject spinner styles once
    const styleId = 'lms-ajax-table-styles';
    if (!document.getElementById(styleId)) {
        const style = document.createElement('style');
        style.id = styleId;
        style.textContent = `
            [data-lms-table-container], .lms-table-wrapper {
                position: relative;
            }
            .lms-table-loading-overlay {
                position: absolute;
                inset: 0;
                background: rgba(255, 255, 255, 0.75);
                backdrop-filter: blur(2px);
                -webkit-backdrop-filter: blur(2px);
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                z-index: 40;
                border-radius: inherit;
                opacity: 0;
                transition: opacity 0.2s ease-in-out;
                pointer-events: all;
                min-height: 120px;
            }
            .lms-table-loading-overlay.active {
                opacity: 1;
            }
            .lms-spinner-ring {
                width: 36px;
                height: 36px;
                border: 3px solid rgba(123, 48, 70, 0.15);
                border-top-color: #7B3046;
                border-radius: 50%;
                animation: lms-spin 0.75s cubic-bezier(0.55, 0.15, 0.45, 0.85) infinite;
            }
            @keyframes lms-spin {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }
            .lms-spinner-text {
                margin-top: 8px;
                font-size: 11px;
                font-weight: 700;
                letter-spacing: 0.03em;
                text-transform: uppercase;
                color: #7B3046;
                font-family: inherit;
            }
        `;
        document.head.appendChild(style);
    }

    let activeFetchAbortController = null;
    let debounceTimer = null;

    /**
     * Check if a form is an AJAX filter form
     */
    function isFilterForm(form) {
        if (!form) return false;
        if (form.method && form.method.toUpperCase() === 'POST') return false;
        if (form.hasAttribute('data-lms-filter') || form.hasAttribute('data-ajax-filter')) return true;

        // Auto-detect GET forms with search, filter, class_id, term_id, status, or role
        const inputs = form.querySelectorAll('input[type="text"], input[type="search"], input[name="q"], input[name="search"], select[name]');
        return inputs.length > 0 && (
            form.querySelector('input[name="q"], input[name="search"]') !== null ||
            form.closest('[data-lms-table-container]') !== null ||
            document.querySelector('[data-lms-table-container]') !== null ||
            form.querySelector('table') !== null ||
            document.querySelector('table') !== null
        );
    }

    /**
     * Helper to serialize a form into a URL string
     */
    function buildUrlFromForm(form) {
        const formData = new FormData(form);
        const params = new URLSearchParams();

        for (const [key, value] of formData.entries()) {
            if (value !== '' && value !== null && value !== undefined) {
                params.append(key, value);
            }
        }

        const action = form.getAttribute('action') || window.location.pathname;
        const queryString = params.toString();
        return action + (queryString ? '?' + queryString : '');
    }

    /**
     * Identify the primary container to swap when loading new table data
     */
    function findTargetContainer() {
        return document.querySelector('[data-lms-table-container]') ||
               document.querySelector('.lms-table-wrapper') ||
               document.getElementById('main-content');
    }

    /**
     * Show elegant spinner overlay over the active table container
     */
    function showLoading(container) {
        if (!container) return;
        
        const computedStyle = window.getComputedStyle(container);
        if (computedStyle.position === 'static') {
            container.style.position = 'relative';
        }

        let overlay = container.querySelector('.lms-table-loading-overlay');
        if (!overlay) {
            overlay = document.createElement('div');
            overlay.className = 'lms-table-loading-overlay';
            overlay.setAttribute('aria-live', 'polite');
            overlay.setAttribute('aria-busy', 'true');
            overlay.innerHTML = `
                <div class="lms-spinner-ring"></div>
                <div class="lms-spinner-text">Updating records...</div>
            `;
            container.appendChild(overlay);
        }

        requestAnimationFrame(() => {
            overlay.classList.add('active');
        });
    }

    /**
     * Hide and remove the spinner overlay
     */
    function hideLoading(container) {
        if (!container) return;
        const overlay = container.querySelector('.lms-table-loading-overlay');
        if (overlay) {
            overlay.classList.remove('active');
            setTimeout(() => {
                if (overlay && overlay.parentNode) {
                    overlay.parentNode.removeChild(overlay);
                }
            }, 180);
        }
    }

    /**
     * Capture focus state of the currently active input
     */
    function captureActiveState() {
        const el = document.activeElement;
        if (!el) return null;

        const isInput = el.tagName === 'INPUT' || el.tagName === 'TEXTAREA' || el.tagName === 'SELECT';
        if (!isInput) return null;

        return {
            id: el.id || null,
            name: el.getAttribute('name') || null,
            tagName: el.tagName,
            type: el.getAttribute('type') || null,
            value: el.value,
            selectionStart: (el.tagName === 'INPUT' || el.tagName === 'TEXTAREA') ? el.selectionStart : null,
            selectionEnd: (el.tagName === 'INPUT' || el.tagName === 'TEXTAREA') ? el.selectionEnd : null
        };
    }

    /**
     * Restore focus state
     */
    function restoreActiveState(state) {
        if (!state) return;

        let el = null;
        if (state.id) {
            el = document.getElementById(state.id);
        }
        if (!el && state.name) {
            el = document.querySelector(`${state.tagName}[name="${state.name}"]`);
        }

        if (el) {
            // Only refocus and adjust selection if the element actually lost focus
            if (document.activeElement !== el) {
                el.focus();
                if (state.selectionStart !== null && state.selectionEnd !== null && (el.tagName === 'INPUT' || el.tagName === 'TEXTAREA')) {
                    try {
                        const len = el.value.length;
                        const start = Math.min(state.selectionStart, len);
                        const end = Math.min(state.selectionEnd, len);
                        el.setSelectionRange(start, end);
                    } catch (e) {
                        // Ignore elements that don't support setSelectionRange
                    }
                }
            }
        }
    }

    /**
     * Perform the AJAX fetch and update DOM
     */
    async function fetchTableData(url, pushToHistory = true) {
        if (activeFetchAbortController) {
            activeFetchAbortController.abort();
        }
        activeFetchAbortController = new AbortController();

        const container = findTargetContainer();
        showLoading(container);
        const focusState = captureActiveState();

        try {
            const response = await fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'text/html, application/xhtml+xml',
                },
                signal: activeFetchAbortController.signal
            });

            if (!response.ok) {
                window.location.href = url;
                return;
            }

            const html = await response.text();
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');

            // 1. Prefer targeted swap of [data-lms-table-container]
            const newTableContainer = doc.querySelector('[data-lms-table-container]');
            const curTableContainer = document.querySelector('[data-lms-table-container]');

            if (newTableContainer && curTableContainer) {
                // The table and pagination are inside curTableContainer,
                // while the search input and filter form are OUTSIDE, so the input
                // is never destroyed and never loses focus!
                curTableContainer.innerHTML = newTableContainer.innerHTML;
            } else {
                // Fallback to swapping main-content
                const newContent = doc.getElementById('main-content') || doc.body;
                const currentContent = document.getElementById('main-content') || document.body;
                if (newContent && currentContent) {
                    currentContent.innerHTML = newContent.innerHTML;
                }
            }

            // Sync document title if changed
            if (doc.title) {
                document.title = doc.title;
            }

            // Sync URL history state
            if (pushToHistory && window.location.href !== url) {
                window.history.pushState({ lmsAjax: true, url: url }, '', url);
            }

            // Re-render UI icons and interactive handlers
            if (window.lucide && typeof window.lucide.createIcons === 'function') {
                window.lucide.createIcons();
            }
            if (typeof window.initUniversalPasswordToggles === 'function') {
                window.initUniversalPasswordToggles();
            }

            // Restore focus and cursor position smoothly
            restoreActiveState(focusState);

        } catch (err) {
            if (err.name !== 'AbortError') {
                console.error('[LMS-AJAX-TABLE] Fetch failed, navigating normally:', err);
                window.location.href = url;
            }
        } finally {
            hideLoading(container);
        }
    }

    /**
     * Intercept native form.submit() on HTMLFormElement
     * This stops inline onchange="this.form.submit()" from triggering full browser page reloads!
     */
    const originalFormSubmit = HTMLFormElement.prototype.submit;
    HTMLFormElement.prototype.submit = function() {
        if (isFilterForm(this)) {
            const url = buildUrlFromForm(this);
            fetchTableData(url);
            return;
        }
        return originalFormSubmit.call(this);
    };

    /**
     * Global Event Delegations
     */

    // 1. Real-time Search Filter (Debounced 250ms on input & keyup)
    function handleSearchInput(e) {
        const target = e.target;
        if (!target || !target.matches('input[type="text"], input[type="search"], input[name="search"], input[name="q"]')) return;
        
        const form = target.closest('form');
        if (!isFilterForm(form)) return;

        // Reset page to 1 on new search
        const pageInput = form.querySelector('input[name="page"]');
        if (pageInput) {
            pageInput.value = '1';
        }

        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => {
            const url = buildUrlFromForm(form);
            fetchTableData(url);
        }, 250);
    }

    document.addEventListener('input', handleSearchInput);
    document.addEventListener('keyup', function(e) {
        if (e.key === 'Backspace' || e.key === 'Delete') {
            handleSearchInput(e);
        }
    });

    // 2. Dropdown Filter change (Immediate AJAX trigger, no reload)
    document.addEventListener('change', function(e) {
        const target = e.target;
        if (!target.matches('select, input[type="checkbox"], input[type="radio"]')) return;
        
        const form = target.closest('form');
        if (!isFilterForm(form)) return;

        // Reset page to 1 on filter adjustment
        const pageInput = form.querySelector('input[name="page"]');
        if (pageInput) {
            pageInput.value = '1';
        }

        const url = buildUrlFromForm(form);
        fetchTableData(url);
    });

    // 3. Form Submit (Enter key or Submit button click)
    document.addEventListener('submit', function(e) {
        const form = e.target;
        if (!isFilterForm(form)) return;

        e.preventDefault();
        clearTimeout(debounceTimer);
        const url = buildUrlFromForm(form);
        fetchTableData(url);
    });

    // 4. Pagination Click Interceptor
    document.addEventListener('click', function(e) {
        const link = e.target.closest('a');
        if (!link) return;

        const isPagination = link.closest('nav[aria-label="Pagination Navigation"]') ||
                             link.closest('.lms-pagination') ||
                             link.hasAttribute('data-lms-page');
        
        const href = link.getAttribute('href');
        if (!href || href === '#' || href.startsWith('javascript:')) return;

        const hasPageParam = href.includes('page=');

        if (isPagination || (hasPageParam && !link.hasAttribute('target') && !link.closest('#sidebar'))) {
            e.preventDefault();
            fetchTableData(href);
        }
    });

    // 5. Browser History Back / Forward navigation
    window.addEventListener('popstate', function(e) {
        fetchTableData(window.location.href, false);
    });

    // Expose utility on window for programmatic calls if needed
    window.LMS = window.LMS || {};
    window.LMS.fetchTableData = fetchTableData;

})();
