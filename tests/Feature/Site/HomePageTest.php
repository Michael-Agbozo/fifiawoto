<?php

use App\Enums\EventStatus;
use App\Models\Donation;
use App\Models\Event;

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
        'Our Work in Action',
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

it('hides the featured event section when nothing is scheduled', function () {
    Event::query()->delete();

    $this->get(route('home'))
        ->assertDontSee('Upcoming Outreach');
});
