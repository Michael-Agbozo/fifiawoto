@props(['testimonial'])

@php
    $video = \App\Support\MediaLink::parse($testimonial->video_url ?? null);
    $externalUrl = ! $video && str_starts_with((string) ($testimonial->video_url ?? null), 'https://') ? $testimonial->video_url : null;
@endphp

@if ($video)
    <div x-data="{ videoOpen: false }" {{ $attributes }}>
        <button type="button" @click="videoOpen = true" class="inline-flex cursor-pointer items-center gap-2 rounded-full bg-brand-50 px-4 py-2 text-sm font-semibold text-heading transition hover:bg-brand-100">
            <svg class="size-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M8 5v14l11-7z"/></svg>
            {{ __('Watch :v1’s story', ['v1' => $testimonial->author_name]) }}
        </button>

        <x-site.modal name="video" :title="$testimonial->author_name" :subtitle="$testimonial->author_role" size="xl">
            {{-- Only load the player while the dialog is open, so closing it stops playback. --}}
            <template x-if="videoOpen">
                <div class="aspect-video w-full bg-night">
                    <iframe
                        src="{{ $video->embedUrl() }}"
                        title="{{ __('Video from :v1', ['v1' => $testimonial->author_name]) }}"
                        class="size-full"
                        allow="autoplay; encrypted-media; picture-in-picture"
                        allowfullscreen
                    ></iframe>
                </div>
            </template>
        </x-site.modal>
    </div>
@elseif ($externalUrl)
    <a href="{{ $externalUrl }}" target="_blank" rel="noopener noreferrer" {{ $attributes->merge(['class' => 'inline-flex items-center gap-2 rounded-full bg-brand-50 px-4 py-2 text-sm font-semibold text-heading transition hover:bg-brand-100']) }}>
        <svg class="size-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M8 5v14l11-7z"/></svg>
        {{ __('Watch :v1’s story', ['v1' => $testimonial->author_name]) }}
    </a>
@endif
