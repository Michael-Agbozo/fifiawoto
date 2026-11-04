<p class="eyebrow">{{ __('Announcement') }}</p>
<h2 id="site-popup-heading" class="mt-2 pr-8 font-serif text-2xl text-heading sm:text-3xl">{{ __($config['announcement_heading']) }}</h2>
@if (filled($config['announcement_body']))
    <p class="mt-3 whitespace-pre-line text-sm leading-relaxed text-ink-500">{{ __($config['announcement_body']) }}</p>
@endif
@if (filled($config['announcement_cta_label']) && filled($config['announcement_cta_url']))
    <div class="mt-6">
        <a href="{{ $config['announcement_cta_url'] }}" @click="dismiss()" class="btn btn-primary">
            {{ __($config['announcement_cta_label']) }} <span aria-hidden="true">→</span>
        </a>
    </div>
@endif
