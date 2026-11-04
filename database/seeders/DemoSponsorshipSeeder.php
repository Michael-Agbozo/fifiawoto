<?php

namespace Database\Seeders;

use App\Enums\SupportCategory;
use App\Enums\SupportStatus;
use App\Models\Beneficiary;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class DemoSponsorshipSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->isProduction() && ! config('demo.live_content')) {
            throw new RuntimeException('Demo sponsorship profiles must not be created in production.');
        }

        $profiles = [
            ['Ama', 7, 'female', 'Ghana', 'reading and drawing', 'outreach-2.webp'],
            ['Kofi', 10, 'male', 'Ghana', 'football and science', 'outreach-3.webp'],
            ['Abena', 5, 'female', 'Togo', 'music and storytelling', 'school-donation.jpg'],
            ['Kwame', 13, 'male', 'Benin', 'mathematics and building things', 'outreach-1.jpg'],
            ['Esi', 9, 'female', 'Benin', 'art and exploring nature', 'about/empowering-hope.jpg'],
            ['Yaw', 16, 'male', 'Togo', 'technology and athletics', 'about/team-group.jpg'],
        ];

        foreach ($profiles as $index => [$name, $age, $gender, $country, $interests, $sourcePhoto]) {
            $uuid = 'dff00000-0000-4000-8000-'.str_pad((string) ($index + 1), 12, '0', STR_PAD_LEFT);
            $photoPath = 'sponsorship/demo-'.strtolower($name).'.'.pathinfo($sourcePhoto, PATHINFO_EXTENSION);
            Storage::disk('public')->put($photoPath, File::get(public_path('images/foundation/'.$sourcePhoto)));

            Beneficiary::query()->updateOrCreate(['public_sponsorship_id' => $uuid], [
                'full_name' => $name.' (Demo)',
                'date_of_birth' => today()->subYears($age)->subMonths(2),
                'gender' => $gender,
                'country' => $country,
                'category' => SupportCategory::ChildEducation,
                'status' => SupportStatus::Active,
                'description' => 'Fictional demonstration record. Replace with an approved real profile before launch.',
                'public_display_name' => $name.' (Demo)',
                'public_story' => $this->story($name, $interests),
                'public_community_story' => 'Community is part of a child’s everyday story: friendships, learning together and the encouragement of people who care.'
                    ."\n\n".'This demo section shows how staff can describe approved community activities and local support. It intentionally leaves out home addresses, school names and identifying family details.',
                'public_country_story' => 'This example profile is set in '.$country.'. The foundation can share approved country context and explain how local partners coordinate support.'
                    ."\n\n".'This is demonstration content. The photograph and fictional profile do not establish where the people pictured live.',
                'public_country' => $country,
                'public_photo_path' => $photoPath,
                'sponsorship_published' => true,
                'guardian_consent_at' => now(),
                'guardian_consent_reference' => 'DEMO FIXTURE ONLY — no real child or guardian consent represented.',
            ]);
        }
    }

    private function story(string $name, string $interests): string
    {
        return $name.' enjoys '.$interests.'. A chance to learn, explore and feel supported can help a child turn everyday curiosity into lasting confidence.'
            ."\n\n".'A good school day is about more than a classroom. It can mean having the right books, a comfortable uniform, and the encouragement to ask questions and try something new. This example profile shows the kind of everyday support a sponsor could discuss with our team.'
            ."\n\n".'We believe families should help shape the support they receive. The foundation can explain current priorities and coordinate a thoughtful arrangement, with any updates shared in a way that respects the family’s wishes.'
            ."\n\n".'Demo profile — this is a fictional child for previewing the sponsorship section. The outreach photograph is illustrative: the name, age, country and story do not identify or describe the people pictured. No real sponsorship opportunity or guardian consent is represented.';
    }
}
