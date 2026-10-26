<p class="text-xs font-semibold uppercase tracking-[0.3em] text-gold-500">Give</p>
<h2 id="site-popup-heading" class="mt-2 pr-8 font-serif text-2xl text-brand-900 sm:text-3xl">Your gift keeps a legacy alive</h2>
<p class="mt-3 text-sm leading-relaxed text-ink-500">Every contribution funds school fees, medical care and support for widows and families across Ghana, Togo and Benin.</p>
<div class="mt-6 flex flex-wrap gap-3">
    <a href="{{ route('contact', ['subject' => 'donation']) }}" @click="dismiss()" class="inline-flex items-center gap-2 rounded-2xl bg-gold-500 px-6 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-brand-900" wire:navigate>
        Coordinate a gift <span aria-hidden="true">→</span>
    </a>
    <a href="{{ route('donate') }}" @click="dismiss()" class="inline-flex items-center rounded-2xl border-2 border-cream-300 px-6 py-3 text-sm font-semibold text-ink-700 transition hover:border-brand-900 hover:text-brand-900" wire:navigate>
        Ways to give
    </a>
</div>
