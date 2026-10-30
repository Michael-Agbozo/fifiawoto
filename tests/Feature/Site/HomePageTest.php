<?php

use App\Enums\EventStatus;
use App\Models\Donation;
use App\Models\Event;
use App\Models\InstagramPost;
use App\Models\Testimonial;

it('renders every PRD-mandated section on the home page', function () {
    Event::factory()->create([
        'starts_at' => now()->addWeeks(2),
        'status' => EventStatus::Published->value,
        'published_at' => now()->subDay(),
    ]);

    $response = $this->get(route('home'));

    $response->assertOk();

    foreach ([
        'Empowering',
        'New Beginnings',
        'Our Impact in Numbers',
        'A Legacy of',
        'A Future of Hope',
        'Real impact, measured',
        'Our Programs and Initiatives',
        'Follow Our Journey',
        'Stories of Impact',
        'Upcoming Outreach',
        'Stay Connected',
    ] as $section) {
        $response->assertSee($section, escape: false);
    }
});

it('exposes both hero CTAs', function () {
    $this->get(route('home'))
        ->assertSee('Donate')
        ->assertSee('Get In Touch');
});

it('leaves the media gallery to its own page', function () {
    $this->get(route('home'))->assertOk()->assertDontSee('Our Work in Action');
});

it('lists all five core programs', function () {
    $response = $this->get(route('home'));

    foreach ([
        'Women Empowerment',
        'Child Education Support',
        'Support for Vulnerable Populations',
        'Community Development',
        'Global Outreach',
    ] as $program) {
        $response->assertSee($program);
    }
});

it('renders the featured event when one is published and upcoming', function () {
    $event = Event::factory()->create([
        'title' => 'Spotlight Event',
        'slug' => 'spotlight-event',
        'goal_cents' => 10_000_00,
        'starts_at' => now()->addWeeks(2),
        'status' => EventStatus::Published->value,
        'published_at' => now()->subDay(),
    ]);

    Donation::factory()->for($event)->create(['amount_cents' => 4_300_00]);

    $this->get(route('home'))
        ->assertSee('Upcoming Outreach')
        ->assertSee('Spotlight Event')
        ->assertSee('Goal $10,000')
        ->assertSee('43% funded');
});

it('shows the trusted non-profit welcome with both ways to help', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('A Trusted Non-Profit')
        ->assertSee('Be a Hero', escape: false)
        ->assertSee(route('sponsor-a-child'))
        ->assertSee('Support Now');
});

it('lists popular causes with raised amounts and progress', function () {
    $cause = Event::factory()->create([
        'title' => 'School Supplies Drive',
        'country' => 'Togo',
        'goal_cents' => 20_000_00,
        'starts_at' => now()->addWeeks(3),
        'status' => EventStatus::Published->value,
        'published_at' => now()->subDay(),
    ]);
    Donation::factory()->for($cause)->create(['amount_cents' => 5_000_00]);

    Event::factory()->create([
        'title' => 'No Goal Gathering',
        'goal_cents' => 0,
        'starts_at' => now()->addWeeks(4),
    ]);

    $this->get(route('home'))
        ->assertSee('Find Popular Causes')
        ->assertSee('School Supplies Drive')
        ->assertSee('Raised: <b class="font-semibold text-heading">$5,000</b>', escape: false)
        ->assertSee('25% of', escape: false)
        ->assertSee(route('events.show', $cause))
        ->assertDontSee('No Goal Gathering');
});

it('fills the stories carousel with featured stories first and more beyond three', function () {
    Testimonial::factory()->featured()->create(['author_name' => 'Featured Voice', 'sort' => 50]);
    Testimonial::factory()->count(3)->create(['featured' => false]);
    Testimonial::factory()->create(['author_name' => 'Fourth Plus Voice', 'featured' => false, 'sort' => 99]);

    $response = $this->get(route('home'))->assertOk()->assertSee('Stories of Impact');

    $response->assertSee('Featured Voice')->assertSee('Fourth Plus Voice');
    expect(strpos($response->getContent(), 'Featured Voice'))->toBeLessThan(strpos($response->getContent(), 'Fourth Plus Voice'));
});

it('hides popular causes when no fundraising events are upcoming', function () {
    Event::query()->delete();

    $this->get(route('home'))->assertDontSee('Find Popular Causes');
});

it('hides the featured event section when nothing is scheduled', function () {
    Event::query()->delete();

    $this->get(route('home'))
        ->assertDontSee('Upcoming Outreach');
});

it('falls back to media_url for Instagram image posts without a thumbnail', function () {
    InstagramPost::factory()->create([
        'is_approved' => true,
        'is_hidden' => false,
        'media_type' => 'IMAGE',
        'media_url' => 'https://cdn.example.test/ig-image-only.jpg',
        'thumbnail_url' => null,
        'posted_at' => now(),
    ]);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('https://cdn.example.test/ig-image-only.jpg', false);
});
