<?php

use Livewire\Component;

new class extends Component
{
}; ?>

<section class="mt-10 space-y-6" x-data>
    <div class="mb-5">
        <h3 class="font-serif text-xl font-bold text-ink-900">{{ __('Delete account') }}</h3>
        <p class="mt-1 text-sm text-ink-500">{{ __('Delete your account and all of its resources') }}</p>
    </div>

    <button
        type="button"
        @click="$dispatch('open-delete-account')"
        data-test="delete-user-button"
        class="inline-flex items-center justify-center rounded-2xl bg-gold-500 px-6 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-brand-900"
    >
        {{ __('Delete account') }}
    </button>

    <livewire:pages::settings.delete-user-modal />
</section>
