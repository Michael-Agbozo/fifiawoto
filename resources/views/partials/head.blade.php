<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />

<title>
    {{ filled($title ?? null) ? $title.' - '.config('app.name', 'Laravel') : config('app.name', 'Laravel') }}
</title>

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
<link href="https://fonts.bunny.net/css?family=roboto:400,500,700|playfair-display:400,600,700,800" rel="stylesheet" />

@vite(['resources/css/app.css', 'resources/js/app.js'])
