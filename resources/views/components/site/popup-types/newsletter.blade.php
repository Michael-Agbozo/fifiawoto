<p class="eyebrow">{{ __('Newsletter') }}</p>
<h2 id="site-popup-heading" class="mt-2 pr-8 font-serif text-2xl text-heading sm:text-3xl">{{ __('Stay connected') }}</h2>
<p class="mt-3 text-sm leading-relaxed text-ink-500">{{ __('Get updates on our programs, events and the families we walk alongside. No spam — unsubscribe any time.') }}</p>
<div class="mt-5">
    <livewire:site.newsletter-signup source="popup" :compact="true" />
</div>
