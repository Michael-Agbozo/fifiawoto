<?php

namespace App\Support;

use App\Models\SiteConversion;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Throwable;

/**
 * First-party, cookieless page counts. No IP address or query string is stored: a visitor
 * is a hash that also uses today's date, so it can't follow anyone from one day to the next.
 * Campaign details stay in the normal session so a later form submission can be credited.
 */
class SiteAnalytics
{
    private const SESSION_KEY = 'analytics.attribution';

    /** @return array{source: string, medium: ?string, campaign: ?string} */
    public static function attributionFrom(Request $request): array
    {
        $campaign = [
            'source' => self::clean($request->query('utm_source')),
            'medium' => self::clean($request->query('utm_medium')),
            'campaign' => self::clean($request->query('utm_campaign')),
        ];

        if ($campaign['source'] !== null) {
            return $campaign;
        }

        $referrerHost = parse_url((string) $request->headers->get('referer'), PHP_URL_HOST);
        $isExternal = is_string($referrerHost) && $referrerHost !== $request->getHost();

        return [
            'source' => $isExternal ? Str::limit(Str::lower(Str::after($referrerHost, 'www.')), 120, '') : 'direct',
            'medium' => $campaign['medium'],
            'campaign' => $campaign['campaign'],
        ];
    }

    public static function visitorHash(Request $request): string
    {
        return hash('sha256', implode('|', [today()->toDateString(), config('app.key'), $request->ip(), $request->userAgent()]));
    }

    /** Credit the first way this visitor arrived in this session; later direct page views don't replace it. */
    public static function rememberAttribution(Request $request, array $attribution): void
    {
        if ($request->hasSession() && ! $request->session()->has(self::SESSION_KEY)) {
            $request->session()->put(self::SESSION_KEY, $attribution);
        }
    }

    /** Record a form completion. Analytics must never stop the form itself from working. */
    public static function conversion(string $type): void
    {
        try {
            $attribution = session(self::SESSION_KEY, ['source' => 'direct', 'medium' => null, 'campaign' => null]);

            SiteConversion::query()->create(['type' => $type] + $attribution);
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    private static function clean(mixed $value): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        return Str::limit(Str::lower(trim(strip_tags($value))), 120, '');
    }
}
