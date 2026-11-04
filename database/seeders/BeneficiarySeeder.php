<?php

namespace Database\Seeders;

use App\Enums\AssistanceType;
use App\Enums\BeneficiaryApplicationStatus;
use App\Enums\SupportCategory;
use App\Enums\SupportStatus;
use App\Enums\TimelineEntryType;
use App\Models\Beneficiary;
use App\Models\BeneficiaryApplication;
use App\Models\BeneficiaryFolder;
use App\Models\BeneficiaryTimelineEntry;
use App\Models\Testimonial;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Fictional applications, beneficiaries and testimonials for local and staging sites.
 * Safe to rerun: records are keyed by name.
 */
class BeneficiarySeeder extends Seeder
{
    public function run(): void
    {
        if (app()->isProduction()) {
            throw new RuntimeException('Demo beneficiaries must not be seeded in production.');
        }

        foreach ($this->applications() as [$name, $country, $region, $type, $status, $situation]) {
            BeneficiaryApplication::query()->updateOrCreate(['full_name' => $name], [
                'phone' => '+233 20 000 0000',
                'email' => Str::slug($name, '.').'@example.com',
                'country' => $country,
                'region' => $region,
                'assistance_type' => $type->value,
                'situation' => $situation,
                'status' => $status->value,
            ]);
        }

        foreach ($this->beneficiaries() as [$name, $born, $gender, $country, $region, $category, $status, $description]) {
            $beneficiary = Beneficiary::query()->updateOrCreate(['full_name' => $name], [
                'date_of_birth' => $born,
                'gender' => $gender,
                'phone' => '+233 20 000 0000',
                'country' => $country,
                'region' => $region,
                'category' => $category->value,
                'status' => $status->value,
                'description' => $description,
            ]);

            foreach (['Medical Records', 'School Documents', 'Photos', 'Support Reports', 'Identification Documents'] as $folder) {
                BeneficiaryFolder::query()->firstOrCreate(
                    ['beneficiary_id' => $beneficiary->id, 'slug' => Str::slug($folder)],
                    ['name' => $folder],
                );
            }

            BeneficiaryTimelineEntry::query()->firstOrCreate(
                ['beneficiary_id' => $beneficiary->id, 'type' => TimelineEntryType::ApplicationReceived->value],
                ['description' => 'Initial intake captured.', 'occurred_at' => $beneficiary->created_at],
            );
        }

        foreach ($this->testimonials() as $sort => [$name, $role, $quote]) {
            Testimonial::query()->updateOrCreate(
                ['author_name' => $name],
                ['author_role' => $role, 'quote' => $quote, 'featured' => true, 'sort' => $sort],
            );
        }
    }

    /**
     * @return list<array{string, string, string, AssistanceType, BeneficiaryApplicationStatus, string}>
     */
    private function applications(): array
    {
        $new = BeneficiaryApplicationStatus::New;
        $review = BeneficiaryApplicationStatus::UnderReview;

        return [
            ['Afua Owusu', 'Ghana', 'Ho', AssistanceType::Widow, $new, 'Widowed last year and caring for three children. Looking for help with a small trading business.'],
            ['Komi Agbeko', 'Togo', 'Kpalimé', AssistanceType::Education, $new, 'Needs school fees and uniforms for two children starting the new term.'],
            ['Mawuli Dzide', 'Ghana', 'Keta', AssistanceType::Medical, $new, 'Requires help with the cost of follow-up treatment after an operation.'],
            ['Sena Houngbo', 'Benin', 'Cotonou', AssistanceType::Disability, $new, 'Requesting a replacement wheelchair so they can return to their market stall.'],
            ['Edem Kpodo', 'Ghana', 'Hohoe', AssistanceType::Community, $new, 'Our village would like support to repair the shared borehole.'],
            ['Yawa Amegah', 'Togo', 'Lomé', AssistanceType::Widow, $review, 'Widow supporting two grandchildren who needs help with household costs.'],
            ['Kafui Tsikata', 'Ghana', 'Aflao', AssistanceType::Education, $review, 'Bright student who needs help with exam fees and books.'],
            ['Rosine Adjovi', 'Benin', 'Ouidah', AssistanceType::Medical, BeneficiaryApplicationStatus::Rejected, 'Asked for help with a clinic bill, which has since been covered by another charity.'],
        ];
    }

    /**
     * @return list<array{string, string, string, string, string, SupportCategory, SupportStatus, string}>
     */
    private function beneficiaries(): array
    {
        $pending = SupportStatus::PendingReview;
        $active = SupportStatus::Active;

        return [
            ['Akosua Dogbe', '1968-03-14', 'female', 'Ghana', 'Ho', SupportCategory::WidowSupport, $pending, 'Widow who needs help restarting a small food business.'],
            ['Dela Agbenyega', '2013-09-02', 'male', 'Ghana', 'Kpando', SupportCategory::ChildEducation, $pending, 'Primary school pupil who needs fees, books and a uniform.'],
            ['Essi Lawson', '1975-06-21', 'female', 'Togo', 'Lomé', SupportCategory::Medical, $pending, 'Needs regular check-ups and medicine for a long-term condition.'],
            ['Gildas Sossou', '1990-11-30', 'male', 'Benin', 'Porto-Novo', SupportCategory::Disability, $pending, 'Needs a mobility aid and help getting back to work.'],
            ['Nana Yaa Boateng', '1959-01-09', 'female', 'Ghana', 'Accra', SupportCategory::Community, $pending, 'Community elder coordinating support for older women.'],
            ['Selasi Ahiable', '2011-04-17', 'female', 'Ghana', 'Sogakope', SupportCategory::ChildEducation, $pending, 'Junior high student at risk of leaving school because of costs.'],
            ['Adjo Mensah', '1964-08-05', 'female', 'Togo', 'Atakpamé', SupportCategory::WidowSupport, $active, 'Receives a monthly food parcel and business mentoring.'],
            ['Elom Fiagbe', '2012-12-11', 'male', 'Ghana', 'Ho', SupportCategory::ChildEducation, $active, 'School fees and supplies are covered for the full school year.'],
            ['Fifame Dossou', '1982-02-26', 'female', 'Benin', 'Abomey', SupportCategory::Medical, $active, 'Treatment costs are covered, with check-ins every quarter.'],
        ];
    }

    /**
     * @return list<array{string, string, string}>
     */
    private function testimonials(): array
    {
        return [
            ['Akua (Demo)', 'Community Member', 'Sample testimonial. When the team came to our town, they listened first and then helped with what we needed most.'],
            ['Daniel (Demo)', 'Volunteer', 'Sample testimonial. Volunteering on outreach days showed me how far a small, organised effort can go.'],
            ['Esinam (Demo)', 'Beneficiary', 'Sample testimonial. Help with school fees meant my children could stay in class this year.'],
            ['Grace (Demo)', 'Partner', 'Sample testimonial. Working with the foundation is easy because they are clear about where support goes.'],
        ];
    }
}
