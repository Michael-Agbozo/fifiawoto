<?php

use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\NewsletterSubscriber;
use Illuminate\Support\Facades\URL;

it('renders meta description, canonical, Open Graph and Twitter tags with a default description', function () {
    $this->get(route('about'))
        ->assertOk()
        ->assertSee('<meta name="description" content="The Dadaa Fifiawoto Nyamadi Foundation supports', false)
        ->assertSee('<link rel="canonical" href="'.route('about').'">', false)
        ->assertSee('<meta property="og:title"', false)
        ->assertSee('<meta property="og:url" content="'.route('about').'">', false)
        ->assertSee('<meta property="og:image"', false)
        ->assertSee('<meta name="twitter:card" content="summary_large_image">', false);
});

it('uses the event description for event pages and drops query strings from the canonical url', function () {
    $event = Event::factory()->create([
        'slug' => 'seo-event',
        'description' => 'A community health screening in the Volta region.',
        'status' => EventStatus::Published->value,
        'published_at' => now()->subDay(),
    ]);

    $this->get(route('events.show', $event).'?utm_source=test')
        ->assertOk()
        ->assertSee('<meta name="description" content="A community health screening in the Volta region.">', false)
        ->assertSee('<link rel="canonical" href="'.route('events.show', $event).'">', false);
});

it('serves an XML sitemap of static pages and published events only', function () {
    $published = Event::factory()->create([
        'slug' => 'live-event',
        'status' => EventStatus::Published->value,
        'published_at' => now()->subDay(),
    ]);
    $scheduled = Event::factory()->create([
        'slug' => 'future-event',
        'status' => EventStatus::Published->value,
        'published_at' => now()->addWeek(),
    ]);
    $draft = Event::factory()->draft()->create(['slug' => 'draft-event']);

    $response = $this->get('/sitemap.xml');

    $response->assertOk()
        ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
        ->assertSee('<loc>'.route('home').'</loc>', false)
        ->assertSee('<loc>'.route('legal.privacy').'</loc>', false)
        ->assertSee('<loc>'.route('events.show', $published).'</loc>', false)
        ->assertDontSee(route('events.show', $scheduled))
        ->assertDontSee(route('events.show', $draft));

    expect(simplexml_load_string($response->getContent()))->not->toBeFalse();
});

it('references the sitemap from robots.txt', function () {
    expect(file_get_contents(public_path('robots.txt')))->toContain('Sitemap: ')->toContain('/sitemap.xml');
});

it('marks signed newsletter pages as noindex', function () {
    $subscriber = NewsletterSubscriber::factory()->create();

    $this->get(URL::signedRoute('newsletter.unsubscribe', ['subscriber' => $subscriber]))
        ->assertOk()
        ->assertSee('<meta name="robots" content="noindex, nofollow">', false);
});
