<?php

namespace App\Http\Middleware;

use App\Models\PageVisit;
use App\Support\SiteAnalytics;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Counts views of public pages only. Routes are an allowlist so that signed links,
 * admin screens and account pages can never end up in the analytics data.
 */
class RecordPageVisit
{
    private const TRACKED_ROUTES = [
        'home', 'about', 'volunteer', 'apply-for-support', 'contact', 'donate', 'testimonials',
        'sponsor-a-child', 'sponsor-a-child.*', 'media', 'media.*', 'events.*', 'legal.*',
    ];

    private const BOT_PATTERN = '/bot|crawl|spider|slurp|preview|monitor|headless|lighthouse|curl|wget|python|scrapy/i';

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($this->shouldRecord($request, $response)) {
            try {
                $attribution = SiteAnalytics::attributionFrom($request);
                SiteAnalytics::rememberAttribution($request, $attribution);

                PageVisit::query()->create($attribution + [
                    'path' => mb_substr('/'.ltrim($request->path(), '/'), 0, 255),
                    'visitor_hash' => SiteAnalytics::visitorHash($request),
                ]);
            } catch (Throwable $exception) {
                report($exception);
            }
        }

        return $response;
    }

    private function shouldRecord(Request $request, Response $response): bool
    {
        return $request->isMethod('GET')
            && $response->getStatusCode() === 200
            && $request->routeIs(self::TRACKED_ROUTES)
            && $request->user()?->canAccessAdmin() !== true
            && $request->header('Purpose') !== 'prefetch'
            && $request->header('Sec-Purpose') === null
            && $this->isHumanRequest($request);
    }

    private function isHumanRequest(Request $request): bool
    {
        return $request->header('Sec-GPC') !== '1'
            && $request->header('DNT') !== '1'
            && ! preg_match(self::BOT_PATTERN, (string) $request->userAgent())
            && filled($request->userAgent());
    }
}
