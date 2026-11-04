<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Public visitors get the language they chose, or else the one their browser prefers.
 * Staff always see English, because the admin screens are English only.
 */
class SetLocale
{
    public const LOCALES = ['en', 'fr'];

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->canAccessAdmin() !== true) {
            app()->setLocale($request->session()->get('locale') ?? $request->getPreferredLanguage(self::LOCALES) ?? 'en');
        }

        return $next($request);
    }
}
