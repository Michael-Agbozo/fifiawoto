{{-- Spam honeypot: hidden from people and assistive tech; bots that fill every field trip it. --}}
<div aria-hidden="true" class="pointer-events-none absolute -left-[9999px] h-px w-px overflow-hidden">
    <label for="hp-website-{{ $this->getId() }}">{{ __('Leave this field empty') }}</label>
    <input id="hp-website-{{ $this->getId() }}" type="text" name="website" wire:model="website" tabindex="-1" autocomplete="off">
</div>
