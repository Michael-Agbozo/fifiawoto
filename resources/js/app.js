/**
 * Site-wide motion helpers.
 *
 *  - [data-counter] elements with a numeric target animate up from 0 to
 *    their target value when scrolled into view. Optional attributes:
 *      data-prefix="$"   prepended to the displayed value (e.g. "$1,200")
 *      data-suffix="+"   appended ("250+")
 *      data-duration="1800"   milliseconds (default 1500)
 *
 *  - img.img-fade fades in once the underlying image has decoded. Pair
 *    with loading="lazy" for a graceful reveal of below-the-fold images.
 *
 * No Alpine plugin or external dep — uses IntersectionObserver, which is
 * baseline-supported and degrades to "value appears instantly" on older
 * browsers.
 */

const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches

function formatNumber(n) {
    return Math.round(n).toLocaleString()
}

function animateCounter(el) {
    if (el.dataset.counted === 'true') return
    el.dataset.counted = 'true'

    const target = Number(el.dataset.counter ?? el.textContent.replace(/[^\d.-]/g, '')) || 0
    const prefix = el.dataset.prefix ?? ''
    const suffix = el.dataset.suffix ?? ''
    const duration = Number(el.dataset.duration ?? 2800)

    if (prefersReducedMotion || target <= 0) {
        el.textContent = `${prefix}${formatNumber(target)}${suffix}`
        return
    }

    const start = performance.now()
    function frame(now) {
        const elapsed = now - start
        const progress = Math.min(1, elapsed / duration)
        // Ease-out cubic — fast at the start, soft landing on the target.
        const eased = 1 - Math.pow(1 - progress, 3)
        const value = target * eased
        el.textContent = `${prefix}${formatNumber(value)}${suffix}`
        if (progress < 1) requestAnimationFrame(frame)
    }
    requestAnimationFrame(frame)
}

function markRevealVisible(el) {
    el.classList.add('is-visible')
}

function setupObservers() {
    if (typeof IntersectionObserver === 'undefined') {
        // Bail out gracefully on ancient browsers — show everything immediately.
        document.querySelectorAll('.reveal,.reveal-fade,.reveal-up,.reveal-down,.reveal-left,.reveal-right,.reveal-zoom')
            .forEach((el) => el.classList.add('is-visible'))
        document.querySelectorAll('[data-counter]:not([data-counted="true"])')
            .forEach((el) => {
                const target = Number(el.dataset.counter ?? el.textContent.replace(/[^\d.-]/g, '')) || 0
                el.textContent = `${el.dataset.prefix ?? ''}${formatNumber(target)}${el.dataset.suffix ?? ''}`
                el.dataset.counted = 'true'
            })
        return
    }

    const revealObserver = new IntersectionObserver(
        (entries) => {
            for (const entry of entries) {
                if (entry.isIntersecting) {
                    markRevealVisible(entry.target)
                    revealObserver.unobserve(entry.target)
                }
            }
        },
        { rootMargin: '0px 0px -10% 0px', threshold: 0.05 },
    )

    const counterObserver = new IntersectionObserver(
        (entries) => {
            for (const entry of entries) {
                if (entry.isIntersecting) {
                    animateCounter(entry.target)
                    counterObserver.unobserve(entry.target)
                }
            }
        },
        { threshold: 0.4 },
    )

    document.querySelectorAll('.reveal,.reveal-fade,.reveal-up,.reveal-down,.reveal-left,.reveal-right,.reveal-zoom')
        .forEach((el) => {
            if (!el.classList.contains('is-visible')) revealObserver.observe(el)
        })

    document.querySelectorAll('[data-counter]:not([data-counted="true"])')
        .forEach((el) => counterObserver.observe(el))
}

function setupImageFade() {
    document.querySelectorAll('img.img-fade:not([data-loaded="true"])').forEach((img) => {
        if (img.complete && img.naturalWidth > 0) {
            img.dataset.loaded = 'true'
        } else {
            img.addEventListener('load', () => { img.dataset.loaded = 'true' }, { once: true })
            img.addEventListener('error', () => { img.dataset.loaded = 'true' }, { once: true })
        }
    })
}

function init() {
    setupObservers()
    setupImageFade()
}

document.addEventListener('DOMContentLoaded', init)

// Re-run after Livewire `wire:navigate` swaps a new page in — without this
// the next page's reveal targets never get observed.
document.addEventListener('livewire:navigated', init)
