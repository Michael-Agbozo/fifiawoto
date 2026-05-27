<x-layouts::app :title="__('Dashboard')">
    <div class="grid gap-6 md:grid-cols-3">
        @foreach ([
            ['Beneficiaries', '0'],
            ['Active volunteers', '0'],
            ['Donations received', '0'],
        ] as [$label, $value])
            <div class="rounded-3xl border border-cream-300 bg-white p-6 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-brand-700">{{ $label }}</p>
                <p class="mt-3 font-serif text-3xl font-bold text-ink-900">{{ $value }}</p>
                <p class="mt-2 text-xs text-ink-500">Live metrics will appear here once the admin modules are connected.</p>
            </div>
        @endforeach
    </div>

    <div class="mt-8 rounded-3xl border border-cream-300 bg-white p-6 shadow-sm">
        <h2 class="font-serif text-xl font-bold text-ink-900">Welcome back, {{ auth()->user()->name }}.</h2>
        <p class="mt-2 text-sm text-ink-500">
            The full admin workspace is being built next. Once delivered, this dashboard will surface beneficiary case loads, recent volunteer applications, donation activity, and upcoming events.
        </p>
        <div class="mt-6 flex flex-wrap gap-3">
            <a href="{{ route('home') }}" class="inline-flex items-center justify-center rounded-2xl border-2 border-brand-900 px-5 py-2.5 text-sm font-bold text-brand-900 transition hover:bg-brand-900 hover:text-white" wire:navigate>
                View public site
            </a>
            <a href="{{ route('profile.edit') }}" class="inline-flex items-center justify-center rounded-2xl bg-gold-500 px-5 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-brand-900" wire:navigate>
                Account settings
            </a>
        </div>
    </div>
</x-layouts::app>
