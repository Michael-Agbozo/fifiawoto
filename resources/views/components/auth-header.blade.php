@props([
    'title',
    'description' => null,
    'eyebrow' => 'Account access',
])

<div class="flex w-full flex-col">
    <p class="eyebrow">{{ $eyebrow }}</p>
    <h1 class="mt-3 font-serif text-3xl font-bold leading-tight text-heading sm:text-4xl">{{ $title }}</h1>
    @if ($description)
        <p class="mt-3 text-sm leading-relaxed text-ink-500 sm:text-base">{{ $description }}</p>
    @endif
</div>
