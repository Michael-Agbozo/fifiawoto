<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\InstagramPost;
use App\Models\MediaItem;
use App\Models\Testimonial;
use Illuminate\Contracts\View\View;

class PageController extends Controller
{
    public function home(): View
    {
        $featuredEvent = Event::query()
            ->published()
            ->upcoming()
            ->first();

        $featuredTestimonials = Testimonial::query()
            ->featured()
            ->ordered()
            ->limit(3)
            ->get();

        $instagramHighlights = InstagramPost::query()
            ->where('is_approved', true)
            ->where('is_hidden', false)
            ->orderByDesc('posted_at')
            ->limit(6)
            ->get();

        return view('site.home', [
            'featuredEvent' => $featuredEvent,
            'featuredTestimonials' => $featuredTestimonials,
            'instagramHighlights' => $instagramHighlights,
        ]);
    }

    public function about(): View
    {
        return view('site.about');
    }

    public function volunteer(): View
    {
        return view('site.volunteer');
    }

    public function contact(): View
    {
        return view('site.contact');
    }

    public function donate(): View
    {
        $featuredEvent = Event::query()
            ->published()
            ->upcoming()
            ->where('goal_cents', '>', 0)
            ->orderBy('starts_at')
            ->first();

        return view('site.donate', [
            'featuredEvent' => $featuredEvent,
        ]);
    }

    public function testimonials(): View
    {
        // Featured testimonials always lead. Then manual sort, then newest by id.
        $testimonials = Testimonial::query()
            ->orderByDesc('featured')
            ->orderBy('sort')
            ->orderBy('id')
            ->paginate(12)
            ->withQueryString();

        return view('site.testimonials', [
            'testimonials' => $testimonials,
            'total' => $testimonials->total(),
        ]);
    }

    public function media(): View
    {
        $items = MediaItem::query()
            ->with('event:id,slug,title,status,published_at')
            ->orderBy('sort')
            ->orderByDesc('id')
            ->get();

        return view('site.media', [
            'items' => $items,
        ]);
    }
}
