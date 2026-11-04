<?php

use App\Enums\EventStatus;
use App\Models\Donation;
use App\Models\Event;
use Database\Seeders\EventSeeder;

it('seeds fundraisers, past events and a draft without duplicating on rerun', function () {
    $this->seed(EventSeeder::class);
    $this->seed(EventSeeder::class);

    expect(Event::query()->count())->toBe(8)
        ->and(Event::query()->published()->upcoming()->where('goal_cents', '>', 0)->count())->toBe(5)
        ->and(Event::query()->where('status', EventStatus::Draft->value)->count())->toBe(1);

    $volta = Event::query()->where('slug', 'community-outreach-program-volta-region')->sole();
    expect($volta->raisedCents())->toBe(4_300_00)
        ->and($volta->progressPercent())->toBe(43)
        ->and(Donation::query()->where('external_reference', 'like', 'SEED-%')->count())->toBe(30);
});

it('shows the seeded fundraisers as popular causes on the home page', function () {
    $this->seed(EventSeeder::class);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Find Popular Causes')
        ->assertSee('Community Outreach Program — Volta Region', escape: false)
        ->assertDontSee('Clean Water Borehole Project');
});

it('refuses to seed demo events in production', function () {
    app()->instance('env', 'production');

    expect(fn () => (new EventSeeder)->run())->toThrow(RuntimeException::class);
    expect(Event::query()->count())->toBe(0);
});
