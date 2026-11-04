<p class="eyebrow">{{ __('Give') }}</p>
<h2 id="site-popup-heading" class="mt-2 pr-8 font-serif text-2xl text-heading sm:text-3xl">{{ __('Your gift keeps a legacy alive') }}</h2>
<p class="mt-3 text-sm leading-relaxed text-ink-500">{{ __('Every contribution funds school fees, medical care and support for widows and families across Ghana, Togo and Benin.') }}</p>
<div class="mt-6 flex flex-wrap gap-3">
    <a href="{{ route('contact', ['subject' => 'donation']) }}" @click="dismiss()" class="inline-flex items-center gap-2 rounded-2xl bg-gold-500 px-6 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-brand-900" wire:navigate>
        {{ __('Coordinate a gift') }} <span aria-hidden="true">→</span>
    </a>
    <a href="{{ route('donate') }}" @click="dismiss()" class="btn btn-secondary" wire:navigate>
        {{ __('Ways to give') }}
    </a>
</div>
