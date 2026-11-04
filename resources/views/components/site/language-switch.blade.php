@php $otherLocale = app()->getLocale() === 'fr' ? 'en' : 'fr'; @endphp

<form method="POST" action="{{ route('language.switch', $otherLocale) }}" {{ $attributes }}>
    @csrf
    <button type="submit" lang="{{ $otherLocale }}" aria-label="{{ $otherLocale === 'fr' ? 'Voir le site en français' : 'View the site in English' }}" class="rounded-full px-2 py-1 text-xs font-bold uppercase tracking-wide underline-offset-4 hover:underline">
        {{ $otherLocale }}
    </button>
</form>
