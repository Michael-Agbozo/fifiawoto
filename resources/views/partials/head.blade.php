<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />

<title>
    {{ filled($title ?? null) ? $title.' - '.config('app.name', 'Laravel') : config('app.name', 'Laravel') }}
</title>

<link rel="icon" href="{{ asset('images/logos/favicon-512.jpg') }}" type="image/jpeg">
<link rel="apple-touch-icon" href="{{ asset('images/logos/favicon-512.jpg') }}">
<meta name="theme-color" content="#00044e">

<link rel="preconnect" href="https://fonts.bunny.net">
<link href="https://fonts.bunny.net/css?family=roboto:400,500,700|playfair-display:400,600,700,800" rel="stylesheet" />

@vite(['resources/css/app.css', 'resources/js/app.js'])
