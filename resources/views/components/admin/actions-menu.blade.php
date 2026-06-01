@props([
    'label' => 'Actions',
    'align' => 'right',
])

<div
    x-data="{
        open: false,
        x: 0,
        y: 0,
        toggle() {
            const rect = this.$refs.btn.getBoundingClientRect();
            this.y = rect.bottom + 6;
            @if ($align === 'left')
            this.x = rect.left;
            @else
            this.x = rect.right - 240;
            @endif
            if (this.x < 8) this.x = 8;
            if (this.x + 240 > window.innerWidth - 8) this.x = window.innerWidth - 248;
            this.open = !this.open;
        }
    }"
    @keydown.escape.window="open = false"
    @scroll.window="open = false"
    {{ $attributes->class(['inline-block text-left']) }}
>
    <button
        x-ref="btn"
        type="button"
        @click="toggle()"
        :aria-expanded="open"
        class="grid aspect-square size-9 place-items-center rounded-xl border border-cream-300 bg-white text-ink-700 transition hover:border-brand-900 hover:text-brand-900"
        aria-label="{{ $label }}"
        title="{{ $label }}"
    >
        <svg class="size-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
            <circle cx="5" cy="12" r="1.75"/>
            <circle cx="12" cy="12" r="1.75"/>
            <circle cx="19" cy="12" r="1.75"/>
        </svg>
    </button>

    <div
        x-show="open"
        x-cloak
        x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        :style="`top: ${y}px; left: ${x}px`"
        @click.outside="open = false"
        class="fixed z-50 w-60 overflow-hidden rounded-2xl border border-cream-300 bg-white shadow-[0_20px_60px_-20px_rgba(0,4,78,0.25)]"
        role="menu"
    >
        <div class="py-1.5">
            {{ $slot }}
        </div>
    </div>
</div>
