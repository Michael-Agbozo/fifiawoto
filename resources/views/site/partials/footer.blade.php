<footer class="bg-cream-100 text-ink-700">
    <div class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
        <div class="grid gap-12 lg:grid-cols-5">
            <div class="lg:col-span-2">
                <img src="{{ asset('images/logos/dff-footer-logo.svg') }}" alt="Dadaa Fifiawoto Nyamadi Foundation" class="h-16 w-auto">
                <p class="mt-5 max-w-md text-sm leading-relaxed text-ink-500">
                    The Fifiawoto Foundation works to empower communities through compassion, service, and sustainable development initiatives across the United States, Ghana, Togo, and Benin.
                </p>
                <div class="mt-6 flex items-center gap-3">
                    <a href="{{ config('social.instagram') }}" target="_blank" rel="noopener" class="grid size-10 place-items-center rounded-full bg-white text-ink-700 ring-1 ring-cream-300 transition hover:bg-gold-500 hover:text-white hover:ring-gold-500" aria-label="Instagram">
                        <svg class="size-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.16c3.2 0 3.58.01 4.85.07 1.17.05 1.8.25 2.23.41.56.22.96.48 1.38.9.42.42.68.82.9 1.38.16.42.36 1.06.41 2.23.06 1.27.07 1.65.07 4.85s-.01 3.58-.07 4.85c-.05 1.17-.25 1.8-.41 2.23-.22.56-.48.96-.9 1.38-.42.42-.82.68-1.38.9-.42.16-1.06.36-2.23.41-1.27.06-1.65.07-4.85.07s-3.58-.01-4.85-.07c-1.17-.05-1.8-.25-2.23-.41-.56-.22-.96-.48-1.38-.9-.42-.42-.68-.82-.9-1.38-.16-.42-.36-1.06-.41-2.23C2.17 15.58 2.16 15.2 2.16 12s.01-3.58.07-4.85c.05-1.17.25-1.8.41-2.23.22-.56.48-.96.9-1.38.42-.42.82-.68 1.38-.9.42-.16 1.06-.36 2.23-.41C8.42 2.17 8.8 2.16 12 2.16zm0 1.92c-3.15 0-3.51.01-4.74.07-1.04.05-1.61.22-1.98.36-.5.2-.85.43-1.23.8-.37.38-.6.73-.8 1.23-.14.37-.31.94-.36 1.98-.06 1.23-.07 1.59-.07 4.74s.01 3.51.07 4.74c.05 1.04.22 1.61.36 1.98.2.5.43.85.8 1.23.38.37.73.6 1.23.8.37.14.94.31 1.98.36 1.23.06 1.59.07 4.74.07s3.51-.01 4.74-.07c1.04-.05 1.61-.22 1.98-.36.5-.2.85-.43 1.23-.8.37-.38.6-.73.8-1.23.14-.37.31-.94.36-1.98.06-1.23.07-1.59.07-4.74s-.01-3.51-.07-4.74c-.05-1.04-.22-1.61-.36-1.98-.2-.5-.43-.85-.8-1.23-.38-.37-.73-.6-1.23-.8-.37-.14-.94-.31-1.98-.36-1.23-.06-1.59-.07-4.74-.07zm0 3.27a4.65 4.65 0 1 1 0 9.3 4.65 4.65 0 0 1 0-9.3zm0 1.92a2.73 2.73 0 1 0 0 5.46 2.73 2.73 0 0 0 0-5.46zm5.9-2.13a1.09 1.09 0 1 1-2.18 0 1.09 1.09 0 0 1 2.18 0z"/></svg>
                    </a>
                    <a href="https://facebook.com" target="_blank" rel="noopener" class="grid size-10 place-items-center rounded-full bg-white text-ink-700 ring-1 ring-cream-300 transition hover:bg-gold-500 hover:text-white hover:ring-gold-500" aria-label="Facebook">
                        <svg class="size-5" fill="currentColor" viewBox="0 0 24 24"><path d="M22 12.06C22 6.5 17.52 2 12 2S2 6.5 2 12.06c0 5.02 3.66 9.18 8.44 9.94v-7.03H7.9v-2.91h2.54V9.84c0-2.52 1.49-3.92 3.78-3.92 1.1 0 2.24.2 2.24.2v2.47h-1.26c-1.24 0-1.63.78-1.63 1.57v1.88h2.78l-.45 2.91h-2.33V22c4.78-.76 8.43-4.92 8.43-9.94z"/></svg>
                    </a>
                    <a href="https://youtube.com" target="_blank" rel="noopener" class="grid size-10 place-items-center rounded-full bg-white text-ink-700 ring-1 ring-cream-300 transition hover:bg-gold-500 hover:text-white hover:ring-gold-500" aria-label="YouTube">
                        <svg class="size-5" fill="currentColor" viewBox="0 0 24 24"><path d="M23.5 6.2a3 3 0 0 0-2.12-2.12C19.5 3.5 12 3.5 12 3.5s-7.5 0-9.38.58A3 3 0 0 0 .5 6.2C0 8.08 0 12 0 12s0 3.92.5 5.8a3 3 0 0 0 2.12 2.12C4.5 20.5 12 20.5 12 20.5s7.5 0 9.38-.58a3 3 0 0 0 2.12-2.12C24 15.92 24 12 24 12s0-3.92-.5-5.8zM9.75 15.5v-7l6.5 3.5-6.5 3.5z"/></svg>
                    </a>
                </div>
            </div>

            <div>
                <h3 class="font-serif text-base font-bold text-ink-900">Quick Links</h3>
                <ul class="mt-4 space-y-2 text-sm">
                    <li><a href="{{ route('home') }}" class="text-ink-500 hover:text-gold-500" wire:navigate>Home</a></li>
                    <li><a href="{{ route('about') }}" class="text-ink-500 hover:text-gold-500" wire:navigate>About</a></li>
                    <li><a href="{{ route('events.index') }}" class="text-ink-500 hover:text-gold-500" wire:navigate>Events</a></li>
                    <li><a href="{{ route('volunteer') }}" class="text-ink-500 hover:text-gold-500" wire:navigate>Volunteer</a></li>
                    <li><a href="{{ route('testimonials') }}" class="text-ink-500 hover:text-gold-500" wire:navigate>Stories of Impact</a></li>
                    <li><a href="{{ route('media') }}" class="text-ink-500 hover:text-gold-500" wire:navigate>Media Gallery</a></li>
                    <li><a href="{{ route('contact') }}" class="text-ink-500 hover:text-gold-500" wire:navigate>Contact</a></li>
                </ul>
            </div>

            <div>
                <h3 class="font-serif text-base font-bold text-ink-900">Legal</h3>
                <ul class="mt-4 space-y-2 text-sm">
                    <li><a href="{{ route('legal.privacy') }}" class="text-ink-500 hover:text-gold-500" wire:navigate>Privacy Policy</a></li>
                    <li><a href="{{ route('legal.terms') }}" class="text-ink-500 hover:text-gold-500" wire:navigate>Terms of Service</a></li>
                    <li><a href="{{ route('legal.cookies') }}" class="text-ink-500 hover:text-gold-500" wire:navigate>Cookie Policy</a></li>
                    <li><a href="{{ route('legal.disclaimer') }}" class="text-ink-500 hover:text-gold-500" wire:navigate>Disclaimer</a></li>
                </ul>
            </div>

            <div>
                <h3 class="font-serif text-base font-bold text-ink-900">Stay Connected</h3>
                <p class="mt-4 text-sm text-ink-500">Get foundation updates straight to your inbox.</p>
                <form action="{{ route('home') }}" method="get" class="mt-4 flex gap-2">
                    <input
                        type="email"
                        name="email"
                        required
                        placeholder="you@example.com"
                        class="w-full rounded-lg border border-cream-300 bg-white px-3 py-2.5 text-sm text-ink-900 placeholder-ink-500/60 focus:border-gold-500 focus:outline-none focus:ring-1 focus:ring-gold-500"
                    >
                    <button type="submit" class="rounded-lg bg-gold-500 px-4 py-2.5 text-sm font-bold text-white transition hover:bg-brand-900">
                        Subscribe
                    </button>
                </form>
                <p class="mt-2 text-xs text-ink-500">Newsletter signup form is also available on the home, about, and contact pages.</p>
            </div>
        </div>

        <div class="mt-12 flex flex-col items-center justify-between gap-4 border-t border-cream-300 pt-6 text-xs text-ink-500 sm:flex-row">
            <p>Copyright &copy; {{ now()->year }}, The Fifiawoto Foundation</p>
            <p>United States · Ghana · Togo · Benin</p>
        </div>
    </div>
</footer>
