<?php

namespace Database\Seeders;

use App\Enums\BeneficiaryApplicationStatus;
use App\Enums\SupportStatus;
use App\Enums\TimelineEntryType;
use App\Models\Beneficiary;
use App\Models\BeneficiaryApplication;
use App\Models\BeneficiaryFolder;
use App\Models\BeneficiaryTimelineEntry;
use App\Models\InstagramPost;
use App\Models\Testimonial;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class BeneficiarySeeder extends Seeder
{
    public function run(): void
    {
        BeneficiaryApplication::factory()->count(5)->create([
            'status' => BeneficiaryApplicationStatus::New->value,
        ]);
        BeneficiaryApplication::factory()->count(2)->create([
            'status' => BeneficiaryApplicationStatus::UnderReview->value,
        ]);
        BeneficiaryApplication::factory()->count(1)->create([
            'status' => BeneficiaryApplicationStatus::Rejected->value,
        ]);

        Beneficiary::factory()->count(6)->create()->each(function (Beneficiary $b) {
            foreach (['Medical Records', 'School Documents', 'Photos', 'Support Reports', 'Identification Documents'] as $name) {
                BeneficiaryFolder::query()->create([
                    'beneficiary_id' => $b->id,
                    'name' => $name,
                    'slug' => Str::slug($name),
                ]);
            }

            BeneficiaryTimelineEntry::query()->create([
                'beneficiary_id' => $b->id,
                'type' => TimelineEntryType::ApplicationReceived->value,
                'description' => 'Initial intake captured.',
                'occurred_at' => $b->created_at,
            ]);
        });

        Beneficiary::factory()->count(3)->state(['status' => SupportStatus::Active->value])->create()
            ->each(function (Beneficiary $b) {
                foreach (['Medical Records', 'School Documents', 'Photos', 'Support Reports', 'Identification Documents'] as $name) {
                    BeneficiaryFolder::query()->create([
                        'beneficiary_id' => $b->id,
                        'name' => $name,
                        'slug' => Str::slug($name),
                    ]);
                }
            });

        Testimonial::factory()->count(4)->state(['featured' => true])->create();
        InstagramPost::factory()->count(6)->create();
    }
}
