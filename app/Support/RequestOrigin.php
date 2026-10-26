<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Resolves which page(s) a request really originates from.
 *
 * A Livewire update hits a single shared endpoint, so its URL says nothing
 * about the page it came from. Each component snapshot in the payload records
 * the path it was rendered on, and Livewire may batch several components
 * into one request — so every one of them has to be checked, not just the first.
 */
class RequestOrigin
{
    /**
     * Whether every page this request originates from matches one of the given patterns.
     *
     * @param  list<string>  $patterns  Paths without a leading slash; `*` wildcards allowed.
     */
    public static function isWithin(Request $request, array $patterns): bool
    {
        $paths = static::paths($request);

        if ($paths === []) {
            return false;
        }

        foreach ($paths as $path) {
            if (! Str::is($patterns, $path)) {
                return false;
            }
        }

        return true;
    }

    /**
     * A genuine Livewire update: a POST that routed to Livewire's update endpoint.
     * The X-Livewire header alone proves nothing — clients can send it anywhere.
     */
    protected static function isLivewireUpdate(Request $request): bool
    {
        return $request->isMethod('post')
            && $request->hasHeader('X-Livewire')
            && ($request->route()?->named('*livewire.update') ?? false);
    }

    /**
     * @return list<string>
     */
    public static function paths(Request $request): array
    {
        if (! self::isLivewireUpdate($request)) {
            return [ltrim($request->path(), '/')];
        }

        // Body only — never the query string, which anyone can append to any URL.
        $components = $request->isJson() ? $request->json('components') : $request->post('components');

        if (! is_array($components)) {
            return [];
        }

        $paths = [];

        foreach ($components as $component) {
            $snapshot = json_decode((string) data_get($component, 'snapshot', ''), true);
            $path = data_get($snapshot, 'memo.path');

            if (! is_string($path)) {
                return [];
            }

            $paths[] = ltrim($path, '/');
        }

        return $paths;
    }
}
