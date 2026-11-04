<?php

use App\Enums\EventStatus;
use App\Models\Event;
use Database\Seeders\LeaderSeeder;

it('renders every PRD-mandated About section', function () {
    $this->seed(LeaderSeeder::class);

    $response = $this->get(route('about'));

    $response->assertOk();

    foreach ([
        'About Us',
        'Serving with Heart',
        'Our Legacy',
        'Mission',
        'Vision',
        'Core Values',
        'A Global Presence',
        'Board of Directors',
        'Board of Advisors',
        'Stay Connected',
    ] as $section) {
        $response->assertSee($section);
    }
});

it('lists every named board member from the PRD', function () {
    $this->seed(LeaderSeeder::class);

    $response = $this->get(route('about'));

    foreach ([
        // Directors
        'Victoria Nyamadi', 'Bless Amago', 'Sarah Nyamai', 'Gladys Kplorla Nyamadi',
        'R.E. Amedzekor', 'Daniel Gbetodeme', 'Togbui Gbe', 'Ama Baffoe', 'Sabrina Nyamadi',
        // Advisors
        'Prof Lebene', 'Dr. Kaledzi',
    ] as $person) {
        $response->assertSee($person);
    }
});

it('mentions every country in the global presence list', function () {
    $response = $this->get(route('about'));

    foreach (['United States', 'Ghana', 'Togo', 'Benin'] as $country) {
        $response->assertSee($country);
    }
});

it('enumerates every core value from the PRD', function () {
    $response = $this->get(route('about'));

    foreach (['Compassion', 'Service', 'Empowerment', 'Inclusivity', 'Sustainability'] as $value) {
        $response->assertSee($value);
    }
});

it('links each country to its events with a live upcoming count', function () {
    Event::factory()->count(2)->create([
        'country' => 'Togo',
        'starts_at' => now()->addWeeks(2),
        'status' => EventStatus::Published->value,
        'published_at' => now()->subDay(),
    ]);

    $this->get(route('about'))
        ->assertOk()
        ->assertSee(route('events.index', ['q' => 'Togo']), escape: false)
        ->assertSee('2 upcoming events')
        ->assertDontSee('An interactive map will replace this list');
});
