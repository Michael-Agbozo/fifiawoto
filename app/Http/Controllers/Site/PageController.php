<?php

namespace App\Http\Controllers\Site;

use App\Enums\MediaCategory;
use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Faq;
use App\Models\Testimonial;
use App\Services\ImpactMetricService;
use App\Support\MediaAlbums;
use Illuminate\Contracts\View\View;

class PageController extends Controller
{
    public function home(ImpactMetricService $metrics): View
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
            'impact' => $metrics->publicStats(),
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
        return view('site.contact', [
            'faqs' => Faq::query()->onPage('contact')->published()->ordered()->get(),
        ]);
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

    public function media(MediaAlbums $albums): View
    {
        return view('site.media', [
            'albums' => $albums->all(),
        ]);
    }

    /** One event's own photo gallery. */
    public function mediaEvent(Event $event, MediaAlbums $albums): View
    {
        abort_unless(Event::query()->published()->whereKey($event->getKey())->exists(), 404);

        return $this->albumPage($albums->forEvent($event), $albums);
    }

    /** Photos from a programme that aren't tied to a public event. */
    public function mediaCollection(string $category, MediaAlbums $albums): View
    {
        $category = MediaCategory::tryFrom($category) ?? abort(404);

        return $this->albumPage($albums->forCategory($category), $albums);
    }

    /** @param  array<string, mixed>|null  $album */
    private function albumPage(?array $album, MediaAlbums $albums): View
    {
        abort_if($album === null, 404);

        return view('site.media-album', [
            'album' => $album,
            'moreAlbums' => $albums->all()->reject(fn (array $other) => $other['key'] === $album['key'])->take(3)->values(),
        ]);
    }
}
