{!! '<'.'?xml version="1.0" encoding="UTF-8"?>' !!}
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
@foreach ($staticRoutes as $routeName)
    <url>
        <loc>{{ route($routeName) }}</loc>
    </url>
@endforeach
@foreach ($events as $event)
    <url>
        <loc>{{ route('events.show', $event) }}</loc>
        @if ($event->updated_at)
            <lastmod>{{ $event->updated_at->toAtomString() }}</lastmod>
        @endif
    </url>
@endforeach
</urlset>
