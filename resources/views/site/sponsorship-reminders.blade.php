@php $heading = $done ? 'Reminders stopped' : 'Stop pledge reminders'; @endphp

<x-layouts::site :title="$heading" :noindex="true">
    <section class="section" aria-labelledby="reminders-heading">
        <div class="mx-auto max-w-xl px-4 text-center sm:px-6 lg:px-8">
            <p class="eyebrow">{{ __('Sponsorship') }}</p>
            <h1 id="reminders-heading" class="mt-3 font-serif text-3xl text-heading sm:text-4xl">{{ __($heading) }}</h1>

            @if ($done)
                <p role="status" class="mt-4 text-sm leading-relaxed text-ink-500">{{ __('You won\'t get any more pledge reminder emails. Your sponsorship itself hasn\'t changed. Thank you for your support.') }}</p>
            @else
                <p class="mt-4 text-sm leading-relaxed text-ink-500">{{ __('Stop the monthly reminder emails? This doesn\'t end your sponsorship.') }}</p>
                <form method="POST" action="{{ $formUrl }}" class="mt-8">
                    @csrf
                    <button type="submit" class="btn btn-primary">{{ __('Stop reminders') }}</button>
                </form>
            @endif
        </div>
    </section>
</x-layouts::site>
