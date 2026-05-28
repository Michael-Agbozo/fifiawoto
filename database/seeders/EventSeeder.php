<?php

namespace Database\Seeders;

use App\Enums\EventStatus;
use App\Models\Donation;
use App\Models\Event;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class EventSeeder extends Seeder
{
    public function run(): void
    {
        $featured = Event::query()->updateOrCreate(
            ['slug' => 'community-outreach-program-volta-region'],
            [
                'title' => 'Community Outreach Program — Volta Region',
                'starts_at' => now()->addWeeks(2)->setTime(9, 0),
                'ends_at' => now()->addWeeks(2)->addDays(2)->setTime(17, 0),
                'location' => 'Volta Region',
                'country' => 'Ghana',
                'description' => 'A multi-day outreach providing food distribution, education support, and empowerment programs for vulnerable families in the Volta Region of Ghana.',
                'activities' => "Food distribution to 200+ families\nSchool supplies for school-aged children\nMobile health clinic\nWomen's empowerment workshop",
                'expected_impact' => 'Direct assistance for at least 300 community members, with continuing follow-up support through the foundation\'s partner churches.',
                'volunteer_opportunities' => "Logistics & set-up\nProgramme facilitation\nMedia & photography\nChild care during workshops",
                'goal_cents' => 10_000_00,
                'status' => EventStatus::Published->value,
                'published_at' => now()->subDays(7),
            ],
        );

        Donation::factory()
            ->count(8)
            ->state(['amount_cents' => 50_000])
            ->for($featured)
            ->create();

        Donation::factory()
            ->count(1)
            ->state(['amount_cents' => 30_000])
            ->for($featured)
            ->create();

        Event::factory()
            ->count(4)
            ->sequence(fn ($s) => ['slug' => Str::slug('seed-event-'.$s->index)])
            ->create();

        Event::factory()
            ->count(3)
            ->past()
            ->sequence(fn ($s) => ['slug' => Str::slug('past-event-'.$s->index)])
            ->create();
    }
}
