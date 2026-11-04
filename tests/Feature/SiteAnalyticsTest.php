<?php

use App\Models\PageVisit;
use App\Models\SiteConversion;
use App\Models\User;
use App\Support\SiteAnalytics;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

const BROWSER = ['User-Agent' => 'Mozilla/5.0 (Macintosh) Safari/605'];

it('counts a public page view without keeping the IP or query string', function () {
    $this->withHeaders(BROWSER)->get('/about?utm_source=Facebook&utm_medium=social&utm_campaign=Xmas&email=private@example.org')->assertOk();

    $visit = PageVisit::query()->sole();
    expect($visit)->path->toBe('/about')->source->toBe('facebook')->medium->toBe('social')->campaign->toBe('xmas')
        ->and(json_encode($visit->toArray()))->not->toContain('private@example.org')->not->toContain('127.0.0.1');
});

it('uses the referrer site as the source', function () {
    $this->withHeaders(BROWSER + ['Referer' => 'https://www.google.com/search?q=secret'])->get('/about');
    $this->withHeaders(BROWSER + ['Referer' => url('/about')])->get('/about');

    expect(PageVisit::query()->pluck('source')->all())->toBe(['google.com', 'direct']);
});

it('changes the visitor code every day', function () {
    $this->withHeaders(BROWSER)->get('/about');
    $this->travel(1)->day();
    $this->withHeaders(BROWSER)->get('/about');

    expect(PageVisit::query()->distinct()->count('visitor_hash'))->toBe(2);
});

it('skips bots, privacy signals, prefetches and non-public pages', function () {
    $this->withHeaders(['User-Agent' => 'Googlebot/2.1'])->get('/about');
    $this->withHeaders(BROWSER + ['DNT' => '1'])->get('/about');
    $this->withHeaders(BROWSER + ['Sec-GPC' => '1'])->get('/about');
    $this->withHeaders(BROWSER + ['Sec-Purpose' => 'prefetch'])->get('/about');
    $this->withHeaders(BROWSER)->get('/login');
    $this->withHeaders(BROWSER)->get('/sitemap.xml');

    expect(PageVisit::query()->count())->toBe(0);
});

it('does not count staff', function () {
    $this->actingAs(User::factory()->superAdmin()->create())->withHeaders(BROWSER)->get('/about');

    expect(PageVisit::query()->count())->toBe(0);
});

it('credits a form to the campaign the visitor first arrived from', function () {
    Mail::fake();

    $this->withHeaders(BROWSER)->get('/?utm_source=newsletter&utm_campaign=nov');
    $this->withHeaders(BROWSER)->get('/about');

    SiteAnalytics::conversion('newsletter_signup');

    expect(SiteConversion::query()->sole())->source->toBe('newsletter')->campaign->toBe('nov');
});

it('shows the report to staff who can view analytics', function () {
    PageVisit::query()->create(['path' => '/donate', 'source' => 'facebook', 'campaign' => 'xmas', 'visitor_hash' => str_repeat('a', 64)]);
    SiteConversion::query()->create(['type' => 'newsletter_signup', 'source' => 'facebook', 'campaign' => 'xmas']);

    $this->actingAs(User::factory()->superAdmin()->create())
        ->get(route('admin.analytics.index'))
        ->assertOk()
        ->assertSee('/donate')
        ->assertSee('facebook')
        ->assertSee('xmas')
        ->assertSee('Newsletter signups');

    Livewire::test('admin.analytics')->set('days', 999)->assertSet('days', 30);
});

it('blocks roles without analytics access', function () {
    $this->actingAs(User::factory()->create(['role' => 'volunteer_coordinator']))
        ->get(route('admin.analytics.index'))
        ->assertForbidden();
});

it('prunes old data', function () {
    PageVisit::query()->create(['path' => '/', 'source' => 'direct', 'visitor_hash' => 'x']);
    $this->travel(14)->months();
    PageVisit::query()->create(['path' => '/about', 'source' => 'direct', 'visitor_hash' => 'y']);

    $this->artisan('model:prune', ['--model' => [PageVisit::class]])->assertSuccessful();

    expect(PageVisit::query()->pluck('path')->all())->toBe(['/about']);
});
