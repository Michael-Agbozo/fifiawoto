@props([
    'title' => null,
    'breadcrumbs' => ['Pages', 'Dashboard'],
])

@php
    $user = auth()->user();
    $role = $user?->role;

    $navGroups = [
        [
            'heading' => 'Overview',
            'items' => [
                ['label' => 'Dashboard',                'route' => 'admin.dashboard',                       'icon' => 'home',      'show' => true],
                ['label' => 'Email inbox',              'route' => 'admin.inbox.index',                     'icon' => 'inbox',     'show' => $user?->canDo('inbox', 'view') ?? false],
            ],
        ],
        [
            'heading' => 'People',
            'items' => [
                ['label' => 'Beneficiaries',            'route' => 'admin.beneficiaries.index',             'icon' => 'users',     'show' => $user?->canDo('beneficiaries', 'view') ?? false],
                ['label' => 'Beneficiary applications', 'route' => 'admin.beneficiary-applications.index',  'icon' => 'inbox',     'show' => $user?->canDo('beneficiary_applications', 'view') ?? false],
                ['label' => 'Volunteers',               'route' => 'admin.volunteers.index',                'icon' => 'hand',      'show' => $user?->canDo('volunteers', 'view') ?? false],
            ],
        ],
        [
            'heading' => 'Programmes',
            'items' => [
                ['label' => 'Events',                   'route' => 'admin.events.index',                    'icon' => 'calendar',  'show' => $user?->canDo('events', 'view') ?? false],
                ['label' => 'Donations',                'route' => 'admin.donations.index',                 'icon' => 'heart',     'show' => $user?->canDo('donations', 'view') ?? false],
            ],
        ],
        [
            'heading' => 'Media & Website',
            'items' => [
                ['label' => 'Media gallery',            'route' => 'admin.media.index',                     'icon' => 'image',     'show' => $user?->canDo('media', 'view') ?? false],
                ['label' => 'Instagram sync',           'route' => 'admin.instagram.index',                 'icon' => 'instagram', 'show' => $user?->canDo('instagram', 'view') ?? false],
                ['label' => 'Testimonials',             'route' => 'admin.testimonials.index',              'icon' => 'quote',     'show' => $user?->canDo('testimonials', 'view') ?? false],
                ['label' => 'Leadership team',          'route' => 'admin.leaders.index',                   'icon' => 'users',     'show' => $user?->canDo('leaders', 'view') ?? false],
                ['label' => 'Newsletter subscribers',   'route' => 'admin.newsletter.index',                'icon' => 'sparkles',  'show' => $user?->canDo('newsletter', 'view') ?? false],
            ],
        ],
        [
            'heading' => 'Operations',
            'items' => [
                ['label' => 'User management',          'route' => 'admin.users.index',                     'icon' => 'shield',    'show' => $user?->canDo('users', 'view') ?? false],
                ['label' => 'System logs',              'route' => 'admin.system-logs.index',               'icon' => 'shield',    'show' => $user?->canDo('system_logs', 'view') ?? false],
            ],
        ],
        [
            'heading' => 'Profile',
            'items' => [
                ['label' => 'Settings',                 'route' => 'profile.edit',                          'icon' => 'cog',       'show' => true],
            ],
        ],
    ];

    $icons = [
        'home'      => 'M2.25 12 12 2.25 21.75 12M4.5 9.75v10.125a1.125 1.125 0 0 0 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125a1.125 1.125 0 0 0 1.125-1.125V9.75',
        'users'     => 'M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z',
        'inbox'     => 'M2.25 13.5h3.86a2.25 2.25 0 0 1 2.012 1.244l.256.512a2.25 2.25 0 0 0 2.013 1.244h3.218a2.25 2.25 0 0 0 2.013-1.244l.256-.512a2.25 2.25 0 0 1 2.013-1.244h3.859m-19.5.338V18a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18v-4.162c0-.224-.034-.447-.1-.661L19.24 5.338a2.25 2.25 0 0 0-2.15-1.588H6.911a2.25 2.25 0 0 0-2.15 1.588L2.35 13.177a2.25 2.25 0 0 0-.1.661Z',
        'hand'      => 'M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 5.25h.008v.008H12v-.008Z',
        'quote'     => 'M9 17.25v1.007a3 3 0 0 1-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0 1 15 18.257V17.25m6-12V15a2.25 2.25 0 0 1-2.25 2.25H5.25A2.25 2.25 0 0 1 3 15V5.25m18 0A2.25 2.25 0 0 0 18.75 3H5.25A2.25 2.25 0 0 0 3 5.25m18 0V12a2.25 2.25 0 0 1-2.25 2.25H5.25A2.25 2.25 0 0 1 3 12V5.25',
        'calendar'  => 'M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5',
        'heart'     => 'M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z',
        'image'     => 'm2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z',
        'instagram' => 'M17.5 6.5h.01M7 2h10a5 5 0 0 1 5 5v10a5 5 0 0 1-5 5H7a5 5 0 0 1-5-5V7a5 5 0 0 1 5-5Zm5 5a5 5 0 1 1 0 10 5 5 0 0 1 0-10Z',
        'chart'     => 'M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z',
        'shield'    => 'M9 12.75 11.25 15 15 9.75M21 12c0 4.97-3.582 9-9 9s-9-4.03-9-9 3.582-9 9-9c1.052 0 2.062.18 3 .512',
        'cog'       => 'M10.343 3.94c.09-.542.56-.94 1.11-.94h1.093c.55 0 1.02.398 1.11.94l.149.894c.07.424.384.764.78.93.398.164.855.142 1.205-.108l.737-.527a1.125 1.125 0 0 1 1.45.12l.773.774c.39.389.44 1.002.12 1.45l-.527.737c-.25.35-.272.806-.107 1.204.165.397.505.71.93.78l.893.15c.543.09.94.56.94 1.109v1.094c0 .55-.397 1.02-.94 1.11l-.893.149c-.425.07-.765.383-.93.78-.165.398-.143.854.107 1.204l.527.738c.32.447.269 1.06-.12 1.45l-.774.773a1.125 1.125 0 0 1-1.449.12l-.738-.527c-.35-.25-.806-.272-1.203-.107-.397.165-.71.505-.781.929l-.149.894c-.09.542-.56.94-1.11.94h-1.094c-.55 0-1.019-.398-1.11-.94l-.148-.894c-.071-.424-.384-.764-.781-.93-.398-.164-.854-.142-1.204.108l-.738.527c-.447.32-1.06.269-1.45-.12l-.773-.774a1.125 1.125 0 0 1-.12-1.45l.527-.737c.25-.35.273-.806.108-1.204-.165-.397-.505-.71-.93-.78l-.894-.15c-.542-.09-.94-.56-.94-1.109v-1.094c0-.55.398-1.02.94-1.11l.894-.149c.424-.07.765-.383.93-.78.165-.398.143-.854-.107-1.204l-.527-.738a1.125 1.125 0 0 1 .12-1.45l.773-.773a1.125 1.125 0 0 1 1.45-.12l.737.527c.35.25.807.272 1.204.107.397-.165.71-.505.78-.929l.15-.894Z M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z',
    ];
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body
        class="min-h-screen bg-cream-200 font-sans text-ink-700 antialiased"
        x-data="{
            sidebarOpen: false,
            sidebarCollapsed: localStorage.getItem('admin-sidebar-collapsed') === '1',
            toggleCollapsed() {
                this.sidebarCollapsed = !this.sidebarCollapsed;
                localStorage.setItem('admin-sidebar-collapsed', this.sidebarCollapsed ? '1' : '0');
            },
        }"
    >
        {{-- Outer wrap: light cream canvas with padding around the app card --}}
        <div class="min-h-screen p-0 sm:p-4 lg:p-6">
            <div class="flex min-h-[calc(100vh-3rem)] overflow-hidden bg-white shadow-[0_10px_40px_-15px_rgba(0,4,78,0.18)] sm:rounded-3xl">
                {{-- SIDEBAR --}}
                <aside
                    :class="[
                        sidebarOpen ? 'translate-x-0' : '-translate-x-full',
                        sidebarCollapsed ? 'lg:w-20' : 'lg:w-72',
                    ]"
                    class="fixed inset-y-0 left-0 z-40 flex w-72 flex-col border-r border-cream-300 bg-white transition-all duration-200 sm:rounded-l-3xl lg:static lg:translate-x-0"
                >
                    <div
                        :class="sidebarCollapsed ? 'lg:justify-center lg:px-3' : 'lg:px-5'"
                        class="flex items-center justify-between gap-2 px-5 py-5"
                    >
                        <a
                            x-show="!sidebarCollapsed"
                            href="{{ route('admin.dashboard') }}"
                            class="flex items-center"
                            aria-label="Fifiawoto Foundation admin"
                            wire:navigate
                        >
                            <img src="{{ asset('images/logos/dff.svg') }}" alt="Dadaa Fifiawoto Nyamadi Foundation" class="h-9 w-auto">
                        </a>

                        <a
                            x-show="sidebarCollapsed"
                            x-cloak
                            href="{{ route('admin.dashboard') }}"
                            class="hidden aspect-square size-10 place-items-center rounded-xl bg-brand-900 font-serif text-base font-bold text-white lg:grid"
                            aria-label="Fifiawoto Foundation admin"
                            wire:navigate
                        >
                            F
                        </a>

                        <button
                            type="button"
                            @click="toggleCollapsed()"
                            class="hidden rounded-lg p-1.5 text-ink-500 hover:bg-cream-100 hover:text-gold-500 lg:inline-flex"
                            :aria-label="sidebarCollapsed ? 'Expand sidebar' : 'Collapse sidebar'"
                            :title="sidebarCollapsed ? 'Expand sidebar' : 'Collapse sidebar'"
                        >
                            <svg
                                :class="sidebarCollapsed ? 'rotate-180' : ''"
                                class="size-4 transition-transform"
                                xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"/>
                            </svg>
                        </button>

                        <button
                            type="button"
                            @click="sidebarOpen = false"
                            class="rounded-md p-1.5 text-ink-500 hover:text-gold-500 lg:hidden"
                            aria-label="Close menu"
                        >
                            <svg class="size-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    {{-- Foundation card (analog of the "store selector" in the sample) --}}
                    @if ($user)
                        <div :class="sidebarCollapsed ? 'lg:hidden' : 'lg:block'" class="px-5 pb-4">
                            <p class="font-sans text-[10px] font-semibold uppercase tracking-[0.22em] text-ink-500">Foundation</p>
                            <div class="mt-2 flex items-center gap-3 rounded-2xl border border-cream-300 bg-cream-100 px-3 py-2.5">
                                <span class="grid aspect-square size-8 place-items-center rounded-lg bg-brand-900 text-xs font-bold text-white">DF</span>
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-semibold text-ink-900">Dadaa Fifiawoto</p>
                                    <p class="truncate text-[11px] text-ink-500">Foundation HQ</p>
                                </div>
                            </div>
                        </div>
                    @endif

                    <nav
                        :class="sidebarCollapsed ? 'lg:px-2' : 'lg:px-3'"
                        class="flex-1 space-y-5 overflow-y-auto px-3 pb-6"
                        aria-label="Admin navigation"
                    >
                        @foreach ($navGroups as $group)
                            @php
                                $visibleItems = collect($group['items'])->filter(fn ($item) => $item['show']);
                            @endphp
                            @if ($visibleItems->isNotEmpty())
                                <div>
                                    <p
                                        x-show="!sidebarCollapsed"
                                        class="font-sans px-3 pb-1 text-[10px] font-semibold uppercase tracking-[0.22em] text-ink-500"
                                    >
                                        {{ $group['heading'] }}
                                    </p>
                                    <ul class="space-y-0.5">
                                        @foreach ($visibleItems as $item)
                                            @php
                                                $isCurrent = request()->routeIs($item['route']) || request()->routeIs($item['route'].'.*');
                                            @endphp
                                            <li>
                                                <a
                                                    href="{{ route($item['route']) }}"
                                                    wire:navigate
                                                    :class="sidebarCollapsed ? 'lg:justify-center lg:px-2' : ''"
                                                    @class([
                                                        'group flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition-colors',
                                                        'bg-cream-100 font-semibold text-brand-900' => $isCurrent,
                                                        'text-ink-700 hover:bg-cream-100 hover:text-brand-900' => ! $isCurrent,
                                                    ])
                                                    :title="sidebarCollapsed ? @js($item['label']) : null"
                                                >
                                                    <span @class([
                                                        'grid aspect-square size-7 shrink-0 place-items-center rounded-lg transition-colors',
                                                        'bg-brand-900 text-white' => $isCurrent,
                                                        'bg-cream-100 text-ink-500 group-hover:bg-white group-hover:text-brand-900' => ! $isCurrent,
                                                    ])>
                                                        <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $icons[$item['icon']] ?? $icons['cog'] }}"/>
                                                        </svg>
                                                    </span>
                                                    <span x-show="!sidebarCollapsed" class="flex-1">{{ $item['label'] }}</span>
                                                </a>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                        @endforeach
                    </nav>

                    @if ($user)
                        <div class="border-t border-cream-300 p-3">
                            <div :class="sidebarCollapsed ? 'lg:justify-center' : ''" class="flex items-center gap-3 rounded-2xl bg-cream-100 px-3 py-2.5">
                                <span class="grid aspect-square size-9 shrink-0 place-items-center rounded-full bg-brand-900 text-xs font-bold text-white">
                                    {{ $user->initials() }}
                                </span>
                                <div x-show="!sidebarCollapsed" class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-semibold text-ink-900">{{ $user->name }}</p>
                                    <p class="truncate text-[11px] text-ink-500">{{ $role?->label() ?? 'Member' }}</p>
                                </div>
                                <form x-show="!sidebarCollapsed" method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button
                                        type="submit"
                                        data-test="logout-button"
                                        class="grid aspect-square size-8 place-items-center rounded-lg text-ink-500 transition hover:bg-white hover:text-gold-500"
                                        title="Log out"
                                        aria-label="Log out"
                                    >
                                        <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15M12 9l-3 3m0 0 3 3m-3-3h12.75"/>
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endif
                </aside>

                <div
                    x-show="sidebarOpen"
                    x-cloak
                    x-transition.opacity
                    @click="sidebarOpen = false"
                    class="fixed inset-0 z-30 bg-brand-900/40 lg:hidden"
                ></div>

                {{-- MAIN --}}
                <div class="flex min-w-0 flex-1 flex-col">
                    <header class="flex items-center gap-4 border-b border-cream-300 bg-white px-5 py-4 sm:px-6 lg:px-8">
                        <button
                            type="button"
                            @click="sidebarOpen = true"
                            class="rounded-lg p-1.5 text-ink-700 hover:bg-cream-100 hover:text-gold-500 lg:hidden"
                            aria-label="Open menu"
                        >
                            <svg class="size-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/>
                            </svg>
                        </button>

                        {{-- Breadcrumbs --}}
                        <div class="hidden items-center gap-2 sm:flex">
                            <button type="button" onclick="history.back()" class="grid aspect-square size-8 place-items-center rounded-lg border border-cream-300 text-ink-500 transition hover:border-brand-900 hover:text-brand-900" aria-label="Back">
                                <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m15 19-7-7 7-7"/></svg>
                            </button>
                            <button type="button" onclick="history.forward()" class="grid aspect-square size-8 place-items-center rounded-lg border border-cream-300 text-ink-500 transition hover:border-brand-900 hover:text-brand-900" aria-label="Forward">
                                <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m9 5 7 7-7 7"/></svg>
                            </button>
                            <nav class="ml-1 flex items-center gap-1 text-sm" aria-label="Breadcrumb">
                                @foreach ($breadcrumbs as $i => $crumb)
                                    @if ($i > 0)
                                        <span class="text-ink-500">/</span>
                                    @endif
                                    @if ($loop->last)
                                        <span class="font-semibold text-ink-900">{{ $crumb }}</span>
                                    @else
                                        <span class="text-ink-500">{{ $crumb }}</span>
                                    @endif
                                @endforeach
                            </nav>
                        </div>

                        {{-- Search --}}
                        <form action="#" method="get" role="search" class="ml-auto hidden flex-1 max-w-md md:block">
                            <label class="relative block">
                                <span class="absolute inset-y-0 left-3 grid place-items-center text-ink-500">
                                    <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35M10.5 18a7.5 7.5 0 1 1 0-15 7.5 7.5 0 0 1 0 15Z"/></svg>
                                </span>
                                <input type="search" name="q" placeholder="Search beneficiaries, events, donors…" class="w-full rounded-full border border-cream-300 bg-cream-100 py-2 pl-9 pr-4 text-sm text-ink-900 placeholder-ink-500/70 focus:border-brand-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand-900/15">
                            </label>
                        </form>

                        <div class="ml-auto flex items-center gap-2 md:ml-0">
                            <button type="button" class="grid aspect-square size-9 place-items-center rounded-full border border-cream-300 text-ink-500 transition hover:border-brand-900 hover:text-brand-900" aria-label="Help">
                                <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 5.25h.008v.008H12v-.008Z"/></svg>
                            </button>
                            <button type="button" class="relative grid aspect-square size-9 place-items-center rounded-full border border-cream-300 text-ink-500 transition hover:border-brand-900 hover:text-brand-900" aria-label="Notifications">
                                <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0"/></svg>
                                <span class="absolute right-1.5 top-1.5 size-2 rounded-full bg-gold-500"></span>
                            </button>
                        </div>
                    </header>

                    <main class="flex-1 overflow-x-hidden bg-white px-4 py-6 sm:rounded-br-3xl sm:px-6 lg:px-8 lg:py-8">
                        @if ($title)
                            <div class="mb-6">
                                <h1 class="font-serif text-2xl font-bold text-ink-900 sm:text-3xl">{{ $title }}</h1>
                            </div>
                        @endif

                        {{ $slot }}
                    </main>
                </div>
            </div>
        </div>

        @livewireScripts
    </body>
</html>
