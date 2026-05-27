<?php

use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Appearance settings')] class extends Component
{
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <x-pages::settings.layout :heading="__('Appearance')" :subheading="__('Update the appearance settings for your account')">
        <div
            x-data="{
                value: localStorage.getItem('appearance') || 'system',
                apply() {
                    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
                    const dark = this.value === 'dark' || (this.value === 'system' && prefersDark);
                    document.documentElement.classList.toggle('dark', dark);
                    localStorage.setItem('appearance', this.value);
                },
            }"
            x-init="apply()"
            class="inline-flex rounded-full border border-cream-300 bg-white p-1"
            role="radiogroup"
            aria-label="{{ __('Appearance') }}"
        >
            @foreach ([
                ['light',  __('Light')],
                ['dark',   __('Dark')],
                ['system', __('System')],
            ] as [$option, $label])
                <label
                    class="cursor-pointer rounded-full px-4 py-2 text-sm font-medium transition"
                    :class="value === @js($option) ? 'bg-gold-500 text-white' : 'text-ink-700 hover:text-gold-500'"
                >
                    <input
                        type="radio"
                        name="appearance"
                        value="{{ $option }}"
                        x-model="value"
                        @change="apply()"
                        class="sr-only"
                    >
                    {{ $label }}
                </label>
            @endforeach
        </div>
    </x-pages::settings.layout>
</section>
