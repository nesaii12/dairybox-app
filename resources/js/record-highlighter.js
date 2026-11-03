/**
 * DairyBox Global Record Highlighter & Smooth Zoom Animator
 * Smoothly centers and zooms any target record referenced by notification redirections.
 */

export function highlightRecordElement(element) {
    if (!element) return;

    // Smoothly scroll the element to vertical center of viewport
    element.scrollIntoView({
        behavior: 'smooth',
        block: 'center',
        inline: 'nearest'
    });

    // Remove any previous active animation class and trigger DOM reflow to restart
    element.classList.remove('record-highlight-active');
    void element.offsetWidth;
    element.classList.add('record-highlight-active');

    // Clean up class after animation sequence completes
    setTimeout(() => {
        element.classList.remove('record-highlight-active');
    }, 3500);
}

export function autoDetectAndHighlightRecord() {
    const params = new URLSearchParams(window.location.search);
    const highlightTarget = params.get('highlight') || (window.location.hash ? window.location.hash.replace('#', '') : null);

    if (!highlightTarget) return;

    // Clean the URL without reloading the page so future manual refreshes don't re-trigger
    const cleanUrl = new URL(window.location.href);
    cleanUrl.searchParams.delete('highlight');
    if (window.location.hash) {
        cleanUrl.hash = '';
    }
    window.history.replaceState({}, document.title, cleanUrl.toString());

    // Allow browser rendering, tables, and lazy DOM elements to settle
    setTimeout(() => {
        const targetClean = highlightTarget.trim();
        let el = null;

        // 1. By ID (e.g. #product-1 or #order-63)
        el = document.getElementById(targetClean);

        // 2. By data-record-id
        if (!el) {
            el = document.querySelector(`[data-record-id="${targetClean}"]`);
        }

        // 3. By data-record-number (e.g. DB-20261024-0001)
        if (!el) {
            el = document.querySelector(`[data-record-number="${targetClean}"]`);
        }

        // 4. By data-record-name (partial match)
        if (!el) {
            const lower = targetClean.toLowerCase();
            el = document.querySelector(`[data-record-name*="${lower}"]`);
        }

        // 5. If target starts with product- / order- / delivery-
        if (!el && targetClean.includes('-')) {
            const idOnly = targetClean.split('-').pop();
            el = document.getElementById(targetClean) ||
                 document.getElementById(idOnly) ||
                 document.querySelector(`[data-id="${idOnly}"]`) ||
                 document.querySelector(`[data-record-id="${idOnly}"]`);
        }

        // 6. Generic container text scanner fallback
        if (!el) {
            const potentialElements = document.querySelectorAll('.record-highlight-target, tr, .bg-white.rounded-2xl, .bg-white.rounded-3xl');
            for (const item of potentialElements) {
                if (item.innerText && item.innerText.includes(targetClean)) {
                    el = item;
                    break;
                }
            }
        }

        if (el) {
            highlightRecordElement(el);
        }
    }, 280);
}

// Auto-run on load
if (typeof document !== 'undefined') {
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', autoDetectAndHighlightRecord);
    } else {
        autoDetectAndHighlightRecord();
    }
}
