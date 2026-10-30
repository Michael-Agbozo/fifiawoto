<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />

@php
    $pageTitle = filled($title ?? null) ? $title.' - '.config('app.name', 'Laravel') : config('app.name', 'Laravel');
    $metaDescription = filled($description ?? null)
        ? \Illuminate\Support\Str::limit(trim(preg_replace('/\s+/', ' ', strip_tags($description))), 160)
        : 'The Dadaa Fifiawoto Nyamadi Foundation supports widows, children and vulnerable families across Ghana, Togo, Benin and the United States through education, healthcare and community outreach.';
    $metaImage = filled($image ?? null) ? $image : asset('images/foundation/community-1.jpg');
    $canonicalUrl = url()->current();
@endphp

<title>
    {{ $pageTitle }}
</title>

<meta name="description" content="{{ $metaDescription }}">
<link rel="canonical" href="{{ $canonicalUrl }}">
@if ($noindex ?? false)
    <meta name="robots" content="noindex, nofollow">
@endif

<meta property="og:type" content="website">
<meta property="og:site_name" content="{{ config('app.name', 'Laravel') }}">
<meta property="og:title" content="{{ $pageTitle }}">
<meta property="og:description" content="{{ $metaDescription }}">
<meta property="og:url" content="{{ $canonicalUrl }}">
<meta property="og:image" content="{{ $metaImage }}">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $pageTitle }}">
<meta name="twitter:description" content="{{ $metaDescription }}">
<meta name="twitter:image" content="{{ $metaImage }}">

<link rel="icon" href="{{ asset('images/logos/favicon-512.jpg') }}" type="image/jpeg">
<link rel="apple-touch-icon" href="{{ asset('images/logos/favicon-512.jpg') }}">
<meta name="theme-color" content="#00044e">

{{-- Apply the saved (or OS-preferred) theme BEFORE first paint so the page never
     flashes the wrong colour scheme. Runs synchronously in <head>; reads the
     same `theme` key + `data-theme` attribute that the Alpine store uses below. --}}
<script>
    (function () {
        try {
            var stored = localStorage.getItem('theme');
            var prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
            var mode = stored === 'light' || stored === 'dark' ? stored : (prefersDark ? 'dark' : 'light');
            document.documentElement.setAttribute('data-theme', mode);
            if (mode === 'dark') {
                document.documentElement.classList.add('dark');
            }
        } catch (e) {
            /* localStorage unavailable (private mode, etc.) — fall back to light. */
            document.documentElement.setAttribute('data-theme', 'light');
        }
    })();
</script>

<link rel="preconnect" href="https://fonts.bunny.net">
<link href="https://fonts.bunny.net/css?family=roboto:400,500,700|playfair-display:400,600,700,800,400i,500i,600i" rel="stylesheet" />

@vite(['resources/css/app.css', 'resources/js/app.js'])
