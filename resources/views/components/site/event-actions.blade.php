@props([
    'event' => null,           // optional Event model — used to title the modals
    'variant' => 'light',      // 'light' (default) or 'dark' (for use on dark hero strips)
    'size' => 'md',            // 'sm' for compact list rows, 'md' for hero
    'stack' => false,          // stack vertically (used inside aside columns)
])

@php
    $eventTitle = $event?->title;
    $donateLabel = $event ? 'Donate to this event' : 'Donate';
    $volunteerLabel = $event ? 'Volunteer for this event' : 'Volunteer';
    $padding = $size === 'sm' ? 'px-4 py-1.5 text-xs' : 'px-5 py-2.5 text-sm';
    $primaryClasses = 'rounded-full bg-gold-500 font-bold text-white transition hover:bg-brand-900 '.$padding;

    $secondaryClasses = match ($variant) {
        'dark' => 'rounded-full border border-cream-200/40 font-medium text-paper transition hover:bg-cream-50 hover:text-heading '.$padding,
        default => 'rounded-full border-2 border-heading font-semibold text-heading transition hover:bg-brand-900 hover:text-white '.$padding,
    };

    $wrapClasses = $stack
        ? 'flex flex-col gap-3'
        : 'flex flex-wrap items-center gap-3';
@endphp

<div x-data="{ donateOpen: false, volunteerOpen: false }" class="{{ $wrapClasses }}">
    <button type="button" @click="donateOpen = true" class="{{ $primaryClasses }} {{ $stack ? 'w-full text-center' : '' }}">
        {{ $event ? 'Donate to this event' : 'Donate' }}
    </button>
    <button type="button" @click="volunteerOpen = true" class="{{ $secondaryClasses }} {{ $stack ? 'w-full text-center' : '' }}">
        {{ $event ? 'Volunteer for this event' : 'Volunteer' }}
    </button>

    {{-- DONATE MODAL --}}
    <x-site.modal
        name="donate"
        :title="$eventTitle ? 'Support · '.$eventTitle : 'Support the Foundation'"
        subtitle="Every gift extends our reach across the four countries we serve."
        size="md"
    >
        <div class="space-y-4 text-sm leading-relaxed text-ink-700">
            <p>
                Online giving will be enabled once a payment processor is in place. In the meantime, our team will coordinate the gift with you directly — whether it's mobile money, bank transfer, cheque, or cash.
            </p>
            @if ($event && $event->goal_cents)
                @php
                    $raised = (int) ($event->raised_cents ?? $event->raisedCents());
                    $goal = (int) $event->goal_cents;
                    $percent = (int) min(100, round($raised / $goal * 100));
                @endphp
                <div class="rounded-2xl bg-cream-50 p-4">
                    <p class="eyebrow">Progress so far</p>
                    <p class="mt-1 font-serif text-2xl font-bold text-heading">{{ \App\Support\Money::fromCents((int) $raised) }} <span class="font-sans text-sm font-normal text-ink-500">of {{ \App\Support\Money::fromCents((int) $goal) }} goal</span></p>
                    <div class="mt-3 h-2 w-full overflow-hidden rounded-full bg-brand-100">
                        <div class="h-full rounded-full bg-gold-500" style="width: {{ $percent }}%"></div>
                    </div>
                </div>
            @endif
            <p class="text-ink-500">
                Click below to start a short note to the foundation. We'll respond with the next steps and a confirmation receipt.
            </p>
        </div>
        <div class="mt-6 flex flex-wrap gap-3">
            <a href="{{ route('contact') }}{{ $event ? '?subject=donation&event='.$event->slug : '?subject=donation' }}" class="inline-flex items-center justify-center rounded-2xl bg-gold-500 px-6 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-brand-900" wire:navigate>
                Coordinate a gift →
            </a>
            <button type="button" @click="donateOpen = false" class="btn btn-secondary">
                Not now
            </button>
        </div>
    </x-site.modal>

    {{-- VOLUNTEER MODAL --}}
    <x-site.modal
        name="volunteer"
        :title="$eventTitle ? 'Volunteer for '.$eventTitle : 'Volunteer with the Foundation'"
        subtitle="Tell us about yourself and the volunteer coordinator will be in touch."
        size="xl"
    >
        <livewire:site.volunteer-application-form
            :compact="true"
            :context-event-title="$eventTitle"
            :key="'vol-modal-'.($event?->id ?? 'generic')"
        />
    </x-site.modal>
</div>
