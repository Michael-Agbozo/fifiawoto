<?php

use App\Enums\EventStatus;
use App\Models\Donation;
use App\Models\Event;

it('lists upcoming published events on /events', function () {
    $published = Event::factory()->create([
        'title' => 'Volta Outreach',
        'slug' => 'volta-outreach',
        'starts_at' => now()->addDays(20),
        'status' => EventStatus::Published->value,
        'published_at' => now()->subDay(),
    ]);

    Event::factory()->draft()->create([
        'title' => 'Hidden Draft Event',
        'slug' => 'hidden-draft',
        'starts_at' => now()->addDays(20),
    ]);

    $this->get(route('events.index'))
        ->assertOk()
        ->assertSee($published->title)
        ->assertDontSee('Hidden Draft Event');
});

it('shows a published event detail page with progress', function () {
    $event = Event::factory()->create([
        'title' => 'Brooklyn Family Day',
        'slug' => 'brooklyn-family-day',
        'starts_at' => now()->addDays(15),
        'location' => 'Brooklyn, New York',
        'country' => 'United States',
        'description' => 'A community celebration with health screening and educational booths.',
        'activities' => "Health screening\nKid's reading corner",
        'expected_impact' => 'Engage 300 community members.',
        'volunteer_opportunities' => "Greeter\nMedia",
        'goal_cents' => 5_000_00,
        'status' => EventStatus::Published->value,
        'published_at' => now()->subDay(),
    ]);

    Donation::factory()->for($event)->create(['amount_cents' => 2_500_00]);

    $this->get(route('events.show', $event))
        ->assertOk()
        ->assertSee('Brooklyn Family Day')
        ->assertSee('Brooklyn, New York')
        ->assertSee('Event Overview')
        ->assertSee('Health screening')
        ->assertSee('Engage 300 community members.')
        ->assertSee('Greeter')
        ->assertSee('50% funded');
});

it('404s when a draft event is requested by slug', function () {
    $event = Event::factory()->draft()->create([
        'slug' => 'unpublished-event',
    ]);

    $this->get(route('events.show', $event))->assertNotFound();
});

it('filters events by title via the search box', function () {
    Event::factory()->create([
        'title' => 'Mobile Health Clinic Outreach',
        'slug' => 'mobile-health-clinic',
        'location' => 'Volta Region',
        'starts_at' => now()->addDays(10),
        'status' => EventStatus::Published->value,
        'published_at' => now()->subDay(),
    ]);
    Event::factory()->create([
        'title' => 'Back-to-School Drive',
        'slug' => 'back-to-school-drive',
        'location' => 'Greater Accra',
        'starts_at' => now()->addDays(12),
        'status' => EventStatus::Published->value,
        'published_at' => now()->subDay(),
    ]);

    $response = $this->get(route('events.index', ['q' => 'health']));

    $response->assertOk()
        ->assertSee('Mobile Health Clinic Outreach')
        ->assertDontSee('Back-to-School Drive')
        ->assertSee('Showing results for', escape: false);
});

it('matches the search query against location and country too', function () {
    Event::factory()->create([
        'title' => 'Family Day',
        'slug' => 'family-day-volta',
        'location' => 'Volta Region',
        'country' => 'Ghana',
        'starts_at' => now()->addDays(10),
        'status' => EventStatus::Published->value,
        'published_at' => now()->subDay(),
    ]);
    Event::factory()->create([
        'title' => 'NY Outreach',
        'slug' => 'ny-outreach',
        'location' => 'Brooklyn',
        'country' => 'United States',
        'starts_at' => now()->addDays(12),
        'status' => EventStatus::Published->value,
        'published_at' => now()->subDay(),
    ]);

    $this->get(route('events.index', ['q' => 'Ghana']))
        ->assertOk()
        ->assertSee('Family Day')
        ->assertDontSee('NY Outreach');
});

it('shows a friendly empty state when search returns no results', function () {
    $this->get(route('events.index', ['q' => 'no-match-here']))
        ->assertOk()
        ->assertSee('No upcoming events match');
});

it('partitions past events into a separate section', function () {
    Event::factory()->past()->create([
        'title' => 'Old Outreach',
        'slug' => 'old-outreach',
        'status' => EventStatus::Published->value,
        'published_at' => now()->subMonth(),
    ]);

    $this->get(route('events.index'))
        ->assertSee('Past events')
        ->assertSee('Old Outreach');
});
