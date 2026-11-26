import './offline-pos';

if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js').catch(() => {});
    });
}

/**
 * SiLeuit Silky Inertial Smooth Scroll (Zero-Dependency, 60/120/144Hz Adaptive)
 * Memberikan sensasi gulir elegan berinersia lembut di seluruh halaman tanpa membebani CPU saat diam.
 */
(function initSilkyScroll() {
    if (window.__sileuitSmoothScrollInit) return;
    window.__sileuitSmoothScrollInit = true;

    const prefersReduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const isCoarsePointer = window.matchMedia('(pointer: coarse)').matches;
    if (prefersReduced || isCoarsePointer) {
        return;
    }

    let currentY = window.scrollY;
    let targetY = window.scrollY;
    let rafId = null;
    let lastTime = 0;

    const getMaxScroll = () => Math.max(0, document.documentElement.scrollHeight - window.innerHeight);

    const clamp = (val, min, max) => Math.min(Math.max(val, min), max);

    function canElementScroll(el, deltaY) {
        while (el && el !== document.body && el !== document.documentElement) {
            if (el.tagName === 'TEXTAREA' || el.tagName === 'SELECT' || el.hasAttribute('data-native-scroll')) {
                return true;
            }
            const style = window.getComputedStyle(el);
            const overflowY = style.overflowY;
            if ((overflowY === 'auto' || overflowY === 'scroll' || overflowY === 'overlay') && el.scrollHeight > el.clientHeight + 1) {
                const atTop = el.scrollTop <= 0 && deltaY < 0;
                const atBottom = el.scrollTop + el.clientHeight >= el.scrollHeight - 1 && deltaY > 0;
                if (!atTop && !atBottom) {
                    return true;
                }
            }
            el = el.parentElement;
        }
        return false;
    }

    function step(timestamp) {
        if (!lastTime) lastTime = timestamp;
        const dt = Math.min((timestamp - lastTime) / 1000, 0.05);
        lastTime = timestamp;

        const maxScroll = getMaxScroll();
        targetY = clamp(targetY, 0, maxScroll);

        // Frame-rate independent exponential smoothing (subtle elegant delay ~9.0 damping)
        const factor = 1 - Math.exp(-9.0 * dt);
        currentY += (targetY - currentY) * factor;

        if (Math.abs(targetY - currentY) <= 0.45) {
            window.scrollTo(0, targetY);
            currentY = targetY;
            rafId = null;
            lastTime = 0;
            return;
        }

        window.scrollTo(0, currentY);
        rafId = window.requestAnimationFrame(step);
    }

    function startAnimation() {
        if (!rafId) {
            currentY = window.scrollY;
            lastTime = 0;
            rafId = window.requestAnimationFrame(step);
        }
    }

    window.addEventListener('wheel', (e) => {
        if (e.ctrlKey || e.metaKey || e.defaultPrevented) return;
        if (document.body.style.overflow === 'hidden') return;
        if (canElementScroll(e.target, e.deltaY)) return;

        e.preventDefault();

        let delta = e.deltaY;
        if (e.deltaMode === 1) delta *= 36;
        else if (e.deltaMode === 2) delta *= window.innerHeight * 0.85;

        if (!rafId) {
            currentY = window.scrollY;
            targetY = window.scrollY;
        }
        targetY = clamp(targetY + delta, 0, getMaxScroll());
        startAnimation();
    }, { passive: false });

    // Sync target when scrollbar is dragged or keyboard/native scroll occurs outside rAF
    window.addEventListener('scroll', () => {
        if (!rafId) {
            currentY = window.scrollY;
            targetY = window.scrollY;
        }
    }, { passive: true });

    // Handle smooth anchor jumps (#section)
    document.addEventListener('click', (e) => {
        const anchor = e.target.closest('a[href^="#"]');
        if (!anchor) return;
        const id = anchor.getAttribute('href');
        if (!id || id === '#') return;
        const targetEl = document.querySelector(id);
        if (!targetEl) return;

        e.preventDefault();
        const rect = targetEl.getBoundingClientRect();
        currentY = window.scrollY;
        targetY = clamp(window.scrollY + rect.top - 72, 0, getMaxScroll());
        startAnimation();
    });

    // Reset state cleanly across Livewire / SPA navigations
    document.addEventListener('livewire:navigated', () => {
        if (rafId) {
            window.cancelAnimationFrame(rafId);
            rafId = null;
        }
        currentY = window.scrollY;
        targetY = window.scrollY;
        if (window.AOS) {
            window.AOS.refreshHard();
        }
    });
})();

/**
 * Initialize AOS cleanly with generous 950ms reveal duration & offset so scroll reveals feel tactile.
 */
function initAosSafely() {
    if (window.AOS) {
        window.AOS.init({
            duration: 950,
            easing: 'ease-out-cubic',
            once: false,
            mirror: false,
            offset: 120,
            throttleDelay: 16,
            debounceDelay: 50,
            anchorPlacement: 'top-bottom',
        });
    }
}

window.addEventListener('DOMContentLoaded', initAosSafely);
window.addEventListener('load', () => {
    if (window.AOS) {
        window.AOS.refresh();
    } else if (document.querySelector('[data-aos]')) {
        document.documentElement.classList.add('aos-fallback');
    }
});
