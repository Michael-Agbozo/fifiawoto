@props([
    'title',
    'description' => null,
])

<div class="flex w-full flex-col text-center">
    <h1 class="font-serif text-2xl font-bold text-ink-900 sm:text-3xl">{{ $title }}</h1>
    @if ($description)
        <p class="mt-2 text-sm text-ink-500">{{ $description }}</p>
    @endif
</div>
