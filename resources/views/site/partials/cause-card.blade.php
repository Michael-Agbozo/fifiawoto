@php
    $raised = (int) ($cause->raised_cents ?? 0);
    $goal = (int) $cause->goal_cents;
    $percent = min(100, (int) round($raised / $goal * 100));
    $fallbacks = ['images/foundation/school-donation.jpg', 'images/foundation/outreach-1.jpg', 'images/foundation/outreach-4.webp'];
    $causeImage = \App\Support\MediaUrl::for($cause->hero_image_path ?: $fallbacks[$index % count($fallbacks)]);
@endphp
<article class="tilt group flex h-full flex-col overflow-hidden rounded-2xl border border-cream-300 bg-surface shadow-sm hover:shadow-lg">
    <div class="relative">
        <div class="aspect-[4/3] overflow-hidden">
            <img src="{{ $causeImage }}" alt="{{ $cause->title }}" class="h-full w-full object-cover transition duration-500 group-hover:scale-105" loading="lazy">
        </div>
        @if ($cause->country)
            <span class="absolute -bottom-3 left-6 rounded-md bg-brand-900 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-white">{{ $cause->country }}</span>
        @endif
    </div>
    <div class="flex flex-1 flex-col p-6 pt-7">
        <h3 class="font-serif text-2xl font-bold leading-snug text-heading">
            <a href="{{ route('events.show', $cause) }}" class="hover:text-gold-500" wire:navigate>{{ $cause->title }}</a>
        </h3>
        <p class="mt-3 line-clamp-2 text-sm leading-relaxed text-ink-500">{{ \Illuminate\Support\Str::limit(strip_tags((string) $cause->description), 120) }}</p>

        <div class="mt-auto pt-6">
            <div class="relative h-1.5 rounded-full bg-cream-300" role="progressbar" aria-valuenow="{{ $percent }}" aria-valuemin="0" aria-valuemax="100" aria-label="{{ $percent }}% of goal raised">
                <div class="h-full rounded-full bg-gold-500" style="width: {{ $percent }}%"></div>
            </div>
            <div class="mt-3 flex justify-between gap-3 text-sm text-ink-500">
                <span>{{ __('Raised:') }} <b class="font-semibold text-heading">{{ \App\Support\Money::fromCents($raised) }}</b></span>
                <span>{{ __(':v1% of', ['v1' => $percent]) }} <b class="font-semibold text-heading">{{ \App\Support\Money::fromCents($goal) }}</b></span>
            </div>
            <a href="{{ route('events.show', $cause) }}" class="btn btn-primary mt-5 w-full" wire:navigate>{{ __('View Cause') }}</a>
        </div>
    </div>
</article>
