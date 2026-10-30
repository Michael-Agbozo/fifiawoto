import './popup'

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
        document.querySelectorAll('.reveal,.reveal-fade,.reveal-up,.reveal-down,.reveal-left,.reveal-right,.reveal-zoom,.reveal-3d')
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

    document.querySelectorAll('.reveal,.reveal-fade,.reveal-up,.reveal-down,.reveal-left,.reveal-right,.reveal-zoom,.reveal-3d')
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

/**
 * 3D motion only runs where it reads well: a fine pointer that can hover,
 * and no reduced-motion preference. Touch screens keep the plain layout.
 */
function canUse3dMotion() {
    return window.matchMedia('(hover: hover) and (pointer: fine)').matches
        && !window.matchMedia('(prefers-reduced-motion: reduce)').matches
}

/** Cards with `.tilt` lean toward the pointer and catch a light sheen. */
function setupTilt() {
    if (!canUse3dMotion()) return

    document.querySelectorAll('.tilt:not([data-tilt-bound])').forEach((card) => {
        card.dataset.tiltBound = 'true'
        let settleTimer = null

        card.addEventListener('pointermove', (event) => {
            const rect = card.getBoundingClientRect()
            const x = (event.clientX - rect.left) / rect.width
            const y = (event.clientY - rect.top) / rect.height
            clearTimeout(settleTimer)
            card.classList.remove('is-settling')
            card.classList.add('is-tilting')
            card.style.setProperty('--tilt-x', `${((x - 0.5) * 10).toFixed(2)}deg`)
            card.style.setProperty('--tilt-y', `${((0.5 - y) * 10).toFixed(2)}deg`)
            card.style.setProperty('--sheen-x', `${(x * 100).toFixed(1)}%`)
            card.style.setProperty('--sheen-y', `${(y * 100).toFixed(1)}%`)
        })

        card.addEventListener('pointerleave', () => {
            card.classList.remove('is-tilting')
            card.classList.add('is-settling')
            settleTimer = setTimeout(() => card.classList.remove('is-settling'), 500)
        })
    })
}

/** Feature photos with `data-float` lean toward the pointer across their section. */
function setupFloat() {
    if (!canUse3dMotion()) return

    document.querySelectorAll('[data-float]:not([data-float-bound])').forEach((photo) => {
        photo.dataset.floatBound = 'true'
        const area = photo.closest('section') ?? photo.parentElement

        area.addEventListener('pointermove', (event) => {
            const rect = area.getBoundingClientRect()
            const x = (event.clientX - rect.left) / rect.width - 0.5
            const y = (event.clientY - rect.top) / rect.height - 0.5
            photo.style.setProperty('--float-x', `${(x * 12).toFixed(2)}deg`)
            photo.style.setProperty('--float-y', `${(-y * 8).toFixed(2)}deg`)
        })

        area.addEventListener('pointerleave', () => {
            photo.style.setProperty('--float-x', '0deg')
            photo.style.setProperty('--float-y', '0deg')
        })
    })
}

/**
 * Copy each admin table's column headings onto its cells as data-label so
 * the stacked phone layout in app.css can show which value is which.
 */
function labelStackedTables() {
    document.querySelectorAll('.admin-content table:not(.no-stack)').forEach((table) => {
        const headings = [...table.querySelectorAll(':scope > thead th')].map((th) => th.textContent.trim())
        if (headings.length === 0) return
        table.querySelectorAll(':scope > tbody > tr').forEach((row) => {
            [...row.children].forEach((cell, index) => {
                cell.dataset.label = cell.cellIndex === index ? (headings[index] ?? '') : ''
            })
            const lastCell = row.lastElementChild
            const lastHeading = (headings[headings.length - 1] ?? '').replace(/\u00a0/g, '').trim()
            lastCell?.classList.toggle(
                'is-row-actions',
                row.children.length > 1 && /^(actions?|)$/i.test(lastHeading),
            )
        })
    })
}

function init() {
    labelStackedTables()
    setupObservers()
    setupImageFade()
    setupTilt()
    setupFloat()
}

document.addEventListener('DOMContentLoaded', init)

// Re-run after Livewire `wire:navigate` swaps a new page in — without this
// the next page's reveal targets never get observed.
document.addEventListener('livewire:navigated', init)

// Livewire re-renders replace table rows, so relabel after every update.
document.addEventListener('livewire:init', () => {
    window.Livewire.hook('morphed', () => labelStackedTables())
})

/* ------------------------------------------------------------------
 * Theme store
 *
 * Backs the public site + admin <x-*.theme-toggle> components.
 * The initial value is read from the same `data-theme` attribute that
 * the inline script in partials/head.blade.php sets before paint, so
 * Alpine never disagrees with what's already on screen.
 * ------------------------------------------------------------------ */
document.addEventListener('alpine:init', () => {
    const root = document.documentElement

    function paint(mode) {
        root.setAttribute('data-theme', mode)
        root.classList.toggle('dark', mode === 'dark')
    }

    function applyMode(mode) {
        paint(mode)
        try {
            localStorage.setItem('theme', mode)
        } catch (e) {
            /* ignore */
        }
    }

    window.Alpine.store('theme', {
        mode: root.getAttribute('data-theme') === 'dark' ? 'dark' : 'light',

        isDark() {
            return this.mode === 'dark'
        },

        toggle() {
            this.setMode(this.mode === 'dark' ? 'light' : 'dark')
        },

        setMode(mode) {
            this.mode = mode === 'dark' ? 'dark' : 'light'
            applyMode(this.mode)
        },

        /* 'light' | 'dark' when the user picked one, 'system' when following the OS. */
        preference() {
            try {
                return localStorage.getItem('theme') || 'system'
            } catch (e) {
                return 'system'
            }
        },

        followSystem() {
            try { localStorage.removeItem('theme') } catch (e) { /* ignore */ }
            this.mode = window.matchMedia?.('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'
            paint(this.mode)
        },
    })

    /* wire:navigate can swap <html> attributes for the next page's — repaint
       from the store without touching localStorage (keeps OS-tracking intact). */
    document.addEventListener('livewire:navigated', () => paint(window.Alpine.store('theme').mode))

    /* Follow OS changes whenever the user hasn't explicitly picked a theme
       (no `theme` key) — checked at change time so switching back to
       "System" in Appearance settings starts tracking immediately. */
    window.matchMedia?.('(prefers-color-scheme: dark)').addEventListener('change', () => {
        if (window.Alpine.store('theme').preference() === 'system') {
            window.Alpine.store('theme').followSystem()
        }
    })
})
