<p class="text-xs font-semibold uppercase tracking-[0.3em] text-gold-500">Announcement</p>
<h2 id="site-popup-heading" class="mt-2 pr-8 font-serif text-2xl text-heading sm:text-3xl">{{ $config['announcement_heading'] }}</h2>
@if (filled($config['announcement_body']))
    <p class="mt-3 whitespace-pre-line text-sm leading-relaxed text-ink-500">{{ $config['announcement_body'] }}</p>
@endif
@if (filled($config['announcement_cta_label']) && filled($config['announcement_cta_url']))
    <div class="mt-6">
        <a href="{{ $config['announcement_cta_url'] }}" @click="dismiss()" class="inline-flex items-center gap-2 rounded-2xl bg-gold-500 px-6 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-brand-900">
            {{ $config['announcement_cta_label'] }} <span aria-hidden="true">→</span>
        </a>
    </div>
@endif
