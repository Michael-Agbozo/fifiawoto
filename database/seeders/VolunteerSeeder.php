<?php

namespace Database\Seeders;

use App\Enums\VolunteerApplicationStatus;
use App\Enums\VolunteerAvailability;
use App\Enums\VolunteerInterest;
use App\Enums\VolunteerRole;
use App\Models\Volunteer;
use App\Models\VolunteerApplication;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Fictional volunteers and applications for local and staging sites.
 * Safe to rerun: records are keyed by email.
 */
class VolunteerSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->isProduction()) {
            throw new RuntimeException('Demo volunteers must not be seeded in production.');
        }

        $applications = [
            ['Abla Kudjo', 'Ghana', VolunteerApplicationStatus::New, VolunteerInterest::CommunityOutreach, VolunteerAvailability::Weekends],
            ['Bright Ofori', 'Ghana', VolunteerApplicationStatus::New, VolunteerInterest::EventSupport, VolunteerAvailability::Flexible],
            ['Clarisse Akue', 'Togo', VolunteerApplicationStatus::New, VolunteerInterest::EducationPrograms, VolunteerAvailability::Weekdays],
            ['Dodzi Avorgbedor', 'Ghana', VolunteerApplicationStatus::New, VolunteerInterest::MediaCommunications, VolunteerAvailability::Weekends],
            ['Emefa Klu', 'United States', VolunteerApplicationStatus::New, VolunteerInterest::AdministrativeSupport, VolunteerAvailability::Flexible],
            ['Fabrice Zinsou', 'Benin', VolunteerApplicationStatus::New, VolunteerInterest::CommunityOutreach, VolunteerAvailability::Weekends],
            ['Gifty Asante', 'Ghana', VolunteerApplicationStatus::Approved, VolunteerInterest::EventSupport, VolunteerAvailability::Weekends],
            ['Herve Gnansounou', 'Benin', VolunteerApplicationStatus::Approved, VolunteerInterest::EducationPrograms, VolunteerAvailability::Flexible],
            ['Irene Quarshie', 'Ghana', VolunteerApplicationStatus::Rejected, VolunteerInterest::MediaCommunications, VolunteerAvailability::Weekdays],
        ];

        foreach ($applications as [$name, $country, $status, $interest, $availability]) {
            VolunteerApplication::query()->updateOrCreate(['email' => Str::slug($name, '.').'@example.com'], [
                'full_name' => $name,
                'phone' => '+233 20 000 0000',
                'country' => $country,
                'interests' => [$interest->value],
                'availability' => $availability->value,
                'skills' => 'Organising, speaking with families and helping on event days.',
                'motivation' => 'I would like to give my time to the communities the foundation serves.',
                'consented_at' => now(),
                'status' => $status->value,
                'reviewed_at' => $status === VolunteerApplicationStatus::New ? null : now()->subDays(3),
            ]);
        }

        $volunteers = [
            VolunteerRole::Event->value => [['Jonas Tetteh', 'Ghana'], ['Kekeli Amenyo', 'Ghana'], ['Lucie Ayivi', 'Togo'], ['Marius Hounkpe', 'Benin']],
            VolunteerRole::CommunityOutreach->value => [['Nadia Addo', 'Ghana'], ['Olivier Koffi', 'Togo'], ['Peace Gadzekpo', 'Ghana']],
            VolunteerRole::Administrative->value => [['Queenie Sarpong', 'United States'], ['Richard Dey', 'Ghana']],
            VolunteerRole::Media->value => [['Sefakor Ameku', 'Ghana'], ['Thierry Agossou', 'Benin']],
        ];

        foreach ($volunteers as $role => $people) {
            foreach ($people as $weeksAgo => [$name, $country]) {
                Volunteer::query()->updateOrCreate(['email' => Str::slug($name, '.').'@example.com'], [
                    'full_name' => $name,
                    'phone' => '+233 20 000 0000',
                    'country' => $country,
                    'role' => $role,
                    'assigned_at' => now()->subWeeks($weeksAgo + 2),
                ]);
            }
        }
    }
}
