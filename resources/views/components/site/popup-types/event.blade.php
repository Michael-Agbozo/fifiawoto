@php($event = \App\Support\SitePopup::event($config))

<p class="text-xs font-semibold uppercase tracking-[0.3em] text-gold-500">Upcoming event</p>
<h2 id="site-popup-heading" class="mt-2 pr-8 font-serif text-2xl text-heading sm:text-3xl">{{ $event->title }}</h2>
<dl class="mt-4 space-y-1.5 text-sm text-ink-700">
    @if ($event->starts_at)
        <div class="flex gap-2"><dt class="font-semibold text-heading">When</dt><dd>{{ $event->starts_at->format('l, F j, Y · g:i A') }}</dd></div>
    @endif
    @if ($event->location)
        <div class="flex gap-2"><dt class="font-semibold text-heading">Where</dt><dd>{{ $event->location }}</dd></div>
    @endif
</dl>
<div class="mt-6">
    <a href="{{ route('events.show', $event) }}" @click="dismiss()" class="inline-flex items-center gap-2 rounded-2xl bg-gold-500 px-6 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-brand-900" wire:navigate>
        Learn more <span aria-hidden="true">→</span>
    </a>
</div>
