<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Event;
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

        $popularCauses = Event::query()
            ->published()
            ->upcoming()
            ->where('goal_cents', '>', 0)
            ->withSum(['donations as raised_cents' => fn ($donations) => $donations->inBaseCurrency()], 'amount_cents')
            ->limit(9)
            ->get();

        // Featured stories lead the home carousel, then the rest fill it out.
        $featuredTestimonials = Testimonial::query()
            ->orderByDesc('featured')
            ->ordered()
            ->limit(9)
            ->get();

        return view('site.home', [
            'featuredEvent' => $featuredEvent,
            'popularCauses' => $popularCauses,
            'featuredTestimonials' => $featuredTestimonials,
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

    public function applyForSupport(): View
    {
        return view('site.apply-for-support');
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
