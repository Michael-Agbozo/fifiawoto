<?php

use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\Leader;
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
        'Victoria Nyamadi', 'Bless Amago', 'Sarah Nyamadi', 'Gladys Kplorla Nyamadi',
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

it('gives every leader a bio pop-up, with a placeholder when no bio has been written', function () {
    Leader::factory()->create(['name' => 'Written Leader', 'bio' => 'Has led community programmes for many years.', 'sort' => 0]);
    Leader::factory()->create(['name' => 'Unwritten Leader', 'bio' => null, 'sort' => 1]);

    $this->get(route('about'))->assertOk()
        ->assertSee('role="dialog"', escape: false)
        ->assertSee('Read the bio of Written Leader')
        ->assertSee('Read the bio of Unwritten Leader')
        ->assertSee('Has led community programmes for many years.', escape: false)
        ->assertSee('A full biography is coming soon.', escape: false);
});

it('keeps unpublished leaders and their bios off the about page', function () {
    Leader::factory()->unpublished()->create(['name' => 'Hidden Leader', 'bio' => 'Private draft biography.']);

    $this->get(route('about'))->assertOk()
        ->assertDontSee('Hidden Leader')
        ->assertDontSee('Private draft biography.');
});

it('corrects the misspelled board member name already in the database', function () {
    $leader = Leader::factory()->create(['name' => 'Sarah Nyamai']);

    (require database_path('migrations/'.basename(glob(database_path('migrations/*_rename_sarah_nyamai_leader.php'))[0])))->up();

    expect($leader->refresh()->name)->toBe('Sarah Nyamadi');
});

it('links a leader to their own social profiles and otherwise to the foundation', function () {
    config(['social.instagram' => 'https://www.instagram.com/the_fifiawotofoundation/']);
    Leader::factory()->create(['name' => 'Linked Leader', 'linkedin_url' => 'https://www.linkedin.com/in/linked-leader', 'x_url' => 'https://x.com/linkedleader']);
    Leader::factory()->create(['name' => 'Unlinked Leader']);

    $this->get(route('about'))->assertOk()
        ->assertSee('https://www.linkedin.com/in/linked-leader', escape: false)
        ->assertSee('https://x.com/linkedleader', escape: false)
        ->assertSee('Connect with Linked Leader')
        ->assertSee('Follow the foundation')
        ->assertSee('https://www.instagram.com/the_fifiawotofoundation/', escape: false);
});

it('does not offer a network home page as the foundation profile', function () {
    config(['social.facebook' => null, 'social.youtube' => null]);
    Leader::factory()->create();

    $section = str($this->get(route('about'))->getContent())->between('leader-bio-close', 'Get involved')->toString();

    expect($section)->not->toContain('href="https://facebook.com"')
        ->not->toContain('href="https://youtube.com"');
});
