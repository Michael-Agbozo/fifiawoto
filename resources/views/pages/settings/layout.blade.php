@php
    $settingsNav = collect([
        ['label' => __('Profile'),          'route' => 'profile.edit'],
        ['label' => __('Password'),         'route' => 'user-password.edit'],
        Laravel\Fortify\Features::canManageTwoFactorAuthentication()
            ? ['label' => __('Two-factor auth'), 'route' => 'two-factor.show']
            : null,
        ['label' => __('Appearance'),       'route' => 'appearance.edit'],
    ])->filter()->values();
@endphp

<div class="grid gap-10 md:grid-cols-[220px_1fr] md:gap-12">
    <aside aria-label="{{ __('Settings') }}">
        <nav class="space-y-1">
            @foreach ($settingsNav as $item)
                @php $isCurrent = request()->routeIs($item['route']); @endphp
                <a
                    href="{{ route($item['route']) }}"
                    @class([
                        'block rounded-lg px-3 py-2 text-sm font-medium transition-colors',
                        'bg-cream-100 text-gold-500' => $isCurrent,
                        'text-ink-700 hover:bg-cream-100 hover:text-gold-500' => ! $isCurrent,
                    ])
                    wire:navigate
                >
                    {{ $item['label'] }}
                </a>
            @endforeach
        </nav>
    </aside>

    <div class="border-cream-300 max-md:border-t max-md:pt-6">
        @if (filled($heading ?? null))
            <h2 class="font-serif text-2xl font-bold text-ink-900">{{ $heading }}</h2>
        @endif
        @if (filled($subheading ?? null))
            <p class="mt-1 text-sm text-ink-500">{{ $subheading }}</p>
        @endif

        <div class="mt-6 w-full max-w-lg">
            {{ $slot }}
        </div>
    </div>
</div>
