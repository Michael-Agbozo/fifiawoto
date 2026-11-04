/**
 * Site popup controller — backs <x-site.popup>.
 *
 * Config comes from Admin → Popup settings. `key` is derived from that config,
 * so editing the popup re-shows it to visitors who dismissed the old version.
 *
 *   frequency: 'always' | 'session' (sessionStorage) | 'once' (localStorage)
 *   trigger:   'delay' (ms) | 'scroll' (percent) | 'exit_intent'
 */
function storageFor(frequency) {
    try {
        if (frequency === 'once') return window.localStorage
        if (frequency === 'session') return window.sessionStorage
    } catch (e) {
        /* storage blocked (private mode) — behave like 'always' */
    }
    return null
}

document.addEventListener('alpine:init', () => {
    window.Alpine.data('sitePopup', ({ key, frequency, trigger, delay, scroll }) => ({
        open: false,
        cleanup: null,

        init() {
            const storage = storageFor(frequency)
            try {
                if (storage?.getItem(key)) return
            } catch (e) {
                /* ignore */
            }

            if (trigger === 'scroll') {
                const onScroll = () => {
                    const scrollable = document.documentElement.scrollHeight - window.innerHeight
                    const percent = scrollable > 0 ? (window.scrollY / scrollable) * 100 : 100
                    if (percent >= scroll) this.show()
                }
                window.addEventListener('scroll', onScroll, { passive: true })
                this.cleanup = () => window.removeEventListener('scroll', onScroll)
                onScroll()
            } else if (trigger === 'exit_intent') {
                const onLeave = (event) => {
                    if (event.clientY <= 0 && !event.relatedTarget) this.show()
                }
                document.documentElement.addEventListener('mouseleave', onLeave)
                this.cleanup = () => document.documentElement.removeEventListener('mouseleave', onLeave)
            } else {
                const timer = setTimeout(() => this.show(), delay)
                this.cleanup = () => clearTimeout(timer)
            }
        },

        show() {
            this.cleanup?.()
            this.cleanup = null
            this.open = true
        },

        dismiss() {
            this.open = false
            try {
                storageFor(frequency)?.setItem(key, '1')
            } catch (e) {
                /* ignore */
            }
        },

        destroy() {
            this.cleanup?.()
            document.body.classList.remove('overflow-hidden')
        },
    }))
})
