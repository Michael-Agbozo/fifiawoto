<?php

namespace Database\Seeders;

use App\Enums\VolunteerApplicationStatus;
use App\Enums\VolunteerRole;
use App\Models\Volunteer;
use App\Models\VolunteerApplication;
use Illuminate\Database\Seeder;

class VolunteerSeeder extends Seeder
{
    public function run(): void
    {
        VolunteerApplication::factory()->count(6)->create([
            'status' => VolunteerApplicationStatus::New->value,
        ]);

        VolunteerApplication::factory()->count(2)->create([
            'status' => VolunteerApplicationStatus::Approved->value,
            'reviewed_at' => now()->subDays(3),
        ]);

        VolunteerApplication::factory()->count(1)->create([
            'status' => VolunteerApplicationStatus::Rejected->value,
            'reviewed_at' => now()->subDays(7),
        ]);

        Volunteer::factory()->count(4)->state(['role' => VolunteerRole::Event->value])->create();
        Volunteer::factory()->count(3)->state(['role' => VolunteerRole::CommunityOutreach->value])->create();
        Volunteer::factory()->count(2)->state(['role' => VolunteerRole::Administrative->value])->create();
        Volunteer::factory()->count(2)->state(['role' => VolunteerRole::Media->value])->create();
    }
}
