<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    /** Named routes of the static public pages listed in the sitemap. */
    public const STATIC_ROUTES = [
        'home', 'about', 'events.index', 'volunteer', 'apply-for-support', 'donate',
        'testimonials', 'media', 'contact',
        'legal.privacy', 'legal.terms', 'legal.cookies', 'legal.disclaimer',
    ];

    public function __invoke(): Response
    {
        $events = Event::query()
            ->published()
            ->orderByDesc('starts_at')
            ->get(['id', 'slug', 'updated_at']);

        return response()
            ->view('site.sitemap', [
                'staticRoutes' => self::STATIC_ROUTES,
                'events' => $events,
            ])
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }
}
